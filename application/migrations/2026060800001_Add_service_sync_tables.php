<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_AddServiceSyncTables
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
        $this->CI->db->query("ALTER TABLE `fo_services`
            ADD COLUMN `transaction_code` VARCHAR(50) NULL DEFAULT NULL AFTER `service_type`;");

        $this->CI->db->query("ALTER TABLE `housekeeping_services`
            ADD COLUMN `transaction_code` VARCHAR(50) NULL DEFAULT NULL AFTER `price`;");

        $this->CI->db->query("CREATE TABLE IF NOT EXISTS `opera_folio_sync_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `registration_id` INT NOT NULL,
            `opera_posting_no` VARCHAR(50) NOT NULL,
            `transaction_code` VARCHAR(50) NULL DEFAULT NULL,
            `service_no` VARCHAR(50) NULL DEFAULT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_reg_posting` (`registration_id`, `opera_posting_no`)
        );");
    }
}
