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
$GLOBALS['TL_DCA'][$ptable] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'ctable'					  => array($ctable),
		'switchToEdit'                => true,
		'ondelete_callback'			  => array
		(
			array('meta_editor', 'ondelete_metafile')
		),
		'onsubmit_callback' => array
		(
			array('meta_editor', 'onsubmit_metafile')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter'
		),
		'label' => array
		(
			'fields'                  => array('title', 'language'),
            'label_callback'          => array('meta_editor', 'label_metafile')
		),
		'global_operations' => array
		(	
			'import' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['import'],
				'href'                => 'key=import',
				'class'               => 'header_import',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
//			'all' => array
//			(
//				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
//				'href'                => 'act=select',
//				'class'               => 'header_edit_all',
//				'attributes'          => 'onclick="Backend.getScrollOffset();"'
//			)
		),
		'operations' => array
		(
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
				'button_callback'     => array('meta_editor', 'ontoggle_metafile')
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['edit'],
				'href'                => 'table=tl_metaitem',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG'][$ptable]['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'title,metatype,language;folder;'
	),

	// Fields
	'fields' => array
	(
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ptable]['title'],
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true,'maxlength'=>255,'tl_class'=>'w50')
		),
		'metatype' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ptable]['metatype'],
			'inputType'               => 'select',
			'options'				  => array('0','1'),
			'reference'				  => &$GLOBALS['TL_LANG'][$ptable]['metatypereference'],
			'eval'                    => array('tl_class'=>'w50')
		),		
		'folder' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ptable]['folder'],
			'inputType'               => 'fileTree',
			'filter'				  => true,
			'eval'                    => array('mandatory'=>true,'multiple'=>false,'files'=>false,'fieldType'=>'radio'),
			'save_callback'			  => array(
				array('meta_editor','save_folder')
			)
		),
		'language' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ptable]['language'],
			'inputType'               => 'text',
			'filter'				  => true,
			'eval'                    => array('rgxp'=>'alpha','maxlength'=>2,'nospace'=>true,'doNotCopy'=>true),
			'save_callback'			  => array(
				array('meta_editor','save_language')
			)
		),
		/* this field is required by the import form only, its not used in the metafile palette.*/
		'file' => array
		(
			'label'                   => &$GLOBALS['TL_LANG'][$ptable]['file'],
			'inputType'				  => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'txt', 'class'=>'mandatory', 'mandatory'=>true)
		),
	)
);


class tl_metafile extends BackendModule {
	
	protected function compile() {
	}

	public function generate() {
	}
	
	/**
	 * Interface methode. Required to handle ajax toggle requests.
	 * @param $id		id of the tl_metafile record to change visibility
	 * @param $newState	boolean value with the new already toggled state (derived from ui button state by javascript)
	 */
	public function toggleVisibility($id, $newState){
		$i = new meta_editor();
		$i->togglePublished($id, $newState);		
	}
}
?>