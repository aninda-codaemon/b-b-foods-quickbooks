<?php
// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

//set the maximum execution time to infinite for bulk data
ini_set('max_execution_time', 0);

// Require the framework
require_once 'QuickBooks.php';

// Require the neccessary db connection
require_once 'IncludesForDB.php';

$dblink = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

$sql = "SELECT * FROM `qb_example_purchaseorder`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0) {
    $iteration = 1;

    while ($row = mysqli_fetch_array($query)) {
        //echo "TxnID : ".$row['TxnID']." <br>";
        
        $sql_row_maintbl = "SELECT * FROM `qb_purchaseorder` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_purchaseorder` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_tmptbl = mysqli_query($dblink, $sql_row_tmptbl);
        $result_row_tmptbl = mysqli_fetch_assoc($query_row_tmptbl);

        if(mysqli_num_rows($query_row_maintbl) > 0){
            echo "update into qb_purchaseorder : iteration - $iteration <br><br>";
            $result_row_maintbl = mysqli_fetch_assoc($query_row_maintbl);

            //do the compare
            $diff = array_diff($result_row_tmptbl, $result_row_maintbl);
            if(!empty($diff)){
                //update qb_purchaseorder
                //echo $result_row_maintbl['TxnID'];
                //echo '<pre>';
                //print_r($diff);
                foreach($diff as $key=>$val)
                {
                    $val =  mysqli_real_escape_string($dblink, $val);
                    mysqli_query($dblink, "
                    UPDATE qb_purchaseorder SET ". $key . " = '". $val ."' WHERE `RefNumber` = '".$result_row_tmptbl['RefNumber']."'");
                }
            }

            // PO line items
            $sql_rows_pol_tmptbl = "SELECT `TxnLineID` FROM `qb_example_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '".$row['TxnID']."'";	
            $query_rows_pol_tmptbl = mysqli_query($dblink, $sql_rows_pol_tmptbl);
            $count_pol_tmptbl = mysqli_num_rows($query_rows_pol_tmptbl);

            if($count_pol_tmptbl > 0){
                $sql_rows_pol_maintbl = "SELECT `TxnLineID` FROM `qb_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '".$row['TxnID']."'";	
                $query_rows_pol_maintbl = mysqli_query($dblink, $sql_rows_pol_maintbl);
                $count_pol_maintbl = mysqli_num_rows($query_rows_pol_maintbl);

                if($count_pol_tmptbl == $count_pol_maintbl){
                    while ($row_pol_tmptbl = mysqli_fetch_array($query_rows_pol_tmptbl)) {
                        $sql_row_pol_tmptbl = "SELECT Item_ListID, Item_FullName, ManufacturerPartNumber, Descrip, UnitOfMeasure, OverrideUOMSet_ListID, OverrideUOMSet_FullName, Rate, Class_ListID, Class_FullName, Amount, Customer_ListID, Customer_FullName, ServiceDate, ReceivedQuantity, IsManuallyClosed, Other1, Other2 FROM `qb_example_purchaseorder_purchaseorderline` where `TxnLineID` = '".$row_pol_tmptbl['TxnLineID']."'";	
                        $query_row_pol_tmptbl = mysqli_query($dblink, $sql_row_pol_tmptbl);
                        $result_row_pol_tmptbl = mysqli_fetch_assoc($query_row_pol_tmptbl);

                        $sql_row_pol_maintbl = "SELECT Item_ListID, Item_FullName, ManufacturerPartNumber, Descrip, UnitOfMeasure, OverrideUOMSet_ListID, OverrideUOMSet_FullName, Rate, Class_ListID, Class_FullName, Amount, Customer_ListID, Customer_FullName, ServiceDate, ReceivedQuantity, IsManuallyClosed, Other1, Other2 FROM `qb_purchaseorder_purchaseorderline` where `TxnLineID` = '".$row_pol_tmptbl['TxnLineID']."'";	
                        $query_row_pol_maintbl = mysqli_query($dblink, $sql_row_pol_maintbl);
                        $result_row_pol_maintbl = mysqli_fetch_assoc($query_row_pol_maintbl);
                        
                        //do the compare
                        $diff_pol = array_diff($result_row_pol_tmptbl, $result_row_pol_maintbl);
                        if(!empty($diff_pol)){
                            foreach($diff_pol as $key_pol=>$val_pol)
                            {
                                $val_pol =  mysqli_real_escape_string($dblink, $val_pol);
                                mysqli_query($dblink, "
                                UPDATE qb_purchaseorder_purchaseorderline SET ". $key_pol . " = '". $val_pol ."' WHERE `TxnLineID` = '".$row_pol_tmptbl['TxnLineID']."'");
                            }
                        }
                    }
                }else if($count_pol_tmptbl > $count_pol_maintbl){
                    $sql_new_pol_tmptbl = "SELECT * FROM `qb_example_purchaseorder_purchaseorderline` WHERE `PurchaseOrder_TxnID` = '".$row['TxnID']."' AND `TxnLineID` NOT IN(SELECT `TxnLineID` FROM `qb_purchaseorder_purchaseorderline` WHERE `PurchaseOrder_TxnID` = '".$row['TxnID']."')";
                    $query_new_pol_tmptbl = mysqli_query($dblink,$sql_new_pol_tmptbl);
                    if(mysqli_num_rows($query_new_pol_tmptbl) > 0) {
                        while ($row_new_pol_tmptbl = mysqli_fetch_assoc($query_new_pol_tmptbl)) {
                            foreach ($row_new_pol_tmptbl as $key_pol => $value_pol)
                            {
                                $result_row_tmptbl_pol[$key_pol] = mysqli_real_escape_string($dblink, $value_pol);
                            }
                            
                            mysqli_query($dblink, "
                            INSERT INTO
                            qb_purchaseorder_purchaseorderline
                            (
                                " . implode(", ", array_keys($result_row_tmptbl_pol)) . "
                            ) VALUES (
                                '" . implode("', '", array_values($result_row_tmptbl_pol)) . "'
                            )");
                        }
                    }
                }else if($count_pol_tmptbl < $count_pol_maintbl){
                    $sql_new_pol_tmptbl = "SELECT TxnLineID FROM `qb_purchaseorder_purchaseorderline` WHERE `PurchaseOrder_TxnID` = '".$row['TxnID']."' AND `TxnLineID` NOT IN(SELECT `TxnLineID` FROM `qb_example_purchaseorder_purchaseorderline` WHERE `PurchaseOrder_TxnID` = '".$row['TxnID']."')";
                    $query_new_pol_tmptbl = mysqli_query($dblink,$sql_new_pol_tmptbl);
                    if(mysqli_num_rows($query_new_pol_tmptbl) > 0) {
                        while ($row_new_pol_tmptbl = mysqli_fetch_assoc($query_new_pol_tmptbl)) {
                            mysqli_query($dblink, "
                            DELETE FROM qb_purchaseorder_purchaseorderline WHERE TxnLineID = '".$row_new_pol_tmptbl['TxnLineID']."'");
                        }
                    }
                }
            }
            // Remove related data with PO table

            /*mysqli_query($dblink, "
                DELETE FROM qb_purchaseorder_purchaseorderline WHERE PurchaseOrder_TxnID = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'
            ");*/ //or die(trigger_error(mysql_error()));
        }else{
            //echo "insert into qb_purchaseorder : iteration - $iteration <br><br>";
            
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            
            mysqli_query($dblink, "
                INSERT INTO
                qb_purchaseorder
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");

            // Insert related data with PO table
            $sql_row_tmptbl_poline = "
            SELECT * FROM `qb_example_purchaseorder_purchaseorderline` where `PurchaseOrder_TxnID` = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'";	
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
                        qb_purchaseorder_purchaseorderline
                        (
                            " . implode(", ", array_keys($result_row_tmptbl_poline)) . "
                        ) VALUES (
                            '" . implode("', '", array_values($result_row_tmptbl_poline)) . "'
                        )");
                }
            }
        }

        //echo "<br><br>";

        $iteration++;
    }
    
}