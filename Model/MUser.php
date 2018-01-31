<?php
class MUser extends AppModel {

	public $validate = array(

			'user_id' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
					'AlphaNumeric' => array(
						'rule' => array('custom', '/^[a-zA-Z0-9\-]+$/'),
						'last' => true,
						'allowEmpty' => true
					),
					'IsUnique' => array(
							'rule' => 'isUnique',
							'last' => true,
					),
					// 2016.07.19 murata.s ADD(S) 入力文字数を20文字に制限
					'OverMaxLength20' => array(
							'rule' => array('maxLength', 20),
							'last' => true,
							'allowEmpty' => true
					),
					// 2016.07.19 murata.s ADD(E)
			),
			'user_name' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
			),
			'password' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
					'CheckPassword' => array(
						'rule' => array('custom', '/^[a-zA-Z0-9_\<\>\!\$%&@\+\-\*\=]*$/'),
						'last' => true,
					),
			),
			'password_confirm' => array(
					'SamePassword' => array(
						'rule' => array('sameCheck','password'),
						'last' => true,
					),
			),
			'auth' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
			),
			'affiliation_id' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
			),

	);

	public function dropDownUser(){
		$user = $this->find('all', array('conditions'=>array('auth !=' => 'affiliation'), 'order'=>'user_name'));

		foreach ($user as $val) {

			$users_[] = array(
					"MUser" => array(
							"id" => $val['MUser']['id'],
							"user_name" => $val['MUser']['user_name'],
					)
			);
		}
		$user_list = Set::Combine(@$users_, '{n}.MUser.id', '{n}.MUser.user_name');
		return $user_list;
	}

	public function dropDownUser2(){
		$user = $this->find('all', array('conditions'=>array('auth !=' => 'affiliation'), 'order'=>''));

		foreach ($user as $val) {

			$users_[] = array(
					"MUser" => array(
							"id" => $val['MUser']['user_id'],
							"user_name" => $val['MUser']['user_name'],
					)
			);
		}
		$user_list = Set::Combine(@$users_, '{n}.MUser.id', '{n}.MUser.user_name');
		return $user_list;
	}

	/**
	 * 指定されたIDの名称を取得します
	 *
	 * @param unknown_type $id
	 */
	public function getListText($id){

		$conditions = array('id' => $id);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MUser.id asc'));

		$name ='';

		foreach ($list as $val) {

			if($id == $val['MUser']['id']) {
				$name = $val['MUser']['user_name'];
			}
		}

		return $name;
	}

	// パスワード同一チェック
	public function sameCheck($value , $field_name) {
		$v1 = array_shift($value);
		$v2 = $this->data[$this->name][$field_name];
		return $v1 == $v2;
	}

	// murata.s ORANGE-430 ADD(S)
	/**
	 * 最終ログイン日時を更新する
	 * @param int $id ユーザID
	 */
	public function updateLastLoginDate($id){
		$data = array(
				'id' => $id,
				'last_login_date' => date("Y/m/d H:i:s", time())
		);
		return $this->save($data);
	}
	// murata.s ORANGE-430 ADD(E)

	// murata.s ORANGE-430 ADD(S)
	public $csvFormat = array(
			'default' => array(
					'MUser.user_name' => 'ユーザー名',
					'MCorp.id' => '企業ID',
					'MCorp.official_corp_name' => '加盟店名',
					'MUser.auth' => '権限',
					'MUser.last_login_date' => '最終ログイン日時'
			)
	);
	// murata.s ORANGE-430 ADD(E)
}