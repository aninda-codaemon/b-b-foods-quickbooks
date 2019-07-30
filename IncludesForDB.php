<?php
define('DB_HOST', "localhost");
define('DB_USER', "root");
define('DB_PASS', "");
define('DB_DATABASE', "quickbooks_sqli");
/**
 * Configuration parameter for the database connection
 */
$dsn = 'mysqli://'.DB_USER.':@'.DB_HOST.'/'.DB_DATABASE;
define('QB_QUICKBOOKS_DSN', $dsn);

/**
 * A username and password you'll use in: 
 *	a) Your .QWC file
 *	b) The Web Connector
 *	c) The QuickBooks framework
 *
 * 	NOTE: This has *no relationship* with QuickBooks usernames, Windows usernames, etc. 
 * 	It is *only* used for the Web Connector and SOAP server! 
 */
define('QUICKBOOKS_USER', 'quickbooks');
define('QUICKBOOKS_PASS', 'password');