<?
require('system.php');
require('html.php');

/*
$weken = db_query(<<<EOQ
SELECT DISTINCT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week FROM $voxdb.time
EOQ
);

$options = array();
while ($assoc = mysqli_fetch_assoc($weken)) {
	print_r($assoc);
}
 */

if (!check_logged_in()) {
	html_start();
	html_end();
	exit;
}

function do_staff() {
	global $voxdb, $session_guid;
	$default_week = db_single_field("SELECT config_value FROM config WHERE config_key = 'DEFAULT_WEEK_DOC'");

	$uren = db_query(<<<EOQ
SELECT * FROM $voxdb.time WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
	, $default_week);

	$select = '';
	$join = '';

	while ($row = mysqli_fetch_assoc($uren)) {
		$du = $row['time_day'].$row['time_hour'];
		$select .= <<<EOS
, IF(c$du.avail_id IS NULL, CONCAT('<input type="checkbox" name="time-{$row['time_id']}-', subj.subj_id, '" value="1"', IF(a$du.avail_id IS NULL, '', ' checked'),'>'), 'X') $du
EOS;
		$join .= <<<EOJ
LEFT JOIN $voxdb.avail AS a$du ON a$du.time_id = {$row['time_id']} AND a$du.ppl_id = {$GLOBALS['session_state']['ppl_id']} AND a$du.subj_id = subj.subj_id
LEFT JOIN (
	SELECT avail_id, COUNT(ppl_id) FROM $voxdb.claim
	GROUP BY avail_id
) AS c$du ON c$du.avail_id = a$du.avail_id

EOJ;
	}

	$rooster = db_query(<<<EOQ
SELECT subj_abbrev vak$select
FROM $voxdb.subj
$join
EOQ
	);

	html_start();

?>
<p>Beschikbaarheid docent <?= db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = {$GLOBALS['session_state']['ppl_id']}")?> in <?=$default_week?>.

<form method="POST" accept-charset="UTF-8" action="do_avail.php?session_guid=<?=$session_guid?>">
<div class="tablemarkup"><?  db_dump_result($rooster); ?></div>
<input type="hidden" name="week" value="<?=$default_week?>">
<input type="hidden" name="ppl_id" value="<?=$GLOBALS['session_state']['ppl_id']?>">
<input type="submit" value="Opslaan">
</form>
<? 
html_end();
}

function do_student() {
	global $voxdb, $session_guid;
	$default_week = db_single_field("SELECT config_value FROM config WHERE config_key = 'DEFAULT_WEEK_DOC'");

	$uren = db_query(<<<EOQ
SELECT * FROM $voxdb.time WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
	, $default_week);

	$select = '';
	$join = '';
	$where = 'FALSE';

	while ($row = mysqli_fetch_assoc($uren)) {
		$du = $row['time_day'].$row['time_hour'];
		$select .= <<<EOS
, IFNULL(CONCAT('<input type="radio" name="ppl_id-', a$du.ppl_id, '"> ', a$du.subj_ids), '') $du
EOS;
		$join .= <<<EOJ
LEFT JOIN (
	SELECT ppl_id, GROUP_CONCAT(subj_abbrev) subj_ids
	FROM $voxdb.avail
	JOIN $voxdb.subj USING (subj_id)
	WHERE time_id = {$row['time_id']}
	GROUP BY ppl_id
) AS a$du USING (ppl_id)

EOJ;
		$where .= <<<EOW
 OR a$du.ppl_id IS NOT NULL
EOW;
	}
	
	$rooster = db_query(<<<EOQ
SELECT ppl_login docent$select
FROM $voxdb.ppl
$join
WHERE ppl_type = 'personeel' AND ( $where )
EOQ
);
	html_start();
	echo($default_week);
	?><div class="tablemarkup"><?
	db_dump_result($rooster);
	?></div><?
	html_end();
}


if (check_staff()) do_staff();
else if (check_student()) do_student();


?>
