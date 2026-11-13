<?php

declare(strict_types=1);

namespace App;

/**
 * Google-signed-in visitors. Only a name and an email are ever stored
 * (plus timestamps/counters for the admin dashboard).
 */
final class Users
{
    public static function upsert(string $email, string $name): int
    {
        $now = time();
        $pdo = Db::pdo();
        // Google proves ownership of the address. A password account that was
        // created for it without that proof (email_verified = 0) loses its
        // password, so whoever signed up first can't keep access to the owner's
        // account. Verified accounts keep any password their owner set.
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, name, created_at, last_login_at, login_count, email_verified)
             VALUES (:e, :n, :t, :t, 1, 1)
             ON CONFLICT(email) DO UPDATE SET
                name = excluded.name,
                last_login_at = excluded.last_login_at,
                login_count = users.login_count + 1,
                password_hash = CASE WHEN users.email_verified = 1 THEN users.password_hash ELSE NULL END,
                email_verified = 1'
        );
        $stmt->execute([':e' => $email, ':n' => $name, ':t' => $now]);
        $sel = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $sel->execute([':e' => $email]);
        return (int) $sel->fetchColumn();
    }

    /** @return array{id:int,name:string,email:string,has_password:bool}|null */
    public static function find(int $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT id, name, email, (password_hash IS NOT NULL) AS hp FROM users WHERE id = :i');
        $stmt->execute([':i' => $id]);
        $row = $stmt->fetch();
        return $row ? [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'has_password' => (bool) $row['hp'],
        ] : null;
    }

    /**
     * Creates an email + password account (unverified: there is no SMTP).
     *
     * @throws \RuntimeException when the address is already registered
     */
    public static function registerWithPassword(string $email, string $name, string $password): int
    {
        $now = time();
        $stmt = Db::pdo()->prepare(
            'INSERT INTO users (email, name, created_at, last_login_at, login_count, password_hash, email_verified)
             VALUES (:e, :n, :t, :t, 1, :h, 0)'
        );
        try {
            $stmt->execute([':e' => $email, ':n' => $name, ':t' => $now, ':h' => self::hash($password)]);
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new \RuntimeException('An account with that email already exists. Try signing in instead.');
            }
            throw $e;
        }
        return (int) Db::pdo()->lastInsertId();
    }

    /** The user id when the email + password match, otherwise null (constant-ish time). */
    public static function verifyPassword(string $email, string $password): ?int
    {
        $stmt = Db::pdo()->prepare('SELECT id, password_hash FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch();
        $hash = is_array($row) && is_string($row['password_hash'] ?? null) ? $row['password_hash'] : null;

        // Always do one hash check so a missing account costs the same as a wrong password.
        static $dummy = null;
        $dummy ??= password_hash('dummy-password-for-timing', PASSWORD_DEFAULT);
        $ok = password_verify(self::prehash($password), $hash ?? $dummy);
        if (!$ok || $hash === null) {
            return null;
        }
        $id = (int) $row['id'];
        Db::pdo()->prepare('UPDATE users SET last_login_at = :t, login_count = login_count + 1 WHERE id = :i')
            ->execute([':t' => time(), ':i' => $id]);
        return $id;
    }

    /** True when the stored password for this user matches (used to confirm a password change). */
    public static function checkPassword(int $id, string $password): bool
    {
        $stmt = Db::pdo()->prepare('SELECT password_hash FROM users WHERE id = :i');
        $stmt->execute([':i' => $id]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) && password_verify(self::prehash($password), $hash);
    }

    public static function setPassword(int $id, string $password): void
    {
        Db::pdo()->prepare('UPDATE users SET password_hash = :h WHERE id = :i')
            ->execute([':h' => self::hash($password), ':i' => $id]);
    }

    /** SHA-256 first, so bcrypt's 72-byte limit and NUL handling never weaken a long password. */
    private static function prehash(string $password): string
    {
        return base64_encode(hash('sha256', $password, true));
    }

    private static function hash(string $password): string
    {
        return password_hash(self::prehash($password), PASSWORD_DEFAULT);
    }

    public static function total(): int
    {
        return (int) Db::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function signInsTotal(): int
    {
        return (int) Db::pdo()->query('SELECT COALESCE(SUM(login_count), 0) FROM users')->fetchColumn();
    }

    public static function createdSince(int $ts): int
    {
        $stmt = Db::pdo()->prepare('SELECT COUNT(*) FROM users WHERE created_at >= :t');
        $stmt->execute([':t' => $ts]);
        return (int) $stmt->fetchColumn();
    }

    public static function activeSince(int $ts): int
    {
        $stmt = Db::pdo()->prepare('SELECT COUNT(*) FROM users WHERE last_login_at >= :t');
        $stmt->execute([':t' => $ts]);
        return (int) $stmt->fetchColumn();
    }

    public static function withProfile(): int
    {
        return (int) Db::pdo()->query('SELECT COUNT(*) FROM profiles')->fetchColumn();
    }

    /** @return array{rows: list<array<string,mixed>>, total: int} */
    public static function search(string $q, int $limit, int $offset): array
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $pdo = Db::pdo();
        $count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email LIKE :q ESCAPE '\\' OR name LIKE :q ESCAPE '\\'");
        $count->execute([':q' => $like]);
        $total = (int) $count->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT u.id, u.name, u.email, u.created_at, u.last_login_at, u.login_count,
                    (SELECT COUNT(*) FROM profiles p WHERE p.user_id = u.id) AS has_profile
             FROM users u
             WHERE u.email LIKE :q ESCAPE '\\' OR u.name LIKE :q ESCAPE '\\'
             ORDER BY u.last_login_at DESC
             LIMIT :l OFFSET :o"
        );
        $stmt->bindValue(':q', $like);
        $stmt->bindValue(':l', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':o', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /** Deletes the user and (via ON DELETE CASCADE) their saved details. */
    public static function delete(int $id): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM users WHERE id = :i');
        $stmt->execute([':i' => $id]);
    }
}
