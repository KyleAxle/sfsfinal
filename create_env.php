<?php
/**
 * Setup script to create .env file with your Supabase credentials
 * Run this once: php create_env.php
 */

$envContent = <<<'ENV'
SUPABASE_DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.ndnoevxzgczvyghaktxn
SUPABASE_DB_PASSWORD=YOUR_SUPABASE_PASSWORD
SUPABASE_DB_SSLMODE=require
ENV;

$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    echo "⚠️  .env file already exists!\n";
    echo "If you want to recreate it, delete it first and run this script again.\n";
    exit(1);
}

file_put_contents($envFile, $envContent);
echo "✅ Created .env file!\n\n";
echo "⚠️  IMPORTANT: Edit .env and replace YOUR_SUPABASE_PASSWORD with your actual password!\n";
echo "   Open .env in a text editor and update the password.\n\n";
echo "Your Supabase connection details:\n";
echo "  Host: aws-1-ap-southeast-1.pooler.supabase.com\n";
echo "  Port: 5432\n";
echo "  Database: postgres\n";
echo "  User: postgres.ndnoevxzgczvyghaktxn\n";
echo "  Password: [You need to set this]\n\n";
echo "Next steps:\n";
echo "1. Edit .env and add your password\n";
echo "2. Run the schema in Supabase SQL Editor (see SETUP_INSTRUCTIONS.md)\n";
echo "3. Test connection with: php test_connection.php\n";

