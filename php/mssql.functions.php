<?php
/**
 * FUNCTION: mssqlConnect - connects to the database
 *
 * @param string $idForError
 * @param string[optional] $dbHostTemp
 * @param string[optional] $dbUserTemp
 * @param string[optional] $dbPassTemp
 * @param string[optional] $idForError
 * @return resource/link
 */
function mssqlConnect($idForError='',$dbHostTemp=false,$dbUserTemp=false,$dbPassTemp=false,$dbDatabaseTemp=false){

	$dbHostTemp = ($dbHostTemp !== false) ? $dbHostTemp : DB_MSSQL_HOST;
	$dbUserTemp = ($dbUserTemp !== false) ? $dbUserTemp : DB_MSSQL_USER;
	$dbPassTemp = ($dbPassTemp !== false) ? $dbPassTemp : DB_MSSQL_PASS;
	$dbDatabaseTemp = ($dbDatabaseTemp !== false) ? $dbDatabaseTemp : DB_DEFAULT;

	$tempLink = @mssql_connect($dbHostTemp,$dbUserTemp,$dbPassTemp);
	if(!$tempLink){
		if(MYSQL_DEBUG === true){
			die(MYSQL_ERROR_MESSAGE."<br>
			Debug: Error connecting to host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]<br/>
			Debug: MS SQL Error - ".mssql_get_last_message()."");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('mssqlConnect()',$idForError,0,mssql_get_last_message());
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--
			 Debug: Error connecting to host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]
			 Debug: MS SQL Error - ".mssql_get_last_message()."
			 -->");
	}

	if(!@mssql_select_db($dbDatabaseTemp,$tempLink)){
		if(MYSQL_DEBUG === true){
			die("<br>\n<b>Debug:</b> Error selecting database [".$dbDatabaseTemp."] on host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]<br>
				Debug: MS SQL Error - ".mssql_get_last_message()."");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('mssqlConnect()',$idForError,0,mssql_get_last_message());
		}
		die(MYSQL_ERROR_MESSAGE."\n<!--
			 Debug: Error selectiong database [".$dbDatabaseTemp."] on host [".$dbHostTemp."] as [".$dbUserTemp."]. [id $idForError]
			 Debug: MS SQL Error - ".mssql_get_last_message()."
			 -->");
	}
	return $tempLink;
}


/**
 * FUNCTION: mssqlQuery - queries the database using the current connection
 *
 * @param string $query
 * @param string $idForError
 * @param int[optional] $linkIdentifier
 * @param bool[optional] $
 * @return result
 */
function mssqlQuery($query,$idForError='',$linkIdentifier=null,$dieOnError=true){

	//replace if necessary - database items
	$dbsToCheck = array('web_to_print','intranet','intranet_client_side');
	foreach($dbsToCheck as $dbToCheck){
		if(strpos($query,$dbsToCheck.'.') && !strpos($query,$dbsToCheck.'.dbo.')){
			$query = str_replace($dbsToCheck.'.',$dbsToCheck.'.dbo.',$query);
		}
	}
	//replace if necessary - NOW() timestamp function
	if(strpos($query,'NOW()')){
		$query = str_replace('NOW()','GETDATE()',$query);
	}

	if($linkIdentifier){
		$result = @mssql_query($query,$linkIdentifier);
	} else {
		$result = @mssql_query($query);
	}
	if(!$result){
		if(MYSQL_DEBUG === true){
			die("<br><b>Debug: </b>MS SQL Error - ".mssql_get_last_message()."<br/>Database Error : Unable to query [id $idForError]<br/><br/>$query\n");
		}
		if(MYSQL_DEBUG_BY_EMAIL === true){
			sendDatabaseErrorEmail('mssqlQuery()',$idForError,0,mssql_get_last_message(),$query);
		}
		if($dieOnError){
			die(MYSQL_ERROR_MESSAGE."\n<!--Debug: MS SQL Error - ".mssql_get_last_message()."\nDatabase Error : Unable to query [id $idForError]\n\n$query\n-->");
		}
	}
	return $result;
}

function mssqlFreeResult($result){
	mssql_free_result($result);
}

function mssqlLastInsertID($linkIdentifier=null){
	$result = mssqlQuery("SELECT SCOPE_IDENTITY() AS last_insert_id",'mssqlLastInsertID()',$linkIdentifier);
	// get the last insert id
	$data = mssql_fetch_assoc($result);
	mssqlFreeResult($result);
	return $data['last_insert_id'];
}
?>