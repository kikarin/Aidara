<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InjectUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\Http\Response|\Closure)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();

            // Get user permissions
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();

            // Get user roles
            $roles = $user->getRoleNames()->toArray();

            if ($response->headers->get('content-type') && str_contains($response->headers->get('content-type'), 'text/html')) {
                $content = $response->getContent();

                $script = '
                <script>
                    window.userData = {
                        permissions: ' . json_encode($permissions) . ',
                        roles: ' . json_encode($roles) . ',
                        user: {
                            id: ' . $user->id . ',
                            name: ' . json_encode($user->name) . ',
                            email: ' . json_encode($user->email) . '
                        }
                    };
                </script>';

                $content = str_replace('</head>', $script . '</head>', $content);

                $response->setContent($content);
            }
        }

        return $response;
    }
}
