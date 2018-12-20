<?
require('system.php');
require('html.php');

//enforce_logged_in();
enforce_permission('ACCOUNT');

$res = db_query(<<<EOQ
SELECT auth_user user, password_hash hash FROM passwords
EOQ
);

html_start(); ?>
<? db_dump_result($res, false); ?>
<form action="do_account.php?session_guid=<?=$session_guid?>" accept-charset="UTF-8" method="POST">
<input type="text" name="username">
<input type="text" name="password">
<input type="submit" value="Upsert">
</form>
<?  html_end();
?>
