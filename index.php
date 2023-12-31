<?
require('system.php');
require('html.php');
require('common.php');

if (!check_logged_in()) {
	html_start(); ?>
Wachtwoord vergeten? Zoek een docent als je leerling bent en zoek een beheerder als je docent bent.
<?
	html_end();
	exit;
}

function do_staff() {
	global $voxdb, $session_guid;

	if (isset($_GET['week_id'])) {
		$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE week_id = ? AND status_doc = 1", $_GET['week_id']);
		if (!$week_id) fatal("week niet toegankelijk voor docenten");
	} else {
		$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE status_doc = 1 ORDER BY time_year DESC, time_week DESC");
	}

	if (!$week_id) {
		html_start(); ?>
Geen lesweken toegankelijk voor docenten om inschrijvingen in te doen op dit moment.
<? 		html_end();
	       	return;
	}	

	$default_week = db_single_field("SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) FROM $voxdb.weken WHERE week_id = $week_id");

	$weken = generate_weken_select($week_id, 'status_doc');

	$uren = db_query(<<<EOQ
SELECT * FROM $voxdb.time WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
	, $default_week);

	$select = '';
	$join = '';

	$select2 = '';
	$join2 = '';

	$select3 = '';

	while ($row = mysqli_fetch_assoc($uren)) {
		$du = $row['time_day'].$row['time_hour'];
		$select2 .= <<<EOS
, CONCAT('<input type="text" size="2" name="time-{$row['time_id']}" value="', IFNULL(MIN(a$du.capacity), 25), '">') $du
EOS;
		$join2 .= <<<EOJ
LEFT JOIN $voxdb.avail AS a$du ON a$du.time_id = {$row['time_id']} AND a$du.ppl_id = {$GLOBALS['session_state']['ppl_id']}

EOJ;
		$select3 .= <<<EOS
, (
	SELECT CONCAT('<select name="lok-time-{$row['time_id']}"><option value="">-</option>',
		GROUP_CONCAT(
			CONCAT('<option', IF(bla11.ppl2time2lok_id, ' selected', ''), ' value="', lok_id, '">', lok_afk, '</option>')
			SEPARATOR ''),
		'</select>')
	FROM $voxdb.lok
	LEFT JOIN (
		SELECT ppl2time2lok_id, lok_id
		FROM $voxdb.ppl2time2lok
		WHERE ppl_id = {$GLOBALS['session_state']['ppl_id']} AND time_id = {$row['time_id']}
	) AS bla11 USING (lok_id)
)
EOS;

		$select .= <<<EOS
, IF(c$du.avail_id IS NULL, CONCAT('<input class="avail" id="time-{$row['time_id']}-', subj.subj_id, '" type="checkbox" name="time-{$row['time_id']}-', subj.subj_id, '" value="1"', IF(a$du.avail_id IS NULL, '', ' checked'),'>'), CONCAT('<input type="checkbox" checked disabled><input type="hidden" name="time-{$row['time_id']}-', subj.subj_id, '" value="1">')) $du

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
SELECT '<b>cap.</b>' vak$select2
FROM $voxdb.time
$join2
WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = '$default_week'
UNION
SELECT '<b>lok.</b>'$select3
UNION
SELECT subj_abbrev vak$select
FROM $voxdb.subj
$join
EOQ
	);

	html_start();

?>
<form method="GET" accept-charset="UTF-8">
<input type="hidden" name="session_guid" value="<?=$GLOBALS['session_guid']?>">
<p>Beschikbaarheid docent <?= db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = {$GLOBALS['session_state']['ppl_id']}")?> in <?=$weken?>.
</form>

<form method="POST" accept-charset="UTF-8" action="do_avail.php?session_guid=<?=$session_guid?>">
<div class="tablemarkup"><?  db_dump_result($rooster); ?></div>
<input type="hidden" name="week_id" value="<?=$week_id?>">
<input type="hidden" name="ppl_id" value="<?=$GLOBALS['session_state']['ppl_id']?>">
<input type="submit" value="Opslaan">
</form>

<p>De maximale capaciteit van een docent staat standaard op 25. Als de maximale capaciteit is bereikt, dan kunnen leerlingen zich niet (meer) inschrijven. Docenten kunnen leerlingen nog wel inschrijven. Als het de bedoeling is dat leerlingen zichzelf niet kunnen inschrijven, dan dient de capaciteit op 0 te staan.

<p>Een disabled checkbox (<input type="checkbox" checked disabled>) betekent dat er leeringen bij jou zijn ingeschreven voor het betreffende vak. Het is pas mogelijk om de beschikbaarheid van de docent uit te zetten als de ingeschreven leerlingen zijn uitgeschreven.

<? 
	html_end();
}

