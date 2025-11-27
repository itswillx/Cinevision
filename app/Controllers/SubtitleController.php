<?php

namespace App\Controllers;

use App\Services\OpenSubtitlesService;

class SubtitleController {
    private $subtitleService;
    
    public function __construct() {
        $this->subtitleService = new OpenSubtitlesService();
    }
    
    /**
     * Search subtitles for a movie/series
     */
    public function search() {
        // Garantir que sempre retorne JSON válido
        ob_start();
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        try {
            // Allow subtitle access without auth for ngrok tunnel usage
            $isNgrokRequest = strpos($_SERVER['HTTP_HOST'] ?? '', 'ngrok') !== false;
            
            if (!$isNgrokRequest && !isset($_SESSION['user_id'])) {
                ob_end_clean();
                http_response_code(401);
                echo json_encode(['error' => true, 'message' => 'Unauthorized']);
                exit;
            }
            
            // Get data from request body (form-urlencoded or JSON)
            $jsonInput = file_get_contents('php://input');
            $data = json_decode($jsonInput, true) ?: [];

            $imdbId = $data['imdb_id'] ?? $_POST['imdb_id'] ?? $_GET['imdb_id'] ?? '';
            $title = $data['title'] ?? $_POST['title'] ?? $_GET['title'] ?? '';
            $type = $data['type'] ?? $_POST['type'] ?? $_GET['type'] ?? 'movie';
            $season = $data['season'] ?? $_POST['season'] ?? $_GET['season'] ?? null;
            $episode = $data['episode'] ?? $_POST['episode'] ?? $_GET['episode'] ?? null;
            
            if (empty($imdbId)) {
                ob_end_clean();
                echo json_encode(['error' => true, 'message' => 'IMDB ID required']);
                exit;
            }
            
            // Clean IMDB ID (remove 'tt' prefix if present)
            $imdbId = preg_replace('/^tt/', '', $imdbId);
            
            $subtitles = $this->subtitleService->searchSubtitles($imdbId, $title, $type, $season, $episode);
            
            if (isset($subtitles['error'])) {
                ob_end_clean();
                echo json_encode(['error' => true, 'message' => $subtitles['error'], 'subtitles' => []]);
                exit;
            }
            
            // Update subtitle URLs to use our proxy endpoint
            foreach ($subtitles as &$subtitle) {
                $subtitle['url'] = '/api/subtitles/proxy?url=' . urlencode($subtitle['url']);
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'subtitles' => $subtitles]);
        } catch (\Exception $e) {
            ob_end_clean();
            error_log("[SUBTITLES] Exception: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => 'Exception: ' . $e->getMessage(), 'subtitles' => []]);
        } catch (\Error $e) {
            ob_end_clean();
            error_log("[SUBTITLES] Error: " . $e->getMessage());
            echo json_encode(['error' => true, 'message' => 'Error: ' . $e->getMessage(), 'subtitles' => []]);
        }
        exit;
    }
    
    /**
     * Proxy endpoint for subtitle files to handle CORS
     */
    public function proxy() {
        // Headers CORS - permitir qualquer origem
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo 'Unauthorized';
            exit;
        }
        
        $url = $_GET['url'] ?? '';
        if (empty($url)) {
            http_response_code(400);
            echo 'URL required';
            exit;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($content === false || $error) {
            error_log("Subtitle proxy error: $error");
            http_response_code(500);
            echo "Error: $error";
            exit;
        }
        
        if ($httpCode !== 200) {
            error_log("Subtitle proxy HTTP error: $httpCode");
            http_response_code($httpCode);
            exit;
        }
        
        // Set appropriate headers for subtitle file
        header('Content-Type: text/vtt');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Convert SRT to VTT if needed
        if (strpos($content, '0') !== false && strpos($content, '00:00:00') !== false) {
            $content = $this->convertSrtToVtt($content);
        }
        
        echo $content;
        exit;
    }
    
    private function convertSrtToVtt($srt) {
        // Simple SRT to VTT conversion
        $vtt = "WEBVTT\n\n";
        $vtt .= str_replace(',', '.', $srt);
        return $vtt;
    }
}
