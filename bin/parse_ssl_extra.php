#!/usr/bin/env php
<?php
/* This converts people from the old montioring format to the new one .. lots more efficient and what not ... */
$_SERVER['HTTP_HOST'] = 'my.interserver.net';
require_once __DIR__.'/../../include/functions.inc.php';

$db = clone $GLOBALS['tf']->db;
$db2 = clone $db;
$result = $db->query('select * from ssl_certs where ssl_id=' .intval($_SERVER['argv'][1]));
while ($db->next_record(MYSQL_ASSOC)) {
    echo 'Got Unparsed '.$db->Record['ssl_extra'].PHP_EOL;
    $extra = myadmin_unstringify($db->Record['ssl_extra']);
    print_r($extra);
    //			$updates[] = "ssl_extra='" .$db->real_escape($extra)."'";
//			echo "updates:".print_r($updates,true).PHP_EOL;
//			$db2->query("update ssl_certs set " . implode(', ', $updates) . " where ssl_id=" . $db->Record['ssl_id'], __LINE__, __FILE__);
}
