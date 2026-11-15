<?php

use App\Models\Color;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ExpenseCategory;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\ProductionHall;
use App\Models\UnitOfMeasurement;
use App\Models\Warehouse;

return [
    'resources' => [
        'production-halls' => [
            'label' => 'Production halls',
            'model' => ProductionHall::class,
            'permissions' => ['view' => 'machines-halls.view', 'manage' => 'machines-halls.manage'],
            'hub' => 'machines-halls',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Hall code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'machine-types' => [
            'label' => 'Machine types',
            'model' => MachineType::class,
            'permissions' => ['view' => 'machines-halls.view', 'manage' => 'machines-halls.manage'],
            'hub' => 'machines-halls',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Type code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
            'references' => [
                ['table' => 'machines', 'column' => 'machine_type_id'],
            ],
        ],
        'machines' => [
            'label' => 'Machines',
            'model' => Machine::class,
            'permissions' => ['view' => 'machines-halls.view', 'manage' => 'machines-halls.manage'],
            'hub' => 'machines-halls',
            'with' => ['machineType', 'productionHall', 'capacityUnit'],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'machine_type_label', 'label' => 'Type'],
                ['key' => 'production_hall_label', 'label' => 'Hall'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Machine code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'machine_type_id', 'label' => 'Machine type', 'type' => 'relation', 'relation' => 'machine-types', 'required' => true],
                ['name' => 'production_hall_id', 'label' => 'Production hall', 'type' => 'relation', 'relation' => 'production-halls', 'required' => true],
                ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
                ['name' => 'capacity_unit_id', 'label' => 'Capacity unit', 'type' => 'relation', 'relation' => 'units-of-measurement'],
                [
                    'name' => 'status',
                    'label' => 'Status',
                    'type' => 'status',
                    'options' => ['active', 'inactive', 'under_maintenance'],
                ],
                ['name' => 'installation_date', 'label' => 'Installation date', 'type' => 'date'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'colors' => [
            'label' => 'Colors',
            'model' => Color::class,
            'permissions' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
            'hub' => 'settings',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'hex_color', 'label' => 'Swatch', 'display' => 'swatch'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Color name', 'type' => 'text', 'required' => true],
                ['name' => 'code', 'label' => 'Color code', 'type' => 'text', 'required' => true],
                ['name' => 'hex_color', 'label' => 'Hex color', 'type' => 'color'],
                ['name' => 'reference_color', 'label' => 'Reference / masterbatch ref', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'units-of-measurement' => [
            'label' => 'Units of measurement',
            'model' => UnitOfMeasurement::class,
            'permissions' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
            'hub' => 'settings',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'symbol', 'label' => 'Symbol'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Unit code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'symbol', 'label' => 'Symbol', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name', 'symbol'],
            'unique' => ['code' => 'code'],
            'references' => [
                ['table' => 'machines', 'column' => 'capacity_unit_id'],
            ],
        ],
        'material-categories' => [
            'label' => 'Material categories',
            'model' => MaterialCategory::class,
            'permissions' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
            'hub' => 'settings',
            'with' => ['parent'],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'parent_label', 'label' => 'Parent'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Category code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'parent_id', 'label' => 'Parent category', 'type' => 'relation', 'relation' => 'material-categories'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'product-categories' => [
            'label' => 'Product categories',
            'model' => ProductCategory::class,
            'permissions' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
            'hub' => 'settings',
            'with' => ['parent'],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'parent_label', 'label' => 'Parent'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Category code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'parent_id', 'label' => 'Parent category', 'type' => 'relation', 'relation' => 'product-categories'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'departments' => [
            'label' => 'Departments',
            'model' => Department::class,
            'permissions' => ['view' => 'employees.view', 'manage' => 'employees.manage'],
            'hub' => 'settings',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Department code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
            'references' => [
                ['table' => 'designations', 'column' => 'department_id'],
            ],
        ],
        'designations' => [
            'label' => 'Designations',
            'model' => Designation::class,
            'permissions' => ['view' => 'employees.view', 'manage' => 'employees.manage'],
            'hub' => 'settings',
            'with' => ['department'],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'department_label', 'label' => 'Department'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Designation code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'department_id', 'label' => 'Department', 'type' => 'relation', 'relation' => 'departments'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
        'warehouses' => [
            'label' => 'Warehouses',
            'model' => Warehouse::class,
            'permissions' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
            'hub' => 'settings',
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'address', 'label' => 'Address'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Warehouse code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name', 'address'],
            'unique' => ['code' => 'code'],
        ],
        'expense-categories' => [
            'label' => 'Expense categories',
            'model' => ExpenseCategory::class,
            'permissions' => ['view' => 'expenses.view', 'manage' => 'expenses.manage'],
            'hub' => 'settings',
            'with' => ['parent'],
            'columns' => [
                ['key' => 'code', 'label' => 'Code', 'sortable' => true],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'parent_label', 'label' => 'Parent'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Category code', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'parent_id', 'label' => 'Parent category', 'type' => 'relation', 'relation' => 'expense-categories'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['active', 'inactive']],
            ],
            'search' => ['code', 'name'],
            'unique' => ['code' => 'code'],
        ],
    ],
];