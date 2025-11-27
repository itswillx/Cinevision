<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\SupabaseService;

class AuthController {
    
    /**
     * Verifica se um email está associado a uma conta desabilitada
     * @param string $email
     * @return bool true se desabilitado
     */
    private function isEmailDisabled(string $email): bool {
        $config = require __DIR__ . '/../../config/env.php';
        $supabaseUrl = $config['SUPABASE_URL'];
        $serviceKey = $config['SUPABASE_SERVICE_KEY'];
        
        // Normalizar email
        $email = trim(strtolower($email));
        
        // Query direta - buscar por email (case insensitive usando lower())
        $url = $supabaseUrl . '/rest/v1/rpc/check_user_disabled';
        
        // Se a função RPC não existir, usar query direta
        $url = $supabaseUrl . '/rest/v1/profiles?select=id,email,disabled&limit=100';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $serviceKey,
            'Authorization: Bearer ' . $serviceKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("[AUTH] isEmailDisabled - URL: $url");
        error_log("[AUTH] isEmailDisabled - HTTP $httpCode");
        error_log("[AUTH] isEmailDisabled - Buscando email: $email");
        
        $profiles = json_decode($response, true);
        
        if (!is_array($profiles)) {
            error_log("[AUTH] isEmailDisabled - Resposta inválida");
            return false;
        }
        
        // Procurar o email na lista (case insensitive)
        foreach ($profiles as $profile) {
            $profileEmail = trim(strtolower($profile['email'] ?? ''));
            if ($profileEmail === $email) {
                $disabled = $profile['disabled'] ?? false;
                $isDisabled = ($disabled === true || $disabled === 't' || $disabled === 'true' || $disabled === 1 || $disabled === '1');
                error_log("[AUTH] isEmailDisabled - Encontrado! Email: $profileEmail, Disabled: " . var_export($disabled, true) . ", Result: " . ($isDisabled ? 'DISABLED' : 'ACTIVE'));
                return $isDisabled;
            }
        }
        
        error_log("[AUTH] isEmailDisabled - Email não encontrado no banco");
        return false;
    }
    
    public function loginForm() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        $view = __DIR__ . '/../Views/auth/login.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function registerForm() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        $view = __DIR__ . '/../Views/auth/register.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function register() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $displayName = $_POST['display_name'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            $_SESSION['error'] = 'Email e senha são obrigatórios.';
            header('Location: /register');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            $_SESSION['error'] = 'Email inválido.';
            header('Location: /register');
            exit;
        }
        if (strlen($password) < 8) {
            http_response_code(400);
            $_SESSION['error'] = 'Senha deve ter pelo menos 8 caracteres.';
            header('Location: /register');
            exit;
        }

        // VERIFICAR SE O EMAIL JÁ PERTENCE A UM USUÁRIO DESABILITADO
        if ($this->isEmailDisabled($email)) {
            error_log("[AUTH] Registro - BLOQUEADO: Email desabilitado: $email");
            $_SESSION['error'] = 'Este email está associado a uma conta desabilitada. Entre em contato com o administrador.';
            header('Location: /register');
            exit;
        }

        $svc = new SupabaseService();
        $signup = $svc->authSignup($email, $password, $displayName);
        if (!$signup['ok']) {
            $msg = 'Erro ao criar conta.';
            if (is_array($signup['json'])) {
                $msg = $signup['json']['msg'] ?? $signup['json']['error_description'] ?? $msg;
            }
            if ($signup['status'] === 400) {
                if (isset($signup['json']['msg']) && stripos($signup['json']['msg'], 'signups') !== false) {
                    $msg = 'Cadastro desabilitado no projeto Supabase.';
                }
            }
            http_response_code($signup['status'] ?: 400);
            $_SESSION['error'] = $msg;
            header('Location: /register');
            exit;
        }

        $token = $signup['json']['access_token'] ?? null;
        $userId = $signup['json']['user']['id'] ?? null;
        $needsEmailConfirmation = empty($token) && !empty($userId);

        error_log("[AUTH] Registro - Resposta signup: userId=$userId, hasToken=" . ($token ? 'yes' : 'no') . ", needsConfirmation=" . ($needsEmailConfirmation ? 'yes' : 'no'));

        if (!$userId) {
            http_response_code(500);
            $_SESSION['error'] = 'Cadastro realizado, mas não foi possível obter o usuário.';
            header('Location: /register');
            exit;
        }

