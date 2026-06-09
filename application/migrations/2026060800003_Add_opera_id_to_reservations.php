<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_AddOperaIdToReservations
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
        $this->CI->db->query("ALTER TABLE `fo_reservations`
            ADD COLUMN `opera_id` VARCHAR(50) NULL DEFAULT NULL AFTER `id`,
            ADD UNIQUE INDEX `opera_id_unique` (`opera_id`);");
    }
}
