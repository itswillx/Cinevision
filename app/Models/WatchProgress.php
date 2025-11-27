<?php

namespace App\Models;

use App\Services\SupabaseService;

class WatchProgress {
    private $supabase;
    private const TABLE = 'watch_progress';

    public function __construct() {
        $this->supabase = new SupabaseService();
    }

    public function setToken($token) {
        $this->supabase->setAccessToken($token);
    }

    /**
     * Salva ou atualiza o progresso de visualização
     */
    public function saveProgress($userId, $data) {
        // Usar 0 em vez de NULL para season/episode (necessário para o unique index funcionar)
        $season = isset($data['season']) && $data['season'] ? (int)$data['season'] : 0;
        $episode = isset($data['episode']) && $data['episode'] ? (int)$data['episode'] : 0;
        
        $progressData = [
            'user_id' => $userId,
            'imdb_id' => $data['imdb_id'],
            'type' => $data['type'],
            'title' => $data['title'] ?? '',
            'poster' => $data['poster'] ?? '',
            'year' => $data['year'] ?? '',
            'season' => $season,
            'episode' => $episode,
            'current_time_sec' => (float)($data['current_time'] ?? 0),
            'duration_sec' => (float)($data['duration'] ?? 0),
            'percent_watched' => (int)($data['percent'] ?? 0),
            'completed' => isset($data['completed']) ? (bool)$data['completed'] : false,
            // Stream info for resuming with the same source
            'stream_index' => isset($data['stream_index']) ? (int)$data['stream_index'] : 0,
            'stream_infohash' => $data['stream_infohash'] ?? null,
            'stream_url' => $data['stream_url'] ?? null,
            'stream_title' => $data['stream_title'] ?? null,
            'last_watched_at' => date('c') // ISO 8601 format
        ];

        error_log("WatchProgress::saveProgress - Data: " . json_encode($progressData));

        // Usar upsert para inserir ou atualizar
        // Como estamos usando 0 em vez de NULL, podemos usar as colunas diretamente
        $onConflict = 'user_id,imdb_id,season,episode';
        $result = $this->supabase->upsert(self::TABLE, $progressData, $onConflict);
        
        if (SupabaseService::hasError($result)) {
            error_log("WatchProgress::saveProgress - Error: " . json_encode($result));
            return false;
        }

        return $result;
    }

    /**
     * Obtém o progresso de um conteúdo específico
     */
    public function getProgress($userId, $imdbId, $season = null, $episode = null) {
        $filters = [
            'user_id' => $userId,
            'imdb_id' => $imdbId
        ];

        // Para séries, buscar episódio específico
        if ($season !== null && $episode !== null) {
            $filters['season'] = $season;
            $filters['episode'] = $episode;
        }

        $result = $this->supabase->select(self::TABLE, $filters, ['single' => true]);
        
        if (SupabaseService::hasError($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Obtém todos os conteúdos com progresso (para "Continuar Assistindo")
     */
    public function getContinueWatching($userId, $limit = 20) {
        $result = $this->supabase->select(self::TABLE, ['user_id' => $userId], [
            'order' => 'last_watched_at.desc',
            'limit' => $limit
        ]);
        
        if (SupabaseService::hasError($result)) {
            return [];
        }

        // Filtrar apenas os que não foram completados e têm progresso > 0
        return array_filter($result ?? [], function($item) {
            return !$item['completed'] && $item['percent_watched'] > 0 && $item['percent_watched'] < 95;
        });
    }

    /**
     * Obtém todo o progresso do usuário (para sync)
     */
    public function getAllProgress($userId) {
        $result = $this->supabase->select(self::TABLE, ['user_id' => $userId], [
            'order' => 'last_watched_at.desc'
        ]);
        
        if (SupabaseService::hasError($result)) {
            return [];
        }

        return $result ?? [];
    }

    /**
     * Remove o progresso de um conteúdo
     */
    public function removeProgress($userId, $imdbId, $season = 0, $episode = 0) {
        $filters = [
            'user_id' => $userId,
            'imdb_id' => $imdbId,
            'season' => (int)$season,
            'episode' => (int)$episode
        ];

        return $this->supabase->delete(self::TABLE, $filters);
    }

    /**
     * Marca conteúdo como completado
     */
    public function markCompleted($userId, $imdbId, $season = null, $episode = null) {
        $filters = [
            'user_id' => $userId,
            'imdb_id' => $imdbId
        ];

        if ($season !== null) {
            $filters['season'] = $season;
        }
        if ($episode !== null) {
            $filters['episode'] = $episode;
        }

        return $this->supabase->update(self::TABLE, $filters, [
            'completed' => true,
            'percent_watched' => 100
        ]);
    }
}
