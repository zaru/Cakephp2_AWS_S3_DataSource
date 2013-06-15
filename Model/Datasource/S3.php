<?php
/**
 * AmazonWebServices S3 へのファイル操作データソース
 *
 */
App::import('Vendor', 'AmazonWebServices.aws_sdk/sdk.class');

class S3 extends DataSource {
	public $description = 'AmazonWebServices S3 File Controller';
	public $S3 = '';
	public $bucketName = '';
	
	public function __construct($config = array(), $autoConnect = true){
		parent::__construct($config);
		$this->S3 = new AmazonS3($config);
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
		
		$res = $this->S3->create_object(
			$this->bucketName,
			$dstFilePath,
			array(
				'fileUpload' => $srcFilePath,
				'acl'=>AmazonS3::ACL_PUBLIC
			)
		);
		
		if ($res->status == '200') {
			return $res->header['_info']['url'];
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
		
		$res = $this->S3->delete_object(
			$this->bucketName,
			$filePath
		);
		
		// 削除しようが存在しなかろうが、204が返ってくるのでとりあえずこのままで…
		if ($res->status == '204') {
			return true;
		}
		
		return false;
		
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
		
		$res = $this->S3->copy_object(
			array('bucket' => $this->bucketName, 'filename' => $srcFilePath),
			array('bucket' => $this->bucketName, 'filename' => $dstFilePath)
		);
		
		if ($res->status != '200') {
			return false;
		}
		
		$s3Url = $res->header['_info']['url'];
		
		$res = $this->S3->delete_object(
			$this->bucketName,
			$srcFilePath
		);
		
		if ($res->status == '204') {
			return $s3Url;
		}
		
		return false;
		
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
		
		$res = $this->S3->copy_object(
			array('bucket' => $this->bucketName, 'filename' => $srcFilePath),
			array('bucket' => $this->bucketName, 'filename' => $dstFilePath)
		);
		
		if ($res->status != '200') {
			return false;
		}
		
		return $res->header['_info']['url'];
		
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