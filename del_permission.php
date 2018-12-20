<?
require('system.php');
require('html.php');

enforce_logged_in();

$res = db_single_row(<<<EOQ
SELECT log.foreign_id, session_log.auth_user, session_log.timestamp, log_next.foreign_id new_foreign_id, session_log_next.auth_user new_auth_user, session_log_next.timestamp new_timestamp
FROM log
JOIN log_permissions ON log_permissions.log_permissions_id = log.foreign_id
JOIN session_log USING (session_prev_log_id)
LEFT JOIN log AS log_next ON log_next.prev_log_id = log.log_id
LEFT JOIN session_log AS session_log_next ON session_log_next.session_prev_log_id = log_next.session_prev_log_id
WHERE log.foreign_table = 'log_permissions' AND log.log_id = ?
EOQ
, $_GET['log_id']);

html_start(); ?>
<pre>
<? print_r($res); ?>
</pre>
<?
html_end();
?>
