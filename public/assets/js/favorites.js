/**
 * CineVision - Sistema de Favoritos e Progresso
 * Gerencia favoritos localmente + sync com Supabase
 * Salva progresso de visualização
 */

const CineVision = {
    // Storage keys
    FAVORITES_KEY: 'cinevision_favorites',
    PROGRESS_KEY: 'cinevision_progress',
    
    // Cache local de favoritos (para verificação rápida)
    favoritesCache: new Set(),
    
    /**
     * Inicializa o sistema
     */
    init() {
        this.loadFavoritesFromStorage();
        this.attachCardListeners();
        this.updateAllHeartIcons();
        
        // Sincronizar favoritos do servidor em background
        this.syncFavoritesFromServer();
    },
    
    /**
     * Carrega favoritos do localStorage
     */
    loadFavoritesFromStorage() {
        try {
            const stored = localStorage.getItem(this.FAVORITES_KEY);
            if (stored) {
                const favorites = JSON.parse(stored);
                this.favoritesCache = new Set(favorites.map(f => f.imdb_id));
            }
        } catch (e) {
            console.error('Erro ao carregar favoritos:', e);
            this.favoritesCache = new Set();
        }
    },
    
    /**
     * Salva favoritos no localStorage
     */
    saveFavoritesToStorage(favorites) {
        try {
            localStorage.setItem(this.FAVORITES_KEY, JSON.stringify(favorites));
            this.favoritesCache = new Set(favorites.map(f => f.imdb_id));
        } catch (e) {
            console.error('Erro ao salvar favoritos:', e);
        }
    },
    
    /**
     * Verifica se um item está nos favoritos
     */
    isFavorite(imdbId) {
        return this.favoritesCache.has(imdbId);
    },
    
    /**
     * Obtém todos os favoritos do storage
     */
    getFavorites() {
        try {
            const stored = localStorage.getItem(this.FAVORITES_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    },
    
    /**
     * Adiciona aos favoritos (local + servidor)
     */
    async addFavorite(data) {
        const { imdb_id, type, title, poster, year } = data;
        
        // Atualiza localmente primeiro (resposta instantânea)
        const favorites = this.getFavorites();
        if (!favorites.find(f => f.imdb_id === imdb_id)) {
            favorites.push({ imdb_id, type, title, poster, year, added_at: new Date().toISOString() });
            this.saveFavoritesToStorage(favorites);
        }
        
        // Atualiza ícone imediatamente
        this.updateHeartIcon(imdb_id, true);
        
        // Sync com servidor (em background)
        try {
            const formData = new FormData();
            formData.append('imdb_id', imdb_id);
            formData.append('type', type);
            formData.append('title', title);
            formData.append('poster', poster);
            formData.append('year', year);
            
            const response = await fetch('/api/favorites/add', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.error) {
                console.warn('Erro ao sincronizar favorito:', result.error);
            }
        } catch (e) {
            console.error('Erro ao sincronizar favorito:', e);
        }
        
        return true;
    },
    
    /**
     * Remove dos favoritos (local + servidor)
     */
    async removeFavorite(imdbId) {
        // Atualiza localmente primeiro
        let favorites = this.getFavorites();
        favorites = favorites.filter(f => f.imdb_id !== imdbId);
        this.saveFavoritesToStorage(favorites);
        
        // Atualiza ícone imediatamente
        this.updateHeartIcon(imdbId, false);
        
        // Sync com servidor
        try {
            const formData = new FormData();
            formData.append('imdb_id', imdbId);
            
            const response = await fetch('/api/favorites/remove', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.error) {
                console.warn('Erro ao remover favorito:', result.error);
            }
        } catch (e) {
            console.error('Erro ao remover favorito:', e);
        }
        
        return true;
    },
    
    /**
     * Toggle favorito
     */
    async toggleFavorite(data) {
        if (this.isFavorite(data.imdb_id)) {
            return this.removeFavorite(data.imdb_id);
        } else {
            return this.addFavorite(data);
        }
    },
    
    /**
     * Atualiza ícone de coração de um item
     */
    updateHeartIcon(imdbId, isFavorite) {
        const hearts = document.querySelectorAll(`[data-heart-id="${imdbId}"]`);
        hearts.forEach(heart => {
            if (isFavorite) {
                heart.innerHTML = '❤️';
                heart.classList.add('favorited');
                heart.title = 'Remover dos favoritos';
            } else {
                heart.innerHTML = '🤍';
                heart.classList.remove('favorited');
                heart.title = 'Adicionar aos favoritos';
            }
        });
    },
    
    /**
     * Atualiza todos os ícones de coração na página
     */
    updateAllHeartIcons() {
        const hearts = document.querySelectorAll('[data-heart-id]');
        hearts.forEach(heart => {
            const imdbId = heart.dataset.heartId;
            this.updateHeartIcon(imdbId, this.isFavorite(imdbId));
        });
    },
    
    /**
     * Attach listeners aos cards
     */
    attachCardListeners() {
        document.addEventListener('click', (e) => {
            const heartBtn = e.target.closest('.favorite-btn');
            if (heartBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                const data = {
                    imdb_id: heartBtn.dataset.heartId,
                    type: heartBtn.dataset.type || 'movie',
                    title: heartBtn.dataset.title || '',
                    poster: heartBtn.dataset.poster || '',
                    year: heartBtn.dataset.year || ''
                };
                
                this.toggleFavorite(data);
            }
        });
    },
    
    // ========== SISTEMA DE PROGRESSO (SERVIDOR) ==========
    
    /**
     * Salva progresso imediatamente no servidor
     */
    saveProgressNow(imdbId, progressData) {
        return fetch('/api/progress/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                imdb_id: imdbId,
                ...progressData
            })
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Erro ao salvar progresso:', error);
        });
    },
    
    /**
     * Salva progresso usando Beacon (para quando a página está fechando)
     */
    saveProgressBeacon(imdbId, progressData) {
        const data = {
            imdb_id: imdbId,
            ...progressData
        };
        
        if (navigator.sendBeacon) {
            // Usar Blob com tipo JSON para sendBeacon
            const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
            navigator.sendBeacon('/api/progress/save', blob);
        } else {
            // Fallback para fetch normal
            this.saveProgressNow(imdbId, progressData);
        }
    },
    
    // ========== SISTEMA DE PROGRESSO (LOCAL) ==========
    
    /**
     * Salva progresso de visualização no localStorage
     */
    saveProgress(imdbId, data) {
        try {
            const allProgress = this.getAllProgress();
            allProgress[imdbId] = {
                ...data,
                updated_at: new Date().toISOString()
            };
            localStorage.setItem(this.PROGRESS_KEY, JSON.stringify(allProgress));
        } catch (e) {
            console.error('Erro ao salvar progresso:', e);
        }
    },
    
    /**
     * Obtém progresso de um item
     */
    getProgress(imdbId) {
        const allProgress = this.getAllProgress();
        return allProgress[imdbId] || null;
    },
    
    /**
     * Obtém todo o progresso
     */
    getAllProgress() {
        try {
            const stored = localStorage.getItem(this.PROGRESS_KEY);
            return stored ? JSON.parse(stored) : {};
        } catch (e) {
            return {};
        }
    },
    
    /**
     * Salva progresso do player
     * @param {string} imdbId - ID IMDB
     * @param {object} data - { currentTime, duration, streamIndex, season, episode }
     */
    savePlayerProgress(imdbId, data) {
        // Usar CineVision diretamente para evitar problemas de contexto
        CineVision.saveProgress(imdbId, {
            currentTime: data.currentTime || 0,
            duration: data.duration || 0,
            percent: data.duration ? Math.round((data.currentTime / data.duration) * 100) : 0,
            streamIndex: data.streamIndex || 0,
            streamUrl: data.streamUrl || '',
            season: data.season || null,
            episode: data.episode || null,
            type: data.type || 'movie'
        });
    },
    
    /**
     * Carrega progresso do player
     */
    getPlayerProgress(imdbId, season = null, episode = null) {
        const progress = this.getProgress(imdbId);
        if (!progress) return null;
        
        // Para séries, verificar se é o mesmo episódio
        if (season && episode) {
            if (progress.season != season || progress.episode != episode) {
                return null;
            }
        }
        
        return progress;
    },
    
    /**
     * Formata tempo para exibição
     */
    formatTime(seconds) {
        if (!seconds) return '00:00';
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        if (h > 0) {
            return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }
        return `${m}:${s.toString().padStart(2, '0')}`;
    },
    
    /**
     * Sincroniza favoritos do servidor para local
     */
    async syncFavoritesFromServer() {
        try {
            const response = await fetch('/api/favorites/list');
            if (response.ok) {
                const data = await response.json();
                if (data.favorites) {
                    this.saveFavoritesToStorage(data.favorites);
                    this.updateAllHeartIcons();
                }
            }
        } catch (e) {
            console.error('Erro ao sincronizar favoritos:', e);
        }
    },
    
    /**
     * Atualiza as barras de progresso nos cards
     */
    updateProgressBars() {
        const progressBars = document.querySelectorAll('[data-progress-id]');
        const allProgress = this.getAllProgress();
        
        progressBars.forEach(bar => {
            const imdbId = bar.dataset.progressId;
            const progress = allProgress[imdbId];
            
            if (progress && progress.percent > 0 && progress.percent < 100) {
                bar.style.width = `${progress.percent}%`;
            }
        });
    }
};

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    CineVision.init();
    CineVision.updateProgressBars();
    
    // Iniciar auto-refresh de token
    AuthRefresh.init();
});

