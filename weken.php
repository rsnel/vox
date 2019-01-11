<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('WEEKBEHEER');

$weken = db_query(<<<EOQ
SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week,
	GROUP_CONCAT(DISTINCT CONCAT(time_day, time_hour) ORDER BY time_day, time_hour) lesuren,
	CONCAT('<input type="checkbox" name="doc[]" value="', week_id, '"', IF(status_doc, ' checked', ''), '>') doc,
	CONCAT('<input type="checkbox" name="lln[]" value="', week_id, '"', IF(status_lln, ' checked', ''), '>') lln,
	IF(NOT status_lln, CONCAT('<a href="tags2week.php?session_guid=$session_guid&amp;week_id=', week_id, '">inschrijven lln op basis van tags in deze week</a>'),
		IF(NOT status_doc AND NOT status_lln,
			CONCAT('<a href="doc2week.php?session_guid=$session_guid&amp;week_id=', week_id, '">docentbeschikbaarheid kopi&euml;ren uit andere week</a>'),
			'')) `handigheid`,
	COUNT(DISTINCT avail_id) docvakuur, COUNT(claim_id) llvakuur
FROM $voxdb.weken
JOIN $voxdb.time USING (time_year, time_week)
LEFT JOIN $voxdb.avail USING (time_id)
LEFT JOIN $voxdb.claim USING (avail_id)
GROUP BY week_id
EOQ
);

$year_min = db_single_field("SELECT config_value FROM config WHERE config_key = 'YEAR_MIN'");
$year_max = db_single_field("SELECT config_value FROM config WHERE config_key = 'YEAR_MAX'");

html_start(); ?>
<form action="do_weken.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<? db_dump_result($weken, false); ?>
<input type="submit" value="Opslaan">
</form>

<p>
De kolom 'handigheid' kan gebruikt worden om
<ul>
<li>docentbeschikbaarheid te kopi&euml;ren uit een andere week (maar alleen als de week dicht staat voor iedereen en als de week nog leeg is)</li>
<li>om getagde leerlingen automatisch in te schrijven (maar alleen als de week dicht staat voor leerlingen).</li>
</ul>
<form action="do_new_week.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
Maak nieuwe week <select name="year">
<option>2019</option>
<option>2020</option>
<option>2021</option>
<option>2022</option>
<option>2023</option>
<option>2024</option>
<option>2025</option>
<option>2026</option>
<option>2027</option>

<?  html_end();
?>
