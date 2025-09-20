<?php
require_once __DIR__ . '/vendor/autoload.php';

\Midtrans\Config::$serverKey = 'SB-Mid-server-SAYi1hM4Ku8MKt78hN4cFxaj'; // ganti dengan Server Key sandbox
\Midtrans\Config::$isProduction = false; // false = sandbox, true = production
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
