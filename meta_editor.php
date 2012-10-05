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
 * Class meta_editor
 * Provide all methods for the backend module meta_editor.
 * @copyright  Ueli Kunz 2011
 * @author     Ueli Kunz <kunz@ideadapt.net>
 */
class meta_editor extends BackendModule {

    public  $pid            	= NULL;
	private $add_fsonly_items 	= false;
	private $delete_dbonly_items= false;
	private $metatype			= 0; 		// 0 = imagegallery, 1 = download
	private $recursive 			= false;	// recursive meta item management not currently supported
	private $folder_excl_mask 	= array(".svn", ".", "..");
	private $filestates 		= array();
	private $folderstates 		= array();
	private $ptable		    	= "";
	private $ctable 			= "";

	public static $STATE_FILE = 0;
	public static $STATE_BOTH = 1;
	public static $STATE_DB   = 2;
	public static $STATE_REFRESH_FOLDER = 10;

	protected function compile() {
	}

	public function generate() {
	}
	
	function __construct(DataContainer $dc=NULL){
		parent::__construct($dc);
		$this->ptable = &$GLOBALS['BE_MOD']['content']['meta_editor']['tables'][0];
		$this->ctable = &$GLOBALS['BE_MOD']['content']['meta_editor']['tables'][1];
	}

	/**
	 * Handler for key=scanimport
	 * */
	public function onkey_scanimport(){

		if ($this->Input->get('key') != 'scanimport'){
			return '';
		}

		$this->generateMetafile(CURRENT_ID, true, false);
			
		$this->redirect(str_replace('&key=scanimport', '', $this->Environment->request));
	}

	/**
	 * Handler for key=import
	 * */
	public function onkey_import(){

		if ($this->Input->get('key') != 'import'){
			return '';
		}

		$submitKey 	= $this->ptable."_import";
		$fileFieldname 	= 'file';

		// ...T') === $submitKey does not work, why?
		if ($this->Input->post('FORM_SUBMIT') == $submitKey){

			$metaFilename = $this->Input->post($fileFieldname);
			if($this->canImportMetafile($metaFilename) === false){
				// invalid submit, show error messages saved in TL_ERROR
				$this->reload();
			}else{
				// parse meta file, create meta file in db, and add entries to db
				$pid = $this->importMetafile($metaFilename);

				// redirect to edit screen of just created meta file
				$this->redirect(str_replace('&key=import', "&table={$this->ctable}&id={$pid}", $this->Environment->request));
			}
		}else{
			return $this->getRenderedImportForm($fileFieldname, $submitKey);
		}
	}

	/**
	 * Generates the HTML foreach field in $fieldnames. This HTML can be used in backend forms.
	 * The HTML includes the label, field html (with errors if any) and help text.
	 * @param $tablename		name of the DCA / database table
	 * @param $fieldnames:array	array with DCA fieldnames as key (e.g. array(0=>'username',1=>'file');
	 * @return array of format: array('username'=>'generated field html'[, ...]);
	 * */
	protected function getRenderedFields($tablename, $fieldnames){

		$renderedFields = array();
		$tableDca = &$GLOBALS['TL_DCA'][$tablename];

		foreach($fieldnames as $i=>$fieldname){
			$fieldConfig = $this->prepareForWidget($tableDca['fields'][$fieldname], $fieldname, null, $fieldname, $tablename);
			$widget = new $GLOBALS['BE_FFL'][$tableDca['fields'][$fieldname]['inputType']]($fieldConfig);
				
			$renderedFields[$fieldname] =
					         '<div class="tl_tbox block">
						        <h3>'.$widget->generateLabel().'</h3>'
						        .$widget->generateWithError().
						        (strlen($GLOBALS['TL_LANG'][$tablename][$fieldname][1])
						        ? '<p class="tl_help tl_tip">'.$GLOBALS['TL_LANG'][$tablename][$fieldname][1].'</p>'
						        : '')
						        .'</div>';
		}
		return $renderedFields;
	}

