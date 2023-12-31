
<?php
// Hier zijn enkele belangrijke functies en concepten uitgelegd:

// fatal($string): Deze functie wordt gebruikt voor het afhandelen van fatale fouten. Het stopt de uitvoering van het script en geeft een foutmelding weer. -->
// warning($string): Deze functie wordt gebruikt voor het afhandelen van waarschuwingen. Het registreert een waarschuwing in de foutenlog.
// checksetarray($base, $keys): Een hulpprogrammafunctie om te controleren of alle elementen in een array zijn ingesteld.
// generate_session_guid(): Genereert een unieke sessie-GUID.
// htmlenc($string): HTML-encodeert een tekenreeks om HTML-injecties te voorkomen.
// write_state(): Schrijft de sessiestatus naar de database bij het afsluiten van het script.
// check_logged_in(): Controleert of de gebruiker is ingelogd.
// enforce_logged_in(): Dwint de ingelogde status af, anders wordt de gebruiker doorgestuurd naar de inlogpagina.
// check_su(): Controleert of de gebruiker een beheerder is.
// check_staff(): Controleert of de gebruiker een personeelslid is.
// check_student(): Controleert of de gebruiker een student is.
// check_staff_rights(): Controleert of de gebruiker personeelsrechten heeft.
// enforce_staff(): Dwint de personeelslid-status af, anders wordt een fatale fout weergegeven.
// enforce_student(): Dwint de student-status af, anders wordt een fatale fout weergegeven.
// enforce_staff_rights(): Dwint de personeelsrechten af, anders wordt een fatale fout weergegeven.
// check_permission($permission): Controleert of de gebruiker een specifieke permissie heeft.
// enforce_permission($permission): Dwint een specifieke permissie af, anders wordt een fatale fout weergegeven.
// upsert_password($username, $password, $old_log_id = 0): Werkt het wachtwoord van een gebruiker bij in de database. -->


// the default config file is config.php, it can be overridden
// in the Apache2 config like so:
//
// SetEnv LOGDB_CONFIG 'alt'
//
// in the case the file config_alt.php will be loaded

// Laden van configuratiebestand
if (isset($_SERVER['LOGDB_CONFIG'])) {
    $session_config = $_SERVER['LOGDB_CONFIG'];
    $configfile = 'config_'.$_SERVER['LOGDB_CONFIG'].'.php';
} else {
    $session_config = '';
    $configfile = 'config.php';
}

require($configfile);

// Functie voor fatale fouten
function fatal($string) {
    header('Content-type: text/plain');
    error_log('session_log_prev_id='.$GLOBALS['session_state']['session_log_id'].':fatal:'.$string);
    echo("fatal:$string\n");
    exit;
}

// Functie voor waarschuwingen
function warning($string) {
    error_log('session_log_prev_id='.$GLOBALS['session_state']['session_log_id'].':warning:'.$string);
}

// Functie om te controleren of alle elementen in een array zijn ingesteld
function checksetarray($base, $keys) {
    foreach ($keys as $key) {
        if (!isset($base[$key])) return false;
    }
    return true;
}

// Controleren en laden van $auth-array
if (!checksetarray($auth, array('url', 'prefix', 'method')))
    fatal('$auth array not defined or complete (server, prefix) in '.$configfile);

require('db.php');

// Functies voor het afhandelen van cURL-fouten
function fatal_curl($ch) {
    fatal('(errno='.curl_errno($ch).'):'.curl_error($ch));
}

function warning_curl($ch) {
    warning('(errno='.curl_errno($ch).'):'.curl_error($ch));
}

// Sessiebeheer
$useragent_id = db_get_useragent_id($_SERVER['HTTP_USER_AGENT']);
$session_config_id = db_get_id('session_config_id', 'session_configs', 'session_config', $session_config);

// Genereren van sessie GUID
function generate_session_guid() {
    $guid = bin2hex(openssl_random_pseudo_bytes(32, $strong));
    if (!$strong) fatal('no strong pseudorandom generator available to generate session_guid');
    return $guid;
}

