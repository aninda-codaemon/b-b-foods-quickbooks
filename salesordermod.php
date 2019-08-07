<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Require the framework
require_once 'QuickBooks.php';

// Require the neccessary variables/methods
require_once 'Includes.php';

/**
 * Login success hook - perform an action when a user logs in via the Web Connector
 *
 * 
 */
if (!QuickBooks_Utilities::initialized(QB_QUICKBOOKS_DSN))
{
	QuickBooks_Utilities::initialize(QB_QUICKBOOKS_DSN);
	
	QuickBooks_Utilities::createUser(QB_QUICKBOOKS_DSN, QUICKBOOKS_USER, QUICKBOOKS_PASS);
	
	$Queue = new QuickBooks_WebConnector_Queue(QB_QUICKBOOKS_DSN);
	$Queue->enqueue(QUICKBOOKS_MOD_SALESORDER);
}

$Server = new QuickBooks_WebConnector_Server(QB_QUICKBOOKS_DSN, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/**
 * Build a request to generate the xml which contains modified sales order - modified quantity only
 * @param string $requestID					You should include this in your qbXML request (it helps with debugging later)
 * @param string $action					The QuickBooks action being performed (CustomerAdd in this case)
 * @param mixed $ID							The unique identifier for the record (maybe a customer ID number in your database or something)
 * @param array $extra						Any extra data you included with the queued item when you queued it up
 * @param string $err						An error message, assign a value to $err if you want to report an error
 * @param integer $last_action_time			A unix timestamp (seconds) indicating when the last action of this type was dequeued (i.e.: for CustomerAdd, the last time a customer was added, for CustomerQuery, the last time a CustomerQuery ran, etc.)
 * @param integer $last_actionident_time	A unix timestamp (seconds) indicating when the combination of this action and ident was dequeued (i.e.: when the last time a CustomerQuery with ident of get-new-customers was dequeued)
 * @param float $version					The max qbXML version your QuickBooks version supports
 * @param string $locale					
 * @return string							A valid qbXML request
 */
function _quickbooks_so_mod_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

	$sql = "SELECT * FROM `qb_salesorder`";	
	$query = mysqli_query($dblink, $sql);
	
	$qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
		$arr_TxnID = array();
		$iteration = 1;
		while ($row = mysqli_fetch_array($query)) {
			$sql_trxn_sol = "SELECT * FROM `qb_salesorder_salesorderline` where `SalesOrder_TxnID` = '".$row['TxnID']."'";	
			$query_trxn_sol = mysqli_query($dblink, $sql_trxn_sol);
			if(mysqli_num_rows($query_trxn_sol) > 0) {
				while ($row_sol = mysqli_fetch_array($query_trxn_sol)) {
					$sql_sol = "SELECT `Quantity` FROM `qb_salesorder_salesorderline` where `TxnLineID` = '".$row_sol['TxnLineID']."'";	
					$query_sol = mysqli_query($dblink, $sql_sol);
					$result_sol = mysqli_fetch_assoc($query_sol);
					
					$sql_recent_sol = "SELECT `Quantity` FROM `qb_recent_salesorder_salesorderline` where `TxnLineID` = '".$row_sol['TxnLineID']."'";	
					$query_recent_sol = mysqli_query($dblink, $sql_recent_sol);
					$result_recent_sol = mysqli_fetch_assoc($query_recent_sol);
	
					if(!empty($result_sol) && !empty($result_recent_sol)){
						$diff = array_diff($result_sol, $result_recent_sol);
						if(!empty($diff)){
							$arr_TxnID[$iteration] = $row['TxnID'];
						}
					}
				}
			}
		$iteration++;
		}
		
		if(!empty($arr_TxnID)){
			foreach($arr_TxnID as $key=>$val){
				$qbxml .= '<SalesOrderModRq>';
				$sql_xml_so = "SELECT TxnID, EditSequence FROM `qb_salesorder` WHERE TxnID = '".$val."'";	
				$query_xml_so = mysqli_query($dblink, $sql_xml_so);
				$result_xml_so = mysqli_fetch_assoc($query_xml_so);
	
				$qbxml .= '<SalesOrderMod>';
				$qbxml .= '<TxnID>'.$result_xml_so['TxnID'].'</TxnID>';
				$qbxml .= '<EditSequence>'.$result_xml_so['EditSequence'].'</EditSequence>';
	
				$sql_xml_sol = "SELECT TxnLineID FROM `qb_salesorder_salesorderline` where `SalesOrder_TxnID` = '".$val."'";	
				$query_xml_sol = mysqli_query($dblink, $sql_xml_sol);
				if(mysqli_num_rows($query_xml_sol) > 0) {
					while ($row_xml_sol = mysqli_fetch_array($query_xml_sol)) {
						$sql_xml_sols = "SELECT `TxnLineID`, `Item_ListID`, `Item_FullName`, `Quantity` FROM `qb_salesorder_salesorderline` where `TxnLineID` = '".$row_xml_sol['TxnLineID']."'";	
						$query_xml_sols = mysqli_query($dblink, $sql_xml_sols);
						$result_xml_sols = mysqli_fetch_assoc($query_xml_sols);
	
						$qbxml .= '<SalesOrderLineMod>';
						$qbxml .= '<TxnLineID>'.$result_xml_sols['TxnLineID'].'</TxnLineID>';
						$qbxml .= '<Quantity>'.$result_xml_sols['Quantity'].'</Quantity>';
						$qbxml .= '</SalesOrderLineMod>';
					}
				}
	
				$qbxml .= '</SalesOrderMod>';
				$qbxml .='</SalesOrderModRq>';
			}
		}
	}
	$qbxml .='</QBXMLMsgsRq></QBXML>';
	
	$sql_trnct_so = "TRUNCATE TABLE `qb_recent_salesorder`";	
	$query_trnct_so = mysqli_query($dblink, $sql_trnct_so);

	$sql_trnct_sol = "TRUNCATE TABLE `qb_recent_salesorder_salesorderline`";
	$query_trnct_sol = mysqli_query($dblink, $sql_trnct_sol);

	/*$qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError"><SalesOrderModRq><SalesOrderMod><TxnID>21F7A-1671086496</TxnID><EditSequence>1671086496</EditSequence><SalesOrderLineMod><TxnLineID>21F7C-1671086496</TxnLineID><Quantity>7.00000</Quantity></SalesOrderLineMod></SalesOrderMod></SalesOrderModRq></QBXMLMsgsRq></QBXML>';*/
	
	return $qbxml;
}

/**
 * Receive a response from QuickBooks 
 * 
 * @param string $requestID					The requestID you passed to QuickBooks previously
 * @param string $action					The action that was performed (CustomerAdd in this case)
 * @param mixed $ID							The unique identifier of the record
 * @param array $extra			
 * @param string $err						An error message, assign a valid to $err if you want to report an error
 * @param integer $last_action_time			A unix timestamp (seconds) indicating when the last action of this type was dequeued (i.e.: for CustomerAdd, the last time a customer was added, for CustomerQuery, the last time a CustomerQuery ran, etc.)
 * @param integer $last_actionident_time	A unix timestamp (seconds) indicating when the combination of this action and ident was dequeued (i.e.: when the last time a CustomerQuery with ident of get-new-customers was dequeued)
 * @param string $xml						The complete qbXML response
 * @param array $idents						An array of identifiers that are contained in the qbXML response
 * @return void
 */
function _quickbooks_so_mod_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
}