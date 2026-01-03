<div class="container" style="margin-top: 40px;">
    <!-- Seção: Continuar Assistindo (carregada via JS) -->
    <div id="continueWatchingSection" style="display: none; margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px; border-left: 4px solid #f5c518; padding-left: 10px;">Continuar Assistindo</h2>
        <div id="continueWatchingGrid" class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <!-- Cards carregados via JavaScript -->
        </div>
    </div>
    
    <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">Minha Lista</h2>

    <?php if (empty($favorites)): ?>
        <div style="text-align: center; padding: 50px; color: var(--text-muted);">
            <p style="font-size: 1.2rem;">Você ainda não adicionou nenhum favorito.</p>
            <a href="/" class="btn btn-primary" style="margin-top: 20px; display: inline-block;">Explorar Filmes</a>
        </div>
    <?php else: ?>
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
            <?php foreach ($favorites as $fav): ?>
                <div class="card-wrapper" style="position: relative;">
                    <a href="/watch?id=<?php echo $fav['imdb_id']; ?>&type=<?php echo $fav['type']; ?>" class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                        <div class="card-image-wrapper">
                            <img src="<?php echo $fav['poster']; ?>" alt="<?php echo $fav['title']; ?>">
                            <!-- Barra de Progresso -->
                            <div class="progress-bar" data-progress-id="<?php echo $fav['imdb_id']; ?>"></div>
                            <span class="progress-text" data-text-id="<?php echo $fav['imdb_id']; ?>"></span>
                        </div>
                        <div style="padding: 10px;">
                            <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $fav['title']; ?></h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $fav['year']; ?></span>
                        </div>
                    </a>
                    <button onclick="removeFavorite('<?php echo $fav['imdb_id']; ?>', this)" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); border: none; color: white; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; z-index: 20;">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .card-image-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 2/3;
        overflow: hidden;
    }
    .card-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 5px;
        background: #e50914;
        width: 0%;
        transition: width 0.3s ease;
    }
    .progress-text {
        position: absolute;
        bottom: 12px;
        left: 8px;
        background: rgba(0,0,0,0.85);
        color: #fff;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        display: none;
    }
    .progress-text.has-progress {
        display: block;
    }
    .card:hover {
        transform: scale(1.03);
    }
    
    /* Continue Watching Cards */
    .continue-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .continue-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .continue-card img {
        transition: opacity 0.2s;
    }
    .continue-card:hover img {
        opacity: 0.9;
    }
    
    /* Responsive for continue watching */
    @media (max-width: 600px) {
        .continue-card {
            flex-direction: column !important;
        }
        .continue-card > div:first-child {
            width: 100% !important;
            height: 120px !important;
        }
        .continue-card > div:first-child img {
            height: 120px !important;
        }
    }
</style>

<script>
async function removeFavorite(imdbId, btn) {
    if (!confirm('Remover dos favoritos?')) return;
    
    try {
        const res = await fetch('/api/favorites/remove', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `imdb_id=${imdbId}`
        }).then(r => r.json());

        if (res.success) {
            btn.closest('.card-wrapper').remove();
            // Também remover do localStorage
            if (typeof CineVision !== 'undefined') {
                CineVision.removeFavorite(imdbId);
            }
        } else {
            alert('Erro ao remover.');
        }
    } catch (e) {
        console.error(e);
    }
}

// Atualizar barras de progresso
document.addEventListener('DOMContentLoaded', function() {
    updateFavoritesProgress();
    loadContinueWatching();
});

