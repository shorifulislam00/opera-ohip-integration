<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_housekeeping_service_bills_table
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
        $this->CI->db->query("CREATE TABLE `housekeeping_service_bills` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `guest_type` TINYINT(3) NULL DEFAULT NULL,
            `room_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `reg_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `service_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `opera_posting_no` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `fo_service_no` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `date` DATE NOT NULL,
            `status` TINYINT(3) NOT NULL DEFAULT '0',
            `guest_name` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `guest_email` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `service_id` INT(10) NOT NULL,
            `service_rate` DECIMAL(10,2) NULL DEFAULT NULL,
            `service_qty` INT(10) NULL DEFAULT NULL,
            `service_charge` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `vat_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `grand_total` DECIMAL(10,2) NULL DEFAULT NULL,
            `remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `night_audit_status` TINYINT(3) NOT NULL DEFAULT '0',
            `is_complimentary` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '0=no, 1=yes',
            `created_by` INT(10) NOT NULL,
            `updated_by` INT(10) NOT NULL,
            `deleted_by` INT(10) NOT NULL,
            `delete_reason` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `service_discount` DECIMAL(10,2) NULL DEFAULT NULL,
            `amount` DECIMAL(10,2) NULL DEFAULT NULL,
            `service_contact_number` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `discount_type` INT(10) NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb4_0900_ai_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `housekeeping_service_bills`;");
    }
}
