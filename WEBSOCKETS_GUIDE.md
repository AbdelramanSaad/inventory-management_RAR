# دليل استخدام WebSockets في نظام إدارة المخزون والتدقيق

## مقدمة

يوفر نظام إدارة المخزون والتدقيق إشعارات فورية باستخدام Laravel WebSockets، وهو بديل مجاني ومفتوح المصدر لخدمة Pusher. يتيح هذا للمستخدمين تلقي تحديثات فورية عند حدوث تغييرات في المخزون، مثل إضافة عناصر جديدة، تعديل المخزون، أو انخفاض المخزون تحت الحد الأدنى.

## الإعداد

### 1. إعداد الخادم (Backend)

#### تثبيت الحزم المطلوبة

```bash
composer require beyondcode/laravel-websockets pusher/pusher-php-server
```

#### نشر ملفات التكوين

```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

#### تعديل ملف `.env`

```
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

#### تشغيل خادم WebSockets

```bash
php artisan websockets:serve
```

### 2. إعداد العميل (Frontend)

#### تثبيت الحزم المطلوبة

```bash
npm install laravel-echo pusher-js
```

#### إعداد Laravel Echo

قم بإنشاء ملف `resources/js/echo-setup.js`:

```javascript
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
```

## هيكل النظام

### 1. قنوات البث (Broadcast Channels)

يستخدم النظام قنوات خاصة لكل مستودع لضمان أن المستخدمين يتلقون فقط الإشعارات المتعلقة بالمستودعات التي لديهم صلاحية الوصول إليها.

```php
// routes/channels.php
Broadcast::channel('warehouse.{warehouseId}', WarehouseChannel::class);
```

```php
// app/Broadcasting/WarehouseChannel.php
public function join(User $user, int $warehouseId)
{
    // السماح بالوصول إذا كان المستخدم مسؤولاً أو ينتمي إلى هذا المستودع
    return $user->isAdmin() || $user->warehouse_id === $warehouseId;
}
```

### 2. الأحداث (Events)

يتم بث الأحداث المتعلقة بعناصر المخزون باستخدام فئة `InventoryItemEvent` التي تنفذ واجهة `ShouldBroadcast`:

```php
// app/Events/InventoryItemEvent.php
class InventoryItemEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $inventoryItem;
    public $type;
    public $message;

    public function __construct(InventoryItem $inventoryItem, string $type, string $message)
    {
        $this->inventoryItem = $inventoryItem;
        $this->type = $type;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // البث على قناة خاصة محددة للمستودع
        return new PrivateChannel('warehouse.' . $this->inventoryItem->warehouse_id);
    }

    public function broadcastAs()
    {
        return 'inventory.event';
    }

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
```

### 3. المراقبون (Observers)

يتم استخدام مراقب `InventoryItemObserver` لالتقاط أحداث CRUD على عناصر المخزون وبث الأحداث المناسبة:

```php
// app/Observers/InventoryItemObserver.php
public function created(InventoryItem $inventoryItem)
{
    // إنشاء سجل تدقيق
    $this->createAuditLog($inventoryItem, 'created', 'تم إنشاء عنصر جديد');
    
    // بث حدث
    event(new InventoryItemEvent(
        $inventoryItem,
        'item_created',
        "تم إضافة عنصر جديد: {$inventoryItem->name}"
    ));
}

public function updated(InventoryItem $inventoryItem)
{
    // التحقق مما إذا تم تغيير الكمية
    if ($inventoryItem->isDirty('quantity')) {
        $oldQuantity = $inventoryItem->getOriginal('quantity');
        $newQuantity = $inventoryItem->quantity;
        
        // إنشاء سجل تدقيق
        $this->createAuditLog(
            $inventoryItem,
            'stock_adjusted',
            "تم تعديل المخزون من {$oldQuantity} إلى {$newQuantity}"
        );
        
        // بث حدث تعديل المخزون
        event(new InventoryItemEvent(
            $inventoryItem,
            'stock_adjusted',
            "تم تعديل مخزون {$inventoryItem->name} من {$oldQuantity} إلى {$newQuantity}"
        ));
        
        // التحقق من المخزون المنخفض
        if ($newQuantity < $inventoryItem->min_stock_level) {
            // بث تنبيه المخزون المنخفض
            event(new InventoryItemEvent(
                $inventoryItem,
                'low_stock_alert',
                "تنبيه: المخزون منخفض لـ {$inventoryItem->name} ({$newQuantity} متبقي)"
            ));
            
            // إرسال إشعار إلى مدير المستودع
            $this->sendLowStockNotification($inventoryItem);
        }
    } else {
        // إنشاء سجل تدقيق للتحديثات الأخرى
        $this->createAuditLog($inventoryItem, 'updated', 'تم تحديث معلومات العنصر');
        
        // بث حدث
        event(new InventoryItemEvent(
            $inventoryItem,
            'item_updated',
            "تم تحديث العنصر: {$inventoryItem->name}"
        ));
    }
}
```

