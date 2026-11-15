<?php

namespace App\Http\Middleware;

use App\Support\ErpNavigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if ($request->routeIs('website.public.*')) {
            return [
                ...parent::share($request),
                'auth' => ['user' => null],
                'navigation' => [],
                'flash' => [
                    'status' => fn () => $request->session()->get('status'),
                ],
                'appName' => config('app.name'),
                'notifications' => [],
                'website' => fn () => app(\App\Services\Website\WebsitePresenter::class)->shared(),
            ];
        }

        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'navigation' => $user ? ErpNavigation::forUser($user) : [],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appName' => config('app.name'),
            'notifications' => [],
        ];
    }
}
