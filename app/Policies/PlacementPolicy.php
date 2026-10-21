<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Placement: internal semua; HR Client kliennya; talent miliknya.
 */
class PlacementPolicy
{
    public function view(User $user, Placement $placement): Response
    {
        $boleh = match (true) {
            $user->isInternal() => true,
            $user->hasRole(Role::HrClient) => $placement->id_klien === $user->idKlien(),
            $user->hasRole(Role::Talent) => $placement->id_talent === $user->talent?->id_talent,
            default => false,
        };

        return $boleh ? Response::allow() : Response::deny('Anda tidak memiliki akses ke placement ini.');
    }
}
