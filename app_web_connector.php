<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if (function_exists('date_default_timezone_set')) {
	date_default_timezone_set('America/New_York');
}

require_once 'QuickBooks.php';

$user = 'quickbooks';
$pass = 'password';

$map = array(
	QUICKBOOKS_ADD_CUSTOMER => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ),
	);

$errmap = array(
	3070 => '_quickbooks_error_stringtoolong',	
	);

$hooks = array();

// Logging level
//$log_level = QUICKBOOKS_LOG_NORMAL;
$log_level = QUICKBOOKS_LOG_VERBOSE;
//$log_level = QUICKBOOKS_LOG_DEBUG;				
//$log_level = QUICKBOOKS_LOG_DEVELOP;		

// What SOAP server you're using 
//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)

$soap_options = array();

$handler_options = array(
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false, 
	);

$driver_options = array();

$callback_options = array();

$dsn = 'mysqli://root:@localhost/quickbooks_sqli';

if (!QuickBooks_Utilities::initialized($dsn))
{
	QuickBooks_Utilities::initialize($dsn);
	QuickBooks_Utilities::createUser($dsn, $user, $pass);
		
	$Queue = new QuickBooks_WebConnector_Queue($dsn);
	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER);
}

$Server = new QuickBooks_WebConnector_Server($dsn, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

function _quickbooks_customer_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	$xml = '<?xml version="1.0" encoding="utf-8"?>
		<?qbxml version="2.0"?>
		<QBXML>
			<QBXMLMsgsRq onError="stopOnError">
				<CustomerAddRq>
					<CustomerAdd>
						<Name>Codaemon, LLC (' . mt_rand() . ')</Name>
						<CompanyName>Codaemon, LLC</CompanyName>
						<FirstName>Rajib</FirstName>
						<LastName>N.</LastName>
						<BillAddress>
							<Addr1>Codaemon, LLC</Addr1>
							<Addr2>134 Stonemill Road</Addr2>
							<City>Mansfield</City>
							<State>CT</State>
							<PostalCode>06268</PostalCode>
							<Country>United States</Country>
						</BillAddress>
						<Phone>860-634-1602</Phone>
						<AltPhone>860-429-0021</AltPhone>
						<Fax>860-429-5183</Fax>
						<Email>Keith@ConsoliBYTE.com</Email>
						<Contact>Keith Palmer</Contact>
					</CustomerAdd>
				</CustomerAddRq>
			</QBXMLMsgsRq>
		</QBXML>';
	
	return $xml;
}

function _quickbooks_customer_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents) {	

}

function _quickbooks_error_stringtoolong($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
{
	mail('your-email@your-domain.com', 
		'QuickBooks error occured!', 
		'QuickBooks thinks that ' . $action . ': ' . $ID . ' has a value which will not fit in a QuickBooks field...');
}
