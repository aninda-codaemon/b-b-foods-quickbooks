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

$sql = "SELECT * FROM `qb_example_creditmemo`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0) {
    $iteration = 1;

    while ($row = mysqli_fetch_array($query)) {
        //echo "TxnID : ".$row['TxnID']." <br><br>";
        
        $sql_row_maintbl = "SELECT * FROM `qb_creditmemo` where `RefNumber` = '".$row['RefNumber']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_creditmemo` where `RefNumber` = '".$row['RefNumber']."'";	
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
                    UPDATE qb_creditmemo SET ". $key . " = '". $val ."' WHERE `RefNumber` = '".$result_row_tmptbl['RefNumber']."'");
                }
            }else{
                //echo "equal : iteration - $iteration <br><br>" ;
            }

            // Remove related data with invoice table
            mysqli_query($dblink, "
                DELETE FROM qb_creditmemo_creditmemoline WHERE CreditMemo_TxnID = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'
            "); //or die(trigger_error(mysql_error()));

        }else{
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            
            mysqli_query($dblink, "
                INSERT INTO
                qb_creditmemo
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");
        }

        $sql_row_tmptbl_poline = "
            SELECT * FROM `qb_example_creditmemo_creditmemoline` where `CreditMemo_TxnID` = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['TxnID']) . "'";	
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
                    qb_creditmemo_creditmemoline
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

