<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    public function delete(User $user, Block $block): bool
    {
        return $block->blocker_id === $user->id;
    }
}
