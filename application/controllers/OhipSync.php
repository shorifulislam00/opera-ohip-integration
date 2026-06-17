<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OhipSync extends CI_Controller {

    public function registration_update($confirmationNo = '') {
        $this->load->library('OhipReservation');
        $this->load->model('Ohip_sync_model');

        if (!$confirmationNo) {
            echo "Usage: php index.php OhipSync registration_update CONFIRMATION_NO\n";
            return;
        }

        echo "Looking up Opera reservation by confirmation: $confirmationNo ...\n";
        $opera_id = $this->ohipreservation->getReservationByConfirmation($confirmationNo);

        if (!$opera_id) {
            echo "Not found in Opera.\n";
            return;
        }

        echo "Found Opera ID: $opera_id\n";

        $existing = $this->Ohip_sync_model->isSynced($opera_id);
        if (!$existing) {
            echo "Registration not found in PMS (opera_id=$opera_id). Run `reservations` first to sync it.\n";
            return;
        }

        $response = $this->ohipreservation->getReservation($opera_id);
        if (empty($response)) {
            echo "Error fetching reservation $opera_id from Opera.\n";
            return;
        }

        $detail = $response['reservations']['reservation'][0] ?? [];
        if (empty($detail)) {
            echo "Empty detail for $opera_id.\n";
            return;
        }

        $result = $this->Ohip_sync_model->syncFromOpera($opera_id, $detail);
        if ($result) {
            echo "Updated: $opera_id -> Registration #$result\n";
        } else {
            echo "Failed: $opera_id\n";
        }
    }

    public function reservations() {

        $startDate = $this->db->select("app_date")->get("setting")->row()->app_date;

        $this->load->library('OhipReservationSync');
        $this->load->model('Ohip_sync_model');

        if (!$startDate) {
            $startDate = date('Y-m-d');
        }

        // Fetch reservations modified in the last 7 days to catch all changes
        $since = date('Y-m-d', strtotime($startDate . ' -7 day'));

        echo "Fetching Opera reservations modified since $since ...\n";

        $result = $this->ohipreservationsync->syncReservations($since);

        $msg = isset($result['message']) ? $result['message'] : '';
        if ($msg) {
            echo $msg . "\n";
            return;
        }

        echo "  Reservation — Synced: {$result['rsvSynced']}, Updated: {$result['rsvUpdated']}\n";
        echo "  Registration — Synced: {$result['regSynced']}, Updated: {$result['regUpdated']}\n";
        echo "  Checked Out: {$result['checkedOut']}, Errors: {$result['errors']}\n";
        echo "Done.\n";
    }

    public function restaurant_charges() {
        $this->load->library('OhipPosting');
        $this->load->model('Ohip_sync_model');

        $bills = $this->Ohip_sync_model->getPendingMultipayBills();

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

            $charge = [
                'transactionCode'          => $bill->transaction_code,
                'price'                    => [
                    'amount'       => (float) $bill->amount,
                    'currencyCode' => 'BDT',
                ],
                'postingQuantity'          => 1,
                'checkNumber'              => (string) $bill->order_id,
                'applyRoutingInstructions' => false,
                'usePackageAllowance'      => false,
                'folioWindowNo'            => 1,
            ];

            $isRoomTransfer = ((int) $bill->payment_type_id === 8);

            if ($isRoomTransfer) {
                // Room transfer — post charge only to guest's Opera reservation
                if (!$bill->opera_id) {
                    echo "No Opera reservation for room transfer multipay #{$bill->multipay_id}\n";
                    $errors++;
                    continue;
                }

                $payload = [
                    'criteria' => [
                        'postIt'    => false,
                        'cashierId' => $this->ohipposting->getCashierId(),
                        'charges'   => [$charge],
                    ],
                ];

                $result = $this->ohipposting->postCharge($bill->opera_id, $payload);
                $postings = $result['body']['postings'] ?? [];

                if (!empty($postings)) {
                    $this->Ohip_sync_model->markMultipaySynced($bill->multipay_id);
                    $txn_nos = array_column($postings, 'transactionNo');
                    echo "Room transfer: multipay #{$bill->multipay_id} (order {$bill->order_id}) -> Opera {$bill->opera_id}, txn(s): " . implode(',', $txn_nos) . "\n";
                    $posted++;
                } else {
                    $msg = is_string($result['body']) ? $result['body'] : json_encode($result['body'] ?? '');
                    echo "Failed room transfer multipay #{$bill->multipay_id}: $msg\n";
                    $errors++;
                }
            } else {
                // Direct payment (cash/card/company) or walk-in — post charge + payment together
                $targetOperaId = $bill->opera_id ?: $this->Ohip_sync_model->getPmReservationId();

                if (!$targetOperaId) {
                    echo "No target Opera reservation for direct pay multipay #{$bill->multipay_id} (payment_type_id={$bill->payment_type_id})\n";
                    $errors++;
                    continue;
                }

                // For M-Banking, resolve payment code from acc_automation by head_code
                $paymentMapping = null;
                if ((int) $bill->payment_type_id === 4 && $bill->bank_name) {
                    $paymentMapping = $this->Ohip_sync_model->resolveMbankingFromAutomation($bill->bank_name);
                }
                if (!$paymentMapping) {
                    $paymentMapping = $this->Ohip_sync_model->resolvePaymentMethodToOpera($bill->payment_type_id);
                }

                if (!$paymentMapping) {
                    echo "No Opera payment mapping for payment_type_id={$bill->payment_type_id} (multipay #{$bill->multipay_id})\n";
                    $errors++;
                    continue;
                }

                $payment = [
                    'paymentMethod'  => [
                        'paymentMethod' => $paymentMapping->opera_payment_code,
                    ],
                    'postingAmount'  => [
                        'amount'       => (float) $bill->amount,
                        'currencyCode' => 'BDT',
                    ],
                    'action'         => $paymentMapping->opera_action,
                    'folioWindowNo'  => 1,
                ];

                $result = $this->ohipposting->postChargesAndPayments($targetOperaId, [$charge], [$payment]);

                $statusCode = $result['status'] ?? 0;
                $isSuccess  = $statusCode >= 200 && $statusCode < 300;

                if (!$isSuccess) {
                    $msg = is_string($result['body']) ? $result['body'] : json_encode($result['body'] ?? '');
                    echo "Failed direct pay multipay #{$bill->multipay_id} (order {$bill->order_id}): $msg\n";
                    $errors++;
                    continue;
                }

                $this->Ohip_sync_model->markMultipaySynced($bill->multipay_id);
                $txn_nos = [];
                if (!empty($result['body']['postings'])) {
                    $txn_nos = array_column($result['body']['postings'], 'transactionNo');
                }
                echo "Direct pay: multipay #{$bill->multipay_id} (order {$bill->order_id}) -> Opera {$targetOperaId}"
                    . (!empty($txn_nos) ? ", txn(s): " . implode(',', $txn_nos) : "")
                    . "\n";
                $posted++;
            }
        }

        echo "\nDone — Posted: $posted, Errors: $errors\n";
    }

    public function savePmReservation($confirmationNo = '') {
        $this->load->library('OhipReservation');
        $this->load->model('Ohip_sync_model');

        if (!$confirmationNo) {
            echo "Usage: php index.php OhipSync savePmReservation CONFIRMATION_NUMBER\n";
            return;
        }

        echo "Looking up Opera reservation by confirmation: $confirmationNo ...\n";

        $operaId = $this->ohipreservation->getReservationByConfirmation($confirmationNo);

        if (!$operaId) {
            echo "Error: No reservation found for confirmation number '$confirmationNo'\n";
            return;
        }

        $this->Ohip_sync_model->savePmReservationConfig($confirmationNo, $operaId);
        echo "Saved: confirmation=$confirmationNo, reservation_id=$operaId\n";
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
