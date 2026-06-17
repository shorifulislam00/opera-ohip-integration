<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_tbl_roomnofloorassign_table
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
        $this->CI->db->query("CREATE TABLE `tbl_roomnofloorassign` (
            `roomassignid` INT(10) NOT NULL AUTO_INCREMENT,
            `roomid` INT(10) NOT NULL,
            `floorid` INT(10) NOT NULL,
            `roomno` INT(10) NOT NULL,
            `status` INT(10) NULL DEFAULT '1' COMMENT '1=ready,2=booked,3=assigned to clean,4=booked and assigned to clean, 5=under maintenance,6=dirty,7=blocked,8=do not reserve',
            PRIMARY KEY (`roomassignid`) USING BTREE
        ) COLLATE='utf8mb3_general_ci' ENGINE=InnoDB;");
    }

    public function down()
    {
        $this->CI->db->query("DROP TABLE IF EXISTS `tbl_roomnofloorassign`;");
    }
}
