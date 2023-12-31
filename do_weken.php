<?php
require('system.php');
enforce_permission('WEEKBEHEER');

header('Content-type: text/plain');

$status = db_all_assoc_rekey("SELECT week_id, status_doc, status_lln, rooster_zichtbaar FROM $voxdb.weken");

print_r($_POST);
//print_r($status);

if (!isset($_POST['doc'])) $_POST['doc'] = array();
else if (!is_array($_POST['doc'])) fatal("impossible!");

if (!isset($_POST['lln'])) $_POST['lln'] = array();
else if (!is_array($_POST['lln'])) fatal("impossible!");

if (!isset($_POST['rst'])) $_POST['rst'] = array();
else if (!is_array($_POST['rst'])) fatal("impossible!");

foreach ($status as $week_id => $info) {
	echo($week_id."\n");
	if (in_array($week_id, $_POST['doc']) && !$info['status_doc']) {
		db_exec("UPDATE $voxdb.weken SET status_doc = 1 WHERE week_id = ?", $week_id);
		echo("zet doc aan\n");
	}
	if (in_array($week_id, $_POST['lln']) && !$info['status_lln']) {
		db_exec("UPDATE $voxdb.weken SET status_lln = 1 WHERE week_id = ?", $week_id);
		echo("zet lln aan\n");
	}
	if (in_array($week_id, $_POST['rst']) && !$info['rooster_zichtbaar']) {
		db_exec("UPDATE $voxdb.weken SET rooster_zichtbaar = 1 WHERE week_id = ?", $week_id);
		echo("zet rooster aan\n");
	}
	if (!in_array($week_id, $_POST['doc']) && $info['status_doc']) {
		db_exec("UPDATE $voxdb.weken SET status_doc = 0 WHERE week_id = ?", $week_id);
		echo("zet doc uit\n");
	}
	if (!in_array($week_id, $_POST['lln']) && $info['status_lln']) {
		db_exec("UPDATE $voxdb.weken SET status_lln = 0 WHERE week_id = ?", $week_id);
		echo("zet lln uit\n");
	}
	if (!in_array($week_id, $_POST['rst']) && $info['rooster_zichtbaar']) {
		db_exec("UPDATE $voxdb.weken SET rooster_zichtbaar = 0 WHERE week_id = ?", $week_id);
		echo("zet rooster uit\n");
	}
	print_r($info);
}


$GLOBALS['session_state']['success_msg'] = 'Vinkjes opgeslagen';

header('Location: weken.php?session_guid='.$session_guid);

?>
