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

$select = '';

//print_r($tags);

foreach ($tags as $tag_id => $tag_name) {
	$tag_col = implode('<br>', explode('-', $tag_name));
	$select .= <<<EOS
, CONCAT('<input type="checkbox" name="ppl2tag[]"', IF((SELECT ppl2tag_id FROM $voxdb.ppl2tag WHERE ppl2tag.ppl_id = ppl.ppl_id AND ppl2tag.tag_id = $tag_id), ' checked', ''),' value="', ppl_id, '-$tag_id">') '$tag_col'
EOS;
}

$res = db_query(<<<EOQ
SELECT CONCAT(ppl_login, '<input type="hidden" name="betreft[]" value="', ppl_id, '">') login,
	CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname) naam$select
FROM $voxdb.ppl
WHERE ppl_type = 'leerling'
ORDER BY ppl_surname, ppl_forename, ppl_prefix
EOQ
);

//exec('pwgen 8 10', $output, $ret);
//print_r($output);
//echo($ret);

html_start(); ?>
<form method="GET" accept-charset="UTF-8">
Soort tags:
<input type="hidden" name="session_guid" value="<?=$session_guid?>">
<?=$selecttags?>
<input type="submit" value="Wijzig soort tag (niet opgeslagen wijzigingen in vinkjes gaan verloren!)">
</form>
<p><form action="do_tags.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<div class="tablemarkup"><? db_dump_result($res, false); ?></div>
<input type="hidden" name="type" value="<?=htmlenc($type)?>">
<input type="submit" value="Opslaan">
</form>
<?  html_end();
?>
