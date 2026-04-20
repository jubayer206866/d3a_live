<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Create ALB Invoices table
if (!$CI->db->table_exists(db_prefix() . 'alb_invoices')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_invoices` (
      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `number` int(11) NOT NULL,
      `invoice_number` VARCHAR(100) NULL,
      `clientid` int(11) NOT NULL,
      `date` DATE NOT NULL,
      `duedate` DATE NULL,
      `subtotal` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `total_tax` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `total` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `status` INT(11) NOT NULL DEFAULT '1',
      `currency` INT(11) NOT NULL DEFAULT '0',
      `billing_street` TEXT NULL,
      `billing_city` VARCHAR(100) NULL,
      `billing_state` VARCHAR(100) NULL,
      `billing_zip` VARCHAR(100) NULL,
      `billing_country` INT(11) NULL DEFAULT '0',
      `shipping_street` TEXT NULL,
      `shipping_city` VARCHAR(100) NULL,
      `shipping_state` VARCHAR(100) NULL,
      `shipping_zip` VARCHAR(100) NULL,
      `shipping_country` INT(11) NULL DEFAULT '0',
      `adminnote` TEXT NULL,
      `terms` TEXT NULL,
      `datecreated` DATETIME NOT NULL,
      `addedfrom` INT(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `clientid` (`clientid`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Add hash, invoice_currency, invoice_amount if missing
if ($CI->db->table_exists(db_prefix() . 'alb_invoices')) {
    if (!$CI->db->field_exists('hash', db_prefix() . 'alb_invoices')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'alb_invoices` ADD `hash` VARCHAR(32) NULL DEFAULT NULL');
        // Generate hash for existing records
        $rows = $CI->db->get(db_prefix() . 'alb_invoices')->result();
        foreach ($rows as $row) {
            $CI->db->where('id', $row->id);
            $CI->db->update(db_prefix() . 'alb_invoices', ['hash' => app_generate_hash()]);
        }
    }
    if (!$CI->db->field_exists('sent', db_prefix() . 'alb_invoices')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'alb_invoices` ADD `sent` TINYINT(1) NOT NULL DEFAULT 0');
    }
    if (!$CI->db->field_exists('datesend', db_prefix() . 'alb_invoices')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'alb_invoices` ADD `datesend` DATETIME NULL DEFAULT NULL');
    }
    if (!$CI->db->field_exists('invoice_currency', db_prefix() . 'alb_invoices')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'alb_invoices` ADD `invoice_currency` INT(11) NOT NULL DEFAULT 0 AFTER `currency`');
        $CI->db->query('UPDATE `' . db_prefix() . 'alb_invoices` SET `invoice_currency` = `currency`');
    }
    if (!$CI->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'alb_invoices` ADD `invoice_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total`');
        $CI->db->query('UPDATE `' . db_prefix() . 'alb_invoices` SET `invoice_amount` = `total`');
    }
}

