<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_roomdetails_table
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
        $this->CI->db->query("CREATE TABLE `roomdetails` (
            `roomid` INT(10) NOT NULL AUTO_INCREMENT,
            `roomtype` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `shortname` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `opera_code` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_room_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gozayan_room_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `sharetrip_room_code` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `roomsize` INT(10) NOT NULL,
            `roomsizemesurement` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `roomactive` INT(10) NOT NULL,
            `bedsno` INT(10) NOT NULL,
            `bedstype` INT(10) NOT NULL,
            `number_of_star` INT(10) NULL DEFAULT '4',
            `roomdescription` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `reservecondition` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `roomstatus` INT(10) NOT NULL DEFAULT '0',
            `capacity` INT(10) NOT NULL,
            `exbedcapability` INT(10) NOT NULL DEFAULT '1',
            `child_limit` INT(10) NULL DEFAULT '0',
            `rate` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `weekend_rate` DECIMAL(10,2) UNSIGNED NULL DEFAULT '0.00',
            `festival_rate` DECIMAL(10,2) UNSIGNED NULL DEFAULT '0.00',
            `bedcharge` DECIMAL(10,0) NOT NULL,
            `personcharge` DECIMAL(10,0) NOT NULL,
            `is_folio` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
            `aiosell_rateplancode_EP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_CP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_AP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_MAP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gozayan_rateplancode_EP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gozayan_rateplancode_CP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gozayan_rateplancode_AP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gozayan_rateplancode_MAP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `sharetrip_rateplancode_EP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `sharetrip_rateplancode_CP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `sharetrip_rateplancode_AP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `sharetrip_rateplancode_MAP` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_EP_s` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_EP_d` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_CP_s` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_CP_d` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_AP_s` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_AP_d` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_MAP_s` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `aiosell_rateplancode_MAP_d` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`roomid`) USING BTREE,
            INDEX `roomtype` (`roomtype`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `roomdetails`;");
    }
}
