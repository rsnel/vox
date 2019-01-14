<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('TAGBEHEER');

if (!isset($_GET['type'])) $type = 'ROOSTERLLN';
else $type = $_GET['type'];

$check = db_single_field("SELECT DISTINCT tag_type FROM $voxdb.tag WHERE tag_type = ?", $type);

if (!$check) fatal("tags van type ".$type." bestaan niet");

$tags = db_all_assoc_rekey(<<<EOQ
SELECT tag_id, tag_name FROM $voxdb.tag WHERE tag_type = ? ORDER BY tag_order, tag_name
EOQ
, $type);

$selecttags = db_single_field(<<<EOQ
SELECT CONCAT('<select name="type">', GROUP_CONCAT(DISTINCT CONCAT('<option', IF(tag_type = ?, ' selected', ''), '>', tag_type, '</option>')), '</select>') FROM $voxdb.tag
EOQ
, $type);

if (!isset($_GET['filter']) || $_GET['filter'] == '') $_GET['filter'] = array();
else if (!is_array($_GET['filter'])) fatal("impossible");

$filter = db_all_assoc_rekey(<<<EOQ
SELECT tag_type, GROUP_CONCAT(CONCAT(tag_id, '-', tag_name) ORDER BY tag_order) FROM $voxdb.tag WHERE tag_type != ? GROUP BY tag_type
EOQ
, $type);

$select = '';

//print_r($tags);

foreach ($tags as $tag_id => $tag_name) {
	$tag_col = implode('<br>', explode('-', $tag_name));
	$select .= <<<EOS
, CONCAT('<input type="checkbox" name="ppl2tag[]"', IF((SELECT ppl2tag_id FROM $voxdb.ppl2tag WHERE ppl2tag.ppl_id = ppl.ppl_id AND ppl2tag.tag_id = $tag_id), ' checked', ''),' value="', ppl_id, '-$tag_id">') '$tag_col'
EOS;
}

//exec('pwgen 8 10', $output, $ret);
//print_r($output);
//echo($ret);

html_start();
$where = array();
$qfilter = array();
?>
<form method="GET" accept-charset="UTF-8">
<input type="hidden" name="session_guid" value="<?=$session_guid?>">
Filter:<br>
<? foreach ($filter as $soort => $list) {
	echo($soort);
	$where[$soort] = array();
	$tags = explode(',', $list);
	foreach ($tags as $tag_info) {
		$tmp = explode('-', $tag_info);
		$tag_id = db_single_field("SELECT tag_id FROM $voxdb.tag WHERE tag_id = ?", $tmp[0]);
		if (!$tag_id) fatal("tag id bestaat niet");
		array_shift($tmp);
		$tag_name = implode('-', $tmp);
		if (in_array($tag_id, $_GET['filter'])) {
			$where[$soort][] = "tag_id = $tag_id";
			$qfilter[] = '<input type="hidden" name="filter[]" value="'.$tag_id.'">';
		}
		?><input type="checkbox" name="filter[]" value="<?=$tag_id?>"<?=in_array($tag_id, $_GET['filter'])?' checked':'' ?>><?=$tag_name ?><?
		
	}
	echo('<br>');
}
foreach ($where as $soort => $stuff) {
	$stuff = implode(' OR ', $stuff);
	if ($stuff == '') $where[$soort] = 'TRUE';
	else $where[$soort] = 'ppl_id IN ( SELECT ppl_id FROM '.$voxdb.'.ppl2tag WHERE '.$stuff.' )';
}
$where = implode(' AND ', $where);

$res = db_query(<<<EOQ
SELECT CONCAT(ppl_login, '<input type="hidden" name="betreft[]" value="', ppl_id, '">') login,
	CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname) naam$select
FROM $voxdb.ppl
WHERE ppl_type = 'leerling' AND $where
ORDER BY ppl_surname, ppl_forename, ppl_prefix
EOQ
);

?>
Soort tags:
<?=$selecttags?><br>
<input type="submit" value="Wijzig filter/soort tag (niet opgeslagen wijzigingen in vinkjes gaan verloren!)">
</form>
<p><form action="do_tags.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<?=implode('', $qfilter)?>
<div class="tablemarkup"><? db_dump_result($res, false); ?></div>
<input type="hidden" name="type" value="<?=htmlenc($type)?>">
<input type="submit" value="Opslaan">
</form>
<?  html_end();
?>
