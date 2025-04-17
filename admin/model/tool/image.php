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
	 */public function resize(string $filename, int $width, int $height, string $default = ''): string {
        $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');

        $s3_base_url = S3_BASE_URL;
        $s3_cache_path = 'images/';

        $image_old = $filename;
        $image_new = $s3_cache_path . oc_substr($filename, 0, oc_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . pathinfo($filename, PATHINFO_EXTENSION);


		if ($this->is_bucket_file($image_new)) {
            return $s3_base_url . $image_new;
        }

        $image_info = @getimagesize(DIR_IMAGE . $image_old);

        if (!$image_info) {
            return $s3_base_url . $image_old;
        }

        [$width_orig, $height_orig, $image_type] = $image_info;

        // Validate image type
        if (!in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            return $s3_base_url . $image_old;
        }

        // Resize image if needed
        if ($width_orig != $width || $height_orig != $height) {
            $image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
            $image->resize($width, $height, $default);
            $image->save(DIR_IMAGE . $image_new);
        } else {
            copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
        }

        $this->upload_to_bucket(DIR_IMAGE . $image_new, $image_new);

        return $s3_base_url . $image_new;
    }
}