<?php

namespace App\Services;

class SupabaseService {
    private $url;
    private $key;
    private $headers;
    private $accessToken;

    public function __construct() {
        $config = require __DIR__ . '/../../config/env.php';
        $this->url = $config['SUPABASE_URL'];
        $this->key = $config['SUPABASE_KEY'];
        $this->accessToken = null;
        $this->headers = [];
        $this->buildHeaders();
    }

    private function buildHeaders() {
        $token = $this->accessToken ?: $this->key;
        $this->headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    public function setAccessToken($token) {
        $this->accessToken = $token;
        $this->buildHeaders();
        return $this;
    }

    public function clearAccessToken() {
        $this->accessToken = null;
        $this->buildHeaders();
        return $this;
    }

    /**
     * Select records from a table
     * @param string $table Table name
     * @param array $filters Filters as key-value pairs (e.g., ['email' => 'test@test.com'])
     * @param array $options Additional options (select, order, limit, single)
     * @return array|null
     */
    public function select($table, $filters = [], $options = []) {
        $url = $this->url . '/rest/v1/' . $table;
        $params = [];

        // Build select parameter
        if (isset($options['select'])) {
            $params['select'] = $options['select'];
        }

        // Build filters
        foreach ($filters as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }

        // Build order
        if (isset($options['order'])) {
            $params['order'] = $options['order'];
        }

        // Build limit
        if (isset($options['limit'])) {
            $params['limit'] = $options['limit'];
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = $this->request('GET', $url);
        
        // Verificar se houve erro na requisição
        if (self::hasError($response)) {
            return null;
        }

        // If single option is set, return first item or null
        if (isset($options['single']) && $options['single']) {
            return (is_array($response) && isset($response[0])) ? $response[0] : null;
        }

        return $response;
    }

    public function authSignup($email, $password, $displayName = '') {
        $url = $this->url . '/auth/v1/signup';
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        // Passar display_name nos metadados para o trigger poder usar
        if (!empty($displayName)) {
            $data['data'] = [
                'display_name' => $displayName
            ];
        }
        
        return $this->requestAuth('POST', $url, $data);
    }

    public function authLogin($email, $password) {
        $url = $this->url . '/auth/v1/token?grant_type=password';
        return $this->requestAuth('POST', $url, [
            'email' => $email,
            'password' => $password
        ]);
    }

    public function authRecover($email, $redirectTo) {
        $url = $this->url . '/auth/v1/recover';
        return $this->requestAuth('POST', $url, [
            'email' => $email,
            'redirect_to' => $redirectTo
        ]);
    }

    public function authUpdatePassword($newPassword) {
        $url = $this->url . '/auth/v1/update';
        return $this->requestAuth('POST', $url, [
            'password' => $newPassword
        ]);
    }

    /**
     * Refresh the access token using a refresh token
     * @param string $refreshToken
     * @return array
     */
    public function authRefreshToken($refreshToken) {
        $url = $this->url . '/auth/v1/token?grant_type=refresh_token';
        return $this->requestAuth('POST', $url, [
            'refresh_token' => $refreshToken
        ]);
    }

    /**
     * Get the current user info using the access token
     * @return array
     */
    public function getUser() {
        $url = $this->url . '/auth/v1/user';
        return $this->requestAuth('GET', $url);
    }

    private function requestAuth($method, $url, $data = null) {
        $ch = curl_init();
        $headers = [
            'apikey: ' . $this->key,
            'Content-Type: application/json'
        ];
        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = null;
        if (curl_errno($ch)) {
            $err = curl_error($ch);
        }
        curl_close($ch);
        $json = null;
        if ($response) {
            $json = json_decode($response, true);
        }
        return [
            'ok' => ($httpCode >= 200 && $httpCode < 300),
            'status' => $httpCode,
            'json' => $json,
            'text' => $response,
            'error' => $err
        ];
    }

    /**
     * Insert a record into a table
     * @param string $table Table name
     * @param array $data Data to insert
     * @return array|null Inserted record
     */
    public function insert($table, $data) {
        $url = $this->url . '/rest/v1/' . $table;
        return $this->request('POST', $url, $data);
    }

    /**
     * Update records in a table
     * @param string $table Table name
     * @param array $filters Filters to match records
     * @param array $data Data to update
     * @return array|null Updated records
     */
    public function update($table, $filters, $data) {
        $url = $this->url . '/rest/v1/' . $table;
        $params = [];

        foreach ($filters as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $this->request('PATCH', $url, $data);
    }

    /**
     * Delete records from a table
     * @param string $table Table name
     * @param array $filters Filters to match records
     * @return bool Success status
     */
    public function delete($table, $filters) {
        $url = $this->url . '/rest/v1/' . $table;
        $params = [];

        foreach ($filters as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = $this->request('DELETE', $url);
        return $response !== null;
    }

    /**
     * Execute a raw HTTP request
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array|null $data Request body
     * @return array|null Response data
     */
    private function request($method, $url, $data = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        curl_close($ch);
        
        // Tratar erros de conexão
        if ($curlErrno) {
            $errorMsg = $this->getConnectionErrorMessage($curlErrno, $curlError);
            error_log("Supabase Connection Error [{$curlErrno}]: {$curlError}");
            return ['_error' => true, '_message' => $errorMsg, '_code' => $curlErrno];
        }

        // Handle different HTTP codes
        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            return $decoded;
        } else {
            $errorMsg = $this->getHttpErrorMessage($httpCode, $response);
            error_log("Supabase API Error (HTTP $httpCode): " . $response);
            return ['_error' => true, '_message' => $errorMsg, '_code' => $httpCode, '_response' => $response];
        }
    }
    
    /**
     * Retorna mensagem amigável para erros de conexão
     */
    private function getConnectionErrorMessage($errno, $error) {
        $messages = [
            CURLE_COULDNT_CONNECT => 'Não foi possível conectar ao servidor. Verifique sua conexão.',
            CURLE_COULDNT_RESOLVE_HOST => 'Servidor não encontrado. Verifique a URL do Supabase.',
            CURLE_OPERATION_TIMEDOUT => 'A conexão expirou. Tente novamente.',
            CURLE_SSL_CONNECT_ERROR => 'Erro de conexão SSL. Verifique a configuração.',
        ];
        
        return $messages[$errno] ?? "Erro de conexão: {$error}";
    }
    
    /**
     * Retorna mensagem amigável para erros HTTP
     */
    private function getHttpErrorMessage($httpCode, $response) {
        $decoded = json_decode($response, true);
        $apiMessage = $decoded['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? null;
        
        $messages = [
            400 => 'Requisição inválida.',
            401 => 'Não autorizado. Faça login novamente.',
            403 => 'Acesso negado. Você não tem permissão para esta ação.',
            404 => 'Recurso não encontrado.',
            409 => 'Conflito de dados.',
            422 => 'Dados inválidos.',
            429 => 'Muitas requisições. Aguarde um momento.',
            500 => 'Erro interno do servidor.',
            502 => 'Servidor temporariamente indisponível.',
            503 => 'Serviço indisponível. Tente novamente mais tarde.',
        ];
        
        $baseMessage = $messages[$httpCode] ?? "Erro HTTP {$httpCode}";
        
        if ($apiMessage) {
            return "{$baseMessage} ({$apiMessage})";
        }
        
        return $baseMessage;
    }
    
    /**
     * Verifica se o resultado contém erro
     */
    public static function hasError($result) {
        return is_array($result) && isset($result['_error']) && $result['_error'] === true;
    }
    
    /**
     * Obtém mensagem de erro do resultado
     */
    public static function getErrorMessage($result) {
        if (self::hasError($result)) {
            return $result['_message'] ?? 'Erro desconhecido';
        }
        return null;
    }

    /**
     * Check if a record exists
     * @param string $table Table name
     * @param array $filters Filters
     * @return bool
     */
    public function exists($table, $filters) {
        $result = $this->select($table, $filters, ['limit' => 1]);
        return !empty($result);
    }

    /**
     * Upsert (insert or update) a record
     * @param string $table Table name
     * @param array $data Data to upsert
     * @param string $onConflict Column(s) to use for conflict resolution
     * @return array|null Upserted record
     */
    public function upsert($table, $data, $onConflict = 'id') {
        $url = $this->url . '/rest/v1/' . $table . '?on_conflict=' . $onConflict;
        
        error_log("[SUPABASE] Upsert - URL: $url");
        error_log("[SUPABASE] Upsert - Data: " . json_encode($data));
        
        // Construir headers com merge-duplicates
        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'Prefer: resolution=merge-duplicates,return=representation'
        ];
        
        error_log("[SUPABASE] Upsert - Using token: " . substr($this->accessToken, 0, 50) . "...");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("[SUPABASE] Upsert - HTTP Code: $httpCode");
        error_log("[SUPABASE] Upsert - Response: " . $response);
        if ($curlError) {
            error_log("[SUPABASE] Upsert - cURL Error: " . $curlError);
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            return is_array($decoded) && isset($decoded[0]) ? $decoded[0] : $decoded;
        }
        
        error_log("[SUPABASE] Upsert FAILED (HTTP $httpCode): " . $response);
        return null;
    }

    /**
     * Select all records from a table (for admin usage)
     * @param string $table Table name
     * @param array $options Additional options
     * @return array|null
     */
    public function selectAll($table, $options = []) {
        $url = $this->url . '/rest/v1/' . $table;
        $params = [];

        if (isset($options['select'])) {
            $params['select'] = $options['select'];
        }

        if (isset($options['order'])) {
            $params['order'] = $options['order'];
        }

        if (isset($options['limit'])) {
            $params['limit'] = $options['limit'];
        }

        if (isset($options['offset'])) {
            $params['offset'] = $options['offset'];
        }

        // Filtros customizados (para busca)
        if (isset($options['filters'])) {
            foreach ($options['filters'] as $key => $value) {
                $params[$key] = $value;
            }
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = $this->request('GET', $url);
        
        if (self::hasError($response)) {
            return null;
        }

        return $response;
    }

    /**
     * Get the service role key if configured
     * @return string|null
     */
    public function getServiceRoleKey() {
        $config = require __DIR__ . '/../../config/env.php';
        return $config['SUPABASE_SERVICE_KEY'] ?? null;
    }

    /**
     * Set service role for admin operations
     * @return $this
     */
    public function useServiceRole() {
        $serviceKey = $this->getServiceRoleKey();
        if ($serviceKey) {
            $this->accessToken = $serviceKey;
            $this->buildHeaders();
            error_log("[SUPABASE] Service role ativado");
        } else {
            error_log("[SUPABASE] ERRO: Service role key não encontrada!");
        }
        return $this;
    }

    /**
     * Update with logging for debugging
     */
    public function updateWithLog($table, $filters, $data) {
        error_log("[SUPABASE] Update - Table: $table, Filters: " . json_encode($filters) . ", Data: " . json_encode($data));
        
        $url = $this->url . '/rest/v1/' . $table;
        $params = [];

        foreach ($filters as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        error_log("[SUPABASE] Update URL: $url");
        error_log("[SUPABASE] Headers: " . json_encode($this->headers));

        $result = $this->request('PATCH', $url, $data);
        
        error_log("[SUPABASE] Update Result: " . json_encode($result));
        
        return $result;
    }
}
