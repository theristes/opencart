<?php
namespace Opencart\Admin\Model\Tool;
/**
 * Class Image
 *
 * Can be loaded using $this->load->model('tool/image');
 *
 * @package Opencart\Admin\Model\Tool
 */
class Image extends \Opencart\System\Engine\Model {
	/**
	 * Resize
	 *
	 * @param string $filename
	 * @param int    $width
	 * @param int    $height
	 *
	 * @throws \Exception
	 *
	 * @return string
	 *
	 * @example
	 *
	 * $this->load->model('tool/image');
	 *
	 * $placeholder = $this->model_tool_image->resize($filename, $width, $height);
	 */
    public function resize(string $filename, int $width, int $height, string $default = ''): string {

        $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');
        
        $store_name = STORE_NAME;
        
        $path = dirname($filename);
        
        $name = basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION));
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $image_relative = $path . '/' . $name . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
        
        $image_new = $store_name . '/' . ltrim($image_relative, '/');
                
        if (is_bucket_file($image_new)) {
            return $s3_base_url . $image_new;
        }
    
        $image_info = @getimagesize(DIR_IMAGE . $image_old);
        if (!$image_info) {
            return $s3_base_url . $image_old;
        }
    
        [$width_orig, $height_orig, $image_type] = $image_info;
    
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
    
        upload_to_bucket(DIR_IMAGE . $image_relative, $image_new);
    
        return $s3_base_url . $image_new;
    }
    
}