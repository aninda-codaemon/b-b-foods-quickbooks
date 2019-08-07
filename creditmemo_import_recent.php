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
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_CREDITMEMO))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_CREDITMEMO, $date);
	}

	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_CREDITMEMO, 1, QB_PRIORITY_CREDITMEMO, null, $user);
}

/**
 * Build a request to import credit memos already in QuickBooks into our database
 */
function _quickbooks_creditmemo_import_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
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
				<CreditMemoQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestID . '">
					<MaxReturned>' . QB_QUICKBOOKS_MAX_RETURNED . '</MaxReturned>
					<IncludeLineItems>true</IncludeLineItems>
					<OwnerID>0</OwnerID>
				</CreditMemoQueryRq>	
			</QBXMLMsgsRq>
		</QBXML>';
		
	return $xml;
}

/** 
 * Handle a response from QuickBooks 
 */
function _quickbooks_creditmemo_import_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_CREDITMEMO, null, QB_PRIORITY_CREDITMEMO, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
		$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CreditMemoQueryRs');
		
		foreach ($List->children() as $CreditMemo)
		{
			$IsPending = ($CreditMemo->getChildDataAt('CreditMemoRet IsPending') === 'true') ? 1 : 0;
			$IsToBePrinted = ($CreditMemo->getChildDataAt('CreditMemoRet IsToBePrinted') === 'true') ? 1 : 0;
			$IsToBeEmailed = ($CreditMemo->getChildDataAt('CreditMemoRet IsToBeEmailed') === 'true') ? 1 : 0;
			$IsTaxIncluded = ($CreditMemo->getChildDataAt('CreditMemoRet IsTaxIncluded') === 'true') ? 1 : 0;
			
			$arr = array(
				'TxnID' => $CreditMemo->getChildDataAt('CreditMemoRet TxnID'),
				'TimeCreated' => $CreditMemo->getChildDataAt('CreditMemoRet TimeCreated'),
				'TimeModified' => $CreditMemo->getChildDataAt('CreditMemoRet TimeModified'),
				'EditSequence' => $CreditMemo->getChildDataAt('CreditMemoRet EditSequence'),
				'TxnNumber' => $CreditMemo->getChildDataAt('CreditMemoRet TxnNumber'),
				'Customer_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerRef ListID'),
				'Customer_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerRef FullName'),
				'Class_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet ClassRef ListID'),
				'Class_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet ClassRef FullName'),
				'ARAccount_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet ARAccountRef ListID'),
				'ARAccount_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet ARAccountRef FullName'),
				'Template_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet TemplateRef ListID'),
				'Template_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet TemplateRef FullName'),
				'TxnDate' => $CreditMemo->getChildDataAt('CreditMemoRet TxnDate'),
				'RefNumber' => $CreditMemo->getChildDataAt('CreditMemoRet RefNumber'),
				'ShipAddress_Addr1' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Addr1'),
				'ShipAddress_Addr2' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Addr2'),
				'ShipAddress_Addr3' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Addr3'),
				'ShipAddress_Addr4' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Addr4'),
				'ShipAddress_Addr5' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Addr5'),
				'ShipAddress_City' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress City'),
				'ShipAddress_State' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress State'),
				'ShipAddress_PostalCode' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress PostalCode'),
				'ShipAddress_Country' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Country'),
				'ShipAddress_Note' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddress Note'),
				'ShipAddressBlock_Addr1' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddressBlock Addr1'),
				'ShipAddressBlock_Addr2' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddressBlock Addr2'),
				'ShipAddressBlock_Addr3' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddressBlock Addr3'),
				'ShipAddressBlock_Addr4' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddressBlock Addr4'),
				'ShipAddressBlock_Addr5' => $CreditMemo->getChildDataAt('CreditMemoRet ShipAddressBlock Addr5'),
				'BillAddress_Addr1' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Addr1'),
				'BillAddress_Addr2' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Addr2'),
				'BillAddress_Addr3' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Addr3'),
				'BillAddress_Addr4' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Addr4'),
				'BillAddress_Addr5' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Addr5'),
				'BillAddress_City' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress City'),
				'BillAddress_State' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress State'),
				'BillAddress_PostalCode' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress PostalCode'),
				'BillAddress_Country' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Country'),
				'BillAddress_Note' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddress Note'),
				'BillAddressBlock_Addr1' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddressBlock Addr1'),
				'BillAddressBlock_Addr2' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddressBlock Addr2'),
				'BillAddressBlock_Addr3' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddressBlock Addr3'),
				'BillAddressBlock_Addr4' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddressBlock Addr4'),
				'BillAddressBlock_Addr5' => $CreditMemo->getChildDataAt('CreditMemoRet BillAddressBlock Addr5'),
				'IsPending' => $IsPending,
				'PONumber' => $CreditMemo->getChildDataAt('CreditMemoRet PONumber'),
				'Terms_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet TermsRef ListID'),
				'Terms_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet TermsRef FullName'),
				'DueDate' => $CreditMemo->getChildDataAt('CreditMemoRet DueDate'),
				'SalesRep_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet SalesRepRef ListID'),
				'SalesRep_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet SalesRepRef FullName'),
				'FOB' => $CreditMemo->getChildDataAt('CreditMemoRet FOB'),
				'ShipDate' => $CreditMemo->getChildDataAt('CreditMemoRet ShipDate'),
				'ShipMethod_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet ShipMethodRef ListID'),
				'ShipMethod_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet ShipMethodRef FullName'),
				'Subtotal' => $CreditMemo->getChildDataAt('CreditMemoRet Subtotal'),
				'ItemSalesTax_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet ItemSalesTaxRef ListID'), 
				'ItemSalesTax_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet ItemSalesTaxRef FullName'), 
				'SalesTaxPercentage' => $CreditMemo->getChildDataAt('CreditMemoRet SalesTaxPercentage'),
				'SalesTaxTotal' => $CreditMemo->getChildDataAt('CreditMemoRet SalesTaxTotal'),
				'TotalAmount' => $CreditMemo->getChildDataAt('CreditMemoRet TotalAmount'),
				'CreditRemaining' => $CreditMemo->getChildDataAt('CreditMemoRet CreditRemaining'),
				'Memo' => $CreditMemo->getChildDataAt('CreditMemoRet Memo'),
				'CustomerMsg_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerMsgRef ListID'),
				'CustomerMsg_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerMsgRef FullName'),
				'IsToBePrinted' => $IsToBePrinted,
				'IsToBeEmailed' => $IsToBeEmailed,
				'IsTaxIncluded' => $IsTaxIncluded,
				'CustomerSalesTaxCode_ListID' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerSalesTaxCodeRef ListID'),
				'CustomerSalesTaxCode_FullName' => $CreditMemo->getChildDataAt('CreditMemoRet CustomerSalesTaxCodeRef FullName'),
				'Other' => $CreditMemo->getChildDataAt('CreditMemoRet Other'),
				'ExternalGUID' => $CreditMemo->getChildDataAt('CreditMemoRet ExternalGUID')
				);
			
			QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, 'Importing creditmemo order #' . $arr['RefNumber'] . ': ' . print_r($arr, true));
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}

			// Store the credit memos in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
				qb_recent_creditmemo
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)");
			mysqli_query($dblink, "
				DELETE FROM qb_recent_creditmemo_creditmemoline WHERE CreditMemo_TxnID = '".$arr['TxnID']."'");

			// Process all child elements of the credit memos
			foreach ($CreditMemo->children() as $Child)
			{
				if ($Child->name() == 'CreditMemoLineRet')
				{
					$CreditMemoLine = $Child;
					
					$lineitem = array( 
						'CreditMemo_TxnID' => $arr['TxnID'], 
						'TxnLineID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet TxnLineID'), 
						'Item_ListID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet ItemRef ListID'), 
						'Item_FullName' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet ItemRef FullName'), 
						'Descrip' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Desc'), 
						'Quantity' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Quantity'),
						'UnitOfMeasure' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet UnitOfMeasure'),
						'OverrideUOMSet_ListID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet OverrideUOMSetRef ListID'),
						'OverrideUOMSet_FullName' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet OverrideUOMSetRef FullName'),
						'Rate' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Rate'), 
						'RatePercent' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet RatePercent'), 
						'Class_ListID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet ClassRef ListID'), 
						'Class_FullName' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet ClassRef FullName'), 
						'Amount' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Amount'), 
						'ServiceDate' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet ServiceDate'), 
						'SalesTaxCode_ListID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet SalesTaxCodeRef ListID'), 
						'SalesTaxCode_FullName' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet SalesTaxCodeRef FullName'), 
						'Other1' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Other1'), 
						'Other2' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet Other2'),

						'CreditCardTxnInputInfo_CreditCardNumber' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo CreditCardNumber'),
						'CreditCardTxnInputInfo_ExpirationMonth' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo ExpirationMonth'),
						'CreditCardTxnInputInfo_NameOnCard' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo NameOnCard'),
						'CreditCardTxnInputInfo_CreditCardAddress' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo CreditCardAddress'),
						'CreditCardTxnInputInfo_CreditCardPostalCode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo CreditCardPostalCode'),
						'CreditCardTxnInputInfo_CommercialCardCode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo CommercialCardCode'),
						'CreditCardTxnInputInfo_TransactionMode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo TransactionMode'),
						'CreditCardTxnInputInfo_CreditCardTxnType' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnInputInfo CreditCardTxnType'),

						'CreditCardTxnResultInfo_ResultCode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo ResultCode'),
						'CreditCardTxnResultInfo_ResultMessage' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo ResultMessage'),
						'CreditCardTxnResultInfo_CreditCardTransID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo CreditCardTransID'),
						'CreditCardTxnResultInfo_MerchantAccountNumber' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo MerchantAccountNumber'),
						'CreditCardTxnResultInfo_AuthorizationCode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo AuthorizationCode'),
						'CreditCardTxnResultInfo_AVSStreet' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo AVSStreet'),
						'CreditCardTxnResultInfo_AVSZip' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo AVSZip'),
						'CreditCardTxnResultInfo_CardSecurityCodeMatch' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo CardSecurityCodeMatch'),
						'CreditCardTxnResultInfo_ReconBatchID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo ReconBatchID'),
						'CreditCardTxnResultInfo_PaymentGroupingCode' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo PaymentGroupingCode'),
						'CreditCardTxnResultInfo_PaymentStatus' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo PaymentStatus'),
						'CreditCardTxnResultInfo_TxnAuthorizationTime' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo TxnAuthorizationTime'),
						'CreditCardTxnResultInfo_TxnAuthorizationStamp' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo TxnAuthorizationStamp'),
						'CreditCardTxnResultInfo_ClientTransID' => $CreditMemoLine->getChildDataAt('CreditMemoLineRet CreditCardTxnInfo CreditCardTxnResultInfo ClientTransID'),
						);
					
					QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, ' - line item #' . $lineitem['TxnLineID'] . ': ' . print_r($lineitem, true));
					foreach ($lineitem as $keyli => $valueli)
					{
						$lineitem[$keyli] = mysqli_real_escape_string($dblink, $valueli);
					}

					// Store the credit memos in MySQL
					mysqli_query($dblink, "
						INSERT INTO
						qb_recent_creditmemo_creditmemoline
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