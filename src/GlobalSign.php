<?php
/**
 * GlobalSign SSL Related Functionality
 * Last Changed: $LastChangedDate: 2017-07-20 04:48:03 -0400 (Thu, 20 Jul 2017) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category SSL
 */

/**
 * GlobalSign
 * The following URL’s should be used to access the GlobalSign live API:
 *		SSL Functions: https://system.globalsign.com/kb/ws/v1/ServerSSLService
 *		Service/Query: https://system.globalsign.com/kb/ws/v1/GASService
 *		Account: https://system.globalsign.com/kb/ws/v1/AccountService
 *		Subscriber Agreement: https://system.globalsign.com/qb/ws/GasQuery
 * The following URLs* should be used to access the GlobalSign Test API:
 *		SSL Functions: https://testsystem.globalsign.com/kb/ws/v1/ServerSSLService
 *		Service/Query: https://testsystem.globalsign.com/kb/ws/v1/GASService
 *		Account: https://testsystem.globalsign.com/kb/ws/v1/AccountService
 *
 *GlobalSign’s WSDL files are available from:
 *		SSL Functions: https://system.globalsign.com/kb/ws/v1/ServerSSLService?wsdl
 *		Service/Query: https://system.globalsign.com/kb/ws/v1/GASService?wsdl
 *		Account: https://system.globalsign.com/kb/ws/v1/AccountService?wsdl
 *		Subscriber Agreement: https://system.globalsign.com/qb/ws/GasQuery?wsdl
 * Test account WSDL files are available from:
 *		SSL Functions: https://testsystem.globalsign.com/kb/ws/v1/ServerSSLService?wsdl
 *		Service/Query: https://testsystem.globalsign.com/kb/ws/v1/GASService?wsdl
 *		Account: https://testsystem.globalsign.com/kb/ws/v1/AccountService?wsdl
 *
 * SSL Functions:
 *		GetApproverList		Getting list of approver email addresses
 *		GetDVApproverList		Getting list of approver email addresses and OrderID for DVOrder (DomainSSL and AlphaSSL only)
 *		DVOrder		Order AlphaSSL or DomainSSL Certificate with Approver Email validation
 *		URLVerification		Order AlphaSSL or DomainSSL Certificate with Metatag validation
 *		URLVerificationForIssue		Order AlphaSSL or DomainSSL Certificate with Metatag validation
 *		OVOrder		Order OrganizationSSL Certificate
 *		EVOrder		Order ExtendedSSL Certificate
 *		ModifyOrder		Changing certificate order status
 *		ResendEmail		Resend Approver Emails for AlphaSSL & DomainSSL orders
 *		CertInviteOrder		Place an order using the cert invite functionality
 *		ChangeApproverEmail		Change the email address that the approval request is sent to for domain validated products
 *		ChangeSubjectAltName	Change the SubjectAltName in certificate.
 *  Service/Query Functions:
 *		GetOrderByOrderID		Searching order information by Order ID
 *		GetModifiedOrders		Searching modified orders by modified date (from/to)
 *		GetOrderByDateRange		Getting order list GetCertificateOrders Searching orders by order date (from/to)
 *		ValidateOrderParameters		Checking order parameter validity
 *		DecodeCSR		Decoding a CSR
 *		ReIssue		Certificate ReIssue
 *		ToggleRenewalNotice		Turn on/off Renewal notice
 *		GetOrderByExpirationDate		Check upcoming expirations
 *  Account Functions:
 *		AccountSnapshot		To view account balance and recent usage
 *		AddResellerDeposit		Add deposit to a sub reseller account
 *		QueryInvoices		Query outstanding invoices
 *		ResellerApplication		Create a sub-reseller account
 *
 * @access public
 */
class GlobalSign
{
	public $functions_wsdl = 'https://system.globalsign.com/kb/ws/v1/ServerSSLService?wsdl';
	public $order_wsdl = 'https://system.globalsign.com/wsdls/gasorder.wsdl';
	public $order_wsdl_new = 'https://system.globalsign.com/kb/ws/v1/GASService?wsdl';
	public $query_wsdl = 'https://system.globalsign.com/wsdls/gasquery.wsdl';
	public $autocsr_wsdl = 'https://system.globalsign.com/wsdls/gasorderwoc.wsdl';

	public $test_functions_wsdl = 'https://testsystem.globalsign.com/kb/ws/v1/ServerSSLService';
	public $test_order_wsdl = 'https://testsystem.globalsign.com/wsdls/gasorder.wsdl';
	public $test_query_wsdl = 'https://testsystem.globalsign.com/wsdls/gasquery.wsdl';
	public $test_autocsr_wsdl = 'https://testsystem.globalsign.com/wsdls/gasorderwoc.wsdl';

	public $order_namespace = 'http://stub.order.gasapiserver.esp.globalsign.com';
	public $query_namespace = 'http://stub.query.gasapiserver.esp.globalsign.com';
	public $autocsr_namespace = 'http://stub.orderwoc.gasapiserver.esp.globalsign.com';

