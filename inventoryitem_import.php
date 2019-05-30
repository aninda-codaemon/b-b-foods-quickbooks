<?php
// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Support URL
if (!empty($_GET['support']))
{
	header('Location: http://www.consolibyte.com/');
	exit;
}

// We need to make sure the correct timezone is set, or some PHP installations will complain
if (function_exists('date_default_timezone_set'))
{
	// * MAKE SURE YOU SET THIS TO THE CORRECT TIMEZONE! *
	// List of valid timezones is here: http://us3.php.net/manual/en/timezones.php
	date_default_timezone_set('America/New_York');
}

// Require the framework
require_once 'QuickBooks.php';

$user = 'quickbooks';
$pass = 'password';

/**
 * Configuration parameter for the quickbooks_config table, used to keep track of the last time the QuickBooks sync ran
 */
define('QB_QUICKBOOKS_CONFIG_LAST', 'last');

/**
 * Configuration parameter for the quickbooks_config table, used to keep track of the timestamp for the current iterator
 */
define('QB_QUICKBOOKS_CONFIG_CURR', 'curr');

/**
 * Maximum number of customers/invoices returned at a time when doing the import
 */
define('QB_QUICKBOOKS_MAX_RETURNED', 10);

/**
 * Request priorities, items sync first
 */
define('QB_PRIORITY_INVENTORYITEM', 6);

/**
 * Send error notices to this e-mail address
 */
define('QB_QUICKBOOKS_MAILTO', 'keith@consolibyte.com');

// Map QuickBooks actions to handler functions
$map = array(
		QUICKBOOKS_IMPORT_INVENTORYITEM => array( '_quickbooks_inventoryitem_import_request', '_quickbooks_inventoryitem_import_response' ), 
	);

// Error handlers
$errmap = array(
	500 => '_quickbooks_error_e500_notfound', 			// Catch errors caused by searching for things not present in QuickBooks
	1 => '_quickbooks_error_e500_notfound', 
	'*' => '_quickbooks_error_catchall', 				// Catch any other errors that might occur
	);

// An array of callback hooks
$hooks = array(
	QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess', 	// call this whenever a successful login occurs
	);

// Logging level
//$log_level = QUICKBOOKS_LOG_NORMAL;
//$log_level = QUICKBOOKS_LOG_VERBOSE;
//$log_level = QUICKBOOKS_LOG_DEBUG;				// Use this level until you're sure everything works!!!
$log_level = QUICKBOOKS_LOG_DEVELOP;

// What SOAP server you're using 
//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)

$soap_options = array();		// See http://www.php.net/soap

$handler_options = array(		// See the comments in the QuickBooks/Server/Handlers.php file
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false, 
	);		

$driver_options = array();		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )

$callback_options = array();

// * MAKE SURE YOU CHANGE THE DATABASE CONNECTION STRING BELOW TO A VALID MYSQL USERNAME/PASSWORD/HOSTNAME *
$dsn = 'mysqli://root:@localhost/quickbooks_sqli';
$dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");
/**
 * Constant for the connection string (because we'll use it in other places in the script)
 */
define('QB_QUICKBOOKS_DSN', $dsn);

// If we haven't done our one-time initialization yet, do it now!
if (!QuickBooks_Utilities::initialized($dsn))
{
	// Create the database tables
	QuickBooks_Utilities::initialize($dsn);
	
	// Add the default authentication username/password
	QuickBooks_Utilities::createUser($dsn, $user, $pass);
}

// Initialize the queue
QuickBooks_WebConnector_Queue_Singleton::initialize($dsn);

// Create a new server and tell it to handle the requests
// __construct($dsn_or_conn, $map, $errmap = array(), $hooks = array(), $log_level = QUICKBOOKS_LOG_NORMAL, $soap = QUICKBOOKS_SOAPSERVER_PHP, $wsdl = QUICKBOOKS_WSDL, $soap_options = array(), $handler_options = array(), $driver_options = array(), $callback_options = array()
$Server = new QuickBooks_WebConnector_Server($dsn, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
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
	
	// Set up the item imports
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_INVENTORYITEM))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_INVENTORYITEM, $date);
	}
	
	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_INVENTORYITEM, 1, QB_PRIORITY_INVENTORYITEM, null, $user);
}

