<?php
ini_set('display_errors', true);
error_reporting(E_ALL | E_STRICT);
 
// Require the queueuing class
require_once 'QuickBooks.php';

//if (isset($_POST['customer'])) {
	$dsn = 'mysqli://root:@localhost/quickbooks_sqli';
	$Queue = new QuickBooks_WebConnector_Queue($dsn);
	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER);
//}

