<?
require('system.php');
require('html.php');
require('common.php');

enforce_logged_in();

if (isset($_GET['week_id'])) {
	$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE week_id = ? AND rooster_zichtbaar= 1", $_GET['week_id']);
	if (!$week_id) fatal("week niet zichtbaar in rooster");
} else {
	$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE rooster_zichtbaar= 1 ORDER BY time_year DESC, time_week DESC");
}

if (!$week_id) {
	html_start(); ?>
Geen lesweken zichtbaar in rooster op dit moment.
<?	html_end();
	exit;
}	

$default_week = db_single_field("SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) FROM voxdb.weken WHERE week_id = $week_id");

$onsubmit = "this.form.submit()";
$error = '';
if (!isset($_GET['q']) || $_GET['q'] == '') {
	if (check_student() && !isset($_GET['q'])) {
		$target = db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']);
		$urlencodeq = urlencode($target);
		$where = ' AND claim.ppl_id = '.$GLOBALS['session_state']['ppl_id'];
		$onsubmit = <<<EOS
document.getElementById(\'zoekbox\').disabled = true; this.form.submit();
EOS;
	} else {
		$urlencodeq = '';
		$target = 'de hele school';
		$where = '';
	}
} else {
	$urlencodeq = urlencode($_GET['q']);
	$target_info = db_single_row("SELECT ppl_id, ppl_login, ppl_type FROM $voxdb.ppl WHERE ppl_login = ?", $_GET['q']);
	if (!$target_info) {
		$error = '<b>zoekterm '.htmlenc($_GET['q']).' niet gevonden</b>';
		$target = 'de hele school';
		$where = '';
	} else {
		$target = $target_info['ppl_login'];
		if ($target_info['ppl_type'] == 'personeel') $where = ' AND avail.ppl_id = '.$target_info['ppl_id'];
		else $where = ' AND claim.ppl_id = '.$target_info['ppl_id'];
	}
}

$weken = generate_weken_select($week_id, 'rooster_zichtbaar', $onsubmit);

$rooster = db_query(<<<EOS
SELECT time_hour uur, time_day-1 dag, CONCAT(ppl_login, '/', GROUP_CONCAT(DISTINCT subj_abbrev ORDER BY subj_abbrev), ' (<a href="klassenlijst.php?session_guid=$session_guid&amp;time_id=', time_id, '&amp;ppl_id=', avail.ppl_id, '&amp;week_id=$week_id&amp;q=$urlencodeq">', COUNT(DISTINCT claim.ppl_id), '</a>)') 'doc/vak'
FROM $voxdb.weken
JOIN $voxdb.time USING (time_year, time_week)
JOIN $voxdb.avail USING (time_id)
JOIN $voxdb.ppl USING (ppl_id)
JOIN $voxdb.subj USING (subj_id)
LEFT JOIN $voxdb.claim USING (avail_id)
WHERE week_id = $week_id$where
GROUP BY time_id, ppl_login
ORDER BY uur, dag, 'doc/vak'
EOS
);

$dagen = db_single_field(<<<EOQ
SELECT GROUP_CONCAT(DISTINCT time_day-1 ORDER BY time_day)
FROM $voxdb.weken
JOIN $voxdb.time USING (time_year, time_week)
WHERE week_id = $week_id
EOQ
);

$uren = db_single_field(<<<EOQ
SELECT GROUP_CONCAT(DISTINCT time_hour ORDER BY time_hour)
FROM $voxdb.weken
JOIN $voxdb.time USING (time_year, time_week)
WHERE week_id = $week_id
EOQ
);

$dagnamen = array ('ma', 'di', 'wo', 'do', 'vr', 'za', 'zo');

if ($dagen == '' || $uren == '') fatal("geen uren/lesdagen in rooster");
$dagen = explode(',', $dagen);
$uren = explode(',', $uren);

html_start();

?>
<form method="GET" accept-charset="UTF-8">
<input type="hidden" name="session_guid" value="<?=$GLOBALS['session_guid']?>">
<p>Rooster in <?=$weken?> van <?=$target?>.
<input type="submit" value="Zoek"><input id="zoekbox" type="text" name="q" placeholder="llnr of docent" autofocus><?=$error?>
</form>

<? $row = mysqli_fetch_assoc($rooster); ?>
<div class="tablemarkup">
<table>
<tr>
<th></th>
<? foreach ($dagen as $dag) { ?><th><?=$dagnamen[$dag]?></th>
<? } ?>
<? foreach ($uren as $uur) { ?><tr>
<td style="vertical-align: top;"><?=$uur?></td>
<? foreach ($dagen as $dag) { ?><td style="vertical-align: top;">
<? while ($row && $row['uur'] == $uur && $row['dag'] == $dag) { ?>
<?=$row['doc/vak']?><br>
<? $row = mysqli_fetch_assoc($rooster);
} ?>
</td>
<? } ?>
</tr>
<? } ?>
</tr>
</table>
</div>
<?

html_end();

?>
