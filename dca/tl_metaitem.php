<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');
/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.

 * PHP version 5
 * @copyright  Ueli Kunz 2011
 * @author     Ueli Kunz <kunz@ideadapt.net>
 * @package    ia.contao.system.modules.meta_editor
 * @license    LGPL
 */


/**
 * @copyright  Ueli Kunz 2011
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */


/**
 * Table tl_metaitem 
 */
$ptable = $GLOBALS['BE_MOD']['content']['meta_editor']['tables'][0];
$ctable = $GLOBALS['BE_MOD']['content']['meta_editor']['tables'][1];
$GLOBALS['TL_DCA'][$ctable] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'closed'					  => true,
		'ptable'					  => $ptable,
		'onsubmit_callback' => array
		(
			array('meta_editor', 'onsubmit_metaitem')
		),
		'ondelete_callback' => array
		(
			array('meta_editor', 'ondelete_metaitem')
		),
		'oncut_callback' => array
		(
			array('meta_editor', 'oncut_metaitem')
		),
		'onload_callback' => array(
			array('meta_editor', 'onload_metaitem')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'filter'                  => true,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'limit',
			'headerFields'            => array('title', 'folder', 'language', 'metatype'),
			'child_record_callback'   => array('meta_editor', 'list_metaitem')
		),
		'global_operations' => array
		(
			'scanimport' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ctable]['scanimport'],
				'href'                => 'key=scanimport',
				'class'               => 'header_scanimport',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
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
				'label'               => &$GLOBALS['TL_LANG'][$ctable]['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
// can be done by drag&drop            
//			'cut' => array
//			(
//				'label'               => &$GLOBALS['TL_LANG'][$ctable]['cut'],
//				'href'                => 'act=paste&amp;mode=cut',
//				'icon'                => 'cut.gif',
//				'attributes'          => 'onclick="Backend.getScrollOffset();"'
//			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ctable]['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ctable]['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'filename,title,link,description'
	),

	// Fields
	'fields' => array
	(
		'filename' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ctable]['filename'],
			'inputType'               => 'fileTree',
			'eval'                    => array('mandatory'=>true,'multiple'=>false,'files'=>true,'fieldType'=>'radio'),
			'save_callback'			  => array(array('meta_editor', 'save_filename'))
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ctable]['title'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255)
		),
		'link' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ctable]['link'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255)
		),
		'description' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ctable]['description'],
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>false,'rte'=>($GLOBALS['TL_CONFIG']['meta_editor_deactivate_rte'] !== true ? 'tinyMCE' : null),'cols'=>40,'rows'=>5)
		)
	)
);
?>
