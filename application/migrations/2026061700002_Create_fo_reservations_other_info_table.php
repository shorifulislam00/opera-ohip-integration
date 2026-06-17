<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_reservations_other_info_table
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
        $this->CI->db->query("CREATE TABLE `fo_reservations_other_info` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `reservation_id` BIGINT(19) NOT NULL,
            `arrive_airport_pickup` TINYINT(1) NULL DEFAULT NULL,
            `arrive_airline_name` BIGINT(19) NULL DEFAULT NULL,
            `arrive_flight_number` VARCHAR(160) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `arrive_depture_date` TIME NULL DEFAULT NULL,
            `arrive_is_chargable` TINYINT(1) NULL DEFAULT NULL,
            `depture_airport_pickup` TINYINT(1) NULL DEFAULT NULL,
            `depture_airline_name` BIGINT(19) NULL DEFAULT NULL,
            `depture_flight_number` VARCHAR(160) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `depture_date` TIME NULL DEFAULT NULL,
            `depture_is_chargable` TINYINT(1) NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `reservation_id` (`reservation_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_reservations_other_info`;");
    }
}
