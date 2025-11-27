<?php

namespace App\Controllers;

use App\Services\TorrentioService;
use App\Services\TMDBService;

class CatalogController {
    
    public function index() {
        $tmdb = new TMDBService();
        $torrentio = new TorrentioService();
        
        error_log("CatalogController::index - TMDB configured: " . ($tmdb->isConfigured() ? 'YES' : 'NO'));
        
        // Use TMDB for better popularity-based results
        if ($tmdb->isConfigured() && false) { // Temporarily disabled to restore content
            error_log("CatalogController::index - Using TMDB API");
            
            // Get trending content (what's popular this week)
            $trending = $tmdb->getTrending('all', 'week');
            $trendingItems = $trending['results'] ?? [];
            error_log("CatalogController::index - Trending items count: " . count($trendingItems));
            
            // Get popular movies
            $popularMovies = $tmdb->getPopularMovies();
            $movieItems = $popularMovies['results'] ?? [];
            error_log("CatalogController::index - Popular movies count: " . count($movieItems));
            
            // Get popular series
            $popularSeries = $tmdb->getPopularSeries();
            $seriesItems = $popularSeries['results'] ?? [];
            error_log("CatalogController::index - Popular series count: " . count($seriesItems));
            
            // Get top rated movies
            $topMovies = $tmdb->getTopRatedMovies();
            $topMovieItems = $topMovies['results'] ?? [];
            error_log("CatalogController::index - Top rated movies count: " . count($topMovieItems));
            
            // Get now playing (in theaters)
            $nowPlaying = $tmdb->getNowPlayingMovies();
            $nowPlayingItems = $nowPlaying['results'] ?? [];
            error_log("CatalogController::index - Now playing movies count: " . count($nowPlayingItems));
            
            // Format to standard format
            $movies = array_map(fn($item) => $tmdb->formatToStandard($item, 'movie'), $movieItems);
            $series = array_map(fn($item) => $tmdb->formatToStandard($item, 'tv'), $seriesItems);
            $trending = array_map(fn($item) => $tmdb->formatToStandard($item), $trendingItems);
            $topRated = array_map(fn($item) => $tmdb->formatToStandard($item, 'movie'), $topMovieItems);
            $inTheaters = array_map(fn($item) => $tmdb->formatToStandard($item, 'movie'), $nowPlayingItems);
            
            error_log("CatalogController::index - Formatted movies count: " . count($movies));
            error_log("CatalogController::index - Formatted series count: " . count($series));
            
            // Use TMDB data
            $useTmdb = true;
        } else {
            error_log("CatalogController::index - Using Torrentio fallback");
            
            // Fallback to Torrentio/Cinemeta
            $movieCatalog = $torrentio->getCatalog('movie');
            $seriesCatalog = $torrentio->getCatalog('series');
            
            $movies = $movieCatalog['metas'] ?? [];
            $series = $seriesCatalog['metas'] ?? [];
            $trending = [];
            $topRated = [];
            $inTheaters = [];
            $useTmdb = false;
            
            error_log("CatalogController::index - Fallback movies count: " . count($movies));
            error_log("CatalogController::index - Fallback series count: " . count($series));
        }

        $view = __DIR__ . '/../Views/home.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? ''; // movie, series, ou vazio para todos
        $genre = $_GET['genre'] ?? '';
        $year = $_GET['year'] ?? '';
        $metas = [];
        
        // Lista de gêneros disponíveis para o filtro
        $availableGenres = [
            'action' => 'Ação',
            'adventure' => 'Aventura',
            'animation' => 'Animação',
            'comedy' => 'Comédia',
            'crime' => 'Crime',
            'documentary' => 'Documentário',
            'drama' => 'Drama',
            'family' => 'Família',
            'fantasy' => 'Fantasia',
            'horror' => 'Terror',
            'mystery' => 'Mistério',
            'romance' => 'Romance',
            'sci-fi' => 'Ficção Científica',
            'thriller' => 'Thriller',
            'war' => 'Guerra',
            'western' => 'Faroeste'
        ];
        
        // Anos disponíveis (últimos 50 anos)
        $currentYear = (int)date('Y');
        $availableYears = range($currentYear, $currentYear - 50);

        error_log("CatalogController::search - Query: '$query', Type: '$type', Genre: '$genre', Year: '$year'");

        if (!empty($query)) {
            $tmdb = new TMDBService();
            
            error_log("CatalogController::search - TMDB configured: " . ($tmdb->isConfigured() ? 'YES' : 'NO'));
            
            if ($tmdb->isConfigured() && false) { // Temporarily disabled - needs real TMDB key
                error_log("CatalogController::search - Using TMDB search");
                
                // Use TMDB for search
                $results = $tmdb->search($query);
                $items = $results['results'] ?? [];
                
                error_log("CatalogController::search - TMDB raw results count: " . count($items));
                
                // Filter only movies and TV shows, format to standard
                $metas = array_filter(array_map(function($item) use ($tmdb) {
                    if (!in_array($item['media_type'] ?? '', ['movie', 'tv'])) {
                        return null;
                    }
                    return $tmdb->formatToStandard($item);
                }, $items));
                
                $metas = array_values($metas); // Re-index array
                error_log("CatalogController::search - TMDB formatted results count: " . count($metas));
            } else {
                error_log("CatalogController::search - Using Torrentio fallback");
                
                // Fallback to Torrentio
                $torrentio = new TorrentioService();
                $results = $torrentio->search($query);
                $metas = $results['metas'] ?? [];
                error_log("CatalogController::search - Torrentio results count: " . count($metas));
            }
            
            // Aplicar filtros pós-busca
            $metas = $this->applyFilters($metas, $type, $genre, $year);
            error_log("CatalogController::search - After filters: " . count($metas));
        }

        $view = __DIR__ . '/../Views/search.php';
        require __DIR__ . '/../Views/layout.php';
    }
    
