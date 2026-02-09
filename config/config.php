<?php
// URL racine du projet
define('BASE_URL', '/read_ujn');
// Configuration générale
define('SITE_NAME', 'Bibliothèque UN JOUR NOUVEAU');
define('DB_HOST', 'localhost');
define('DB_NAME', 'afriory_librairy');
define('DB_USER', 'root');
define('DB_PASS', '');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la base de données
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonctions utilitaires
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
    
function getUserData() {
    global $db;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            // Mettre à jour les données de session si nécessaire
            if ($user) {
                $_SESSION['user_data'] = $user;
                return $user;
            }
        } catch (PDOException $e) {
            error_log("Erreur getUserData: " . $e->getMessage());
        }
    }
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function isAdmin() {
    $user = getUserData();
    return $user && $user['role'] === 'admin';
}

function isBibliothecaire() {
    $user = getUserData();
    return $user && $user['role'] === 'bibliothecaire';
}

function isAssistant() {
    $user = getUserData();
    return $user && $user['role'] === 'assistant';
}

/**
 * Génère un token CSRF pour sécuriser les formulaires
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche les messages flash
 */
function displayFlashMessage() {
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $type) {
        if (isset($_SESSION[$type . '_message'])) {
            $message = $_SESSION[$type . '_message'];
            unset($_SESSION[$type . '_message']);
            
            $color = [
                'success' => 'green',
                'error' => 'red',
                'warning' => 'yellow',
                'info' => 'blue'
            ][$type];
            
            echo "
            <div class='mb-4 p-4 rounded-lg bg-{$color}-50 border border-{$color}-200'>
                <div class='flex'>
                    <div class='flex-shrink-0'>
                        <i class='fas fa-" . ($type === 'success' ? 'check' : ($type === 'error' ? 'times' : ($type === 'warning' ? 'exclamation-triangle' : 'info'))) . " text-{$color}-400'></i>
                    </div>
                    <div class='ml-3'>
                        <p class='text-sm text-{$color}-800'>{$message}</p>
                    </div>
                </div>
            </div>";
        }
    }
}
// 
   

// Initialiser la base de données si nécessaire
require_once __DIR__ . '/../database_bibliotheque.php';
$dbInit = new BibliothequeDatabase();
$dbInit->conn = $db;
$dbInit->createTables();
?>

