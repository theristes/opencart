<?php
/**
 * Validate Length
 *
 * @param string $string
 * @param int    $minimum
 * @param int    $maximum
 *
 * @return bool
 */
function oc_validate_length(string $string, int $minimum, int $maximum): bool {
	return strlen(trim($string)) >= $minimum && strlen(trim($string)) <= $maximum;
}

/**
 * Validate Email
 *
 * @param string $email
 *
 * @return bool
 */
function oc_validate_email(string $email): bool {
	if (oc_strrpos($email, '@') === false) {
		return false;
	}

	$local = oc_substr($email, 0, oc_strrpos($email, '@'));

	$domain = oc_substr($email, (oc_strrpos($email, '@') + 1));

	$email = $local . '@' . idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);

	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate IP
 *
 * @param string $ip
 *
 * @return bool
 */
function oc_validate_ip(string $ip): bool {
	return filter_var($ip, FILTER_VALIDATE_IP);
}

/**
 * Validate URL
 *
 * @param string $filename
 *
 * @return bool
 */
function oc_validate_filename(string $filename): bool {
	return preg_match('/[^a-zA-Z0-9\/._-]|[\p{Cyrillic}]+/u', $filename);
}

/**
 * Validate URL
 *
 * @param string $url
 *
 * @return bool
 */
function oc_validate_url(string $url): bool {
	return filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Validate SEO URL
 *
 * @param string $keyword
 *
 * @return bool
 */
function oc_validate_seo_url(string $keyword): bool {
	return !preg_match('/[^\p{Latin}\p{Cyrillic}\p{Greek}0-9\/\.\-\_]+/u', $keyword);
}
