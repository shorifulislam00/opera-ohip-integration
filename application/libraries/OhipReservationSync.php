<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OhipReservationSync extends OhipClient
{
    public function getReservationsByDate($startDate)
    {
        $result = $this->request('GET',
            '/rsv/v1/hotels/' . $this->hotel_id . '/reservations?createdOnStartDate=' . $startDate . '&limit=200'
        );
        return $result['body']['reservations']['reservationInfo'] ?? [];
    }

    public function syncReservations($startDate)
    {
        $CI =& get_instance();
        $CI->load->model('Ohip_sync_model');

        $list = $this->getReservationsByDate($startDate);

        // echo "<pre>";
        // print_r($list);
        // exit;

        if (empty($list)) {
            return ['message' => 'No reservations found from ' . $startDate];
        }

        $synced  = 0;
        $updated = 0;
        $errors  = 0;

        foreach ($list as $item) {
            $idList  = $item['reservationIdList'] ?? [];
            $operaId = '';

            foreach ($idList as $entry) {
                if (($entry['type'] ?? '') === 'Reservation') {
                    $operaId = $entry['id'] ?? '';
                    break;
                }
            }

            if (!$operaId) {
                $errors++;
                continue;
            }

            $status = $item['reservationStatus'] ?? '';

            $existing = $CI->Ohip_sync_model->findReservationByOperaId($operaId);

            if ($existing) {
                $CI->Ohip_sync_model->updateReservation($existing->id, $item);
                $updated++;
            } else {
                $result = $CI->Ohip_sync_model->createReservation($operaId, $item);
                if ($result) {
                    $synced++;
                } else {
                    $errors++;
                }
            }
        }

        return [
            'synced'  => $synced,
            'updated' => $updated,
            'errors'  => $errors,
        ];
    }
}
