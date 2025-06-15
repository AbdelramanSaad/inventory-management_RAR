import './bootstrap';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import './echo-setup';

// Import components
import NotificationsComponent from './components/NotificationsComponent.vue';

// Import translations
const messages = {
    en: {
        notifications: {
            title: 'Notifications',
            clear_all: 'Clear All',
            no_notifications: 'No new notifications'
        }
    },
    ar: {
        notifications: {
            title: 'الإشعارات',
            clear_all: 'مسح الكل',
            no_notifications: 'لا توجد إشعارات جديدة'
        }
    }
};

// Create i18n instance
const i18n = createI18n({
    locale: document.documentElement.lang || 'en',
    fallbackLocale: 'en',
    messages
});

// Create Vue application
const app = createApp({});

// Register components
app.component('notifications', NotificationsComponent);

// Use plugins
app.use(i18n);

// Mount the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    app.mount('#app');
});
