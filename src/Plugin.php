<?php

namespace Detain\MyAdminGlobalSign;

use Detain\MyAdminGlobalSign\GlobalSign;
use Symfony\Component\EventDispatcher\GenericEvent;
//include_once __DIR__.'/GlobalSign.php';
//use GlobalSign;

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
			if (mb_strlen($extra['csr']) == 0)
				$extra = ensure_csr($serviceInfo[$prefix.'_id']);
			myadmin_log('ssl', 'info', 'Got CSR Size: '.mb_strlen($extra['csr']), __LINE__, __FILE__);
			myadmin_log('ssl', 'info', "starting SSL Hostname {$serviceClass->getHostname()} Type ".$event['field1'], __LINE__, __FILE__);
			$db = get_module_db(self::$module);
			if ($event['field2'] == 'wildcard')
				$wildcard = TRUE;
			else
				$wildcard = FALSE;
			switch ($event['field1']) {
				case 'DV_LOW':
					$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
					$ret = $GS->create_alphassl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $extra['approver_email'], $wildcard);
					if ($ret === FALSE) {
						myadmin_log('ssl', 'debug', 'Error so setting up status to pending', __LINE__, __FILE__);
						$query = "UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_status='pending' WHERE {$settings['PREFIX']}_id='".$serviceClass->getId()."'";
						myadmin_log('ssl', 'debug', $query, __LINE__, __FILE__);
						$db->query($query, __LINE__, __FILE__);
						dialog('Error Registering Cert', 'The order process did not complete successfully. Please contact support so they can get it registered.');
					} else {
						foreach ($ret as $key => $value)
							$extra[$key] = $value;
						$order_id = $extra['order_id'];
						$query = "UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_order_id='$order_id', {$settings['PREFIX']}_extra='".$db->real_escape(base64_encode(gzcompress(myadmin_stringify($extra))))."' WHERE {$settings['PREFIX']}_id='".$serviceClass->getId()."'";
						myadmin_log('ssl', 'debug', $query, __LINE__, __FILE__);
						$db->query($query, __LINE__, __FILE__);
					}
					break;
				case 'DV_SKIP':
					$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
					$ret = $GS->create_domainssl($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $extra['approver_email'], $wildcard);
					if ($ret === FALSE) {
						$query = "UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_status='pending' WHERE {$settings['PREFIX']}_id='".$serviceClass->getId()."'";
						myadmin_log('ssl', 'debug', $query, __LINE__, __FILE__);
						$db->query($query, __LINE__, __FILE__);
						dialog('Error Registering Cert', 'The order process did not complete successfully. Please contact support so they can get it registered.');
					} else {
						foreach ($ret as $key => $value)
							$extra[$key] = $value;
						$order_id = $extra['order_id'];
						$query = "update {$settings['TABLE']} set ssl_order_id='$order_id', ssl_extra='".$db->real_escape(base64_encode(gzcompress(myadmin_stringify($extra))))."' where ssl_id='".$serviceClass->getId()."'";
						$db->query($query, __LINE__, __FILE__);
					}
					break;
				case 'EV':
					$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
					$ret = $GS->create_extendedssl(
						$serviceClass->getHostname(),
						$extra['csr'],
						$serviceClass->getFirstname(),
						$serviceClass->getLastname(),
						$serviceClass->getPhone(),
						$serviceClass->getEmail(),
						$serviceClass->getCompany(),
						$serviceClass->getAddress(),
						$serviceClass->getCity(),
						$serviceClass->getState(),
						$serviceClass->getZip(),
						$extra['business_category'],
						$extra['agency'],
						$extra['approver_email']);
					if ($ret === FALSE) {
						$query = "UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_status='pending' WHERE {$settings['PREFIX']}_id='".$serviceClass->getId()."'";
						myadmin_log('ssl', 'debug', $query, __LINE__, __FILE__);
						$db->query($query, __LINE__, __FILE__);
						dialog('Error Registering Cert', 'The order process did not complete successfully. Please contact support so they can get it registered.');
					} else {
						foreach ($ret as $key => $value)
							$extra[$key] = $value;
						$order_id = $extra['order_id'];
						$query = "update {$settings['TABLE']} set ssl_order_id='$order_id', ssl_extra='".$db->real_escape(base64_encode(gzcompress(myadmin_stringify($extra))))."' where ssl_id='".$serviceClass->getId()."'";
						$db->query($query, __LINE__, __FILE__);
					}
					break;
				case 'OV_SKIP':
					$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
					$ret = $GS->create_organizationssl(
						$serviceClass->getHostname(),
						$extra['csr'],
						$serviceClass->getFirstname(),
						$serviceClass->getLastname(),
						$serviceClass->getPhone(),
						$serviceClass->getEmail(),
						$serviceClass->getCompany(),
						$serviceClass->getAddress(),
						$serviceClass->getCity(),
						$serviceClass->getState(),
						$serviceClass->getZip(),
						$extra['approver_email'],
						$wildcard);
					if ($ret === FALSE) {
						$query = "UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_status='pending' WHERE {$settings['PREFIX']}_id='".$serviceClass->getId()."'";
						myadmin_log('ssl', 'debug', $query, __LINE__, __FILE__);
						$db->query($query, __LINE__, __FILE__);
						dialog('Error Registering Cert', 'The order process did not complete successfully. Please contact support so they can get it registered.');
					} else {
						foreach ($ret as $key => $value)
							$extra[$key] = $value;
						$order_id = $extra['order_id'];
						$query = "update {$settings['TABLE']} set ssl_order_id='$order_id', ssl_extra='".$db->real_escape(base64_encode(gzcompress(myadmin_stringify($extra))))."' where ssl_id='".$serviceClass->getId()."'";
						$db->query($query, __LINE__, __FILE__);
					}
					break;
			}
			if (!isset($order_id)) {
				$subject = 'Error Registering SSL Certificate '.$serviceClass->getHostname();
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
