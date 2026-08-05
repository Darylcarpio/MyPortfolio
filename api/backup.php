<?php
/**
 * SYSTEM BACKUP API - Clean Version
 * Handles full system backup/restore operations
 */

// Aggressive suppression of all warnings/notices before headers
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

// Prevent output
ob_start();

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check authentication
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        ob_end_clean();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    // Clear buffer and set headers
    ob_end_clean();
    ob_start();
    
    header('Content-Type: application/json', true);
    
    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('No action specified');
    }
    
    // Backup directory
    $backupDir = __DIR__ . '/../backups';
    @mkdir($backupDir, 0755, true);
    
    // ─────────────────────────────────────────────
    // FULL BACKUP
    // ─────────────────────────────────────────────
    if ($action === 'full-backup') {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "backup_$timestamp";
        $tempDir = "$backupDir/temp_$timestamp";
        $zipFile = "$backupDir/{$backupName}.zip";
        
        // Create temp directory
        if (!@mkdir($tempDir, 0755, true)) {
            throw new Exception('Cannot create backup directory');
        }
        
        // Backup database
        try {
            $dbFile = "$tempDir/database.sql";
            backupDatabase($dbFile);
        } catch (Exception $e) {
            deleteDir($tempDir);
            throw new Exception('Database backup failed: ' . $e->getMessage());
        }
        
        // Backup files
        $filesToBackup = [
            'config.php',
            'panel.php',
            'dashboard.php',
            'admin_login.php',
            'index.php',
            'logout.php'
        ];
        foreach ($filesToBackup as $file) {
            $src = __DIR__ . "/../$file";
            if (file_exists($src) && is_file($src)) {
                @copy($src, "$tempDir/$file");
            }
        }
        
        // Backup API files
        $apiDir = __DIR__ . '/../api';
        if (is_dir($apiDir)) {
            @mkdir("$tempDir/api", 0755, true);
            copyDir($apiDir, "$tempDir/api");
        }
        
        // Backup media
        foreach (['images', 'img', 'audio'] as $dir) {
            $src = __DIR__ . "/../$dir";
            if (is_dir($src)) {
                @mkdir("$tempDir/$dir", 0755, true);
                copyDir($src, "$tempDir/$dir");
            }
        }
        
        // Create ZIP
        $zipSize = 0;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                addToZip($zip, $tempDir, basename($tempDir));
                $zip->close();
                $zipSize = @filesize($zipFile);
            }
        }
        
        // Cleanup
        deleteDir($tempDir);
        
        // Return success
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Full system backup created successfully!',
            'backup_name' => $backupName,
            'file_size' => $zipSize ? round($zipSize / 1024 / 1024, 2) . ' MB' : 'unknown'
        ]);
        exit;
    }
    
    // ─────────────────────────────────────────────
    // LIST BACKUPS
    // ─────────────────────────────────────────────
    if ($action === 'list-backups') {
        $backups = [];
        if (is_dir($backupDir)) {
            $files = @glob("$backupDir/backup_*.zip");
            if ($files) {
                rsort($files);
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => round(filesize($file) / 1024, 2) . ' KB',
                        'date' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'backups' => $backups
        ]);
        exit;
    }
    
    // ─────────────────────────────────────────────
    // RESTORE
    // ─────────────────────────────────────────────
    if ($action === 'restore') {
        $backup = $_POST['backup'] ?? null;
        if (!$backup || strpos($backup, '..') !== false) {
            throw new Exception('Invalid backup file');
        }
        
        $zipPath = "$backupDir/$backup";
        if (!file_exists($zipPath)) {
            throw new Exception('Backup file not found');
        }
        
        // Extract backup
        $extractDir = "$backupDir/temp_restore_" . time();
        if (!@mkdir($extractDir, 0755, true)) {
            throw new Exception('Cannot create extraction directory');
        }
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($extractDir);
                $zip->close();
                
                // Get the extracted folder name
                $extracted = @scandir($extractDir);
                $srcDir = $extractDir;
                if ($extracted && count($extracted) > 2) {
                    foreach ($extracted as $item) {
                        if ($item !== '.' && $item !== '..' && is_dir("$extractDir/$item")) {
                            $srcDir = "$extractDir/$item";
                            break;
                        }
                    }
                }
                
                // Restore database
                $dbFile = "$srcDir/database.sql";
                if (file_exists($dbFile)) {
                    restoreDatabase($dbFile);
                }
                
                // Restore files
                foreach (['config.php', 'panel.php', 'dashboard.php', 'admin_login.php', 'index.php', 'logout.php'] as $file) {
                    if (file_exists("$srcDir/$file")) {
                        @copy("$srcDir/$file", __DIR__ . "/../$file");
                    }
                }
                
                // Restore API
                if (is_dir("$srcDir/api")) {
                    @mkdir(__DIR__ . '/../api', 0755, true);
                    copyDir("$srcDir/api", __DIR__ . '/../api');
                }
                
                // Restore media
                foreach (['images', 'img', 'audio'] as $dir) {
                    if (is_dir("$srcDir/$dir")) {
                        @mkdir(__DIR__ . "/../$dir", 0755, true);
                        copyDir("$srcDir/$dir", __DIR__ . "/../$dir");
                    }
                }
            }
        }
        
        deleteDir($extractDir);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'System restored successfully!'
        ]);
        exit;
    }
    
    // ─────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────
    if ($action === 'delete') {
        $backup = $_POST['backup'] ?? null;
        if (!$backup || strpos($backup, '..') !== false) {
            throw new Exception('Invalid backup file');
        }
        
        $zipPath = "$backupDir/$backup";
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Backup deleted'
        ]);
        exit;
    }
    
    throw new Exception('Unknown action: ' . $action);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function backupDatabase($outputFile) {
    try {
        require_once __DIR__ . '/../config.php';
    } catch (Exception $e) {
        throw new Exception('Cannot load config: ' . $e->getMessage());
    }
    
    try {
        $db = Database::getInstance();
        $tables = $db->fetchAll("SHOW TABLES FROM `" . DB_NAME . "`");
        
        if (empty($tables)) {
            throw new Exception('No database tables found');
        }
        
        $sql = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Tables: " . count($tables) . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = reset($table);
            $createTable = $db->fetch("SHOW CREATE TABLE `$tableName`");
            
            if ($createTable) {
                $sql .= "\nDROP TABLE IF EXISTS `$tableName`;\n";
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                $rows = $db->fetchAll("SELECT * FROM `$tableName`");
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $values = array_map(function($v) {
                            if ($v === null) return 'NULL';
                            return "'" . str_replace("'", "''", (string)$v) . "'";
                        }, $row);
                        
                        $cols = implode('`, `', array_keys($row));
                        $vals = implode(', ', $values);
                        $sql .= "INSERT INTO `$tableName` (`$cols`) VALUES ($vals);\n";
                    }
                }
            }
        }
        
        if (!@file_put_contents($outputFile, $sql)) {
            throw new Exception('Cannot write to backup file');
        }
    } catch (Exception $e) {
        throw new Exception('Database backup error: ' . $e->getMessage());
    }
}

