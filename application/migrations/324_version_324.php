<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_324 extends CI_Migration
{
    public function up()
    {
        $dbPrefix = db_prefix();

        if (! $this->db->table_exists($dbPrefix . 'po_invoice_details')) {
            $this->db->query('CREATE TABLE `' . $dbPrefix . 'po_invoice_details` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `pur_invoice` INT(11) NOT NULL,
                `item_code` VARCHAR(100) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `unit_id` INT(11) DEFAULT NULL,
                `unit_price` DECIMAL(15,2) DEFAULT NULL,
                `quantity` DECIMAL(15,2) DEFAULT NULL,
                `into_money` DECIMAL(15,2) DEFAULT NULL,
                `tax` TEXT DEFAULT NULL,
                `total` DECIMAL(15,2) DEFAULT NULL,
                `discount_percent` DECIMAL(15,2) DEFAULT NULL,
                `discount_money` DECIMAL(15,2) DEFAULT NULL,
                `total_money` DECIMAL(15,2) DEFAULT NULL,
                `tax_value` DECIMAL(15,2) DEFAULT NULL,
                `tax_rate` TEXT DEFAULT NULL,
                `tax_name` TEXT DEFAULT NULL,
                `item_name` TEXT DEFAULT NULL,
                `koli` VARCHAR(255) DEFAULT NULL,
                `cope_koli` VARCHAR(255) DEFAULT NULL,
                `total_koli` VARCHAR(255) DEFAULT NULL,
                `price` VARCHAR(255) DEFAULT NULL,
                `price_total` VARCHAR(255) DEFAULT NULL,
                `gross_weight` VARCHAR(255) DEFAULT NULL,
                `total_gross_weight` VARCHAR(255) DEFAULT NULL,
                `net_weight` VARCHAR(255) DEFAULT NULL,
                `total_net_weight` VARCHAR(255) DEFAULT NULL,
                `cbm_koli` VARCHAR(255) DEFAULT NULL,
                `total_cbm` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `pur_invoice` (`pur_invoice`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ' 
            COLLATE=' . $this->db->dbcollat . ';');
        }


        if (! $this->db->table_exists($dbPrefix . 'po_approval_details')) {
            $this->db->query('CREATE TABLE `' . $dbPrefix . 'po_approval_details` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `rel_id` INT(11) NOT NULL,
                `rel_type` VARCHAR(45) NOT NULL,
                `staffid` VARCHAR(45) DEFAULT NULL,
                `approve` VARCHAR(45) DEFAULT NULL,
                `note` TEXT DEFAULT NULL,
                `date` DATETIME DEFAULT NULL,
                `approve_action` VARCHAR(255) DEFAULT NULL,
                `reject_action` VARCHAR(255) DEFAULT NULL,
                `approve_value` VARCHAR(255) DEFAULT NULL,
                `reject_value` VARCHAR(255) DEFAULT NULL,
                `staff_approve` INT(11) DEFAULT NULL,
                `action` VARCHAR(45) DEFAULT NULL,
                `sender` INT(11) DEFAULT NULL,
                `date_send` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `rel_id` (`rel_id`),
                KEY `rel_type` (`rel_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ' 
            COLLATE=' . $this->db->dbcollat . ';');
        }
    }

    public function down()
    {
        $dbPrefix = db_prefix();

        if ($this->db->table_exists($dbPrefix . 'po_invoice_details')) {
            $this->db->query('DROP TABLE `' . $dbPrefix . 'po_invoice_details`;');
        }

        if ($this->db->table_exists($dbPrefix . 'po_approval_details')) {
            $this->db->query('DROP TABLE `' . $dbPrefix . 'po_approval_details`;');
        }
    }
}