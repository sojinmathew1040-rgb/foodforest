<?php
// =========================================================================
// Food Forest Sanctuary — Database Backup Generator
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/admin/includes/db.php';

try {
    $pdo = get_db();
    
    // Target directory: "backup" in root folder
    $backupDir = __DIR__ . '/backup';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Query all tables in the database
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $dateSlug = date('Y_m_d_His');
    
    $sqlContent = "-- =========================================================================\n";
    $sqlContent .= "-- Food Forest Sanctuary — Automated Database Backup\n";
    $sqlContent .= "-- Generated at: " . $timestamp . "\n";
    $sqlContent .= "-- Database: " . DB_NAME . "\n";
    $sqlContent .= "-- =========================================================================\n\n";
    $sqlContent .= "SET NAMES utf8mb4;\n";
    $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sqlContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlContent .= "SET time_zone = \"+00:00\";\n\n";
    
    $tableSummaries = [];
    
    foreach ($tables as $table) {
        // Table schema
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $createTableSql = $createStmt[1];
        
        $sqlContent .= "-- -------------------------------------------------------------\n";
        $sqlContent .= "-- Table structure for `{$table}`\n";
        $sqlContent .= "-- -------------------------------------------------------------\n";
        $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sqlContent .= $createTableSql . ";\n\n";
        
        // Table data
        $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($rows);
        $tableSummaries[$table] = $rowCount;
        
        if ($rowCount > 0) {
            $sqlContent .= "-- Dumping data for table `{$table}` (" . $rowCount . " records)\n";
            
            // Collect column names
            $columns = array_keys($rows[0]);
            $quotedColumns = array_map(function($c) {
                return "`" . str_replace("`", "``", $c) . "`";
            }, $columns);
            $columnList = implode(', ', $quotedColumns);
            
            // Batch inserts in chunks of 50
            $chunks = array_chunk($rows, 50);
            foreach ($chunks as $chunk) {
                $valuesList = [];
                foreach ($chunk as $row) {
                    $rowValues = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $rowValues[] = 'NULL';
                        } elseif (is_numeric($val) && !is_string($val)) {
                            $rowValues[] = $val;
                        } else {
                            $rowValues[] = $pdo->quote($val);
                        }
                    }
                    $valuesList[] = "(" . implode(', ', $rowValues) . ")";
                }
                $sqlContent .= "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $valuesList) . ";\n";
            }
            $sqlContent .= "\n";
        }
    }
    
    $sqlContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sqlContent .= "-- =========================================================================\n";
    $sqlContent .= "-- End of Database Backup\n";
    $sqlContent .= "-- =========================================================================\n";
    
    // Save to primary backup file
    $mainBackupFile = $backupDir . '/foodforest_backup.sql';
    file_put_contents($mainBackupFile, $sqlContent);
    
    // Save timestamped version
    $datedBackupFile = $backupDir . '/foodforest_backup_' . $dateSlug . '.sql';
    file_put_contents($datedBackupFile, $sqlContent);
    
    // Also update root foodforest.sql and admin/data/foodforest.sql
    file_put_contents(__DIR__ . '/foodforest.sql', $sqlContent);
    if (is_dir(__DIR__ . '/admin/data')) {
        file_put_contents(__DIR__ . '/admin/data/foodforest.sql', $sqlContent);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database backup successfully created in backup folder',
        'database' => DB_NAME,
        'timestamp' => $timestamp,
        'files' => [
            'backup_folder_main' => $mainBackupFile,
            'backup_folder_dated' => $datedBackupFile,
            'root_file' => __DIR__ . '/foodforest.sql',
            'admin_data_file' => __DIR__ . '/admin/data/foodforest.sql'
        ],
        'file_size_bytes' => filesize($mainBackupFile),
        'tables_count' => count($tables),
        'tables' => $tableSummaries
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
