<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_setting_table
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
        $this->CI->db->query("CREATE TABLE `setting` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `storename` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `address` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `email` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `phone` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `logo` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `is_enable_cubixsite` INT(10) NOT NULL DEFAULT '1',
            `hotel_code` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `api_email` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `api_password` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `token` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `aiosell_hotel_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `gozayan_hotel_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `sharetrip_hotel_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `splash_logo` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_general_ci',
            `favicon` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `vat` INT(10) NOT NULL DEFAULT '0',
            `isvatnumshow` INT(10) NULL DEFAULT '0',
            `vattinno` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `vat_registration_no` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `servicecharge` INT(10) NOT NULL DEFAULT '0',
            `vat_for_rooms` INT(10) NOT NULL DEFAULT '0',
            `service_charge_for_rooms` INT(10) NOT NULL DEFAULT '0',
            `banquet_vat` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb3_general_ci',
            `banquet_service_charge` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8mb3_general_ci',
            `max_room_per_row` INT(10) NOT NULL DEFAULT '10',
            `is_room_type_short_name` INT(10) NOT NULL DEFAULT '1',
            `voucher_approved_date_wise` TINYINT(3) NOT NULL DEFAULT '1',
            `bin_no` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `check_vat` INT(10) NOT NULL DEFAULT '1',
            `check_service_charge` INT(10) NOT NULL DEFAULT '1',
            `discount_type` INT(10) NOT NULL DEFAULT '0' COMMENT '0=amount,1=percent',
            `service_chargeType` INT(10) NOT NULL DEFAULT '0' COMMENT '0=amount,1=percent',
            `discountrate` DECIMAL(19,3) NOT NULL DEFAULT '0.000',
            `country` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `map_key` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `latitude` DOUBLE(10,4) NULL DEFAULT NULL,
            `longitude` DOUBLE(10,4) NULL DEFAULT NULL,
            `currency` INT(10) NULL DEFAULT '0',
            `language` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `timezone` VARCHAR(150) NOT NULL COLLATE 'utf8mb3_general_ci',
            `checkintime` TIME NOT NULL,
            `checkouttime` TIME NOT NULL,
            `dateformat` TEXT NOT NULL COLLATE 'utf8mb3_general_ci',
            `site_align` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `pricetxt` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `powerbytxt` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `footer_text` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `app_date` DATE NULL DEFAULT NULL,
            `app_room_audit_date` DATE NULL DEFAULT NULL,
            `app_service_audit_date` DATE NULL DEFAULT NULL,
            `app_restaurant_audit_date` DATE NULL DEFAULT NULL,
            `app_banquet_audit_date` DATE NULL DEFAULT NULL,
            `hotel_policy_desc` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `registration_term` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `inventory_deduct` TINYINT(3) NULL DEFAULT NULL,
            `extra_bed_stock` INT(10) NULL DEFAULT NULL,
            `invoice_print_copy` ENUM('1','2') NULL DEFAULT '2' COLLATE 'utf8mb3_general_ci',
            `is_forcefull_checkout` TINYINT(3) UNSIGNED NULL DEFAULT '0',
            `is_enable_rateplan` TINYINT(3) UNSIGNED NULL DEFAULT '0',
            `is_enable_vat_server` TINYINT(3) UNSIGNED NULL DEFAULT '0',
            `vat_server_link` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `is_enable_vat_delete` TINYINT(3) UNSIGNED NULL DEFAULT '0',
            `weekend_days` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `theme` INT(10) NOT NULL DEFAULT '2',
            `is_received_from_enabled` TINYINT(1) NOT NULL DEFAULT '0',
            `login_back` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `secondary_logo` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `sd_charge_percent` INT(10) NULL DEFAULT NULL,
            `sur_charge_percent` INT(10) NULL DEFAULT NULL,
            `whatsapp_support_group` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `warning_message` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `warning_icon` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `warning_type` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `setting`;");
    }
}
