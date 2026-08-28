<?php

namespace App\Support;

/**
 * Every part of the admin panel that can be granted separately, and which of
 * the four abilities apply to it. One definition drives the permission seeder,
 * the sidebar, the route guards and the user form, so a new module cannot be
 * added to one and forgotten in the others.
 */
class AdminModules
{
    public const VIEW = 'view';

    public const CREATE = 'create';

    public const EDIT = 'edit';

    public const DELETE = 'delete';

    /** The role that bypasses every check. */
    public const SUPER_ADMIN = 'Super Admin';

    /**
     * key => [label, abilities]
     *
     * Abilities are deliberately not uniform: you cannot "delete" the POS
     * screen, and a settings page has nothing to create.
     */
    public const MODULES = [
        'dashboard' => ['Dashboard', [self::VIEW]],
        'pos' => ['POS System', [self::VIEW, self::CREATE]],
        'orders' => ['Orders', [self::VIEW, self::EDIT]],
        'customers' => ['Customers', [self::VIEW]],
        'products' => ['Products', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'categories' => ['Categories', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'combos' => ['Combos', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'units' => ['Units', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'inventory' => ['Inventory', [self::VIEW, self::EDIT]],
        'purchases' => ['Purchases', [self::VIEW, self::CREATE, self::DELETE]],
        'suppliers' => ['Suppliers', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'adjustments' => ['Adjustments', [self::VIEW, self::CREATE, self::DELETE]],
        'expenses' => ['Expenses', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'reports' => ['Reports', [self::VIEW]],
        'pages' => ['Static Pages', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'settings' => ['Settings', [self::VIEW, self::EDIT]],
        'users' => ['Users', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
        'roles' => ['Roles', [self::VIEW, self::CREATE, self::EDIT, self::DELETE]],
    ];

    /** Every permission name, e.g. products.create. */
    public static function permissions(): array
    {
        $names = [];

        foreach (self::MODULES as $module => [$label, $abilities]) {
            foreach ($abilities as $ability) {
                $names[] = "{$module}.{$ability}";
            }
        }

        return $names;
    }

    public static function label(string $module): string
    {
        return self::MODULES[$module][0] ?? ucfirst($module);
    }

    public static function abilities(string $module): array
    {
        return self::MODULES[$module][1] ?? [];
    }

    /** Shape the user form and the role editor render from. */
    public static function grid(): array
    {
        $grid = [];

        foreach (self::MODULES as $module => [$label, $abilities]) {
            $grid[] = [
                'key' => $module,
                'label' => $label,
                'abilities' => $abilities,
                'permissions' => array_map(fn ($a) => "{$module}.{$a}", $abilities),
            ];
        }

        return $grid;
    }
}
