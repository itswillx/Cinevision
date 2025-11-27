<?php 
// Select featured content from trending or movies
$featured = !empty($trending) ? $trending[0] : (!empty($movies) ? $movies[0] : (!empty($series) ? $series[0] : null)); 

// Preparar dados para JavaScript - filmes e séries para paginação
$moviesJson = json_encode($movies ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$seriesJson = json_encode($series ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Preparar filmes para o hero carousel (até 8 filmes)
$heroMovies = array_slice($movies ?? [], 0, 8);
$heroMoviesJson = json_encode($heroMovies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!-- Hero Carousel com navegação -->
<div class="hero-carousel-wrapper" style="position: relative;">
    <div class="hero" id="heroSection" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.3), #0f0f0f), url('<?php echo $featured['background'] ?? ''; ?>'); background-size: cover; background-position: center; height: 60vh; display: flex; align-items: flex-end; padding-bottom: 50px; margin-bottom: 40px; border-radius: 0 0 20px 20px; transition: background-image 0.5s ease;">
        <div class="container">
            <div id="heroContent">
                <?php if ($featured): ?>
                    <h1 id="heroTitle" style="font-size: 3rem; margin-bottom: 10px;"><?php echo $featured['name']; ?></h1>
                    <p id="heroDescription" style="max-width: 600px; margin-bottom: 20px; font-size: 1.1rem; color: #ddd;"><?php echo $featured['description'] ?? ''; ?></p>
                    <div id="heroMeta" style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                        <?php if (!empty($featured['imdbRating'])): ?>
                            <span id="heroRating" style="color: #f5c518; font-weight: bold;">⭐ <?php echo $featured['imdbRating']; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($featured['year'])): ?>
                            <span id="heroYear" style="color: #ccc;"><?php echo $featured['year']; ?></span>
                        <?php endif; ?>
                        <span id="heroGenres">
                        <?php if (!empty($featured['genres'])): ?>
                            <?php foreach (array_slice(array_filter($featured['genres']), 0, 3) as $genre): ?>
                                <span style="background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;"><?php echo $genre; ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </span>
                    </div>
                    <?php 
                        $featuredType = $featured['type'] ?? 'movie';
                        $featuredId = $featured['imdb_id'] ?? '';
                        $featuredLink = $featuredType === 'series' 
                            ? "/details?id={$featuredId}&type=series" 
                            : "/watch?id={$featuredId}&type=movie";
                    ?>
                    <?php if ($featuredId): ?>
                        <a href="<?php echo $featuredLink; ?>" id="heroButton" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">
                            ▶ <?php echo $featuredType === 'series' ? 'Ver Detalhes' : 'Assistir Agora'; ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Setas de navegação do Hero -->
    <button class="hero-nav-btn hero-prev" id="heroPrev" style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); z-index: 20; width: 50px; height: 50px; border-radius: 50%; background: rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); color: #fff; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
        ‹
    </button>
    <button class="hero-nav-btn hero-next" id="heroNext" style="position: absolute; right: 30px; top: 50%; transform: translateY(-50%); z-index: 20; width: 50px; height: 50px; border-radius: 50%; background: rgba(0,0,0,0.6); border: 2px solid rgba(255,255,255,0.3); color: #fff; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
        ›
    </button>
    
    <!-- Indicadores -->
    <div id="heroIndicators" style="position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 20;">
        <?php for ($i = 0; $i < min(count($heroMovies), 8); $i++): ?>
            <span class="hero-indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>" style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $i === 0 ? 'var(--primary)' : 'rgba(255,255,255,0.4)'; ?>; cursor: pointer; transition: all 0.3s;"></span>
        <?php endfor; ?>
    </div>
</div>

<!-- Dados do Hero para JavaScript -->
<script id="heroMoviesData" type="application/json"><?php echo $heroMoviesJson; ?></script>

