<?php
/**
 * SKYNOXX Tournament Platform - Health Check
 * Use this to verify server configuration and database connectivity
 * DELETE this file after successful deployment for security
 */

// Prevent access in production after testing
$allow_health_check = true; // Set to false after deployment

if (!$allow_health_check) {
    http_response_code(403);
    die('Health check disabled');
}

header('Content-Type: application/json');

$health = [
    'status' => 'checking',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check PHP version
$health['checks']['php_version'] = [
    'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'pass' : 'fail',
    'value' => PHP_VERSION,
    'required' => '7.4.0+'
];

// Check required extensions
$required_extensions = ['mysqli', 'json', 'mbstring', 'openssl', 'curl'];
foreach ($required_extensions as $ext) {
    $health['checks']['ext_' . $ext] = [
        'status' => extension_loaded($ext) ? 'pass' : 'fail',
        'value' => extension_loaded($ext) ? 'loaded' : 'missing'
    ];
}

// Check database connection
try {
    include __DIR__ . '/src/db.php';
    
    if ($conn && !$conn->connect_error) {
        $health['checks']['database'] = [
            'status' => 'pass',
            'message' => 'Connected successfully',
            'database' => $database ?? 'unknown'
        ];
        
        // Check if tables exist
        $tables = ['users', 'tournaments', 'registrations', 'payments'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            $health['checks']['database_tables'] = [
                'status' => 'pass',
                'message' => 'All required tables exist'
            ];
        } else {
            $health['checks']['database_tables'] = [
                'status' => 'fail',
                'message' => 'Missing tables: ' . implode(', ', $missing_tables)
            ];
        }
        
    } else {
        $health['checks']['database'] = [
            'status' => 'fail',
            'message' => $conn->connect_error ?? 'Connection failed'
        ];
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'fail',
        'message' => $e->getMessage()
    ];
}

// Check writable directories
$writable_dirs = ['uploads', 'uploads/tournaments', 'uploads/profiles', 'uploads/qr_codes'];
foreach ($writable_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $health['checks']['writable_' . str_replace('/', '_', $dir)] = [
        'status' => is_writable($path) ? 'pass' : 'fail',
        'value' => is_writable($path) ? 'writable' : 'not writable or missing'
    ];
}

// Check .htaccess
$htaccess_exists = file_exists(__DIR__ . '/.htaccess');
$health['checks']['htaccess'] = [
    'status' => $htaccess_exists ? 'pass' : 'warning',
    'value' => $htaccess_exists ? 'exists' : 'missing'
];

// Check SSL
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$health['checks']['https'] = [
    'status' => $is_https ? 'pass' : 'warning',
    'value' => $is_https ? 'enabled' : 'not enabled'
];

// Overall status
$failed = array_filter($health['checks'], function($check) {
    return isset($check['status']) && $check['status'] === 'fail';
});

$health['status'] = empty($failed) ? 'healthy' : 'unhealthy';
$health['summary'] = [
    'total_checks' => count($health['checks']),
    'passed' => count(array_filter($health['checks'], function($c) { return ($c['status'] ?? '') === 'pass'; })),
    'failed' => count($failed),
    'warnings' => count(array_filter($health['checks'], function($c) { return ($c['status'] ?? '') === 'warning'; }))
];

// Output
echo json_encode($health, JSON_PRETTY_PRINT);

// Reminder to delete this file
if ($health['status'] === 'healthy') {
    echo "\n\n<!-- SUCCESS! Please delete health_check.php for security -->";
}
?>
