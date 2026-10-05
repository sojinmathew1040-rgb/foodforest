<?php
require __DIR__ . '/../admin/includes/db.php';
require __DIR__ . '/../admin/kitchen.php';
// Let's just test format_chalet_label
echo format_chalet_label('mudhouse-stay') . "\n";
echo format_chalet_label('duplex-suite-02') . "\n";
echo format_chalet_label('cottage-hut-02') . "\n";
