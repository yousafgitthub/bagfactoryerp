<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class ErpNavigation
{
    /**
     * @return list<array{key: string, label: string, route: string, href: string|null}>
     */
    public static function forUser(?\App\Models\User $user = null): array
    {
        $user ??= Auth::user();
        $modules = config('erp.modules', []);
        $items = [];

        foreach ($modules as $key => $module) {
            $permission = "{$key}.view";

            if ($user !== null && ! $user->can($permission)) {
                continue;
            }

            $routeName = $module['route'];
            $href = null;

            try {
                $href = route($routeName);
            } catch (\Throwable) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => $module['label'],
                'route' => $routeName,
                'href' => $href,
            ];
        }

        return $items;
    }
}