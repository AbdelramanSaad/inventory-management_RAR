<?php

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class InventoryItemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var InventoryItem
     */
    public $inventoryItem;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $message;

    /**
     * Create a new notification instance.
     *
     * @param InventoryItem $inventoryItem
     * @param string $type
     * @param string $message
     */
    public function __construct(InventoryItem $inventoryItem, string $type, string $message)
    {
        $this->inventoryItem = $inventoryItem;
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Inventory Notification: {$this->type}")
            ->line($this->message)
            ->line("Item: {$this->inventoryItem->name}")
            ->line("Warehouse: {$this->inventoryItem->warehouse->name}")
            ->line("Current Quantity: {$this->inventoryItem->quantity}")
            ->line("Minimum Stock Level: {$this->inventoryItem->min_stock_level}")
            ->action('View Item', url("/inventory-items/{$this->inventoryItem->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'inventory_item_id' => $this->inventoryItem->id,
            'warehouse_id' => $this->inventoryItem->warehouse_id,
            'type' => $this->type,
            'message' => $this->message,
            'item_name' => $this->inventoryItem->name,
            'quantity' => $this->inventoryItem->quantity,
            'min_stock_level' => $this->inventoryItem->min_stock_level,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'inventory_item_id' => $this->inventoryItem->id,
            'warehouse_id' => $this->inventoryItem->warehouse_id,
            'type' => $this->type,
            'message' => $this->message,
            'item_name' => $this->inventoryItem->name,
            'quantity' => $this->inventoryItem->quantity,
            'min_stock_level' => $this->inventoryItem->min_stock_level,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
