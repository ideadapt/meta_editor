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

// initialize environment
require_once("contao_test_init.php");
require_once('contao_test_config.php');

// objects required for this test case (e.g. unit under test, dca, module config)
require_once(TL_ROOT."/system/modules/meta_editor/config/config.php");
require_once(TL_ROOT."/system/modules/meta_editor/dca/tl_metafile.php");
require_once(TL_ROOT."/system/modules/meta_editor/dca/tl_metaitem.php");
require_once(TL_ROOT."/system/modules/meta_editor/meta_editor.php");

class meta_editor_test extends PHPUnit_Framework_TestCase
{
    private $e;
    private $db;
    private $TLUploadRoot;
    private $TLPresetRoot;
    private $TLTestRoot;
    private $ctable, $ptable;
    
    public function setUp(){
        $this->ptable = &$GLOBALS['BE_MOD']['content']['meta_editor']['tables'][0];
		$this->ctable = &$GLOBALS['BE_MOD']['content']['meta_editor']['tables'][1];
        $this->TLUploadRoot = $GLOBALS['TL_CONFIG']['uploadPath'];
        $this->TLPresetRoot = "system/modules/meta_editor/test/preset";
        $this->TLTestRoot   = $this->TLUploadRoot."/MetaEditorTest";
        $this->db = Database::getInstance();        

        $dbdriver   = $GLOBALS['TL_CONFIG']['dbDriver'];
        $dbname     = $GLOBALS['TL_CONFIG']['dbDatabase'];
        $dbuser     = $GLOBALS['TL_CONFIG']['dbUser'];
        $dbpw       = $GLOBALS['TL_CONFIG']['dbPass'];
        $dbhost     = $GLOBALS['TL_CONFIG']['dbHost'];
        $dbport     = $GLOBALS['TL_CONFIG']['dbPort'];
        
        $dbpwparam  = empty($dbpw)? "" : "-p$dbpw";    // support blank passwords
        $sqlSetupScriptPath = addTLRoot($GLOBALS['TL_CONFIG']['test']['sqlSetupScriptTLPath']);
        $sqlExecPath        = $GLOBALS['TL_CONFIG']['test']['sqlExecPath'];
        
        // setup FS
        if(is_dir(addTLRoot($this->TLTestRoot))){
            Files::getInstance()->rrdir($this->TLTestRoot);
        }
        mkdir(addTLRoot($this->TLTestRoot));
        rcopy(addTLRoot($this->TLPresetRoot), addTLRoot($this->TLTestRoot));
        
        // setup DB
        switch(strtolower($dbdriver)){
            case "mysql":
                exec("\"$sqlExecPath\" \"$dbname\" < \"$sqlSetupScriptPath\" -u $dbuser $dbpwparam -h $dbhost -P $dbport", $out);
            break;
            default:
                throw new Exception("Database driver '$dbdriver' not supported.");
            break;
        }        
    }
    
    public function tearDown(){
        /* this is done by setup as well. with no tearDown we can manually check the outputs. 
        if(is_dir(addTLRoot($this->TLTestRoot))){
           Files::getInstance()->rrdir($this->TLTestRoot);
        }
        */
    }
    
