<?php
// Get cookies from curl login
$cookieFile = __DIR__ . '/cookie.txt';
// Login via curl to ensure fresh session
exec('curl.exe -c "' . $cookieFile . '" -d "username=admin&password=admin123" "http://localhost/foodforest/admin/login.php" -L -s -o /dev/null');

$lines = file($cookieFile);
$phpsessid = '';
foreach ($lines as $line) {
    if (strpos($line, 'PHPSESSID') !== false) {
        $parts = preg_split('/\s+/', trim($line));
        $phpsessid = end($parts);
        break;
    }
}
echo "PHPSESSID: " . $phpsessid . "\n";
file_put_contents(__DIR__ . '/test_sess.txt', $phpsessid);
