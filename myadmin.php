<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_globalsign define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Globalsign Ssl',
	'description' => 'Allows selling of Globalsign Server and VPS License Types.  More info at https://www.netenberg.com/globalsign.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a globalsign license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-globalsign-ssl',
	'repo' => 'https://github.com/detain/myadmin-globalsign-ssl',
	'version' => '1.0.0',
	'type' => 'licenses',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminGlobalsign\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminGlobalsign\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminGlobalsign\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminGlobalsign\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminGlobalsign\Plugin', 'Menu'] */
	],
];
