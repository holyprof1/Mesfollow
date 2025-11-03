<?php
// find_fees.php — search for anything that might flip fees_status
ini_set('max_execution_time', 0);
$root = __DIR__;

$needles = [
  'fees_status = 1',
  "fees_status=1",
  "fees_status = '1'",
  "fees_status='1'",
  'fees_status',
  'setSubscriptionPayments',
  'updateSubscriptionPayments',
  'becomeCreator',
  'sub_week_status',
  'sub_month_status',
  'sub_year_status',
];

$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

function showHit($rel, $lineNo, $line, $context) {
  echo '<pre style="font-family:monospace;white-space:pre-wrap;border-bottom:1px solid #ddd;padding:8px;margin:0">';
  echo htmlspecialchars("$rel:$lineNo: $line");
  if ($context) {
    echo "\n-- context --\n";
    echo htmlspecialchars($context);
  }
  echo "</pre>\n";
}

foreach ($it as $file) {
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  if (!in_array($ext, ['php','js'])) continue;

  $rel = substr($file->getPathname(), strlen($root) + 1);
  $contents = @file_get_contents($file->getPathname());
  if ($contents === false) continue;

  $lines = explode("\n", $contents);
  foreach ($lines as $i => $line) {
    foreach ($needles as $needle) {
      if (stripos($line, $needle) !== false) {
        // context: 2 lines before and after
        $start = max(0, $i-2);
        $end   = min(count($lines)-1, $i+2);
        $ctx   = implode("\n", array_slice($lines, $start, $end - $start + 1));
        showHit($rel, $i+1, rtrim($line), $ctx);
        break;
      }
    }
  }
}
