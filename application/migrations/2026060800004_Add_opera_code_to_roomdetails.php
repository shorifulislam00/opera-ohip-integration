<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_AddOperaCodeToRoomdetails
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
        $this->CI->db->query("ALTER TABLE `roomdetails`
            ADD COLUMN `opera_code` VARCHAR(50) NULL DEFAULT NULL AFTER `shortname`;");
    }
}
