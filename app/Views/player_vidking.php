<?php 
$isSeries = ($type ?? 'movie') === 'series';
$displayTitle = $meta['name'] ?? 'Reproduzindo';
$baseTitle = $meta['name'] ?? '';
if ($isSeries && isset($season) && isset($episode)) {
    $displayTitle .= " - S{$season}E{$episode}";
}
$poster = $meta['poster'] ?? '';
$year = $meta['year'] ?? '';

// Build vidking embed URL using TMDB ID
$contentId = $tmdbId ?? $imdbId; // Fallback to IMDB ID if TMDB conversion failed

// Vidking embed parameters
// color: Netflix red, autoplay: true
// Note: Server selection (Oxygen, Hydrogen, etc.) is controlled by the player UI, not URL params
$playerParams = 'color=%23E50914&primaryColor=%23E50914&autoplay=true';

if ($isSeries && isset($season) && isset($episode)) {
    $embedUrl = "https://www.vidking.net/embed/tv/{$contentId}/{$season}/{$episode}?{$playerParams}";
} else {
    $embedUrl = "https://www.vidking.net/embed/movie/{$contentId}?{$playerParams}";
}
?>

<div class="container" style="margin-top: 20px;">
    <!-- Title, Favorite Button and Back Button -->
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <?php if ($isSeries): ?>
            <a href="/details?id=<?php echo $imdbId; ?>&type=series" class="btn" style="background: var(--card-bg); padding: 8px 15px;">← Voltar</a>
        <?php else: ?>
            <a href="/" class="btn" style="background: var(--card-bg); padding: 8px 15px;">← Início</a>
        <?php endif; ?>
        <h2 style="margin: 0;"><?php echo htmlspecialchars($displayTitle); ?></h2>
        
        <!-- Botão de Favoritos -->
        <button id="playerFavoriteBtn" 
                class="favorite-btn" 
                data-heart-id="<?php echo $imdbId; ?>" 
                data-type="<?php echo $type; ?>" 
                data-title="<?php echo htmlspecialchars($baseTitle); ?>" 
                data-poster="<?php echo $poster; ?>" 
                data-year="<?php echo $year; ?>"
                style="opacity: 1; position: relative; bottom: auto; right: auto;"
                title="Adicionar aos favoritos">
            🤍
        </button>
    </div>

    <!-- Vidking Player Embed -->
    <div class="player-wrapper" id="playerContainer" style="position: relative; padding-top: 56.25%; background: #000; margin-bottom: 20px; border-radius: 10px; overflow: hidden;">
        <iframe 
            id="vidkingPlayer"
            src="<?php echo htmlspecialchars($embedUrl); ?>" 
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="origin"
            sandbox="allow-scripts allow-same-origin allow-forms allow-presentation allow-fullscreen"
            loading="lazy">
        </iframe>
        <!-- Overlay to block first click popup -->
        <div id="clickOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; cursor: pointer; display: none;"></div>
    </div>

    <!-- Player Info -->
    <div class="player-controls" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div class="player-info" style="flex: 1; min-width: 300px;">
            <h3>Informações</h3>
            <div style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                <p><strong>Título:</strong> <?php echo htmlspecialchars($meta['name'] ?? 'N/A'); ?></p>
                <?php if ($year): ?>
                    <p><strong>Ano:</strong> <?php echo htmlspecialchars($year); ?></p>
                <?php endif; ?>
                <?php if ($isSeries): ?>
                    <p><strong>Temporada:</strong> <?php echo $season; ?></p>
                    <p><strong>Episódio:</strong> <?php echo $episode; ?></p>
                <?php endif; ?>
                <?php if (!empty($meta['description'])): ?>
                    <p style="margin-top: 10px; color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars(substr($meta['description'] ?? '', 0, 300)); ?><?php echo strlen($meta['description'] ?? '') > 300 ? '...' : ''; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- OpenSubtitles Section -->
            <div style="margin-top: 20px;">
                <h3>🌐 OpenSubtitles</h3>
                <div id="openSubtitlesSection" style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                    <button id="loadOpenSubtitles" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">
                        Buscar Legendas no OpenSubtitles
                    </button>
                    <div id="subtitlesList" style="display: none;">
                        <input type="text" id="subtitleSearch" placeholder="Filtrar por idioma..." 
                               class="form-control" style="margin-bottom: 10px;">
                        <div id="subtitlesContainer" style="max-height: 300px; overflow-y: auto;">
                            <!-- Legendas serão carregadas aqui -->
                        </div>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 10px;">
                        💡 Baixe a legenda e use "Upload Custom Subtitle (.vtt)" no player
                    </p>
                </div>
            </div>
            
            <!-- Player Tips -->
            <div style="margin-top: 20px;">
                <h3>Dicas</h3>
                <div style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                    <ul style="margin: 0; padding-left: 20px; color: var(--text-muted);">
                        <li>Use os controles do player para ajustar qualidade e legendas</li>
                        <li>Se o vídeo não carregar, tente recarregar a página</li>
                        <li>Alguns conteúdos podem demorar para carregar na primeira vez</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if ($isSeries): ?>
        <!-- Episode Navigation for Series -->
        <div class="episode-nav" style="flex: 1; min-width: 300px;">
            <h3>Navegação de Episódios</h3>
            <div style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?php 
                    $prevEp = $episode - 1;
                    $nextEp = $episode + 1;
                    ?>
                    <?php if ($prevEp >= 1): ?>
                        <a href="/watch?id=<?php echo $imdbId; ?>&type=series&season=<?php echo $season; ?>&episode=<?php echo $prevEp; ?>" 
                           class="btn" style="background: var(--card-bg); border: 1px solid var(--text-muted);">
                            ← Episódio Anterior
                        </a>
                    <?php endif; ?>
                    <a href="/watch?id=<?php echo $imdbId; ?>&type=series&season=<?php echo $season; ?>&episode=<?php echo $nextEp; ?>" 
                       class="btn btn-primary">
                        Próximo Episódio →
                    </a>
                </div>
                <p style="text-align: center; margin-top: 15px; color: var(--text-muted); font-size: 0.85rem;">
                    <a href="/details?id=<?php echo $imdbId; ?>&type=series" style="color: var(--primary);">Ver todos os episódios</a>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Botão de Favoritos no Player */
    #playerFavoriteBtn {
        width: 44px;
        height: 44px;
        background: var(--card-bg);
        border: 2px solid rgba(255,255,255,0.2);
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    #playerFavoriteBtn:hover {
        background: rgba(255,255,255,0.1);
        transform: scale(1.1);
        border-color: var(--primary);
    }
    #playerFavoriteBtn.favorited {
        border-color: #e74c3c;
        background: rgba(231, 76, 60, 0.15);
    }
    
    .player-wrapper {
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    
    .player-wrapper iframe {
        background: #000;
    }
</style>

<script>
    const imdbId = '<?php echo $imdbId ?? ''; ?>';
    const contentType = '<?php echo $type ?? 'movie'; ?>';
    const title = '<?php echo htmlspecialchars($meta['name'] ?? ''); ?>';
    const poster = '<?php echo htmlspecialchars($meta['poster'] ?? ''); ?>';
    const year = '<?php echo htmlspecialchars($meta['year'] ?? ''); ?>';
    const season = '<?php echo $season ?? ''; ?>';
    const episode = '<?php echo $episode ?? ''; ?>';

    console.log('Vidking Player loaded for:', contentType, imdbId);
    <?php if ($isSeries): ?>
    console.log('Series: Season', season, 'Episode', episode);
    <?php endif; ?>
    
    // Save to continue watching when user starts watching
    // Use window.onload to ensure all scripts are loaded
    window.addEventListener('load', function() {
        // Register view after 5 seconds (user is actually watching)
        setTimeout(function() {
            console.log('[Vidking] Attempting to save progress...');
            console.log('[Vidking] CineVision available:', typeof window.CineVision !== 'undefined');
            
            if (typeof window.CineVision !== 'undefined' && typeof window.CineVision.savePlayerProgress === 'function') {
                const progressData = {
                    current_time: 1,      // Minimum value to indicate started watching
                    duration: 100,        // Estimated duration (will show ~1% progress)
                    percent: 1,           // 1% progress to appear in "Continue Watching"
                    season: season || null,
                    episode: episode || null,
                    type: contentType,
                    title: title,
                    poster: poster,
                    year: year,
                    player: 'vidking'
                };
                console.log('[Vidking] Progress data:', progressData);
                window.CineVision.savePlayerProgress(imdbId, progressData);
                console.log('[Vidking] Progress saved for continue watching');
            } else {
                console.error('[Vidking] CineVision not available or savePlayerProgress not a function');
                // Fallback: call API directly
                fetch('/api/progress/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        imdb_id: imdbId,
                        current_time: 1,
                        duration: 100,
                        percent: 1,
                        season: season || null,
                        episode: episode || null,
                        type: contentType,
                        title: title,
                        poster: poster,
                        year: year
                    })
                }).then(r => r.json()).then(data => {
                    console.log('[Vidking] Fallback save result:', data);
                }).catch(err => {
                    console.error('[Vidking] Fallback save error:', err);
                });
            }
        }, 5000);
    });
    
    // OpenSubtitles functionality
    document.getElementById('loadOpenSubtitles').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Buscando...';
        
        try {
            let params = `imdb_id=${imdbId}&type=${contentType}`;
            if (season) params += `&season=${season}`;
            if (episode) params += `&episode=${episode}`;
            
            const response = await fetch('/api/subtitles/search', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });
            
            const data = await response.json();
            console.log('[OpenSubtitles] Response:', data);
            
            if (data.error) throw new Error(data.message);
            
            renderSubtitles(data.subtitles || []);
            document.getElementById('subtitlesList').style.display = 'block';
            btn.textContent = 'Atualizar Legendas';
        } catch (e) {
            console.error('[OpenSubtitles] Error:', e);
            alert('Erro ao buscar legendas: ' + e.message);
            btn.textContent = 'Buscar Legendas no OpenSubtitles';
        } finally {
            btn.disabled = false;
        }
    });

    function renderSubtitles(subtitles) {
        const container = document.getElementById('subtitlesContainer');
        
        if (subtitles.length === 0) {
            container.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">Nenhuma legenda encontrada para este conteúdo.</p>';
            return;
        }
        
        // Ordenar: PT-BR primeiro, depois por downloads
        subtitles.sort((a, b) => {
            const isPtA = /pt-br|pob|por|portuguese|portugu/i.test(a.language + ' ' + a.language_name);
            const isPtB = /pt-br|pob|por|portuguese|portugu/i.test(b.language + ' ' + b.language_name);
            if (isPtA && !isPtB) return -1;
            if (!isPtA && isPtB) return 1;
            return (b.downloads || 0) - (a.downloads || 0);
        });
        
        container.innerHTML = subtitles.map(sub => `
            <div class="subtitle-item" data-lang="${sub.language_name.toLowerCase()}" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid #333; transition: background 0.2s;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.3rem;">${getFlag(sub.language)}</span>
                    <div>
                        <strong style="color: #fff;">${sub.language_name}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            ⬇️ ${sub.downloads || 0} downloads
                        </div>
                    </div>
                </div>
                <a href="${sub.url}" download class="btn" style="padding: 6px 16px; font-size: 0.85rem; background: var(--primary);">
                    Baixar .srt
                </a>
            </div>
        `).join('');
        
        // Hover effect
        container.querySelectorAll('.subtitle-item').forEach(item => {
            item.addEventListener('mouseenter', () => item.style.background = 'rgba(255,255,255,0.05)');
            item.addEventListener('mouseleave', () => item.style.background = 'transparent');
        });
    }

    function getFlag(lang) {
        const flags = {
            'pt-br': '🇧🇷', 'pob': '🇧🇷', 'por': '🇧🇷',
            'en': '🇺🇸', 'eng': '🇺🇸',
            'es': '🇪🇸', 'spa': '🇪🇸',
            'fr': '🇫🇷', 'fre': '🇫🇷',
            'de': '🇩🇪', 'ger': '🇩🇪',
            'it': '🇮🇹', 'ita': '🇮🇹',
            'nl': '🇳🇱', 'dut': '🇳🇱',
            'pl': '🇵🇱', 'pol': '🇵🇱',
            'ru': '🇷🇺', 'rus': '🇷🇺'
        };
        return flags[lang] || '🌐';
    }

    // Filtro de legendas por idioma
    document.getElementById('subtitleSearch').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.subtitle-item').forEach(item => {
            const lang = item.dataset.lang || '';
            const text = item.textContent.toLowerCase();
            item.style.display = (lang.includes(filter) || text.includes(filter)) ? 'flex' : 'none';
        });
    });
</script>
