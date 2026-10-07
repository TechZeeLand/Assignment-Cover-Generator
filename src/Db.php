<?php

declare(strict_types=1);

namespace App;

/**
 * One small SQLite file (storage/data/app.sqlite) holds everything the app
 * persists besides fonts. There is no user/password system: "users" only
 * exist after a Google sign-in and contain just a name and an email.
 */
final class Db
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $pdo = new \PDO('sqlite:' . Config::dataPath() . '/app.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        self::migrate($pdo);
        self::$pdo = $pdo;
        return $pdo;
    }

    private static function migrate(\PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT NOT NULL UNIQUE,
            name          TEXT NOT NULL,
            created_at    INTEGER NOT NULL,
            last_login_at INTEGER NOT NULL,
            login_count   INTEGER NOT NULL DEFAULT 1
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS profiles (
            user_id    INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
            data       TEXT NOT NULL,
            updated_at INTEGER NOT NULL
        )');
        // Anonymous daily counters only (e.g. "pdf", "pdf:modern", "signin"):
        // no IP addresses, no user identifiers.
        $pdo->exec('CREATE TABLE IF NOT EXISTS events (
            day   TEXT NOT NULL,
            name  TEXT NOT NULL,
            count INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (day, name)
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
            ip TEXT NOT NULL,
            ts INTEGER NOT NULL
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts ON login_attempts (ip, ts)');
    }
}
