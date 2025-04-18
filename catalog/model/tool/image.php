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
	
	
	public function resize(string $filename, int $width, int $height, string $default = ''): string {
        $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');
    
        $s3_base_url = S3_BASE_URL;
    
        // Original image path (relative to S3 or local DIR_IMAGE)
        $image_old = $filename;
    
        // Generate new filename in the SAME path as the original
        $path = dirname($filename);
        $name = basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION));
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
        $image_new = $path . '/' . $name . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
    
        // Check if resized version already exists in S3
        if (is_bucket_file($image_new)) {
            return $s3_base_url . $image_new;
        }
    
        // Try to get original image dimensions
        $image_info = @getimagesize(DIR_IMAGE . $image_old);
        if (!$image_info) {
            return $s3_base_url . $image_old;
        }
    
        [$width_orig, $height_orig, $image_type] = $image_info;
    
        // Validate image type
        if (!in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            return $s3_base_url . $image_old;
        }
    
        // Resize if dimensions differ
        if ($width_orig != $width || $height_orig != $height) {
            $image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
            $image->resize($width, $height, $default);
            $image->save(DIR_IMAGE . $image_new);
        } else {
            copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
        }
    
        // Upload to S3
        upload_to_bucket(DIR_IMAGE . $image_new, $image_new);
    
        return $s3_base_url . $image_new;
    }
    
	
}
