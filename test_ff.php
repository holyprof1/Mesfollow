<?php
echo "<pre>";
echo "disable_functions = ".ini_get('disable_functions')."\n\n";
echo shell_exec('/usr/bin/ffmpeg -version 2>&1');
