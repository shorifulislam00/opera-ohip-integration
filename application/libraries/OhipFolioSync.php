<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OhipFolioSync extends OhipClient
{
    public function getFolio($reservation_id)
    {
        $result = $this->request('GET',
            '/csh/v1/hotels/' . $this->hotel_id . '/reservations/' . $reservation_id . '/folios?folioWindowNo=1&limit=50&fetchInstructions=Postings&fetchInstructions=Totalbalance&fetchInstructions=Transactioncodes&fetchInstructions=Windowbalances'
        );
        return $result['body'];
    }

    public function syncServices($registration_id, $opera_id, $room_number, $guest_name)
    {
        $CI =& get_instance();
        $CI->load->model('Ohip_sync_model');

        $folio = $this->getFolio($opera_id);
        $postings = $this->extractPostings($folio);
        
        // echo "<pre>";
        // print_r($postings);
        // exit;


        if (empty($postings)) {
            return ['synced' => 0, 'skipped' => 0, 'errors' => 0, 'message' => 'No postings found'];
        }

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($postings as $posting) {
            $txn_code = $posting['transactionCode'] ?? '';
            $txn_no   = $posting['transactionNo'] ?? '';

            if (!$txn_code) {
                $errors++;
                continue;
            }

            if ($CI->Ohip_sync_model->isFolioPostingSynced($registration_id, $txn_no)) {
                $skipped++;
                continue;
            }

            $fo_service = $CI->Ohip_sync_model->findFoServiceByTransactionCode($txn_code);
            $hk_service = $CI->Ohip_sync_model->findHousekeepingServiceByTransactionCode($txn_code);

            if (!$fo_service && !$hk_service) {
                $skipped++;
                continue;
            }

            $result = $CI->Ohip_sync_model->createServiceBillFromPosting(
                $registration_id, $room_number, $guest_name, $posting, $fo_service, $hk_service
            );

            if ($result) {
                $synced++;
            } else {
                $errors++;
            }
        }

        return [
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }

    public function syncAll()
    {
        $CI =& get_instance();
        $CI->load->model('Ohip_sync_model');

        $registrations = $CI->Ohip_sync_model->getRegistrationsForServiceSync();

        if (empty($registrations)) {
            return ['message' => 'No registrations to sync'];
        }

        $results = [];

        foreach ($registrations as $reg) {
            $result = $this->syncServices($reg->id, $reg->opera_id, $reg->room_number, $reg->guest_name);
            $result['registration_id'] = $reg->id;
            $result['room_number']     = $reg->room_number;
            $result['opera_id']        = $reg->opera_id;
            $results[] = $result;
        }

        return $results;
    }

    private function extractPostings($folio)
    {
        $postings = [];

        $folioInfo = $folio['reservationFolioInformation'] ?? [];
        $windows   = $folioInfo['folioWindows'] ?? [];

        // echo "<pre>";
        // print_r($windows);
        // exit;


        foreach ($windows as $win) {
            $folios = $win['folios'] ?? [];
            foreach ($folios as $f) {
                $folioPostings = $f['postings'] ?? [];
                foreach ($folioPostings as $p) {
                    $postings[] = $p;
                }
            }
        }

        return $postings;
    }
}
