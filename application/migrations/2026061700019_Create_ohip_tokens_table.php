<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_ohip_tokens_table
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
        $this->CI->db->query("CREATE TABLE `ohip_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `access_token` TEXT NOT NULL,
            `expires_at` INT(10) UNSIGNED NOT NULL COMMENT 'Unix timestamp',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `ohip_tokens`;");
    }
}
