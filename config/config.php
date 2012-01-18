<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');
/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Ueli Kunz 2010
 * @author     Ueli Kunz <kunz@ideadapt.net>
 * @package    ia.contao.system.modules.meta_editor
 * @license    LGPL
 */


/**
 * @copyright  Ueli Kunz 2010
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */


/**
 * -------------------------------------------------------------------------
 * BACK END MODULES
 * -------------------------------------------------------------------------
 *
 * Back end modules are stored in a global array called "BE_MOD". Each module 
 * has certain properties like an icon, an optional callback function and one 
 * or more tables. Each module belongs to a particular group.
 * 
 *   $GLOBALS['BE_MOD'] = array
 *   (
 *       'group_1' => array
 *       (
 *           'module_1' => array
 *           (
 *               'tables'       => array('table_1', 'table_2'),
 *               'mykey'        => array('Class', 'method'),
 *               'callback'     => 'ClassName',
 *               'icon'         => 'path/to/icon.gif',
 *               'stylesheet'   => 'path/to/stylesheet.css'
 *           )
 *       )
 *   );
 * 
 * Use function array_insert() to modify an existing modules array.
 */

$GLOBALS['BE_MOD']['content']['meta_editor'] = array(
	'tables' 		=> array('tl_metafile', 'tl_metaitem'),
	'icon'   		=> 'system/modules/meta_editor/html/icon_meta_editor.png',
	'scanimport' 	=> array('meta_editor', 'onkey_scanimport'),
	'import' 		=> array('meta_editor', 'onkey_import'),
	'stylesheet' 	=> 'system/modules/meta_editor/html/meta_editor.css'
);
?>