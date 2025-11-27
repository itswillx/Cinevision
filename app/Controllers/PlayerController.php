<?php

namespace App\Controllers;

use App\Services\TorrentioService;
use App\Services\SubtitlesService;
use App\Services\RealDebridService;
use App\Models\Settings;

class PlayerController {
    
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

        // Get User Settings first (needed for streams filtering)
        $settingsModel = new Settings();
        if (isset($_SESSION['access_token'])) {
            $settingsModel->setToken($_SESSION['access_token']);
        }
        $settings = $settingsModel->getByUserId($_SESSION['user_id']);
        $rdEnabled = !empty($settings['rd_token']);
        $rdToken = $settings['rd_token'] ?? null;
        $qualityPref = $settings['quality_pref'] ?? null;

        // Get Streams with user preferences (RD token for better results, quality filter)
        $torrentio = new TorrentioService($rdToken, $qualityPref);
        $streamsData = $torrentio->getStreams($type, $imdbId, $season, $episode);
        $streams = $streamsData['streams'] ?? [];

        // Get metadata for title display (no need for RD token here)
        $torrentioMeta = new TorrentioService();
        if ($type === 'series') {
            $metaData = $torrentioMeta->getSeriesInfo($imdbId);
        } else {
            $metaData = $torrentioMeta->getMovieInfo($imdbId);
        }
        $meta = $metaData['meta'] ?? null;

        // Get Subtitles
        $subtitlesService = new SubtitlesService();
        $subtitles = $subtitlesService->getSubtitles($type, $imdbId, $season, $episode);

        $view = __DIR__ . '/../Views/player.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
