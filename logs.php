<?php
require "config.php";
db_Connect('logs.php');
print "<div style='font-family:Arial;font-size:10pt;'>";
$query = "SELECT COUNT(*) AS num, action FROM logs\n";
if(TEST_SITE === false){
	$query .= "WHERE ip_v4_address <> '66.117.212.154'\n";
}
$query .= "GROUP BY action ORDER BY action ASC";
$result = db_Query($query,'logs q1');
if(db_NumRows($result)){
	while(($data = db_NextRecord($result)) != false){
		print ucwords(str_replace('_',' ',$data['action'])).": ".$data['num'].'<br/>';
	}
}
print "</div>";
db_DisconnectAll();

?>