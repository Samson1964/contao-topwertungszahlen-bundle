<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package News
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Table tl_topwertungszahlen_photos
 */
$GLOBALS['TL_DCA']['tl_topwertungszahlen_photos'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_topwertungszahlen',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('date DESC'),
			'headerFields'            => array('nachname', 'vorname', 'geburtstag'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_topwertungszahlen_photos', 'listImages'),
			'disableGrouping'         => true
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{image_legend},hinweis,date,singleSRC,source;{publish_legend},published'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Reines Anzeigefeld ohne Datenbankspalte: Es erklärt oberhalb der
		// Dateiauswahl, wie ein brauchbares Spielerfoto aussieht. Ohne
		// 'exclude', damit es jeder Redakteur sieht — es gibt daran nichts zu
		// berechtigen.
		'hinweis' => array
		(
			'input_field_callback'    => array('tl_topwertungszahlen_photos', 'fotohinweis')
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array
			(
				'mandatory'           => true,
				'filesOnly'           => true,
				'fieldType'           => 'radio',
				'extensions'          => 'jpg,jpeg,png,gif',
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "binary(16) NULL"
		), 
		'date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['date'],
			'exclude'                 => true,
			'sorting'                 => true,
			'default'                 => date('Ymd'),
			'flag'                    => 12,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => true,
				'tl_class'            => 'w50 clr',
				'maxlength'           => 10
			),
			'load_callback'           => array
			(
				array('\Schachbulle\ContaoHelperBundle\Classes\Helper', 'getDate')
			),
			'save_callback' => array
			(
				array('\Schachbulle\ContaoHelperBundle\Classes\Helper', 'putDate')
			),
			'sql'                     => "varchar(8) NOT NULL default ''"
		), 
		'source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['source'],
			'exclude'                 => true,
			'search'                  => true,
			'default'                 => 'Foto: ',
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 128,
				'tl_class'            => 'long clr'
			),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['published'],
			'default'                 => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'tl_class'            => 'w50',
				'isBoolean'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);


