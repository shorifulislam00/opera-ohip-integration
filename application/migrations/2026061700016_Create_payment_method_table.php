<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_payment_method_table
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
        $this->CI->db->query("CREATE TABLE `payment_method` (
            `payment_method_id` BIGINT(19) NOT NULL AUTO_INCREMENT,
            `payment_method` VARCHAR(100) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `is_active` TINYINT(1) NOT NULL,
            `opera_code` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Opera payment method code' COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`payment_method_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `payment_method`;");
    }
}
