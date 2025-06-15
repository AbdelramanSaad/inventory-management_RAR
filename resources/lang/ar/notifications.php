<?php

return [
    'title' => 'الإشعارات',
    'clear_all' => 'مسح الكل',
    'no_notifications' => 'لا توجد إشعارات جديدة',
    'types' => [
        'item_created' => 'تم إضافة عنصر جديد',
        'item_updated' => 'تم تحديث العنصر',
        'stock_adjusted' => 'تم تعديل المخزون',
        'low_stock_alert' => 'تنبيه: مخزون منخفض',
        'item_deleted' => 'تم حذف العنصر'
    ],
    'messages' => [
        'new_item' => 'تم إضافة عنصر جديد: :name',
        'item_updated' => 'تم تحديث العنصر: :name',
        'stock_adjusted' => 'تم تعديل مخزون :name من :old إلى :new',
        'low_stock' => 'تنبيه: المخزون منخفض لـ :name (:quantity متبقي)',
        'item_deleted' => 'تم حذف العنصر: :name'
    ],
    'browser' => [
        'permission_title' => 'السماح بالإشعارات',
        'permission_message' => 'هل ترغب في تلقي إشعارات فورية عن تغييرات المخزون؟'
    ]
];
