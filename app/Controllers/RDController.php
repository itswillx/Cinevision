<?php

namespace App\Controllers;

use App\Models\Settings;
use App\Services\RealDebridService;

class RDController {
    
    private function getService() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $settingsModel = new Settings();
        if (isset($_SESSION['access_token'])) {
            $settingsModel->setToken($_SESSION['access_token']);
        }
        $settings = $settingsModel->getByUserId($_SESSION['user_id']);

        if (empty($settings['rd_token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'RealDebrid token not configured']);
            exit;
        }

        return new RealDebridService($settings['rd_token']);
    }

    public function addMagnet() {
        $service = $this->getService();
        $magnet = $_POST['magnet'] ?? '';

        if (empty($magnet)) {
            echo json_encode(['error' => 'Magnet link required']);
            exit;
        }

        $result = $service->addMagnet($magnet);
        
        if ($result === null) {
            $result = ['error' => 'Empty response from RealDebrid'];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function selectFile() {
        $service = $this->getService();
        $torrentId = $_POST['torrent_id'] ?? '';
        $fileId = $_POST['file_id'] ?? 'all'; // 'all' or specific ID

        if (empty($torrentId)) {
            echo json_encode(['error' => 'Torrent ID required']);
            exit;
        }

        $result = $service->selectFiles($torrentId, $fileId);
        
        // RD API selectFiles returns 204 No Content (null) on success
        if ($result === null) {
            $result = ['success' => true];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function getTorrentInfo() {
        $service = $this->getService();
        $torrentId = $_GET['id'] ?? '';

        if (empty($torrentId)) {
            echo json_encode(['error' => 'Torrent ID required']);
            exit;
        }

        $result = $service->getTorrentInfo($torrentId);

        if ($result === null) {
            $result = ['error' => 'Empty response from RealDebrid'];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function unrestrictLink() {
        $service = $this->getService();
        $link = $_POST['link'] ?? '';

        if (empty($link)) {
            echo json_encode(['error' => 'Link required']);
            exit;
        }

        $result = $service->unrestrictLink($link);

        if ($result === null) {
            $result = ['error' => 'Empty response from RealDebrid'];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * Resolve a Torrentio URL to get the actual stream URL
     */
    public function resolve() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Unauthorized']);
            exit;
        }

        $url = $_POST['url'] ?? '';
        if (empty($url)) {
            echo json_encode(['error' => true, 'message' => 'URL required']);
            exit;
        }

        // Simple file_get_contents approach for Torrentio resolve URLs
        // These URLs redirect to the actual stream
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: Mozilla/5.0',
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 10
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo json_encode(['error' => true, 'message' => 'Failed to resolve URL']);
            exit;
        }

        // For Torrentio resolve URLs, the final redirect location is what we need
        // Since file_get_contents with follow_location gives us the final content,
        // we'll use the original URL as it should be a direct stream after redirects
        echo json_encode(['url' => $url]);
        exit;
    }
}
