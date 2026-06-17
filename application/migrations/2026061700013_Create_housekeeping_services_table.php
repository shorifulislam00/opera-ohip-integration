<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_housekeeping_services_table
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
        $this->CI->db->query("CREATE TABLE `housekeeping_services` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `category_id` INT(10) NOT NULL,
            `type_id` INT(10) NULL DEFAULT NULL COMMENT '1=LD,2=DC,3=PR,4=Others',
            `service_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `price` DECIMAL(10,2) NOT NULL,
            `transaction_code` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
            `status` TINYINT(3) NOT NULL DEFAULT '1',
            `created_by` INT(10) NULL DEFAULT NULL,
            `created_at` DATETIME NULL DEFAULT NULL,
            `updated_by` INT(10) NULL DEFAULT NULL,
            `updated_at` DATETIME NULL DEFAULT NULL,
            `deleted_by` INT(10) NULL DEFAULT NULL,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            `is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb4_0900_ai_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `housekeeping_services`;");
    }
}
