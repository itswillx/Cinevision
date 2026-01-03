# Mudanças para Deploy em Produção - Player Vidking

## Resumo
O player foi alterado de Torrentio/RealDebrid para Vidking, que usa iframe embed com TMDB ID.

## Arquivos para Copiar/Substituir

| Arquivo | Ação |
|---------|------|
| `app/Controllers/PlayerController.php` | **Substituir** |
| `app/Views/player_vidking.php` | **Copiar** (novo) |
| `app/Services/TMDBService.php` | **Substituir** |

## Configuração Necessária em PRD

### config/env.php
Adicione a chave TMDB:

```php
'TMDB_API_KEY' => 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmMWVlMjg1OTBlZGMyYjQxMGVlOWIwNDIxYzE0Nzc4MyIsIm5iZiI6MTc2NzQ1NDQxNi42NjcsInN1YiI6IjY5NTkzNmQwYzE4NTc1NTQ2MmQ3Y2EwMSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.2xsdzvxuZXD0EQcw1OgR_G381qwbxJQpnmw3kSl2hkk',
```

## URLs do Player Vidking

- **Filmes:** `https://www.vidking.net/embed/movie/{tmdbId}?color=%23E50914&primaryColor=%23E50914&autoplay=true`
- **Séries:** `https://www.vidking.net/embed/tv/{tmdbId}/{season}/{episode}?color=%23E50914&primaryColor=%23E50914&autoplay=true`

## Arquivos NÃO copiar para PRD

- `public/index_local.php`
- `setup_local.php`
- `DEPLOY_VIDKING.md`

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
3. Player Vidking exibe conteúdo
4. Cores do player em vermelho
