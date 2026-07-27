<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $clubs = $db->query("SELECT id, name FROM clubs")->fetchAll();
    echo "MySQL Connection SUCCESSFUL!\n";
    echo "Connected to database 'ccms_db'. Total Clubs: " . count($clubs) . "\n";
    foreach ($clubs as $c) {
        echo " - " . $c['name'] . " (ID: " . $c['id'] . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
