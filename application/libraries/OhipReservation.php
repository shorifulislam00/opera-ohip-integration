<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OhipReservation extends OhipClient {

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

    public function getReservationByConfirmation($confirmationNo) {
        $result = $this->request('GET',
            '/rsv/v1/hotels/' . $this->hotel_id . '/reservations?confirmationNumberList=' . urlencode($confirmationNo) . '&limit=1'
        );
        $list = $result['body']['reservations']['reservationInfo'] ?? [];
        if (empty($list)) {
            return null;
        }
        $idList = $list[0]['reservationIdList'] ?? [];
        foreach ($idList as $entry) {
            if (($entry['type'] ?? '') === 'Reservation') {
                return $entry['id'];
            }
        }
        return null;
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