#!/bin/bash

echo "🔄 Réinitialisation de la base de données..."
echo ""

cd /workspaces/codespaces-template-laravel-priv || exit 1

# Clear cache
echo "🗑️  Nettoyage du cache..."
php artisan cache:clear > /dev/null 2>&1 || true
php artisan config:clear > /dev/null 2>&1 || true

# Drop and recreate migrations (SQLite)
echo "🔨 Réinitialisation des migrations..."
php artisan migrate:fresh --force 2>&1 | grep -E "Migrating|Migrated"

# Run seeders to create test data
echo ""
echo "🌱 Ajout des données de test..."
php artisan db:seed --force 2>&1 | tail -10

echo ""
echo "✅ Base de données réinitialisée avec succès!"
echo ""
echo "📋 Identifiants de test:"
echo "   Email: admin@example.com"
echo "   Mot de passe: password"
echo ""
