<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineVision</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="logo">CineVision</a>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/">Início</a>
                    <a href="/search">Buscar</a>
                    <a href="/favorites">Favoritos</a>
                    <a href="/settings">Configurações</a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/admin" class="nav-admin">Admin</a>
                    <?php endif; ?>
                    <form action="/auth/logout" method="POST" style="display:inline;">
                        <button type="submit" class="btn-text">Sair</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Entrar</a>
                    <a href="/register" class="btn btn-primary">Criar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($view)) include $view; ?>
    </main>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/favorites.js"></script>
    <script>
        // Handle Supabase auth redirects (recovery, email confirmation, etc.)
        (function() {
            const hash = window.location.hash;
            if (hash && hash.includes('access_token') && hash.includes('type=recovery')) {
                // Redirect to reset password page with the hash
                window.location.href = '/auth/reset' + hash;
            }
        })();
    </script>
</body>
</html>
