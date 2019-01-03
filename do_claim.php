<?
require('system.php');
enforce_student();

header('Content-type: text/plain');

print_r($_POST);

if ($GLOBALS['session_state']['ppl_id'] != $_POST['ppl_id']) fatal("impersonator");

$uren = db_query(<<<EOQ
SELECT *
FROM $voxdb.time
WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ?
EOQ
        , $_POST['week']);

$ins = db_all_assoc_rekey(<<<EOQ
SELECT CONCAT('time-', time_id), avail.ppl_id, BIT_OR(claim_locked) locked
FROM $voxdb.claim
JOIN $voxdb.avail USING (avail_id)
JOIN $voxdb.time USING (time_id)
WHERE CONCAT(time_year, 'wk', LPAD(time_week, 2, '0')) = ? AND claim.ppl_id = ?
GROUP BY time_id, avail.ppl_id
EOQ
, $_POST['week'], $_POST['ppl_id']);

print_r($ins);

if (!isset($_POST['lock']) || !is_array($_POST['lock'])) $_POST['lock'] = array ();

while ($row = mysqli_fetch_assoc($uren)) {
	$verwijder = NULL;
	$idx = 'time-'.$row['time_id'];
	if (isset($_POST[$idx])) {
		$ppl_id = explode('-', $_POST[$idx])[1];
		if (isset($ins[$idx])) {
			if ($ins[$idx]['ppl_id'] == $ppl_id) {
				// geen wijziging
				echo("time $idx OK\n");
				goto ending;
			}
			// mag niet worden verwijderd
			if (!check_su() && $ins[$idx]['locked'] == 1) {
				$GLOBALS['session_state']['error_msg'] .= " $idx kan niet worden aangepast wegens lock";
				goto ending;
			}
			$verwijder = $ins[$idx]['ppl_id'];
		}
		echo("te verwijderen $verwijder\n");
		if ($ppl_id) {
			$avails = db_single_row("SELECT GROUP_CONCAT(avail_id) avail_ids, MIN(capacity) capacity FROM $voxdb.avail WHERE time_id = ? AND ppl_id = ?", $row['time_id'], $ppl_id);
			//print_r($avails);
			foreach (explode(',', $avails['avail_ids']) as $avail_id) {
				db_exec("INSERT INTO $voxdb.claim ( ppl_id, avail_id, claim_locked ) VALUES ( ?, ?, ? )", $_POST['ppl_id'], $avail_id, isset($ins[$idx])?$ins[$idx]['locked']:0);
			}

			// check capacity

			$count = db_single_field("SELECT COUNT(DISTINCT claim.ppl_id) FROM $voxdb.claim WHERE avail_id IN ( {$avails['avail_ids']} )");
			echo("Count = $count\n");

			if ($count > $avails['capacity'] && !check_su()) {
				$GLOBALS['session_state']['error_msg'] .= " $idx kan niet worden aangepast wegens te lage capaciteit";
				// we veranderen de waarde van 'verwijder' zodat onze nieuwe toevoegingen worden verwijderd
				$verwijder = $ppl_id;
			}
		}
		if ($verwijder) {
			$claim_ids = db_single_field("SELECT GROUP_CONCAT(claim_id) FROM $voxdb.claim AS claim JOIN $voxdb.avail USING (avail_id) WHERE time_id = {$row['time_id']} AND claim.ppl_id = ? AND avail.ppl_id = ?", $_POST['ppl_id'], $verwijder);
			db_exec("DELETE FROM $voxdb.claim WHERE claim_id IN ( $claim_ids )");
		}
	}
	ending:
	if (check_su()) {
		$claim_ids = db_single_field("SELECT GROUP_CONCAT(claim_id) FROM $voxdb.claim AS claim JOIN $voxdb.avail USING (avail_id) WHERE time_id = {$row['time_id']} AND claim.ppl_id = ?", $_POST['ppl_id']);
		if (!$claim_ids) continue;
		if (in_array($row['time_id'], $_POST['lock'])) {
			db_exec("UPDATE $voxdb.claim SET claim_locked = 1 WHERE claim_id IN ( $claim_ids )");
		} else {
			db_exec("UPDATE $voxdb.claim SET claim_locked = 0 WHERE claim_id IN ( $claim_ids )");
		}

	}

}

$GLOBALS['session_state']['success_msg'] = 'Keuzes opgeslagen';

header('Location: index.php?session_guid='.$session_guid.'&week='.$_POST['week']);

?>
