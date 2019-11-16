<?php

namespace Detain\MyAdminGlobalSign;

use Detain\MyAdminGlobalSign\GlobalSign;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminGlobalSign
 */
class Plugin
{
	public static $name = 'GlobalSign SSL';
	public static $description = 'Allows selling of GlobalSign Server and VPS License Types.  More info at https://www.netenberg.com/globalsign.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a globalsign license. Allow 10 minutes for activation.';
	public static $module = 'ssl';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
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
	public static function getActivate(GenericEvent $event)
	{
		if ($event['category'] == get_service_define('GLOBALSIGN')) {
			$serviceClass = $event->getSubject();
			myadmin_log(self::$module, 'info', 'GlobalSign Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$serviceTypes = run_event('get_service_types', false, self::$module);
			$settings = get_module_settings(self::$module);
			$extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
			$GS = new GlobalSign(GLOBALSIGN_USERNAME, GLOBALSIGN_PASSWORD);
			$orderData = $GS->GetOrderByOrderID($serviceClass->getOrderId());
			$renew = $orderData['Response']['OrderDetail']['OrderInfo']['OrderStatus'] == 4 && (new \DateTime($orderData['Response']['OrderDetail']['CertificateInfo']['EndDate']))->diff(new \DateTime('now'))->invert == 1;
			if (!isset($extra['csr']) || '' == $extra['csr']) {
				$extra = ensure_csr($serviceClass->getId());
			}
			if (!isset($extra['approver_email'])) {
				$extra['approver_email'] = '';
			}
			myadmin_log(self::$module, 'info', "starting SSL Hostname {$serviceClass->getHostname()} Type ".$event['field1'].' Got CSR Size: '.mb_strlen($extra['csr']), __LINE__, __FILE__, self::$module, $serviceClass->getId());
			myadmin_log(self::$module, 'info', $renew === true ? 'found order_id already set and GetOrderByOrderID is returning a vald order so decided to renew the cert' : 'order_id is either not seto or invalid so placing a new order', __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$ssl_typeArray = ['AlphaSSL' =>1, 'DomainSSL' =>2, 'OrganizationSSL' =>3, 'ExtendedSSL' =>4, 'Alpha SSL w/ WildCard' => 5, 'DomainSSL w/ WildCard' => 6, 'OrganizationSSL w/ WildCard' => 7];
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
				if ($res !== false) {
					foreach ($res as $key => $value) {
						$extra[$key] = $value;
					}
					$orderId = $extra['order_id'];
					$serviceClass->setOrderId($orderId)->setExtra(myadmin_stringify($extra))->save();
				}
				if ($res === false) {
					myadmin_log(self::$module, 'debug', 'Error so setting up status to pending', __LINE__, __FILE__, self::$module, $serviceClass->getId());
					$serviceClass->setStatus('pending')->save();
				}
			} else {
				// renewing ssl order
				switch ($event['field1']) {
					case 'DV_LOW':
					case 'DV_SKIP':
						myadmin_log('ssl', 'info', "renewAlphaDomain({$serviceClass->getHostname()}, {$extra['csr']}, {$serviceClass->getFirstname()}, {$serviceClass->getLastname()}, {$serviceClass->getPhone()}, {$serviceClass->getEmail()}, {$extra['approver_email']}, FALSE, {$ssl_typeArray[$serviceTypes[$serviceClass->getType()]['services_name']]}, {$serviceClass->getOrderId()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
						$res = $GS->renewAlphaDomain($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $extra['approver_email'], $event['field2'] == 'wildcard', $ssl_typeArray[$serviceTypes[$serviceClass->getType()]['services_name']], $serviceClass->getOrderId());
						break;
					case 'EV':
						myadmin_log('ssl', 'info', "renewExtendedSSL({$serviceClass->getHostname()}, {$extra['csr']}, {$serviceClass->getFirstname()}, {$serviceClass->getLastname()}, {$serviceClass->getPhone()}, {$serviceClass->getEmail()}, {$serviceClass->getCompany()}, {$serviceClass->getAddress()}, {$serviceClass->getCity()}, {$serviceClass->getState()}, {$serviceClass->getZip()}, {$extra['business_category']}, {$extra['agency']}, {$serviceClass->getOrderId()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
						$res = $GS->renewExtendedSSL($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $serviceClass->getCompany(), $serviceClass->getAddress(), $serviceClass->getCity(), $serviceClass->getState(), $serviceClass->getZip(), $extra['business_category'], $extra['agency'], $serviceClass->getOrderId());
						break;
					case 'OV_SKIP':
						myadmin_log('ssl', 'info', "renewOrganizationSSL({$serviceClass->getHostname()}, {$extra['csr']}, {$serviceClass->getFirstname()}, {$serviceClass->getLastname()}, {$serviceClass->getPhone()}, {$serviceClass->getEmail()}, {$serviceClass->getCompany()}, {$serviceClass->getAddress()}, {$serviceClass->getCity()}, {$serviceClass->getState()}, {$serviceClass->getZip()}, {$extra['approver_email']}, TRUE, {$serviceClass->getOrderId()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
						$res = $GS->renewOrganizationSSL($serviceClass->getHostname(), $extra['csr'], $serviceClass->getFirstname(), $serviceClass->getLastname(), $serviceClass->getPhone(), $serviceClass->getEmail(), $serviceClass->getCompany(), $serviceClass->getAddress(), $serviceClass->getCity(), $serviceClass->getState(), $serviceClass->getZip(), $extra['approver_email'], $event['field2'] == 'wildcard', $serviceClass->getOrderId());
						break;
				}
				if ($res != false && isset($res['finished']) && $res['finished'] == 1) {
					$orderId = $res['order_id'];
					$serviceClass->setOrderId($orderId)->save();
				}
			}
			if (!isset($orderId)) {
				dialog('Error Registering Cert', 'The order process did not complete successfully.   Please contact support so they can get it registered.');
				$subject = 'Error Registering SSL Certificate '.$serviceClass->getHostname();
				(new MyAdmin\Mail())->adminMail($subject, $subject.PHP_EOL.print_r($res, false), false, 'admin/ssl_error.tpl');
				myadmin_log('ssl', 'info', $subject, __LINE__, __FILE__, self::$module, $serviceClass->getId());
				$event['success'] = false;
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_globalsign', '/images/myadmin/to-do.png', _('ReUsable GlobalSign Licenses'));
			$menu->add_link(self::$module, 'choice=none.globalsign_list', '/images/myadmin/to-do.png', _('GlobalSign Licenses Breakdown'));
			$menu->add_link(self::$module.'api', 'choice=none.globalsign_licenses_list', '/images/whm/createacct.gif', _('List all GlobalSign Licenses'));
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_requirement('class.GlobalSign', '/../vendor/detain/myadmin-globalsign-ssl/src/GlobalSign.php', '\\Detain\\MyAdminGlobalSign\\');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, _('API Settings'), 'globalsign_username', _('GlobalSign Username'), _('Username to use for GlobalSign API Authentication'), $settings->get_setting('GLOBALSIGN_USERNAME'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'globalsign_password', _('GlobalSign Password'), _('Password to use for GlobalSign API Authentication'), $settings->get_setting('GLOBALSIGN_PASSWORD'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'globalsign_test_username', _('GlobalSign Username'), _('Username to use for GlobalSign API Testing Authentication'), $settings->get_setting('GLOBALSIGN_TEST_USERNAME'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'globalsign_test_password', _('GlobalSign Password'), _('Password to use for GlobalSign API Testing Authentication'), $settings->get_setting('GLOBALSIGN_TEST_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'globalsign_testing', _('GlobalSign Test Mode'), _('Enable API Test mode (doesnt create real certs or cost)'), GLOBALSIGN_TESTING, ['false', 'true'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_globalsign_ssl', _('Out Of Stock GlobalSign SSL'), _('Enable/Disable Sales Of This Type'), OUTOFSTOCK_GLOBALSIGN_SSL, ['0', '1'], ['No', 'Yes']);
	}
}
