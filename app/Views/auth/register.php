<div class="auth-container">
    <h2 class="auth-title">Criar Conta</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="text-danger text-center" style="margin-bottom: 15px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="/auth/register" method="POST">
        <div class="form-group">
            <label for="display_name">Nome de Exibição</label>
            <input type="text" id="display_name" name="display_name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Criar Conta</button>
    </form>

    <div class="text-center mt-2">
        <span style="color: var(--text-muted);">Já tem uma conta?</span>
        <a href="/login" style="color: var(--primary);">Entrar</a>
    </div>
</div>
