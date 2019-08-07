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
	$Queue->enqueue(QUICKBOOKS_ADD_ITEMRECEIPT);
}

$Server = new QuickBooks_WebConnector_Server(QB_QUICKBOOKS_DSN, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/**
 * Build a request to generate the xml which contains modified purchase order - modified quantity only
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
function _quickbooks_receipt_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
    $dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
    
    $sql = "SELECT * FROM `qb_purchaseorder` WHERE `is_fully_received_from_wms` = 'Y' AND `IsFullyReceived` = '0'";	
    $query = mysqli_query($dblink, $sql);
    $qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_array($query)) {
            $sql_trxn_pol = "SELECT * FROM `qb_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '".$row['TxnID']."'";	
			$query_trxn_pol = mysqli_query($dblink, $sql_trxn_pol);
			if(mysqli_num_rows($query_trxn_pol) > 0) {
                $qbxml .= '<ItemReceiptAddRq>';
                $qbxml .= '<ItemReceiptAdd defMacro="TxnID:'.$row['TxnID'].'">';
				$qbxml .= '<VendorRef><FullName>'.htmlspecialchars($row['Vendor_FullName']).'</FullName></VendorRef>';
                $qbxml .= '<RefNumber>'.$row['RefNumber'].'</RefNumber>';
                $qbxml .= '<LinkToTxnID>'.$row['TxnID'].'</LinkToTxnID>';
				while ($row_pol = mysqli_fetch_array($query_trxn_pol)) {
                    $qbxml .= '<ItemLineAdd>';
                    $qbxml .= '<ItemRef><FullName>'.htmlspecialchars($row_pol['Item_FullName']).'</FullName></ItemRef>';
                    if(!empty($row_pol['Descrip'])){
                    $qbxml .= '<Desc>'.htmlspecialchars($row_pol['Descrip']).'</Desc>';
                    }
                    if(!empty($row_pol['Quantity'])){
                    $qbxml .= '<Quantity>'.$row_pol['Quantity'].'</Quantity>';
                    }
                    if(!empty($row_pol['UnitOfMeasure'])){
                    $qbxml .= '<UnitOfMeasure>'.$row_pol['UnitOfMeasure'].'</UnitOfMeasure>';
                    }
                    if(!empty($row_pol['Rate'])){
                    $qbxml .= '<Cost>'.$row_pol['Rate'].'</Cost>';
                    }
                    if(!empty($row_pol['Amount'])){
                    $qbxml .= '<Amount>'.$row_pol['Amount'].'</Amount>';
                    }
                    if(!empty($row_pol['Customer_FullName'])){
                        $qbxml .= '<CustomerRef><FullName>'.htmlspecialchars($row_pol['Customer_FullName']).'</FullName></CustomerRef>';
                    } 
                    if(!empty($row_pol['Class_FullName'])){
                        $qbxml .= '<ClassRef><FullName>'.htmlspecialchars($row_pol['Class_FullName']).'</FullName></ClassRef>';
                    } 
                    $qbxml .= '</ItemLineAdd>';
                }
                $qbxml .= '</ItemReceiptAdd>';
                $qbxml .= '</ItemReceiptAddRq>';
            }
        }
    }
    $qbxml .='</QBXMLMsgsRq></QBXML>';
    
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
function _quickbooks_receipt_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
    return $qbxml;
}