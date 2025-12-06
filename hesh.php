<?php
// image_info.php
header('Content-Type: application/json');

// Параметры из URL
$image_url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($image_url)) {
    echo json_encode(['error' => 'URL параметр отсутствует']);
    exit;
}

// Валидация URL
if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Неверный URL']);
    exit;
}

try {
    // Получение заголовков для определения размера
    $headers = get_headers($image_url, 1);
    
    if (!$headers) {
        throw new Exception('Не удалось получить данные изображения');
    }
    
    // Получение размера из заголовков
    $size = 0;
    if (isset($headers['Content-Length'])) {
        $size = is_array($headers['Content-Length']) 
            ? end($headers['Content-Length']) 
            : $headers['Content-Length'];
    }
    
    // Скачивание изображения для вычисления хеша
    $image_data = @file_get_contents($image_url);
    
    if ($image_data === false) {
        throw new Exception('Не удалось загрузить изображение');
    }
    
    // Вычисление хешей
    $md5_hash = md5($image_data);
    $sha1_hash = sha1($image_data);
    
    // Получение информации о изображении
    $image_info = @getimagesizefromstring($image_data);
    $dimensions = $image_info ? [
        'width' => $image_info[0],
        'height' => $image_info[1],
        'mime' => $image_info['mime']
    ] : null;
    
    // Формирование ответа
    $response = [
        'success' => true,
        'url' => $image_url,
        'size_bytes' => (int)$size,
        'size_formatted' => format_bytes((int)$size),
        'hashes' => [
            'md5' => $md5_hash,
            'sha1' => $sha1_hash
        ],
        'dimensions' => $dimensions,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'url' => $image_url
    ]);
}

// Функция для форматирования размера
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
