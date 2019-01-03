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

if (check_staff()) {
	$default_week = db_single_field("SELECT config_value FROM config WHERE config_key = 'DEFAULT_WEEK_DOC'");
} else {
	$default_week = db_single_field("SELECT config_value FROM config WHERE config_key = 'DEFAULT_WEEK_LLN'");
}

if (check_logged_in()) {

$uren = db_query(<<<EOQ
SELECT * FROM $voxdb.time WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
, $default_week);

if (check_staff()) {
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
}

}

html_start();

if (check_staff()) { ?>
<p>Beschikbaarheid docent <?= db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = {$GLOBALS['session_state']['ppl_id']}")?> in <?=$default_week?>.

<form method="POST" accept-charset="UTF-8" action="do_avail.php?session_guid=<?=$session_guid?>">
<?  db_dump_result($rooster); ?>
<input type="hidden" name="week" value="<?=$default_week?>">
<input type="hidden" name="ppl_id" value="<?=$GLOBALS['session_state']['ppl_id']?>">
<input type="submit" value="Opslaan">
</form>
<? } ?>
<?  html_end();


?>
