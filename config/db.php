<?php

/**
 * This file is kept for compatibility but no longer used.
 * The system uses Supabase for all data storage.
 */

// Load environment config
function getConfig() {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/env.php';
    }
    return $config;
}
