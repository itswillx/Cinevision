# 🎬 CineVision

**CineVision** é uma aplicação web para streaming de filmes e séries, desenvolvida em PHP com integração completa ao **Supabase** como backend.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![Supabase](https://img.shields.io/badge/Supabase-Backend-3ECF8E?style=flat-square&logo=supabase&logoColor=white)
![TMDB](https://img.shields.io/badge/TMDB-API-01D277?style=flat-square&logo=themoviedatabase&logoColor=white)

## ✨ Funcionalidades

- 🔐 **Autenticação completa** via Supabase Auth (cadastro, login, recuperação de senha)
- 🎥 **Catálogo de filmes e séries** integrado com TMDB API
- ⭐ **Sistema de favoritos** com sincronização em nuvem
- 🎬 **Player de vídeo** integrado com suporte a legendas
- 🔗 **Integração Real-Debrid** para resolução de links de streaming
- 📝 **Busca de legendas** via OpenSubtitles
- ⚙️ **Configurações personalizadas** por usuário

## 🏗️ Arquitetura

```
CineVision/
├── app/
│   ├── Controllers/     # Controladores MVC
│   ├── Models/          # Modelos de dados
│   ├── Services/        # Serviços (Supabase, Real-Debrid, etc.)
│   └── Views/           # Templates PHP
├── config/
│   ├── db.php           # Configuração do banco
│   └── env.php          # Variáveis de ambiente
├── public/
│   ├── assets/          # CSS, JS, imagens
│   └── index.php        # Entry point
└── sql/
    └── supabase_schema.sql  # Schema do banco
```

## 🚀 Instalação

### Pré-requisitos

- PHP 8.0+
- Conta no [Supabase](https://supabase.com)
- Chave API do [TMDB](https://www.themoviedb.org/settings/api)

### Configuração

1. **Clone o repositório**
   ```bash
   git clone https://github.com/itswillx/CineVision.git
   cd CineVision
   ```

2. **Configure as variáveis de ambiente**
   
   Edite `config/env.php`:
   ```php
   return [
       'APP_URL' => 'http://localhost:8000',
       'SUPABASE_URL' => 'sua-url-supabase',
       'SUPABASE_KEY' => 'sua-chave-anon',
       'TMDB_API_KEY' => 'sua-chave-tmdb',
   ];
   ```

3. **Configure o banco de dados no Supabase**
   
   Execute o script `sql/supabase_schema.sql` no SQL Editor do Supabase Dashboard.

4. **Inicie o servidor**
   ```bash
   php -S localhost:8000 -t public
   ```
   
   Ou use o script incluído:
   ```bash
   start_server.bat
   ```

## 🗄️ Schema do Banco de Dados

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `profiles` | Perfis de usuário vinculados ao Supabase Auth |
| `favorites_v2` | Filmes/séries favoritos do usuário |
| `user_settings_v2` | Configurações personalizadas |

### Row Level Security (RLS)

Todas as tabelas possuem RLS habilitado, garantindo que cada usuário só acesse seus próprios dados.

## 🔧 Tecnologias

- **Backend**: PHP 8.0+ (MVC puro, sem framework)
- **Banco de Dados**: PostgreSQL via Supabase
- **Autenticação**: Supabase Auth (JWT)
- **APIs Externas**:
  - TMDB (catálogo de filmes/séries)
  - OpenSubtitles (legendas)
  - Real-Debrid (resolução de links)

## 📸 Screenshots

<img width="1366" height="887" alt="image" src="https://github.com/user-attachments/assets/3c489ce4-79f1-48ef-8866-f97c0e716962" />


## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou pull requests.

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👤 Autor

**William** - [@itswillx](https://github.com/itswillx)

---

⭐ Se este projeto foi útil para você, considere dar uma estrela!