	private $username = '';
	private $password = '';

	private $test_username = '';
	private $test_password = '';

	public $testing = false;
	public $connection_timeout = 1000;

	public $functions_client;
	public $order_client;
	public $order_client_new;
	public $query_client;
	public $autocsr_client;

	public $user_agent = '';
	public $trace_connections = 1;

	public $extra;

	/**
	* GlobalSign::GlobalSign()
	* @param string $username the API username
	* @param string $password the API password
	* @param bool $testing optional (defaults to false) testing
	* @return \GlobalSign
	*/
	public function __construct($username, $password, $testing = false) {
		//myadmin_log('ssl', 'info', "__construct({$username}, {$password})", __LINE__, __FILE__);
		$this->username = $username;
		$this->password = $password;
		$this->testing = $testing;
		if ($this->testing == true) {
			$this->order_wsdl = $this->test_order_wsdl;
			$this->query_wsdl = $this->test_query_wsdl;
			$this->autocsr_wsdl = $this->test_autocsr_wsdl;
		}
		$this->functions_client = new SoapClient($this->functions_wsdl, [
			'user_agent' => $this->user_agent,
			'connection_timeout' => $this->connection_timeout,
			'trace' => $this->trace_connections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		]
		);
		$this->order_client = new SoapClient($this->order_wsdl, [
			'user_agent' => $this->user_agent,
			'connection_timeout' => $this->connection_timeout,
			'trace' => $this->trace_connections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		]
		);
		$this->order_client_new = new SoapClient($this->order_wsdl_new, [
			'user_agent' => $this->user_agent,
			'connection_timeout' => $this->connection_timeout,
			'trace' => $this->trace_connections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		]
		);

		$this->query_client = new SoapClient($this->query_wsdl, [
			'user_agent' => $this->user_agent,
			'connection_timeout' => $this->connection_timeout,
			'trace' => $this->trace_connections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		]
		);
		$this->autocsr_client = new SoapClient($this->autocsr_wsdl, [
			'user_agent' => $this->user_agent,
			'connection_timeout' => $this->connection_timeout,
			'trace' => $this->trace_connections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		]
		);
		$this->query_client->_namespace = $this->query_namespace;
		$this->order_client->_namespace = $this->order_namespace;
		$this->autocsr_client->_namespace = $this->autocsr_namespace;
	}

	/**
	* GlobalSign::list_certs()
	* @return mixed
	*/
	public function list_certs() {
		$res = $this->GetCertificateOrders();
		print_r($res);
		return $res;
	}