<div class="container">
    <?php if (!empty($trending)): ?>
    <!-- Em Alta Agora -->
    <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">🔥 Em Alta Agora</h2>
    
    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <?php foreach (array_slice($trending, 1, 10) as $meta): ?>
            <?php 
                $type = $meta['type'] ?? 'movie';
                $id = $meta['imdb_id'] ?? '';
                if (empty($id)) continue;
                $link = $type === 'series' ? "/details?id={$id}&type=series" : "/watch?id={$id}&type=movie";
                $poster = $meta['poster'] ?? '';
                $name = $meta['name'] ?? '';
                $year = $meta['year'] ?? '';
            ?>
            <div class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                <a href="<?php echo $link; ?>" style="display: block;">
                    <div style="position: relative; padding-top: 150%;">
                        <img src="<?php echo $poster; ?>" alt="<?php echo $name; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <?php if ($type === 'series'): ?>
                            <span style="position: absolute; top: 8px; right: 8px; background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;">SÉRIE</span>
                        <?php endif; ?>
                        <?php if (!empty($meta['imdbRating']) && $meta['imdbRating'] > 0): ?>
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #f5c518; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">⭐ <?php echo $meta['imdbRating']; ?></span>
                        <?php endif; ?>
                        <div class="progress-bar" data-progress-id="<?php echo $id; ?>" style="width: 0%;"></div>
                    </div>
                    <div style="padding: 10px;">
                        <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $name; ?></h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $year; ?></span>
                    </div>
                </a>
                <button class="favorite-btn" data-heart-id="<?php echo $id; ?>" data-type="<?php echo $type; ?>" data-title="<?php echo htmlspecialchars($name); ?>" data-poster="<?php echo $poster; ?>" data-year="<?php echo $year; ?>" title="Adicionar aos favoritos">🤍</button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Filmes Populares com Paginação -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; border-left: 4px solid var(--primary); padding-left: 10px;">🎬 Filmes Populares</h2>
        <div class="pagination-controls" id="moviesPagination">
            <button class="pagination-btn" id="moviesPrevBtn" disabled>‹ Anterior</button>
            <span class="pagination-info" id="moviesPageInfo">Página 1</span>
            <button class="pagination-btn" id="moviesNextBtn">Próxima ›</button>
        </div>
    </div>
    
    <div id="moviesGrid" class="grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 40px;">
        <!-- Cards renderizados via JavaScript -->
    </div>

    <?php if (!empty($topRated)): ?>
    <!-- Melhores Avaliados -->
    <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">⭐ Melhores Avaliados</h2>
    
    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <?php foreach (array_slice($topRated, 0, 10) as $meta): ?>
            <?php 
                $id = $meta['imdb_id'] ?? ''; 
                if (empty($id)) continue;
                $poster = $meta['poster'] ?? '';
                $name = $meta['name'] ?? '';
                $year = $meta['year'] ?? '';
            ?>
            <div class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                <a href="/watch?id=<?php echo $id; ?>&type=movie" style="display: block;">
                    <div style="position: relative; padding-top: 150%;">
                        <img src="<?php echo $poster; ?>" alt="<?php echo $name; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <?php if (!empty($meta['imdbRating']) && $meta['imdbRating'] > 0): ?>
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #f5c518; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">⭐ <?php echo $meta['imdbRating']; ?></span>
                        <?php endif; ?>
                        <div class="progress-bar" data-progress-id="<?php echo $id; ?>" style="width: 0%;"></div>
                    </div>
                    <div style="padding: 10px;">
                        <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $name; ?></h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $year; ?></span>
                    </div>
                </a>
                <button class="favorite-btn" data-heart-id="<?php echo $id; ?>" data-type="movie" data-title="<?php echo htmlspecialchars($name); ?>" data-poster="<?php echo $poster; ?>" data-year="<?php echo $year; ?>" title="Adicionar aos favoritos">🤍</button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Séries Populares com Paginação -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; border-left: 4px solid var(--primary); padding-left: 10px;">📺 Séries Populares</h2>
        <div class="pagination-controls" id="seriesPagination">
            <button class="pagination-btn" id="seriesPrevBtn" disabled>‹ Anterior</button>
            <span class="pagination-info" id="seriesPageInfo">Página 1</span>
            <button class="pagination-btn" id="seriesNextBtn">Próxima ›</button>
        </div>
    </div>
    
    <div id="seriesGrid" class="grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 40px;">
        <!-- Cards renderizados via JavaScript -->
    </div>

    <?php if (!empty($inTheaters)): ?>
    <!-- Nos Cinemas -->
    <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">🎞️ Nos Cinemas</h2>
    
    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <?php foreach (array_slice($inTheaters, 0, 10) as $meta): ?>
            <?php 
                $id = $meta['imdb_id'] ?? ''; 
                if (empty($id)) continue;
                $poster = $meta['poster'] ?? '';
                $name = $meta['name'] ?? '';
                $year = $meta['year'] ?? '';
            ?>
            <div class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                <a href="/watch?id=<?php echo $id; ?>&type=movie" style="display: block;">
                    <div style="position: relative; padding-top: 150%;">
                        <img src="<?php echo $poster; ?>" alt="<?php echo $name; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <span style="position: absolute; top: 8px; right: 8px; background: #4CAF50; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;">CINEMA</span>
                        <?php if (!empty($meta['imdbRating']) && $meta['imdbRating'] > 0): ?>
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #f5c518; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">⭐ <?php echo $meta['imdbRating']; ?></span>
                        <?php endif; ?>
                        <div class="progress-bar" data-progress-id="<?php echo $id; ?>" style="width: 0%;"></div>
                    </div>
                    <div style="padding: 10px;">
                        <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $name; ?></h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $year; ?></span>
                    </div>
                </a>
                <button class="favorite-btn" data-heart-id="<?php echo $id; ?>" data-type="movie" data-title="<?php echo htmlspecialchars($name); ?>" data-poster="<?php echo $poster; ?>" data-year="<?php echo $year; ?>" title="Adicionar aos favoritos">🤍</button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Dados para JavaScript -->
