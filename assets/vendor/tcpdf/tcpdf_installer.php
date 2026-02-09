<?php
/**
 * Installation automatique de TCPDF
 */

class TCPDFInstaller {
    private $version = '6.6.5';
    private $downloadUrl = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/';
    private $installPath;
    
    public function __construct($installPath = null) {
        $this->installPath = $installPath ?: __DIR__;
    }
    
    public function install() {
        echo "Installation de TCPDF version {$this->version}...\n";
        
        // Créer le dossier de destination
        if (!is_dir($this->installPath)) {
            mkdir($this->installPath, 0755, true);
        }
        
        // Télécharger TCPDF
        $zipFile = $this->downloadTCPDF();
        
        if ($zipFile) {
            // Extraire l'archive
            $this->extractTCPDF($zipFile);
            
            // Nettoyer
            unlink($zipFile);
            
            // Créer le fichier de configuration
            $this->createConfig();
            
            echo "TCPDF installé avec succès!\n";
            return true;
        }
        
        return false;
    }
    
    private function downloadTCPDF() {
        $url = $this->downloadUrl . $this->version . '.zip';
        $zipFile = $this->installPath . '/tcpdf.zip';
        
        echo "Téléchargement depuis: {$url}\n";
        
        // Utiliser cURL si disponible, sinon file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $data = curl_exec($ch);
            
            if (curl_error($ch)) {
                echo "Erreur cURL: " . curl_error($ch) . "\n";
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
        } else {
            $data = file_get_contents($url);
        }
        
        if ($data === false) {
            echo "Erreur lors du téléchargement\n";
            return false;
        }
        
        file_put_contents($zipFile, $data);
        echo "Téléchargement terminé (" . filesize($zipFile) . " bytes)\n";
        
        return $zipFile;
    }
    
    private function extractTCPDF($zipFile) {
        echo "Extraction de l'archive...\n";
        
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            // Extraire dans un dossier temporaire
            $tempDir = $this->installPath . '/temp_tcpdf';
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Déplacer les fichiers vers la destination finale
            $sourceDir = $tempDir . '/TCPDF-' . $this->version;
            $this->moveDirectory($sourceDir, $this->installPath);
            
            // Nettoyer le dossier temporaire
            $this->removeDirectory($tempDir);
            
            echo "Extraction terminée\n";
            return true;
        } else {
            echo "Erreur lors de l'extraction\n";
            return false;
        }
    }
    
    private function moveDirectory($source, $destination) {
        $dir = opendir($source);
        
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                $srcFile = $source . '/' . $file;
                $destFile = $destination . '/' . $file;
                
                if (is_dir($srcFile)) {
                    if (!is_dir($destFile)) {
                        mkdir($destFile, 0755, true);
                    }
                    $this->moveDirectory($srcFile, $destFile);
                } else {
                    copy($srcFile, $destFile);
                }
            }
        }
        
        closedir($dir);
    }
    
    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    private function createConfig() {
        $configFile = $this->installPath . '/config.php';
        
        $config = "<?php
/**
 * Configuration TCPDF pour Bibliothèque UN JOUR NOUVEAU
 */

// Définir les constantes si elles n'existent pas déjà
if (!defined('PDF_PAGE_ORIENTATION')) define('PDF_PAGE_ORIENTATION', 'P');
if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
if (!defined('PDF_MARGIN_LEFT')) define('PDF_MARGIN_LEFT', 15);
if (!defined('PDF_MARGIN_TOP')) define('PDF_MARGIN_TOP', 27);
if (!defined('PDF_MARGIN_RIGHT')) define('PDF_MARGIN_RIGHT', 15);
if (!defined('PDF_MARGIN_HEADER')) define('PDF_MARGIN_HEADER', 5);
if (!defined('PDF_MARGIN_FOOTER')) define('PDF_MARGIN_FOOTER', 10);
if (!defined('PDF_FONT_NAME_MAIN')) define('PDF_FONT_NAME_MAIN', 'helvetica');
if (!defined('PDF_FONT_SIZE_MAIN')) define('PDF_FONT_SIZE_MAIN', 10);
if (!defined('PDF_FONT_NAME_DATA')) define('PDF_FONT_NAME_DATA', 'helvetica');
if (!defined('PDF_FONT_SIZE_DATA')) define('PDF_FONT_SIZE_DATA', 8);
if (!defined('PDF_FONT_MONOSPACED')) define('PDF_FONT_MONOSPACED', 'courier');
if (!defined('PDF_IMAGE_SCALE_RATIO')) define('PDF_IMAGE_SCALE_RATIO', 1.25);
if (!defined('HEAD_MAGNIFICATION')) define('HEAD_MAGNIFICATION', 1.1);
if (!defined('K_CELL_HEIGHT_RATIO')) define('K_CELL_HEIGHT_RATIO', 1.25);
if (!defined('K_TITLE_MAGNIFICATION')) define('K_TITLE_MAGNIFICATION', 1.3);
if (!defined('K_SMALL_RATIO')) define('K_SMALL_RATIO', 2/3);
if (!defined('K_THAI_TOPCHARS')) define('K_THAI_TOPCHARS', true);
if (!defined('K_TCPDF_EXTERNAL_CONFIG')) define('K_TCPDF_EXTERNAL_CONFIG', true);

// Chemin vers les polices TCPDF
if (!defined('K_PATH_FONTS')) define('K_PATH_FONTS', dirname(__FILE__) . '/fonts/');

// URL de base pour les images
if (!defined('K_PATH_URL')) define('K_PATH_URL', 'http://localhost/');

// Informations sur l'organisation
if (!defined('PDF_HEADER_TITLE')) define('PDF_HEADER_TITLE', 'Bibliothèque UN JOUR NOUVEAU');
if (!defined('PDF_HEADER_STRING')) define('PDF_HEADER_STRING', 'Système de Gestion des Abonnés');
if (!defined('PDF_CREATOR')) define('PDF_CREATOR', 'Bibliothèque UN JOUR NOUVEAU');
if (!defined('PDF_AUTHOR')) define('PDF_AUTHOR', 'Système de Gestion');

?>";

        file_put_contents($configFile, $config);
        echo "Fichier de configuration créé\n";
    }
}

// Installation automatique si le script est exécuté directement
if (php_sapi_name() === 'cli' || (isset($_GET['install']) && $_GET['install'] === 'tcpdf')) {
    $installer = new TCPDFInstaller();
    if ($installer->install()) {
        echo "Installation réussie!\n";
        if (!php_sapi_name() === 'cli') {
            echo "<script>alert('TCPDF installé avec succès!');</script>";
        }
    } else {
        echo "Échec de l'installation!\n";
        if (!php_sapi_name() === 'cli') {
            echo "<script>alert('Échec de l\\'installation de TCPDF');</script>";
        }
    }
}

?>