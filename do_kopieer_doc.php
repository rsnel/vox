<?php
require('system.php');
enforce_permission('WEEKBEHEER');

header('Content-type: text/plain');

print_r($_POST);

$target = db_single_row(<<<EOQ
SELECT weken.*, COUNT(DISTINCT avail.ppl_id, time_id) docs
FROM $voxdb.weken
LEFT JOIN $voxdb.time USING (time_year, time_week)
LEFT JOIN $voxdb.avail USING (time_id)
WHERE week_id = ?
EOQ
, $_POST['week_id']);

print_r($target);

if ($target['status_doc'] == 1) {
	$GLOBALS['session_state']['error_msg'] = "Kopi&euml;ren niet mogelijk, omdat de week open staat voor docenten.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}


if ($target['docs'] > 0) {
	$GLOBALS['session_state']['error_msg'] = "Kopi&euml;ren niet mogelijk, omdat er al docenten ingeschreven zijn in deze week.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}

db_exec("INSERT IGNORE INTO $voxdb.avail ( time_id, ppl_id, subj_id, capacity ) SELECT (SELECT time_id FROM $voxdb.time AS time2 JOIN $voxdb.weken AS weken2 ON time2.time_year = weken2.time_year AND time2.time_week = weken2.time_week WHERE weken2.week_id = ? AND time2.time_day = time.time_day AND time2.time_hour = time.time_hour ), ppl_id, subj_id, capacity FROM $voxdb.avail JOIN $voxdb.time USING (time_id) JOIN $voxdb.weken USING (time_year, time_week) WHERE week_id = ?", $_POST['week_id'], $_POST['basis_week_id']);

header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);

?>
