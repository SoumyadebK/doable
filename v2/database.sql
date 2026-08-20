-- ============================================================================
--  DOable — Database schema (MySQL / MariaDB)
--  Import this ONCE into the database named in config.php (DB_NAME).
--    mysql -u <user> -p <database> < database.sql
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Contact / lead form submissions ------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id`            CHAR(36)      NOT NULL,
  `name`          VARCHAR(191)  NOT NULL,
  `email`         VARCHAR(191)  NOT NULL,
  `business_name` VARCHAR(191)  NOT NULL DEFAULT '',
  `business_type` VARCHAR(191)  NOT NULL DEFAULT '',
  `phone`         VARCHAR(191)  NOT NULL DEFAULT '',
  `message`       TEXT          NOT NULL,
  `sms_consent`   TINYINT(1)    NOT NULL DEFAULT 0,
  `status`        VARCHAR(32)   NOT NULL DEFAULT 'new',
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo requests -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `demo_requests` (
  `id`               CHAR(36)     NOT NULL,
  `name`             VARCHAR(191) NOT NULL,
  `email`            VARCHAR(191) NOT NULL,
  `business_name`    VARCHAR(191) NULL,
  `business_type`    VARCHAR(191) NULL,
  `phone`            VARCHAR(191) NULL,
  `preferred_date`   VARCHAR(191) NULL,
  `preferred_time`   VARCHAR(191) NULL,
  `number_of_staff`  VARCHAR(191) NULL,
  `current_solution` VARCHAR(191) NULL,
  `message`          TEXT         NULL,
  `status`           VARCHAR(32)  NOT NULL DEFAULT 'new',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demo_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         CHAR(36)     NOT NULL,
  `email`      VARCHAR(191) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `name`       VARCHAR(191) NULL,
  `role`       VARCHAR(32)  NOT NULL DEFAULT 'admin',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Editable site content (one row, JSON blob under key 'main') ---------------
CREATE TABLE IF NOT EXISTS `site_content` (
  `id`         CHAR(36)     NOT NULL,
  `key_name`   VARCHAR(64)  NOT NULL,
  `value`      LONGTEXT     NOT NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_content_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blog posts ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`           CHAR(36)     NOT NULL,
  `slug`         VARCHAR(191) NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `excerpt`      TEXT         NOT NULL,
  `content`      LONGTEXT     NOT NULL,
  `cover_image`  VARCHAR(500) NULL,
  `author`       VARCHAR(191) NOT NULL DEFAULT 'Doable Team',
  `category`     VARCHAR(191) NOT NULL DEFAULT 'General',
  `tags`         VARCHAR(500) NOT NULL DEFAULT '',
  `published`    TINYINT(1)   NOT NULL DEFAULT 0,
  `published_at` DATETIME     NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_blog_slug` (`slug`),
  KEY `idx_blog_pub` (`published`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Two starter blog posts (optional; safe to delete from the admin panel) -----
INSERT INTO `blog_posts` (`id`,`slug`,`title`,`excerpt`,`content`,`author`,`category`,`tags`,`published`,`published_at`)
VALUES
('seed-post-0000-0000-000000000001','5-ways-to-reduce-no-shows',
 '5 Ways to Reduce No-Shows at Your Studio',
 'No-shows quietly drain revenue. Here are five proven tactics that keep your calendar full and your clients showing up.',
 '## Why no-shows hurt more than you think\n\nEvery empty slot is revenue you can never get back. The good news: a few simple systems can cut no-shows dramatically.\n\n### 1. Automated reminders\nSend an SMS and email 24 hours and 1 hour before each appointment.\n\n### 2. Easy rescheduling\nGive clients a one-tap way to move their booking instead of skipping it.\n\n### 3. Clear cancellation policies\nSet expectations up front and apply them consistently.\n\n### 4. Deposits for high-demand slots\nA small deposit turns a maybe into a commitment.\n\n### 5. Personal follow-up\nA quick check-in after a missed session shows you care and wins clients back.',
 'Doable Team','Operations','no-shows,scheduling,retention',1, NOW()),
('seed-post-0000-0000-000000000002','grow-your-class-based-business',
 'How to Grow a Class-Based Business Without Burning Out',
 'Growth should not cost you your evenings. Learn how to scale your studio while getting your time back.',
 '## Work on the business, not just in it\n\nMost studio owners hit a ceiling because every task runs through them. Breaking that pattern is how you grow.\n\n### Automate the repetitive work\nBilling, reminders, and follow-ups should run without you.\n\n### Build recurring revenue\nMemberships and packages smooth out cash flow and reward loyalty.\n\n### Use your numbers\nLet your dashboard tell you what is working so you can do more of it.',
 'Doable Team','Growth','growth,memberships,automation',1, NOW());
