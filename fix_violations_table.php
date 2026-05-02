<?php
// Fix exam_violations table to add AUTO_INCREMENT to id column
require_once 'config/database.php';

echo "Checking exam_violations table structure...\n";

// Check if id column exists and has AUTO_INCREMENT
$result = $conn->query("SHOW COLUMNS FROM exam_violations LIKE 'id'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Current id column: " . print_r($row, true) . "\n";
    
    if (strpos($row['Extra'], 'auto_increment') === false) {
        echo "Adding AUTO_INCREMENT to id column...\n";
        $sql = "ALTER TABLE exam_violations MODIFY COLUMN `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY";
        if ($conn->query($sql)) {
            echo "Success! id column now has AUTO_INCREMENT.\n";
        } else {
            echo "Error: " . $conn->error . "\n";
        }
    } else {
        echo "id column already has AUTO_INCREMENT.\n";
    }
} else {
    echo "id column not found!\n";
}

$conn->close();
?>