// Expor globalmente
window.CineVision = CineVision;

/**
 * Sistema de Auto-Refresh de Token
 * Renova automaticamente o token de autenticação antes de expirar
 */
const AuthRefresh = {
    // Intervalo de verificação (a cada 2 minutos)
    CHECK_INTERVAL: 2 * 60 * 1000,
    
    // Timer ID
    timerId: null,
    
    /**
     * Inicializa o sistema de auto-refresh
     */
    init() {
        // Não iniciar em páginas de login/registro (usuário não autenticado)
        if (window.location.pathname === '/login' || window.location.pathname === '/register') {
            console.log('[AuthRefresh] Página de autenticação detectada, auto-refresh desativado');
            return;
        }
        
        // Verificar imediatamente
        this.checkAndRefresh();
        
        // Configurar verificação periódica
        this.timerId = setInterval(() => {
            this.checkAndRefresh();
        }, this.CHECK_INTERVAL);
        
        // Também verificar quando a página volta a ficar visível
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.checkAndRefresh();
            }
        });
        
        console.log('[AuthRefresh] Sistema de auto-refresh iniciado');
    },
    
    /**
     * Verifica e renova o token se necessário
     */
    async checkAndRefresh() {
        try {
            const response = await fetch('/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    console.log('[AuthRefresh] Token renovado, expira em:', new Date(data.expires_at * 1000).toLocaleString());
                }
            } else if (response.status === 401) {
                // Token expirou e não pode ser renovado - parar o refresh e não redirecionar
                // O usuário será redirecionado naturalmente ao tentar fazer uma ação que requer auth
                console.warn('[AuthRefresh] Sessão expirada ou usuário não autenticado');
                this.stop();
            }
        } catch (e) {
            // Erro de rede - não fazer nada, tentar novamente no próximo intervalo
            console.warn('[AuthRefresh] Erro ao verificar token:', e.message);
        }
    },
    
    /**
     * Para o sistema de auto-refresh
     */
    stop() {
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
    }
};

// Expor globalmente
window.AuthRefresh = AuthRefresh;
