<?php

namespace App\Enums;

enum CommerceUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Cashier = 'cashier';
    case StockManager = 'stock_manager';
    case Seller = 'seller';

    public function permissions(): array
    {
        return match ($this) {
            self::Owner, self::Admin => [
                'commerce_manage_users',
                'commerce_manage_products',
                'commerce_manage_stocks',
                'commerce_manage_sales',
                'commerce_manage_cash',
                'commerce_view_reports',
                'commerce_manage_settings',
            ],
            self::Cashier => [
                'commerce_manage_sales',
                'commerce_manage_cash',
                'commerce_view_reports',
            ],
            self::StockManager => [
                'commerce_manage_products',
                'commerce_manage_stocks',
                'commerce_view_reports',
            ],
            self::Seller => [
                'commerce_manage_sales',
                'commerce_view_reports',
            ],
        };
    }
}
