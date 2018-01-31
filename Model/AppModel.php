<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

	public $ignoreDeleteFlg = true;

	public function beforeFind($queryData){

		$tables = array('DemandInfo', 'CommissionInfo', 'MCorp', 'IntroduceInfo', 'AdditionInfo');

		if (in_array($this->alias, $tables)) {
			$queryData["conditions"][$this->alias."."."del_flg"] = 0;
		}

		foreach($this->hasOne as $key=>&$joinTable){
			if (in_array($key, $tables)) {
				$joinTable["conditions"][$key.".del_flg"] = 0;
			}
		}
		foreach($this->hasMany as $key=>&$joinTable){
			if (in_array($key, $tables)) {
				$joinTable["conditions"][$key.".del_flg"] = 0;
			}
		}
		foreach($this->belongsTo as $key=>&$joinTable){
			if (in_array($key, $tables)) {
				$joinTable["conditions"][$key.".del_flg"] = 0;
			}
		}
		foreach($this->hasAndBelongsToMany as $key=>&$joinTable){
			if (in_array($key, $tables)) {
				$joinTable["conditions"][$key.".del_flg"] = 0;
			}
		}

		return $queryData;
	}

	public function beforeSave($options = array()) {

		$user_id = isset($_SESSION['Auth']['User']['user_id']) ? $_SESSION['Auth']['User']['user_id'] : '';
		$id = isset($_SESSION['Auth']['User']['id']) ? $_SESSION['Auth']['User']['id'] : null;

		if(empty($this->data[$this->alias]['id'])){
			$this->data[$this->alias]['created_user_id'] = $user_id;
			$this->data[$this->alias]['created'] = date('Y-m-d H:i:s');
			if(empty($this->data[$this->alias]['create_date']))$this->data[$this->alias]['create_date'] = date('Y-m-d H:i:s');
			if(empty($this->data[$this->alias]['create_user_id']))$this->data[$this->alias]['create_user_id'] = $id;
		}
		$this->data[$this->alias]['modified_user_id'] = $user_id;
		$this->data[$this->alias]['modified'] = date('Y-m-d H:i:s');
		if(empty($this->data[$this->alias]['update_date']))$this->data[$this->alias]['update_date'] = date('Y-m-d H:i:s');
		if(empty($this->data[$this->alias]['update_user_id']))$this->data[$this->alias]['update_user_id'] = $id;

	}

	public function deleteLogical($id){
		if(empty($id)) return false;

		$data = array(
				$this->name => array(
						'id' => $id,
						'del_flg' => true
				)
		);
		return $this->save($data, false, array('del_flg'));
	}

	function begin() {
		$dataSource = $this->getDataSource();
		$dataSource->begin($this);
	}

	function commit() {
		$dataSource = $this->getDataSource();
		$dataSource->commit($this);
	}

	function rollback() {
		$dataSource = $this->getDataSource();
		$dataSource->rollback($this);
	}

	public function unbindModelAll($reset = true) {
		foreach(array('hasOne','hasMany','belongsTo','hasAndBelongsToMany') as $relation){
			$this->unbindModel(array($relation => array_keys($this->$relation)), $reset);
		}
	}

	//ORANGE-13 ADD S
	/**
	 * モデル内からログインユーザ情報を取得
	 * @return mixed|NULL|array|boolean|unknown
	 */
	protected function __getLoginUser(){
		App::uses('AuthComponent', 'controller/component');
		return AuthComponent::user();
	}
	//ORANGE-13 ADD E
}
