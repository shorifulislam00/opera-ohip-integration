<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_acc_automation_table
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
        $this->CI->db->query("CREATE TABLE `acc_automation` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `company_head_code_name` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
            `employee_head_code_name` JSON NULL DEFAULT NULL,
            `cost_center_head_code_name` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
            `cash_main_head_code_name` JSON NULL DEFAULT NULL,
            `cash_in_hand_head_code_name` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
            `banking_head_code_name` JSON NULL DEFAULT NULL,
            `m_banking_head_code_name` JSON NULL DEFAULT NULL,
            `entertainment_head_code_name` JSON NULL DEFAULT NULL,
            `revenue_head_code_name` JSON NULL DEFAULT NULL,
            `vat_tax_head_code_name` JSON NULL DEFAULT NULL,
            `other_outlet_revenue_head_code_name` JSON NULL DEFAULT NULL,
            `rebate_refund_complimentary_head_code_name` JSON NULL DEFAULT NULL,
            `user_id_inserted` INT(10) NOT NULL DEFAULT '0',
            `user_id_updated` INT(10) NOT NULL DEFAULT '0',
            `user_id_deleted` INT(10) NOT NULL DEFAULT '0',
            `user_date_inserted` DATETIME NULL DEFAULT NULL,
            `user_date_updated` DATETIME NULL DEFAULT NULL,
            `user_date_deleted` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE,
            CONSTRAINT `acc_automation_chk_1` CHECK (json_valid(`company_head_code_name`)),
            CONSTRAINT `acc_automation_chk_2` CHECK (json_valid(`cost_center_head_code_name`)),
            CONSTRAINT `acc_automation_chk_3` CHECK (json_valid(`cash_in_hand_head_code_name`))
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB AUTO_INCREMENT=2;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `acc_automation`;");
    }
}
