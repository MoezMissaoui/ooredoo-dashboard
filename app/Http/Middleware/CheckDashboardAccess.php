<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckDashboardAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $dashboardType
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $dashboardType = 'operators')
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Vérifier l'accès selon le type de dashboard
        switch ($dashboardType) {
            case 'operators':
                if (!$user->canAccessOperatorsDashboard()) {
                    abort(403, 'Accès refusé au Dashboard Opérateurs');
                }
                break;
                
            case 'sub-stores':
                if (!$user->canAccessSubStoresDashboard()) {
                    abort(403, 'Accès refusé au Dashboard Sub-Stores');
                }
                break;
                
            case 'eklektik-config':
                if (!$user->canAccessEklektikConfig()) {
                    abort(403, 'Accès refusé à la Configuration Eklektik');
                }
                break;
        }

        return $next($request);
    }
}
