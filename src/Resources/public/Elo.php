<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   Elo
 * @author    Frank Hoppe
 * @license   GNU/LPGL
 * @copyright Frank Hoppe 2016
 */


/**
 * Namespace
 */
namespace Samson\Elo;

/**
 * Class Elo
 *
 * @copyright  Frank Hoppe 2016
 * @author     Frank Hoppe
 * @package    Devtools
 */
class Elo extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_elo';
	var $cache = false;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ELO-LISTE ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		
		return parent::generate(); // Weitermachen mit dem Modul
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$cachetime = 3600 * 24 * 40; // 40 Tage

		// Aktuelle Liste ermitteln
		$objActiv = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
		                                    ->limit(1)
		                                    ->execute(1);

		// Cache initialisieren
		$this->cache = new \Samson\Helper\Cache('Elo');
		$this->cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

		$this->Template->headline = 'FIDE-Ratingliste Deutschland';
		$this->Template->hl = 'h2';
		$this->Template->datum = date('d.m.Y', $objActiv->datum).' ('.$objActiv->title.')';

		$this->Template->count = $this->elo_topcount;
		$this->Template->eloN = $this->getEloliste($objActiv->id, 'eloN', $this->elo_topcount);
		$this->Template->eloB = $this->getEloliste($objActiv->id, 'eloB', $this->elo_topcount);
		$this->Template->eloR = $this->getEloliste($objActiv->id, 'eloR', $this->elo_topcount);
		$this->Template->eloNw = $this->getEloliste($objActiv->id, 'eloNw', $this->elo_topcount);
		$this->Template->eloBw = $this->getEloliste($objActiv->id, 'eloBw', $this->elo_topcount);
		$this->Template->eloRw = $this->getEloliste($objActiv->id, 'eloRw', $this->elo_topcount);

	}

	/**
	 * Eloliste aus Datenbank laden
	 * @param integer $listid         ID der Eloliste
	 * @param string $listtyp         Typ der Eloliste
	 * @param integer $count          Anzahl der Topeinträge der Eloliste
	 * @return array                  Gefundene Datensätze
	 */
	function getEloliste($listid, $listtype, $count)
	{

		switch($listtype)
		{
			case 'eloN':
				$sql = 'ORDER BY rating DESC';
				break;
			case 'eloB':
				$sql = 'ORDER BY blitz_rating DESC';
				break;
			case 'eloR':
				$sql = 'ORDER BY rapid_rating DESC';
				break;
			case 'eloNw':
				$sql = 'AND sex=\'F\' ORDER BY rating DESC';
				break;
			case 'eloBw':
				$sql = 'AND sex=\'F\' ORDER BY blitz_rating DESC';
				break;
			case 'eloRw':
				$sql = 'AND sex=\'F\' ORDER BY rapid_rating DESC';
				break;
			default:
		}

		$cachekey = $listtype.'_'.$count.'_'.$listid;

		if($this->cache->isCached($cachekey))
		{
			// Daten aus dem Cache laden
			$result = $this->cache->retrieve($cachekey);
		}
		else
		{
			// Daten aus der Datenbank laden
			$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sql)
			                                  ->limit($count)
			                                  ->execute($listid, 1, '%i%');
			// Elo zuweisen
			if($objElo->numRows > 1)
			{
				$result = array();
				// Datensätze anzeigen
				while($objElo->next()) 
				{
					$line = $objElo->intent;
					$line .= ($line) ? ' '.$objElo->prename : $objElo->prename; 
					$line .= ($line) ? ' '.$objElo->surname : $objElo->surname; 
					$result[] = array
					(
						'name' 	=> $line,
						'elo'  	=> (substr($listtype,0,4) == 'eloN') ? $objElo->rating : ((substr($listtype,0,4) == 'eloB') ? $objElo->blitz_rating : $objElo->rapid_rating),
						'fid' 	=> $objElo->fideid,
						'title'	=> ($objElo->title) ? $objElo->title . ' ' : (($objElo->w_title) ? $objElo->w_title . ' ': ''),
					);
				}
				// Daten im Cache speichern
				$this->cache->store($cachekey, $result, $cachetime);
			}
		}

		return $result;
	}
}
