<?php
/**
 * FUNCTION: dbConnect - connects to the database
 *
 * @param string $idForError
 * @param string[optional] $dbHostTemp
 * @param string[optional] $dbUserTemp
 * @param string[optional] $dbPassTemp
 * @param string[optional] $idForError
 * @return resource/link
 */
function dbConnect($idForError='',$dbHostTemp=false,$dbUserTemp=false,$dbPassTemp=false,$dbDatabaseTemp=false){
	$dbHostTemp = ($dbHostTemp !== false) ? $dbHostTemp : DB_HOST;
	$dbUserTemp = ($dbUserTemp !== false) ? $dbUserTemp : DB_USER;
	$dbPassTemp = ($dbPassTemp !== false) ? $dbPassTemp : DB_PASS;
	$dbDatabaseTemp = ($dbDatabaseTemp !== false) ? $dbDatabaseTemp : DB_DEFAULT;

	$tempLink = @mysql_connect($dbHostTemp,$dbUserTemp,$dbPassTemp);
	if(mysql_errno()){
		if(MYSQL_DEBUG === true){
			die(MYSQL_ERROR_MESSAGE."<br>
			Debug: Error connecting to host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]<br/>
			Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('dbConnect()',$idForError,mysql_errno(),mysql_error());
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--
			 Debug: Error connecting to host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]
			 Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."
			 -->");
	}

	@mysql_select_db($dbDatabaseTemp,$tempLink);
	if(mysql_errno()){
		if(MYSQL_DEBUG === true){
			die("<br>\n<b>Debug:</b> Error selecting database [".$dbDatabaseTemp."] on host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]<br>
				Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('dbConnect()',$idForError,mysql_errno(),mysql_error());
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--
			 Debug: Error selectiong database [".$dbDatabaseTemp."] on host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]
			 Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."
			 -->");
	}
	return $tempLink;
}
/**
 * Connects to the database if no connection is present
 *
 * @param string[optional] $idForError
 */
function dbConnectIfNeeded($idForError=''){
	if(!@mysql_ping()){
		return dbConnect($idForError);
	} else {
		return false;
	}
}
/**
 * FUNCTION: dbConnectLocal- connects to the local database
 *
 * @param string $idForError
 * @return resource/link
 */
function dbConnectLocal($idForError=''){
	global $dbhostLocal,$dbuserLocal,$dbpwdLocal;
	$tempLink = mysql_connect($dbhostLocal,$dbuserLocal,$dbpwdLocal);
	if(mysql_errno()){
		if(MYSQL_DEBUG === true){
			die("<br>\n<b>Debug:</b> Error connecting to host [$dbhostLocal] as [$dbuserLocal]. [id $idForError]<br>\n");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('dbConnectLocal()',$idForError,mysql_errno(),mysql_error());
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--Debug: Error connecting to localhost [$dbhostLocal] as [$dbuserLocal:$dbpwdLocal]. [id $idForError]-->");
	}
	return $tempLink;
}

/**
 * FUNCTION: dbQuery - queries the database using the current connection
 *
 * @param string $dbName
 * @param string $query
 * @param string $idForError
 * @param int[optional] $linkIdentifier
 * @return result
 */
function dbQuery($dbName,$query,$idForError='',$linkIdentifier=null){
	if($linkIdentifier){
		$result = @mysql_db_query($dbName,$query,$linkIdentifier);
	} else {
		$result = @mysql_db_query($dbName,$query);
	}
	if(!$result){
		if(MYSQL_DEBUG === true){
			die("<br><b>Debug: </b>MySQL Error # ".mysql_errno()." - ".mysql_error()."<br/>Database Error : Unable to query (db $dbName). [id $idForError]<br/><br/>$query\n");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('dbQuery()',$idForError,mysql_errno(),mysql_error(),$query);
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."\nDatabase Error : Unable to query (db $dbName). [id $idForError]\n\n$query\n-->");
	}
	return $result;
}

/**
 * FUNCTION: myQuery - queries the database using the current connection
 *
 * @param string $query
 * @param string $idForError
 * @param int[optional] $linkIdentifier
 * @param bool[optional] $
 * @return result
 */
function myQuery($query,$idForError='',$linkIdentifier=null,$dieOnError=true){
	if($linkIdentifier){
		$result = @mysql_query($query,$linkIdentifier);
	} else {
		$result = @mysql_query($query);
	}
	if(!$result){
		if(MYSQL_DEBUG === true){
			die("<br><b>Debug: </b>MySQL Error # ".mysql_errno()." - ".mysql_error()."<br/>Database Error : Unable to query. [id $idForError]<br/><br/>$query\n");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('myQuery()',$idForError,mysql_errno(),mysql_error(),$query);
		}
		if($dieOnError){
			die(MYSQL_ERROR_MESSAGE."\n<!--Debug: MySQL Error # ".mysql_errno()." - ".mysql_error()."\nDatabase Error : Unable to query. [id $idForError]\n\n$query\n-->");
		}
	}
	return $result;
}

/**
 * Sends the email that includes database error information.
 *
 * @param string $functionOrigination
 * @param string $customErrorCode
 * @param string $errorNumber
 * @param string $errorString
 * @param string $failedQuery
 * @param string $selectedDatabase
 */
function sendDatabaseErrorEmail($functionOrigination,$customErrorCode,$errorNumber,$errorString,$failedQuery='',$selectedDatabase=''){
	global $FILE_DIRS, $clientkey, $userkey;
	require_once($FILE_DIRS['lib_shared'].'/SendEmail.inc');

	if(empty($selectedDatabase)){
		$selectedDatabase = DB_DEFAULT;
	}
	$clientSideQueryWithLoggedInUser = (IS_GATEWAY_SCRIPT === false && !empty($_SESSION['userkey']) && !empty($_SESSION['clientkey']));
	$userData = array('id'=>$userkey,'name'=>'','email'=>'');
	$clientData = array('id'=>$clientkey,'name'=>'');
	if(!stristr($functionOrigination,'connect')){
		$connectionIssue = false;
		if($clientSideQueryWithLoggedInUser){
			if (dbConnectIfNeeded()){
				$query = "SELECT first_name, last_name, email, clients.name AS client_name
				FROM intranet_client_side.users_client
				LEFT JOIN intranet.clients ON clients.clientid = users_client.clientid
				WHERE users_client.id = '$_SESSION[userkey]' AND users_client.clientid = '$_SESSION[clientkey]'";
				$result = myQuery($query,'sendDatabaseErrorEmail()');
				if(mysql_num_rows($result)){
					$data = mysql_fetch_assoc($result);
					$userData['name'] = $data['first_name'].' '.$data['last_name'];
					$userData['email'] = $data['email'];
					$clientData['name'] = $data['client_name'];
				}
			} else {
				//$connectionIssue = true;
			}
		}
	} else {
		$connectionIssue = true;
	}
	//Send $_POST vars
	//Send $_GET vars
	//Send full URL
	//Send user info
	$postData = array();
	if(count($_POST)){
		foreach($_POST as $key=>$val){
			if(is_array($val)){
				$postData[] = $key.' = '.implode(', ',$val);
			} else {
				if(strpos($key,'cc_') === 0){
					$tempVal = $val;
					$val = '';
					for($i=0;$i<strlen($tempVal);$i++){
						$val .= 'x';
					}
					unset($tempVal);
				}
				$postData[] = $key.' = '.$val;
			}
		}
	}
	$getData = array();
	if(count($_GET)){
		foreach($_GET as $key=>$val){
			if(is_array($val)){
				$getData[] = $key.' = '.implode(', ',$val);
			} else {
				if(strpos($key,'cc_') === 0){
					$tempVal = $val;
					$val = '';
					for($i=0;$i<strlen($tempVal);$i++){
						$val .= 'x';
					}
					unset($tempVal);
				}
				$getData[] = $key.' = '.$val;
			}
		}
	}
	$url = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
	$fullURL = HTTP_LEAD.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
	$subject = 'MySQL Error ('.$errorNumber.'): '.SERVER_NAME.' - '.$functionOrigination;
	$message = "<table width='100%' border='0' cellspacing='0' style='font-family:Arial;font-size:10pt;color:black;'>
	<tr style='background-color:#3D7CDF;color:white;font-weight:bold;'>
		<td>&nbsp;</td>
		<td align='left'>Information</td>
	</tr><tr>
		<td align='right' style='font-weight:bold' width='30%'>Server:&nbsp;</td>
		<td align='left' width='80%'>".SERVER_NAME." ($_SERVER[SERVER_ADDR])</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>Connecting IP:&nbsp;</td>
		<td align='left'>$_SERVER[REMOTE_ADDR]</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>Area:&nbsp;</td>
		<td align='left'>".((IS_GATEWAY_SCRIPT === true) ? 'Gateway' : 'Client')."</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>Function:&nbsp;</td>
		<td align='left'>$functionOrigination</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>Connection Issue?&nbsp;</td>
		<td align='left'>".( ($connectionIssue) ? 'Yes' : 'No')."</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>Custom Error Code:&nbsp;</td>
		<td align='left'>$customErrorCode</td>
	</tr><tr>
		<td align='right' style='font-weight:bold'>MySQL Error #:&nbsp;</td>
		<td align='left'>$errorNumber</td>
	</tr><tr>
		<td align='right' style='font-weight:bold' valign='top'>MySQL Error:&nbsp;</td>
		<td align='left'>$errorString</td>
	</tr>";

	if(!empty($failedQuery)){
		$message .= "<tr style='background-color:#3D7CDF;color:white;font-weight:bold;'>
			<td>&nbsp;</td>
		<td align='left'>Variables</td>
		</tr><tr>
			<td align='right' style='font-weight:bold' valign='top'>Database:&nbsp;</td>
			<td align='left'>$selectedDatabase</td>
		</tr><tr>
			<td align='right' style='font-weight:bold' valign='top'>Query:&nbsp;</td>
			<td align='left'>$failedQuery</td>
		</tr><tr>";
	}
	if(!$connectionIssue){
		if($clientSideQueryWithLoggedInUser){
			$message .= "<td align='right' style='font-weight:bold'>Client:&nbsp;</td>
				<td align='left'>$clientData[name] (#$clientData[id])</td>
			</tr><tr>
				<td align='right' style='font-weight:bold'>User:&nbsp;</td>
				<td align='left'>$userData[name] (#$userData[id])</td>
			</tr><tr>
				<td align='right' style='font-weight:bold'>Email:&nbsp;</td>
				<td align='left'>$userData[email]</td>
			</tr><tr>";
		}
		$message .= "<td align='right' style='font-weight:bold'>URL:&nbsp;</td>
			<td align='left'><a href='$fullURL' target='_blank'>$url</a></td>
		</tr><tr>
			<td align='right' style='font-weight:bold' valign='top'>\$_GET:&nbsp;</td>
			<td align='left'>".implode('<br/>',$getData)."</td>
		</tr><tr>
			<td align='right' style='font-weight:bold' valign='top'>\$_POST:&nbsp;</td>
			<td align='left'>".implode('<br/>',$postData)."</td>
		</tr>
		<table>";
	}
	SendEmail(MYSQL_DEBUG_EMAIL_TO,MYSQL_DEBUG_EMAIL_FROM,$subject,$message,true);
}


/**
 * FUNCTION dbDisconnect - disconnects from the current database
 *
 * @param resource/link $linkToClose
 */
function dbDisconnect($linkToClose=null){
	if($linkToClose){
		mysql_close($linkToClose);
	} else {
		mysql_close();
	}
}

/**
 * Disconnects from all open MySQL connections
 *
 */
function dbDisconnectAll(){
	while (@mysql_close());
}
?>