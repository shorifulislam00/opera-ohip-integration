<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_AddOperaPostingNoToBills
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
        $this->CI->db->query("ALTER TABLE `fo_service_bills`
            ADD COLUMN `opera_posting_no` VARCHAR(50) NULL DEFAULT NULL AFTER `service_no`;");

        $this->CI->db->query("ALTER TABLE `housekeeping_service_bills`
            ADD COLUMN `opera_posting_no` VARCHAR(50) NULL DEFAULT NULL AFTER `service_no`;");

        $this->CI->db->query("DROP TABLE IF EXISTS `opera_folio_sync_log`;");
    }
}
