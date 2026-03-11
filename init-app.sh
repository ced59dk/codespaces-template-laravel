#!/bin/bash

# Initialize Laravel Application
# This script sets up a fresh database with migrations and test data

cd /workspaces/codespaces-template-laravel-priv || exit 1

echo "🚀 Initialisation de l'application Laravel..."
echo ""

# 1. Install dependencies
echo "📦 Vérification des dépendances..."
composer --quiet --no-ansi update 2>/dev/null || true

# 2. Clear cache
echo "🗑️  Nettoyage du cache..."
php artisan cache:clear > /dev/null 2>&1 || true
php artisan config:clear > /dev/null 2>&1 || true

# 3. Run fresh migrations (recreates tables)
echo "🔨 Exécution des migrations (fresh)..."
php artisan migrate:fresh --force --quiet

# 4. Seed the database with test data
echo "🌱 Ajout des données de test..."
php artisan db:seed --force --quiet

# 5. Clear cache again
echo "🗑️  Nettoyage final du cache..."
php artisan cache:clear > /dev/null 2>&1 || true

echo ""
echo "✅ Initialisation complète!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 IDENTIFIANTS DE CONNEXION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 Admin:"
echo "   Email: admin@example.com"
echo "   Mot de passe: password"
echo ""
echo "👤 User standard:"
echo "   Email: user@example.com"
echo "   Mot de passe: password"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📍 Accédez à: http://localhost:8000/admin"
echo ""