### 4. الإشعارات (Notifications)

يتم إرسال الإشعارات إلى مديري المستودعات باستخدام فئة `InventoryItemNotification`:

```php
// app/Notifications/InventoryItemNotification.php
class InventoryItemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $inventoryItem;
    protected $type;
    protected $message;

    public function __construct(InventoryItem $inventoryItem, string $type, string $message)
    {
        $this->inventoryItem = $inventoryItem;
        $this->type = $type;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

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
```

## استخدام WebSockets في الواجهة الأمامية

### 1. الاستماع للأحداث

```javascript
// الاستماع لأحداث المخزون على قناة خاصة بالمستودع
window.Echo.private(`warehouse.${warehouseId}`)
    .listen('.inventory.event', (event) => {
        console.log('Inventory event received:', event);
        
        // إنشاء إشعار بناءً على نوع الحدث
        const notification = createNotification(event);
        
        // عرض الإشعار
        showNotification(notification);
    });
```

### 2. مكون الإشعارات في Vue.js

يمكن استخدام مكون `NotificationsComponent.vue` لعرض الإشعارات في الواجهة الأمامية:

```javascript
// في ملف app.js
import NotificationsComponent from './components/NotificationsComponent.vue';
app.component('notifications', NotificationsComponent);
```

```html
<!-- في القالب -->
<notifications></notifications>
```

## أنواع الأحداث

1. **item_created**: عند إنشاء عنصر جديد في المخزون
2. **item_updated**: عند تحديث معلومات عنصر موجود
3. **stock_adjusted**: عند تغيير كمية عنصر في المخزون
4. **low_stock_alert**: عندما تنخفض كمية عنصر تحت الحد الأدنى
5. **item_deleted**: عند حذف عنصر من المخزون

## الاختبار

### 1. اختبار WebSockets

يمكنك اختبار وظائف WebSockets باستخدام الاختبارات المضمنة:

```bash
php artisan test --filter=WebSocketNotificationTest
```

### 2. اختبار واجهة برمجة التطبيقات (API) مع WebSockets

```bash
php artisan test --filter=ApiWebSocketIntegrationTest
```

### 3. اختبار يدوي

يمكنك اختبار الإشعارات الفورية يدويًا باستخدام صفحة العرض التوضيحي:

```
http://localhost:8000/notifications-demo
```

## استكشاف الأخطاء وإصلاحها

### 1. التحقق من حالة خادم WebSockets

يمكنك التحقق من حالة خادم WebSockets باستخدام لوحة التحكم:

```
http://localhost:8000/laravel-websockets
```

### 2. مشاكل الاتصال

- تأكد من أن خادم WebSockets يعمل
- تحقق من إعدادات CORS
- تأكد من تكوين Laravel Echo بشكل صحيح
- تحقق من أن المستخدم لديه صلاحية الوصول إلى القناة

### 3. مشاكل المصادقة

- تأكد من أن المستخدم مصادق عليه
- تحقق من تكوين قنوات البث
- تأكد من أن المستخدم لديه صلاحية الوصول إلى المستودع

## الخلاصة

يوفر نظام WebSockets في نظام إدارة المخزون والتدقيق تجربة مستخدم تفاعلية من خلال الإشعارات الفورية. يتم تنظيم الإشعارات حسب المستودع لضمان أن المستخدمين يتلقون فقط المعلومات ذات الصلة بمسؤولياتهم.
