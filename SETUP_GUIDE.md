# دليل إعداد وتشغيل نظام إدارة المخزون والتدقيق

## متطلبات النظام

- PHP 8.1+
- Composer
- MySQL 5.7+ أو PostgreSQL
- Node.js و NPM

## خطوات الإعداد

### 1. تثبيت المتطلبات الأساسية

```bash
# تثبيت اعتماديات PHP
composer install

# تثبيت اعتماديات JavaScript
npm install
```

### 2. إعداد ملف البيئة

```bash
# نسخ ملف البيئة
cp .env.example .env

# توليد مفتاح التطبيق ومفتاح JWT
php artisan key:generate
php artisan jwt:secret
```

قم بتعديل ملف `.env` لتكوين اتصال قاعدة البيانات والإعدادات الأخرى:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=root
DB_PASSWORD=

# إعدادات WebSockets
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

WEBSOCKETS_ENABLED=true
```

### 3. إعداد قاعدة البيانات

```bash
# تشغيل الترحيلات وبذر البيانات
php artisan migrate --seed
```

### 4. إعداد الملفات الثابتة

```bash
# ربط مجلد التخزين للصور والملفات
php artisan storage:link
```

### 5. إعداد وتشغيل WebSockets

```bash
# نشر ملفات تكوين WebSockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"

# تشغيل خادم WebSockets
php artisan websockets:serve
```

### 6. توليد توثيق Swagger

```bash
# نشر ملفات تكوين Swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"

# توليد توثيق Swagger
php artisan l5-swagger:generate
```

### 7. تشغيل التطبيق

```bash
# تشغيل خادم التطوير
php artisan serve
```

## الوصول إلى التطبيق

- واجهة المستخدم الرئيسية: http://localhost:8000
- توثيق API (Swagger): http://localhost:8000/api/documentation
- لوحة تحكم WebSockets: http://localhost:8000/laravel-websockets

## اختبار النظام

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل اختبارات WebSockets
php artisan test --filter=WebSocketNotificationTest

# تشغيل اختبارات توثيق Swagger
php artisan test --filter=SwaggerDocumentationTest
```

## استخدام WebSockets في الواجهة الأمامية

### 1. إعداد Laravel Echo

تأكد من تضمين ملف `resources/js/echo-setup.js` في تطبيق الواجهة الأمامية الخاص بك:

```javascript
// في ملف app.js أو ما يعادله
import './echo-setup';
```

### 2. الاستماع للأحداث

```javascript
// الاستماع لأحداث المخزون
window.Echo.private(`warehouse.${warehouseId}`)
    .listen('.inventory.event', (event) => {
        console.log('Inventory event received:', event);
        // معالجة الحدث (مثل عرض إشعار)
    });
```

### 3. استخدام مكون الإشعارات

يمكنك استخدام مكون `NotificationsComponent.vue` في تطبيق Vue الخاص بك:

```javascript
import NotificationsComponent from './components/NotificationsComponent.vue';

// تسجيل المكون
Vue.component('notifications', NotificationsComponent);
```

ثم استخدامه في القالب:

```html
<notifications></notifications>
```

## الأدوار والصلاحيات

- **Admin**: وصول كامل لجميع الميزات
- **Warehouse Manager**: إدارة عناصر المخزون في المستودع المخصص
- **Staff**: عرض عناصر المخزون وسجلات التدقيق للمستودع المخصص

## الإشعارات الفورية

يدعم النظام الإشعارات الفورية للأحداث التالية:

- إنشاء عنصر جديد
- تحديث عنصر موجود
- تعديل كمية المخزون
- تنبيه المخزون المنخفض
- حذف عنصر

يتم إرسال الإشعارات عبر قنوات خاصة بالمستودع، مما يضمن أن المستخدمين يتلقون فقط الإشعارات المتعلقة بالمستودعات التي لديهم صلاحية الوصول إليها.
