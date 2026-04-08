#!/usr/bin/env php
<?php
/**
 * CLI Database Query Tool for REMS
 * Usage: php db.php "SELECT * FROM users LIMIT 5"
 */

$host = 'localhost';
$port = 3306;
$user = 'a0051406_rems';
$pass = '28biliSEge';
$db   = 'a0051406_rems';

$args = $argv;
array_shift($args); // Remove script name

if (empty($args)) {
    echo "Usage: php db.php \"SQL QUERY\"\n";
    echo "Examples:\n";
    echo "  php db.php \"SHOW TABLES\"\n";
    echo "  php db.php \"SELECT * FROM users LIMIT 5\"\n";
    echo "  php db.php \"DESCRIBE leads\"\n";
    exit(1);
}

$sql = implode(' ', $args);

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    if (empty($results)) {
        echo "No results found or query executed successfully.\n";
    } else {
        // Output as table
        $headers = array_keys($results[0]);
        $colWidths = [];
        
        foreach ($headers as $header) {
            $colWidths[$header] = strlen($header);
        }
        
        foreach ($results as $row) {
            foreach ($row as $key => $value) {
                $len = strlen($value ?? 'NULL');
                if ($len > $colWidths[$key]) {
                    $colWidths[$key] = $len;
                }
            }
        }
        
        // Print header
        foreach ($headers as $header) {
            echo str_pad($header, $colWidths[$header] + 2);
        }
        echo "\n";
        
        // Print separator
        foreach ($colWidths as $width) {
            echo str_repeat('-', $width + 2);
        }
        echo "\n";
        
        // Print rows
        foreach ($results as $row) {
            foreach ($row as $key => $value) {
                echo str_pad($value ?? 'NULL', $colWidths[$key] + 2);
            }
            echo "\n";
        }
        
        echo "\nTotal rows: " . count($results) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}