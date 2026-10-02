<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case AccessAdmin = 'access_admin';
    case ManageCatalog = 'manage_catalog';
    case ManageInventory = 'manage_inventory';
    case ManageOrders = 'manage_orders';
    case ManageCustomers = 'manage_customers';
    case ManageFinderRequests = 'manage_finder_requests';
    case ManageAuctions = 'manage_auctions';
    case ManageContent = 'manage_content';
    case ManageSettings = 'manage_settings';
    case ManageUsers = 'manage_users';
}
