<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} - الإشعارات</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        
        [lang="ar"] {
            direction: rtl;
            text-align: right;
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-size: 0.6rem;
        }
        
        .notifications-container {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 350px;
            max-width: 90vw;
            z-index: 1000;
        }
        
        [lang="ar"] .notifications-container {
            right: auto;
            left: 20px;
        }
        
        .demo-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 0.5rem;
            background-color: white;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">{{ config('app.name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-home"></i> الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-box"></i> المخزون</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-warehouse"></i> المستودعات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-clipboard-list"></i> سجلات التدقيق</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-link text-white position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationsDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                            <div id="notifications-dropdown-container"></div>
                        </div>
                    </div>
                    <div class="dropdown ms-3">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog"></i> الإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h1 class="mb-4">عرض توضيحي للإشعارات الفورية</h1>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="demo-section">
                    <h2>كيفية عمل الإشعارات الفورية</h2>
                    <p>
                        يستخدم نظام إدارة المخزون والتدقيق Laravel WebSockets لتوفير تحديثات فورية للمستخدمين عند حدوث تغييرات في المخزون.
                        يتم بث الأحداث عبر قنوات خاصة بالمستودع، مما يضمن أن المستخدمين يتلقون فقط الإشعارات المتعلقة بالمستودعات التي لديهم صلاحية الوصول إليها.
                    </p>
                    
                    <h3 class="mt-4">أنواع الإشعارات</h3>
                    <ul>
                        <li><strong>إضافة عنصر جديد</strong> - عند إضافة عنصر جديد إلى المخزون</li>
                        <li><strong>تحديث عنصر</strong> - عند تعديل معلومات عنصر موجود</li>
                        <li><strong>تعديل المخزون</strong> - عند تغيير كمية عنصر في المخزون</li>
                        <li><strong>تنبيه المخزون المنخفض</strong> - عندما تنخفض كمية عنصر تحت الحد الأدنى</li>
                        <li><strong>حذف عنصر</strong> - عند إزالة عنصر من المخزون</li>
                    </ul>
                    
                    <h3 class="mt-4">اختبار الإشعارات</h3>
                    <p>يمكنك اختبار نظام الإشعارات باستخدام الأزرار أدناه:</p>
                    
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button id="test-new-item" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> إضافة عنصر جديد
                        </button>
                        <button id="test-update-item" class="btn btn-info">
                            <i class="fas fa-edit"></i> تحديث عنصر
                        </button>
                        <button id="test-adjust-stock" class="btn btn-primary">
                            <i class="fas fa-layer-group"></i> تعديل المخزون
                        </button>
                        <button id="test-low-stock" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle"></i> تنبيه المخزون المنخفض
                        </button>
                        <button id="test-delete-item" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> حذف عنصر
                        </button>
                    </div>
                </div>
                
                <div class="demo-section mt-4">
                    <h2>توثيق API</h2>
                    <p>
                        يوفر النظام توثيقًا شاملاً للـ API باستخدام Swagger/OpenAPI، مما يسهل على المطورين فهم واستخدام نقاط النهاية المتاحة.
                    </p>
                    <a href="/api/documentation" class="btn btn-primary" target="_blank">
                        <i class="fas fa-book"></i> عرض توثيق API
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="position-sticky" style="top: 2rem;">
                    <div id="notifications-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Test Notifications Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Test notification functions
            const testNewItem = document.getElementById('test-new-item');
            const testUpdateItem = document.getElementById('test-update-item');
            const testAdjustStock = document.getElementById('test-adjust-stock');
            const testLowStock = document.getElementById('test-low-stock');
            const testDeleteItem = document.getElementById('test-delete-item');
            
            // Sample notification data
            const createNotificationEvent = (type) => {
                const events = {
                    'item_created': {
                        id: Date.now(),
                        name: 'كمبيوتر محمول HP ProBook',
                        type: 'item_created',
                        message: 'تم إضافة عنصر جديد: كمبيوتر محمول HP ProBook',
                        warehouse_id: 1,
                        timestamp: new Date().toISOString()
                    },
                    'item_updated': {
                        id: Date.now(),
                        name: 'كمبيوتر محمول HP ProBook',
                        type: 'item_updated',
                        message: 'تم تحديث العنصر: كمبيوتر محمول HP ProBook',
                        warehouse_id: 1,
                        timestamp: new Date().toISOString()
                    },
                    'stock_adjusted': {
                        id: Date.now(),
                        name: 'كمبيوتر محمول HP ProBook',
                        type: 'stock_adjusted',
                        message: 'تم تعديل مخزون كمبيوتر محمول HP ProBook من 10 إلى 15',
                        warehouse_id: 1,
                        timestamp: new Date().toISOString()
                    },
                    'low_stock_alert': {
                        id: Date.now(),
                        name: 'كمبيوتر محمول HP ProBook',
                        type: 'low_stock_alert',
                        message: 'تنبيه: المخزون منخفض لـ كمبيوتر محمول HP ProBook (3 متبقي)',
                        warehouse_id: 1,
                        timestamp: new Date().toISOString()
                    },
                    'item_deleted': {
                        id: Date.now(),
                        name: 'كمبيوتر محمول HP ProBook',
                        type: 'item_deleted',
                        message: 'تم حذف العنصر: كمبيوتر محمول HP ProBook',
                        warehouse_id: 1,
                        timestamp: new Date().toISOString()
                    }
                };
                
                return events[type];
            };
            
            // Dispatch custom event to simulate WebSocket event
            const dispatchNotificationEvent = (type) => {
                const event = new CustomEvent('inventory-notification', {
                    detail: createNotificationEvent(type)
                });
                
                window.dispatchEvent(event);
            };
            
            // Add event listeners to test buttons
            testNewItem.addEventListener('click', () => dispatchNotificationEvent('item_created'));
            testUpdateItem.addEventListener('click', () => dispatchNotificationEvent('item_updated'));
            testAdjustStock.addEventListener('click', () => dispatchNotificationEvent('stock_adjusted'));
            testLowStock.addEventListener('click', () => dispatchNotificationEvent('low_stock_alert'));
            testDeleteItem.addEventListener('click', () => dispatchNotificationEvent('item_deleted'));
        });
    </script>
</body>
</html>
