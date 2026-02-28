<?php
// 设置更长的执行时间（首次运行需要下载模型，可能较慢）
set_time_limit(300); // 5分钟超时
ignore_user_abort(true);

// 禁用错误显示，确保返回 JSON
error_reporting(0);
ini_set('display_errors', 0);

// 允许的文件类型
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

// 日志函数
function logMessage($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// 异常处理函数
function handleError($errno, $errstr, $errfile, $errline) {
    logMessage("PHP Error: [$errno] $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => "服务器错误: $errstr"]);
    }
    exit;
}

// 异常处理
set_error_handler('handleError');
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logMessage("Fatal Error: [{$error['type']}] {$error['message']} in {$error['file']} on line {$error['line']}");
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => '服务器内部错误']);
        }
    }
});

logMessage('=== 开始处理上传 ===');

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = '没有文件上传或上传出错';
    if (isset($_FILES['file'])) {
        $errorMsg .= ' (错误代码: ' . $_FILES['file']['error'] . ')';
    }
    logMessage($errorMsg);
    header('Content-Type: application/json');
    echo json_encode(['error' => $errorMsg]);
    exit;
}

$file = $_FILES['file'];
logMessage('接收到文件: ' . $file['name'] . ', 大小: ' . $file['size'] . ' 字节');
logMessage('临时文件路径: ' . $file['tmp_name']);

// 检查文件类型
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

logMessage('文件MIME类型: ' . $mimeType);

if (!in_array($mimeType, $allowedTypes)) {
    logMessage('不允许的文件类型');
    header('Content-Type: application/json');
    echo json_encode(['error' => '不允许的文件类型']);
    exit;
}

// 创建临时目录
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
logMessage('上传目录: ' . $uploadDir);

if (!is_dir($uploadDir)) {
    logMessage('目录不存在，尝试创建...');
    if (mkdir($uploadDir, 0777, true)) {
        logMessage('目录创建成功');
    } else {
        logMessage('目录创建失败');
        header('Content-Type: application/json');
        echo json_encode(['error' => '无法创建上传目录']);
        exit;
    }
}

// 检查目录是否可写
if (!is_writable($uploadDir)) {
    logMessage('目录不可写');
    header('Content-Type: application/json');
    echo json_encode(['error' => '上传目录不可写']);
    exit;
}

logMessage('目录检查通过，可写');

// 定期清理旧的临时文件（超过1小时的文件）
$tempFiles = glob($uploadDir . DIRECTORY_SEPARATOR . '*');
$currentTime = time();
foreach ($tempFiles as $tempFile) {
    if (is_file($tempFile) && ($currentTime - filemtime($tempFile)) > 3600) {
        logMessage('清理旧临时文件: ' . $tempFile);
        unlink($tempFile);
    }
}

// 生成唯一文件名（加上更随机的前缀以避免高并发时冲突）
$uniqueId = uniqid(mt_rand(), true);
$inputFile = $uploadDir . DIRECTORY_SEPARATOR . $uniqueId . '_input.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$outputFile = $uploadDir . DIRECTORY_SEPARATOR . $uniqueId . '_output.png';

logMessage('输入文件路径: ' . $inputFile);
logMessage('输出文件路径: ' . $outputFile);

// 检查临时文件是否存在
if (!file_exists($file['tmp_name'])) {
    logMessage('临时文件不存在');
    header('Content-Type: application/json');
    echo json_encode(['error' => '临时文件不存在']);
    exit;
}

// 保存上传的文件
logMessage('尝试移动文件...');
if (!move_uploaded_file($file['tmp_name'], $inputFile)) {
    logMessage('文件移动失败');
    // 尝试复制作为备选方案
    logMessage('尝试复制文件...');
    if (copy($file['tmp_name'], $inputFile)) {
        logMessage('文件复制成功');
    } else {
        logMessage('文件复制也失败');
        header('Content-Type: application/json');
        echo json_encode(['error' => '文件保存失败']);
        exit;
    }
} else {
    logMessage('文件移动成功');
}

// 验证文件是否保存成功
if (!file_exists($inputFile)) {
    logMessage('保存后文件不存在');
    header('Content-Type: application/json');
    echo json_encode(['error' => '文件保存验证失败']);
    exit;
}

logMessage('文件保存验证成功，文件大小: ' . filesize($inputFile) . ' 字节');

// 调用Python脚本处理图片
$pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'removeBackground.py';
$command = "python \"$pythonScript\" \"$inputFile\" \"$outputFile\" 2>&1";
logMessage('执行命令: ' . $command);
exec($command, $output, $returnCode);
logMessage('Python输出: ' . implode("\n", $output));
logMessage('Python返回码: ' . $returnCode);

// 检查处理是否成功
if ($returnCode !== 0 || !file_exists($outputFile)) {
    logMessage('图片处理失败');
    // 清理临时文件
    if (file_exists($inputFile)) {
        unlink($inputFile);
    }
    header('Content-Type: application/json');
    echo json_encode(['error' => '图片处理失败: ' . implode("\n", $output)]);
    exit;
}

logMessage('图片处理成功');

// 输出处理后的图片
header('Content-Type: image/png');
// 移除 Content-Disposition: attachment，让前端能正常显示图片
readfile($outputFile);

// 清理临时文件
if (file_exists($inputFile)) {
    unlink($inputFile);
}
if (file_exists($outputFile)) {
    unlink($outputFile);
}
