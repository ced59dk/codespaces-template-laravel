#!/bin/bash

# Restart Laravel Server - Kill old processes and start fresh
echo "🔄 Redémarrage du serveur Laravel..."
echo ""

# Kill any existing artisan serve processes
echo "🔍 Recherche des anciens processus PHP..."
pkill -f "artisan serve" || echo "  (aucun processus trouvé)"
sleep 2

# Stop any process listening on port 8000
echo "📛 Libération du port 8000..."
sudo lsof -ti :8000 | xargs kill -9 2>/dev/null || echo "  (port libre)"
sleep 1

cd /workspaces/codespaces-template-laravel-priv || exit 1

# Clear Laravel cache to be safe
echo "🗑️  Nettoyage du cache..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

# Start the server
echo ""
echo "▶️   Lancement du serveur sur 0.0.0.0:8000..."
echo "📍 Accédez à: http://localhost:8000/admin"
echo ""

# Set environment variables to disable Xdebug
export XDEBUG_MODE=off
export XDEBUG_CONFIG="idekey=noxdebug"

# Start Laravel
exec php artisan serve --host=0.0.0.0 --port=8000
