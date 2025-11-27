<?php

namespace App\Services;

class TMDBService {
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBaseUrl = 'https://image.tmdb.org/t/p/';
    private static $cache = [];
    
    public function __construct() {
        // Get API key from settings or env
        $this->apiKey = $_ENV['TMDB_API_KEY'] ?? $this->getApiKeyFromConfig();
    }
    
    private function getApiKeyFromConfig() {
        $configFile = __DIR__ . '/../../config/env.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            return $config['TMDB_API_KEY'] ?? '';
        }
        return '';
    }
    
    /**
     * Get trending movies/series (what's popular now)
     */
    public function getTrending($type = 'all', $timeWindow = 'week') {
        $cacheKey = "trending_{$type}_{$timeWindow}";
        return $this->getCached($cacheKey, "/trending/{$type}/{$timeWindow}", [
            'language' => 'pt-BR'
        ]);
    }
    
    /**
     * Get popular movies
     */
    public function getPopularMovies($page = 1) {
        return $this->getCached("popular_movies_{$page}", '/movie/popular', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Get popular TV series
     */
    public function getPopularSeries($page = 1) {
        return $this->getCached("popular_series_{$page}", '/tv/popular', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Get top rated movies
     */
    public function getTopRatedMovies($page = 1) {
        return $this->getCached("top_movies_{$page}", '/movie/top_rated', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Get top rated TV series
     */
    public function getTopRatedSeries($page = 1) {
        return $this->getCached("top_series_{$page}", '/tv/top_rated', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Get now playing movies (in theaters)
     */
    public function getNowPlayingMovies($page = 1) {
        return $this->getCached("now_playing_{$page}", '/movie/now_playing', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Get upcoming movies
     */
    public function getUpcomingMovies($page = 1) {
        return $this->getCached("upcoming_{$page}", '/movie/upcoming', [
            'language' => 'pt-BR',
            'page' => $page
        ]);
    }
    
    /**
     * Search movies and TV shows
     */
    public function search($query, $page = 1) {
        if (empty($query)) return ['results' => []];
        
        return $this->makeRequest('/search/multi', [
            'query' => $query,
            'language' => 'pt-BR',
            'page' => $page,
            'include_adult' => false
        ]);
    }
    
    /**
     * Get movie details with IMDB ID
     */
    public function getMovieDetails($tmdbId) {
        return $this->makeRequest("/movie/{$tmdbId}", [
            'language' => 'pt-BR',
            'append_to_response' => 'external_ids,credits,videos'
        ]);
    }
    
    /**
     * Get TV series details
     */
    public function getSeriesDetails($tmdbId) {
        return $this->makeRequest("/tv/{$tmdbId}", [
            'language' => 'pt-BR',
            'append_to_response' => 'external_ids,credits,videos'
        ]);
    }
    
    /**
     * Get movie by IMDB ID
     */
    public function findByImdbId($imdbId) {
        return $this->makeRequest("/find/{$imdbId}", [
            'external_source' => 'imdb_id',
            'language' => 'pt-BR'
        ]);
    }
    
    /**
     * Convert TMDB result to standard format (compatible with Cinemeta)
     */
    public function formatToStandard($item, $type = null) {
        if (!$item) return null;
        
        $mediaType = $type ?? ($item['media_type'] ?? 'movie');
        $isMovie = $mediaType === 'movie';
        
        return [
            'id' => $item['id'],
            'imdb_id' => $item['imdb_id'] ?? $this->getImdbId($item['id'], $mediaType),
            'name' => $isMovie ? ($item['title'] ?? '') : ($item['name'] ?? ''),
            'type' => $isMovie ? 'movie' : 'series',
            'poster' => $this->getImageUrl($item['poster_path'] ?? '', 'w500'),
            'background' => $this->getImageUrl($item['backdrop_path'] ?? '', 'original'),
            'description' => $item['overview'] ?? '',
            'year' => $this->extractYear($isMovie ? ($item['release_date'] ?? '') : ($item['first_air_date'] ?? '')),
            'imdbRating' => number_format($item['vote_average'] ?? 0, 1),
            'popularity' => $item['popularity'] ?? 0,
            'genres' => $this->getGenreNames($item['genre_ids'] ?? []),
            'vote_count' => $item['vote_count'] ?? 0
        ];
    }
    
    /**
     * Get IMDB ID from TMDB ID
     */
    private function getImdbId($tmdbId, $type) {
        $cacheKey = "imdb_{$type}_{$tmdbId}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $endpoint = $type === 'movie' ? "/movie/{$tmdbId}/external_ids" : "/tv/{$tmdbId}/external_ids";
        $data = $this->makeRequest($endpoint);
        
        $imdbId = $data['imdb_id'] ?? null;
        self::$cache[$cacheKey] = $imdbId;
        
        return $imdbId;
    }
    
    private function getImageUrl($path, $size = 'w500') {
        if (empty($path)) return '';
        return $this->imageBaseUrl . $size . $path;
    }
    
    private function extractYear($date) {
        if (empty($date)) return '';
        return substr($date, 0, 4);
    }
    
    private function getGenreNames($genreIds) {
        $genres = [
            28 => 'Ação', 12 => 'Aventura', 16 => 'Animação', 35 => 'Comédia',
            80 => 'Crime', 99 => 'Documentário', 18 => 'Drama', 10751 => 'Família',
            14 => 'Fantasia', 36 => 'História', 27 => 'Terror', 10402 => 'Música',
            9648 => 'Mistério', 10749 => 'Romance', 878 => 'Ficção Científica',
            10770 => 'Cinema TV', 53 => 'Thriller', 10752 => 'Guerra', 37 => 'Faroeste',
            10759 => 'Ação & Aventura', 10762 => 'Kids', 10763 => 'Notícias',
            10764 => 'Reality', 10765 => 'Sci-Fi & Fantasia', 10766 => 'Soap',
            10767 => 'Talk', 10768 => 'Guerra & Política'
        ];
        
        return array_map(fn($id) => $genres[$id] ?? '', $genreIds);
    }
    
    private function getCached($key, $endpoint, $params = [], $ttl = 300) {
        // Check memory cache
        if (isset(self::$cache[$key]) && self::$cache[$key]['expires'] > time()) {
            return self::$cache[$key]['data'];
        }
        
        // Check file cache
        $cacheFile = sys_get_temp_dir() . '/cinevision_tmdb_' . md5($key) . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $content = file_get_contents($cacheFile);
            $data = $content ? json_decode($content, true) : null;
            if ($data) {
                self::$cache[$key] = ['data' => $data, 'expires' => time() + $ttl];
                return $data;
            }
        }
        
        // Fetch fresh data
        $data = $this->makeRequest($endpoint, $params);
        
        if ($data && !isset($data['error'])) {
            self::$cache[$key] = ['data' => $data, 'expires' => time() + $ttl];
            @file_put_contents($cacheFile, json_encode($data));
        }
        
        return $data;
    }
    
    private function makeRequest($endpoint, $params = []) {
        if (empty($this->apiKey)) {
            error_log("TMDBService::makeRequest - API key not configured");
            return ['error' => 'TMDB API key not configured'];
        }
        
        $params['api_key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        
        error_log("TMDBService::makeRequest - URL: $url");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("TMDBService::makeRequest - HTTP Code: $httpCode, Response length: " . strlen($response ?? ''));
        error_log("TMDBService::makeRequest - cURL Error: $error");
        
        if ($httpCode !== 200 || !$response) {
            error_log("TMDBService::makeRequest - API Error: HTTP $httpCode, Error: $error");
            return ['error' => 'TMDB API error: ' . $httpCode];
        }
        
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            error_log("TMDBService::makeRequest - Invalid JSON response");
            return ['error' => 'Invalid JSON'];
        }
        
        error_log("TMDBService::makeRequest - Success: " . (isset($decoded['results']) ? count($decoded['results']) . ' results' : 'no results key'));
        return $decoded;
    }
    
    /**
     * Check if TMDB is configured
     */
    public function isConfigured() {
        return !empty($this->apiKey);
    }
}
