<?php
require "config.php";
db_Connect("registrants.php");
function displayLineOut($lineArray,$separator='","'){
	print '"'.implode($separator,$lineArray).'"'."\n";
}
$booleanFields = array('bringing_pc'=>1,'reserving_swanky'=>1);
$result = db_Query("SELECT full_name,forum_name,email,reserving_swanky,coming_from,bringing_pc,bringing_general FROM registrants");
if(db_NumRows($result)){
	$headers = array();
	header('Content-Disposition: attachment; filename=penguincon-'.date('Y').'.csv');
	while(($record = db_NextRecord($result)) != false){
		if(!count($headers)){
			$headers = array_keys($record);
			displayLineOut($headers);
		}
		foreach($record as $key=>$val){
			if(isset($booleanFields[$key])){
				$record[$key] = ($val == 1) ? 'Y' : 'N';
			} else {
				$record[$key] = trim(str_replace('"',"'",stripslashes($val)));
			}
		}
		displayLineOut($record);
	}
} else {
	die("You need people, yo");
}
?>