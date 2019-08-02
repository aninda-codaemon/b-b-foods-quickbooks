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
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_PURCHASEORDER))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_PURCHASEORDER, $date);
	}

	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_PURCHASEORDER, 1, QB_PRIORITY_PURCHASEORDER, null, $user);
}

/**
 * Build a request to import customers already in QuickBooks into our application
 */
function _quickbooks_purchaseorder_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<PurchaseOrderQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<IncludeLineItems>true</IncludeLineItems>
					<OwnerID>0</OwnerID>
				</PurchaseOrderQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_purchaseorder_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_PURCHASEORDER, null, QB_PRIORITY_PURCHASEORDER, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/PurchaseOrderQueryRs');
		
		foreach ($List->children() as $PurchaseOrder)
		{
			$IsManuallyClosed = ($PurchaseOrder->getChildDataAt('PurchaseOrderRet IsManuallyClosed') === 'true') ? 1 : 0;
			$IsFullyReceived = ($PurchaseOrder->getChildDataAt('PurchaseOrderRet IsFullyReceived') === 'true') ? 1 : 0;
			$IsToBePrinted = ($PurchaseOrder->getChildDataAt('PurchaseOrderRet IsToBePrinted') === 'true') ? 1 : 0;
			$IsToBeEmailed = ($PurchaseOrder->getChildDataAt('PurchaseOrderRet IsToBeEmailed') === 'true') ? 1 : 0;
			$arr = array(
				'TxnID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TxnID'),
				'TimeCreated' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TimeCreated'),
				'TimeModified' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TimeModified'),
				'EditSequence' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet EditSequence'),
				'TxnNumber' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TxnNumber'),
				'Vendor_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorRef ListID'),
				'Vendor_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorRef FullName'),
				'Class_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ClassRef ListID'),
				'Class_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ClassRef FullName'),
				'ShipToEntity_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipToEntityRef ListID'),
				'ShipToEntity_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipToEntityRef FullName'),
				'Template_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TemplateRef ListID'),
				'Template_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TemplateRef FullName'),
				'TxnDate' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TxnDate'),
				'RefNumber' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet RefNumber'),
				'VendorAddress_Addr1' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Addr1'),
				'VendorAddress_Addr2' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Addr2'),
				'VendorAddress_Addr3' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Addr3'),
				'VendorAddress_Addr4' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Addr4'),
				'VendorAddress_Addr5' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Addr5'),
				'VendorAddress_City' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress City'),
				'VendorAddress_State' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress State'),
				'VendorAddress_PostalCode' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress PostalCode'),
				'VendorAddress_Country' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Country'),
				'VendorAddress_Note' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddress Note'),
				'VendorAddressBlock_Addr1' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddressBlock Addr1'),
				'VendorAddressBlock_Addr2' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddressBlock Addr2'),
				'VendorAddressBlock_Addr3' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddressBlock Addr3'),
				'VendorAddressBlock_Addr4' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddressBlock Addr4'),
				'VendorAddressBlock_Addr5' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorAddressBlock Addr5'),
				'ShipAddress_Addr1' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Addr1'),
				'ShipAddress_Addr2' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Addr2'),
				'ShipAddress_Addr3' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Addr3'),
				'ShipAddress_Addr4' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Addr4'),
				'ShipAddress_Addr5' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Addr5'),
				'ShipAddress_City' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress City'),
				'ShipAddress_State' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress State'),
				'ShipAddress_PostalCode' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress PostalCode'),
				'ShipAddress_Country' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Country'),
				'ShipAddress_Note' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddress Note'),
				'ShipAddressBlock_Addr1' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddressBlock Addr1'),
				'ShipAddressBlock_Addr2' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddressBlock Addr2'),
				'ShipAddressBlock_Addr3' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddressBlock Addr3'),
				'ShipAddressBlock_Addr4' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddressBlock Addr4'),
				'ShipAddressBlock_Addr5' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipAddressBlock Addr5'),
				'Terms_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TermsRef ListID'),
				'Terms_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TermsRef FullName'),
				'DueDate' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet DueDate'),
				'ExpectedDate' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ExpectedDate'),
				'ShipMethod_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipMethodRef ListID'),
				'ShipMethod_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ShipMethodRef FullName'),
				'FOB' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet FOB'),
				'TotalAmount' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TotalAmount'),
				'Currency_ListID' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet CurrencyRef ListID'),
				'Currency_FullName' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet CurrencyRef FullName'),
				'ExchangeRate' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet ExchangeRate'),
				'TotalAmountInHomeCurrency' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet TotalAmountInHomeCurrency'),
				'IsManuallyClosed' => $IsManuallyClosed,
				'IsFullyReceived' => $IsFullyReceived,
				'Memo' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet Memo'),
				'VendorMsg' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet VendorMsg'),
				'IsToBePrinted' => $IsToBePrinted,
				'IsToBeEmailed' => $IsToBeEmailed,
				'Other1' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet Other1'),
				'Other2' => $PurchaseOrder->getChildDataAt('PurchaseOrderRet Other2')
				);
			
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing purchase order #' . $arr['RefNumber'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			// Store the purchase orders in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
				qb_example_purchaseorder
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)"); //or die(trigger_error(mysqli_connect_error()));

			// Remove any old line items
			mysqli_query($dblink, "
				DELETE FROM qb_example_purchaseorder_purchaseorderline WHERE PurchaseOrder_TxnID = '" . mysqli_real_escape_string($dblink, $arr['TxnID']) . "'
			"); //or die(trigger_error(mysql_error()));
			
			// Process all child elements of the Purchase Order
			foreach ($PurchaseOrder->children() as $Child)
			{
				if ($Child->name() == 'PurchaseOrderLineRet')
				{
					// Loop through line items
					
					$PurchaseOrderLine = $Child;
					
					$IsManuallyClosed = ($PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet IsManuallyClosed') == true) ? 1 : 0;
					$lineitem = array( 
						'PurchaseOrder_TxnID' => $arr['TxnID'], 
						'TxnLineID' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet TxnLineID'), 
						'Item_ListID' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ItemRef ListID'), 
						'Item_FullName' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ItemRef FullName'), 
						'ManufacturerPartNumber' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ManufacturerPartNumber'), 
						'Descrip' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Desc'), 
						'Quantity' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Quantity'),
						'UnitOfMeasure' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet UnitOfMeasure'),
						'OverrideUOMSet_ListID' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet OverrideUOMSetRef ListID'),
						'OverrideUOMSet_FullName' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet OverrideUOMSetRef FullName'),
						'Rate' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Rate'), 
						'Class_ListID' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ClassRef ListID'), 
						'Class_FullName' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ClassRef FullName'), 
						'Amount' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Amount'), 
						'Customer_ListID' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet CustomerRef ListID'), 
						'Customer_FullName' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet CustomerRef FullName'), 
						'ServiceDate' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ServiceDate'), 
						'ReceivedQuantity' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet ReceivedQuantity'), 
						'IsManuallyClosed' => $IsManuallyClosed, 
						'Other1' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Rate'), 
						'Other2' => $PurchaseOrderLine->getChildDataAt('PurchaseOrderLineRet Rate')
						);
					
					QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, ' - line item #' . $lineitem['TxnLineID'] . ': ' . print_r($lineitem, true));
					foreach ($lineitem as $keyli => $valueli)
					{
						$lineitem[$keyli] = mysqli_real_escape_string($dblink, $valueli);
					}
					// Store the invoices in MySQL
					mysqli_query($dblink, "
						REPLACE INTO
						qb_example_purchaseorder_purchaseorderline
						(
							" . implode(", ", array_keys($lineitem)) . "
						) VALUES (
							'" . implode("', '", array_values($lineitem)) . "'
						)"); //or die(trigger_error(mysqli_connect_error()));
				}
			}
		}
	}
	
	return true;
}