<?php
$create_database = "
--
-- Table structure for table `DOA_APPOINTMENT_CUSTOMER`
--

CREATE TABLE `DOA_APPOINTMENT_CUSTOMER` (
  `PK_APPOINTMENT_CUSTOMER` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL DEFAULT 0,
  `PK_USER_MASTER` int(11) NOT NULL DEFAULT 0,
  `WITH_PARTNER` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_APPOINTMENT_ENROLLMENT`
--

CREATE TABLE `DOA_APPOINTMENT_ENROLLMENT` (
  `PK_APPOINTMENT_ENROLLMENT` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) DEFAULT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_SERVICE` int(11) DEFAULT NULL,
  `TYPE` varchar(100) DEFAULT NULL,
  `IS_CHARGED` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_APPOINTMENT_MASTER`
--

CREATE TABLE `DOA_APPOINTMENT_MASTER` (
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL DEFAULT 0,
  `PK_ENROLLMENT_SERVICE` int(11) NOT NULL DEFAULT 0,
  `SERIAL_NUMBER` int(11) NOT NULL DEFAULT 0,
  `STANDING_ID` int(11) NOT NULL DEFAULT 0,
  `GROUP_NAME` varchar(255) DEFAULT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL DEFAULT 0,
  `PK_SERVICE_CODE` int(11) NOT NULL DEFAULT 0,
  `PK_SCHEDULING_CODE` int(11) NOT NULL DEFAULT 0,
  `PK_LOCATION` int(11) NOT NULL DEFAULT 0,
  `DATE` date DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `PK_APPOINTMENT_STATUS` int(11) NOT NULL,
  `NO_SHOW` varchar(100) DEFAULT NULL,
  `COMMENT` text DEFAULT NULL,
  `INTERNAL_COMMENT` text DEFAULT NULL,
  `IMAGE` varchar(255) DEFAULT NULL,
  `VIDEO` varchar(255) DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL,
  `STATUS` enum('A','C') NOT NULL DEFAULT 'A' COMMENT '''A''->Active,''C''->Cancelled',
  `IS_PAID` tinyint(4) NOT NULL DEFAULT 0,
  `IS_CHARGED` tinyint(4) NOT NULL DEFAULT 0,
  `APPOINTMENT_TYPE` varchar(100) DEFAULT NULL,
  `IS_REMINDER_SEND` tinyint(4) NOT NULL DEFAULT 0,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_APPOINTMENT_SERVICE_PROVIDER`
--

CREATE TABLE `DOA_APPOINTMENT_SERVICE_PROVIDER` (
  `PK_APPOINTMENT_SERVICE_PROVIDER` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL DEFAULT 0,
  `PK_USER` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_APPOINTMENT_STATUS_HISTORY`
--

CREATE TABLE `DOA_APPOINTMENT_STATUS_HISTORY` (
  `PK_APPOINTMENT_STATUS_HISTORY` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `PK_APPOINTMENT_STATUS` int(11) NOT NULL,
  `TIME_STAMP` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_COMMENT`
--

CREATE TABLE `DOA_COMMENT` (
  `PK_COMMENT` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `COMMENT` text NOT NULL,
  `COMMENT_DATE` date DEFAULT NULL,
  `FOR_PK_USER` int(11) NOT NULL,
  `BY_PK_USER` int(11) NOT NULL,
  `ACTIVE` int(1) NOT NULL,
  `CREATED_ON` date DEFAULT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` date DEFAULT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_DETAILS`
--

CREATE TABLE `DOA_CUSTOMER_DETAILS` (
  `PK_CUSTOMER_DETAILS` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `IS_PRIMARY` tinyint(4) DEFAULT NULL,
  `PK_CUSTOMER_PRIMARY` int(11) DEFAULT NULL,
  `CUSTOMER_ID` char(10) DEFAULT NULL,
  `FIRST_NAME` varchar(150) DEFAULT NULL,
  `LAST_NAME` varchar(150) DEFAULT NULL,
  `PK_RELATIONSHIP` int(11) DEFAULT NULL,
  `EMAIL` varchar(150) DEFAULT NULL,
  `PHONE` varchar(20) DEFAULT NULL,
  `GENDER` varchar(10) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `CALL_PREFERENCE` varchar(200) DEFAULT NULL,
  `REMINDER_OPTION` varchar(250) DEFAULT NULL,
  `ATTENDING_WITH` varchar(50) DEFAULT NULL,
  `PARTNER_FIRST_NAME` varchar(150) DEFAULT NULL,
  `PARTNER_LAST_NAME` varchar(150) DEFAULT NULL,
  `PARTNER_PHONE` varchar(20) DEFAULT NULL,
  `PARTNER_EMAIL` varchar(100) DEFAULT NULL,
  `PARTNER_GENDER` varchar(20) DEFAULT NULL,
  `PARTNER_DOB` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_DOCUMENT`
--

CREATE TABLE `DOA_CUSTOMER_DOCUMENT` (
  `PK_CUSTOMER_DOCUMENT` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `DOCUMENT_NAME` varchar(100) DEFAULT NULL,
  `FILE_PATH` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_EMAIL`
--

CREATE TABLE `DOA_CUSTOMER_EMAIL` (
  `PK_CUSTOMER_EMAIL` int(11) NOT NULL,
  `PK_CUSTOMER_DETAILS` int(11) DEFAULT NULL,
  `EMAIL` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_INTEREST`
--

CREATE TABLE `DOA_CUSTOMER_INTEREST` (
  `PK_CUSTOMER_INTEREST` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `PK_INTERESTS` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_INTEREST_OTHER_DATA`
--

CREATE TABLE `DOA_CUSTOMER_INTEREST_OTHER_DATA` (
  `PK_CUSTOMER_INTEREST_OTHER_DATA` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `WHAT_PROMPTED_YOU_TO_INQUIRE` varchar(150) DEFAULT NULL,
  `PK_SKILL_LEVEL` int(11) DEFAULT NULL,
  `PK_INQUIRY_METHOD` int(11) DEFAULT NULL,
  `INQUIRY_TAKER_ID` int(11) DEFAULT NULL,
  `INQUIRY_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_PAYMENT_INFO`
--

CREATE TABLE `DOA_CUSTOMER_PAYMENT_INFO` (
  `PK_CUSTOMER_PAYMENT_INFO` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `CUSTOMER_PAYMENT_ID` varchar(200) NOT NULL,
  `PAYMENT_TYPE` varchar(100) NOT NULL,
  `CREATED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_PHONE`
--

CREATE TABLE `DOA_CUSTOMER_PHONE` (
  `PK_CUSTOMER_PHONE` int(11) NOT NULL,
  `PK_CUSTOMER_DETAILS` int(11) DEFAULT NULL,
  `PHONE` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_SPECIAL_DATE`
--

CREATE TABLE `DOA_CUSTOMER_SPECIAL_DATE` (
  `PK_CUSTOMER_SPECIAL_DATE` int(11) NOT NULL,
  `PK_CUSTOMER_DETAILS` int(11) DEFAULT NULL,
  `SPECIAL_DATE` varchar(10) DEFAULT NULL,
  `DATE_NAME` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_CUSTOMER_WALLET`
--

CREATE TABLE `DOA_CUSTOMER_WALLET` (
  `PK_CUSTOMER_WALLET` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `CURRENT_BALANCE` float(9,2) NOT NULL,
  `DEBIT` float(9,2) NOT NULL DEFAULT 0.00,
  `CREDIT` float(9,2) NOT NULL DEFAULT 0.00,
  `BALANCE_LEFT` float(9,2) NOT NULL DEFAULT 0.00,
  `DESCRIPTION` text DEFAULT NULL,
  `PK_PAYMENT_TYPE` int(11) DEFAULT NULL,
  `RECEIPT_NUMBER` varchar(255) DEFAULT NULL,
  `RECEIPT_PDF_LINK` varchar(255) DEFAULT NULL,
  `NOTE` text DEFAULT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_DOCUMENT_LIBRARY`
--

CREATE TABLE `DOA_DOCUMENT_LIBRARY` (
  `PK_DOCUMENT_LIBRARY` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `PK_DOCUMENT_TYPE` int(11) NOT NULL,
  `PK_LOCATION` varchar(255) DEFAULT NULL,
  `DOCUMENT_NAME` text NOT NULL,
  `DOCUMENT_TEMPLATE` longtext DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_DOCUMENT_LOCATION`
--

CREATE TABLE `DOA_DOCUMENT_LOCATION` (
  `PK_DOCUMENT_LOCATION` int(11) NOT NULL,
  `PK_DOCUMENT_LIBRARY` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL`
--

CREATE TABLE `DOA_EMAIL` (
  `PK_EMAIL` int(11) NOT NULL,
  `INTERNAL_ID` int(11) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `PK_EMAIL_TYPE` int(11) NOT NULL,
  `DRAFT` int(11) NOT NULL DEFAULT 0,
  `SUBJECT` varchar(300) NOT NULL,
  `CONTENT` longtext NOT NULL,
  `REMINDER_DATE` date DEFAULT NULL,
  `DUE_DATE` date DEFAULT NULL,
  `EMAIL_FOR` varchar(100) NOT NULL,
  `ID` int(11) NOT NULL DEFAULT 0,
  `PARENT_ID` int(11) NOT NULL DEFAULT 0,
  `PK_EMAIL_STATUS` int(11) NOT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_ACCOUNT`
--

CREATE TABLE `DOA_EMAIL_ACCOUNT` (
  `PK_EMAIL_ACCOUNT` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `HOST` varchar(250) NOT NULL,
  `PORT` varchar(250) NOT NULL,
  `USER_NAME` varchar(250) NOT NULL,
  `PASSWORD` varchar(250) NOT NULL,
  `ENCRYPTION_TYPE` varchar(150) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_ATTACHMENT`
--

CREATE TABLE `DOA_EMAIL_ATTACHMENT` (
  `PK_EMAIL_ATTACHMENT` int(11) NOT NULL,
  `PK_EMAIL` int(11) NOT NULL,
  `FILE_NAME` varchar(250) NOT NULL,
  `LOCATION` varchar(500) NOT NULL,
  `UPLOADED_ON` datetime NOT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_RECEPTION`
--

CREATE TABLE `DOA_EMAIL_RECEPTION` (
  `PK_EMAIL_RECEPTION` int(11) NOT NULL,
  `PK_EMAIL` int(11) NOT NULL,
  `INTERNAL_ID` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `VIWED` int(11) NOT NULL,
  `REPLY` int(11) NOT NULL,
  `DELETED` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_STARRED`
--

CREATE TABLE `DOA_EMAIL_STARRED` (
  `PK_EMAIL_STARRED` int(11) NOT NULL,
  `INTERNAL_ID` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `PK_EMAIL` int(11) NOT NULL,
  `STARRED` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_STATUS`
--

CREATE TABLE `DOA_EMAIL_STATUS` (
  `PK_EMAIL_STATUS` int(11) NOT NULL,
  `EMAIL_STATUS` varchar(250) NOT NULL,
  `ACTIVE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_TEMPLATE`
--

CREATE TABLE `DOA_EMAIL_TEMPLATE` (
  `PK_EMAIL_TEMPLATE` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `TEMPLATE_NAME` varchar(100) DEFAULT NULL,
  `SUBJECT` varchar(255) DEFAULT NULL,
  `PK_TEMPLATE_CATEGORY` int(11) NOT NULL,
  `PK_EMAIL_TRIGGER` int(11) DEFAULT NULL,
  `PK_EMAIL_ACCOUNT` int(11) DEFAULT NULL,
  `CONTENT` text DEFAULT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL DEFAULT 0,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_TYPE`
--

CREATE TABLE `DOA_EMAIL_TYPE` (
  `PK_EMAIL_TYPE` int(11) NOT NULL,
  `EMAIL_TYPE` varchar(250) NOT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_BALANCE`
--

CREATE TABLE `DOA_ENROLLMENT_BALANCE` (
  `PK_ENROLLMENT_BALANCE` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `TOTAL_BALANCE_PAID` float(9,2) NOT NULL,
  `TOTAL_BALANCE_USED` float(9,2) NOT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_BILLING`
--

CREATE TABLE `DOA_ENROLLMENT_BILLING` (
  `PK_ENROLLMENT_BILLING` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `BILLING_REF` varchar(150) DEFAULT NULL,
  `BILLING_DATE` date DEFAULT NULL,
  `ACTUAL_AMOUNT` float(9,2) DEFAULT NULL,
  `DISCOUNT` float(9,2) DEFAULT NULL,
  `TOTAL_AMOUNT` float(9,2) DEFAULT NULL,
  `DOWN_PAYMENT` float(9,2) DEFAULT NULL,
  `BALANCE_PAYABLE` float(9,2) DEFAULT NULL,
  `PAYMENT_METHOD` varchar(50) DEFAULT NULL,
  `PAYMENT_TERM` varchar(50) DEFAULT NULL,
  `NUMBER_OF_PAYMENT` int(11) NOT NULL DEFAULT 0,
  `FIRST_DUE_DATE` date DEFAULT NULL,
  `INSTALLMENT_AMOUNT` float(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_LEDGER`
--

CREATE TABLE `DOA_ENROLLMENT_LEDGER` (
  `PK_ENROLLMENT_LEDGER` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_BILLING` int(11) NOT NULL,
  `TRANSACTION_TYPE` varchar(50) DEFAULT NULL,
  `ENROLLMENT_LEDGER_PARENT` int(11) DEFAULT 0,
  `DUE_DATE` date DEFAULT NULL,
  `BILLED_AMOUNT` float(9,2) DEFAULT 0.00,
  `PAID_AMOUNT` float(9,2) DEFAULT 0.00,
  `BALANCE` float(9,2) DEFAULT 0.00,
  `AMOUNT_REMAIN` float(9,2) NOT NULL DEFAULT 0.00,
  `IS_PAID` tinyint(4) DEFAULT 0,
  `IS_REFUNDED` tinyint(4) NOT NULL DEFAULT 0,
  `IS_DOWN_PAYMENT` tinyint(4) NOT NULL DEFAULT 0,
  `STATUS` varchar(50) DEFAULT NULL COMMENT '''A''->Active,''C''->Cancelled, ''CA''->Cancelled But Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_MASTER`
--

CREATE TABLE `DOA_ENROLLMENT_MASTER` (
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_TYPE` int(11) NOT NULL DEFAULT 0,
  `MISC_TYPE` varchar(50) DEFAULT NULL,
  `ENROLLMENT_NAME` varchar(100) DEFAULT NULL,
  `ENROLLMENT_ID` varchar(100) DEFAULT NULL,
  `MISC_ID` varchar(100) DEFAULT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `CUSTOMER_ENROLLMENT_NUMBER` int(11) DEFAULT NULL,
  `PK_LOCATION` int(11) DEFAULT NULL,
  `PK_PACKAGE` int(11) DEFAULT NULL,
  `CHARGE_TYPE` varchar(20) DEFAULT NULL,
  `PK_AGREEMENT_TYPE` int(11) DEFAULT NULL,
  `PK_DOCUMENT_LIBRARY` int(11) DEFAULT NULL,
  `AGREEMENT_PDF_LINK` varchar(255) DEFAULT NULL,
  `ENROLLMENT_BY_ID` int(11) DEFAULT NULL,
  `ENROLLMENT_BY_PERCENTAGE` float(9,2) DEFAULT NULL,
  `MEMO` text DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL DEFAULT 1,
  `STATUS` varchar(50) DEFAULT NULL COMMENT '''A''->Active,''C''->Cancelled, ''CA''->Cancelled But Active',
  `ENROLLMENT_DATE` date DEFAULT NULL,
  `EXPIRY_DATE` date DEFAULT NULL,
  `ALL_APPOINTMENT_DONE` tinyint(4) NOT NULL DEFAULT 0,
  `IS_SALE` varchar(20) DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_PAYMENT`
--

CREATE TABLE `DOA_ENROLLMENT_PAYMENT` (
  `PK_ENROLLMENT_PAYMENT` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_BILLING` int(11) NOT NULL,
  `PK_PAYMENT_TYPE` int(11) NOT NULL,
  `PK_ENROLLMENT_LEDGER` int(11) NOT NULL,
  `TYPE` varchar(25) DEFAULT NULL,
  `IS_REFUNDED` tinyint(4) NOT NULL DEFAULT 0,
  `AMOUNT` float(9,2) NOT NULL,
  `NOTE` text DEFAULT NULL,
  `PAYMENT_DATE` date NOT NULL,
  `PAYMENT_INFO` text NOT NULL,
  `PAYMENT_STATUS` varchar(50) DEFAULT NULL,
  `RECEIPT_NUMBER` varchar(255) DEFAULT NULL,
  `IS_ORIGINAL_RECEIPT` tinyint(4) NOT NULL DEFAULT 1,
  `RECEIPT_PDF_LINK` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_SERVICE`
--

CREATE TABLE `DOA_ENROLLMENT_SERVICE` (
  `PK_ENROLLMENT_SERVICE` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) DEFAULT NULL,
  `PK_SERVICE_MASTER` int(11) DEFAULT NULL,
  `PK_SERVICE_CODE` int(11) DEFAULT NULL,
  `SERVICE_DETAILS` varchar(250) DEFAULT NULL,
  `NUMBER_OF_SESSION` int(11) DEFAULT NULL,
  `PRICE_PER_SESSION` float(9,2) DEFAULT NULL,
  `TOTAL` float(9,2) DEFAULT NULL,
  `TOTAL_AMOUNT_PAID` float(9,2) DEFAULT NULL,
  `DISCOUNT` float(9,2) DEFAULT NULL,
  `DISCOUNT_TYPE` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1->Fixed, 2->Percent',
  `FINAL_AMOUNT` float(9,2) DEFAULT NULL,
  `SESSION_CREATED` int(11) NOT NULL DEFAULT 0,
  `SESSION_COMPLETED` int(11) NOT NULL DEFAULT 0,
  `STATUS` varchar(50) DEFAULT NULL COMMENT '''A''->Active,''C''->Cancelled, ''CA''->Cancelled But Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ENROLLMENT_SERVICE_PROVIDER`
--

CREATE TABLE `DOA_ENROLLMENT_SERVICE_PROVIDER` (
  `PK_ENROLLMENT_SERVICE_PROVIDER` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `SERVICE_PROVIDER_ID` int(11) NOT NULL,
  `SERVICE_PROVIDER_PERCENTAGE` float(9,2) NOT NULL,
  `PERCENTAGE_AMOUNT` float(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EVENT`
--

CREATE TABLE `DOA_EVENT` (
  `PK_EVENT` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `HEADER` varchar(255) NOT NULL,
  `PK_EVENT_TYPE` int(11) NOT NULL,
  `START_DATE` date DEFAULT NULL,
  `END_DATE` date DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `ALL_DAY` tinyint(4) NOT NULL DEFAULT 0,
  `DESCRIPTION` text DEFAULT NULL,
  `PK_LOCATION` varchar(255) DEFAULT NULL,
  `SHARE_WITH_CUSTOMERS` tinyint(4) DEFAULT NULL,
  `SHARE_WITH_SERVICE_PROVIDERS` tinyint(4) DEFAULT NULL,
  `SHARE_WITH_EMPLOYEES` tinyint(4) DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EVENT_IMAGE`
--

CREATE TABLE `DOA_EVENT_IMAGE` (
  `PK_EVENT_IMAGE` int(11) NOT NULL,
  `PK_EVENT` int(11) NOT NULL,
  `IMAGE` varchar(255) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EVENT_LOCATION`
--

CREATE TABLE `DOA_EVENT_LOCATION` (
  `PK_EVENT_LOCATION` int(11) NOT NULL,
  `PK_EVENT` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EVENT_TYPE`
--

CREATE TABLE `DOA_EVENT_TYPE` (
  `PK_EVENT_TYPE` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `EVENT_TYPE` varchar(250) NOT NULL,
  `COLOR_CODE` varchar(100) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_GIFT_CERTIFICATE_MASTER`
--

CREATE TABLE `DOA_GIFT_CERTIFICATE_MASTER` (
  `PK_GIFT_CERTIFICATE_MASTER` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `PK_GIFT_CERTIFICATE_SETUP` int(11) NOT NULL,
  `DATE_OF_PURCHASE` date DEFAULT NULL,
  `GIFT_NOTE` text NOT NULL,
  `AMOUNT` float(9,2) NOT NULL,
  `PK_PAYMENT_TYPE` int(11) NOT NULL,
  `CHECK_NUMBER` varchar(150) NOT NULL,
  `CHECK_DATE` date DEFAULT NULL,
  `PAYMENT_INFO` text NOT NULL,
  `ACTIVE` int(1) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` date DEFAULT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_GIFT_CERTIFICATE_SETUP`
--

CREATE TABLE `DOA_GIFT_CERTIFICATE_SETUP` (
  `PK_GIFT_CERTIFICATE_SETUP` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `GIFT_CERTIFICATE_NAME` varchar(200) NOT NULL,
  `GIFT_CERTIFICATE_CODE` varchar(200) NOT NULL,
  `MINIMUM_AMOUNT` float(9,2) NOT NULL,
  `MAXIMUM_AMOUNT` float(9,2) NOT NULL,
  `EFFECTIVE_DATE` date DEFAULT NULL,
  `END_DATE` date DEFAULT NULL,
  `ACTIVE` int(1) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` date DEFAULT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_HOLIDAY_LIST`
--

CREATE TABLE `DOA_HOLIDAY_LIST` (
  `PK_HOLIDAY_LIST` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `HOLIDAY_DATE` date NOT NULL,
  `HOLIDAY_NAME` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_INQUIRY_METHOD`
--

CREATE TABLE `DOA_INQUIRY_METHOD` (
  `PK_INQUIRY_METHOD` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) DEFAULT NULL,
  `INQUIRY_METHOD` varchar(250) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_INTERESTS`
--

CREATE TABLE `DOA_INTERESTS` (
  `PK_INTERESTS` int(11) NOT NULL,
  `PK_BUSINESS_TYPE` int(11) NOT NULL,
  `INTERESTS` varchar(250) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_OPERATIONAL_HOUR`
--

CREATE TABLE `DOA_OPERATIONAL_HOUR` (
  `PK_OPERATIONAL_HOUR` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `DAY_NUMBER` smallint(6) NOT NULL,
  `OPEN_TIME` time NOT NULL,
  `CLOSE_TIME` time NOT NULL,
  `CLOSED` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ORDER`
--

CREATE TABLE `DOA_ORDER` (
  `PK_ORDER` int(11) NOT NULL,
  `ORDER_ID` varchar(255) DEFAULT NULL,
  `PK_USER_MASTER` int(11) DEFAULT NULL,
  `ORDER_TYPE` varchar(100) DEFAULT NULL,
  `ITEM_TOTAL` float(9,2) DEFAULT NULL,
  `SALES_TAX` float(7,2) DEFAULT NULL,
  `SHIPPING_CHARGE` float(7,2) DEFAULT NULL,
  `ORDER_TOTAL` float(7,2) DEFAULT NULL,
  `ADDRESS` varchar(255) DEFAULT NULL,
  `ADDRESS_1` varchar(255) DEFAULT NULL,
  `PK_COUNTRY` int(11) DEFAULT NULL,
  `PK_STATES` int(11) DEFAULT NULL,
  `CITY` varchar(255) DEFAULT NULL,
  `ZIP` varchar(100) DEFAULT NULL,
  `PK_PAYMENT_TYPE` int(11) DEFAULT NULL,
  `PAYMENT_DETAILS` text DEFAULT NULL,
  `PAYMENT_STATUS` varchar(100) DEFAULT NULL,
  `PK_ORDER_STATUS` int(11) DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_ORDER_ITEM`
--

CREATE TABLE `DOA_ORDER_ITEM` (
  `PK_ORDER_ITEM` int(11) NOT NULL,
  `PK_ORDER` int(11) NOT NULL,
  `PK_PRODUCT` int(11) NOT NULL,
  `PK_PRODUCT_COLOR` int(11) DEFAULT NULL,
  `PK_PRODUCT_SIZE` int(11) DEFAULT NULL,
  `PRODUCT_QUANTITY` int(11) NOT NULL,
  `PRODUCT_PRICE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PACKAGE`
--

CREATE TABLE `DOA_PACKAGE` (
  `PK_PACKAGE` int(11) NOT NULL,
  `PACKAGE_NAME` varchar(250) NOT NULL,
  `SORT_ORDER` int(11) DEFAULT NULL,
  `EXPIRY_DATE` int(11) DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL,
  `IS_DELETED` int(11) NOT NULL,
  `CREATED_ON` date NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` date DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PACKAGE_LOCATION`
--

CREATE TABLE `DOA_PACKAGE_LOCATION` (
  `PK_PACKAGE_LOCATION` int(11) NOT NULL,
  `PK_PACKAGE` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PACKAGE_SERVICE`
--

CREATE TABLE `DOA_PACKAGE_SERVICE` (
  `PK_PACKAGE_SERVICE` int(11) NOT NULL,
  `PK_PACKAGE` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `PK_SERVICE_CODE` int(11) NOT NULL,
  `SERVICE_DETAILS` varchar(250) DEFAULT NULL,
  `NUMBER_OF_SESSION` int(11) DEFAULT NULL,
  `PRICE_PER_SESSION` float(9,2) DEFAULT NULL,
  `TOTAL` float(9,2) DEFAULT NULL,
  `DISCOUNT` float(9,2) DEFAULT NULL,
  `DISCOUNT_TYPE` tinyint(4) DEFAULT NULL,
  `FINAL_AMOUNT` float(9,2) DEFAULT NULL,
  `ACTIVE` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PRODUCT`
--

CREATE TABLE `DOA_PRODUCT` (
  `PK_PRODUCT` int(11) NOT NULL,
  `PRODUCT_ID` varchar(155) DEFAULT NULL,
  `PRODUCT_NAME` varchar(100) DEFAULT NULL,
  `PRODUCT_DESCRIPTION` text DEFAULT NULL,
  `PRICE` float(9,2) DEFAULT NULL,
  `SHIPPING_INFORMATION` text DEFAULT NULL,
  `PRODUCT_IMAGES` varchar(255) DEFAULT NULL,
  `BRAND` varchar(255) DEFAULT NULL,
  `CATEGORY` varchar(255) DEFAULT NULL,
  `SIZE` varchar(55) DEFAULT NULL,
  `COLOR` varchar(155) DEFAULT NULL,
  `WEIGHT` varchar(255) DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL,
  `IS_DELETED` int(11) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PRODUCT_COLOR`
--

CREATE TABLE `DOA_PRODUCT_COLOR` (
  `PK_PRODUCT_COLOR` int(11) NOT NULL,
  `PK_PRODUCT` int(11) NOT NULL,
  `COLOR` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_PRODUCT_SIZE`
--

CREATE TABLE `DOA_PRODUCT_SIZE` (
  `PK_PRODUCT_SIZE` int(11) NOT NULL,
  `PK_PRODUCT` int(11) NOT NULL,
  `SIZE` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_REPORT_EXPORT_DETAILS`
--

CREATE TABLE `DOA_REPORT_EXPORT_DETAILS` (
  `PK_REPORT_EXPORT_DETAILS` int(11) NOT NULL,
  `REPORT_TYPE` varchar(100) DEFAULT NULL,
  `WEEK_NUMBER` int(11) DEFAULT NULL,
  `YEAR` int(11) DEFAULT NULL,
  `SUBMISSION_DATE` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SCHEDULING_CODE`
--

CREATE TABLE `DOA_SCHEDULING_CODE` (
  `PK_SCHEDULING_CODE` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) DEFAULT NULL,
  `SCHEDULING_CODE` varchar(200) DEFAULT NULL,
  `SCHEDULING_NAME` varchar(200) DEFAULT NULL,
  `PK_SCHEDULING_EVENT` int(11) DEFAULT NULL,
  `PK_EVENT_ACTION` int(11) DEFAULT NULL,
  `TO_DOS` int(11) DEFAULT NULL,
  `IS_GROUP` int(11) DEFAULT NULL,
  `IS_DEFAULT` int(11) DEFAULT NULL,
  `COLOR_CODE` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `DURATION` int(11) DEFAULT NULL,
  `UNIT` float NOT NULL DEFAULT 1,
  `SORT_ORDER` int(11) DEFAULT NULL,
  `ACTIVE` int(1) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SCHEDULING_SERVICE`
--

CREATE TABLE `DOA_SCHEDULING_SERVICE` (
  `PK_SCHEDULING_SERVICE` int(11) NOT NULL,
  `PK_SCHEDULING_CODE` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `PK_SERVICE_CODE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_CODE`
--

CREATE TABLE `DOA_SERVICE_CODE` (
  `PK_SERVICE_CODE` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `IS_DEFAULT` int(11) DEFAULT NULL,
  `SERVICE_CODE` varchar(100) DEFAULT NULL,
  `DESCRIPTION` varchar(255) DEFAULT NULL,
  `IS_GROUP` tinyint(4) NOT NULL DEFAULT 0,
  `IS_SUNDRY` tinyint(4) NOT NULL DEFAULT 0,
  `CAPACITY` int(11) DEFAULT 0,
  `IS_CHARGEABLE` tinyint(4) NOT NULL DEFAULT 0,
  `PRICE` float(9,2) NOT NULL DEFAULT 0.00,
  `ACTIVE` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_COMMISSION`
--

CREATE TABLE `DOA_SERVICE_COMMISSION` (
  `PK_SERVICE_COMMISSION` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `COMMISSION_AMOUNT` float(9,2) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `ACTIVE` tinyint(4) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_DOCUMENTS`
--

CREATE TABLE `DOA_SERVICE_DOCUMENTS` (
  `PK_SERVICE_DOCUMENTS` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `FILE_PATH` varchar(250) DEFAULT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_LOCATION`
--

CREATE TABLE `DOA_SERVICE_LOCATION` (
  `PK_SERVICE_LOCATION` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_MASTER`
--

CREATE TABLE `DOA_SERVICE_MASTER` (
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `SERVICE_NAME` varchar(250) DEFAULT NULL,
  `PK_SERVICE_CLASS` int(11) DEFAULT NULL,
  `MISC_TYPE` varchar(50) DEFAULT NULL,
  `IS_SCHEDULE` tinyint(4) DEFAULT NULL,
  `IS_SUNDRY` tinyint(4) NOT NULL DEFAULT 0,
  `DESCRIPTION` varchar(250) DEFAULT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `IS_DELETED` tinyint(4) NOT NULL DEFAULT 0,
  `CREATED_ON` datetime DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_PROVIDER_AMOUNT`
--

CREATE TABLE `DOA_SERVICE_PROVIDER_AMOUNT` (
  `PK_SERVICE_PROVIDER_AMOUNT` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `SERVICE_PROVIDER_ID` int(11) NOT NULL,
  `PERCENTAGE_AMOUNT` float(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_PROVIDER_LOCATION_HOURS`
--

CREATE TABLE `DOA_SERVICE_PROVIDER_LOCATION_HOURS` (
  `PK_SERVICE_PROVIDER_LOCATION_HOURS` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `MON_START_TIME` time DEFAULT NULL,
  `MON_END_TIME` time DEFAULT NULL,
  `TUE_START_TIME` time DEFAULT NULL,
  `TUE_END_TIME` time DEFAULT NULL,
  `WED_START_TIME` time DEFAULT NULL,
  `WED_END_TIME` time DEFAULT NULL,
  `THU_START_TIME` time DEFAULT NULL,
  `THU_END_TIME` time DEFAULT NULL,
  `FRI_START_TIME` time DEFAULT NULL,
  `FRI_END_TIME` time DEFAULT NULL,
  `SAT_START_TIME` time DEFAULT NULL,
  `SAT_END_TIME` time DEFAULT NULL,
  `SUN_START_TIME` time DEFAULT NULL,
  `SUN_END_TIME` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SERVICE_SCHEDULING_CODE`
--

CREATE TABLE `DOA_SERVICE_SCHEDULING_CODE` (
  `PK_SERVICE_BOOKING_CODE` int(11) NOT NULL,
  `PK_SERVICE_MASTER` int(11) NOT NULL,
  `PK_SERVICE_CODE` int(11) NOT NULL,
  `PK_SCHEDULING_CODE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SKILL_LEVEL`
--

CREATE TABLE `DOA_SKILL_LEVEL` (
  `PK_SKILL_LEVEL` int(11) NOT NULL,
  `SKILL_LEVEL` varchar(250) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SPECIAL_APPOINTMENT`
--

CREATE TABLE `DOA_SPECIAL_APPOINTMENT` (
  `PK_SPECIAL_APPOINTMENT` int(11) NOT NULL,
  `STANDING_ID` int(11) NOT NULL DEFAULT 0,
  `PK_LOCATION` int(11) NOT NULL DEFAULT 0,
  `TITLE` varchar(255) NOT NULL,
  `DATE` date NOT NULL,
  `START_TIME` time NOT NULL,
  `END_TIME` time NOT NULL,
  `DESCRIPTION` text NOT NULL,
  `PK_SCHEDULING_CODE` int(11) NOT NULL,
  `PK_APPOINTMENT_STATUS` int(11) NOT NULL,
  `ACTIVE` tinyint(4) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SPECIAL_APPOINTMENT_CUSTOMER`
--

CREATE TABLE `DOA_SPECIAL_APPOINTMENT_CUSTOMER` (
  `PK_SPECIAL_APPOINTMENT_CUSTOMER` int(11) NOT NULL,
  `PK_SPECIAL_APPOINTMENT` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_SPECIAL_APPOINTMENT_USER`
--

CREATE TABLE `DOA_SPECIAL_APPOINTMENT_USER` (
  `PK_SPECIAL_APPOINTMENT_USER` int(11) NOT NULL,
  `PK_SPECIAL_APPOINTMENT` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_UPDATE_HISTORY`
--

CREATE TABLE `DOA_UPDATE_HISTORY` (
  `PK_UPDATE_HISTORY` int(11) NOT NULL,
  `CLASS` varchar(255) NOT NULL,
  `PRIMARY_KEY` int(11) NOT NULL,
  `FIELD_NAME` varchar(255) NOT NULL,
  `FROM_VALUE` varchar(255) DEFAULT NULL,
  `TO_VALUE` varchar(255) DEFAULT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_USERS`
--

CREATE TABLE `DOA_USERS` (
  `PK_USER` int(11) NOT NULL,
  `PK_USER_MASTER_DB` int(11) DEFAULT NULL,
  `PK_ACCOUNT_MASTER` int(11) DEFAULT NULL,
  `FIRST_NAME` varchar(100) DEFAULT NULL,
  `LAST_NAME` varchar(100) DEFAULT NULL,
  `USER_NAME` varchar(100) DEFAULT NULL,
  `EMAIL_ID` varchar(100) DEFAULT NULL,
  `PHONE` varchar(20) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_USER_DOCUMENT`
--

CREATE TABLE `DOA_USER_DOCUMENT` (
  `PK_USER_DOCUMENT` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `DOCUMENT_NAME` varchar(100) NOT NULL,
  `FILE_PATH` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_USER_RATE`
--

CREATE TABLE `DOA_USER_RATE` (
  `PK_USER_RATE` int(11) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `PK_RATE_TYPE` int(11) NOT NULL,
  `RATE` float(9,2) NOT NULL,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_Z_HELP`
--

CREATE TABLE `DOA_Z_HELP` (
  `PK_HELP` int(11) NOT NULL,
  `PK_HELP_CATEGORY` int(11) NOT NULL,
  `PK_HELP_SUB_CATEGORY` int(11) NOT NULL,
  `NAME_ENG` varchar(500) DEFAULT NULL,
  `NAME_SPA` varchar(500) DEFAULT NULL,
  `TOOL_CONTENT_ENG` text DEFAULT NULL,
  `TOOL_CONTENT_SPA` text DEFAULT NULL,
  `CONTENT_ENG` longtext DEFAULT NULL,
  `CONTENT_SPA` longtext DEFAULT NULL,
  `URL` varchar(250) DEFAULT NULL,
  `DISPLAY_ORDER` int(11) DEFAULT 0,
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_Z_HELP_FILES`
--

CREATE TABLE `DOA_Z_HELP_FILES` (
  `PK_HELP_FILES` int(11) NOT NULL,
  `PK_HELP` int(11) NOT NULL,
  `FILE_NAME` varchar(250) NOT NULL,
  `FILE_LOCATION` varchar(500) NOT NULL,
  `FILE_TYPE` int(1) NOT NULL COMMENT '1 - image, 2 - pdf',
  `ACTIVE` int(1) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `DOA_APPOINTMENT_CUSTOMER`
--
ALTER TABLE `DOA_APPOINTMENT_CUSTOMER`
  ADD PRIMARY KEY (`PK_APPOINTMENT_CUSTOMER`),
  ADD KEY `PK_APPOINTMENT_MASTER` (`PK_APPOINTMENT_MASTER`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`);

--
-- Indexes for table `DOA_APPOINTMENT_ENROLLMENT`
--
ALTER TABLE `DOA_APPOINTMENT_ENROLLMENT`
  ADD PRIMARY KEY (`PK_APPOINTMENT_ENROLLMENT`),
  ADD KEY `PK_APPOINTMENT_MASTER` (`PK_APPOINTMENT_MASTER`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_SERVICE` (`PK_ENROLLMENT_SERVICE`);

--
-- Indexes for table `DOA_APPOINTMENT_MASTER`
--
ALTER TABLE `DOA_APPOINTMENT_MASTER`
  ADD PRIMARY KEY (`PK_APPOINTMENT_MASTER`),
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`),
  ADD KEY `CREATED_BY` (`CREATED_BY`),
  ADD KEY `EDITED_BY` (`EDITED_BY`),
  ADD KEY `PK_SERVICE_CODE` (`PK_SERVICE_CODE`),
  ADD KEY `PK_APPOINTMENT_STATUS` (`PK_APPOINTMENT_STATUS`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_SERVICE` (`PK_ENROLLMENT_SERVICE`),
  ADD KEY `PK_SCHEDULING_CODE` (`PK_SCHEDULING_CODE`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_APPOINTMENT_SERVICE_PROVIDER`
--
ALTER TABLE `DOA_APPOINTMENT_SERVICE_PROVIDER`
  ADD PRIMARY KEY (`PK_APPOINTMENT_SERVICE_PROVIDER`),
  ADD KEY `PK_APPOINTMENT_MASTER` (`PK_APPOINTMENT_MASTER`),
  ADD KEY `PK_USER_MASTER` (`PK_USER`);

--
-- Indexes for table `DOA_APPOINTMENT_STATUS_HISTORY`
--
ALTER TABLE `DOA_APPOINTMENT_STATUS_HISTORY`
  ADD PRIMARY KEY (`PK_APPOINTMENT_STATUS_HISTORY`);

--
-- Indexes for table `DOA_COMMENT`
--
ALTER TABLE `DOA_COMMENT`
  ADD PRIMARY KEY (`PK_COMMENT`);

--
-- Indexes for table `DOA_CUSTOMER_DETAILS`
--
ALTER TABLE `DOA_CUSTOMER_DETAILS`
  ADD PRIMARY KEY (`PK_CUSTOMER_DETAILS`),
  ADD KEY `PK_RELATIONSHIP` (`PK_RELATIONSHIP`),
  ADD KEY `PK_CUSTOMER_PRIMARY` (`PK_CUSTOMER_PRIMARY`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`);

--
-- Indexes for table `DOA_CUSTOMER_DOCUMENT`
--
ALTER TABLE `DOA_CUSTOMER_DOCUMENT`
  ADD PRIMARY KEY (`PK_CUSTOMER_DOCUMENT`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`);

--
-- Indexes for table `DOA_CUSTOMER_EMAIL`
--
ALTER TABLE `DOA_CUSTOMER_EMAIL`
  ADD PRIMARY KEY (`PK_CUSTOMER_EMAIL`),
  ADD KEY `PK_CUSTOMER_DETAILS` (`PK_CUSTOMER_DETAILS`);

--
-- Indexes for table `DOA_CUSTOMER_INTEREST`
--
ALTER TABLE `DOA_CUSTOMER_INTEREST`
  ADD PRIMARY KEY (`PK_CUSTOMER_INTEREST`),
  ADD KEY `PK_INTERESTS` (`PK_INTERESTS`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`);

--
-- Indexes for table `DOA_CUSTOMER_INTEREST_OTHER_DATA`
--
ALTER TABLE `DOA_CUSTOMER_INTEREST_OTHER_DATA`
  ADD PRIMARY KEY (`PK_CUSTOMER_INTEREST_OTHER_DATA`),
  ADD KEY `PK_INQUIRY_METHOD` (`PK_INQUIRY_METHOD`),
  ADD KEY `INQUIRY_TAKER_ID` (`INQUIRY_TAKER_ID`),
  ADD KEY `PK_SKILL_LEVEL` (`PK_SKILL_LEVEL`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`);

--
-- Indexes for table `DOA_CUSTOMER_PAYMENT_INFO`
--
ALTER TABLE `DOA_CUSTOMER_PAYMENT_INFO`
  ADD PRIMARY KEY (`PK_CUSTOMER_PAYMENT_INFO`);

--
-- Indexes for table `DOA_CUSTOMER_PHONE`
--
ALTER TABLE `DOA_CUSTOMER_PHONE`
  ADD PRIMARY KEY (`PK_CUSTOMER_PHONE`),
  ADD KEY `PK_CUSTOMER_DETAILS` (`PK_CUSTOMER_DETAILS`);

--
-- Indexes for table `DOA_CUSTOMER_SPECIAL_DATE`
--
ALTER TABLE `DOA_CUSTOMER_SPECIAL_DATE`
  ADD PRIMARY KEY (`PK_CUSTOMER_SPECIAL_DATE`),
  ADD KEY `PK_CUSTOMER_DETAILS` (`PK_CUSTOMER_DETAILS`);

--
-- Indexes for table `DOA_CUSTOMER_WALLET`
--
ALTER TABLE `DOA_CUSTOMER_WALLET`
  ADD PRIMARY KEY (`PK_CUSTOMER_WALLET`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`),
  ADD KEY `PK_PAYMENT_TYPE` (`PK_PAYMENT_TYPE`);

--
-- Indexes for table `DOA_DOCUMENT_LIBRARY`
--
ALTER TABLE `DOA_DOCUMENT_LIBRARY`
  ADD PRIMARY KEY (`PK_DOCUMENT_LIBRARY`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`),
  ADD KEY `PK_DOCUMENT_TYPE` (`PK_DOCUMENT_TYPE`);

--
-- Indexes for table `DOA_DOCUMENT_LOCATION`
--
ALTER TABLE `DOA_DOCUMENT_LOCATION`
  ADD PRIMARY KEY (`PK_DOCUMENT_LOCATION`);

--
-- Indexes for table `DOA_EMAIL`
--
ALTER TABLE `DOA_EMAIL`
  ADD PRIMARY KEY (`PK_EMAIL`),
  ADD KEY `PK_EMAIL_TYPE` (`PK_EMAIL_TYPE`);

--
-- Indexes for table `DOA_EMAIL_ACCOUNT`
--
ALTER TABLE `DOA_EMAIL_ACCOUNT`
  ADD PRIMARY KEY (`PK_EMAIL_ACCOUNT`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_LOCATION`);

--
-- Indexes for table `DOA_EMAIL_ATTACHMENT`
--
ALTER TABLE `DOA_EMAIL_ATTACHMENT`
  ADD PRIMARY KEY (`PK_EMAIL_ATTACHMENT`),
  ADD KEY `PK_EMAIL` (`PK_EMAIL`);

--
-- Indexes for table `DOA_EMAIL_RECEPTION`
--
ALTER TABLE `DOA_EMAIL_RECEPTION`
  ADD PRIMARY KEY (`PK_EMAIL_RECEPTION`),
  ADD KEY `PK_EMAIL` (`PK_EMAIL`);

--
-- Indexes for table `DOA_EMAIL_STARRED`
--
ALTER TABLE `DOA_EMAIL_STARRED`
  ADD PRIMARY KEY (`PK_EMAIL_STARRED`),
  ADD KEY `PK_USER` (`PK_USER`);

--
-- Indexes for table `DOA_EMAIL_STATUS`
--
ALTER TABLE `DOA_EMAIL_STATUS`
  ADD PRIMARY KEY (`PK_EMAIL_STATUS`);

--
-- Indexes for table `DOA_EMAIL_TEMPLATE`
--
ALTER TABLE `DOA_EMAIL_TEMPLATE`
  ADD PRIMARY KEY (`PK_EMAIL_TEMPLATE`),
  ADD KEY `PK_EMAIL_EVENTS` (`PK_EMAIL_TRIGGER`),
  ADD KEY `PK_EMAIL_ACCOUNTS` (`PK_EMAIL_ACCOUNT`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`),
  ADD KEY `PK_TEMPLATE_CATEGORY` (`PK_TEMPLATE_CATEGORY`);

--
-- Indexes for table `DOA_EMAIL_TYPE`
--
ALTER TABLE `DOA_EMAIL_TYPE`
  ADD PRIMARY KEY (`PK_EMAIL_TYPE`);

--
-- Indexes for table `DOA_ENROLLMENT_BALANCE`
--
ALTER TABLE `DOA_ENROLLMENT_BALANCE`
  ADD PRIMARY KEY (`PK_ENROLLMENT_BALANCE`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`);

--
-- Indexes for table `DOA_ENROLLMENT_BILLING`
--
ALTER TABLE `DOA_ENROLLMENT_BILLING`
  ADD PRIMARY KEY (`PK_ENROLLMENT_BILLING`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`);

--
-- Indexes for table `DOA_ENROLLMENT_LEDGER`
--
ALTER TABLE `DOA_ENROLLMENT_LEDGER`
  ADD PRIMARY KEY (`PK_ENROLLMENT_LEDGER`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_BILLING` (`PK_ENROLLMENT_BILLING`),
  ADD KEY `ENROLLMENT_LEDGER_PARENT` (`ENROLLMENT_LEDGER_PARENT`);

--
-- Indexes for table `DOA_ENROLLMENT_MASTER`
--
ALTER TABLE `DOA_ENROLLMENT_MASTER`
  ADD PRIMARY KEY (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`),
  ADD KEY `PK_AGREEMENT_TYPE` (`PK_AGREEMENT_TYPE`),
  ADD KEY `PK_ONBOARDING_DOCUMENT` (`PK_DOCUMENT_LIBRARY`),
  ADD KEY `ENROLLMENT_BY_ID` (`ENROLLMENT_BY_ID`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`),
  ADD KEY `PK_ENROLLMENT_TYPE` (`PK_ENROLLMENT_TYPE`),
  ADD KEY `PK_PACKAGE` (`PK_PACKAGE`);

--
-- Indexes for table `DOA_ENROLLMENT_PAYMENT`
--
ALTER TABLE `DOA_ENROLLMENT_PAYMENT`
  ADD PRIMARY KEY (`PK_ENROLLMENT_PAYMENT`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_BILLING` (`PK_ENROLLMENT_BILLING`),
  ADD KEY `PK_PAYMENT_TYPE` (`PK_PAYMENT_TYPE`),
  ADD KEY `PK_ENROLLMENT_LEDGER` (`PK_ENROLLMENT_LEDGER`);

--
-- Indexes for table `DOA_ENROLLMENT_SERVICE`
--
ALTER TABLE `DOA_ENROLLMENT_SERVICE`
  ADD PRIMARY KEY (`PK_ENROLLMENT_SERVICE`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`),
  ADD KEY `PK_SERVICE_CODE` (`PK_SERVICE_CODE`);

--
-- Indexes for table `DOA_ENROLLMENT_SERVICE_PROVIDER`
--
ALTER TABLE `DOA_ENROLLMENT_SERVICE_PROVIDER`
  ADD PRIMARY KEY (`PK_ENROLLMENT_SERVICE_PROVIDER`);

--
-- Indexes for table `DOA_EVENT`
--
ALTER TABLE `DOA_EVENT`
  ADD PRIMARY KEY (`PK_EVENT`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`),
  ADD KEY `PK_EVENT_TYPE` (`PK_EVENT_TYPE`);

--
-- Indexes for table `DOA_EVENT_IMAGE`
--
ALTER TABLE `DOA_EVENT_IMAGE`
  ADD PRIMARY KEY (`PK_EVENT_IMAGE`),
  ADD KEY `PK_EVENT` (`PK_EVENT`);

--
-- Indexes for table `DOA_EVENT_LOCATION`
--
ALTER TABLE `DOA_EVENT_LOCATION`
  ADD PRIMARY KEY (`PK_EVENT_LOCATION`),
  ADD KEY `PK_EVENT` (`PK_EVENT`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_EVENT_TYPE`
--
ALTER TABLE `DOA_EVENT_TYPE`
  ADD PRIMARY KEY (`PK_EVENT_TYPE`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`);

--
-- Indexes for table `DOA_GIFT_CERTIFICATE_MASTER`
--
ALTER TABLE `DOA_GIFT_CERTIFICATE_MASTER`
  ADD PRIMARY KEY (`PK_GIFT_CERTIFICATE_MASTER`);

--
-- Indexes for table `DOA_GIFT_CERTIFICATE_SETUP`
--
ALTER TABLE `DOA_GIFT_CERTIFICATE_SETUP`
  ADD PRIMARY KEY (`PK_GIFT_CERTIFICATE_SETUP`);

--
-- Indexes for table `DOA_HOLIDAY_LIST`
--
ALTER TABLE `DOA_HOLIDAY_LIST`
  ADD PRIMARY KEY (`PK_HOLIDAY_LIST`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`);

--
-- Indexes for table `DOA_INQUIRY_METHOD`
--
ALTER TABLE `DOA_INQUIRY_METHOD`
  ADD PRIMARY KEY (`PK_INQUIRY_METHOD`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`);

--
-- Indexes for table `DOA_INTERESTS`
--
ALTER TABLE `DOA_INTERESTS`
  ADD PRIMARY KEY (`PK_INTERESTS`),
  ADD KEY `PK_BUSINESS_TYPE` (`PK_BUSINESS_TYPE`);

--
-- Indexes for table `DOA_OPERATIONAL_HOUR`
--
ALTER TABLE `DOA_OPERATIONAL_HOUR`
  ADD PRIMARY KEY (`PK_OPERATIONAL_HOUR`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_ORDER`
--
ALTER TABLE `DOA_ORDER`
  ADD PRIMARY KEY (`PK_ORDER`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`),
  ADD KEY `PK_COUNTRY` (`PK_COUNTRY`),
  ADD KEY `PK_STATES` (`PK_STATES`),
  ADD KEY `PK_PAYMENT_TYPE` (`PK_PAYMENT_TYPE`);

--
-- Indexes for table `DOA_ORDER_ITEM`
--
ALTER TABLE `DOA_ORDER_ITEM`
  ADD PRIMARY KEY (`PK_ORDER_ITEM`),
  ADD KEY `PK_ORDER` (`PK_ORDER`),
  ADD KEY `PK_PRODUCT` (`PK_PRODUCT`),
  ADD KEY `PK_PRODUCT_COLOR` (`PK_PRODUCT_COLOR`),
  ADD KEY `PK_PRODUCT_SIZE` (`PK_PRODUCT_SIZE`);

--
-- Indexes for table `DOA_PACKAGE`
--
ALTER TABLE `DOA_PACKAGE`
  ADD PRIMARY KEY (`PK_PACKAGE`);

--
-- Indexes for table `DOA_PACKAGE_LOCATION`
--
ALTER TABLE `DOA_PACKAGE_LOCATION`
  ADD PRIMARY KEY (`PK_PACKAGE_LOCATION`);

--
-- Indexes for table `DOA_PACKAGE_SERVICE`
--
ALTER TABLE `DOA_PACKAGE_SERVICE`
  ADD PRIMARY KEY (`PK_PACKAGE_SERVICE`),
  ADD KEY `PK_PACKAGE` (`PK_PACKAGE`),
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`),
  ADD KEY `PK_SERVICE_CODE` (`PK_SERVICE_CODE`);

--
-- Indexes for table `DOA_PRODUCT`
--
ALTER TABLE `DOA_PRODUCT`
  ADD PRIMARY KEY (`PK_PRODUCT`);

--
-- Indexes for table `DOA_PRODUCT_COLOR`
--
ALTER TABLE `DOA_PRODUCT_COLOR`
  ADD PRIMARY KEY (`PK_PRODUCT_COLOR`);

--
-- Indexes for table `DOA_PRODUCT_SIZE`
--
ALTER TABLE `DOA_PRODUCT_SIZE`
  ADD PRIMARY KEY (`PK_PRODUCT_SIZE`);

--
-- Indexes for table `DOA_REPORT_EXPORT_DETAILS`
--
ALTER TABLE `DOA_REPORT_EXPORT_DETAILS`
  ADD PRIMARY KEY (`PK_REPORT_EXPORT_DETAILS`);

--
-- Indexes for table `DOA_SCHEDULING_CODE`
--
ALTER TABLE `DOA_SCHEDULING_CODE`
  ADD PRIMARY KEY (`PK_SCHEDULING_CODE`);

--
-- Indexes for table `DOA_SCHEDULING_SERVICE`
--
ALTER TABLE `DOA_SCHEDULING_SERVICE`
  ADD PRIMARY KEY (`PK_SCHEDULING_SERVICE`);

--
-- Indexes for table `DOA_SERVICE_CODE`
--
ALTER TABLE `DOA_SERVICE_CODE`
  ADD PRIMARY KEY (`PK_SERVICE_CODE`),
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`);

--
-- Indexes for table `DOA_SERVICE_COMMISSION`
--
ALTER TABLE `DOA_SERVICE_COMMISSION`
  ADD PRIMARY KEY (`PK_SERVICE_COMMISSION`),
  ADD KEY `PK_SERVICE_CODE` (`PK_SERVICE_MASTER`),
  ADD KEY `PK_USER` (`PK_USER`);

--
-- Indexes for table `DOA_SERVICE_DOCUMENTS`
--
ALTER TABLE `DOA_SERVICE_DOCUMENTS`
  ADD PRIMARY KEY (`PK_SERVICE_DOCUMENTS`),
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`),
  ADD KEY `PK_LOCATION_DETAILS` (`PK_LOCATION`);

--
-- Indexes for table `DOA_SERVICE_LOCATION`
--
ALTER TABLE `DOA_SERVICE_LOCATION`
  ADD PRIMARY KEY (`PK_SERVICE_LOCATION`);

--
-- Indexes for table `DOA_SERVICE_MASTER`
--
ALTER TABLE `DOA_SERVICE_MASTER`
  ADD PRIMARY KEY (`PK_SERVICE_MASTER`),
  ADD KEY `PK_SERVICE_CLASS` (`PK_SERVICE_CLASS`);

--
-- Indexes for table `DOA_SERVICE_PROVIDER_AMOUNT`
--
ALTER TABLE `DOA_SERVICE_PROVIDER_AMOUNT`
  ADD PRIMARY KEY (`PK_SERVICE_PROVIDER_AMOUNT`);

--
-- Indexes for table `DOA_SERVICE_PROVIDER_LOCATION_HOURS`
--
ALTER TABLE `DOA_SERVICE_PROVIDER_LOCATION_HOURS`
  ADD PRIMARY KEY (`PK_SERVICE_PROVIDER_LOCATION_HOURS`),
  ADD KEY `PK_USER` (`PK_USER`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_SERVICE_SCHEDULING_CODE`
--
ALTER TABLE `DOA_SERVICE_SCHEDULING_CODE`
  ADD PRIMARY KEY (`PK_SERVICE_BOOKING_CODE`);

--
-- Indexes for table `DOA_SKILL_LEVEL`
--
ALTER TABLE `DOA_SKILL_LEVEL`
  ADD PRIMARY KEY (`PK_SKILL_LEVEL`);

--
-- Indexes for table `DOA_SPECIAL_APPOINTMENT`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT`
  ADD PRIMARY KEY (`PK_SPECIAL_APPOINTMENT`),
  ADD KEY `PK_APPOINTMENT_STATUS` (`PK_APPOINTMENT_STATUS`);

--
-- Indexes for table `DOA_SPECIAL_APPOINTMENT_CUSTOMER`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT_CUSTOMER`
  ADD PRIMARY KEY (`PK_SPECIAL_APPOINTMENT_CUSTOMER`);

--
-- Indexes for table `DOA_SPECIAL_APPOINTMENT_USER`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT_USER`
  ADD PRIMARY KEY (`PK_SPECIAL_APPOINTMENT_USER`),
  ADD KEY `PK_SPECIAL_APPOINTMENT` (`PK_SPECIAL_APPOINTMENT`),
  ADD KEY `PK_USER` (`PK_USER`);

--
-- Indexes for table `DOA_UPDATE_HISTORY`
--
ALTER TABLE `DOA_UPDATE_HISTORY`
  ADD PRIMARY KEY (`PK_UPDATE_HISTORY`);

--
-- Indexes for table `DOA_USERS`
--
ALTER TABLE `DOA_USERS`
  ADD PRIMARY KEY (`PK_USER`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`);

--
-- Indexes for table `DOA_USER_DOCUMENT`
--
ALTER TABLE `DOA_USER_DOCUMENT`
  ADD PRIMARY KEY (`PK_USER_DOCUMENT`);

--
-- Indexes for table `DOA_USER_RATE`
--
ALTER TABLE `DOA_USER_RATE`
  ADD PRIMARY KEY (`PK_USER_RATE`);

--
-- Indexes for table `DOA_Z_HELP`
--
ALTER TABLE `DOA_Z_HELP`
  ADD PRIMARY KEY (`PK_HELP`);

--
-- Indexes for table `DOA_Z_HELP_FILES`
--
ALTER TABLE `DOA_Z_HELP_FILES`
  ADD PRIMARY KEY (`PK_HELP_FILES`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_CUSTOMER`
--
ALTER TABLE `DOA_APPOINTMENT_CUSTOMER`
  MODIFY `PK_APPOINTMENT_CUSTOMER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_ENROLLMENT`
--
ALTER TABLE `DOA_APPOINTMENT_ENROLLMENT`
  MODIFY `PK_APPOINTMENT_ENROLLMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_MASTER`
--
ALTER TABLE `DOA_APPOINTMENT_MASTER`
  MODIFY `PK_APPOINTMENT_MASTER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_SERVICE_PROVIDER`
--
ALTER TABLE `DOA_APPOINTMENT_SERVICE_PROVIDER`
  MODIFY `PK_APPOINTMENT_SERVICE_PROVIDER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_STATUS_HISTORY`
--
ALTER TABLE `DOA_APPOINTMENT_STATUS_HISTORY`
  MODIFY `PK_APPOINTMENT_STATUS_HISTORY` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_COMMENT`
--
ALTER TABLE `DOA_COMMENT`
  MODIFY `PK_COMMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_DETAILS`
--
ALTER TABLE `DOA_CUSTOMER_DETAILS`
  MODIFY `PK_CUSTOMER_DETAILS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_DOCUMENT`
--
ALTER TABLE `DOA_CUSTOMER_DOCUMENT`
  MODIFY `PK_CUSTOMER_DOCUMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_EMAIL`
--
ALTER TABLE `DOA_CUSTOMER_EMAIL`
  MODIFY `PK_CUSTOMER_EMAIL` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_INTEREST`
--
ALTER TABLE `DOA_CUSTOMER_INTEREST`
  MODIFY `PK_CUSTOMER_INTEREST` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_INTEREST_OTHER_DATA`
--
ALTER TABLE `DOA_CUSTOMER_INTEREST_OTHER_DATA`
  MODIFY `PK_CUSTOMER_INTEREST_OTHER_DATA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_PAYMENT_INFO`
--
ALTER TABLE `DOA_CUSTOMER_PAYMENT_INFO`
  MODIFY `PK_CUSTOMER_PAYMENT_INFO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_PHONE`
--
ALTER TABLE `DOA_CUSTOMER_PHONE`
  MODIFY `PK_CUSTOMER_PHONE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_SPECIAL_DATE`
--
ALTER TABLE `DOA_CUSTOMER_SPECIAL_DATE`
  MODIFY `PK_CUSTOMER_SPECIAL_DATE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_CUSTOMER_WALLET`
--
ALTER TABLE `DOA_CUSTOMER_WALLET`
  MODIFY `PK_CUSTOMER_WALLET` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_DOCUMENT_LIBRARY`
--
ALTER TABLE `DOA_DOCUMENT_LIBRARY`
  MODIFY `PK_DOCUMENT_LIBRARY` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_DOCUMENT_LOCATION`
--
ALTER TABLE `DOA_DOCUMENT_LOCATION`
  MODIFY `PK_DOCUMENT_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL`
--
ALTER TABLE `DOA_EMAIL`
  MODIFY `PK_EMAIL` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_ACCOUNT`
--
ALTER TABLE `DOA_EMAIL_ACCOUNT`
  MODIFY `PK_EMAIL_ACCOUNT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_ATTACHMENT`
--
ALTER TABLE `DOA_EMAIL_ATTACHMENT`
  MODIFY `PK_EMAIL_ATTACHMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_RECEPTION`
--
ALTER TABLE `DOA_EMAIL_RECEPTION`
  MODIFY `PK_EMAIL_RECEPTION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_STARRED`
--
ALTER TABLE `DOA_EMAIL_STARRED`
  MODIFY `PK_EMAIL_STARRED` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_STATUS`
--
ALTER TABLE `DOA_EMAIL_STATUS`
  MODIFY `PK_EMAIL_STATUS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_TEMPLATE`
--
ALTER TABLE `DOA_EMAIL_TEMPLATE`
  MODIFY `PK_EMAIL_TEMPLATE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EMAIL_TYPE`
--
ALTER TABLE `DOA_EMAIL_TYPE`
  MODIFY `PK_EMAIL_TYPE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_BALANCE`
--
ALTER TABLE `DOA_ENROLLMENT_BALANCE`
  MODIFY `PK_ENROLLMENT_BALANCE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_BILLING`
--
ALTER TABLE `DOA_ENROLLMENT_BILLING`
  MODIFY `PK_ENROLLMENT_BILLING` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_LEDGER`
--
ALTER TABLE `DOA_ENROLLMENT_LEDGER`
  MODIFY `PK_ENROLLMENT_LEDGER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_MASTER`
--
ALTER TABLE `DOA_ENROLLMENT_MASTER`
  MODIFY `PK_ENROLLMENT_MASTER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_PAYMENT`
--
ALTER TABLE `DOA_ENROLLMENT_PAYMENT`
  MODIFY `PK_ENROLLMENT_PAYMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_SERVICE`
--
ALTER TABLE `DOA_ENROLLMENT_SERVICE`
  MODIFY `PK_ENROLLMENT_SERVICE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_SERVICE_PROVIDER`
--
ALTER TABLE `DOA_ENROLLMENT_SERVICE_PROVIDER`
  MODIFY `PK_ENROLLMENT_SERVICE_PROVIDER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EVENT`
--
ALTER TABLE `DOA_EVENT`
  MODIFY `PK_EVENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EVENT_IMAGE`
--
ALTER TABLE `DOA_EVENT_IMAGE`
  MODIFY `PK_EVENT_IMAGE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EVENT_LOCATION`
--
ALTER TABLE `DOA_EVENT_LOCATION`
  MODIFY `PK_EVENT_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_EVENT_TYPE`
--
ALTER TABLE `DOA_EVENT_TYPE`
  MODIFY `PK_EVENT_TYPE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_GIFT_CERTIFICATE_MASTER`
--
ALTER TABLE `DOA_GIFT_CERTIFICATE_MASTER`
  MODIFY `PK_GIFT_CERTIFICATE_MASTER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_GIFT_CERTIFICATE_SETUP`
--
ALTER TABLE `DOA_GIFT_CERTIFICATE_SETUP`
  MODIFY `PK_GIFT_CERTIFICATE_SETUP` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_HOLIDAY_LIST`
--
ALTER TABLE `DOA_HOLIDAY_LIST`
  MODIFY `PK_HOLIDAY_LIST` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_INQUIRY_METHOD`
--
ALTER TABLE `DOA_INQUIRY_METHOD`
  MODIFY `PK_INQUIRY_METHOD` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_INTERESTS`
--
ALTER TABLE `DOA_INTERESTS`
  MODIFY `PK_INTERESTS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_OPERATIONAL_HOUR`
--
ALTER TABLE `DOA_OPERATIONAL_HOUR`
  MODIFY `PK_OPERATIONAL_HOUR` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ORDER`
--
ALTER TABLE `DOA_ORDER`
  MODIFY `PK_ORDER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ORDER_ITEM`
--
ALTER TABLE `DOA_ORDER_ITEM`
  MODIFY `PK_ORDER_ITEM` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PACKAGE`
--
ALTER TABLE `DOA_PACKAGE`
  MODIFY `PK_PACKAGE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PACKAGE_LOCATION`
--
ALTER TABLE `DOA_PACKAGE_LOCATION`
  MODIFY `PK_PACKAGE_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PACKAGE_SERVICE`
--
ALTER TABLE `DOA_PACKAGE_SERVICE`
  MODIFY `PK_PACKAGE_SERVICE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PRODUCT`
--
ALTER TABLE `DOA_PRODUCT`
  MODIFY `PK_PRODUCT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PRODUCT_COLOR`
--
ALTER TABLE `DOA_PRODUCT_COLOR`
  MODIFY `PK_PRODUCT_COLOR` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_PRODUCT_SIZE`
--
ALTER TABLE `DOA_PRODUCT_SIZE`
  MODIFY `PK_PRODUCT_SIZE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_REPORT_EXPORT_DETAILS`
--
ALTER TABLE `DOA_REPORT_EXPORT_DETAILS`
  MODIFY `PK_REPORT_EXPORT_DETAILS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SCHEDULING_CODE`
--
ALTER TABLE `DOA_SCHEDULING_CODE`
  MODIFY `PK_SCHEDULING_CODE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SCHEDULING_SERVICE`
--
ALTER TABLE `DOA_SCHEDULING_SERVICE`
  MODIFY `PK_SCHEDULING_SERVICE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_CODE`
--
ALTER TABLE `DOA_SERVICE_CODE`
  MODIFY `PK_SERVICE_CODE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_COMMISSION`
--
ALTER TABLE `DOA_SERVICE_COMMISSION`
  MODIFY `PK_SERVICE_COMMISSION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_DOCUMENTS`
--
ALTER TABLE `DOA_SERVICE_DOCUMENTS`
  MODIFY `PK_SERVICE_DOCUMENTS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_LOCATION`
--
ALTER TABLE `DOA_SERVICE_LOCATION`
  MODIFY `PK_SERVICE_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_MASTER`
--
ALTER TABLE `DOA_SERVICE_MASTER`
  MODIFY `PK_SERVICE_MASTER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_PROVIDER_AMOUNT`
--
ALTER TABLE `DOA_SERVICE_PROVIDER_AMOUNT`
  MODIFY `PK_SERVICE_PROVIDER_AMOUNT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_PROVIDER_LOCATION_HOURS`
--
ALTER TABLE `DOA_SERVICE_PROVIDER_LOCATION_HOURS`
  MODIFY `PK_SERVICE_PROVIDER_LOCATION_HOURS` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SERVICE_SCHEDULING_CODE`
--
ALTER TABLE `DOA_SERVICE_SCHEDULING_CODE`
  MODIFY `PK_SERVICE_BOOKING_CODE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SKILL_LEVEL`
--
ALTER TABLE `DOA_SKILL_LEVEL`
  MODIFY `PK_SKILL_LEVEL` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SPECIAL_APPOINTMENT`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT`
  MODIFY `PK_SPECIAL_APPOINTMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SPECIAL_APPOINTMENT_CUSTOMER`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT_CUSTOMER`
  MODIFY `PK_SPECIAL_APPOINTMENT_CUSTOMER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_SPECIAL_APPOINTMENT_USER`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT_USER`
  MODIFY `PK_SPECIAL_APPOINTMENT_USER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_UPDATE_HISTORY`
--
ALTER TABLE `DOA_UPDATE_HISTORY`
  MODIFY `PK_UPDATE_HISTORY` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_USERS`
--
ALTER TABLE `DOA_USERS`
  MODIFY `PK_USER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_USER_DOCUMENT`
--
ALTER TABLE `DOA_USER_DOCUMENT`
  MODIFY `PK_USER_DOCUMENT` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_USER_RATE`
--
ALTER TABLE `DOA_USER_RATE`
  MODIFY `PK_USER_RATE` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_Z_HELP`
--
ALTER TABLE `DOA_Z_HELP`
  MODIFY `PK_HELP` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_Z_HELP_FILES`
--
ALTER TABLE `DOA_Z_HELP_FILES`
  MODIFY `PK_HELP_FILES` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


INSERT INTO `DOA_DOCUMENT_LIBRARY` (`PK_DOCUMENT_LIBRARY`, `PK_DOCUMENT_TYPE`, `DOCUMENT_NAME`, `DOCUMENT_TEMPLATE`, `ACTIVE`, `CREATED_ON`, `CREATED_BY`, `EDITED_ON`, `EDITED_BY`) VALUES (NULL, '1', 'Enrollment Template', '<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>CLIENT ENROLLMENT AGREEMENT</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>Please Print</td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:auto\">Client</th>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:5cm\">Full Name</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:center\"><strong>{FULL_NAME}</strong></td>\r\n <th>Street Address</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:center\"><strong>{STREET_ADD}</strong></td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left\">City</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:5cm\">{CITY}</td>\r\n <th>State</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:5cm\">{STATE}</td>\r\n <th>Zip</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:5cm\">{ZIP}</td>\r\n <th>Res. Phone</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:5cm\">&nbsp;</td>\r\n <th>Cell Phone</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:5cm\">{CELL_PHONE}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:left; width:91%\">The Client agrees to purchase and the owner of {BUSINESS_NAME} agrees to provide the following course of dance instruction and/or services on the following terms and conditions including the terms and conditions on the reverse side of this agreement.</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">NAME/TYPE OF ENROLLMENT</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">SERVICES</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">PRIVATE LESSON(S)</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">TUITION</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">DISCOUNT</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">BALANCE DUE</td>\r\n </tr>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{TYPE_OF_ENROLLMENT}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{SERVICE_DETAILS}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{PVT_LESSONS}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{TUITION}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{DISCOUNT}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{BAL_DUE}</td>\r\n </tr>\r\n <tr>\r\n </tr>\r\n <tr>\r\n <td colspan=\"5\" style=\"border-style:solid; border-width:0px\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"5\" style=\"border-style:solid; border-width:0px; text-align:center; vertical-align:middle\">Lessons are 45 minutes which includes transition time between lessons</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"height:auto; text-align:left; width:91%\">The Client acknowledges the above of {CASH_PRICE}&nbsp;for the instruction and/or services(s) described above and agrees to pay {DOWN_PAYMENTS} on {FIRST_DATE} and the remaining cash balance of {REMAINING_BALANCE}, which includes any applicable previous balance and service charge as shown below, in {NO_AMT_PAYMENT} Installments of {INSTALLMENT_AMOUNT} starting on .</td>\r\n <td>&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td style=\"height:auto; text-align:left; width:91%\">The lesson rates in this agreement are: Private Instruction $189.00 per lesson, Class Instruction $0.00 per lesson, and there is no charge for Party Practice units when included.</td>\r\n <td>&nbsp;</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"width:50%\">\r\n <table>\r\n <tbody>\r\n <tr>\r\n <th style=\"width:9cm\">1. Cash Price of This Course</th>\r\n <td style=\"border-bottom:1px solid #cccccc; vertical-align:bottom\">{CASH_PRICE}</td>\r\n </tr>\r\n <tr>\r\n <th style=\"width:9cm\">2. Down Payment(s)</th>\r\n <td style=\"border-bottom:1px solid #cccccc; vertical-align:bottom\">{DOWN_PAYMENTS}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:left; width:99%\">You have the right at this time to receive an itemization of the amount financed, which is shown in the left column of this document</td>\r\n <td>&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td style=\"width:auto\">\r\n <p>Your payment schedule will be</p>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Date of Payment</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Amount of Payment</td>\r\n </tr>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{DUE_DATE}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{BILLED_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <p>and on the same date each month thereafter until paid in full</p>\r\n\r\n <p>Notice to Buyer: Do not sign this agreement before you read it or if it contains any blank spaces. You are entitled to a copy of the agreement you sign. Keep this agreement to protect your legal rights.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"width:50%\">\r\n <table>\r\n <tbody>\r\n <tr>\r\n <th style=\"width:9cm\">Amount to be Scheduled\r\n <p>The amount of tuition to be scheduled as installments:</p>\r\n </th>\r\n <td style=\"border-bottom:0px solid #cccccc; height:auto; text-align:center; vertical-align:bottom; width:93px\">{SCHEDULE_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>\r\n <p>If you pay off early, you:</p>\r\n\r\n <p><strong>Will not</strong> have to pay a penalty</p>\r\n\r\n <p><strong>May</strong> be entitled to a refund of part of the service Charge, under rule of 78, prorata or a method whichever is applicable in your state.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center\">CLIENT ACKNOWLEDGES RECEIPT OF AN EXACT COPY OF THIS RETAIL INSTALLMENT AGREEMENT.</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"border-bottom:0px solid #cccccc; height:auto\">It is agreed that the Studio&#39;s obligation for furnishing instructions under this agreement shall expire on 12/07/2024 or three years from the date of this agreement whichever occurs first.</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th rowspan=\"8\" style=\"text-align:center; width:8cm\"><strong>{BUSINESS_NAME}</strong><br />\r\n {BUSINESS_ADD}<br />\r\n {BUSINESS_CITY}<br />\r\n {BUSINESS_STATE},&nbsp;{BUSINESS_ZIP}&nbsp; {BUSINESS_COUNTRY} &nbsp;{BUSINESS_PHONE}</th>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:center\">&nbsp;</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th style=\"text-align:center\">&nbsp;</th>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:center\">__________________________</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th style=\"text-align:center\">__________________________</th>\r\n </tr>\r\n <tr>\r\n <th>Client&#39;s Signature</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th>Studio Representative</th>\r\n </tr>\r\n <tr>\r\n <td style=\"height:50px\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:center\">&nbsp;</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th style=\"text-align:center\">&nbsp;</th>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:center\">__________________________</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th style=\"text-align:center\">__________________________</th>\r\n </tr>\r\n <tr>\r\n <th>Co-Client or Guardian</th>\r\n <th style=\"width:15px\">&nbsp;</th>\r\n <th>Verified by</th>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n', '1', '2023-09-05 14:56:00', '14551', '2024-11-19 08:19:00', '5')
";

