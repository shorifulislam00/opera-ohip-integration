<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_registrations_table
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
        $this->CI->db->query("CREATE TABLE `fo_registrations` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `opera_id` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `reservation_id` INT(10) UNSIGNED NULL DEFAULT NULL,
            `guest_ids` TEXT NULL DEFAULT NULL COMMENT '[1,2,3...]' COLLATE 'utf8mb3_unicode_ci',
            `checkin_date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
            `departure_date` DATETIME NULL DEFAULT NULL,
            `number_of_night` INT(10) NOT NULL DEFAULT '0',
            `company_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `currency_type` VARCHAR(20) NULL DEFAULT '1' COLLATE 'utf8mb3_unicode_ci',
            `conversion_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `room_type` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
            `room_number` JSON NULL DEFAULT NULL,
            `discount_type` SMALLINT(5) NULL DEFAULT NULL,
            `discount_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `rack_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `negotiated_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `service_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `vat_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `city_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `additional_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `total_room_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `market_segment_id` INT(10) NULL DEFAULT NULL,
            `guest_source_id` INT(10) NULL DEFAULT NULL,
            `meal_plan_id` INT(10) NULL DEFAULT NULL,
            `reference_id` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `hotel_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `pos_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `complimentary_item_ids` JSON NULL DEFAULT NULL COMMENT '[1,2,3...]',
            `person_adult` INT(10) NULL DEFAULT '0',
            `guest_type` INT(10) NULL DEFAULT '1',
            `person_child` INT(10) NULL DEFAULT '0',
            `registration_number` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `bill_no` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `contact_person` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `contact_person_id` INT(10) UNSIGNED NOT NULL,
            `mobile_number` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `contact_address` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `pay_for` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
            `payment_mode` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
            `checkout_status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 = Inhouse, 1 = Checked out',
            `house_use` INT(10) NULL DEFAULT NULL,
            `has_complimentary_guest` INT(10) NULL DEFAULT NULL,
            `created_by` INT(10) NULL DEFAULT NULL,
            `updated_by` INT(10) NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `checkin_by` INT(10) NULL DEFAULT NULL,
            `checkout_by` INT(10) NULL DEFAULT NULL,
            `reactive_by` INT(10) NULL DEFAULT NULL,
            `reactive_time` DATETIME NULL DEFAULT NULL,
            `guest_signature` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `registration_number_unique` (`registration_number`) USING BTREE,
            UNIQUE INDEX `opera_id_unique` (`opera_id`) USING BTREE,
            INDEX `reservation_id` (`reservation_id`) USING BTREE,
            INDEX `registration_number` (`registration_number`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_registrations`;");
    }
}
