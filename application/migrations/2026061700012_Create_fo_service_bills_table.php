<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_service_bills_table
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
        $this->CI->db->query("CREATE TABLE `fo_service_bills` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `guest_type` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
            `room_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `reg_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `service_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `opera_posting_no` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `date` DATE NOT NULL,
            `status` TINYINT(1) NOT NULL DEFAULT '0',
            `guest_name` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `guest_email` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `service_id` INT(10) UNSIGNED NULL DEFAULT NULL,
            `service_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `service_qty` INT(10) UNSIGNED NULL DEFAULT NULL,
            `service_charge` DOUBLE(13,4) NULL DEFAULT '0.0000',
            `vat_amount` DOUBLE(13,4) NULL DEFAULT '0.0000',
            `sd_charge` DOUBLE(13,4) NULL DEFAULT '0.0000',
            `additional_charge` DOUBLE(13,4) NULL DEFAULT '0.0000',
            `grand_total` DOUBLE(13,4) NULL DEFAULT NULL,
            `is_complimentary` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
            `remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `created_by` INT(10) UNSIGNED NOT NULL,
            `updated_by` INT(10) UNSIGNED NULL DEFAULT NULL,
            `deleted_by` INT(10) UNSIGNED NULL DEFAULT NULL,
            `delete_reason` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `night_audit_status` INT(10) NOT NULL DEFAULT '0',
            `pre_service_rate` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `rate_remarks` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `rate_updated_by` INT(10) NULL DEFAULT NULL,
            `rate_updated_date_time` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `service_discount` DECIMAL(10,2) NULL DEFAULT NULL,
            `amount` DECIMAL(10,2) NULL DEFAULT NULL,
            `service_contact_number` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `discount_type` INT(10) NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB AUTO_INCREMENT=60;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_service_bills`;");
    }
}
