<?php

namespace App\Controllers;

use App\Services\TorrentioService;
use App\Services\TMDBService;
use App\Models\Settings;

class PlayerController {
    
    /**
     * API endpoint to fetch streams via backend (avoids CORS issues)
     */
    public function getStreams() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $imdbId = $_GET['id'] ?? $_POST['id'] ?? '';
        $type = $_GET['type'] ?? $_POST['type'] ?? 'movie';
        $season = isset($_GET['season']) ? (int)$_GET['season'] : (isset($_POST['season']) ? (int)$_POST['season'] : null);
        $episode = isset($_GET['episode']) ? (int)$_GET['episode'] : (isset($_POST['episode']) ? (int)$_POST['episode'] : null);
        
        if (empty($imdbId)) {
            http_response_code(400);
            echo json_encode(['error' => 'IMDB ID required']);
            exit;
        }
        
        // Get user settings for RD token
        $settingsModel = new Settings();
        if (isset($_SESSION['access_token'])) {
            $settingsModel->setToken($_SESSION['access_token']);
        }
        $settings = $settingsModel->getByUserId($_SESSION['user_id']);
        $rdToken = $settings['rd_token'] ?? null;
        $qualityPref = $settings['quality_pref'] ?? null;
        
        // Fetch streams via backend (no CORS issues)
        $torrentio = new TorrentioService($rdToken, $qualityPref);
        $streamsData = $torrentio->getStreams($type, $imdbId, $season, $episode);
        $streams = $streamsData['streams'] ?? [];
        
        echo json_encode([
            'success' => true,
            'streams' => $streams,
            'count' => count($streams),
            'rdEnabled' => !empty($rdToken)
        ]);
        exit;
    }
    
    public function watch() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $imdbId = $_GET['id'] ?? '';
        $type = $_GET['type'] ?? 'movie';
        $season = isset($_GET['season']) ? (int)$_GET['season'] : null;
        $episode = isset($_GET['episode']) ? (int)$_GET['episode'] : null;
        $resumeTime = isset($_GET['t']) ? (int)$_GET['t'] : null; // Resume time in seconds
        $streamHash = $_GET['sh'] ?? null; // Stream hash for resuming with same source

        if (empty($imdbId)) {
            header('Location: /');
            exit;
        }

        // For series, require season and episode
        if ($type === 'series' && ($season === null || $episode === null)) {
            header("Location: /details?id=$imdbId&type=series");
            exit;
        }

        // Get metadata for title display using Cinemeta API
        $torrentioMeta = new TorrentioService();
        if ($type === 'series') {
            $metaData = $torrentioMeta->getSeriesInfo($imdbId);
        } else {
            $metaData = $torrentioMeta->getMovieInfo($imdbId);
        }
        $meta = $metaData['meta'] ?? null;

        // Convert IMDB ID to TMDB ID for Vidking player
        $tmdbId = null;
        $tmdbService = new TMDBService();
        if ($tmdbService->isConfigured()) {
            $findResult = $tmdbService->findByImdbId($imdbId);
            if ($type === 'series') {
                $tmdbId = $findResult['tv_results'][0]['id'] ?? null;
            } else {
                $tmdbId = $findResult['movie_results'][0]['id'] ?? null;
            }
            error_log("[Player] IMDB->TMDB conversion: $imdbId -> $tmdbId");
        } else {
            error_log("[Player] TMDB not configured, cannot convert IMDB to TMDB ID");
        }

        // Use Vidking player (iframe-based, no need for streams/RD)
        $view = __DIR__ . '/../Views/player_vidking.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
