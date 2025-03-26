use Aws\S3\S3Client;

function initializeS3Wrapper() {
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'your-region', // Replace with your AWS region
        'credentials' => [
            'key'    => 'your-access-key', // Replace with your AWS access key
            'secret' => 'your-secret-key', // Replace with your AWS secret key
        ],
    ]);

    $s3->registerStreamWrapper();
}
