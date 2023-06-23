<?php

use \Detain\MyAdminGlobalSign\GlobalSign;

include __DIR__.'/../../../../include/functions.inc.php';
$ssl = new \Detain\MyAdminGlobalSign\GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
$orders = $ssl->GetCertificateOrders();
print_r($orders);
