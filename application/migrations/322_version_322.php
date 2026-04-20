<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_322 extends CI_Migration
{
    public function up()
    {
        $dbPrefix = db_prefix();

        if ($this->db->table_exists($dbPrefix . 'categories')) {
            if (! $this->db->field_exists('customs_duty', $dbPrefix . 'categories')) {
                $this->db->query('ALTER TABLE `' . $dbPrefix . 'categories` ADD `customs_duty` DECIMAL(10,2) NULL DEFAULT NULL AFTER `floor_euro_per_cbm`;');
            }
            if (! $this->db->field_exists('vat_integre_percent', $dbPrefix . 'categories')) {
                $this->db->query('ALTER TABLE `' . $dbPrefix . 'categories` ADD `vat_integre_percent` DECIMAL(10,2) NULL DEFAULT NULL AFTER `customs_duty`;');
            }
        }
    }

    public function down()
    {
        $dbPrefix = db_prefix();

        if ($this->db->table_exists($dbPrefix . 'categories')) {
            if ($this->db->field_exists('customs_duty', $dbPrefix . 'categories')) {
                $this->db->query('ALTER TABLE `' . $dbPrefix . 'categories` DROP COLUMN `customs_duty`;');
            }
            if ($this->db->field_exists('vat_integre_percent', $dbPrefix . 'categories')) {
                $this->db->query('ALTER TABLE `' . $dbPrefix . 'categories` DROP COLUMN `vat_integre_percent`;');
            }
        }
    }
}
