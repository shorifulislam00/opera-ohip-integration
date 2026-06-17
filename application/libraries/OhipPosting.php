<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OhipPosting extends OhipClient {

    public function postCharge($reservation_id, $data) {
        $result = $this->request('POST',
            '/csh/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id . '/charges',
            $data
        );
        return $result;
    }

    public function postChargesAndPayments($reservation_id, $charges, $payments) {
        $data = [
            'charges'   => $charges,
            'payments'  => $payments,
            'cashierId' => (int) $this->cashier_id,
        ];
        $result = $this->request('POST',
            '/csh/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id . '/chargesAndPayments',
            $data
        );

        // echo "<pre>";
        // print_r($result);
        // exit;

        return $result;
    }
}
