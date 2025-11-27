<?php

namespace App\Services;

class RealDebridService {
    private $baseUrl = 'https://api.real-debrid.com/rest/1.0';
    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    private function request($endpoint, $method = 'GET', $data = []) {
        $ch = curl_init($this->baseUrl . $endpoint);
        
        $headers = [
            'Authorization: Bearer ' . $this->token
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local dev
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // RD uses form-data usually
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // If there's a CURL error (network issue), return error
        if ($response === false) {
            return ['error' => true, 'code' => 0, 'message' => 'Network error: ' . $curlError];
        }

        // If HTTP error code, return the error
        if ($httpCode >= 400) {
            return ['error' => true, 'code' => $httpCode, 'message' => $response];
        }

        return json_decode($response, true);
    }

    public function getUserInfo() {
        return $this->request('/user');
    }

    public function addMagnet($magnet) {
        return $this->request('/torrents/addMagnet', 'POST', ['magnet' => $magnet]);
    }

    public function getTorrentInfo($torrentId) {
        return $this->request('/torrents/info/' . $torrentId);
    }

    public function selectFiles($torrentId, $fileIds) {
        return $this->request('/torrents/selectFiles/' . $torrentId, 'POST', ['files' => $fileIds]);
    }

    public function unrestrictLink($link) {
        return $this->request('/unrestrict/link', 'POST', ['link' => $link]);
    }
}
