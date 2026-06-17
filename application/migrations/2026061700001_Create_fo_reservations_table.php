<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_reservations_table
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
        $this->CI->db->query("CREATE TABLE `fo_reservations` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `opera_id` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `reservation_number` VARCHAR(20) NOT NULL COMMENT 'Example: GR00010386' COLLATE 'utf8mb3_unicode_ci',
            `cmBookingId` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_bookingId` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `guest_ids` JSON NOT NULL COMMENT '[1,2,3...]',
            `checkin_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `checkout_date` DATETIME NULL DEFAULT NULL,
            `number_of_night` INT(10) NOT NULL DEFAULT '0',
            `reservation_mode` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1 = Self, 2 = Company, 3 = Group',
            `company_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `group_name` VARCHAR(60) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `contact_person` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `contact_address` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `mobile` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `email` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `payment_mode` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
            `pay_for` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
            `currency_type` VARCHAR(20) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `conversion_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `market_segment_id` INT(10) UNSIGNED NULL DEFAULT NULL COMMENT '1 = Dhaka Office',
            `guest_source_id` INT(10) UNSIGNED NULL DEFAULT NULL COMMENT '1 = Website, 2 = Marketing, 3 = Other Source, 4 = Broker, 5 = Bed',
            `channel` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `bookers_name` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `classification` SMALLINT(5) UNSIGNED NULL DEFAULT NULL COMMENT '1 = Regular, 2 = VIP, 3 = Complimentary, 4 = Time Sharing',
            `meal_plan` SMALLINT(5) UNSIGNED NULL DEFAULT NULL COMMENT '1 = Bed Only, 2 = Bed & Breakfast, 3 = Half Board, 4 = Full Board, 5 = Bed, Food, Laundry except hard-drinks',
            `reference_id` VARCHAR(30) NULL DEFAULT NULL COMMENT 'ID of the customers' COLLATE 'utf8mb3_unicode_ci',
            `hotel_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `guest_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `pos_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `waiting_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `confirm_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `reservation_status` TINYINT(3) UNSIGNED NOT NULL COMMENT '1 = Confirmed, 2 = Waiting',
            `reason_of_cancel` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `show_room_rate_in_reports` TINYINT(3) UNSIGNED NOT NULL COMMENT '1 = Yes, 2 = No',
            `complimentary_item_ids` JSON NULL DEFAULT NULL COMMENT '[1,2,3...]',
            `is_printable` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
            `is_printable_pre_reg` TINYINT(1) NULL DEFAULT '0',
            `registration_status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 = Pending, 1 = Registered, 2 = Partially Registered, 3 = No Show ',
            `created_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `updated_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `cancelled_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `reactivate_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `waited_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `confirmed_by` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `reactive_by` INT(10) NULL DEFAULT NULL,
            `reactive_time` DATETIME NULL DEFAULT NULL,
            `advance_payment_id` INT(10) NULL DEFAULT NULL COMMENT 'This id is for advance reservation payment from reservation form.',
            `guest_signature` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `reservation_number_unique` (`reservation_number`) USING BTREE,
            UNIQUE INDEX `opera_id_unique` (`opera_id`) USING BTREE,
            INDEX `reservation_number` (`reservation_number`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB AUTO_INCREMENT=201;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_reservations`;");
    }
}