	/**
	* GlobalSign::create_alphassl()
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $approver_email
	* @param bool  $wildcard
	* @return array
	*/
	public function create_alphassl($fqdn, $csr, $firstname, $lastname, $phone, $email, $approver_email, $wildcard = false) {
		//print_r($res);
		$product = 'DV_LOW_SHA2';
		$res = $this->GSValidateOrderParameters($product, $fqdn, $csr, $wildcard);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			//			print_r($res);
			//			return false;
		}
		$this->__construct($this->username, $this->password);
		$res = $this->GetDVApproverList($fqdn);
		$this->extra['laststep'] = 'GetDVApproverList';
		$this->extra['GetDVApproverList'] = obj2array($res);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			//			return $this->extra;
		}
		$order_id = $res->Response->OrderID;
		$this->extra['order_id'] = $order_id;
		if ($approver_email == '') {
			$approver_email = $res->Response->Approvers->Approver[0]->ApproverEmail;
		}
		myadmin_log('ssl', 'info', "GSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard)", __LINE__, __FILE__);
		$this->__construct($this->username, $this->password);
		$res = $this->GSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard);
		myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'GSDVOrder';
		$this->extra['GSDVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0)
			$this->extra['error'] = 'Error In order';
		else
			$this->extra['finished'] = 1;
		//print_r($res);
		return $this->extra;
	}

	/**
	* GlobalSign::create_domainssl()
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $approver_email
	* @param bool  $wildcard
	* @return array|bool
	*/
	public function create_domainssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $approver_email, $wildcard = false) {
		//print_r($res);
		$product = 'DV_SHA2';
		$res = $this->GSValidateOrderParameters($product, $fqdn, $csr, $wildcard);
		myadmin_log('ssl', 'info', "GSValidateOrderParameters($product, $fqdn, [CSR], $wildcard) returned: " . json_encode($res), __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			myadmin_log('ssl', 'info', 'create_domainssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$this->__construct($this->username, $this->password);
		$res = $this->GetDVApproverList($fqdn);
		myadmin_log('ssl', 'info', "GetDVApproverList($fqdn) returned: " . json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'GetDVApproverList';
		$this->extra['GetDVApproverList'] = obj2array($res);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			myadmin_log('ssl', 'info', 'create_domainssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$order_id = $res->Response->OrderID;
		$this->extra['order_id'] = $order_id;
		if ($approver_email == '') {
			$approver_email = $res->Response->Approvers->Approver[0]->ApproverEmail;
		}
		$this->__construct($this->username, $this->password);
		$res = $this->GSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard);
		myadmin_log('ssl', 'info', "GSDVOrder($product, $order_id, $approver_email, $fqdn, [CSR], $firstname, $lastname, $phone, $email, $wildcard) returned: " . json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'GSDVOrder';
		$this->extra['GSDVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_domainssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			$this->extra['finished'] = 1;
			dialog('Order Completed', 'Your SSL Certificate order has been successfully processed.');
		}
		//print_r($res);
		return $this->extra;
	}

	/**
	* GlobalSign::create_domainssl_autocsr()
	* @param mixed $fqdn
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $approver_email
	* @param bool  $wildcard
	* @return bool
	*/
	public function create_domainssl_autocsr($fqdn, $firstname, $lastname, $phone, $email, $approver_email, $wildcard = false) {
		$res = $this->GetDVApproverList($fqdn);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
			print_r($res);
			return false;
		}
		$order_id = $res->Response->OrderID;
		if ($approver_email == '') {
			$approver_email = $res->Response->Approvers->Approver[0]->ApproverEmail;
		}

		$this->__construct($this->username, $this->password);
		$res = $this->GSDVOrderWithoutCSR($fqdn, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $wildcard);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_domainssl_autocsrf returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			echo 'Your Order Has Been Completed';
		}
		//print_r($res);
		return $order_id;
	}

	/**
	* GlobalSign::create_organizationssl()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param mixed $approver_email
	* @param bool  $wildcard
	* @return array|bool
	*/
	public function create_organizationssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approver_email, $wildcard = false) {
		$res = $this->GSValidateOrderParameters('OV_SHA2', $fqdn, $csr, $wildcard);
		myadmin_log('ssl', 'info', 'GSValidateOrderParameters returned '.str_replace("\n", '', var_export($res, TRUE)), __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
			print_r($res);
			myadmin_log('ssl', 'info', 'SSL Renew Order Error in validation - create_organizationssl', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$order_id = $res->Response->OrderID;
		$this->__construct($this->username, $this->password);
		$res = $this->GSOVOrder($fqdn, $csr, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard);
		$this->extra['laststep'] = 'GSOVOrder';
		$this->extra['GSOVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_organizationssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - create_organizationssl', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		//print_r($res);
		$this->extra['order_id'] = $order_id;
		return $this->extra;
	}

	/**
	* GlobalSign::create_organizationssl_autocsr()
	*
	* @param mixed $fqdn
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param mixed $approver_email
	* @param       $wildcard
	* @return bool
	*/
	public function create_organizationssl_autocsr($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approver_email, $wildcard) {
		//print_r($res);
		$res = $this->GSOVOrderWithoutCSR($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_organizationalssl_autocsr returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			echo 'Your Order Has Been Completed';
		}
		//print_r($res);
		$order_id = $res->Response->OrderID;
		return $order_id;
	}

	/**
	* GlobalSign::create_extendedssl()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param mixed $business_category
	* @param mixed $agency
	* @param mixed $approver_email
	* @return array|bool
	*/
	public function create_extendedssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency, $approver_email) {
		$res = $this->GSValidateOrderParameters('EV_SHA2', $fqdn, $csr);

		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_extendedssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$this->__construct($this->username, $this->password);

		$res = $this->GSEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency);
		$order_id = $res->Response->OrderID;
		$this->extra['laststep'] = 'GSEVOrder';
		$this->extra['GSEVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'create_extendedssl returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
		}
		//print_r($res);
		$this->extra['order_id'] = $order_id;
		return $this->extra;
	}

	/**
	* GlobalSign::GetOrderByOrderID()
	*
	* @param mixed $order_id
	* @return array
	*/
	public function GetOrderByOrderID($order_id) {
		$params = [
			'GetOrderByOrderID' => [
				'Request' => [
					'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'OrderID' => $order_id,
					'OrderQueryOption' => [
						'ReturnOrderOption' => 'true',
						'ReturnCertificateInfo' => 'true',
						'ReturnFulfillment' => 'true',
						'ReturnCACerts' => 'true'
					]
				]
			]
		];
		$this->extra['GetOrderByOrderID_params'] = $params;
		return obj2array($this->query_client->__soapCall('GetOrderByOrderID', $params));
	}

	/**
	* GlobalSign::GetOrderByDataRange()
	*
	* @param mixed $fromdate
	* @param mixed $todate
	* @return mixed
	*/
	public function GetOrderByDataRange($fromdate, $todate) {
		$params = [
			'GetOrderByDataRange' => [
				'Request' => [
					'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'FromDate' => $fromdate,
					'ToDate' => $todate
				]
			]
		];
		$this->extra['GetOrderByDataRange_params'] = $params;
		return $this->query_client->__soapCall('GetOrderByDataRange', $params);
	}

	/**
	* GlobalSign::GetCertificateOrders()
	*
	* @param mixed $fromdate
	* @param mixed $todate
	* @return mixed
	*/
	private function GetCertificateOrders($fromdate, $todate) {
		$params = ['GetCertificateOrders' => ['Request' => ['QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]]]]];
		$this->extra['GetCertificateOrders'] = $params;
		return $this->query_client->__soapCall('GetCertificateOrders', $params);
	}

	/**
	* GlobalSign::GSValidateOrderParameters()
	*
	* @param mixed  $product
	* @param mixed  $fqdn
	* @param string $csr
	* @param bool   $wildcard
	* @return mixed
	*/
	private function GSValidateOrderParameters($product, $fqdn, $csr = '', $wildcard = false) {
		// 1.1 Extracting Common Name from the CSR and carrying out a Phishing DB Check
		$OrderType = 'new';
		$params = [
			'GSValidateOrderParameters' => [
				'Request' => [
					'OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => $OrderType,
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12']
					],
					'FQDN' => $fqdn
				]
			]
		];
		if ($wildcard === true) {
			$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		if ($csr != '') {
			$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['CSR'] = $csr;
			unset($params['GSValidateOrderParameters']['Request']['FQDN']);
		}
		$this->extra['GSValidateOrderParameters_params'] = $params;
		$res = $this->order_client->__soapCall('GSValidateOrderParameters', $params);
		return $res;
	}

	/**
	* GlobalSign::GSDVOrder()
	*
	* @param mixed $product
	* @param mixed $order_id
	* @param mixed $approver_email
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param bool  $wildcard
	* @return mixed
	*/
	private function GSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard = false) {

		/*
		* $Options = array(
		* 'Option' => array(
		* 'OptionName' => 'SAN',
		* 'OptionValue' => 'true',
		* ),
		* );
		* $params['GSDVOrder']['Request']['OrderRequestParameter']['Options'] = $Options;

		* $SANEntries => array(
		* 'SANEntry' => array(
		* array(
		* 'SANOptionType' => '1',
		* 'SubjectAltName' => 'mail.test12345.com',
		* ),
		* array(
		* 'SANOptionType' => '3',
		* 'SubjectAltName' => 'tester.test12345.com',
		* ),
		* ),
		* );
		* $params['GSDVOrder']['Request']['SANEntries'] = $SANEntries;
		*/
		$params = [
			'GSDVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
					'UserName' => $this->username,
					'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => 'new',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr
					],
					'OrderID' => $order_id,
					'ApproverEmail' => $approver_email,
					'ContactInfo' => [
				'FirstName' => $firstname,
				'LastName' => $lastname,
				'Phone' => $phone,
				'Email' => $email
					]
				]
			]
		];

		if ($wildcard === true) {
			$params['GSDVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSDVOrder_params'] = $params;
		//  	    ini_set("max_input_time", -1);
		//	        ini_set("max_execution_time", -1);
		ini_set('max_execution_time', 1000); // just put a lot of time
		ini_set('default_socket_timeout', 1000); // same
		$res = $this->order_client->__soapCall('GSDVOrder', $params);
		return $res;
	}

	/**
	* GlobalSign::GSOVOrder()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $order_id
	* @param mixed $approver_email
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param bool  $wildcard
	* @return mixed
	*/
	private function GSOVOrder($fqdn, $csr, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = false) {
		$params = [
			'GSOVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
						'UserName' => $this->username,
						'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => 'OV_SHA2',
						'OrderKind' => 'new',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr,
						/*
						* 'Options' => array(
						* 'Option' => array(
						* 'OptionName' => 'SAN',
						* 'OptionValue' => 'true',
						* ),
						* ),
						*/
					],
					'OrderID' => $order_id,
					'ApproverEmail' => $approver_email,
					'OrganizationInfo' => [
						'OrganizationName' => $company, 'OrganizationAddress' => [
						'AddressLine1' => $address,
						'City' => $city,
						'Region' => $state,
						'PostalCode' => $zip,
						'Country' => 'US',
						'Phone' => $phone
						]
					],
					'ContactInfo' => [
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Phone' => $phone,
					'Email' => $email
					],
					/*
					* 'SANEntries' => array(
					* 'SANEntry' => array(
					* array(
					* 'SANOptionType' => '1',
					* 'SubjectAltName' => 'mail.test12345.com',
					* ),
					* array(
					* 'SANOptionType' => '3',
					* 'SubjectAltName' => 'tester.test12345.com',
					* ),
					* ),
					* ),
					*/
				]
			]
		];

		if ($wildcard === true) {
			$params['GSOVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSOVOrder_params'] = $params;
		$res = $this->order_client->__soapCall('GSOVOrder', $params);
		return $res;
	}

	/**
	* GlobalSign::GSOVOrderWithoutCSR()
	*
	* @param mixed $fqdn
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param bool  $wildcard
	* @return mixed
	*/
	private function GSOVOrderWithoutCSR($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = false) {
		$params = [
			'GSOVOrderWithoutCSR' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
						]
					],
					'OrderRequestParameterWithoutCSR' => [
						'ProductCode' => 'OV_SKIP_SHA2',
						'OrderKind' => 'new',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'PIN' => '',
						'KeyLength' => '',
						'Options' => [
							'Option' => [
								'OptionName' => 'SAN',
								'OptionValue' => 'true'
							]
						]
					],
					'OrganizationInfo' => [
						'OrganizationName' => $company, 'OrganizationAddress' => [
							'AddressLine1' => $address,
							'City' => $city,
							'Region' => $state,
							'PostalCode' => $zip,
							'Country' => 'US',
							'Phone' => $phone
						]
					],
					'FQDN' => $fqdn,
					'OVCSRInfo' => [
						'OrganizationName' => $company,
						'Locality' => $city,
						'StateOrProvince' => $state,
						'Country' => 'US'
					],
					'ContactInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
					],
					'SANEntries' => [
						'SANEntry' => [
							[
								'SANOptionType' => '1',
								'SubjectAltName' => 'mail.test12345.com'
							],
							[
								'SANOptionType' => '3',
								'SubjectAltName' => 'tester.test12345.com'
							]
						]
					]
				]
			]
		];
		if ($wildcard === true) {
			$params['GSOVOrderWithoutCSR']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSOVOrderWithoutCSR_params'] = $params;
		$res = $this->order_client->__soapCall('GSOVOrderWithoutCSR', $params);
		return $res;
	}

	/**
	* GlobalSign::GSEVOrder()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param mixed $business_category PO, GE, or BE for Private Organization, Government Entity, or Business Entity
	* @param mixed $agency
	* @return mixed
	*/
	private function GSEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency) {

		$params = [
			'GSEVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => 'EV_SHA2',
						'OrderKind' => 'new',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr,
						/*
						* 'Options' => array(
						* 'Option' => array(
						* 'OptionName' => 'SAN',
						* 'OptionValue' => 'true',
						* ),
						* ),
						*/
					],
					'OrganizationInfoEV' => [
						'BusinessCategoryCode' => $business_category,
						'OrganizationAddress' => [
							'AddressLine1' => $address,
							'City' => $city,
							'Region' => $state,
							'PostalCode' => $zip,
							'Country' => 'US',
							'Phone' => $phone
						]
					],
					'RequestorInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email,
						'OrganizationName' => $company
					],
					'ApproverInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email,
						'OrganizationName' => $company
					],
					'AuthorizedSignerInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
					],
					'JurisdictionInfo' => [
						'Country' => 'US',
						'StateOrProvince' => $state,
						'Locality' => $city,
						'IncorporatingAgencyRegistrationNumber' => $agency
					],
					'OrganizationInfo' => [
						'OrganizationName' => $company,
						'OrganizationAddress' => [
							'AddressLine1' => $address,
							'City' => $city,
							'Region' => $state,
							'PostalCode' => $zip,
							'Country' => 'US',
							'Phone' => $phone
						]
					],
					'ContactInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
					],
					/*
					* 'SANEntries' => array(
					* 'SANEntry' => array(
					* array(
					* 'SANOptionType' => '1',
					* 'SubjectAltName' => 'mail.test12345.com',
					* ),
					* array(
					* 'SANOptionType' => '3',
					* 'SubjectAltName' => 'tester.test12345.com',
					* ),
					* ),
					* ),
					*/
				]
			]
		];
		//print_r($params);
		$this->extra['GSEVOrder_params'] = $params;
		$res = $this->order_client->__soapCall('GSEVOrder', $params);
		return $res;
	}

	/**
	* GlobalSign::GetDVApproverList()
	*
	* @param mixed $fqdn
	* @return mixed
	*/
	public function GetDVApproverList($fqdn) {
		// 1.1 Receive List of Approver email addresses
		$params = ['GetDVApproverList' => ['Request' => ['QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'FQDN' => $fqdn]]];
		$this->extra['GetDVApproverList_params'] = $params;
		myadmin_log('ssl', 'info', 'Calling GetDVApproverList', __LINE__, __FILE__);
		myadmin_log('ssl', 'info', str_replace("\n", '', var_export($params, TRUE)), __LINE__, __FILE__);
		return $this->query_client->__soapCall('GetDVApproverList', $params);
	}

	/**
	 * GlobalSign::ResendEmail()
	 *
	 * @param string $orderID
	 * @param string $approverEmail
	 * @return mixed
	 */
	public function GSResendEmail($orderID, $approverEmail) {
		myadmin_log('ssl', 'info', "In function : GSResendEmail($orderID, $approverEmail)", __LINE__, __FILE__);
		$params = ['ResendEmail' => ['Request' => ['OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'OrderID' => $orderID, 'ResendEmailType' =>$approverEmail]]];
		//myadmin_log('ssl', 'info', 'Params: ' .print_r($params), __LINE__, __FILE__);
		return $this->order_client->__soapCall('ResendEmail', $params);
	}

	/**
	 * GlobalSign::ChangeApproverEmail()
	 *
	 * @param $orderID
	 * @param $approverEmail
	 * @param $fqdn
	 * @return string
	 * @internal param mixed $fdqn
	 */
	public function GSChangeApproverEmail($orderID, $approverEmail, $fqdn) {
		$params = [
			'ChangeApproverEmail' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
						]
					],
					'OrderID' => $orderID,
					'ApproverEmail'=>$approverEmail,
					'FQDN'=>$fqdn
				]
			]
		];
		return $this->functions_client->__soapCall('ChangeApproverEmail', $params);
	}

	/**
	 * GlobalSign::renewGSValidateOrderParameters()
	 *
	 * @param mixed  $product
	 * @param mixed  $fqdn
	 * @param string $csr
	 * @param bool   $wildcard
	 * @param bool   $order_id
	 * @return mixed
	 */
	private function renewGSValidateOrderParameters($product, $fqdn, $csr = '', $wildcard = false, $order_id = false) {
		// 1.1 Extracting Common Name from the CSR and carrying out a Phishing DB Check
		$params = [
			'GSValidateOrderParameters' => [
				'Request' => [
					'OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => 'renewal',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12']
					],
					'FQDN' => $fqdn
				]
			]
		];
		if ($wildcard === true) {
			$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		if ($csr != '') {
			$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['CSR'] = $csr;
			unset($params['GSValidateOrderParameters']['Request']['FQDN']);
		}
		if($order_id) {
			$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['RenewalTargetOrderID'] = $order_id;
		}
		$params['GSValidateOrderParameters']['Request']['OrderRequestParameter']['CSR'] = $csr;
		$this->extra['GSValidateOrderParameters_params'] = $params;
		myadmin_log('ssl', 'info', 'Params: ' .print_r($params), __LINE__, __FILE__);
		$res = $this->order_client->__soapCall('GSValidateOrderParameters', $params);
		return $res;
	}

	/**
	 * GlobalSIgn::renewAlphaDomain()
	 *
	 * @param $fqdn
	 * @param $csr
	 * @param $firstname
	 * @param $lastname
	 * @param $phone
	 * @param $email
	 * @param $approver_email
	 * @param bool $wildcard
	 * @param $SSLType
	 * @param $oldOrderId
	 * @return array
	 */
	public function renewAlphaDomain($fqdn, $csr, $firstname, $lastname, $phone, $email, $approver_email, $wildcard = false, $SSLType, $oldOrderId) {
		myadmin_log('ssl', 'info', "renew AlphaDomain called - renewAlphaDomain($fqdn, $csr, $firstname, $lastname, $phone, $email, $approver_email, $wildcard, $SSLType, $oldOrderId)", __LINE__, __FILE__);
		if($SSLType == 1) {
			$product = 'DV_LOW_SHA2';
		} else {
			$product = 'DV_SHA2';
		}
		$res = $this->renewGSValidateOrderParameters($product, $fqdn, $csr, $wildcard, $oldOrderId);
		myadmin_log('ssl', 'info', "renewGSValidateOrderParameters($product, $fqdn, $csr, $wildcard, $oldOrderId)", __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'renewGSValidateOrderParameters returned: ' . json_encode($res), __LINE__, __FILE__);
			$this->extra['error'] = 'Error In order';
			return $this->extra;
		}
		$this->__construct($this->username, $this->password);
		$res = $this->GetDVApproverList($fqdn);
		$this->extra['laststep'] = 'GetDVApproverList';
		$this->extra['GetDVApproverList'] = obj2array($res);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			myadmin_log('ssl', 'info', 'SSL Renew Order Error in GetDVApproverList - renewAlphaDomain', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		$order_id = $res->Response->OrderID;
		$this->extra['order_id'] = $order_id;
		if ($approver_email == '') {
			$approver_email = $res->Response->Approvers->Approver[0]->ApproverEmail;
		}
		myadmin_log('ssl', 'info', "renewGSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderId)", __LINE__, __FILE__);
		$this->__construct($this->username, $this->password);
		$res = $this->renewGSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderId);
		$this->extra['laststep'] = 'GSDVOrder';
		$this->extra['GSDVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'renewGSValidateOrderParameters returned: ' . json_encode($res), __LINE__, __FILE__);
		} else {
			$this->extra['finished'] = 1;
			myadmin_log('ssl', 'info', 'SSL Renew Order success - renewAlphaDomain', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		return $this->extra;
	}

	/**
	* GlobalSign::renewGSDVOrder()
	*
	* @param mixed $product
	* @param mixed $order_id
	* @param mixed $approver_email
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param bool  $wildcard
	* @param mixed $oldOrderID
	* @return mixed
	*/
	private function renewGSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard = false, $oldOrderID) {
		myadmin_log('ssl', 'info', "Called renewGSDVOrder - renewGSDVOrder($product, $order_id, $approver_email, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderID)", __LINE__, __FILE__);
		$params = [
			'GSDVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
					'UserName' => $this->username,
					'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => 'renewal',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr,
						'RenewalTargetOrderID' => $oldOrderID
					],
					'OrderID' => $order_id,
					'ApproverEmail' => $approver_email,
					'ContactInfo' => [
				'FirstName' => $firstname,
				'LastName' => $lastname,
				'Phone' => $phone,
				'Email' => $email
					]
				]
			]
		];
		if ($wildcard === true) {
			$params['GSDVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSDVOrder_params'] = $params;
		//  	    ini_set("max_input_time", -1);
		//	        ini_set("max_execution_time", -1);
		ini_set('max_execution_time', 1000); // just put a lot of time
		ini_set('default_socket_timeout', 1000); // same
		myadmin_log('ssl', 'info', 'Params - '.print_r($params), __LINE__, __FILE__);
		$res = $this->order_client->__soapCall('GSDVOrder', $params);
		return $res;
	}

	/**
	* GlobalSign::renewGSOVOrder()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $order_id
	* @param mixed $approver_email
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param bool  $wildcard
	* @param string $oldOrderId
	* @return mixed
	*/
	private function renewGSOVOrder($fqdn, $csr, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = false, $oldOrderId) {
		$params = [
			'GSOVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => 'OV_SHA2',
						'OrderKind' => 'renewal',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr,
						'RenewalTargetOrderID' => $oldOrderId
						/*
						* 'Options' => array(
						* 'Option' => array(
						* 'OptionName' => 'SAN',
						* 'OptionValue' => 'true',
						* ),
						* ),
						*/
					],
					'OrderID' => $order_id,
					'ApproverEmail' => $approver_email,
					'OrganizationInfo' => [
						'OrganizationName' => $company, 'OrganizationAddress' => [
							'AddressLine1' => $address,
							'City' => $city,
							'Region' => $state,
							'PostalCode' => $zip,
							'Country' => 'US',
							'Phone' => $phone
						]
					],
					'ContactInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
					],
					/*
					* 'SANEntries' => array(
					* 'SANEntry' => array(
					* array(
					* 'SANOptionType' => '1',
					* 'SubjectAltName' => 'mail.test12345.com',
					* ),
					* array(
					* 'SANOptionType' => '3',
					* 'SubjectAltName' => 'tester.test12345.com',
					* ),
					* ),
					* ),
					*/
				]
			]
		];

		if ($wildcard === true) {
			$params['GSOVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSOVOrder_params'] = $params;
		$res = $this->order_client->__soapCall('GSOVOrder', $params);
		return $res;
	}

	/**
	* GlobalSign::renewOrganizationSSL()
	*
	* @param mixed $fqdn
	* @param mixed $csr
	* @param mixed $firstname
	* @param mixed $lastname
	* @param mixed $phone
	* @param mixed $email
	* @param mixed $company
	* @param mixed $address
	* @param mixed $city
	* @param mixed $state
	* @param mixed $zip
	* @param mixed $approver_email
	* @param bool  $wildcard
	* @param string $oldOrderId
	* @return array|bool
	*/
	public function renewOrganizationSSL($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approver_email, $wildcard = false, $oldOrderId) {
		$res = $this->renewGSValidateOrderParameters('OV_SHA2', $fqdn, $csr, $wildcard);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'renewOrganizationSSL returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$order_id = $res->Response->OrderID;
		$this->__construct($this->username, $this->password);
		$res = $this->renewGSOVOrder($fqdn, $csr, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard, $oldOrderId);
		$this->extra['laststep'] = 'GSOVOrder';
		$this->extra['GSOVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'renewOrganizationSSL returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - renewOrganizationSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		//print_r($res);
		$this->extra['order_id'] = $order_id;
		return $this->extra;
	}

	/**
	 * GlobalSign::GSEVOrder()
	 *
	 * @param mixed $fqdn
	 * @param mixed $csr
	 * @param mixed $firstname
	 * @param mixed $lastname
	 * @param mixed $phone
	 * @param mixed $email
	 * @param mixed $company
	 * @param mixed $address
	 * @param mixed $city
	 * @param mixed $state
	 * @param mixed $zip
	 * @param mixed $business_category
	 * @param mixed $agency
	 * @param $oldOrderId
	 * @return mixed
	 */
	private function renewGSEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency, $oldOrderId) {
		$params = [
			'GSEVOrder' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
						'UserName' => $this->username,
						'Password' => $this->password
						]
					],
					'OrderRequestParameter' => [
						'ProductCode' => 'EV_SHA2',
						'OrderKind' => 'renewal',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'CSR' => $csr,
						'RenewalTargetOrderID'=>$oldOrderId
					],
					'OrganizationInfoEV' => [
						'BusinessCategoryCode' => $business_category, 'OrganizationAddress' => [
						'AddressLine1' => $address,
						'City' => $city,
						'Region' => $state,
						'PostalCode' => $zip,
						'Country' => 'US',
						'Phone' => $phone
						]
					],
					'RequestorInfo' => [
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Phone' => $phone,
					'Email' => $email,
					'OrganizationName' => $company
					],
					'ApproverInfo' => [
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Phone' => $phone,
					'Email' => $email,
					'OrganizationName' => $company
					],
					'AuthorizedSignerInfo' => [
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Phone' => $phone,
					'Email' => $email
					],
					'JurisdictionInfo' => [
					'Country' => 'US',
					'StateOrProvince' => $state,
					'Locality' => $city,
					'IncorporatingAgencyRegistrationNumber' => $agency
					],
					'OrganizationInfo' => [
						'OrganizationName' => $company, 'OrganizationAddress' => [
						'AddressLine1' => $address,
						'City' => $city,
						'Region' => $state,
						'PostalCode' => $zip,
						'Country' => 'US',
						'Phone' => $phone
						]
					],
					'ContactInfo' => [
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Phone' => $phone,
					'Email' => $email
					]
				]
			]
		];

		//print_r($params);
		$this->extra = [];
		$this->extra['GSEVOrder_params'] = $params;
		$res = $this->order_client->__soapCall('GSEVOrder', $params);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			return false;
		} else {
			$this->extra['finished'] = 1;
			$this->extra['GSEVOrder'] = obj2array($res);
		}
		return $this->extra;
	}

	/**
	 * GlobalSign::renewExtendedSSL()
	 *
	 * @param mixed $fqdn
	 * @param mixed $csr
	 * @param mixed $firstname
	 * @param mixed $lastname
	 * @param mixed $phone
	 * @param mixed $email
	 * @param mixed $company
	 * @param mixed $address
	 * @param mixed $city
	 * @param mixed $state
	 * @param mixed $zip
	 * @param mixed $business_category
	 * @param mixed $agency
	 * @param mixed $approver_email
	 * @param $oldOrderId
	 * @return array|bool
	 */
	public function renewExtendedSSL($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency, $approver_email, $oldOrderId) {
		$res = $this->renewGSValidateOrderParameters('EV_SHA2', $fqdn, $csr, FALSE);
		$this->extra = [];
		$this->extra['laststep'] = 'GSValidateOrderParameters';
		$this->extra['GSValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
//				print_r($res);
			myadmin_log('ssl', 'info', 'SSL Renew Order Error in validation - renewExtendedSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
			return false;
		}
		$this->__construct($this->username, $this->password);

		$order_id = $res->Response->OrderID;
		$res = $this->renewGSEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $business_category, $agency, $oldOrderId);
		$this->extra['laststep'] = 'GSEVOrder';
		$this->extra['GSEVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$serviceInfo[$prefix.'_hostname'];
				admin_mail($subject, $subject.'<br>'.print_r($ret, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.print_r($res, TRUE));
			}
			myadmin_log('ssl', 'info', 'renewExtendedSSL returned: ' . json_encode($res), __LINE__, __FILE__);
			return false;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - renewExtendedSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		//print_r($res);

		$this->extra['order_id'] = $order_id;
		return $this->extra;
	}

	/**
	 * @param $orderID
	 * @param $csr
	 * @return mixed
	 */
	public function GSReIssue($orderID, $csr) {
		$params = ['ReIssue' => ['Request' => ['OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'OrderParameter' => ['CSR' => $csr], 'TargetOrderID' => $orderID, 'HashAlgorithm' =>'SHA256']]];
		return $this->order_client_new->__soapCall('ReIssue', $params);
	}

	/**
	 * GlobalSign::GSDVOrderWithoutCSR()
	 *
	 * @param mixed $fqdn
	 * @param mixed $order_id
	 * @param mixed $approver_email
	 * @param mixed $firstname
	 * @param mixed $lastname
	 * @param mixed $phone
	 * @param mixed $email
	 * @param bool $wildcard
	 * @return mixed
	 */
	private function GSDVOrderWithoutCSR($fqdn, $order_id, $approver_email, $firstname, $lastname, $phone, $email, $wildcard = false) {
		$params = [
			'GSDVOrderWithoutCSR' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
						]
					],
					'OrderRequestParameterWithoutCSR' => [
						'ProductCode' => 'DV_SKIP_SHA2',
						'OrderKind' => 'new',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'PIN' => '',
						'KeyLength' => '',
						'Options' => [
							'Option' => [
								'OptionName' => 'SAN',
								'OptionValue' => 'true'
							]
						]
					],
					'OrderID' => $order_id,
					'FQDN' => $fqdn,
					'DVCSRInfo' => ['Country' => 'US'],
					'ApproverEmail' => $approver_email,
					'ContactInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
					],
					'SANEntries' => [
						'SANEntry' => [
							[
								'SANOptionType' => '1',
								'SubjectAltName' => 'mail.test12345.com'
							],
							[
								'SANOptionType' => '3',
								'SubjectAltName' => 'tester.test12345.com'
							]
						]
					]
				]
			]
		];
		if ($wildcard === true) {
			$params['GSDVOrderWithoutCSR']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		}
		$this->extra['GSDVOrderWithoutCSR_params'] = $params;
		return $order_client->__soapCall('GSDVOrderWithoutCSR', $params);
	}

}
