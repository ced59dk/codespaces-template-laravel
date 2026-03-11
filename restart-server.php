<?php
/**
 * Script pour redémarrer le serveur Laravel en tuant les anciens processus
 */

echo "🔄 Tentative de redémarrage du serveur Laravel...\n";

// Tuer les anciens processus artisan sur le port 8000
passthru('pkill -f "artisan serve" || true');
sleep(2);

// Désactiver Xdebug pour ce script
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

echo "✓ Anciens processus terminés\n";

// Lancer le nouveau serveur
echo "▶️  Lancement du serveur sur 0.0.0.0:8000...\n";
echo "⏱️  Le serveur devrait être accessible dans quelques secondes\n\n";

// Set environment to disable Xdebug
putenv('XDEBUG_MODE=off');
putenv('XDEBUG_CONFIG=idekey=off');

passthru('php artisan serve --host=0.0.0.0 --port=8000');
?>
