<?php
// Fix exam_violations table - add AUTO_INCREMENT if missing
require_once 'config/database.php';

echo "<h3>Checking exam_violations table...</h3>";

// Check current structure
$result = $conn->query("SHOW CREATE TABLE exam_violations");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre>" . $row['Create Table'] . "</pre>";
    
    // Check if AUTO_INCREMENT exists
    if (strpos($row['Create Table'], 'AUTO_INCREMENT') === false) {
        echo "<p style='color:red'>AUTO_INCREMENT missing! Fixing...</p>";
        
        // Fix by recreating the table
        $fix_sql = "
        ALTER TABLE exam_violations 
        MODIFY COLUMN `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY;
        ";
        
        if ($conn->query($fix_sql)) {
            echo "<p style='color:green'>Success! AUTO_INCREMENT added.</p>";
        } else {
            echo "<p style='color:red'>Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:green'>AUTO_INCREMENT already exists.</p>";
    }
} else {
    echo "<p style='color:red'>Table not found or error: " . $conn->error . "</p>";
}

$conn->close();
?>
