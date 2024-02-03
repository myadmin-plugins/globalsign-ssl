#!/usr/bin/env php
<?php
/**
* Creates A CSR And SelfS Signs It
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category make_cert
* @copyright 2020
*/

$_SERVER['HTTP_HOST'] = 'inssl.net';
//	$_SERVER['HTTP_HOST'] = 'illuminati.interserver.net';

require_once __DIR__.'/../../include/functions.inc.php';

$webpage = false;
define('VERBOSE_MODE', false);
/*	$db = clone $GLOBALS['tf']->db;
$db2 = clone $db;
$GLOBALS['tf']->session->create(160306, 'admin');
$sid = $GLOBALS['tf']->session->sessionid;
*/
if (file_exists(__DIR__.'/.make_cert.last')) {
    include __DIR__.'/.make_cert.last';
}
$vars = ['fqdn' => 'Fully Qualified Domain Name', 'email' => 'Email Address', 'city' => 'City', 'state' => 'State (Full State Not Abbreviation)', 'country' => 'Country (2 Letters)', 'company' => 'Company', department => 'Department'];
$fout = "<?php\n";
foreach ($vars as $var => $description) {
    if (isset($settings[$var])) {
        fwrite(STDOUT, "$description [" . $settings[$var] . ']? ');
        $t = trim(fgets(STDIN));
        if ($t == '') {
            $t = $settings[$var];
        }
    } else {
        fwrite(STDOUT, "$description? ");
        $t = trim(fgets(STDIN));
    }
    $fout .= "\$settings['{$var}'] = '{$t}';\n";
    eval('$'.$var.' = "$t";');
}
$fout .= "?>\n";
$fd = fopen(__DIR__.'/.make_cert.last', 'wb');
fwrite($fd, $fout);
fclose($fd);
//echo "Calling  make_csr($fqdn, $email, $city, $state, $country, $company, $department);\n";
[$csr, $sspublic, $ssprivate] = make_csr($fqdn, $email, $city, $state, $country, $company, $department);
echo "Here is your CSR:\n$csr\n\n";
echo "Here is your Self Signed Public Key:\n$sspublic\n\n";
echo "Here is your Self Signed Private Key:\n$ssprivate\n\n";
