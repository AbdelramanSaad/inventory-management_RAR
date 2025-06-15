<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryItemEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The inventory item instance.
     *
     * @var InventoryItem
     */
    public $inventoryItem;

    /**
     * The event type.
     *
     * @var string
     */
    public $type;

    /**
     * The event message.
     *
     * @var string
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param InventoryItem $inventoryItem
     * @param string $type
     * @param string $message
     * @return void
     */
    public function __construct(InventoryItem $inventoryItem, string $type, string $message)
    {
        $this->inventoryItem = $inventoryItem;
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast to a private channel specific to the warehouse
        return new PrivateChannel('warehouse.' . $this->inventoryItem->warehouse_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'inventory.event';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->inventoryItem->id,
            'name' => $this->inventoryItem->name,
            'type' => $this->type,
            'message' => $this->message,
            'warehouse_id' => $this->inventoryItem->warehouse_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
