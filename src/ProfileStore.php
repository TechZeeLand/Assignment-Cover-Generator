<?php

declare(strict_types=1);

namespace App;

use App\Templates\TemplateRegistry;

/**
 * "Save my details" for signed-in users. Every form field is whitelisted
 * and sanitised on the way in, so what is stored (and later pushed back
 * into the form) is always plain, bounded data. Everything the form holds
 * can be saved - including the topic and submission date - plus a few
 * interface preferences: favourite designs, theme, whether the design
 * picker is expanded, and auto-save.
 */
final class ProfileStore
{
    private const MAX_JSON_BYTES = 800000;

    /** field name => type (bool | text:<max> | area:<max> | color | key | num | rich:<max> | logo | design | designs | date | theme) */
    private const FIELDS = [
        'design'                      => 'design',
        'border'                      => 'bool',
        'icons'                       => 'bool',
        'use-title-border-color'      => 'bool',
        'primary-color'               => 'color',
        'secondary-color'             => 'color',
        'title-border-color'          => 'color',
        'versity-name-font'           => 'key',
        'primary-font'                => 'key',
        'secondary-font'              => 'key',
        'header-suffix'               => 'text:10',

        'bismillah'                   => 'bool',
        'show-versity-name'           => 'bool',
        'versity'                     => 'text:120',
        'show-dept-name'              => 'bool',
        'dept-name'                   => 'text:120',
        'show-faculty'                => 'bool',
        'faculty'                     => 'text:120',
        'logo-data'                   => 'logo',
        'logo-size'                   => 'num',

        'show-cover-title'            => 'bool',
        'assignment-type'             => 'text:40',
        'assignment-no'               => 'text:12',

        'show-student-name'           => 'bool',
        'student-name'                => 'text:80',
        'show-student-id'             => 'bool',
        'student-id'                  => 'text:40',
        'show-student-section'        => 'bool',
        'student-section'             => 'text:40',
        'show-student-batch'          => 'bool',
        'student-batch'               => 'text:40',
        'show-student-program'        => 'bool',
        'student-program'             => 'text:60',
        'semester-type'               => 'text:12',
        'show-semester'               => 'bool',
        'semester'                    => 'text:40',
        'show-student-session'        => 'bool',
        'student-session'             => 'text:30',
        'show-student-email'          => 'bool',
        'student-email'               => 'text:80',

        'show-course-code'            => 'bool',
        'course-code'                 => 'text:40',
        'show-course-title'           => 'bool',
        'course-title-html'           => 'rich:300',
        'show-course-teacher-name'    => 'bool',
        'course-teacher-name'         => 'text:80',
        'show-course-teacher-designation' => 'bool',
        'course-teacher-designation'  => 'text:120',

        'show-group'                  => 'bool',
        'group-name'                  => 'text:60',
        'group-members'               => 'area:900',

        'show-topic'                  => 'bool',
        'topic-html'                  => 'rich:400',
        'show-submission-date'        => 'bool',
        'submission-date'             => 'date',

        'bismillah-font-size'         => 'num',
        'versity-font-size'           => 'num',
        'dept-font-size'              => 'num',
        'student-font-size'           => 'num',
        'course-font-size'            => 'num',
        'topic-font-size'             => 'num',
        'submission-font-size'        => 'num',

        // Interface preferences (not part of the cover itself).
        'favorite-designs'            => 'designs',
        'ui-theme'                    => 'theme',
        'ui-design-expanded'          => 'bool',
        'auto-save'                   => 'bool',
    ];

