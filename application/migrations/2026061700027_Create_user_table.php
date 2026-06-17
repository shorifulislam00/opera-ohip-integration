<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_user_table
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
        $this->CI->db->query("CREATE TABLE `user` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `firstname` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `lastname` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `about` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `email` VARCHAR(100) NOT NULL COLLATE 'utf8mb3_general_ci',
            `device_token` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `waiter_kitchenToken` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `password` VARCHAR(32) NOT NULL COLLATE 'utf8mb3_general_ci',
            `password_reset_token` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `image` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `last_login` DATETIME NULL DEFAULT NULL,
            `last_logout` DATETIME NULL DEFAULT NULL,
            `ip_address` VARCHAR(14) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            `status` TINYINT(1) NOT NULL DEFAULT '1',
            `usertype` INT(10) NOT NULL DEFAULT '1' COMMENT '1=user,2=employee',
            `is_admin` TINYINT(3) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`) USING BTREE
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `user`;");
    }
}
