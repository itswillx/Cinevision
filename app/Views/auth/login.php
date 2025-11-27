<div class="auth-container">
    <h2 class="auth-title">Entrar</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="text-danger text-center" style="margin-bottom: 15px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="/auth/login" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Entrar</button>
    </form>

    <div class="text-center mt-2">
        <span style="color: var(--text-muted);">Não tem uma conta?</span>
        <a href="/register" style="color: var(--primary);">Cadastre-se</a>
    </div>
    <div class="text-center mt-2">
        <a href="/recover" style="color: var(--primary);">Esqueci minha senha</a>
    </div>
</div>
