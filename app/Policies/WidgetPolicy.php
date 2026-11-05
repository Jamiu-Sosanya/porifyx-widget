<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Widget;

class WidgetPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    public function view(User $user, Widget $widget): bool
    {
        return (int) $user->id === (int) $widget->user_id;
    }

    public function update(User $user, Widget $widget): bool
    {
        return (int) $user->id === (int) $widget->user_id;
    }

    public function delete(User $user, Widget $widget): bool
    {
        return (int) $user->id === (int) $widget->user_id;
    }
}
