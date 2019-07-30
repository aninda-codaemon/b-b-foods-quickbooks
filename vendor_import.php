<?php
// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Require the framework
require_once 'QuickBooks.php';

// Require the neccessary variables/methods
require_once 'Includes.php';

// Require the neccessary variables/methods only for import 
require_once 'IncludesForImport.php';

// If we haven't done our one-time initialization yet, do it now!
if (!QuickBooks_Utilities::initialized(QB_QUICKBOOKS_DSN))
{
	// Create the database tables
	QuickBooks_Utilities::initialize(QB_QUICKBOOKS_DSN);
	
	// Add the default authentication username/password
	QuickBooks_Utilities::createUser(QB_QUICKBOOKS_DSN, QUICKBOOKS_USER, QUICKBOOKS_PASS);
}

// Initialize the queue
QuickBooks_WebConnector_Queue_Singleton::initialize(QB_QUICKBOOKS_DSN);

// Create a new server and tell it to handle the requests
$Server = new QuickBooks_WebConnector_Server(QB_QUICKBOOKS_DSN, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/**
 * Login success hook - perform an action when a user logs in via the Web Connector
 *
 * 
 */
function _quickbooks_hook_loginsuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
{
	// For new users, we need to set up a few things

	// Fetch the queue instance
	$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
	$date = '1983-01-02 12:01:01';
	
	// Set up the vendor imports
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_VENDOR))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_VENDOR, $date);
	}
	
	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_VENDOR, 1, QB_PRIORITY_VENDOR, null, $user);
}

/**
 * Build a request to import vendors already in QuickBooks into our database
 */
