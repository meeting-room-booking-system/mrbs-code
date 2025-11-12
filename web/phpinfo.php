<?php
// Show environment variables
echo "<h2>Environment Variables</h2>";
echo "<pre>";
print_r([
    'DB_HOST' => getenv('DB_HOST'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'ADMIN_EMAIL' => getenv('ADMIN_EMAIL'),
    'PORT' => getenv('PORT')
]);
echo "</pre>";

// Show PHP configuration
phpinfo();
?>
