<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_currency_table
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        if (!isset($this->CI->db)) {
            $this->CI->load->database();
        }
    }

    public function up()
    {
        $this->CI->db->query("CREATE TABLE `currency` (
            `currencyid` INT(10) NOT NULL AUTO_INCREMENT,
            `currencyname` VARCHAR(50) NOT NULL COLLATE 'utf8mb3_general_ci',
            `curr_icon` VARCHAR(50) NOT NULL COLLATE 'utf8mb3_general_ci',
            `position` INT(10) NOT NULL DEFAULT '1' COMMENT '1=left.2=right',
            `curr_rate` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            PRIMARY KEY (`currencyid`) USING BTREE
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `currency`;");
    }
}
