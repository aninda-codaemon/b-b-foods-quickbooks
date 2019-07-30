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
	
	// Set up the customer imports
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_CUSTOMER))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_CUSTOMER, $date);
	}

	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_CUSTOMER, 1, QB_PRIORITY_CUSTOMER, null, $user);
}

/**
 * Build a request to import customers already in QuickBooks into our database
 */
function _quickbooks_customer_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<CustomerQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<FromModifiedDate>' . $last . '</FromModifiedDate>
					<OwnerID>0</OwnerID>
				</CustomerQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_customer_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_CUSTOMER, null, QB_PRIORITY_CUSTOMER, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');

		foreach ($List->children() as $Customer)
		{
			$is_active = $Customer->getChildDataAt('CustomerRet IsActive');
			$is_active_val = ($is_active == true ? '1' : '0');
			
			$arr = array(
				'ListID' => $Customer->getChildDataAt('CustomerRet ListID'),
				'TimeCreated' => $Customer->getChildDataAt('CustomerRet TimeCreated'),
				'TimeModified' => $Customer->getChildDataAt('CustomerRet TimeModified'),
				'EditSequence' => $Customer->getChildDataAt('CustomerRet EditSequence'),
				'Name' => $Customer->getChildDataAt('CustomerRet Name'),
				'FullName' => $Customer->getChildDataAt('CustomerRet FullName'),
				'IsActive' => $is_active_val,
				'Parent_ListID' => $Customer->getChildDataAt('CustomerRet Parent ListID'),
				'Parent_FullName' => $Customer->getChildDataAt('CustomerRet Parent FullName'),
				'Sublevel' => $Customer->getChildDataAt('CustomerRet Sublevel'),
				'CompanyName' => $Customer->getChildDataAt('CustomerRet CompanyName'),
				'Salutation' => $Customer->getChildDataAt('CustomerRet Salutation'),
				'FirstName' => $Customer->getChildDataAt('CustomerRet FirstName'),
				'MiddleName' => $Customer->getChildDataAt('CustomerRet MiddleName'),
				'LastName' => $Customer->getChildDataAt('CustomerRet LastName'),
				'BillAddress_Addr1' => $Customer->getChildDataAt('CustomerRet BillAddress Addr1'),
				'BillAddress_Addr2' => $Customer->getChildDataAt('CustomerRet BillAddress Addr2'),
				'BillAddress_Addr3' => $Customer->getChildDataAt('CustomerRet BillAddress Addr3'),
				'BillAddress_Addr4' => $Customer->getChildDataAt('CustomerRet BillAddress Addr4'),
				'BillAddress_Addr5' => $Customer->getChildDataAt('CustomerRet BillAddress Addr5'),
				'BillAddress_City' => $Customer->getChildDataAt('CustomerRet BillAddress City'),
				'BillAddress_State' => $Customer->getChildDataAt('CustomerRet BillAddress State'),
				'BillAddress_PostalCode' => $Customer->getChildDataAt('CustomerRet BillAddress PostalCode'),
				'BillAddress_Country' => $Customer->getChildDataAt('CustomerRet BillAddress Country'),
				'BillAddress_Note' => $Customer->getChildDataAt('CustomerRet BillAddress Note'),
				'BillAddressBlock_Addr1' => $Customer->getChildDataAt('CustomerRet BillAddressBlock Addr1'),
				'BillAddressBlock_Addr2' => $Customer->getChildDataAt('CustomerRet BillAddressBlock Addr2'),
				'BillAddressBlock_Addr3' => $Customer->getChildDataAt('CustomerRet BillAddressBlock Addr3'),
				'BillAddressBlock_Addr4' => $Customer->getChildDataAt('CustomerRet BillAddressBlock Addr4'),
				'BillAddressBlock_Addr5' => $Customer->getChildDataAt('CustomerRet BillAddressBlock Addr5'),
				'ShipAddress_Addr1' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr1'),
				'ShipAddress_Addr2' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr2'),
				'ShipAddress_Addr3' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr3'),
				'ShipAddress_Addr4' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr4'),
				'ShipAddress_Addr5' => $Customer->getChildDataAt('CustomerRet ShipAddress Addr5'),
				'ShipAddress_City' => $Customer->getChildDataAt('CustomerRet ShipAddress City'),
				'ShipAddress_State' => $Customer->getChildDataAt('CustomerRet ShipAddress State'),
				'ShipAddress_PostalCode' => $Customer->getChildDataAt('CustomerRet ShipAddress PostalCode'),
				'ShipAddress_Country' => $Customer->getChildDataAt('CustomerRet ShipAddress Country'),
				'ShipAddressBlock_Addr1' => $Customer->getChildDataAt('CustomerRet ShipAddressBlock Addr1'),
				'ShipAddressBlock_Addr2' => $Customer->getChildDataAt('CustomerRet ShipAddressBlock Addr2'),
				'ShipAddressBlock_Addr3' => $Customer->getChildDataAt('CustomerRet ShipAddressBlock Addr3'),
				'ShipAddressBlock_Addr4' => $Customer->getChildDataAt('CustomerRet ShipAddressBlock Addr4'),
				'ShipAddressBlock_Addr5' => $Customer->getChildDataAt('CustomerRet ShipAddressBlock Addr5'),
				'ShipAddress_Country' => $Customer->getChildDataAt('CustomerRet ShipAddress Country'),
				'Phone' => $Customer->getChildDataAt('CustomerRet Phone'),
				'AltPhone' => $Customer->getChildDataAt('CustomerRet AltPhone'),
				'Fax' => $Customer->getChildDataAt('CustomerRet Fax'),
				'Email' => $Customer->getChildDataAt('CustomerRet Email'),
				'AltEmail' => $Customer->getChildDataAt('CustomerRet AltEmail'),
				'Contact' => $Customer->getChildDataAt('CustomerRet Contact'),
				'AltContact' => $Customer->getChildDataAt('CustomerRet AltContact'),
				'CustomerType_ListID' => $Customer->getChildDataAt('CustomerRet CustomerType ListID'),
				'CustomerType_FullName' => $Customer->getChildDataAt('CustomerRet CustomerType FullName'),
				'Terms_ListID' => $Customer->getChildDataAt('CustomerRet Terms ListID'),
				'Terms_FullName' => $Customer->getChildDataAt('CustomerRet Terms FullName'),
				'SalesRep_ListID' => $Customer->getChildDataAt('CustomerRet SalesRep ListID'),
				'SalesRep_FullName' => $Customer->getChildDataAt('CustomerRet SalesRep FullName'),
				'Balance' => $Customer->getChildDataAt('CustomerRet Balance'),
				'TotalBalance' => $Customer->getChildDataAt('CustomerRet TotalBalance'),
				'SalesTaxCode_ListID' => $Customer->getChildDataAt('CustomerRet SalesTaxCode ListID'),
				'SalesTaxCode_FullName' => $Customer->getChildDataAt('CustomerRet SalesTaxCode FullName'),
				'ItemSalesTax_ListID' => $Customer->getChildDataAt('CustomerRet ItemSalesTax ListID'),
				'ItemSalesTax_FullName' => $Customer->getChildDataAt('CustomerRet ItemSalesTax FullName'),
				'ResaleNumber' => $Customer->getChildDataAt('CustomerRet ResaleNumber'),
				'AccountNumber' => $Customer->getChildDataAt('CustomerRet AccountNumber'),
				'CreditLimit' => $Customer->getChildDataAt('CustomerRet CreditLimit'),
				'PreferredPaymentMethod_ListID' => $Customer->getChildDataAt('CustomerRet PreferredPaymentMethod ListID'),
				'PreferredPaymentMethod_FullName' => $Customer->getChildDataAt('CustomerRet PreferredPaymentMethod FullName'),
				'CreditCardInfo_CreditCardNumber' => $Customer->getChildDataAt('CustomerRet CreditCardInfo CreditCardNumber'),
				'CreditCardInfo_ExpirationMonth' => $Customer->getChildDataAt('CustomerRet CreditCardInfo ExpirationMonth'),
				'CreditCardInfo_ExpirationYear' => $Customer->getChildDataAt('CustomerRet CreditCardInfo ExpirationYear'),
				'CreditCardInfo_NameOnCard' => $Customer->getChildDataAt('CustomerRet CreditCardInfo NameOnCard'),
				'CreditCardInfo_CreditCardAddress' => $Customer->getChildDataAt('CustomerRet CreditCardInfo CreditCardAddress'),
				'CreditCardInfo_CreditCardPostalCode' => $Customer->getChildDataAt('CustomerRet CreditCardInfo CreditCardPostalCode'),
				'JobStatus' => $Customer->getChildDataAt('CustomerRet JobStatus'),
				'JobStartDate' => $Customer->getChildDataAt('CustomerRet JobStartDate'),
				'JobProjectedEndDate' => $Customer->getChildDataAt('CustomerRet JobProjectedEndDate'),
				'JobEndDate' => $Customer->getChildDataAt('CustomerRet JobEndDate'),
				'JobDesc' => $Customer->getChildDataAt('CustomerRet JobDesc'),
				'JobType_ListID' => $Customer->getChildDataAt('CustomerRet JobType ListID'),
				'JobType_FullName' => $Customer->getChildDataAt('CustomerRet JobType FullName'),
				'Notes' => $Customer->getChildDataAt('CustomerRet Notes'),
				'PriceLevel_ListID' => $Customer->getChildDataAt('CustomerRet PriceLevel ListID'),
				'PriceLevel_FullName' => $Customer->getChildDataAt('CustomerRet PriceLevel FullName')
				);
				
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing customer ' . $arr['FullName'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$value = strip_tags($value);
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			
			// Store the records in MySQL
			if (!mysqli_query($dblink, "REPLACE INTO qb_example_customer (" . implode(", ", array_keys($arr)) . ") VALUES ('" . implode("', '", array_values($arr)) . "')")){
				error_log("Error description: " . mysqli_error($dblink));
			}
		}
	}
	
	return true;
}
