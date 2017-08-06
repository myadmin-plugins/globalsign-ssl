<?php

namespace Detain\MyAdminGlobalSign;

use Detain\MyAdminGlobalSign\GlobalSign;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminGlobalSign
 */
class Plugin {

	public static $name = 'GlobalSign SSL';
	public static $description = 'Allows selling of GlobalSign Server and VPS License Types.  More info at https://www.netenberg.com/globalsign.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a globalsign license. Allow 10 minutes for activation.';
	public static $module = 'ssl';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			'function.requirements' => [__CLASS__, 'getRequirements']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		if ($event['category'] == get_service_define('GLOBALSIGN')) {
			myadmin_log(self::$module, 'info', 'GlobalSign Activation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$serviceTypes = run_event('get_service_types', FALSE, self::$module);
			$settings = get_module_settings(self::$module);
			$extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
			if ('' === $extra['csr'])
				$extra = ensure_csr($serviceInfo[$prefix.'_id']);
			myadmin_log('ssl', 'info', "starting SSL Hostname {$serviceClass->getHostname()} Type ".$event['field1'].' Got CSR Size: '.mb_strlen($extra['csr']), __LINE__, __FILE__);
			$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
			$renew = substr($serviceClass->getOrderId(), 0, 2) == 'CE' && $GS->GetOrderByOrderID($serviceClass->getOrderId())['Response']['QueryResponseHeader']['SuccessCode'] == '0';
			myadmin_log(self::$module, 'info', $renew === true ? 'found order_id already set and GetOrderByOrderID is returning a vald order so decided to renew the cert' : 'order_id is either not seto or invalid so placing a new order', __LINE__, __FIE__);
			if ($renew === false) {
				// placing new ssl order
				switch ($event['field1']) {
					case 'DV_LOW':
						$res = $GS->create_alphassl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $extra['approver_email'], $event['field2'] == 'wildcard');
						break;
					case 'DV_SKIP':
						$res = $GS->create_domainssl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $extra['approver_email'], $event['field2'] == 'wildcard');
						break;
					case 'EV':
						$res = $GS->create_extendedssl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $serviceClass->getCompany(), $serviceClass->getAddress(), $serviceClass->getCity(), $serviceClass->getState(), $serviceClass->getZip(), $extra['business_category'], $extra['agency'], $extra['approver_email']);
						break;
					case 'OV_SKIP':
						$res = $GS->create_organizationssl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $serviceClass->getCompany(), $serviceClass->getAddress(), $serviceClass->getCity(), $serviceClass->getState(), $serviceClass->getZip(), $extra['approver_email'], $event['field2'] == 'wildcard');
						break;
				}
				if ($res !== FALSE) {
					foreach ($res as $key => $value)
						$extra[$key] = $value;
					$order_id = $extra['order_id'];
					$serviceClass->setOrderId($order_id)->setExtra(base64_encode(gzcompress(myadmin_stringify($extra))))->save();
				}
			} else {
				// renewing ssl order
				switch ($event['field1']) {
					case 'DV_LOW':
					case 'DV_SKIP':
						myadmin_log('ssl', 'info', "renewAlphaDomain($hostname, $csr, $firstname, $lastname, $phone, $email, $approver_email, FALSE, {$ssl_typeArray[$servicename]}, $order_id)", __LINE__, __FILE__);
						$res = $GS->renewAlphaDomain($hostname, $csr, $firstname, $lastname, $phone, $email, $approver_email, $event['field2'] == 'wildcard', $ssl_typeArray[$servicename], $order_id);
						break;
					case 'EV':
						myadmin_log('ssl', 'info', "renewExtendedSSL($hostname, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, {$extra['business_category']}, {$extra['agency']}, $approver_email, $order_id)", __LINE__, __FILE__);
						$res = $GS->renewExtendedSSL($hostname, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $extra['business_category'], $extra['agency'], $approver_email, $order_id);
						break;
					case 'OV_SKIP':
						myadmin_log('ssl', 'info', "renewOrganizationSSL($hostname, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approver_email, TRUE, $order_id)", __LINE__, __FILE__);
						$res = $GS->renewOrganizationSSL($hostname, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approver_email, $event['field2'] == 'wildcard', $order_id);
						break;
				}
				if ($res != false && isset($res['finished']) && $res['finished'] == 1) {
					$order_id = $res['order_id'];
					$serviceClass->setOrderId($order_id)->save();
				}
			}
			if (!isset($order_id)) {
				dialog('Error Registering Cert', 'The order process did not complete successfully.   Please contact support so they can get it registered.');
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.EMAIL_NEWLINE;
				$subject = 'Error Registering SSL Certificate '.$serviceClass->getHostname();
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), $headers, FALSE, 'admin_email_ssl_error.tpl');
				myadmin_log('ssl', 'info', $subject, __LINE__, __FILE__);
				$event['success'] = FALSE;
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_globalsign', 'images/icons/database_warning_48.png', 'ReUsable GlobalSign Licenses');
			$menu->add_link(self::$module, 'choice=none.globalsign_list', 'images/icons/database_warning_48.png', 'GlobalSign Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.globalsign_licenses_list', 'whm/createacct.gif', 'List all GlobalSign Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.GlobalSign', '/../vendor/detain/myadmin-globalsign-ssl/src/GlobalSign.php', '\\Detain\\MyAdminGlobalSign\\');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'API Settings', 'globalsign_username', 'GlobalSign Username:', 'Username to use for GlobalSign API Authentication', $settings->get_setting('GLOBALSIGN_USERNAME'));
		$settings->add_text_setting(self::$module, 'API Settings', 'globalsign_password', 'GlobalSign Password:', 'Password to use for GlobalSign API Authentication', $settings->get_setting('GLOBALSIGN_PASSWORD'));
		$settings->add_text_setting(self::$module, 'API Settings', 'globalsign_test_username', 'GlobalSign Username:', 'Username to use for GlobalSign API Testing Authentication', $settings->get_setting('GLOBALSIGN_TEST_USERNAME'));
		$settings->add_text_setting(self::$module, 'API Settings', 'globalsign_test_password', 'GlobalSign Password:', 'Password to use for GlobalSign API Testing Authentication', $settings->get_setting('GLOBALSIGN_TEST_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'globalsign_testing', 'GlobalSign Test Mode', 'Enable API Test mode (doesnt create real certs or cost)', GLOBALSIGN_TESTING, ['false', 'true'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_globalsign_ssl', 'Out Of Stock GlobalSign SSL', 'Enable/Disable Sales Of This Type', OUTOFSTOCK_GLOBALSIGN_SSL, ['0', '1'], ['No', 'Yes']);
	}

}
