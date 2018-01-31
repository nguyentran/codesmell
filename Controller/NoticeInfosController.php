<?php
App::uses('AppController', 'Controller');

/**
 * NoticeInfos Controller
 * 掲示板
 * @property NoticeInfo $NoticeInfo
 * @property PaginatorComponent $Paginator
 * @property SessionComponent $Session
 */
class NoticeInfosController extends AppController {

	/**
	 * Name
	 * @var string
	 */
	public $name = 'NoticeInfos';

	/**
	 * Helpers
	 * @var array
	 */
	public $helpers = array('Csv');

	/**
	 * Components
	 * @var array
	 */
	public $components = array('Csv');

	/**
	 * Uses
	 * @var array
	 */
	// 2017.03.16 izumi.s ORANGE-350 CHG(S)
	public $uses = array('NoticeInfo', /* 'MCorp', */ /* 'MCorpsNoticeInfo', */ 'MCorpCategory',
			'MGenre', 'MTargetArea', 'MCorpTargetArea', /* 'MPost', */ 'MItem', 'NotCorrespond', /* 'NearPrefecture', */ 'NotCorrespondItem');

	// 2017.03.16 izumi.s ORANGE-350 CHG(E)

	/**
	 * コントローラの各アクションの前に実行
	 * @return void
	 */
	public function beforeFilter() {
		// 共通フィルタ
		parent::beforeFilter();
		// 権限により取得できるデータが異なるためモデルにユーザー情報をセット
		$this->NoticeInfo->setUser($this->User);
		// 加盟店からのアクセスかどうか false:加盟店以外, true:加盟店
		$this->set('is_poster', $this->NoticeInfo->isPoster());
                // 2017.10.16 ORANGE-568 h.miyake ADD(S)
                $this->set('is_reader', $this->NoticeInfo->isReader());
                // 2017.10.16 ORANGE-568 h.miyake ADD(E)
                $this->paginate = array('limit' => Configure::read((Util::isMobile()) ? 'list_limit_mobile' : 'list_limit'));
	}

	/**
	 * 記事一覧
	 * @return void
	 */
	public function index() {
		// データ取得
		$results = $this->paginate('NoticeInfo');
		$this->set('results', $results);
		$this->_saveConditions();
		$this->_set_cc_count();
		$this->_rendarIndex();
	}

