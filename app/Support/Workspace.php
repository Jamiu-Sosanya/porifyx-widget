<?php

namespace App\Support;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Workspaces and team roles.
 * A workspace is a business account (a User that owns widgets). People log in as themselves and can
 * switch into any workspace where they are an active team member; the role decides what they may do.
 */
class Workspace
{
    /** permission => [label, area] */
    public const PERMISSIONS = [
        'widgets.manage'       => ['Create and edit widgets, flows, answers, engagement, AI tools', 'Assistants'],
        'knowledge.manage'     => ['Manage the knowledge base and help centre', 'Assistants'],
        'conversations.reply'  => ['Reply in conversations, tag, take over chats', 'Conversations'],
        'tickets.manage'       => ['Work on support tickets', 'Conversations'],
        'leads.view'           => ['See leads', 'Leads & bookings'],
        'leads.manage'         => ['Edit, move and export leads', 'Leads & bookings'],
        'bookings.manage'      => ['Manage bookings and booking setup', 'Leads & bookings'],
        'analytics.view'       => ['See analytics', 'Reports'],
        'automations.manage'   => ['Manage automations and webhooks', 'Integrations'],
        'privacy.manage'       => ['Export or erase visitor data (GDPR)', 'Privacy'],
        'settings.manage'      => ['Workspace settings, API keys, canned replies', 'Workspace'],
        'team.manage'          => ['Invite and remove people, edit roles', 'Workspace'],
        'billing.manage'       => ['Change plan and payment details', 'Workspace'],
    ];

    public const BUILT_IN = [
        'owner'  => ['Owner', '*'],
        'admin'  => ['Admin', ['widgets.manage', 'knowledge.manage', 'conversations.reply', 'tickets.manage', 'leads.view', 'leads.manage', 'bookings.manage', 'analytics.view', 'automations.manage', 'privacy.manage', 'settings.manage', 'team.manage']],
        'agent'  => ['Agent', ['conversations.reply', 'tickets.manage', 'leads.view', 'bookings.manage']],
        'viewer' => ['Viewer', ['leads.view', 'analytics.view']],
    ];

    /** Route name prefix => permission needed to change something (GET pages are open unless listed in VIEW). */
    private const WRITE = [
        'widgets.knowledge' => 'knowledge.manage', 'widgets.help' => 'knowledge.manage', 'knowledge' => 'knowledge.manage',
        'widgets.booking' => 'bookings.manage', 'appointments' => 'bookings.manage',
        'widgets' => 'widgets.manage', 'conversations' => 'conversations.reply', 'tickets' => 'tickets.manage',
        'leads' => 'leads.manage', 'automations' => 'automations.manage', 'visitors' => 'privacy.manage',
        'settings.team' => 'team.manage', 'settings.billing' => 'billing.manage', 'billing' => 'billing.manage',
        'settings.roles' => 'team.manage', 'settings' => 'settings.manage',
    ];
    private const VIEW = ['leads' => 'leads.view', 'analytics' => 'analytics.view', 'settings.billing' => 'billing.manage', 'billing' => 'billing.manage',
        'settings.team' => 'team.manage', 'settings.apiKeys' => 'settings.manage', 'visitors' => 'privacy.manage', 'widgets.tools' => 'widgets.manage'];
    /** Personal pages: only in your own account (they would change the workspace owner's login). */
    public const PERSONAL = ['settings.profile', 'settings.password', 'settings.twoFactor', 'settings.notifications'];

    public static function roleLabel(string $role, ?int $ownerId = null): string
    {
        if (isset(self::BUILT_IN[$role])) return self::BUILT_IN[$role][0];
        if (str_starts_with($role, 'custom:')) return (string) (DB::table('custom_roles')->where('id', (int) substr($role, 7))->value('name') ?? 'Custom');

        return ucfirst($role);
    }

    public static function permissions(string $role): array
    {
        if (isset(self::BUILT_IN[$role])) {
            $p = self::BUILT_IN[$role][1];

            return $p === '*' ? array_keys(self::PERMISSIONS) : $p;
        }
        if (str_starts_with($role, 'custom:')) {
            return (array) json_decode((string) DB::table('custom_roles')->where('id', (int) substr($role, 7))->value('permissions'), true);
        }

        return [];
    }

    /** Is this request allowed for these permissions? */
    public static function allows(array $perms, ?string $route, string $method): bool
    {
        if (! $route) return true;
        foreach (self::PERSONAL as $p) if (str_starts_with($route, $p)) return false;
        $map = $method === 'GET' ? self::VIEW : self::WRITE;
        foreach ($map as $prefix => $perm) {
            if ($route === $prefix || str_starts_with($route, $prefix . '.')) return in_array($perm, $perms, true);
        }

        return $method === 'GET'; // unknown write routes need an explicit permission
    }

    /** Workspaces a person can work in: their own account plus active memberships. */
    public static function for(User $u): array
    {
        $own = $u->is_workspace ? [] : [['id' => $u->id, 'name' => $u->company_name ?: $u->name, 'role' => 'owner', 'own' => true]];
        $member = TeamMember::where('user_id', $u->id)->where('status', 'active')->with('owner:id,name,company_name')->get()
            ->filter(fn ($m) => $m->owner)->map(fn ($m) => ['id' => $m->owner_id, 'name' => $m->owner->company_name ?: $m->owner->name, 'role' => $m->role, 'own' => false]);

        return array_merge($own, $member->values()->all());
    }

    /** The person actually logged in (differs from auth()->user() while working in another workspace). */
    public static function realUser(): ?User
    {
        return app()->bound('pfx.real_user') ? app('pfx.real_user') : auth()->user();
    }
}
