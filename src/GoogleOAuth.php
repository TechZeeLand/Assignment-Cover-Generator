<?php

declare(strict_types=1);

namespace App;

/**
 * "Sign in with Google" using the OAuth 2.0 authorization-code flow with
 * PKCE, `state` and `nonce` - no third-party library, no passwords, and
 * nothing is requested beyond the OpenID scopes needed for name + email.
 */
final class GoogleOAuth
{
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public static function redirectUri(): string
    {
        return Http::baseUrl() . '/auth/google-callback.php';
    }

    public static function startUrl(string $return): string
    {
        Session::start();
        $state    = bin2hex(random_bytes(16));
        $nonce    = bin2hex(random_bytes(16));
        $verifier = self::b64url(random_bytes(32));

        $_SESSION['oauth'] = [
            'state'    => $state,
            'nonce'    => $nonce,
            'verifier' => $verifier,
            'return'   => Http::safeReturn($return),
            'time'     => time(),
        ];

        return self::AUTH_URL . '?' . http_build_query([
            'client_id'             => Settings::get('google_client_id'),
            'redirect_uri'          => self::redirectUri(),
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => self::b64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'prompt'                => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array<string,mixed> $query the callback's $_GET
     * @return array{name:string,email:string,return:string}
     * @throws \RuntimeException with a message that is safe to show the visitor
     */
    public static function finish(array $query): array
    {
        Session::start();
        $flow = $_SESSION['oauth'] ?? null;
        unset($_SESSION['oauth']); // single use

        if (isset($query['error'])) {
            throw new \RuntimeException('Sign-in was cancelled.');
        }
        if (!is_array($flow) || (time() - (int) ($flow['time'] ?? 0)) > 900) {
            throw new \RuntimeException('Your sign-in attempt expired. Please try again.');
        }
        $state = (string) ($query['state'] ?? '');
        $code  = (string) ($query['code'] ?? '');
        if ($code === '' || !hash_equals((string) $flow['state'], $state)) {
            throw new \RuntimeException('Sign-in could not be verified. Please try again.');
        }

        $response = self::postForm(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => Settings::get('google_client_id'),
            'client_secret' => Settings::get('google_client_secret'),
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
            'code_verifier' => (string) $flow['verifier'],
        ]);

        $idToken = (string) ($response['id_token'] ?? '');
        $claims  = self::decodeClaims($idToken);

        // The token came straight from Google over TLS, so the signature
        // needn't be re-verified, but its claims must still match this request.
        $issuerOk = in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
        $aud      = $claims['aud'] ?? '';
        $audOk    = is_array($aud)
            ? in_array(Settings::get('google_client_id'), $aud, true)
            : $aud === Settings::get('google_client_id');
        if (!$issuerOk || !$audOk || (int) ($claims['exp'] ?? 0) < time() - 60
            || !hash_equals((string) $flow['nonce'], (string) ($claims['nonce'] ?? ''))) {
            throw new \RuntimeException('Google sign-in could not be verified.');
        }

        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        $verified = $claims['email_verified'] ?? false;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !($verified === true || $verified === 'true')) {
            throw new \RuntimeException('Your Google account has no verified email address.');
        }

        $name = trim(strip_tags((string) ($claims['name'] ?? '')));
        if ($name === '') {
            $name = (string) strstr($email, '@', true);
        }
        $name = mb_substr($name, 0, 80);

        return ['name' => $name, 'email' => $email, 'return' => Http::safeReturn((string) $flow['return'])];
    }

    // ---- internals ---------------------------------------------------

    /** @return array<string,mixed> */
    private static function postForm(string $url, array $fields): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Could not contact Google.');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        $data = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($data) || $status !== 200) {
            error_log('[assignment-cover-generator] Google token exchange failed (' . $status . '): '
                . ($err !== '' ? $err : (is_string($body) ? substr($body, 0, 300) : '')));
            throw new \RuntimeException('Google rejected the sign-in. Please check the OAuth settings in the admin panel and try again.');
        }
        return $data;
    }

    /** @return array<string,mixed> */
    private static function decodeClaims(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Google sign-in returned an unexpected response.');
        }
        $json = base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4), true);
        $claims = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($claims)) {
            throw new \RuntimeException('Google sign-in returned an unexpected response.');
        }
        return $claims;
    }

    private static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
