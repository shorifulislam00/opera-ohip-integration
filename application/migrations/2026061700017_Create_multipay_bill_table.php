<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_multipay_bill_table
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
        $this->CI->db->query("CREATE TABLE `multipay_bill` (
            `multipay_id` INT(10) NOT NULL AUTO_INCREMENT,
            `order_id` INT(10) NOT NULL,
            `sub_order_id` INT(10) NULL DEFAULT NULL,
            `multipayid` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `payment_type_id` INT(10) NOT NULL COMMENT '1=Card,2=Nagad,4=Cash,5=BKash,6=Bank,7=Company,8=RoomTransfer',
            `registration_id` INT(10) NULL DEFAULT NULL,
            `room_no` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `company_id` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `payment_acc_number` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `transaction_id` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `bank_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `amount` FLOAT NOT NULL,
            `payment_status` INT(10) NOT NULL DEFAULT '1',
            `created_at` DATETIME NULL DEFAULT NULL,
            `created_by` INT(10) NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` INT(10) NULL DEFAULT NULL,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `deleted_by` INT(10) NULL DEFAULT NULL,
            `opera_sync` TINYINT(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`multipay_id`) USING BTREE,
            INDEX `order_id` (`order_id`) USING BTREE,
            INDEX `payment_type_id` (`payment_type_id`) USING BTREE,
            INDEX `registration_id` (`registration_id`) USING BTREE,
            INDEX `room_no` (`room_no`) USING BTREE,
            INDEX `multipay_opera_sync_idx` (`opera_sync`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `multipay_bill`;");
    }
}