// Controleren op geldige querystring
if ($_SERVER['QUERY_STRING'] != '' && !preg_match('/^session_guid=[0-9a-f]{64}(&.*)?$/', $_SERVER['QUERY_STRING'])) 
    fatal('impossible query string detected');

// Een sessie wordt gedefinieerd door de GUID van de client, het IP-adres en de UserAgent
if (!isset($_GET['session_guid']) ||
    !($session_id = db_single_field(<<<EOQ
SELECT session_id FROM sessions
WHERE session_guid = ?
AND session_useragent_id = ?
AND session_address = ?
AND session_config_id = ?
EOQ
, $_GET['session_guid'], $useragent_id, $_SERVER['REMOTE_ADDR'], $session_config_id))) {
    // Nieuwe sessie aanmaken als deze niet bestaat
    if (!isset($_GET['session_guid']) || (isset($_GET['session_guid']) && !preg_match('/^[0-9a-f]{64}$/', $_GET['session_guid']))) {
        $session_guid = generate_session_guid();
    } else {
        $session_guid = $_GET['session_guid'];
    }

    if (db_exec(<<<EOQ
INSERT INTO sessions
SET session_guid = ?,
    session_useragent_id = ?,
    session_address = ?,
    session_config_id = ?
EOQ
, $session_guid, $useragent_id, $_SERVER['REMOTE_ADDR'], $session_config_id) != 1)
        fatal('unable to insert new session in DB');
    $session_id =  mysqli_insert_id($GLOBALS['db']);

    // Nieuwe sessie, maak een startrecord session_log
    db_exec('INSERT INTO session_log SET session_id = ?', $session_id);
} else {
    // Bestaande sessie ophalen
    if (!preg_match('/^[0-9a-f]{64}$/', $_GET['session_guid']))
        fatal('impossible: illegal session_guid in database?!?!?!');
    $session_guid = $_GET['session_guid'];
}

// Informatie over sessiestatus ophalen
$GLOBALS['session_state'] = db_single_row(<<<EOQ
SELECT session_log.session_log_id, session_log.session_id, session_log.auth_user,
    session_log.ppl_id, TIMESTAMPDIFF(SECOND, session_log.timestamp, NOW()) age,
    session_log.success_msg, session_log.error_msg, session_log.request_uri FROM session_log
LEFT JOIN session_log AS session_log_next ON session_log_next.session_prev_log_id = session_log.session_log_id
WHERE session_log.session_id = ? AND session_log_next.session_prev_log_id IS NULL
EOQ
, $session_id);

// Fatale fout als sessie niet gevonden wordt
if (!$GLOBALS['session_state']) fatal('impossible, session not found in session_log');