function _quickbooks_vendor_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	// Iterator support (break the result set into small chunks)
	$attr_iteratorID = '';
	$attr_iterator = ' iterator="Start" ';
	if (empty($extra['iteratorID']))
	{
		// This is the first request in a new batch
		$last = _quickbooks_get_last_run($user, $action);
		_quickbooks_set_last_run($user, $action);			// Update the last run time to NOW()
		
		// Set the current run to $last
		_quickbooks_set_current_run($user, $action, $last);
	}
	else
	{
		// This is a continuation of a batch
		$attr_iteratorID = ' iteratorID="' . $extra['iteratorID'] . '" ';
		$attr_iterator = ' iterator="Continue" ';
		
		$last = _quickbooks_get_current_run($user, $action);
	}
	
	// Build the request
	$xml = '<?xml version="1.0" encoding="utf-8"?>
		<?qbxml version="' . $version . '"?>
		<QBXML>
			<QBXMLMsgsRq onError="stopOnError">
				<VendorQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<FromModifiedDate>' . $last . '</FromModifiedDate>
					<OwnerID>0</OwnerID>
				</VendorQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_vendor_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_VENDOR, null, QB_PRIORITY_VENDOR, array( 'iteratorID' => $idents['iteratorID'] ), $user);
	}
	
	// This piece of the response from QuickBooks is now stored in $xml. You 
	//	can process the qbXML response in $xml in any way you like. Save it to 
	//	a file, stuff it in a database, parse it and stuff the records in a 
	//	database, etc. etc. etc. 
	//	
	// The following example shows how to use the built-in XML parser to parse 
	//	the response and stuff it into a database. 
	
	// Import all of the records
	$errnum = 0;
	$errmsg = '';
	$Parser = new QuickBooks_XML_Parser($xml);
	if ($Doc = $Parser->parse($errnum, $errmsg))
	{
		$Root = $Doc->getRoot();
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/VendorQueryRs');
		
		foreach ($List->children() as $Vendor)
		{
			$is_active = $Vendor->getChildDataAt('VendorRet IsActive');
			$is_active_val = ($is_active == true ? '1' : '0');
			
			$arr = array(
                'ListID' => $Vendor->getChildDataAt('VendorRet ListID'),
				'TimeCreated' => $Vendor->getChildDataAt('VendorRet TimeCreated'),
				'TimeModified' => $Vendor->getChildDataAt('VendorRet TimeModified'),
				'EditSequence' => $Vendor->getChildDataAt('VendorRet EditSequence'),
                'Name' => $Vendor->getChildDataAt('VendorRet Name'),
                'IsActive' => $is_active_val,
                'CompanyName' => $Vendor->getChildDataAt('VendorRet CompanyName'),
                'Salutation' => $Vendor->getChildDataAt('VendorRet Salutation'),
				'FirstName' => $Vendor->getChildDataAt('VendorRet FirstName'),
				'MiddleName' => $Vendor->getChildDataAt('VendorRet MiddleName'),
                'LastName' => $Vendor->getChildDataAt('VendorRet LastName'),
                'VendorAddress_Addr1' => $Vendor->getChildDataAt('VendorRet VendorAddress Addr1'),
                'VendorAddress_Addr2' => $Vendor->getChildDataAt('VendorRet VendorAddress Addr2'),
                'VendorAddress_Addr3' => $Vendor->getChildDataAt('VendorRet VendorAddress Addr3'),
                'VendorAddress_Addr4' => $Vendor->getChildDataAt('VendorRet VendorAddress Addr4'),
                'VendorAddress_Addr5' => $Vendor->getChildDataAt('VendorRet VendorAddress Addr5'),
                'VendorAddress_City' => $Vendor->getChildDataAt('VendorRet VendorAddress City'),
                'VendorAddress_State' => $Vendor->getChildDataAt('VendorRet VendorAddress State'),
                'VendorAddress_PostalCode' => $Vendor->getChildDataAt('VendorRet VendorAddress PostalCode'),
                'VendorAddress_Country' => $Vendor->getChildDataAt('VendorRet VendorAddress Country'),
                'VendorAddress_Note' => $Vendor->getChildDataAt('VendorRet VendorAddress Note'),
                'VendorAddressBlock_Addr1' => $Vendor->getChildDataAt('VendorRet VendorAddressBlock Addr1'),
                'VendorAddressBlock_Addr2' => $Vendor->getChildDataAt('VendorRet VendorAddressBlock Addr2'),
                'VendorAddressBlock_Addr3' => $Vendor->getChildDataAt('VendorRet VendorAddressBlock Addr3'),
                'VendorAddressBlock_Addr4' => $Vendor->getChildDataAt('VendorRet VendorAddressBlock Addr4'),
                'VendorAddressBlock_Addr5' => $Vendor->getChildDataAt('VendorRet VendorAddressBlock Addr5'),
                'Phone' => $Vendor->getChildDataAt('VendorRet Phone'),
				'AltPhone' => $Vendor->getChildDataAt('VendorRet AltPhone'),
				'Fax' => $Vendor->getChildDataAt('VendorRet Fax'),
				'Email' => $Vendor->getChildDataAt('VendorRet Email'),
				'Contact' => $Vendor->getChildDataAt('VendorRet Contact'),
                'AltContact' => $Vendor->getChildDataAt('VendorRet AltContact'),
                'NameOnCheck' => $Vendor->getChildDataAt('VendorRet NameOnCheck'),
                'AccountNumber' => $Vendor->getChildDataAt('VendorRet AccountNumber'),
                'Notes' => $Vendor->getChildDataAt('VendorRet Notes'),
                'VendorType_ListID' => $Vendor->getChildDataAt('VendorRet VendorTypeRef ListID'),
                'VendorType_FullName' => $Vendor->getChildDataAt('VendorRet VendorTypeRef FullName'),
                'Terms_ListID' => $Vendor->getChildDataAt('VendorRet TermsRef ListID'),
                'Terms_FullName' => $Vendor->getChildDataAt('VendorRet TermsRef FullName'),
                'CreditLimit' => $Vendor->getChildDataAt('VendorRet CreditLimit'),
                'VendorTaxIdent' => $Vendor->getChildDataAt('VendorRet VendorTaxIdent'),
                'IsVendorEligibleFor1099' => $Vendor->getChildDataAt('VendorRet IsVendorEligibleFor1099'),
                'Balance' => $Vendor->getChildDataAt('VendorRet Balance'),
                'BillingRate_ListID' => $Vendor->getChildDataAt('VendorRet BillingRateRef ListID'),
                'BillingRate_FullName' => $Vendor->getChildDataAt('VendorRet BillingRateRef FullName')
				);

			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing vendor ' . $arr['Name'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			
			// Store the vendors in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
					qb_example_vendor
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)"); //or die(trigger_error(mysqli_connect_error()));
		}
	}
	
	return true;
}