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

$sql = "SELECT * FROM `qb_example_vendor`";	
$query = mysqli_query($dblink,$sql);

if(mysqli_num_rows($query) > 0){
    $iteration = 1;

    while ($row = mysqli_fetch_array($query))
    {
        mysqli_query($dblink, "
                INSERT INTO
                qb_error_log
                (`ListID`, `Module`, `Msg`) VALUES ('".$row['ListID']."', 'datatransfervendor', 'cron test')");
        
        //echo "ListID : ".$row['ListID']." <br><br>";

        $sql_row_maintbl = "SELECT * FROM `qb_vendor` where `ListID` = '".$row['ListID']."'";	
        $query_row_maintbl = mysqli_query($dblink, $sql_row_maintbl);

        $sql_row_tmptbl = "SELECT * FROM `qb_example_vendor` where `ListID` = '".$row['ListID']."'";	
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
                //echo "update into qb_vandor : iteration - $iteration <br><br>";
                foreach($diff as $key=>$val)
                {
                    $val =  mysqli_real_escape_string($dblink, $val);
                    mysqli_query($dblink, "
                    UPDATE qb_vendor SET ". $key . " = '". $val ."' WHERE `ListID` = '".$result_row_tmptbl['ListID']."'");
                }
            }else{
                //echo "equal : iteration - $iteration <br><br>" ;
            }
        }else{
            //echo "insert into qb_vendor : iteration - $iteration <br><br>";
            
            foreach ($result_row_tmptbl as $key => $value)
			{
				$result_row_tmptbl[$key] = mysqli_real_escape_string($dblink, $value);
            }
            mysqli_query($dblink, "
                INSERT INTO
                qb_vendor
                (
                    " . implode(", ", array_keys($result_row_tmptbl)) . "
                ) VALUES (
                    '" . implode("', '", array_values($result_row_tmptbl)) . "'
                )");
        }
        $iteration++;
    }
}

