<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case Admin = 'admin';
    case StoreManager = 'store_manager';
    case ContentEditor = 'content_editor';
    case CustomerService = 'customer_service';

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::StoreManager => [
                Permission::AccessAdmin, Permission::ManageCatalog, Permission::ManageInventory,
                Permission::ManageOrders, Permission::ManageCustomers, Permission::ManageFinderRequests,
                Permission::ManageAuctions, Permission::ManageContent,
            ],
            self::ContentEditor => [Permission::AccessAdmin, Permission::ManageCatalog, Permission::ManageContent],
            self::CustomerService => [
                Permission::AccessAdmin, Permission::ManageOrders, Permission::ManageCustomers,
                Permission::ManageFinderRequests,
            ],
        };
    }
}
