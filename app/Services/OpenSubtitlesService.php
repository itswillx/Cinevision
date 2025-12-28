<?php

namespace App\Services;

class OpenSubtitlesService {
    private $apiKey;
    private $baseUrl = 'https://api.opensubtitles.com/api/v1';
    
    public function __construct() {
        $this->apiKey = $this->getApiKeyFromConfig();
    }
    
    private function getApiKeyFromConfig() {
        $configFile = __DIR__ . '/../../config/env.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            return $config['OPENSUBTITLES_API_KEY'] ?? '';
        }
        return '';
    }
    
    public function searchSubtitles($imdbId, $title = '', $type = 'movie', $season = null, $episode = null) {
        if (empty($this->apiKey)) {
            error_log("[SUBTITLES] API key not configured");
            return ['error' => 'API key not configured'];
        }
        
        // Clean IMDB ID (ensure it has 'tt' prefix)
        $imdbId = preg_replace('/^tt/', '', $imdbId);
        $imdbId = 'tt' . $imdbId;
        
        // Build query parameters
        $params = [
            'imdb_id' => $imdbId,
            'languages' => 'pt-br,en',
            'order_by' => 'download_count',
            'order_direction' => 'desc'
        ];
        
        // Add season/episode for series
        if ($type === 'series' && $season && $episode) {
            $params['season_number'] = $season;
            $params['episode_number'] = $episode;
        }
        
        $url = $this->baseUrl . '/subtitles?' . http_build_query($params);
        
        error_log("[SUBTITLES] Searching: $url");
        
        $response = $this->makeRequest($url);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return $this->formatSubtitles($response);
    }
    
    private function makeRequest($url, $method = 'GET', $body = null) {
        $ch = curl_init();
        
        $headers = [
            'Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: CineVision v1.0'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        if ($method === 'POST' && $body) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("[SUBTITLES] Response code: $httpCode");
        
        if ($error) {
            error_log("[SUBTITLES] cURL error: $error");
            return ['error' => 'Network error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            error_log("[SUBTITLES] HTTP error: $httpCode - Response: " . substr($response, 0, 200));
            return ['error' => "HTTP error: $httpCode"];
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            return ['error' => 'Invalid JSON response'];
        }
        
        return $data;
    }
    
    public function getDownloadUrl($fileId) {
        $url = $this->baseUrl . '/download';
        
        $response = $this->makeRequest($url, 'POST', [
            'file_id' => $fileId
        ]);
        
        if (isset($response['error'])) {
            return null;
        }
        
        return $response['link'] ?? null;
    }
    
    private function formatSubtitles($data) {
        $subtitles = [];
        $items = $data['data'] ?? [];
        
        // Limit to 15 subtitles
        $items = array_slice($items, 0, 15);
        
        foreach ($items as $item) {
            $attributes = $item['attributes'] ?? [];
            $files = $attributes['files'] ?? [];
            
            if (empty($files)) continue;
            
            $file = $files[0]; // Get first file
            $lang = $attributes['language'] ?? 'unknown';
            
            // Get download URL
            $downloadUrl = $this->getDownloadUrl($file['file_id']);
            
            if (!$downloadUrl) continue;
            
            $subtitle = [
                'id' => $item['id'] ?? uniqid(),
                'title' => $attributes['release'] ?? $attributes['feature_details']['title'] ?? 'Legenda',
                'language' => $lang,
                'language_name' => $this->getLanguageName($lang),
                'url' => $downloadUrl,
                'downloads' => $attributes['download_count'] ?? 0,
                'rating' => $attributes['ratings'] ?? 0,
                'format' => $file['file_name'] ? pathinfo($file['file_name'], PATHINFO_EXTENSION) : 'srt',
                'uploader' => $attributes['uploader']['name'] ?? 'Unknown'
            ];
            
            $subtitles[] = $subtitle;
        }
        
        return $subtitles;
    }
    
    private function getLanguageName($code) {
        $languages = [
            'pt-br' => 'Português (BR)',
            'pt-pt' => 'Português (PT)',
            'por' => 'Português (BR)',
            'pob' => 'Português (BR)',
            'en' => 'English',
            'eng' => 'English',
            'es' => 'Español',
            'spa' => 'Español',
            'fr' => 'Français',
            'fre' => 'Français',
            'de' => 'Deutsch',
            'ger' => 'Deutsch',
            'it' => 'Italiano',
            'ita' => 'Italiano',
            'nl' => 'Nederlands',
            'dut' => 'Nederlands',
            'pl' => 'Polski',
            'pol' => 'Polski',
            'ru' => 'Русский',
            'rus' => 'Русский'
        ];
        
        return $languages[$code] ?? strtoupper($code);
    }
}
