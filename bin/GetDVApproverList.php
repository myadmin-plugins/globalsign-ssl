#!/usr/bin/env php
<?php

use \Detain\MyAdminGlobalSign\GlobalSign;

include __DIR__.'/../../../include/functions.inc.php';
$GB = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
$approvers = obj2array($GB->GetDVApproverList($_SERVER['argv'][1]));
print_r($approvers);
