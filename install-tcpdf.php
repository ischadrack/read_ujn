<?php
/**
 * Script d'installation de TCPDF
 * À exécuter une seule fois pour installer la librairie TCPDF
 */

echo "<h2>Installation de TCPDF</h2>\n";

// Créer le dossier vendor si il n'existe pas
$vendor_dir = 'assets/vendor';
if (!is_dir($vendor_dir)) {
    mkdir($vendor_dir, 0755, true);
    echo "<p>✓ Dossier vendor créé</p>\n";
}

$tcpdf_dir = $vendor_dir . '/tcpdf';

// Télécharger TCPDF depuis GitHub
echo "<p>Téléchargement de TCPDF...</p>\n";
flush();

$tcpdf_zip_url = 'https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip';
$zip_file = $vendor_dir . '/tcpdf.zip';

// Utiliser cURL ou file_get_contents selon ce qui est disponible
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tcpdf_zip_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $zip_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $zip_content !== false) {
        file_put_contents($zip_file, $zip_content);
        echo "<p>✓ TCPDF téléchargé avec cURL</p>\n";
    } else {
        echo "<p>❌ Erreur lors du téléchargement avec cURL (Code: $http_code)</p>\n";
        exit;
    }
} else {
    // Fallback avec file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 120,
            'user_agent' => 'Mozilla/5.0 (compatible; TCPDF Installer)'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $zip_content = file_get_contents($tcpdf_zip_url, false, $context);
    if ($zip_content !== false) {
        file_put_contents($zip_file, $zip_content);
        echo "<p>✓ TCPDF téléchargé avec file_get_contents</p>\n";
    } else {
        echo "<p>❌ Erreur lors du téléchargement</p>\n";
        exit;
    }
}

// Extraire le ZIP
echo "<p>Extraction en cours...</p>\n";
flush();

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        $zip->extractTo($vendor_dir);
        $zip->close();
        
        // Renommer le dossier extrait
        $extracted_dir = $vendor_dir . '/TCPDF-main';
        if (is_dir($extracted_dir)) {
            // Supprimer l'ancien dossier tcpdf s'il existe
            if (is_dir($tcpdf_dir)) {
                removeDirectory($tcpdf_dir);
            }
            rename($extracted_dir, $tcpdf_dir);
            echo "<p>✓ TCPDF extrait et installé</p>\n";
        } else {
            echo "<p>❌ Dossier extrait non trouvé</p>\n";
            exit;
        }
    } else {
        echo "<p>❌ Impossible d'ouvrir le fichier ZIP</p>\n";
        exit;
    }
} else {
    echo "<p>❌ Extension ZIP non disponible sur ce serveur</p>\n";
    echo "<p>Veuillez extraire manuellement tcpdf.zip dans assets/vendor/</p>\n";
    exit;
}

// Nettoyer
unlink($zip_file);
echo "<p>✓ Fichier ZIP supprimé</p>\n";

// Créer le fichier de configuration TCPDF
$config_content = '<?php
// Configuration TCPDF pour Bibliothèque Un Jour Nouveau
define("PDF_PAGE_ORIENTATION", "P");
define("PDF_UNIT", "mm");
define("PDF_PAGE_FORMAT", "A4");
define("PDF_MARGIN_LEFT", 15);
define("PDF_MARGIN_TOP", 27);
define("PDF_MARGIN_RIGHT", 15);
define("PDF_MARGIN_BOTTOM", 25);
define("PDF_MARGIN_HEADER", 5);
define("PDF_MARGIN_FOOTER", 10);
define("PDF_IMAGE_SCALE_RATIO", 1.25);
define("HEAD_MAGNIFICATION", 1.1);
define("K_CELL_HEIGHT_RATIO", 1.25);
define("K_TITLE_MAGNIFICATION", 1.3);
define("K_SMALL_RATIO", 2/3);
define("K_THAI_TOPCHARS", true);
define("K_TCPDF_EXTERNAL_CONFIG", false);
?>';

file_put_contents($tcpdf_dir . '/config/tcpdf_config_custom.php', $config_content);
echo "<p>✓ Configuration TCPDF créée</p>\n";

// Vérifier l'installation
if (file_exists($tcpdf_dir . '/tcpdf.php')) {
    echo "<h3 style='color: green;'>✓ Installation réussie !</h3>\n";
    echo "<p>TCPDF est maintenant installé et prêt à être utilisé.</p>\n";
    echo "<p>Vous pouvez maintenant utiliser l'export PDF dans votre application.</p>\n";
    
    // Test rapide
    echo "<p>Test de l'installation...</p>\n";
    try {
        require_once $tcpdf_dir . '/tcpdf.php';
        $test_pdf = new TCPDF();
        echo "<p style='color: green;'>✓ TCPDF peut être instancié correctement</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur lors du test: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Installation échouée - fichier tcpdf.php non trouvé</p>\n";
}

function removeDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

echo "<hr><p><a href='modules/abonnes/views/index'>Retourner à la gestion des abonnés</a></p>\n";
?>