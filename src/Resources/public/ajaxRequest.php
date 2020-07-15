<?php
ini_set('display_errors', '1');

// MyClass.php

define('TL_SCRIPT', 'bundles/contaolizenzverwaltung/ajaxRequest.php');
define('TL_MODE', 'FE');

require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Instantiate controller
 */
$ajax = new ajaxRequest();
$ajax->run();

class ajaxRequest
{

	var $organization;
	var $host;
	var $username;
	var $password;
	var $training_course_id;

	function __construct()
	{
		$this->organization = '1093';
		$this->host = LIMS_HOST;
		$this->username = LIMS_USERNAME;
		$this->password = LIMS_PASSWORD;
		$this->training_course_id = array
		(
			'A'              => 515, // T-A/L > Schach
			'B'              => 514, // T-B/L > Schach
			'C'              => 513, // T-C/L > Schach
			'C-B'            => 512, // T-C/B > Schach
			'C-Sonderlizenz' => 512, // T-C/B > Schach
			'F'              => 512, // fiktiv, nicht angelegt beim DOSB
			'F/C'            => 512, // fiktiv, nicht angelegt beim DOSB
			'J'              => 512, // fiktiv, nicht angelegt beim DOSB
			'AB-Z'           => 49337, // Ausbilder-Zertifikat
		);
	}

	public function run()
	{

		if(\Input::get('acid') == 'lizenzverwaltung')
		{

			// Anfügen der Methode für das Erstellen/Aktualisieren einer Lizenz
			$host = $this->host.'request';

			// Datensatz laden
			$result = \Database::getInstance()->prepare("SELECT * FROM tl_lizenzverwaltung LEFT JOIN tl_lizenzverwaltung_items ON tl_lizenzverwaltung_items.pid = tl_lizenzverwaltung.id WHERE tl_lizenzverwaltung_items.id = ?")
			                                  ->limit(1)
			                                  ->execute(\Input::get('record'));

			// Auswerten
			if($result->numRows)
			{
				// Letztes Verlängerungsdatum ermitteln
				$verlaengerung = \Schachbulle\ContaoLizenzverwaltungBundle\Classes\Helper::getVerlaengerung($result->erwerb, $result->verlaengerungen);

				// Datenpaket aufbereiten
				$data = array
				(
					'firstname'          => $result->vorname,
					'lastname'           => $result->name,
					'academic_title'     => $result->titel,
					'birthdate'          => $result->geburtstag, // Geburtstag als Unixzeit
					'gender'             => $result->geschlecht,
					'street'             => $result->strasse,
					'city'               => $result->ort,
					'postal'             => $result->plz,
					'mail'               => $result->email,
					'training_course_id' => $this->training_course_id[$result->lizenz], // Ausbildungsgang
					'valid_until'        => $result->gueltigkeit, // Lizenz gültig bis als Unixzeit
					'issue_date'         => $verlaengerung, // Verlängerungsdatum
					'issue_place'        => 'Berlin', // Ausstellungsort
					'honor_code'         => (int)$result->codex, // Ehrenkodex
					'honor_code_date'    => $result->codex_date, // Datum Ehrenkodex
					'first_aid'          => (int)$result->help, // Erste-Hilfe-Ausbildung
					'first_aid_date'     => $result->help_date, // Datum der Erste-Hilfe-Ausbildung
				);
				// Restliche Werte ergänzen
				if($result->license_number_dosb)
				{
					$data['license_number_dosb'] = $result->license_number_dosb; // Aktive DOSB-Lizenznummer
				}
				else
				{
					$data['organisation_id']     = 1093; // Organisationsnummer DSB
					$data['first_issue_date']    = $result->erwerb; // Erstausstellungsdatum
				}

				$additionalHeaders = '';
				$process = curl_init($host);

				//hier ist auch noch application/xml möglich
				curl_setopt($process, CURLOPT_HTTPHEADER, array
				(
				  'Accept: application/json',
				  $additionalHeaders
				));
				curl_setopt($process, CURLOPT_HEADER, 1);
				curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->password);
				curl_setopt($process, CURLOPT_TIMEOUT, 30);
				curl_setopt($process, CURLOPT_POST, 1);
				curl_setopt($process, CURLOPT_POSTFIELDS, $data);
				curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
				//nur für test zwecke
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
				//request ausführen
				$response = curl_exec($process);

				$errors = NULL;
				if (curl_errno($process))
				{
					$errors = 'Curl error: ' . curl_error($process);
				}

				$header_size = curl_getinfo($process, CURLINFO_HEADER_SIZE);
				$httpCode = curl_getinfo($process, CURLINFO_HTTP_CODE); // HTTP-Code der Abfrage
				$header = substr($response, 0, $header_size);
				$body = substr($response, $header_size);//json_decode(substr($response, $header_size));

				if($errors)
				{
					// Fehlermeldung in Datenbank eintragen
				}
				else
				{
					$data = json_decode($body);

					if(is_object($data) && $httpCode == 200)
					{
						// Datensatz aktualisieren
						$set = array(
							'license_number_dosb' => $data->license_number_dosb,
							'lid'                 => $data->lid,
						);
						$erg = \Database::getInstance()->prepare("UPDATE tl_lizenzverwaltung_items %s WHERE id=?")
						                               ->set($set)
						                               ->execute($result->id);
						$httpText = 'OK';
					}
					else
					{
						$httpText = substr($body, 2, strlen($body) - 4);
					}
					// Abrufinformationen ergänzen
					$set = array(
						'dosb_tstamp'         => time(),
						'dosb_code'           => $httpCode,
						'dosb_antwort'        => $httpText,
					);
					$erg = \Database::getInstance()->prepare("UPDATE tl_lizenzverwaltung_items %s WHERE id=?")
					                               ->set($set)
					                               ->execute($result->id);

				}

				// Sitzung anlegen/initialisieren
				$session = \Session::getInstance();
				$count = $session->get('lizenzverwaltung_counter') + 1;
				$session->set('lizenzverwaltung_counter', $count);

			}

			$return = array
			(
				'css_id' => '#export_'.$result->id,
				'color'  => $httpCode == 200 ? '#008000' : '#CA0000',
				'text'   => ' ('.$httpCode.' '.$httpText.')',
				'datum'  => '[<i>'.date('d.m.Y H:i:s').'</i>] '
			);

			echo json_encode($return);
		}
	}
}
