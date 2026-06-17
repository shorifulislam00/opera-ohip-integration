<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_guest_registration_room_no_mapping_table
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
        $this->CI->db->query("CREATE TABLE `fo_guest_registration_room_no_mapping` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `fo_registration_id` INT(10) UNSIGNED NOT NULL,
            `fo_guest_id` INT(10) UNSIGNED NOT NULL,
            `room_number` VARCHAR(20) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `fo_registration_id` (`fo_registration_id`) USING BTREE,
            INDEX `fo_guest_id` (`fo_guest_id`) USING BTREE,
            INDEX `room_number` (`room_number`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_guest_registration_room_no_mapping`;");
    }
}
