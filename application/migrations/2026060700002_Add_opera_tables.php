<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_AddOperaTables
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
        $this->CI->db->query("ALTER TABLE `tbl_tablefloor`
            ADD COLUMN `transaction_code` VARCHAR(50) NULL DEFAULT NULL AFTER `is_surcharge`;");

        $this->CI->db->query("ALTER TABLE `multipay_bill`
            ADD COLUMN `opera_sync` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted_by`,
            ADD INDEX `multipay_opera_sync_idx` (`opera_sync`);");
    }
}
