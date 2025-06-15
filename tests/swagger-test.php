<?php

// ملف اختبار بسيط للتحقق من الوصول إلى ملف توثيق Swagger JSON
$apiDocsPath = __DIR__ . '/../storage/api-docs/api-docs.json';

if (file_exists($apiDocsPath)) {
    echo "ملف التوثيق موجود في: " . $apiDocsPath . PHP_EOL;
    $content = file_get_contents($apiDocsPath);
    $json = json_decode($content, true);
    if ($json === null) {
        echo "خطأ في تحليل ملف JSON: " . json_last_error_msg() . PHP_EOL;
    } else {
        echo "تم تحليل ملف JSON بنجاح!" . PHP_EOL;
        echo "عنوان API: " . $json['info']['title'] . PHP_EOL;
        echo "عدد المسارات: " . count($json['paths']) . PHP_EOL;
    }
} else {
    echo "ملف التوثيق غير موجود في: " . $apiDocsPath . PHP_EOL;
}