        // Determinar role inicial
        $config = require __DIR__ . '/../../config/env.php';
        $adminEmail = $config['ADMIN_EMAIL'] ?? '';
        $role = ($email === $adminEmail) ? 'admin' : 'user';

        // IMPORTANTE: Salvar display_name IMEDIATAMENTE usando service role
        error_log("[AUTH] Registro - Salvando perfil para user_id: $userId, email: $email, display_name: '$displayName'");

        // Usar service role para CRIAR/ATUALIZAR o perfil diretamente
        $svcAdmin = new SupabaseService();
        $svcAdmin->useServiceRole();
        
        // Aguardar um pouco para o trigger ter chance de executar
        usleep(300000); // 300ms
        
        $profileData = [
            'id' => $userId,
            'display_name' => $displayName,
            'email' => $email,
            'role' => $role,
            'last_access_at' => date('c'),
            'disabled' => false
        ];
        
        error_log("[AUTH] Registro - Tentando UPSERT direto com service role");
        
        // Usar UPSERT diretamente - isso cria se não existe ou atualiza se existe
        $upsertRes = $svcAdmin->upsert('profiles', $profileData, 'id');
        
        if ($upsertRes === null) {
            error_log("[AUTH] Registro - Upsert retornou null, verificando se precisa INSERT...");
            
            // Tentar INSERT direto
            $insertRes = $svcAdmin->insert('profiles', $profileData);
            
            if ($insertRes === null || SupabaseService::hasError($insertRes)) {
                error_log("[AUTH] Registro - INSERT também falhou, tentando UPDATE...");
                
                // Última tentativa: UPDATE
                unset($profileData['id']);
                unset($profileData['disabled']);
                $updateRes = $svcAdmin->update('profiles', ['id' => $userId], $profileData);
                
                if ($updateRes === null || SupabaseService::hasError($updateRes)) {
                    error_log("[AUTH] Registro - TODAS AS TENTATIVAS FALHARAM para: $email");
                } else {
                    error_log("[AUTH] Registro - UPDATE funcionou para: $email");
                }
            } else {
                error_log("[AUTH] Registro - INSERT funcionou para: $email");
            }
        } else {
            error_log("[AUTH] Registro - UPSERT funcionou! display_name: '$displayName'");
        }

        // Se precisa confirmação de email, redirecionar para página de sucesso
        if ($needsEmailConfirmation) {
            $_SESSION['success'] = 'Conta criada! Verifique seu email para confirmar o cadastro.';
            header('Location: /login');
            exit;
        }

        // Login automático se não precisa confirmação
        if ($token) {
            $svc->setAccessToken($token);
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $displayName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        if ($token) {
            $_SESSION['access_token'] = $token;
        }
        header('Location: /');
        exit;
    }