function restoreDatabase($sqlFile) {
    try {
        require_once __DIR__ . '/../config.php';
    } catch (Exception $e) {
        throw new Exception('Cannot load config: ' . $e->getMessage());
    }
    
    try {
        $db = Database::getInstance();
        $sql = @file_get_contents($sqlFile);
        
        if (!$sql) {
            throw new Exception('Cannot read backup file');
        }
        
        // Execute SQL - split by semicolon
        $queries = array_filter(array_map('trim', explode(';', $sql)), function($q) {
            return !empty($q) && strpos($q, '--') === false;
        });
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                try {
                    $db->query($query);
                } catch (Exception $e) {
                    // Some queries might fail, that's OK
                }
            }
        }
    } catch (Exception $e) {
        throw new Exception('Database restore error: ' . $e->getMessage());
    }
}

function copyDir($src, $dst) {
    @mkdir($dst);
    $handle = @opendir($src);
    if (!$handle) return;
    
    while (($file = readdir($handle)) !== false) {
        if ($file === '.' || $file === '..') continue;
        
        if (is_dir("$src/$file")) {
            copyDir("$src/$file", "$dst/$file");
        } else {
            @copy("$src/$file", "$dst/$file");
        }
    }
    @closedir($handle);
}

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    
    $files = @scandir($dir);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = "$dir/$file";
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function addToZip(&$zip, $dir, $base) {
    $handle = @opendir($dir);
    if (!$handle) return;
    
    while (($file = readdir($handle)) !== false) {
        if ($file === '.' || $file === '..') continue;
        
        $path = "$dir/$file";
        $zipPath = $base ? "$base/$file" : $file;
        
        if (is_dir($path)) {
            addToZip($zip, $path, $zipPath);
        } else {
            $zip->addFile($path, $zipPath);
        }
    }
    @closedir($handle);
}
?>
