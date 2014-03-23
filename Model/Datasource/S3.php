<?php
/**
 * AmazonWebServices S3 へのファイル操作データソース
 *
 */
use Aws\S3\Enum\CannedAcl;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Guzzle\Http\EntityBody;

/**
 * Class S3
 */
class S3 extends DataSource {
	public $description = 'AmazonWebServices S3 File Controller';
	public $S3 = '';
	public $bucketName = '';
	
	public function __construct($config = array(), $autoConnect = true){
		parent::__construct($config);
		$this->S3 = S3Client::factory($config);
		$this->bucketName = $config['bucket_name'];
	}
	
	public function listSources($data = null) {
		return null;
	}
	
	public function describe($model) {
		return array();
	}
	
	public function calculate(Model $model, $func, $params = array()) {
		return 'COUNT';
	}
	
/**
 * AWS S3へファイルをアップロードする
 *
 * @param string $srcFilePath アップロード元ファイルの絶対パス
 * @param string $dstFilePath アップロード先の絶対パス
 * @return mixed
 */
	protected function putFile($srcFilePath, $dstFilePath) {
		
		if (!file_exists($srcFilePath)) {
			return false;
		}
		
		// 行頭にスラッシュが入っていた場合は消す
		$dstFilePath = preg_replace("/^\/(.+)$/", "$1", $dstFilePath);

		try {
			$result = $this->S3->putObject(array(
					'Bucket' => $this->bucketName,
					'Key' => $dstFilePath,
					'Body' => EntityBody::factory(fopen($srcFilePath, 'r')),
					'ACL' => CannedAcl::PUBLIC_READ,
				));
		} catch (S3Exception $exc) {
			CakeLog::error('AWS S3 [putObject]: ' . $exc->getMessage());
			return false;
		}

		if (isset($result['ObjectURL'])) {
			return $result['ObjectURL'];
		}

		return false;
	}

/**
 * AWS S3へファイルを削除する
 *
 * @param string $filePath 削除対象のファイル
 * @return mixed
 */
	protected function deleteFile($filePath) {
		
		// 行頭にスラッシュが入っていた場合は消す
		$filePath = preg_replace("/^\/(.+)$/", "$1", $filePath);

		try {
			 $this->S3->deleteObject(array(
					'Bucket' => $this->bucketName,
					'Key' => $filePath,
				));
		} catch (S3Exception $exc) {
			CakeLog::error('AWS S3 [deleteObject]: ' . $exc->getMessage());
			return false;
		}

		return true;
	}

/**
 * AWS S3のファイルを移動する
 *
 * @param string $srcFilePath 移動元のファイル
 * @param string $dstFilePath 移動先のファイル
 * @return mixed
 */
	protected function moveFile($srcFilePath, $dstFilePath) {
		
		// 行頭にスラッシュが入っていた場合は消す
		$srcFilePath = preg_replace("/^\/(.+)$/", "$1", $srcFilePath);
		$dstFilePath = preg_replace("/^\/(.+)$/", "$1", $dstFilePath);

		try {
			 $this->S3->copyObject(array(
					'Bucket' => $this->bucketName,
					'CopySource' => $this->bucketName . '/' . $srcFilePath,
					'Key' => $dstFilePath,
					'ACL' => CannedAcl::PUBLIC_READ,
				));
		} catch (Exception $e) {
			CakeLog::error('AWS S3 [copyObject]: ' . $exc->getMessage());
			return false;
		}

		$this->deleteFile($srcFilePath);
		
		return true;
		
	}

/**
 * AWS S3のファイルをコピーする
 *
 * @param string $srcFilePath 移動元のファイル
 * @param string $dstFilePath 移動先のファイル
 * @return mixed
 */
	protected function copyFile($srcFilePath, $dstFilePath) {
		
		// 行頭にスラッシュが入っていた場合は消す
		$srcFilePath = preg_replace("/^\/(.+)$/", "$1", $srcFilePath);
		$dstFilePath = preg_replace("/^\/(.+)$/", "$1", $dstFilePath);

		try {
			$this->S3->copyObject(array(
					'Bucket' => $this->bucketName,
					'CopySource' => $this->bucketName . '/' . $srcFilePath,
					'Key' => $dstFilePath,
					'ACL' => CannedAcl::PUBLIC_READ,
				));
		} catch (Exception $e) {
			CakeLog::error('AWS S3 [copyObject]: ' . $exc->getMessage());
			return false;
		}
		
		return true;
		
	}
	

/**
 * AWS S3への操作クエリ
 *
 * @param string $method S3への操作メソッド（PUT/GET/DELETE/MOVE/COPY)
 * @param array $query パラメータ
 * @return mixed
 */
	public function query($method, $query = array()) {
	
		switch ($method) {
			case 'putFile':
				if (isset($query['0']) && isset($query['1'])) {
					return $this->putFile($query['0'], $query['1']);
				}
				break;
			case 'deleteFile':
				if (isset($query['0'])) {
					return $this->deleteFile($query['0']);
				}
				break;
			case 'moveFile':
				if (isset($query['0']) && isset($query['1'])) {
					return $this->moveFile($query['0'], $query['1']);
				}
				break;
			case 'copyFile':
				if (isset($query['0']) && isset($query['1'])) {
					return $this->copyFile($query['0'], $query['1']);
				}
				break;
			default:
				break;
		}
		
	}
}