<?php

namespace App\Models;

use App\Services\SupabaseService;

class Settings {
    private $supabase;

    public function __construct() {
        $this->supabase = new SupabaseService();
    }

    public function setToken($token) {
        $this->supabase->setAccessToken($token);
    }

    public function getByUserId($userId) {
        return $this->supabase->select('user_settings_v2', ['user_id' => $userId], ['single' => true]);
    }

    public function save($userId, $data) {
        error_log("Settings::save - Starting save for user: $userId");
        
        // Check if settings exist
        $existing = $this->getByUserId($userId);
        error_log("Settings::save - Existing settings: " . ($existing ? 'FOUND' : 'NOT FOUND'));

        $settingsData = [
            'user_id' => $userId,
            'subtitle_lang' => $data['subtitle_lang'] ?? 'pob',
            'quality_pref' => $data['quality_pref'] ?? '1080p',
            'rd_enabled' => $data['rd_enabled'] ?? false,
            'rd_token' => $data['rd_token'] ?? null
        ];

        error_log("Settings::save - Settings data: " . print_r($settingsData, true));

        try {
            if ($existing) {
                // Update existing settings
                error_log("Settings::save - Updating existing settings");
                unset($settingsData['user_id']); // Don't update the primary key
                $result = $this->supabase->update('user_settings_v2', ['user_id' => $userId], $settingsData);
                error_log("Settings::save - Update result: " . print_r($result, true));
            } else {
                error_log("Settings::save - Inserting new settings");
                $result = $this->supabase->insert('user_settings_v2', $settingsData);
                error_log("Settings::save - Insert result: " . print_r($result, true));
            }

            // Verificar se houve erro na resposta
            if (\App\Services\SupabaseService::hasError($result)) {
                $errorMsg = \App\Services\SupabaseService::getErrorMessage($result);
                error_log("Settings::save - Supabase error: " . $errorMsg);
                return false;
            }

            $success = $result !== null;
            error_log("Settings::save - Final result: " . ($success ? 'SUCCESS' : 'FAILED'));
            return $success;
        } catch (\Exception $e) {
            error_log("Settings::save - Exception: " . $e->getMessage());
            return false;
        } catch (\Error $e) {
            error_log("Settings::save - Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Helper to encrypt/decrypt token if needed. 
    // For MVP we might store plain or simple encryption.
    // Ideally use OpenSSL with key from env.
}
