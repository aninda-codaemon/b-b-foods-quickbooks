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
	
	// Set up the item imports
	if (!_quickbooks_get_last_run($user, QUICKBOOKS_IMPORT_INVENTORYITEM))
	{
		_quickbooks_set_last_run($user, QUICKBOOKS_IMPORT_INVENTORYITEM, $date);
	}
	
	// Make sure the requests get queued up
	$Queue->enqueue(QUICKBOOKS_IMPORT_INVENTORYITEM, 1, QB_PRIORITY_INVENTORYITEM, null, $user);
}

/**
 * Build a request to import inventory items already in QuickBooks into our database
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
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	
	if (!empty($idents['iteratorRemainingCount']))
	{
		// Queue up another request
		
		$Queue = QuickBooks_WebConnector_Queue_Singleton::getInstance();
		$Queue->enqueue(QUICKBOOKS_IMPORT_INVENTORYITEM, null, QB_PRIORITY_INVENTORYITEM, array( 'iteratorID' => $idents['iteratorID'] ), $user);
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
			foreach ($arr as $key => $value)
			{
				$arr[$key] = mysqli_real_escape_string($dblink, $value);
			}
			
			// Store the inventory items in MySQL
			mysqli_query($dblink, "
				REPLACE INTO
				qb_example_iteminventory
				(
					" . implode(", ", array_keys($arr)) . "
				) VALUES (
					'" . implode("', '", array_values($arr)) . "'
				)"); //or die(trigger_error(mysqli_connect_error()));

			// Remove any old data exts
			mysqli_query($dblink, "
				DELETE FROM qb_example_iteminventory_dataext WHERE ListID = '" . mysqli_real_escape_string($dblink, $arr['ListID']) . "'
			");

			foreach ($Item->children() as $Child)
			{
				if ($Child->name() == 'DataExtRet')
				{
					// Loop through custom fields
					
					$DataExt = $Child;
					
					$dataext = array(
						'ListID' => $arr['ListID'],
						'DataExtName' => $DataExt->getChildDataAt('DataExtRet DataExtName'),
						'DataExtType' => $DataExt->getChildDataAt('DataExtRet DataExtType'), 
						'DataExtValue' => $DataExt->getChildDataAt('DataExtRet DataExtValue') 
						);
					
					QuickBooks_Utilities::log(QB_QUICKBOOKS_DSN, ' - custom field "' . $dataext['DataExtName'] . '": ' . $dataext['DataExtValue']);
					foreach ($dataext as $keyde => $valuede)
					{
						$dataext[$keyde] = mysqli_real_escape_string($dblink, $valuede);
					}
					mysqli_query($dblink, "
						REPLACE INTO
						qb_example_iteminventory_dataext
						(
							" . implode(", ", array_keys($dataext)) . "
						) VALUES (
							'" . implode("', '", array_values($dataext)) . "'
						)");
				}
			}
		}
	}
	
	return true;
}