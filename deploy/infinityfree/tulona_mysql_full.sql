-- Tulona FULL MySQL backup (schema + data) generated from SQLite
-- Import this file via InfinityFree phpMyAdmin → Import
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `affiliate_conversions`;
CREATE TABLE `affiliate_conversions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `merchant_id` integer, `network` varchar, `product_id` integer, `external_order_ref` varchar, `commission_amount` DECIMAL(10,2) not null default '0', `currency` varchar not null default 'BDT', `status` varchar not null default 'pending', `converted_at` DATETIME, `imported_at` DATETIME not null default CURRENT_TIMESTAMP, foreign key(`merchant_id`) references `merchants`(`id`) on delete set null, foreign key(`product_id`) references `products`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `affiliate_generation_runs`;
CREATE TABLE `affiliate_generation_runs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `merchant_id` integer, `status` varchar not null default 'queued', `total` INT NOT NULL DEFAULT 0, `processed` INT NOT NULL DEFAULT 0, `generated` INT NOT NULL DEFAULT 0, `failed` INT NOT NULL DEFAULT 0, `created_by` integer, `started_at` DATETIME, `completed_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`merchant_id`) references `merchants`(`id`) on delete set null, foreign key(`created_by`) references `users`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `affiliate_link_generations`;
CREATE TABLE `affiliate_link_generations` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `affiliate_offer_id` integer not null, `product_id` integer, `merchant_id` integer, `method` varchar not null, `status` varchar not null default 'queued', `input_url` TEXT, `generated_url` TEXT, `error` TEXT, `initiated_by` integer, `metadata` TEXT, `processed_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`affiliate_offer_id`) references `affiliate_offers`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete set null, foreign key(`merchant_id`) references `merchants`(`id`) on delete set null, foreign key(`initiated_by`) references `users`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `affiliate_networks`;
CREATE TABLE `affiliate_networks` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar not null, `slug` varchar not null, `website_url` varchar, `api_config` TEXT, `notes` TEXT, `created_at` DATETIME, `updated_at` DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `affiliate_offers`;
CREATE TABLE `affiliate_offers` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `offer_id` integer not null, `product_id` integer not null, `merchant_id` integer not null, `normal_product_url` TEXT, `affiliate_url` TEXT, `tracking_identifier` varchar, `commission_rate` DECIMAL(10,2), `commission_type` varchar, `commission_eligible` tinyint(1) NOT NULL DEFAULT 0, `status` varchar not null default 'pending', `generation_method` varchar, `generated_at` DATETIME, `last_verified_at` DATETIME, `last_error` TEXT, `metadata` TEXT, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`offer_id`) references `offers`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade, foreign key(`merchant_id`) references `merchants`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `article_product`;
CREATE TABLE `article_product` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `article_id` integer not null, `product_id` integer not null, `blurb` TEXT, `pick_label` varchar, `sort_order` INT NOT NULL DEFAULT 0, foreign key(`article_id`) references `articles`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar not null, `slug` varchar not null, `type` varchar not null default 'guide', `excerpt` TEXT, `content` TEXT not null, `featured_image` varchar, `category_id` integer, `author` varchar not null default 'Editorial Team', `status` varchar not null default 'draft', `published_at` DATETIME, `seo_title` varchar, `seo_description` varchar, `canonical_url` varchar, `og_image` varchar, `faqs` TEXT, `selection_criteria` TEXT, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`category_id`) references `categories`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `attribute_definitions`;
CREATE TABLE `attribute_definitions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `category_id` integer, `name` varchar not null, `key` varchar not null, `data_type` varchar not null default 'string', `unit` varchar, `options` TEXT, `is_filterable` tinyint(1) NOT NULL DEFAULT 0, `sort_order` INT NOT NULL DEFAULT 0, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`category_id`) references `categories`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer, `action` varchar not null, `subject_type` varchar, `subject_id` integer, `properties` TEXT, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`user_id`) references `users`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar not null, `slug` varchar not null, `logo_path` varchar, `description` TEXT, `website_url` varchar, `seo_title` varchar, `seo_description` varchar, `created_at` DATETIME, `updated_at` DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (`key` varchar not null, `value` TEXT not null, `expiration` integer not null, primary key (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (`key` varchar not null, `owner` varchar not null, `expiration` integer not null, primary key (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `parent_id` integer, `name` varchar not null, `slug` varchar not null, `description` TEXT, `icon` varchar, `sort_order` INT NOT NULL DEFAULT 0, `is_active` tinyint(1) NOT NULL DEFAULT 1, `seo_title` varchar, `seo_description` varchar, `intro_content` TEXT, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`parent_id`) references `categories`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `clicks`;
CREATE TABLE `clicks` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `offer_id` integer not null, `product_id` integer not null, `merchant_id` integer not null, `referrer_page` varchar, `ip_hash` varchar, `user_agent_family` varchar, `clicked_at` DATETIME not null, `clicked_on` date not null, foreign key(`offer_id`) references `offers`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade, foreign key(`merchant_id`) references `merchants`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comparison_offer`;
CREATE TABLE `comparison_offer` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `comparison_id` integer not null, `offer_id` integer not null, `product_id` integer, `is_hidden` tinyint(1) NOT NULL DEFAULT 0, `override_price` DECIMAL(10,2), `override_availability` varchar, `override_warranty` varchar, `override_shipping` varchar, `sort_order` INT NOT NULL DEFAULT 0, `is_best_deal` tinyint(1) NOT NULL DEFAULT 0, foreign key(`comparison_id`) references `comparisons`(`id`) on delete cascade, foreign key(`offer_id`) references `offers`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comparison_product`;
CREATE TABLE `comparison_product` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `comparison_id` integer not null, `product_id` integer not null, `sort_order` INT NOT NULL DEFAULT 0, `editorial_notes` TEXT, `pick_label` varchar, foreign key(`comparison_id`) references `comparisons`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comparisons`;
CREATE TABLE `comparisons` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar not null, `slug` varchar not null, `introduction` TEXT, `description` TEXT, `verdict` TEXT, `notes` TEXT, `cta_text` varchar, `status` varchar not null default 'draft', `featured` tinyint(1) NOT NULL DEFAULT 0, `merchant_order` TEXT, `specifications_shown` TEXT, `published_at` DATETIME, `seo_title` varchar, `seo_description` varchar, `canonical_url` varchar, `created_at` DATETIME, `updated_at` DATETIME, `deleted_at` DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `deals`;
CREATE TABLE `deals` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `offer_id` integer not null, `product_id` integer not null, `label` varchar, `source` varchar not null default 'price_drop', `is_active` tinyint(1) NOT NULL DEFAULT 1, `expires_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`offer_id`) references `offers`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uuid` varchar not null, `connection` varchar not null, `queue` varchar not null, `payload` TEXT not null, `exception` TEXT not null, `failed_at` DATETIME not null default CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `import_batches`;
CREATE TABLE `import_batches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `filename` varchar not null, `type` varchar not null default ('csv'), `status` varchar not null default ('pending'), `total_rows` integer not null default ('0'), `imported_count` integer not null default ('0'), `created_count` integer not null default ('0'), `updated_count` integer not null default ('0'), `skipped_count` integer not null default ('0'), `failed_count` integer not null default ('0'), `created_by` integer, `completed_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, `merchant_id` integer, `source_url` varchar, `source_type` varchar not null default 'csv', `category_slug` varchar, foreign key(`created_by`) references users(`id`) on delete set null on update no action, foreign key(`merchant_id`) references `merchants`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `import_errors`;
CREATE TABLE `import_errors` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `import_batch_id` integer not null, `row_number` integer, `field` varchar, `message` TEXT not null, `severity` varchar not null default 'error', `created_at` DATETIME, `updated_at` DATETIME, foreign key(`import_batch_id`) references `import_batches`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `import_items`;
CREATE TABLE `import_items` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `import_batch_id` integer not null, `source_identifier` varchar, `raw_data` TEXT, `normalized_data` TEXT, `product_id` integer, `offer_id` integer, `match_type` varchar, `status` varchar not null default 'pending', `error` TEXT, `processed_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`import_batch_id`) references `import_batches`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete set null, foreign key(`offer_id`) references `offers`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (`id` varchar not null, `name` varchar not null, `total_jobs` integer not null, `pending_jobs` integer not null, `failed_jobs` integer not null, `failed_job_ids` TEXT not null, `options` TEXT, `cancelled_at` integer, `created_at` integer not null, `finished_at` integer, primary key (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `queue` varchar not null, `payload` TEXT not null, `attempts` integer not null, `reserved_at` integer, `available_at` integer not null, `created_at` integer not null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `landing_page_comparison`;
CREATE TABLE `landing_page_comparison` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `landing_page_id` integer not null, `comparison_id` integer not null, `sort_order` INT NOT NULL DEFAULT 0, foreign key(`landing_page_id`) references `landing_pages`(`id`) on delete cascade, foreign key(`comparison_id`) references `comparisons`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `landing_page_product`;
CREATE TABLE `landing_page_product` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `landing_page_id` integer not null, `product_id` integer not null, `sort_order` INT NOT NULL DEFAULT 0, foreign key(`landing_page_id`) references `landing_pages`(`id`) on delete cascade, foreign key(`product_id`) references `products`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `landing_pages`;
CREATE TABLE `landing_pages` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar not null, `slug` varchar not null, `excerpt` TEXT, `status` varchar not null default 'draft', `sections` TEXT, `published_at` DATETIME, `seo_title` varchar, `seo_description` varchar, `canonical_url` varchar, `created_at` DATETIME, `updated_at` DATETIME, `deleted_at` DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `merchants`;
CREATE TABLE `merchants` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `affiliate_network_id` integer, `name` varchar not null, `slug` varchar not null, `logo_path` varchar, `description` TEXT, `website_url` varchar, `country` varchar not null default 'BD', `region` varchar, `currencies` TEXT not null default '[`BDT`]', `base_affiliate_url` varchar, `tracking_template` varchar, `feed_config` TEXT, `commission_note` varchar, `status` varchar not null default 'active', `last_synced_at` DATETIME, `sync_status` varchar, `terms_notes` TEXT, `seo_title` varchar, `seo_description` varchar, `created_at` DATETIME, `updated_at` DATETIME, `connector_type` varchar, `product_import_method` varchar not null default 'csv', `affiliate_link_method` varchar, `affiliate_enabled` tinyint(1) NOT NULL DEFAULT 0, `configuration` TEXT, foreign key(`affiliate_network_id`) references `affiliate_networks`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` varchar not null, `batch` integer not null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_id` integer not null, `merchant_id` integer not null, `external_product_id` varchar, `external_url` TEXT, `affiliate_url` TEXT not null, `current_price` DECIMAL(10,2), `original_price` DECIMAL(10,2), `currency` varchar not null default 'BDT', `availability` varchar not null default 'unknown', `shipping_info` varchar, `deal_expires_at` DATETIME, `source` varchar not null default 'manual', `status` varchar not null default 'active', `clicks_count` INT NOT NULL DEFAULT 0, `last_synced_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`product_id`) references `products`(`id`) on delete cascade, foreign key(`merchant_id`) references `merchants`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `page_views`;
CREATE TABLE `page_views` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` varchar not null, `referrer_page` varchar, `ip_hash` varchar, `user_agent_family` varchar, `viewed_at` DATETIME not null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (`email` varchar not null, `token` varchar not null, `created_at` DATETIME, primary key (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `price_drop_events`;
CREATE TABLE `price_drop_events` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_id` integer not null, `offer_id` integer not null, `previous_price` DECIMAL(10,2) not null, `current_price` DECIMAL(10,2) not null, `drop_amount` DECIMAL(10,2) not null, `drop_percent` DECIMAL(10,2) not null, `currency` varchar not null default 'BDT', `occurred_at` DATETIME not null, foreign key(`product_id`) references `products`(`id`) on delete cascade, foreign key(`offer_id`) references `offers`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `price_history`;
CREATE TABLE `price_history` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `offer_id` integer not null, `price` DECIMAL(10,2) not null, `currency` varchar not null default 'BDT', `recorded_at` DATETIME not null, foreign key(`offer_id`) references `offers`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_attributes`;
CREATE TABLE `product_attributes` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_id` integer not null, `attribute_definition_id` integer not null, `value_text` varchar, `value_number` DECIMAL(10,2), `value_boolean` tinyint(1), `created_at` DATETIME, `updated_at` DATETIME, foreign key(`product_id`) references `products`(`id`) on delete cascade, foreign key(`attribute_definition_id`) references `attribute_definitions`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_drafts`;
CREATE TABLE `product_drafts` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `data` TEXT, `merchant_id` integer, `created_by` integer, `status` varchar not null default 'draft', `error` varchar, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`merchant_id`) references `merchants`(`id`) on delete set null, foreign key(`created_by`) references `users`(`id`) on delete set null) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_id` integer not null, `path` varchar not null, `alt_text` varchar, `is_main` tinyint(1) NOT NULL DEFAULT 0, `sort_order` INT NOT NULL DEFAULT 0, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`product_id`) references `products`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `category_id` integer not null, `brand_id` integer, `name` varchar not null, `slug` varchar not null, `sku` varchar, `model_number` varchar, `gtin` varchar, `product_type` varchar not null default ('physical'), `short_description` TEXT, `description` TEXT, `summary_editorial` TEXT, `pros` TEXT, `cons` TEXT, `rating` DECIMAL(10,2), `pricing_model` varchar, `has_free_plan` tinyint(1) not null default ('0'), `platforms` TEXT, `is_featured` tinyint(1) not null default ('0'), `is_trending` tinyint(1) not null default ('0'), `is_editors_pick` tinyint(1) not null default ('0'), `is_best_value` tinyint(1) not null default ('0'), `is_budget_pick` tinyint(1) not null default ('0'), `is_premium_pick` tinyint(1) not null default ('0'), `clicks_count` integer not null default ('0'), `popularity_score` DECIMAL(10,2) not null default ('0'), `status` varchar not null default 'draft', `created_at` DATETIME, `updated_at` DATETIME, `deleted_at` DATETIME, `is_top_selling` tinyint(1) NOT NULL DEFAULT 0, foreign key(`brand_id`) references brands(`id`) on delete set null on update no action, foreign key(`category_id`) references categories(`id`) on delete restrict on update no action) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (`id` varchar not null, `user_id` integer, `ip_address` varchar, `user_agent` TEXT, `payload` TEXT not null, `last_activity` integer not null, primary key (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `key` varchar not null, `value` TEXT, `created_at` DATETIME, `updated_at` DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sync_logs`;
CREATE TABLE `sync_logs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `merchant_id` integer not null, `status` varchar not null, `items_updated` INT NOT NULL DEFAULT 0, `items_failed` INT NOT NULL DEFAULT 0, `message` TEXT, `started_at` DATETIME, `finished_at` DATETIME, `created_at` DATETIME, `updated_at` DATETIME, foreign key(`merchant_id`) references `merchants`(`id`) on delete cascade) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar not null, `email` varchar not null, `email_verified_at` DATETIME, `password` varchar not null, `remember_token` varchar, `created_at` DATETIME, `updated_at` DATETIME, `role` varchar not null default 'analyst', `is_active` tinyint(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data
-- Table affiliate_conversions: 0 rows
-- Table affiliate_generation_runs: 0 rows
-- Table affiliate_link_generations: 0 rows
-- Table affiliate_networks: 3 rows
INSERT INTO `affiliate_networks` (`id`, `name`, `slug`, `website_url`, `api_config`, `notes`, `created_at`, `updated_at`) VALUES
-- Table affiliate_offers: 50 rows
INSERT INTO `affiliate_offers` (`id`, `offer_id`, `product_id`, `merchant_id`, `normal_product_url`, `affiliate_url`, `tracking_identifier`, `commission_rate`, `commission_type`, `commission_eligible`, `status`, `generation_method`, `generated_at`, `last_verified_at`, `last_error`, `metadata`, `created_at`, `updated_at`) VALUES
-- Table article_product: 0 rows
-- Table articles: 2 rows
INSERT INTO `articles` (`id`, `title`, `slug`, `type`, `excerpt`, `content`, `featured_image`, `category_id`, `author`, `status`, `published_at`, `seo_title`, `seo_description`, `canonical_url`, `og_image`, `faqs`, `selection_criteria`, `created_at`, `updated_at`) VALUES
-- Table attribute_definitions: 12 rows
INSERT INTO `attribute_definitions` (`id`, `category_id`, `name`, `key`, `data_type`, `unit`, `options`, `is_filterable`, `sort_order`, `created_at`, `updated_at`) VALUES
-- Table audit_logs: 53 rows
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `subject_type`, `subject_id`, `properties`, `created_at`, `updated_at`) VALUES
-- Table brands: 11 rows
INSERT INTO `brands` (`id`, `name`, `slug`, `logo_path`, `description`, `website_url`, `seo_title`, `seo_description`, `created_at`, `updated_at`) VALUES
-- Table cache: 9 rows
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
-- Table cache_locks: 0 rows
-- Table categories: 634 rows
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `intro_content`, `created_at`, `updated_at`) VALUES
-- Table clicks: 5 rows
INSERT INTO `clicks` (`id`, `offer_id`, `product_id`, `merchant_id`, `referrer_page`, `ip_hash`, `user_agent_family`, `clicked_at`, `clicked_on`) VALUES
-- Table comparison_offer: 0 rows
-- Table comparison_product: 0 rows
-- Table comparisons: 0 rows
-- Table deals: 0 rows
-- Table failed_jobs: 0 rows
-- Table import_batches: 2 rows
INSERT INTO `import_batches` (`id`, `filename`, `type`, `status`, `total_rows`, `imported_count`, `created_count`, `updated_count`, `skipped_count`, `failed_count`, `created_by`, `completed_at`, `created_at`, `updated_at`, `merchant_id`, `source_url`, `source_type`, `category_slug`) VALUES
-- Table import_errors: 0 rows
-- Table import_items: 0 rows
-- Table job_batches: 0 rows
-- Table jobs: 0 rows
-- Table landing_page_comparison: 0 rows
-- Table landing_page_product: 0 rows
-- Table landing_pages: 0 rows
-- Table merchants: 6 rows
INSERT INTO `merchants` (`id`, `affiliate_network_id`, `name`, `slug`, `logo_path`, `description`, `website_url`, `country`, `region`, `currencies`, `base_affiliate_url`, `tracking_template`, `feed_config`, `commission_note`, `status`, `last_synced_at`, `sync_status`, `terms_notes`, `seo_title`, `seo_description`, `created_at`, `updated_at`, `connector_type`, `product_import_method`, `affiliate_link_method`, `affiliate_enabled`, `configuration`) VALUES
-- Table migrations: 22 rows
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
-- Table offers: 50 rows
INSERT INTO `offers` (`id`, `product_id`, `merchant_id`, `external_product_id`, `external_url`, `affiliate_url`, `current_price`, `original_price`, `currency`, `availability`, `shipping_info`, `deal_expires_at`, `source`, `status`, `clicks_count`, `last_synced_at`, `created_at`, `updated_at`) VALUES
-- Table page_views: 0 rows
-- Table password_reset_tokens: 0 rows
-- Table price_drop_events: 0 rows
-- Table price_history: 50 rows
INSERT INTO `price_history` (`id`, `offer_id`, `price`, `currency`, `recorded_at`) VALUES
-- Table product_attributes: 0 rows
-- Table product_drafts: 0 rows
-- Table product_images: 50 rows
INSERT INTO `product_images` (`id`, `product_id`, `path`, `alt_text`, `is_main`, `sort_order`, `created_at`, `updated_at`) VALUES
-- Table products: 50 rows
INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `sku`, `model_number`, `gtin`, `product_type`, `short_description`, `description`, `summary_editorial`, `pros`, `cons`, `rating`, `pricing_model`, `has_free_plan`, `platforms`, `is_featured`, `is_trending`, `is_editors_pick`, `is_best_value`, `is_budget_pick`, `is_premium_pick`, `clicks_count`, `popularity_score`, `status`, `created_at`, `updated_at`, `deleted_at`, `is_top_selling`) VALUES
-- Table sessions: 1 rows
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
-- Table settings: 1 rows
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
-- Table sync_logs: 0 rows
-- Table users: 4 rows
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `is_active`) VALUES

SET FOREIGN_KEY_CHECKS=1;
