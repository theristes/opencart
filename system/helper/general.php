<?php



// @return string
function oc_get_ip(): string {
	$headers = [
		'HTTP_CF_CONNECTING_IP', // CloudFlare
		'HTTP_X_FORWARDED_FOR',  // AWS LB and other reverse-proxies
		'HTTP_X_REAL_IP',
		'HTTP_X_CLIENT_IP',
		'HTTP_CLIENT_IP',
		'HTTP_X_CLUSTER_CLIENT_IP',
	];

	foreach ($headers as $header) {
		if (array_key_exists($header, $_SERVER)) {
			$ip = $_SERVER[$header];

			// This line might or might not be used.
			$ip = trim(explode(',', $ip)[0]);

			return $ip;
		}
	}

	return $_SERVER['REMOTE_ADDR'];
}

/**
 * @param string $string
 *
 * @return int
 */
function oc_strlen(string $string): int {
	return mb_strlen($string);
}

/**
 * @param string $string
 * @param string $needle
 * @param int    $offset
 *
 * @return false|int
 */
function oc_strpos(string $string, string $needle, int $offset = 0) {
	return mb_strpos($string, $needle, $offset);
}

/**
 * @param string $string
 * @param string $needle
 * @param int    $offset
 *
 * @return false|int
 */
function oc_strrpos(string $string, string $needle, int $offset = 0) {
	return mb_strrpos($string, $needle, $offset);
}

/**
 * @param string $string
 * @param int    $offset
 * @param ?int   $length
 * 
 * @return string
 */
function oc_substr(string $string, int $offset, ?int $length = null): string {
	return mb_substr($string, $offset, $length);
}

/**
 * @param string $string
 * 
 * @return string
 */
function oc_strtoupper(string $string): string {
	return mb_strtoupper($string);
}

/**
 * @param string $string
 * 
 * @return string
 */
function oc_strtolower(string $string): string {
	return mb_strtolower($string);
}

/** 
 * Other
 *
 * @param int $length
 *
 * @return string
 */
function oc_token(int $length = 32): string {
	return substr(bin2hex(random_bytes($length)), 0, $length);
}

// Pre PHP8 compatibility
/** 
 * @param string $string
 * @param string $find
 *
 * @return bool
 */
if (!function_exists('str_starts_with')) {
	function str_starts_with(string $string, string $find): bool {
		$substring = substr($string, 0, strlen($find));

		if ($substring === $find) {
			return true;
		} else {
			return false;
		}
	}
}

/** 
 * @param string $string
 * @param string $find
 *
 * @return bool
 */
if (!function_exists('str_ends_with')) {
	function str_ends_with(string $string, string $find): bool {
		return substr($string, -strlen($find)) === $find;
	}
}

/** 
 * @param string $string
 * @param string $find
 *
 * @return bool
 */
if (!function_exists('str_contains')) {
	function str_contains(string $string, string $find): bool {
		return $find === '' || strpos($string, $find) !== false;
	}
}

if (!function_exists('upload_to_bucket')) {
    function upload_to_bucket(string $localPath, string $s3Path): string {
        $s3 = $GLOBALS['s3'];

        if (!file_exists($localPath)) {
            throw new \Exception("Local file does not exist: $localPath");
        }

        $resource = fopen($localPath, 'r');
        $stream = \GuzzleHttp\Psr7\Utils::streamFor($resource);
        $seekableStream = new \GuzzleHttp\Psr7\CachingStream($stream);

        try {
            $s3->putObject([
                'Bucket'        => S3_BUCKET,
                'Key'           => ltrim($s3Path, '/'),
                'Body'          => $seekableStream,
                'ACL'           => 'public-read',
                'ContentType'   => mime_content_type($localPath),
                'ContentSHA256' => 'UNSIGNED-PAYLOAD'
            ]);
        } catch (\Exception $e) {
            error_log("S3 Upload failed: " . $e->getMessage());
            throw $e;
        }

        return bucket_file_url('s3://' . S3_BUCKET . '/' . ltrim($s3Path, '/'));
    }
}

if (!function_exists('is_bucket_file')) {
    function is_bucket_file($path) {
        // Check if path uses S3 stream wrapper
        if (strpos($path, 's3://') === 0) {
            
            $parts = parse_url($path);
            $bucket = $parts['host'];
            $key = ltrim($parts['path'], '/');

            // Use global S3 client
            if (!isset($GLOBALS['s3'])) {
                trigger_error('S3 client is not initialized.', E_USER_WARNING);
                return false;
            }

            try {
                return $GLOBALS['s3']->doesObjectExist($bucket, $key);
            } catch (Exception $e) {
                error_log("S3 check error: " . $e->getMessage());
                return false;
            }
        }

        // Default local file check
        return is_file($path);
    }
}

