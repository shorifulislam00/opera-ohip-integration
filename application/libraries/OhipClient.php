<?php
defined('BASEPATH') or exit('No direct script access allowed');
class OhipClient {

    protected $token;
    protected $base_url;
    protected $hotel_id;
    protected $app_key;
    protected $cashier_id;
    protected $config;

    public function __construct() {
        $CI =& get_instance();
        $CI->load->database();

        // Load config from DB
        $row = $CI->db->get('sys_opera_config')->row();

        if (!$row) {
            throw new Exception('Opera config not found in sys_opera_config');
        }

        $this->config     = $row;
        $this->base_url   = $row->hostname;
        $this->hotel_id   = $row->hotel_id;
        $this->app_key    = $row->app_key;
        $this->cashier_id = $row->cashier_id;

        $this->token = $this->getToken($row);
    }

    private function getToken($config) {
        $CI =& get_instance();

        // Check cached token
        $cached = $CI->db->get('ohip_tokens')->row();
        if ($cached && time() < ($cached->expires_at - 60)) {
            return $cached->access_token;
        }

        // Fetch new token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $config->hostname . '/oauth/v1/tokens',
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'x-app-key: ' . $config->app_key,
                'enterpriseId: ' . $config->enterprise_id,
                'Authorization: Basic ' . base64_encode($config->client_id . ':' . $config->client_secret),
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'scope'      => 'urn:opc:hgbu:ws:__myscopes__',
            ]),
        ]);

        $response = json_decode(curl_exec($ch), true);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200 || empty($response['access_token'])) {
            throw new Exception('OHIP token fetch failed: ' . json_encode($response));
        }

        // Cache token — truncate and insert
        $CI->db->truncate('ohip_tokens');
        $CI->db->insert('ohip_tokens', [
            'access_token' => $response['access_token'],
            'expires_at'   => time() + $response['expires_in'],
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return $response['access_token'];
    }

    protected function request($method, $path, $body = null) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->base_url . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'x-app-key: ' . $this->app_key,
                'x-hotelId: ' . $this->hotel_id,
                'Content-Type: application/json',
                'x-Request-Id: ' . $this->uuid(),
            ],
            CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $status,
            'body'   => json_decode($response, true),
        ];
    }

    public function getCashierId() {
        return $this->cashier_id;
    }

    private function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}