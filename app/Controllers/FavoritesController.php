<?php

namespace App\Controllers;

use App\Models\Favorite;

class FavoritesController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $favModel = new Favorite();
        if (isset($_SESSION['access_token'])) {
            $favModel->setToken($_SESSION['access_token']);
        }
        $favorites = $favModel->getAll($_SESSION['user_id']);

        $view = __DIR__ . '/../Views/favorites.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function add() {
        header('Content-Type: application/json');
        
        error_log("FavoritesController::add - Session: " . json_encode([
            'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
            'has_token' => isset($_SESSION['access_token']) ? 'YES' : 'NO'
        ]));
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - no user_id in session']);
            exit;
        }
        
        if (!isset($_SESSION['access_token'])) {
            error_log("FavoritesController::add - No access_token in session!");
            echo json_encode(['error' => 'No access token - please login again']);
            return;
        }

        $data = [
            'imdb_id' => $_POST['imdb_id'] ?? '',
            'type' => $_POST['type'] ?? 'movie',
            'title' => $_POST['title'] ?? '',
            'poster' => $_POST['poster'] ?? '',
            'year' => $_POST['year'] ?? ''
        ];

        error_log("Adding favorite: " . json_encode($data));

        if (empty($data['imdb_id']) || empty($data['title'])) {
            echo json_encode(['error' => 'Invalid data', 'received' => $data]);
            return;
        }

        try {
            $favModel = new Favorite();
            $favModel->setToken($_SESSION['access_token']);
            
            $result = $favModel->add($_SESSION['user_id'], $data);
            error_log("Favorite add result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Could not add favorite - database error']);
            }
        } catch (\Exception $e) {
            error_log("Favorite add error: " . $e->getMessage());
            echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    public function remove() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $imdbId = $_POST['imdb_id'] ?? '';

        $favModel = new Favorite();
        if (isset($_SESSION['access_token'])) {
            $favModel->setToken($_SESSION['access_token']);
        }
        if ($favModel->remove($_SESSION['user_id'], $imdbId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Could not remove favorite']);
        }
    }
    
    public function list() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $favModel = new Favorite();
        if (isset($_SESSION['access_token'])) {
            $favModel->setToken($_SESSION['access_token']);
        }
        
        $favorites = $favModel->getAll($_SESSION['user_id']);
        echo json_encode(['favorites' => $favorites]);
    }
}
