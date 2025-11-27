<?php

namespace App\Controllers;

use App\Models\Settings;
use App\Services\RealDebridService;

class SettingsController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        error_log("SettingsController::index - User ID: " . $_SESSION['user_id']);
        error_log("SettingsController::index - Access token present: " . (isset($_SESSION['access_token']) ? 'YES (len=' . strlen($_SESSION['access_token']) . ')' : 'NO'));

        $settingsModel = new Settings();
        if (isset($_SESSION['access_token'])) {
            $settingsModel->setToken($_SESSION['access_token']);
        }
        $settings = $settingsModel->getByUserId($_SESSION['user_id']);
        error_log("SettingsController::index - Settings loaded: " . ($settings ? 'YES' : 'NO'));
        
        // If RD token exists, try to fetch user info to validate/show status
        $rdInfo = null;
        if (!empty($settings['rd_token'])) {
            $rdService = new RealDebridService($settings['rd_token']);
            $rdInfo = $rdService->getUserInfo();
        }

        $view = __DIR__ . '/../Views/settings.php';
        require __DIR__ . '/../Views/layout.php';
    }

    public function save() {
        // Iniciar output buffering para evitar problemas com headers
        ob_start();
        
        if (!isset($_SESSION['user_id'])) {
            ob_end_clean();
            header('Location: /login');
            exit;
        }

        error_log("SettingsController::save - Starting save process for user: " . $_SESSION['user_id']);
        error_log("SettingsController::save - Access token present: " . (isset($_SESSION['access_token']) ? 'YES' : 'NO'));

        $data = [
            'subtitle_lang' => $_POST['subtitle_lang'] ?? 'pob',
            'quality_pref' => $_POST['quality_pref'] ?? '1080p',
            'rd_token' => !empty($_POST['rd_token']) ? trim($_POST['rd_token']) : '',
            'rd_enabled' => !empty($_POST['rd_token']) ? 1 : (isset($_POST['rd_enabled']) ? 1 : 0)
        ];

        error_log("SettingsController::save - Data to save: " . print_r($data, true));

        try {
            $settingsModel = new Settings();
            if (isset($_SESSION['access_token'])) {
                error_log("SettingsController::save - Setting access token");
                $settingsModel->setToken($_SESSION['access_token']);
            }
            
            $result = $settingsModel->save($_SESSION['user_id'], $data);
            error_log("SettingsController::save - Save result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                $_SESSION['success'] = 'Configurações salvas com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao salvar configurações. Verifique os logs para detalhes.';
                error_log("SettingsController::save - Save returned false/null");
            }
        } catch (\Exception $e) {
            error_log("SettingsController::save - Exception: " . $e->getMessage());
            $_SESSION['error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
        } catch (\Error $e) {
            error_log("SettingsController::save - Error: " . $e->getMessage());
            $_SESSION['error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
        }

        ob_end_clean();
        header('Location: /settings');
        exit;
    }
}
