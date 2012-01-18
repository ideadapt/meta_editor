<?php
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
 * required init steps to run PHPUnit within contao 
 * Provide all methods for the backend module meta_editor
 * @copyright  Ueli Kunz 2010
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */
define('TL_MODE', 'BE');

if (function_exists('my_autoload') == false){
        
    function my_autoload($strClassName){
    	// Library
    	if (file_exists(TL_ROOT . '/system/libraries/' . $strClassName . '.php')){
    		include_once(TL_ROOT . '/system/libraries/' . $strClassName . '.php');
    		return;
    	}
        
        // Driver
        if (file_exists(TL_ROOT . '/system/drivers/' . $strClassName . '.php')){
    		include_once(TL_ROOT . '/system/drivers/' . $strClassName . '.php');
    		return;
    	}
        
    	// Modules
    	foreach (scan(TL_ROOT . '/system/modules/') as $strFolder){
    		if (substr($strFolder, 0, 1) == '.'){
    			continue;
    		}
    
    		if (file_exists(TL_ROOT . '/system/modules/' . $strFolder . '/' . $strClassName . '.php')){
    			include_once(TL_ROOT . '/system/modules/' . $strFolder . '/' . $strClassName . '.php');
    			return;
    		}
    	}
    	// HOOK: include Swift classes
    	if (class_exists('Swift', false)){
    		Swift::autoload($strClassName);
    		return;
    	}
    	// HOOK: include DOMPDF classes
    	if (function_exists('DOMPDF_autoload')){
    		DOMPDF_autoload($strClassName);
    		return;
    	}
    
    	trigger_error(sprintf('Could not load class %s', $strClassName), E_USER_ERROR);
    }
    spl_autoload_register('my_autoload');
}

function rcopy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    
    while(false !== ($file = readdir($dir))){
        if (( $file != '.' ) && ( $file != '..' )){
            if (is_dir($src . '/' . $file)){
                rcopy($src . '/' . $file,$dst . '/' . $file);
            }
            else{
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}

function addTLRoot($TLPath){
    return TL_ROOT."/".$TLPath;
}

require_once('../../../initialize.php');

?>