/**
 * Class tl_topwertungszahlen_photos
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_topwertungszahlen_photos extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Zeigt über der Dateiauswahl, welche Anforderungen an ein Spielerfoto
	 * gestellt werden.
	 *
	 * Die Vorgaben stehen als Liste in der Sprachdatei, daneben eine
	 * schematische Zeichnung des Bildausschnitts. Die Zeichnung ist bewußt ein
	 * eingebettetes SVG und keine Bilddatei: So braucht es kein
	 * `contao:assets:install` nach dem Einspielen, und die Maßangaben in der
	 * Zeichnung bleiben bei jeder Bildschirmgröße scharf.
	 *
	 * @param Contao\DataContainer $dc     Wird nicht ausgewertet — das Feld
	 *                                     zeigt für jeden Datensatz dasselbe
	 * @param string               $xlabel Zusatz, den Contao an Beschriftungen
	 *                                     hängt (hier ohne Bedeutung)
	 *
	 * @return string HTML-Block für das Eingabeformular
	 */
	public function fotohinweis($dc = null, $xlabel = '')
	{
		$lang = $GLOBALS['TL_LANG']['tl_topwertungszahlen_photos'] ?? array();

		$str = '<div class="widget long clr" style="margin-bottom:18px;">';
		$str .= '<h3 style="margin-bottom:6px;">'.($lang['hinweis_legend'] ?? 'Anforderungen an das Foto').'</h3>';
		$str .= '<div style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap;">';
		$str .= '<div style="flex:0 0 auto;">'.$this->fotoschema().'</div>';
		$str .= '<div style="flex:1 1 320px;min-width:280px;">';
		$str .= '<ul style="margin:0 0 8px 18px;padding:0;list-style:disc;line-height:1.7;">';

		foreach (($lang['hinweis_punkte'] ?? array()) as $punkt)
		{
			$str .= '<li>'.$punkt.'</li>';
		}

		$str .= '</ul>';
		$str .= '<p class="tl_help" style="margin:0;">'.($lang['hinweis_beispiel'] ?? '').'</p>';
		$str .= '</div></div></div>';

		return $str;
	}

	/**
	 * Zeichnet den gewünschten Bildausschnitt als SVG.
	 *
	 * Maßstäblich zu den Vorgaben: Der Kopf reicht von der Bildmitte bis kurz
	 * unter den oberen Rand, der Oberkörper endet an der Brust am unteren
	 * Rand, und links wie rechts bleibt neben den Armen Platz.
	 *
	 * @return string SVG-Element, 200 mal 200 Pixel
	 */
	private function fotoschema()
	{
		$svg = '<svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Schema des Bildausschnitts">';
		$svg .= '<rect x="0.5" y="0.5" width="199" height="199" fill="#f7f7f7" stroke="#a3a3a3"/>';

		// Arme: heller als der Rumpf, damit sie sich davon abheben, und mit
		// Abstand zum Bildrand — genau das ist die Vorgabe
		$svg .= '<path d="M52 132 h-18 a10 10 0 0 0 -10 10 v58 h28 z" fill="#dfe9f0" stroke="#7b98ad"/>';
		$svg .= '<path d="M148 132 h18 a10 10 0 0 1 10 10 v58 h-28 z" fill="#dfe9f0" stroke="#7b98ad"/>';

		// Der freie Streifen neben den Armen, damit der Abstand ins Auge fällt
		$svg .= '<line x1="24" y1="176" x2="0" y2="176" stroke="#3f7ba7" stroke-width="1"/>';
		$svg .= '<line x1="176" y1="176" x2="200" y2="176" stroke="#3f7ba7" stroke-width="1"/>';
		$svg .= '<circle cx="12" cy="176" r="2" fill="#3f7ba7"/><circle cx="188" cy="176" r="2" fill="#3f7ba7"/>';

		// Oberkörper bis zur Brust am unteren Rand
		$svg .= '<path d="M60 200 v-64 a16 16 0 0 1 12 -15 l28 -7 l28 7 a16 16 0 0 1 12 15 v64 z" fill="#c8d6e0" stroke="#7b98ad"/>';

		// Hals und Kopf, Kopf endet unten genau auf der Bildmitte
		$svg .= '<rect x="88" y="90" width="24" height="26" fill="#e6d3c4" stroke="#b79c88"/>';
		$svg .= '<circle cx="100" cy="62" r="38" fill="#e6d3c4" stroke="#b79c88"/>';

		// Augen: Blick in die Kamera
		$svg .= '<circle cx="87" cy="56" r="3.5" fill="#4a4a4a"/><circle cx="113" cy="56" r="3.5" fill="#4a4a4a"/>';

		// Hilfslinien mit Beschriftung
		$svg .= '<line x1="0" y1="100" x2="200" y2="100" stroke="#3f7ba7" stroke-width="1" stroke-dasharray="5 4"/>';
		$svg .= '<text x="4" y="112" font-size="11" fill="#3f7ba7">Bildmitte</text>';
		$svg .= '<line x1="0" y1="24" x2="200" y2="24" stroke="#3f7ba7" stroke-width="1" stroke-dasharray="5 4"/>';
		$svg .= '<text x="4" y="19" font-size="11" fill="#3f7ba7">Scheitel kurz vor dem Rand</text>';
		$svg .= '</svg>';

		return $svg;
	}

	public function listImages($arrRow)
	{
		// Bild extrahieren
		$objFile = \FilesModel::findByPk($arrRow['singleSRC']);
		$thumbnail = Image::get($objFile->path, 160, 160, 'crop');

		$temp = '<div class="tl_content_left" style="min-width:300px">';
		$temp .= '<img src="' . $thumbnail . '" width="160" height="160" style="float:left; margin-right:5px" />';
		$temp .= 'Datei: <b>' . $objFile->path . '</b><br>';
		$temp .= 'Datum: <b>' . \Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($arrRow['date']) . '</b><br>';
		$temp .= 'Quelle: <b>' . $arrRow['source'] . '</b>';
		return $temp.'</div>';
	}

}
