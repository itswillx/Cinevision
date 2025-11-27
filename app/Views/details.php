<?php
// Organize episodes by season
$seasons = [];
if (!empty($meta['videos'])) {
    foreach ($meta['videos'] as $video) {
        $seasonNum = $video['season'] ?? 0;
        if ($seasonNum > 0) {
            if (!isset($seasons[$seasonNum])) {
                $seasons[$seasonNum] = [];
            }
            $seasons[$seasonNum][] = $video;
        }
    }
    ksort($seasons);
    // Sort episodes within each season
    foreach ($seasons as &$eps) {
        usort($eps, function($a, $b) {
            return ($a['episode'] ?? 0) - ($b['episode'] ?? 0);
        });
    }
}
$type = $meta['type'] ?? 'movie';
$isSeries = $type === 'series';
?>

<div class="details-hero" style="background-image: linear-gradient(to right, rgba(15,15,15,1) 0%, rgba(15,15,15,0.8) 50%, rgba(15,15,15,0.4) 100%), url('<?php echo $meta['background'] ?? $meta['poster'] ?? ''; ?>'); background-size: cover; background-position: center; min-height: 500px; display: flex; align-items: center; margin-bottom: 30px;">
    <div class="container" style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div class="poster" style="flex-shrink: 0;">
            <img src="<?php echo $meta['poster'] ?? ''; ?>" alt="<?php echo $meta['name']; ?>" style="width: 250px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        </div>
        <div class="info" style="flex: 1; min-width: 300px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo $meta['name']; ?></h1>
            
            <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                <?php if (!empty($meta['year'])): ?>
                    <span style="color: var(--text-muted);"><?php echo $meta['year']; ?></span>
                <?php endif; ?>
                <?php if (!empty($meta['runtime'])): ?>
                    <span style="color: var(--text-muted);"><?php echo $meta['runtime']; ?></span>
                <?php endif; ?>
                <?php if (!empty($meta['imdbRating'])): ?>
                    <span style="color: #f5c518;">⭐ <?php echo $meta['imdbRating']; ?></span>
                <?php endif; ?>
                <span style="background: var(--primary); padding: 2px 10px; border-radius: 4px; font-size: 0.8rem;">
                    <?php echo $isSeries ? 'SÉRIE' : 'FILME'; ?>
                </span>
            </div>
            
            <?php if (!empty($meta['genres'])): ?>
                <div style="margin-bottom: 15px;">
                    <?php foreach ($meta['genres'] as $genre): ?>
                        <span style="background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-right: 8px;"><?php echo $genre; ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <p style="color: #ccc; line-height: 1.6; max-width: 600px; margin-bottom: 20px;">
                <?php echo $meta['description'] ?? ''; ?>
            </p>
            
            <?php if (!$isSeries): ?>
                <a href="/watch?id=<?php echo $meta['imdb_id']; ?>&type=movie" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">
                    ▶ Assistir Agora
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isSeries && !empty($seasons)): ?>
<div class="container">
    <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">Temporadas e Episódios</h2>
    
    <!-- Season Tabs -->
    <div class="season-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php foreach (array_keys($seasons) as $index => $seasonNum): ?>
            <button class="season-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-season="<?php echo $seasonNum; ?>"
                    style="padding: 10px 20px; background: <?php echo $index === 0 ? 'var(--primary)' : 'var(--card-bg)'; ?>; border: none; border-radius: 8px; color: #fff; cursor: pointer; transition: all 0.2s;">
                Temporada <?php echo $seasonNum; ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Episodes Grid -->
    <?php foreach ($seasons as $seasonNum => $episodes): ?>
        <div class="season-content" data-season="<?php echo $seasonNum; ?>" style="display: <?php echo array_key_first($seasons) === $seasonNum ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($episodes as $episode): ?>
                <a href="/watch?id=<?php echo $meta['imdb_id']; ?>&type=series&season=<?php echo $episode['season']; ?>&episode=<?php echo $episode['episode']; ?>" 
                   class="episode-card" 
                   style="display: flex; gap: 15px; background: var(--card-bg); border-radius: 10px; overflow: hidden; transition: transform 0.2s; text-decoration: none; color: inherit;">
                    <div style="width: 120px; height: 80px; flex-shrink: 0; background: #222; position: relative;">
                        <?php if (!empty($episode['thumbnail'])): ?>
                            <img src="<?php echo $episode['thumbnail']; ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                            <span style="font-size: 1.5rem;">▶</span>
                        </div>
                    </div>
                    <div style="padding: 10px 10px 10px 0; flex: 1; min-width: 0;">
                        <div style="font-weight: bold; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            E<?php echo $episode['episode']; ?>. <?php echo $episode['name'] ?? 'Episódio ' . $episode['episode']; ?>
                        </div>
                        <?php if (!empty($episode['overview'])): ?>
                            <div style="font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo $episode['overview']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.season-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const season = this.dataset.season;
        
        // Update active tab
        document.querySelectorAll('.season-tab').forEach(t => {
            t.classList.remove('active');
            t.style.background = 'var(--card-bg)';
        });
        this.classList.add('active');
        this.style.background = 'var(--primary)';
        
        // Show corresponding content
        document.querySelectorAll('.season-content').forEach(content => {
            content.style.display = content.dataset.season === season ? 'grid' : 'none';
        });
    });
});
</script>

<style>
.episode-card:hover {
    transform: translateX(5px);
    background: rgba(255,255,255,0.1) !important;
}
.season-tab:hover {
    opacity: 0.8;
}
</style>
<?php endif; ?>

<?php if (!$isSeries): ?>
<!-- For movies, show cast and similar info if available -->
<div class="container">
    <?php if (!empty($meta['cast'])): ?>
        <h2 style="margin-bottom: 20px; border-left: 4px solid var(--primary); padding-left: 10px;">Elenco</h2>
        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px;">
            <?php foreach (array_slice($meta['cast'], 0, 10) as $actor): ?>
                <span style="background: var(--card-bg); padding: 8px 15px; border-radius: 20px;"><?php echo $actor; ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
