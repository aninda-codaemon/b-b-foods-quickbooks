-- phpMyAdmin SQL Dump
-- version 2.11.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 28, 2009 at 07:35 AM
-- Server version: 5.0.67
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: 'quickbooks_import'
--

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_customer'
--

CREATE TABLE IF NOT EXISTS qb_example_customer (
  `ListID` varchar(40) NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `TimeModified` datetime NOT NULL,
  `EditSequence` text,
  `Name` varchar(50) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `IsActive` tinyint(1) DEFAULT '0',
  `Parent_ListID` varchar(40) DEFAULT NULL,
  `Parent_FullName` varchar(255) DEFAULT NULL,
  `Sublevel` int(10) UNSIGNED DEFAULT '0',
  `CompanyName` varchar(41) DEFAULT NULL,
  `Salutation` varchar(15) DEFAULT NULL, 
  `FirstName` varchar(25) DEFAULT NULL,
  `MiddleName` varchar(5) DEFAULT NULL,
  `LastName` varchar(25) DEFAULT NULL,
  `BillAddress_Addr1` varchar(41) DEFAULT NULL,
  `BillAddress_Addr2` varchar(41) DEFAULT NULL,
  `BillAddress_Addr3` varchar(41) DEFAULT NULL,
  `BillAddress_Addr4` varchar(41) DEFAULT NULL,
  `BillAddress_Addr5` varchar(41) DEFAULT NULL,
  `BillAddress_City` varchar(31) DEFAULT NULL,
  `BillAddress_State` varchar(21) DEFAULT NULL,
  `BillAddress_PostalCode` varchar(13) DEFAULT NULL,
  `BillAddress_Country` varchar(31) DEFAULT NULL,
  `BillAddress_Note` varchar(41) DEFAULT NULL,
  `BillAddressBlock_Addr1` text,
  `BillAddressBlock_Addr2` text,
  `BillAddressBlock_Addr3` text,
  `BillAddressBlock_Addr4` text,
  `BillAddressBlock_Addr5` text,
  `ShipAddress_Addr1` varchar(41) DEFAULT NULL,
  `ShipAddress_Addr2` varchar(41) DEFAULT NULL,
  `ShipAddress_Addr3` varchar(41) DEFAULT NULL,
  `ShipAddress_Addr4` varchar(41) DEFAULT NULL,
  `ShipAddress_Addr5` varchar(41) DEFAULT NULL,
  `ShipAddress_City` varchar(31) DEFAULT NULL,
  `ShipAddress_State` varchar(21) DEFAULT NULL,
  `ShipAddress_PostalCode` varchar(13) DEFAULT NULL,
  `ShipAddress_Country` varchar(31) DEFAULT NULL,
  `ShipAddress_Note` varchar(41) DEFAULT NULL,
  `ShipAddressBlock_Addr1` text,
  `ShipAddressBlock_Addr2` text,
  `ShipAddressBlock_Addr3` text,
  `ShipAddressBlock_Addr4` text,
  `ShipAddressBlock_Addr5` text,
  `Phone` varchar(21) DEFAULT NULL,
  `AltPhone` varchar(21) DEFAULT NULL,
  `Fax` varchar(21) DEFAULT NULL,
  `Email` text,
  `AltEmail` text,
  `Contact` varchar(41) DEFAULT NULL,
  `AltContact` varchar(41) DEFAULT NULL,
  `CustomerType_ListID` varchar(40) DEFAULT NULL,
  `CustomerType_FullName` varchar(255) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `Balance` decimal(10,2) DEFAULT NULL,
  `TotalBalance` decimal(10,2) DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `ResaleNumber` varchar(15) DEFAULT NULL,
  `AccountNumber` varchar(99) DEFAULT NULL,
  `CreditLimit` decimal(10,2) DEFAULT NULL,
  `PreferredPaymentMethod_ListID` varchar(40) DEFAULT NULL,
  `PreferredPaymentMethod_FullName` varchar(255) DEFAULT NULL,
  `CreditCardInfo_CreditCardNumber` varchar(25) DEFAULT NULL,
  `CreditCardInfo_ExpirationMonth` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardInfo_ExpirationYear` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardInfo_NameOnCard` varchar(41) DEFAULT NULL,
  `CreditCardInfo_CreditCardAddress` varchar(41) DEFAULT NULL,
  `CreditCardInfo_CreditCardPostalCode` varchar(41) DEFAULT NULL,
  `JobStatus` varchar(40) DEFAULT NULL,
  `JobStartDate` date DEFAULT NULL,
  `JobProjectedEndDate` date DEFAULT NULL,
  `JobEndDate` date DEFAULT NULL,
  `JobDesc` varchar(99) DEFAULT NULL,
  `JobType_ListID` varchar(40) DEFAULT NULL,
  `JobType_FullName` varchar(255) DEFAULT NULL,
  `Notes` text,
  `PriceLevel_ListID` varchar(40) DEFAULT NULL,
  `PriceLevel_FullName` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (ListID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_estimate'
--

CREATE TABLE IF NOT EXISTS qb_example_estimate (
  TxnID varchar(40) NOT NULL,
  TimeCreated datetime NOT NULL,
  TimeModified datetime NOT NULL,
  RefNumber varchar(16) NOT NULL,
  Customer_ListID varchar(40) NOT NULL,
  Customer_FullName varchar(255) NOT NULL,
  ShipAddress_Addr1 varchar(50) NOT NULL,
  ShipAddress_Addr2 varchar(50) NOT NULL,
  ShipAddress_City varchar(50) NOT NULL,
  ShipAddress_State varchar(25) NOT NULL,
  ShipAddress_Province varchar(25) NOT NULL,
  ShipAddress_PostalCode varchar(16) NOT NULL,
  BalanceRemaining float NOT NULL,
  PRIMARY KEY  (TxnID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_estimate_lineitem'
--

CREATE TABLE IF NOT EXISTS qb_example_estimate_lineitem (
  TxnID varchar(40) NOT NULL,
  TxnLineID varchar(40) NOT NULL,
  Item_ListID varchar(40) NOT NULL,
  Item_FullName varchar(255) NOT NULL,
  Descrip text NOT NULL,
  Quantity int(10) unsigned NOT NULL,
  Rate float NOT NULL,
  PRIMARY KEY  (TxnID,TxnLineID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_invoice'
--

CREATE TABLE IF NOT EXISTS qb_example_invoice (
  TxnID varchar(40) NOT NULL,
  TimeCreated datetime NOT NULL,
  TimeModified datetime NOT NULL,
  RefNumber varchar(16) NOT NULL,
  Customer_ListID varchar(40) NOT NULL,
  Customer_FullName varchar(255) NOT NULL,
  ShipAddress_Addr1 varchar(50) NOT NULL,
  ShipAddress_Addr2 varchar(50) NOT NULL,
  ShipAddress_City varchar(50) NOT NULL,
  ShipAddress_State varchar(25) NOT NULL,
  ShipAddress_Province varchar(25) NOT NULL,
  ShipAddress_PostalCode varchar(16) NOT NULL,
  BalanceRemaining float NOT NULL,
  PRIMARY KEY  (TxnID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_invoice_lineitem'
--

CREATE TABLE IF NOT EXISTS qb_example_invoice_lineitem (
  TxnID varchar(40) NOT NULL,
  TxnLineID varchar(40) NOT NULL,
  Item_ListID varchar(40) NOT NULL,
  Item_FullName varchar(255) NOT NULL,
  Descrip text NOT NULL,
  Quantity int(10) unsigned NOT NULL,
  Rate float NOT NULL,
  PRIMARY KEY  (TxnID,TxnLineID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------


--
-- Table structure for table 'qb_example_item'
--

CREATE TABLE IF NOT EXISTS qb_example_item (
  `ListID` varchar(40) NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `TimeModified` datetime NOT NULL,
  `Name` varchar(50) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `Type` varchar(40) NOT NULL,
  `Parent_ListID` varchar(40) NOT NULL,
  `Parent_FullName` varchar(255) NOT NULL,
  `ManufacturerPartNumber` varchar(40) NOT NULL,
  `SalesTaxCode_ListID` varchar(40) NOT NULL,
  `SalesTaxCode_FullName` varchar(255) NOT NULL,
  `BuildPoint` varchar(40) NOT NULL,
  `ReorderPoint` varchar(40) NOT NULL,
  `QuantityOnHand` int(10) unsigned NOT NULL,
  `AverageCost` float NOT NULL,
  `QuantityOnOrder` int(10) unsigned NOT NULL,
  `QuantityOnSalesOrder` int(10) unsigned NOT NULL,
  `TaxRate` varchar(40) NOT NULL,
  `SalesPrice` float NOT NULL,
  `SalesDesc` text NOT NULL,
  `PurchaseCost` float NOT NULL,
  `PurchaseDesc` text NOT NULL,
  `PrefVendor_ListID` varchar(40) NOT NULL,
  `PrefVendor_FullName` varchar(255) NOT NULL,
  PRIMARY KEY  (`ListID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `qb_example_iteminventory`
--

CREATE TABLE `qb_example_iteminventory` (
  `ListID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `Name` varchar(31) DEFAULT NULL,
  `FullName` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '0',
  `Parent_ListID` varchar(40) DEFAULT NULL,
  `Parent_FullName` varchar(255) DEFAULT NULL,
  `Sublevel` int(10) UNSIGNED DEFAULT '0',
  `ManufacturerPartNumber` varchar(31) DEFAULT NULL,
  `UnitOfMeasureSet_ListID` varchar(40) DEFAULT NULL,
  `UnitOfMeasureSet_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `SalesDesc` text,
  `SalesPrice` decimal(13,5) DEFAULT NULL,
  `IncomeAccount_ListID` varchar(40) DEFAULT NULL,
  `IncomeAccount_FullName` varchar(255) DEFAULT NULL,
  `PurchaseDesc` text,
  `PurchaseCost` decimal(13,5) DEFAULT NULL,
  `COGSAccount_ListID` varchar(40) DEFAULT NULL,
  `COGSAccount_FullName` varchar(255) DEFAULT NULL,
  `PrefVendor_ListID` varchar(40) DEFAULT NULL,
  `PrefVendor_FullName` varchar(255) DEFAULT NULL,
  `AssetAccount_ListID` varchar(40) DEFAULT NULL,
  `AssetAccount_FullName` varchar(255) DEFAULT NULL,
  `ReorderPoint` decimal(12,5) DEFAULT '0.00000',
  `QuantityOnHand` decimal(12,5) DEFAULT '0.00000',
  `AverageCost` decimal(13,5) DEFAULT NULL,
  `QuantityOnOrder` decimal(12,5) DEFAULT '0.00000',
  `QuantityOnSalesOrder` decimal(12,5) DEFAULT '0.00000',
  PRIMARY KEY  (`ListID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_salesorder'
--

CREATE TABLE IF NOT EXISTS qb_example_salesorder (
  TxnID varchar(40) NOT NULL,
  TimeCreated datetime NOT NULL,
  TimeModified datetime NOT NULL,
  RefNumber varchar(16) NOT NULL,
  Customer_ListID varchar(40) NOT NULL,
  Customer_FullName varchar(255) NOT NULL,
  ShipAddress_Addr1 varchar(50) NOT NULL,
  ShipAddress_Addr2 varchar(50) NOT NULL,
  ShipAddress_City varchar(50) NOT NULL,
  ShipAddress_State varchar(25) NOT NULL,
  ShipAddress_Province varchar(25) NOT NULL,
  ShipAddress_PostalCode varchar(16) NOT NULL,
  BalanceRemaining float NOT NULL,
  PRIMARY KEY  (TxnID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_salesorder_lineitem'
--

CREATE TABLE IF NOT EXISTS qb_example_salesorder_lineitem (
  TxnID varchar(40) NOT NULL,
  TxnLineID varchar(40) NOT NULL,
  Item_ListID varchar(40) NOT NULL,
  Item_FullName varchar(255) NOT NULL,
  Descrip text NOT NULL,
  Quantity int(10) unsigned NOT NULL,
  Rate float NOT NULL,
  PRIMARY KEY  (TxnID,TxnLineID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_vendor'
--

CREATE TABLE IF NOT EXISTS qb_example_vendor (
  `ListID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `Name` varchar(41) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '0',
  `CompanyName` varchar(41) DEFAULT NULL,
  `Salutation` varchar(15) DEFAULT NULL,
  `FirstName` varchar(25) DEFAULT NULL,
  `MiddleName` varchar(5) DEFAULT NULL,
  `LastName` varchar(25) DEFAULT NULL,
  `VendorAddress_Addr1` varchar(41) DEFAULT NULL,
  `VendorAddress_Addr2` varchar(41) DEFAULT NULL,
  `VendorAddress_Addr3` varchar(41) DEFAULT NULL,
  `VendorAddress_Addr4` varchar(41) DEFAULT NULL,
  `VendorAddress_Addr5` varchar(41) DEFAULT NULL,
  `VendorAddress_City` varchar(31) DEFAULT NULL,
  `VendorAddress_State` varchar(21) DEFAULT NULL,
  `VendorAddress_PostalCode` varchar(13) DEFAULT NULL,
  `VendorAddress_Country` varchar(31) DEFAULT NULL,
  `VendorAddress_Note` varchar(41) DEFAULT NULL,
  `VendorAddressBlock_Addr1` text,
  `VendorAddressBlock_Addr2` text,
  `VendorAddressBlock_Addr3` text,
  `VendorAddressBlock_Addr4` text,
  `VendorAddressBlock_Addr5` text,
  `Phone` varchar(21) DEFAULT NULL,
  `AltPhone` varchar(21) DEFAULT NULL,
  `Fax` varchar(21) DEFAULT NULL,
  `Email` text,
  `Contact` varchar(41) DEFAULT NULL,
  `AltContact` varchar(41) DEFAULT NULL,
  `NameOnCheck` varchar(41) DEFAULT NULL,
  `AccountNumber` varchar(99) DEFAULT NULL,
  `Notes` text,
  `VendorType_ListID` varchar(40) DEFAULT NULL,
  `VendorType_FullName` varchar(255) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `CreditLimit` decimal(10,2) DEFAULT NULL,
  `VendorTaxIdent` varchar(15) DEFAULT NULL,
  `IsVendorEligibleFor1099` tinyint(1) DEFAULT '0',
  `Balance` decimal(10,2) DEFAULT NULL,
  `BillingRate_ListID` varchar(40) DEFAULT NULL,
  `BillingRate_FullName` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (ListID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------