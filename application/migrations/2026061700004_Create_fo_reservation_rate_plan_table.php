<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_reservation_rate_plan_table
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
        $this->CI->db->query("CREATE TABLE `fo_reservation_rate_plan` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `room_details_id` BIGINT(20) UNSIGNED NOT NULL,
            `reservation_id` BIGINT(20) UNSIGNED NOT NULL,
            `rate_date` DATE NOT NULL,
            `room_type` BIGINT(20) UNSIGNED NOT NULL,
            `room_number` SMALLINT(5) UNSIGNED NOT NULL,
            `rate_status` ENUM('1','2','3') NOT NULL DEFAULT '1' COMMENT '1=Regular, 2=Weekend, 3=Festival' COLLATE 'utf8mb4_general_ci',
            `discount_type` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
            `discount_amount` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
            `rack_rate` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
            `negotiated_rate` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
            `service_charge` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
            `vat_charge` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
            `total_room_rate` DECIMAL(10,2) UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL,
            `created_by` BIGINT(20) UNSIGNED NOT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `deleted_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `reservation_id` (`reservation_id`, `rate_date`, `room_number`) USING BTREE,
            INDEX `fo_reservation_rate_plan_ibfk_1` (`room_details_id`) USING BTREE,
            INDEX `FK_fo_reservation_rate_plan_user` (`created_by`) USING BTREE,
            INDEX `FK_fo_reservation_rate_plan_user_2` (`updated_by`) USING BTREE,
            INDEX `FK_fo_reservation_rate_plan_user_3` (`deleted_by`) USING BTREE,
            CONSTRAINT `FK_fo_reservation_rate_plan_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
            CONSTRAINT `FK_fo_reservation_rate_plan_user_2` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
            CONSTRAINT `FK_fo_reservation_rate_plan_user_3` FOREIGN KEY (`deleted_by`) REFERENCES `user` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
            CONSTRAINT `fo_reservation_rate_plan_ibfk_1` FOREIGN KEY (`room_details_id`) REFERENCES `fo_reservations_room_details` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
            CONSTRAINT `fo_reservation_rate_plan_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `fo_reservations` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
        ) COLLATE='utf8mb4_general_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_reservation_rate_plan`;");
    }
}
