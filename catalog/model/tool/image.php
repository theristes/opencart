<?php
namespace Opencart\Catalog\Model\Tool;
/**
 * Class Image
 *
 * Can be called using $this->load->model('tool/image');
 *
 * @package Opencart\Catalog\Model\Tool
 */
class Image extends \Opencart\System\Engine\Model {
	/**
	 * Resize
	 *
	 * @param string $filename
	 * @param int    $width
	 * @param int    $height
	 * @param string $default
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */

	 private function s3ImageExists(string $key): bool {
		$s3 = $GLOBALS['s3'];
		return $s3->doesObjectExist(S3_BUCKET, $key);
	}
	
	private function uploadToS3(string $localPath, string $s3Path): void {
		$s3 = $GLOBALS['s3'];
		$s3->putObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $s3Path,
			'Body'   => fopen($localPath, 'r'),
			'ACL'    => 'public-read',
			'ContentType' => mime_content_type($localPath),
			'ContentSHA256' => 'UNSIGNED-PAYLOAD' 
		]);
	}
	

	 public function resize(string $filename, int $width, int $height, string $default = ''): string {
		$filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');
	
		$s3_bucket = S3_BUCKET;
		$s3_base_url = S3_BASE_URL;
		$s3_cache_path = 'cache/';
	
		// Construct S3 image paths
		$image_old = $filename;
		$image_new = $s3_cache_path . oc_substr($filename, 0, oc_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . pathinfo($filename, PATHINFO_EXTENSION);
		
		// Check if the resized image already exists in S3
		if ($this->s3ImageExists($image_new)) {
			return $s3_base_url . $image_new;
		}
	
		// Resize the image
		[$width_orig, $height_orig, $image_type] = getimagesize(DIR_IMAGE . $image_old);
		
		if (!in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
			return $s3_base_url . $image_old;
		}
	
		if ($width_orig != $width || $height_orig != $height) {
			$image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
			$image->resize($width, $height, $default);
			$image->save(DIR_IMAGE . $image_new);
		} else {
			copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
		}
	
		// Upload the resized image to S3
		$this->uploadToS3(DIR_IMAGE . $image_new, $image_new);
	
		return $s3_base_url . $image_new;
	}
}
