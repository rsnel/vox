<?php
require('system.php');
require('html.php');
require('common.php');

enforce_staff_rights();

if (isset($_GET['week_id'])) {
	$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE week_id = ? AND rooster_zichtbaar= 1", $_GET['week_id']);
	if (!$week_id) fatal("week niet zichtbaar in rooster");
} else {
	$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE rooster_zichtbaar= 1 ORDER BY time_year DESC, time_week DESC");
}

if (!$week_id) { ?>
Geen lesweken zichtbaar in rooster op dit moment.
<?php 		 exit;
}	

$default_week = db_single_field("SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) FROM voxdb.weken WHERE week_id = $week_id");

$weken = generate_weken_select($week_id, 'rooster_zichtbaar');

$rooster = db_query(<<<EOQ
SELECT time_hour uur, time_day-1 dag, CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname, ' (', ppl_login, ')') 'doc/vak'
FROM $voxdb.weken
JOIN $voxdb.time USING (time_year, time_week)
JOIN $voxdb.ppl
LEFT JOIN (
	SELECT claim.ppl_id, time_id, claim_id
	FROM $voxdb.claim
	JOIN $voxdb.avail USING (avail_id)
) AS bla USING (time_id, ppl_id)
WHERE week_id = $week_id AND ppl_type = 'leerling' AND claim_id IS NULL AND ppl_active = 1
-- GROUP BY time_id
ORDER BY uur, dag, ppl_surname, ppl_forename, ppl_prefix
EOQ
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
<p>Niet ingeschreven leerlingen in <?=$weken?>.
</form>

<?php $row = mysqli_fetch_assoc($rooster); ?>
<div class="tablemarkup">
<table>
<tr>
<th></th>
<?php foreach ($dagen as $dag) { ?><th><?=$dagnamen[$dag]?></th>
<?php } ?>
<?php foreach ($uren as $uur) { ?><tr>
<td style="vertical-align: top;"><?=$uur?></td>
<?php foreach ($dagen as $dag) { ?><td style="vertical-align: top;">
<?php while ($row && $row['uur'] == $uur && $row['dag'] == $dag) { ?>
<?=$row['doc/vak']?><br>
<?php $row = mysqli_fetch_assoc($rooster);
} ?>
</td>
<?php } ?>
</tr>
<?php } ?>
</tr>
</table>
</div>
<?

html_end();

?>
