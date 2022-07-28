<?php

include __DIR__.'/../../../../include/functions.inc.php';
function_requirements('class.GlobalSign');
$ssl = new \Detain\MyAdminGlobalSign\GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
$orders = $ssl->GetCertificateOrders();
print_r($orders);
