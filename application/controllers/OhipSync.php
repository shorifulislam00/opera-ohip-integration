<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OhipSync extends CI_Controller {

    public function registrations() {
        $this->load->library('OhipReservation');
        $this->load->model('Ohip_sync_model');

        $reservations = $this->ohipreservation->getInHouseReservations();

        if (empty($reservations)) {
            echo "No in-house reservations found.\n";
            return;
        }

        $synced   = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($reservations as $rsv) {
            $list = $rsv['reservationIdList'] ?? [];

            if (empty($list)) {
                $errors++;
                continue;
            }

            $opera_id = $list[0]['id'];

            if ($this->Ohip_sync_model->isSynced($opera_id)) {
                $skipped++;
                continue;
            }

            $response = $this->ohipreservation->getReservation($opera_id);

            if (empty($response)) {
                echo "Error fetching reservation $opera_id\n";
                $errors++;
                continue;
            }

            $detail = $response['reservations']['reservation'][0] ?? [];

            if (empty($detail)) {
                echo "Empty detail for $opera_id\n";
                $errors++;
                continue;
            }

            $result = $this->Ohip_sync_model->syncFromOpera($opera_id, $detail);

            if ($result) {
                // Check for linked reservation
                $linked = $this->Ohip_sync_model->findReservationByOperaId($opera_id);
                $linkMsg = $linked ? " (linked to reservation #{$linked->id})" : '';
                echo "Synced: $opera_id -> Registration #$result{$linkMsg}\n";
                $synced++;
            } else {
                echo "Failed: $opera_id\n";
                $errors++;
            }
        }

        echo "\nDone — Synced: $synced, Skipped: $skipped, Errors: $errors\n";
    }

    public function reservations() {

        $startDate = $this->db->select("app_date")->get("setting")->row()->app_date;

        // echo "<pre>";
        // print_r($startDate);
        // exit;

        $this->load->library('OhipReservationSync');
        $this->load->model('Ohip_sync_model');

        if (!$startDate) {
            $startDate = date('Y-m-d');
        }

        echo "Fetching Opera reservations from $startDate ...\n";

        $result = $this->ohipreservationsync->syncReservations($startDate);

        $msg = isset($result['message']) ? $result['message'] : '';
        if ($msg) {
            echo $msg . "\n";
            return;
        }

        echo "  Synced: {$result['synced']}, Updated: {$result['updated']}, Errors: {$result['errors']}\n";
        echo "Done.\n";
    }

    public function restaurant_charges() {
        $this->load->library('OhipPosting');
        $this->load->model('Ohip_sync_model');

        $bills = $this->Ohip_sync_model->getPendingMultipayBills();

        // echo "<pre>";
        // print_r($bills);
        // exit;

        if (empty($bills)) {
            echo "No pending restaurant bills to sync.\n";
            return;
        }

        $posted  = 0;
        $errors  = 0;

        foreach ($bills as $bill) {
            if (!$bill->transaction_code) {
                echo "No transaction code for outlet {$bill->outlet_id} (multipay #{$bill->multipay_id})\n";
                $errors++;
                continue;
            }

            $payload = [
                'criteria' => [
                    'postIt'    => false,
                    'cashierId' => $this->ohipposting->getCashierId(),
                    'charges'   => [
                        [
                            'transactionCode'          => $bill->transaction_code,
                            'price'                    => [
                                'amount'       => (string) $bill->amount,
                                'currencyCode' => 'BDT',
                            ],
                            'postingQuantity'          => 1,
                            'checkNumber'              => (string) $bill->order_id,
                            'applyRoutingInstructions' => false,
                            'usePackageAllowance'      => false,
                            'folioWindowNo'            => '1',
                        ],
                    ],
                ],
            ];

            $result = $this->ohipposting->postCharge($bill->opera_id, $payload);

            $postings = $result['body']['postings'] ?? [];

            if (!empty($postings)) {
                $this->Ohip_sync_model->markMultipaySynced($bill->multipay_id);
                $txn_nos = array_column($postings, 'transactionNo');
                echo "Posted: multipay #{$bill->multipay_id} (order {$bill->order_id}) -> Opera {$bill->opera_id}, txn(s): " . implode(',', $txn_nos) . "\n";
                $posted++;
            } else {
                $msg = is_string($result['body']) ? $result['body'] : json_encode($result['body'] ?? '');
                echo "Failed multipay #{$bill->multipay_id}: $msg\n";
                $errors++;
            }
        }

        echo "\nDone — Posted: $posted, Errors: $errors\n";
    }

    public function services() {
        $this->load->library('OhipFolioSync');
        $this->load->model('Ohip_sync_model');

        $registrations = $this->Ohip_sync_model->getRegistrationsForServiceSync();

        // echo "<pre>";
        // print_r($registrations);
        // exit;

        if (empty($registrations)) {
            echo "No registrations with Opera IDs to sync.\n";
            return;
        }

        $total_synced  = 0;
        $total_skipped = 0;
        $total_errors  = 0;

        foreach ($registrations as $reg) {
            echo "Processing reg #{$reg->id} (room {$reg->room_number}, Opera {$reg->opera_id})...\n";

            $result = $this->ohipfoliosync->syncServices(
                $reg->id,
                $reg->opera_id,
                $reg->room_number,
                $reg->guest_name
            );

            $msg = isset($result['message']) ? ' (' . $result['message'] . ')' : '';
            echo "  => Synced: {$result['synced']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}{$msg}\n";

            $total_synced  += $result['synced'];
            $total_skipped += $result['skipped'];
            $total_errors  += $result['errors'];
        }

        echo "\nDone — Total Synced: $total_synced, Skipped: $total_skipped, Errors: $total_errors\n";
    }
}
