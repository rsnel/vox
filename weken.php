<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('WEEKBEHEER');

$weken = db_query(<<<EOQ
SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week,
	IFNULL(GROUP_CONCAT(DISTINCT CONCAT(time_day, time_hour) ORDER BY time_day, time_hour), 'geen lesuren') lesuren,
	CONCAT('<input type="checkbox" name="doc[]" value="', week_id, '"', IF(status_doc, ' checked', ''), '>') doc,
	CONCAT('<input type="checkbox" name="lln[]" value="', week_id, '"', IF(status_lln, ' checked', ''), '>') lln,
	CONCAT('<input type="checkbox" name="rst[]" value="', week_id, '"', IF(rooster_zichtbaar, ' checked', ''), '>') 'rooster zichtbaar',
	COUNT(DISTINCT time_id, avail.ppl_id) docuur,
	COUNT(DISTINCT time_id, claim.ppl_id) lluur,
	CONCAT('<a href="week_ops.php?session_guid=$session_guid&amp;week_id=', week_id, '">ops</a>') ops
FROM $voxdb.weken
LEFT JOIN $voxdb.time USING (time_year, time_week)
LEFT JOIN $voxdb.avail USING (time_id)
LEFT JOIN $voxdb.claim USING (avail_id)
GROUP BY week_id
ORDER BY time_year DESC, time_week DESC
EOQ
);

$year_min = db_single_field("SELECT config_value FROM config WHERE config_key = 'YEAR_MIN'");
$year_max = db_single_field("SELECT config_value FROM config WHERE config_key = 'YEAR_MAX'");

$select = '<select name="year">';
for ($i = $year_min; $i <= $year_max; $i++) {
	$select .= '<option>'.$i.'</option>';
}
$select .= '</select>';

$select_wk = '<select name="week">';
for ($i = 0; $i <= 54; $i++) {
	$select_wk .= '<option>'.$i.'</option>';
}
$select_wk .= '</select>';

$options = db_all_assoc_rekey(<<<EOQ
SELECT week_id, CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week FROM $voxdb.weken ORDER BY time_year DESC, time_week DESC
EOQ
);

html_start(); ?>

<h3>Nieuwe week maken (met lesuren gebaseerd op bestaande week</h3>
<form action="do_new_week.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
Maak nieuwe week <?=$select?>wk<?=$select_wk?> en baseer de lesuren op <select name="basis_week_id"><?
foreach ($options as $week_id => $week) { ?>
	<option value="<?=$week_id?>"><?=$week?></option>
<? } ?></select><input type="submit" value="Maak"></form>
<p>Als je andere lesuren wilt, dan moet je dat direct aanpassen in de database in de tabel time in de rijen van de betreffende week.

<h3>Bestaande weken</h3>
<form action="do_weken.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<? db_dump_result($weken, false); ?>
<input type="submit" value="Vinkjes doc/lln opslaan">
</form>

<p>
De link in kolom ops leidt naar een scherm om
<ul>
<li>docentbeschikbaarheid te kopi&euml;ren uit een andere week</li>
<li>om getagde leerlingen automatisch in te schrijven</li>
<li>alle leerlinginschrijvingen of alle docentbeschikbaarheden te verwijderen</li>
</ul>


<?  html_end();
?>