    public function testGenerateMetaNoAuto(){
        /*
        before:
        DB      has     image001
        File    has not image006/1
        after:
        DB      has     image001
        File    has not image006 but 001
        */
        // set metafile database id to 1 
        define("CURRENT_ID", 1);
        
        // update FS and DB
        $this->e = new meta_editor();
        $this->e->generateMetafile(1, false, false);
        
        // assertions
        // check metaitems and metafile (original entry still there, new missing)
        $dbOriginal = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image001.jpg'")->executeUncached();
        $this->assertEquals(1, $dbOriginal->numRows);
        $dbNew      = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image006.jpg'")->executeUncached();
        $this->assertEquals(0, $dbNew->numRows);

        // check meta.txt (original still there, new missing)
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true,  strpos($lines[0], "/image001.jpg") !== false);
        $this->assertEquals(false, strpos($lines[0], "/image006.jpg"));
    }
    
    public function testGenerateMetaAutoAdd(){
        /*
        before:
        DB      has     image001
        File    has not image006/1
        after:
        DB      has     image001/6
        File    has     image006/1
        */
        // set metafile database id to 1, used by meta_editor class
        define("CURRENT_ID", 1);
        
        // update FS and DB
        $this->e = new meta_editor();
        $this->e->generateMetafile(1, true, false);
        
        // assertions
        // check metaitems and metafile (original entry still there, new added)
        $dbOriginal = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image001.jpg'")->executeUncached();
        $this->assertEquals(1, $dbOriginal->numRows);
        $dbNew      = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image006.jpg'")->executeUncached();
        $this->assertEquals(1, $dbNew->numRows);

        // check meta.txt (original still there, new added)
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image001.jpg") !== false);
        $this->assertEquals(true, strpos($lines[5], "/image006.jpg") !== false);
    }
        
    public function testGenerateMetaAutoDelete(){
        // rename a file
        $old = $this->TLTestRoot."/nature/gallery1/image001.jpg";
        Files::getInstance()->delete($old); 
        /*
        before:
        DB      has     image001
        File    has not image006/1
        after:
        DB      has not image001
        File    has not image001 but 006
        */
        // set metafile database id to 1, used by meta_editor class
        define("CURRENT_ID", 1);
        
        // update FS and DB
        $this->e = new meta_editor();
        $this->e->generateMetafile(1, false, true);
        
        // assertions
        // check metaitems and metafile (old missing)
        $dbOriginal = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image001.jpg'")->executeUncached();
        $this->assertEquals(0, $dbOriginal->numRows);

        // check meta.txt (old missing)
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image001.jpg") === false);      
    }
    
    
    public function testGenerateMetaAutoAddDelete(){
        // rename a file
        $old = $this->TLTestRoot."/nature/gallery1/image001.jpg";
        Files::getInstance()->delete($old); 
        /*
        before:
        DB      has     image001
        File    has not image006/1
        after:
        DB      has not image001 but 006
        File    has not image001 but 006
        */
        // set metafile database id to 1, used by meta_editor class
        define("CURRENT_ID", 1);
        
        // update FS and DB
        $this->e = new meta_editor();
        $this->e->generateMetafile(1, true, true);
        
        // assertions
        // check metaitems and metafile (old missing, new added)
        $dbOriginal = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image001.jpg'")->executeUncached();
        $this->assertEquals(0, $dbOriginal->numRows);
        $dbNew      = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename LIKE '%image006.jpg'")->executeUncached();
        $this->assertEquals(1, $dbNew->numRows);

        // check meta.txt (old missing, new added)
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image001.jpg") == false);  
        $this->assertEquals(true, strpos($lines[3], "/image006.jpg") !== false);
    }
    
    // TODO: test field values (column mappings), test canImport
    public function testImportMetaEn(){
        // simulate form submit
        Input::getInstance()->setGet("key", "import");
        Input::getInstance()->setPost("FORM_SUBMIT", "{$this->ptable}_import");
        Input::getInstance()->setPost("file", $this->TLTestRoot."/nature/gallery1/meta_en.txt");
        
        $this->e = new meta_editor();
        $this->e->onkey_import();
        
        // assertions
        // check metaitems and metafile 
        // check meta.txt is not modified

        // assertions
        // check metaitems and metafile (only one added -> image001)
        $dbOriginal = $this->db->prepare("SELECT * FROM {$this->ctable} WHERE filename WHERE pid=2")->executeUncached();
        $this->assertEquals(1, $dbOriginal->numRows);
        $rows = $dbOriginal->fetchAllAssoc();
        $this->assertEquals("en", $rows[0]["language"]);

        // check meta.txt (the one and only entry for image001 still exists)
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta_en.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, count($lines) === 1);
        $this->assertEquals(true, strpos($lines[0], "/image001.jpg") !== false);        
    }
    
    public function testCutItemToFront(){
        // move metaitem 2 to the front of the items of metafile 1 => mode = 2
        Input::getInstance()->setGet('id', 2);
        Input::getInstance()->setGet('pid', 1);
        Input::getInstance()->setGet('mode', 2);
        // create sample meta file for import
        $this->e = new meta_editor();
        $table = new DC_Table("{$this->ctable}");        
        $table->cut(true);

        // assertions
        // check DB, the cut operation on database level is handled by contaos DC_Table->cut() operation.
        $dbOriginal = $this->db->prepare("SELECT id FROM {$this->ctable} WHERE pid=1 ORDER BY sorting")->executeUncached();
        $this->assertEquals(5, $dbOriginal->numRows);
        $rows = $dbOriginal->fetchAllAssoc();
        $this->assertEquals($rows[0]['id'], 2);
        $this->assertEquals($rows[1]['id'], 1);
        
        // check FS       
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image002.jpg") !== false); 
        $this->assertEquals(true, strpos($lines[1], "/image001.jpg") !== false); 
    }    
    
    public function testCutItemNotToFront(){
        // move the front metaitem with id 1 behind metaitem 2 => mode = 1
        Input::getInstance()->setGet('id', 1);
        Input::getInstance()->setGet('pid', 2);
        Input::getInstance()->setGet('mode', 1);
        
        // create sample meta file for import
        $this->e = new meta_editor();
        $table = new DC_Table("{$this->ctable}");
        $table->cut(true);

        // assertions
        // check DB, the cut operation on database level is handled by contaos DC_Table->cut() operation.
        $dbOriginal = $this->db->prepare("SELECT id FROM {$this->ctable} WHERE pid=1 ORDER BY sorting")->executeUncached();
        $this->assertEquals(5, $dbOriginal->numRows);
        $rows = $dbOriginal->fetchAllAssoc();
        $this->assertEquals($rows[0]['id'], 2);
        $this->assertEquals($rows[1]['id'], 1);
        
        // check FS       
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image002.jpg") !== false); 
        $this->assertEquals(true, strpos($lines[1], "/image001.jpg") !== false); 
    }

    public function testDeleteItem(){
        define("CURRENT_ID", 1);

        /* 
         * this test only removes the entry in the meta.txt file.
         * the entry in the db will last. in reality contao will handle database removal automatically.
         */
        $this->e = new meta_editor();
        $this->e->pid = CURRENT_ID;
        $this->e->deleteMetaitem(1);
        
        // assertions
        // check FS
        $metafile = new File($this->TLTestRoot."/nature/gallery1/meta.txt");
        $lines = $metafile->getContentAsArray();
        $this->assertEquals(true, strpos($lines[0], "/image001.jpg") === false); 
        $this->assertEquals(true, strpos($lines[0], "/image002.jpg") !== false); 
    }
}
?>