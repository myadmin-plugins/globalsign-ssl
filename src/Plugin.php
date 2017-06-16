<?php

namespace Detain\MyAdminGlobalsign;

use Detain\Globalsign\Globalsign;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Globalsign Ssl';
	public static $description = 'Allows selling of Globalsign Server and VPS License Types.  More info at https://www.netenberg.com/globalsign.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a globalsign license. Allow 10 minutes for activation.';
	public static $module = 'ssl';
	public static $type = 'service';


	public function __construct() {
	}

	public static function Hooks() {
		return [
			'ssl.settings' => ['Detain\MyAdminGlobalsign\Plugin', 'Settings'],
		];
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Globalsign Activation', __LINE__, __FILE__);
			function_requirements('activate_globalsign');
			activate_globalsign($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$globalsign = new Globalsign(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $globalsign->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Globalsign editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_globalsign', 'icons/database_warning_48.png', 'ReUsable Globalsign Licenses');
			$menu->add_link($module, 'choice=none.globalsign_list', 'icons/database_warning_48.png', 'Globalsign Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.globalsign_licenses_list', 'whm/createacct.gif', 'List all Globalsign Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_globalsign_list', '/../vendor/detain/crud/src/crud/crud_globalsign_list.php');
		$loader->add_requirement('crud_reusable_globalsign', '/../vendor/detain/crud/src/crud/crud_reusable_globalsign.php');
		$loader->add_requirement('get_globalsign_licenses', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign.inc.php');
		$loader->add_requirement('get_globalsign_list', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign.inc.php');
		$loader->add_requirement('globalsign_licenses_list', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign_licenses_list.php');
		$loader->add_requirement('globalsign_list', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign_list.php');
		$loader->add_requirement('get_available_globalsign', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign.inc.php');
		$loader->add_requirement('activate_globalsign', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign.inc.php');
		$loader->add_requirement('get_reusable_globalsign', '/../vendor/detain/myadmin-globalsign-ssl/src/globalsign.inc.php');
		$loader->add_requirement('reusable_globalsign', '/../vendor/detain/myadmin-globalsign-ssl/src/reusable_globalsign.php');
		$loader->add_requirement('class.Globalsign', '/../vendor/detain/globalsign-ssl/src/Globalsign.php');
		$loader->add_requirement('vps_add_globalsign', '/vps/addons/vps_add_globalsign.php');
	}

	public static function Settings(GenericEvent $event) {
		$module = 'ssl';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'API Settings', 'globalsign_username', 'GlobalSign Username:', 'Username to use for GlobalSign API Authentication', $settings->get_setting('GLOBALSIGN_USERNAME'));
		$settings->add_text_setting($module, 'API Settings', 'globalsign_password', 'GlobalSign Password:', 'Password to use for GlobalSign API Authentication', $settings->get_setting('GLOBALSIGN_PASSWORD'));
		$settings->add_text_setting($module, 'API Settings', 'globalsign_test_username', 'GlobalSign Username:', 'Username to use for GlobalSign API Testing Authentication', $settings->get_setting('GLOBALSIGN_TEST_USERNAME'));
		$settings->add_text_setting($module, 'API Settings', 'globalsign_test_password', 'GlobalSign Password:', 'Password to use for GlobalSign API Testing Authentication', $settings->get_setting('GLOBALSIGN_TEST_PASSWORD'));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'globalsign_testing', 'GlobalSign Test Mode', 'Enable API Test mode (doesnt create real certs or cost)', GLOBALSIGN_TESTING, array('false', 'true'), array('No', 'Yes', ));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_globalsign_ssl', 'Out Of Stock GlobalSign SSL', 'Enable/Disable Sales Of This Type', OUTOFSTOCK_GLOBALSIGN_SSL, array('0', '1'), array('No', 'Yes', ));
	}

}