if (!function_exists('bucket_file_url')) {
    function bucket_file_url($path): string {
        // If S3 path
        if (strpos($path, 's3://') === 0) {
            $parts = parse_url($path);
            $bucket = $parts['host'];
            $key = ltrim($parts['path'], '/');

            // Build full URL using S3_BASE_URL constant
            if (defined('S3_BASE_URL')) {
                return rtrim(S3_BASE_URL, '/') . '/' . $key;
            } else {
                trigger_error('S3_BASE_URL is not defined.', E_USER_WARNING);
                return '';
            }
        }
        // Return raw path if nothing else
        return $path;
    }
}

if (!function_exists('bucket_file_url_not_found')) {
    function bucket_file_url_not_found(string $default = 'no_image.png'): string {
        return bucket_file_url("s3://" . S3_BUCKET . '/' . ltrim($default, '/'));
    }
}

if (!function_exists('delete_from_bucket')) {
    function delete_from_bucket(string $s3Path): bool {
        $s3 = $GLOBALS['s3'];
        return $s3->deleteObject([
            'Bucket' => S3_BUCKET,
            'Key'    => ltrim($s3Path, '/')
        ]);
    }
}


if (!function_exists('resize_image')) {
    function resize_image(string $filename, int $width, int $height, string $default = ''): string {
        if (empty($filename)) return '';

        $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');
        $store_name = STORE_NAME;
        $s3_base_url = defined('S3_BASE_URL') ? rtrim(S3_BASE_URL, '/') . '/' : '';

        $path = dirname($filename);
        $name = basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION));
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $folder = 'cache';
        $resized_file = $store_name . '/' . $folder . '/' . $name . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
        $original_file = $filename;

        // Check original image in bucket
        if (is_bucket_file('s3://' . S3_BUCKET . '/' . $original_file)) {
            // Resize and upload if resized version doesn't exist yet
            if (!is_bucket_file('s3://' . S3_BUCKET . '/' . $resized_file)) {
                resize_and_upload_image($original_file, $resized_file, $width, $height, $default);
            }
        }

        // Handle fallback no_image.png resized version
        $no_image_base = 'no_image.png';
        $no_image_name = basename($no_image_base, '.' . pathinfo($no_image_base, PATHINFO_EXTENSION));
        $no_image_ext = pathinfo($no_image_base, PATHINFO_EXTENSION);
        $no_image_resized = 'cache/' . $no_image_name . '-' . (int)$width . 'x' . (int)$height . '.' . $no_image_ext;

        $local_no_image = DIR_IMAGE . $no_image_base;
        $local_no_image_resized = DIR_IMAGE . $no_image_resized;

        if (!is_bucket_file('s3://' . S3_BUCKET . '/' . $no_image_resized) && file_exists($local_no_image)) {
            try {
                $image = new \Opencart\System\Library\Image($local_no_image);
                $image->resize($width, $height, $default);
                $image->save($local_no_image_resized);
                upload_to_bucket($local_no_image_resized, $no_image_resized);
            } catch (\Exception $e) {
                error_log('Failed to resize no_image fallback: ' . $e->getMessage());
            }
        }

        // Priority paths to return: resized image, original, resized no_image, fallback
        $try_paths = [$resized_file, $filename, $no_image_resized];

        foreach ($try_paths as $try) {
            if (is_bucket_file('s3://' . S3_BUCKET . '/' . $try)) {
                return $s3_base_url . $try;
            }
        }

        return $s3_base_url . $no_image_base;
    }
}

if (!function_exists('fetch_image')) {
    function fetch_image(string $filename): string {
        if (empty($filename)) return '';

        $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');

        $s3_base_url = defined('S3_BASE_URL') ? rtrim(S3_BASE_URL, '/') . '/' : '';

        return $s3_base_url . ltrim($filename, '/');
    }
}

if (!function_exists('get_geo_code_key')) {
    function get_geo_code_key(): string {
       return "AAALOHAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALAAALA";
    }
}




function resize_and_upload_image(string $source, string $destPath, int $width, int $height, string $default = '') {
    $source_path = DIR_IMAGE . ltrim($source, '/');
    $dest_path = DIR_IMAGE . ltrim($destPath, '/');
    $s3_path = ltrim($destPath, '/');

    if (!file_exists($source_path)) {
        error_log("Source image not found: $source_path");
        return;
    }

    try {
        $image = new \Opencart\System\Library\Image($source_path);
        $image->resize($width, $height, $default);
        $image->save($dest_path);
        upload_to_bucket($dest_path, $s3_path);
    } catch (\Exception $e) {
        error_log('Failed to resize and upload image: ' . $e->getMessage());
    }
}


