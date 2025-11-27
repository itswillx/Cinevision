<div class="container" style="margin-top: 40px;">
    <!-- Busca com Autocomplete -->
    <div class="search-header" style="margin-bottom: 20px;">
        <form id="searchForm" action="/search" method="GET">
            <div style="display: flex; gap: 10px; margin-bottom: 15px; position: relative;">
                <div style="flex: 1; position: relative;">
                    <input type="text" name="q" id="searchInput" class="form-control" 
                           placeholder="Buscar filmes e séries..." 
                           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" 
                           style="font-size: 1.2rem; padding: 15px;" 
                           autocomplete="off">
                    <!-- Dropdown de sugestões -->
                    <div id="suggestionsDropdown" class="suggestions-dropdown" style="display: none;"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0 30px;">Buscar</button>
            </div>
            
            <!-- Filtros Avançados -->
            <div class="filters-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="filter-group">
                    <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; display: block;">Tipo</label>
                    <select name="type" class="form-control" style="min-width: 140px;">
                        <option value="">Todos</option>
                        <option value="movie" <?php echo ($_GET['type'] ?? '') === 'movie' ? 'selected' : ''; ?>>Filmes</option>
                        <option value="series" <?php echo ($_GET['type'] ?? '') === 'series' ? 'selected' : ''; ?>>Séries</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; display: block;">Gênero</label>
                    <select name="genre" class="form-control" style="min-width: 160px;">
                        <option value="">Todos os gêneros</option>
                        <?php foreach ($availableGenres ?? [] as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($_GET['genre'] ?? '') === $key ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; display: block;">Ano</label>
                    <select name="year" class="form-control" style="min-width: 120px;">
                        <option value="">Qualquer ano</option>
                        <?php 
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 50; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo ($_GET['year'] ?? '') == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <?php if (!empty($_GET['type']) || !empty($_GET['genre']) || !empty($_GET['year'])): ?>
                    <a href="/search?q=<?php echo urlencode($_GET['q'] ?? ''); ?>" class="btn" style="background: var(--card-bg); padding: 8px 15px; align-self: flex-end;">
                        Limpar Filtros
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['q'])): ?>
        <h2 style="margin-bottom: 20px;">
            Resultados para "<?php echo htmlspecialchars($_GET['q']); ?>"
            <?php if (!empty($_GET['type']) || !empty($_GET['genre']) || !empty($_GET['year'])): ?>
                <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: normal;">
                    (filtrado por: 
                    <?php 
                    $filters = [];
                    if (!empty($_GET['type'])) $filters[] = $_GET['type'] === 'movie' ? 'Filmes' : 'Séries';
                    if (!empty($_GET['genre'])) $filters[] = $availableGenres[$_GET['genre']] ?? $_GET['genre'];
                    if (!empty($_GET['year'])) $filters[] = $_GET['year'];
                    echo implode(', ', $filters);
                    ?>)
                </span>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($metas)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 1.2rem;">Nenhum resultado encontrado.</p>
                <p style="font-size: 0.9rem;">Tente ajustar os filtros ou buscar por outro termo.</p>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); margin-bottom: 15px;"><?php echo count($metas); ?> resultado(s) encontrado(s)</p>
            <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
                <?php foreach ($metas as $meta): 
                    $isSeries = ($meta['type'] ?? 'movie') === 'series';
                    $imdbId = $meta['imdb_id'] ?? $meta['id'] ?? '';
                    $link = $isSeries 
                        ? "/details?id={$imdbId}&type=series" 
                        : "/watch?id={$imdbId}&type=movie";
                    $year = $meta['releaseInfo'] ?? $meta['year'] ?? '';
                ?>
                    <a href="<?php echo $link; ?>" class="card" style="display: block; background: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.2s;">
                        <div style="position: relative; padding-top: 150%;">
                            <img src="<?php echo $meta['poster'] ?? ''; ?>" alt="<?php echo htmlspecialchars($meta['name'] ?? ''); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 300 450%22><rect fill=%22%23333%22 width=%22300%22 height=%22450%22/><text fill=%22%23666%22 x=%22150%22 y=%22225%22 text-anchor=%22middle%22>Sem Imagem</text></svg>'">
                            <?php if ($isSeries): ?>
                                <span style="position: absolute; top: 8px; right: 8px; background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;">SÉRIE</span>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 10px;">
                            <h3 style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($meta['name'] ?? ''); ?></h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php echo $isSeries ? 'Série' : 'Filme'; ?>
                                <?php if ($year): ?> • <?php echo $year; ?><?php endif; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
            <h3 style="margin-bottom: 10px;">Busque por filmes e séries</h3>
            <p>Digite o nome do título que você procura</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .card:hover {
        transform: scale(1.05);
        z-index: 10;
        box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }
    
    .suggestions-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 1000;
        max-height: 400px;
        overflow-y: auto;
        margin-top: 5px;
    }
    
    .suggestion-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        cursor: pointer;
        transition: background 0.2s;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .suggestion-item:hover {
        background: rgba(255,255,255,0.1);
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    .suggestion-poster {
        width: 40px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
        background: #333;
    }
    
    .suggestion-info {
        flex: 1;
        min-width: 0;
    }
    
    .suggestion-title {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .suggestion-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    @media (max-width: 768px) {
        .filters-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-group select {
            width: 100%;
        }
    }
</style>

<script>
(function() {
    const searchInput = document.getElementById('searchInput');
    const dropdown = document.getElementById('suggestionsDropdown');
    let debounceTimer = null;
    let currentQuery = '';
    
    // Debounced search
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        currentQuery = query;
        
        clearTimeout(debounceTimer);
        
        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => fetchSuggestions(query), 250);
    });
    
    // Fetch suggestions from API
    async function fetchSuggestions(query) {
        if (query !== currentQuery) return; // Query changed, skip
        
        try {
            const response = await fetch(`/api/search/suggest?q=${encodeURIComponent(query)}&limit=8`);
            const data = await response.json();
            
            if (query !== currentQuery) return; // Query changed during fetch
            
            renderSuggestions(data.suggestions || []);
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            dropdown.style.display = 'none';
        }
    }
    
    // Render suggestions dropdown
    function renderSuggestions(suggestions) {
        if (suggestions.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        dropdown.innerHTML = suggestions.map(item => `
            <div class="suggestion-item" data-id="${item.id}" data-type="${item.type}" data-name="${escapeHtml(item.name)}">
                <img class="suggestion-poster" src="${item.poster || ''}" alt="" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 60%22><rect fill=%22%23333%22 width=%2240%22 height=%2260%22/></svg>'">
                <div class="suggestion-info">
                    <div class="suggestion-title">${escapeHtml(item.name)}</div>
                    <div class="suggestion-meta">${item.type === 'series' ? 'Série' : 'Filme'}${item.year ? ' • ' + item.year : ''}</div>
                </div>
            </div>
        `).join('');
        
        dropdown.style.display = 'block';
        
        // Add click handlers
        dropdown.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const type = item.dataset.type;
                
                if (type === 'series') {
                    window.location.href = `/details?id=${id}&type=series`;
                } else {
                    window.location.href = `/watch?id=${id}&type=movie`;
                }
            });
        });
    }
    
    // Escape HTML for safety
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Close on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });
})();
</script>
