<?php
// image_proxy.php

// Base URL for Firebase Storage
$firebase_base_url = 'https://storage.googleapis.com/myfarmax.appspot.com/';

// Get the 'end' parameter from the query string
$end = isset($_GET['end']) ? $_GET['end'] : '';

// Validate the 'end' parameter
if (empty($end)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing "end" parameter');
}

// Construct the full external image URL
$image_url = $firebase_base_url . $end;

// Fetch the image from the external URL
$image_data = @file_get_contents($image_url);

if ($image_data === false) {
    header('HTTP/1.1 404 Not Found');
    exit('Image not found');
}

// Set the appropriate content type header
$image_info = getimagesizefromstring($image_data);
if ($image_info === false) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Invalid image');
}

header('Content-Type: ' . $image_info['mime']);
echo $image_data;