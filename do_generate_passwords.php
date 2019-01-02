<?
require('system.php');
enforce_permission('ACCOUNT');

header('Content-type: text/plain');

exec('pwgen 8 '.count($_POST['pplpwlog']), $output, $ret);
//print_r($output);
if ($ret != 0) fatal("error calling pwgen");
//echo($ret);

?>Lijst met gegenereerde wachtwoorden voor onderstaande gebruikers. Advies: print enkelzijdig en knip strookjes.

<?

foreach ($_POST['pplpwlog'] as $idx => $info) {
	$infos = explode('-', $info);
	$username = db_single_field("SELECT ppl_login FROM $voxdb.ppl WHERE ppl_id = ?", $infos[0]);
	$name = db_single_field("SELECT CONCAT(ppl_forename, IF(ppl_prefix = '', '', CONCAT(' ', ppl_prefix)), ' ', ppl_surname) FROM $voxdb.ppl WHERE ppl_id = ?", $infos[0]);
	upsert_password($username, $output[$idx], ($infos[1] == 'NULL')?NULL:$infos[1]);
	echo($username.' '.$name.' '.$output[$idx]."\n\n");
}

?>