    /**
     * Aplica filtros de tipo, gênero e ano nos resultados
     */
    private function applyFilters($metas, $type, $genre, $year) {
        if (empty($type) && empty($genre) && empty($year)) {
            return $metas;
        }
        
        return array_values(array_filter($metas, function($meta) use ($type, $genre, $year) {
            // Filtro de tipo
            if (!empty($type)) {
                $metaType = $meta['type'] ?? 'movie';
                if ($metaType !== $type) {
                    return false;
                }
            }
            
            // Filtro de ano
            if (!empty($year)) {
                $releaseInfo = $meta['releaseInfo'] ?? $meta['year'] ?? '';
                if (strpos($releaseInfo, $year) === false) {
                    return false;
                }
            }
            
            // Filtro de gênero
            if (!empty($genre)) {
                $genres = $meta['genres'] ?? [];
                if (is_array($genres)) {
                    $genreNames = array_map('strtolower', $genres);
                    $genreMatch = false;
                    foreach ($genreNames as $g) {
                        if (stripos($g, $genre) !== false) {
                            $genreMatch = true;
                            break;
                        }
                    }
                    if (!$genreMatch) {
                        return false;
                    }
                }
            }
            
            return true;
        }));
    }
    
    /**
     * Endpoint de sugestões para autocomplete
     */
    public function suggest() {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 8), 15);
        
        if (strlen($query) < 2) {
            echo json_encode(['suggestions' => []]);
            return;
        }
        
        $torrentio = new TorrentioService();
        $results = $torrentio->search($query);
        $metas = $results['metas'] ?? [];
        
        // Limitar e formatar sugestões
        $suggestions = array_slice(array_map(function($meta) {
            return [
                'id' => $meta['imdb_id'] ?? $meta['id'] ?? '',
                'name' => $meta['name'] ?? '',
                'type' => $meta['type'] ?? 'movie',
                'year' => $meta['releaseInfo'] ?? $meta['year'] ?? '',
                'poster' => $meta['poster'] ?? ''
            ];
        }, $metas), 0, $limit);
        
        echo json_encode(['suggestions' => $suggestions]);
    }

    public function details() {
        $imdbId = $_GET['id'] ?? '';
        $type = $_GET['type'] ?? 'movie';

        if (empty($imdbId)) {
            header('Location: /');
            exit;
        }

        $torrentio = new TorrentioService();
        
        if ($type === 'series') {
            $metaData = $torrentio->getSeriesInfo($imdbId);
        } else {
            $metaData = $torrentio->getMovieInfo($imdbId);
        }
        
        $meta = $metaData['meta'] ?? null;

        if (!$meta) {
            header('Location: /');
            exit;
        }

        $view = __DIR__ . '/../Views/details.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
