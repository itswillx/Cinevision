<?php
// setup_remote_db.php

$config = require __DIR__ . '/config/env.php';

$host = $config['DB_HOST'];
$db   = $config['DB_NAME'];
$user = $config['DB_USER'];
$pass = $config['DB_PASS'];

echo "Attempting to connect to $host...\n";

// Try PDO first
if (extension_loaded('pdo_mysql')) {
    try {
        echo "Using PDO...\n";
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
        
        // Split statements (naive split by ;)
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
        echo "Tables created successfully using PDO.\n";
        exit(0);
    } catch (PDOException $e) {
        echo "PDO Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "PDO MySQL driver not loaded.\n";
}

// Try MySQLi as fallback
if (extension_loaded('mysqli')) {
    echo "Using MySQLi...\n";
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_error) {
        die("MySQLi Connection failed: " . $mysqli->connect_error . "\n");
    }
    
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($mysqli->multi_query($sql)) {
        do {
            // store first result set
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            // print divider
            if ($mysqli->more_results()) {
                // printf("-----------------\n");
            }
        } while ($mysqli->next_result());
        echo "Tables created successfully using MySQLi.\n";
    } else {
        echo "MySQLi Error: " . $mysqli->error . "\n";
    }
    $mysqli->close();
} else {
    echo "MySQLi extension not loaded either. Cannot connect to database.\n";
}
