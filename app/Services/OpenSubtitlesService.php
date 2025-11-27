<?php

namespace App\Services;

class OpenSubtitlesService {
    // Stremio addon for OpenSubtitles
    private $baseUrl = 'https://opensubtitlesv3-pro.dexter21767.com';
    private $config = 'eyJsYW5ncyI6WyJwb3J0dWd1ZXNlLWJyIl0sInNvdXJjZSI6InRydXN0ZWQiLCJhaVRyYW5zbGF0ZWQiOnRydWUsImF1dG9BZGp1c3RtZW50Ijp0cnVlfQ==';
    
    public function searchSubtitles($imdbId, $title = '', $type = 'movie', $season = null, $episode = null) {
        // Clean IMDB ID
        $imdbId = preg_replace('/^tt/', '', $imdbId);
        
        // Stremio addon format: 
        // Movies: /subtitles/movie/tt{imdbId}.json
        // Series: /subtitles/series/tt{imdbId}:{season}:{episode}.json
        $videoId = 'tt' . $imdbId;
        if ($type === 'series' && $season && $episode) {
            $videoId .= ':' . $season . ':' . $episode;
        }
        
        $url = $this->baseUrl . '/' . $this->config . '/subtitles/' . $type . '/' . $videoId . '.json';
        
        error_log("[SUBTITLES] Searching: $url");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        error_log("[SUBTITLES] Response code: $httpCode, error: $error");
        
        if ($errno || $error) {
            error_log("[SUBTITLES] cURL error ($errno): $error");
            return ['error' => 'Network error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            error_log("[SUBTITLES] HTTP error: $httpCode - Response: " . substr($response, 0, 200));
            // Retornar array vazio em vez de erro para não quebrar o player
            return [];
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            error_log("[SUBTITLES] Invalid JSON: " . substr($response, 0, 200));
            return [];
        }
        
        error_log("[SUBTITLES] Found " . count($data['subtitles'] ?? []) . " subtitles");
        
        return $this->formatSubtitles($data);
    }
    
    private function formatSubtitles($data) {
        $subtitles = [];
        
        // Stremio addon format: { subtitles: [{id, url, lang, ...}] }
        $items = $data['subtitles'] ?? [];
        
        // Limit to 10 subtitles
        $items = array_slice($items, 0, 10);
        
        foreach ($items as $item) {
            $lang = $item['lang'] ?? 'unknown';
            
            $subtitle = [
                'id' => $item['id'] ?? uniqid(),
                'title' => $item['SubFileName'] ?? $item['id'] ?? 'Legenda',
                'language' => $lang,
                'language_name' => $this->getLanguageName($lang),
                'url' => $item['url'] ?? '',
                'downloads' => $item['SubDownloadsCnt'] ?? 0,
                'rating' => $item['SubRating'] ?? 0,
                'format' => 'srt'
            ];
            
            if (!empty($subtitle['url'])) {
                $subtitles[] = $subtitle;
            }
        }
        
        return $subtitles;
    }
    
    private function getLanguageName($code) {
        $languages = [
            'por' => 'Português (BR)',
            'pob' => 'Português (BR)',
            'pt' => 'Português (PT)',
            'eng' => 'English',
            'en' => 'English',
            'spa' => 'Español',
            'es' => 'Español',
            'fre' => 'Français',
            'fr' => 'Français',
            'ger' => 'Deutsch',
            'de' => 'Deutsch',
            'ita' => 'Italiano',
            'it' => 'Italiano',
            'dut' => 'Nederlands',
            'nl' => 'Nederlands',
            'pol' => 'Polski',
            'pl' => 'Polski',
            'rus' => 'Русский',
            'ru' => 'Русский'
        ];
        
        return $languages[$code] ?? strtoupper($code);
    }
}
