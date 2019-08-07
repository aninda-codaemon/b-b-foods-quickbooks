<?php
ini_set('display_errors', true);
error_reporting(E_ALL | E_STRICT);
 
// Require the queueuing class
require_once 'QuickBooks.php';

// Require the neccessary variables/methods
require_once 'IncludesForDB.php';

$Queue = new QuickBooks_WebConnector_Queue(QB_QUICKBOOKS_DSN);
$Queue->enqueue(QUICKBOOKS_ADD_INVOICE);

