<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_registrations_other_info_table
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
        $this->CI->db->query("CREATE TABLE `fo_registrations_other_info` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `registration_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `coming_from` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `next_destination` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `visit_purpose` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `has_complimentary_guests` TINYINT(3) NULL DEFAULT NULL,
            `has_new_complimentary_guest` INT(10) NULL DEFAULT NULL,
            `house_use` TINYINT(3) NULL DEFAULT NULL,
            `new_house_use` INT(10) NULL DEFAULT NULL,
            `room_owner` TINYINT(3) NULL DEFAULT NULL,
            `is_previously_visited` TINYINT(3) NULL DEFAULT NULL,
            `is_vip` TINYINT(3) NULL DEFAULT NULL,
            `airport_drop` TINYINT(3) NULL DEFAULT NULL,
            `airline_name` BIGINT(19) NULL DEFAULT NULL,
            `flight_number` VARCHAR(160) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `departure_time_etd` TIME NULL DEFAULT NULL,
            `is_chargable` TINYINT(3) NULL DEFAULT NULL,
            `card_type` TINYINT(3) NULL DEFAULT NULL,
            `card_number` INT(10) NULL DEFAULT NULL,
            `cardholder_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `card_expiry_date` DATE NULL DEFAULT NULL,
            `card_reference` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `registration_id` (`registration_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_registrations_other_info`;");
    }
}
