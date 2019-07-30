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
	$Queue->enqueue(QUICKBOOKS_ADD_INVOICE);
}

$Server = new QuickBooks_WebConnector_Server(QB_QUICKBOOKS_DSN, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/**
 * Build a request to generate the xml which contains newly generated invoices
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
function _quickbooks_invoice_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

    $sql = "SELECT TxnID FROM `qb_invoice` WHERE RefNumber NOT IN (SELECT RefNumber FROM `qb_recent_invoice`)";	
    $query = mysqli_query($dblink,$sql);

    $qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
        $qbxml_ln = ''; $quantity_check = $item_line = 0;
        while ($row = mysqli_fetch_assoc($query))
        {
            $sql_invoice = "SELECT * FROM `qb_invoice` WHERE TxnID = '".$row['TxnID']."'";
            $query_invoice = mysqli_query($dblink, $sql_invoice);
            if(mysqli_num_rows($query_invoice) > 0) {	
                $row_invoice = mysqli_fetch_assoc($query_invoice);

                $check_customer_sql = "SELECT FullName FROM `qb_customer` WHERE ListID = '".$row_invoice['Customer_ListID']."'";
                $check_customer_query = mysqli_query($dblink, $check_customer_sql);

                $check_invoice_exists_sql = "SELECT TxnID FROM `qb_recent_invoice` WHERE RefNumber = '".$row_invoice['RefNumber']."'";
                $check_invoice_exists_query = mysqli_query($dblink, $check_invoice_exists_sql);

                $sql_invoiceline = "SELECT * FROM `qb_invoice_invoiceline` WHERE Invoice_TxnID = '".$row['TxnID']."'";
                $query_invoiceline = mysqli_query($dblink, $sql_invoiceline);

                while ($row_invoiceline = mysqli_fetch_assoc($query_invoiceline)){
                    $item_quantity_check_sql = "SELECT QuantityOnHand FROM qb_iteminventory WHERE ListID = '".$row_invoiceline['Item_ListID']."'";
                    $item_quantity_check_query = mysqli_query($dblink, $item_quantity_check_sql);
                    if(mysqli_num_rows($item_quantity_check_query) > 0){
                        $item_quantity_check_row = mysqli_fetch_assoc($item_quantity_check_query);
                        if($item_quantity_check_row['QuantityOnHand'] >= $row_invoiceline['Quantity']){
                            $quantity_check++;  
                        }
                    }

                    $qbxml_ln .= '<InvoiceLineAdd>';
                    
                    $qbxml_ln .= '<ItemRef>';
                    //$qbxml_ln .= '<ListID>'.$row_invoiceline['Item_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_invoiceline['Item_FullName']).'</FullName>';
                    $qbxml_ln .= '</ItemRef>';

                    $qbxml_ln .= '<Desc>'.htmlspecialchars($row_invoiceline['Descrip']).'</Desc>';

                    $qbxml_ln .= '<Quantity>'.htmlspecialchars($row_invoiceline['Quantity']).'</Quantity>';

                    $qbxml_ln .= '<UnitOfMeasure>'.htmlspecialchars($row_invoiceline['UnitOfMeasure']).'</UnitOfMeasure>';

                    if(!empty($row_invoiceline['Rate']) && intval($row_invoiceline['Rate'] != 0)){
                    $qbxml_ln .= '<Rate>'.htmlspecialchars($row_invoiceline['Rate']).'</Rate>';
                    } elseif(!empty($row_invoiceline['RatePercent']) && intval($row_invoiceline['RatePercent'] != 0)){
                    $qbxml_ln .= '<RatePercent>'.htmlspecialchars($row_invoiceline['RatePercent']).'</RatePercent>';
                    }

                    if(!empty($row_invoiceline['Class_FullName'])){
                    $qbxml_ln .= '<ClassRef>';
                    //$qbxml_ln .= '<ListID>'.$row_invoiceline['Class_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_invoiceline['Class_FullName']).'</FullName>';
                    $qbxml_ln .= '</ClassRef>';
                    }

                    if(!empty($row_invoiceline['Amount']) && intval($row_invoiceline['Amount'] != 0)){
                    $qbxml_ln .= '<Amount>'.htmlspecialchars($row_invoiceline['Amount']).'</Amount>';
                    }

                    if(!empty($row_invoiceline['InventorySite_FullName'])){
                    $qbxml_ln .= '<InventorySiteRef>';
                    //$qbxml .= '<ListID>'.$row_invoiceline['InventorySite_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_invoiceline['InventorySite_FullName']).'</FullName>';
                    $qbxml_ln .= '</InventorySiteRef>';
                    }

                    if(!empty($row_invoiceline['InventorySiteLocation_FullName'])){
                    $qbxml_ln .= '<InventorySiteLocationRef>';
                    //$qbxml .= '<ListID>'.$row_invoiceline['InventorySite_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_invoiceline['InventorySiteLocation_FullName']).'</FullName>';
                    $qbxml_ln .= '</InventorySiteLocationRef>';
                    }

                    if(!empty($row_invoiceline['SerialNumber'])){
                    $qbxml_ln .= '<SerialNumber>'.htmlspecialchars($row_invoiceline['SerialNumber']).'</SerialNumber>';
                    }
                    
                    if(!empty($row_invoiceline['LotNumber'])){
                    $qbxml_ln .= '<LotNumber>'.htmlspecialchars($row_invoiceline['LotNumber']).'</LotNumber>';
                    }

                    if(!empty($row_invoiceline['ServiceDate']) && $row_invoiceline['ServiceDate'] != '0000-00-00'){
                    $qbxml_ln .= '<ServiceDate>'.htmlspecialchars($row_invoiceline['ServiceDate']).'</ServiceDate>';
                    }

                    if(!empty($row_invoiceline['SalesTaxCode_FullName'])){
                    $qbxml_ln .= '<SalesTaxCodeRef>';
                    //$qbxml_ln .= '<ListID>'.$row_invoiceline['SalesTaxCode_ListID'].'</ListID>';
                    $qbxml_ln .= '<FullName>'.htmlspecialchars($row_invoiceline['SalesTaxCode_FullName']).'</FullName>';
                    $qbxml_ln .= '</SalesTaxCodeRef>';
                    }

                    if(!empty($row_invoiceline['Other1'])){
                    $qbxml_ln .= '<Other1>'.htmlspecialchars($row_invoiceline['Other1']).'</Other1>';
                    }

                    if(!empty($row_invoiceline['Other2'])){
                    $qbxml_ln .= '<Other2>'.htmlspecialchars($row_invoiceline['Other2']).'</Other2>';
                    }

                    $qbxml_ln .= '</InvoiceLineAdd>';

                    $item_line++;
                }

                if(mysqli_num_rows($check_customer_query) > 0) {
                    if(mysqli_num_rows($check_invoice_exists_query) > 0){
                        $invoice_no = mysqli_real_escape_string($dblink, $row_invoice['RefNumber']);	
                        mysqli_query($dblink, "
                        INSERT INTO 
                        qb_error_log (Module, Msg) VALUES ('InvoiceAddToQB', 'Invoice #'.$invoice_no.' already exist in WMS')");
                    }else{
                        //if($quantity_check == $item_line){
                            $qbxml .= '<InvoiceAddRq>';
                            $qbxml .= '<InvoiceAdd defMacro="TxnID:'.$row['TxnID'].'">';

                            $qbxml .= '<CustomerRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['Customer_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['Customer_FullName']).'</FullName>';
                            $qbxml .= '</CustomerRef>';

                            if(!empty($row_invoice['Class_FullName'])){
                            $qbxml .= '<ClassRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['Class_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['Class_FullName']).'</FullName>';
                            $qbxml .= '</ClassRef>';
                            }

                            if(!empty($row_invoice['ARAccount_FullName'])){
                            $qbxml .= '<ARAccountRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['ARAccount_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['ARAccount_FullName']).'</FullName>';
                            $qbxml .= '</ARAccountRef>';
                            }

                            if(!empty($row_invoice['Template_FullName'])){
                            $qbxml .= '<TemplateRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['Template_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['Template_FullName']).'</FullName>';
                            $qbxml .= '</TemplateRef>';
                            }

                            if(!empty($row_invoice['TxnDate']) && $row_invoice['TxnDate'] != '0000-00-00'){
                            $qbxml .= '<TxnDate>'.htmlspecialchars($row_invoice['TxnDate']).'</TxnDate>';
                            }

                            if(!empty($row_invoice['RefNumber'])){
                            $qbxml .= '<RefNumber>'.htmlspecialchars($row_invoice['RefNumber']).'</RefNumber>';
                            }

                            $qbxml .= '<BillAddress>';
                            if(!empty($row_invoice['BillAddress_Addr1'])){
                            $qbxml .= '<Addr1>'.htmlspecialchars($row_invoice['BillAddress_Addr1']).'</Addr1>';
                            }
                            if(!empty($row_invoice['BillAddress_Addr2'])){
                            $qbxml .= '<Addr2>'.htmlspecialchars($row_invoice['BillAddress_Addr2']).'</Addr2>';
                            }
                            if(!empty($row_invoice['BillAddress_Addr3'])){
                            $qbxml .= '<Addr3>'.htmlspecialchars($row_invoice['BillAddress_Addr3']).'</Addr3>';
                            }
                            if(!empty($row_invoice['BillAddress_Addr4'])){
                            $qbxml .= '<Addr4>'.htmlspecialchars($row_invoice['BillAddress_Addr4']).'</Addr4>';
                            }
                            if(!empty($row_invoice['BillAddress_Addr5'])){
                            $qbxml .= '<Addr5>'.htmlspecialchars($row_invoice['BillAddress_Addr5']).'</Addr5>';
                            }
                            if(!empty($row_invoice['BillAddress_City'])){
                            $qbxml .= '<City>'.htmlspecialchars($row_invoice['BillAddress_City']).'</City>';
                            }
                            if(!empty($row_invoice['BillAddress_State'])){
                            $qbxml .= '<State>'.htmlspecialchars($row_invoice['BillAddress_State']).'</State>';
                            }
                            if(!empty($row_invoice['BillAddress_PostalCode'])){
                            $qbxml .= '<PostalCode>'.htmlspecialchars($row_invoice['BillAddress_PostalCode']).'</PostalCode>';
                            }
                            if(!empty($row_invoice['BillAddress_Country'])){
                            $qbxml .= '<Country>'.htmlspecialchars($row_invoice['BillAddress_Country']).'</Country>';
                            }
                            if(!empty($row_invoice['BillAddress_Note'])){
                            $qbxml .= '<Note>'.htmlspecialchars($row_invoice['BillAddress_Note']).'</Note>';
                            }
                            $qbxml .= '</BillAddress>';

                            $qbxml .= '<ShipAddress>';
                            if(!empty($row_invoice['ShipAddress_Addr1'])){
                            $qbxml .= '<Addr1>'.htmlspecialchars($row_invoice['ShipAddress_Addr1']).'</Addr1>';
                            }
                            if(!empty($row_invoice['ShipAddress_Addr2'])){
                            $qbxml .= '<Addr2>'.htmlspecialchars($row_invoice['ShipAddress_Addr2']).'</Addr2>';
                            }
                            if(!empty($row_invoice['ShipAddress_Addr3'])){
                            $qbxml .= '<Addr3>'.htmlspecialchars($row_invoice['ShipAddress_Addr3']).'</Addr3>';
                            }
                            if(!empty($row_invoice['ShipAddress_Addr4'])){
                            $qbxml .= '<Addr4>'.htmlspecialchars($row_invoice['ShipAddress_Addr4']).'</Addr4>';
                            }
                            if(!empty($row_invoice['ShipAddress_Addr5'])){
                            $qbxml .= '<Addr5>'.htmlspecialchars($row_invoice['ShipAddress_Addr5']).'</Addr5>';
                            }
                            if(!empty($row_invoice['ShipAddress_City'])){
                            $qbxml .= '<City>'.htmlspecialchars($row_invoice['ShipAddress_City']).'</City>';
                            }
                            if(!empty($row_invoice['ShipAddress_State'])){
                            $qbxml .= '<State>'.htmlspecialchars($row_invoice['ShipAddress_State']).'</State>';
                            }
                            if(!empty($row_invoice['ShipAddress_PostalCode'])){
                            $qbxml .= '<PostalCode>'.htmlspecialchars($row_invoice['ShipAddress_PostalCode']).'</PostalCode>';
                            }
                            if(!empty($row_invoice['ShipAddress_Country'])){
                            $qbxml .= '<Country>'.htmlspecialchars($row_invoice['ShipAddress_Country']).'</Country>';
                            }
                            if(!empty($row_invoice['ShipAddress_Note'])){
                            $qbxml .= '<Note>'.htmlspecialchars($row_invoice['ShipAddress_Note']).'</Note>';
                            }
                            $qbxml .= '</ShipAddress>';

                            $IsPending = ($row_invoice['IsPending'] == 1)?'true':'false';
                            $qbxml .= '<IsPending>'.$IsPending.'</IsPending>';

                            $IsFinanceCharge = ($row_invoice['IsFinanceCharge'] == 1)?'true':'false';
                            $qbxml .= '<IsFinanceCharge>'.$IsFinanceCharge.'</IsFinanceCharge>';
                        
                            if(!empty($row_invoice['PONumber'])){
                            $qbxml .= '<PONumber>'.$row_invoice['PONumber'].'</PONumber>';
                            }

                            if(!empty($row_invoice['Terms_FullName'])){
                            $qbxml .= '<TermsRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['Terms_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['Terms_FullName']).'</FullName>';
                            $qbxml .= '</TermsRef>';
                            }

                            if(!empty($row_invoice['DueDate']) && $row_invoice['DueDate'] != '0000-00-00'){
                            $qbxml .= '<DueDate>'.$row_invoice['DueDate'].'</DueDate>';
                            }

                            if(!empty($row_invoice['SalesRep_FullName'])){
                            $qbxml .= '<SalesRepRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['SalesRep_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['SalesRep_FullName']).'</FullName>';
                            $qbxml .= '</SalesRepRef>';
                            }

                            if(!empty($row_invoice['FOB'])){
                            $qbxml .= '<FOB>'.htmlspecialchars($row_invoice['FOB']).'</FOB>';
                            }

                            if(!empty($row_invoice['ShipDate']) && $row_invoice['ShipDate'] != '0000-00-00'){
                            $qbxml .= '<ShipDate>'.htmlspecialchars($row_invoice['ShipDate']).'</ShipDate>';
                            }

                            if(!empty($row_invoice['ShipMethod_FullName'])){
                            $qbxml .= '<ShipMethodRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['ShipMethod_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['ShipMethod_FullName']).'</FullName>';
                            $qbxml .= '</ShipMethodRef>';
                            }

                            if(!empty($row_invoice['ItemSalesTax_FullName'])){
                            $qbxml .= '<ItemSalesTaxRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['ItemSalesTax_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['ItemSalesTax_FullName']).'</FullName>';
                            $qbxml .= '</ItemSalesTaxRef>';
                            }

                            if(!empty($row_invoice['Memo'])){
                            $qbxml .= '<Memo>'.htmlspecialchars($row_invoice['Memo']).'</Memo>';
                            }

                            if(!empty($row_invoice['CustomerMsg_FullName'])){
                            $qbxml .= '<CustomerMsgRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['CustomerMsg_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['CustomerMsg_FullName']).'</FullName>';
                            $qbxml .= '</CustomerMsgRef>';
                            }

                            $IsToBePrinted = ($row_invoice['IsToBePrinted'] == 1)?'true':'false';
                            $qbxml .= '<IsToBePrinted>'.$IsToBePrinted.'</IsToBePrinted>';

                            $IsToBeEmailed = ($row_invoice['IsToBeEmailed'] == 1)?'true':'false';
                            $qbxml .= '<IsToBeEmailed>'.$IsToBeEmailed.'</IsToBeEmailed>';

                            if(!empty($row_invoice['CustomerSalesTaxCode_FullName'])){
                            $qbxml .= '<CustomerSalesTaxCodeRef>';
                            //$qbxml .= '<ListID>'.$row_invoice['CustomerSalesTaxCode_ListID'].'</ListID>';
                            $qbxml .= '<FullName>'.htmlspecialchars($row_invoice['CustomerSalesTaxCode_FullName']).'</FullName>';
                            $qbxml .= '</CustomerSalesTaxCodeRef>';
                            }

                            if(!empty($row_invoice['Other'])){
                            $qbxml .= '<Other>'.htmlspecialchars($row_invoice['Other']).'</Other>';
                            }

                            if(!empty($row_invoice['ExchangeRate']) && intval($row_invoice['ExchangeRate']) == 0){
                            $qbxml .= '<ExchangeRate>'.htmlspecialchars($row_invoice['ExchangeRate']).'</ExchangeRate>';
                            }
                            
                            $qbxml .= $qbxml_ln;

                            $qbxml .= '</InvoiceAdd>';
                            $qbxml .= '</InvoiceAddRq>';
                            
                        /*}else{
                            mysqli_query($dblink, "
                            INSERT INTO 
                            qb_error_log (Module, Msg) VALUES ('InvoiceAddToQB', 'Insufficient quantity entered for item line')");
                        }*/
                    }
                }else{
                    mysqli_query($dblink, "
                    INSERT INTO 
                    qb_error_log (Module, Msg) VALUES ('InvoiceAddToQB', 'Customer doesn\'t exist in WMS')");
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
function _quickbooks_invoice_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
}