// Create ALB Invoice Items table
if (!$CI->db->table_exists(db_prefix() . 'alb_invoice_items')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_invoice_items` (
      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `invoice_id` int(11) NOT NULL,
      `description` TEXT NOT NULL,
      `long_description` TEXT NULL,
      `qty` DECIMAL(15,2) NOT NULL DEFAULT '1.00',
      `rate` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `item_order` INT(11) NULL,
      `tax` INT(11) NULL,
      `tax2` INT(11) NULL,
      PRIMARY KEY (`id`),
      KEY `invoice_id` (`invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create ALB Payments table
if (!$CI->db->table_exists(db_prefix() . 'alb_payments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_payments` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `invoiceid` INT(11) NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `invoice_amount` DECIMAL(15,2) NOT NULL,
      `paymentmode` INT(11) DEFAULT NULL,
      `paymentmethod` VARCHAR(50) DEFAULT NULL,
      `date` DATE NOT NULL,
      `daterecorded` DATETIME NOT NULL,
      `note` TEXT DEFAULT NULL,
      `transactionid` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `invoiceid` (`invoiceid`)
    ) ENGINE=InnoDB 
    DEFAULT CHARSET=utf8mb4 
    COLLATE=utf8mb4_general_ci;");
}

// Create ALB Expenses table
if (!$CI->db->table_exists(db_prefix() . 'alb_expenses')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_expenses` (
      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `category` int(11) NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `tax` int(11) DEFAULT NULL,
      `reference_no` VARCHAR(100) DEFAULT NULL,
      `note` TEXT NULL,
      `attachment` MEDIUMTEXT NULL,
      `filetype` VARCHAR(50) DEFAULT NULL,
      `clientid` int(11) NOT NULL DEFAULT '0',
      `paymentmode` int(11) DEFAULT NULL,
      `date` DATE NOT NULL,
      `dateadded` DATETIME NOT NULL,
      `addedfrom` INT(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `category` (`category`),
      KEY `clientid` (`clientid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create ALB PO Invoices table
if (!$CI->db->table_exists(db_prefix() . 'alb_po_invoices')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_po_invoices` (
      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `number` int(11) NOT NULL,
      `invoice_number` VARCHAR(100) NULL,
      `vendor` int(11) NULL,
      `invoice_date` DATE NULL,
      `duedate` DATE NULL,
      `subtotal` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `tax` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `total` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `payment_status` VARCHAR(30) NULL DEFAULT 'unpaid',
      `vendor_note` TEXT NULL,
      `adminnote` TEXT NULL,
      `terms` TEXT NULL,
      `datecreated` DATETIME NOT NULL,
      `addedfrom` INT(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `vendor` (`vendor`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create ALB PO Invoice Items table
if (!$CI->db->table_exists(db_prefix() . 'alb_po_invoice_items')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_po_invoice_items` (
      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `po_invoice_id` int(11) NOT NULL,
      `description` TEXT NOT NULL,
      `qty` DECIMAL(15,2) NOT NULL DEFAULT '1.00',
      `rate` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
      `item_order` INT(11) NULL,
      `tax` INT(11) NULL,
      `tax_select` VARCHAR(255) NULL,
      PRIMARY KEY (`id`),
      KEY `po_invoice_id` (`po_invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create ALB PO Payments table
if (!$CI->db->table_exists(db_prefix() . 'alb_po_payments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "alb_po_payments` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `invoiceid` INT(11) NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `invoice_amount` DECIMAL(15,2) NOT NULL,
      `paymentmode` INT(11) DEFAULT NULL,
      `paymentmethod` VARCHAR(50) DEFAULT NULL,
      `date` DATE NOT NULL,
      `daterecorded` DATETIME NOT NULL,
      `note` TEXT DEFAULT NULL,
      `transactionid` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `invoiceid` (`invoiceid`)
    ) ENGINE=InnoDB 
    DEFAULT CHARSET=utf8mb4 
    COLLATE=utf8mb4_general_ci;");
}

// Set default options for ALB Invoice prefix and next number
$CI->db->where('option_name', 'alb_inv_prefix');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_inv_prefix", "ALB-INV-", "1");');
} else {
    // Migrate old default to new format (ALB-INV-0000001)
    $CI->db->where('option_name', 'alb_inv_prefix');
    $row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    if ($row && $row->option_val == 'D3A-ALB') {
        $CI->db->where('option_name', 'alb_inv_prefix');
        $CI->db->update(db_prefix() . 'purchase_option', ['option_val' => 'ALB-INV-']);
    }
}

$CI->db->where('option_name', 'alb_next_inv_number');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_next_inv_number", "1", "1");');
}

// Set default options for ALB Draft next number
$CI->db->where('option_name', 'alb_next_draft_number');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_next_draft_number", "1", "1");');
}

// Set default number padding to 7 (ALB-INV-0000001)
$CI->db->where('option_name', 'alb_number_padding');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_number_padding", "7", "1");');
} else {
    // Migrate old default padding 6 to 7
    $CI->db->where('option_name', 'alb_number_padding');
    $row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    if ($row && $row->option_val == '6') {
        $CI->db->where('option_name', 'alb_number_padding');
        $CI->db->update(db_prefix() . 'purchase_option', ['option_val' => '7']);
    }
}

// Set default options for ALB PO Invoice prefix and next number
$CI->db->where('option_name', 'alb_po_inv_prefix');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_po_inv_prefix", "AIV", "1");');
}

$CI->db->where('option_name', 'alb_po_next_inv_number');
if ($CI->db->get(db_prefix() . 'purchase_option')->num_rows() == 0) {
    $CI->db->query('INSERT INTO `' . db_prefix() . 'purchase_option` (`option_name`, `option_val`, `auto`) VALUES ("alb_po_next_inv_number", "1", "1");');
}

// Create ALB company uploads directory for logo, stamp, etc.
$alb_company_path = FCPATH . 'uploads/alb_company/';
if (!is_dir($alb_company_path)) {
    _maybe_create_upload_path($alb_company_path);
}
