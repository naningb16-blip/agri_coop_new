<?php
// Try env vars first (set via Render/Docker), fall back to Aiven defaults
define('DB_HOST',   getenv('DB_HOST')   ?: 'mysql-1e56207f-dsuanzon2004-8667.e.aivencloud.com');
define('DB_PORT',   (int)(getenv('DB_PORT') ?: 15304));
define('DB_USER',   getenv('DB_USER')   ?: 'avnadmin');
define('DB_PASS',   getenv('DB_PASS')   ?: 'AVNS_vjJM9OSnzunmQELaqjg');
define('DB_NAME',   getenv('DB_NAME')   ?: 'defaultdb');
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '/var/www/html/config/aiven-ca.pem');
define('BASE_URL',  getenv('BASE_URL')  ?: 'https://agri-coop.onrender.com');
define('APP_NAME',  'FARCO');
