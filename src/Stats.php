<?php

declare(strict_types=1);

namespace App;

/** Anonymous per-day counters for the admin dashboard. Never throws. */
final class Stats
{
    public static function hit(string $name): void
    {
        try {
            $stmt = Db::pdo()->prepare(
                'INSERT INTO events (day, name, count) VALUES (:d, :n, 1)
                 ON CONFLICT(day, name) DO UPDATE SET count = count + 1'
            );
            $stmt->execute([':d' => gmdate('Y-m-d'), ':n' => $name]);
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] stats: ' . $e->getMessage());
        }
    }

    /** @return array<string,int> day => count for the last $days days (oldest first, zero-filled) */
    public static function series(string $name, int $days): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $out[gmdate('Y-m-d', time() - $i * 86400)] = 0;
        }
        $stmt = Db::pdo()->prepare('SELECT day, count FROM events WHERE name = :n AND day >= :d');
        $stmt->execute([':n' => $name, ':d' => array_key_first($out)]);
        foreach ($stmt as $row) {
            if (isset($out[$row['day']])) {
                $out[$row['day']] = (int) $row['count'];
            }
        }
        return $out;
    }

    public static function total(string $name, ?int $lastDays = null): int
    {
        $sql = 'SELECT COALESCE(SUM(count), 0) FROM events WHERE name = :n';
        $args = [':n' => $name];
        if ($lastDays !== null) {
            $sql .= ' AND day >= :d';
            $args[':d'] = gmdate('Y-m-d', time() - ($lastDays - 1) * 86400);
        }
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute($args);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,int> design key => PDFs generated (all time) */
    public static function designUsage(): array
    {
        $out = [];
        $stmt = Db::pdo()->query("SELECT name, SUM(count) AS c FROM events WHERE name LIKE 'pdf:%' GROUP BY name");
        foreach ($stmt as $row) {
            $out[substr((string) $row['name'], 4)] = (int) $row['c'];
        }
        return $out;
    }
}
