<?php
// =========================
// Sécurité minimale
// =========================
if (!isset($db)) {
    die('Connexion DB non disponible');
}

$notifications = [];

// =========================
// 📕 Livres indisponibles
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM livres 
    WHERE quantite_disponible = 0 AND statut = 'actif'
");
$nbLivresRupture = (int) $stmt->fetchColumn();

if ($nbLivresRupture > 0) {
    $notifications[] = [
        'type' => 'danger',
        'icon' => 'fas fa-book-dead',
        'title' => 'Livres indisponibles',
        'message' => "$nbLivresRupture livre(s) non disponible(s)",
        'link' => 'modules/livres/views/index?disponible=0'
    ];
}

// =========================
// ⚠️ Livres presque épuisés
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM livres 
    WHERE quantite_disponible <= 1 AND quantite_disponible > 0
");
$nbLivresCritiques = (int) $stmt->fetchColumn();

if ($nbLivresCritiques > 0) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Stock critique',
        'message' => "$nbLivresCritiques livre(s) presque épuisé(s)",
        'link' => 'modules/livres/views/index?critique=1'
    ];
}

// =========================
// ⏰ Emprunts en retard
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM emprunts 
    WHERE statut = 'en_cours' 
      AND date_retour_prevue < CURDATE()
");
$nbEmpruntsRetard = (int) $stmt->fetchColumn();

if ($nbEmpruntsRetard > 0) {
    $notifications[] = [
        'type' => 'danger',
        'icon' => 'fas fa-clock',
        'title' => 'Emprunts en retard',
        'message' => "$nbEmpruntsRetard emprunt(s) en retard",
        'link' => 'modules/emprunts/views/index?statut=en_retard'
    ];
}

// =========================
// 💸 Amendes impayées
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM amendes_pertes 
    WHERE statut = 'impayee'
");
$nbAmendesImpayees = (int) $stmt->fetchColumn();

if ($nbAmendesImpayees > 0) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-money-bill-wave',
        'title' => 'Amendes impayées',
        'message' => "$nbAmendesImpayees amende(s) non réglée(s)",
        'link' => 'modules/amendes/views/index?statut=impayee'
    ];
}

// =========================
// 🚫 Abonnés suspendus / expirés
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM abonnes 
    WHERE statut IN ('suspendu','expire')
");
$nbAbonnesBloques = (int) $stmt->fetchColumn();

if ($nbAbonnesBloques > 0) {
    $notifications[] = [
        'type' => 'danger',
        'icon' => 'fas fa-user-lock',
        'title' => 'Abonnés bloqués',
        'message' => "$nbAbonnesBloques abonné(s) suspendu(s) ou expiré(s)",
        'link' => 'modules/abonnes/views/index?statut=probleme'
    ];
}

// =========================
// 📅 Abonnements expirant bientôt
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM abonnes 
    WHERE statut = 'actif'
      AND date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$nbExpirationsProches = (int) $stmt->fetchColumn();

if ($nbExpirationsProches > 0) {
    $notifications[] = [
        'type' => 'info',
        'icon' => 'fas fa-calendar-alt',
        'title' => 'Abonnements bientôt expirés',
        'message' => "$nbExpirationsProches abonnement(s) arrivent à expiration",
        'link' => 'modules/abonnes/views/index?expiration=proche'
    ];
}

// =========================
// 📚 Réservations actives
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM reservations 
    WHERE statut = 'active'
");
$nbReservationsActives = (int) $stmt->fetchColumn();

if ($nbReservationsActives > 0) {
    $notifications[] = [
        'type' => 'info',
        'icon' => 'fas fa-bookmark',
        'title' => 'Réservations en attente',
        'message' => "$nbReservationsActives réservation(s) active(s)",
        'link' => 'modules/reservations/views/index'
    ];
}

// =========================
// 👤 Utilisateurs inactifs
// =========================
$stmt = $db->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE status = 'inactive'
");
$nbUsersInactifs = (int) $stmt->fetchColumn();

if ($nbUsersInactifs > 0) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-user-slash',
        'title' => 'Utilisateurs inactifs',
        'message' => "$nbUsersInactifs utilisateur(s) inactif(s)",
        'link' => 'modules/users/views/index?status=inactive'
    ];
}

// =========================
// 🎨 Styles par type
// =========================
$notifStyles = [
    'danger' => [
        'bg' => 'bg-red-50 dark:bg-red-900/30',
        'border' => 'border-red-500',
        'text' => 'text-red-700 dark:text-red-300'
    ],
    'warning' => [
        'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
        'border' => 'border-yellow-500',
        'text' => 'text-yellow-700 dark:text-yellow-300'
    ],
    'info' => [
        'bg' => 'bg-blue-50 dark:bg-blue-900/30',
        'border' => 'border-blue-500',
        'text' => 'text-blue-700 dark:text-blue-300'
    ],
    'success' => [
        'bg' => 'bg-green-50 dark:bg-green-900/30',
        'border' => 'border-green-500',
        'text' => 'text-green-700 dark:text-green-300'
    ],
];
?>

<!-- ========================= -->
<!-- 🔔 Bouton Notifications -->
<!-- ========================= -->
<div class="relative">
    <button id="notifDropdownBtn"
        class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        <i class="fas fa-bell text-lg"></i>

        <?php if (!empty($notifications)): ?>
        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-semibold rounded-full flex items-center justify-center">
            <?= count($notifications); ?>
        </span>
        <?php endif; ?>
    </button>

    <!-- ========================= -->
    <!-- 📌 Menu déroulant -->
    <!-- ========================= -->
    <div id="notifDropdownMenu"
        class="hidden absolute right-0 mt-2 w-96 max-h-96 overflow-y-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg border z-50">
        
        <div class="p-4 border-b">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notifications importantes</h3>
        </div>

        <?php if (!empty($notifications)): ?>
        <div class="p-4 space-y-4">
            <?php foreach ($notifications as $notif): 
                $style = $notifStyles[$notif['type']] ?? $notifStyles['info'];
            ?>
            <div class="p-3 rounded-lg border-l-4 
                <?= $style['bg'] ?> 
                <?= $style['border'] ?> 
                <?= $style['text'] ?>">
                
                <div class="flex items-center mb-1">
                    <i class="<?= htmlspecialchars($notif['icon']) ?> mr-2 <?= $style['text'] ?>"></i>
                    <h4 class="font-medium">
                        <?= htmlspecialchars($notif['title']) ?>
                    </h4>
                </div>

                <p class="text-sm mb-1">
                    <?= htmlspecialchars($notif['message']) ?>
                </p>

                <a href="<?= htmlspecialchars($notif['link']) ?>" 
                   class="text-sm font-semibold hover:underline">
                    Voir les détails →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="p-4 text-sm text-gray-500">
            Aucune notification pour le moment.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================= -->
<!-- 🧠 JS Dropdown -->
<!-- ========================= -->
<script>
const notifBtn = document.getElementById('notifDropdownBtn');
const notifMenu = document.getElementById('notifDropdownMenu');

notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifMenu.classList.toggle('hidden');
});

document.addEventListener('click', () => {
    notifMenu.classList.add('hidden');
});
</script>
