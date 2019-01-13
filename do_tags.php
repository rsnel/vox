<?
require('system.php');
enforce_permission('TAGBEHEER');

header('Content-type: text/plain');

$res = db_all_assoc_rekey(<<<EOQ
SELECT CONCAT(ppl_id, '-', tag_id), ppl2tag_id FROM $voxdb.ppl
JOIN $voxdb.tag ON tag.tag_type = ?
LEFT JOIN $voxdb.ppl2tag USING (ppl_id, tag_id)
EOQ
, $_POST['type']);

if (!isset($_POST['ppl2tag'])) $_POST['ppl2tag'] = array();
else if (!is_array($_POST['ppl2tag'])) fatal("impossible");

if (!isset($_POST['betreft'])) $_POST['betreft'] = array();
else if (!is_array($_POST['betreft'])) fatal("impossible");

if (!isset($_POST['filter'])) $_POST['filter'] = array();
else if (!is_array($_POST['filter'])) fatal("impossible");

//print_r($_POST);
//print_r($res);

$qstring = '';
foreach ($_POST['filter'] as $tag_id) {
	$qstring .= '&filter[]='.$tag_id;
}
//echo($qstring);
//exit;
foreach ($res as $ppltag => $coupled) {
	if (!in_array(explode('-', $ppltag)[0], $_POST['betreft'])) continue;
	if ($coupled && !in_array($ppltag, $_POST['ppl2tag'])) {
		db_exec("DELETE FROM $voxdb.ppl2tag WHERE ppl2tag_id = $coupled");
//		echo("decouple! $ppltag\n");
	} else if (!$coupled && in_array($ppltag, $_POST['ppl2tag'])) {
//		echo("couple! $ppltag\n");
		db_exec("INSERT INTO $voxdb.ppl2tag ( ppl_id, tag_id ) VALUES ( ?, ? )", explode('-', $ppltag)[0], explode('-', $ppltag)[1]);
	}
}

header('Location: tags.php?session_guid='.$session_guid.'&type='.$_POST['type'].$qstring);

?>
