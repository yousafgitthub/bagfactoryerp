<?php

namespace App\Policies;

use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function createMaterial(User $user): bool
    {
        return $this->manage($user);
    }

    public function updateMaterial(User $user, Material $material): bool
    {
        return $this->manage($user);
    }

    public function createTransaction(User $user): bool
    {
        return $this->manage($user);
    }

    public function postTransaction(User $user, InventoryTransaction $transaction): bool
    {
        return $this->manage($user);
    }
}