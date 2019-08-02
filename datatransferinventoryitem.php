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

$sql = "SELECT * FROM `qb_example_iteminventory`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0){
    $iteration = 1;

    while ($row = mysqli_fetch_array($query))
    {
        //echo "ListID : ".$row['ListID']." <br><br>";

        $sql_row_maintbl = "SELECT * FROM `qb_iteminventory` where `ListID` = '".$row['ListID']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_iteminventory` where `ListID` = '".$row['ListID']."'";	
        $query_row_tmptbl = mysqli_query($dblink, $sql_row_tmptbl);
        $result_row_tmptbl = mysqli_fetch_assoc($query_row_tmptbl);

        if(mysqli_num_rows($query_row_maintbl) > 0){
            $result_row_maintbl = mysqli_fetch_assoc($query_row_maintbl);

            //do the compare
            $diff = array_diff($result_row_tmptbl, $result_row_maintbl);
            if(!empty($diff)){
                //update qb_vendor
                //echo $result_row_main['ListID'];
                //echo '<pre>';
                //print_r($diff);
                //echo "update into qb_iteminventory : iteration - $iteration <br><br>";
                foreach($diff as $key=>$val)
                {
                    $val =  mysqli_real_escape_string($dblink, $val);
                    mysqli_query($dblink, "
                    UPDATE qb_iteminventory SET ". $key . " = '". $val ."' WHERE `ListID` = '".$result_row_tmptbl['ListID']."'");
                }
            }else{
                //echo "equal : iteration - $iteration <br><br>" ;
            }

            mysqli_query($dblink, "
                DELETE FROM qb_example_iteminventory_dataext WHERE ListID = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['ListID']) . "'
            "); 
        }else{
            //echo "insert into qb_vendor : iteration - $iteration <br><br>";
            
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            mysqli_query($dblink, "
                INSERT INTO
                qb_iteminventory
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");
        }

        // Insert related data with iteminventory table
        $sql_row_tmptbl_dataext = "
            SELECT * FROM `qb_example_iteminventory_dataext` where `ListID` = '" . mysqli_real_escape_string($dblink, $result_row_tmptbl['ListID']) . "'";	
        $query_row_tmptbl_dataext = mysqli_query($dblink, $sql_row_tmptbl_dataext);
        if(mysqli_num_rows($query_row_tmptbl_dataext) > 0) {
            while ($result_row_tmptbl_dataext = mysqli_fetch_assoc($query_row_tmptbl_dataext)) {
                //echo "insert into qb_iteminventory_dataext : iteration - $iteration <br><br>";
            
                foreach ($result_row_tmptbl_dataext as $keyde => $valuede)
                {
                    $result_row_tmptbl_dataext[$keyde] = mysqli_real_escape_string($dblink, $valuede);
                }
                
                mysqli_query($dblink, "
                    INSERT INTO
                    qb_iteminventory_dataext
                    (
                        " . implode(", ", array_keys($result_row_tmptbl_dataext)) . "
                    ) VALUES (
                        '" . implode("', '", array_values($result_row_tmptbl_dataext)) . "'
                    )");
            }
        }

        $iteration++;
        
    }
    
}

