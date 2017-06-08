<?php

namespace Detain\MyAdminGlobalsign;

use Detain\Globalsign\Globalsign;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
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
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Globalsign', 'globalsign_username', 'Globalsign Username:', 'Globalsign Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Globalsign', 'globalsign_password', 'Globalsign Password:', 'Globalsign Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Globalsign', 'outofstock_licenses_globalsign', 'Out Of Stock Globalsign Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
