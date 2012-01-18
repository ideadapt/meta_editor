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

$GLOBALS['TL_LANG']['ERR']['filepublishedbutinexistend'] = "Meta file does not exist. The file has maybe manually been removed from the file system. Try regenerating it by republishing this item.";
$GLOBALS['TL_LANG']['ERR']['filenotpublished'] = "Meta file not published";
$GLOBALS['TL_LANG']['ERR']['filedoesntexist'] = "Meta file does not exist.";
$GLOBALS['TL_LANG']['ERR']['duplicateoncreate'] = "You can only create one meta file per language and directory. Choose another language (e.g., type 'en' for English) or directory.";
$GLOBALS['TL_LANG']['ERR']['fileexists_gotoimport'] = "The file '%s' already exists. Use the <a href='%s' title='goto import form'>import operation</a> to import and manage existing meta text files.";
$GLOBALS['TL_LANG']['ERR']['nofile'] = "Please select a file in the directory: '%s'. That is the root directory in the tree above.";
$GLOBALS['TL_LANG']['ERR']['nofolder'] = "Please select a source folder.";	
$GLOBALS['TL_LANG']['ERR']['duplicatemetafile'] = "The meta file '%s' was already added to MetaEditor. Choose another one to import.";
$GLOBALS['TL_LANG']['ERR']['duplicatefile'] = "The file '%s' is already in this meta file. Please choose another file to add.";

?>