<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_tbl_tablefloor_table
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
        $this->CI->db->query("CREATE TABLE `tbl_tablefloor` (
            `tbfloorid` INT(10) NOT NULL AUTO_INCREMENT,
            `floorName` VARCHAR(100) NOT NULL COLLATE 'utf8mb3_general_ci',
            `width` INT(10) NOT NULL DEFAULT '800',
            `height` INT(10) NOT NULL DEFAULT '540',
            `coa_id` VARCHAR(50) NOT NULL COLLATE 'utf8mb3_general_ci',
            `is_surcharge` INT(10) NULL DEFAULT '0',
            `transaction_code` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
            PRIMARY KEY (`tbfloorid`) USING BTREE,
            INDEX `coa_id` (`coa_id`) USING BTREE
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB AUTO_INCREMENT=6;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `tbl_tablefloor`;");
    }
}
