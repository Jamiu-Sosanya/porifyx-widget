<?php

namespace App\Http\Middleware;

use App\Models\TeamMember;
use App\Models\User;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * While a person works in another workspace, the request runs as that workspace's account
 * (so every page and plan gate scopes to it) and their role is enforced per route.
 */
class WorkspaceContext
{
    public function handle(Request $request, Closure $next)
    {
        $real = $request->user();
        $wsId = (int) session('workspace_id');
        if (! $real || ! $wsId || $wsId === $real->id || $real->isAdmin()) return $next($request);

        $member = TeamMember::where('owner_id', $wsId)->where('user_id', $real->id)->where('status', 'active')->first();
        $owner = $member ? User::find($wsId) : null;
        if (! $owner) {
            session()->forget('workspace_id'); // membership ended
            return $next($request);
        }

        $perms = Workspace::permissions($member->role);
        $route = $request->route()?->getName();
        if (! Workspace::allows($perms, $route, $request->method()) && ! in_array($route, ['workspaces.switch', 'workspaces.store', 'logout', 'changelog.seen'], true)) {
            if ($request->expectsJson()) return response()->json(['message' => 'Your role in this workspace does not allow this.'], 403);
            abort(403, in_array(true, array_map(fn ($p) => str_starts_with((string) $route, $p), Workspace::PERSONAL), true)
                ? 'Switch to your own account to change your personal settings.'
                : 'Your role (' . Workspace::roleLabel($member->role) . ') does not allow this. Ask the workspace owner.');
        }

        app()->instance('pfx.real_user', $real);
        app()->instance('pfx.workspace_role', $member->role);
        Auth::setUser($owner);
        $request->setUserResolver(fn () => $owner);

        return $next($request);
    }
}
