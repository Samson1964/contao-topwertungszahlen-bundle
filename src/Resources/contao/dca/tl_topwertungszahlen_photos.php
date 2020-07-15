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
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_topwertungszahlen_photos']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
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
		'default'                     => '{image_legend},date,singleSRC,source;{publish_legend},published'
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
				'rgxp'                => 'digit',
				'tl_class'            => 'w50 clr',
				'maxlength'           => 8
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

	public function listImages($arrRow)
	{
		// Bild extrahieren
		$objFile = \FilesModel::findByPk($arrRow['singleSRC']);
		$thumbnail = Image::get($objFile->path, 120, 160, 'crop');

		$temp = '<div class="tl_content_left" style="min-width:300px">';
		$temp .= '<img src="' . $thumbnail . '" width="120" height="160" style="float:left; margin-right:5px" />';
		$temp .= 'Datei: <b>' . $objFile->path . '</b><br>';
		$temp .= 'Datumskennung: <b>' . $arrRow['date'] . '</b><br>';
		$temp .= 'Quelle: <b>' . $arrRow['source'] . '</b>';
		return $temp.'</div>';
	}

}