	/**
	 * 記事詳細
	 * @param string $id notice_infos.id
	 * @return null
	 */
	public function detail($id = null) {
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		$this->data = $this->NoticeInfo->findById($id);
		if (empty($this->data)) {
			// 有効な記事データが得られなければindexへリダイレクト
			return $this->redirect(array('controller' => 'notice_infos', 'action' => 'index'));
		}
		// 有効な記事が得られたのなら既読にする。結果は問わない。
		$this->NoticeInfo->markRead($id);
		if (Util::isMobile()) {
			// モバイル端末であればモバイルレイアウト表示
			$this->render('detail_m', 'default_m');
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
	}

	/**
	 * 記事の編集・投稿を行う
	 * @param type $id
	 * @return void
	 * @throws UnauthorizedException
	 */
	// 2017.03.16 izumi.s ORANGE-350 ADD(S)
	public function edit($id = null) {
		if (!$this->NoticeInfo->isPoster()) {
			throw new UnauthorizedException(__('NoReferenceAuthority', true));
		}
		if ($this->request->is(array('post', 'put'))) {
			// POST,PUTされた場合は記事作成/更新
			if ($this->NoticeInfo->merge($this->request->data) === true) {
				// 記事作成/更新に成功したら自身へリダイレクトして再読み込みする(ブラウザ更新による複数回投稿防止)
				$this->Session->setFlash(__($this->NoticeInfo->getFlashMessage(), true), 'default', array('class' => 'message_inner'));
				return $this->redirect(array('controller' => 'notice_infos', 'action' => 'edit', $this->NoticeInfo->getID()));
			} else {
				// 記事作成/更新に失敗した場合、POSTデータには検証結果が含まれるのでマージして表示させる
				$this->Session->setFlash(__($this->NoticeInfo->getFlashMessage(), true), 'default', array('class' => 'warning_inner'));
				$this->data = Hash::merge($this->NoticeInfo->findById($id), $this->request->data);
			}
		} elseif (!$id) {
			//ID指定が無い場合新規作成
			$this->data = $this->NoticeInfo->create();
		} else {
			$this->data = $this->NoticeInfo->findById($id);
		}
		if (empty($this->data)) {
			// 有効な記事データが得られなければindexへリダイレクト
			$this->redirect(array('controller' => 'notice_infos', 'action' => 'index'));
		}
		$this->loadModel('NoticeInfoTarget');
		if ($corp_ids = Hash::get($this->data, 'NoticeInfoTarget.corp_id')) {
			$NIT = $this->NoticeInfoTarget->findCorpListByCorpIds($corp_ids);
			$this->data = Hash::insert($this->data, 'NoticeInfoTarget', Hash::get($NIT, 'NoticeInfoTarget'));
		} else {
			$this->data += (array) $this->NoticeInfoTarget->findCorpListByNoticeInfoId($id);
		}
		$this->loadModel('MCorpsNoticeInfo');
		$this->data += (array) $this->MCorpsNoticeInfo->findAnswerListByNoticeInfoId($id);
		$this->data += array('target' => (Hash::get($this->data, 'NoticeInfo.is_target_selected') === false) ? 'disp_1' : 'disp_2');
		$this->data += array('require_answer' => (Hash::get($this->data, 'NoticeInfo.choices')) ? 'answer_1' : 'answer_0');
	}

	// 2017.03.16 izumi.s ORANGE-350 ADD(E)

	/**
	 * 「戻る」した時にセッションに保存されているソート条件を復帰して一覧へリダイレクト
	 * @return void
	 */
	public function cancel() {
		// セッションの値を取得
		$data = $this->Session->read(self::$__sessionKeyForNoticeInfo);
		$url = array('controller' => 'notice_infos', 'action' => 'index',);
		if (isset($data['page'])) {
			$url['page'] = $data ['page'];
		}
		if (isset($data['sort'])) {
			$url['sort'] = $data ['sort'];
		}
		if (isset($data['direction'])) {
			$url['direction'] = $data ['direction'];
		}
		$this->redirect($url);
	}

	// 2017.03.16 izumi.s ORANGE-350 ADD(S)
	/**
	 * 加盟店の一覧を取得する
	 * @return type
	 * @throws BadRequestException
	 * @TODO ロジックのモデルへの切り出しが必要
	 */
	public function affiliation_list() {
		if (!$this->request->isAll(array('post', 'ajax'))) {
			throw new BadRequestException();
		}
		try {
			$this->autoRender = false;
			$this->response->type('json');

			switch (Hash::get($this->request->data, 'searchkey')) {
				case 'search_corp_name':
					$patterns = preg_split('/[\s]/', str_replace('　', ' ', Hash::get($this->request->data, 'search_corp_name')), -1, PREG_SPLIT_NO_EMPTY);
					$searchkey = array();
					array_walk($patterns, function($value) use(&$searchkey) {
						if (mb_substr($value, 0, 1) === '-') {
							$searchkey[] = array('MCorp.corp_name NOT LIKE' => '%' . mb_substr($value, 1) . '%');
						} else {
							$searchkey[] = array('MCorp.corp_name LIKE' => '%' . $value . '%');
						}
					});
					break;
				case 'search_corp_id':
					$patterns = Hash::filter(
													preg_split('/[\s\n,]/', Hash::get($this->request->data, 'search_corp_id'), -1, PREG_SPLIT_NO_EMPTY)
													, function($part) {
										return is_numeric($part);
									});
					$searchkey = array('MCorp.id' => (array) $patterns);
					break;
			}
			$knowns = Hash::get($this->request->data, 'NoticeInfoTarget.corp_id');
			$query = array(
					'conditions' => array(
							(array) $searchkey,
							'del_flg' => 0,
							'affiliation_status' => 1,
							'NOT' => array(
									'MCorp.id' => $knowns,
							),
					),
					'fields' => array('id', 'corp_name'),
					'recursive' => -1,
					'order' => array('id'),
					'limit' => 50,
			);
			$status = true;
			$count = $this->MCorp->find('count', $query);
			$data = $this->MCorp->find('list', $query);
			return json_encode(compact('status', 'count', 'data'));
		} catch (Exception $e) {
			$this->response->statusCode(500);
			return json_encode(array('message' => $e->getMessage()));
		}
	}

	/**
	 * 記事への回答を行う
	 * @param type $id 記事ID
	 * @return type
	 * @throws BadRequestException
	 * @throws UnauthorizedException
	 * @TODO ロジックのモデルへの切り出しが必要
	 */
	public function answer($id) {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			throw new BadRequestException();
		}
		if ($this->NoticeInfo->isPoster()) {
			$this->Session->setFlash('加盟店以外は回答を登録できません。', 'default', array('class' => 'warning_inner'));
			$this->redirect(array('action' => 'detail', $id));
		}
		if (!$this->NoticeInfo->isReader()) {
			throw new UnauthorizedException(__('NoReferenceAuthority', true));
		}
		$this->loadModel('MCorpsNoticeInfo');
		$this->MCorpsNoticeInfo->create();
		$data = $this->MCorpsNoticeInfo->find('first', array(
				'conditions' => array(
						'm_corp_id' => $this->Auth->user('affiliation_id'),
						'notice_info_id' => $id,
				)
		));
		if (Hash::get($data, 'MCorpsNoticeInfo.answer_value') != null) {
			$this->Session->setFlash('既に回答済です。', 'default', array('class' => 'warning_inner'));
			$this->redirect(array('action' => 'detail', $id));
		}
		$this->MCorpsNoticeInfo->set($data);
		$conditions = array(
				'answer_value' => Hash::get($this->request->data, 'MCorpsNoticeInfo.answer_value'),
				'answer_user_id' => $this->Auth->user('user_id'),
				'answer_date' => date('Y-m-d H:i:s'),
				'modified_user_id' => $this->Auth->user('user_id'),
				'modified' => date('Y-m-d H:i:s'),
		);
		$result = $this->MCorpsNoticeInfo->save($conditions);
		if ($this->request->is('ajax')) {
			$this->response->type('json');
			return json_encode(compact('result'));
		} else {
			if ($result) {
				$this->Session->setFlash(__('SuccessRegist', true), 'default', array('class' => 'message_inner'));
			} else {
				$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'warning_inner'));
			}
			$this->redirect(array('action' => 'detail', $id));
		}
	}

	public function csv_download($id = null) {
		if (!$this->NoticeInfo->isPoster()) {
			throw new UnauthorizedException(__('NoReferenceAuthority', true));
		}
		$this->autoRender = false;
		$this->layout = false;
		$file_name = mb_convert_encoding(__('notice_infos', true) . '_' . $id . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
		$this->response->type('text/csv');
		$noticeinfo = $this->NoticeInfo->findById($id);
		$this->set('ex_header', array(
				'掲示板番号' => Hash::get($noticeinfo, 'NoticeInfo.id'),
				'件名' => Hash::get($noticeinfo, 'NoticeInfo.info_title'),
				'内容' => Hash::get($noticeinfo, 'NoticeInfo.info_contents'),
		));
		$this->Csv->download('NoticeInfo', 'answer', $file_name, $this->NoticeInfo->findAnswerCSV($id));
	}

	/**
	 * GETパラメーターをセッションに保存
	 * @return void
	 */
	private function _saveConditions() {
		$this->Session->delete(self::$__sessionKeyForNoticeInfo);
		$this->Session->write(self::$__sessionKeyForNoticeInfo, $this->request->named);
	}

	/**
	 * インデックスのテンプレートを表示するための処理
	 * （ijdex/serch共通）
	 *
	 * @return void
	 */
	private function _rendarIndex() {
		$itemList = array(
				'掲示板番号' => array(
						'field' => 'id',
						'th_options' => 'style="width:15%;"',
				),
				'件名' => array('field' => 'info_title'),
		);

		if (!$this->NoticeInfo->isPoster()) {
			$itemList['未読/既読'] = array(
					'field' => 'status',
					'th_options' => 'style="width:10%;"',
			);
		} else {
			$itemList['表示対象'] = array(
					'field' => 'corp_commission_type',
					'th_options' => 'style="width:180px;"',
			);
		}
		$itemList['登録日時'] = array(
				'field' => 'created',
				'th_options' => 'style="width:180px;"',
		);

		$this->set('itemList', $itemList);

		if (Util::isMobile() && !$this->NoticeInfo->isPoster()) {
			$this->layout = 'default_m';
			$this->render('index_m');
		} else {
			$this->render('index');
		}
	}

	// 2017.03.16 izumi.s ORANGE-350 ADD(E)
	// 2017.02.20 murata.s ORANGE-328 ADD(S)
	/**
	 * 自動開拓一覧表示
	 */
	public function near() {

		$corp_id = Hash::get($this->User, 'affiliation_id');
		$data = array();

		// エリア登録
		if (isset($this->request->data['regist-area'])) {
			if ($this->__saveTargetAreas($corp_id, $this->request->data)) {
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner')
				);
				$this->redirect('/notice_infos/near');
			}
		}

		// 表示する案件数の下限値を取得
		$settings = $this->NotCorrespondItem->find('first', array(
				'order' => array('id' => 'desc')
		));

		// エリア対応加盟店なし案件を取得
		$corresponds = $this->NotCorrespond->findNotCorrespond($corp_id);

		// 開拓区分を取得
		$development_groups = $this->MItem->getList('開拓区分');

		// 区分、ジャンル、エリア別にグループ化
		foreach ($development_groups as $dkey => $dval) {
			$groups = array_filter($corresponds, function($v) use($dkey) {
				return $v['MGenre']['development_group'] == $dkey;
			});

			if (!empty($groups)) {
				// 加盟店が登録済みの区分内のジャンルを取得
				$corp_genre = $this->MGenre->find('first', array(
						'fields' => array('MGenre.id'),
						'joins' => array(
								array(
										'table' => 'm_corp_categories',
										'alias' => 'MCorpCategory',
										'conditions' => array(
												'MCorpCategory.genre_id = MGenre.id',
												'MGenre.development_group' => $dkey,
												'MCorpCategory.corp_id' => $corp_id
										)
								)
						)
				));
				// 区分内のジャンルが登録されているかどうか
				$is_target_genre = !empty($corp_genre) ? true : false;
				// 対応エリアに登録済みかどうか
				$is_target_area = false;

				$correspond_data = array(
						'NotCorrespond' => array(
								'development_group' => $dkey,
								'development_group_name' => $dval,
								'registed' => $is_target_genre,
								'sort' => 0
						),
						'MGenre' => array()
				);

				foreach ($groups as $val) {

					// 加盟店の対応エリア内かどうか
					$is_target_area = $is_target_area || !empty($val['NotCorrespond']['target_corp_area']);

					// ジャンルでグループ化
					$genre_id = $val['NotCorrespond']['genre_id'];
					if (!array_key_exists($genre_id, $correspond_data['MGenre'])) {
						$genre_unit_prices = Configure::read('GENRE_UNIT_PRICE');
						$unit_price = !empty($genre_unit_prices[$genre_id]) ? $genre_unit_prices[$genre_id] : 0;

						$correspond_data['MGenre'][$genre_id] = array(
								'genre_id' => $genre_id,
								'genre_name' => $val['MGenre']['genre_name'],
								'unit_price' => $unit_price,
								'registed' => $val['NotCorrespond']['target_category'] > 0,
								'Correspond' => array(),
						);
					}

					// 都道府県毎にグループ化
					$prefecture_cd = $val['NotCorrespond']['prefecture_cd'];
					if (!array_key_exists($prefecture_cd, $correspond_data['MGenre'][$genre_id]['Correspond'])) {
						$correspond_data['MGenre'][$genre_id]['Correspond'][$prefecture_cd] = array(
								'address1' => $val['MPost']['address1'],
								'Area' => array()
						);
					}
					$correspond_data['MGenre'][$genre_id]['Correspond'][$prefecture_cd]['Area'][] = array(
							'jis_cd' => $val['NotCorrespond']['jis_cd'],
							'address1' => $val['MPost']['address1'],
							'address2' => $val['MPost']['address2'],
							'count' => $val['NotCorrespond']['not_correspond_count_year'],
							'new_flg' => date('Y-m-d', strtotime($val['NotCorrespond']['min_import_date'])) > date('Y-m-d', strtotime('-1week')) ? true : false,
							//'sort' => $this->__get_nearly_sort($val['NotCorrespond']['not_correspond_count_year'],
							//		$val['NotCorrespond']['not_correspond_count_latest'])
							'sort' => $this->__get_nearly_sort($settings, $val['NotCorrespond']['not_correspond_count_year'], $val['NotCorrespond']['not_correspond_count_latest'])
					);
				}

				// 市区町村別の表示順を並び替え
				foreach ($correspond_data['MGenre'] as $g_key => $g_val) {
					foreach ($g_val['Correspond'] as $c_key => $c_val) {
						usort($correspond_data['MGenre'][$g_key]['Correspond'][$c_key]['Area'], function($a, $b) {
							if ($a['sort'] == $b['sort']) {
								if ($a['count'] == $b['count'])
								// 年間案件数が同じ場合は市区町村コード順
									return $a['jis_cd'] < $b['jis_cd'] ? -1 : 1;
								else
								// 年間案件数(降順)
									return $a['count'] < $b['count'] ? 1 : -1;
							}
							// 優先度順
							return $a['sort'] < $b['sort'] ? -1 : 1;
						});
					}
				}

				// ジャンルを登録有無で並び替え
				usort($correspond_data['MGenre'], function($a, $b) {
					if ($a['registed'] == $b['registed']) {
						// 登録有無が同じ場合はジャンルID順
						return $a['genre_id'] < $b['genre_id'] ? -1 : 1;
					}
					return $a['registed'] < $b['registed'] ? 1 : -1;
				});

				// 区分の表示順序を決定
				$correspond_data['NotCorrespond']['sort'] = $this->__get_group_sort($is_target_genre, $is_target_area);
				$data[] = $correspond_data;
			}
		}
		// 区分を優先度順で並び替え
		usort($data, function($a, $b) {
			if ($a['NotCorrespond']['sort'] == $b['NotCorrespond']['sort']) {
				return $a['NotCorrespond']['development_group'] < $b['NotCorrespond']['development_group'] ? -1 : 1;
			}
			return $a['NotCorrespond']['sort'] < $b['NotCorrespond']['sort'] ? -1 : 1;
		});

		// 画面表示
		$this->set('results', $data);
		$this->set('setting', $settings);

		if (Util::isMobile() && (Hash::get($this->User, 'auth') === 'affiliation')) {
			$this->layout = 'default_m';
			$this->render('near_m');
			;
		} else {
			$this->render('near');
		}
	}

	/**
	 * 「今日明日の予想案件数」を表示
	 * ※モバイルのみ 
	 * ※ PCも可 // 2017.08.16 e.takeuchi@SharingTechnology ORANGE-489
	 */
	private function _set_cc_count() {
		
		// 2017.08.16 e.takeuchi@SharingTechnology ORANGE-489 CHG(S)
		if (!$this->NoticeInfo->isPoster()) {
		// if (Util::isMobile() && !$this->NoticeInfo->isPoster()) {
		// 2017.08.16e.takeuchi@SharingTechnology ORANGE-489 CHG(E)
			$options = array(
					'conditions' => array(
							'MCorpCategory.corp_id' => $this->User['affiliation_id'],
							'MCorpCategory.genre_id' => Configure::read('FORECAST_CATEGORY_ID')
					),
					'recursive' => -1,
			);
			$cc_cnt = $this->MCorpCategory->find('count', $options);
			if ($cc_cnt >= 1) {
				$this->set('link_disp', $cc_cnt);
			}
		}
	}

	/**
	 * エリア別自動開拓の並び順を取得する
	 * @param array $settings 並び順設定
	 * @param int $count_year エリア対応加盟店案件数(年間)
	 * @param int $count_latest エリア対応加盟店案件数(直近)
	 */
	private function __get_nearly_sort($settings, $count_year, $count_latest) {
		if ($count_latest >= $settings['NotCorrespondItem']['immediate_lower_limit']) {
			// 優先度が急
			return 0;
		} else if ($count_year >= $settings['NotCorrespondItem']['large_lower_limit']) {
			// 優先度が多
			return 1;
		} else if ($count_year >= $settings['NotCorrespondItem']['midium_lower_limit']) {
			// 優先度が中
			return 2;
		} else {
			// 優先度が小
			return 3;
		}
	}

	/**
	 * 開拓区分の並び順を取得する
	 *
	 * @param unknown $is_target_genre ジャンル登録有無
	 * @param unknown $is_target_area エリア登録有無
	 */
	private function __get_group_sort($is_target_genre, $is_target_area) {
		if ($is_target_genre) {
			// 第1優先
			return 1;
		} else if (!$is_target_genre && $is_target_area) {
			// 第2優先
			return 2;
		} else if (!$is_target_genre && !$is_target_area) {
			// 第3優先
			return 3;
		}
		return 9;
	}

	/**
	 * 対応可能エリアを登録する
	 *
	 * @param int $corp_id 企業ID
	 * @param array $data 登録データ
	 */
	private function __saveTargetAreas($corp_id, $data) {

		$result = true;


		if (empty($data['id'])) {
			// 登録するエリアが未選択
			$this->Session->setFlash(__('NotAreaEmpty', true), 'default', array('class' => 'error_inner'));
			$result = false;
		} else {
			// 登録するジャンルIDと市区町村コードを取得
			$regists = array();
			foreach ($data['id'] as $id) {
				$ids = explode('-', $id);
				$regists[] = array(
						'genre_id' => $ids[0],
						'jis_cd' => $ids[1],
				);
			}

			$save_data = array();
			foreach ($regists as $regist) {
				// ジャンルIDに紐付く加盟店登録済みカテゴリを取得
				$corp_categories = $this->MGenre->find('all', array(
						'fields' => array(
								'MGenre.id',
								'MCorpCategory.id',
								'MCorpCategory.genre_id',
								'MCorpCategory.category_id'
						),
						'joins' => array(
								array(
										'type' => 'left',
										'table' => 'm_corp_categories',
										'alias' => 'MCorpCategory',
										'conditions' => array(
												'MCorpCategory.genre_id = MGenre.id',
												'MCorpCategory.corp_id' => $corp_id
										)
								)
						),
						'conditions' => array(
								'MGenre.id' => $regist['genre_id']
						),
						'group' => array(
								'MGenre.id',
								'MCorpCategory.id'
						)
				));

				foreach ($corp_categories as $corp_category) {
					if (empty($corp_category['MCorpCategory']['id'])) {
						// 加盟店ジャンル未登録エラー
						$this->Session->setFlash(__('NotRegistedGenre', true), 'default', array('class' => 'error_inner'));
						$result = false;
						break 2;
					}

					// 登録済み対応可能エリアマスタの取得
					$target_area = $this->MTargetArea->find('first', array(
							'fields' => array('id'),
							'conditions' => array(
									'corp_category_id' => $corp_category['MCorpCategory']['id'],
									'jis_cd' => $regist['jis_cd']
							)
					));
					// 対応可能エリア未登録の場合のみ登録
					if (empty($target_area)) {
						$save_data[] = array(
								'MTargetArea' => array(
										'corp_category_id' => $corp_category['MCorpCategory']['id'],
										'jis_cd' => $regist['jis_cd'],
										'address1_cd' => substr($regist['jis_cd'], 0, 2)
								)
						);
					}
				}
			}
			if ($result && !empty($save_data)) {

				$this->MCorpTargetArea->begin();
				$this->MCorpCategory->begin();

				// 対応可能エリアの登録
				if ($this->MTargetArea->saveAll($save_data)) {
					// 基本対応エリアを取得
					$corp_target_areas = $this->MCorpTargetArea->find('all', array(
							'fields' => 'jis_cd',
							'conditions' => array(
									'corp_id' => $corp_id
							)
					));

					// 対応可能エリアタイプの変更が必要な企業別対応カテゴリIDを取得
					$corp_category_id = array();
					foreach ($save_data as $val) {
						if (!array_key_exists($val['MTargetArea']['corp_category_id'], $corp_category_id))
							$corp_category_id[] = $val['MTargetArea']['corp_category_id'];
					}
					// 基本対応エリアの更新
					$save_category = array();
					foreach ($corp_category_id as $val) {
						$ta_count = count($corp_target_areas);
						$cta_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val);
						$target_area_type = 2; // 個別対応エリア
						if ($ta_count == $cta_count) {
							$jis_cds = array();
							foreach ($corp_target_areas as $corp_target_area) {
								$jis_cds[] = $corp_target_area['MCorpTargetArea']['jis_cd'];
							}
							$default_count = $this->MTargetArea->countHasJisCdsOfCorpCategory($val, $jis_cds);
							if ($ta_count == $default_count) {
								$target_area_type = 1; // 基本対応エリア
							}
						}
						$save_category[] = array(
								'MCorpCategory' => array(
										'id' => $val,
										'target_area_type' => $target_area_type
								)
						);
					}

					if ($this->MCorpCategory->saveAll($save_category)) {
						$this->MCorpTargetArea->commit();
						$this->MCorpCategory->commit();
					} else {
						$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner')
						);
						$this->MCorpTargetArea->rollback();
						$this->MCorpCategory->rollback();
						$result = false;
					}
				}
			}
		}

		return $result;
	}

	// 2017.03.16 izumi.s ORANGE-350 DEL(S)
	///**
	// * GETパラメーターをセッションに保存
	// * @return void
	// */
	//private function _saveConditions() {
	//	$this->Session->delete(self::$__sessionKeyForNoticeInfo);
	//	$this->Session->write(self::$__sessionKeyForNoticeInfo, $this->request->named);
	//}
	//
	///**
	// * インデックスのテンプレートを表示するための処理
	// * （ijdex/serch共通）
	// *
	// * @return void
	// */
	//private function _rendarIndex() {
	//	$itemList = array(
	//			'掲示板番号' => array(
	//					'field' => 'id',
	//					'th_options' => 'style="width:15%;"',
	//			),
	//			'件名' => array('field' => 'info_title'),
	//	);
	//
	//	if (!$this->NoticeInfo->isPoster()) {
	//		$itemList['未読/既読'] = array(
	//				'field' => 'unread',
	//				'th_options' => 'style="width:10%;"',
	//		);
	//	} else {
	//		$itemList['企業取次形態'] = array(
	//				'field' => 'corp_commission_type',
	//				'th_options' => 'style="width:180px;"',
	//		);
	//	}
	//	$itemList['登録日時'] = array(
	//			'field' => 'created',
	//			'th_options' => 'style="width:180px;"',
	//	);
	//
	//	$this->set('itemList', $itemList);
	//
	//	if (Util::isMobile() && !$this->NoticeInfo->isPoster()) {
	//		$this->layout = 'default_m';
	//		$this->render('index_m');
	//	} else {
	//		$this->render('index');
	//	}
	//}
	// 2017.03.16 izumi.s ORANGE-350 DEL(E)
}
