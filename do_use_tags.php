<?php
require('system.php');
enforce_permission('WEEKBEHEER');

$week = db_single_row(<<<EOQ
SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week,
        IFNULL(GROUP_CONCAT(DISTINCT CONCAT(time_day, time_hour) ORDER BY time_day, time_hour), 'geen lesuren') lesuren,
        status_doc,
        status_lln,
        COUNT(DISTINCT time_id, avail.ppl_id) docuur,
        COUNT(DISTINCT time_id, claim.ppl_id) lluur
FROM $voxdb.weken
LEFT JOIN $voxdb.time USING (time_year, time_week)
LEFT JOIN $voxdb.avail USING (time_id)
LEFT JOIN $voxdb.claim USING (avail_id)
WHERE week_id = ?
EOQ
, $_POST['week_id']);

$tags = db_all_assoc_rekey(<<<EOQ
SELECT tag_id, GROUP_CONCAT(DISTINCT ppl2tag.ppl_id) ppl_ids, tag_name, GROUP_CONCAT(DISTINCT avail_id) avail_ids
FROM $voxdb.ppl2tag 
JOIN $voxdb.tag USING (tag_id)
JOIN $voxdb.time ON CONCAT(time_day, time_hour) = SUBSTR(tag_name, 1, 3)
JOIN $voxdb.weken USING (time_year, time_week)
JOIN $voxdb.avail USING (time_id)
JOIN $voxdb.ppl ON avail.ppl_id = ppl.ppl_id
WHERE tag_type = 'ROOSTERLLN' AND week_id = ? AND ppl.ppl_login = SUBSTR(tag_name, 5)
GROUP BY tag_id
EOQ
, $_POST['week_id']);

$tags2 = db_query(<<<EOQ
SELECT CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname, ' (', ppl_login, ')') leerling, GROUP_CONCAT(tag_name) tags, COUNT(SUBSTR(tag_name, 1, 3)) - COUNT(DISTINCT SUBSTR(tag_name, 1, 3)) dubbel
FROM $voxdb.tag
JOIN $voxdb.ppl2tag USING (tag_id)
JOIN $voxdb.ppl USING (ppl_id)
WHERE tag_type = 'ROOSTERLLN'
GROUP BY ppl_id
HAVING dubbel > 0
ORDER BY tag_order, tag_name
EOQ
);

if ($week['lluur'] || $week['status_lln'] || !$week['docuur']) {
	$GLOBALS['session_state']['error_msg'] = "Het inschrijven van leerlingen op basis van tags is niet mogelijk, omdat er al leerlingen ingeschreven zijn in deze week EN/OF de week open staat voor leerlingen EN/OF er geen docenten ingeroosterd zijn.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}

if (mysqli_num_rows($tags2)) { 
	$GLOBALS['session_state']['error_msg'] = "Tagconflict, lln kunnen niet ingeschreven worden.";
	header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);
	exit;
}


header('Content-type: text/plain');

print_r($_POST);

//print_r($tags);

foreach ($tags as $tag_id => $info) {
	if (!$info['ppl_ids']) continue; // geen leerlingen
	if (!$info['avail_ids']) continue; // niet in rooster
	foreach (explode(',', $info['ppl_ids']) as $ppl_id) {
		foreach (explode(',', $info['avail_ids']) as $avail_id) {
			//echo("enter ppl_id=$ppl_id op avail_id=$avail_id\n");
			db_exec("INSERT INTO $voxdb.claim ( avail_id, ppl_id, claim_locked ) VALUES ( ?, ?, 1 )", $avail_id, $ppl_id);
		}
	}
}

header('Location: week_ops.php?session_guid='.$session_guid.'&week_id='.$_POST['week_id']);

?>
