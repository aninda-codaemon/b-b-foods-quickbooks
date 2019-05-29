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

$sql = "SELECT * FROM `qb_example_customer`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0){
    while ($row = mysqli_fetch_array($query))
    {
        //echo $row['ListID'].'<br>';
        $sql_row_tmp = "SELECT * FROM `qb_example_customer` where `ListID` = '".$row['ListID']."'";	
        $query_row_tmp = mysqli_query($dblink, $sql_row_tmp);
        if(mysqli_num_rows($query_row_tmp) > 0){
            $result_row_tmp = mysqli_fetch_assoc($query_row_tmp);
            
            $sql_row_main = "SELECT * FROM `qb_customer` where `ListID` = '".$result_row_tmp['ListID']."'";	
            $query_row_main = mysqli_query($dblink, $sql_row_main);
            if(mysqli_num_rows($query_row_main) > 0){
                $result_row_main = mysqli_fetch_assoc($query_row_main);

                //do the compare
                $diff = array_diff($result_row_tmp, $result_row_main);
                if(!empty($diff)){
                    //update qb_customer
                    //echo $result_row_main['ListID'];
                    //echo '<pre>';
                    //print_r($diff);
                    foreach($diff as $key=>$val)
                    {
                        //echo "UPDATE qb_customer SET ". $key . " = '". $val ."' WHERE `ListID` = '".$result_row_tmp['ListID']."'";
                        mysqli_query($dblink, "
                        UPDATE qb_customer SET ". $key . " = '". $val ."' WHERE `ListID` = '".$result_row_tmp['ListID']."'");
                    }
                }else{
                    //echo 'equal' ;
                }
            }else{
                //insert into qb_customer
                mysqli_query($dblink, "
                    REPLACE INTO
                    qb_customer
                    (
                        " . implode(", ", array_keys($result_row_tmp)) . "
                    ) VALUES (
                        '" . implode("', '", array_values($result_row_tmp)) . "'
                    )");
            }
        }
        
    }
    
}

