<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Require the framework
require_once 'QuickBooks.php';

$dblink = mysqli_connect("localhost", "root", "", "quickbooks_sqli");

$sql = "SELECT * FROM `qb_example_salesorder`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0) {
    $iteration = 1;

    while ($row = mysqli_fetch_array($query)) {
        //echo "TxnID : ".$row['TxnID']." <br><br>";
        
        $sql_row_maintbl = "SELECT * FROM `qb_salesorder` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_salesorder` where `RefNumber` = '".$row['RefNumber']."'";	
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
                //echo "update into qb_salesorder : iteration - $iteration <br><br>";
                foreach($diff as $key=>$val)
                {
                    $val =  mysqli_real_escape_string($dblink, $val);
                    mysqli_query($dblink, "
                    UPDATE qb_salesorder SET ". $key . " = '". $val ."' WHERE `RefNumber` = '".$result_row_tmptbl['RefNumber']."'");
                }
            }

            // SO line items
            $sql_rows_sol_tmptbl = "SELECT `TxnLineID` FROM `qb_example_salesorder_salesorderline` where `SalesOrder_TxnID` = '".$row['TxnID']."'";	
            $query_rows_sol_tmptbl = mysqli_query($dblink, $sql_rows_sol_tmptbl);
            $count_sol_tmptbl = mysqli_num_rows($query_rows_sol_tmptbl);

            if($count_sol_tmptbl > 0){
                $sql_rows_sol_maintbl = "SELECT `TxnLineID` FROM `qb_salesorder_salesorderline` where `SalesOrder_TxnID` = '".$row['TxnID']."'";	
                $query_rows_sol_maintbl = mysqli_query($dblink, $sql_rows_sol_maintbl);
                $count_sol_maintbl = mysqli_num_rows($query_rows_sol_maintbl);

                if($count_sol_tmptbl == $count_sol_maintbl){
                    while ($row_sol_tmptbl = mysqli_fetch_array($query_rows_sol_tmptbl)) {
                        $sql_row_sol_tmptbl = "SELECT Item_ListID, Item_FullName, Descrip, UnitOfMeasure, OverrideUOMSet_ListID, OverrideUOMSet_FullName, Rate, RatePercent, Class_ListID, Class_FullName, Amount, InventorySite_ListID, InventorySite_FullName, SerialNumber, LotNumber, SalesTaxCode_ListID, SalesTaxCode_FullName, Invoiced, IsManuallyClosed, Other1, Other2 FROM `qb_example_salesorder_salesorderline` where `TxnLineID` = '".$row_sol_tmptbl['TxnLineID']."'";	
                        $query_row_sol_tmptbl = mysqli_query($dblink, $sql_row_sol_tmptbl);
                        $result_row_sol_tmptbl = mysqli_fetch_assoc($query_row_sol_tmptbl);

                        $sql_row_sol_maintbl = "SELECT Item_ListID, Item_FullName, Descrip, UnitOfMeasure, OverrideUOMSet_ListID, OverrideUOMSet_FullName, Rate, RatePercent, Class_ListID, Class_FullName, Amount, InventorySite_ListID, InventorySite_FullName, SerialNumber, LotNumber, SalesTaxCode_ListID, SalesTaxCode_FullName, Invoiced, IsManuallyClosed, Other1, Other2 FROM `qb_salesorder_salesorderline` where `TxnLineID` = '".$row_sol_tmptbl['TxnLineID']."'";	
                        $query_row_sol_maintbl = mysqli_query($dblink, $sql_row_sol_maintbl);
                        $result_row_sol_maintbl = mysqli_fetch_assoc($query_row_sol_maintbl);
                        
                        //do the compare
                        $diff_sol = array_diff($result_row_sol_tmptbl, $result_row_sol_maintbl);
                        if(!empty($diff_sol)){
                            foreach($diff_sol as $key_sol=>$val_sol)
                            {
                                $val_sol =  mysqli_real_escape_string($dblink, $val_sol);
                                mysqli_query($dblink, "
                                UPDATE qb_salesorder_salesorderline SET ". $key_sol . " = '". $val_sol ."' WHERE `TxnLineID` = '".$row_sol_tmptbl['TxnLineID']."'");
                            }
                        }
                    }
                }else if($count_sol_tmptbl > $count_sol_maintbl){
                    $sql_new_sol_tmptbl = "SELECT * FROM `qb_example_salesorder_salesorderline` WHERE `SalesOrder_TxnID` = '".$row['TxnID']."' AND `TxnLineID` NOT IN(SELECT `TxnLineID` FROM `qb_salesorder_salesorderline` WHERE `SalesOrder_TxnID` = '".$row['TxnID']."')";
                    $query_new_sol_tmptbl = mysqli_query($dblink,$sql_new_sol_tmptbl);
                    if(mysqli_num_rows($query_new_sol_tmptbl) > 0) {
                        while ($row_new_sol_tmptbl = mysqli_fetch_assoc($query_new_sol_tmptbl)) {
                            foreach ($row_new_sol_tmptbl as $key_sol => $value_sol)
                            {
                                $result_row_tmptbl_sol[$key_sol] = mysqli_real_escape_string($dblink, $value_sol);
                            }
                            
                            mysqli_query($dblink, "
                            INSERT INTO
                            qb_salesorder_salesorderline
                            (
                                " . implode(", ", array_keys($result_row_tmptbl_sol)) . "
                            ) VALUES (
                                '" . implode("', '", array_values($result_row_tmptbl_sol)) . "'
                            )");
                        }
                    }
                }else if($count_sol_tmptbl < $count_sol_maintbl){
                    $sql_new_sol_tmptbl = "SELECT TxnLineID FROM `qb_salesorder_salesorderline` WHERE `SalesOrder_TxnID` = '".$row['TxnID']."' AND `TxnLineID` NOT IN(SELECT `TxnLineID` FROM `qb_example_salesorder_salesorderline` WHERE `SalesOrder_TxnID` = '".$row['TxnID']."')";
                    $query_new_sol_tmptbl = mysqli_query($dblink,$sql_new_sol_tmptbl);
                    if(mysqli_num_rows($query_new_sol_tmptbl) > 0) {
                        while ($row_new_sol_tmptbl = mysqli_fetch_assoc($query_new_sol_tmptbl)) {
                            mysqli_query($dblink, "
                            DELETE FROM qb_salesorder_salesorderline WHERE TxnLineID = '".$row_new_sol_tmptbl['TxnLineID']."'");
                        }
                    }
                }
            }
            // Remove related data with SO table
            /*mysqli_query($dblink, "
                DELETE FROM qb_salesorder_salesorderline WHERE SalesOrder_TxnID = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'
            ");*/ //or die(trigger_error(mysql_error()));

        }else{
            //echo "insert into qb_salesorder : iteration - $iteration <br><br>";
            
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            
            mysqli_query($dblink, "
                INSERT INTO
                qb_salesorder
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");

            // Insert related data with SO table
            $sql_row_tmptbl_soline = "
            SELECT * FROM `qb_example_salesorder_salesorderline` where `SalesOrder_TxnID` = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'";	
            $query_row_tmptbl_soline = mysqli_query($dblink, $sql_row_tmptbl_soline);
            if(mysqli_num_rows($query_row_tmptbl_soline) > 0) {
                while ($result_row_tmptbl_soline = mysqli_fetch_assoc($query_row_tmptbl_soline)) {
                    //echo "insert into qb_salesorder_salesorderline : iteration - $iteration <br><br>";
                
                    foreach ($result_row_tmptbl_soline as $keyli => $valueli)
                    {
                        $result_row_tmptbl_soline[$keyli] = mysqli_real_escape_string($dblink, $valueli);
                    }
                    
                    mysqli_query($dblink, "
                        INSERT INTO
                        qb_salesorder_salesorderline
                        (
                            " . implode(", ", array_keys($result_row_tmptbl_soline)) . "
                        ) VALUES (
                            '" . implode("', '", array_values($result_row_tmptbl_soline)) . "'
                        )");
                }
            }
        }
        $iteration++;
    }
    
}

