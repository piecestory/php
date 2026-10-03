<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

/**
 * The site's fixed content pages. Each has a stable route (same name as the key) and is edited from
 * the admin; it appears in menus only once published.
 */
enum PageKey: string
{
    case About = 'about';
    case Services = 'services';
    case ShippingPolicy = 'shipping-policy';
    case ReturnsPolicy = 'returns-policy';
    case Privacy = 'privacy';
    case Terms = 'terms';

    /** URL path, the same in both languages (English adds the /en prefix). */
    public function path(): string
    {
        return match ($this) {
            self::About => '/about',
            self::Services => '/services',
            self::ShippingPolicy => '/policies/shipping',
            self::ReturnsPolicy => '/policies/returns',
            self::Privacy => '/policies/privacy',
            self::Terms => '/policies/terms',
        };
    }
}
