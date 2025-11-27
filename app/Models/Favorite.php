<?php

namespace App\Models;

use App\Services\SupabaseService;

class Favorite {
    private $supabase;

    public function __construct() {
        $this->supabase = new SupabaseService();
    }

    public function setToken($token) {
        $this->supabase->setAccessToken($token);
    }

    public function add($userId, $data) {
        $insertData = [
            'user_id' => $userId,
            'imdb_id' => $data['imdb_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'poster' => $data['poster'],
            'year' => $data['year'] ?? ''
        ];

        error_log("Favorite::add - Inserting: " . json_encode($insertData));
        error_log("Favorite::add - User ID: " . $userId);

        try {
            $result = $this->supabase->insert('favorites_v2', $insertData);
            error_log("Favorite::add - Result: " . json_encode($result));
            
            // Verificar se houve erro na resposta (formato _error do SupabaseService)
            if (is_array($result) && isset($result['_error']) && $result['_error'] === true) {
                error_log("Favorite::add - API Error: " . ($result['_message'] ?? 'Unknown'));
                
                // Verificar se é erro de duplicata (já existe)
                $response = $result['_response'] ?? '';
                if (strpos($response, 'duplicate') !== false || strpos($response, 'unique') !== false) {
                    error_log("Favorite::add - Duplicate detected, treating as success");
                    return true;
                }
                
                return false;
            }
            
            // Sucesso se retornou um array com dados
            return is_array($result) && !empty($result);
        } catch (\Exception $e) {
            error_log("Favorite::add - Exception: " . $e->getMessage());
            // Check for duplicate error - treat as success (already exists)
            if (strpos($e->getMessage(), 'duplicate') !== false || 
                strpos($e->getMessage(), 'unique') !== false) {
                return true;
            }
            throw $e;
        }
    }

    public function remove($userId, $imdbId) {
        return $this->supabase->delete('favorites_v2', [
            'user_id' => $userId,
            'imdb_id' => $imdbId
        ]);
    }

    public function getAll($userId) {
        $result = $this->supabase->select('favorites_v2', ['user_id' => $userId], [
            'order' => 'added_at.desc'
        ]);
        return $result ?? [];
    }

    public function isFavorite($userId, $imdbId) {
        return $this->supabase->exists('favorites_v2', [
            'user_id' => $userId,
            'imdb_id' => $imdbId
        ]);
    }
}