// Functie voor HTML-encoding
function htmlenc($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function write_state() {
	db_exec(<<<EOQ
INSERT INTO session_log
SET session_prev_log_id = ?, session_id = ?, auth_user = ?, ppl_id = ?,
	request_uri = ?, success_msg = ?, error_msg = ?
EOQ
	, 
		$GLOBALS['session_state']['session_log_id'],
		$GLOBALS['session_state']['session_id'],
		$GLOBALS['session_state']['auth_user'],
		$GLOBALS['session_state']['ppl_id'],
		$_SERVER['REQUEST_URI'],
		$GLOBALS['session_state']['success_msg'],
		$GLOBALS['session_state']['error_msg']);
}

register_shutdown_function('write_state');

// Valideren van gebruikersnaam
function check_username($username) {
    return !preg_match('/[^A-Za-z0-9.]/', $username);
}

// Controleren of gebruiker is ingelogd
function check_logged_in() {
    return isset($GLOBALS['session_state']['auth_user']);
}

// Afdwingen van ingelogde status
function enforce_logged_in() {
    if (check_logged_in()) return;
    header("Location: index.php?session_guid={$GLOBALS['session_guid']}");
    exit;
}


// Controleer of $voxdb is gedefinieerd
if (isset($voxdb)) {
    // Controleer of $voxdb niet leeg is
    if (!empty($voxdb)) {
        // Als er inhoud is, toon deze
        echo "Inhoud van \$voxdb:\n";
        print_r($voxdb);
    } else {
        // Als $voxdb leeg is, geef een mededeling weer
        echo "\$voxdb is leeg.\n";
    }
} else {
    // Als $voxdb niet is gedefinieerd, geef een foutmelding weer
    echo "\$voxdb is niet gedefinieerd.\n";
}


// Controleren op beheerdersrechten
function check_su() {
    global $voxdb;
    if (!check_logged_in()) return false;
    return $GLOBALS['session_state']['ppl_id'] != db_single_field("SELECT ppl_id FROM $voxdb.ppl WHERE ppl_login = ?", $GLOBALS['session_state']['auth_user']);
}

// Controleren op personeelslid
function check_staff() {
    global $voxdb;
    if (!check_logged_in()) return false;
    $type = db_single_field("SELECT ppl_type FROM $voxdb.ppl WHERE ppl_id = ?", $GLOBALS['session_state']['ppl_id']);
    return ($type == 'personeel');
}

// Controleren op student
function check_student() {
    return !check_staff();
}

// Controleren op personeelsrechten
function check_staff_rights() {
    global $voxdb;
    if (!check_logged_in()) return false;
    $type = db_single_field("SELECT ppl_type FROM $voxdb.ppl WHERE ppl_login = ?", $GLOBALS['session_state']['auth_user']);
    return ($type == 'personeel');
}

// Afdwingen van personeelslid-status
function enforce_staff() {
    enforce_logged_in();
    if (check_staff()) return;
    fatal("only accessible by staff");
}

// Afdwingen van student-status
function enforce_student() {
    if (check_student()) return;
    fatal("only accessible by students");
}

// Afdwingen van personeelsrechten
function enforce_staff_rights() {
    enforce_logged_in();
    if (check_staff_rights()) return;
    fatal("only accessible by staff");
}

// Controleren van permissies
function check_permission($permission) {
    if (!check_logged_in()) return false;
    return db_single_field("SELECT user FROM permissions WHERE permission = ? AND user = ?", $permission, $GLOBALS['session_state']['auth_user']) !== false;
}

// Afdwingen van permissies
function enforce_permission($permission) {
    enforce_logged_in();
    if (check_permission($permission)) return;
    fatal("permission denied for $permission");
}

// Functie voor het updaten van wachtwoord
function upsert_password($username, $password, $old_log_id = 0) {
    if (!check_username($username)) fatal("username not allowed");
    if ($password != '') {
        // Nieuwe wachtwoordhash genereren
        $salt = str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(12, $strong)));
        if (!$strong) fatal('no strong pseudoramdomgenerator available to generate password salt');
        $hash = crypt($password, '$6$rounds=5000$'.$salt.'$');
        // Nieuwe wachtwoordhash in database opslaan
        $log_password_id = db_get_id('log_password_id', 'log_passwords', 'auth_user', $username, 'password_hash', $hash);
    } else {
        $log_password_id = NULL;
    }
    db_direct('LOCK TABLES log WRITE, log AS log_next READ, log_passwords READ');
    $log_id = db_single_field("SELECT log.log_id FROM log_passwords JOIN log ON log.foreign_id = log_password_id AND log.foreign_table = 'log_passwords' LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id WHERE log_next.log_id IS NULL AND log_passwords.auth_user = ?", $username);
    if ($old_log_id === NULL && $log_id) {
        db_direct('UNLOCK TABLES');
        fatal("password is al geupdate \$log_id = $log_id \$old_log_id = $old_log_id");
    }
    if ($old_log_id > 0 && $log_id != $old_log_id) {
        db_direct('UNLOCK TABLES');
        fatal("password is al geupdate \$log_id = $log_id \$old_log_id = $old_log_id");
    }
    db_exec("INSERT INTO log ( prev_log_id, foreign_table, foreign_id, session_prev_log_id ) VALUES ( ?, 'log_passwords', ?, ? )", $log_id?$log_id:NULL, $log_password_id, $GLOBALS['session_state']['session_log_id']);
    db_direct('UNLOCK TABLES');
}

?>
