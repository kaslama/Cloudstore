<?php
define('ENCRYPTION_KEY', '12345678901234567890123456789012'); // 32 bytes = AES-256
define('ENCRYPTION_IV', substr(hash('sha256', 'my_custom_iv'), 0, 16)); // 16 bytes
define('ENCRYPTION_METHOD', 'AES-256-CBC');
