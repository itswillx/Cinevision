# Mudanças para Deploy em Produção - Player Vidking

## Resumo
O player foi alterado de Torrentio/RealDebrid para Vidking, que usa iframe embed com TMDB ID.

## Arquivos para Copiar/Substituir

| Arquivo | Ação |
|---------|------|
| `app/Controllers/PlayerController.php` | **Substituir** |
| `app/Controllers/WatchProgressController.php` | **Substituir** |
| `app/Views/player_vidking.php` | **Copiar** (novo) |
| `app/Views/favorites.php` | **Substituir** |
| `app/Models/WatchProgress.php` | **Substituir** |
| `app/Services/TMDBService.php` | **Substituir** |
| `public/assets/js/favorites.js` | **Substituir** |

## Configuração Necessária em PRD

### config/env.php
Adicione a chave TMDB:

```php
'TMDB_API_KEY' => 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmMWVlMjg1OTBlZGMyYjQxMGVlOWIwNDIxYzE0Nzc4MyIsIm5iZiI6MTc2NzQ1NDQxNi42NjcsInN1YiI6IjY5NTkzNmQwYzE4NTc1NTQ2MmQ3Y2EwMSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.2xsdzvxuZXD0EQcw1OgR_G381qwbxJQpnmw3kSl2hkk',
```

## URLs do Player Vidking

- **Filmes:** `https://www.vidking.net/embed/movie/{tmdbId}?color=%23E50914&primaryColor=%23E50914&autoplay=true`
- **Séries:** `https://www.vidking.net/embed/tv/{tmdbId}/{season}/{episode}?color=%23E50914&primaryColor=%23E50914&autoplay=true`

## Parâmetros do Player

| Parâmetro | Valor | Descrição |
|-----------|-------|-----------|
| `color` | `%23E50914` | Cor vermelha (Netflix) |
| `primaryColor` | `%23E50914` | Cor primária vermelha |
| `autoplay` | `true` | Reprodução automática |

**Nota:** A seleção de servidor (Oxygen, Hydrogen, etc.) é controlada pelo usuário na interface do player. Não é possível definir via URL.

## Arquivos NÃO copiar para PRD

- `public/index_local.php`
- `setup_local.php`
- `DEPLOY_VIDKING.md`

## Limitações

- **Progresso de reprodução:** O player Vidking é um iframe cross-origin, então não é possível capturar o tempo real de reprodução. A seção "Continuar Assistindo" mostra "Começou a assistir" para itens do Vidking.

## Rollback

Em `app/Controllers/PlayerController.php`, altere:
```php
$view = __DIR__ . '/../Views/player_vidking.php';
```
Para:
```php
$view = __DIR__ . '/../Views/player.php';
```

## Testando

1. Login funciona
2. Catálogo carrega
3. Player Vidking exibe conteúdo com servidor Oxygen
4. Cores do player em vermelho
5. Seção "Continuar Assistindo" mostra itens assistidos