function do_student() {
	global $voxdb, $session_guid;

	if (isset($_GET['week_id'])) {
		$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE week_id = ? AND status_lln = 1", $_GET['week_id']);
		if (!$week_id) fatal("week niet toegankelijk voor leerlingen");
	} else {
		$week_id = db_single_field("SELECT week_id FROM $voxdb.weken WHERE status_lln = 1 ORDER BY time_year DESC, time_week DESC");
	}

	if (!$week_id) { 
		html_start();?>
Geen lesweken toegankelijk om inschrijvingen in te doen voor leerlingen op dit moment.
<? 		html_end();
	      	return;
	}	

	$default_week = db_single_field("SELECT CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) FROM voxdb.weken WHERE week_id = $week_id");

	$weken = generate_weken_select($week_id, 'status_lln');

	$uren = db_query(<<<EOQ
SELECT * FROM $voxdb.time WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
	, $default_week);

	$not_su = 'TRUE';
	if (check_su()) $not_su = 'FALSE';

	$select = '';
	$join = '';
	$where = 'FALSE';

	$select2 = '';
	$join2 = '';

	$select3 = '';
	$join3 = '';

	while ($row = mysqli_fetch_assoc($uren)) {
		$du = $row['time_day'].$row['time_hour'];
		$select3 .= <<<EOS
, CONCAT('<input type="radio"', IF(BIT_OR(IFNULL(a$du.claim_locked, 0)) AND $not_su, ' disabled', ''), IF((SELECT claim_id FROM $voxdb.claim JOIN $voxdb.avail USING (avail_id) WHERE time_id = {$row['time_id']} AND claim.ppl_id = {$GLOBALS['session_state']['ppl_id']} LIMIT 1) IS NULL, ' checked', '') ,' name="time-{$row['time_id']}" value="ppl_id-0">') $du
EOS;
		if (!check_su()) {
			$select2 .= <<<EOS
, CONCAT('<input type="checkbox" disabled', IF(BIT_OR(IFNULL(a$du.claim_locked, 0)), ' checked', ''), '>', IF(BIT_OR(IFNULL(a$du.claim_locked, 0)), '<input type="hidden" name="lock[]" value="{$row['time_id']}">', '')) $du
EOS;
		} else {
			$select2 .= <<<EOS
, CONCAT('<input type="checkbox"', IF(BIT_OR(IFNULL(a$du.claim_locked, 0)), ' checked', ''), ' name="lock[]" value="{$row['time_id']}">') $du
EOS;

		}

		$join2 .= <<<EOJ
LEFT JOIN (
	SELECT time_id, claim_locked
	FROM $voxdb.claim
	JOIN $voxdb.avail USING (avail_id)
	WHERE time_id = {$row['time_id']} AND claim.ppl_id = {$GLOBALS['session_state']['ppl_id']}
) AS a$du USING (time_id)

EOJ;

		$join3 .= <<<EOJ
LEFT JOIN (
	SELECT time_id, claim_locked
	FROM $voxdb.claim
	JOIN $voxdb.avail USING (avail_id)
	WHERE time_id = {$row['time_id']} AND claim.ppl_id = {$GLOBALS['session_state']['ppl_id']}
) AS a$du USING (time_id)

EOJ;

		$select .= <<<EOS
, IFNULL(
	CONCAT(
		IF((a$du.locked OR (a$du.full AND NOT a$du.selected)) AND $not_su,
			CONCAT('<input type="radio" disabled',
				IF(a$du.selected, ' checked', ''),
				'>', IF(a$du.selected,
					CONCAT('<input type="hidden" name="time-{$row['time_id']}" value="ppl_id-', a$du.ppl_id, '">'), '')), CONCAT('<input type="radio" name="time-{$row['time_id']}" value="ppl_id-', a$du.ppl_id, '"', IF(a$du.selected, ' checked', ''), '> ')), ' ', IF(a$du.full AND NOT a$du.selected, '<del>', ''), a$du.subj_ids, IF(a$du.full AND NOT a$du.selected, '</del>', '')
-- , ' ', a$du.full, ' ', a$du.selected, ' ', a$du.locked
), '') $du

EOS;
		$join .= <<<EOJ
LEFT JOIN (
	SELECT avail.ppl_id, GROUP_CONCAT(DISTINCT subj_abbrev ORDER BY subj_abbrev) subj_ids, IFNULL(COUNT(DISTINCT claim.ppl_id) >= MIN(avail.capacity), avail.capacity) full, IFNULL(BIT_OR(claim.ppl_id = {$GLOBALS['session_state']['ppl_id']}), 0) selected, IFNULL(locked, 0) locked
	FROM $voxdb.avail
	JOIN $voxdb.subj USING (subj_id)
	LEFT JOIN $voxdb.claim USING (avail_id)
	LEFT JOIN (
		SELECT time_id, IFNULL(BIT_OR(claim_locked), 0) locked
		FROM $voxdb.claim
		JOIN $voxdb.avail USING (avail_id)
		WHERE claim.ppl_id = {$GLOBALS['session_state']['ppl_id']}
		GROUP BY time_id
	) AS locked USING (time_id)
	WHERE time_id = {$row['time_id']}
	GROUP BY avail.ppl_id
) AS a$du USING (ppl_id)

EOJ;
		$where .= <<<EOW
 OR a$du.ppl_id IS NOT NULL
EOW;
	}
	
	$rooster = db_query(<<<EOQ
SELECT '<b>locked</b>' AS `docent`$select2
FROM $voxdb.time
$join2
WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = '$default_week'
UNION
SELECT '<i>geen</i>' AS `docent`$select3
FROM $voxdb.time
$join3
UNION
SELECT ppl_login docent$select
FROM $voxdb.ppl
$join
WHERE ppl_type = 'personeel' AND ( $where )
ORDER BY docent
EOQ
);
	html_start();?>
<form method="GET" accept-charset="UTF-8">
<input type="hidden" name="session_guid" value="<?=$GLOBALS['session_guid']?>">
<p>Keuzes leerling <?= db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = {$GLOBALS['session_state']['ppl_id']}")?> in <?=$weken?>.
</form>
<form method="POST" accept-charset="UTF-8" action="do_claim.php?session_guid=<?=$session_guid?>">
<div class="tablemarkup"><?  db_dump_result($rooster); ?></div>
<input type="hidden" name="week_id" value="<?=$week_id?>">
<input type="hidden" name="ppl_id" value="<?=$GLOBALS['session_state']['ppl_id']?>">
<input type="submit" value="Opslaan">
</form>
<p>
Als de vakken zijn <del>doorgestreept</del> dan zit de docent vol. Als het vakje bij <b>locked</b> aan staat, dan kun je in de betreffende kolom niets wijzigen. Neem contact op met een docent als er iets niet klopt.
	<?
	html_end();
}


if (check_staff()) do_staff();
else if (check_student()) do_student();


?>
