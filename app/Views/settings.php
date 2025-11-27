<div class="container" style="max-width: 800px; margin-top: 40px;">
    <h2 style="margin-bottom: 20px;">Configurações</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #2ecc71; color: white; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: var(--primary); color: white; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="/settings/save" method="POST" class="settings-form">
        
        <div class="card" style="background: var(--card-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: var(--primary);">RealDebrid</h3>
            <div class="form-group">
                <label for="rd_token">API Token</label>
                <input type="text" id="rd_token" name="rd_token" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['rd_token'] ?? ''); ?>" 
                       placeholder="Obtenha em https://real-debrid.com/apitoken">
                <small style="color: var(--text-muted); display: block; margin-top: 5px;">
                    Necessário para assistir e baixar conteúdo.
                </small>
            </div>

            <?php if (isset($rdInfo) && !isset($rdInfo['error'])): ?>
                <div style="margin-top: 10px; padding: 10px; background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; border-radius: 4px;">
                    <strong>Status:</strong> Conectado como <?php echo htmlspecialchars($rdInfo['username']); ?> 
                    (Premium até: <?php echo date('d/m/Y', strtotime($rdInfo['expiration'])); ?>)
                </div>
            <?php elseif (!empty($settings['rd_token'])): ?>
                <div style="margin-top: 10px; padding: 10px; background: rgba(231, 76, 60, 0.1); border: 1px solid var(--primary); border-radius: 4px;">
                    <strong>Erro:</strong> Token inválido ou expirado.
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="background: var(--card-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: var(--primary);">Preferências</h3>
            
            <div class="form-group">
                <label for="subtitle_lang">Idioma das Legendas</label>
                <select id="subtitle_lang" name="subtitle_lang" class="form-control">
                    <option value="pob" <?php echo ($settings['subtitle_lang'] ?? '') === 'pob' ? 'selected' : ''; ?>>Português (Brasil)</option>
                    <option value="eng" <?php echo ($settings['subtitle_lang'] ?? '') === 'eng' ? 'selected' : ''; ?>>Inglês</option>
                    <option value="spa" <?php echo ($settings['subtitle_lang'] ?? '') === 'spa' ? 'selected' : ''; ?>>Espanhol</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality_pref">Qualidade Preferida</label>
                <select id="quality_pref" name="quality_pref" class="form-control">
                    <option value="4k" <?php echo ($settings['quality_pref'] ?? '') === '4k' ? 'selected' : ''; ?>>4K (2160p)</option>
                    <option value="1080p" <?php echo ($settings['quality_pref'] ?? '') === '1080p' ? 'selected' : ''; ?>>Full HD (1080p)</option>
                    <option value="720p" <?php echo ($settings['quality_pref'] ?? '') === '720p' ? 'selected' : ''; ?>>HD (720p)</option>
                    <option value="480p" <?php echo ($settings['quality_pref'] ?? '') === '480p' ? 'selected' : ''; ?>>SD (480p)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Configurações</button>
    </form>
</div>
