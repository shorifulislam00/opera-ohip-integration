<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_reservations_room_details_table
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
        $this->CI->db->query("CREATE TABLE `fo_reservations_room_details` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `reservation_id` INT(10) UNSIGNED NULL DEFAULT NULL,
            `reservation_number` VARCHAR(20) NOT NULL COMMENT 'Example: GR00010386' COLLATE 'utf8mb3_unicode_ci',
            `checkin_date2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `checkout_date2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `number_of_night2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `room_type` SMALLINT(5) UNSIGNED NOT NULL,
            `room_number` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `discount_type` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
            `discount_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `rack_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `negotiated_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `service_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `vat_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `city_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `additional_charge` DOUBLE(13,4) NULL DEFAULT NULL,
            `total_room_rate` DOUBLE(13,4) NULL DEFAULT NULL,
            `room_quantity` INT(10) UNSIGNED NOT NULL DEFAULT '1',
            `pax_in` INT(10) UNSIGNED NOT NULL DEFAULT '1',
            `child_in` SMALLINT(5) UNSIGNED NULL DEFAULT '0',
            `extra_bed` TINYINT(3) NOT NULL DEFAULT '0',
            `extrabed_from_date` DATE NULL DEFAULT NULL,
            `extrabed_to_date` DATE NULL DEFAULT NULL,
            `extrabed_total` DOUBLE NULL DEFAULT '0',
            `extrabed_rate` DOUBLE NULL DEFAULT '0',
            `extrabed_vat` DOUBLE NOT NULL DEFAULT '0',
            `extrabed_Scharge` DOUBLE NOT NULL DEFAULT '0',
            `room_registration_status` TINYINT(3) NOT NULL DEFAULT '0',
            `is_global_date` TINYINT(3) NOT NULL DEFAULT '1',
            `is_no_show` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
            `is_no_post` TINYINT(1) NOT NULL DEFAULT '0',
            `is_dnm` TINYINT(1) NOT NULL DEFAULT '0',
            `has_complimentary_guest` TINYINT(1) NULL DEFAULT NULL,
            `house_use` TINYINT(1) NULL DEFAULT NULL,
            `created_at` DATETIME NULL DEFAULT NULL,
            `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            UNIQUE INDEX `id_primary` (`id`) USING BTREE,
            INDEX `reservation_id` (`reservation_id`) USING BTREE,
            INDEX `reservation_number` (`reservation_number`) USING BTREE,
            INDEX `room_type` (`room_type`) USING BTREE,
            INDEX `room_number` (`room_number`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_reservations_room_details`;");
    }
}
