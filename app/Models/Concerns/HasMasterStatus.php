<?php

namespace App\Models\Concerns;

trait HasMasterStatus
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}