<?php

namespace App\Http\Middleware;

use App\Services\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * If the application has not been installed yet, redirect every request
     * to the installer — except requests that are already headed there.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install*') || $request->is('offline') || $request->is('up')) {
            return $next($request);
        }

        if (! InstallerService::isInstallationComplete()) {
            return redirect('/install/');
        }

        return $next($request);
    }
}
