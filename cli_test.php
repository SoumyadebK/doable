
<?php
date_default_timezone_set('Asia/Kolkata');

$logFile = 'C:/xampp/htdocs/doable/cli_test.log';

file_put_contents(
    $logFile,
    "PHP CLI executed successfully at " . date('Y-m-d H:i:s') . PHP_EOL,
    FILE_APPEND
);

echo "PHP CLI test successful!";
