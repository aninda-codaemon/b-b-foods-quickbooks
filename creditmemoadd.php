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
	$Queue->enqueue(QUICKBOOKS_ADD_CREDITMEMO);
}

$Server = new QuickBooks_WebConnector_Server(QB_QUICKBOOKS_DSN, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/**
 * Build a request to generate the xml which contains newly generated credit memod - RMA
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
function _quickbooks_creditmemo_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

    $sql = "SELECT TxnID FROM `qb_creditmemo` WHERE RefNumber NOT IN (SELECT RefNumber FROM `qb_recent_creditmemo`)";	
    $query = mysqli_query($dblink,$sql);

    $qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
        $qbxml_ln = ''; $quantity_check = $item_line = 0;
        while ($row = mysqli_fetch_assoc($query))
        {
            $sql_creditmemo = "SELECT * FROM `qb_creditmemo` WHERE TxnID = '".$row['TxnID']."'";
            $query_creditmemo = mysqli_query($dblink, $sql_creditmemo);
            if(mysqli_num_rows($query_creditmemo) > 0) {	
                $row_creditmemo = mysqli_fetch_assoc($query_creditmemo);

                $check_customer_sql = "SELECT FullName FROM `qb_customer` WHERE ListID = '".$row_creditmemo['Customer_ListID']."'";
                $check_customer_query = mysqli_query($dblink, $check_customer_sql);

                $check_creditmemo_exists_sql = "SELECT TxnID FROM `qb_recent_creditmemo` WHERE RefNumber = '".$row_creditmemo['RefNumber']."'";
                $check_creditmemo_exists_query = mysqli_query($dblink, $check_creditmemo_exists_sql);

                $sql_creditmemoline = "SELECT * FROM `qb_creditmemo_creditmemoline` WHERE CreditMemo_TxnID = '".$row['TxnID']."'";
                $query_creditmemoline = mysqli_query($dblink, $sql_creditmemoline);

                while ($row_creditmemoline = mysqli_fetch_assoc($query_creditmemoline)){
                    $item_quantity_check_sql = "SELECT QuantityOnHand FROM qb_iteminventory WHERE ListID = '".$row_creditmemoline['Item_ListID']."'";
                    $item_quantity_check_query = mysqli_query($dblink, $item_quantity_check_sql);
                    if(mysqli_num_rows($item_quantity_check_query) > 0){
                        $item_quantity_check_row = mysqli_fetch_assoc($item_quantity_check_query);
                        if($item_quantity_check_row['QuantityOnHand'] >= $row_creditmemoline['Quantity']){
                            $quantity_check++;  
                        }
                    }

                    $qbxml_ln .= '<CreditMemoLineAdd>';
                    
                    $qbxml_ln .= '<ItemRef>';
                    //$qbxml_ln .= '<ListID>'.$row_creditmemoline['Item_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_creditmemoline['Item_FullName']).'</FullName>';
                    $qbxml_ln .= '</ItemRef>';

                    $qbxml_ln .= '<Desc>'.htmlspecialchars($row_creditmemoline['Descrip']).'</Desc>';

                    $qbxml_ln .= '<Quantity>'.htmlspecialchars($row_creditmemoline['Quantity']).'</Quantity>';

                    $qbxml_ln .= '<UnitOfMeasure>'.htmlspecialchars($row_creditmemoline['UnitOfMeasure']).'</UnitOfMeasure>';

                    if(!empty($row_creditmemoline['Rate']) && intval($row_creditmemoline['Rate'] != 0)){
                    $qbxml_ln .= '<Rate>'.htmlspecialchars($row_creditmemoline['Rate']).'</Rate>';
                    } elseif(!empty($row_creditmemoline['RatePercent']) && intval($row_creditmemoline['RatePercent'] != 0)){
                    $qbxml_ln .= '<RatePercent>'.htmlspecialchars($row_creditmemoline['RatePercent']).'</RatePercent>';
                    }

                    if(!empty($row_creditmemoline['Class_FullName'])){
                    $qbxml_ln .= '<ClassRef>';
                    //$qbxml_ln .= '<ListID>'.$row_creditmemoline['Class_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_creditmemoline['Class_FullName']).'</FullName>';
                    $qbxml_ln .= '</ClassRef>';
                    }

                    if(!empty($row_creditmemoline['Amount']) && intval($row_creditmemoline['Amount'] != 0)){
                    $qbxml_ln .= '<Amount>'.htmlspecialchars($row_creditmemoline['Amount']).'</Amount>';
                    }

                    if(!empty($row_creditmemoline['InventorySite_FullName'])){
                    $qbxml_ln .= '<InventorySiteRef>';
                    //$qbxml .= '<ListID>'.$row_creditmemoline['InventorySite_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_creditmemoline['InventorySite_FullName']).'</FullName>';
                    $qbxml_ln .= '</InventorySiteRef>';
                    }

                    if(!empty($row_creditmemoline['InventorySiteLocation_FullName'])){
                    $qbxml_ln .= '<InventorySiteLocationRef>';
                    //$qbxml .= '<ListID>'.$row_creditmemoline['InventorySite_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_creditmemoline['InventorySiteLocation_FullName']).'</FullName>';
                    $qbxml_ln .= '</InventorySiteLocationRef>';
                    }

                    if(!empty($row_creditmemoline['SerialNumber'])){
                    $qbxml_ln .= '<SerialNumber>'.htmlspecialchars($row_creditmemoline['SerialNumber']).'</SerialNumber>';
                    }
                    
                    if(!empty($row_creditmemoline['LotNumber'])){
                    $qbxml_ln .= '<LotNumber>'.htmlspecialchars($row_creditmemoline['LotNumber']).'</LotNumber>';
                    }

                    if(!empty($row_creditmemoline['ServiceDate']) && $row_creditmemoline['ServiceDate'] != '0000-00-00'){
                    $qbxml_ln .= '<ServiceDate>'.htmlspecialchars($row_creditmemoline['ServiceDate']).'</ServiceDate>';
                    }

                    if(!empty($row_creditmemoline['SalesTaxCode_FullName'])){
                    $qbxml_ln .= '<SalesTaxCodeRef>';
                    //$qbxml_ln .= '<ListID>'.$row_creditmemoline['SalesTaxCode_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_creditmemoline['SalesTaxCode_FullName']).'</FullName>';
                    $qbxml_ln .= '</SalesTaxCodeRef>';
                    }

                    if(!empty($row_creditmemoline['Other1'])){
                    $qbxml_ln .= '<Other1>'.htmlspecialchars($row_creditmemoline['Other1']).'</Other1>';
                    }

                    if(!empty($row_creditmemoline['Other2'])){
                    $qbxml_ln .= '<Other2>'.htmlspecialchars($row_creditmemoline['Other2']).'</Other2>';
                    }

                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardNumber']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationMonth']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationYear']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_NameOnCard']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardAddress']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CommercialCardCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_TransactionMode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardTxnType']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ResultCode']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ResultMessage']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_CreditCardTransID']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_MerchantAccountNumber']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AuthorizationCode']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AVSStreet']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AVSZip']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_CardSecurityCodeMatch']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ReconBatchID']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentGroupingCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentStatus']) ||
                    (!empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime']) && $row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime'] != '0000-00-00 00:00:00')||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationStamp']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ClientTransID'])){

                    $qbxml_ln .= '<CreditCardTxnInfo>';
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardNumber']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationMonth']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationYear']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_NameOnCard']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardAddress']) || 
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CommercialCardCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_TransactionMode']) ||
                    !empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardTxnType'])){

                    $qbxml_ln .= '<CreditCardTxnInputInfo>';
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardNumber'])){
                    $qbxml_ln .= '<CreditCardNumber>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_CreditCardNumber']).'</CreditCardNumber>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationMonth'])){
                    $qbxml_ln .= '<ExpirationMonth>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_ExpirationMonth']).'</ExpirationMonth>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_ExpirationYear'])){
                    $qbxml_ln .= '<ExpirationYear>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_ExpirationYear']).'</ExpirationYear>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_NameOnCard'])){
                    $qbxml_ln .= '<NameOnCard>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_NameOnCard']).'</NameOnCard>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardAddress'])){
                    $qbxml_ln .= '<CreditCardAddress>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_CreditCardAddress']).'</CreditCardAddress>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode'])){
                    $qbxml_ln .= '<CreditCardPostalCode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_CreditCardPostalCode']).'</CreditCardPostalCode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CommercialCardCode'])){
                    $qbxml_ln .= '<CommercialCardCode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_CommercialCardCode']).'</CommercialCardCode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_TransactionMode'])){
                    $qbxml_ln .= '<TransactionMode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_TransactionMode']).'</TransactionMode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnInputInfo_CreditCardTxnType'])){
                    $qbxml_ln .= '<CreditCardTxnType>'.htmlspecialchars($row_creditmemoline['CreditCardTxnInputInfo_CreditCardTxnType']).'</CreditCardTxnType>';
                    }
                    $qbxml_ln .= '</CreditCardTxnInputInfo>';
                    
                    }

                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_ResultCode']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ResultMessage']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_CreditCardTransID']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_MerchantAccountNumber']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AuthorizationCode']) || 
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AVSStreet']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_AVSZip']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_CardSecurityCodeMatch']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ReconBatchID']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentGroupingCode']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentStatus']) ||
                    (!empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime']) && $row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime'] != '0000-00-00 00:00:00')||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationStamp']) ||
                    !empty($row_creditmemoline['CreditCardTxnResultInfo_ClientTransID'])){

                    $qbxml_ln .= '<CreditCardTxnResultInfo>';
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_ResultCode'])){
                    $qbxml_ln .= '<ResultCode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_ResultCode']).'</ResultCode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_ResultMessage'])){
                    $qbxml_ln .= '<ResultMessage>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_ResultMessage']).'</ResultMessage>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_CreditCardTransID'])){
                    $qbxml_ln .= '<CreditCardTransID>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_CreditCardTransID']).'</CreditCardTransID>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_MerchantAccountNumber'])){
                    $qbxml_ln .= '<MerchantAccountNumber>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_MerchantAccountNumber']).'</MerchantAccountNumber>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_AuthorizationCode'])){
                    $qbxml_ln .= '<AuthorizationCode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_AuthorizationCode']).'</AuthorizationCode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_AVSStreet'])){
                    $qbxml_ln .= '<AVSStreet>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_AVSStreet']).'</AVSStreet>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_AVSZip'])){
                    $qbxml_ln .= '<AVSZip>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_AVSZip']).'</AVSZip>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_CardSecurityCodeMatch'])){
                    $qbxml_ln .= '<CardSecurityCodeMatch>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_CardSecurityCodeMatch']).'</CardSecurityCodeMatch>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_ReconBatchID'])){
                    $qbxml_ln .= '<ReconBatchID>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_ReconBatchID']).'</ReconBatchID>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentGroupingCode'])){
                    $qbxml_ln .= '<PaymentGroupingCode>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_PaymentGroupingCode']).'</PaymentGroupingCode>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_PaymentStatus'])){
                    $qbxml_ln .= '<PaymentStatus>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_PaymentStatus']).'</PaymentStatus>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime']) && $row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime'] != '0000-00-00 00:00:00'){
                    $qbxml_ln .= '<TxnAuthorizationTime>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationTime']).'</TxnAuthorizationTime>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationStamp'])){
                    $qbxml_ln .= '<TxnAuthorizationStamp>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_TxnAuthorizationStamp']).'</TxnAuthorizationStamp>';
                    }
                    if(!empty($row_creditmemoline['CreditCardTxnResultInfo_ClientTransID'])){
                    $qbxml_ln .= '<ClientTransID>'.htmlspecialchars($row_creditmemoline['CreditCardTxnResultInfo_ClientTransID']).'</ClientTransID>';
                    }
                    $qbxml_ln .= '</CreditCardTxnResultInfo>';

                    }

                    $qbxml_ln .= '</CreditCardTxnInfo>';
                    }   

                    $qbxml_ln .= '</CreditMemoLineAdd>';

                    $item_line++;
                }

                if(mysqli_num_rows($check_customer_query) > 0) {
                    if(mysqli_num_rows($check_creditmemo_exists_query) > 0){
                        $creditmemo_no = mysqli_real_escape_string($dblink, $row_creditmemo['RefNumber']);	
                        mysqli_query($dblink, "
                        INSERT INTO 
                        qb_error_log (Module, Msg) VALUES ('CreditMemoAddToQB', 'CreditMemo #'.$creditmemo_no.' already exist in WMS')");
                    }else{
                        //if($quantity_check == $item_line){
                            $qbxml .= '<CreditMemoAddRq>';
                            $qbxml .= '<CreditMemoAdd defMacro="RefNumber:'.$row_creditmemo['RefNumber'].'">';

                            $qbxml .= '<CustomerRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['Customer_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['Customer_FullName']).'</FullName>';
                            $qbxml .= '</CustomerRef>';

                            if(!empty($row_creditmemo['Class_FullName'])){
                            $qbxml .= '<ClassRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['Class_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['Class_FullName']).'</FullName>';
                            $qbxml .= '</ClassRef>';
                            }

                            if(!empty($row_creditmemo['ARAccount_FullName'])){
                            $qbxml .= '<ARAccountRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['ARAccount_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['ARAccount_FullName']).'</FullName>';
                            $qbxml .= '</ARAccountRef>';
                            }

                            if(!empty($row_creditmemo['Template_FullName'])){
                            $qbxml .= '<TemplateRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['Template_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['Template_FullName']).'</FullName>';
                            $qbxml .= '</TemplateRef>';
                            }

                            if(!empty($row_creditmemo['TxnDate']) && $row_creditmemo['TxnDate'] != '0000-00-00'){
                            $qbxml .= '<TxnDate>'.htmlspecialchars($row_creditmemo['TxnDate']).'</TxnDate>';
                            }

                            if(!empty($row_creditmemo['RefNumber'])){
                            $qbxml .= '<RefNumber>'.htmlspecialchars($row_creditmemo['RefNumber']).'</RefNumber>';
                            }

                            $qbxml .= '<BillAddress>';
                            if(!empty($row_creditmemo['BillAddress_Addr1'])){
                            $qbxml .= '<Addr1>'.htmlspecialchars($row_creditmemo['BillAddress_Addr1']).'</Addr1>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Addr2'])){
                            $qbxml .= '<Addr2>'.htmlspecialchars($row_creditmemo['BillAddress_Addr2']).'</Addr2>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Addr3'])){
                            $qbxml .= '<Addr3>'.htmlspecialchars($row_creditmemo['BillAddress_Addr3']).'</Addr3>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Addr4'])){
                            $qbxml .= '<Addr4>'.htmlspecialchars($row_creditmemo['BillAddress_Addr4']).'</Addr4>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Addr5'])){
                            $qbxml .= '<Addr5>'.htmlspecialchars($row_creditmemo['BillAddress_Addr5']).'</Addr5>';
                            }
                            if(!empty($row_creditmemo['BillAddress_City'])){
                            $qbxml .= '<City>'.htmlspecialchars($row_creditmemo['BillAddress_City']).'</City>';
                            }
                            if(!empty($row_creditmemo['BillAddress_State'])){
                            $qbxml .= '<State>'.htmlspecialchars($row_creditmemo['BillAddress_State']).'</State>';
                            }
                            if(!empty($row_creditmemo['BillAddress_PostalCode'])){
                            $qbxml .= '<PostalCode>'.htmlspecialchars($row_creditmemo['BillAddress_PostalCode']).'</PostalCode>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Country'])){
                            $qbxml .= '<Country>'.htmlspecialchars($row_creditmemo['BillAddress_Country']).'</Country>';
                            }
                            if(!empty($row_creditmemo['BillAddress_Note'])){
                            $qbxml .= '<Note>'.htmlspecialchars($row_creditmemo['BillAddress_Note']).'</Note>';
                            }
                            $qbxml .= '</BillAddress>';

                            $qbxml .= '<ShipAddress>';
                            if(!empty($row_creditmemo['ShipAddress_Addr1'])){
                            $qbxml .= '<Addr1>'.htmlspecialchars($row_creditmemo['ShipAddress_Addr1']).'</Addr1>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Addr2'])){
                            $qbxml .= '<Addr2>'.htmlspecialchars($row_creditmemo['ShipAddress_Addr2']).'</Addr2>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Addr3'])){
                            $qbxml .= '<Addr3>'.htmlspecialchars($row_creditmemo['ShipAddress_Addr3']).'</Addr3>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Addr4'])){
                            $qbxml .= '<Addr4>'.htmlspecialchars($row_creditmemo['ShipAddress_Addr4']).'</Addr4>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Addr5'])){
                            $qbxml .= '<Addr5>'.htmlspecialchars($row_creditmemo['ShipAddress_Addr5']).'</Addr5>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_City'])){
                            $qbxml .= '<City>'.htmlspecialchars($row_creditmemo['ShipAddress_City']).'</City>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_State'])){
                            $qbxml .= '<State>'.htmlspecialchars($row_creditmemo['ShipAddress_State']).'</State>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_PostalCode'])){
                            $qbxml .= '<PostalCode>'.htmlspecialchars($row_creditmemo['ShipAddress_PostalCode']).'</PostalCode>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Country'])){
                            $qbxml .= '<Country>'.htmlspecialchars($row_creditmemo['ShipAddress_Country']).'</Country>';
                            }
                            if(!empty($row_creditmemo['ShipAddress_Note'])){
                            $qbxml .= '<Note>'.htmlspecialchars($row_creditmemo['ShipAddress_Note']).'</Note>';
                            }
                            $qbxml .= '</ShipAddress>';

                            $IsPending = ($row_creditmemo['IsPending'] == 1)?'true':'false';
                            $qbxml .= '<IsPending>'.$IsPending.'</IsPending>';

                            if(!empty($row_creditmemo['PONumber'])){
                            $qbxml .= '<PONumber>'.$row_creditmemo['PONumber'].'</PONumber>';
                            }

                            if(!empty($row_creditmemo['Terms_FullName'])){
                            $qbxml .= '<TermsRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['Terms_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['Terms_FullName']).'</FullName>';
                            $qbxml .= '</TermsRef>';
                            }

                            if(!empty($row_creditmemo['DueDate']) && $row_creditmemo['DueDate'] != '0000-00-00'){
                            $qbxml .= '<DueDate>'.$row_creditmemo['DueDate'].'</DueDate>';
                            }

                            if(!empty($row_creditmemo['SalesRep_FullName'])){
                            $qbxml .= '<SalesRepRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['SalesRep_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['SalesRep_FullName']).'</FullName>';
                            $qbxml .= '</SalesRepRef>';
                            }

                            if(!empty($row_creditmemo['FOB'])){
                            $qbxml .= '<FOB>'.htmlspecialchars($row_creditmemo['FOB']).'</FOB>';
                            }

                            if(!empty($row_creditmemo['ShipDate']) && $row_creditmemo['ShipDate'] != '0000-00-00'){
                            $qbxml .= '<ShipDate>'.htmlspecialchars($row_creditmemo['ShipDate']).'</ShipDate>';
                            }

                            if(!empty($row_creditmemo['ShipMethod_FullName'])){
                            $qbxml .= '<ShipMethodRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['ShipMethod_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['ShipMethod_FullName']).'</FullName>';
                            $qbxml .= '</ShipMethodRef>';
                            }

                            if(!empty($row_creditmemo['ItemSalesTax_FullName'])){
                            $qbxml .= '<ItemSalesTaxRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['ItemSalesTax_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['ItemSalesTax_FullName']).'</FullName>';
                            $qbxml .= '</ItemSalesTaxRef>';
                            }

                            if(!empty($row_creditmemo['Memo'])){
                            $qbxml .= '<Memo>'.htmlspecialchars($row_creditmemo['Memo']).'</Memo>';
                            }

                            if(!empty($row_creditmemo['CustomerMsg_FullName'])){
                            $qbxml .= '<CustomerMsgRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['CustomerMsg_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['CustomerMsg_FullName']).'</FullName>';
                            $qbxml .= '</CustomerMsgRef>';
                            }

                            $IsToBePrinted = ($row_creditmemo['IsToBePrinted'] == 1)?'true':'false';
                            $qbxml .= '<IsToBePrinted>'.$IsToBePrinted.'</IsToBePrinted>';

                            $IsToBeEmailed = ($row_creditmemo['IsToBeEmailed'] == 1)?'true':'false';
                            $qbxml .= '<IsToBeEmailed>'.$IsToBeEmailed.'</IsToBeEmailed>';

                            //$IsTaxIncluded = ($row_creditmemo['IsTaxIncluded'] == 1)?'true':'false';
                            //$qbxml .= '<IsTaxIncluded>'.$IsTaxIncluded.'</IsTaxIncluded>';

                            if(!empty($row_creditmemo['CustomerSalesTaxCode_FullName'])){
                            $qbxml .= '<CustomerSalesTaxCodeRef>';
                            //$qbxml .= '<ListID>'.$row_creditmemo['CustomerSalesTaxCode_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_creditmemo['CustomerSalesTaxCode_FullName']).'</FullName>';
                            $qbxml .= '</CustomerSalesTaxCodeRef>';
                            }

                            if(!empty($row_creditmemo['Other'])){
                            $qbxml .= '<Other>'.htmlspecialchars($row_creditmemo['Other']).'</Other>';
                            }

                            if(!empty($row_creditmemo['ExternalGUID'])){
                            $qbxml .= '<ExternalGUID>'.htmlspecialchars($row_creditmemo['ExternalGUID']).'</ExternalGUID>';
                            }
                            
                            $qbxml .= $qbxml_ln;

                            $qbxml .= '</CreditMemoAdd>';
                            $qbxml .= '</CreditMemoAddRq>';
                            
                        /*}else{
                            mysqli_query($dblink, "
                            INSERT INTO 
                            qb_error_log (Module, Msg) VALUES ('CreditMemoAddToQB', 'Insufficient quantity entered for item line')");
                        }*/
                    }
                }else{
                    mysqli_query($dblink, "
                    INSERT INTO 
                    qb_error_log (Module, Msg) VALUES ('CreditMemoAddToQB', 'Customer doesn\'t exist in WMS')");
                }
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
function _quickbooks_creditmemo_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
}