/**
 * Get the last date/time the QuickBooks sync ran
 * 
 * @param string $user		The web connector username 
 * @return string			A date/time in this format: "yyyy-mm-dd hh:ii:ss"
 */
function _quickbooks_get_last_run($user, $action)
{
	$type = null;
	$opts = null;
	return QuickBooks_Utilities::configRead(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_LAST . '-' . $action, $type, $opts);
}

/**
 * Set the last date/time the QuickBooks sync ran to NOW
 * 
 * @param string $user
 * @return boolean
 */
function _quickbooks_set_last_run($user, $action, $force = null)
{
	$value = date('Y-m-d') . 'T' . date('H:i:s');
	
	if ($force)
	{
		$value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
	}
	
	return QuickBooks_Utilities::configWrite(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_LAST . '-' . $action, $value);
}

/**
 * 
 * 
 */
function _quickbooks_get_current_run($user, $action)
{
	$type = null;
	$opts = null;
	return QuickBooks_Utilities::configRead(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_CURR . '-' . $action, $type, $opts);	
}

/**
 * 
 * 
 */
function _quickbooks_set_current_run($user, $action, $force = null)
{
	$value = date('Y-m-d') . 'T' . date('H:i:s');
	
	if ($force)
	{
		$value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
	}
	
	return QuickBooks_Utilities::configWrite(QB_QUICKBOOKS_DSN, $user, md5(__FILE__), QB_QUICKBOOKS_CONFIG_CURR . '-' . $action, $value);	
}


/**
 * Build a request to import customers already in QuickBooks into our application
 */
