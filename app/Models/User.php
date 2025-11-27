<?php

namespace App\Models;

use App\Services\SupabaseService;

class User {
    private $supabase;

    public function __construct() {
        $this->supabase = new SupabaseService();
    }

    public function findById($id) {
        return $this->supabase->select('profiles', ['id' => $id], ['single' => true]);
    }

    public function setToken($token) {
        $this->supabase->setAccessToken($token);
    }
}
