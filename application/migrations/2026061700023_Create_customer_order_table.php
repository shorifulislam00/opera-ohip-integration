<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_customer_order_table
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
        $this->CI->db->query("CREATE TABLE `customer_order` (
            `order_id` BIGINT(19) NOT NULL AUTO_INCREMENT,
            `saleinvoice` VARCHAR(100) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `marge_order_id` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `customer_id` INT(10) NOT NULL,
            `registration_id` INT(10) NULL DEFAULT NULL,
            `room_number` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `guest_id` INT(10) NULL DEFAULT NULL,
            `outlet_id` INT(10) NULL DEFAULT NULL,
            `is_booking` SMALLINT(5) NULL DEFAULT NULL,
            `booking_no` INT(10) NULL DEFAULT NULL,
            `cutomertype` INT(10) NOT NULL,
            `waiter_id` INT(10) NULL DEFAULT NULL,
            `kitchen` INT(10) NULL DEFAULT NULL,
            `order_date` DATE NOT NULL,
            `order_time` TIME NOT NULL,
            `cookedtime` TIME NOT NULL DEFAULT '00:15:00',
            `pax` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `table_no` INT(10) NULL DEFAULT NULL,
            `tokenno` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `bill_amount` DECIMAL(20,4) NULL DEFAULT '0.0000',
            `totalamount` DECIMAL(20,4) NOT NULL DEFAULT '0.0000',
            `customerpaid` DECIMAL(20,2) NULL DEFAULT '0.00',
            `is_complimentary` TINYINT(1) NOT NULL DEFAULT '0',
            `is_no_bill_charged` TINYINT(1) NOT NULL DEFAULT '0',
            `customer_note` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `anyreason` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `remarks` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `order_status` TINYINT(1) NOT NULL COMMENT '1=Pending, 2=Processing, 3=Ready, 4=Served,5=Cancel',
            `orderacceptreject` INT(10) NULL DEFAULT NULL,
            `splitpay_status` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '0=no split,1=split',
            `audit_status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 = Pending, 1 = Audited',
            `isupdate` INT(10) NULL DEFAULT NULL,
            `tokenprint` INT(10) NOT NULL DEFAULT '0' COMMENT '1=print done,0=not done',
            `created_at` DATETIME NULL DEFAULT NULL,
            `created_by` INT(10) NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` INT(10) NULL DEFAULT NULL,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `deleted_by` INT(10) NULL DEFAULT NULL,
            `reactivate` INT(10) NOT NULL DEFAULT '0',
            `reactivate_by` TINYINT(3) NULL DEFAULT NULL,
            PRIMARY KEY (`order_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `customer_order`;");
    }
}
