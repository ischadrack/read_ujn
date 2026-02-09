<?php
$current_user = getUserData();
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - Bibliothèque</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 dark:bg-red-900/30">
                    <i class="fas fa-ban text-3xl text-red-600 dark:text-red-400"></i>
                </div>
                <h1 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    Accès refusé
                </h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                    Désolé, vous n'avez pas l'autorisation d'accéder à cette ressource.
                </p>
                <?php if ($current_user): ?>
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        Votre rôle actuel: <strong><?php echo ucfirst($current_user['role']); ?></strong>
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                        Contactez votre administrateur si vous pensez que vous devriez avoir accès à cette page.
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="space-y-4">
                <a href="javascript:history.back()" 
                   class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à la page précédente
                </a>
                
                <a href="/index" 
                   class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Aller au tableau de bord
                </a>
            </div>
        </div>
    </div>
</body>
</html>