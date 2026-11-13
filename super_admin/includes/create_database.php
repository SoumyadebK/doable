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
-- Table structure for table `DOA_AUTOMATIONS`
--

CREATE TABLE `DOA_AUTOMATIONS` (
  `PK_AUTOMATION_ID` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `TITLE` varchar(250) NOT NULL,
  `IS_ACTIVE` tinyint(1) NOT NULL DEFAULT 1,
  `TRIGGER_TYPE` varchar(100) DEFAULT 'customer_completes_class',
  `TRIGGER_VALUE` varchar(250) DEFAULT 'trial_class',
  `CONDITION_TYPE` varchar(100) DEFAULT 'customer_not_purchased_contract',
  `CONDITION_VALUE` text DEFAULT NULL,
  `SCHEDULE_TYPE` enum('simple','custom') DEFAULT 'custom',
  `START_REMINDER_VALUE` int(11) DEFAULT 3,
  `START_REMINDER_UNIT` varchar(20) DEFAULT 'Days',
  `MAX_REMINDERS` int(11) DEFAULT 5,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_AUTOMATION_LOG`
--

CREATE TABLE `DOA_AUTOMATION_LOG` (
  `PK_AUTOMATION_LOG` int(11) NOT NULL,
  `PK_AUTOMATION_ID` int(11) NOT NULL,
  `PK_MESSAGE_ID` int(11) NOT NULL,
  `MESSAGE` text DEFAULT NULL,
  `TYPE` varchar(50) DEFAULT NULL,
  `PK_VALUE` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `LAST_CLASS_SP_ID` text DEFAULT NULL,
  `LAST_ENROLLMENT_SP_ID` text DEFAULT NULL,
  `STUDIO_MANAGER_ID` text DEFAULT NULL,
  `IS_ARCHIVE` tinyint(4) NOT NULL DEFAULT 0,
  `CREATED_ON` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `DOA_AUTOMATION_MESSAGES`
--

CREATE TABLE `DOA_AUTOMATION_MESSAGES` (
  `PK_MESSAGE_ID` int(11) NOT NULL,
  `PK_AUTOMATION_ID` int(11) NOT NULL,
  `FOLLOW_UP_NUMBER` int(11) NOT NULL,
  `MESSAGE_CONTENT` text NOT NULL,
  `IS_ENABLE` tinyint(4) NOT NULL,
  `VALUE` smallint(6) NOT NULL,
  `UNIT` varchar(20) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `NOTIFY_SERVICE_PROVIDER_LAST` tinyint(1) DEFAULT 0,
  `NOTIFY_SERVICE_PROVIDER_ENROLL` tinyint(1) DEFAULT 0,
  `NOTIFY_STUDIO_MANAGER` tinyint(1) DEFAULT 0,
  `NOTIFY_CUSTOMER` tinyint(1) DEFAULT 0,
  `MESSAGE_TYPE` varchar(10) NOT NULL DEFAULT 'SMS'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `PK_LOCATION` int(11) NOT NULL,
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
  `SUB_TOTAL` float(9,2) NOT NULL,
  `SALES_TAX` float(9,2) NOT NULL,
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
  `IS_SIGNED` tinyint(4) NOT NULL DEFAULT 0,
  `ENROLLMENT_BY_ID` int(11) DEFAULT NULL,
  `ENROLLMENT_BY_PERCENTAGE` float(9,2) DEFAULT NULL,
  `MEMO` text DEFAULT NULL,
  `ACTIVE` tinyint(4) NOT NULL DEFAULT 1,
  `STATUS` varchar(50) DEFAULT NULL COMMENT '''A''->Active,''C''->Cancelled, ''CA''->Cancelled But Active',
  `ENROLLMENT_DATE` date DEFAULT NULL,
  `EXPIRY_DATE` date DEFAULT NULL,
  `COMPLETED_DATE` date DEFAULT NULL,
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
  `PK_GIFT_CERTIFICATE_MASTER` int(11) NOT NULL,
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
  `SALES_TAX` float(9,2) NOT NULL,
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
-- Table structure for table `DOA_ENROLLMENT_TIP`
--

CREATE TABLE `DOA_ENROLLMENT_TIP` (
  `PK_ENROLLMENT_TIP` int(11) NOT NULL,
  `PK_ENROLLMENT_MASTER` int(11) NOT NULL,
  `PK_ENROLLMENT_PAYMENT` int(11) NOT NULL,
  `TIP_PERCENTAGE` float(7,2) NOT NULL,
  `TIP_AMOUNT` float(9,2) NOT NULL,
  `PK_USER` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL
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
  `EDITED_ON` date DEFAULT NULL,
  `IS_REDEEMED` tinyint(1) DEFAULT 0,
  `REDEEMED_DATE` datetime DEFAULT NULL,
  `LAST_NAME` varchar(255) NOT NULL,
  `IS_REFUNDED` tinyint(1) DEFAULT 0,
  `REFUNDED_DATE` datetime DEFAULT NULL
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
-- Table structure for table `DOA_MARKET_CAMPAIGN`
--

CREATE TABLE `DOA_MARKET_CAMPAIGN` (
  `PK_MARKET_CAMPAIGN` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `CAMPAIGN_NAME` varchar(155) NOT NULL,
  `REMINDER_TYPE` varchar(50) NOT NULL,
  `SUBJECT` varchar(155) NOT NULL,
  `OPERATION` varchar(500) DEFAULT NULL,
  `TAGS` text NOT NULL,
  `LEADS` text NOT NULL,
  `CONTENT` text NOT NULL,
  `ACTIVE` int(11) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `SCHEDULE_DATETIME` datetime DEFAULT NULL,
  `TIMEZONE` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `PACKAGE_DESCRIPTION` text DEFAULT NULL,
  `PACKAGE_NAME` varchar(250) NOT NULL,
  `SORT_ORDER` int(11) DEFAULT NULL,
  `EXPIRY_DATE` int(11) DEFAULT NULL,
  `ACTIVE` int(11) NOT NULL,
  `IS_DELETED` int(11) NOT NULL,
  `CREATED_ON` date NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` date DEFAULT NULL,
  `EDITED_BY` int(11) DEFAULT NULL,
  `CHATBOT_ENABLED` tinyint(1) DEFAULT 0
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
  `ACTIVE` int(11) DEFAULT NULL,
  `CHATBOT_ENABLED` tinyint(1) DEFAULT 0
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
  `FOR_RECORD_ONLY` int(11) NOT NULL DEFAULT 1,
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
-- Table structure for table `DOA_SMS_TEMPLATE`
--

CREATE TABLE `DOA_SMS_TEMPLATE` (
  `PK_SMS_TEMPLATE` int(11) NOT NULL,
  `PK_ACCOUNT_MASTER` int(11) NOT NULL,
  `PK_LOCATION` int(11) NOT NULL,
  `TEMPLATE_NAME` varchar(100) NOT NULL,
  `CONTENT` text NOT NULL,
  `ACTIVE` int(11) NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL
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
  `ALL_DAY` tinyint(4) NOT NULL DEFAULT 0,
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
-- Table structure for table `DOA_TAG`
--

CREATE TABLE `DOA_TAG` (
  `PK_TAG` int(11) NOT NULL,
  `TAG_NAME` varchar(250) NOT NULL,
  `ACTIVE` int(11) NOT NULL,
  `CREATED_ON` datetime NOT NULL,
  `CREATED_BY` int(11) NOT NULL,
  `EDITED_ON` datetime NOT NULL,
  `EDITED_BY` int(11) NOT NULL
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
-- Table structure for table `DOA_USER_TAG`
--

CREATE TABLE `DOA_USER_TAG` (
  `PK_USER_TAG` int(11) NOT NULL,
  `PK_USER_MASTER` int(11) NOT NULL,
  `PK_TAG` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  ADD KEY `DATE` (`DATE`,`STATUS`,`IS_PAID`,`IS_CHARGED`,`CREATED_ON`),
  ADD KEY `idx_apm_location_date` (`PK_LOCATION`,`DATE`,`PK_APPOINTMENT_STATUS`);

--
-- Indexes for table `DOA_APPOINTMENT_SERVICE_PROVIDER`
--
ALTER TABLE `DOA_APPOINTMENT_SERVICE_PROVIDER`
  ADD PRIMARY KEY (`PK_APPOINTMENT_SERVICE_PROVIDER`),
  ADD KEY `PK_APPOINTMENT_MASTER` (`PK_APPOINTMENT_MASTER`),
  ADD KEY `PK_USER_MASTER` (`PK_USER`),
  ADD KEY `idx_asp_user_appt` (`PK_USER`,`PK_APPOINTMENT_MASTER`);

--
-- Indexes for table `DOA_APPOINTMENT_STATUS_HISTORY`
--
ALTER TABLE `DOA_APPOINTMENT_STATUS_HISTORY`
  ADD PRIMARY KEY (`PK_APPOINTMENT_STATUS_HISTORY`);

--
-- Indexes for table `DOA_AUTOMATIONS`
--
ALTER TABLE `DOA_AUTOMATIONS`
  ADD PRIMARY KEY (`PK_AUTOMATION_ID`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`);

--
-- Indexes for table `DOA_AUTOMATION_LOG`
--
ALTER TABLE `DOA_AUTOMATION_LOG`
  ADD PRIMARY KEY (`PK_AUTOMATION_LOG`);

--
-- Indexes for table `DOA_AUTOMATION_MESSAGES`
--
ALTER TABLE `DOA_AUTOMATION_MESSAGES`
  ADD PRIMARY KEY (`PK_MESSAGE_ID`),
  ADD KEY `PK_AUTOMATION_ID` (`PK_AUTOMATION_ID`);

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
-- Indexes for table `DOA_ENROLLMENT_TIP`
--
ALTER TABLE `DOA_ENROLLMENT_TIP`
  ADD PRIMARY KEY (`PK_ENROLLMENT_TIP`);

--
-- Indexes for table `DOA_EVENT`
--
ALTER TABLE `DOA_EVENT`
  ADD PRIMARY KEY (`PK_EVENT`),
  ADD KEY `PK_ACCOUNT_MASTER` (`PK_ACCOUNT_MASTER`),
  ADD KEY `PK_EVENT_TYPE` (`PK_EVENT_TYPE`),
  ADD KEY `idx_ev_start_date` (`START_DATE`);

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
-- Indexes for table `DOA_MARKET_CAMPAIGN`
--
ALTER TABLE `DOA_MARKET_CAMPAIGN`
  ADD PRIMARY KEY (`PK_MARKET_CAMPAIGN`);

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
-- Indexes for table `DOA_SMS_TEMPLATE`
--
ALTER TABLE `DOA_SMS_TEMPLATE`
  ADD PRIMARY KEY (`PK_SMS_TEMPLATE`);

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
-- Indexes for table `DOA_TAG`
--
ALTER TABLE `DOA_TAG`
  ADD PRIMARY KEY (`PK_TAG`);

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
-- Indexes for table `DOA_USER_TAG`
--
ALTER TABLE `DOA_USER_TAG`
  ADD PRIMARY KEY (`PK_USER_TAG`);

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
-- AUTO_INCREMENT for table `DOA_AUTOMATIONS`
--
ALTER TABLE `DOA_AUTOMATIONS`
  MODIFY `PK_AUTOMATION_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_AUTOMATION_LOG`
--
ALTER TABLE `DOA_AUTOMATION_LOG`
  MODIFY `PK_AUTOMATION_LOG` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_AUTOMATION_MESSAGES`
--
ALTER TABLE `DOA_AUTOMATION_MESSAGES`
  MODIFY `PK_MESSAGE_ID` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_ENROLLMENT_TIP`
--
ALTER TABLE `DOA_ENROLLMENT_TIP`
  MODIFY `PK_ENROLLMENT_TIP` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_MARKET_CAMPAIGN`
--
ALTER TABLE `DOA_MARKET_CAMPAIGN`
  MODIFY `PK_MARKET_CAMPAIGN` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_SMS_TEMPLATE`
--
ALTER TABLE `DOA_SMS_TEMPLATE`
  MODIFY `PK_SMS_TEMPLATE` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `DOA_TAG`
--
ALTER TABLE `DOA_TAG`
  MODIFY `PK_TAG` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_UPDATE_HISTORY`
--
ALTER TABLE `DOA_UPDATE_HISTORY`
  MODIFY `PK_UPDATE_HISTORY` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DOA_USER_TAG`
--
ALTER TABLE `DOA_USER_TAG`
  MODIFY `PK_USER_TAG` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `DOA_AUTOMATION_MESSAGES`
--
ALTER TABLE `DOA_AUTOMATION_MESSAGES`
  ADD CONSTRAINT `fk_automation_messages` FOREIGN KEY (`PK_AUTOMATION_ID`) REFERENCES `DOA_AUTOMATIONS` (`PK_AUTOMATION_ID`) ON DELETE CASCADE;
COMMIT;";

$insert_enr_agreement = "INSERT INTO `DOA_DOCUMENT_LIBRARY` (`PK_DOCUMENT_LIBRARY`, `PK_ACCOUNT_MASTER`, `PK_DOCUMENT_TYPE`, `PK_LOCATION`, `DOCUMENT_NAME`, `DOCUMENT_TEMPLATE`, `ACTIVE`, `CREATED_ON`, `CREATED_BY`, `EDITED_ON`, `EDITED_BY`) VALUES (NULL, '0', '1', '0', 'Enrollment Template', '<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:center\">\r\n <h3>CLIENT ENROLLMENT AGREEMENT</h3>\r\n </td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>Please Print</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:auto\">Client</th>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:5cm\">Full Name</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:center\"><strong>{FULL_NAME}</strong></td>\r\n <th>Street Address</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:center\"><strong>{STREET_ADD}</strong></td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:10%\">City</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:20%\">{CITY}</td>\r\n <th style=\"width:10%\">State</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%\">{STATE}</td>\r\n <th style=\"width:5%\">Zip</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%\">{ZIP}</td>\r\n <th style=\"width:10%\">Res. Phone</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%\">&nbsp;</td>\r\n <th style=\"width:10%\">Cell Phone</th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center; width:15%\">{CELL_PHONE}</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:left; width:100%\">The Client agrees to purchase and the owner of&nbsp;{BUSINESS_NAME}&nbsp;agrees to provide the following course of dance instruction and/or services on the following terms and conditions including the terms and conditions on the reverse side of this agreement.</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <thead>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">NAME/TYPE OF ENROLLMENT</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">SERVICES</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">PRIVATE LESSON(S)</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">TUITION</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">DISCOUNT</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">BALANCE DUE</td>\r\n </tr>\r\n </thead>\r\n <tbody>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{TYPE_OF_ENROLLMENT}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{SERVICE_DETAILS}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{PVT_LESSONS}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{TUITION}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{DISCOUNT}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{BAL_DUE}</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"height:auto; text-align:left; width:100%\">The Client acknowledges the above of {CASH_PRICE}&nbsp;for the instruction and/or services(s) described above and agrees to pay {DOWN_PAYMENTS} on {FIRST_DATE} and the remaining cash balance of {REMAINING_BALANCE}, which includes any applicable previous balance and service charge as shown below, in {NO_AMT_PAYMENT} Installments of {INSTALLMENT_AMOUNT} starting on {DUE_DATE}.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"height:auto; text-align:left; width:100%\">The lesson rates in this agreement are: {SERVICE_PRICE}</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"border-collapse:separate; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">1. Cash Price of This Course</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:right; vertical-align:bottom\">{CASH_PRICE}</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">&nbsp; &nbsp; Sales Tax ({TAX_PERCENT})</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:right; vertical-align:bottom\">{SALES_TAX}</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">2. Down Payment(s)</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:right; vertical-align:bottom\">{DOWN_PAYMENTS}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:left; width:100%\">You have the right at this time to receive an itemization of the amount financed, which is shown in the left column of this document</td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <p>Your payment schedule will be</p>\r\n\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Date of Payment</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Amount of Payment</td>\r\n </tr>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{DUE_DATE}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{BILLED_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <p>and on the same date each month thereafter until paid in full</p>\r\n\r\n <p>Notice to Buyer: Do not sign this agreement before you read it or if it contains any blank spaces. You are entitled to a copy of the agreement you sign. Keep this agreement to protect your legal rights.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; vertical-align:top; width:65%\">Amount to be Scheduled\r\n <p>The amount of tuition to be scheduled as installments:</p>\r\n </th>\r\n <td style=\"border-bottom:1px solid #cccccc; height:auto; text-align:right; vertical-align:bottom\">{SCHEDULE_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>\r\n <p>If you pay off early, you:</p>\r\n\r\n <p><strong>Will not</strong> have to pay a penalty</p>\r\n\r\n <p><strong>May</strong> be entitled to a refund of part of the service charge, under rule of 78, prorata or a method whichever is applicable in your state.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"border-bottom:1px solid #cccccc; height:auto; text-align:center\">CLIENT ACKNOWLEDGES RECEIPT OF AN EXACT COPY OF THIS RETAIL INSTALLMENT AGREEMENT.</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"height:auto\">It is agreed that the Studio&#39;s obligation for furnishing instructions under this agreement shall expire on {EXPIRATION_DATE} or three years from the date of this agreement whichever occurs first.</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <th rowspan=\"8\" style=\"text-align:center; vertical-align:top; width:45%\">\r\n <p>&nbsp;</p>\r\n\r\n <p><strong>{BUSINESS_NAME}</strong><br />\r\n {BUSINESS_ADD}<br />\r\n {BUSINESS_CITY}<br />\r\n {BUSINESS_STATE},&nbsp;{BUSINESS_ZIP}&nbsp; {BUSINESS_COUNTRY} &nbsp;{BUSINESS_PHONE}</p>\r\n </th>\r\n <td style=\"width:10%\">&nbsp;</td>\r\n <th style=\"text-align:center; width:20%\">___________________________________________</th>\r\n <td style=\"width:10%\">&nbsp;</td>\r\n <th style=\"text-align:center; width:20%\">___________________________________________</th>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center\">Client&#39;s Signature</th>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center\">Studio Representative</th>\r\n </tr>\r\n <tr>\r\n <td colspan=\"4\" style=\"height:30px\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center; width:20%\">___________________________________________</th>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center; width:20%\">___________________________________________</th>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center\">Co-Client or Guardian</th>\r\n <td>&nbsp;</td>\r\n <th style=\"text-align:center\">Verified by</th>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<h3>TERMS AND CONDITIONS</h3>\r\n\r\n<table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">A.</td>\r\n <td style=\"text-align:left; width:97%\">The Studio agrees to provide this course of instruction and/or services in accordance with the Arthur Murray&reg; method of dance instruction and to make its facilities and personnel available by individual independent appointment for each lesson or service for such purposes during the term of this agreement. The Studio may provide the Student with any instructor employed by the Studio and is not obligated to provide any specific instructor nor to provide the same instructor for different lessons.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">B.</td>\r\n <td style=\"text-align:left\">Instructions shall be available commencing this date and shall not be charged against this enrollment until the completion of all previous enrollments, if any. Performance of the agreed upon lessons and instructions shall begin within six months from this date. All lessons are 45 minutes long, which includes transition time between lessons. The rates for lessons calculated on an hourly basis equal the lesson rates set forth in this agreement multiplied by 1.33.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">C.</td>\r\n <td style=\"text-align:left\">Private lessons to be made available by the Studio, shall expire whether actually used or not upon the agreed expiration date for all instruction or lessons under this agreement. All group lessons (if charged) and Video Tape Studies shall expire over the same period as the private lessons. The teaching or honoring of any lessons and/or services beyond the term of expiration shall not be deemed as a waiver of this expiration provision by the Studio.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">D.</td>\r\n <td style=\"text-align:left\">Student agrees to complete all lessons and/or services as expressly provided in this agreement. Student shall not be relieved of the obligation to make any payment agreed to, and no reduction or allowance for any payments shall be made by reason of Student&#39;s failure to use any lessons and/or services, except as provided.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">E.</td>\r\n <td style=\"text-align:left\">This agreement is subject to cancellation at any time during the term of the Agreement upon notification by the Student to the Studio as set forth herein. In the event that this Agreement is cancelled, the Studio shall calculate the refund on the contract, if any, on a pro rata basis. The Studio shall refund any moneys owed to the Student within 10 days of receiving the cancellation notice, as specified within paragraph &quot;G&quot; below, unless the Student owes the Studio money for lessons or other services received prior to the cancellation, in which case any moneys owed to the Studio shall be deducted by the Studio from the refund owed to the Student and the balance, if any, shall be refunded as specified above. The Studio shall charge no cancellation fee, or other fee, for cancellation of the contract by the Student.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">F.</td>\r\n <td style=\"text-align:left\">If other than an original enrollment, this agreement, if for dance instruction, is subject to cancellation by the Student on the same terms and basis as set forth above.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">G.</td>\r\n <td style=\"text-align:left\">&quot;Notice of cancellation&quot; shall be deemed to have been provided by a Student or prospective Student by mailing or delivering written notification to cancel the contract or written agreement to the Studio at the address specified herein, or by failing to attend instructional facilities for a period of five consecutive appointment days on which classes or the provision of services which are the subject of the contract or written agreement were prearranged with the Student or prospective Student.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">H.</td>\r\n <td style=\"text-align:left\">Unless otherwise stated in this agreement and for refund when applicable, there is no charge for providing group lessons, practice sessions, parties or complimentary services offered by the Studio and it is agreed that the tuition is based solely upon the number of private lessons of instruction, the use of video equipment and expressly paid-for services.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">I.</td>\r\n <td style=\"text-align:left\">The Studio may assign this agreement and all monies due shall be paid directly to such third party upon notification.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">J.</td>\r\n <td style=\"text-align:left\">Student agrees to notify the Studio at least 12 hours in advance to cancel or change any private appointment or be charged for such lessons.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">K.</td>\r\n <td style=\"text-align:left\">Student&#39;s rights under this agreement are personal in nature and may not be sold, assigned or transferred to any other person. If by reason of death or disability, Student is unable to receive all lessons and other services for which he or she has contracted, Student and his or her estate shall be relieved from the obligation of making payments for lessons and other services other than those received prior to death or the onset of disability, and that if Student has prepaid any sum for lessons and other services so much of that sum as is allocatable to lessons and other services he or she has not taken shall be promptly refunded to Student or his or her representative. In the event of Student&#39;s death or disability, the Student or his representative may also sell, donate or transfer the remaining lessons and/or services to any persons or charity subject to Studio approval. Student lessons may be transferred to any other Arthur Murray&reg; Franchised Dance Studio beyond twenty-five miles from this Studio.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">L.</td>\r\n <td style=\"text-align:left\">Student agrees not to associate with any Studio instructor and other personnel outside the Studio or to give or loan anything of value to any Studio personnel during the term of this agreement and for a one year period thereafter. To protect the Studio from unfair competition by any Studio personnel, Student also agrees not to directly or indirectly aid or assist such personnel to engage in any capacity in the teaching of dance lessons or providing services which employ the know-how or knowledge used as an employee of the Studio within a 25-mile radius of the Studio or to solicit other students or personnel of the Studio for such purpose during the term of this agreement and for a one year period thereafter. It is agreed by Student that violation of this paragraph shall be expressly damaging to the Studio and shall release Studio from obligatory terms of this agreement and provide grounds for damages by the Studio.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">M.</td>\r\n <td style=\"text-align:left\">Student represents to the Studio that (s)he is physically able to take and financially able to pay for this course of instruction and/or services, has read and fully understands the terms of this agreement, has signed the agreement voluntarily and hereby acknowledges receipt of a fully executed copy.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">N.</td>\r\n <td style=\"text-align:left\">ANY HOLDER OF THIS CONSUMER CREDIT CONTRACT IS SUBJECT TO ALL CLAIMS AND DEFENSES WHICH THE DEBTOR COULD ASSERT AGAINST THE SELLER OF GOODS OR SERVICES OBTAINED PURSUANT HERETO OR WITH THE PROCEEDS HEREOF. RECOVERY HEREUNDER BY THE DEBTOR SHALL NOT EXCEED AMOUNTS PAID BY THE DEBTOR HEREUNDER.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">O.</td>\r\n <td style=\"text-align:left\">Any controversy or claim arising out of or relating to this agreement shall be settled solely by arbitration in accordance with the commercial arbitration rules of the American Arbitration Association, and judgment upon the award rendered by the arbitrator may be entered in any court having jurisdiction. All fees and expenses in connection with the arbitration shall be shared equally by the parties. Any action or arbitration on or related to this Agreement must be brought within the applicable statutory period.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">P.</td>\r\n <td style=\"text-align:left\">The Federal Equal Credit Opportunity Act prohibits creditors from discriminating against credit applicants on the basis of sex or marital status. The Federal agency which administers compliance with this law is the F.T.C., Washington, D.C.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">Q.</td>\r\n <td style=\"text-align:left\">AS STUDENT, I UNDERSTAND AND AGREE THAT THIS AGREEMENT IS MADE BY ME SOLELY WITH THE OWNER OF THE STUDIO, AS SELLER, AND DOES NOT DIRECTLY OR INDIRECTLY CONSTITUTE AN AGREEMENT WITH OR AN OBLIGATION OF ARTHUR MURRAY INTERNATIONAL, INC., OR AS THE STUDIO FRANCHISOR. ARTHUR MURRAY INTERNATIONAL, INC. IS NOT THE OWNER OF THIS STUDIO. SHOULD THIS AGREEMENT COMBINED WITH THE COST OF STUDENT&#39;S OTHER UNUSED LESSONS AND/OR SERVICES, EXCEED $20,000.00 OR 200 ENROLLED PRIVATE LESSONS OR UNITS WHICHEVER OCCURS FIRST, OR THE MAXIMUM PERMITTED BY LAW, WHICHEVER IS LESS, THIS AGREEMENT IS VOID.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">R.</td>\r\n <td style=\"text-align:left\">If any particular provision of this Agreement is held invalid or unenforceable by an arbitrator or court of competent jurisdiction, such invalidity shall not affect the other provisions of this Agreement.</td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top\">S.</td>\r\n <td style=\"text-align:left\">No other representations or provisions, either written or oral, are a part of this Agreement, unless expressed herein.</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n', '1', '', '', '', '') ";

$insert_misc_agreement = "INSERT INTO `DOA_DOCUMENT_LIBRARY` (`PK_DOCUMENT_LIBRARY`, `PK_ACCOUNT_MASTER`, `PK_DOCUMENT_TYPE`, `PK_LOCATION`, `DOCUMENT_NAME`, `DOCUMENT_TEMPLATE`, `ACTIVE`, `CREATED_ON`, `CREATED_BY`, `EDITED_ON`, `EDITED_BY`) VALUES (NULL, '0', '3', '0', 'Miscellaneous Agreement', '<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:center\">CLIENT ENROLLMENT AGREEMENT</td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<table style=\"width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>Please Print</td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:5%\">Client</th>\r\n <td colspan=\"3\" style=\"width:95%\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:15%\">Full Name</th>\r\n <td style=\"border-bottom:1px solid black; text-align:left; width:35%\"><strong>{FULL_NAME}</strong></td>\r\n <th style=\"width:15%\">Street Address</th>\r\n <td style=\"border-bottom:1px solid black; text-align:left; width:35%\"><strong>{STREET_ADD}</strong></td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:8%\">City</th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:left; width:18%\">{CITY}</td>\r\n <th style=\"width:8%\">State</th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:left; width:10%\">{STATE}</td>\r\n <th style=\"width:5%\">Zip</th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:left; width:10%\">{ZIP}</td>\r\n <th style=\"width:10%\">Res. Phone</th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:left; width:15%\">&nbsp;</td>\r\n <th style=\"width:10%\">Cell Phone</th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:left; width:15%\">{CELL_PHONE}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <p>The Client agrees to purchase and the owner of Arthur Murray Woodland Hills agrees to provide the following course of dance instruction and/or services on the following terms and conditions including the terms and conditions on the reverse side of this agreement.</p>\r\n\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td colspan=\"4\" style=\"text-align:center; vertical-align:middle\">Lessons are 45 minutes which includes transition time between lessons</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"3\" style=\"background-color:#f0f0f0; border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">ENROLLED ON MISCELLANEOUS SERVICES</td>\r\n <td style=\"background-color:#f0f0f0; border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">TUITION OR COST</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"3\" style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{SERVICE_DETAILS}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{TUITION}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <p>The Client acknowledges the above of {CASH_PRICE}&nbsp;for the instruction and/or services(s) described above and agrees to pay {DOWN_PAYMENTS} on {BILLING_DATE} and the remaining cash balance of {REMAINING_BALANCE}, which includes any applicable previous balance and service charge as shown below, in {NO_AMT_PAYMENT} installments of {INSTALLMENT_AMOUNT} starting on {STARTING_DATE}.</p>\r\n\r\n <p>The lesson rates in this agreement are: Private Instruction $189.00 per lesson, Class Instruction $0.00 per lesson, and there is no charge for Party Practice units when included.</p>\r\n\r\n <table style=\"border-collapse:separate; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">1. Cash Price of This Course</th>\r\n <td style=\"border-bottom:1px solid black; text-align:right; vertical-align:bottom; width:35%\">{CASH_PRICE}</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">&nbsp; &nbsp; Sales Tax ({TAX_PERCENT})</th>\r\n <td style=\"border-bottom:1px solid #cccccc; text-align:right; vertical-align:bottom\">{SALES_TAX}</td>\r\n </tr>\r\n <tr>\r\n <th style=\"text-align:left; width:65%\">2. Down Payment(s)</th>\r\n <td style=\"border-bottom:1px solid black; text-align:right; vertical-align:bottom; width:35%\">{DOWN_PAYMENTS}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"text-align:left\">You have the right at this time to receive an itemization of the amount financed, which is shown in the left column of this document</td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <p>Your payment schedule will be</p>\r\n\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"background-color:#f0f0f0; border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Date of Payment</td>\r\n <td style=\"background-color:#f0f0f0; border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">Amount of Payment</td>\r\n </tr>\r\n <tr>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{DUE_DATE}</td>\r\n <td style=\"border-color:#cccccc; border-style:solid; border-width:1px; text-align:center\">{BILLED_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <p>and on the same date each month thereafter until paid in full</p>\r\n\r\n <p>Notice to Buyer: Do not sign this agreement before you read it or if it contains any blank spaces. You are entitled to a copy of the agreement you sign. Keep this agreement to protect your legal rights.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n\r\n <table style=\"border-collapse:separate; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <th style=\"text-align:left; vertical-align:top; width:65%\">Amount to be Scheduled\r\n <p>The amount of tuition to be scheduled as installments:</p>\r\n </th>\r\n <td style=\"border-bottom:1px solid black; height:auto; text-align:right; vertical-align:bottom; width:35%\">{SCHEDULE_AMOUNT}</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n <td style=\"vertical-align:top; width:50%\">\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td>\r\n <p>If you pay off early, you:</p>\r\n\r\n <p><strong>Will not</strong> have to pay a penalty</p>\r\n\r\n <p><strong>May</strong> be entitled to a refund of part of the service charge, under rule of 78, prorata or a method whichever is applicable in your state.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"border-bottom:1px solid black; height:auto; text-align:center\">CLIENT ACKNOWLEDGES RECEIPT OF AN EXACT COPY OF THIS RETAIL INSTALLMENT AGREEMENT.</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"2\" style=\"height:auto\">It is agreed that the Studio&#39;s obligation for furnishing instructions under this agreement shall expire on {EXPIRATION_DATE} or three years from the date of this agreement whichever occurs first.</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td>\r\n <table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <th rowspan=\"6\" style=\"text-align:left; vertical-align:top; width:45%\">\r\n <p>{BUSINESS_NAME}</p>\r\n {BUSINESS_ADD}<br />\r\n {BUSINESS_CITY}<br />\r\n {BUSINESS_STATE},&nbsp;{BUSINESS_ZIP}&nbsp; {BUSINESS_COUNTRY} &nbsp;{BUSINESS_PHONE}</th>\r\n <td style=\"width:5%\">&nbsp;</td>\r\n <td style=\"border-top:1px solid black; text-align:center; width:25%\">&nbsp;</td>\r\n <td style=\"width:5%\">&nbsp;</td>\r\n <td style=\"border-top:1px solid black; text-align:center; width:25%\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <td style=\"text-align:center\">Client&#39;s Signature</td>\r\n <td>&nbsp;</td>\r\n <td style=\"text-align:center\">Studio Representative</td>\r\n </tr>\r\n <tr>\r\n <td colspan=\"5\" style=\"height:5px\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <td style=\"border-top:1px solid black; text-align:center\">&nbsp;</td>\r\n <td>&nbsp;</td>\r\n <td style=\"border-top:1px solid black; text-align:center\">&nbsp;</td>\r\n </tr>\r\n <tr>\r\n <td>&nbsp;</td>\r\n <td style=\"text-align:center\">Co-Client or Guardian</td>\r\n <td>&nbsp;</td>\r\n <td style=\"text-align:center\">Verified by</td>\r\n </tr>\r\n </tbody>\r\n </table>\r\n </td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n\r\n<h3>TERMS AND CONDITIONS</h3>\r\n\r\n<table cellspacing=\"0\" style=\"border-collapse:collapse; width:100%\">\r\n <tbody>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">A.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>The Studio agrees to provide this course of instruction and/or services in accordance with the Arthur Murray&reg; method of dance instruction and to make its facilities and personnel available by individual independent appointment for each lesson or service for such purposes during the term of this agreement. The Studio may provide the Student with any instructor employed by the Studio and is not obligated to provide any specific instructor nor to provide the same instructor for different lessons.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">B.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Instructions shall be available commencing this date and shall not be charged against this enrollment until the completion of all previous enrollments, if any. Performance of the agreed upon lessons and instructions shall begin within six months from this date. All lessons are 45 minutes long, which includes transition time between lessons. The rates for lessons calculated on an hourly basis equal the lesson rates set forth in this agreement multiplied by 1.33.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">C.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Private lessons to be made available by the Studio, shall expire whether actually used or not upon the agreed expiration date for all instruction or lessons under this agreement. All group lessons (if charged) and Video Tape Studies shall expire over the same period as the private lessons. The teaching or honoring of any lessons and/or services beyond the term of expiration shall not be deemed as a waiver of this expiration provision by the Studio.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">D.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Student agrees to complete all lessons and/or services as expressly provided in this agreement. Student shall not be relieved of the obligation to make any payment agreed to, and no reduction or allowance for any payments shall be made by reason of Student&#39;s failure to use any lessons and/or services, except as provided.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">E.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>This agreement is subject to cancellation at any time during the term of the Agreement upon notification by the Student to the Studio as set forth herein. In the event that this Agreement is cancelled, the Studio shall calculate the refund on the contract, if any, on a pro rata basis. The Studio shall refund any moneys owed to the Student within 10 days of receiving the cancellation notice, as specified within paragraph &quot;G&quot; below, unless the Student owes the Studio money for lessons or other services received prior to the cancellation, in which case any moneys owed to the Studio shall be deducted by the Studio from the refund owed to the Student and the balance, if any, shall be refunded as specified above. The Studio shall charge no cancellation fee, or other fee, for cancellation of the contract by the Student.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">F.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>If other than an original enrollment, this agreement, if for dance instruction, is subject to cancellation by the Student on the same terms and basis as set forth above.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">G.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>&quot;Notice of cancellation&quot; shall be deemed to have been provided by a Student or prospective Student by mailing or delivering written notification to cancel the contract or written agreement to the Studio at the address specified herein, or by failing to attend instructional facilities for a period of five consecutive appointment days on which classes or the provision of services which are the subject of the contract or written agreement were prearranged with the Student or prospective Student.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">H.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Unless otherwise stated in this agreement and for refund when applicable, there is no charge for providing group lessons, practice sessions, parties or complimentary services offered by the Studio and it is agreed that the tuition is based solely upon the number of private lessons of instruction, the use of video equipment and expressly paid-for services.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">I.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>The Studio may assign this agreement and all monies due shall be paid directly to such third party upon notification.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">J.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Student agrees to notify the Studio at least 12 hours in advance to cancel or change any private appointment or be charged for such lessons.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">K.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Student&#39;s rights under this agreement are personal in nature and may not be sold, assigned or transferred to any other person. If by reason of death or disability, Student is unable to receive all lessons and other services for which he or she has contracted, Student and his or her estate shall be relieved from the obligation of making payments for lessons and other services other than those received prior to death or the onset of disability, and that if Student has prepaid any sum for lessons and other services so much of that sum as is allocatable to lessons and other services he or she has not taken shall be promptly refunded to Student or his or her representative. In the event of Student&#39;s death or disability, the Student or his representative may also sell, donate or transfer the remaining lessons and/or services to any persons or charity subject to Studio approval. Student lessons may be transferred to any other Arthur Murray&reg; Franchised Dance Studio beyond twenty-five miles from this Studio.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">L.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Student agrees not to associate with any Studio instructor and other personnel outside the Studio or to give or loan anything of value to any Studio personnel during the term of this agreement and for a one year period thereafter. To protect the Studio from unfair competition by any Studio personnel, Student also agrees not to directly or indirectly aid or assist such personnel to engage in any capacity in the teaching of dance lessons or providing services which employ the know-how or knowledge used as an employee of the Studio within a 25-mile radius of the Studio or to solicit other students or personnel of the Studio for such purpose during the term of this agreement and for a one year period thereafter. It is agreed by Student that violation of this paragraph shall be expressly damaging to the Studio and shall release Studio from obligatory terms of this agreement and provide grounds for damages by the Studio.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">M.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Student represents to the Studio that (s)he is physically able to take and financially able to pay for this course of instruction and/or services, has read and fully understands the terms of this agreement, has signed the agreement voluntarily and hereby acknowledges receipt of a fully executed copy.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">N.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>ANY HOLDER OF THIS CONSUMER CREDIT CONTRACT IS SUBJECT TO ALL CLAIMS AND DEFENSES WHICH THE DEBTOR COULD ASSERT AGAINST THE SELLER OF GOODS OR SERVICES OBTAINED PURSUANT HERETO OR WITH THE PROCEEDS HEREOF. RECOVERY HEREUNDER BY THE DEBTOR SHALL NOT EXCEED AMOUNTS PAID BY THE DEBTOR HEREUNDER.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">O.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>Any controversy or claim arising out of or relating to this agreement shall be settled solely by arbitration in accordance with the commercial arbitration rules of the American Arbitration Association, and judgment upon the award rendered by the arbitrator may be entered in any court having jurisdiction. All fees and expenses in connection with the arbitration shall be shared equally by the parties. Any action or arbitration on or related to this Agreement must be brought within the applicable statutory period.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">P.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>The Federal Equal Credit Opportunity Act prohibits creditors from discriminating against credit applicants on the basis of sex or marital status. The Federal agency which administers compliance with this law is the F.T.C., Washington, D.C.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">Q.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>AS STUDENT, I UNDERSTAND AND AGREE THAT THIS AGREEMENT IS MADE BY ME SOLELY WITH THE OWNER OF THE STUDIO, AS SELLER, AND DOES NOT DIRECTLY OR INDIRECTLY CONSTITUTE AN AGREEMENT WITH OR AN OBLIGATION OF ARTHUR MURRAY INTERNATIONAL, INC., OR AS THE STUDIO FRANCHISOR. ARTHUR MURRAY INTERNATIONAL, INC. IS NOT THE OWNER OF THIS STUDIO. SHOULD THIS AGREEMENT COMBINED WITH THE COST OF STUDENT&#39;S OTHER UNUSED LESSONS AND/OR SERVICES, EXCEED $20,000.00 OR 200 ENROLLED PRIVATE LESSONS OR UNITS WHICHEVER OCCURS FIRST, OR THE MAXIMUM PERMITTED BY LAW, WHICHEVER IS LESS, THIS AGREEMENT IS VOID.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">R.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>If any particular provision of this Agreement is held invalid or unenforceable by an arbitrator or court of competent jurisdiction, such invalidity shall not affect the other provisions of this Agreement.</p>\r\n </td>\r\n </tr>\r\n <tr>\r\n <td style=\"vertical-align:top; width:3%\">S.</td>\r\n <td style=\"text-align:left; width:97%\">\r\n <p>No other representations or provisions, either written or oral, are a part of this Agreement, unless expressed herein.</p>\r\n </td>\r\n </tr>\r\n </tbody>\r\n</table>\r\n', '1', '', '', '', '') ";

$create_store_procedure = 'DELIMITER $$

DROP PROCEDURE IF EXISTS `getCalendarAppointments`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `getCalendarAppointments`(
    IN `p_location_ids` TEXT,
    IN `p_status_ids` TEXT,
    IN `p_date_condition` TEXT,
    IN `p_type_condition` TEXT,
    IN `p_service_provider_condition` TEXT
)
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
            DOA_APPOINTMENT_MASTER.IS_FROM_AI_CALL,
            DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER,
            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
            DOA_SERVICE_MASTER.SERVICE_NAME,
            DOA_SERVICE_CODE.SERVICE_CODE,
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
        AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (", p_status_ids, ")
        ", p_date_condition, "
        ", p_type_condition, "
        ", p_service_provider_condition, "
        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC
    ");

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$';
