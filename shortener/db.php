<?php
/**
 * NS Link - database bootstrap (PDO SQLite).
 * Returns a PDO instance. Creates the data dir and schema on first use.
 */

function nslink_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dbFile = $config['db_file'];

    $dir = dirname($dbFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ]);

    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Create schema if missing (idempotent - CREATE TABLE IF NOT EXISTS).
    $t = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'links'");
    if (!$t->fetch()) {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
    }

    return $pdo;
}

/**
 * Shortcut helpers.
 */
function nslink_settings(string $key, mixed $default = null): mixed
{
    try {
        $stmt = nslink_db()->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row['value'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function nslink_setting_set(string $key, string $value): void
{
    $stmt = nslink_db()->prepare(
        'INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    );
    $stmt->execute([$key, $value]);
}