<script id="moviesData" type="application/json"><?php echo $moviesJson; ?></script>
<script id="seriesData" type="application/json"><?php echo $seriesJson; ?></script>

<style>
    .card {
        position: relative;
    }
    .card:hover {
        transform: scale(1.05);
        z-index: 10;
        box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }
    .card:hover .favorite-btn {
        opacity: 1;
    }
    .favorite-btn {
        position: absolute;
        bottom: 60px;
        right: 8px;
        width: 36px;
        height: 36px;
        background: rgba(0,0,0,0.7);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        opacity: 0;
        transition: all 0.2s ease;
        z-index: 5;
    }
    .favorite-btn:hover {
        background: rgba(0,0,0,0.9);
        transform: scale(1.15);
    }
    .favorite-btn.favorited {
        opacity: 1;
    }
    .progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 4px;
        background: var(--primary);
        border-radius: 0 0 8px 8px;
        transition: width 0.3s ease;
    }
    
    /* Hero Carousel Styles */
    .hero-carousel-wrapper {
        position: relative;
    }
    .hero-nav-btn:hover {
        background: rgba(0,0,0,0.8);
        border-color: var(--primary);
        transform: translateY(-50%) scale(1.1);
    }
    .hero-indicator:hover {
        transform: scale(1.3);
    }
    #heroSection {
        transition: opacity 0.3s ease, background-image 0.5s ease;
    }
    
    /* Carrossel Styles */
    .carousel-wrapper {
        padding: 0 30px;
    }
    .carousel-btn:hover {
        background: var(--primary);
        transform: translateY(-50%) scale(1.1);
    }
    .carousel-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    .carousel-card:hover {
        transform: scale(1.08);
        z-index: 5;
        box-shadow: 0 8px 16px rgba(0,0,0,0.5);
    }
    
    /* Pagination Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .pagination-btn {
        background: var(--card-bg);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.9rem;
    }
    .pagination-btn:hover:not(:disabled) {
        background: var(--primary);
        border-color: var(--primary);
    }
    .pagination-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .pagination-info {
        color: var(--text-muted);
        font-size: 0.9rem;
        min-width: 80px;
        text-align: center;
    }
    
    /* Grid responsivo para 5 colunas */
    @media (max-width: 1200px) {
        #moviesGrid, #seriesGrid {
            grid-template-columns: repeat(4, 1fr) !important;
        }
    }
    @media (max-width: 992px) {
        #moviesGrid, #seriesGrid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .carousel-card {
            flex: 0 0 calc(25% - 12px) !important;
        }
    }
    @media (max-width: 768px) {
        #moviesGrid, #seriesGrid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .carousel-card {
            flex: 0 0 calc(33.33% - 10px) !important;
        }
        .pagination-controls {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
    @media (max-width: 480px) {
        .carousel-card {
            flex: 0 0 calc(50% - 8px) !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============ HERO CAROUSEL ============
    const heroSection = document.getElementById('heroSection');
    const heroTitle = document.getElementById('heroTitle');
    const heroDescription = document.getElementById('heroDescription');
    const heroRating = document.getElementById('heroRating');
    const heroYear = document.getElementById('heroYear');
    const heroGenres = document.getElementById('heroGenres');
    const heroButton = document.getElementById('heroButton');
    const heroPrevBtn = document.getElementById('heroPrev');
    const heroNextBtn = document.getElementById('heroNext');
    const heroIndicators = document.querySelectorAll('.hero-indicator');
    const heroMoviesDataEl = document.getElementById('heroMoviesData');
    
    let heroMovies = [];
    let currentHeroIndex = 0;
    
    if (heroMoviesDataEl) {
        try {
            heroMovies = JSON.parse(heroMoviesDataEl.textContent || '[]');
        } catch(e) {
            console.error('Error parsing hero movies data:', e);
        }
    }
    
    function updateHero(index) {
        if (!heroMovies.length || !heroSection) return;
        
        const movie = heroMovies[index];
        if (!movie) return;
        
        // Fade out
        heroSection.style.opacity = '0.7';
        
        setTimeout(() => {
            // Update background
            const bgUrl = movie.background || movie.poster || '';
            heroSection.style.backgroundImage = `linear-gradient(to bottom, rgba(0,0,0,0.3), #0f0f0f), url('${bgUrl}')`;
            
            // Update content
            if (heroTitle) heroTitle.textContent = movie.name || '';
            if (heroDescription) heroDescription.textContent = movie.description || '';
            if (heroRating) {
                heroRating.textContent = movie.imdbRating ? `⭐ ${movie.imdbRating}` : '';
                heroRating.style.display = movie.imdbRating ? 'inline' : 'none';
            }
            if (heroYear) {
                heroYear.textContent = movie.year || '';
                heroYear.style.display = movie.year ? 'inline' : 'none';
            }
            if (heroGenres && movie.genres) {
                const genres = Array.isArray(movie.genres) ? movie.genres.filter(g => g) : [];
                heroGenres.innerHTML = genres.slice(0, 3).map(g => 
                    `<span style="background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">${g}</span>`
                ).join('');
            }
            if (heroButton) {
                const type = movie.type || 'movie';
                const id = movie.imdb_id || '';
                const link = type === 'series' ? `/details?id=${id}&type=series` : `/watch?id=${id}&type=movie`;
                heroButton.href = link;
                heroButton.innerHTML = `▶ ${type === 'series' ? 'Ver Detalhes' : 'Assistir Agora'}`;
            }
            
            // Update indicators
            heroIndicators.forEach((ind, i) => {
                ind.style.background = i === index ? 'var(--primary)' : 'rgba(255,255,255,0.4)';
            });
            
            currentHeroIndex = index;
            
            // Fade in
            heroSection.style.opacity = '1';
        }, 200);
    }
    
    if (heroPrevBtn) {
        heroPrevBtn.addEventListener('click', () => {
            let newIndex = currentHeroIndex - 1;
            if (newIndex < 0) newIndex = heroMovies.length - 1;
            updateHero(newIndex);
        });
    }
    
    if (heroNextBtn) {
        heroNextBtn.addEventListener('click', () => {
            let newIndex = currentHeroIndex + 1;
            if (newIndex >= heroMovies.length) newIndex = 0;
            updateHero(newIndex);
        });
    }
    
    // Click on indicators
    heroIndicators.forEach(ind => {
        ind.addEventListener('click', () => {
            const index = parseInt(ind.dataset.index);
            if (!isNaN(index)) updateHero(index);
        });
    });
    
    // Auto-rotate every 8 seconds
    let heroAutoRotate = setInterval(() => {
        let newIndex = currentHeroIndex + 1;
        if (newIndex >= heroMovies.length) newIndex = 0;
        updateHero(newIndex);
    }, 8000);
    
    // Pause auto-rotate on hover
    if (heroSection) {
        heroSection.parentElement.addEventListener('mouseenter', () => {
            clearInterval(heroAutoRotate);
        });
        heroSection.parentElement.addEventListener('mouseleave', () => {
            heroAutoRotate = setInterval(() => {
                let newIndex = currentHeroIndex + 1;
                if (newIndex >= heroMovies.length) newIndex = 0;
                updateHero(newIndex);
            }, 8000);
        });
    }
    
    // ============ PAGINAÇÃO DE FILMES ============
    const moviesDataEl = document.getElementById('moviesData');
    const moviesGrid = document.getElementById('moviesGrid');
    const moviesPrevBtn = document.getElementById('moviesPrevBtn');
    const moviesNextBtn = document.getElementById('moviesNextBtn');
    const moviesPageInfo = document.getElementById('moviesPageInfo');
    
    let allMovies = [];
    let moviesPage = 1;
    const moviesPerPage = 10;
    
    if (moviesDataEl) {
        try {
            allMovies = JSON.parse(moviesDataEl.textContent || '[]');
        } catch(e) {
            console.error('Error parsing movies data:', e);
        }
    }
    
    function renderMovies() {
        if (!moviesGrid) return;
        
        const start = (moviesPage - 1) * moviesPerPage;
        const end = start + moviesPerPage;
        const pageMovies = allMovies.slice(start, end);
        const totalPages = Math.ceil(allMovies.length / moviesPerPage);
        
        moviesGrid.innerHTML = pageMovies.map(meta => {
            const id = meta.imdb_id || '';
            if (!id) return '';
            const poster = meta.poster || '';
            const name = meta.name || '';
            const year = meta.year || (meta.releaseInfo ? meta.releaseInfo.substring(0, 4) : '');
            const rating = meta.imdbRating || 0;
            
            return `
                <div class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                    <a href="/watch?id=${id}&type=movie" style="display: block;">
                        <div style="position: relative; padding-top: 150%;">
                            <img src="${poster}" alt="${escapeHtml(name)}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                            ${rating > 0 ? `<span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #f5c518; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">⭐ ${rating}</span>` : ''}
                            <div class="progress-bar" data-progress-id="${id}" style="width: 0%;"></div>
                        </div>
                        <div style="padding: 10px;">
                            <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(name)}</h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">${year}</span>
                        </div>
                    </a>
                    <button class="favorite-btn" data-heart-id="${id}" data-type="movie" data-title="${escapeHtml(name)}" data-poster="${poster}" data-year="${year}" title="Adicionar aos favoritos">🤍</button>
                </div>
            `;
        }).join('');
        
        if (moviesPageInfo) moviesPageInfo.textContent = `Página ${moviesPage} de ${totalPages}`;
        if (moviesPrevBtn) moviesPrevBtn.disabled = moviesPage <= 1;
        if (moviesNextBtn) moviesNextBtn.disabled = moviesPage >= totalPages;
        
        // Reinicializar favoritos após renderizar
        if (typeof CineVision !== 'undefined' && CineVision.initFavoriteButtons) {
            CineVision.initFavoriteButtons();
        }
    }
    
    if (moviesPrevBtn) {
        moviesPrevBtn.addEventListener('click', () => {
            if (moviesPage > 1) {
                moviesPage--;
                renderMovies();
            }
        });
    }
    
    if (moviesNextBtn) {
        moviesNextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(allMovies.length / moviesPerPage);
            if (moviesPage < totalPages) {
                moviesPage++;
                renderMovies();
            }
        });
    }
    
    renderMovies();
    
    // ============ PAGINAÇÃO DE SÉRIES ============
    const seriesDataEl = document.getElementById('seriesData');
    const seriesGrid = document.getElementById('seriesGrid');
    const seriesPrevBtn = document.getElementById('seriesPrevBtn');
    const seriesNextBtn = document.getElementById('seriesNextBtn');
    const seriesPageInfo = document.getElementById('seriesPageInfo');
    
    let allSeries = [];
    let seriesPage = 1;
    const seriesPerPage = 10;
    
    if (seriesDataEl) {
        try {
            allSeries = JSON.parse(seriesDataEl.textContent || '[]');
        } catch(e) {
            console.error('Error parsing series data:', e);
        }
    }
    
    function renderSeries() {
        if (!seriesGrid) return;
        
        const start = (seriesPage - 1) * seriesPerPage;
        const end = start + seriesPerPage;
        const pageSeries = allSeries.slice(start, end);
        const totalPages = Math.ceil(allSeries.length / seriesPerPage);
        
        seriesGrid.innerHTML = pageSeries.map(meta => {
            const id = meta.imdb_id || '';
            if (!id) return '';
            const poster = meta.poster || '';
            const name = meta.name || '';
            const year = meta.year || (meta.releaseInfo ? meta.releaseInfo.substring(0, 4) : '');
            const rating = meta.imdbRating || 0;
            
            return `
                <div class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                    <a href="/details?id=${id}&type=series" style="display: block;">
                        <div style="position: relative; padding-top: 150%;">
                            <img src="${poster}" alt="${escapeHtml(name)}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                            <span style="position: absolute; top: 8px; right: 8px; background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;">SÉRIE</span>
                            ${rating > 0 ? `<span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #f5c518; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">⭐ ${rating}</span>` : ''}
                            <div class="progress-bar" data-progress-id="${id}" style="width: 0%;"></div>
                        </div>
                        <div style="padding: 10px;">
                            <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(name)}</h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">${year}</span>
                        </div>
                    </a>
                    <button class="favorite-btn" data-heart-id="${id}" data-type="series" data-title="${escapeHtml(name)}" data-poster="${poster}" data-year="${year}" title="Adicionar aos favoritos">🤍</button>
                </div>
            `;
        }).join('');
        
        if (seriesPageInfo) seriesPageInfo.textContent = `Página ${seriesPage} de ${totalPages}`;
        if (seriesPrevBtn) seriesPrevBtn.disabled = seriesPage <= 1;
        if (seriesNextBtn) seriesNextBtn.disabled = seriesPage >= totalPages;
        
        // Reinicializar favoritos após renderizar
        if (typeof CineVision !== 'undefined' && CineVision.initFavoriteButtons) {
            CineVision.initFavoriteButtons();
        }
    }
    
    if (seriesPrevBtn) {
        seriesPrevBtn.addEventListener('click', () => {
            if (seriesPage > 1) {
                seriesPage--;
                renderSeries();
            }
        });
    }
    
    if (seriesNextBtn) {
        seriesNextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(allSeries.length / seriesPerPage);
            if (seriesPage < totalPages) {
                seriesPage++;
                renderSeries();
            }
        });
    }
    
    renderSeries();
    
    // Função auxiliar para escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
});
</script>
