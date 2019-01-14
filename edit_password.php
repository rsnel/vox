<?
require('system.php');
require('html.php');

enforce_logged_in();

html_start(); ?>
<form action="do_edit_password.php?session_guid=<? echo($session_guid); ?>" method="POST" accept-charset="UTF-8">
<? if (!check_su()) { ?>
<br>Huidig wachtwoord: <input type="password" name="old_password">
<? } else { ?>
<br>vanwege 'Switch User' is het niet nodig het oude wachtwoord op te geven
<input type="hidden" name="old_password" value="">
<? } ?>
<br>Nieuw wachtwoord: <input type="password" name="new_password">
<br>Herhaal nieuw wachtwoord: <input type="password" name="new_password2">
<br> <input type="submit" value="opslaan">
</form>
<? html_end();
?>
