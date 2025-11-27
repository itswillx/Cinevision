<?php

namespace App\Services;

class TorrentioService {
    // Base URL for Torrentio - will be built dynamically based on user settings
    private $baseUrl = 'https://torrentio.strem.fun';
    private $rdToken = null;
    private $qualityPref = null;
    private static $cache = [];
    private const CACHE_TTL = 300; // 5 minutes cache

    public function __construct($rdToken = null, $qualityPref = null) {
        $this->rdToken = $rdToken;
        $this->qualityPref = $qualityPref;
    }

    /**
     * Fast HTTP request using cURL with timeout
     */
    private function httpGet($url, $timeout = 5) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '', // Accept all encodings (gzip, deflate)
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: CineVision/1.0'
            ]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }

    /**
     * Get cached data or fetch from URL
     */
    private function getCached($key, $url, $ttl = null) {
        $ttl = $ttl ?? self::CACHE_TTL;
        
        // Check memory cache
        if (isset(self::$cache[$key]) && self::$cache[$key]['expires'] > time()) {
            return self::$cache[$key]['data'];
        }
        
        // Check file cache
        $cacheFile = sys_get_temp_dir() . '/cinevision_' . md5($key) . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $fileContent = file_get_contents($cacheFile);
            $data = $fileContent ? json_decode($fileContent, true) : null;
            if ($data) {
                self::$cache[$key] = ['data' => $data, 'expires' => time() + $ttl];
                return $data;
            }
        }
        
        // Fetch fresh data
        $response = $this->httpGet($url);
        $data = $response ? json_decode($response, true) : null;
        
        if ($data) {
            // Save to memory cache
            self::$cache[$key] = ['data' => $data, 'expires' => time() + $ttl];
            // Save to file cache
            @file_put_contents($cacheFile, $response);
        }
        
        return $data;
    }

    /**
     * Parallel HTTP requests using cURL multi
     */
    private function httpGetMulti($urls, $timeout = 5) {
        $mh = curl_multi_init();
        $handles = [];
        
        foreach ($urls as $key => $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: CineVision/1.0'
                ]
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        
        // Execute all requests in parallel
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        // Collect responses
        $results = [];
        foreach ($handles as $key => $ch) {
            $results[$key] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        
        return $results;
    }

    private function buildConfigUrl($language = 'portuguese') {
        $config = "sort=seeders|language={$language}|qualityfilter=480p,cam|limit=50";
        
        if ($this->rdToken) {
            $config .= '|realdebrid=' . $this->rdToken;
        } else {
            $config .= '|debridoptions=nodownloadlinks';
        }
        
        return $this->baseUrl . '/' . urlencode($config);
    }

    public function search($query) {
        return $this->searchCinemeta($query);
    }

    private function searchCinemeta($query) {
        $encodedQuery = urlencode($query);
        $urls = [
            'movies' => "https://v3-cinemeta.strem.io/catalog/movie/top/search={$encodedQuery}.json",
            'series' => "https://v3-cinemeta.strem.io/catalog/series/top/search={$encodedQuery}.json"
        ];
        
        // Parallel requests
        $responses = $this->httpGetMulti($urls);
        
        $moviesJson = $responses['movies'] ?? '';
        $seriesJson = $responses['series'] ?? '';
        $movies = $moviesJson ? (json_decode($moviesJson, true)['metas'] ?? []) : [];
        $series = $seriesJson ? (json_decode($seriesJson, true)['metas'] ?? []) : [];
        
        return ['metas' => array_merge($movies, $series)];
    }

    public function getCatalog($type = 'movie') {
        $url = "https://v3-cinemeta.strem.io/catalog/$type/top.json";
        $cacheKey = "catalog_{$type}";
        
        return $this->getCached($cacheKey, $url);
    }

    public function getStreams($type, $imdbId, $season = null, $episode = null) {
        // Primeira tentativa: buscar em português
        $ptStreams = $this->fetchStreams($type, $imdbId, $season, $episode, 'portuguese');
        
        // Segunda tentativa: buscar em inglês para complementar
        $enStreams = $this->fetchStreams($type, $imdbId, $season, $episode, 'english');
        
        // Mesclar streams: PT-BR primeiro, depois EN (sem duplicatas)
        $allStreams = $this->mergeStreamsByPriority($ptStreams, $enStreams);
        
        // Filtrar e ordenar
        $allStreams = $this->filterAndSortStreams($allStreams);
        
        return ['streams' => $allStreams];
    }
    
    /**
     * Busca streams com idioma específico
     */
    private function fetchStreams($type, $imdbId, $season, $episode, $language) {
        $configUrl = $this->buildConfigUrl($language);
        
        if ($type === 'series' && $season !== null && $episode !== null) {
            $url = $configUrl . "/stream/series/{$imdbId}:{$season}:{$episode}.json";
        } else {
            $url = $configUrl . "/stream/$type/$imdbId.json";
        }
        
        $response = $this->httpGet($url, 8);
        $data = $response ? json_decode($response, true) : null;
        
        return $data['streams'] ?? [];
    }
    
    /**
     * Mescla streams com prioridade PT-BR > EN
     * Remove duplicatas baseado em infoHash
     */
    private function mergeStreamsByPriority($ptStreams, $enStreams) {
        $merged = [];
        $seenHashes = [];
        
        // Primeiro: adicionar streams PT-BR (prioridade)
        foreach ($ptStreams as $stream) {
            $hash = $stream['infoHash'] ?? ($stream['behaviorHints']['infoHash'] ?? null);
            if ($hash && !isset($seenHashes[$hash])) {
                $stream['_language'] = 'pt'; // Marcar idioma
                $merged[] = $stream;
                $seenHashes[$hash] = true;
            } elseif (!$hash) {
                // Stream sem hash (direto), adicionar com base na URL
                $url = $stream['url'] ?? '';
                $urlKey = md5($url);
                if (!isset($seenHashes[$urlKey])) {
                    $stream['_language'] = 'pt';
                    $merged[] = $stream;
                    $seenHashes[$urlKey] = true;
                }
            }
        }
        
        // Segundo: adicionar streams EN que não estão duplicados
        foreach ($enStreams as $stream) {
            $hash = $stream['infoHash'] ?? ($stream['behaviorHints']['infoHash'] ?? null);
            if ($hash && !isset($seenHashes[$hash])) {
                $stream['_language'] = 'en';
                $merged[] = $stream;
                $seenHashes[$hash] = true;
            } elseif (!$hash) {
                $url = $stream['url'] ?? '';
                $urlKey = md5($url);
                if (!isset($seenHashes[$urlKey])) {
                    $stream['_language'] = 'en';
                    $merged[] = $stream;
                    $seenHashes[$urlKey] = true;
                }
            }
        }
        
        return $merged;
    }

    /**
     * Filter and sort streams based on quality, audio and seeders
     */
    private function filterAndSortStreams($streams) {
        // 1. FILTRAR FONTES DE BAIXA QUALIDADE (CAM, TS, etc)
        $streams = $this->filterLowQualitySources($streams);
        
        // Quality mapping
        $qualityPatterns = [
            '4k' => ['4k', '2160p', 'uhd'],
            '1080p' => ['1080p', 'fullhd', 'full hd'],
            '720p' => ['720p'],
            '480p' => ['480p', 'sd', 'dvdrip'],
        ];
        
        // Filter by quality preference if set
        if ($this->qualityPref && isset($qualityPatterns[$this->qualityPref])) {
            $patterns = $qualityPatterns[$this->qualityPref];
            $filtered = array_filter($streams, function($stream) use ($patterns) {
                $title = strtolower($stream['title'] ?? '');
                $name = strtolower($stream['name'] ?? '');
                $combined = $title . ' ' . $name;
                
                foreach ($patterns as $pattern) {
                    if (strpos($combined, $pattern) !== false) {
                        return true;
                    }
                }
                return false;
            });
            
            // If we found streams matching the quality, use them; otherwise fall back to all
            if (!empty($filtered)) {
                $streams = array_values($filtered);
            }
        }
        
        // 2. CALCULAR SCORES (idioma + áudio)
        foreach ($streams as &$stream) {
            $stream['_langScore'] = $this->calculateLanguageScore($stream);
            $stream['_audioScore'] = $this->calculateAudioQualityScore($stream);
            $stream['_totalScore'] = $stream['_langScore'] + $stream['_audioScore'];
        }
        unset($stream);
        
        // 3. ORDENAR: score total > seeders
        usort($streams, function($a, $b) {
            // Primeiro: score total (idioma + áudio)
            $scoreDiff = ($b['_totalScore'] ?? 0) - ($a['_totalScore'] ?? 0);
            if ($scoreDiff !== 0) {
                return $scoreDiff;
            }
            
            // Segundo: por seeders
            $seedersA = $this->extractSeeders($a['title'] ?? '');
            $seedersB = $this->extractSeeders($b['title'] ?? '');
            return $seedersB - $seedersA;
        });
        
        return $streams;
    }
    
    /**
     * Filtra fontes de baixa qualidade conhecidas
     */
    private function filterLowQualitySources($streams) {
        // Termos que indicam fontes problemáticas (áudio ruim/inexistente)
        $blacklistTerms = [
            'cam', 'camrip', 'hdcam', 'cam-rip',
            'ts', 'telesync', 'hdts', 'hd-ts',
            'tc', 'telecine',
            'scr', 'screener', 'dvdscr',
            'workprint',
            'incompleto', 'incomplete',
            'sample',
            'trailer',
            '3d sbs', '3d-sbs', 'sbs 3d', // 3D pode ter problemas de áudio
        ];
        
        return array_filter($streams, function($stream) use ($blacklistTerms) {
            $title = strtolower($stream['title'] ?? '');
            $name = strtolower($stream['name'] ?? '');
            $combined = $title . ' ' . $name;
            
            foreach ($blacklistTerms as $term) {
                if (strpos($combined, $term) !== false) {
                    return false; // Descarta este stream
                }
            }
            return true; // Mantém este stream
        });
    }
    
    /**
     * Calcula score de qualidade de áudio
     * Maior score = maior probabilidade de ter áudio funcionando
     */
    private function calculateAudioQualityScore($stream) {
        $title = strtolower($stream['title'] ?? '');
        $name = strtolower($stream['name'] ?? '');
        $combined = $title . ' ' . $name;
        
        $score = 0;
        
        // ALTA PRIORIDADE - Indicadores de áudio multi-track ou de qualidade
        $highPriority = ['dual audio', 'dual-audio', 'dualaudio', 'multi audio', 'multi-audio'];
        foreach ($highPriority as $term) {
            if (strpos($combined, $term) !== false) {
                $score += 50;
                break;
            }
        }
        
        // MÉDIA PRIORIDADE - Codecs de áudio conhecidos
        $audioCodecs = ['5.1', '7.1', 'dts', 'dts-hd', 'truehd', 'atmos', 'aac', 'ac3', 'eac3', 'dd5.1', 'ddp5.1'];
        foreach ($audioCodecs as $codec) {
            if (strpos($combined, $codec) !== false) {
                $score += 30;
                break;
            }
        }
        
        // PRIORIDADE - Fontes conhecidas por qualidade
        $trustedSources = ['bluray', 'blu-ray', 'bdrip', 'brrip', 'remux', 'web-dl', 'webdl', 'webrip', 'amzn', 'nf', 'dsnp', 'hmax'];
        foreach ($trustedSources as $source) {
            if (strpos($combined, $source) !== false) {
                $score += 20;
                break;
            }
        }
        
        // PENALIDADE - Indicadores de possíveis problemas
        $suspicious = ['hdtv', 'pdtv', 'dsr', 'hr', 'r5'];
        foreach ($suspicious as $term) {
            if (strpos($combined, $term) !== false) {
                $score -= 10;
                break;
            }
        }
        
        return max(0, $score); // Nunca retorna negativo
    }
    
    /**
     * Calcula score de prioridade de idioma
     * Maior score = maior prioridade
     */
    private function calculateLanguageScore($stream) {
        $title = strtolower($stream['title'] ?? '');
        $name = strtolower($stream['name'] ?? '');
        $combined = $title . ' ' . $name;
        $lang = $stream['_language'] ?? '';
        
        $score = 0;
        
        // Termos que indicam conteúdo em português (maior prioridade)
        $ptTerms = ['dublado', 'pt-br', 'ptbr', 'pt br', 'portuguese', 'nacional', 'dual audio', 'dub'];
        foreach ($ptTerms as $term) {
            if (strpos($combined, $term) !== false) {
                $score += 100;
                break;
            }
        }
        
        // Se veio da busca PT
        if ($lang === 'pt') {
            $score += 50;
        }
        
        // Termos em inglês (prioridade média)
        if ($lang === 'en' || strpos($combined, 'english') !== false) {
            $score += 25;
        }
        
        return $score;
    }

    /**
     * Extract seeder count from Torrentio stream title
     * Format usually includes "👤 123" or "S: 123"
     */
    private function extractSeeders($title) {
        // Try to match "👤 123" pattern
        if (preg_match('/👤\s*(\d+)/u', $title, $matches)) {
            return (int)$matches[1];
        }
        // Try to match "S: 123" or "Seeders: 123"
        if (preg_match('/(?:S|Seeders?):\s*(\d+)/i', $title, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    public function getSeriesInfo($imdbId) {
        $url = "https://v3-cinemeta.strem.io/meta/series/{$imdbId}.json";
        $cacheKey = "series_meta_{$imdbId}";
        return $this->getCached($cacheKey, $url, 600); // 10 min cache for metadata
    }

    public function getMovieInfo($imdbId) {
        $url = "https://v3-cinemeta.strem.io/meta/movie/{$imdbId}.json";
        $cacheKey = "movie_meta_{$imdbId}";
        return $this->getCached($cacheKey, $url, 600); // 10 min cache for metadata
    }
}
