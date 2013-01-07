<?php
/**
 * FTP class
 * Written by Joshua Mills - mills.joshua@gmail.com
 * 12.11.2008: Added support for changing directories using a full directory path, as opposed to subdirectories.
 * 				ie: before, ../ & subdirectory/ worked, now /full/directory/path/ works.
 * 			   Also added the chmod() method.
 * 12.15.2008: Added support for the system type of MACOS - this is only detected when Rumpus is being used, as
 *              normally, using the built-in FTP server, UNIX is the system type.
 */
class Ftp{
	public $url;
	public $user;
	public $pass;
	public $port;
	public $conn_id;
	public $sys_type;
	public $timeout;
	public $autoseek;
	public $login_result;
	public $filesize;
	public $raw_filelist;
	public $dir = array();
	public $filelist = array();
	public $current_dir;
	public $overwrite_on_download = false;
	public $active=false;
	public $keys=array();
	public $data_array = array();
	public $check_remotely_for_items = true;
	public $output_actions = false;

	/**
	 * Constructor for the FTP class.  Defines basic ftp connection requirements.  If $connect is [true], will connect automatically
	 *
	 * @param boolean $connect
	 * @param string $ftp_server
	 * @param string $ftp_user_name
	 * @param string $ftp_user_pass
	 * @param int $port
	 * @return FTP
	 */
	function FTP($ftp_server,$ftp_user_name,$ftp_user_pass,$port='21',$connect=true,$check_remotely_for_items=true,$output_actions=false){
		$this->url = $ftp_server;
		$this->user = $ftp_user_name;
		$this->pass = $ftp_user_pass;
		$this->port = $port;
		$this->check_remotely_for_items = $check_remotely_for_items;
		$this->output_actions = $output_actions;
		if($connect){
			$this->connect();
		}
	}
	/**
	 * Sets the values for the parsing of the ftp_rawlist command used in parse_file_list();
	 *
	 */
	function sysTypes(){
		switch($this->sys_type){
			case 'MACOS':
				$this->keys = array('Permissions','1','2','3','4','5','Name_Dir','Name');
				break;
			case 'UNIX':
				$this->keys = array('Permissions','1','2','Ftp','Size','Mon','Day','Time','Name');
				break;
			default:
				$this->keys = array('Date','Time','Type','Name');
				break;
		}
	}
	/**
	 * Depending on the server type, the file type will be determined differently.  Currently UNIX and Windows_NT are supported.
	 *
	 * @param array $array
	 */
	function determineFileType(&$array){
		switch($this->sys_type){
			case 'MACOS':
				$array['Type'] = (strpos($array['Permissions'],'d') === 0) ? 'd' : 'f';
				break;
			case 'UNIX':
				$array['Type'] = (strpos($array['Permissions'],'d') === 0) ? 'd' : 'f';
				break;
			default:
				$array['Type'] = (strtolower($array['Type']) == '<dir>') ? 'd' : 'f';
				break;
		}
	}
	/**
	 * Connects to the ftp server
	 *
	 * @return boolean
	 */
	function connect(){
		if(!$this->active){
			$this->conn_id = ftp_connect($this->url,$this->port);
			// login with username and password
			$this->login_result = ftp_login($this->conn_id, $this->user, $this->pass);
			// check connection
			if ((!$this->conn_id) || (!$this->login_result)) {
				print("FTP connection has failed!<br/>
         		Attempted to connect to ".$this->url." for user ".$this->user." / ".$this->pass." on port ".$this->port."<br/>\n");
			} else {
				$this->active = true;
				$this->sys_type = ftp_systype($this->conn_id);
				$this->key3 = ($this->sys_type == 'UNIX') ? 'Permissions' : 'Date';
				$this->sysTypes();
				$this->timeout = ftp_get_option($this->conn_id, FTP_TIMEOUT_SEC);
				$this->autoseek = (ftp_get_option($this->conn_id, FTP_AUTOSEEK)) ? 'On' : 'Off';
				if($this->output_actions){
					print ("Connection to $this->url successful!<br/>\nSystem is $this->sys_type<br/>\nTimeout is [$this->timeout] secs.<br/>\nAutoseek is [$this->autoseek]<br/>\n");
				}
			}
			$this->updateCurrDir();
			return $this->login_result;
		} else {
			if($this->output_actions){
				print ("Connection still active [".$this->url."]<br/>\n");
			}
			return false;
		}
	}
	/**
	 * Detects if the connection to the ftp is still active.
	 *
	 * @return boolean
	 */
	function isActive(){
		if(!$this->active){
			print ("No current connection active [".$this->url."]<br/>\n");
		}
		return $this->active;
	}
	/**
	 * Disconnects from the ftp.
	 *
	 * @return boolean
	 */
	function disconnect(){
		if($this->isActive()){
			ftp_close($this->conn_id);
			$this->active = false;
			if($this->output_actions){
				print ("Disconnect successful! [$this->url]<br/>\n");
			}
			return true;
		} else {
			if($this->output_actions){
				print ("Error disconnecting! [$this->url]<br/>\n");
			}
			return false;
		}
	}
	/**
	 * Detects the current path of the ftp connection.
	 *
	 * @return mixed
	 */
	function updateCurrDir(){
		if($this->isActive()){
			$this->current_dir = ftp_pwd($this->conn_id);
			return $this->current_dir;
		} else {
			return false;
		}
	}
	/**
	 * Changes directory if the directory exists.
	 *
	 * @param string $directory
	 * @return boolean
	 */
	function changeDir($directory){
		if($this->isActive()){
			$firstChar = substr($directory,0,1);
			//if a full directory shift...
			if($firstChar == '/'){
				$lastChar = $firstChar = substr($directory,-1);
				$dirs = explode('/',$directory);
				if($lastChar == '/'){
					unset($dirs[count($dirs)-1]);
				}
				unset($dirs[0]);
				$this->dir = $dirs;
				$this->dir_change = ftp_chdir($this->conn_id, $directory);
			} else {
				$this->dir[] = $directory;
				$this->dir_change = ftp_chdir($this->conn_id, $this->dir[count($this->dir)-1]);
			}
			if (!$this->dir_change) { #attempt to change the directory
				print ("Error changing directory to [".$directory." on ".$this->url.$this->current_dir."]<br/>\n");
			} else {
				$this->updateCurrDir();
			}
			return $this->dir_change;
		} else {
			return false;
		}
	}
	/**
	 * Creates a directory on the ftp.  Detects if exists prior to creating.  To automatically drop into it, use the $changeto flag.
	 *
	 * @param string $directory
	 * @param boolean $changeto
	 * @return boolean
	 */
	function makeDir($directory,$changeto=true){
		if($this->isActive()){
			if(!$this->remoteItemExists($directory)){
				$this->lastMadeDir = ftp_mkdir($this->conn_id, $directory);
				if($this->lastMadeDir){
					if($changeto){
						$this->changeDir($directory);
					}
				} else {
					print ("Error creating directory [".$directory." on ".$this->url.$this->current_dir."]<br/>\n");
				}
				return $this->lastMadeDir;
			} else {
				print ("Directory already exists [".$directory." on ".$this->url.$this->current_dir."]<br/>\n");
				return false;
			}
		} else {
			return false;
		}
	}
	/**
	 * Removes a directory from the ftp.  Detects if present before attempting to remove it.
	 *
	 * @param string $directory
	 * @return boolean
	 */
	function removeDir($directory){
		if($this->isActive()){
			if($this->remoteItemExists($directory)){
				$this->dir_remove = ftp_rmdir($this->conn_id,$directory);
				if(!$this->dir_remove){
					print ("Error removing directory [".$directory." on ".$this->url.$this->current_dir."]<br/>\n");
				}
			} else {
				print ("Directory does not exist [".$directory." on ".$this->url.$this->current_dir."]<br/>\n");
				$this->dir_remove = false;
			}
			return $this->dir_remove;
		} else {
			return false;
		}
	}
	/**
	 * Uploads a file to the ftp.  Detects if present, and will only overwrite it with the $overwrite flag set to [true]
	 *
	 * @param string $localFile
	 * @param string $saveFileAs
	 * @param boolean $overwrite
	 * @return boolean
	 */
	function uploadFile($localFile,$saveFileAs,$overwrite=false){
		if($this->isActive()){
			$overwrite_tag = '';
			$it_exists = $this->remoteItemExists($saveFileAs);
			if($it_exists){
				$overwrite_tag = ($overwrite) ? "[overwrite switch set to true]" : "[overwrite switch set to false]";
			}
			if( (!$it_exists) || ($overwrite) ){
				if(file_exists($localFile)){
					$this->uploaded = ftp_put($this->conn_id,$saveFileAs,$localFile,FTP_BINARY);
					if($this->output_actions){
						if($this->uploaded){
							print ("File [$localFile] saved as [$saveFileAs] on [".$this->url.$this->current_dir."] $overwrite_tag<br/>\n");
						} else {
							print ("Error saving [$localFile] as [$saveFileAs] on [".$this->url.$this->current_dir."] $overwrite_tag<br/>\n");
						}
					}
				} else {
					print ("File [$localFile] does not exist<br/>\n");
					$this->uploaded = false;
				}
			} else {
				if($this->output_actions){
					print ("File exists on server [".$saveFileAs." on ".$this->url.$this->current_dir."] $overwrite_tag<br/>\n");
				}
				$this->uploaded = false;
			}
			return $this->uploaded;
		} else {
			return false;
		}
	}
	/**
	 * Retrieves a file from the ftp and saves it locally.  Detects if local file exists, if it does, will only overwrite if $overwrite is set to [true]
	 *
	 * @param string $fileOnFTP
	 * @param string $saveFileAs
	 * @param boolean $overwrite
	 * @param boolean $binaryMode
	 * @return boolean
	 */
	function downloadFile($fileOnFTP,$saveFileAs, $overwrite=false,$binaryMode=true){
		if($this->isActive()){
			$ok_to_download = true;
			$exists = ($this->check_remotely_for_items) ? $this->remoteItemExists($fileOnFTP) : true;
			if($exists){
				if(file_exists($saveFileAs)) {
					if($this->overwrite_on_download || $overwrite){
						print ("File [$saveFileAs] exists - overwriting [overwrite switch set to true]<br/>\n");
						unlink($saveFileAs);
					} else {
						print ("File [$saveFileAs] exists [overwrite switch set to false]<br/>\n");
						$ok_to_download = false;
					}
				}
				if($ok_to_download){
					$this->file_size = ftp_size($this->conn_id,$fileOnFTP);
					$this->downloaded = ftp_get($this->conn_id,$saveFileAs,$fileOnFTP, ( ($binaryMode) ? FTP_BINARY : FTP_ASCII));
					if ($this->downloaded) {#check download status
						print ("File [$fileOnFTP] saved as [$saveFileAs] [$this->file_size bytes] [".$this->url.$this->current_dir."]<br/>\n");
					} else {
						print ("Error saving [$fileOnFTP] as [$saveFileAs] [$this->file_size bytes] [".$this->url.$this->current_dir."]<br/>\n");
					}
				} else {
					print ("Download of [$fileOnFTP] as [$saveFileAs] aborted [".$this->url.$this->current_dir."]<br/>\n");
					$this->downloaded = false;
				}
			} else {
				print ("File does not exist [".$fileOnFTP." on ".$this->url.$this->current_dir."]<br/>\n");
				$this->downloaded = false;
			}
			return $this->downloaded;
		} else {
			return false;
		}
	}
	/**
	 * Removes a file from the ftp.  Detects if present before attempting to remove it.
	 *
	 * @param string $fileOnFTP
	 * @return boolean
	 */
	function removeFile($fileOnFTP){
		if($this->isActive()){
			if($this->remoteItemExists($fileOnFTP)){
				$this->deleted = ftp_delete($this->conn_id,$fileOnFTP);
				if($this->deleted){
					print ("File [$fileOnFTP] deleted [".$this->url.$this->current_dir."]<br/>\n");
				} else {
					print ("Error deleting [$fileOnFTP on".$this->url.$this->current_dir."]<br/>\n");
				}
			} else {
				print ("File does not exist [".$fileOnFTP." on ".$this->url.$this->current_dir."]<br/>\n");
				$this->deleted = false;
			}
			return $this->deleted;
		} else {
			return false;
		}
	}
	/**
	 * Detects whether the passed item exists on the remote ftp, with the type of files[f], directories[d], or both[a].
	 *
	 * @param string $item
	 * @param char $type
	 * @param boolean $case_sensitive
	 * @return boolean
	 */
	function remoteItemExists($item,$type='a',$case_sensitive=false){
		if($this->isActive()){
			$type = strtolower($type);
			$this->getFileList($type);
			$found = false;
			if($case_sensitive){
				$item = strtolower($item);
			}
			foreach($this->filelist as $file_info){
				if($case_sensitive){
					if (strtolower($file_info['Name']) == $item){
						if($type=='a'){ #if the type requested is all, it's found
							$found = true;
						} else if($file_info['Type'] == $type){ #otherwise, check the types
							$found = true;
						}
						break;
					}
				} else {
					if ($file_info['Name'] == $item){
						if($type=='a'){ #if the type requested is all, it's found
							$found = true;
						} else if($file_info['Type'] == $type){ #otherwise, check the types
							$found = true;
						}
						break;
					}
				}
			}
			return $found;
		} else {
			return false;
		}
	}
	/**
	 * Checks the ftp server for a list of files[f], directories[d], or both[a].  Parse by filetype[.php],[.txt],[.zip]
	 *
	 * @param char $toGet
	 * @param string $fileTypes
	 * @return boolean
	 */
	function getFileList($toGet='a',$fileTypes=''){
		if($this->isActive()){
			$toGet = strtolower($toGet);
			$this->raw_filelist = ftp_rawlist($this->conn_id, '.');
			$this->filelist = $this->parse_file_list($this->raw_filelist,$toGet,$fileTypes);
			#parse out the file list for the current directory[.]
			return $this->filelist;
		} else {
			return false;
		}
	}
	/**
	 * Merges the keys($this->keys) and values($this->data_array) and returns the array
	 *
	 * @return array
	 */
	function mergeKeysWithArray(){
		//		$this->keys;
		//		$this->data_array;
		$returnarray = array();
		if ((!count($this->keys)) || (!count($this->data_array))) {
			return 0;
		}
		#find the longest of the two arrays
		$longestamount = (count($this->keys) > count($this->data_array)) ? count($this->keys) : count($this->data_array);
		for($x = 0; $x < $longestamount; $x++){
			$returnarray[$this->keys[$x]] = addslashes($this->data_array[$x]);
		}
		return $returnarray;
	}
	/**
	 * Takes an array of ftp_rawlist from the server and parses it to find the files[f], directories[b], or both[a]. Parse by filetype[.php],[.txt],[.zip]
	 *
	 * @param ftp_rawlist $array
	 * @param char $toGet
	 * @param string $filetype
	 * @return array
	 */
	function parse_file_list($array,$toGet='a',$filetype=''){
		$filearray = array();
		if(count($array)){
			foreach($array as $file){
				while(stristr($file, "  ")){
					$file = str_replace("  ", " ", $file);
				}
				$this->data_array = explode(' ',$file);
				$tempArray = $this->mergeKeysWithArray();
				$this->determineFileType($tempArray);
				if( ($toGet == 'a') || ( ($toGet == 'd') && ($tempArray['Type'] == 'd') ) ){
					$filearray[] = $tempArray;
				} else if( ($toGet == 'f') && ($tempArray['Type'] == 'f') ){
					if(!empty($filetype)){
						#if the last[x amount] is the same as the filetype requested, add it
						if(strtolower(substr($tempArray['Name'],-(strlen($filetype)))) == strtolower($filetype)){
							$filearray[] = $tempArray;
						}
					}else{
						$filearray[] = $tempArray;
					}
				}
			}
		}
		return $filearray;
	}
	/**
	 * Sends a site command to the FTP server
	 *
	 * @param string $command
	 */
	function sendSiteCommand($command){
		if($this->isActive()){
			//			return ftp_site($this->conn_id,$command);
			return ftp_raw($this->conn_id,$command);
		} else {
			return false;
		}
	}
	/**
	 * Changes the permissions for the remote file specified.
	 *
	 * @param string $remoteFile
	 * @param int/string $mode
	 * @return boolean
	 */
	function chmod($remoteFile, $mode){
		if($this->isActive()){
			return ftp_chmod($this->conn_id, $mode, $remoteFile);
		} else {
			return false;
		}
	}
}
?>