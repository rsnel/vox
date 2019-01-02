<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('ACCOUNT');

$res = db_query(<<<EOQ
SELECT ppl_login login,
	CONCAT(ppl_forename, ' ', ppl_prefix, ' ', ppl_surname) naam, 
	CONCAT('<input type="checkbox"', 
		IF(password_hash IS NULL, ' checked', ''),
		' value="', ppl_id, '-', IFNULL(log_id, 'NULL'),
		'" name="pplpwlog[]">') `genereer wachtwoord`
FROM $voxdb.ppl LEFT JOIN passwords ON passwords.auth_user = ppl_login
EOQ
);

//exec('pwgen 8 10', $output, $ret);
//print_r($output);
//echo($ret);

html_start(); ?>
<form action="do_generate_passwords.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<? db_dump_result($res, false); ?>
<input type="submit" value="Genereer wachtwoorden">
</form>
<h4>Wachtwoorden wijzigen/verwijderen</h4>

<form action="do_account.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<input type="text" placeholder="username" name="username">
<input type="text" placeholder="password" name="password">
<input type="submit" value="Upsert">
</form>
<?  html_end();
?>
