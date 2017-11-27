<?php
/**
 * GlobalSign SSL Related Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign;

/**
 * GlobalSign
 *
 * SSL Functions:
 *		URLVerification		Order AlphaSSL or DomainSSL Certificate with Metatag validation
 *		URLVerificationForIssue		Order AlphaSSL or DomainSSL Certificate with Metatag validation
 *		ModifyOrder		Changing certificate order status
 *		CertInviteOrder		Place an order using the cert invite functionality
 *		ChangeSubjectAltName	Change the SubjectAltName in certificate.
 *  Service/Query Functions:
 *		GetModifiedOrders		Searching modified orders by modified date (from/to)
 *		DecodeCSR		Decoding a CSR
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
class GlobalSign {
	public $functionsWsdl = 'https://system.globalsign.com/kb/ws/v1/ServerSSLService?wsdl';
	public $queryWsdl = 'https://system.globalsign.com/kb/ws/v1/GASService?wsdl';
	public $accountWsdl = 'https://system.globalsign.com/kb/ws/v1/AccountService?wsdl';

	public $testFunctionsWsdl = 'https://test-gcc.globalsign.com/kb/ws/v1/ServerSSLService?wsdl';
	public $testQueryWsdl = 'https://test-gcc.globalsign.com/kb/ws/v1/GASService?wsdl';
	public $testAccountWsdl = 'https://test-gcc.globalsign.com/kb/ws/v1/AccountService?wsdl';

	private $username = '';
	private $password = '';

	public $testing = FALSE;
	public $connectionTimeout = 1000;

	public $functionsClient;
	public $accountClient;
	public $queryClient;

	public $userAgent = 'MyAdmin GlobalSign Plugin';
	public $traceConnections = 1;

	public $extra;

	/**
	 * GlobalSign::GlobalSign()
	 *
	 * @param string $username the API username
	 * @param string $password the API password
	 * @param bool   $testing  optional (defaults to false) testing
	 */
	public function __construct($username, $password, $testing = FALSE) {
		//myadmin_log('ssl', 'info', "__construct({$username}, {$password})", __LINE__, __FILE__);
		$this->username = $username;
		$this->password = $password;
		$this->testing = $testing;
		$soapOptions = [
			'user_agent' => $this->userAgent,
			'connection_timeout' => $this->connectionTimeout,
			'trace' => $this->traceConnections,
			'cache_wsdl' => WSDL_CACHE_BOTH
		];
		$this->functionsClient = new \SoapClient($this->testing != TRUE ? $this->functionsWsdl : $this->testFunctionsWsdl, $soapOptions);
		$this->accountClient = new \SoapClient($this->testing != TRUE ? $this->accountWsdl : $this->testAccountWsdl, $soapOptions);
		$this->queryClient = new \SoapClient($this->testing != TRUE ? $this->queryWsdl : $this->testQueryWsdl, $soapOptions);
	}

	/**
	 * Searching order information by Order ID
	 *
	 * @param string $orderId
	 * @return array
	 */
	public function GetOrderByOrderID($orderId) {
		$params = [
			'GetOrderByOrderID' => [
				'Request' => [
					'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'OrderID' => $orderId,
					'OrderQueryOption' => [
						'ReturnOrderOption' => 'true',
						'ReturnCertificateInfo' => 'true',
						'ReturnFulfillment' => 'true',
						'ReturnCACerts' => 'true'
		]]]];
		$this->extra['GetOrderByOrderID_params'] = $params;
		return obj2array($this->queryClient->__soapCall('GetOrderByOrderID', $params));
	}

	/**
	 * GlobalSign::GetOrderByDateRange()
	 *
	 * @param string $fromdate optional from date for lookup in YYYY-MM-DDTHH:MM:SS.000Z format
	 * @param string $todate optional to date for lookup in YYYY-MM-DDTHH:MM:SS.000Z format
	 * @return mixed
	 */
	public function GetOrderByDateRange($fromdate, $todate) {
		$params = [
			'GetOrderByDateRange' => [
				'Request' => [
					'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'FromDate' => $fromdate,
					'ToDate' => $todate
		]]];
		$this->extra['GetOrderByDateRange_params'] = $params;
		return $this->queryClient->__soapCall('GetOrderByDateRange', $params);
	}

	/**
	 * return a list of orders based on criteria provided.
	 * sample date might be 2017-10-08T04:54:12.000-05:00
	 * to access response its something like:
	 *  $response->Response->SearchOrderDetails->SearchOrderDetail[0]->OrderID
	 *
	 * @param string $fromdate optional from date for lookup in YYYY-MM-DDTHH:MM:SS.000Z format
	 * @param string $todate optional to date for lookup in YYYY-MM-DDTHH:MM:SS.000Z format
	 * @param string $fqdn optional domain name to check/lookup
	 * @param string $status optional status to check, status can be   1: INITIAL, 2: Waiting for phishing check, 3: Cancelled - Not Issued, 4: Issue completed, 5: Cancelled - Issued, 6: Waiting for revocation, 7: Revoked
	 * @return mixed
	 */
	public function GetCertificateOrders($fromdate = '', $todate = '', $fqdn = '', $status = '') {
		$params = [
			'GetCertificateOrders' => [
				'Request' => [
					'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
				]]];
		if ($fromdate != '')
			$params['GetCertificateOrders']['Request']['FromDate'] = $fromdate;
		if ($todate != '')
			$params['GetCertificateOrders']['Request']['ToDate'] = $todate;
		if ($fqdn != '')
			$params['GetCertificateOrders']['Request']['FQDN'] = $fqdn;
		if ($status != '')
			$params['GetCertificateOrders']['Request']['OrderStatus'] = $status;
		$this->extra['GetCertificateOrders'] = $params;
		return $this->queryClient->__soapCall('GetCertificateOrders', $params);
	}

	/**
	 * Checking order parameter validity
	 *
	 * @param string  $product
	 * @param string  $fqdn
	 * @param string $csr
	 * @param bool   $wildcard
	 * @return mixed
	 */
	public function ValidateOrderParameters($product, $fqdn, $csr = '', $wildcard = FALSE) {
		// 1.1 Extracting Common Name from the CSR and carrying out a Phishing DB Check
		$OrderType = 'new';
		$params = [
			'ValidateOrderParameters' => [
				'Request' => [
					'OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => $OrderType,
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12']
					],
					'FQDN' => $fqdn
		]]];
		if ($wildcard === TRUE)
			$params['ValidateOrderParameters']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		if ($csr != '') {
			$params['ValidateOrderParameters']['Request']['OrderRequestParameter']['CSR'] = $csr;
			unset($params['ValidateOrderParameters']['Request']['FQDN']);
		}
		$this->extra['ValidateOrderParameters_params'] = $params;
		$res = $this->queryClient->__soapCall('ValidateOrderParameters', $params);
		return $res;
	}

	/**
	 * Getting list of approver email addresses and OrderID for DVOrder (DomainSSL and AlphaSSL only)
	 *
	 * @param string $fqdn
	 * @return mixed
	 */
	public function GetDVApproverList($fqdn) {
		// 1.1 Receive List of Approver email addresses
		$params = ['GetDVApproverList' => ['Request' => ['QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'FQDN' => $fqdn]]];
		$this->extra['GetDVApproverList_params'] = $params;
		myadmin_log('ssl', 'info', 'Calling GetDVApproverList', __LINE__, __FILE__);
		myadmin_log('ssl', 'info', json_encode($params), __LINE__, __FILE__);
		return $this->functionsClient->__soapCall('GetDVApproverList', $params);
	}

	/**
	 * GlobalSign::renewValidateOrderParameters()
	 *
	 * @param string  $product
	 * @param string  $fqdn
	 * @param string $csr
	 * @param bool   $wildcard
	 * @param bool   $orderId
	 * @return mixed
	 */
	public function renewValidateOrderParameters($product, $fqdn, $csr = '', $wildcard = FALSE, $orderId = FALSE) {
		// 1.1 Extracting Common Name from the CSR and carrying out a Phishing DB Check
		if ($wildcard === TRUE) {
			$wild_card_str = 'wildcard';
		} else {
			$wild_card_str = '';
		}
		$params = [
			'ValidateOrderParameters' => [
				'Request' => [
					'OrderRequestHeader' => [
						'AuthToken' => [
							'UserName' => $this->username,
							'Password' => $this->password
					]],
					'OrderRequestParameter' => [
						'ProductCode' => $product,
						'OrderKind' => 'renewal',
						'Licenses' => '1',
						'ValidityPeriod' => ['Months' => '12'],
						'BaseOption' => $wild_card_str,
						'CSR' => $csr,
						'RenewalTargetOrderID' => $orderId,
					],
					//'FQDN' => $fqdn
		]]];
		//if ($csr != '')
			//unset($params['ValidateOrderParameters']['Request']['FQDN']);
		$this->extra['ValidateOrderParameters_params'] = $params;
		myadmin_log('ssl', 'info', 'Params: '.json_encode($params), __LINE__, __FILE__);
		$res = $this->queryClient->__soapCall('ValidateOrderParameters', $params);
		return $res;
	}

	/**
	 * Resend Approver Emails for AlphaSSL & DomainSSL orders
	 *
	 * @param string $orderID
	 * @return mixed
	 */
	public function ResendEmail($orderID) {
		myadmin_log('ssl', 'info', "In function : ResendEmail($orderID)", __LINE__, __FILE__);
		$params = ['ResendEmail' => ['Request' => ['OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'OrderID' => $orderID, 'ResendEmailType' =>'APPROVEREMAIL']]];
		myadmin_log('ssl', 'info', 'Params: '.json_encode($params), __LINE__, __FILE__);
		return $this->functionsClient->__soapCall('ResendEmail', $params);
	}

	/**
	 * Change the email address that the approval request is sent to for domain validated products
	 *
	 * @param $orderID
	 * @param $approverEmail
	 * @param $fqdn
	 * @return string
	 * @internal param mixed $fdqn
	 */
	public function ChangeApproverEmail($orderID, $approverEmail, $fqdn) {
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
		]]];
		return $this->functionsClient->__soapCall('ChangeApproverEmail', $params);
	}

	/**
	 * Certificate ReIssue
	 *
	 * @param $orderID
	 * @param $csr
	 * @return mixed
	 */
	public function ReIssue($orderID, $csr) {
		$params = ['ReIssue' => ['Request' => ['OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]], 'OrderParameter' => ['CSR' => $csr], 'TargetOrderID' => $orderID, 'HashAlgorithm' =>'SHA256']]];
		return $this->queryClient->__soapCall('ReIssue', $params);
	}

	/**
	 * Order AlphaSSL or DomainSSL Certificate with Approver Email validation
	 *
	 * @param string $product
	 * @param string $orderId
	 * @param string $approverEmail
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param bool  $wildcard
	 * @return mixed
	 */
	public function DVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard = FALSE) {

		/*
		* $Options = array(
		* 'Option' => array(
		* 'OptionName' => 'SAN',
		* 'OptionValue' => 'true',
		* ),
		* );
		* $params['DVOrder']['Request']['OrderRequestParameter']['Options'] = $Options;

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
		* $params['DVOrder']['Request']['SANEntries'] = $SANEntries;
		*/
		$params = [
			'DVOrder' => [
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
					'OrderID' => $orderId,
					'ApproverEmail' => $approverEmail,
					'ContactInfo' => [
				'FirstName' => $firstname,
				'LastName' => $lastname,
				'Phone' => $phone,
				'Email' => $email
		]]]];
		if ($wildcard === TRUE)
			$params['DVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['DVOrder_params'] = $params;
		//  	    ini_set("max_input_time", -1);
		//	        ini_set("max_execution_time", -1);
		ini_set('max_execution_time', 1000); // just put a lot of time
		ini_set('default_socket_timeout', 1000); // same
		$res = $this->functionsClient->__soapCall('DVOrder', $params);
		return $res;
	}

	/**
	 * GlobalSign::DVOrderWithoutCSR()
	 *
	 * @param string $fqdn
	 * @param string $orderId
	 * @param string $approverEmail
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param bool $wildcard
	 * @return mixed
	 */
	public function DVOrderWithoutCSR($fqdn, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $wildcard = FALSE) {
		$params = [
			'DVOrderWithoutCSR' => [
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
					'OrderID' => $orderId,
					'FQDN' => $fqdn,
					'DVCSRInfo' => ['Country' => 'US'],
					'ApproverEmail' => $approverEmail,
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
		]]]]]];
		if ($wildcard === TRUE)
			$params['DVOrderWithoutCSR']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['DVOrderWithoutCSR_params'] = $params;
		return $this->functionsClient->__soapCall('DVOrderWithoutCSR', $params);
	}

	/**
	 * Order OrganizationSSL Certificate
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $orderId
	 * @param string $approverEmail
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param bool  $wildcard
	 * @return mixed
	 */
	public function OVOrder($fqdn, $csr, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = FALSE) {
		$params = [
			'OVOrder' => [
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
					'OrderID' => $orderId,
					'ApproverEmail' => $approverEmail,
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
		]]];
		if ($wildcard === TRUE)
			$params['OVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['OVOrder_params'] = $params;
		$res = $this->functionsClient->__soapCall('OVOrder', $params);
		return $res;
	}

	/**
	 * GlobalSign::OVOrderWithoutCSR()
	 *
	 * @param string $fqdn
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param bool  $wildcard
	 * @return mixed
	 */
	public function OVOrderWithoutCSR($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = FALSE) {
		$params = [
			'OVOrderWithoutCSR' => [
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
		]]]]]];
		if ($wildcard === TRUE)
			$params['OVOrderWithoutCSR']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['OVOrderWithoutCSR_params'] = $params;
		$res = $this->functionsClient->__soapCall('OVOrderWithoutCSR', $params);
		return $res;
	}

	/**
	 * Order ExtendedSSL Certificate
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $businessCategory PO, GE, or BE for Private Organization, Government Entity, or Business Entity
	 * @param string $agency
	 * @return mixed
	 */
	public function EVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency) {

		$params = [
			'EVOrder' => [
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
						'BusinessCategoryCode' => $businessCategory,
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
		]]];
		$this->extra['EVOrder_params'] = $params;
		$res = $this->functionsClient->__soapCall('EVOrder', $params);
		return $res;
	}

	/**
	 * GlobalSign::create_alphassl()
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $approverEmail
	 * @param bool  $wildcard
	 * @return array
	 */
	public function create_alphassl($fqdn, $csr, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard = FALSE) {
		$product = 'DV_LOW_SHA2';
		$res = $this->ValidateOrderParameters($product, $fqdn, $csr, $wildcard);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0)
			$this->extra['error'] = 'Error In order';
		$this->__construct($this->username, $this->password);
		$res = $this->GetDVApproverList($fqdn);
		$this->extra['laststep'] = 'GetDVApproverList';
		$this->extra['GetDVApproverList'] = obj2array($res);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			//			return $this->extra;
		}
		$orderId = $res->Response->OrderID;
		$this->extra['order_id'] = $orderId;
		if ($approverEmail == '')
			$approverEmail = $res->Response->Approvers->SearchOrderDetail[0]->ApproverEmail;
		myadmin_log('ssl', 'info', "DVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard)", __LINE__, __FILE__);
		$this->__construct($this->username, $this->password);
		$res = $this->DVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard);
		myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'DVOrder';
		$this->extra['DVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0)
			$this->extra['error'] = 'Error In order';
		else
			$this->extra['finished'] = 1;
		return $this->extra;
	}

	/**
	 * GlobalSign::create_domainssl()
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $approverEmail
	 * @param bool  $wildcard
	 * @return array|bool
	 */
	public function create_domainssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard = FALSE) {
		$product = 'DV_SHA2';
		$res = $this->ValidateOrderParameters($product, $fqdn, $csr, $wildcard);
		myadmin_log('ssl', 'info', "ValidateOrderParameters($product, $fqdn, [CSR], $wildcard) returned: ".json_encode($res), __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			myadmin_log('ssl', 'info', 'create_domainssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$this->__construct($this->username, $this->password);
		$res = $this->GetDVApproverList($fqdn);
		myadmin_log('ssl', 'info', "GetDVApproverList($fqdn) returned: ".json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'GetDVApproverList';
		$this->extra['GetDVApproverList'] = obj2array($res);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			myadmin_log('ssl', 'info', 'create_domainssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$orderId = $res->Response->OrderID;
		$this->extra['order_id'] = $orderId;
		if ($approverEmail == '')
			$approverEmail = $res->Response->Approvers->SearchOrderDetail[0]->ApproverEmail;
		$this->__construct($this->username, $this->password);
		$res = $this->DVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard);
		myadmin_log('ssl', 'info', "DVOrder($product, $orderId, $approverEmail, $fqdn, [CSR], $firstname, $lastname, $phone, $email, $wildcard) returned: ".json_encode($res), __LINE__, __FILE__);
		$this->extra['laststep'] = 'DVOrder';
		$this->extra['DVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_domainssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			dialog('Order Completed', 'Your SSL Certificate order has been successfully processed.');
		}
		return $this->extra;
	}

	/**
	 * GlobalSign::create_domainssl_autocsr()
	 * @param string $fqdn
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $approverEmail
	 * @param bool  $wildcard
	 * @return bool
	 */
	public function create_domainssl_autocsr($fqdn, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard = FALSE) {
		$res = $this->GetDVApproverList($fqdn);
		if ($res->Response->QueryResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
			print_r($res->Response->OrderResponseHeader->Errors);
			return FALSE;
		}
		$orderId = $res->Response->OrderID;
		if ($approverEmail == '')
			$approverEmail = $res->Response->Approvers->SearchOrderDetail[0]->ApproverEmail;

		$this->__construct($this->username, $this->password);
		$res = $this->DVOrderWithoutCSR($fqdn, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $wildcard);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_domainssl_autocsrf returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			echo 'Your Order Has Been Completed';
		}
		return $orderId;
	}

	/**
	 * GlobalSign::create_organizationssl()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $approverEmail
	 * @param bool  $wildcard
	 * @return array|bool
	 */
	public function create_organizationssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approverEmail, $wildcard = FALSE) {
		$res = $this->ValidateOrderParameters('OV_SHA2', $fqdn, $csr, $wildcard);
		myadmin_log('ssl', 'info', 'ValidateOrderParameters returned '.json_encode($res), __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
			print_r($res->Response->OrderResponseHeader->Errors);
			myadmin_log('ssl', 'info', 'SSL Renew Order Error in validation - create_organizationssl', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$orderId = $res->Response->OrderID;
		$this->__construct($this->username, $this->password);
		$res = $this->OVOrder($fqdn, $csr, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard);
		$this->extra['laststep'] = 'OVOrder';
		$this->extra['OVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_organizationssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - create_organizationssl', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		$this->extra['order_id'] = $orderId;
		return $this->extra;
	}

	/**
	 * GlobalSign::create_organizationssl_autocsr()
	 *
	 * @param string $fqdn
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param       $wildcard
	 * @return bool
	 */
	public function create_organizationssl_autocsr($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard) {
		$res = $this->OVOrderWithoutCSR($fqdn, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_organizationalssl_autocsr returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			echo 'Your Order Has Been Completed';
		}
		$orderId = $res->Response->OrderID;
		return $orderId;
	}

	/**
	 * GlobalSign::create_extendedssl()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $businessCategory
	 * @param string $agency
	 * @return array|bool
	 */
	public function create_extendedssl($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency) {
		$res = $this->ValidateOrderParameters('EV_SHA2', $fqdn, $csr);

		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_extendedssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$this->__construct($this->username, $this->password);

		$res = $this->EVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency);
		$orderId = $res->Response->OrderID;
		$this->extra['laststep'] = 'EVOrder';
		$this->extra['EVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order.<br>Response: '.json_encode($res->Response->OrderResponseHeader->Errors));
			}
			myadmin_log('ssl', 'info', 'create_extendedssl returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
		}
		$this->extra['order_id'] = $orderId;
		return $this->extra;
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
	 * @param $approverEmail
	 * @param bool $wildcard
	 * @param $sslType
	 * @param $oldOrderId
	 * @return array
	 */
	public function renewAlphaDomain($fqdn, $csr, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard = FALSE, $sslType, $oldOrderId) {
		myadmin_log('ssl', 'info', "renew AlphaDomain called - renewAlphaDomain($fqdn, $csr, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard, $sslType, $oldOrderId)", __LINE__, __FILE__);
		if ($sslType == 1) {
			$product = 'DV_LOW_SHA2';
		} else {
			$product = 'DV_SHA2';
		}
		$res = $this->renewValidateOrderParameters($product, $fqdn, $csr, $wildcard, $oldOrderId);
		myadmin_log('ssl', 'info', "renewValidateOrderParameters($product, $fqdn, $csr, $wildcard, $oldOrderId)", __LINE__, __FILE__);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team.');
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team.');
			}
			myadmin_log('ssl', 'info', 'renewValidateOrderParameters returned: '.json_encode($res), __LINE__, __FILE__);
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
		$orderId = $res->Response->OrderID;
		$this->extra['order_id'] = $orderId;
		if ($approverEmail == '')
			$approverEmail = $res->Response->Approvers->SearchOrderDetail[0]->ApproverEmail;
		myadmin_log('ssl', 'info', "renewDVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderId)", __LINE__, __FILE__);
		$this->__construct($this->username, $this->password);
		$res = $this->renewDVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderId);
		$this->extra['laststep'] = 'DVOrder';
		$this->extra['DVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			$this->extra['error'] = 'Error In order';
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
			}
			myadmin_log('ssl', 'info', 'renewValidateOrderParameters returned: '.json_encode($res), __LINE__, __FILE__);
		} else {
			$this->extra['finished'] = 1;
			myadmin_log('ssl', 'info', 'SSL Renew Order success - renewAlphaDomain', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		return $this->extra;
	}

	/**
	 * GlobalSign::renewDVOrder()
	 *
	 * @param string $product
	 * @param string $orderId
	 * @param string $approverEmail
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param bool  $wildcard
	 * @param string $oldOrderID
	 * @return mixed
	 */
	public function renewDVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard = FALSE, $oldOrderID) {
		myadmin_log('ssl', 'info', "Called renewDVOrder - renewDVOrder($product, $orderId, $approverEmail, $fqdn, $csr, $firstname, $lastname, $phone, $email, $wildcard, $oldOrderID)", __LINE__, __FILE__);
		$params = [
			'DVOrder' => [
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
						'RenewaltargetOrderID' => $oldOrderID,
						'RenewalTargetOrderID' => $oldOrderID,
						'CSR' => $csr
					],
					'OrderID' => $orderId,
					'ApproverEmail' => $approverEmail,
					'ContactInfo' => [
						'FirstName' => $firstname,
						'LastName' => $lastname,
						'Phone' => $phone,
						'Email' => $email
		]]]];
		if ($wildcard === TRUE)
			$params['DVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['DVOrder_params'] = $params;
		//  	    ini_set("max_input_time", -1);
		//	        ini_set("max_execution_time", -1);
		ini_set('max_execution_time', 1000); // just put a lot of time
		ini_set('default_socket_timeout', 1000); // same
		myadmin_log('ssl', 'info', 'Params - '.json_encode($params), __LINE__, __FILE__);
		$res = $this->functionsClient->__soapCall('DVOrder', $params);
		return $res;
	}

	/**
	 * GlobalSign::renewOVOrder()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $orderId
	 * @param string $approverEmail
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param bool  $wildcard
	 * @param string $oldOrderId
	 * @return mixed
	 */
	public function renewOVOrder($fqdn, $csr, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard = FALSE, $oldOrderId) {
		$params = [
			'OVOrder' => [
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
						'RenewaltargetOrderID' => $oldOrderId,
						'RenewalTargetOrderID' => $oldOrderId,
						'CSR' => $csr
						/*
						* 'Options' => array(
						* 'Option' => array(
						* 'OptionName' => 'SAN',
						* 'OptionValue' => 'true',
						* ),
						* ),
						*/
					],
					'OrderID' => $orderId,
					'ApproverEmail' => $approverEmail,
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
		]]];
		if ($wildcard === TRUE)
			$params['OVOrder']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
		$this->extra['OVOrder_params'] = $params;
		$res = $this->functionsClient->__soapCall('OVOrder', $params);
		return $res;
	}

	/**
	 * GlobalSign::renewOrganizationSSL()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $approverEmail
	 * @param bool  $wildcard
	 * @param string $oldOrderId
	 * @return array|bool
	 */
	public function renewOrganizationSSL($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $approverEmail, $wildcard = FALSE, $oldOrderId) {
		$res = $this->renewValidateOrderParameters('OV_SHA2', $fqdn, $csr, $wildcard);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
			}
			myadmin_log('ssl', 'info', 'renewOrganizationSSL returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$orderId = $res->Response->OrderID;
		$this->__construct($this->username, $this->password);
		$res = $this->renewOVOrder($fqdn, $csr, $orderId, $approverEmail, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $wildcard, $oldOrderId);
		$this->extra['laststep'] = 'OVOrder';
		$this->extra['OVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team');
			}
			myadmin_log('ssl', 'info', 'renewOrganizationSSL returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - renewOrganizationSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		$this->extra['order_id'] = $orderId;
		return $this->extra;
	}

	/**
	 * GlobalSign::EVOrder()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $businessCategory
	 * @param string $agency
	 * @param $oldOrderId
	 * @return mixed
	 */
	public function renewEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency, $oldOrderId) {
		$params = [
			'EVOrder' => [
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
						'RenewaltargetOrderID'=>$oldOrderId,
						'RenewalTargetOrderID'=>$oldOrderId,
						'CSR' => $csr
					],
					'OrganizationInfoEV' => [
						'BusinessCategoryCode' => $businessCategory, 'OrganizationAddress' => [
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
		]]]];
		$this->extra = [];
		$this->extra['EVOrder_params'] = $params;
		$res = $this->functionsClient->__soapCall('EVOrder', $params);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			$this->extra['EVOrder'] = obj2array($res);
		}
		return $this->extra;
	}

	/**
	 * GlobalSign::renewExtendedSSL()
	 *
	 * @param string $fqdn
	 * @param string $csr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $phone
	 * @param string $email
	 * @param string $company
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $businessCategory
	 * @param string $agency
	 * @param $oldOrderId
	 * @return array|bool
	 */
	public function renewExtendedSSL($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency, $oldOrderId) {
		$res = $this->renewValidateOrderParameters('EV_SHA2', $fqdn, $csr, FALSE);
		$this->extra = [];
		$this->extra['laststep'] = 'ValidateOrderParameters';
		$this->extra['ValidateOrderParameters'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			echo "Error In order\n";
			myadmin_log('ssl', 'info', 'SSL Renew Order Error in validation - renewExtendedSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
			return FALSE;
		}
		$this->__construct($this->username, $this->password);

		$orderId = $res->Response->OrderID;
		$res = $this->renewEVOrder($fqdn, $csr, $firstname, $lastname, $phone, $email, $company, $address, $city, $state, $zip, $businessCategory, $agency, $oldOrderId);
		$this->extra['laststep'] = 'EVOrder';
		$this->extra['EVOrder'] = obj2array($res);
		if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
			if ($res->Response->OrderResponseHeader->Errors->Error->ErrorMessage == 'Balance Error') {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team.');
				$subject = 'GlobalSign Balance/Funds Error While Registering '.$fqdn;
				admin_mail($subject, $subject.'<br>'.print_r($res, TRUE), FALSE, FALSE, 'admin_email_ssl_error.tpl');
			} else {
				dialog('Error In Order', 'There was an error procesisng your order. Please contact our support team.');
			}
			myadmin_log('ssl', 'info', 'renewExtendedSSL returned: '.json_encode($res), __LINE__, __FILE__);
			return FALSE;
		} else {
			$this->extra['finished'] = 1;
			echo 'Your Order Has Been Completed';
			myadmin_log('ssl', 'info', 'SSL Renew Order Success - renewExtendedSSL', __LINE__, __FILE__);
			myadmin_log('ssl', 'info', json_encode($res), __LINE__, __FILE__);
		}
		$this->extra['order_id'] = $orderId;
		return $this->extra;
	}
}
