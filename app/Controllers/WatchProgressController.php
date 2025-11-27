<?php

namespace App\Controllers;

use App\Models\WatchProgress;

class WatchProgressController {
    
    /**
     * Salva o progresso de visualização
     * POST /api/progress/save
     */
    public function save() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No access token']);
            return;
        }

        // Aceitar tanto JSON quanto form data
        $input = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = json_decode(file_get_contents('php://input'), true);
            if ($jsonInput) {
                $input = $jsonInput;
            }
        }

        // Extrair valores
        $currentTime = (float)($input['current_time'] ?? $input['currentTime'] ?? 0);
        $duration = (float)($input['duration'] ?? 0);
        
        // Calcular percent se não foi enviado
        $percent = (int)($input['percent'] ?? 0);
        if ($percent === 0 && $duration > 0 && $currentTime > 0) {
            $percent = (int)round(($currentTime / $duration) * 100);
        }
        
        $data = [
            'imdb_id' => $input['imdb_id'] ?? '',
            'type' => $input['type'] ?? 'movie',
            'title' => $input['title'] ?? '',
            'poster' => $input['poster'] ?? '',
            'year' => $input['year'] ?? '',
            'season' => $input['season'] ?? null,
            'episode' => $input['episode'] ?? null,
            'current_time' => $currentTime,
            'duration' => $duration,
            'percent' => $percent,
            'completed' => ($input['completed'] ?? 'false') === 'true' || $input['completed'] === true,
            // Stream info for resuming with the same source
            'stream_index' => isset($input['stream_index']) ? (int)$input['stream_index'] : (isset($input['streamIndex']) ? (int)$input['streamIndex'] : 0),
            'stream_infohash' => $input['stream_infohash'] ?? $input['streamInfohash'] ?? null,
            'stream_url' => $input['stream_url'] ?? $input['streamUrl'] ?? null,
            'stream_title' => $input['stream_title'] ?? $input['streamTitle'] ?? null
        ];

        if (empty($data['imdb_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing imdb_id']);
            return;
        }

        error_log("WatchProgressController::save - Data: " . json_encode($data));

        try {
            $progressModel = new WatchProgress();
            $progressModel->setToken($_SESSION['access_token']);
            
            $result = $progressModel->saveProgress($_SESSION['user_id'], $data);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save progress']);
            }
        } catch (\Exception $e) {
            error_log("WatchProgressController::save - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtém o progresso de um conteúdo específico
     * GET /api/progress/get?imdb_id=...&season=...&episode=...
     */
    public function get() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No access token']);
            return;
        }

        $imdbId = $_GET['imdb_id'] ?? '';
        $season = isset($_GET['season']) && $_GET['season'] !== '' ? (int)$_GET['season'] : null;
        $episode = isset($_GET['episode']) && $_GET['episode'] !== '' ? (int)$_GET['episode'] : null;

        if (empty($imdbId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing imdb_id']);
            return;
        }

        try {
            $progressModel = new WatchProgress();
            $progressModel->setToken($_SESSION['access_token']);
            
            $progress = $progressModel->getProgress($_SESSION['user_id'], $imdbId, $season, $episode);
            
            echo json_encode(['progress' => $progress]);
        } catch (\Exception $e) {
            error_log("WatchProgressController::get - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Lista conteúdos para "Continuar Assistindo"
     * GET /api/progress/continue-watching
     */
    public function continueWatching() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No access token']);
            return;
        }

        try {
            $progressModel = new WatchProgress();
            $progressModel->setToken($_SESSION['access_token']);
            
            $items = $progressModel->getContinueWatching($_SESSION['user_id']);
            
            echo json_encode(['items' => array_values($items)]);
        } catch (\Exception $e) {
            error_log("WatchProgressController::continueWatching - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Sincroniza todo o progresso do usuário
     * GET /api/progress/sync
     */
    public function sync() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No access token']);
            return;
        }

        try {
            $progressModel = new WatchProgress();
            $progressModel->setToken($_SESSION['access_token']);
            
            $allProgress = $progressModel->getAllProgress($_SESSION['user_id']);
            
            // Converter para formato indexado por imdb_id para fácil acesso no frontend
            $indexed = [];
            foreach ($allProgress as $item) {
                $key = $item['imdb_id'];
                // Para séries, incluir season/episode no key
                if ($item['type'] === 'series' && $item['season'] && $item['episode']) {
                    $key .= "_s{$item['season']}e{$item['episode']}";
                }
                $indexed[$key] = $item;
            }
            
            echo json_encode(['progress' => $indexed]);
        } catch (\Exception $e) {
            error_log("WatchProgressController::sync - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove o progresso de um conteúdo
     * POST /api/progress/remove
     */
    public function remove() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No access token']);
            return;
        }

        $imdbId = $_POST['imdb_id'] ?? '';
        $season = isset($_POST['season']) ? (int)$_POST['season'] : 0;
        $episode = isset($_POST['episode']) ? (int)$_POST['episode'] : 0;

        if (empty($imdbId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing imdb_id']);
            return;
        }

        try {
            $progressModel = new WatchProgress();
            $progressModel->setToken($_SESSION['access_token']);
            
            $result = $progressModel->removeProgress($_SESSION['user_id'], $imdbId, $season, $episode);
            
            echo json_encode(['success' => $result]);
        } catch (\Exception $e) {
            error_log("WatchProgressController::remove - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
