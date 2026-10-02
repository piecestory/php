<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ProductSort: string
{
    case Relevance = 'relevance';
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';

    public function label(): string
    {
        return __("catalog.sort.{$this->value}");
    }

    /** @return list<self> */
    public static function options(bool $searching): array
    {
        return $searching ? self::cases() : [self::Newest, self::PriceAsc, self::PriceDesc];
    }
}
