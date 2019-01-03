<?
require('system.php');
enforce_staff();

header('Content-type: text/plain');

if ($GLOBALS['session_state']['ppl_id'] != $_POST['ppl_id']) fatal("impersonator");
print_r($_POST);

//exit;

$stuff = db_query("SELECT CONCAT('time-', time.time_id, '-', subj.subj_id) time, time.time_id, subj.subj_id, avail.avail_id FROM $voxdb.time JOIN $voxdb.subj LEFT JOIN $voxdb.avail ON time.time_id = avail.time_id AND subj.subj_id = avail.subj_id AND avail.ppl_id = ? WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?", $_POST['ppl_id'], $_POST['week']);

while ($row = mysqli_fetch_assoc($stuff)) {
	//print_r($row);
	if ($row['avail_id'] && !isset($_POST[$row['time']])) db_exec("DELETE FROM $voxdb.avail WHERE avail_id = ?", $row['avail_id']);
	else if (!$row['avail_id'] && isset($_POST[$row['time']])) db_exec("INSERT INTO $voxdb.avail ( ppl_id, subj_id, time_id ) VALUES ( ?, ?, ? )", $_POST['ppl_id'], $row['subj_id'], $row['time_id']);
}

$stuff2 = db_query("SELECT avail_id, time_id, capacity FROM $voxdb.avail JOIN $voxdb.time USING (time_id) WHERE avail.ppl_id = ? AND CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?", $_POST['ppl_id'], $_POST['week']);

while ($row = mysqli_fetch_assoc($stuff2)) {
	if (!isset($_POST['time-'.$row['time_id']])) fatal("impossible error!");
	$cap = $_POST['time-'.$row['time_id']];
	if ($cap != $row['capacity']) db_exec("UPDATE $voxdb.avail SET capacity = ? WHERE avail_id = ?", $cap, $row['avail_id']);
	//print_r($row);
}

$GLOBALS['session_state']['success_msg'] = 'Beschikbaarheid opgeslagen';

header('Location: index.php?session_guid='.$session_guid.'&week='.$_POST['week']);


?>
