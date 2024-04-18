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
		'ptable'                    => 'tl_topwertungszahlen',
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
			'fields'                  => array('date DESC'),
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
				'label'                => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['toggle'],
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
		'default'                     => '{rating_legend},type,date,rank,rating,rating_info,rating_id,association;{fide_legend},fide_title,fide_title_w;{publish_legend},published'
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
		'type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['type'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 8,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(8) NOT NULL default ''"
		),
		'date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['date'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'flag'                    => 5,
			'eval'                    => array
			(
				'tl_class'            => 'w50 clr' 
			),
			'sql'                     => "varchar(8) NOT NULL default ''" 
		),
		'rank' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['rank'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'rating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['rating'],
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
		'rating_info' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['rating_info'],
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
		'rating_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['rating_id'],
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
		'association' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['association'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'inputType'               => 'select',
			'options'                 => $GLOBALS['TL_LANG']['tl_topwertungszahlen_ratings']['associations'],
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(1) NOT NULL default ''"
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
	 * Generiere eine Zeile als HTML
	 * @param array
	 * @return string
	 */
	public function listPlayers($arrRow)
	{
		$line = '';
		$line .= '<div>';
		$line .= 'Datum '.$arrRow['date'];
		if($arrRow['type']) $line .= ' - Liste '.$arrRow['type'];
		if($arrRow['rank']) $line .= ' - Platz '.$arrRow['rank'];
		if($arrRow['rating']) $line .= ' - Rating '.$arrRow['rating'];
		$line .= "</div>";
		$line .= "\n";
		return($line);
	}

}
