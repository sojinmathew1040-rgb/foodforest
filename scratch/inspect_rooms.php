<?php
$lines = file(__DIR__ . '/../admin/edit_section.php');
foreach ($lines as $num => $line) {
    if (strpos($line, 'rooms_settings') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
