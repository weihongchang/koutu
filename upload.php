
<?php
// 允许的文件类型
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '没有文件上传或上传出错']);
    exit;
}

$file = $_FILES['file'];

// 检查文件类型
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '不允许的文件类型']);
    exit;
}

// 创建临时目录
$uploadDir = __DIR__ . '/temp';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 生成唯一文件名
$uniqueId = uniqid();
$inputFile = $uploadDir . '/' . $uniqueId . '_input.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$outputFile = $uploadDir . '/' . $uniqueId . '_output.png';

// 保存上传的文件
if (!move_uploaded_file($file['tmp_name'], $inputFile)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '文件保存失败']);
    exit;
}

// 调用Python脚本处理图片
$pythonScript = __DIR__ . '/removeBackground.py';
$command = "python \"$pythonScript\" \"$inputFile\" \"$outputFile\" 2>&1";
exec($command, $output, $returnCode);

// 检查处理是否成功
if ($returnCode !== 0 || !file_exists($outputFile)) {
    // 清理临时文件
    if (file_exists($inputFile)) {
        unlink($inputFile);
    }
    header('Content-Type: application/json');
    echo json_encode(['error' => '图片处理失败: ' . implode("\n", $output)]);
    exit;
}

// 输出处理后的图片
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="result.png"');
readfile($outputFile);

// 清理临时文件
if (file_exists($inputFile)) {
    unlink($inputFile);
}
if (file_exists($outputFile)) {
    unlink($outputFile);
}
?>
