<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_sys_opera_config_table
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
        $this->CI->db->query("CREATE TABLE `sys_opera_config` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `hostname` VARCHAR(255) NOT NULL COMMENT 'OHIP base URL',
            `hotel_id` VARCHAR(50) NOT NULL COMMENT 'Opera hotel/enterprise ID',
            `app_key` VARCHAR(255) NOT NULL COMMENT 'x-app-key header',
            `enterprise_id` VARCHAR(100) NOT NULL COMMENT 'Enterprise ID for token auth',
            `client_id` VARCHAR(255) NOT NULL COMMENT 'Basic auth client ID',
            `client_secret` VARCHAR(255) NOT NULL COMMENT 'Basic auth client secret',
            `cashier_id` VARCHAR(50) NOT NULL COMMENT 'Cashier ID for charge posting',
            `pm_reservation_confirmation` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Fallback PM reservation confirmation',
            `pm_reservation_id` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Fallback PM Opera reservation ID',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `sys_opera_config`;");
    }
}