function _quickbooks_inventoryitem_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<ItemInventoryQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<FromModifiedDate>' . $last . '</FromModifiedDate>
					<OwnerID>0</OwnerID>
				</ItemInventoryQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_inventoryitem_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_INVENTORYITEM, null, QB_PRIORITY_INVENTORYITEM, array( 'iteratorID' => $idents['iteratorID'] ), $user);
	}
	
	// Import all of the records
	$errnum = 0;
	$errmsg = '';
	$Parser = new QuickBooks_XML_Parser($xml);
	if ($Doc = $Parser->parse($errnum, $errmsg))
	{
		$Root = $Doc->getRoot();
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemInventoryQueryRs');
		
		foreach ($List->children() as $Item)
		{
			$is_active = $Item->getChildDataAt('ItemInventoryRet IsActive');
			$is_active_val = ($is_active == true ? '1' : '0');
			
			$arr = array(
				'ListID' => $Item->getChildDataAt('ItemInventoryRet ListID'),
				'TimeCreated' => $Item->getChildDataAt('ItemInventoryRet TimeCreated'),
				'TimeModified' => $Item->getChildDataAt('ItemInventoryRet TimeModified'),
				'EditSequence' => $Item->getChildDataAt('ItemInventoryRet EditSequence'),
				'Name' => $Item->getChildDataAt('ItemInventoryRet Name'),
				'FullName' => $Item->getChildDataAt('ItemInventoryRet FullName'),
				'IsActive' => $is_active_val,
				'Parent_ListID' => $Item->getChildDataAt('ItemInventoryRet ParentRef ListID'),
				'Parent_FullName' => $Item->getChildDataAt('ItemInventoryRet ParentRef FullName'),
				'Sublevel' =>  $Item->getChildDataAt('ItemInventoryRet Sublevel'),
				'ManufacturerPartNumber' => $Item->getChildDataAt('ItemInventoryRet ManufacturerPartNumber'), 
				'UnitOfMeasureSet_ListID' => $Item->getChildDataAt('ItemInventoryRet UnitOfMeasureSetRef ListID'), 
				'UnitOfMeasureSet_FullName' => $Item->getChildDataAt('ItemInventoryRet UnitOfMeasureSetRef FullName'), 
				'SalesTaxCode_ListID' => $Item->getChildDataAt('ItemInventoryRet SalesTaxCodeRef ListID'), 
				'SalesTaxCode_FullName' => $Item->getChildDataAt('ItemInventoryRet SalesTaxCodeRef FullName'), 
				'SalesDesc' => $Item->getChildDataAt('ItemInventoryRet SalesDesc'), 
				'SalesPrice' => $Item->getChildDataAt('ItemInventoryRet SalesPrice'),
				'IncomeAccount_ListID' => $Item->getChildDataAt('ItemInventoryRet IncomeAccountRef ListID'),
				'IncomeAccount_FullName' => $Item->getChildDataAt('ItemInventoryRet IncomeAccountRef FullName'),
				'PurchaseDesc' => $Item->getChildDataAt('ItemInventoryRet PurchaseDesc'),
				'PurchaseCost' => $Item->getChildDataAt('ItemInventoryRet PurchaseCost'),
				'COGSAccount_ListID' => $Item->getChildDataAt('ItemInventoryRet COGSAccountRef ListID'),
				'COGSAccount_FullName' => $Item->getChildDataAt('ItemInventoryRet COGSAccountRef FullName'),
				'PrefVendor_ListID' => $Item->getChildDataAt('ItemInventoryRet PrefVendorRef ListID'),
				'PrefVendor_FullName' => $Item->getChildDataAt('ItemInventoryRet PrefVendorRef FullName'),
				'AssetAccount_ListID' => $Item->getChildDataAt('ItemInventoryRet AssetAccountRef ListID'),
				'AssetAccount_FullName' => $Item->getChildDataAt('ItemInventoryRet AssetAccountRef FullName'),
				'ReorderPoint' => $Item->getChildDataAt('ItemInventoryRet ReorderPoint'), 
				'QuantityOnHand' => $Item->getChildDataAt('ItemInventoryRet QuantityOnHand'), 
				'AverageCost' => $Item->getChildDataAt('ItemInventoryRet AverageCost'), 
				'QuantityOnOrder' => $Item->getChildDataAt('ItemInventoryRet QuantityOnOrder'), 
				'QuantityOnSalesOrder' => $Item->getChildDataAt('ItemInventoryRet QuantityOnSalesOrder'),  
				);
			
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing Inventory Item ' . $arr['FullName'] . ': ' . print_r($arr, true));
			$dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			
			//print_r(array_keys($arr));
			//trigger_error(print_r(array_keys($arr), true));

			// Store the customers in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
				qb_example_iteminventory
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)"); //or die(trigger_error(mysqli_connect_error()));
		}

	}
	
	return true;
}

/**
 * Handle a 500 not found error from QuickBooks
 * 
 * Instead of returning empty result sets for queries that don't find any 
 * records, QuickBooks returns an error message. This handles those error 
 * messages, and acts on them by adding the missing item to QuickBooks. 
 */
function _quickbooks_error_e500_notfound($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
{
	$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
	
	if ($action == QUICKBOOKS_IMPORT_INVENTORYITEM)
	{
		return true;
	}
	
	return false;
}


/**
 * Catch any errors that occur
 * 
 * @param string $requestID			
 * @param string $action
 * @param mixed $ID
 * @param mixed $extra
 * @param string $err
 * @param string $xml
 * @param mixed $errnum
 * @param string $errmsg
 * @return void
 */
function _quickbooks_error_catchall($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
{
	$message = '';
	$message .= 'Request ID: ' . $requestID . "\r\n";
	$message .= 'User: ' . $user . "\r\n";
	$message .= 'Action: ' . $action . "\r\n";
	$message .= 'ID: ' . $ID . "\r\n";
	$message .= 'Extra: ' . print_r($extra, true) . "\r\n";
	//$message .= 'Error: ' . $err . "\r\n";
	$message .= 'Error number: ' . $errnum . "\r\n";
	$message .= 'Error message: ' . $errmsg . "\r\n";
	
	@mail(QB_QUICKBOOKS_MAILTO, 
		'QuickBooks error occured!', 
		$message);
}
