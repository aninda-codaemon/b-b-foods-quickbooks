<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
// Require the framework
require_once 'QuickBooks.php';

$dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

$sql = "SELECT * FROM `qb_example_invoice`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0) {
    $iteration = 1;

    while ($row = mysqli_fetch_array($query)) {
        mysqli_query($dblink, "
                INSERT INTO
                qb_error_log
                (`ListID`, `Module`, `Msg`) VALUES ('".$row['RefNumber']."', 'datatransferinvoice', 'cron test')");

        //echo "TxnID : ".$row['TxnID']." <br><br>";
        
        $sql_row_maintbl = "SELECT * FROM `qb_invoice` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_invoice` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_tmptbl = mysqli_query($dblink, $sql_row_tmptbl);
        $result_row_tmptbl = mysqli_fetch_assoc($query_row_tmptbl);

        if(mysqli_num_rows($query_row_maintbl) > 0){
            $result_row_maintbl = mysqli_fetch_assoc($query_row_maintbl);

            //do the compare
            $diff = array_diff($result_row_tmptbl, $result_row_maintbl);
            if(!empty($diff)){
                //update qb_purchaseorder
                //echo $result_row_maintbl['TxnID'];
                //echo '<pre>';
                //print_r($diff);
                //echo "update into qb_purchaseorder : iteration - $iteration <br><br>";
                foreach($diff as $key=>$val)
                {
                    $val =  mysqli_real_escape_string($dblink, $val);
                    mysqli_query($dblink, "
                    UPDATE qb_invoice SET ". $key . " = '". $val ."' WHERE `RefNumber` = '".$result_row_tmptbl['RefNumber']."'");
                }
            }else{
                //echo "equal : iteration - $iteration <br><br>" ;
            }

            // Remove related data with invoice table
            mysqli_query($dblink, "
                DELETE FROM qb_invoice_invoiceline WHERE Invoice_TxnID = '" . mysqli_real_escape_string($dblink, $row['TxnID']) . "'
            "); //or die(trigger_error(mysql_error()));

        }else{
            //echo "insert into qb_purchaseorder : iteration - $iteration <br><br>";
            
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            
            mysqli_query($dblink, "
                INSERT INTO
                qb_invoice
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");
        }

        $sql_row_tmptbl_poline = "
            SELECT * FROM `qb_example_invoice_invoiceline` where `Invoice_TxnID` = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'";	
        $query_row_tmptbl_poline = mysqli_query($dblink, $sql_row_tmptbl_poline);
        if(mysqli_num_rows($query_row_tmptbl_poline) > 0) {
            while ($result_row_tmptbl_poline = mysqli_fetch_assoc($query_row_tmptbl_poline)) {
                //echo "insert into qb_purchaseorder_purchaseorderline : iteration - $iteration <br><br>";
            
                foreach ($result_row_tmptbl_poline as $keyli => $valueli)
                {
                    $result_row_tmptbl_poline[$keyli] = mysqli_real_escape_string($dblink, $valueli);
                }
                
                mysqli_query($dblink, "
                    INSERT INTO
                    qb_invoice_invoiceline
                    (
                        " . implode(", ", array_keys($result_row_tmptbl_poline)) . "
                    ) VALUES (
                        '" . implode("', '", array_values($result_row_tmptbl_poline)) . "'
                    )");
            }
        }

        $iteration++;
    }
    
}

