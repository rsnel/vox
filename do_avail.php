<?php
require('system.php');
enforce_staff();

header('Content-type: text/plain');

if ($GLOBALS['session_state']['ppl_id'] != $_POST['ppl_id']) fatal("impersonator");
//print_r($_POST);

//exit;

if (!db_single_field("SELECT status_doc FROM $voxdb.weken WHERE week_id = ?", $_POST['week_id'])) fatal("permission denied");

$stuff = db_query("SELECT CONCAT('time-', time.time_id, '-', subj.subj_id) time, time.time_id, subj.subj_id, avail.avail_id FROM $voxdb.weken JOIN $voxdb.time USING (time_week, time_year) JOIN $voxdb.subj LEFT JOIN $voxdb.avail ON time.time_id = avail.time_id AND subj.subj_id = avail.subj_id AND avail.ppl_id = ? WHERE week_id = ?", $_POST['ppl_id'], $_POST['week_id']);

while ($row = mysqli_fetch_assoc($stuff)) {
	//print_r($row);
	if ($row['avail_id'] && !isset($_POST[$row['time']])) db_exec("DELETE FROM $voxdb.avail WHERE avail_id = ?", $row['avail_id']);
	else if (!$row['avail_id'] && isset($_POST[$row['time']])) db_exec("INSERT INTO $voxdb.avail ( ppl_id, subj_id, time_id ) VALUES ( ?, ?, ? )", $_POST['ppl_id'], $row['subj_id'], $row['time_id']);
}

$stuff2 = db_query("SELECT avail_id, time_id, capacity FROM $voxdb.avail JOIN $voxdb.time USING (time_id) JOIN $voxdb.weken USING (time_year, time_week) WHERE avail.ppl_id = ? AND week_id = ?", $_POST['ppl_id'], $_POST['week_id']);

while ($row = mysqli_fetch_assoc($stuff2)) {
	if (!isset($_POST['time-'.$row['time_id']])) fatal("impossible error!");
	$cap = $_POST['time-'.$row['time_id']];
	if ($cap != $row['capacity']) db_exec("UPDATE $voxdb.avail SET capacity = ? WHERE avail_id = ?", $cap, $row['avail_id']);
	//print_r($row);
}

$stuff3 = db_all_assoc_rekey(<<<EOQ
SELECT time_id, lok_id
FROM $voxdb.time
JOIN $voxdb.weken USING (time_year, time_week)
LEFT JOIN (
	SELECT time_id, lok_id
	FROM $voxdb.ppl2time2lok
	WHERE ppl_id = ?
) AS bla USING (time_id)
WHERE week_id = ?
EOQ
, $_POST['ppl_id'], $_POST['week_id']);

foreach ($stuff3 as $time_id => $lok_id) {
	if (!isset($_POST['lok-time-'.$time_id])) fatal('impossible error');
	$newlok = $_POST['lok-time-'.$time_id];
	db_exec("DELETE FROM $voxdb.ppl2time2lok WHERE ppl_id = ? AND time_id = ?",  $_POST['ppl_id'],$time_id);
	if ($newlok) db_exec("INSERT INTO $voxdb.ppl2time2lok ( ppl_id, time_id, lok_id ) VALUES ( ?, ?, ? )", $_POST['ppl_id'], $time_id, $newlok);
}

// print_r($_POST);
//print_r($stuff3);
//exit;

$GLOBALS['session_state']['success_msg'] = 'Beschikbaarheid opgeslagen';

header('Location: index.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);


?>
