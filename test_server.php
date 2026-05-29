<?php
// Simple test to check if PHP is working
echo "<h1>✅ PHP is Working!</h1>";
echo "<p>PHP Version: " . phpinfo(INFO_GENERAL) . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Check if key files exist
echo "<h2>File Structure Check:</h2>";
$files_to_check = [
    'src/index.php',
    'src/config.php',
    'src/db.php',
    'admin/admin_dashboard.php',
    'assets/css/style.css'
];

foreach ($files_to_check as $file) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
    if (file_exists($path)) {
        echo "✅ {$file} - EXISTS<br>";
    } else {
        echo "❌ {$file} - MISSING<br>";
    }
}

// Check PHP extensions
echo "<h2>PHP Extensions Check:</h2>";
$required_extensions = ['mysqli', 'json', 'mbstring', 'gd'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} - LOADED<br>";
    } else {
        echo "❌ {$ext} - NOT LOADED<br>";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<p>If you see this page, PHP is working! The 500 error is likely from .htaccess or file paths.</p>";
echo "<p><strong>Delete this file after testing!</strong></p>";
?>