    /**
     * @param array<string,mixed> $input
     * @return array<string,string|bool>
     */
    public static function sanitize(array $input): array
    {
        $out = [];
        foreach (self::FIELDS as $name => $type) {
            if (!array_key_exists($name, $input)) {
                continue;
            }
            $v = $input[$name];
            [$kind, $arg] = array_pad(explode(':', $type, 2), 2, '');

            switch ($kind) {
                case 'bool':
                    $out[$name] = $v === true || $v === '1' || $v === 1 || $v === 'on' || $v === 'true';
                    break;
                case 'text':
                    $out[$name] = self::plain($v, (int) $arg, false);
                    break;
                case 'area':
                    $out[$name] = self::plain($v, (int) $arg, true);
                    break;
                case 'color':
                    $out[$name] = Sanitize::color($v, '#000000');
                    break;
                case 'key':
                    $key = is_string($v) ? strtolower(trim($v)) : '';
                    $out[$name] = preg_match('/^[a-z0-9\-]{1,40}$/', $key) ? $key : '';
                    break;
                case 'design':
                    $out[$name] = TemplateRegistry::resolveKey(is_string($v) ? $v : '');
                    break;
                case 'num':
                    $out[$name] = is_numeric($v) ? (string) max(0, min(600, (float) $v)) : '';
                    break;
                case 'rich':
                    $out[$name] = Sanitize::richText($v, $arg !== '' ? (int) $arg : 300);
                    break;
                case 'date':
                    $out[$name] = self::isoDate($v);
                    break;
                case 'designs':
                    $out[$name] = self::designList($v);
                    break;
                case 'theme':
                    $out[$name] = ($v === 'light' || $v === 'dark') ? $v : '';
                    break;
                case 'logo':
                    $logo = $v === '' ? null : Logo::fromDataUri($v);
                    $out[$name] = $logo ? $logo['uri'] : '';
                    break;
            }
        }
        return $out;
    }

    /** @return array<string,string|bool>|null */
    public static function load(int $userId): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT data FROM profiles WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        $json = $stmt->fetchColumn();
        $data = is_string($json) ? json_decode($json, true) : null;
        return is_array($data) ? self::sanitize($data) : null;
    }

    /** @param array<string,mixed> $input */
    public static function save(int $userId, array $input): void
    {
        $clean = self::sanitize($input);
        if (!array_key_exists('favorite-designs', $input)) {
            // An older page that doesn't know about favourites must not wipe them.
            $existing = self::load($userId);
            if ($existing !== null && isset($existing['favorite-designs'])) {
                $clean['favorite-designs'] = $existing['favorite-designs'];
            }
        }
        $json = (string) json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > self::MAX_JSON_BYTES) {
            throw new \RuntimeException('Your saved details are too large.');
        }
        $stmt = Db::pdo()->prepare(
            'INSERT INTO profiles (user_id, data, updated_at) VALUES (:u, :d, :t)
             ON CONFLICT(user_id) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at'
        );
        $stmt->execute([':u' => $userId, ':d' => $json, ':t' => time()]);
    }

    /** Updates only the favourite designs (a comma-separated key list), keeping everything else saved. */
    public static function saveFavorites(int $userId, mixed $designs): void
    {
        $profile = self::load($userId) ?? [];
        $profile['favorite-designs'] = self::designList($designs);
        self::save($userId, $profile);
    }

    public static function delete(int $userId): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM profiles WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
    }

    /** YYYY-MM-DD for a real calendar date, otherwise ''. */
    private static function isoDate(mixed $v): string
    {
        if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($v), $m)) {
            return '';
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? trim($v) : '';
    }

    /**
     * A list of design keys (array or comma-separated string) reduced to known
     * designs, de-duplicated, order kept, returned as a comma-separated string.
     */
    private static function designList(mixed $v): string
    {
        if (is_string($v)) {
            $v = explode(',', $v);
        }
        if (!is_array($v)) {
            return '';
        }
        $known = TemplateRegistry::all();
        $out = [];
        foreach ($v as $key) {
            $key = is_string($key) ? strtolower(trim($key)) : '';
            if ($key !== '' && isset($known[$key]) && !in_array($key, $out, true)) {
                $out[] = $key;
            }
        }
        return implode(',', $out);
    }

    private static function plain(mixed $v, int $max, bool $multiline): string
    {
        $s = is_string($v) ? $v : '';
        $s = str_replace("\0", '', $s);
        $s = $multiline ? str_replace("\r\n", "\n", $s) : preg_replace('/[\r\n\t]+/', ' ', $s);
        $s = trim((string) $s);
        return mb_substr($s, 0, $max);
    }
}