	/**
	 * Returns the rendered import form (based on the be_dev_form template.
	 * @param $fileFieldname	name of the fileTree field in the tl_metafile dca
	 * @param $submitKey 		id of the form element, identifies the form on post back 
	 * */
	protected function getRenderedImportForm($fileFieldname, $submitKey){
		$objTempl = new BackendTemplate("be_dev_form");
		$objTempl->backHref 	= ampersand(str_replace('&key=import', '', $this->Environment->request));
		$objTempl->backTitle 	= specialchars($GLOBALS['TL_LANG']['MSC']['backBT']);
		$objTempl->backText 	= $GLOBALS['TL_LANG']['MSC']['backBT'];
		$objTempl->heading 		= $GLOBALS['TL_LANG'][$this->ptable]['import'][0];
		$objTempl->messagesHtml = $this->getMessages();
		$objTempl->formAction 	= ampersand($this->Environment->request, true);
		$objTempl->formId 		= $submitKey;
		$objTempl->FORM_SUBMIT 	= $submitKey;
		$objTempl->submitValue 	= specialchars($GLOBALS['TL_LANG'][$this->ptable]['import'][3]);
		$objTempl->renderedFields = $this->getRenderedFields($this->ptable, array($fileFieldname));

		return $objTempl->parse();
	}

