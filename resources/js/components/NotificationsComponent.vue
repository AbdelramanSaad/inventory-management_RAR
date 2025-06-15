<template>
  <div class="notifications-container">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>{{ $t('notifications.title') }}</h5>
        <button 
          v-if="notifications.length > 0" 
          class="btn btn-sm btn-outline-secondary"
          @click="clearAllNotifications"
        >
          {{ $t('notifications.clear_all') }}
        </button>
      </div>
      <div class="card-body p-0">
        <div v-if="notifications.length === 0" class="text-center p-4">
          <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
          <p class="text-muted">{{ $t('notifications.no_notifications') }}</p>
        </div>
        <transition-group name="notification-list" tag="ul" class="list-group list-group-flush">
          <li 
            v-for="notification in notifications" 
            :key="notification.id"
            class="list-group-item notification-item"
            :class="{'unread': !notification.read}"
          >
            <div class="d-flex justify-content-between align-items-start">
              <div class="notification-content">
                <div class="notification-header d-flex align-items-center">
                  <span 
                    class="notification-icon me-2"
                    :class="getNotificationIconClass(notification.type)"
                  >
                    <i :class="getNotificationIcon(notification.type)"></i>
                  </span>
                  <h6 class="mb-0">{{ notification.title }}</h6>
                </div>
                <p class="notification-message mb-1">{{ notification.message }}</p>
                <div class="notification-meta d-flex align-items-center">
                  <small class="text-muted me-2">
                    <i class="far fa-clock me-1"></i>
                    {{ formatTime(notification.timestamp) }}
                  </small>
                  <small v-if="notification.item" class="text-muted">
                    <i class="fas fa-box me-1"></i>
                    {{ notification.item.name }}
                  </small>
                </div>
              </div>
              <div class="notification-actions">
                <button 
                  class="btn btn-sm btn-link p-0 text-muted" 
                  @click="removeNotification(notification.id)"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </li>
        </transition-group>
      </div>
    </div>
  </div>
</template>

<script>
import { listenForInventoryEvents } from '../echo-setup';
import { formatDistanceToNow } from 'date-fns';
import { ar, enUS } from 'date-fns/locale';

export default {
  name: 'NotificationsComponent',
  
  data() {
    return {
      notifications: [],
      warehouseId: null,
      maxNotifications: 20
    };
  },
  
  created() {
    // Get user's warehouse ID from auth state
    this.warehouseId = this.$store.state.auth.user.warehouse_id;
    
    // Load existing notifications from local storage
    this.loadNotifications();
    
    // Setup WebSocket listeners
    if (this.warehouseId) {
      listenForInventoryEvents(this.warehouseId);
      
      // Listen for custom events from echo-setup.js
      window.addEventListener('inventory-notification', this.handleNewNotification);
    }
  },
  
  beforeDestroy() {
    window.removeEventListener('inventory-notification', this.handleNewNotification);
  },
  
  methods: {
    handleNewNotification(event) {
      const notification = {
        id: Date.now(),
        ...event.detail,
        read: false
      };
      
      // Add to the beginning of the array
      this.notifications.unshift(notification);
      
      // Limit the number of notifications
      if (this.notifications.length > this.maxNotifications) {
        this.notifications = this.notifications.slice(0, this.maxNotifications);
      }
      
      // Save to local storage
      this.saveNotifications();
      
      // Show browser notification if supported
      this.showBrowserNotification(notification);
    },
    
    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id);
      this.saveNotifications();
    },
    
    clearAllNotifications() {
      this.notifications = [];
      this.saveNotifications();
    },
    
    loadNotifications() {
      const saved = localStorage.getItem(`notifications_${this.warehouseId}`);
      if (saved) {
        try {
          this.notifications = JSON.parse(saved);
        } catch (e) {
          console.error('Failed to parse notifications from localStorage', e);
          this.notifications = [];
        }
      }
    },
    
    saveNotifications() {
      localStorage.setItem(
        `notifications_${this.warehouseId}`, 
        JSON.stringify(this.notifications)
      );
    },
    
    formatTime(timestamp) {
      try {
        const date = new Date(timestamp);
        const locale = this.$i18n.locale === 'ar' ? ar : enUS;
        return formatDistanceToNow(date, { addSuffix: true, locale });
      } catch (e) {
        return timestamp;
      }
    },
    
    getNotificationIcon(type) {
      switch (type) {
        case 'success':
          return 'fas fa-check-circle';
        case 'warning':
          return 'fas fa-exclamation-triangle';
        case 'danger':
          return 'fas fa-exclamation-circle';
        case 'info':
        default:
          return 'fas fa-info-circle';
      }
    },
    
    getNotificationIconClass(type) {
      switch (type) {
        case 'success':
          return 'text-success';
        case 'warning':
          return 'text-warning';
        case 'danger':
          return 'text-danger';
        case 'info':
        default:
          return 'text-info';
      }
    },
    
    showBrowserNotification(notification) {
      if (!('Notification' in window)) {
        return;
      }
      
      if (Notification.permission === 'granted') {
        new Notification(notification.title, {
          body: notification.message,
          icon: '/img/logo.png'
        });
      } else if (Notification.permission !== 'denied') {
        Notification.requestPermission();
      }
    }
  }
};
</script>

<style scoped>
.notifications-container {
  max-width: 500px;
  margin: 0 auto;
}

.notification-item {
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.notification-item.unread {
  background-color: rgba(13, 110, 253, 0.05);
  border-left-color: #0d6efd;
}

.notification-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: rgba(0, 0, 0, 0.05);
}

.notification-list-enter-active, .notification-list-leave-active {
  transition: all 0.5s;
}

.notification-list-enter, .notification-list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.notification-list-move {
  transition: transform 0.5s;
}
</style>
