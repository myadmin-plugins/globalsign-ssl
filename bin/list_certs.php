<?php
include __DIR__.'/../../../include/functions.inc.php';
function_requirements('class.GlobalSign');
$ssl = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
$orders = $ssl->list_certs(['']);
print_r($orders);
