<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('TAGBEHEER');

$tags = db_all_assoc_rekey(<<<EOQ
SELECT tag_id, tag_name FROM $voxdb.tag WHERE tag_type = 'ROOSTERLLN' ORDER BY tag_order, tag_name
EOQ
);

$select = '';

//print_r($tags);

foreach ($tags as $tag_id => $tag_name) {
	$tag_col = implode('<br>', explode('-', $tag_name));
	$select .= <<<EOS
, CONCAT('<input type="checkbox" name="ppl2tag[]"', IF((SELECT ppl2tag_id FROM $voxdb.ppl2tag WHERE ppl2tag.ppl_id = ppl.ppl_id AND ppl2tag.tag_id = $tag_id), ' checked', ''),' value="', ppl_id, '-$tag_id">') '$tag_col'
EOS;
}

$res = db_query(<<<EOQ
SELECT ppl_login login,
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
<form action="do_tags.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<? db_dump_result($res, false); ?>
<input type="submit" value="Opslaan">
</form>
<?  html_end();
?>