    public function login() {
        $emailInput = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $email = trim(strtolower($emailInput)); // Normalizar email
        
        // VERIFICAÇÃO 1: Checar se conta está desabilitada ANTES de tudo
        if ($this->isEmailDisabled($email)) {
            error_log("[AUTH] Login - BLOQUEADO NA VERIFICAÇÃO INICIAL: $email");
            $_SESSION['error'] = 'Sua conta foi desabilitada. Entre em contato com o administrador.';
            header('Location: /login');
            exit;
        }

        $config = require __DIR__ . '/../../config/env.php';
        
        $svc = new SupabaseService();
        $loginRes = $svc->authLogin($emailInput, $password); // Usar email original para auth
        
        if (!$loginRes['ok'] || !isset($loginRes['json']['access_token']) || !isset($loginRes['json']['user'])) {
            $msg = 'Credenciais inválidas.';
            if (isset($loginRes['json']['error_description'])) {
                $msg = $loginRes['json']['error_description'];
            } elseif (isset($loginRes['json']['msg'])) {
                $msg = $loginRes['json']['msg'];
            }
            $_SESSION['error'] = $msg;
            header('Location: /login');
            exit;
        }

        $token = $loginRes['json']['access_token'];
        $userId = $loginRes['json']['user']['id'] ?? null;
        if (!$userId) {
            $_SESSION['error'] = 'Credenciais inválidas.';
            header('Location: /login');
            exit;
        }

        $svc->setAccessToken($token);
        
        // Log: Iniciando login
        error_log("[AUTH] Login - Buscando perfil para user_id: $userId, email: $email");
        
        // IMPORTANTE: Usar service role para verificar disabled (RLS pode ocultar perfis desabilitados)
        $svcAdmin = new SupabaseService();
        $svcAdmin->useServiceRole();
        $profile = $svcAdmin->select('profiles', ['id' => $userId], ['single' => true]);
        
        // Se não encontrou por ID, tentar por email
        if ($profile === null || empty($profile)) {
            $profile = $svcAdmin->select('profiles', ['email' => $email], ['single' => true]);
        }
        
        // Admin email já carregado acima
        $adminEmail = $config['ADMIN_EMAIL'] ?? '';
        
        // VERIFICAÇÃO EXTRA: Se perfil existe e está desabilitado, bloquear
        if ($profile && !empty($profile['disabled'])) {
            $disVal = $profile['disabled'];
            if ($disVal === true || $disVal === 't' || $disVal === 'true' || $disVal === 1 || $disVal === '1') {
                error_log("[AUTH] Login - BLOQUEADO PÓS-AUTH: Usuário desabilitado: $email");
                $_SESSION['error'] = 'Sua conta foi desabilitada. Entre em contato com o administrador.';
                header('Location: /login');
                exit;
            }
        }
        
        // FALLBACK: Se perfil não existe, criar agora (SEM definir disabled!)
        if ($profile === null || empty($profile)) {
            error_log("[AUTH] Login - Perfil não existe, criando via fallback...");
            
            $role = ($email === $adminEmail) ? 'admin' : 'user';
            $profileData = [
                'id' => $userId,
                'display_name' => '',
                'email' => $email,
                'role' => $role,
                'last_access_at' => date('c')
                // NÃO incluir 'disabled' - deixar o default do banco (false)
            ];
            
            // Usar INSERT ao invés de UPSERT para não sobrescrever dados existentes
            $createRes = $svcAdmin->insert('profiles', $profileData);
            
            if ($createRes !== null && !SupabaseService::hasError($createRes)) {
                error_log("[AUTH] Login - Perfil criado via fallback com sucesso");
                $profile = $profileData;
            } else {
                error_log("[AUTH] Login - FALHA ao criar perfil via fallback");
                $profile = ['display_name' => '', 'role' => 'user', 'email' => $email];
            }
        }
        
        $displayName = $profile['display_name'] ?? '';
        $currentRole = $profile['role'] ?? 'user';
        
        // Promover para admin se for o email configurado
        if ($email === $adminEmail && $currentRole !== 'admin') {
            $currentRole = 'admin';
        }
        
        // Preparar dados para atualização (APENAS last_access_at e role se necessário)
        // NOTA: display_name e email só podem ser alterados pelo admin
        $updateData = [
            'last_access_at' => date('c')
        ];
        
        // Atualizar role se promovido a admin (único caso especial)
        if ($email === $adminEmail && ($profile['role'] ?? 'user') !== 'admin') {
            $updateData['role'] = 'admin';
            error_log("[AUTH] Login - Promovendo usuário para admin: $email");
        }
        
        // Executar update
        $updateRes = $svcAdmin->update('profiles', ['id' => $userId], $updateData);
        if (SupabaseService::hasError($updateRes)) {
            error_log("[AUTH] Login - Erro ao atualizar perfil: " . SupabaseService::getErrorMessage($updateRes));
        } else {
            error_log("[AUTH] Login - Perfil atualizado com sucesso para: $email");
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $displayName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $currentRole;
        $_SESSION['access_token'] = $token;
        
        error_log("[AUTH] Login - Sessão criada para: $email (role: $currentRole)");
        
        header('Location: /');
        exit;
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function recoverForm() {
        $view = __DIR__ . '/../Views/auth/recover.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function recover() {
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            $_SESSION['error'] = 'Informe seu email.';
            header('Location: /recover');
            exit;
        }
        
        // Verificar se conta está desabilitada
        if ($this->isEmailDisabled($email)) {
            error_log("[AUTH] Recover - BLOQUEADO: Email desabilitado: $email");
            $_SESSION['error'] = 'Sua conta foi desabilitada. Entre em contato com o administrador.';
            header('Location: /recover');
            exit;
        }
        
        $config = require __DIR__ . '/../../config/env.php';
        $redirect = $config['APP_URL'] . '/auth/reset';
        $svc = new SupabaseService();
        $res = $svc->authRecover($email, $redirect);
        if ($res['ok']) {
            $_SESSION['success'] = 'Se o email estiver cadastrado, você receberá um link de recuperação.';
        } else {
            $_SESSION['error'] = 'Erro ao enviar email de recuperação.';
        }
        header('Location: /recover');
        exit;
    }

    public function resetForm() {
        $view = __DIR__ . '/../Views/auth/reset.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
