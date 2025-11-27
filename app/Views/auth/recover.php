<div class="auth-container">
    <h2 class="auth-title">Redefinir Senha</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="text-danger text-center" style="margin-bottom: 15px;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="text-success text-center" style="margin-bottom: 15px;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <form action="/auth/recover" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Enviar link</button>
    </form>
    <div class="text-center mt-2">
        <a href="/login" style="color: var(--primary);">Voltar ao login</a>
    </div>
</div>
