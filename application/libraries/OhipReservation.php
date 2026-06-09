<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OhipReservation extends OhipClient {

    public function getInHouseReservations() {
        $result = $this->request('GET',
            '/rsv/v1/hotels/' . $this->hotel_id . '/reservations?reservationStatus=InHouse&limit=100'
        );

        // echo "<pre>";
        // print_r($result);
        // exit;

        return $result['body']['reservations']['reservationInfo'] ?? [];
    }

    public function getReservation($reservation_id) {
        $result = $this->request('GET',
            '/rsv/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id
        );
        return $result['body'];
    }

    public function getReservationsByDate($startDate) {
        $result = $this->request('GET',
            '/rsv/v1/hotels/' . $this->hotel_id . '/reservations?createdOnStartDate=' . $startDate . '&limit=200'
        );
        return $result['body']['reservations']['reservationInfo'] ?? [];
    }

    public function getFolio($reservation_id) {
        $result = $this->request('GET',
            '/csh/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id . '/folios'
        );
        return $result['body'];
    }

    public function postCharge($reservation_id, $data) {
        $result = $this->request('POST',
            '/csh/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id . '/charges',
            $data
        );
        return $result;
    }
}