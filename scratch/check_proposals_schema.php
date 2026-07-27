<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE club_proposals");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Current columns in club_proposals:\n";
    print_r($columns);

    $desiredColumns = [
        'is_uit_student'     => "TINYINT(1) DEFAULT 0",
        'student_id_number'  => "VARCHAR(100) NULL",
        'student_id_photo'   => "VARCHAR(255) NULL",
        'department_branch'  => "VARCHAR(150) NULL",
        'academic_year'      => "VARCHAR(50) NULL",
        'current_semester'   => "VARCHAR(50) NULL"
    ];

    foreach ($desiredColumns as $col => $definition) {
        if (!in_array($col, $columns)) {
            echo "Adding column '$col'...\n";
            $db->exec("ALTER TABLE club_proposals ADD COLUMN $col $definition");
            echo "Added '$col'!\n";
        }
    }

    echo "Schema update completed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
