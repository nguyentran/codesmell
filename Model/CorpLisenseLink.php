<?php

App::uses('AppModel', 'Model');

/**
 * CorpLisenseLink Model
 *
 * @property Corps $Corps
 * @property Lisense $Lisense
 * @property CreateUser $CreateUser
 * @property UpdateUser $UpdateUser
 */
class CorpLisenseLink extends AppModel {

	/**
	 * Use table
	 *
	 * @var mixed False or table name
	 */
	public $useTable = 'corp_lisense_link';

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
			'corps_id' => array(
					'numeric' => array(
							'rule' => array('numeric'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'lisense_id' => array(
					'numeric' => array(
							'rule' => array('numeric'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'have_lisense' => array(
					'boolean' => array(
							'rule' => array('boolean'),
							'message' => 'どちらかを選択してください。',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'version_no' => array(
					'numeric' => array(
							'rule' => array('numeric'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'delete_flag' => array(
					'boolean' => array(
							'rule' => array('boolean'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'lisense_check' => array(
					'notEmpty' => array(
							'rule' => array('notEmpty'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
	);

    // 2017/10/19 ORANGE-541 m-kawamoto DEL(S)
    ///**
    // * 2017/4/26 ichino ORANGE-402 ADD start
	// * 保持しているライセンスの有効期限が設定されており、有効期限1ヶ月前のデータを取得する
	// * @param string $corp_id 加盟店ID
	// */
    //public function licenseExpirationDateByCorpId($corp_id) {
    //    //共通のフィールド、検索条件、結合条件を取得
    //    $query = $this->licenseExpirationDateQuery();
    //
    //    //検索条件の追加
    //    $query['conditions'] = array_merge(
    //            $query['conditions'],
    //            array(
    //                'MCorp.license_display_flag' => 1,          //ライセンスチェックポップアップ表示フラグ
    //                'CorpLisenseLink.corps_id' => $corp_id,     //加盟店ID
    //                'CorpLisenseLink.license_expiration_date <=' => date('Y-m-d', strtotime("+1 month")), //現在日時に一か月を足し、有効期限が一か月前かどうかを判定
    //                // 2017/6/15 inokuma ORANGE 442 ADD start
    //                'CorpLisenseLink.lisense_check <>' => 'None', //資格確認がNone以外か
    //                // 2017/6/15 inokuma ORANGE 442 ADD end
    //            )
    //    );
    //
    //    //データ取得
    //    $data = $this->find('all', $query);
    //    return $data;
    //}
    ////2017/4/26 ichino ORANGE-402 ADD end
    //
    ///**
    // * 2017/4/26 ichino ORANGE-402 ADD start
	// * 各加盟店の保持するライセンスに有効期限が設定されており、有効期限2週間を切ったのデータを取得する
	// */
    //public function licenseExpirationDate(){
    //    //共通のフィールド、検索条件、結合条件を取得
    //    $query = $this->licenseExpirationDateQuery();
    //
    //    //検索条件の追加
    //    $query['conditions'] = array_merge(
    //            $query['conditions'],
    //            array(
    //               'MCorp.affiliation_status' => 1,            //加盟店 加盟状態
    //                'MCorp.del_flg' => 0,                      //加盟店削除フラグ
    //                'CorpLisenseLink.license_expiration_date <=' => date('Y-m-d', strtotime("+2 month")),  //現在日時に2週間を足し、有効期限が二週間前かどうかを判定
    //            )
    //    );
    //
    //    //並び順の指定
    //    $query['order'] = 'MCorp.id';
    //
    //    //1ページに表示する最大件数の指定
    //    $query['limit'] = Configure::read('list_limit');
    //
    //    //クエリの返却
    //    return $query;
    //}
    ////2017/4/26 ichino ORANGE-402 ADD end
    //
    ///**
    // * 2017/4/26 ichino ORANGE-402 ADD start
    // * 共通のフィールド、検索条件、結合条件の設定
    // * @return array $query
    // */
    //private function licenseExpirationDateQuery(){
    //    $query = array(
    //        'fields' => array(
    //            'CorpLisenseLink.id',
    //            'CorpLisenseLink.corps_id',
    //            'CorpLisenseLink.lisense_id',
    //            'CorpLisenseLink.have_lisense',
    //            'CorpLisenseLink.version_no',
    //            'CorpLisenseLink.create_date',
    //            'CorpLisenseLink.create_user_id',
    //            'CorpLisenseLink.update_date',
    //            'CorpLisenseLink.update_user_id',
    //            'CorpLisenseLink.delete_date',
    //            'CorpLisenseLink.delete_flag',
    //            'CorpLisenseLink.lisense_check',
    //            'CorpLisenseLink.license_expiration_date',
    //            'License.id',
    //            'License.name',
    //            'MCorp.id',
    //            'MCorp.official_corp_name',
    //            'MCorp.corp_name_kana',
    //            'MCorp.license_display_flag',
    //            'MCorp.corp_commission_status',
    //        ),
    //    	'conditions' => array(
    //            'CorpLisenseLink.have_lisense' => true,     //ライセンス保持フラグ
    //            'CorpLisenseLink.delete_flag' => false,     //ライセンス削除フラグ
    //        ),
    //        'joins' => array(
    //           array(
    //               'type' => 'inner',
    //               'table' => 'license',
    //               'alias' => 'License',
    //               'conditions' => array(
    //                    'CorpLisenseLink.lisense_id = License.id',
    //                )
    //            ),
    //            array(
    //               'type' => 'inner',
    //               'table' => 'm_corps',
    //               'alias' => 'MCorp',
    //               'conditions' => array(
    //                    'CorpLisenseLink.corps_id = MCorp.id',
    //                )
    //            ),
    //        ),
    //    );
    //    return $query;
    //}
    ////2017/4/26 ichino ORANGE-402 ADD end
    //
    ///**
    // * 2017/4/26 ichino ORANGE-402 ADD start
	// * ライセンス後追いレポートcsvのヘッダー
	// */
    //public function findCSV(){
    //    $query = $this->licenseExpirationDateQuery();
    //
    //    //フィールドとヘッダーの指定
    //    $query['fields'] = array_keys($this->csvFormat['license_follow']);
    //    //検索条件の追加
    //    $query['conditions'] = array_merge(
    //            $query['conditions'],
    //            array(
    //                'MCorp.affiliation_status' => 1,            //加盟店 加盟状態
    //                'MCorp.del_flg' => 0,                      //加盟店削除フラグ
    //                'CorpLisenseLink.license_expiration_date <=' => date('Y-m-d', strtotime("+2 month"))  //現在日時に2週間を足し、有効期限が二週間前かどうかを判定
    //            )
    //    );
    //
    //    //並び順の指定
    //    $query['order'] = 'MCorp.id';
    //
    //    //クエリ実行と返却
    //    return $this->find('all', $query);
    //}
    ////2017/4/26 ichino ORANGE-402 ADD end
    //
    ///**
    // * 2017/4/26 ichino ORANGE-402 ADD start
	// * CSV項目、CSVComponentで利用される
	// * @var array $csvFormat
	// */
	//public $csvFormat = array(
    //            'license_follow' => array(
    //                'MCorp.id' => '企業ID',
    //                'MCorp.official_corp_name' => '正式企業名',
    //                'MCorp.corp_name_kana' => '企業名ふりがな',
    //                'License.id' => 'ライセンスID',
    //                'License.name' => 'ライセンス名',
    //                'CorpLisenseLink.license_expiration_date' => '有効期限',
    //        ),
	//);
    ////2017/4/26 ichino ORANGE-402 ADD end
    // 2017/10/19 ORANGE-541 m-kawamoto DEL(E)

}
