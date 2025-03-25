<?php
/**
 * 教職員管理外掛打包腳本
 * 
 * 用於創建發佈包的腳本
 * 
 * @package HK_Faculty
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 */

// 設置基本信息
$plugin_name = 'hk-faculty';
$version = '1.0.0'; // 從主文件讀取版本
$output_dir = './build';

// 確保輸出目錄存在
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// 創建臨時目錄
$temp_dir = $output_dir . '/temp';
if (file_exists($temp_dir)) {
    // 清理已存在的臨時目錄
    removeDirectory($temp_dir);
}
mkdir($temp_dir, 0755, true);

// 需要包含的文件和目錄
$include_paths = [
    'assets',
    'includes',
    'templates',
    'languages',
    'hk-faculty.php',
    'documents/README.md',  // 從documents目錄獲取README
    'LICENSE.txt',
    'uninstall.php'
];

// 需要排除的文件和目錄
$exclude_patterns = [
    '*.git*',
    '*.DS_Store',
    '*.zip',
    '*.bak',
    '*.log',
    'build*',
    'node_modules',
    'vendor',
    'tests',
    'build.php',
    'documents'  // 排除整個documents目錄，但上面單獨包含README
];

// 復制文件到臨時目錄
foreach ($include_paths as $path) {
    if (file_exists($path)) {
        if (is_dir($path)) {
            copyDirectory($path, $temp_dir . '/' . $path, $exclude_patterns);
        } else {
            // 特殊處理README文件
            if ($path === 'documents/README.md') {
                copy($path, $temp_dir . '/README.md');
            } else {
                copy($path, $temp_dir . '/' . $path);
            }
        }
    } else {
        echo "警告: {$path} 不存在，已跳過。\n";
    }
}

// 創建 zip 文件
$zip_filename = $output_dir . "/{$plugin_name}-{$version}.zip";
createZip($temp_dir, $zip_filename);

// 清理臨時目錄
removeDirectory($temp_dir);

echo "打包完成！發佈包位於: {$zip_filename}\n";

/**
 * 復制目錄
 */
function copyDirectory($source, $destination, $exclude_patterns = []) {
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $src = $source . '/' . $file;
            $dst = $destination . '/' . $file;
            
            // 檢查是否應該排除
            $exclude = false;
            foreach ($exclude_patterns as $pattern) {
                if (fnmatch($pattern, $file) || fnmatch($pattern, $src)) {
                    $exclude = true;
                    break;
                }
            }
            
            if (!$exclude) {
                if (is_dir($src)) {
                    copyDirectory($src, $dst, $exclude_patterns);
                } else {
                    copy($src, $dst);
                }
            }
        }
    }
    closedir($dir);
}

/**
 * 刪除目錄
 */
function removeDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

/**
 * 創建 ZIP 文件
 */
function createZip($source_dir, $zip_file) {
    // 獲取絕對路徑
    $source_dir = realpath($source_dir);
    
    // 初始化 ZipArchive 對象
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit("無法創建 {$zip_file}\n");
    }
    
    // 遞歸添加文件
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        // 跳過目錄 (它們會在循環中被添加)
        if ($file->isDir()) {
            continue;
        }
        
        // 獲取相對路徑（用於 zip 文件中的路徑）
        $file_path = $file->getRealPath();
        $relative_path = 'hk-faculty/' . substr($file_path, strlen($source_dir) + 1);
        
        // 添加當前文件到 zip 包
        $zip->addFile($file_path, $relative_path);
    }
    
    // 關閉 zip 文件
    $zip->close();
} 