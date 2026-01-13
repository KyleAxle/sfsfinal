<?php
/**
 * Force load PostgreSQL extensions and show detailed error information
 * Run: php force_load_extensions.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "Force Load PostgreSQL Extensions\n";
echo "========================================\n\n";

// Check current status
echo "[1] Current Status:\n";
echo "    pdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo "    pgsql loaded: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "\n";

// Check php.ini location
echo "[2] PHP Configuration:\n";
echo "    php.ini: " . php_ini_loaded_file() . "\n";
echo "    extension_dir: " . ini_get('extension_dir') . "\n";
echo "\n";

// Check if extension files exist
$ext_dir = ini_get('extension_dir');
if (empty($ext_dir) || $ext_dir === './') {
    $ext_dir = dirname(PHP_BINARY) . '\\ext';
}

echo "[3] Extension Files:\n";
$pdo_pgsql_dll = $ext_dir . '\\php_pdo_pgsql.dll';
$pgsql_dll = $ext_dir . '\\php_pgsql.dll';

if (file_exists($pdo_pgsql_dll)) {
    echo "    ✅ php_pdo_pgsql.dll exists: $pdo_pgsql_dll\n";
} else {
    echo "    ❌ php_pdo_pgsql.dll NOT found: $pdo_pgsql_dll\n";
}

if (file_exists($pgsql_dll)) {
    echo "    ✅ php_pgsql.dll exists: $pgsql_dll\n";
} else {
    echo "    ❌ php_pgsql.dll NOT found: $pgsql_dll\n";
}
echo "\n";

// Check for libpq.dll
echo "[4] Dependencies:\n";
$libpq_locations = [
    dirname(PHP_BINARY) . '\\libpq.dll',
    'C:\\xampp\\php\\libpq.dll',
    'C:\\Windows\\System32\\libpq.dll',
    'C:\\Windows\\SysWOW64\\libpq.dll'
];

$libpq_found = false;
foreach ($libpq_locations as $loc) {
    if (file_exists($loc)) {
        echo "    ✅ libpq.dll found: $loc\n";
        $libpq_found = true;
        break;
    }
}

if (!$libpq_found) {
    echo "    ❌ libpq.dll NOT found in common locations\n";
}
echo "\n";

// Try to check php.ini content
echo "[5] php.ini Configuration:\n";
$php_ini = php_ini_loaded_file();
if (file_exists($php_ini)) {
    $content = file_get_contents($php_ini);
    if (preg_match('/^extension=pdo_pgsql/m', $content)) {
        echo "    ✅ extension=pdo_pgsql is enabled (not commented)\n";
    } elseif (preg_match('/^;extension=pdo_pgsql/m', $content)) {
        echo "    ❌ extension=pdo_pgsql is COMMENTED (has semicolon)\n";
    } else {
        echo "    ⚠️  extension=pdo_pgsql not found in php.ini\n";
    }
    
    if (preg_match('/^extension=pgsql/m', $content)) {
        echo "    ✅ extension=pgsql is enabled (not commented)\n";
    } elseif (preg_match('/^;extension=pgsql/m', $content)) {
        echo "    ❌ extension=pgsql is COMMENTED (has semicolon)\n";
    } else {
        echo "    ⚠️  extension=pgsql not found in php.ini\n";
    }
}
echo "\n";

// Try to load manually (if dl() is available)
echo "[6] Attempting Manual Load:\n";
if (function_exists('dl')) {
    echo "    dl() function is available\n";
    if (!extension_loaded('pdo_pgsql')) {
        echo "    Attempting to load php_pdo_pgsql.dll...\n";
        $result = @dl('php_pdo_pgsql.dll');
        if ($result) {
            echo "    ✅ Successfully loaded via dl()\n";
        } else {
            echo "    ❌ Failed to load via dl()\n";
            $error = error_get_last();
            if ($error) {
                echo "    Error: " . $error['message'] . "\n";
            }
        }
    } else {
        echo "    Extension already loaded\n";
    }
} else {
    echo "    dl() function is NOT available (disabled in php.ini)\n";
    echo "    This is normal for security reasons\n";
}
echo "\n";

// Final check
echo "[7] Final Status:\n";
if (extension_loaded('pdo_pgsql')) {
    echo "    ✅ pdo_pgsql is NOW LOADED\n";
    echo "    Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
} else {
    echo "    ❌ pdo_pgsql is STILL NOT LOADED\n";
    echo "\n";
    echo "    Possible causes:\n";
    echo "    1. Extension is commented in php.ini (check line 947)\n";
    echo "    2. Missing libpq.dll or its dependencies\n";
    echo "    3. Version mismatch between PHP and extension DLL\n";
    echo "    4. Missing Visual C++ Redistributables\n";
    echo "    5. Permission issues\n";
}
echo "\n";

echo "========================================\n";

