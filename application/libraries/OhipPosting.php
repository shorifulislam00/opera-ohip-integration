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
}
