<?php
/**
 * Installation and Setup Script for Library Management System
 * Run this file once to set up all dependencies
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 or higher is required. Current version: ' . PHP_VERSION);
}

// Check if running from command line or web
$isCLI = php_sapi_name() === 'cli';

function output($message, $isCLI) {
    if ($isCLI) {
        echo $message . "\n";
    } else {
        echo "<p>" . htmlspecialchars($message) . "</p>";
    }
}

function success($message, $isCLI) {
    if ($isCLI) {
        echo "✅ " . $message . "\n";
    } else {
        echo "<div style='color: green; padding: 10px; margin: 5px 0; border: 1px solid #4caf50; border-radius: 4px; background: #f1f8e9;'>✅ " . htmlspecialchars($message) . "</div>";
    }
}

function error($message, $isCLI) {
    if ($isCLI) {
        echo "❌ " . $message . "\n";
    } else {
        echo "<div style='color: red; padding: 10px; margin: 5px 0; border: 1px solid #f44336; border-radius: 4px; background: #ffebee;'>❌ " . htmlspecialchars($message) . "</div>";
    }
}

function warning($message, $isCLI) {
    if ($isCLI) {
        echo "⚠️  " . $message . "\n";
    } else {
        echo "<div style='color: orange; padding: 10px; margin: 5px 0; border: 1px solid #ff9800; border-radius: 4px; background: #fff3e0;'>⚠️ " . htmlspecialchars($message) . "</div>";
    }
}

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>Library Management System - Setup</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}</style></head><body>";
    echo "<h1>📚 Library Management System Setup</h1>";
}

output("Starting installation process...", $isCLI);

// Check for Composer
if (!file_exists('composer.json')) {
    error("composer.json not found. Please make sure you're in the correct directory.", $isCLI);
    exit(1);
}

// Check if Composer is installed
$composerExists = false;
$composerCommand = 'composer';

// Try different composer commands
$commands = ['composer', 'php composer.phar', 'composer.phar'];
foreach ($commands as $cmd) {
    $result = shell_exec("$cmd --version 2>&1");
    if ($result && strpos($result, 'Composer') !== false) {
        $composerCommand = $cmd;
        $composerExists = true;
        break;
    }
}

if (!$composerExists) {
    warning("Composer not found. Attempting to download...", $isCLI);
    
    // Download Composer
    $composerInstaller = file_get_contents('https://getcomposer.org/installer');
    if ($composerInstaller) {
        file_put_contents('composer-setup.php', $composerInstaller);
        
        exec('php composer-setup.php', $output_lines, $return_code);
        if ($return_code === 0) {
            success("Composer downloaded successfully!", $isCLI);
            $composerCommand = 'php composer.phar';
            unlink('composer-setup.php');
        } else {
            error("Failed to download Composer. Please install it manually from https://getcomposer.org/", $isCLI);
            exit(1);
        }
    } else {
        error("Cannot download Composer. Please install it manually.", $isCLI);
        exit(1);
    }
}

success("Composer found: $composerCommand", $isCLI);

// Install dependencies
output("Installing dependencies...", $isCLI);
exec("$composerCommand install --no-dev --optimize-autoloader 2>&1", $install_output, $install_return);

if ($install_return === 0) {
    success("Dependencies installed successfully!", $isCLI);
} else {
    error("Failed to install dependencies. Output:", $isCLI);
    foreach ($install_output as $line) {
        output("  " . $line, $isCLI);
    }
    exit(1);
}

// Check installed packages
$vendorDir = __DIR__ . '/vendor';
if (is_dir($vendorDir)) {
    $packages = [
        'tecnickcom/tcpdf' => 'vendor/tecnickcom/tcpdf/tcpdf.php',
        'phpoffice/phpspreadsheet' => 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php'
    ];
    
    foreach ($packages as $package => $checkFile) {
        if (file_exists($checkFile)) {
            success("$package installed successfully", $isCLI);
        } else {
            warning("$package may not be properly installed", $isCLI);
        }
    }
} else {
    error("Vendor directory not created. Installation may have failed.", $isCLI);
    exit(1);
}

// Create necessary directories
$directories = [
    'assets/uploads/abonnes',
    'assets/exports',
    'logs',
    'tmp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            success("Created directory: $dir", $isCLI);
        } else {
            warning("Could not create directory: $dir", $isCLI);
        }
    }
}

// Set permissions
$permissionDirs = ['assets/uploads', 'assets/exports', 'logs', 'tmp'];
foreach ($permissionDirs as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0755);
        success("Set permissions for: $dir", $isCLI);
    }
}

success("Installation completed successfully!", $isCLI);
output("", $isCLI);
output("Next steps:", $isCLI);
output("1. Configure your database connection in config/config.php", $isCLI);
output("2. Run your database migrations", $isCLI);
output("3. Access the application through your web browser", $isCLI);
output("", $isCLI);
output("PDF Export Features:", $isCLI);
output("- Individual subscription cards", $isCLI);
output("- Bulk subscription cards", $isCLI);
output("- Complete subscriber lists", $isCLI);
output("- Excel/CSV exports", $isCLI);

if (!$isCLI) {
    echo "<h2>🎉 Installation Complete!</h2>";
    echo "<p><strong>Your library management system is now ready to use.</strong></p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Generate professional PDF subscription cards</li>";
    echo "<li>Export subscriber data to Excel format</li>";
    echo "<li>Create comprehensive PDF reports</li>";
    echo "<li>Print individual or bulk subscriber cards</li>";
    echo "</ul>";
    echo "<p><a href='modules/abonnes/views/index' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Access Subscriber Management</a></p>";
    echo "</body></html>";
}

?>