<?
require('system.php');
require('html.php');

//enforce_logged_in();
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
, $_GET['week_id']);

if (!$week) fatal("gevraagde week bestaat niet");

$options = db_all_assoc_rekey(<<<EOQ
SELECT week_id, CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) week FROM $voxdb.weken ORDER BY time_year ASC, time_week ASC
EOQ
);


$tags = db_query(<<<EOQ
SELECT tag_name, IF((
	SELECT time_id
	 FROM $voxdb.time
	JOIN $voxdb.weken USING (time_year, time_week)
	JOIN $voxdb.avail USING (time_id)
	JOIN $voxdb.ppl USING (ppl_id)
	WHERE week_id = ?
	AND CONCAT(time_day, time_hour) = SUBSTR(tag_name, 1, 3)
	AND ppl_login = SUBSTR(tag_name, 5)
	), 'OK', 'niet OK') ok, COUNT(ppl_id) lln
-- , CONCAT('<input type="checkbox" checked name="tag_id[]" value="', tag_id, '">') actie
FROM $voxdb.tag
JOIN $voxdb.ppl2tag USING (tag_id)
WHERE tag_type = 'ROOSTERLLN'
GROUP BY tag_id
ORDER BY tag_order, tag_name
EOQ
, $_GET['week_id']);

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

html_start(); ?>

<a href="weken.php?session_guid=<?=$session_guid?>">&lt;--- terug naar weken</a>

<h3>Ops voor <?=$week['week']?>.</h3>

Lesuren: <?=$week['lesuren']?>. (lesuren kunnen bewerkt worden in database, voor als er in een week bijvoorbeeld een dag uitvalt)

<p>De week is <b><?=($week['status_doc'])?'wel':'niet'?></b> beschikbaar voor docenten om zich op te geven. Aantal docenturen <?=$week['docuur']?>.

<p>De week is <b><?=($week['status_lln'])?'wel':'niet'?></b> beschikbaar voor leerlingen om zich aan te melden. Aantal leerlinguren <?=$week['lluur']?>.
 
<h4>Beschikbaarheid docenten kopi&euml;ren uit andere week</h4>

<? if ($week['docuur'] || $week['status_doc']) { ?>
Het kopi&euml;ren uit andere week is niet mogelijk, omdat er al minstens 1 docentuur staat in deze week EN/OF omdat de week open staat voor docenten. <b>Let op:</b> Als de week waar je naartoe kopieert een lesuur mist, dan worden de aanmeldingen die in de bronweek op dat uur staan, niet gekopieerd.
<? } else { ?>
<form accept-charset="UTF-8" method="POST" action="do_kopieer_doc.php?session_guid=<?=$session_guid?>">
Kopieer de beschikbaarheid van de docenten uit week <select name="basis_week_id"><?
foreach ($options as $week_id => $name) {
	if ($week_id == $_GET['week_id']) continue; ?>
        <option value="<?=$week_id?>"><?=$name?></option>
		<? } ?></select><input type="hidden" name="week_id" value="<?=$_GET['week_id']?>"><input type="submit" value="Kopieer">
<? } ?>

<h4>Leerlingen automatisch inschrijven op basis van tags</h4>

<? if ($week['lluur'] || $week['status_lln'] || !$week['docuur']) { ?>
Het inschrijven van leerlingen op basis van tags is niet mogelijk, omdat er al leerlingen ingeschreven zijn in deze week EN/OF de week open staat voor leerlingen EN/OF er geen docenten ingeroosterd zijn.
<? } else { ?>
<h5>Beschikbaarheid docent op getagde uren</h5>
<form accept-charset="UTF-8" method="POST" action="do_use_tags.php?session_guid=<?=$session_guid?>">
Hieronder staat per tag of inschrijving mogelijk is. Inschrijving is mogelijk als de combinatie uur-docent deze week in het rooster bestaat.
<? db_dump_result($tags); ?>
<p>Als een docent vervangen wordt in het rooster, dan kan de inschrijving toch automatisch plaatsvinden door de tag tijdelijk te hernoemen in de database.

<p>Alle bovenstaande rijen waar "niet OK" staat, kunnen niet verwerkt worden in het rooster. Bijvoorbeeld, omdat het lesuur niet bestaat of omdat de docent niet in het rooster staat op het betreffende uur.

<h5>Conflicterende tags</h5>

Hier komt informatie als minstens 1 leerling minstens 1 keer minstens 2 lessen op hetzelfde uur heeft.

<div class="tablemarkup"><? db_dump_result($tags2); ?></div>
 
<h5>Inschrijven</h5>

<? if (mysqli_num_rows($tags2)) { ?>
Inschrijven van leerlingen op basis van tags niet mogelijk, wegens bovenstaand conflict.
<? } else { ?>
<input type="hidden" name="week_id" value="<?=$_GET['week_id']?>">
<input type="submit" value="Leerlingen inschrijven in deze week op basis van tags">
Deze inschrijvingen worden direct vergrendeld, zodat leerlingen zichzelf niet kunnen weghalen bij de getagde lessen.
<? } ?>
</form>
<? } ?>
<h4>Gevaarlijke opties</h4>

<? if (!$week['docuur'] || $week['lluur'] || $week['status_lln']) { ?>
<p>Het wissen van de docentbeschikbaarheden is niet mogelijk, omdat er al leerlingen ingeschreven zijn in deze week EN/OF de week open staat voor leerlingen OF er geen docenten zijn aangemeld.
<? } else { ?>
<p><form accept-charset="UTF-8" method="POST" action="do_wis_doc.php?session_guid=<?=$session_guid?>">
Hier kun je alle beschikbaarheden van docenten in deze week wissen. Om dit de toen moet je: IKWEETWATIKDOEENIKGANIETRIKINPANIEKBELLENDATHIJDEBACKUPVANVANNACHTTERUGMOETZETTEN in het tekstveld typen en op de knop kikken. <b>Deze handeling kan niet ongedaan gemaakt worden.</b>

<input type="hidden" name="week_id" value="<?=$_GET['week_id']?>">
<br>Ik weet wat ik doe: <input type="text" name="ikweet" value="">
<br><input type="submit" value="ALLE DOCENTENAANMELDINGEN IN DEZE WEEK WISSEN">
<? } ?>

<? if (!$week['lluur'] || $week['status_lln']) { ?>
<p>Het wissen van de leerlingaanmeldingen is niet mogelijk, omdat de week open staat voor leerlingen OF omdat er geen leerlingen aangemeld zijn.
<? } else { ?>
<form accept-charset="UTF-8" method="POST" action="do_wis_lln.php?session_guid=<?=$session_guid?>">
<p>Hier kun je alle aanmeldingen van leerlingen in deze week wissen. Om dit de toen moet je: IKWEETWATIKDOEENIKGANIETRIKINPANIEKBELLENDATHIJDEBACKUPVANVANNACHTTERUGMOETZETTEN in het tekstveld typen en op de knop kikken. <b>Deze handeling kan niet ongedaan gemaakt worden.</b>

<input type="hidden" name="week_id" value="<?=$_GET['week_id']?>">
<br>Ik weet wat ik doe: <input type="text" name="ikweet" value="">
<br><input type="submit" value="ALLE LEERLINGAANMELDINGEN IN DEZE WEEK WISSEN">
<? } ?>

<?  html_end();
?>
