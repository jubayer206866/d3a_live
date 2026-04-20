<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_323 extends CI_Migration
{
    public function up()
    {
        $dbPrefix = db_prefix();

        if (! $this->db->table_exists($dbPrefix . 'ladder_rates')) {
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . $dbPrefix . 'ladder_rates` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `min_cbm` decimal(10,2) NOT NULL DEFAULT 0.00,
                `max_cbm` decimal(10,2) NOT NULL DEFAULT 0.00,
                `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ' COLLATE=' . $this->db->dbcollat . ';');
        }
    }

    public function down()
    {
        $dbPrefix = db_prefix();

        if ($this->db->table_exists($dbPrefix . 'ladder_rates')) {
            $this->db->query('DROP TABLE IF EXISTS `' . $dbPrefix . 'ladder_rates`;');
        }
    }
}
