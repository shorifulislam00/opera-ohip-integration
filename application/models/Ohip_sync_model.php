<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ohip_sync_model extends CI_Model
{
    public function isSynced($opera_id)
    {
        return $this->db->where('opera_id', $opera_id)->get('fo_registrations')->row();
    }

    public function syncFromOpera($opera_id, $detail)
    {
        $roomStay = $detail['roomStay'] ?? [];
        $roomInfo = $roomStay['currentRoomInfo'] ?? [];

        $existing    = $this->isSynced($opera_id);
        $room_number = $roomInfo['roomId'] ?? '';
        $arrival     = $roomStay['arrivalDate'] ?? date('Y-m-d');
        $departure   = $roomStay['departureDate'] ?? date('Y-m-d');

        $room_type = $this->resolveRoomType($room_number);

        $reservation = $this->findReservationByOperaId($opera_id);

        $guestCounts = $roomStay['guestCounts'] ?? [];
        $adults  = $guestCounts['adults'] ?? 1;
        $children = $guestCounts['children'] ?? 0;

        $roomRates  = $roomStay['roomRates'] ?? [];
        $rateEntries = $this->extractRateEntries($roomRates);

        $guest_arr  = $detail['reservationGuests'] ?? [];
        $guest      = $this->extractGuest($guest_arr);

        $roomRateFirst   = $rateEntries[0] ?? [];
        $rack_rate       = $roomRateFirst['base_amount'] ?? 0;
        $negotiated_rate = $roomRateFirst['amount'] ?? 0;
        $total_room_rate = array_sum(array_column($rateEntries, 'amount'));

        $hotel_remarks = $this->extractComments($detail['comments'] ?? []);

        $registration = [
            'opera_id'        => $opera_id,
            'checkin_date'    => date('Y-m-d H:i:s', strtotime($arrival)),
            'departure_date'  => date('Y-m-d H:i:s', strtotime($departure)),
            'number_of_night' => $this->nightCount($arrival, $departure),
            'contact_person'  => $guest['name'],
            'contact_address' => $guest['address'],
            'mobile_number'   => $guest['phone'],
            'guest_type'      => 1,
            'person_adult'    => $adults,
            'person_child'    => $children,
            'hotel_remarks'   => $hotel_remarks,
            'checkout_status' => 0,
            'reservation_id'  => $reservation ? $reservation->id : null,
            'created_by'      => 1,
            'updated_by'      => 1,
            'checkin_by'      => 1,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $room_detail = [
            'room_type'        => $room_type,
            'room_number'      => $room_number,
            'checkin_date2'    => $arrival,
            'checkout_date2'   => $departure,
            'number_of_night2' => $this->nightCount($arrival, $departure),
            'pax_in'           => $adults,
            'child_in'         => $children,
            'rack_rate'        => $rack_rate,
            'negotiated_rate'  => $negotiated_rate,
            'total_room_rate'  => $total_room_rate,
            'status'           => 1,
            'is_global_date'   => 1,
            'checkin_time'     => date('Y-m-d H:i:s', strtotime($arrival)),
            'checkin_by'       => 1,
        ];

        $departureTransport = $guest_arr[0]['departureTransport'] ?? [];

        $other_info = [
            'airport_drop'       => !empty($departureTransport['transportationReqd']) ? 1 : null,
            'flight_number'        => !empty($departureTransport['stationCode']) ? $departureTransport['stationCode'] : null,
            'departure_time_etd' => !empty($departureTransport['dateTime']) ? date('H:i:s', strtotime($departureTransport['dateTime'])) : null,
        ];

        if ($existing) {
            return $this->updateExisting($existing, $registration, $room_detail, $rateEntries);
        }

        return $this->insertNew($registration, $room_detail, $guest, $rateEntries, $other_info);
    }

    private function extractRateEntries($roomRates)
    {
        $entries = [];

        foreach ($roomRates as $rr) {
            $rates = $rr['rates']['rate'] ?? [];

            if (isset($rates[0])) {
                // indexed array of rate entries
                $entries = array_merge($entries, $rates);
            } elseif (isset($rates['base'])) {
                // single rate object
                $entries[] = $rates;
            }

            // also use flat per-roomRate entries if no nested rates
            if (empty($rates) && isset($rr['total'])) {
                $entries[] = $rr;
            }
        }

        $out = [];
        foreach ($entries as $e) {
            $base    = $e['base'] ?? [];
            $total   = $e['total'] ?? [];

            $out[] = [
                'start'       => $e['start'] ?? '',
                'end'         => $e['end'] ?? '',
                'base_amount' => $base['baseAmount'] ?? $base['amountBeforeTax'] ?? 0,
                'amount'      => $total['amountBeforeTax'] ?? 0,
            ];
        }

        return $out;
    }

    private function extractComments($comments)
    {
        $lines = [];

        foreach ($comments as $c) {
            $text = isset($c['comment']['text']['value']) ? $c['comment']['text']['value'] : '';

            if ($text) {
                $lines[] = $text;
            }
        }

        if(empty($lines)) {
            return '';
        }

        return $lines ? implode("\n", $lines) : '';
    }

    private function extractGuest($guest_arr)
    {
        $out = [
            'name'        => '',
            'title'       => 6,
            'first_name'  => 'Guest',
            'last_name'   => '',
            'phone'       => '',
            'email'       => '',
            'address'     => '',
            'country'     => '',
            'nationality' => '',
        ];

        $g0          = $guest_arr[0] ?? [];
        $profileInfo = $g0['profileInfo'] ?? [];
        $profile     = $profileInfo['profile'] ?? [];
        $customer    = $profile['customer'] ?? [];

        $personName = $customer['personName'] ?? [];
        $pn0        = $personName[0] ?? [];

        $givenName  = $pn0['givenName'] ?? '';
        $surname    = $pn0['surname'] ?? '';
        $nameTitle  = $pn0['nameTitle'] ?? '';

        $title_map = ['Mr.' => 1, 'Mrs.' => 2, 'Ms.' => 3, 'Miss' => 4, 'Dr.' => 5];
        $out['title']      = $title_map[$nameTitle] ?? 6;
        $out['first_name'] = $givenName ?: $surname ?: 'Guest';
        $out['last_name']  = $surname;
        $out['name']       = trim("$givenName $surname");

        $telephones   = $customer['telephones'] ?? $profile['telephones'] ?? [];
        $telInfo      = $telephones['telephoneInfo'] ?? [];
        $tel0         = $telInfo[0] ?? [];
        $tel          = $tel0['telephone'] ?? [];
        $out['phone'] = $tel['phoneNumber'] ?? '';

        $emails        = $customer['emails'] ?? $profile['emails'] ?? [];
        $emailInfo     = $emails['emailInfo'] ?? [];
        $email0        = $emailInfo[0] ?? [];
        $email         = $email0['email'] ?? [];
        $out['email']  = $email['emailAddress'] ?? '';

        $addresses     = $customer['addresses'] ?? $profile['addresses'] ?? [];
        $addrInfo      = $addresses['addressInfo'] ?? [];
        $addr0         = $addrInfo[0] ?? [];
        $addr          = $addr0['address'] ?? [];
        $addressLines  = $addr['addressLine'] ?? [];
        $filteredLines = [];
        foreach ($addressLines as $al) {
            if ($al !== null && $al !== '') {
                $filteredLines[] = $al;
            }
        }
        $out['address'] = implode(', ', $filteredLines);

        $country           = $addr['country'] ?? [];
        $out['country']     = $country['value'] ?? '';
        $out['nationality'] = $country['code'] ?? '';

        return $out;
    }

    private function insertNew($registration, $room_detail, $guest, $rateEntries, $other_info)
    {
        $this->db->trans_start();

        $guest_data = [
            'title'             => $guest['title'],
            'first_name'        => $guest['first_name'],
            'last_name'         => $guest['last_name'],
            'phone'             => $guest['phone'],
            'email'             => $guest['email'],
            'address'           => $guest['address'],
            'country'           => $guest['country'],
            'nationality'       => $guest['nationality'],
            'is_contact_person' => 1,
        ];
        $this->db->insert('fo_guests', $guest_data);
        $guest_id = $this->db->insert_id();

        $reg = $this->db->order_by('id', 'DESC')->get('fo_registrations')->row();
        $reg_num = $reg ? (int) explode('RR', $reg->registration_number)[1] : 0;
        $registration['registration_number'] = 'RR' . sprintf('%08d', $reg_num + 1);
        $registration['contact_person_id'] = $guest_id;

        $this->db->insert('fo_registrations', $registration);
        $registration_id = $this->db->insert_id();

        $this->db->insert('fo_guest_registration_room_no_mapping', [
            'fo_registration_id' => $registration_id,
            'fo_guest_id'        => $guest_id,
            'room_number'        => $room_detail['room_number'],
        ]);

        $other_info['registration_id'] = $registration_id;
        $this->db->insert('fo_registrations_other_info', $other_info);

        $room_detail['fo_registration_id'] = $registration_id;
        $this->db->insert('fo_registration_room_details', $room_detail);
        $room_details_id = $this->db->insert_id();

        $this->insertRoomBills($registration_id, $room_details_id, $room_detail, $guest, $rateEntries);

        $this->db->trans_complete();

        return $this->db->trans_status() ? $registration_id : false;
    }

    private function updateExisting($existing, $registration, $room_detail, $rateEntries)
    {
        $this->db->trans_start();

        unset($registration['registration_number'], $registration['contact_person_id']);
        $this->db->where('id', $existing->id)->update('fo_registrations', $registration);

        $rd = $this->db->where('fo_registration_id', $existing->id)
                       ->where('status !=', 4)
                       ->get('fo_registration_room_details')
                       ->row();

        $room_details_id = null;
        if ($rd) {
            $room_detail['fo_registration_id'] = $existing->id;
            $this->db->where('id', $rd->id)->update('fo_registration_room_details', $room_detail);
            $room_details_id = $rd->id;
        }

        $guest_name = $existing->contact_person ?: ($registration['contact_person'] ?? '');
        $guest_obj  = (object) ['name' => $guest_name];
        $this->insertRoomBills($existing->id, $room_details_id, $room_detail, $guest_obj, $rateEntries);

        $this->db->trans_complete();

        return $this->db->trans_status() ? $existing->id : false;
    }

    private function insertRoomBills($registration_id, $room_details_id, $room_detail, $guest, $rateEntries)
    {
        if (empty($rateEntries) || empty($room_detail['room_number'])) {
            return;
        }

        $room_number = $room_detail['room_number'];
        $guest_name  = $guest['name'] ?? $room_detail['contact_person'] ?? '';

        foreach ($rateEntries as $re) {
            $bill_date = $re['start'] ?? '';

            if (!$bill_date) {
                continue;
            }

            $exists = $this->db->where('date', $bill_date)
                ->where('room', $room_number)
                ->where('registration_id', $registration_id)
                ->get('fo_room_bills')
                ->row();

            if ($exists) {
                continue;
            }

            $rate_amount   = $re['base_amount'] ?? 0;
            $total_amount  = $re['amount'] ?? 0;
            $discount      = $rate_amount > 0 ? $rate_amount - $total_amount : 0;

            $this->db->insert('fo_room_bills', [
                'date'             => $bill_date,
                'room'             => $room_number,
                'registration_id'  => $registration_id,
                'guest_name'       => $guest_name,
                'service'          => 'Room Charge',
                'qty'              => 1,
                'rate'             => $total_amount,
                'discount_amount'  => max($discount, 0),
                'service_charge'   => 0,
                'city_charge'      => 0,
                'vat_charge'       => 0,
                'additional_charge' => 0,
                'total'            => $total_amount,
                'status'           => 0,
                'entry_type'       => 1,
                'hotel_remarks'    => 'Synced from Opera',
            ]);
        }
    }



    private function resolveRoomType($room_number)
    {
        if (!$room_number) {
            return 0;
        }

        $assign = $this->db->select('roomid')
            ->where('roomno', $room_number)
            ->get('tbl_roomnofloorassign')
            ->row();

        return $assign ? (int) $assign->roomid : 0;
    }

    private function nightCount($from, $to)
    {
        $start = new DateTime(date('Y-m-d', strtotime($from)));
        $end   = new DateTime(date('Y-m-d', strtotime($to)));
        return $start->diff($end)->days;
    }

        public function getPendingMultipayBills()
    {
        return $this->db
            ->select("
                mb.multipay_id,
                mb.order_id,
                mb.amount,
                mb.room_no,
                mb.registration_id,
                co.outlet_id,
                fr.opera_id,
                tf.transaction_code
            ")
            ->from('multipay_bill mb')
            ->join('customer_order co', 'co.order_id = mb.order_id')
            ->join('fo_registrations fr', 'fr.id = mb.registration_id')
            ->join('tbl_tablefloor tf', 'tf.tbfloorid = co.outlet_id', 'left')
            ->where('mb.opera_sync', 0)
            ->where('mb.payment_status', 1)
            ->where('fr.opera_id IS NOT NULL')
            ->where('fr.opera_id !=', '')
            ->get()
            ->result();
    }

    public function markMultipaySynced($multipay_id)
    {
        $this->db->where('multipay_id', $multipay_id)->update('multipay_bill', ['opera_sync' => 1]);
    }

    // --------------------------------------------------------------------
    //  Service sync (Opera folio -> PMS service bills)
    // --------------------------------------------------------------------

    public function getRegistrationsForServiceSync()
    {
        return $this->db
            ->select('
                fr.id,
                fr.opera_id,
                fr.registration_number,
                fr.contact_person AS guest_name,
                frd.room_number
            ')
            ->from('fo_registrations fr')
            ->join('fo_registration_room_details frd', 'frd.fo_registration_id = fr.id')
            ->where('fr.opera_id IS NOT NULL')
            // ->where('fr.opera_id', 229891)  // for testing, remove this line in production
            ->where('fr.opera_id !=', '')
            ->where('fr.checkout_status', 0)
            ->where('frd.status', 1)
            ->get()
            ->result();
    }

    public function findFoServiceByTransactionCode($code)
    {
        return $this->db
            ->where('transaction_code', $code)
            ->where('is_deleted', 0)
            ->get('fo_services')
            ->row();
    }

    public function findHousekeepingServiceByTransactionCode($code)
    {
        return $this->db
            ->where('transaction_code', $code)
            ->where('is_deleted', 0)
            ->get('housekeeping_services')
            ->row();
    }

    public function getCurrentFOServiceNumber()
    {
        $this->db->select("MAX(CAST(SUBSTRING(service_no, 3) AS UNSIGNED)) AS max_no", false)
            ->from('fo_service_bills')
            ->where("service_no LIKE 'SB%'");
        $row = $this->db->get()->row();
        $max_no = ($row && $row->max_no !== null) ? (int)$row->max_no : 0;
        return 'SB' . sprintf('%08d', $max_no + 1);
    }

    public function getCurrentHKServiceNumber()
    {
        $this->db->select("MAX(CAST(SUBSTRING(service_no, 3) AS UNSIGNED)) AS max_no", false)
            ->from('housekeeping_service_bills')
            ->where("service_no LIKE 'EB%'");
        $row = $this->db->get()->row();
        $max_no = ($row && $row->max_no !== null) ? (int)$row->max_no : 0;
        return 'EB' . sprintf('%08d', $max_no + 1);
    }

    public function isFolioPostingSynced($registration_id, $txn_no)
    {
        return $this->db
            ->where('reg_no IN (SELECT registration_number FROM fo_registrations WHERE id = ' . (int)$registration_id . ')', null, false)
            ->where('opera_posting_no', $txn_no)
            ->get('fo_service_bills')
            ->row();
    }

    public function createServiceBillFromPosting($registration_id, $room_number, $guest_name, $posting, $fo_service, $hk_service)
    {
        $txn_code  = $posting['transactionCode'] ?? '';
        $txn_no    = $posting['transactionNo'] ?? '';
        $amount    = $posting['postedAmount']['amount'] ?? $posting['transactionAmount'] ?? 0;
        $remark    = $posting['remark'] ?? '';
        $date      = $posting['transactionDate'] ?? $posting['postingDate'] ?? date('Y-m-d');
        $qty       = $posting['postingQuantity'] ?? 1;
        $rate      = $qty > 0 ? round($amount / $qty, 2) : $amount;
        $reg_no    = $this->getRegistrationNumber($registration_id);

        $this->db->trans_start();

        if ($hk_service) {
            $eb_no = $this->getCurrentHKServiceNumber();
            $sb_no = $this->getCurrentFOServiceNumber();

            $this->db->insert('housekeeping_service_bills', [
                'guest_type'       => 1,
                'room_no'          => $room_number,
                'reg_no'           => $reg_no,
                'service_no'       => $eb_no,
                'fo_service_no'    => $sb_no,
                'opera_posting_no' => $txn_no,
                'date'             => $date,
                'status'           => 0,
                'guest_name'       => $guest_name,
                'service_id'       => $hk_service->id,
                'service_rate'     => $rate,
                'service_qty'      => $qty,
                'service_charge'   => $amount,
                'vat_amount'       => 0,
                'grand_total'      => $amount,
                'amount'           => $amount,
                'remarks'          => $remark,
                'created_by'       => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            $this->db->insert('fo_service_bills', [
                'guest_type'       => 1,
                'room_no'          => $room_number,
                'reg_no'           => $reg_no,
                'service_no'       => $sb_no,
                'opera_posting_no' => $txn_no,
                'date'             => $date,
                'status'           => 0,
                'guest_name'       => $guest_name,
                'service_id'       => $hk_service->category_id,
                'service_rate'     => $rate,
                'service_qty'      => $qty,
                'service_charge'   => $amount,
                'vat_amount'       => 0,
                'grand_total'      => $amount,
                'amount'           => $amount,
                'remarks'          => $remark,
                'created_by'       => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            $return_no = $eb_no;
        } elseif ($fo_service) {
            $sb_no = $this->getCurrentFOServiceNumber();

            $this->db->insert('fo_service_bills', [
                'guest_type'       => 1,
                'room_no'          => $room_number,
                'reg_no'           => $reg_no,
                'service_no'       => $sb_no,
                'opera_posting_no' => $txn_no,
                'date'             => $date,
                'status'           => 0,
                'guest_name'       => $guest_name,
                'service_id'       => $fo_service->id,
                'service_rate'     => $rate,
                'service_qty'      => $qty,
                'service_charge'   => $amount,
                'vat_amount'       => 0,
                'grand_total'      => $amount,
                'amount'           => $amount,
                'remarks'          => $remark,
                'created_by'       => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            $return_no = $sb_no;
        } else {
            $this->db->trans_complete();
            return false;
        }

        $this->db->trans_complete();

        return $this->db->trans_status() ? $return_no : false;
    }

    private function getRegistrationNumber($registration_id)
    {
        $row = $this->db->select('registration_number')
            ->where('id', $registration_id)
            ->get('fo_registrations')
            ->row();
        return $row ? $row->registration_number : '';
    }

    // --------------------------------------------------------------------
    //  Reservation sync (Opera -> fo_reservations)
    // --------------------------------------------------------------------

    public function findReservationByOperaId($operaId)
    {
        return $this->db->where('opera_id', $operaId)->get('fo_reservations')->row();
    }

    public function getCurrentReservationNumber()
    {
        $row = $this->db->order_by('reservation_number', 'DESC')->get('fo_reservations')->row();
        $num = 0;
        if ($row) {
            $parts = explode('GR', $row->reservation_number);
            $num = isset($parts[1]) ? (int) $parts[1] : 0;
        }
        return 'GR' . sprintf('%08d', $num + 1);
    }

    public function createReservation($operaId, $item)
    {
        $roomStay   = $item['roomStay'] ?? [];
        $guest      = $item['reservationGuest'] ?? [];
        $arrival    = $roomStay['arrivalDate'] ?? date('Y-m-d');
        $departure  = $roomStay['departureDate'] ?? date('Y-m-d');
        $operaStatus = $item['reservationStatus'] ?? '';

        $guestName = trim(($guest['givenName'] ?? '') . ' ' . ($guest['surname'] ?? ''));

        // Determine our PMS status
        $pmsStatus   = 1;
        $reason      = null;
        if (strtolower($operaStatus) === 'cancelled') {
            $pmsStatus = 3;
            $reason    = 'Synced from Opera — Cancelled';
        }

        // Resolve currency
        $currency = $this->db->where('currencyname', 'BDT')->get('currency')->row();
        $currencyId = $currency ? $currency->currencyid : 1;

        $this->db->trans_start();

        // Insert guest
        $title_map = ['Mr.' => 1, 'Mrs.' => 2, 'Ms.' => 3, 'Miss' => 4, 'Dr.' => 5];
        $nameTitle = $guest['nameTitle'] ?? '';
        $firstName = $guest['givenName'] ?? '';
        $lastName  = $guest['surname'] ?? '';
        $address   = $guest['address']['streetAddress'] ?? '';
        $country   = $guest['address']['country']['code'] ?? '';
        $phone     = $guest['phoneNumber'] ?? '';
        $email     = $guest['email'] ?? '';

        $this->db->insert('fo_guests', [
            'title'             => $title_map[$nameTitle] ?? 6,
            'first_name'        => $firstName ?: $guestName,
            'last_name'         => $lastName,
            'address'           => $address,
            'email'             => $email,
            'phone'             => $phone,
            'country'           => $country,
            'nationality'       => $country,
            'is_contact_person' => 1,
        ]);
        $guestId = $this->db->insert_id();

        $reservation_no = $this->getCurrentReservationNumber();

        $reservation = [
            'opera_id'           => $operaId,
            'reservation_number' => $reservation_no,
            'guest_ids'          => json_encode([$guestId]),
            'checkin_date'       => date('Y-m-d H:i:s', strtotime($arrival)),
            'checkout_date'      => $departure ? date('Y-m-d H:i:s', strtotime($departure)) : null,
            'number_of_night'    => $this->nightCount($arrival, $departure),
            'contact_person'     => $guestName,
            'mobile'             => $phone,
            'email'              => $email,
            'contact_address'    => $address,
            'reservation_status' => $pmsStatus,
            'reason_of_cancel'   => $reason,
            'currency_type'      => $currencyId,
            'created_by'         => '1',
            'updated_by'         => '1',
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('fo_reservations', $reservation);
        $reservation_id = $this->db->insert_id();

        // Insert room details
        $roomTypeName = $roomStay['roomType'] ?? '';
        $roomTypeId   = $this->resolveRoomTypeByName($roomTypeName);
        $adults       = $roomStay['adultCount'] ?? 1;
        $children     = $roomStay['childCount'] ?? 0;
        $rateAmount   = $roomStay['rateAmount']['amount'] ?? 0;

        $this->db->insert('fo_reservations_room_details', [
            'reservation_id'     => $reservation_id,
            'reservation_number' => $reservation_no,
            'checkin_date2'      => $arrival,
            'checkout_date2'     => $departure,
            'number_of_night2'   => $this->nightCount($arrival, $departure),
            'room_type'          => $roomTypeId,
            'rack_rate'          => $rateAmount,
            'negotiated_rate'    => $rateAmount,
            'total_room_rate'    => $rateAmount,
            'room_quantity'      => $roomStay['numberOfRooms'] ?? 1,
            'pax_in'             => $adults,
            'child_in'           => $children,
            'is_global_date'     => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'created_by'         => 1,
        ]);
        $roomDetailsId = $this->db->insert_id();

        // Insert rate plan (one entry per night)
        $nights = $this->nightCount($arrival, $departure);
        for ($i = 0; $i < $nights; $i++) {
            $rateDate = date('Y-m-d', strtotime($arrival . ' +' . $i . ' days'));
            $this->db->insert('fo_reservation_rate_plan', [
                'room_details_id'  => $roomDetailsId,
                'reservation_id'   => $reservation_id,
                'rate_date'        => $rateDate,
                'room_type'        => $roomTypeId,
                'room_number'      => 0,
                'rack_rate'        => $rateAmount,
                'negotiated_rate'  => $rateAmount,
                'total_room_rate'  => $rateAmount,
                'created_at'       => date('Y-m-d H:i:s'),
                'created_by'       => 1,
            ]);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() ? $reservation_id : false;
    }

    public function updateReservation($id, $item)
    {
        $roomStay   = $item['roomStay'] ?? [];
        $guest      = $item['reservationGuest'] ?? [];
        $arrival    = $roomStay['arrivalDate'] ?? '';
        $departure  = $roomStay['departureDate'] ?? '';
        $operaStatus = $item['reservationStatus'] ?? '';

        $guestName = trim(($guest['givenName'] ?? '') . ' ' . ($guest['surname'] ?? ''));

        $update = [
            'checkin_date'   => $arrival ? date('Y-m-d H:i:s', strtotime($arrival)) : null,
            'checkout_date'  => $departure ? date('Y-m-d H:i:s', strtotime($departure)) : null,
            'number_of_night' => $this->nightCount($arrival, $departure),
            'contact_person' => $guestName,
            'mobile'         => $guest['phoneNumber'] ?? '',
            'email'          => $guest['email'] ?? '',
            'updated_by'     => '1',
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if (strtolower($operaStatus) === 'cancelled') {
            $update['reservation_status'] = 3;
            $update['reason_of_cancel']   = 'Synced from Opera — Cancelled';
        } elseif (strtolower($operaStatus) === 'reserved') {
            $update['reservation_status'] = 1;
            $update['reason_of_cancel']   = null;
        }

        $this->db->where('id', $id)->update('fo_reservations', $update);

        return $this->db->affected_rows() > 0;
    }

    public function resolveRoomTypeByName($roomTypeName)
    {
        if (!$roomTypeName) {
            return 0;
        }
        $row = $this->db->select('roomid')
            ->where('opera_code', $roomTypeName)
            ->get('roomdetails')
            ->row();
        return $row ? (int) $row->roomid : 0;
    }
}
