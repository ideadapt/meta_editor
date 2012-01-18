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
 * @package    ia.contao.system.modules.meta_editor.test
 * @license    LGPL
 */


/**
 * contao configuration settings for PHPUnit tests
 * Provide all methods for the backend module meta_editor
 * @copyright  Ueli Kunz 2011
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */

// override localconfig values to use test database
$GLOBALS['TL_CONFIG']['dbDriver']    = 'MySQL';
$GLOBALS['TL_CONFIG']['dbUser']      = 'root';
$GLOBALS['TL_CONFIG']['dbPass']      = '';
$GLOBALS['TL_CONFIG']['dbHost']      = 'localhost';
$GLOBALS['TL_CONFIG']['dbDatabase']  = 'contao_dev';
$GLOBALS['TL_CONFIG']['dbPort']      = 3306;
$GLOBALS['TL_CONFIG']['dbPconnect']  = false;
$GLOBALS['TL_CONFIG']['dbCharset']   = 'UTF8';
$GLOBALS['TL_CONFIG']['dbCollation'] = 'utf8_general_ci';

$GLOBALS['TL_CONFIG']['test'] = array(
    'sqlSetupScriptTLPath'  => "/system/modules/meta_editor/test/sql/setup.sql",
    'sqlExecPath'           => "D:/Programme/xampp/mysql/bin/mysql"
);

?>