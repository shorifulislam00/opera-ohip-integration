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

        $existing      = $this->isSynced($opera_id);
        $room_number   = $roomInfo['roomId'] ?? $roomStay['roomId'] ?? '';
        $arrival       = $roomStay['arrivalDate'] ?? date('Y-m-d');
        $departure     = $roomStay['departureDate'] ?? date('Y-m-d');

        $room_type = $this->resolveRoomTypeByName($roomInfo['roomType'] ?? $roomStay['roomType'] ?? '');

        // echo "<pre>";
        // print_r($roomInfo);
        // print_r($room_type);
        // exit;

        if (!$room_type && $room_number) {
            $room_type = $this->resolveRoomType($room_number);
        }

        // echo "<pre>";
        // print_r($room_type);
        // exit;

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

        // echo "<pre>";
        // print_r($room_detail);
        // exit;

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

    public function syncRegistrationFromList($opera_id, $item)
    {
        $roomStay    = $item['roomStay'] ?? [];
        $guestData   = $item['reservationGuest'] ?? [];
        $arrival     = $roomStay['arrivalDate'] ?? date('Y-m-d');
        $departure   = $roomStay['departureDate'] ?? date('Y-m-d');

        $room_number = $roomStay['roomId'] ?? '';
        $room_type   = $this->resolveRoomTypeByName($roomStay['roomType'] ?? '');
        if (!$room_type && $room_number) {
            $room_type = $this->resolveRoomType($room_number);
        }

        $reservation = $this->findReservationByOperaId($opera_id);

        $title_map = ['Mr.' => 1, 'Mrs.' => 2, 'Ms.' => 3, 'Miss' => 4, 'Dr.' => 5];
        $title   = $title_map[$guestData['nameTitle'] ?? ''] ?? 6;
        $first   = $guestData['givenName'] ?? '';
        $last    = $guestData['surname'] ?? '';
        $name    = trim("$first $last");
        $phone   = $guestData['phoneNumber'] ?? '';
        $email   = $guestData['email'] ?? '';
        $addr    = $guestData['address']['streetAddress'] ?? '';
        $country = $guestData['address']['country']['code'] ?? '';

        // if(!isset($roomStay['ratePlanCode'])){
        //     echo "<pre>";
        //     print_r($item);
        //     exit;
        // }

        $adults   = $roomStay['adultCount'] ?? 1;
        $children = $roomStay['childCount'] ?? 0;
        $rate     = $roomStay['rateAmount']['amount'] ?? 0;
        $is_complementary     = isset($roomStay['ratePlanCode']) && $roomStay['ratePlanCode'] == 'COMP' && $rate == 0 ? 1 : 0;

        $existing = $this->isSynced($opera_id);

        if ($existing) {
            // Update existing registration
            $this->db->where('id', $existing->id)->update('fo_registrations', [
                'checkin_date'    => date('Y-m-d H:i:s', strtotime($arrival)),
                'departure_date'  => date('Y-m-d H:i:s', strtotime($departure)),
                'number_of_night' => $this->nightCount($arrival, $departure),
                'contact_person'  => $name,
                'contact_address' => $addr,
                'mobile_number'   => $phone,
                'person_adult'    => $adults,
                'person_child'    => $children,
                'has_complimentary_guest' => $is_complementary,
            ]);

            $rd = $this->db->where('fo_registration_id', $existing->id)
                           ->get('fo_registration_room_details')
                           ->row();
            if ($rd) {
                $this->db->where('id', $rd->id)->update('fo_registration_room_details', [
                    'room_type'        => $room_type,
                    'room_number'      => $room_number,
                    'checkin_date2'    => $arrival,
                    'checkout_date2'   => $departure,
                    'number_of_night2' => $this->nightCount($arrival, $departure),
                    'pax_in'           => $adults,
                    'child_in'         => $children,
                    'rack_rate'        => $rate,
                    'negotiated_rate'  => $rate,
                    'total_room_rate'  => $rate,
                    'has_complimentary_guest' => $is_complementary,
                ]);
            }

            $registration_id = $existing->id;
        } else {
        $this->db->trans_start();

        $this->db->insert('fo_guests', [
            'title'             => $title,
            'first_name'        => $first ?: $name,
            'last_name'         => $last,
            'phone'             => $phone,
            'email'             => $email,
            'address'           => $addr,
            'country'           => $country,
            'nationality'       => $country,
            'is_contact_person' => 1,
        ]);
        $guest_id = $this->db->insert_id();

        $reg = $this->db->order_by('id', 'DESC')->get('fo_registrations')->row();
        $reg_num = $reg ? (int) explode('RR', $reg->registration_number)[1] : 0;

        $this->db->insert('fo_registrations', [
            'opera_id'            => $opera_id,
            'registration_number' => 'RR' . sprintf('%08d', $reg_num + 1),
            'checkin_date'        => date('Y-m-d H:i:s', strtotime($arrival)),
            'departure_date'      => date('Y-m-d H:i:s', strtotime($departure)),
            'number_of_night'     => $this->nightCount($arrival, $departure),
            'contact_person'      => $name,
            'contact_person_id'   => $guest_id,
            'contact_address'     => $addr,
            'mobile_number'       => $phone,
            'guest_type'          => 1,
            'person_adult'        => $adults,
            'person_child'        => $children,
            'checkout_status'     => 0,
            'reservation_id'      => $reservation ? $reservation->id : null,
            'created_by'          => 1,
            'updated_by'          => 1,
            'checkin_by'          => 1,
            'created_at'          => date('Y-m-d H:i:s'),
            'has_complimentary_guest' => $is_complementary
        ]);
        $registration_id = $this->db->insert_id();

        $this->db->insert('fo_guest_registration_room_no_mapping', [
            'fo_registration_id' => $registration_id,
            'fo_guest_id'        => $guest_id,
            'room_number'        => $room_number,
        ]);

        $this->db->insert('fo_registration_room_details', [
            'fo_registration_id' => $registration_id,
            'room_type'          => $room_type,
            'room_number'        => $room_number,
            'checkin_date2'      => $arrival,
            'checkout_date2'     => $departure,
            'number_of_night2'   => $this->nightCount($arrival, $departure),
            'pax_in'             => $adults,
            'child_in'           => $children,
            'rack_rate'          => $rate,
            'negotiated_rate'    => $rate,
            'total_room_rate'    => $rate,
            'status'             => 1,
            'is_global_date'     => 1,
            'checkin_time'       => date('Y-m-d H:i:s', strtotime($arrival)),
            'checkin_by'         => 1,
            'has_complimentary_guest' => $is_complementary,
        ]);

        $this->db->trans_complete();

        if (!$this->db->trans_status()) return false;

    }

    $this->syncRoomBills($registration_id, $room_number, $room_type, $name, $arrival, $departure, $rate, $is_complementary);

    return $registration_id;
}

