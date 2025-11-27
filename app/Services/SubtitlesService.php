<?php

namespace App\Services;

class SubtitlesService {
    private $baseUrl = 'https://opensubtitlesv3-pro.dexter21767.com/eyJsYW5ncyI6WyJwb3J0dWd1ZXNlLWJyIl0sInNvdXJjZSI6ImFsbCIsImFpVHJhbnNsYXRlZCI6dHJ1ZSwiYXV0b0FkanVzdG1lbnQiOnRydWV9';

    /**
     * Fast HTTP request using cURL with timeout
     */
    private function httpGet($url, $timeout = 3) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: CineVision/1.0'
            ]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }

    public function getSubtitles($type, $imdbId, $season = null, $episode = null) {
        $id = $imdbId;
        if ($type === 'series' && $season !== null && $episode !== null) {
            $id = "{$imdbId}:{$season}:{$episode}";
        }

        $url = $this->baseUrl . "/subtitles/$type/$id.json";
        
        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }
        $data = json_decode($response, true);
        $subtitles = $data['subtitles'] ?? [];
        
        // Reescrever URLs para usar o proxy local (evita CORS)
        foreach ($subtitles as &$subtitle) {
            if (!empty($subtitle['url'])) {
                $subtitle['url'] = '/api/subtitles/proxy?url=' . urlencode($subtitle['url']);
            }
        }
        
        return $subtitles;
    }
}
