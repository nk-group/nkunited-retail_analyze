<?php

// minimal_check.php - 最小限のチェック
// php minimal_check.php

echo "=== Minimal Project Check ===\n";

// 1. 基本ファイルの存在確認
echo "1. Checking basic files:\n";
$requiredFiles = [
    'vendor/autoload.php',
    'app/Config/Paths.php', 
    'app/Config/Boot.php',
    'spark'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "  ✓ {$file} exists\n";
    } else {
        echo "  ✗ {$file} missing\n";
    }
}

// 2. Commandsディレクトリの確認
echo "\n2. Commands directory:\n";
if (is_dir('app/Commands')) {
    $files = scandir('app/Commands');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = "app/Commands/{$file}";
            $size = filesize($fullPath);
            echo "  - {$file} ({$size} bytes)\n";
        }
    }
} else {
    echo "  app/Commands directory not found\n";
}

// 3. sparkファイルの実行テスト（出力をキャプチャ）
echo "\n3. Testing spark list (with output capture):\n";
echo "実行中...\n";

// バッファリングを使用してエラーをキャッチ
ob_start();
$start_memory = memory_get_usage(true);
$start_time = microtime(true);

// 制限時間を設定（10秒でタイムアウト）
set_time_limit(10);

try {
    // 別プロセスでspark listを実行
    $command = 'php spark list 2>&1';
    $output = '';
    $return_code = 0;
    
    exec($command, $output, $return_code);
    
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    
    echo "実行時間: " . number_format($execution_time, 2) . "秒\n";
    echo "リターンコード: {$return_code}\n";
    echo "出力:\n";
    
    if (empty($output)) {
        echo "  (出力なし)\n";
    } else {
        foreach (array_slice($output, 0, 20) as $line) {  // 最初の20行のみ表示
            echo "  {$line}\n";
        }
        if (count($output) > 20) {
            echo "  ... (残り" . (count($output) - 20) . "行省略)\n";
        }
    }
    
} catch (\Throwable $e) {
    echo "エラーが発生しました:\n";
    echo "  " . $e->getMessage() . "\n";
}

ob_end_clean();
echo "\n=== Check Complete ===\n";