// Carregar seção "Continuar Assistindo" do servidor
async function loadContinueWatching() {
    try {
        console.log('[Favorites] Loading continue watching...');
        const response = await fetch('/api/progress/continue-watching');
        console.log('[Favorites] Response status:', response.status);
        if (!response.ok) {
            console.error('[Favorites] Response not ok:', response.status);
            return;
        }
        
        const data = await response.json();
        console.log('[Favorites] Data received:', data);
        const items = data.items || [];
        console.log('[Favorites] Items count:', items.length);
        
        if (items.length === 0) {
            console.log('[Favorites] No items to display');
            return;
        }
        
        const section = document.getElementById('continueWatchingSection');
        const grid = document.getElementById('continueWatchingGrid');
        
        grid.innerHTML = items.map(item => {
            const timeStr = formatTime(item.current_time_sec || 0);
            const remainingTime = formatTime((item.duration_sec || 0) - (item.current_time_sec || 0));
            const percent = item.percent_watched || 0;
            
            // Check if this is from Vidking player (has minimal progress values)
            const isVidkingProgress = item.duration_sec <= 100 && item.current_time_sec <= 1;
            
            // Construir URL com parâmetro de tempo e stream info
            let watchUrl = `/watch?id=${item.imdb_id}&type=${item.type}`;
            if (item.type === 'series' && item.season && item.episode) {
                watchUrl += `&season=${item.season}&episode=${item.episode}`;
            }
            watchUrl += `&t=${Math.floor(item.current_time_sec || 0)}`;
            // Adicionar info do stream para garantir que a fonte correta seja usada
            if (item.stream_infohash) {
                watchUrl += `&sh=${encodeURIComponent(item.stream_infohash)}`;
            }
            
            // URL para começar do início
            let startUrl = `/watch?id=${item.imdb_id}&type=${item.type}`;
            if (item.type === 'series' && item.season && item.episode) {
                startUrl += `&season=${item.season}&episode=${item.episode}`;
            }
            
            // Título do episódio para séries
            let displayTitle = item.title || 'Sem título';
            if (item.type === 'series' && item.season && item.episode) {
                displayTitle += ` - S${item.season}E${item.episode}`;
            }
            
            return `
                <div class="continue-card" style="background: var(--card-bg); border-radius: 10px; overflow: hidden; display: flex; gap: 15px;">
                    <div style="position: relative; width: 120px; flex-shrink: 0;">
                        <img src="${item.poster || ''}" alt="${displayTitle}" style="width: 100%; height: 160px; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: rgba(255,255,255,0.2);">
                            <div style="height: 100%; width: ${percent}%; background: #e50914;"></div>
                        </div>
                    </div>
                    <div style="flex: 1; padding: 15px 15px 15px 0; display: flex; flex-direction: column; justify-content: center;">
                        <h3 style="margin: 0 0 5px; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${displayTitle}</h3>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 10px;">
                            ${isVidkingProgress ? 'Começou a assistir' : `${timeStr} assistido • Faltam ${remainingTime}`}
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <a href="${watchUrl}" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Continuar
                            </a>
                            <a href="${startUrl}" class="btn" style="padding: 8px 10px; font-size: 0.9rem; background: rgba(255,255,255,0.1); border: none; border-radius: 6px; color: #fff; text-decoration: none;" title="Começar do início">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                            </a>
                            <button onclick="removeFromContinueWatching('${item.imdb_id}', ${item.season || 0}, ${item.episode || 0}, this)" class="btn" style="padding: 8px 10px; font-size: 0.9rem; background: rgba(255,255,255,0.1); border: none; border-radius: 6px; color: #fff; cursor: pointer;" title="Remover da lista">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        section.style.display = 'block';
        
    } catch (e) {
        console.error('Erro ao carregar continue watching:', e);
    }
}

async function removeFromContinueWatching(imdbId, season, episode, buttonEl) {
    if (!confirm('Remover da lista "Continuar Assistindo"?')) return;
    
    try {
        const formData = new FormData();
        formData.append('imdb_id', imdbId);
        formData.append('season', season);
        formData.append('episode', episode);
        
        const response = await fetch('/api/progress/remove', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            // Remover o card da tela
            const card = buttonEl.closest('.continue-card');
            if (card) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    // Se não houver mais cards, esconder a seção
                    const grid = document.getElementById('continueWatchingGrid');
                    if (grid && grid.children.length === 0) {
                        document.getElementById('continueWatchingSection').style.display = 'none';
                    }
                }, 300);
            }
        } else {
            alert('Erro ao remover: ' + (result.error || 'Tente novamente'));
        }
    } catch (e) {
        console.error('Erro ao remover:', e);
        alert('Erro ao remover. Tente novamente.');
    }
}

function updateFavoritesProgress() {
    const progressKey = 'cinevision_progress';
    let allProgress = {};
    
    try {
        const stored = localStorage.getItem(progressKey);
        allProgress = stored ? JSON.parse(stored) : {};
    } catch (e) {
        return;
    }
    
    const progressBars = document.querySelectorAll('.progress-bar[data-progress-id]');
    
    progressBars.forEach(bar => {
        const imdbId = bar.dataset.progressId;
        const progress = allProgress[imdbId];
        
        if (progress && progress.percent > 0) {
            // Definir largura da barra diretamente
            bar.style.width = progress.percent + '%';
            
            // Atualizar texto
            const text = document.querySelector(`.progress-text[data-text-id="${imdbId}"]`);
            if (text) {
                text.classList.add('has-progress');
                const timeStr = formatTime(progress.currentTime || 0);
                text.textContent = `${Math.round(progress.percent)}% • ${timeStr}`;
            }
        }
    });
}

function formatTime(seconds) {
    if (!seconds) return '00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m}:${s.toString().padStart(2, '0')}`;
}
</script>
