<?php
namespace Opencart\Admin\Controller\Common;
/**
 * Class File Manager
 *
 * Can be loaded using $this->load->controller('common/filemanager');
 *
 * @package Opencart\Admin\Controller\Common
 */
class FileManager extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('common/filemanager');

		$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

		$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

		// Return the target ID for the file manager to set the value
		if (isset($this->request->get['target'])) {
			$data['target'] = $this->request->get['target'];
		} else {
			$data['target'] = '';
		}

		// Return the thumbnail for the file manager to show a thumbnail
		if (isset($this->request->get['thumb'])) {
			$data['thumb'] = $this->request->get['thumb'];
		} else {
			$data['thumb'] = '';
		}

		if (isset($this->request->get['ckeditor'])) {
			$data['ckeditor'] = $this->request->get['ckeditor'];
		} else {
			$data['ckeditor'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('common/filemanager', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */

	 public function list(): void {
		$this->load->language('common/filemanager');
	
		// Base directory in S3 (e.g. 'mystore/')
		$s3BasePath = STORE_NAME . '/';
		
		// Current directory (relative to base)
		if (isset($this->request->get['directory'])) {
			$currentDir = rtrim(html_entity_decode($this->request->get['directory'], ENT_QUOTES, 'UTF-8'), '/') . '/';
		} else {
			$currentDir = '';
		}
	
		// Full S3 path for listing
		$fullS3Path = $s3BasePath . $currentDir;
	
		// Filter and pagination
		if (isset($this->request->get['filter_name'])) {
			$filter_name = basename(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_name = '';
		}
	
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
	
		$allowed = [];
		foreach (explode("\r\n", $this->config->get('config_file_ext_allowed')) as $key => $extension) {
			$allowed[] = '.' . \strtolower($extension);
			$allowed[] = '.' . \strtoupper($extension);
		}
	
		$directories = [];
		$files = [];
	
		try {
			$s3 = $GLOBALS['s3'];
			
			// List objects in the current directory
			$objects = $s3->listObjectsV2([
				'Bucket' => S3_BUCKET,
				'Prefix' => $fullS3Path,
				'Delimiter' => '/'
			]);
	
			// Process directories
			if (!empty($objects['CommonPrefixes'])) {
				foreach ($objects['CommonPrefixes'] as $prefix) {
					$dirPath = rtrim($prefix['Prefix'], '/');
					$dirName = substr($dirPath, strrpos($dirPath, '/') + 1);
					
					if ($filter_name && !str_starts_with($dirName, $filter_name)) {
						continue;
					}
					
					$relativePath = substr($dirPath, strlen($s3BasePath));
					$directories[] = [
						'name' => $dirName,
						'path' => $relativePath . '/'
					];
				}
			}
	
			// Process files
			if (!empty($objects['Contents'])) {
				foreach ($objects['Contents'] as $object) {
					// Skip the directory itself and subdirectories
					if ($object['Key'] === $fullS3Path || substr($object['Key'], -1) === '/') {
						continue;
					}
					
					$fileName = basename($object['Key']);
					$relativePath = substr($object['Key'], strlen($s3BasePath));
					
					if ($filter_name && !str_starts_with($fileName, $filter_name)) {
						continue;
					}
					
					$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
					if (in_array('.' . $ext, $allowed)) {
						$files[] = [
							'name' => $fileName,
							'path' => $relativePath,
							's3_path' => $object['Key'], // Full S3 path for URL generation
							'size' => $object['Size'],
							'last_modified' => $object['LastModified']
						];
					}
				}
			}
	
			$total = count($directories) + count($files);
			$limit = 16;
			$start = ($page - 1) * $limit;
	
			$data['directories'] = [];
			$data['images'] = [];
	
			$this->load->model('tool/image');
	
			$url = '';
			if (isset($this->request->get['target'])) {
				$url .= '&target=' . $this->request->get['target'];
			}
			if (isset($this->request->get['thumb'])) {
				$url .= '&thumb=' . $this->request->get['thumb'];
			}
			if (isset($this->request->get['ckeditor'])) {
				$url .= '&ckeditor=' . $this->request->get['ckeditor'];
			}
	
			// Prepare directories data
			foreach (array_slice($directories, $start, $limit) as $dir) {
				$data['directories'][] = [
					'name' => $dir['name'],
					'path' => $dir['path'],
					'href' => $this->url->link('common/filemanager.list', 'user_token=' . $this->session->data['user_token'] . '&directory=' . urlencode($dir['path']) . $url)
				];
			}
	
			// Prepare files data
			$filesLimit = $limit - count($data['directories']);
			foreach (array_slice($files, $start, $filesLimit) as $file) {
				$s3FullPath = "s3://" . S3_BUCKET . '/' . $file['s3_path'];
				$imagePathForThumbnail = $file['path']; // Relative path for thumbnails
				
				$data['images'][] = [
					'name'  => $file['name'],
					'path'  => $file['path'],
					'href'  => bucket_file_url($s3FullPath),
					'thumb' => $this->model_tool_image->resize($imagePathForThumbnail, $this->config->get('config_image_default_width'), $this->config->get('config_image_default_height'))
				];
			}
	
		} catch (\Exception $e) {
			error_log("S3 List Error: " . $e->getMessage());
			$data['directories'] = [];
			$data['images'] = [];
			$total = 0;
		}
	
		// Set current directory for display
		$data['directory'] = $currentDir;
		$data['filter_name'] = $filter_name;
	
		// Parent directory logic
		$url = '';
		if (!empty($currentDir)) {
			$parts = explode('/', rtrim($currentDir, '/'));
			array_pop($parts);
			if (!empty($parts)) {
				$url .= '&directory=' . urlencode(implode('/', $parts) . '/');
			}
		}
		// Add other URL parameters...
		if (isset($this->request->get['target'])) {
			$url .= '&target=' . $this->request->get['target'];
		}
		if (isset($this->request->get['thumb'])) {
			$url .= '&thumb=' . $this->request->get['thumb'];
		}
		if (isset($this->request->get['ckeditor'])) {
			$url .= '&ckeditor=' . $this->request->get['ckeditor'];
		}
	
		$data['parent'] = !empty($url) ? $this->url->link('common/filemanager.list', 'user_token=' . $this->session->data['user_token'] . $url) : '';
	
		// Refresh URL
		$refreshUrl = '';
		if (!empty($currentDir)) {
			$refreshUrl .= '&directory=' . urlencode($currentDir);
		}
		if (isset($this->request->get['filter_name'])) {
			$refreshUrl .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}
		// Add other parameters...
	
		$data['refresh'] = $this->url->link('common/filemanager.list', 'user_token=' . $this->session->data['user_token'] . $refreshUrl);
	
		// Pagination
		$paginationUrl = $refreshUrl; // Reuse refresh URL
		if (isset($this->request->get['page'])) {
			$paginationUrl .= '&page=' . $this->request->get['page'];
		}
	
		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('common/filemanager.list', 'user_token=' . $this->session->data['user_token'] . $paginationUrl . '&page={page}')
		]);
	
		$this->response->setOutput($this->load->view('common/filemanager_list', $data));
	}


	public function upload(): void {
		$this->load->language('common/filemanager');
		$json = [];
	
		// Check user has permission
		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}
	
		if (!$json) {
			$files = [];
	
			if (!empty($this->request->files['file']['name'])) {
				if (is_array($this->request->files['file']['name'])) {
					foreach (array_keys($this->request->files['file']['name']) as $key) {
						$files[] = [
							'name'     => $this->request->files['file']['name'][$key],
							'type'     => $this->request->files['file']['type'][$key],
							'tmp_name' => $this->request->files['file']['tmp_name'][$key],
							'error'    => $this->request->files['file']['error'][$key],
							'size'     => $this->request->files['file']['size'][$key]
						];
					}
				} else {
					$files[] = $this->request->files['file'];
				}
			}
	
			foreach ($files as $file) {
				// Check for upload errors first
				if ($file['error'] != UPLOAD_ERR_OK) {
					$error_message = $this->getUploadErrorMessage($file['error']);
					$json['error'] = $error_message;
					break;
				}
	
				if (!is_file($file['tmp_name'])) {
					$json['error'] = $this->language->get('error_upload');
					break;
				}
	
				$filename = preg_replace('/[\/\\\?%*:|"<>]/', '', basename(html_entity_decode($file['name'], ENT_QUOTES, 'UTF-8')));
	
				if (!oc_validate_length($filename, 4, 255)) {
					$json['error'] = $this->language->get('error_filename');
					break;
				}
	
				// Check extension
				$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				$allowedExts = explode("\r\n", strtolower($this->config->get('config_file_ext_allowed')));
	
				if (!in_array($ext, $allowedExts)) {
					$json['error'] = $this->language->get('error_file_type');
					break;
				}
	
				// Check MIME type
				$allowedMimes = explode("\r\n", $this->config->get('config_file_mime_allowed'));
				if (!in_array($file['type'], $allowedMimes)) {
					$json['error'] = $this->language->get('error_file_type');
					break;
				}
	
				try {
					$directory = isset($this->request->get['directory']) ? trim($this->request->get['directory'], '/') : '';
					$s3Path = $directory . '/' . STORE_NAME . '/' . $filename;
				
					upload_to_bucket($file['tmp_name'], $s3Path);
					
				} catch (\Exception $e) {
					$json['error'] = $this->language->get('error_upload') . ': ' . $e->getMessage();
					break;
				}
			}
		}
	
		if (!$json) {
			$json['success'] = $this->language->get('text_uploaded');
		}
	
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * Get human-readable upload error message
	 */
	protected function getUploadErrorMessage(int $error_code): string {
		$this->load->language('common/filemanager');
		
		$php_errors = [
			UPLOAD_ERR_INI_SIZE   => $this->language->get('error_upload_1'),
			UPLOAD_ERR_FORM_SIZE  => $this->language->get('error_upload_2'),
			UPLOAD_ERR_PARTIAL    => $this->language->get('error_upload_3'),
			UPLOAD_ERR_NO_FILE    => $this->language->get('error_upload_4'),
			UPLOAD_ERR_NO_TMP_DIR => $this->language->get('error_upload_6'),
			UPLOAD_ERR_CANT_WRITE => $this->language->get('error_upload_7'),
			UPLOAD_ERR_EXTENSION  => $this->language->get('error_upload_8'),
		];
	
		return $php_errors[$error_code] ?? $this->language->get('error_upload');
	}



	/**
	 * Folder
	 *
	 * @return void
	 */
	public function folder(): void {
		$this->load->language('common/filemanager');

		$json = [];

		$base = DIR_IMAGE . STORE_NAME . '/';

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Make sure we have the correct directory
		if (isset($this->request->get['directory'])) {
			$directory = $base . html_entity_decode($this->request->get['directory'], ENT_QUOTES, 'UTF-8') . '/';
		} else {
			$directory = $base;
		}

		if (isset($this->request->post['folder'])) {
			$folder = preg_replace('/[\/\\\?%*&:|"<>]/', '', basename(html_entity_decode($this->request->post['folder'], ENT_QUOTES, 'UTF-8')));
		} else {
			$folder = '';
		}

		if (!is_dir($directory) || substr(str_replace('\\', '/', realpath($directory)) . '/', 0, strlen($base)) != $base) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!oc_validate_length($folder, 3, 128)) {
			$json['error'] = $this->language->get('error_folder');
		} elseif (is_dir($directory . $folder)) {
			$json['error'] = $this->language->get('error_exists');
		}

		if (!$json) {
			mkdir($directory . $folder, 0777);
			chmod($directory . $folder, 0777);
			@touch($directory . $folder . '/' . 'index.html');

			$json['success'] = $this->language->get('text_directory');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('common/filemanager');

		$json = [];

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'common/filemanager')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['path'])) {
			$paths = $this->request->post['path'];
		} else {
			$paths = [];
		}

		if (!$json) {
			foreach ($paths as $path) {
				$path = html_entity_decode($path, ENT_QUOTES, 'UTF-8');
				
				try {
					if (strpos($path, 's3://') === 0) {
						// Handle S3 deletion
						if (!delete_from_bucket($path)) {
							throw new \Exception($this->language->get('error_delete'));
						}
					} else {
						// Handle local file deletion (original logic)
						$base = DIR_IMAGE . STORE_NAME . '/';
						$fullPath = rtrim($base . $path, '/');
						
						if (($path == $base) || (substr(str_replace('\\', '/', realpath($fullPath)) . '/', 0, strlen($base)) != $base)) {
							throw new \Exception($this->language->get('error_delete'));
						}
						
						if (is_file($fullPath)) {
							unlink($fullPath);
						} elseif (is_dir($fullPath)) {
							// Handle directory deletion recursively
							$files = new \RecursiveIteratorIterator(
								new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
								\RecursiveIteratorIterator::CHILD_FIRST
							);
							
							foreach ($files as $file) {
								if ($file->isDir()) {
									rmdir($file->getRealPath());
								} else {
									unlink($file->getRealPath());
								}
							}
							
							rmdir($fullPath);
						}
					}
				} catch (\Exception $e) {
					$json['error'] = $e->getMessage();
					break;
				}
			}
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}