<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// We need to make sure the correct timezone is set, or some PHP installations will complain
if (function_exists('date_default_timezone_set'))
{
	// * MAKE SURE YOU SET THIS TO THE CORRECT TIMEZONE! *
	// List of valid timezones is here: http://us3.php.net/manual/en/timezones.php
	date_default_timezone_set('America/New_York');
}

// Require the framework
require_once 'QuickBooks.php';

$dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

if(isset($_GET['action']) && $_GET['action']=='po'){
    $sql = "SELECT * FROM `qb_purchaseorder`";	
    $query = mysqli_query($dblink, $sql);

    $qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
        $arr_TxnID = array();
        $iteration = 1;
        while ($row = mysqli_fetch_array($query)) {
            echo 'Iteration: '.$iteration.'<br>';
            echo 'Transaction : '.$row['TxnID'].'<br>';
            $sql_trxn_pol = "SELECT * FROM `qb_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '".$row['TxnID']."'";	
            $query_trxn_pol = mysqli_query($dblink, $sql_trxn_pol);
            if(mysqli_num_rows($query_trxn_pol) > 0) {
                while ($row_pol = mysqli_fetch_array($query_trxn_pol)) {
                    echo 'Transaction Line: '.$row_pol['TxnLineID'].'<br>';
                    $sql_pol = "SELECT `ReceivedQuantity` FROM `qb_purchaseorder_purchaseorderline` where `TxnLineID` = '".$row_pol['TxnLineID']."'";	
                    $query_pol = mysqli_query($dblink, $sql_pol);
                    $result_pol = mysqli_fetch_assoc($query_pol);
                    
                    $sql_recent_pol = "SELECT `ReceivedQuantity` FROM `qb_recent_purchaseorder_purchaseorderline` where `TxnLineID` = '".$row_pol['TxnLineID']."'";	
                    $query_recent_pol = mysqli_query($dblink, $sql_recent_pol);
                    $result_recent_pol = mysqli_fetch_assoc($query_recent_pol);

                    if(!empty($result_pol) && !empty($result_recent_pol)){
                        $diff = array_diff($result_pol, $result_recent_pol);
                        if(!empty($diff)){
                            echo '<pre>';
                            print_r($diff);
                            $arr_TxnID[$iteration] = $row['TxnID'];
                        }
                    }
                }
                echo '<br>';

            }
        $iteration++;
        }
        
        if(!empty($arr_TxnID)){
			foreach($arr_TxnID as $key=>$val){
				$sql_xml_po = "SELECT * FROM `qb_purchaseorder` WHERE TxnID = '".$val."'";	
				$query_xml_po = mysqli_query($dblink, $sql_xml_po);
				$result_xml_po = mysqli_fetch_assoc($query_xml_po);
	
				$qbxml .= '<ItemReceiptAddRq requestID="'.$result_xml_po['RefNumber'].'">';
				$qbxml .= '<ItemReceiptAdd>';
				$qbxml .= '<VendorRef><FullName>'.htmlspecialchars($result_xml_po['Vendor_FullName']).'</FullName></VendorRef>';
				$qbxml .= '<RefNumber>'.$result_xml_po['RefNumber'].'</RefNumber>';
				$qbxml .= '<LinkToTxnID>'.$result_xml_po['TxnID'].'</LinkToTxnID>';
	
				$sql_xml_pol = "SELECT TxnLineID FROM `qb_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '".$val."'";	
				$query_xml_pol = mysqli_query($dblink, $sql_xml_pol);
				if(mysqli_num_rows($query_xml_pol) > 0) {
					while ($row_xml_pol = mysqli_fetch_array($query_xml_pol)) {
						$sql_xml_pols = "SELECT * FROM `qb_purchaseorder_purchaseorderline` where `TxnLineID` = '".$row_xml_pol['TxnLineID']."'";	
						$query_xml_pols = mysqli_query($dblink, $sql_xml_pols);
						$result_xml_pols = mysqli_fetch_assoc($query_xml_pols);
	
						$qbxml .= '<ItemLineAdd>';
						$qbxml .= '<ItemRef><FullName>'.htmlspecialchars($result_xml_pols['Item_FullName']).'</FullName></ItemRef>';
						if(!empty($result_xml_pols['Descrip'])){
						$qbxml .= '<Desc>'.htmlspecialchars($result_xml_pols['Descrip']).'</Desc>';
						}
						if(!empty($result_xml_pols['ReceivedQuantity'])){
						$qbxml .= '<Quantity>'.$result_xml_pols['ReceivedQuantity'].'</Quantity>';
						}
						if(!empty($result_xml_pols['UnitOfMeasure'])){
						$qbxml .= '<UnitOfMeasure>'.$result_xml_pols['UnitOfMeasure'].'</UnitOfMeasure>';
						}
						if(!empty($result_xml_pols['Rate'])){
						$qbxml .= '<Cost>'.$result_xml_pols['Rate'].'</Cost>';
                        }
                        
                        $Amount = $result_xml_pols['Rate']*$result_xml_pols['ReceivedQuantity'];
						$qbxml .= '<Amount>'.floatval($Amount).'</Amount>';
						
						if(!empty($result_xml_pols['Customer_FullName'])){
							$qbxml .= '<CustomerRef><FullName>'.htmlspecialchars($result_xml_pols['Customer_FullName']).'</FullName></CustomerRef>';
						} 
						if(!empty($result_xml_pols['Class_FullName'])){
							$qbxml .= '<ClassRef><FullName>'.htmlspecialchars($result_xml_pols['Class_FullName']).'</FullName></ClassRef>';
                        }
                        
                        //$qbxml .= '<LinkToTxn><TxnID>'.$result_xml_pols['PurchaseOrder_TxnID'].'</TxnID><TxnLineID>'.$result_xml_pols['TxnLineID'].'</TxnLineID></LinkToTxn>';
						$qbxml .= '</ItemLineAdd>';
					}
				}
	
				$qbxml .= '</ItemReceiptAdd>';
				$qbxml .='</ItemReceiptAddRq>';
			}
		}
        print_r($arr_TxnID);
        //echo '<pre>';
    }
    $qbxml .='</QBXMLMsgsRq></QBXML>';
    print_r($qbxml);
}

