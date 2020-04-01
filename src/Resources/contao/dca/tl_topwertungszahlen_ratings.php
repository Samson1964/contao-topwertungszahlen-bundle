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
 * Table tl_topwertungszahlen_ratings
 */
$GLOBALS['TL_DCA']['tl_topwertungszahlen_ratings'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'             => 'Table',
		'ptable'					=> 'tl_topwertungszahlen',
		'enableVersioning'          => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'                            => 'primary',
				'pid'                           => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('datum DESC'),
			'flag'                    => 6,
			'headerFields'            => array('nachname', 'vorname', 'geburtstag'), 
			'panelLayout'             => 'sort,filter;search,limit',
			'child_record_callback'   => array('tl_topwertungszahlen_ratings', 'listPlayers'),
			'child_record_class'      => 'no_padding',
			'disableGrouping'         => true
		),
		'label' => array
		(
			'fields'                  => array(),
			'showColumns'             => false,
			'format'                  => '%s'
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
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_topwertungszahlen_ratings', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['show'],
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
		'default'                     => '{rating_legend},dwz,dwz_index,fide_id,fide_title,fide_title_w,fide_rating,fide_rating_rapid,fide_rating_blitz,datum;{publish_legend},published'
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
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'dwz' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['dwz'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(4) NOT NULL default ''"
		),
		'dwz_index' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['dwz_index'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(4) NOT NULL default ''"
		),
		'fide_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_id'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 16,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'fide_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_title'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 3,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'fide_title_w' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_title_w'],
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
		'fide_rating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_rating'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(4) NOT NULL default ''"
		),
		'fide_rating_rapid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_rating_rapid'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(4) NOT NULL default ''"
		),
		'fide_rating_blitz' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['fide_rating_blitz'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(4) NOT NULL default ''"
		),
		'datum' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['datum'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => date('d.m.Y'),
			'flag'                    => 5,
			'eval'                    => array
			(
				'rgxp'                => 'date',
				'datepicker'          => true,
				'tl_class'            => 'w50 clr wizard' 
			),
			'sql'                     => "varchar(11) NOT NULL default ''" 
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['published'],
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
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array
 */
class tl_topwertungszahlen_ratings extends Backend
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
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_topwertungszahlen_ratings::published', 'alexf'))
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
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_topwertungszahlen_ratings::published', 'alexf'))
		{
			$this->log('Not enough permissions to show/hide record ID "'.$intId.'"', 'tl_topwertungszahlen_ratings toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}
		
		$this->createInitialVersion('tl_topwertungszahlen_ratings', $intId);
		
		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_topwertungszahlen_ratings']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_topwertungszahlen_ratings']['fields']['published']['save_callback'] as $callback)
			{
				$this->import($callback[0]);
				$blnPublished = $this->$callback[0]->$callback[1]($blnPublished, $this);
			}
		}
		
		// Update the database
		$this->Database->prepare("UPDATE tl_topwertungszahlen_ratings SET tstamp=". time() .", published='" . ($blnPublished ? '' : '1') . "' WHERE id=?")
		               ->execute($intId);
		$this->createNewVersion('tl_topwertungszahlen_ratings', $intId);
	}

    /**
     * Generiere eine Zeile als HTML
     * @param array
     * @return string
     */
    public function listPlayers($arrRow)
    {
        $line = '';
        $line .= '<div>';
        $line .= '<b>'.date('d.m.Y', $arrRow['datum']).'</b>';
        if($arrRow['dwz']) $line .= ' - DWZ '.$arrRow['dwz'];
        if($arrRow['fide_rating']) $line .= ' - Elo '.$arrRow['fide_rating'];
        $line .= "</div>";
        $line .= "\n";
        return($line);

    }

}
