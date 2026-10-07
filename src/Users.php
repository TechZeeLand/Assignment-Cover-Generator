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
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, name, created_at, last_login_at, login_count)
             VALUES (:e, :n, :t, :t, 1)
             ON CONFLICT(email) DO UPDATE SET
                name = excluded.name,
                last_login_at = excluded.last_login_at,
                login_count = users.login_count + 1'
        );
        $stmt->execute([':e' => $email, ':n' => $name, ':t' => $now]);
        $sel = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $sel->execute([':e' => $email]);
        return (int) $sel->fetchColumn();
    }

    /** @return array{id:int,name:string,email:string}|null */
    public static function find(int $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT id, name, email FROM users WHERE id = :i');
        $stmt->execute([':i' => $id]);
        $row = $stmt->fetch();
        return $row ? ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'email' => (string) $row['email']] : null;
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
