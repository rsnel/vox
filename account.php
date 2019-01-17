<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('ACCOUNT');

$res = db_query(<<<EOQ
SELECT ppl_login login, CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname, IFNULL(CONCAT(' (', tags, ')'), '')) naam,
	CONCAT('<input type="checkbox"', 
		IF(password_hash IS NULL, ' checked', ''),
		' value="', ppl.ppl_id, '-', IFNULL(log_id, 'NULL'),
		'" name="pplpwlog[]">') `genereer wachtwoord`,
		IFNULL(last_activity, 'nog nooit ingelogd') `laatste activiteit`

FROM $voxdb.ppl
LEFT JOIN (
	SELECT auth_user ppl_login, MAX(timestamp) last_activity
	FROM session_log
	GROUP BY auth_user
) AS last_activity USING (ppl_login)
LEFT JOIN (
	SELECT ppl_id, GROUP_CONCAT(tag_name ORDER BY tag_type SEPARATOR '') tags
	FROM $voxdb.ppl2tag
	JOIN $voxdb.tag USING (tag_id)
	WHERE tag_type = 'NIVEAU' OR tag_type = 'LEERJAAR'
	GROUP BY ppl_id
) AS tags USING (ppl_id)
LEFT JOIN passwords ON passwords.auth_user = ppl_login
ORDER BY last_activity DESC, ppl_type, ppl_surname, ppl_forename, ppl_prefix
EOQ
);

/*
$res = db_query(<<<EOQ
SELECT ppl_login login,
	CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname) naam, 
	IFNULL(GROUP_CONCAT(DISTINCT tag_name ORDER BY tag_type SEPARATOR ''), '') tags,
	CONCAT('<input type="checkbox"', 
		IF(password_hash IS NULL, ' checked', ''),
		' value="', ppl.ppl_id, '-', IFNULL(log_id, 'NULL'),
		'" name="pplpwlog[]">') `genereer wachtwoord`,
		IFNULL(MAX(session_log.timestamp), 'nog nooit ingelogd') `laatste activiteit`
FROM $voxdb.ppl
LEFT JOIN $voxdb.ppl2tag USING (ppl_id)
LEFT JOIN $voxdb.tag USING (tag_id)
LEFT JOIN passwords ON passwords.auth_user = ppl_login
LEFT JOIN session_log ON session_log.auth_user = ppl_login
WHERE tag_type = 'NIVEAU' OR tag_type = 'LEERJAAR' OR tag_type IS NULL
GROUP BY ppl.ppl_id
ORDER BY ppl_type, ppl_surname, ppl_forename, ppl_prefix
EOQ
);
 */
//exec('pwgen 8 10', $output, $ret);
//print_r($output);
//echo($ret);

html_start(); ?>
Gebruikers die nog geen wachtwoord hebben zijn automatisch aangevinkt.
<form action="do_generate_passwords.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<? db_dump_result($res, false); ?>
<input type="submit" value="Genereer wachtwoorden">
</form>
<!--
<h4>Wachtwoorden wijzigen/verwijderen</h4>

<form action="do_account.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<input type="text" placeholder="username" name="username">
<input type="text" placeholder="password" name="password">
<input type="submit" value="Upsert">
</form>
-->
<?  html_end();
?>
