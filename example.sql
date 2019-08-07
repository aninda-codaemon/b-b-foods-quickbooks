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
-- Database: 'quickbooks_sqli'
--

-- ---------------------------------------------------------

-- --------------------------------------------------------
-- tables added by codaemon
-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_customer'
--

CREATE TABLE IF NOT EXISTS `qb_example_customer` (
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
-- Table structure for table 'qb_example_vendor'
--

CREATE TABLE IF NOT EXISTS `qb_example_vendor` (
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

--
-- Table structure for table `qb_example_iteminventory`
--

CREATE TABLE IF NOT EXISTS `qb_example_iteminventory` (
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
-- Table structure for table `qb_example_iteminventory_dataext`
--

CREATE TABLE IF NOT EXISTS `qb_iteminventory_dataext` (
  `ListID` varchar(40) DEFAULT NULL,
  `DataExtName` varchar(255) DEFAULT NULL,
  `DataExtType` varchar(255) DEFAULT NULL,
  `DataExtValue` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`ListID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `qb_example_iteminventory_dataext` (
  `ListID` varchar(40) DEFAULT NULL,
  `DataExtName` varchar(255) DEFAULT NULL,
  `DataExtType` varchar(255) DEFAULT NULL,
  `DataExtValue` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`ListID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_purchaseorder'
--

CREATE TABLE IF NOT EXISTS `qb_example_purchaseorder` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Vendor_ListID` varchar(40) DEFAULT NULL,
  `Vendor_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ShipToEntity_ListID` varchar(40) DEFAULT NULL,
  `ShipToEntity_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `ExpectedDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Currency_ListID` varchar(40) DEFAULT NULL,
  `Currency_FullName` varchar(255) DEFAULT NULL,
  `ExchangeRate` text,
  `TotalAmountInHomeCurrency` decimal(10,2) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `IsFullyReceived` tinyint(1) DEFAULT NULL,
  `Memo` text,
  `VendorMsg` varchar(99) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `Other1` varchar(25) DEFAULT NULL,
  `Other2` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `qb_example_purchaseorder_purchaseorderline` (
  `PurchaseOrder_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `ManufacturerPartNumber` text,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `ServiceDate` date DEFAULT NULL,
  `ReceivedQuantity` decimal(12,5) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table 'qb_example_salesorder'
--

CREATE TABLE IF NOT EXISTS `qb_example_salesorder` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `IsFullyInvoiced` tinyint(1) DEFAULT NULL,
  `Memo` text,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
);
-- --------------------------------------------------------

--
-- Table structure for table `qb_example_salesorder_salesorderline`
--

CREATE TABLE IF NOT EXISTS `qb_example_salesorder_salesorderline` (
  `SalesOrder_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `SerialNumber` text,
  `LotNumber` text,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Invoiced` decimal(12,5) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
);
-- --------------------------------------------------------

--
-- Table structure for table `qb_example_invoice`
--

CREATE TABLE IF NOT EXISTS `qb_example_invoice` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ARAccount_ListID` varchar(40) DEFAULT NULL,
  `ARAccount_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `IsPending` tinyint(1) DEFAULT NULL,
  `IsFinanceCharge` tinyint(1) DEFAULT NULL,
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `AppliedAmount` decimal(10,2) DEFAULT NULL,
  `BalanceRemaining` decimal(10,2) DEFAULT NULL,
  `Memo` text,
  `IsPaid` tinyint(1) DEFAULT NULL,
  `Currency_ListID` varchar(40) DEFAULT NULL,
  `Currency_FullName` varchar(255) DEFAULT NULL,
  `ExchangeRate` text,
  `BalanceRemainingInHomeCurrency` decimal(10,2) DEFAULT NULL,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `SuggestedDiscountAmount` decimal(10,2) DEFAULT NULL,
  `SuggestedDiscountDate` date DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_example_invoice_invoiceline`
--

CREATE TABLE IF NOT EXISTS `qb_example_invoice_invoiceline` (
  `Invoice_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `InventorySiteLocation_ListID` varchar(40) DEFAULT NULL,
  `InventorySiteLocation_FullName` varchar(255) DEFAULT NULL,
  `SerialNumber` text,
  `LotNumber` text,
  `ServiceDate` date DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_example_creditmemo`
--

CREATE TABLE `qb_example_creditmemo` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ARAccount_ListID` varchar(40) DEFAULT NULL,
  `ARAccount_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `IsPending` tinyint(1) DEFAULT NULL,
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `CreditRemaining` decimal(10,2) DEFAULT NULL,
  `Memo` text,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT '0',
  `IsToBeEmailed` tinyint(1) DEFAULT '0',
  `IsTaxIncluded` tinyint(1) DEFAULT '0',
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  `ExternalGUID` VARCHAR(40) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_example_creditmemo_creditmemoline`
--

CREATE TABLE `qb_example_creditmemo_creditmemoline` (
  `CreditMemo_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT '0.00000',
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `InventorySiteLocation_ListID` varchar(40) DEFAULT NULL,
  `InventorySiteLocation_FullName` varchar(255) DEFAULT NULL,
  `ServiceDate` date DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  `CreditCardTxnInputInfo_CreditCardNumber` text,
  `CreditCardTxnInputInfo_ExpirationMonth` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnInputInfo_ExpirationYear` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnInputInfo_NameOnCard` text,
  `CreditCardTxnInputInfo_CreditCardAddress` text,
  `CreditCardTxnInputInfo_CreditCardPostalCode` text,
  `CreditCardTxnInputInfo_CommercialCardCode` text,
  `CreditCardTxnInputInfo_TransactionMode` varchar(40) DEFAULT NULL,
  `CreditCardTxnInputInfo_CreditCardTxnType` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_ResultCode` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_ResultMessage` text,
  `CreditCardTxnResultInfo_CreditCardTransID` text,
  `CreditCardTxnResultInfo_MerchantAccountNumber` text,
  `CreditCardTxnResultInfo_AuthorizationCode` text,
  `CreditCardTxnResultInfo_AVSStreet` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_AVSZip` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_CardSecurityCodeMatch` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_ReconBatchID` text,
  `CreditCardTxnResultInfo_PaymentGroupingCode` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_PaymentStatus` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_TxnAuthorizationTime` datetime DEFAULT NULL,
  `CreditCardTxnResultInfo_TxnAuthorizationStamp` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_ClientTransID` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- ------------------------------------------------------------

--
-- Table structure for table `qb_recent_purchaseorder`
--

CREATE TABLE IF NOT EXISTS `qb_recent_purchaseorder` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Vendor_ListID` varchar(40) DEFAULT NULL,
  `Vendor_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ShipToEntity_ListID` varchar(40) DEFAULT NULL,
  `ShipToEntity_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `ExpectedDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Currency_ListID` varchar(40) DEFAULT NULL,
  `Currency_FullName` varchar(255) DEFAULT NULL,
  `ExchangeRate` text,
  `TotalAmountInHomeCurrency` decimal(10,2) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `IsFullyReceived` tinyint(1) DEFAULT NULL,
  `Memo` text,
  `VendorMsg` varchar(99) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `Other1` varchar(25) DEFAULT NULL,
  `Other2` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- -----------------------------------------------------

--
-- Table structure for table `qb_recent_purchaseorder_purchaseorderline`
--

CREATE TABLE `qb_recent_purchaseorder_purchaseorderline` (
  `PurchaseOrder_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `ManufacturerPartNumber` text,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `ServiceDate` date DEFAULT NULL,
  `ReceivedQuantity` decimal(12,5) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- -----------------------------------------------------------

--
-- Table structure for table `qb_recent_salesorder`
--

CREATE TABLE IF NOT EXISTS `qb_recent_salesorder` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `IsFullyInvoiced` tinyint(1) DEFAULT NULL,
  `Memo` text,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
);
-- --------------------------------------------------------------

--
-- Table structure for table `qb_recent_salesorder_salesorderline`
--

CREATE TABLE IF NOT EXISTS `qb_recent_salesorder_salesorderline` (
  `SalesOrder_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `SerialNumber` text,
  `LotNumber` text,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Invoiced` decimal(12,5) DEFAULT NULL,
  `IsManuallyClosed` tinyint(1) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
);
-- --------------------------------------------------------

--
-- Table structure for table `qb_recent_invoice`
--

CREATE TABLE IF NOT EXISTS `qb_recent_invoice` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ARAccount_ListID` varchar(40) DEFAULT NULL,
  `ARAccount_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `IsPending` tinyint(1) DEFAULT NULL,
  `IsFinanceCharge` tinyint(1) DEFAULT NULL,
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `AppliedAmount` decimal(10,2) DEFAULT NULL,
  `BalanceRemaining` decimal(10,2) DEFAULT NULL,
  `Memo` text,
  `IsPaid` tinyint(1) DEFAULT NULL,
  `Currency_ListID` varchar(40) DEFAULT NULL,
  `Currency_FullName` varchar(255) DEFAULT NULL,
  `ExchangeRate` text,
  `BalanceRemainingInHomeCurrency` decimal(10,2) DEFAULT NULL,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT NULL,
  `IsToBeEmailed` tinyint(1) DEFAULT NULL,
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `SuggestedDiscountAmount` decimal(10,2) DEFAULT NULL,
  `SuggestedDiscountDate` date DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_recent_invoice_invoiceline`
--

CREATE TABLE IF NOT EXISTS `qb_recent_invoice_invoiceline` (
  `Invoice_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT NULL,
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `InventorySiteLocation_ListID` varchar(40) DEFAULT NULL,
  `InventorySiteLocation_FullName` varchar(255) DEFAULT NULL,
  `SerialNumber` text,
  `LotNumber` text,
  `ServiceDate` date DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_recent_creditmemo`
--

CREATE TABLE `qb_recent_creditmemo` (
  `TxnID` varchar(40) DEFAULT NULL,
  `TimeCreated` datetime DEFAULT NULL,
  `TimeModified` datetime DEFAULT NULL,
  `EditSequence` text,
  `TxnNumber` int(10) UNSIGNED DEFAULT '0',
  `Customer_ListID` varchar(40) DEFAULT NULL,
  `Customer_FullName` varchar(255) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `ARAccount_ListID` varchar(40) DEFAULT NULL,
  `ARAccount_FullName` varchar(255) DEFAULT NULL,
  `Template_ListID` varchar(40) DEFAULT NULL,
  `Template_FullName` varchar(255) DEFAULT NULL,
  `TxnDate` date DEFAULT NULL,
  `RefNumber` varchar(11) DEFAULT NULL,
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
  `IsPending` tinyint(1) DEFAULT NULL,
  `PONumber` varchar(25) DEFAULT NULL,
  `Terms_ListID` varchar(40) DEFAULT NULL,
  `Terms_FullName` varchar(255) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `SalesRep_ListID` varchar(40) DEFAULT NULL,
  `SalesRep_FullName` varchar(255) DEFAULT NULL,
  `FOB` varchar(13) DEFAULT NULL,
  `ShipDate` date DEFAULT NULL,
  `ShipMethod_ListID` varchar(40) DEFAULT NULL,
  `ShipMethod_FullName` varchar(255) DEFAULT NULL,
  `Subtotal` decimal(10,2) DEFAULT NULL,
  `ItemSalesTax_ListID` varchar(40) DEFAULT NULL,
  `ItemSalesTax_FullName` varchar(255) DEFAULT NULL,
  `SalesTaxPercentage` decimal(12,5) DEFAULT NULL,
  `SalesTaxTotal` decimal(10,2) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `CreditRemaining` decimal(10,2) DEFAULT NULL,
  `Memo` text,
  `CustomerMsg_ListID` varchar(40) DEFAULT NULL,
  `CustomerMsg_FullName` varchar(255) DEFAULT NULL,
  `IsToBePrinted` tinyint(1) DEFAULT '0',
  `IsToBeEmailed` tinyint(1) DEFAULT '0',
  `IsTaxIncluded` tinyint(1) DEFAULT '0',
  `CustomerSalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `CustomerSalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other` varchar(29) DEFAULT NULL,
  `ExternalGUID` VARCHAR(40) DEFAULT NULL,
  PRIMARY KEY  (RefNumber)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `qb_recent_creditmemo_creditmemoline`
--

CREATE TABLE `qb_recent_creditmemo_creditmemoline` (
  `CreditMemo_TxnID` varchar(40) DEFAULT NULL,
  `SortOrder` int(10) UNSIGNED DEFAULT '0',
  `TxnLineID` varchar(40) DEFAULT NULL,
  `Item_ListID` varchar(40) DEFAULT NULL,
  `Item_FullName` varchar(255) DEFAULT NULL,
  `Descrip` text,
  `Quantity` decimal(12,5) DEFAULT '0.00000',
  `UnitOfMeasure` text,
  `OverrideUOMSet_ListID` varchar(40) DEFAULT NULL,
  `OverrideUOMSet_FullName` varchar(255) DEFAULT NULL,
  `Rate` decimal(13,5) DEFAULT NULL,
  `RatePercent` decimal(12,5) DEFAULT NULL,
  `Class_ListID` varchar(40) DEFAULT NULL,
  `Class_FullName` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `InventorySite_ListID` varchar(40) DEFAULT NULL,
  `InventorySite_FullName` varchar(255) DEFAULT NULL,
  `InventorySiteLocation_ListID` varchar(40) DEFAULT NULL,
  `InventorySiteLocation_FullName` varchar(255) DEFAULT NULL,
  `ServiceDate` date DEFAULT NULL,
  `SalesTaxCode_ListID` varchar(40) DEFAULT NULL,
  `SalesTaxCode_FullName` varchar(255) DEFAULT NULL,
  `Other1` text,
  `Other2` text,
  `CreditCardTxnInputInfo_CreditCardNumber` text,
  `CreditCardTxnInputInfo_ExpirationMonth` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnInputInfo_ExpirationYear` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnInputInfo_NameOnCard` text,
  `CreditCardTxnInputInfo_CreditCardAddress` text,
  `CreditCardTxnInputInfo_CreditCardPostalCode` text,
  `CreditCardTxnInputInfo_CommercialCardCode` text,
  `CreditCardTxnInputInfo_TransactionMode` varchar(40) DEFAULT NULL,
  `CreditCardTxnInputInfo_CreditCardTxnType` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_ResultCode` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_ResultMessage` text,
  `CreditCardTxnResultInfo_CreditCardTransID` text,
  `CreditCardTxnResultInfo_MerchantAccountNumber` text,
  `CreditCardTxnResultInfo_AuthorizationCode` text,
  `CreditCardTxnResultInfo_AVSStreet` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_AVSZip` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_CardSecurityCodeMatch` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_ReconBatchID` text,
  `CreditCardTxnResultInfo_PaymentGroupingCode` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_PaymentStatus` varchar(40) DEFAULT NULL,
  `CreditCardTxnResultInfo_TxnAuthorizationTime` datetime DEFAULT NULL,
  `CreditCardTxnResultInfo_TxnAuthorizationStamp` int(10) UNSIGNED DEFAULT NULL,
  `CreditCardTxnResultInfo_ClientTransID` text,
  PRIMARY KEY  (TxnLineID)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- ------------------------------------------------------------

--
-- Table structure for table `qb_error_log`
--

CREATE TABLE IF NOT EXISTS `qb_error_log` (
  `ListID` varchar(40) NOT NULL,
  `Module` text,
  `Msg` text,
  PRIMARY KEY  (ListID)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- ------------------------------------------------------------


-- ------------------------------------------------------------
-- ALTER MAIN TABLES ------------------------------------------
-- ------------------------------------------------------------

ALTER TABLE `qb_invoice_invoiceline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
ALTER TABLE `qb_invoice_invoiceline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

-- ALTER TABLE `qb_example_invoice_invoiceline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
-- ALTER TABLE `qb_example_invoice_invoiceline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

-- ALTER TABLE `qb_recent_invoice_invoiceline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
-- ALTER TABLE `qb_recent_invoice_invoiceline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

ALTER TABLE `qb_creditmemo_creditmemoline` ADD `InventorySite_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `Amount`;
ALTER TABLE `qb_creditmemo_creditmemoline` ADD `InventorySite_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_ListID`;
ALTER TABLE `qb_creditmemo_creditmemoline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
ALTER TABLE `qb_creditmemo_creditmemoline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

-- ALTER TABLE `qb_example_creditmemo_creditmemoline` ADD `InventorySite_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `Amount`;
-- ALTER TABLE `qb_example_creditmemo_creditmemoline` ADD `InventorySite_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_ListID`;
-- ALTER TABLE `qb_example_creditmemo_creditmemoline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
-- ALTER TABLE `qb_example_creditmemo_creditmemoline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

-- ALTER TABLE `qb_recent_creditmemo_creditmemoline` ADD `InventorySite_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `Amount`;
-- ALTER TABLE `qb_recent_creditmemo_creditmemoline` ADD `InventorySite_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_ListID`;
-- ALTER TABLE `qb_recent_creditmemo_creditmemoline` ADD `InventorySiteLocation_ListID` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySite_FullName`;
-- ALTER TABLE `qb_recent_creditmemo_creditmemoline` ADD `InventorySiteLocation_FullName` VARCHAR(40) NULL DEFAULT NULL AFTER `InventorySiteLocation_ListID`;

ALTER TABLE `qb_creditmemo` ADD `IsTaxIncluded` TINYINT(1) NULL DEFAULT '0' AFTER `IsToBeEmailed`;

-- ALTER TABLE `qb_example_creditmemo` ADD `IsTaxIncluded` TINYINT(1) NULL DEFAULT '0' AFTER `IsToBeEmailed`;
-- ALTER TABLE `qb_recent_creditmemo` ADD `IsTaxIncluded` TINYINT(1) NULL DEFAULT '0' AFTER `IsToBeEmailed`;

ALTER TABLE `qb_creditmemo` ADD `ExternalGUID` VARCHAR(40) NULL DEFAULT NULL AFTER `Other`;

-- ALTER TABLE `qb_example_creditmemo` ADD `ExternalGUID` VARCHAR(40) NULL DEFAULT NULL AFTER `Other`;
-- ALTER TABLE `qb_recent_creditmemo` ADD `ExternalGUID` VARCHAR(40) NULL DEFAULT NULL AFTER `Other`;

ALTER TABLE `qb_purchaseorder` CHANGE `is_fully_received_from_wms` `is_fully_received_from_wms` ENUM('Y','N') NOT NULL DEFAULT 'N';
