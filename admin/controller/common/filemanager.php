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
	
		$s3BasePath = STORE_NAME . '/';
		$currentDir = $this->getCurrentDirectory();
		$fullS3Path = $s3BasePath . $currentDir;
	
		$filterName = $this->getFilterName();
		$page = $this->getPage();
		$allowedExtensions = $this->getAllowedExtensions();
	
		$directories = [];
		$files = [];
	
		try {
			$objects = $GLOBALS['s3']->listObjectsV2([
				'Bucket' => S3_BUCKET,
				'Prefix' => $fullS3Path,
				'Delimiter' => '/'
			]);
	
			$directories = $this->processDirectories($objects['CommonPrefixes'] ?? [], $filterName, $s3BasePath);
			$files = $this->processFiles($objects['Contents'] ?? [], $filterName, $s3BasePath, $allowedExtensions, $fullS3Path);
			
		} catch (\Exception $e) {
			error_log("S3 List Error: " . $e->getMessage());
		}
	
		$data = $this->paginateAndPrepareData($directories, $files, $page, $s3BasePath);
		$this->response->setOutput($this->load->view('common/filemanager_list', $data));
	}

	private function getCurrentDirectory(): string {
		if (isset($this->request->get['directory'])) {
			return rtrim(html_entity_decode($this->request->get['directory'], ENT_QUOTES, 'UTF-8'), '/') . '/';
		}
		return '';
	}
	
	private function getFilterName(): string {
		return isset($this->request->get['filter_name'])
			? basename(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'))
			: '';
	}
	
	private function getPage(): int {
		return isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
	}
	
	private function getAllowedExtensions(): array {
		$extensions = explode("\r\n", $this->config->get('config_file_ext_allowed'));
		$allowed = [];
		foreach ($extensions as $ext) {
			$allowed[] = '.' . strtolower($ext);
			$allowed[] = '.' . strtoupper($ext);
		}
		return $allowed;
	}
	
	private function processDirectories(array $prefixes, string $filter, string $base): array {
		$dirs = [];
		foreach ($prefixes as $prefix) {
			$dirPath = rtrim($prefix['Prefix'], '/');
			$dirName = substr($dirPath, strrpos($dirPath, '/') + 1);
	
			if ($filter && !str_starts_with($dirName, $filter)) continue;
	
			$relativePath = substr($dirPath, strlen($base));
			$dirs[] = [
				'name' => $dirName,
				'path' => $relativePath . '/'
			];
		}
		return $dirs;
	}
	
	private function processFiles(array $contents, string $filter, string $base, array $allowed, string $fullPath): array {
		$files = [];
		foreach ($contents as $object) {
			if ($object['Key'] === $fullPath || substr($object['Key'], -1) === '/') continue;
	
			$fileName = basename($object['Key']);
			if ($filter && !str_starts_with($fileName, $filter)) continue;
	
			$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
			if (in_array('.' . $ext, $allowed)) {
				$files[] = [
					'name' => $fileName,
					'path' => substr($object['Key'], strlen($base)),
					's3_path' => $object['Key'],
					'size' => $object['Size'],
					'last_modified' => $object['LastModified']
				];
			}
		}
		return $files;
	}
	
	private function paginateAndPrepareData(array $directories, array $files, int $page, string $basePath): array {
		$limit = 16;
		$start = ($page - 1) * $limit;
	
		$this->load->model('tool/image');
	
		$url = $this->getUrlQuery();
		$data = [
			'directories' => [],
			'images' => []
		];
	
		// Diretórios
		foreach (array_slice($directories, $start, $limit) as $dir) {
			$data['directories'][] = [
				'name' => $dir['name'],
				'path' => $dir['path'],
				'href' => $this->url->link(
					'common/filemanager.list',
					'user_token=' . $this->session->data['user_token'] . '&directory=' . urlencode($dir['path']) . $url
				)
			];
		}
	
		// Arquivos
		$filesLimit = $limit - count($data['directories']);
		foreach (array_slice($files, $start, $filesLimit) as $file) {
			$s3FullPath = "s3://" . S3_BUCKET . '/' . $file['s3_path'];
			$thumbnail = $this->getThumbnail($file);
	
			$data['images'][] = [
				'name'  => $file['name'],
				'path'  => $file['path'],
				'href'  => bucket_file_url($s3FullPath),
				'thumb' => $thumbnail
			];
		}
	
		return $data;
	}
	
	private function getThumbnail(array $file): string {
		try {
			return resize_image((
				$file['s3_path'],
				$this->config->get('config_image_default_width'),
				$this->config->get('config_image_default_height')
			);
		} catch (\Exception $e) {
			try {
				return resize_image((
					$file['path'],
					$this->config->get('config_image_default_width'),
					$this->config->get('config_image_default_height')
				);
			} catch (\Exception $e) {
				return bucket_file_url_not_found();
			}
		}
	}
	
	private function getUrlQuery(): string {
		$parts = [];
		foreach (['target', 'thumb', 'ckeditor'] as $key) {
			if (isset($this->request->get[$key])) {
				$parts[] = $key . '=' . $this->request->get[$key];
			}
		}
		return $parts ? '&' . implode('&', $parts) : '';
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