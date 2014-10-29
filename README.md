CakePHP2 AWS S3 DataSource Plugin
==========================

CakePHP2のAmazonWebServices S3のファイルを操作をサポートするデータソースプラグインです。

初期設定
------------

プラグインダウンロード（submoduleでも良いけど）

	$cd app/Plugin
	$git clone git@github.com:zaru/Cakephp2_AWS_S3_DataSource.git AmazonWebServices

もしくはGitHubからZIPダウンロードで app/Plugin に AmazonWebServices という名前で配置。

app/Config/bootstrap.phpに

	CakePlugin::load('AmazonWebServices');

と記述しプラグインを読み込んでください。

app/Config/database.phpに

	class DATABASE_CONFIG {
		
		//...
		
		public $s3 = array(
			'datasource' => 'AmazonWebServices.S3',
			'bucket_name' => '',
			'key' => '',
			'secret' => '',
			'default_cache_config' => '',
			'certificate_authority' => false
		);
	}

とAWSの設定を記述してください。

後は、適当なモデルファイルを用意し、$useDbConfigを上記で記述したデータベース設定にします。

	<?php
	class Amazon extends AppModel {
		public $name = 'Amazon';
		public $useDbConfig = 's3';
	}


使い方
------------

適当なコントローラにて

	<?php
	App::uses('AppController', 'Controller');
	class HogeController extends AppController {
		public $name = 'Hoge';
		public $uses = array('Amazon');
		
		public function s3() {
			
			// アップロード
			$result = $this->Amazon->putFile(APP . WEBROOT_DIR . '/img/cake.power.gif', '/img/cake.power.gif');
			
			// 削除
			$result = $this->Amazon->deleteFile('/img/cake.power.gif');
			
			// 移動
			$result = $this->Amazon->moveFile('/img/cake.power.gif', '/img/cake.power2.gif');
			
			// コピー
			$result = $this->Amazon->copyFile('/img/cake.power.gif', '/img/cake.power2.gif');
            // 取得
			$result = $this->Amazon->getFile('/img/cake.power.gif');
			
		}
	}
