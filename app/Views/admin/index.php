<div class="admin-panel">
    <div class="admin-header">
        <h1>Painel de Administração</h1>
        <p class="text-muted">Gerencie os usuários do CineVision</p>
    </div>

    <!-- Filtros -->
    <div class="admin-filters">
        <div class="filter-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nome ou email...">
        </div>
        <div class="filter-group">
            <select id="roleFilter" class="form-control">
                <option value="">Todos os papéis</option>
                <option value="admin">Administradores</option>
                <option value="user">Usuários</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-control">
                <option value="">Todos os status</option>
                <option value="active">Ativos</option>
                <option value="disabled">Desabilitados</option>
            </select>
        </div>
        <button type="button" class="btn btn-primary" onclick="loadUsers()">Filtrar</button>
    </div>

    <!-- Estatísticas -->
    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-number" id="totalUsers">0</span>
            <span class="stat-label">Total de Usuários</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="activeUsers">0</span>
            <span class="stat-label">Usuários Ativos</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="adminUsers">0</span>
            <span class="stat-label">Administradores</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="disabledUsers">0</span>
            <span class="stat-label">Desabilitados</span>
        </div>
    </div>

    <!-- Tabela de usuários -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Papel</th>
                    <th>Criado em</th>
                    <th>Último Acesso</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?= htmlspecialchars($user['id']) ?>" class="<?= !empty($user['disabled']) ? 'disabled-row' : '' ?>">
                            <td><?= htmlspecialchars($user['display_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $user['role'] === 'admin' ? 'admin' : 'user' ?>">
                                    <?= $user['role'] === 'admin' ? 'Admin' : 'Usuário' ?>
                                </span>
                            </td>
                            <td><?php if (!empty($user['created_at'])): $dt = new DateTime($user['created_at'], new DateTimeZone('UTC')); $dt->setTimezone(new DateTimeZone('America/Sao_Paulo')); echo $dt->format('d/m/Y H:i'); else: echo '-'; endif; ?></td>
                            <td><?php if (!empty($user['last_access_at'])): $dt = new DateTime($user['last_access_at'], new DateTimeZone('UTC')); $dt->setTimezone(new DateTimeZone('America/Sao_Paulo')); echo $dt->format('d/m/Y H:i'); else: echo 'Nunca'; endif; ?></td>
                            <td>
                                <span class="badge badge-<?= empty($user['disabled']) ? 'active' : 'disabled' ?>">
                                    <?= empty($user['disabled']) ? 'Ativo' : 'Desabilitado' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <button class="btn-icon" onclick="openEditModal('<?= htmlspecialchars($user['id']) ?>')" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                </button>
                                <?php if (empty($user['disabled'])): ?>
                                    <button class="btn-icon btn-danger" onclick="disableUser('<?= htmlspecialchars($user['id']) ?>')" title="Desabilitar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-icon btn-success" onclick="restoreUser('<?= htmlspecialchars($user['id']) ?>')" title="Restaurar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Nenhum usuário encontrado</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Edição -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Usuário</h2>
            <button class="btn-icon" onclick="closeEditModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="editUserForm" onsubmit="saveUser(event)">
            <input type="hidden" id="editUserId" name="user_id">
            
            <div class="form-group">
                <label for="editDisplayName">Nome de Exibição</label>
                <input type="text" id="editDisplayName" name="display_name" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="editRole">Papel</label>
                <select id="editRole" name="role" class="form-control">
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="editDisabled" name="disabled">
                    <span>Conta Desabilitada</span>
                </label>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeConfirmModal()"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2 id="confirmTitle">Confirmar Ação</h2>
        </div>
        <p id="confirmMessage"></p>
        <div class="form-group" id="clearDataGroup" style="display: none;">
            <label class="checkbox-label">
                <input type="checkbox" id="clearDataCheckbox">
                <span>Limpar favoritos e configurações do usuário</span>
            </label>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
        </div>
    </div>
</div>

<style>
.admin-panel {
    padding: 20px 0;
}

.admin-header {
    margin-bottom: 30px;
}

.admin-header h1 {
    font-size: 2rem;
    margin-bottom: 5px;
}

.admin-filters {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    border: 1px solid var(--border-color);
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.admin-table-container {
    background: var(--card-bg);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.admin-table th {
    background: rgba(0,0,0,0.3);
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.admin-table tr:hover {
    background: rgba(255,255,255,0.02);
}

.admin-table tr.disabled-row {
    opacity: 0.6;
}

.admin-table .actions {
    display: flex;
    gap: 8px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-admin {
    background: rgba(229, 9, 20, 0.2);
    color: var(--primary);
}

.badge-user {
    background: rgba(100, 100, 100, 0.2);
    color: var(--text-muted);
}

.badge-active {
    background: rgba(46, 204, 113, 0.2);
    color: #2ecc71;
}

.badge-disabled {
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
}

.btn-icon {
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 4px;
    padding: 8px;
    cursor: pointer;
    color: var(--text-main);
    transition: all 0.2s;
}

.btn-icon:hover {
    background: rgba(255,255,255,0.2);
}

.btn-icon.btn-danger:hover {
    background: rgba(231, 76, 60, 0.3);
    color: #e74c3c;
}

.btn-icon.btn-success:hover {
    background: rgba(46, 204, 113, 0.3);
    color: #2ecc71;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.modal-content {
    position: relative;
    background: var(--card-bg);
    border-radius: 12px;
    padding: 25px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid var(--border-color);
}

.modal-content.modal-sm {
    max-width: 400px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

.btn-secondary {
    background: rgba(255,255,255,0.1);
    color: var(--text-main);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.2);
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
}

.text-muted {
    color: var(--text-muted);
}

.text-center {
    text-align: center;
}

/* Responsivo */
@media (max-width: 768px) {
    .admin-table {
        display: block;
        overflow-x: auto;
    }
    
    .admin-filters {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
}
</style>

<script>
// Dados iniciais dos usuários
const initialUsers = <?= json_encode($users ?? []) ?>;

// Atualizar estatísticas
function updateStats(users) {
    const total = users.length;
    const active = users.filter(u => !u.disabled).length;
    const admins = users.filter(u => u.role === 'admin').length;
    const disabled = users.filter(u => u.disabled).length;
    
    document.getElementById('totalUsers').textContent = total;
    document.getElementById('activeUsers').textContent = active;
    document.getElementById('adminUsers').textContent = admins;
    document.getElementById('disabledUsers').textContent = disabled;
}

// Carregar usuários via API
async function loadUsers() {
    const search = document.getElementById('searchInput').value;
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (role) params.append('role', role);
    if (status) params.append('status', status);
    
    try {
        const response = await fetch('/api/admin/users?' + params.toString());
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        renderUsersTable(data.users);
        updateStats(data.users);
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
        alert('Erro ao carregar usuários');
    }
}

// Renderizar tabela de usuários
function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Nenhum usuário encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => {
        return `
        <tr data-user-id="${user.id}" class="${user.disabled ? 'disabled-row' : ''}">
            <td>${escapeHtml(user.display_name || '-')}</td>
            <td>${escapeHtml(user.email || '-')}</td>
            <td>
                <span class="badge badge-${user.role === 'admin' ? 'admin' : 'user'}">
                    ${user.role === 'admin' ? 'Admin' : 'Usuário'}
                </span>
            </td>
            <td>${user.created_at ? formatDate(user.created_at) : '-'}</td>
            <td>${user.last_access_at ? formatDate(user.last_access_at) : 'Nunca'}</td>
            <td>
                <span class="badge badge-${!user.disabled ? 'active' : 'disabled'}">
                    ${!user.disabled ? 'Ativo' : 'Desabilitado'}
                </span>
            </td>
            <td class="actions">
                <button class="btn-icon" onclick="openEditModal('${user.id}')" title="Editar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                </button>
                ${!user.disabled ? `
                    <button class="btn-icon btn-danger" onclick="disableUser('${user.id}')" title="Desabilitar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                    </button>
                ` : `
                    <button class="btn-icon btn-success" onclick="restoreUser('${user.id}')" title="Restaurar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                    </button>
                `}
            </td>
        </tr>
    `}).join('');
}

// Abrir modal de edição
async function openEditModal(userId) {
    try {
        const response = await fetch('/api/admin/user?user_id=' + userId);
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        const user = data.user;
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editDisplayName').value = user.display_name || '';
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editRole').value = user.role || 'user';
        document.getElementById('editDisabled').checked = !!user.disabled;
        
        document.getElementById('editModal').style.display = 'flex';
    } catch (error) {
        console.error('Erro ao carregar usuário:', error);
        alert('Erro ao carregar dados do usuário');
    }
}

// Fechar modal de edição
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Salvar usuário
async function saveUser(event) {
    event.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const displayName = document.getElementById('editDisplayName').value;
    const email = document.getElementById('editEmail').value;
    const role = document.getElementById('editRole').value;
    const disabled = document.getElementById('editDisabled').checked;
    
    try {
        const response = await fetch('/api/admin/user/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                display_name: displayName,
                email: email,
                role: role,
                disabled: disabled
            })
        });
        
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        closeEditModal();
        loadUsers();
        alert('Usuário atualizado com sucesso!');
    } catch (error) {
        console.error('Erro ao salvar usuário:', error);
        alert('Erro ao salvar usuário');
    }
}

// Variáveis para modal de confirmação
let confirmCallback = null;

// Desabilitar usuário
function disableUser(userId) {
    document.getElementById('confirmTitle').textContent = 'Desabilitar Usuário';
    document.getElementById('confirmMessage').textContent = 'Tem certeza que deseja desabilitar este usuário? Ele não poderá mais fazer login.';
    document.getElementById('clearDataGroup').style.display = 'block';
    document.getElementById('clearDataCheckbox').checked = false;
    
    confirmCallback = async () => {
        const clearData = document.getElementById('clearDataCheckbox').checked;
        
        try {
            const response = await fetch('/api/admin/user/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    clear_data: clearData
                })
            });
            
            const data = await response.json();
            
            if (data.error) {
                alert(data.error);
                return;
            }
            
            closeConfirmModal();
            loadUsers();
            alert('Usuário desabilitado com sucesso!');
        } catch (error) {
            console.error('Erro ao desabilitar usuário:', error);
            alert('Erro ao desabilitar usuário');
        }
    };
    
    document.getElementById('confirmBtn').onclick = confirmCallback;
    document.getElementById('confirmModal').style.display = 'flex';
}

