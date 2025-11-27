<?php $config = require __DIR__ . '/../../../config/env.php'; ?>
<div class="auth-container">
    <h2 class="auth-title">Definir Nova Senha</h2>
    <div id="status" class="text-center" style="margin-bottom: 15px;"></div>
    <form id="reset-form">
        <div class="form-group">
            <label for="password">Nova Senha</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Atualizar senha</button>
    </form>
    <div class="text-center mt-2">
        <a href="/login" style="color: var(--primary);">Voltar ao login</a>
    </div>
</div>
<script>
const supabaseUrl = <?php echo json_encode($config['SUPABASE_URL']); ?>;
const apiKey = <?php echo json_encode($config['SUPABASE_KEY']); ?>;

function getAccessTokenFromHash() {
  const hash = window.location.hash.substring(1);
  const params = new URLSearchParams(hash);
  return params.get('access_token');
}

document.getElementById('reset-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const token = getAccessTokenFromHash();
  const password = document.getElementById('password').value;
  const statusEl = document.getElementById('status');
  if (!token) {
    statusEl.textContent = 'Token inválido ou ausente.';
    statusEl.className = 'text-danger text-center';
    return;
  }
  try {
    const res = await fetch(`${supabaseUrl}/auth/v1/user`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'apikey': apiKey,
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ password })
    });
    if (res.ok) {
      statusEl.textContent = 'Senha atualizada com sucesso. Faça login novamente.';
      statusEl.className = 'text-success text-center';
    } else {
      const text = await res.text();
      statusEl.textContent = 'Erro: ' + text;
      statusEl.className = 'text-danger text-center';
    }
  } catch (err) {
    statusEl.textContent = 'Erro de rede.';
    statusEl.className = 'text-danger text-center';
  }
});
</script>
