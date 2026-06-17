<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OhipReservationSync extends OhipClient
{
    public function getReservationsByLastModify($startDate)
    {
        $allItems = [];
        $offset   = 0;
        $limit    = 500;

        while (true) {
            $result = $this->request('GET',
                '/rsv/v1/hotels/' . $this->hotel_id . '/reservations?lastModifyStartDate=' . $startDate . '&limit=' . $limit . '&offset=' . $offset
            );

            $items   = $result['body']['reservations']['reservationInfo'] ?? [];
            $allItems = array_merge($allItems, $items);

            if (empty($result['body']['hasMore']) || empty($items)) {
                break;
            }
            $offset += $limit;
        }

        return $allItems;
    }

    public function syncReservations($startDate)
    {
        $CI =& get_instance();
        $CI->load->model('Ohip_sync_model');

        $list = $this->getReservationsByLastModify($startDate);

        if (empty($list)) {
            return ['message' => 'No reservations found from ' . $startDate];
        }

        $rsvSynced   = 0;
        $rsvUpdated  = 0;
        $regSynced   = 0;
        $regUpdated  = 0;
        $checkedOut  = 0;
        $errors      = 0;

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

            $status = strtolower($item['reservationStatus'] ?? '');

            // 1. Reservation sync (create or update) — applies to all statuses
            $existing = $CI->Ohip_sync_model->findReservationByOperaId($operaId);

            if ($existing) {
                $CI->Ohip_sync_model->updateReservation($existing->id, $item);
                $rsvUpdated++;
            } else {
                $result = $CI->Ohip_sync_model->createReservation($operaId, $item);
                if ($result) {
                    $rsvSynced++;
                } else {
                    $errors++;
                    continue;
                }
            }

            // 2. Registration sync — only for inhouse and checkedout
            if ($status !== 'inhouse' && $status !== 'checkedin' && $status !== 'checkedout') {
                continue;
            }

            $regExisting = $CI->Ohip_sync_model->isSynced($operaId);
            $regResult   = $CI->Ohip_sync_model->syncRegistrationFromList($operaId, $item);

            if ($regResult) {
                if ($regExisting) {
                    $regUpdated++;
                } else {
                    $regSynced++;
                }
            } else {
                echo "Registration sync failed for $operaId\n";
                $errors++;
                continue;
            }

            // 3. Mark registration as checked out
            if ($status === 'checkedout') {
                $regId = $regExisting ? $regExisting->id : $regResult;
                $departure  = $item['roomStay']['departureDate'] ?? null;
                $checkoutDate = $departure ? date('Y-m-d H:i:s', strtotime($departure)) : null;
                $CI->Ohip_sync_model->markRegistrationCheckedOut($regId, $checkoutDate);
                $checkedOut++;
            }
        }

        return [
            'rsvSynced'  => $rsvSynced,
            'rsvUpdated' => $rsvUpdated,
            'regSynced'  => $regSynced,
            'regUpdated' => $regUpdated,
            'checkedOut' => $checkedOut,
            'errors'     => $errors,
        ];
    }
}
