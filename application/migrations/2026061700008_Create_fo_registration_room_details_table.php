<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_registration_room_details_table
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
        $this->CI->db->query("CREATE TABLE `fo_registration_room_details` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `fo_registration_id` INT(10) UNSIGNED NOT NULL,
            `checkin_date2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `checkout_date2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `number_of_night2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `room_type` SMALLINT(5) UNSIGNED NOT NULL,
            `room_number` VARCHAR(20) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `discount_type` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
            `discount_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `rack_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `negotiated_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `service_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `vat_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `city_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `additional_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `total_room_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `pax_in` SMALLINT(5) UNSIGNED NOT NULL,
            `child_in` SMALLINT(5) UNSIGNED NULL DEFAULT '0',
            `status` TINYINT(3) UNSIGNED NOT NULL COMMENT '0= Pending, 1 = Registered, 2 = No Show',
            `checkin_time` DATETIME NULL DEFAULT NULL,
            `checkout_time` DATETIME NULL DEFAULT NULL,
            `extra_bed` TINYINT(3) NOT NULL DEFAULT '0',
            `extrabed_from_date` DATE NULL DEFAULT NULL,
            `extrabed_to_date` DATE NULL DEFAULT NULL,
            `extrabed_total` DOUBLE NULL DEFAULT '0',
            `extrabed_rate` DOUBLE NULL DEFAULT '0',
            `extrabed_vat` DOUBLE NOT NULL DEFAULT '0',
            `extrabed_Scharge` DOUBLE NOT NULL DEFAULT '0',
            `has_complimentary_guest` TINYINT(3) NULL DEFAULT NULL,
            `house_use` TINYINT(3) NULL DEFAULT NULL,
            `pre_room_rate` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `rate_updated_by` INT(10) NULL DEFAULT NULL,
            `rate_updated_date_time` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `is_no_show` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
            `is_no_post` TINYINT(1) NOT NULL DEFAULT '0',
            `rate_remarks` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `is_global_date` TINYINT(3) NOT NULL DEFAULT '1',
            `checkin_by` INT(10) NULL DEFAULT NULL,
            `checkout_by` INT(10) NULL DEFAULT NULL,
            `created_at` DATETIME NULL DEFAULT NULL,
            `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `fo_registration_id` (`fo_registration_id`) USING BTREE,
            INDEX `room_type` (`room_type`) USING BTREE,
            INDEX `room_number` (`room_number`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_registration_room_details`;");
    }
}
