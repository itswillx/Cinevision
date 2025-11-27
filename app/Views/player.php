<?php 
$isSeries = ($type ?? 'movie') === 'series';
$displayTitle = $meta['name'] ?? 'Reproduzindo';
$baseTitle = $meta['name'] ?? '';
if ($isSeries && isset($season) && isset($episode)) {
    $displayTitle .= " - S{$season}E{$episode}";
}
$poster = $meta['poster'] ?? '';
$year = $meta['year'] ?? '';
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

    <div class="player-wrapper" style="position: relative; padding-top: 56.25%; background: #000; margin-bottom: 20px; border-radius: 10px; overflow: hidden;">
        <video id="videoPlayer" controls preload="metadata" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
            <?php foreach ($subtitles as $sub): ?>
                <track label="<?php echo $sub['lang']; ?>" kind="subtitles" srclang="<?php echo $sub['lang']; ?>" src="<?php echo $sub['url']; ?>">
            <?php endforeach; ?>
        </video>
    </div>

    <div class="player-controls" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div class="stream-list" style="flex: 1; min-width: 300px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0;">Fontes Disponíveis <?php if ($qualityPref): ?><span style="font-size: 0.8rem; color: var(--text-muted);">(<?php echo strtoupper($qualityPref); ?>)</span><?php endif; ?></h3>
                <span id="streamCount" style="font-size: 0.85rem; color: var(--text-muted);"><?php echo count($streams); ?> fonte(s)</span>
            </div>
            
            <!-- Controles de Paginação -->
            <div id="paginationControls" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center; flex-wrap: wrap;">
                <button id="prevPage" class="btn" style="padding: 5px 15px; background: var(--card-bg);" disabled>← Anterior</button>
                <span id="pageInfo" style="font-size: 0.9rem; color: var(--text-muted);">Página 1 de 1</span>
                <button id="nextPage" class="btn" style="padding: 5px 15px; background: var(--card-bg);" disabled>Próxima →</button>
                <select id="itemsPerPage" class="form-control" style="width: auto; padding: 5px 10px;">
                    <option value="5">5 por página</option>
                    <option value="10" selected>10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">Todas</option>
                </select>
            </div>
            
            <div id="streamListContainer" class="list-group" style="background: var(--card-bg); border-radius: 8px; padding: 10px;">
                <?php if (empty($streams)): ?>
                    <p style="padding: 10px; color: var(--text-muted);">Nenhuma fonte encontrada<?php if ($qualityPref): ?> para a qualidade <?php echo strtoupper($qualityPref); ?>. Tente alterar nas <a href="/settings">configurações</a>.<?php endif; ?></p>
                <?php else: ?>
                    <!-- Streams serão renderizados via JavaScript para paginação -->
                <?php endif; ?>
            </div>
        </div>
        
        <div class="player-info" style="flex: 1; min-width: 300px;">
            <h3>Status</h3>
            <div id="statusMsg" style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                Selecione uma fonte para iniciar.
            </div>
            
            <!-- Seleção de Áudio -->
            <div style="margin-top: 20px;">
                <h3>Áudio</h3>
                <div id="audioSection" style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                    <select id="audioTrackSelect" class="form-control" style="margin-bottom: 10px;">
                        <option value="">Carregando faixas de áudio...</option>
                    </select>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <button id="audioPtBr" class="btn audio-pref-btn" style="flex: 1; padding: 8px; font-size: 0.85rem;" data-lang="pt">
                            🇧🇷 PT-BR
                        </button>
                        <button id="audioEn" class="btn audio-pref-btn" style="flex: 1; padding: 8px; font-size: 0.85rem;" data-lang="en">
                            🇺🇸 Inglês
                        </button>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h3>Legendas</h3>
                <div id="subtitleSection" style="padding: 15px; background: var(--card-bg); border-radius: 8px;">
                    <button id="loadSubtitles" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                        Carregar Legendas
                    </button>
                    <div id="subtitleList" style="display: none;">
                        <select id="subtitleSelect" class="form-control" style="margin-bottom: 10px;">
                            <option value="">Selecione uma legenda</option>
                        </select>
                        <button id="applySubtitle" class="btn" style="width: 100%;" disabled>
                            Aplicar Legenda
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (!$rdEnabled): ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(231, 76, 60, 0.1); border: 1px solid var(--primary); border-radius: 8px;">
                    <strong>Atenção:</strong> RealDebrid não configurado. Você não conseguirá assistir. 
                    <a href="/settings" style="text-decoration: underline;">Configurar agora</a>.
                </div>
            <?php endif; ?>
        </div>
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
    
    .audio-pref-btn {
        background: var(--card-bg);
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.2s;
    }
    .audio-pref-btn:hover {
        background: rgba(255,255,255,0.1);
    }
    .audio-pref-btn.active, #audioPtBr {
        background: var(--primary);
    }
    .stream-item:hover {
        background: rgba(255,255,255,0.05) !important;
    }
    #paginationControls button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    #paginationControls button:not(:disabled):hover {
        background: rgba(255,255,255,0.1);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
    const rdEnabled = <?php echo $rdEnabled ? 'true' : 'false'; ?>;
    const qualityPref = '<?php echo htmlspecialchars($qualityPref ?? ''); ?>';
    // Nota: Token RD não é exposto no frontend por segurança - usado apenas no backend
    const video = document.getElementById('videoPlayer');
    const imdbId = '<?php echo $imdbId ?? ''; ?>';
    const contentType = '<?php echo $type ?? 'movie'; ?>';
    const title = '<?php echo htmlspecialchars($meta['name'] ?? ''); ?>';
    const poster = '<?php echo htmlspecialchars($meta['poster'] ?? ''); ?>';
    const year = '<?php echo htmlspecialchars($meta['year'] ?? ''); ?>';
    const season = '<?php echo $season ?? ''; ?>';
    const episode = '<?php echo $episode ?? ''; ?>';
    const resumeTimeFromUrl = <?php echo isset($resumeTime) && $resumeTime ? (int)$resumeTime : 'null'; ?>; // Time from URL parameter
    const streamHashFromUrl = '<?php echo htmlspecialchars($streamHash ?? ''); ?>'; // Stream hash from URL for quick resume
    
    // Estado de paginação
    let allStreams = [];
    let currentPage = 1;
    let itemsPerPage = 10;
    let currentHls = null;
    let preferredAudioLang = 'pt'; // Preferência padrão: português
    
    // Estado de progresso
    let currentStreamIndex = 0;
    let currentStreamUrl = '';
    let currentStreamInfohash = '';
    let currentStreamTitle = '';
    let progressSaveInterval = null;
    let lastSavedTime = 0;

    console.log('Player script loaded. RD Enabled:', rdEnabled);
    
    // Função para salvar progresso
    function saveCurrentProgress(forceSync = false, useBeacon = false) {
        if (!video.currentTime || video.currentTime < 5) return; // Não salvar se menos de 5 segundos
        if (!forceSync && Math.abs(video.currentTime - lastSavedTime) < 10) return; // Não salvar se diferença < 10s
        
        lastSavedTime = video.currentTime;
        
        const progressData = {
            currentTime: video.currentTime,
            duration: video.duration,
            streamIndex: currentStreamIndex,
            streamInfohash: currentStreamInfohash,
            streamUrl: currentStreamUrl,
            streamTitle: currentStreamTitle,
            season: season || null,
            episode: episode || null,
            type: contentType,
            title: title,
            poster: poster,
            year: year
        };
        
        console.log('[Player] Salvando progresso:', Math.round(video.currentTime) + 's', 'stream:', currentStreamIndex);
        
        if (typeof CineVision !== 'undefined') {
            if (useBeacon) {
                // Usar beacon para garantir envio ao fechar página
                CineVision.saveProgressBeacon(imdbId, progressData);
            } else if (forceSync) {
                // Forçar sync imediato (para pause)
                CineVision.saveProgressNow(imdbId, progressData);
            } else {
                CineVision.savePlayerProgress(imdbId, progressData);
            }
        }
    }
    
    // Função para carregar progresso salvo
    function loadSavedProgress() {
        if (typeof CineVision === 'undefined') return null;
        return CineVision.getPlayerProgress(imdbId, season || null, episode || null);
    }
    
    // Função para oferecer retomar de onde parou
    function offerResumePlayback(savedProgress) {
        if (!savedProgress || !savedProgress.currentTime) return;
        
        const savedTime = savedProgress.currentTime;
        const percent = savedProgress.percent || 0;
        
        // Se assistiu mais de 95%, não oferecer retomar
        if (percent > 95) return;
        
        // Formatar tempo
        const formattedTime = CineVision.formatTime(savedTime);
        
        // Criar modal de retomar
        const resumeModal = document.createElement('div');
        resumeModal.id = 'resumeModal';
        resumeModal.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div style="background: var(--card-bg); padding: 30px; border-radius: 12px; text-align: center; max-width: 400px;">
                    <h3 style="margin-bottom: 15px;">Continuar assistindo?</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Você parou em ${formattedTime} (${percent}%)</p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button id="resumeYes" class="btn btn-primary" style="padding: 10px 25px;">Continuar</button>
                        <button id="resumeNo" class="btn" style="padding: 10px 25px; background: var(--card-bg); border: 1px solid var(--text-muted);">Começar do início</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(resumeModal);
        
        document.getElementById('resumeYes').addEventListener('click', () => {
            video.currentTime = savedTime;
            resumeModal.remove();
        });
        
        document.getElementById('resumeNo').addEventListener('click', () => {
            resumeModal.remove();
        });
    }
    
    // Configurar eventos de progresso no vídeo
    function setupProgressTracking() {
        // Salvar progresso a cada update (CineVision faz debounce internamente)
        video.addEventListener('timeupdate', () => {
            saveCurrentProgress(false, false);
        });
        
        // Salvar ao pausar (forçar sync imediato)
        video.addEventListener('pause', () => {
            saveCurrentProgress(true, false);
        });
        
        // Salvar ao terminar
        video.addEventListener('ended', () => {
            saveCurrentProgress(true, false);
        });
        
        // Salvar ao fechar página (usar sendBeacon para garantir envio)
        window.addEventListener('beforeunload', () => {
            saveCurrentProgress(true, true); // useBeacon = true
        });
        
        // Também salvar ao sair da aba (usar beacon pois pode ser fechamento)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                saveCurrentProgress(true, true); // useBeacon = true
            }
        });
        
        // Verificar se há progresso salvo quando o vídeo carregar
        video.addEventListener('loadedmetadata', () => {
            // Se veio com tempo da URL (clicou em "Continuar"), pular direto
            if (resumeTimeFromUrl && resumeTimeFromUrl > 0) {
                console.log('[Player] Resumindo do tempo da URL:', resumeTimeFromUrl);
                video.currentTime = resumeTimeFromUrl;
                return;
            }
            
            // Senão, verificar progresso salvo e oferecer modal
            const savedProgress = loadSavedProgress();
            if (savedProgress && savedProgress.currentTime > 30) {
                offerResumePlayback(savedProgress);
            }
        });
        
        // Salvar quando o vídeo começa a reproduzir (para registrar a fonte usada)
        video.addEventListener('playing', () => {
            // Salvar imediatamente a fonte sendo usada
            if (video.currentTime >= 5) {
                console.log('[Player] Vídeo reproduzindo, salvando fonte:', currentStreamIndex, currentStreamInfohash);
                saveCurrentProgress(true, false);
            }
        });
        
        // Salvar periodicamente a cada 30 segundos
        video.addEventListener('timeupdate', () => {
            if (video.currentTime > 0 && Math.floor(video.currentTime) % 30 === 0) {
                saveCurrentProgress(true, false);
            }
        });
    }
    
    // Inicializar tracking de progresso
    setupProgressTracking();

    window.onerror = function(msg, url, line) {
        console.error('Global error:', msg, url, line);
        const status = document.getElementById('statusMsg');
        if (status) status.innerHTML = '<span style="color: #e74c3c;">Erro de Script:</span> ' + msg;
        return false;
    };
    
    // Inicializar streams e paginação - busca via frontend (Torrentio)
    async function initStreams() {
        const container = document.getElementById('streamListContainer');
        const countEl = document.getElementById('streamCount');
        container.innerHTML = '<p style="padding: 10px; color: var(--text-muted);">🔄 Buscando fontes em português...</p>';
        
        try {
            // Buscar streams em português primeiro, depois em inglês
            const ptStreams = await fetchTorrentioStreams('portuguese');
            
            container.innerHTML = '<p style="padding: 10px; color: var(--text-muted);">🔄 Buscando fontes adicionais...</p>';
            const enStreams = await fetchTorrentioStreams('english');
            
            // Mesclar streams sem duplicatas (PT-BR primeiro)
            let streams = mergeStreams(ptStreams, enStreams);
            
            // Filtrar fontes de baixa qualidade
            streams = filterLowQualitySources(streams);
            
            // Ordenar streams por qualidade preferida do usuário
            if (qualityPref && streams.length > 0) {
                streams = sortStreamsByQuality(streams, qualityPref);
            }
            
            allStreams = streams;
            console.log('Total streams loaded:', allStreams.length);
            
            if (countEl) countEl.textContent = allStreams.length + ' fonte(s)';
            renderStreams();
            setupPagination();
            
            // Verificar se deve auto-reproduzir stream salvo
            checkAutoPlaySavedStream();
            
        } catch (e) {
            console.error('Error loading streams:', e);
            container.innerHTML = `<p style="padding: 10px; color: #e74c3c;">Erro ao buscar fontes: ${e.message}</p>`;
        }
    }
    
    // Verifica e auto-reproduz o stream salvo (quando veio de "Continuar Assistindo")
    async function checkAutoPlaySavedStream() {
        // Se não veio com tempo da URL, não auto-reproduzir
        if (!resumeTimeFromUrl || resumeTimeFromUrl <= 0) return;
        
        // Primeiro: usar o stream hash da URL se disponível (mais rápido)
        if (streamHashFromUrl && allStreams.length > 0) {
            console.log('[Player] Usando stream hash da URL:', streamHashFromUrl);
            const streamElement = document.querySelector(`.stream-item[data-infohash="${streamHashFromUrl}"]`);
            if (streamElement) {
                console.log('[Player] Encontrado stream por hash da URL, auto-reproduzindo');
                playStream(streamElement, parseInt(streamElement.dataset.index) || 0);
                return;
            }
        }
        
        // Se não encontrou pelo hash da URL, tentar pelo progresso salvo
        // Aguardar sync do progresso do servidor (máximo 2 segundos)
        let attempts = 0;
        let savedProgress = null;
        
        while (attempts < 4) {
            savedProgress = loadSavedProgress();
            if (savedProgress && (savedProgress.streamInfohash || savedProgress.stream_infohash || savedProgress.streamIndex !== undefined)) {
                break;
            }
            await new Promise(r => setTimeout(r, 500));
            attempts++;
        }
        
        if (savedProgress) {
            const savedInfohash = savedProgress.streamInfohash || savedProgress.stream_infohash || '';
            const savedIndex = savedProgress.streamIndex ?? savedProgress.stream_index ?? 0;
            
            console.log('[Player] Buscando stream salvo:', { savedInfohash, savedIndex });
            
            // Tentar encontrar o stream pelo infohash
            if (savedInfohash && allStreams.length > 0) {
                const streamElement = document.querySelector(`.stream-item[data-infohash="${savedInfohash}"]`);
                if (streamElement) {
                    console.log('[Player] Encontrado stream por infohash salvo, auto-reproduzindo');
                    playStream(streamElement, parseInt(streamElement.dataset.index) || 0);
                    return;
                }
            }
            
            // Fallback: tentar pelo índice salvo
            if (savedIndex >= 0 && savedIndex < allStreams.length) {
                const streamElement = document.querySelector(`.stream-item[data-index="${savedIndex}"]`);
                if (streamElement) {
                    console.log('[Player] Usando stream do índice salvo:', savedIndex);
                    playStream(streamElement, savedIndex);
                    return;
                }
            }
        }
        
        // Último fallback: primeira fonte
        console.log('[Player] Fallback: reproduzindo primeira fonte');
        autoPlayFirstStream();
    }
    
    // Auto-reproduz a primeira fonte disponível
    function autoPlayFirstStream() {
        const firstStream = document.querySelector('.stream-item');
        if (firstStream) {
            playStream(firstStream, 0);
        }
    }
    
    // Buscar streams do Torrentio com configuração específica
    async function fetchTorrentioStreams(language) {
        // Construir configuração do Torrentio
        // NOTA: NÃO incluímos o token RD aqui por segurança
        // O RD será usado apenas no backend para resolver os streams
        let configParts = [
            'sort=seeders',                    // Ordenar por seeders
            'language=' + language,            // Filtro de idioma
            'limit=50',                        // Limite de resultados
            'qualityfilter=cam,screener,tc,ts' // Excluir qualidades ruins
        ];
        
        // Opção para não mostrar links de download (só streaming)
        configParts.push('debridoptions=nodownloadlinks');
        
        const config = configParts.join('|');
        let torrentioUrl = 'https://torrentio.strem.fun/' + encodeURIComponent(config);
        
        if (contentType === 'series' && season && episode) {
            torrentioUrl += `/stream/series/${imdbId}:${season}:${episode}.json`;
        } else {
            torrentioUrl += `/stream/movie/${imdbId}.json`;
        }
        
        console.log(`Fetching ${language} streams:`, torrentioUrl);
        
        const response = await fetch(torrentioUrl);
        
        if (!response.ok) {
            console.warn(`Failed to fetch ${language} streams:`, response.status);
            return [];
        }
        
        const data = await response.json();
        const streams = data.streams || [];
        
        // Marcar idioma da busca
        streams.forEach(s => s._searchLang = language);
        
        console.log(`Found ${streams.length} ${language} streams`);
        return streams;
    }
    
    // Mesclar streams removendo duplicatas
    function mergeStreams(ptStreams, enStreams) {
        const merged = [];
        const seenHashes = new Set();
        
        // Primeiro: streams em português (prioridade)
        for (const stream of ptStreams) {
            const hash = stream.infoHash || (stream.behaviorHints?.infoHash) || '';
            const key = hash || (stream.url ? btoa(stream.url).substring(0, 20) : Math.random());
            
            if (!seenHashes.has(key)) {
                stream._priority = 'pt';
                merged.push(stream);
                seenHashes.add(key);
            }
        }
        
        // Segundo: streams em inglês (sem duplicatas)
        for (const stream of enStreams) {
            const hash = stream.infoHash || (stream.behaviorHints?.infoHash) || '';
            const key = hash || (stream.url ? btoa(stream.url).substring(0, 20) : Math.random());
            
            if (!seenHashes.has(key)) {
                stream._priority = 'en';
                merged.push(stream);
                seenHashes.add(key);
            }
        }
        
        return merged;
    }
    
    // Filtrar fontes de baixa qualidade
    function filterLowQualitySources(streams) {
        const blacklist = ['cam', 'camrip', 'hdcam', 'telesync', 'hdts', 'telecine', 'screener', 'dvdscr', 'workprint', 'sample', 'trailer'];
        
        return streams.filter(stream => {
            const title = (stream.title || '').toLowerCase();
            const name = (stream.name || '').toLowerCase();
            const combined = title + ' ' + name;
            
            for (const term of blacklist) {
                if (combined.includes(term)) {
                    return false;
                }
            }
            return true;
        });
    }
    
    // Função para ordenar streams pela qualidade preferida
    function sortStreamsByQuality(streams, preferredQuality) {
        const qualityOrder = {
            '4k': ['4k', '2160p', '1080p', '720p', '480p'],
            '1080p': ['1080p', '4k', '2160p', '720p', '480p'],
            '720p': ['720p', '1080p', '4k', '2160p', '480p'],
            '480p': ['480p', '720p', '1080p', '4k', '2160p']
        };
        
        const order = qualityOrder[preferredQuality.toLowerCase()] || qualityOrder['1080p'];
        
        return streams.sort((a, b) => {
            const titleA = (a.title || '').toLowerCase();
            const titleB = (b.title || '').toLowerCase();
            
            let scoreA = order.length;
            let scoreB = order.length;
            
            // Verificar qualidade
            for (let i = 0; i < order.length; i++) {
                if (titleA.includes(order[i])) {
                    scoreA = i;
                    break;
                }
            }
            for (let i = 0; i < order.length; i++) {
                if (titleB.includes(order[i])) {
                    scoreB = i;
                    break;
                }
            }
            
            // Priorizar português/dublado
            const hasPtA = /dublado|pt-br|ptbr|portuguese|nacional|dual.?audio/i.test(titleA);
            const hasPtB = /dublado|pt-br|ptbr|portuguese|nacional|dual.?audio/i.test(titleB);
            
            if (hasPtA && !hasPtB) return -1;
            if (!hasPtA && hasPtB) return 1;
            
            return scoreA - scoreB;
        });
    }
    
    // Renderizar streams da página atual
    function renderStreams() {
        const container = document.getElementById('streamListContainer');
        if (!container) return;
        
        if (allStreams.length === 0) {
            container.innerHTML = '<p style="padding: 10px; color: var(--text-muted);">Nenhuma fonte encontrada.</p>';
            return;
        }
        
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, allStreams.length);
        const pageStreams = allStreams.slice(start, end);
        
        container.innerHTML = pageStreams.map((stream, idx) => {
            const globalIdx = start + idx;
            const streamTitle = stream.title || '';
            const streamName = stream.name || 'Torrentio';
            
            // Quality badge
            let qualityBadge = '';
            let badgeColor = '#666';
            if (/4k|2160p/i.test(streamTitle)) {
                qualityBadge = '4K';
                badgeColor = '#f5c518';
            } else if (/1080p/i.test(streamTitle)) {
                qualityBadge = '1080p';
                badgeColor = 'var(--primary)';
            } else if (/720p/i.test(streamTitle)) {
                qualityBadge = '720p';
                badgeColor = '#3498db';
            } else if (/480p|dvdrip/i.test(streamTitle)) {
                qualityBadge = '480p';
                badgeColor = '#95a5a6';
            }
            
            // Seeders
            let seeders = '';
            const seedMatch = streamTitle.match(/👤\s*(\d+)/u);
            if (seedMatch) seeders = seedMatch[1];
            
            // Language badge
            let langBadge = '';
            const titleLower = streamTitle.toLowerCase();
            if (/dublado|pt-br|ptbr|portuguese|nacional|dual.?audio/i.test(titleLower)) {
                langBadge = '<span style="background: #27ae60; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">PT-BR</span>';
            } else if (/english|eng|legendado/i.test(titleLower)) {
                langBadge = '<span style="background: #3498db; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">EN</span>';
            }
            
            // Get infoHash and URL
            const infoHash = stream.infoHash || (stream.behaviorHints?.infoHash || '');
            const streamUrl = stream.url || stream.externalUrl || '';
            
            return `
                <div class="stream-item" 
                     data-index="${globalIdx}"
                     data-infohash="${escapeAttr(infoHash)}"
                     data-url="${escapeAttr(streamUrl)}"
                     data-title="${escapeAttr(streamTitle)}"
                     style="padding: 12px; border-bottom: 1px solid #333; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: background 0.2s;" 
                     onclick="playStream(this)">
                    <div style="flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 60px;">
                        ${qualityBadge ? `<span style="background: ${badgeColor}; color: ${qualityBadge === '4K' ? '#000' : '#fff'}; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">${qualityBadge}</span>` : ''}
                        ${seeders ? `<span style="color: #4CAF50; font-size: 0.75rem;">👤 ${seeders}</span>` : ''}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: bold; color: var(--primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(streamName)}${langBadge}
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(streamTitle)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        updatePaginationInfo();
    }
    
    // Configurar controles de paginação
    function setupPagination() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const perPageSelect = document.getElementById('itemsPerPage');
        
        prevBtn?.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderStreams();
            }
        });
        
        nextBtn?.addEventListener('click', () => {
            const totalPages = Math.ceil(allStreams.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderStreams();
            }
        });
        
        perPageSelect?.addEventListener('change', (e) => {
            itemsPerPage = parseInt(e.target.value) || 10;
            currentPage = 1;
            renderStreams();
        });
        
        updatePaginationInfo();
    }
    
    // Atualizar informação de paginação
    function updatePaginationInfo() {
        const totalPages = Math.ceil(allStreams.length / itemsPerPage) || 1;
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        
        if (pageInfo) pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    function escapeAttr(text) {
        return (text || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function loadVideo(url) {
        console.log('Loading video:', url);
        const status = document.getElementById('statusMsg');
        
        // Destruir instância HLS anterior
        if (currentHls) {
            currentHls.destroy();
            currentHls = null;
        }
        
        // Reset video element
        video.pause();
        video.currentTime = 0;
        
        // Reset audio select
        const audioSelect = document.getElementById('audioTrackSelect');
        if (audioSelect) {
            audioSelect.innerHTML = '<option value="">Carregando faixas de áudio...</option>';
        }
        
        if (Hls.isSupported() && url.endsWith('.m3u8')) {
            const hls = new Hls({
                debug: false,
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });
            currentHls = hls;
            
            hls.loadSource(url);
            hls.attachMedia(video);
            
            hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
                console.log('Manifest parsed, available audio tracks:', hls.audioTracks);
                
                // Popular dropdown de áudio
                populateAudioTracks(hls);
                
                // Selecionar faixa de áudio preferida
                selectPreferredAudioTrack(hls);
                
                // Ensure video is not muted
                video.muted = false;
                video.volume = 1;
                
                video.play().catch(e => {
                    console.log('Autoplay prevented:', e);
                    status.innerHTML = '<span style="color: #f39c12;">Clique no vídeo para reproduzir</span>';
                    video.addEventListener('click', function() {
                        video.play();
                    }, { once: true });
                });
            });
            
            hls.on(Hls.Events.ERROR, function(event, data) {
                console.error('HLS error:', data);
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            status.innerHTML = '<span style="color: #e74c3c;">Erro de rede.</span> Tentando reconectar...';
                            hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            status.innerHTML = '<span style="color: #e74c3c;">Erro de mídia.</span> Tentando recuperar...';
                            hls.recoverMediaError();
                            break;
                        default:
                            status.innerHTML = '<span style="color: #e74c3c;">Erro fatal.</span> Tente outra fonte.';
                            hls.destroy();
                            break;
                    }
                }
            });
            
            hls.on(Hls.Events.AUDIO_TRACK_SWITCHED, function(event, data) {
                console.log('Audio track switched to:', data.id);
                const select = document.getElementById('audioTrackSelect');
                if (select) select.value = data.id;
            });
            
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.muted = false;
            video.volume = 1;
            // Vídeo nativo Safari - sem múltiplas faixas de áudio
            updateAudioSelectForDirectVideo();
            video.addEventListener('loadedmetadata', function() {
                console.log('Metadata loaded, audio tracks:', video.audioTracks);
                video.play().catch(e => console.log('Play error:', e));
            });
        } else {
            video.src = url;
            video.muted = false;
            video.volume = 1;
            // Vídeo direto MP4/MKV - sem múltiplas faixas de áudio disponíveis via JS
            updateAudioSelectForDirectVideo();
            video.addEventListener('loadedmetadata', function() {
                console.log('Direct video loaded');
                video.play().catch(e => console.log('Play error:', e));
            });
        }
    }
    
    // Popular dropdown de faixas de áudio
    function populateAudioTracks(hls) {
        const select = document.getElementById('audioTrackSelect');
        if (!select) return;
        
        if (!hls || !hls.audioTracks || hls.audioTracks.length === 0) {
            select.innerHTML = '<option value="">Áudio padrão do vídeo</option>';
            return;
        }
        
        select.innerHTML = hls.audioTracks.map((track, idx) => {
            const name = track.name || track.lang || `Faixa ${idx + 1}`;
            const lang = track.lang || '';
            let label = name;
            if (lang && lang !== name) {
                label += ` (${lang.toUpperCase()})`;
            }
            return `<option value="${idx}">${label}</option>`;
        }).join('');
        
        select.value = hls.audioTrack;
        
        // Listener para trocar faixa
        select.onchange = function() {
            const trackIdx = parseInt(this.value);
            if (!isNaN(trackIdx) && currentHls) {
                currentHls.audioTrack = trackIdx;
                console.log('Switching audio track to:', trackIdx);
            }
        };
    }
    
    // Selecionar faixa de áudio preferida
    function selectPreferredAudioTrack(hls) {
        if (!hls.audioTracks || hls.audioTracks.length === 0) return;
        
        const tracks = hls.audioTracks;
        let preferredIdx = 0;
        
        // Procurar por faixa em português primeiro
        if (preferredAudioLang === 'pt') {
            for (let i = 0; i < tracks.length; i++) {
                const lang = (tracks[i].lang || '').toLowerCase();
                const name = (tracks[i].name || '').toLowerCase();
                if (lang.includes('pt') || lang.includes('por') || name.includes('portugu') || name.includes('brasil')) {
                    preferredIdx = i;
                    break;
                }
            }
        } else if (preferredAudioLang === 'en') {
            for (let i = 0; i < tracks.length; i++) {
                const lang = (tracks[i].lang || '').toLowerCase();
                const name = (tracks[i].name || '').toLowerCase();
                if (lang.includes('en') || lang.includes('eng') || name.includes('english')) {
                    preferredIdx = i;
                    break;
                }
            }
        }
        
        hls.audioTrack = preferredIdx;
        const select = document.getElementById('audioTrackSelect');
        if (select) select.value = preferredIdx;
    }
    
    // Botões de preferência de áudio
    document.querySelectorAll('.audio-pref-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            preferredAudioLang = this.dataset.lang;
            document.querySelectorAll('.audio-pref-btn').forEach(b => b.style.background = 'var(--card-bg)');
            this.style.background = 'var(--primary)';
            
            if (currentHls) {
                selectPreferredAudioTrack(currentHls);
            }
        });
    });
    
    // Atualizar select de áudio para vídeos diretos (não-HLS)
    function updateAudioSelectForDirectVideo() {
        const select = document.getElementById('audioTrackSelect');
        if (select) {
            select.innerHTML = '<option value="">Áudio embutido no vídeo</option>';
        }
    }
    
    // Salva a seleção da fonte imediatamente
    function saveStreamSelection() {
        const progressData = {
            currentTime: video.currentTime || 0,
            duration: video.duration || 0,
            streamIndex: currentStreamIndex,
            streamInfohash: currentStreamInfohash,
            streamUrl: currentStreamUrl,
            streamTitle: currentStreamTitle,
            season: season || null,
            episode: episode || null,
            type: contentType,
            title: title,
            poster: poster,
            year: year
        };
        
        if (typeof CineVision !== 'undefined') {
            console.log('[Player] Salvando seleção de fonte no servidor...');
            CineVision.saveProgressNow(imdbId, progressData);
        }
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', initStreams);

    async function playStream(element, streamIndex = 0) {
        const infoHash = element.dataset.infohash;
        const streamUrl = element.dataset.url;
        const streamTitle = element.dataset.title;
        
        // Salvar estado do stream atual para o progresso
        currentStreamIndex = parseInt(element.dataset.index) || streamIndex;
        currentStreamInfohash = infoHash || '';
        currentStreamUrl = streamUrl || '';
        currentStreamTitle = streamTitle || '';
        
        console.log('[Player] Fonte selecionada:', currentStreamIndex, 'hash:', currentStreamInfohash?.substring(0, 20));
        
        // Salvar seleção da fonte imediatamente (com tempo atual ou 0)
        saveStreamSelection();

        document.querySelectorAll('.stream-item').forEach(el => el.style.background = 'transparent');
        element.style.background = '#333';
        
        const status = document.getElementById('statusMsg');
        const log = (msg) => {
            console.log('[Player]', msg);
            status.innerHTML = msg;
        };

        try {
            console.log('Stream data:', { infoHash, streamUrl, title });

            // Check if we have a Torrentio resolve URL (RD cached stream)
            if (streamUrl && streamUrl.includes('torrentio.strem.fun/resolve')) {
                log('Iniciando reprodução via Torrentio...');
                // Torrentio resolve URLs are already the final stream URLs
                // No need to resolve or process them further
                loadVideo(streamUrl);
                return;
            }
            
            // Check if we have a direct HTTP URL (other hosters)
            if (streamUrl && streamUrl.startsWith('http')) {
                log('Obtendo link direto...');
                
                const unrestrictRes = await fetch('/api/rd/unrestrict', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `link=${encodeURIComponent(streamUrl)}`
                }).then(r => r.json());

                console.log('Unrestrict response:', unrestrictRes);

                if (unrestrictRes.error) {
                    throw new Error(unrestrictRes.message || JSON.stringify(unrestrictRes));
                }

                log('Iniciando reprodução...');
                loadVideo(unrestrictRes.download);
                return;
            }

            // Fallback to magnet/infoHash method
            if (!infoHash) {
                throw new Error('Nenhum link ou hash disponível para este stream. Verifique se o RealDebrid está configurado corretamente no Torrentio.');
            }

            if (!rdEnabled) {
                alert('Configure o RealDebrid primeiro!');
                return;
            }

            log('Adicionando torrent ao RealDebrid...');

            // 1. Add Magnet
            const magnet = `magnet:?xt=urn:btih:${infoHash}`;
            const addRes = await fetch('/api/rd/add-magnet', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `magnet=${encodeURIComponent(magnet)}`
            }).then(r => r.json());

            if (addRes.error) throw new Error(addRes.message || 'Erro ao adicionar magnet');
            console.log('Magnet added:', addRes);

            log('Selecionando arquivo...');
            
            // 2. Select File (Auto select 'all')
            await fetch('/api/rd/select-file', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `torrent_id=${addRes.id}&file_id=all`
            }).then(r => r.json());

            // Wait loop for status 'downloaded'
            log('Verificando status do torrent...');
            let attempts = 0;
            let infoRes = null;
            
            while (attempts < 10) {
                await new Promise(r => setTimeout(r, 1000));
                infoRes = await fetch(`/api/rd/torrent-info?id=${addRes.id}`).then(r => r.json());
                console.log('Torrent Info:', infoRes);
                
                if (infoRes.error) throw new Error(infoRes.message || 'Erro ao obter info do torrent');
                
                if (infoRes.status === 'downloaded') {
                    break;
                } else if (infoRes.status === 'downloading') {
                    log(`Baixando no RealDebrid... ${infoRes.progress}%`);
                } else if (infoRes.status === 'magnet_error') {
                    throw new Error('Erro no magnet link (RealDebrid)');
                }
                
                attempts++;
            }

            if (infoRes.status !== 'downloaded') {
                // Even if not fully downloaded, we might be able to stream if links are available?
                // RD usually provides links instantly if cached. If not, we wait.
                // If it takes too long, we might need to tell user.
                if (infoRes.links && infoRes.links.length > 0) {
                    log('Links disponíveis, tentando reproduzir...');
                } else {
                    throw new Error('Torrent ainda não está pronto. Tente novamente em alguns instantes.');
                }
            }

            // Find the largest video file
            const videoExtensions = ['mp4', 'mkv', 'avi', 'mov'];
            let bestFileIndex = -1;
            let maxSize = 0;

            if (infoRes.files && infoRes.files.length > 0) {
                infoRes.files.forEach((file, index) => {
                    const ext = file.path.split('.').pop().toLowerCase();
                    if (videoExtensions.includes(ext) && file.bytes > maxSize) {
                        maxSize = file.bytes;
                        bestFileIndex = index;
                    }
                });
            }

            let linkToUnrestrict = '';
            if (infoRes.links && infoRes.links.length > 0) {
                // Map file index to link index. 
                // If we selected 'all', indices should match.
                if (bestFileIndex >= 0 && bestFileIndex < infoRes.links.length) {
                    linkToUnrestrict = infoRes.links[bestFileIndex];
                } else {
                    linkToUnrestrict = infoRes.links[0];
                }
            } else {
                throw new Error('Nenhum link gerado.');
            }

            log('Gerando link direto...');
            console.log('Unrestricting link:', linkToUnrestrict);

            // 4. Unrestrict Link
            const unrestrictRes = await fetch('/api/rd/unrestrict-link', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `link=${encodeURIComponent(linkToUnrestrict)}`
            }).then(r => r.json());

            if (unrestrictRes.error) throw new Error(unrestrictRes.message || 'Erro ao gerar link direto');
            console.log('Unrestrict response:', unrestrictRes);

            // 5. Play
            log('Pronto para assistir: ' + (infoRes.filename || 'Video'));
            
            const finalUrl = unrestrictRes.download;
            console.log('Stream URL:', finalUrl);
            
            loadVideo(finalUrl);

        } catch (e) {
            log('Erro: ' + e.message);
            console.error(e);
        }
    }

    // Subtitle functionality
    let availableSubtitles = [];

    document.getElementById('loadSubtitles').addEventListener('click', async function() {
        if (!imdbId) {
            alert('IMDB ID não encontrado');
            return;
        }

        const button = this;
        button.disabled = true;
        button.textContent = 'Carregando...';

        try {
            let bodyParams = `imdb_id=${imdbId}&title=${encodeURIComponent(title)}&type=${contentType}`;
            if (season) bodyParams += `&season=${season}`;
            if (episode) bodyParams += `&episode=${episode}`;
            
            const response = await fetch('/api/subtitles/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: bodyParams
            });

            const data = await response.json();
            console.log('Subtitles response:', data);

            if (data.error) {
                throw new Error(data.message);
            }

            availableSubtitles = data.subtitles || [];
            populateSubtitleDropdown();
            
            document.getElementById('subtitleList').style.display = 'block';
            button.textContent = 'Atualizar Legendas';

        } catch (error) {
            console.error('Error loading subtitles:', error);
            alert('Erro ao carregar legendas: ' + error.message);
        } finally {
            button.disabled = false;
        }
    });

    function populateSubtitleDropdown() {
        const select = document.getElementById('subtitleSelect');
        const applyButton = document.getElementById('applySubtitle');
        
        // Clear existing options except the first one
        select.innerHTML = '<option value="">Selecione uma legenda</option>';
        
        availableSubtitles.forEach((subtitle, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = subtitle.language_name;
            select.appendChild(option);
        });

        // Enable/disable apply button based on selection
        select.addEventListener('change', function() {
            applyButton.disabled = this.value === '';
        });
    }

    document.getElementById('applySubtitle').addEventListener('click', function() {
        const selectedIndex = document.getElementById('subtitleSelect').value;
        
        if (selectedIndex === '') {
            return;
        }

        const subtitle = availableSubtitles[selectedIndex];
        applySubtitle(subtitle);
    });

    function applySubtitle(subtitle) {
        // Remove existing subtitle tracks
        const existingTracks = video.querySelectorAll('track[data-custom="true"]');
        existingTracks.forEach(track => track.remove());

        // Create and add new track element
        const track = document.createElement('track');
        track.kind = 'subtitles';
        track.label = subtitle.language_name;
        track.srclang = subtitle.language;
        track.src = subtitle.url;
        track.setAttribute('data-custom', 'true');
        
        video.appendChild(track);
        
        // Enable the new track
        track.track.mode = 'showing';
        
        console.log('Subtitle applied:', subtitle);
        
        // Update button text
        document.getElementById('applySubtitle').textContent = `Legenda Ativada: ${subtitle.language_name}`;
    }
</script>
