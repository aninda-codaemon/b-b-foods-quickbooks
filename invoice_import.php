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
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_INVOICE))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_INVOICE, $date);
	}

	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_INVOICE, 1, QB_PRIORITY_INVOICE, null, $user);
}

/**
 * Build a request to import invoices already in QuickBooks into our database
 */
function _quickbooks_invoice_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<InvoiceQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<IncludeLineItems>true</IncludeLineItems>
					<OwnerID>0</OwnerID>
				</InvoiceQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_invoice_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_INVOICE, null, QB_PRIORITY_INVOICE, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/InvoiceQueryRs');
		
		foreach ($List->children() as $Invoice)
		{
			$IsPending = ($Invoice->getChildDataAt('InvoiceRet IsPending') === 'true') ? 1 : 0;
			$IsFinanceCharge = ($Invoice->getChildDataAt('InvoiceRet IsFinanceCharge') === 'true') ? 1 : 0;
			$IsPaid = ($Invoice->getChildDataAt('InvoiceRet IsPaid') === 'true') ? 1 : 0;
			$IsToBePrinted = ($Invoice->getChildDataAt('InvoiceRet IsToBePrinted') === 'true') ? 1 : 0;
			$IsToBeEmailed = ($Invoice->getChildDataAt('InvoiceRet IsToBeEmailed') === 'true') ? 1 : 0;
			
			$arr = array(
				'TxnID' => $Invoice->getChildDataAt('InvoiceRet TxnID'),
				'TimeCreated' => $Invoice->getChildDataAt('InvoiceRet TimeCreated'),
				'TimeModified' => $Invoice->getChildDataAt('InvoiceRet TimeModified'),
				'EditSequence' => $Invoice->getChildDataAt('InvoiceRet EditSequence'),
				'TxnNumber' => $Invoice->getChildDataAt('InvoiceRet TxnNumber'),
				'Customer_ListID' => $Invoice->getChildDataAt('InvoiceRet CustomerRef ListID'),
				'Customer_FullName' => $Invoice->getChildDataAt('InvoiceRet CustomerRef FullName'),
				'Class_ListID' => $Invoice->getChildDataAt('InvoiceRet ClassRef ListID'),
				'Class_FullName' => $Invoice->getChildDataAt('InvoiceRet ClassRef FullName'),
				'ARAccount_ListID' => $Invoice->getChildDataAt('InvoiceRet ARAccountRef ListID'),
				'ARAccount_FullName' => $Invoice->getChildDataAt('InvoiceRet ARAccountRef FullName'),
				'Template_ListID' => $Invoice->getChildDataAt('InvoiceRet TemplateRef ListID'),
				'Template_FullName' => $Invoice->getChildDataAt('InvoiceRet TemplateRef FullName'),
				'TxnDate' => $Invoice->getChildDataAt('InvoiceRet TxnDate'),
				'RefNumber' => $Invoice->getChildDataAt('InvoiceRet RefNumber'),
				'ShipAddress_Addr1' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Addr1'),
				'ShipAddress_Addr2' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Addr2'),
				'ShipAddress_Addr3' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Addr3'),
				'ShipAddress_Addr4' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Addr4'),
				'ShipAddress_Addr5' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Addr5'),
				'ShipAddress_City' => $Invoice->getChildDataAt('InvoiceRet ShipAddress City'),
				'ShipAddress_State' => $Invoice->getChildDataAt('InvoiceRet ShipAddress State'),
				'ShipAddress_PostalCode' => $Invoice->getChildDataAt('InvoiceRet ShipAddress PostalCode'),
				'ShipAddress_Country' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Country'),
				'ShipAddress_Note' => $Invoice->getChildDataAt('InvoiceRet ShipAddress Note'),
				'ShipAddressBlock_Addr1' => $Invoice->getChildDataAt('InvoiceRet ShipAddressBlock Addr1'),
				'ShipAddressBlock_Addr2' => $Invoice->getChildDataAt('InvoiceRet ShipAddressBlock Addr2'),
				'ShipAddressBlock_Addr3' => $Invoice->getChildDataAt('InvoiceRet ShipAddressBlock Addr3'),
				'ShipAddressBlock_Addr4' => $Invoice->getChildDataAt('InvoiceRet ShipAddressBlock Addr4'),
				'ShipAddressBlock_Addr5' => $Invoice->getChildDataAt('InvoiceRet ShipAddressBlock Addr5'),
				'BillAddress_Addr1' => $Invoice->getChildDataAt('InvoiceRet BillAddress Addr1'),
				'BillAddress_Addr2' => $Invoice->getChildDataAt('InvoiceRet BillAddress Addr2'),
				'BillAddress_Addr3' => $Invoice->getChildDataAt('InvoiceRet BillAddress Addr3'),
				'BillAddress_Addr4' => $Invoice->getChildDataAt('InvoiceRet BillAddress Addr4'),
				'BillAddress_Addr5' => $Invoice->getChildDataAt('InvoiceRet BillAddress Addr5'),
				'BillAddress_City' => $Invoice->getChildDataAt('InvoiceRet BillAddress City'),
				'BillAddress_State' => $Invoice->getChildDataAt('InvoiceRet BillAddress State'),
				'BillAddress_PostalCode' => $Invoice->getChildDataAt('InvoiceRet BillAddress PostalCode'),
				'BillAddress_Country' => $Invoice->getChildDataAt('InvoiceRet BillAddress Country'),
				'BillAddress_Note' => $Invoice->getChildDataAt('InvoiceRet BillAddress Note'),
				'BillAddressBlock_Addr1' => $Invoice->getChildDataAt('InvoiceRet BillAddressBlock Addr1'),
				'BillAddressBlock_Addr2' => $Invoice->getChildDataAt('InvoiceRet BillAddressBlock Addr2'),
				'BillAddressBlock_Addr3' => $Invoice->getChildDataAt('InvoiceRet BillAddressBlock Addr3'),
				'BillAddressBlock_Addr4' => $Invoice->getChildDataAt('InvoiceRet BillAddressBlock Addr4'),
				'BillAddressBlock_Addr5' => $Invoice->getChildDataAt('InvoiceRet BillAddressBlock Addr5'),
				'IsPending' => $IsPending,
				'IsFinanceCharge' => $IsFinanceCharge,
				'PONumber' => $Invoice->getChildDataAt('InvoiceRet PONumber'),
				'Terms_ListID' => $Invoice->getChildDataAt('InvoiceRet TermsRef ListID'),
				'Terms_FullName' => $Invoice->getChildDataAt('InvoiceRet TermsRef FullName'),
				'DueDate' => $Invoice->getChildDataAt('InvoiceRet DueDate'),
				'SalesRep_ListID' => $Invoice->getChildDataAt('InvoiceRet SalesRepRef ListID'),
				'SalesRep_FullName' => $Invoice->getChildDataAt('InvoiceRet SalesRepRef FullName'),
				'FOB' => $Invoice->getChildDataAt('InvoiceRet FOB'),
				'ShipDate' => $Invoice->getChildDataAt('InvoiceRet ShipDate'),
				'ShipMethod_ListID' => $Invoice->getChildDataAt('InvoiceRet ShipMethodRef ListID'),
				'ShipMethod_FullName' => $Invoice->getChildDataAt('InvoiceRet ShipMethodRef FullName'),
				'Subtotal' => $Invoice->getChildDataAt('InvoiceRet Subtotal'),
				'ItemSalesTax_ListID' => $Invoice->getChildDataAt('InvoiceRet ItemSalesTaxRef ListID'), 
				'ItemSalesTax_FullName' => $Invoice->getChildDataAt('InvoiceRet ItemSalesTaxRef FullName'), 
				'SalesTaxPercentage' => $Invoice->getChildDataAt('InvoiceRet SalesTaxPercentage'),
				'SalesTaxTotal' => $Invoice->getChildDataAt('InvoiceRet SalesTaxTotal'),
				'AppliedAmount' => $Invoice->getChildDataAt('InvoiceRet AppliedAmount'),
				'BalanceRemaining' => $Invoice->getChildDataAt('InvoiceRet BalanceRemaining'),
				'Memo' => $Invoice->getChildDataAt('InvoiceRet Memo'),
				'IsPaid' => $IsPaid,
				'Currency_ListID' => $Invoice->getChildDataAt('InvoiceRet CurrencyRef ListID'),
				'Currency_FullName' => $Invoice->getChildDataAt('InvoiceRet CurrencyRef FullName'),
				'ExchangeRate' => $Invoice->getChildDataAt('InvoiceRet ExchangeRate'),
				'BalanceRemainingInHomeCurrency' => $Invoice->getChildDataAt('InvoiceRet BalanceRemainingInHomeCurrency'),
				'CustomerMsg_ListID' => $Invoice->getChildDataAt('InvoiceRet CustomerMsgRef ListID'),
				'CustomerMsg_FullName' => $Invoice->getChildDataAt('InvoiceRet CustomerMsgRef FullName'),
				'IsToBePrinted' => $IsToBePrinted,
				'IsToBeEmailed' => $IsToBeEmailed,
				'CustomerSalesTaxCode_ListID' => $Invoice->getChildDataAt('InvoiceRet CustomerSalesTaxCodeRef ListID'),
				'CustomerSalesTaxCode_FullName' => $Invoice->getChildDataAt('InvoiceRet CustomerSalesTaxCodeRef FullName'),
				'SuggestedDiscountAmount' => $Invoice->getChildDataAt('InvoiceRet SuggestedDiscountAmount'),
				'SuggestedDiscountDate' => $Invoice->getChildDataAt('InvoiceRet SuggestedDiscountDate'),
				'Other' => $Invoice->getChildDataAt('InvoiceRet Other')
				);
			
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing invoice order #' . $arr['RefNumber'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}

			// Store the invoices in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
				qb_example_invoice
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)");
			
			// Process all child elements of the Invoice Order
			foreach ($Invoice->children() as $Child)
			{
				if ($Child->name() == 'InvoiceLineRet')
				{
					$InvoiceLine = $Child;
					
					$lineitem = array( 
						'Invoice_TxnID' => $arr['TxnID'], 
						'TxnLineID' => $InvoiceLine->getChildDataAt('InvoiceLineRet TxnLineID'), 
						'Item_ListID' => $InvoiceLine->getChildDataAt('InvoiceLineRet ItemRef ListID'), 
						'Item_FullName' => $InvoiceLine->getChildDataAt('InvoiceLineRet ItemRef FullName'), 
						'Descrip' => $InvoiceLine->getChildDataAt('InvoiceLineRet Desc'), 
						'Quantity' => $InvoiceLine->getChildDataAt('InvoiceLineRet Quantity'),
						'UnitOfMeasure' => $InvoiceLine->getChildDataAt('InvoiceLineRet UnitOfMeasure'),
						'OverrideUOMSet_ListID' => $InvoiceLine->getChildDataAt('InvoiceLineRet OverrideUOMSetRef ListID'),
						'OverrideUOMSet_FullName' => $InvoiceLine->getChildDataAt('InvoiceLineRet OverrideUOMSetRef FullName'),
						'Rate' => $InvoiceLine->getChildDataAt('InvoiceLineRet Rate'), 
						'RatePercent' => $InvoiceLine->getChildDataAt('InvoiceLineRet RatePercent'), 
						'InventorySite_ListID' => $InvoiceLine->getChildDataAt('InvoiceLineRet InventorySiteRef ListID'), 
						'InventorySite_FullName' => $InvoiceLine->getChildDataAt('InvoiceLineRet InventorySiteRef FullName'), 
						//'InventorySiteLocation_ListID' => $InvoiceLine->getChildDataAt('InvoiceLineRet InventorySiteLocationRef ListID'), 
						//'InventorySiteLocation_FullName' => $InvoiceLine->getChildDataAt('InvoiceLineRet InventorySiteLocationRef FullName'), 
						'SerialNumber' => $InvoiceLine->getChildDataAt('InvoiceLineRet SerialNumber'), 
						'LotNumber' => $InvoiceLine->getChildDataAt('InvoiceLineRet LotNumber'), 
						'ServiceDate' => $InvoiceLine->getChildDataAt('InvoiceLineRet ServiceDate'), 
						'SalesTaxCode_ListID' => $InvoiceLine->getChildDataAt('InvoiceLineRet SalesTaxCodeRef ListID'), 
						'SalesTaxCode_FullName' => $InvoiceLine->getChildDataAt('InvoiceLineRet SalesTaxCodeRef FullName'), 
						'Other1' => $InvoiceLine->getChildDataAt('InvoiceLineRet Other1'), 
						'Other2' => $InvoiceLine->getChildDataAt('InvoiceLineRet Other2')
						);
					
					QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, ' - line item #' . $lineitem['TxnLineID'] . ': ' . print_r($lineitem, true));
					foreach ($lineitem as $keyli => $valueli)
					{
						$lineitem[$keyli] = mysqli_real_escape_string($dblink, $valueli);
					}

					// Store the invoices in MySQL
					mysqli_query($dblink, "
						REPLACE INTO
						qb_example_invoice_invoiceline
						(
							" . implode(", ", array_keys($lineitem)) . "
						) VALUES (
							'" . implode("', '", array_values($lineitem)) . "'
						)");
				}
			}
		}
	}
	
	return true;
}