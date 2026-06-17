<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_room_bills_table
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
        $this->CI->db->query("CREATE TABLE `fo_room_bills` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `date` DATE NOT NULL,
            `room` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `registration_id` INT(10) NOT NULL,
            `prev_registration_id` INT(10) UNSIGNED NULL DEFAULT NULL,
            `guest_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `service` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `qty` TINYINT(3) NOT NULL DEFAULT '1',
            `rate` DOUBLE(13,4) NOT NULL,
            `discount_amount` DOUBLE(13,4) NULL DEFAULT NULL,
            `service_charge` DOUBLE(13,4) NOT NULL,
            `city_charge` DOUBLE(13,4) NOT NULL,
            `vat_charge` DOUBLE(13,4) NOT NULL,
            `additional_charge` DOUBLE(13,4) NOT NULL,
            `total` DOUBLE(13,4) NOT NULL,
            `has_complimentary_guest` TINYINT(3) NULL DEFAULT NULL,
            `house_use` TINYINT(3) NULL DEFAULT NULL,
            `status` TINYINT(3) NOT NULL DEFAULT '0' COMMENT '0 = NO 1 = YES',
            `entry_type` TINYINT(3) NOT NULL DEFAULT '1' COMMENT '1= Auto 2 = Scheduler 3 = manual',
            `pre_room_rate` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `rate_remarks` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `rate_updated_by` INT(10) NULL DEFAULT NULL,
            `rate_updated_date_time` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `hotel_remarks` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `is_no_show` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `date_room_registration_unique` (`date`, `room`, `registration_id`) USING BTREE,
            INDEX `room` (`room`) USING BTREE,
            INDEX `registration_id` (`registration_id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB AUTO_INCREMENT=197;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_room_bills`;");
    }
}
