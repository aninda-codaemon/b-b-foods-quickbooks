<?php
// Require the framework
require_once 'IncludesForDB.php';

/**
 * Configuration parameter for the quickbooks_config table, used to keep track of the last time the QuickBooks sync ran
 */
define('QB_QUICKBOOKS_CONFIG_LAST', 'last');

/**
 * Configuration parameter for the quickbooks_config table, used to keep track of the timestamp for the current iterator
 */
define('QB_QUICKBOOKS_CONFIG_CURR', 'curr');

/**
 * Maximum number of customers returned at a time when doing the import
 */
define('QB_QUICKBOOKS_MAX_RETURNED', 10);

/**
 * Send error notices to this e-mail address
 */
define('QB_QUICKBOOKS_MAILTO', 'mail@codaemonsoftwares.com');

/**
 * Request priorities, customers
 */
define('QB_PRIORITY_CUSTOMER', 1);

/**
 * Request priorities, vendors
 */
define('QB_PRIORITY_VENDOR', 2);

/**
 * Request priorities, items
 */
define('QB_PRIORITY_INVENTORYITEM', 3);

/**
 * Request priorities, purchase orders
 */
define('QB_PRIORITY_PURCHASEORDER', 4);

/**
 * Request priorities, sales orders
 */
define('QB_PRIORITY_SALESORDER', 5);

/**
 * Request priorities, invoices
 */
define('QB_PRIORITY_INVOICE', 6);

/**
 * Request priorities, credit memos
 */
define('QB_PRIORITY_CREDITMEMO', 7);

/**
 * Map QuickBooks actions to handler functions
 */
$map = array(
	QUICKBOOKS_IMPORT_CUSTOMER => array( '_quickbooks_customer_import_request', '_quickbooks_customer_import_response' ),
	QUICKBOOKS_IMPORT_VENDOR => array( '_quickbooks_vendor_import_request', '_quickbooks_vendor_import_response' ), 
	QUICKBOOKS_IMPORT_INVENTORYITEM => array( '_quickbooks_inventoryitem_import_request', '_quickbooks_inventoryitem_import_response' ), 
	QUICKBOOKS_IMPORT_PURCHASEORDER => array( '_quickbooks_purchaseorder_import_request', '_quickbooks_purchaseorder_import_response' ), 
	QUICKBOOKS_IMPORT_SALESORDER => array( '_quickbooks_salesorder_import_request', '_quickbooks_salesorder_import_response' ), 
	QUICKBOOKS_IMPORT_INVOICE => array( '_quickbooks_invoice_import_request', '_quickbooks_invoice_import_response' ), 
	QUICKBOOKS_IMPORT_CREDITMEMO => array( '_quickbooks_creditmemo_import_request', '_quickbooks_creditmemo_import_response' ), 
	QUICKBOOKS_MOD_SALESORDER => array( '_quickbooks_so_mod_request', '_quickbooks_so_mod_response' ),
	QUICKBOOKS_MOD_PURCHASEORDER => array( '_quickbooks_po_mod_request', '_quickbooks_po_mod_response' ),
	QUICKBOOKS_ADD_CREDITMEMO => array( '_quickbooks_creditmemo_add_request', '_quickbooks_creditmemo_add_response' ),
	QUICKBOOKS_ADD_INVOICE => array( '_quickbooks_invoice_add_request', '_quickbooks_invoice_add_response' ),
	QUICKBOOKS_ADD_ITEMRECEIPT => array( '_quickbooks_receipt_add_request', '_quickbooks_receipt_add_response' ),
    );
    
/**
 * Error handlers
 */	
$errmap = array(
	500 => '_quickbooks_error_e500_notfound', 			// Catch errors caused by searching for things not present in QuickBooks
	1 => '_quickbooks_error_e500_notfound', 
	3070 => '_quickbooks_error_stringtoolong',		
	'*' => '_quickbooks_error_catchall', 				// Catch any other errors that might occur
    );
    
/**
 * An array of callback hooks
 */	
$hooks = array(
	QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess', 	// call this whenever a successful login occurs
	);

/**
 * Logging level
 */	
//$log_level = QUICKBOOKS_LOG_NORMAL;
$log_level = QUICKBOOKS_LOG_VERBOSE;
//$log_level = QUICKBOOKS_LOG_DEBUG;				// Use this level until you're sure everything works!!!
//$log_level = QUICKBOOKS_LOG_DEVELOP;

/**
 * What SOAP server you're using 
 */	
//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)

$soap_options = array(			// See http://www.php.net/soap
	);

$handler_options = array(		// See the comments in the QuickBooks/Server/Handlers.php file
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false, 
	);		

$driver_options = array(		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
	);

$callback_options = array(
	);

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
	
	if ($action == QUICKBOOKS_IMPORT_CUSTOMER)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_VENDOR)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_ITEM)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_PURCHASEORDER)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_SALESORDER)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_INVOICE)
	{
		return true;
	}
	else if ($action == QUICKBOOKS_IMPORT_CREDITMEMO)
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

/**
 * Catch and handle a "that string is too long for that field" error (err no. 3070) from QuickBooks
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
function _quickbooks_error_stringtoolong($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
{
	mail('your-email@your-domain.com', 
		'QuickBooks error occured!', 
		'QuickBooks thinks that ' . $action . ': ' . $ID . ' has a value which will not fit in a QuickBooks field...');
}