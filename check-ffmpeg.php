<?php
// Delete this file after checking!
echo "<h3>FFmpeg Version:</h3>";
echo "<pre>";
$ffmpegPath = '/usr/bin/ffmpeg'; // Adjust this if different
echo shell_exec("$ffmpegPath -version 2>&1");
echo "</pre>";

echo "<h3>AAC Codec Support:</h3>";
echo "<pre>";
echo shell_exec("$ffmpegPath -codecs 2>&1 | grep aac");
echo "</pre>";

echo "<h3>FFmpeg Path Test:</h3>";
echo "<pre>";
echo shell_exec("which ffmpeg 2>&1");
echo "</pre>";
?>