// Restaurar usuário
function restoreUser(userId) {
    document.getElementById('confirmTitle').textContent = 'Restaurar Usuário';
    document.getElementById('confirmMessage').textContent = 'Tem certeza que deseja restaurar este usuário? Ele poderá fazer login novamente.';
    document.getElementById('clearDataGroup').style.display = 'none';
    
    confirmCallback = async () => {
        try {
            const response = await fetch('/api/admin/user/restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId
                })
            });
            
            const data = await response.json();
            
            if (data.error) {
                alert(data.error);
                return;
            }
            
            closeConfirmModal();
            loadUsers();
            alert('Usuário restaurado com sucesso!');
        } catch (error) {
            console.error('Erro ao restaurar usuário:', error);
            alert('Erro ao restaurar usuário');
        }
    };
    
    document.getElementById('confirmBtn').onclick = confirmCallback;
    document.getElementById('confirmBtn').className = 'btn btn-primary';
    document.getElementById('confirmModal').style.display = 'flex';
}

// Fechar modal de confirmação
function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    document.getElementById('confirmBtn').className = 'btn btn-danger';
    confirmCallback = null;
}

// Utilitários
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' }) + ' ' + date.toLocaleTimeString('pt-BR', { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit' });
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    updateStats(initialUsers);
    
    // Busca ao pressionar Enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadUsers();
        }
    });
});
</script>
