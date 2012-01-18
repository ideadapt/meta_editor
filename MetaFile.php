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
 * Class MetaFile
 * Provide all methods to read and write contao meta text files with meta data for images and download items.
 * available get keys:
 * - language:string
 * - rows:array
 * @copyright  Ueli Kunz 2010
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */
class MetaFile extends File {
	
    /**
     * Format: array(array('link'=>, 'filename'=>,'description'=>)[, ...]) 
     * @var array
     */
    protected $rows = array();
    
    public static $REGEX_METAFILE = "/^meta(_[a-z]{2})?\.txt$/";
    
	function __get($strKey){
	   if (!isset($this->arrCache[$strKey])){
			switch (strtolower($strKey)){
				case 'language':
                    $this->arrCache[$strKey] = MetaFile::getLanguageCodeFromFilename($this->strFile);
                break;
                
                case 'rows':
                    $this->arrCache[$strKey] = &$this->rows;
                break;
                
                default:
                    return NULL;
                break;
            }
        }
        return $this->arrCache[$strKey];
	}
    
    /**
     * Returns true if a meta text file at the given path $TLPath with $preferredLang exists, false otherwise.
     * @param $TLPath
     * @param $preferredLang
     * @return boolean
     */
    protected function validateFilename($TLpath, $preferredLang=NULL){
        $metaFilename = "";
        
        // directory provided, get meta by language
        if(is_dir(TL_ROOT."/".$TLpath) === true){
            $lang = $preferredLang;
            
            if(isset($lang) === false){
            	$lang = $GLOBALS['TL_LANGUAGE'];
            }
            
            $metaFilename = $TLpath."/meta_".$lang.".txt";
        }else{
            // explicit metafilename
        	$metaFilename = $TLpath;
        }
        if(file_exists(TL_ROOT."/".$metaFilename) === true){
        	// all fine
        	$this->strFile = $metaFilename;
            return true;
        }else{
            // not a dir nor a file, invalid
            return false;
        }
    }
    
    /*
     * returns TL-path of meta text file path with correct language set.
     * */
    public static function createMetafilename($TLpath, $preferredLang=NULL){
    	$metaFilename = "";
        
        // directory provided, get meta by language
        if(is_dir(TL_ROOT."/".$TLpath) === true){
            $lang = $preferredLang;
            
            if(isset($lang) === false){
            	$lang = $GLOBALS['TL_LANGUAGE'];
            }
            
            $metaFilename = $TLpath."/meta_".$lang.".txt";
        }else{
            // explicit metafilename
        	$metaFilename = $TLpath;
        }
        return $metaFilename;
    }
    
    /**
     * Creates a new MetaFile object. $TLpath can either be the dirname or the full qualified path of the meta text file, 
     * in which case the language parameter will be ignored.
     * @param $TLPath			path without TL_ROOT prefix of the meta text file path. 
     * @param $preferredLang	the language of the meta text file to load. leave emtpy or set to NULL to load meta.txt.
     */
    public function __construct($TLpath, $preferredLang=NULL){
        $metaFilename = MetaFile::createMetafilename($TLpath, $preferredLang);
        parent::__construct($metaFilename);
    }

	/**
	 * Parse the meta text file an create an associative array of rows (get key: rows).
	 * @return	array('img.ext' => array(0 => 'imgname', 1 => 'description', 2 => path without TL_ROOT) [, ...])
	 * */
	public function parse(){
		
		// TODO: add caching support, similar to Frontend->parseMetaFile implementation.
		
		$strBuffer = file_get_contents(TL_ROOT."/".$this->strFile);
		$strBuffer = utf8_convert_encoding($strBuffer, $GLOBALS['TL_CONFIG']['characterSet']);
		$arrBuffer = array_filter(trimsplit('[\n\r]+', $strBuffer));
		$arrMeta = array();
		foreach ($arrBuffer as $v){
			list($strLabel, $strValue) = array_map('trim', explode('=', $v, 2));
			$arrMeta[$strLabel] = array_map('trim', explode('|', $strValue));
		}

		$this->rows = $arrMeta;
	}

	/**
	 * Creates / overwrites the physical metafile with the data provided by $items.
	 * @param	$items:array	items to add, array structure: array(array('link'=>, 'filename'=>,'description'=>)[, ...])
	 * */
	public function save($items=NULL, $forceCreate=false) {
		
		if($forceCreate === false && file_exists(TL_ROOT."/".$this->strFile) === false){
        	$msg = "No meta file (".MetaFile::$REGEX_METAFILE.") found:  '".$this->strFile."'.";
        	$this->log($msg, "MetaFile::write", TL_ERROR);
            throw new Exception($msg);
		}
		
		if(is_array($items)){
			$this->rows = $items;
		}else{
			$this->log("parameter $items must be of type array.", "MetaFile::write", TL_ERROR);
			throw new Exception("parameter $items must be of type array.");
		}

		$length = count($this->rows);
        $content = "";
		$i=0;
		if(count($this->rows)) {
			foreach ($this->rows as $v) {
				if(empty($v['filename']) === false) {
				    $content .= $this->generateRow($v) . ($i++ < $length -1 ? "\n" : "" );
				}
				else {
					continue;
				}
			}
		}else{
			// nothing to iterate
		}		
		$this->write($content);
	}

	/**
	 * Generates one line of text in the physical meta file.
	 * line format: filename = title | link | lightbox image caption
	 * according to: http://www.contao-community.de/archive/index.php/t-5212.html, only source found on that subject
	 * by default title equals to the basename of filename
	 * @param $row:array	array with row data, keys: array('link'=>, 'filename'=>,'description'=>)
	 */
	protected function generateRow($row) {
		return sprintf("%s = %s | %s | %s", 
			basename($row['filename']),
			preg_replace("/\r|\n/s", " ", trim($row['description'])),
			$row['link'], 
			trim($row['title'])
		);
	}

	/**
	 * Returns the two letter language code in $metaFilename (e.g. $metaFilename = meta.txt returns "", $metaFilename = meta_en.txt return "en")
	 * @param $metaFilename		the path and name of the physical meta file
	 * */
	public static function getLanguageCodeFromFilename($metaFilename){
		$ex   = explode("_", basename($metaFilename));
		$lang = explode(".", $ex[1]);
		return $lang[0];
	}
}
?>