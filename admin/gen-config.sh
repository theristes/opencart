#!/bin/bash

# Parameters
STORE_NAME="$1"
AWS_ACCESS_KEY_ID="$2"
AWS_SECRET_ACCESS_KEY="$3"
AWS_S3_BUCKET_NAME="$4"
AWS_REGION="$5"
DOMAIN_NAME="$6"
DB_HOST="$7"
DB_USER="$8"
DB_PASSWORD="$9"

if [ -z "$STORE_NAME" ] || [ -z "$AWS_ACCESS_KEY_ID" ] || [ -z "$AWS_SECRET_ACCESS_KEY" ] || [ -z "$AWS_S3_BUCKET_NAME" ] || [ -z "$AWS_REGION" ] || [ -z "$DOMAIN_NAME" ] || [ -z "$DB_HOST" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASSWORD" ]; then
  echo "Usage: $0 <STORE_NAME> <AWS_ACCESS_KEY_ID> <AWS_SECRET_ACCESS_KEY> <AWS_S3_BUCKET_NAME> <AWS_REGION> <DOMAIN_NAME> <DB_HOST> <DB_USER> <DB_PASSWORD>"
  exit 1
fi

# Generate config.php
cat <<EOF > ./config.php
<?php
// Load Composer Dependencies
require_once '/var/www/html/$STORE_NAME/vendor/autoload.php';

use Aws\S3\S3Client;

// Initialize S3 Client
\$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => '$AWS_REGION', // Change to your AWS region
    'credentials' => [
        'key'    => '$AWS_ACCESS_KEY_ID',  // Replace with your AWS Access Key
        'secret' => '$AWS_SECRET_ACCESS_KEY',  // Replace with your AWS Secret Key
    ],
]);

// Register the S3 Stream Wrapper
\$s3->registerStreamWrapper();

// APPLICATION
define('APPLICATION', 'Admin');

// HTTP
define('HTTP_SERVER', '$DOMAIN_NAME/$STORE_NAME/admin');
define('HTTP_CATALOG', '$DOMAIN_NAME/$STORE_NAME/');

// DIR
define('DIR_OPENCART', '/var/www/html/$STORE_NAME/');
define('DIR_APPLICATION', DIR_OPENCART . 'admin/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');
define('DIR_IMAGE', 's3://$AWS_S3_BUCKET_NAME/image/');
define('DIR_STORAGE', 's3://$AWS_S3_BUCKET_NAME/$STORE_NAME/storage/');
define('DIR_CATALOG', DIR_OPENCART . 'catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', '$DB_HOST');
define('DB_USERNAME', '$DB_USER');
define('DB_PASSWORD', '$DB_PASSWORD');
define('DB_DATABASE', '$STORE_NAME');
define('DB_PREFIX', 'oc_');
define('DB_PORT', '3306');

//
define('OPENCART_SERVER', 'https://www.opencart.com/');
EOF

chmod 600 ./config.php  # Secure the file

echo "admin config.php has been generated successfully."



