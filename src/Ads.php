<?php

declare(strict_types=1);

namespace App;

/** Google AdSense placement helpers. Nothing renders until the admin enables ads and fills in the IDs. */
final class Ads
{
    public const SLOTS = [
        'top'    => 'ad_slot_top',
        'middle' => 'ad_slot_middle',
        'bottom' => 'ad_slot_bottom',
    ];

    public static function publisherId(): string
    {
        $id = trim(Settings::get('adsense_publisher_id'));
        return preg_match('/^ca-pub-\d{8,20}$/', $id) ? $id : '';
    }

    public static function enabled(): bool
    {
        return Settings::bool('ads_enabled') && self::publisherId() !== '';
    }

    public static function requiresConsent(): bool
    {
        return Settings::bool('ads_require_consent');
    }

    /** Markup for one ad unit, or '' when it isn't configured. Loaded by assets/js/consent.js. */
    public static function slot(string $position): string
    {
        $key = self::SLOTS[$position] ?? null;
        if ($key === null || !self::enabled()) {
            return '';
        }
        $slotId = trim(Settings::get($key));
        if (!preg_match('/^\d{6,20}$/', $slotId)) {
            return '';
        }
        return '<aside class="ad-wrap ad-' . Html::e($position) . '" aria-label="Advertisement" hidden>'
            . '<span class="ad-label">Advertisement</span>'
            . '<ins class="adsbygoogle" style="display:block" data-ad-client="' . Html::e(self::publisherId())
            . '" data-ad-slot="' . Html::e($slotId) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>'
            . '</aside>';
    }

    /** ads.txt body: the admin's text, or the standard AdSense line derived from the publisher ID. */
    public static function adsTxt(): string
    {
        $custom = trim(str_replace("\r\n", "\n", Settings::get('ads_txt')));
        if ($custom !== '') {
            return $custom . "\n";
        }
        $pub = self::publisherId();
        return $pub === '' ? '' : 'google.com, ' . substr($pub, 3) . ", DIRECT, f08c47fec0942fa0\n";
    }
}
