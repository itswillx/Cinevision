<?php

namespace App\Controllers;

use App\Services\SupabaseService;

class AdminController {
    
    /**
     * Verifica se o usuário logado é admin
     */
    private function isAdmin(): bool {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Retorna 403 se não for admin
     */
    private function requireAdmin(): void {
        if (!$this->isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acesso negado. Apenas administradores podem acessar esta área.']);
            exit;
        }
    }

    /**
     * Verifica se o usuário está logado
     */
    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Painel de administração - lista de usuários
     */
    public function index() {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            $_SESSION['error'] = 'Acesso negado. Apenas administradores podem acessar esta área.';
            header('Location: /');
            exit;
        }

        $svc = new SupabaseService();
        
        // Usar service role para listar todos os usuários
        $svc->useServiceRole();
        
        $users = $svc->selectAll('profiles', [
            'select' => 'id,display_name,email,role,created_at,last_access_at,disabled',
            'order' => 'created_at.desc'
        ]);

        $view = __DIR__ . '/../Views/admin/index.php';
        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * API: Lista usuários (JSON)
     */
    public function listUsers() {
        $this->requireAuth();
        $this->requireAdmin();

        header('Content-Type: application/json');

        $svc = new SupabaseService();
        $svc->useServiceRole();

        // Parâmetros de busca
        $search = $_GET['search'] ?? '';
        $roleFilter = $_GET['role'] ?? '';
        $statusFilter = $_GET['status'] ?? '';

        $options = [
            'select' => 'id,display_name,email,role,created_at,last_access_at,disabled',
            'order' => 'created_at.desc'
        ];

        // Aplicar filtros via query string do PostgREST
        $filters = [];
        
        if (!empty($search)) {
            // Busca por email ou display_name (usando or)
            $filters['or'] = "(email.ilike.*{$search}*,display_name.ilike.*{$search}*)";
        }
        
        if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'user'])) {
            $filters['role'] = 'eq.' . $roleFilter;
        }
        
        if ($statusFilter === 'active') {
            $filters['disabled'] = 'eq.false';
        } elseif ($statusFilter === 'disabled') {
            $filters['disabled'] = 'eq.true';
        }

        if (!empty($filters)) {
            $options['filters'] = $filters;
        }

        $users = $svc->selectAll('profiles', $options);

        if ($users === null) {
            echo json_encode(['error' => 'Erro ao carregar usuários']);
            return;
        }

        echo json_encode(['users' => $users]);
    }

    /**
     * API: Atualizar usuário
     */
    public function update() {
        $this->requireAuth();
        $this->requireAdmin();

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $userId = $input['user_id'] ?? '';
        $displayName = $input['display_name'] ?? null;
        $email = $input['email'] ?? null;
        $role = $input['role'] ?? null;
        $disabled = isset($input['disabled']) ? (bool)$input['disabled'] : null;

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário é obrigatório']);
            return;
        }

        // Validar role
        if ($role !== null && !in_array($role, ['admin', 'user'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Role inválido. Use "admin" ou "user"']);
            return;
        }

        // Não permitir que admin se remova como admin
        if ($userId === $_SESSION['user_id'] && $role === 'user') {
            http_response_code(400);
            echo json_encode(['error' => 'Você não pode remover seu próprio acesso de administrador']);
            return;
        }

        // Não permitir que admin se desabilite
        if ($userId === $_SESSION['user_id'] && $disabled === true) {
            http_response_code(400);
            echo json_encode(['error' => 'Você não pode desabilitar sua própria conta']);
            return;
        }

        $svc = new SupabaseService();
        $svc->useServiceRole();

        // Montar dados para atualização
        $updateData = [];
        
        if ($displayName !== null) {
            $updateData['display_name'] = $displayName;
        }
        
        if ($email !== null) {
            // Validar formato do email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email inválido']);
                return;
            }
            $updateData['email'] = strtolower(trim($email));
        }
        
        if ($role !== null) {
            $updateData['role'] = $role;
        }
        
        if ($disabled !== null) {
            $updateData['disabled'] = $disabled;
        }

        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum dado para atualizar']);
            return;
        }

        $result = $svc->update('profiles', ['id' => $userId], $updateData);

        if (SupabaseService::hasError($result)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar usuário', 'details' => SupabaseService::getErrorMessage($result)]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso',
            'user' => is_array($result) && isset($result[0]) ? $result[0] : $result
        ]);
    }

    /**
     * API: Exclusão lógica (soft delete) do usuário
     */
    public function delete() {
        $this->requireAuth();
        $this->requireAdmin();

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $userId = $input['user_id'] ?? '';
        $clearData = isset($input['clear_data']) ? (bool)$input['clear_data'] : false;

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário é obrigatório']);
            return;
        }

        // Não permitir exclusão própria
        if ($userId === $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Você não pode excluir sua própria conta']);
            return;
        }

        $svc = new SupabaseService();
        $svc->useServiceRole();

        // Soft delete: marcar como disabled
        $result = $svc->update('profiles', ['id' => $userId], ['disabled' => true]);

        if (SupabaseService::hasError($result)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao desabilitar usuário']);
            return;
        }

        // Opcionalmente limpar dados relacionados
        if ($clearData) {
            // Limpar favoritos
            $svc->delete('favorites_v2', ['user_id' => $userId]);
            
            // Limpar configurações
            $svc->delete('user_settings_v2', ['user_id' => $userId]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Usuário desabilitado com sucesso'
        ]);
    }

    /**
     * API: Restaurar usuário desabilitado
     */
    public function restore() {
        $this->requireAuth();
        $this->requireAdmin();

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $userId = $input['user_id'] ?? '';

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário é obrigatório']);
            return;
        }

        $svc = new SupabaseService();
        $svc->useServiceRole();

        $result = $svc->update('profiles', ['id' => $userId], ['disabled' => false]);

        if (SupabaseService::hasError($result)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao restaurar usuário']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Usuário restaurado com sucesso'
        ]);
    }

    /**
     * API: Obter detalhes de um usuário
     */
    public function getUser() {
        $this->requireAuth();
        $this->requireAdmin();

        header('Content-Type: application/json');

        $userId = $_GET['user_id'] ?? '';

        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário é obrigatório']);
            return;
        }

        $svc = new SupabaseService();
        $svc->useServiceRole();

        $user = $svc->select('profiles', ['id' => $userId], [
            'select' => 'id,display_name,email,role,created_at,last_access_at,disabled',
            'single' => true
        ]);

        if ($user === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuário não encontrado']);
            return;
        }

        echo json_encode(['user' => $user]);
    }
}
