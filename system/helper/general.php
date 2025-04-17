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


if (!function_exists('is_bucket_file')) {
    function is_bucket_file($path) {
        // Check if path uses S3 stream wrapper
        if (strpos($path, 's3://') === 0) {
            // Parse the path to get the bucket and key
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
    function bucket_file_url($path) {
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


