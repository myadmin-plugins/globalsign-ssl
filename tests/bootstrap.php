<?php
require_once __DIR__.'/../vendor/autoload.php';
function myadmin_log($section, $level, $text, $line, $file) {
	//echo "{$section} {$level} {$line}@{$file}: {$text}\n";
}
/**
 * make_csr()
 * @param string $fqdn
 * @param string $email
 * @param string $city
 * @param string $state
 * @param string $country
 * @param string $company
 * @param string $department
 * @return array
 */
function make_csr($fqdn, $email, $city, $state, $country, $company, $department) {
	$SSLcnf = [
		'config' => '/etc/pki/tls/openssl.cnf',
		//'config' => '/etc/ssl/openssl.cnf',
		//'config' => '/etc/tinyca/openssl.cnf',
		//'config' => '/etc/openvpn/easy-rsa/openssl.cnf',
		'encrypt_key' => true,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
		'digest_alg' => 'sha2',
		'x509_extensions' => 'v3_ca',
		'private_key_bits' => 2048
	];
	// $fqdn = domain name for normal certs, individuals full name for s/MIME certs
	$dn = [
		'countryName' => $country,
		'stateOrProvinceName' => $state,
		'localityName' => $city,
		'organizationName' => $company,
		'organizationalUnitName' => $department,
		'commonName' => $fqdn,
		'emailAddress' => $email
	];
	// Generate a new private (and public) key pair
	$privkey = openssl_pkey_new($SSLcnf);
	// Generate a certificate signing request
	$csr = openssl_csr_new($dn, $privkey, $SSLcnf);
	openssl_csr_export($csr, $csrout);
	// You will usually want to create a self-signed certificate at this point until your CA fulfills your request. This creates a self-signed cert that is valid for 365 days
	$sscert = openssl_csr_sign($csr, null, $privkey, 365);
	// Now you will want to preserve your private key, CSR and self-signed cert so that they can be installed into your web server, mail server or mail client (depending on the intended use of the certificate). This example shows how to get those things into variables, but you can also store them directly into files. Typically, you will send the CSR on to your CA who will then issue you with the "real" certificate.
	openssl_x509_export($sscert, $certout);
	openssl_pkey_export($privkey, $pkeout);
	// Show any errors that occurred here
	//while (($e = openssl_error_string()) !== false)
	//   echo $e . "\n";
	return [$csrout, $certout, $pkeout];
}
