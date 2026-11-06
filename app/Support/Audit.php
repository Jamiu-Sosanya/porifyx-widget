<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/** Privileged actions (payments, plan changes, impersonation, settings, roles), shown in Admin > Activity. */
class Audit
{
    public static function log(string $action, ?Model $subject = null, array $meta = []): void
    {
        try {
            $a = activity('audit')->event($action)->withProperties($meta + ['ip' => request()?->ip()]);
            if ($who = \App\Support\Workspace::realUser()) $a->causedBy($who);
            if ($subject) $a->performedOn($subject);
            $a->log($action);
        } catch (\Throwable $e) {
            report($e); // never block the action itself
        }
    }
}
