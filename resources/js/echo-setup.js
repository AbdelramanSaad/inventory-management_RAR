/**
 * Laravel Echo setup for real-time notifications
 * This file configures Laravel Echo to connect to Laravel WebSockets
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY || 'local',
    wsHost: window.location.hostname,
    wsPort: process.env.MIX_PUSHER_PORT || 6001,
    wssPort: process.env.MIX_PUSHER_PORT || 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

/**
 * Listen for inventory events on the private warehouse channel
 * @param {number} warehouseId - The ID of the warehouse to listen for events
 */
export function listenForInventoryEvents(warehouseId) {
    window.Echo.private(`warehouse.${warehouseId}`)
        .listen('.inventory.event', (event) => {
            console.log('Inventory event received:', event);
            
            // Create notification based on event type
            const notification = createNotification(event);
            
            // Display the notification
            showNotification(notification);
        });
}

/**
 * Create a notification object based on the event type
 * @param {Object} event - The event object received from WebSockets
 * @returns {Object} - Notification object with title, message, and type
 */
function createNotification(event) {
    let title = 'Inventory Update';
    let type = 'info';
    
    switch (event.type) {
        case 'item_created':
            title = 'New Item Added';
            type = 'success';
            break;
        case 'item_updated':
            title = 'Item Updated';
            type = 'info';
            break;
        case 'stock_adjusted':
            title = 'Stock Adjusted';
            type = 'info';
            break;
        case 'low_stock_alert':
            title = 'Low Stock Alert';
            type = 'warning';
            break;
        case 'item_deleted':
            title = 'Item Deleted';
            type = 'danger';
            break;
    }
    
    return {
        title,
        message: event.message,
        type,
        item: {
            id: event.id,
            name: event.name
        },
        timestamp: event.timestamp
    };
}

/**
 * Display a notification to the user
 * This function can be implemented differently based on the UI framework used
 * @param {Object} notification - The notification object to display
 */
function showNotification(notification) {
    // This is a placeholder - implement based on your UI framework
    // For example, if using Bootstrap toasts:
    if (window.showToast) {
        window.showToast(notification);
    } else {
        // Fallback to console if no UI implementation is available
        console.log(`${notification.title}: ${notification.message}`);
    }
    
    // Play sound for important notifications
    if (notification.type === 'warning' || notification.type === 'danger') {
        playNotificationSound();
    }
}

/**
 * Play a notification sound for important alerts
 */
function playNotificationSound() {
    const audio = new Audio('/sounds/notification.mp3');
    audio.play().catch(error => {
        console.log('Could not play notification sound:', error);
    });
}
