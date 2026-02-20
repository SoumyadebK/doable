<?php
$create_database = "--
-- Table structure for table `DOA_APPOINTMENT_CUSTOMER`
--

CREATE TABLE `DOA_APPOINTMENT_CUSTOMER` (
  `PK_APPOINTMENT_CUSTOMER` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL DEFAULT 0,
  `PK_USER_MASTER` int(11) NOT NULL DEFAULT 0,
  `IS_PARTNER` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`
--

CREATE TABLE `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY` (
  `PK_APPOINTMENT_CUSTOMER_UPDATE_HISTORY` int(11) NOT NULL,
  `PK_APPOINTMENT_MASTER` int(11) NOT NULL,
  `DETAILS` text NOT NULL
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
  `IMAGE_2` varchar(255) DEFAULT NULL,
  `VIDEO` varchar(255) DEFAULT NULL,
  `VIDEO_2` varchar(255) DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL,
  `STATUS` enum('A','C') NOT NULL DEFAULT 'A' COMMENT '''A''->Active,''C''->Cancelled',
  `IS_PAID` tinyint(4) NOT NULL DEFAULT 0,
  `IS_CHARGED` tinyint(4) NOT NULL DEFAULT 0,
  `APPOINTMENT_TYPE` varchar(100) DEFAULT NULL,
  `IS_REMINDER_SEND` tinyint(4) NOT NULL DEFAULT 0,
  `IS_FROM_AI_CALL` tinyint(4) NOT NULL DEFAULT 0,
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
  `CUSTOMER_WALLET_PARENT` int(11) NOT NULL DEFAULT 0,
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
  `IS_DELETED` tinyint(4) NOT NULL DEFAULT 0,
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
  `INTERNAL_ID` int(11) DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `PK_EMAIL_TYPE` int(11) DEFAULT NULL,
  `DRAFT` int(11) NOT NULL DEFAULT 0,
  `SUBJECT` varchar(300) DEFAULT NULL,
  `CONTENT` longtext DEFAULT NULL,
  `REMINDER_DATE` date DEFAULT NULL,
  `DUE_DATE` date DEFAULT NULL,
  `EMAIL_FOR` varchar(100) DEFAULT NULL,
  `ID` int(11) NOT NULL DEFAULT 0,
  `PARENT_ID` int(11) NOT NULL DEFAULT 0,
  `PK_EMAIL_STATUS` int(11) DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime DEFAULT NULL
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
-- Table structure for table `DOA_EMAIL_ACCOUNT_LOCATION`
--

CREATE TABLE `DOA_EMAIL_ACCOUNT_LOCATION` (
  `PK_EMAIL_ACCOUNT_LOCATION` int(11) NOT NULL,
  `PK_EMAIL_ACCOUNT` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_ATTACHMENT`
--

CREATE TABLE `DOA_EMAIL_ATTACHMENT` (
  `PK_EMAIL_ATTACHMENT` int(11) NOT NULL,
  `PK_EMAIL` int(11) DEFAULT NULL,
  `FILE_NAME` varchar(250) DEFAULT NULL,
  `LOCATION` varchar(500) DEFAULT NULL,
  `UPLOADED_ON` datetime DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_RECEPTION`
--

CREATE TABLE `DOA_EMAIL_RECEPTION` (
  `PK_EMAIL_RECEPTION` int(11) NOT NULL,
  `PK_EMAIL` int(11) DEFAULT NULL,
  `INTERNAL_ID` int(11) DEFAULT NULL,
  `PK_USER` int(11) DEFAULT NULL,
  `VIWED` int(11) DEFAULT NULL,
  `REPLY` int(11) DEFAULT NULL,
  `DELETED` int(11) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_STARRED`
--

CREATE TABLE `DOA_EMAIL_STARRED` (
  `PK_EMAIL_STARRED` int(11) NOT NULL,
  `INTERNAL_ID` int(11) DEFAULT NULL,
  `PK_USER` int(11) DEFAULT NULL,
  `PK_EMAIL` int(11) DEFAULT NULL,
  `STARRED` int(11) DEFAULT NULL,
  `CREATED_ON` datetime DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_EMAIL_STATUS`
--

CREATE TABLE `DOA_EMAIL_STATUS` (
  `PK_EMAIL_STATUS` int(11) NOT NULL,
  `EMAIL_STATUS` varchar(250) DEFAULT NULL,
  `ACTIVE` int(11) DEFAULT NULL
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
  `EMAIL_TYPE` varchar(250) DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL DEFAULT 1,
  `CREATED_ON` datetime DEFAULT NULL,
  `CREATED_BY` int(11) DEFAULT NULL,
  `EDITED_ON` datetime DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL
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
-- Table structure for table `DOA_ENROLLMENT_CANCEL`
--

CREATE TABLE `DOA_ENROLLMENT_CANCEL` (
  `PK_ENROLLMENT_CANCEL` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_SERVICE` int(11) NOT NULL,
  `ACTUAL_AMOUNT` float(9,2) NOT NULL,
  `CANCEL_AMOUNT` float(9,2) NOT NULL,
  `CANCEL_DATE` datetime NOT NULL
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
-- Table structure for table `DOA_ENROLLMENT_LOG`
--

CREATE TABLE `DOA_ENROLLMENT_LOG` (
  `PK_ENROLLMENT_LOG` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `OPERATION` varchar(255) NOT NULL,
  `DETAILS` text NOT NULL,
  `CREATED_AT` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL
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
  `ACTIVE_AUTO_PAY` tinyint(4) NOT NULL DEFAULT 0,
  `PAYMENT_METHOD_ID` varchar(255) DEFAULT NULL,
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
  `PK_ORDER` int(11) DEFAULT NULL,
  `PK_CUSTOMER_WALLET` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `TYPE` varchar(25) DEFAULT NULL,
  `IS_REFUNDED` tinyint(4) NOT NULL DEFAULT 0,
  `AMOUNT` float(9,2) NOT NULL,
  `NOTE` text DEFAULT NULL,
  `PAYMENT_DATE` date NOT NULL,
  `PAYMENT_INFO` text NOT NULL,
  `PAYMENT_STATUS` varchar(50) DEFAULT NULL,
  `RECEIPT_NUMBER` varchar(255) DEFAULT NULL,
  `IS_ORIGINAL_RECEIPT` tinyint(4) NOT NULL DEFAULT 1,
  `RECEIPT_PDF_LINK` varchar(255) DEFAULT NULL,
  `IS_EXPORTED_TO_AMI` tinyint(4) NOT NULL DEFAULT 0,
  `NOT_EXPORT_TO_AMI` tinyint(4) NOT NULL
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
  `STATUS` varchar(50) DEFAULT NULL COMMENT '''A''->Active,''C''->Cancelled, ''CA''->Cancelled But Active',
  `ORIGINAL_SESSION_COUNT` int(11) DEFAULT NULL,
  `ORIGINAL_AMOUNT` float(7,2) DEFAULT NULL
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
-- Table structure for table `DOA_GIFT_LOCATION`
--

CREATE TABLE `DOA_GIFT_LOCATION` (
  `PK_GIFT_LOCATION` int(11) NOT NULL,
  `PK_GIFT_CERTIFICATE_SETUP` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `PK_LOCATION` int(11) DEFAULT NULL,
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
  `PK_LOCATION` int(11) NOT NULL,
  `ID` varchar(255) NOT NULL,
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
  `PK_LOCATION` int(11) DEFAULT NULL,
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
  `PK_LOCATION` int(11) DEFAULT NULL,
  `IS_DEFAULT` int(11) DEFAULT NULL,
  `SERVICE_CODE` varchar(100) DEFAULT NULL,
  `DESCRIPTION` varchar(255) DEFAULT NULL,
  `IS_GROUP` tinyint(4) NOT NULL DEFAULT 0,
  `IS_SUNDRY` tinyint(4) NOT NULL DEFAULT 0,
  `CAPACITY` int(11) DEFAULT 0,
  `IS_CHARGEABLE` tinyint(4) NOT NULL DEFAULT 0,
  `PRICE` float(9,2) NOT NULL DEFAULT 0.00,
  `ACTIVE` int(1) DEFAULT NULL,
  `COUNT_ON_CALENDAR` tinyint(4) NOT NULL DEFAULT 1,
  `SORT_ORDER` int(11) DEFAULT NULL
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
  `PK_LOCATION` int(11) DEFAULT NULL,
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
-- Table structure for table `DOA_SMS_LOG`
--

CREATE TABLE `DOA_SMS_LOG` (
  `PK_SMS_LOG` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `PHONE_NUMBER` varchar(50) NOT NULL,
  `MESSAGE` text NOT NULL,
  `IS_ERROR` tinyint(4) NOT NULL,
  `ERROR_MESSAGE` text NOT NULL,
  `TRIGGER_TIME` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Indexes for table `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`
--
ALTER TABLE `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`
  ADD PRIMARY KEY (`PK_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`);

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
  ADD KEY `PK_LOCATION` (`PK_LOCATION`),
  ADD KEY `DATE` (`DATE`,`STATUS`,`IS_PAID`,`IS_CHARGED`,`CREATED_ON`);

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
-- Indexes for table `DOA_EMAIL_ACCOUNT_LOCATION`
--
ALTER TABLE `DOA_EMAIL_ACCOUNT_LOCATION`
  ADD PRIMARY KEY (`PK_EMAIL_ACCOUNT_LOCATION`);

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
-- Indexes for table `DOA_ENROLLMENT_CANCEL`
--
ALTER TABLE `DOA_ENROLLMENT_CANCEL`
  ADD PRIMARY KEY (`PK_ENROLLMENT_CANCEL`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_SERVICE` (`PK_ENROLLMENT_SERVICE`);

--
-- Indexes for table `DOA_ENROLLMENT_LEDGER`
--
ALTER TABLE `DOA_ENROLLMENT_LEDGER`
  ADD PRIMARY KEY (`PK_ENROLLMENT_LEDGER`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_BILLING` (`PK_ENROLLMENT_BILLING`),
  ADD KEY `ENROLLMENT_LEDGER_PARENT` (`ENROLLMENT_LEDGER_PARENT`);

--
-- Indexes for table `DOA_ENROLLMENT_LOG`
--
ALTER TABLE `DOA_ENROLLMENT_LOG`
  ADD PRIMARY KEY (`PK_ENROLLMENT_LOG`);

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
  ADD KEY `PK_PACKAGE` (`PK_PACKAGE`),
  ADD KEY `ACTIVE` (`ACTIVE`,`STATUS`,`ENROLLMENT_DATE`,`ALL_APPOINTMENT_DONE`);

--
-- Indexes for table `DOA_ENROLLMENT_PAYMENT`
--
ALTER TABLE `DOA_ENROLLMENT_PAYMENT`
  ADD PRIMARY KEY (`PK_ENROLLMENT_PAYMENT`),
  ADD KEY `PK_ENROLLMENT_MASTER` (`PK_ENROLLMENT_MASTER`),
  ADD KEY `PK_ENROLLMENT_BILLING` (`PK_ENROLLMENT_BILLING`),
  ADD KEY `PK_PAYMENT_TYPE` (`PK_PAYMENT_TYPE`),
  ADD KEY `PK_ENROLLMENT_LEDGER` (`PK_ENROLLMENT_LEDGER`),
  ADD KEY `PK_ORDER` (`PK_ORDER`),
  ADD KEY `PK_CUSTOMER_WALLET` (`PK_CUSTOMER_WALLET`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

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
-- Indexes for table `DOA_GIFT_LOCATION`
--
ALTER TABLE `DOA_GIFT_LOCATION`
  ADD PRIMARY KEY (`PK_GIFT_LOCATION`);

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
  ADD PRIMARY KEY (`PK_PACKAGE`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

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
  ADD PRIMARY KEY (`PK_REPORT_EXPORT_DETAILS`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_SCHEDULING_CODE`
--
ALTER TABLE `DOA_SCHEDULING_CODE`
  ADD PRIMARY KEY (`PK_SCHEDULING_CODE`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

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
  ADD KEY `PK_SERVICE_MASTER` (`PK_SERVICE_MASTER`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

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
  ADD KEY `PK_SERVICE_CLASS` (`PK_SERVICE_CLASS`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

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
-- Indexes for table `DOA_SMS_LOG`
--
ALTER TABLE `DOA_SMS_LOG`
  ADD PRIMARY KEY (`PK_SMS_LOG`),
  ADD KEY `PK_USER_MASTER` (`PK_USER_MASTER`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`);

--
-- Indexes for table `DOA_SPECIAL_APPOINTMENT`
--
ALTER TABLE `DOA_SPECIAL_APPOINTMENT`
  ADD PRIMARY KEY (`PK_SPECIAL_APPOINTMENT`),
  ADD KEY `PK_APPOINTMENT_STATUS` (`PK_APPOINTMENT_STATUS`),
  ADD KEY `PK_LOCATION` (`PK_LOCATION`,`DATE`,`PK_SCHEDULING_CODE`);

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
-- AUTO_INCREMENT for table `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`
--
ALTER TABLE `DOA_APPOINTMENT_CUSTOMER_UPDATE_HISTORY`
  MODIFY `PK_APPOINTMENT_CUSTOMER_UPDATE_HISTORY` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_EMAIL_ACCOUNT_LOCATION`
--
ALTER TABLE `DOA_EMAIL_ACCOUNT_LOCATION`
  MODIFY `PK_EMAIL_ACCOUNT_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_CANCEL`
--
ALTER TABLE `DOA_ENROLLMENT_CANCEL`
  MODIFY `PK_ENROLLMENT_CANCEL` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_LEDGER`
--
ALTER TABLE `DOA_ENROLLMENT_LEDGER`
  MODIFY `PK_ENROLLMENT_LEDGER` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_LOG`
--
ALTER TABLE `DOA_ENROLLMENT_LOG`
  MODIFY `PK_ENROLLMENT_LOG` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_GIFT_LOCATION`
--
ALTER TABLE `DOA_GIFT_LOCATION`
  MODIFY `PK_GIFT_LOCATION` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_SMS_LOG`
--
ALTER TABLE `DOA_SMS_LOG`
  MODIFY `PK_SMS_LOG` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

INSERT INTO `DOA_DOCUMENT_LIBRARY` (`PK_DOCUMENT_TYPE`, `DOCUMENT_NAME`, `DOCUMENT_TEMPLATE`, `ACTIVE`) VALUES ('1', 'Enrollment Template', '<div style="background-color: white; color: black; page-break-after: always; padding: 20px;"><table style="width:100%; color: black;"><tbody><tr><td style="text-align:center; font-size:1.5em; font-weight:bold; color: black;">CLIENT ENROLLMENT AGREEMENT</td></tr></tbody></table><table style="width:100%; margin-top: 15px; color: black;"><tbody><tr><td style="font-style: italic; color: black;">Please Print</td></tr><tr><td><table style="width:100%; margin-top: 10px; color: black;"><tbody><tr><th style="text-align:left; width:auto; color: black;">Client</th></tr><tr><th style="text-align:left; width:5cm; color: black;">Full Name</th><td style="border-bottom:1px solid #cccccc; text-align:center; color: black;"><strong>{FULL_NAME}</strong></td><th style="color: black;">Street Address</th><td style="border-bottom:1px solid #cccccc; text-align:center; color: black;"><strong>{STREET_ADD}</strong></td></tr></tbody></table><table style="width:100%; margin-top: 10px; color: black;"><tbody><tr><th style="text-align:left; width:10%; color: black;">City</th><td style="border-bottom:1px solid #cccccc; height:auto; text-align:center; width:20%; color: black;">{CITY}</td><th style="width:10%; padding-left: 10px; color: black;">State</th><td style="border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%; color: black;">{STATE}</td><th style="width:5%; padding-left: 10px; color: black;">Zip</th><td style="border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%; color: black;">{ZIP}</td><th style="width:10%; padding-left: 10px; color: black;">Res. Phone</th><td style="border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%; color: black;">&nbsp;</td><th style="width:10%; padding-left: 10px; color: black;">Cell Phone</th><td style="border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%; color: black;">{CELL_PHONE}</td></tr></tbody></table><table style="width:100%; margin-top: 15px; color: black;"><tbody><tr><td style="text-align:left; width:100%; color: black;">The Client agrees to purchase and the owner of Arthur Murray Woodland Hills agrees to provide the following course of dance instruction and/or services on the following terms and conditions including the terms and conditions on the reverse side of this agreement.</td></tr></tbody></table><table style="width:100%; margin-top: 15px; border-collapse: collapse; color: black;"><thead><tr><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">NAME/TYPE OF ENROLLMENT</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">SERVICES</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">PRIVATE LESSON(S)</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">TUITION</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">DISCOUNT</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">BALANCE DUE</td></tr></thead><tbody><tr><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{TYPE_OF_ENROLLMENT}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{SERVICE_DETAILS}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{PVT_LESSONS}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{TUITION}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{DISCOUNT}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{BAL_DUE}</td></tr></tbody></table><table style="width:100%; margin-top: 15px; color: black;"><tbody><tr><td style="height:auto; text-align:left; width:100%; color: black;">The Client acknowledges the above of {CASH_PRICE}&nbsp;for the instruction and/or services(s) described above and agrees to pay {DOWN_PAYMENTS} on {FIRST_DATE} and the remaining cash balance of {REMAINING_BALANCE}, which includes any applicable previous balance and service charge as shown below, in {NO_AMT_PAYMENT} Installments of {INSTALLMENT_AMOUNT} starting on .</td></tr><tr><td style="height:auto; text-align:left; width:100%; margin-top: 5px; color: black;">The lesson rates in this agreement are: Private Instruction $189.00 per lesson, Class Instruction $0.00 per lesson, and there is no charge for Party Practice units when included.</td></tr></tbody></table><table style="width:100%; margin-top: 20px; border-collapse: separate; border-spacing: 0; color: black;"><tbody><tr><td style="width:50%; vertical-align: top; padding-right: 10px; color: black;"><table style="width:100%; color: black;"><tbody><tr><th style="width:65%; text-align:left; padding-right: 10px; color: black;">1. Cash Price of This Course</th><td style="border-bottom:1px solid #cccccc; vertical-align:bottom; text-align: right; color: black;">{CASH_PRICE}</td></tr><tr><th style="width:65%; text-align:left; padding-right: 10px; color: black;">2. Down Payment(s)</th><td style="border-bottom:1px solid #cccccc; vertical-align:bottom; text-align: right; color: black;">{DOWN_PAYMENTS}</td></tr></tbody></table></td><td style="width:50%; vertical-align: top; padding-left: 10px; color: black;"><table style="width:100%; color: black;"><tbody><tr><td style="text-align:left; width:100%; color: black;">You have the right at this time to receive an itemization of the amount financed, which is shown in the left column of this document</td></tr><tr><td><p style="margin-top: 10px; margin-bottom: 5px; color: black;">Your payment schedule will be</p><table style="width:100%; border-collapse: collapse; color: black;"><tbody><tr><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">Date of Payment</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold; color: black;">Amount of Payment</td></tr><tr><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{DUE_DATE}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px; color: black;">{BILLED_AMOUNT}</td></tr></tbody></table><p style="margin-top: 5px; margin-bottom: 5px; color: black;">and on the same date each month thereafter until paid in full</p><p style="font-weight: bold; margin-top: 10px; color: black;">Notice to Buyer: Do not sign this agreement before you read it or if it contains any blank spaces. You are entitled to a copy of the agreement you sign. Keep this agreement to protect your legal rights.</p></td></tr></tbody></table></td></tr></tbody></table><table style="width:100%; margin-top: 20px; color: black;"><tbody><tr><td style="width:50%; vertical-align:top; padding-right: 10px; color: black;"><table style="width:100%; color: black;"><tbody><tr><th style="width:65%; text-align:left; vertical-align: top; color: black;">Amount to be Scheduled<p style="font-weight: normal; margin-top: 0; margin-bottom: 0; color: black;">The amount of tuition to be scheduled as installments:</p></th><td style="border-bottom:1px solid #cccccc; height:auto; text-align: right; vertical-align:bottom; color: black;">{SCHEDULE_AMOUNT}</td></tr></tbody></table></td><td style="vertical-align:top; width:50%; padding-left: 10px; color: black;"><table style="width:100%; color: black;"><tbody><tr><td><p style="margin-top: 0; margin-bottom: 5px; color: black;">If you pay off early, you:</p><p style="margin-top: 0; margin-bottom: 0; color: black;"><strong>Will not</strong> have to pay a penalty</p><p style="margin-top: 5px; margin-bottom: 0; color: black;"><strong>May</strong> be entitled to a refund of part of the service Charge, under rule of 78, prorata or a method whichever is applicable in your state.</p></td></tr></tbody></table></td></tr><tr><td colspan="2" style="border-bottom:1px solid #cccccc; height:auto; text-align:center; padding-top: 15px; font-weight: bold; color: black;">CLIENT ACKNOWLEDGES RECEIPT OF AN EXACT COPY OF THIS RETAIL INSTALLMENT AGREEMENT.</td></tr><tr><td colspan="2" style="height:auto; padding-top: 10px; color: black;">It is agreed that the Studio's obligation for furnishing instructions under this agreement shall expire on {EXPIRATION_DATE} or three years from the date of this agreement whichever occurs first.</td></tr></tbody></table></td></tr><tr><td><table style="width:100%; margin-top: 30px; color: black;"><tbody><tr><th rowspan="8" style="text-align:center; width:45%; vertical-align: top; color: black;"><strong>{BUSINESS_NAME}</strong><br />{BUSINESS_ADD}<br />{BUSINESS_CITY}<br />{BUSINESS_STATE},&nbsp;{BUSINESS_ZIP}&nbsp; {BUSINESS_COUNTRY} &nbsp;{BUSINESS_PHONE}</th><td style="width:10%"></td><th style="text-align:center; width:20%; border-top: 1px solid black; color: black;">&nbsp;</th><td style="width:10%"></td><th style="text-align:center; width:20%; border-top: 1px solid black; color: black;">&nbsp;</th></tr><tr><td></td><th style="text-align:center; font-weight: normal; color: black;">Client's Signature</th><td></td><th style="text-align:center; font-weight: normal; color: black;">Studio Representative</th></tr><tr><td style="height:30px" colspan="4">&nbsp;</td></tr><tr><td></td><th style="text-align:center; width:20%; border-top: 1px solid black; color: black;">&nbsp;</th><td></td><th style="text-align:center; width:20%; border-top: 1px solid black; color: black;">&nbsp;</th></tr><tr><td></td><th style="text-align:center; font-weight: normal; color: black;">Co-Client or Guardian</th><td></td><th style="text-align:center; font-weight: normal; color: black;">Verified by</th></tr></tbody></table></td></tr></tbody></table></div><div style="background-color: white; color: black; padding: 20px;"><h3 style="text-align: center; margin-top: 0; color: black;">TERMS AND CONDITIONS</h3><table style="width:100%; border-collapse: collapse; color: black;"><tbody><tr><td style="vertical-align:top; width:3%; padding-right: 5px; color: black;">A.</td><td style="text-align:left; width:97%; padding-bottom: 10px; color: black;">The Studio agrees to provide this course of instruction and/or services in accordance with the Arthur Murray&reg; method of dance instruction and to make its facilities and personnel available by individual independent appointment for each lesson or service for such purposes during the term of this agreement. The Studio may provide the Student with any instructor employed by the Studio and is not obligated to provide any specific instructor nor to provide the same instuctor for different lessons.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">B.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Instructions shall be available commencing this date and shall not be charged against this enrollment until the completion of all previous enrollments, if any. Performance of the agreed upon lessons and instructions shall begin within six months from this date. All lessons are 45 minutes long, which includes transition time between lessons. The rates for lessons calculated on an hourly basis equal the lesson rates set forth in this agreement multiplied by 1.33.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">C.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Private lessons to be made available by the Studio, shall expire whether actually used or not upon the agreed expiration date for all instruction or lessons under this agreement. All group lessons (if charged) and Video Tape Studies shall expire over the same period as the private lessons. The teaching or honoring of any lessons and/or services beyond the term of expiration shall not be deemed as a waiver of this expiration provision by the Studio.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">D.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Student agrees to complete all lessons and/or services as expressly provided in this agreement. Student shall not be relieved of the obligation to make any payment agreed to, and no eduction or allowance for any payments shall be made by reason of Student's failure to use any lessons and/or services, except as provided.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">E.</td><td style="text-align:left; padding-bottom: 10px; color: black;">This agreement is subject to cancellation at any time during the term of the Agreement upon notification by the Student to the Studio as set forth herein. In the event that this Agreement is cancelled, the Studio shall calculate the refund on the contract, if any, on a pro rata basis. The Studio shall refund any moneys owed to the Student within 10 days of receiving the cancellation notice, as specified within paragraph "G" below,unless the Student owes the Studio money for lessons or other services received prior to the cancellation, in which case any moneys owed to the Studio shall be deducted by the Studio from the refund owed to the Student and the balance, If any, shall be refunded as specified above. The Studio shall charge no cancellation fee, or other fee, for cancellation of the contract by the Student.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">F.</td><td style="text-align:left; padding-bottom: 10px; color: black;">If other than an original enrollment, this agreement, if for dance instruction, is subject to cancellation by the Student on the same terms and basis as set forth above.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">G.</td><td style="text-align:left; padding-bottom: 10px; color: black;">"Notice of cancellation" shall be deemed to have been provided by a Student or prospective Student by mailing or delivering written notification to cancel the contract or written agreement to the Studio at the address specified herein, or by failing to attend instructional facilities for a period of five consecutive appointment days on which classes or the provision of services which are the subject of the contract or written agreement were prearranged with the Student or prospective Student.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">H.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Unless otherwise stated in this agreement and for refund when applicable, there is no charge for providing group lessons, practice sessions, parties or complimentary services offered by the Studio and it is agreed that the tuition is based solely upon the number of private lessons of instruction, the use of video equipment and expressly paid-for services.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">I.</td><td style="text-align:left; padding-bottom: 10px; color: black;">The Studio may assign this agreement and all monies due shall be paid directly to such third party upon notification.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">J.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Student agrees to notify the Studio at least 12 hours in advance to cancel or change any private appointment or be charged for such lessons.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">K.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Student's rights under this agreement are personal in nature and may not be sold, assigned or transferred to any other person. If by reason of death or disability, Student is unable to receive all lessons and other services for which he or she has contracted, Student and his or her estate shall be relieved from the obligation of making payments for lessons and other services other than those received prior to death or the onset of disability, and that if Student has prepaid any sum for lessons and other services so much of that sum as is allocatable to lessons and other services he or she has not taken shall be promptly refunded to Student or his or her representative. In the event of Student's death or disability, the Student or his representative may also sell, donate or transfer the remaining lessons and/or services to any persons or charity subject to Studio approval. Student lessons may be transferred to any other Arthur Murray&reg; Franchised Dance Studio beyond twenty-five miles from this Studio.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">L.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Student agrees not to associate with any Studio instructor and other personnel outside the Studio or to give or loan anything of value to any Studio personnel during the term of this agreement and for a one year period thereafter. To protect the Studio from unfair competition by any Studio personnel, Student also agrees not to directly or indirectly aid or assist such personnel to engage in any capacity in the teaching of dance lessons or providing services which employ the know-how or knowledge used as an employee of the Studio within a 25-mile radius of the Studio or to solicit other students or personnel of the Studio for such purpose during the term of this agreement and for a one year period thereafter. II is agreed by Student that violation of this paragraph shall be expressly damaging to the Studio and shall release Studio from obligatory terms of this agreement and provide grounds for damages by the Studio.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">M.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Student represents to the Studio that (s)he is physically able to take and financially able to pay for this course of instruction and/or services, has read and fully understands the terms of this agreement, has signed the agreement voluntarily and hereby acknowledges receipt of a fully executed copy.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">N.</td><td style="text-align:left; padding-bottom: 10px; color: black;">ANY HOLDER OF THIS CONSUMER CREDIT CONTRACT IS SUBJECT TO ALL CLAIMS AND DEFENSES WHICH THE DEBTOR COULD ASSERT AGAINST THE SELLER OF GOODS OR SERVICES OBTAINED PURSUANT HERETO OR WITH THE PROCEEDS HEREOF. RECOVERY HEREUNDER BY THE DEBTOR SHALL NOT EXCEED AMOUNTS PAID BY THE DEBTOR HEREUNDER.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">O.</td><td style="text-align:left; padding-bottom: 10px; color: black;">Any controversy or claim arising out of or relating to this agreement shall be settled solely by arbitration in accordance with the commercial arbitration rules of the American Arbitration Association, and judgment upon the award rendered by the arbitrator may be entered in any court having jurisdiction. All fees and expenses in connection with the arbitration shall be shared equally by the parties. Any action or arbitration on or related to this Agreement must be brought within the applicable statutory period.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">P.</td><td style="text-align:left; padding-bottom: 10px; color: black;">The Federal Equal Credit Opportunity Act prohibits creditors from discriminating against credit applicants on the basis of sex or marital status. The Federal agency which administers compliance with this law is the F.T.C., Washington, D.C.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">Q.</td><td style="text-align:left; padding-bottom: 10px; color: black;">AS STUDENT, I UNDERSTAND AND AGREE THAT THIS AGREEMENT IS MADE BY ME SOLELY WITH THE OWNER OF THE STUDIO, AS SELLER, AND DOES NOT DIRECTLY OR INDIRECTLY CONSTITUTE AN AGREEMENT WITH OR AN OBLIGATION OF ARTHUR MURRAY INTERNATIONAL, INC., OR AS THE STUDIO FRANCHISOR. ARTHUR MURRAY INTERNATIONAL, INC. IS NOT THE OWNER OF THIS STUDIO. SHOULD THIS AGREEMENT COMBINED WITH THE COST OF STUDENT'S OTHER UNUSED LESSONS AND/OR SERVICES, EXCEED $20,000.00 OR 200 ENROLLED PRIVATE LESSONS OR UNITS WHICHEVER OCCURS FIRST, OR THE MAXIMUM PERMITIED BY LAW, WHICHEVER IS LESS, THIS AGREEMENT IS VOID.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">R.</td><td style="text-align:left; padding-bottom: 10px; color: black;">If any particular provision of this Agreement is held invalid or unenforceable by an arbitrator or court of competent jurisdiction, such invalidity shall not affect the other provisions of this Agreement.</td></tr><tr><td style="vertical-align:top; padding-right: 5px; color: black;">S.</td><td style="text-align:left; padding-bottom: 10px; color: black;">No other representations or provisions. either written or oral, are a part of this Agreement, unless expressed herein.</td></tr></tbody></table></div>', '1');
INSERT INTO `DOA_DOCUMENT_LIBRARY` (`PK_DOCUMENT_TYPE`, `DOCUMENT_NAME`, `DOCUMENT_TEMPLATE`, `ACTIVE`) VALUES ('3', 'Miscellaneous Agreement', '<div style="font-family: Arial, sans-serif; color: black; line-height: 1.15; font-size: 9pt;"><div style="padding: 5px; page-break-after: always;"><table style="width:100%; margin-bottom: 5px;"><tbody><tr><td style="text-align:center; font-size: 14pt; font-weight: bold;">CLIENT ENROLLMENT AGREEMENT</td></tr></tbody></table><table style="width:100%; margin-bottom: 5px;"><tbody><tr><td style="padding-bottom: 5px;">Please Print</td></tr><tr><td><table cellspacing="0" style="border-collapse:collapse; width:100%;"><tbody><tr><th style="text-align:left; width:5%; padding-bottom: 5px;">Client</th><td colspan="3" style="width:95%">&nbsp;</td></tr><tr><th style="text-align:left; width:15%;">Full Name</th><td style="border-bottom:1px solid black; text-align:left; width:35%; padding-bottom: 3px;"><strong>{FULL_NAME}</strong></td><th style="width:15%; padding-left: 10px;">Street Address</th><td style="border-bottom:1px solid black; text-align:left; width:35%; padding-bottom: 3px;"><strong>{STREET_ADD}</strong></td></tr></tbody></table><table cellspacing="0" style="border-collapse:collapse; width:100%; margin-top: 5px;"><tbody><tr><th style="text-align:left; width:8%;">City</th><td style="border-bottom:1px solid black; height:auto; text-align:left; width:18%; padding-bottom: 3px;">{CITY}</td><th style="width:8%; padding-left: 10px;">State</th><td style="border-bottom:1px solid black; height:auto; text-align:left; width:10%; padding-bottom: 3px;">{STATE}</td><th style="width:5%; padding-left: 10px;">Zip</th><td style="border-bottom:1px solid black; height:auto; text-align:left; width:10%; padding-bottom: 3px;">{ZIP}</td><th style="width:10%; padding-left: 10px;">Res. Phone</th><td style="border-bottom:1px solid black; height:auto; text-align:left; width:15%; padding-bottom: 3px;">&nbsp;</td><th style="width:10%; padding-left: 10px;">Cell Phone</th><td style="border-bottom:1px solid black; height:auto; text-align:left; width:15%; padding-bottom: 3px;">{CELL_PHONE}</td></tr></tbody></table><p style="margin-top: 10px; margin-bottom: 10px; line-height: 1.2;">The Client agrees to purchase and the owner of Arthur Murray Woodland Hills agrees to provide the following course of dance instruction and/or services on the following terms and conditions including the terms and conditions on the reverse side of this agreement.</p><table cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom: 10px;"><tbody><tr><td colspan="4" style="text-align:center; vertical-align:middle; padding: 5px 0; font-weight: bold;">Lessons are 45 minutes which includes transition time between lessons</td></tr><tr><td colspan="3" style="background-color:#f0f0f0; border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold;">ENROLLED ON MISCELLANEOUS SERVICES</td><td style="background-color:#f0f0f0; border:1px solid #cccccc; text-align:center; padding: 5px; font-weight: bold;">TUITION OR COST</td></tr><tr><td colspan="3" style="border:1px solid #cccccc; text-align:center; padding: 5px;">{SERVICE_DETAILS}</td><td style="border:1px solid #cccccc; text-align:center; padding: 5px;">{TUITION}</td></tr></tbody></table><p style="margin-top: 5px; margin-bottom: 5px; line-height: 1.2;">The Client acknowledges the above of {CASH_PRICE}&nbsp;for the instruction and/or services(s) described above and agrees to pay {DOWN_PAYMENTS} on {BILLING_DATE} and the remaining cash balance of {REMAINING_BALANCE}, which includes any applicable previous balance and service charge as shown below, in {NO_AMT_PAYMENT} installments of {INSTALLMENT_AMOUNT} starting on {STARTING_DATE}.</p><p style="margin-top: 5px; margin-bottom: 15px; line-height: 1.2;">The lesson rates in this agreement are: Private Instruction $189.00 per lesson, Class Instruction $0.00 per lesson, and there is no charge for Party Practice units when included.</p><table style="border-collapse:separate; width:100%;"><tbody><tr><td style="vertical-align:top; width:50%; padding-right: 10px;"><table cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom: 10px;"><tbody><tr><th style="text-align:left; width:65%; font-weight: normal; padding-top: 3px; padding-bottom: 3px;">1. Cash Price of This Course</th><td style="border-bottom:1px solid black; text-align:right; vertical-align:bottom; width:35%; padding-top: 3px; padding-bottom: 3px;">{CASH_PRICE}</td></tr><tr><th style="text-align:left; width:65%; font-weight: normal; padding-top: 3px; padding-bottom: 3px;">2. Down Payment(s)</th><td style="border-bottom:1px solid black; text-align:right; vertical-align:bottom; width:35%; padding-top: 3px; padding-bottom: 3px;">{DOWN_PAYMENTS}</td></tr></tbody></table></td><td style="vertical-align:top; width:50%;"><table cellspacing="0" style="border-collapse:collapse; width:100%;"><tbody><tr><td style="text-align:left; font-size: 8pt; padding-bottom: 5px;">You have the right at this time to receive an itemization of the amount financed, which is shown in the left column of this document</td></tr><tr><td><p style="margin: 0 0 5px 0;">Your payment schedule will be</p><table cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom: 5px;"><tbody><tr><td style="background-color:#f0f0f0; border:1px solid #cccccc; text-align:center; padding: 3px; font-weight: bold; font-size: 8pt;">Date of Payment</td><td style="background-color:#f0f0f0; border:1px solid #cccccc; text-align:center; padding: 3px; font-weight: bold; font-size: 8pt;">Amount of Payment</td></tr><tr><td style="border:1px solid #cccccc; text-align:center; padding: 3px; font-size: 8pt;">{DUE_DATE}</td><td style="border:1px solid #cccccc; text-align:center; padding: 3px; font-size: 8pt;">{BILLED_AMOUNT}</td></tr></tbody></table><p style="margin-top: 5px; margin-bottom: 5px;">and on the same date each month thereafter until paid in full</p><p style="font-size: 8pt; margin-top: 0; margin-bottom: 0;">Notice to Buyer: Do not sign this agreement before you read it or if it contains any blank spaces. You are entitled to a copy of the agreement you sign. Keep this agreement to protect your legal rights.</p></td></tr></tbody></table></td></tr></tbody></table><table style="border-collapse:separate; width:100%; margin-top: 10px;"><tbody><tr><td style="vertical-align:top; width:50%; padding-right: 10px;"><table cellspacing="0" style="border-collapse:collapse; width:100%;"><tbody><tr><th style="text-align:left; vertical-align:top; width:65%; font-weight: normal; padding-top: 3px;">Amount to be Scheduled<p style="font-size: 8pt; line-height: 1.1; margin-top: 2px; margin-bottom: 0; font-weight: normal;">The amount of tuition to be scheduled as installments:</p></th><td style="border-bottom:1px solid black; height:auto; text-align:right; vertical-align:bottom; width:35%; padding-top: 3px;">{SCHEDULE_AMOUNT}</td></tr></tbody></table></td><td style="vertical-align:top; width:50%;"><table cellspacing="0" style="border-collapse:collapse; width:100%;"><tbody><tr><td style="font-size: 8pt;"><p style="margin-top: 0; margin-bottom: 5px;">If you pay off early, you:</p><p style="margin-top: 0; margin-bottom: 5px;"><strong>Will not</strong> have to pay a penalty</p><p style="margin-top: 0; margin-bottom: 5px;"><strong>May</strong> be entitled to a refund of part of the service Charge, under rule of 78, prorata or a method whichever is applicable in your state.</p></td></tr></tbody></table></td></tr><tr><td colspan="2" style="border-bottom:1px solid black; height:auto; text-align:center; font-weight: bold; padding: 5px 0; margin-top: 5px;">CLIENT ACKNOWLEDGES RECEIPT OF AN EXACT COPY OF THIS RETAIL INSTALLMENT AGREEMENT.</td></tr><tr><td colspan="2" style="height:auto; padding-top: 5px; padding-bottom: 20px;">It is agreed that the Studio&#39;s obligation for furnishing instructions under this agreement shall expire on {EXPIRATION_DATE} or three years from the date of this agreement whichever occurs first.</td></tr></tbody></table></td></tr><tr><td><table cellspacing="0" style="border-collapse:collapse; width:100%; font-size: 8pt; margin-top: 5px;"><tbody><tr><th rowspan="6" style="text-align:left; vertical-align:top; width:45%; line-height: 1.3; padding-right: 10px;"><p style="font-weight: bold; margin-bottom: 3px; margin-top: 0;">{BUSINESS_NAME}</p>{BUSINESS_ADD}<br />{BUSINESS_CITY}<br />{BUSINESS_STATE},&nbsp;{BUSINESS_ZIP}&nbsp; {BUSINESS_COUNTRY} &nbsp;{BUSINESS_PHONE}</th><td style="width:5%">&nbsp;</td><td style="border-top:1px solid black; text-align:center; width:25%">&nbsp;</td><td style="width:5%">&nbsp;</td><td style="border-top:1px solid black; text-align:center; width:25%">&nbsp;</td></tr><tr><td>&nbsp;</td><td style="text-align:center; padding-top: 2px;">Client&#39;s Signature</td><td>&nbsp;</td><td style="text-align:center; padding-top: 2px;">Studio Representative</td></tr><tr><td colspan="5" style="height:5px;">&nbsp;</td></tr><tr><td>&nbsp;</td><td style="border-top:1px solid black; text-align:center">&nbsp;</td><td>&nbsp;</td><td style="border-top:1px solid black; text-align:center">&nbsp;</td></tr><tr><td>&nbsp;</td><td style="text-align:center">Co-Client or Guardian</td><td>&nbsp;</td><td style="text-align:center">Verified by</td></tr></tbody></table></td></tr></tbody></table></div><div style="margin: 0; padding: 0 5px; font-size: 6pt; line-height: 1.0;"><h3 style="text-align: center; margin-top: 0; margin-bottom: 3px; font-size: 11pt;">TERMS AND CONDITIONS</h3><table cellspacing="0" style="border-collapse:collapse; width:100%;"><tbody><tr><td style="vertical-align:top; width:3%; padding: 0;">A.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">The Studio agrees to provide this course of instruction and/or services in accordance with the Arthur Murray&reg; method of dance instruction and to make its facilities and personnel available by individual independent appointment for each lesson or service for such purposes during the term of this agreement. The Studio may provide the Student with any instructor employed by the Studio and is not obligated to provide any specific instructor nor to provide the same instuctor for different lessons.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">B.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Instructions shall be available commencing this date and shall not be charged against this enrollment until the completion of all previous enrollments, if any. Performance of the agreed upon lessons and instructions shall begin within six months from this date. All lessons are 45 minutes long, which includes transition time between lessons. The rates for lessons calculated on an hourly basis equal the lesson rates set forth in this agreement multiplied by 1.33.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">C.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Private lessons to be made available by the Studio, shall expire whether actually used or not upon the agreed expiration date for all instruction or lessons under this agreement. All group lessons (if charged) and Video Tape Studies shall expire over the same period as the private lessons. The teaching or honoring of any lessons and/or services beyond the term of expiration shall not be deemed as a waiver of this expiration provision by the Studio.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">D.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Student agrees to complete all lessons and/or services as expressly provided in this agreement. Student shall not be relieved of the obligation to make any payment agreed to, and no eduction or allowance for any payments shall be made by reason of Student&#39;s failure to use any lessons and/or services, except as provided.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">E.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">This agreement is subject to cancellation at any time during the term of the Agreement upon notification by the Student to the Studio as set forth herein. In the event that this Agreement is cancelled, the Studio shall calculate the refund on the contract, if any, on a pro rata basis. The Studio shall refund any moneys owed to the Student within 10 days of receiving the cancellation notice, as specified within paragraph &quot;G&quot; below,unless the Student owes the Studio money for lessons or other services received prior to the cancellation, in which case any moneys owed to the Studio shall be deducted by the Studio from the refund owed to the Student and the balance, If any, shall be refunded as specified above. The Studio shall charge no cancellation fee, or other fee, for cancellation of the contract by the Student.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">F.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">If other than an original enrollment, this agreement, if for dance instruction, is subject to cancellation by the Student on the same terms and basis as set forth above.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">G.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">&quot;Notice of cancellation&quot; shall be deemed to have been provided by a Student or prospective Student by mailing or delivering written notification to cancel the contract or written agreement to the Studio at the address specified herein, or by failing to attend instructional facilities for a period of five consecutive appointment days on which classes or the provision of services which are the subject of the contract or written agreement were prearranged with the Student or prospective Student.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">H.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Unless otherwise stated in this agreement and for refund when applicable, there is no charge for providing group lessons, practice sessions, parties or complimentary services offered by the Studio and it is agreed that the tuition is based solely upon the number of private lessons of instruction, the use of video equipment and expressly paid-for services.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">I.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">The Studio may assign this agreement and all monies due shall be paid directly to such third party upon notification.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">J.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Student agrees to notify the Studio at least 12 hours in advance to cancel or change any private appointment or be charged for such lessons.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">K.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Student&#39;s rights under this agreement are personal in nature and may not be sold, assigned or transferred to any other person. If by reason of death or disability, Student is unable to receive all lessons and other services for which he or she has contracted, Student and his or her estate shall be relieved from the obligation of making payments for lessons and other services other than those received prior to death or the onset of disability, and that if Student has prepaid any sum for lessons and other services so much of that sum as is allocatable to lessons and other services he or she has not taken shall be promptly refunded to Student or his or her representative. In the event of Student&#39;s death or disability, the Student or his representative may also sell, donate or transfer the remaining lessons and/or services to any persons or charity subject to Studio approval. Student lessons may be transferred to any other Arthur Murray&reg; Franchised Dance Studio beyond twenty-five miles from this Studio.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">L.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Student agrees not to associate with any Studio instructor and other personnel outside the Studio or to give or loan anything of value to any Studio personnel during the term of this agreement and for a one year period thereafter. To protect the Studio from unfair competition by any Studio personnel, Student also agrees not to directly or indirectly aid or assist such personnel to engage in any capacity in the teaching of dance lessons or providing services which employ the know-how or knowledge used as an employee of the Studio within a 25-mile radius of the Studio or to solicit other students or personnel of the Studio for such purpose during the term of this agreement and for a one year period thereafter. II is agreed by Student that violation of this paragraph shall be expressly damaging to the Studio and shall release Studio from obligatory terms of this agreement and provide grounds for damages by the Studio.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">M.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Student represents to the Studio that (s)he is physically able to take and financially able to pay for this course of instruction and/or services, has read and fully understands the terms of this agreement, has signed the agreement voluntarily and hereby acknowledges receipt of a fully executed copy.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">N.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">ANY HOLDER OF THIS CONSUMER CREDIT CONTRACT IS SUBJECT TO ALL CLAIMS AND DEFENSES WHICH THE DEBTOR COULD ASSERT AGAINST THE SELLER OF GOODS OR SERVICES OBTAINED PURSUANT HERETO OR WITH THE PROCEEDS HEREOF. RECOVERY HEREUNDER BY THE DEBTOR SHALL NOT EXCEED AMOUNTS PAID BY THE DEBTOR HEREUNDER.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">O.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">Any controversy or claim arising out of or relating to this agreement shall be settled solely by arbitration in accordance with the commercial arbitration rules of the American Arbitration Association, and judgment upon the award rendered by the arbitrator may be entered in any court having jurisdiction. All fees and expenses in connection with the arbitration shall be shared equally by the parties. Any action or arbitration on or related to this Agreement must be brought within the applicable statutory period.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">P.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">The Federal Equal Credit Opportunity Act prohibits creditors from discriminating against credit applicants on the basis of sex or marital status. The Federal agency which administers compliance with this law is the F.T.C., Washington, D.C.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">Q.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">AS STUDENT, I UNDERSTAND AND AGREE THAT THIS AGREEMENT IS MADE BY ME SOLELY WITH THE OWNER OF THE STUDIO, AS SELLER, AND DOES NOT DIRECTLY OR INDIRECTLY CONSTITUTE AN AGREEMENT WITH OR AN OBLIGATION OF ARTHUR MURRAY INTERNATIONAL, INC., OR AS THE STUDIO FRANCHISOR. ARTHUR MURRAY INTERNATIONAL, INC. IS NOT THE OWNER OF THIS STUDIO, SHOULD THIS AGREEMENT COMBINED WITH THE COST OF STUDENT&#39;S OTHER UNUSED LESSONS AND/OR SERVICES, EXCEED $20,000.00 OR 200 ENROLLED PRIVATE LESSONS OR UNITS WHICHEVER OCCURS FIRST, OR THE MAXIMUM PERMITIED BY LAW, WHICHEVER IS LESS, THIS AGREEMENT IS VOID.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">R.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 1px 0;">If any particular provision of this Agreement is held invalid or unenforceable by an arbitrator or court of competent jurisdiction, such invalidity shall not affect the other provisions of this Agreement.</p></td></tr><tr><td style="vertical-align:top; width:3%; padding: 0;">S.</td><td style="text-align:left; width:97%; padding: 0;"><p style="margin: 0 0 0 0;">No other representations or provisions. either written or oral, are a part of this Agreement, unless expressed herein.</p></td></tr></tbody></table></div></div>', '1');
";

$create_store_procedure = 'DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `getCalendarAppointments`(IN `p_location_ids` VARCHAR(255), IN `p_status_ids` VARCHAR(255), IN `p_date_condition` VARCHAR(255), IN `p_type_condition` VARCHAR(255), IN `p_service_provider_condition` VARCHAR(255))
BEGIN
    SET @sql = CONCAT("
        SELECT
            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE AS APT_ENR_SERVICE,
            DOA_APPOINTMENT_MASTER.GROUP_NAME,
            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
            DOA_APPOINTMENT_MASTER.DATE,
            DOA_APPOINTMENT_MASTER.START_TIME,
            DOA_APPOINTMENT_MASTER.END_TIME,
            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
            DOA_APPOINTMENT_MASTER.IS_PAID,
            DOA_APPOINTMENT_MASTER.COMMENT,
            DOA_APPOINTMENT_MASTER.INTERNAL_COMMENT,
            DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER,
            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
            DOA_SERVICE_MASTER.SERVICE_NAME,
            DOA_SERVICE_CODE.SERVICE_CODE,
            DOA_APPOINTMENT_MASTER.IS_PAID,
            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
            DOA_APPOINTMENT_STATUS.STATUS_CODE,
            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
            DOA_SCHEDULING_CODE.COLOR_CODE,
            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
            DOA_SCHEDULING_CODE.DURATION,
            DOA_SCHEDULING_CODE.UNIT,
            GROUP_CONCAT(DISTINCT(DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER) SEPARATOR \',\') AS SERVICE_PROVIDER_ID,
            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, \' \', CUSTOMER.LAST_NAME)) SEPARATOR \', \') AS CUSTOMER_NAME,
            DOA_PACKAGE.PACKAGE_NAME
        FROM
            DOA_APPOINTMENT_MASTER
        LEFT JOIN DOA_APPOINTMENT_CUSTOMER 
            ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
        LEFT JOIN DOA_MASTER.DOA_USER_MASTER AS DOA_USER_MASTER 
            ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
        LEFT JOIN DOA_MASTER.DOA_USERS AS CUSTOMER 
            ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER 
            ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT 
            ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER 
            AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = \'GROUP\'
        LEFT JOIN DOA_ENROLLMENT_MASTER AS APT_ENR 
            ON DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = APT_ENR.PK_ENROLLMENT_MASTER 
            AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = \'GROUP\'
        LEFT JOIN DOA_SCHEDULING_CODE 
            ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
        LEFT JOIN DOA_SERVICE_MASTER 
            ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
        LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS 
            ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
        LEFT JOIN DOA_ENROLLMENT_MASTER 
            ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
        LEFT JOIN DOA_SERVICE_CODE 
            ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
        LEFT JOIN DOA_PACKAGE 
            ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE
        WHERE (CUSTOMER.IS_DELETED = 0 OR CUSTOMER.IS_DELETED IS NULL)
        AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (", p_location_ids, ")
        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN (", p_status_ids, ")
        ", p_date_condition, "
        ", p_type_condition, "
        AND DOA_APPOINTMENT_MASTER.STATUS = \'A\' 
        ", p_service_provider_condition, "
        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC
    ");

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER;';