public function syncRoomBills($registration_id, $room_number, $room_type, $guest_name, $arrival, $departure, $total_rate, $is_complementary)
{
    $CI =& get_instance();
    $CI->load->model('front_office/Settings_model');
    $settings = $CI->Settings_model->getSetting();

    $sc_percent = floatval($settings->service_charge_for_rooms ?? 0);
    $vat_percent = floatval($settings->vat_for_rooms ?? 0);

    $nights = max($this->nightCount($arrival, $departure), 1);
    $per_night = $total_rate;

    for ($i = 0; $i < $nights; $i++) {
        $bill_date = date('Y-m-d', strtotime($arrival . " +$i days"));

        $exists = $this->db->where('date', $bill_date)
            ->where('room', $room_number)
            ->where('registration_id', $registration_id)
            ->get('fo_room_bills')->row();

        if ($exists) continue;

        $service_charge  = $per_night * ($sc_percent / 100);
        $vat_charge      = ($per_night + $service_charge) * ($vat_percent / 100);
        $total_charge    = $per_night + $service_charge + $vat_charge;

        $this->db->insert('fo_room_bills', [
            'date'             => $bill_date,
            'room'             => $room_number,
            'registration_id'  => $registration_id,
            'guest_name'       => $guest_name,
            'service'          => $room_type . ' (' . $room_number . ')',
            'qty'              => 1,
            'rate'             => $per_night,
            'discount_amount'  => 0,
            'service_charge'   => $service_charge,
            'city_charge'      => 0,
            'vat_charge'       => $vat_charge,
            'additional_charge'=> 0,
            'total'            => $total_charge,
            'status'           => 1,
            'entry_type'       => 1,
            'hotel_remarks'    => 'Synced from Opera',
            'has_complimentary_guest' => $is_complementary
        ]);
    }
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
        $guest_name  = is_array($guest) ? ($guest['name'] ?? '') : ($guest->name ?? '');
        if (!$guest_name) {
            $guest_name = $room_detail['contact_person'] ?? '';
        }

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
        return max($start->diff($end)->days, 1);
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
                mb.payment_type_id,
                mb.bank_name,
                co.outlet_id,
                fr.opera_id,
                tf.transaction_code
            ")
            ->from('multipay_bill mb')
            ->join('customer_order co', 'co.order_id = mb.order_id')
            ->join('fo_registrations fr', 'fr.id = mb.registration_id', 'left')
            ->join('tbl_tablefloor tf', 'tf.tbfloorid = co.outlet_id', 'left')
            ->where('mb.opera_sync', 0)
            ->where('mb.payment_status', 1)
            ->group_start()
                ->group_start()
                    ->where('fr.opera_id IS NOT NULL')
                    ->where('fr.opera_id !=', '')
                ->group_end()
                ->or_group_start()
                    ->where('mb.registration_id IS NULL')
                    ->where('mb.payment_type_id IS NOT NULL')
                    ->where('mb.payment_type_id !=', 8)
                ->group_end()
            ->group_end()
            ->get()
            ->result();
    }

    public function markMultipaySynced($multipay_id)
    {
        $this->db->where('multipay_id', $multipay_id)->update('multipay_bill', ['opera_sync' => 1]);
    }

    public function markRegistrationCheckedOut($registration_id, $checkout_date = null)
    {
        if (!$checkout_date) {
            $checkout_date = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $registration_id)->update('fo_registrations', [
            'checkout_status' => 1,
            'departure_date'   => $checkout_date,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->db->where('fo_registration_id', $registration_id)->update('fo_registration_room_details', [
            'status'        => 4,  // checked out
            'checkout_time' => $checkout_date,
        ]);
    }

    public function resolvePaymentMethodToOpera($pmsPaymentTypeId)
    {
        $row = $this->db
            ->where('payment_method_id', $pmsPaymentTypeId)
            ->get('payment_method')
            ->row();

        if (!$row || !$row->opera_code) {
            return null;
        }

        return (object) [
            'opera_payment_code' => $row->opera_code,
            'opera_action'       => 'Billing',
        ];
    }

    public function resolveMbankingFromAutomation($bankName)
    {
        if (!$bankName) {
            return null;
        }
        $row = $this->db->select('m_banking_head_code_name')->get('acc_automation')->row();
        if (!$row || !$row->m_banking_head_code_name) {
            return null;
        }
        $decoded = json_decode($row->m_banking_head_code_name, true);
        $items = $decoded['m_banking'] ?? [];
        $matchedName = '';
        foreach ($items as $item) {
            if (($item['head_code'] ?? '') === $bankName) {
                $matchedName = $item['head_name'] ?? '';
                break;
            }
        }
        if (!$matchedName) {
            return null;
        }
        $nameLower = strtolower($matchedName);
        $code = 'UP';
        if (strpos($nameLower, 'nagad') !== false) {
            $code = 'NGB';
        } elseif (strpos($nameLower, 'bkash') !== false) {
            $code = 'UP';
        }
        return (object) [
            'opera_payment_code' => $code,
            'opera_action'       => 'Billing',
        ];
    }

    public function getPmReservationId()
    {
        $row = $this->db->select('pm_reservation_id')->get('sys_opera_config')->row();
        return $row ? $row->pm_reservation_id : null;
    }

    public function savePmReservationConfig($confirmationNo, $reservationId)
    {
        $this->db->update('sys_opera_config', [
            'pm_reservation_confirmation' => $confirmationNo,
            'pm_reservation_id'           => $reservationId,
        ]);
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
            // ->where('fr.checkout_status', 0)
            // ->where('frd.status', 1)
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

        $CI =& get_instance();
        $CI->load->model('front_office/Settings_model');
        $settings = $CI->Settings_model->getSetting();
        $sc_percent  = floatval($settings->service_charge_for_rooms ?? 0);
        $vat_percent = floatval($settings->vat_for_rooms ?? 0);

        $sc_value   = $amount * ($sc_percent / 100);
        $vat_amount = ($amount + $sc_value) * ($vat_percent / 100);
        $grand_total = $amount + $sc_value + $vat_amount;

        $bill_data = [
            'guest_type'       => 1,
            'room_no'          => $room_number,
            'reg_no'           => $reg_no,
            'opera_posting_no' => $txn_no,
            'date'             => $date,
            'status'           => 0,
            'guest_name'       => $guest_name,
            'service_rate'     => $rate,
            'service_qty'      => $qty,
            'service_charge'   => $sc_value,
            'vat_amount'       => $vat_amount,
            'grand_total'      => $grand_total,
            'amount'           => $amount,
            'remarks'          => $remark,
            'created_by'       => 1,
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $this->db->trans_start();

        if ($hk_service) {
            $existing = $this->db->where('opera_posting_no', $txn_no)->get('fo_service_bills')->row();

            if ($existing) {
                $eb_no = $existing->service_no;
                $sb_no = $existing->service_no;
                $this->db->where('opera_posting_no', $txn_no)->update('fo_service_bills', $bill_data);
                $this->db->where('opera_posting_no', $txn_no)->update('housekeeping_service_bills', $bill_data);
            } else {
                $eb_no = $this->getCurrentHKServiceNumber();
                $sb_no = $this->getCurrentFOServiceNumber();

                $hk_data = $bill_data;
                $hk_data['service_no']    = $eb_no;
                $hk_data['fo_service_no'] = $sb_no;
                $hk_data['service_id']    = $hk_service->id;
                $this->db->insert('housekeeping_service_bills', $hk_data);

                $fo_data = $bill_data;
                $fo_data['service_no']    = $sb_no;
                $fo_data['service_id']    = $hk_service->category_id;
                $this->db->insert('fo_service_bills', $fo_data);
            }

            $return_no = $eb_no;
        } elseif ($fo_service) {
            $existing = $this->db->where('opera_posting_no', $txn_no)->get('fo_service_bills')->row();

            if ($existing) {
                $sb_no = $existing->service_no;
                $this->db->where('opera_posting_no', $txn_no)->update('fo_service_bills', $bill_data);
            } else {
                $sb_no = $this->getCurrentFOServiceNumber();

                $fo_data = $bill_data;
                $fo_data['service_no'] = $sb_no;
                $fo_data['service_id'] = $fo_service->id;
                $this->db->insert('fo_service_bills', $fo_data);
            }

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
        $reservation_status   = 1;
        $registration_status   = 0;
        $reason      = null;

        if (strtolower($operaStatus) === 'cancelled') {
            $reservation_status = 3;
            $reason    = 'Synced from Opera — Cancelled';
        }

        if (strtolower($operaStatus) === 'inhouse' || strtolower($operaStatus) === 'checkedin' || strtolower($operaStatus) === 'checkedout') {
            $registration_status = 1;
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
            'reservation_status' => $reservation_status,
            'registration_status' => $registration_status,
            'reason_of_cancel'   => $reason,
            'currency_type'      => $currencyId,
            'created_by'         => '1',
            'updated_by'         => '1',
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('fo_reservations', $reservation);
        $reservation_id = $this->db->insert_id();

        // Insert room details
        $roomInfo      = $roomStay['currentRoomInfo'] ?? [];
        $roomNumber    = $roomInfo['roomId'] ?? $roomStay['roomId'] ?? '';
        $roomTypeName = $roomStay['roomType'] ?? $roomInfo['roomType'] ?? '';
        $roomTypeId   = $this->resolveRoomTypeByName($roomTypeName);
        $guestCounts  = $roomStay['guestCounts'] ?? [];
        $adults       = $roomStay['adultCount'] ?? $guestCounts['adults'] ?? 1;
        $children     = $roomStay['childCount'] ?? $guestCounts['children'] ?? 0;
        $rateAmount   = $roomStay['rateAmount']['amount'] ?? $roomStay['total']['amountBeforeTax'] ?? 0;

        $this->db->insert('fo_reservations_room_details', [
            'reservation_id'     => $reservation_id,
            'reservation_number' => $reservation_no,
            'room_number'        => $roomNumber ?? null,
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
            'room_registration_status' => $reservation_status === 3 ? 3 : $registration_status,
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
                'room_number'      => $roomNumber ?? null,
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

        // echo $operaStatus . "<br/>";

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
        } else if(strtolower($operaStatus) === 'inhouse') {
            $update['reservation_status'] = 1;
            $update['registration_status'] = 1;
            $update['reason_of_cancel']   = null;
        } else if(strtolower($operaStatus) === 'checkedout') {
            $update['registration_status'] = 1;
            $update['reason_of_cancel']   = null;
        }

        $this->db->where('id', $id)->update('fo_reservations', $update);

        // Update room details
        $roomInfo      = $roomStay['currentRoomInfo'] ?? [];
        $roomTypeName = $roomStay['roomType'] ?? $roomInfo['roomType'] ?? '';
        $roomTypeId   = $this->resolveRoomTypeByName($roomTypeName);
        $guestCounts  = $roomStay['guestCounts'] ?? [];
        $adults       = $roomStay['adultCount'] ?? $guestCounts['adults'] ?? 1;
        $children     = $roomStay['childCount'] ?? $guestCounts['children'] ?? 0;
        $rateAmount   = $roomStay['rateAmount']['amount'] ?? $roomStay['total']['amountBeforeTax'] ?? 0;

        $rd = $this->db->where('reservation_id', $id)->get('fo_reservations_room_details')->row();
        if ($rd) {
            $roomNumber = $roomInfo['roomId'] ?? $roomStay['roomId'] ?? '';
            $this->db->where('id', $rd->id)->update('fo_reservations_room_details', [
                'room_number'        => $roomNumber,
                'checkin_date2'      => $arrival,
                'checkout_date2'     => $departure,
                'number_of_night2'   => $this->nightCount($arrival, $departure),
                'room_type'          => $roomTypeId,
                'rack_rate'          => $rateAmount,
                'negotiated_rate'    => $rateAmount,
                'total_room_rate'    => $rateAmount,
                'pax_in'             => $adults,
                'child_in'           => $children,
                'room_registration_status' => (strtolower($operaStatus) === 'inhouse' || strtolower($operaStatus) === 'checkedout') ? 1 : (strtolower($operaStatus) === 'cancelled' ? 3 : 0),
            ]);
        }

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

        // echo "<pre>";
        // print_r($row);
        // print_r($this->db->last_query());
        // exit;

        return $row ? (int) $row->roomid : 0;
    }
}