	/**
	 * Validates $metaFilename among these conditions:
	 * 1. not empty
	 * 2. not a directory path
	 * 3. same file (langauge & directory) is not already in database
	 * @return true if none of the above conditions is true
	 * */
	protected function canImportMetafile($metaFilename){
		$isValid = true;
		if (empty($metaFilename)){
			$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['all_fields'];
			$isValid = false;
		}else{
			// Folders cannot be imported
			if (is_dir(TL_ROOT."/".$metaFilename)){
				$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($metaFilename));
				$isValid = false;
			}else{
				if($this->isManaged($metaFilename) === true){
					$isValid = false;
					$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['duplicatemetafile'], $metaFilename);
				}
			}
		}
		return $isValid;
	}

	/**
	 * Imports entries of $metaFilename into a new database tl_metafile entry.
	 * 1. parse physical meta file
	 * 2. add meta file data to db
	 * 3. return metafile id
	 * Does validate $metaFilename => class MetaFile throws Exception on error.
     * @param $metaFilename  path of the meta file to be imported without TL_ROOT prefix (e.g. tl_files/pics/my/meta_de.txt).
	 * @return database id of the newly created tl_metafile entry
	 * */
	protected function importMetafile($metaFilename){

		// process validated form submit
		// 1. parse physical meta file
		// 2. add meta file data to db
		// 3. show success

		// 1. parse meta file
		$metaData = new MetaFile($metaFilename);
		$metaData->parse();
		$lang     = $metaData->language;
		$metaData = $metaData->rows;

		// 2. create metafile in db
		$folder = basename(dirname($metaFilename), "/".$metaFilename);
		$metaTitle = $folder." - imported at ".date("d M H:s");

		$qry   = $this->Database->prepare("INSERT INTO {$this->ptable} (tstamp, published, title, folder, language, metatype) VALUES (?, ?, ?, ?, ?, ?)");
		$dbRes = $qry->execute(time(), 1, $metaTitle, dirname($metaFilename), $lang, 0);
        $pid   =  $dbRes->insertId;
        
		// add entries in child table
        if(count($metaData)>0){
    		$toInsert = array();
    		$i = -1;
    		foreach($metaData as $file => $meta){
    			if(++$i <= 7){
    				$sorting = pow(2, $i); 	// max 2^7 = 128
    			}else{
    				$sorting += 128; 
    			}
    			// column order: pid, sorting, tstamp, filename, title, link, description
    			$row = array();
    			$row[] = $pid;
    			$row[] = $sorting;
    			$row[] = time();
    			$row[] = dirname($metaFilename)."/".$file;
    			$row[] = $meta[0];
    			$row[] = $meta[1];
    			$row[] = $meta[2];
    			$toInsert[] = $row;
    		}
    
    		$this->insertMetafileItems($toInsert);
        }
        
		// 3. show success
		return $pid;
	}

	/**
	 * Handler for tl_metafile edit / create form. Creates or modifies a meta_<lang>.txt and the corresponding database entries.
	 * Uses onsave_after for data validation. 
	 */
	public function onsubmit_metafile() {
		if($this->Input->post('FORM_SUBMIT') == $this->ptable) {
			if($this->onsave_after() === true){
				$this->generateMetafile(CURRENT_ID, false, false);
			}
		}
	}
	
	/*
	 * This method is triggered by onsubmit_metafile to see if the data provided are valid.
	 * onsubmit_metafile is triggered by contao core AFTER the values have been updated to the database.
	 * @return boolean true if all validations succeed, false otherwise.
	 * */
	public function onsave_after(){
		$isValid = true;
		$folder = $this->Input->post("folder");
		$this->pid = CURRENT_ID;
		$isValid = $this->save_folder_after($folder);
		
		return $isValid;
	}
	
	/**
	 * Creates or updates physical meta file with database id $id.
	 * @param 	$id			id of the metafile to change
	 * @param	$add_fsonly_items		default false. if true, newly detected files on the file system will be added automatically. Deleted files are not removed from the database.
	 * @param   $delete_dbonly_items	default false. if true, entries in database with no corresponding file on the file system will be deleted from the database and meta text file. 
	 * */
	public function generateMetafile($id, $add_fsonly_items=false, $delete_dbonly_items=false){
		$this->pid 	= $id;
		$dbMetafile = $this->Database->prepare("SELECT folder,language,metatype FROM {$this->ptable} WHERE id=?");
        $dbMetafile = $dbMetafile->limit(1)->executeUncached($id)->row();
		$root       = $dbMetafile["folder"];
		$dirTree    = $this->getDirectoryTree(TL_ROOT."/".$root);		
		$this->lang = $dbMetafile["language"];
		$this->metatype 		   = $dbMetafile['metatype'];
		$this->add_fsonly_items    = $add_fsonly_items;
		$this->delete_dbonly_items = $delete_dbonly_items;

		$this->initMetaitemFilestates();
		$this->updateFilestatesFS($root, $dirTree);
		$this->updateDB();

		$this->writeMetafiles();
	}

	/**
	 * Handler for toggle visibility operation (if not ajax request). 
	 * Handles also output of the toggle button ui on every request.
	 * @param array $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param array $attributes
	 */
	public function ontoggle_metafile($row, $href, $label, $title, $icon, $attributes){
		$tid = $this->Input->get('tid');
		if(strlen($tid)){
			// toggle requested by url, no ajax request
			$this->togglePublished($tid);
			$this->redirect($this->getReferer());
		}else{
			// loading button html, no toggling requested
			$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '0' : '1');
	
			if (!$row['published']){
				$icon = 'invisible.gif';
			}
			return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
		}		
	}	
	
	/**
	 * Toggle published flag ($new_state not provided). Provide 1 or 0 to set published explicitely.
	 * @param $id	id of the tl_metafile record to change visibility
	 */
	public function togglePublished($id, $new_state=''){
		$this->pid = $id;
		if($new_state === ''){
			$dbVisibility = $this->Database->prepare("SELECT published FROM {$this->ptable} WHERE id=?")
							->limit(1)->executeUncached($id)->row();
			if($dbVisibility['published'] == '' || $dbVisibility['published'] == "0"){
				$new_state = true;
			}else{
				$new_state = false;
			}
		}
		$dbVal = $new_state === true ? "1" : "0";
		$dbVisibility = $this->Database->prepare("UPDATE {$this->ptable} SET published=? WHERE id=?")
							->executeUncached($dbVal, $id);
							
		if($dbVisibility->affectedRows > 0){
			if($new_state === true){
				// regenerate FS from DB
				$this->generateMetafile($id, false, false);
			}else if ($new_state === false){
				// delete from FS, remain DB
				$TLfilename = $this->getMetafilename(NULL, $id);
				$this->deleteMetafileFromDisk($TLfilename);
			}
		}
	}
	
	/**
	 * Handler for tl_metafile delete operation.
	 * @param	$dc:DataContainer	Context that provides the metafile data of interest.
	 * */
	public function ondelete_metafile(DataContainer $dc){
		$TLmetafilename = $this->getMetafilename($dc);
		$deleted = $this->deleteMetafileFromDisk($TLmetafilename);
		if($deleted === true){
			$this->deleteMetafilesByIds(array($dc->id));
		}
	}

	/**
	 * Deletes a meta-file from file system (and DB, implecitely done by contao)
	 * @param	$dc:DataContainer	Context that provides the metafile to delete.
	 */
	public function deleteMetafileFromDisk($TLmetafilename) {
		$metafilename = TL_ROOT."/".$TLmetafilename;
		if(is_file($metafilename)) {
			$mf = new MetaFile($TLmetafilename);
			$mf->delete();
			return file_exists($metafilename) === false;
		}
		return false;
	}

	/**
	 * Handler for metaitem onload event.
	 * @param	$dc:DataContainer	Context that provides the meta-item data of interest.
	 * */
	public function onload_metaitem(DataContainer $dc){

        // cut will request onload_metaitem a second time, without any act.
        if($this->Input->get('act') == "cut"){
            return;
        }

		$pid = $this->getParentId($dc);

		$dbMetafile = $this->Database->prepare("SELECT folder,metatype,rte FROM {$this->ptable} WHERE id=?");
        $dbMetafile = $dbMetafile->limit(1)->executeUncached($pid);
		if($dbMetafile->numRows){
			$dca = &$GLOBALS['TL_DCA'][$this->ctable]['fields']['filename'];
			$dca['eval']['path'] 		= $dbMetafile->folder;
			$dca['eval']['filesOnly'] 	= true;

			// 0 = image gallery
			if($dbMetafile->metatype == 0){
                // TODO: make this list configurable for users
				$dca['eval']['extensions'] = $GLOBALS['TL_CONFIG']['validImageTypes'];
			}else{
				// downloaditems
			}
		    if(strlen($dbMetafile->rte)){
		        $GLOBALS['TL_DCA'][$this->ctable]['fields']['description']['eval']['rte'] = $dbMetafile->rte;
		    }
		}else{
			$this->log("No entry in {$this->ctable} with id '{$pid}'.", "meta_editor onload_metaitem", TL_ERROR);
		}
	}

	/**
	 * Handler for metaitem onsubmit event.
	 */
	public function onsubmit_metaitem(DataContainer $dc){

		if($this->Input->post('FORM_SUBMIT') == $this->ctable) {
			$this->pid = $this->getParentId($dc);
			$dbMetaitems = $this->Database->prepare("SELECT * FROM {$this->ctable} WHERE pid=? ORDER BY sorting")->executeUncached($this->pid);
			$this->writeMetaFile($dbMetaitems, $this->getMetafilename($dc));
		}
	}

	/**
	 * Handler for metaitem delete operation, invoked before contao delete operation.
	 * Rewrites physical meta-file, respecting database changes.
	 * @param	$dc:DataContainer	Context that provides the meta-file of interest.
	 */
	public function ondelete_metaitem(DataContainer $dc){
        $this->pid = CURRENT_ID;
        $this->deleteMetaitem($dc->id, false);
	}
    
	/*
	 * Removes the metaitem entry with id $id from the physical text file.
	 * @param	$id 			id of the metaitem to delete.
	 * @param	$deleteFromDB	default is false. if true metaitem with $id will be removed manually. not implemented yet. 
	 * */
    public function deleteMetaitem($id, $deleteFromDB=false){
        // get all items except the one to be deleted by contao
		$dbMetaitems = $this->Database->prepare("SELECT * FROM {$this->ctable} WHERE pid=? AND id!=? ORDER BY sorting")->executeUncached($this->pid, $id);
		$this->writeMetaFile($dbMetaitems, $this->getMetafilename(NULL, $this->pid));
		
		if($deleteFromDB === true){
			$this->Database->prepare("DELETE FROM {$this->ctable} WHERE id!=?")->executeUncached($id);
		}
    }

	/**
	 * Handler for metaitem cut operation (drag & drop, ajax request).
	 * Rewrites physical meta-file respecting database changes
	 * @param	$dc:DataContainer	Context that provides the meta-file of interest.
     */
	 public function oncut_metaitem(DataContainer $dc=NULL){
		// id=id of moved subject, pid=id of new previous item
		// mode=2 if moved to the beginning of the list => pid points to parent item (metafile)
		// mode=1 otherwise
        $id   = $this->Input->get('id');
        $pid  = $this->Input->get('pid');
        $mode = $this->Input->get('mode');
        if($mode == 1){
            $dbMoved = $this->Database->prepare("SELECT pid FROM {$this->ctable} WHERE id=?")->limit(1)->executeUncached($id)->row();
            $pid = $dbMoved['pid'];
        }
        $metafilename = $this->getMetafilename(NULL, $pid);
        $dbMetaitems  = $this->Database->prepare("SELECT * FROM {$this->ctable} WHERE pid=? ORDER BY sorting")->executeUncached($pid);
		$this->writeMetaFile($dbMetaitems, $metafilename);
	}

	/**
	 * Scans $outerDir and builds a array based directory tree.
	 * @param	$outerDir:string	path of the root dir
	 * @return an n-dimensional array of the directory structure with $outerDir as root.
	 * e.g.:
	 * array('0' => array('a' => 'a', 'b' => array('bb', 'bc'), '1'=>'1');
	 * for structure:
	 * 0
	 * -a
	 * -b
	 * --bb
	 * --bc
	 * 1
	 */
	protected function getDirectoryTree($outerDir) {
		$dirs = array_diff(scandir($outerDir), $this->folder_excl_mask);
		$dir_array = Array();
		foreach($dirs as $d) {
			if(is_dir($outerDir."/".$d)) {
				$dir_array[$d] = $this->getDirectoryTree($outerDir."/".$d);
			}
			else {
				$dir_array[$d] = $d;
			}
		}
		return $dir_array;
	}

	/**
	 * Sets the filestates for each tl_metaitem of current tl_metafile to STATE_DB.
	 * */
	protected function initMetaitemFilestates() {
		// read all entries and set the file status to db
		$items = $this->Database->prepare("SELECT * FROM {$this->ctable} WHERE pid=?")->executeUncached($this->pid);

		foreach ($items->fetchAllAssoc() as $f) {
			$this->filestates[$f['filename']] = array($f['id'], meta_editor::$STATE_DB);
		}
	}

	/**
	 * Scans the file system at $TLDirPath and updates filestate of each file:
	 * 1. if file is in FS and filestate was set to STATE_DB => update to STATE_BOTH
	 * 2. if file is in FS but filestate not set previously  => create fielStatus wit state STATE_FILE
	 * 3. update $TLDirPath to STATE_REFRESH_FOLDER
	 * @param	$TLdirPath			absolute directory path
	 * @param	$dirTree			array with directory structure (build by getDirectoryTree method)
	 * */
	protected function updateFilestatesFS($TLdirPath, $dirTree) {

		foreach($dirTree as $dir=>$d) {

			if ($this->recursive && is_dir(TL_ROOT . '/' . $TLdirPath . '/' . $dir)) {
				$this->updateFilestatesFS($TLdirPath.'/'.$dir, $d);
			}else {
				$TLFilename = $TLdirPath."/".$d;

				if(array_key_exists($TLFilename, $this->filestates) === true) {
					// file exists locally and in DB
					$this->filestates[$TLFilename][1] = meta_editor::$STATE_BOTH;

				}else if($this->add_fsonly_items === true) {

					// file exists only locally, set status to file, so that it will be added to the DB
					if(is_file(TL_ROOT."/".$TLdirPath . '/' . $d) === true && !preg_match(MetaFile::$REGEX_METAFILE, $d)) {
						$fdFile = new File($TLdirPath . '/' . $d);

						if($this->metatype == 0 && !$fdFile->isGdImage) {
							continue;
						}else {
							$this->filestates[$TLFilename][1] = meta_editor::$STATE_FILE;
							$this->filestates[$TLFilename][2] = array(
                                'filename' 		=> $TLFilename,
                                'title'    		=> $fdFile->basename,
								'link'     		=> "",
								'description' 	=> ""
								);
						}
					}
				}
			}
		}
		$this->folderstates[$TLDirPath] = meta_editor::$STATE_REFRESH_FOLDER;
	}

	/**
	 * Writes the physical meta-files in each folder that needs a refresh ($STATE_REFRESH_FOLDER).
	 * */
	protected function writeMetafiles() {

		foreach($this->folderstates as $fPath=>$status) {

			switch($status) {
				case meta_editor::$STATE_REFRESH_FOLDER:

					$items = $this->Database->prepare("SELECT * FROM {$this->ctable} WHERE pid=? ORDER BY sorting")->executeUncached($this->pid);

					$metaFilename = $this->getMetafilename(NULL, $this->pid);
					$this->writeMetaFile($items, $metaFilename);

					break;
			}
		}
	}

	/**
	 * Adds or deletes tl_metaitem entries according to the filestates object.
	 * Delete all STATE_DB, adds STATE_FILE
	 * */
	protected function updateDB() {
		$toDelete = array();
		$toInsert = array();

		$sort = $this->Database->prepare("SELECT MAX(sorting) as m FROM {$this->ctable} WHERE pid=?")->execute($this->pid)->row();
		$maxSort = 0 + $sort['m'];
		if($maxSort === 0){
			$maxSort = 1;
		}

		foreach($this->filestates as $file=>$status) {

			switch($status[1]) {
				case meta_editor::$STATE_DB:
					if($this->delete_dbonly_items === true){
						$toDelete[] = $status[0];
					}
					break;

				case meta_editor::$STATE_FILE:
                    $maxSort *= 2;
					$toInsert[] = array($this->pid, $maxSort, time(), $status[2]['filename'], $status[2]['title'], $status[2]['link'],  $status[2]['description']);
					break;
			}
		}

		$this->deleteMetafilesByIds($toDelete);
		$this->insertMetafileItems($toInsert);
	}

	/**
	 * Deletes all tl_metaitem entries that have an id contained in $idsToDelete
	 * @param	$idsToDelete		array of tl_metaitem ids to be deleted (array value = id)
	 * */
	protected function deleteMetafilesByIds($idsToDelete){
		if(!is_array($idsToDelete)){
			trigger_error("\$idsToDelete must be an array with integer values. e.g. array(0=> 12, 1=> 17);", E_USER_ERROR);
		}
		$delete = "DELETE FROM {$this->ctable} WHERE id in (".implode(",", $idsToDelete).")";
		if(count($idsToDelete) > 0) {
			$this->Database->prepare($delete)->execute();
		}
	}

	/**
	 * Inserts all tl_metaitem entries in $rowsToInsert
	 * column kesy and order: pid, sorting, tstamp, filename, title, link, description
	 * @param	$rowsToInsert		2-dimensional array with rows and columns.
	 * */
	protected function insertMetafileItems($rowsToInsert){
		if(!is_array($rowsToInsert)){
			trigger_error("\$colValues must be 2 dimensional array. column order: pid, sorting, tstamp, filename, title, description", E_USER_ERROR);
		}
		$insert = "INSERT INTO {$this->ctable} (pid, sorting, tstamp, filename, title, link, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
		foreach($rowsToInsert as $rk=>$row) {
			$this->Database->prepare($insert)->execute($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
		}
	}
	
	/**
	 * Creates / overwrites the physical metafile $filename with the data provided by $items.
	 * @param	$items:Database_Result		items to add
	 * @param	$filename					path of meta file without TL_ROOT prefix (e.g., tl_files/img/meta.txt)
	 * */
	private function writeMetaFile($items, $TLfilename) {
		if($items->numRows) {
			$items = $items->fetchAllAssoc();
			$mf    = new MetaFile($TLfilename);
			$mf->save($items, true);
		}
	}
		
	/**
	 * Returns the path of the current meta-file without the TL_ROOT prefix (e.g. tl_files/images/meta_en.txt).
	 * Provide either $dc or $id.
	 * @param $dc:DataContainer	The id for the meta-file is read from this object (->activeRecord->pid or ->id)
	 * @param $id				The id of the meta-file
	 */
	protected function getMetafilename(DataContainer $dc=NULL, $id=NULL){
		$pid = NULL;
		if($dc != NULL){
			$pid = $this->getParentId($dc);
		}else if($id != NULL){
			$pid = $id;
		}else{
			trigger_error("Provide either \$dc or \$id", E_USER_ERROR);
		}
		$dbMetafile = $this->Database->prepare("SELECT folder, language FROM {$this->ptable} WHERE id=?")->limit(1)->executeUncached($pid);

		if($dbMetafile->numRows){
			$lang = $dbMetafile->language;
			return sprintf("%s/meta%s.txt", $dbMetafile->folder, empty($lang) ? "" : "_".$lang);
		}else{
			trigger_error(sprintf("metafile for id %s not found.", $pid), E_USER_ERROR);
		}
	}

	/**
	 * Returns the id of the parent table entry represented by $dc.
	 * If in edit / editAll mode the activeRecords pid is returned, otherwise $dc->id.
	 * @param	$dc:DataContainer		DataContainer object to extract id from
	 * */
	protected function getParentId(DataContainer $dc){
		$pid = NULL;
        if(trim($this->Input->get('pid')) !== ''){
            $pid = $this->Input->get('pid');
        }else if($this->Input->get('act') == 'edit' || $this->Input->get('act') == 'editAll'){
            //activeRecord not set at onload_callback
            if(!$dc->activeRecord){
                $record = $this->Database->prepare("SELECT pid FROM {$this->ctable} WHERE id=?")->limit(1)->executeUncached($dc->id)->row();
                $pid = $record['pid'];
            }else{
                $pid = $dc->activeRecord->pid;
            }
		}else{
			$pid = $dc->id;
		}
		return $pid;
	}

	/*
	 * Retruns true if there is a metafile entry in the database that machtes $metaFilename.
	 * That is folder and language are the same.
	 * @param	$metaFilename	absolute or TLPath of a (text) file.
	 * */
	public function isManaged($metaFilename){
		$isManaged = false;
		$folder  = dirname($metaFilename);
		$lang    = MetaFile::getLanguageCodeFromFilename($metaFilename);

		$qry = $this->Database->prepare("SELECT id FROM {$this->ptable} WHERE folder=? AND language=?");
		$dbRes = $qry->executeUncached($folder, $lang);
		
		return $dbRes->numRows > 0;
	}	
	
	/**
	 * Handler for form field language save event.
	 * Throws Exception if a meta-file entry with the same language and the same folder already exists.
	 * @param $value			form field post value
	 * @param $dc:DataContainer	not used, contao event callback constraint
	 */
	public function save_language($varValue, DataContainer $dc){
	    $varValue = strtolower($varValue);
		$objUnique = $this->Database->prepare("SELECT * FROM {$this->ptable} WHERE language=? AND folder=? AND id!=?")
		->execute($varValue, $dc->activeRecord->folder, $dc->id);

		if($objUnique->numRows > 0){
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['duplicateoncreate']));
		}
		else {
			return $varValue;
		}
	}

	/**
	 * save callback of the metafile file field (used in the import form).
	 * Throws Exception if:
	 * - the file does not exist
	 * - a meta-file entry for the file already exists (folder and language match)
	 * @param $value			form field post value
	 * @param $dc:DataContainer	not used, contao event callback constraint
	 * ??DEPRECATED??
	 * */
	public function save_filename($value, DataContainer $dc){
		$this->pid = $this->getParentId($dc);
		$this->log("save_filename", "save_filename", "myactin");
		$dbMeta = $this->Database->prepare("SELECT folder FROM {$this->ptable} WHERE id=?")->limit(1)->executeUncached($this->pid);

		// allow only files that are in the same directory as the meta file
		$destDir = $dbMeta->folder;
		$fileDir = dirname($value);

		if($fileDir !== $destDir){
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['nofile'], $destDir));
		}

		// do not allow duplicate entries
		$dbItems = $this->Database->prepare("SELECT filename FROM {$this->ctable} WHERE pid=?")->executeUncached($this->pid);

		if($dbItems->numRows){
			$files = $dbItems->fetchAllAssoc();
			foreach($files as $i=>$fileRow){
				if($fileRow['filename'] === $value){
					throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['duplicatefile'], specialchars($value)));
				}
			}
		}

		return $value;
	}
	
	/*
	 * save callback of the metafile folder field.
	 * */
	public function save_folder($value, DataContainer $dc){
		
		$newFolder = $value;						// posted value
		$oldFolder = $dc->activeRecord->folder;		// current value in database
		$lang 	   = $this->Input->post("language");
		$metaFilename = $newFolder."/meta".(empty($lang) ? "" : "_".$lang).".txt";
		
		// only validate if value has changed		
		if($oldFolder !== $newFolder){
			// only create new metafile entry if the physical meta text file not already exists
			// if exists the user should use the import form.
			if(file_exists(TL_ROOT."/".$metaFilename) === true){
				$importUrl = sprintf("%s?do=%s&key=%s", $this->Environment->script, 'meta_editor', 'import');			
			    throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['fileexists_gotoimport'], $metaFilename, $importUrl));
			}
			
			// only create new metafile entry if the meta file is not already monitored
			if($this->isManaged($metaFilename) === true){
				$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['duplicatemetafile'], $metaFilename);
				$this->reload();
			}
		}
		return $value;
	}
    
	/*
	 * Validates the folder value. If there is neither a database value nor a post value validation fails.
	 * Errors are stored in TL_ERROR session object.
	 * @param	$id 		database id of the metafile
	 * @return 	boolean 	false if there is neither a database value nor a post value.
	 * */
	public function save_folder_after($newFolder){
		$isValid = true;
		if($this->Input->post('FORM_SUBMIT') == $this->ptable) {
			if($GLOBALS['TL_DCA'][$this->ptable]['fields']['folder']['eval']['mandatory'] == "true"){
				// field is mandatory. so we either need an existing db value or a new post value.				
				$dbVal = $this->Database->prepare("SELECT folder FROM {$this->ptable} WHERE id=?")->limit(1)->execute($this->pid);
				if($dbVal->numRows){
					$dbFolder = $dbVal->row();
					$dbFolder = $dbFolder["folder"];
					if(empty($dbFolder)){
						// no db value -> require post value
						if($newFolder == "") {
							// invalid: no folder in db AND no folder provided by post
							$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['nofolder']);
							$isValid = false;
						}else{
							// ok, post value provided.
						}
					}else{
						// ok, db value already exists.
					}					
				}else{
					// hm something went wrong.
					$this->log("No {$this->ptable} entry for parameter id '$id' found.", "meta_editor save_folder_after", TL_ERROR);
				}			
			}else{
				// dont care about the value. empty would be ok.
			}
		}
		return $isValid;
	}
		
	/*
	 * Gets the html output used for the backend list label of a metafile.
	 * Uses the be_list_metafile_label template.
	 * */
    public function label_metafile($row, $label, DataContainer $dc){
        $pid = $row['id'];
        $dbItemCount = $this->Database->prepare("SELECT COUNT(id) as cnt FROM {$this->ctable} WHERE pid=?")->execute($pid)->row(); 
        $filename    = TL_ROOT."/".$this->getMetafilename(NULL, $pid);
        $isFile      = file_exists($filename) && is_file($filename);
        
        $objTempl = new BackendTemplate("be_list_metafile_label");
        $objTempl->title 		= $row['title'];
        $objTempl->language 	= $row['language'];
        $objTempl->TLFilename 	= str_replace(TL_ROOT."/", "", $filename);
        $objTempl->countText 	= sprintf($GLOBALS['TL_LANG']['tl_metafile']['count']['0'], $dbItemCount['cnt']);
        $objTempl->isFile 		= $isFile;
        $objTempl->isPublished 	= $row['published'] ? true : false;
        
        return $objTempl->parse();
    }

	/**
	 * Get list presentation of a metaitem as rendered html.
	 * Uses the be_list_metaitem template.
	 * @param $arrRow:array		associative array of the meta-file database entry.
	 */
	public function list_metaitem($arrRow){
		$objTempl = new BackendTemplate("be_list_metaitem");
		$objTempl->isFile 	= false;
		$objTempl->isImage 	= false;
		$objTempl->title 		= $arrRow['title'];
		$objTempl->filename 	= $arrRow['filename'];
        $objTempl->iconFilename = NULL;
		$objTempl->imgPath 		= "";
		$objTempl->description 	= $arrRow['description'];
		$objTempl->errNoFile 	= sprintf("%s<br/>'%s'<br/>" , $GLOBALS['TL_LANG']['ERR']['filedoesntexist'], TL_ROOT . '/' . $objTempl->filename);
		$objTempl->downloadText = sprintf("'%s' %s", basename($objTempl->filename), $GLOBALS['TL_LANG'][$this->ctable]['download'][0]);
		
		if(file_exists(TL_ROOT . '/' . $objTempl->filename) && !is_dir(TL_ROOT . '/' . $objTempl->filename)){
			$objFile = new File($objTempl->filename);
            $objTempl->iconFilename = sprintf("%s/system/themes/%s/images/%s", $this->Environment->base, $this->getTheme(), $objFile->icon);
			if ($objFile->isGdImage){
				$objTempl->imgPath = $this->getImage($this->urlEncode($objTempl->filename), 100, 100);			
				$objTempl->isImage = true;
			}
			$objTempl->isFile = true;
		}
		
		return $objTempl->parse();
	}
}
?>