if(isset($_GET['action']) && $_GET['action']=='so'){
	$sql = "SELECT * FROM `qb_salesorder`";	
	$query = mysqli_query($dblink, $sql);
	
	$qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
		$arr_TxnID = array();
		$iteration = 1;
		while ($row = mysqli_fetch_array($query)) {
            echo 'Iteration: '.$iteration.'<br>';
            echo 'Transaction : '.$row['TxnID'].'<br>';
			$sql_trxn_sol = "SELECT * FROM `qb_salesorder_salesorderline` where `SalesOrder_TxnID` = '".$row['TxnID']."'";	
			$query_trxn_sol = mysqli_query($dblink, $sql_trxn_sol);
			if(mysqli_num_rows($query_trxn_sol) > 0) {
				while ($row_sol = mysqli_fetch_array($query_trxn_sol)) {
                    echo 'Transaction Line: '.$row_sol['TxnLineID'].'<br>';
					$sql_sol = "SELECT `Quantity` FROM `qb_salesorder_salesorderline` where `TxnLineID` = '".$row_sol['TxnLineID']."'";	
					$query_sol = mysqli_query($dblink, $sql_sol);
					$result_sol = mysqli_fetch_assoc($query_sol);
					
					$sql_recent_sol = "SELECT `Quantity` FROM `qb_recent_salesorder_salesorderline` where `TxnLineID` = '".$row_sol['TxnLineID']."'";	
					$query_recent_sol = mysqli_query($dblink, $sql_recent_sol);
					$result_recent_sol = mysqli_fetch_assoc($query_recent_sol);
	
					if(!empty($result_sol) && !empty($result_recent_sol)){
						$diff = array_diff($result_sol, $result_recent_sol);
						if(!empty($diff)){
                            echo '<pre>';
                            print_r($diff);
							$arr_TxnID[$iteration] = $row['TxnID'];
						}
					}
                }
                echo '<br>';
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
    print_r($qbxml); 
}

if(isset($_GET['action']) && $_GET['action']=='invoice'){
    $dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

    $sql = "SELECT * FROM `qb_invoice`";	
    $query = mysqli_query($dblink, $sql);
    
    $qbxml = '<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML><QBXMLMsgsRq onError="stopOnError">';
    if(mysqli_num_rows($query) > 0) {
        $arr_TxnID = array();
        $iteration = 1;
        while ($row = mysqli_fetch_array($query)) {
            echo 'Iteration: '.$iteration.'<br>';
            echo 'Transaction : '.$row['TxnID'].'<br>';
            $sql_trxn_pol = "SELECT * FROM `qb_invoice_invoiceline` where `Invoice_TxnID` = '".$row['TxnID']."'";	
            $query_trxn_pol = mysqli_query($dblink, $sql_trxn_pol);
            if(mysqli_num_rows($query_trxn_pol) > 0) {
                while ($row_pol = mysqli_fetch_array($query_trxn_pol)) {
                    echo 'Transaction Line: '.$row_pol['TxnLineID'].'<br>';
                    $sql_pol = "SELECT `Quantity` FROM `qb_invoice_invoiceline` where `TxnLineID` = '".$row_pol['TxnLineID']."'";	
                    $query_pol = mysqli_query($dblink, $sql_pol);
                    $result_pol = mysqli_fetch_assoc($query_pol);
                    
                    $sql_recent_pol = "SELECT `Quantity` FROM `qb_recent_invoice_invoiceline` where `TxnLineID` = '".$row_pol['TxnLineID']."'";	
                    $query_recent_pol = mysqli_query($dblink, $sql_recent_pol);
                    $result_recent_pol = mysqli_fetch_assoc($query_recent_pol);
    
                    if(!empty($result_pol) && !empty($result_recent_pol)){
                        $diff = array_diff($result_pol, $result_recent_pol);
                        if(!empty($diff)){
                            echo '<pre>';
                            print_r($diff);
                            $arr_TxnID[$iteration] = $row['TxnID'];
                        }
                    }
                }
            }
        $iteration++;
        }
        
        if(!empty($arr_TxnID)){
            foreach($arr_TxnID as $key=>$val){
                $qbxml .= '<InvoiceModRq>';
                $sql_xml_po = "SELECT TxnID, EditSequence FROM `qb_invoice` WHERE TxnID = '".$val."'";	
                $query_xml_po = mysqli_query($dblink, $sql_xml_po);
                $result_xml_po = mysqli_fetch_assoc($query_xml_po);
    
                $qbxml .= '<InvoiceMod>';
                $qbxml .= '<TxnID>'.$result_xml_po['TxnID'].'</TxnID>';
                $qbxml .= '<EditSequence>'.$result_xml_po['EditSequence'].'</EditSequence>';
    
                $sql_xml_pol = "SELECT TxnLineID FROM `qb_invoice_invoiceline` where `Invoice_TxnID` = '".$val."'";	
                $query_xml_pol = mysqli_query($dblink, $sql_xml_pol);
                if(mysqli_num_rows($query_xml_pol) > 0) {
                    while ($row_xml_pol = mysqli_fetch_array($query_xml_pol)) {
                        $sql_xml_pols = "SELECT `TxnLineID`, `Item_ListID`, `Item_FullName`, `Quantity` FROM `qb_invoice_invoiceline` where `TxnLineID` = '".$row_xml_pol['TxnLineID']."'";	
                        $query_xml_pols = mysqli_query($dblink, $sql_xml_pols);
                        $result_xml_pols = mysqli_fetch_assoc($query_xml_pols);
    
                        $qbxml .= '<InvoiceLineMod>';
                        $qbxml .= '<TxnLineID>'.$result_xml_pols['TxnLineID'].'</TxnLineID>';
                        //$qbxml .= '<ItemRef><ListID>'.$result_xml_pols['Item_ListID'].'</ListID><FullName>'.$result_xml_pols['Item_FullName'].'</FullName></ItemRef>';
                        $qbxml .= '<Quantity>'.$result_xml_pols['Quantity'].'</Quantity>';
                        $qbxml .= '</InvoiceLineMod>';
                    }
                }
    
                $qbxml .= '</InvoiceMod>';
                $qbxml .='</InvoiceModRq>';
            }
        }
    }
    $qbxml .='</QBXMLMsgsRq></QBXML>';
    print_r($qbxml);
}  


if(isset($_GET['action']) && $_GET['action']=='invadd'){
    $dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

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

                $sql_invoiceline = "SELECT * FROM `qb_invoice_invoiceline` WHERE Invoice_TxnID = '".$row['TxnID']."'";
                $query_invoiceline = mysqli_query($dblink, $sql_invoiceline);

                while ($row_invoiceline = mysqli_fetch_assoc($query_invoiceline)){
                    $item_quantity_check_sql = "SELECT QuantityOnSalesOrder FROM qb_iteminventory WHERE ListID = '".$row_invoiceline['Item_ListID']."'";
                    $item_quantity_check_query = mysqli_query($dblink, $item_quantity_check_sql);
                    if(mysqli_num_rows($item_quantity_check_query) > 0){
                        $item_quantity_check_row = mysqli_fetch_assoc($item_quantity_check_query);
                        if($item_quantity_check_row['QuantityOnSalesOrder'] >= $row_invoiceline['Quantity']){
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
                }else{
                    mysqli_query($dblink, "
                    INSERT INTO 
                    qb_error_log (Module, Msg) VALUES ('InvoiceAddToQB', 'Customer doesn\'t exist in WMS')");
                }
            }
        }
    }
    $qbxml .='</QBXMLMsgsRq></QBXML>';

    echo $qbxml;
}

if(isset($_GET['action']) && $_GET['action']=='creditmemoadd'){

    $dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

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
                        if($quantity_check == $item_line){
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

                            $IsTaxIncluded = ($row_creditmemo['IsTaxIncluded'] == 1)?'true':'false';
                            $qbxml .= '<IsTaxIncluded>'.$IsTaxIncluded.'</IsTaxIncluded>';

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
                            
                        }else{
                            mysqli_query($dblink, "
                            INSERT INTO 
                            qb_error_log (Module, Msg) VALUES ('CreditMemoAddToQB', 'Insufficient quantity entered for item line')");
                        }
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

    echo $qbxml;
}

if(isset($_GET['action']) && $_GET['action']=='po_rcpt'){
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
    echo $qbxml;
}
?>