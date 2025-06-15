<?php

namespace App\Broadcasting;

use App\Models\User;

class WarehouseChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Models\User  $user
     * @param  int  $warehouseId
     * @return array|bool
     */
    public function join(User $user, int $warehouseId)
    {
        // Allow access if user is admin or belongs to this warehouse
        return $user->isAdmin() || $user->warehouse_id === $warehouseId;
    }
}
