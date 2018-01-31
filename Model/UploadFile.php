<?php

App::uses('AppModel', 'Model');

class UploadFile extends AppModel {

	public $useTable = false;
	public $validate = array(
			'file' => array(
					'upload-file-exists' => array(
							'rule' => array('uploadError', false),
							'message' => array('アップロードに失敗しました。'),
					),
					'upload-file-extension' => array(
							'rule' => array('extension', array('jpg', 'jpeg', 'png', 'bmp', 'pdf')),
							'message' => array('アップロードできない拡張子のファイルです。'),
					),
					'upload-file-mimetype' => array(
							'rule' => array('mimeType', array(
											'image/jpeg',
											'image/png', 'image/x-png',
											'image/bmp', 'image/x-bmp', 'image/x-MS-bmp',
											'application/pdf')),
							'message' => array('アップロードできない種類のファイルです。'),
					),
					'upload-file-max-size' => array(
							'rule' => array('fileSize', '<=', '20MB'),
							'message' => array('ファイルのサイズが大きすぎます。'),
					),
	));

}
