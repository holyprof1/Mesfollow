<?php
// DIAGNOSTIC FILE - Upload this to your server and access it via browser
// Example: https://yoursite.com/diagnostic.php

header('Content-Type: text/plain; charset=utf-8');

echo "=== SERVER DIAGNOSTICS ===\n\n";

// 1. FFmpeg Path and Version
echo "1. FFMPEG INFORMATION:\n";
echo "-------------------\n";

$ffmpegPaths = [
    '/usr/bin/ffmpeg',
    '/usr/local/bin/ffmpeg',
    'ffmpeg'
];

foreach ($ffmpegPaths as $path) {
    echo "Trying: $path\n";
    $version = shell_exec("$path -version 2>&1");
    if ($version && stripos($version, 'ffmpeg') !== false) {
        echo "✓ FOUND at: $path\n";
        echo substr($version, 0, 500) . "\n\n";
        $workingFfmpeg = $path;
        break;
    } else {
        echo "✗ Not found or not working\n\n";
    }
}

if (!isset($workingFfmpeg)) {
    echo "❌ FFmpeg not found in common locations\n\n";
    $workingFfmpeg = 'ffmpeg'; // try default
}

// 2. FFmpeg Codecs
echo "\n2. AVAILABLE CODECS:\n";
echo "-------------------\n";
$codecs = shell_exec("$workingFfmpeg -codecs 2>&1");
echo "Looking for libx264: " . (stripos($codecs, 'libx264') !== false ? '✓ YES' : '✗ NO') . "\n";
echo "Looking for libmp3lame: " . (stripos($codecs, 'libmp3lame') !== false ? '✓ YES' : '✗ NO') . "\n";
echo "Looking for aac: " . (stripos($codecs, ' aac ') !== false ? '✓ YES' : '✗ NO') . "\n\n";

// 3. Test MOV Conversion
echo "\n3. TEST MOV CONVERSION:\n";
echo "-------------------\n";

// Create a test video (1 second black screen)
$testDir = __DIR__ . '/test_diagnostic';
if (!file_exists($testDir)) {
    mkdir($testDir, 0755, true);
}

$testInput = $testDir . '/test_input.mp4';
$testMov = $testDir . '/test.mov';
$testConverted = $testDir . '/test_converted.mp4';
$testThumb = $testDir . '/test_thumb.jpg';

// Create a simple test video
echo "Creating test video...\n";
$createTest = shell_exec("$workingFfmpeg -f lavfi -i color=black:s=320x240:d=1 -f lavfi -i anullsrc -t 1 -y $testInput 2>&1");

if (file_exists($testInput)) {
    echo "✓ Test input created\n";
    
    // Convert to MOV
    echo "\nConverting to MOV...\n";
    $toMov = shell_exec("$workingFfmpeg -i $testInput -c copy -y $testMov 2>&1");
    
    if (file_exists($testMov)) {
        echo "✓ MOV created\n";
        
        // Now try the EXACT commands from your code
        echo "\n--- Testing Command 1: -c:v libx264 -c:a aac ---\n";
        $cmd1 = shell_exec("$workingFfmpeg -i $testMov -c:v libx264 -c:a aac -strict -2 $testConverted 2>&1");
        echo $cmd1 . "\n";
        echo "Result: " . (file_exists($testConverted) && filesize($testConverted) > 1000 ? '✓ SUCCESS' : '✗ FAILED') . "\n";
        
        if (file_exists($testConverted)) unlink($testConverted);
        
        echo "\n--- Testing Command 2: -c:v libx264 -c:a libmp3lame ---\n";
        $cmd2 = shell_exec("$workingFfmpeg -i $testMov -c:v libx264 -c:a libmp3lame -q:a 2 -movflags +faststart $testConverted 2>&1");
        echo $cmd2 . "\n";
        echo "Result: " . (file_exists($testConverted) && filesize($testConverted) > 1000 ? '✓ SUCCESS' : '✗ FAILED') . "\n";
        
        // Test thumbnail generation
        echo "\n--- Testing Thumbnail Generation ---\n";
        $thumbCmd = shell_exec("$workingFfmpeg -i $testConverted -ss 00:00:01.000 -vframes 1 -y $testThumb 2>&1");
        echo $thumbCmd . "\n";
        echo "Result: " . (file_exists($testThumb) && filesize($testThumb) > 1000 ? '✓ SUCCESS' : '✗ FAILED') . "\n";
        
    } else {
        echo "✗ Failed to create MOV\n";
        echo $toMov . "\n";
    }
} else {
    echo "✗ Failed to create test input\n";
    echo $createTest . "\n";
}

// 4. Directory Permissions
echo "\n\n4. DIRECTORY PERMISSIONS:\n";
echo "-------------------\n";

$uploadDirs = [
    '../uploads',
    '../uploads/files',
    '../uploads/xvideos',
    '../uploads/videos'
];

foreach ($uploadDirs as $dir) {
    $fullPath = realpath($dir);
    if ($fullPath === false) {
        echo "$dir: ✗ Does not exist\n";
    } else {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        $writable = is_writable($fullPath);
        echo "$dir: " . ($writable ? '✓' : '✗') . " Writable (Permissions: $perms)\n";
    }
}

// 5. PHP Configuration
echo "\n\n5. PHP CONFIGURATION:\n";
echo "-------------------\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "exec() enabled: " . (function_exists('exec') ? '✓ YES' : '✗ NO') . "\n";
echo "shell_exec() enabled: " . (function_exists('shell_exec') ? '✓ YES' : '✗ NO') . "\n";
echo "Max execution time: " . ini_get('max_execution_time') . " seconds\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post max size: " . ini_get('post_max_size') . "\n";

// 6. Test actual upload folder write
echo "\n\n6. UPLOAD FOLDER WRITE TEST:\n";
echo "-------------------\n";

$testFile = '../uploads/files/test_write_' . time() . '.txt';
if (@file_put_contents($testFile, 'test')) {
    echo "✓ Can write to ../uploads/files/\n";
    @unlink($testFile);
} else {
    echo "✗ Cannot write to ../uploads/files/\n";
}

// Clean up test files
echo "\n\nCleaning up test files...\n";
if (file_exists($testInput)) unlink($testInput);
if (file_exists($testMov)) unlink($testMov);
if (file_exists($testConverted)) unlink($testConverted);
if (file_exists($testThumb)) unlink($testThumb);
if (file_exists($testDir)) @rmdir($testDir);

echo "\n=== END DIAGNOSTICS ===\n";
echo "\nCopy ALL of this output and send it back to me.\n";
?>