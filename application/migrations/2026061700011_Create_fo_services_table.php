<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_services_table
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
        $this->CI->db->query("CREATE TABLE `fo_services` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `coa_id` VARCHAR(50) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `service_amount` DOUBLE(13,4) NOT NULL DEFAULT '0.0000',
            `service_type` TINYINT(3) NOT NULL DEFAULT '3',
            `transaction_code` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `is_deleted` TINYINT(3) NOT NULL DEFAULT '0',
            `created_at` DATETIME NULL DEFAULT NULL,
            `created_by` INT(10) NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` INT(10) NULL DEFAULT NULL,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `deleted_by` INT(10) NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `coa_id` (`coa_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_services`;");
    }
}
