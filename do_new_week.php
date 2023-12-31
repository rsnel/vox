<?php
require('system.php');
enforce_permission('WEEKBEHEER');

header('Content-type: text/plain');

print_r($_POST);

$exists = db_single_field("SELECT week_id FROM $voxdb.weken WHERE time_year = ? AND time_week = ?", $_POST['year'], $_POST['week']);

if ($exists) {
	$GLOBALS['session_state']['error_msg'] = "Week {$_POST['year']}wk{$_POST['week']} bestaat al.";
	header('Location: weken.php?session_guid='.$session_guid);
	exit;
}

db_exec("INSERT INTO $voxdb.weken ( time_year, time_week ) VALUES ( ?, ? )", $_POST['year'], $_POST['week']);
$week_id = mysqli_insert_id($GLOBALS['db']);

db_exec("INSERT INTO $voxdb.time ( time_year, time_week, time_day, time_hour ) SELECT ?, ?, time_day, time_hour FROM $voxdb.weken JOIN $voxdb.time USING (time_year, time_week) WHERE week_id = ?",  $_POST['year'], $_POST['week'], $_POST['basis_week_id']);


echo("week_id=$week_id\n");

header('Location: weken.php?session_guid='.$session_guid);

?>
