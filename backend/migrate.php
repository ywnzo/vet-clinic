<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;

// Load configuration
Config::load();

// Get database connection
$db = new Database();
$pdo = $db->getPDO();

echo "🔄 Creating tables...\n";

// Create users table
$createUsersTable = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    surname TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
SQL;

$createRefreshTokensTable = <<<SQL
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($createUsersTable);
    echo "✅ Table 'users' created successfully\n";
} catch (\PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}

// Create index on email for faster lookups
try {
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);");
    echo "✅ Index on 'email' created successfully\n";
} catch (\PDOException $e) {
    echo "⚠️  Index creation warning: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec($createRefreshTokensTable);
    echo "✅ Table 'refresh_tokens' created successfully\n";
} catch (\PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n Migration complete!\n";
