<?php
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
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_SALESORDER))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_SALESORDER, $date);
	}

	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_SALESORDER, 1, QB_PRIORITY_SALESORDER, null, $user);
}

/**
 * Build a request to import customers already in QuickBooks into our application
 */
function _quickbooks_salesorder_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<SalesOrderQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<ModifiedDateRangeFilter>
						<FromModifiedDate>' . $last . '</FromModifiedDate>
					</ModifiedDateRangeFilter>
					<IncludeLineItems>true</IncludeLineItems>
					<OwnerID>0</OwnerID>
				</SalesOrderQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_salesorder_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request

		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_SALESORDER, null, QB_PRIORITY_SALESORDER, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/SalesOrderQueryRs');
		
		foreach ($List->children() as $SalesOrder)
		{
			$IsManuallyClosed = ($SalesOrder->getChildDataAt('SalesOrderRet IsManuallyClosed') === 'true') ? 1 : 0;
			$IsFullyInvoiced = ($SalesOrder->getChildDataAt('SalesOrderRet IsFullyInvoiced') === 'true') ? 1 : 0;
			$IsToBePrinted = ($SalesOrder->getChildDataAt('SalesOrderRet IsToBePrinted') === 'true') ? 1 : 0;
			$IsToBeEmailed = ($SalesOrder->getChildDataAt('SalesOrderRet IsToBeEmailed') === 'true') ? 1 : 0;
			
			$arr = array(
				'TxnID' => $SalesOrder->getChildDataAt('SalesOrderRet TxnID'),
				'TimeCreated' => $SalesOrder->getChildDataAt('SalesOrderRet TimeCreated'),
				'TimeModified' => $SalesOrder->getChildDataAt('SalesOrderRet TimeModified'),
				'EditSequence' => $SalesOrder->getChildDataAt('SalesOrderRet EditSequence'),
				'TxnNumber' => $SalesOrder->getChildDataAt('SalesOrderRet TxnNumber'),
				'Customer_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerRef ListID'),
				'Customer_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerRef FullName'),
				'Class_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet ClassRef ListID'),
				'Class_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet ClassRef FullName'),
				'Template_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet TemplateRef ListID'),
				'Template_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet TemplateRef FullName'),
				'TxnDate' => $SalesOrder->getChildDataAt('SalesOrderRet TxnDate'),
				'RefNumber' => $SalesOrder->getChildDataAt('SalesOrderRet RefNumber'),
				'BillAddress_Addr1' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Addr1'),
				'BillAddress_Addr2' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Addr2'),
				'BillAddress_Addr3' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Addr3'),
				'BillAddress_Addr4' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Addr4'),
				'BillAddress_Addr5' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Addr5'),
				'BillAddress_City' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress City'),
				'BillAddress_State' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress State'),
				'BillAddress_PostalCode' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress PostalCode'),
				'BillAddress_Country' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Country'),
				'BillAddress_Note' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddress Note'),
				'BillAddressBlock_Addr1' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddressBlock Addr1'),
				'BillAddressBlock_Addr2' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddressBlock Addr2'),
				'BillAddressBlock_Addr3' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddressBlock Addr3'),
				'BillAddressBlock_Addr4' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddressBlock Addr4'),
				'BillAddressBlock_Addr5' => $SalesOrder->getChildDataAt('SalesOrderRet BillAddressBlock Addr5'),
				'ShipAddress_Addr1' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Addr1'),
				'ShipAddress_Addr2' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Addr2'),
				'ShipAddress_Addr3' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Addr3'),
				'ShipAddress_Addr4' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Addr4'),
				'ShipAddress_Addr5' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Addr5'),
				'ShipAddress_City' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress City'),
				'ShipAddress_State' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress State'),
				'ShipAddress_PostalCode' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress PostalCode'),
				'ShipAddress_Country' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Country'),
				'ShipAddress_Note' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddress Note'),
				'ShipAddressBlock_Addr1' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddressBlock Addr1'),
				'ShipAddressBlock_Addr2' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddressBlock Addr2'),
				'ShipAddressBlock_Addr3' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddressBlock Addr3'),
				'ShipAddressBlock_Addr4' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddressBlock Addr4'),
				'ShipAddressBlock_Addr5' => $SalesOrder->getChildDataAt('SalesOrderRet ShipAddressBlock Addr5'),
				'PONumber' => $SalesOrder->getChildDataAt('SalesOrderRet PONumber'),
				'Terms_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet TermsRef ListID'),
				'Terms_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet TermsRef FullName'),
				'DueDate' => $SalesOrder->getChildDataAt('SalesOrderRet DueDate'),
				'SalesRep_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet SalesRepRef ListID'),
				'SalesRep_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet SalesRepRef FullName'),
				'FOB' => $SalesOrder->getChildDataAt('SalesOrderRet FOB'),
				'ShipDate' => $SalesOrder->getChildDataAt('SalesOrderRet ShipDate'),
				'ShipMethod_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet ShipMethodRef ListID'),
				'ShipMethod_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet ShipMethodRef FullName'),
				'Subtotal' => $SalesOrder->getChildDataAt('SalesOrderRet Subtotal'),
				'ItemSalesTax_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet ItemSalesTaxRef ListID'),
				'ItemSalesTax_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet ItemSalesTaxRef FullName'),
				'SalesTaxPercentage' => $SalesOrder->getChildDataAt('SalesOrderRet SalesTaxPercentage'),
				'SalesTaxTotal' => $SalesOrder->getChildDataAt('SalesOrderRet SalesTaxTotal'),
				'TotalAmount' => $SalesOrder->getChildDataAt('SalesOrderRet TotalAmount'),
				'IsManuallyClosed' => $IsManuallyClosed,
				'IsFullyInvoiced' => $IsFullyInvoiced,
				'Memo' => $SalesOrder->getChildDataAt('SalesOrderRet Memo'),
				'CustomerMsg_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerMsgRef ListID'),
				'CustomerMsg_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerMsgRef FullName'),
				'IsToBePrinted' => $IsToBePrinted,
				'IsToBeEmailed' => $IsToBeEmailed,
				'CustomerSalesTaxCode_ListID' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerSalesTaxCodeRef ListID'),
				'CustomerSalesTaxCode_FullName' => $SalesOrder->getChildDataAt('SalesOrderRet CustomerSalesTaxCodeRef FullName'),
				'Other' => $SalesOrder->getChildDataAt('SalesOrderRet Other')
				);
			
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing sales order #' . $arr['RefNumber'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			
			// Store the sales orders in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
					qb_recent_salesorder
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)"); //or die(trigger_error(mysql_error()));
			
			// Remove any old so line items
			mysqli_query($dblink, "
				DELETE FROM qb_recent_salesorder_salesorderline WHERE SalesOrder_TxnID = '" . mysqli_real_escape_string($dblink, $arr['TxnID']) . "'
			"); //or die(trigger_error(mysql_error()));
			
			// Process the so line items
			foreach ($SalesOrder->children() as $Child)
			{
				if ($Child->name() == 'SalesOrderLineRet')
				{
					$SalesOrderLine = $Child;
					
					$IsManuallyClosed = ($SalesOrderLine->getChildDataAt('SalesOrderLineRet IsManuallyClosed') === 'true') ? 1 : 0;
					$lineitem = array( 
						'SalesOrder_TxnID' => $arr['TxnID'], 
						'TxnLineID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet TxnLineID'), 
						'Item_ListID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet ItemRef ListID'), 
						'Item_FullName' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet ItemRef FullName'), 
						'Descrip' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Desc'), 
						'Quantity' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Quantity'),
						'UnitOfMeasure' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet UnitOfMeasure'),
						'OverrideUOMSet_ListID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet OverrideUOMSetRef ListID'),
						'OverrideUOMSet_FullName' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet OverrideUOMSetRef FullName'),
						'Rate' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Rate'), 
						'RatePercent' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet RatePercent'), 
						'Class_ListID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet ClassRef ListID'), 
						'Class_FullName' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet ClassRef FullName'), 
						'Amount' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Amount'), 
						'InventorySite_ListID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet InventorySiteRef ListID'), 
						'InventorySite_FullName' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet InventorySiteRef FullName'), 
						'SerialNumber' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet SerialNumber'), 
						'LotNumber' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet LotNumber'), 
						'SalesTaxCode_ListID' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet SalesTaxCodeRef ListID'), 
						'SalesTaxCode_FullName' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet SalesTaxCodeRef FullName'), 
						'Invoiced' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Invoiced'), 
						'IsManuallyClosed' => $IsManuallyClosed,
						'Other1' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Other1'), 
						'Other2' => $SalesOrderLine->getChildDataAt('SalesOrderLineRet Other2')
						);
					
					foreach ($lineitem as $keyli => $valueli)
					{
						$lineitem[$keyli] = mysqli_real_escape_string($dblink, $valueli);
					}
					
					// Store the so line items in MySQL
					mysqli_query($dblink, "
						INSERT INTO
						qb_recent_salesorder_salesorderline
						(
							" . implode(", ", array_keys($lineitem)) . "
						) VALUES (
							'" . implode("', '", array_values($lineitem)) . "'
						) "); //or die(trigger_error(mysql_error()));
				}
			}
		}
	}
	
	return true;
}