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
 * Table tl_topwertungszahlen
 */
$GLOBALS['TL_DCA']['tl_topwertungszahlen'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'             => 'Table',
		'ctable'                    => array('tl_topwertungszahlen_ratings'),
		'enableVersioning'          => true,
		'onload_callback' => array
		(
			array('tl_topwertungszahlen', 'applyAdvancedFilter'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id'                            => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('nachname', 'vorname'),
			'panelLayout'             => 'myfilter;filter;search,sort,limit',
			'panel_callback'          => array('myfilter' => array('tl_topwertungszahlen', 'generateAdvancedFilter')),
			'flag'                    => 11,
			'disableGrouping'         => true,
		),
		'label' => array
		(
			'fields'                  => array('nachname', 'vorname', 'geburtstag', 'foto'),
			'showColumns'             => true,
			'format'                  => '%s',
			'label_callback'          => array('tl_topwertungszahlen', 'viewLabels'),
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'global_operations' => array
		(
			'importRating' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['importRating'],
				'href'                => 'key=importRating',
				'icon'                => 'bundles/contaotopwertungszahlen/images/import.png',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['tl_topwertungszahlen']['importRating_confirm'] . '\'))return false;Backend.getScrollOffset()"',
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['edit'],
				'href'                => 'table=tl_topwertungszahlen_ratings',
				'icon'                => 'bundles/contaotopwertungszahlen/images/rating-icon.png',
			),
			'editPhoto' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['editPhoto'],
				'href'                => 'table=tl_topwertungszahlen_photos',
				'icon'                => 'bundles/contaotopwertungszahlen/images/foto-icon.png',
			),
			'editHeader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['editHeader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
			), 
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_topwertungszahlen', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Select
	'select' => array
	(
		'buttons_callback' => array()
	),

	// Edit
	'edit' => array
	(
		'buttons_callback' => array()
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array(''),
		'default'                     => '{name_legend},vorname,nachname,geburtstag,geschlecht,dewis_id;{publish_legend},published'
	),

	// Subpalettes
	'subpalettes' => array
	(
		''                            => ''
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'actRecord' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'vorname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['vorname'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 64,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'nachname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['nachname'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 64,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'titel' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['titel'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'geburtstag' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['geburtstag'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'flag'                    => 5,
			'default'                 => '',
			'eval'                    => array
			(
				'rgxp'                => 'date',
				'datepicker'          => true,
				'tl_class'            => 'w50 wizard'
			),
			'sql'                     => "varchar(11) NOT NULL default ''" 
		),
		'geschlecht' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['geschlecht'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('male', 'female', 'other'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array
			(
				'includeBlankOption'  => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(32) NOT NULL default ''" 
		),
		'dewis_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['dewis_id'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => true,
				'maxlength'           => 16,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(16) unsigned NOT NULL default '0'"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['published'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'tl_class'            => 'w50',
				'isBoolean'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'foto' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen']['foto'],
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array
 */
class tl_topwertungszahlen extends Backend
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
	 * Ändert das Aussehen des Toggle-Buttons.
	 * @param $row
	 * @param $href
	 * @param $label
	 * @param $title
	 * @param $icon
	 * @param $attributes
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$this->import('BackendUser', 'User');

		if (strlen($this->Input->get('tid')))
		{
			$this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 0));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_topwertungszahlen::published', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;id='.$this->Input->get('id').'&amp;tid='.$row['id'].'&amp;state='.$row[''];

		if (!$row['published'])
		{
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}

	/**
	 * Toggle the visibility of an element
	 * @param integer
	 * @param boolean
	 */
	public function toggleVisibility($intId, $blnPublished)
	{
		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_topwertungszahlen::published', 'alexf'))
		{
			$this->log('Not enough permissions to show/hide record ID "'.$intId.'"', 'tl_topwertungszahlen toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$this->createInitialVersion('tl_topwertungszahlen', $intId);

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_topwertungszahlen']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_topwertungszahlen']['fields']['published']['save_callback'] as $callback)
			{
				$this->import($callback[0]);
				$blnPublished = $this->$callback[0]->$callback[1]($blnPublished, $this);
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_topwertungszahlen SET tstamp=". time() .", published='" . ($blnPublished ? '' : '1') . "' WHERE id=?")
		               ->execute($intId);
		$this->createNewVersion('tl_topwertungszahlen', $intId);
	}

	/**
	 * Zeigt zu einem Datensatz das aktuelle Foto an
	 *
	 * @param array                $row
	 * @param string               $label
	 * @param Contao\DataContainer $dc
	 * @param array                $args        Index 6 ist das Feld lizenzen
	 *
	 * @return array
	 */
	public function viewLabels($row, $label, Contao\DataContainer $dc, $args)
	{

		// Aktuelles Spielerfoto ermitteln
		$foto = \Database::getInstance()->prepare("SELECT * FROM tl_topwertungszahlen_photos WHERE pid=? ORDER BY date DESC")
		                                ->limit(1)
		                                ->execute($row['id']);
		if($foto->numRows)
		{
			// Foto vorhanden
			$bildid = $foto->singleSRC;
			$objFile = \FilesModel::findByPk($foto->singleSRC);
			$pfad = $objFile->path;
			$quelle = $foto->source; // Nur das funktioniert im Moment
			$thumbnail = \Image::get($objFile->path, 20, 20, 'crop');
			$str = '<img src="' . $thumbnail . '" width="20" height="20" style="margin-right:5px">'.\Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($foto->date);
		}
		else
		{
			// Kein Foto vorhanden
			$str = '';
		}

		$args[3] = $str;
		// Datensatz komplett zurückgeben
		return $args;
	}

	public function generateAdvancedFilter(DataContainer $dc)
	{

		if(\Input::get('id') > 0) return '';

		$session = \Session::getInstance()->getData();

		// Filters
		$arrFilters = array
		(
			'ttwz_filter'   => array
			(
				'name'    => 'ttwz_filter',
				'label'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_extended'],
				'options' => array
				(
					'1'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_alle'],
					'2'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_alle'],
					'3'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_u20'],
					'4'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_u20'],
					'5'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_m_ab60'],
					'6'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_dwz_w_ab60'],
					'7'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_alle'],
					'8'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_alle'],
					'9'   => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_u20'],
					'10'  => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_u20'],
					'11'  => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_m_ab60'],
					'12'  => $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter_elo_w_ab60'],
				)
			),
		);

        $strBuffer = '
<div class="tl_filter ttwz_filter tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['tl_topwertungszahlen']['filter'] . ':</strong> ' . "\n";

        // Generate filters
        foreach ($arrFilters as $arrFilter)
        {
            $strOptions = '
  <option value="' . $arrFilter['name'] . '">' . $arrFilter['label'] . '</option>
  <option value="' . $arrFilter['name'] . '">---</option>' . "\n";

            // Generate options
            foreach ($arrFilter['options'] as $k => $v)
            {
                $strOptions .= '  <option value="' . $k . '"' . (($session['filter']['tl_topwertungszahlenFilter'][$arrFilter['name']] === (string) $k) ? ' selected' : '') . '>' . $v . '</option>' . "\n";
            }

            $strBuffer .= '<select name="' . $arrFilter['name'] . '" id="' . $arrFilter['name'] . '" class="tl_select' . (isset($session['filter']['tl_topwertungszahlenFilter'][$arrFilter['name']]) ? ' active' : '') . '">
' . $strOptions . '
</select>' . "\n";
        }

        return $strBuffer . '</div>';

	}

	public function applyAdvancedFilter()
	{

		$session = \Session::getInstance()->getData();

		//echo "<pre>";
		//print_r($session);
		//echo "</pre>";
		
		// Filterwerte in der Sitzung speichern
		foreach($_POST as $k => $v)
		{
			if(substr($k, 0, 5) != 'ttwz_')
			{
				continue;
			}

			// Filter zurücksetzen
			if($k == \Input::post($k))
			{
				unset($session['filter']['tl_topwertungszahlenFilter'][$k]);
			}
			// Filter zuweisen
			else
			{
				$session['filter']['tl_topwertungszahlenFilter'][$k] = \Input::post($k);
			}
		}

		$this->Session->setData($session);

		if(\Input::get('id') > 0 || !isset($session['filter']['tl_topwertungszahlenFilter']))
		{
			return;
		}

		$arrPlayers = null;

		switch($session['filter']['tl_topwertungszahlenFilter']['ttwz_filter'])
		{
			case '1': $typ = 'dwz_alle'; break; // Alle Spieler
			case '2': $typ = 'dwz_w'; break; // Alle Frauen
			case '3': $typ = 'dwz_u20'; break; // Alle U18
			case '4': $typ = 'dwz_u20w'; break; // Alle U18w
			case '5': $typ = 'dwz_60+'; break; // Alle Ü60
			case '6': $typ = 'dwz_60w+'; break; // Alle Ü60w
			case '7': $typ = 'elo_alle'; break; // Alle Spieler
			case '8': $typ = 'elo_w'; break; // Alle Frauen
			case '9': $typ = 'elo_u20'; break; // Alle U18
			case '10': $typ = 'elo_u20w'; break; // Alle U18w
			case '11': $typ = 'elo_60+'; break; // Alle Ü60+
			case '12': $typ = 'elo_60w+'; break; // Alle Ü60w+
			default: $typ = '';
		}

		if($typ)
		{
			$objPlayers = \Database::getInstance()->prepare("SELECT tl_topwertungszahlen.id FROM tl_topwertungszahlen LEFT JOIN tl_topwertungszahlen_ratings ON tl_topwertungszahlen_ratings.pid = tl_topwertungszahlen.id WHERE tl_topwertungszahlen_ratings.type = ? AND tl_topwertungszahlen_ratings.published = ? ORDER BY tl_topwertungszahlen_ratings.date DESC, tl_topwertungszahlen_ratings.rank ASC")
			                                      ->limit(15)
			                                      ->execute($typ, 1);
			$arrPlayers = is_array($arrPlayers) ? array_intersect($arrPlayers, $objPlayers->fetchEach('id')) : $objPlayers->fetchEach('id');
		}
		
		if(is_array($arrPlayers) && empty($arrPlayers))
		{
			$arrPlayers = array(0);
		}

		$log = print_r($arrPlayers, true);
		log_message($log, 'topwertungszahlen.log');

		$GLOBALS['TL_DCA']['tl_topwertungszahlen']['list']['sorting']['root'] = $arrPlayers;

	}

}
