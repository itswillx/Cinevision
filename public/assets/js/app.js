// CineVision App JavaScript
window.CineVision = {
    // Salvar progresso imediatamente
    saveProgressNow: function(imdbId, progressData) {
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

    // Salvar progresso normal
    savePlayerProgress: function(imdbId, progressData) {
        return this.saveProgressNow(imdbId, progressData);
    },

    // Salvar progresso usando Beacon (para quando a página está fechando)
    saveProgressBeacon: function(imdbId, progressData) {
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
    }
};
