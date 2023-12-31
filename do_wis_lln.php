<?php
require('system.php');
enforce_permission('WEEKBEHEER');

header('Content-type: text/plain');

print_r($_POST);

$target = db_single_row(<<<EOQ
SELECT weken.*, COUNT(DISTINCT claim.ppl_id, time_id) lln
FROM $voxdb.weken
LEFT JOIN $voxdb.time USING (time_year, time_week)
LEFT JOIN $voxdb.avail USING (time_id)
LEFT JOIN $voxdb.claim USING (avail_id)
WHERE week_id = ?
EOQ
, $_POST['week_id']);

print_r($target);

if ($target['status_lln'] == 1) {
	$GLOBALS['session_state']['error_msg'] = "Wissen niet mogelijk, omdat de week open staat voor leerlingen.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}


if ($_POST['ikweet'] != 'IKWEETWATIKDOEENIKGANIETRIKINPANIEKBELLENDATHIJDEBACKUPVANVANNACHTTERUGMOETZETTEN') {
	$GLOBALS['session_state']['error_msg'] = "Alle leerlingaanmeldingen wissen is alleen mogelijk als je weet wat je doet.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}

db_exec("DELETE FROM $voxdb.claim WHERE avail_id IN ( SELECT avail_id FROM $voxdb.time JOIN $voxdb.weken USING (time_year, time_week) JOIN $voxdb.avail USING (time_id) WHERE week_id = ? )", $_POST['week_id']);

$GLOBALS['session_state']['success_msg'] = 'Alle leerlingaanmeldingen in deze week gewist';

header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);

?>
