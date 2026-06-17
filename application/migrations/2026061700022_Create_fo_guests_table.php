<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_fo_guests_table
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
        $this->CI->db->query("CREATE TABLE `fo_guests` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `first_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `last_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `coa_id` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `dob` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `gender` TINYINT(3) NULL DEFAULT NULL,
            `company_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            `address` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `email` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `profession` INT(10) NULL DEFAULT NULL,
            `phone` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `city` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `zip` INT(10) NULL DEFAULT NULL,
            `country` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
            `nationality` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `dl_no` INT(10) NULL DEFAULT NULL,
            `nid_no` INT(10) NULL DEFAULT NULL,
            `visa_no` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `visa_issue_date` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `visa_expiry_date` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `passport_no` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `passport_issue_date` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `passport_expiry_date` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            `blocked_guest` TINYINT(3) NOT NULL DEFAULT '0',
            `is_contact_person` TINYINT(1) NOT NULL DEFAULT '0',
            `document_info` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_unicode_ci',
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb3_unicode_ci' ENGINE=InnoDB AUTO_INCREMENT=352;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `fo_guests`;");
    }
}
