<?php

App::uses('AppController', 'Controller');

/**
 * 加盟店の契約申請を行う
 * 
 * 【基本設計】
 * 各アクションでのPOSTは自分自身に対して行う（一部例外あり）
 */
class AgreementsController extends AppController {

	/**
	 * アップロードファイルの保存先
	 * @todo public staticではなくbootstrap.phpでdifineしてAffiliationController.php側もそちらを参照させる
	 * @var type 
	 */
	public static $file_save_directory = '/var/www/htdocs/rits-files';
	public $name = 'Agreements';
	public $helpers = array();
	public $components = array();
	public $uses = array(
			'AffiliationInfo',
			'Agreement',
			'AgreementProvision',
			'AgreementAttachedFile',
			'CorpAgreement',
			'CorpAgreementTempLink',
			'CorpLisenseLink',
			'Holiday',
			'MArea',
			'MCategory',
			'MCorpCategory',
			'MCorpCategoriesTemp',
			'MCorpTargetArea',
			'MGenre',
			'MItem',
			'MPost',
			'MTargetArea',
			'MTargetAreasTemp',
			'UploadFile',
	);
	private $corp_id;
	private $temp_id;
	private $agreement_id;
	public static $tasks = array(
			array('value' => 'step1', 'name' => '約款の同意', 'prev' => 'step0', 'next' => 'step2',),
			array('value' => 'step2', 'name' => '基本情報入力', 'prev' => 'step1', 'next' => 'step3',),
			array('value' => 'step3', 'name' => 'ジャンル設定', 'prev' => 'step2', 'next' => 'step4',
					'subtask' => array(
							array('value' => 'genre', 'name' => 'ジャンル設定', 'prev' => 'step3', 'next' => 'step3',),
					)
			),
			array('value' => 'step4', 'name' => 'エリア設定', 'prev' => 'step3', 'next' => 'step5',
					'subtask' => array(
							array('value' => 'area_edit', 'name' => '基本エリア設定', 'prev' => 'step4', 'next' => 'genre_area',),
							array('value' => 'area_genre', 'name' => 'ジャンル別エリア一覧', 'prev' => 'area', 'next' => 'category_area',),
							array('value' => 'area_genre_edit', 'name' => 'ジャンル別エリア設定', 'prev' => 'area', 'next' => 'category_area',),
							array('value' => 'area_category', 'name' => 'カテゴリ別エリア一覧', 'prev' => 'genre_area', 'next' => 'step4',),
							array('value' => 'area_category_edit', 'name' => 'カテゴリ別エリア設定', 'prev' => 'genre_area', 'next' => 'step4',),
					)
			),
			array('value' => 'step5', 'name' => '必要書類①', 'prev' => 'step4', 'next' => 'step6',),
			array('value' => 'step6', 'name' => '必要書類②', 'prev' => 'step5', 'next' => 'confirm',),
			array('value' => 'confirm', 'name' => '契約内容の確認', 'prev' => 'step6', 'next' => 'complete',),
			array('value' => 'complete', 'name' => '契約締結完了', 'prev' => false, 'next' => false,),
	);

	public function beforeFilter() {

		parent::beforeFilter();

		if (!$this->loadCommonData() && !in_array($this->action, array('index', 'step0'))) {
			$this->Session->setFlash('長時間操作されなかったため、契約処理を中断しました。お手数ですが再度手続きをお願いいたします。');
			return $this->redirect(array('action' => 'step0'));
		}

		$this->set(array('tasks' => self::$tasks));
		$this->CorpAgreement->corp_id = $this->corp_id;

		$this->MCorp->virtualFields['responsibility_sei'] = "substring(responsibility from '(.+) ' )";
		$this->MCorp->virtualFields['responsibility_mei'] = "substring(responsibility from ' (.+)' )";
		$this->MCorp->recursive = -1;
		$m_corp = $this->MCorp->findById($this->corp_id);

		$corp_agreement = $this->CorpAgreement->findById($this->agreement_id);
		$this->request->data = Hash::merge($m_corp, $corp_agreement, $this->request->data);

		$this->loadValidateErrors();
	}

	/**
	 * 契約画面で契約を要求するポップアップが出るとまずいため処理を奪う
	 * @return boolean 常にFalse
	 */
	protected function _agreement_login() {
		return false;
	}

	/**
	 * 契約中に度々進捗表ダイアログが表示されないようにする
	 * @return boolean 常にFalse
	 */
	protected function _is_show_prog() {
		return false;
	}

	/**
	 * ライセンス有効期限のダイアログが表示されないようにする
	 * @return boolean 常にFalse
	 */
	public function _show_license_follow($user) {
		return false;
	}

	/**
	 * 各アクション共通で利用する値をセッションに格納する
	 */
	private function saveCommonData() {
		//セッションに契約ID、仮契約IDを持って使い回す
		$this->Session->write('CorpAgreement.id', $this->CorpAgreement->id);
		$this->Session->write('CorpAgreementTempLink.id', $this->CorpAgreement->CorpAgreementTempLink->id);
	}

	/**
	 * 各アクション共通で利用する値をメンバ変数に格納する
	 * @return boolean
	 * @throws ApplicationException 加盟店IDが取得できなければ権限エラー(NoReferenceAuthority)
	 */
	private function loadCommonData() {
		$this->corp_id = $this->Auth->user('affiliation_id');
		if (empty($this->corp_id)) {
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}
		$this->agreement_id = $this->Session->read('CorpAgreement.id');
		$this->temp_id = $this->Session->read('CorpAgreementTempLink.id');
		if (empty($this->agreement_id)) {
			return false;
		}
		return true;
	}

	/**
	 * セッションに検証エラー情報を保存する
	 */
	private function saveValidateErrors($model) {
		if (!empty($model->validationErrors)) {
			$this->Session->write('validationErrors.' . $model->name, $model->validationErrors);
		}
	}

	/**
	 * セッションから検証エラー情報を取得する
	 */
	private function loadValidateErrors() {
		if ($this->Session->read('validationErrors')) {
			foreach ($this->Session->read('validationErrors') as $model => $errors) {
				$this->loadModel($model);
				$this->$model->validationErrors = $errors;
			}
			$this->Session->delete('validationErrors');
		}
	}

	public function index() {
		$this->redirect(array('action' => 'step0'));
	}

	/**
	 * 契約履歴の一覧画面
	 * @return type
	 */
	public function step0() {
		if ($this->request->is('post')) {
			$this->CorpAgreement->step0Done($this->request->data);
			$this->saveCommonData();
			return $this->redirect(array('action' => 'step1'));
		}
		$all_agreements = $this->CorpAgreement->find('all', array('fields' => array('status', 'status_jp', 'acceptation_date'), 'conditions' => array('corp_id' => $this->corp_id), 'recursive' => -1, 'order' => array('id' => 'asc')));
		$this->set(compact('all_agreements'));
	}

	/**
	 * 約款表示画面
	 * @return type
	 */
	public function step1() {
		if ($this->request->is('post')) {
			$this->CorpAgreement->step1Done($this->request->data);
			return $this->redirect(array('action' => 'step2'));
		}
		$this->request->data = Hash::merge($this->Agreement->findCustomizedText($this->corp_id), $this->request->data);
	}

	/**
	 * 会社情報入力画面（即座に本番に反映される）
	 * @return type
	 */
	public function step2() {
		if ($this->request->is('post')) {
			if ($this->saveAffiliationInfo($this->convertRequestData($this->request->data)) !== false) {
				$this->CorpAgreement->step2Done($this->request->data);
				return $this->redirect(array('action' => 'step3'));
			}
			// チェック状態を復元
			$holidays = $this->Holiday->find('all', array('conditions' => array('corp_id' => $this->corp_id)));
			$checked = Hash::extract($this->request->data, 'Holiday.checked.{n}');
			foreach ($holidays as &$holiday) {
				$holiday['Holiday']['checked'] = in_array($holiday['Holiday']['item_id'], $checked);
			}
		} else {
			$holidays = $this->Holiday->find('all', array('conditions' => array('corp_id' => $this->corp_id)));
		}
		$this->request->data = Hash::merge(array('Holiday' => Hash::extract($holidays, '{n}.Holiday')), $this->AffiliationInfo->findByCorpId($this->corp_id), $this->request->data);
	}

	/**
	 * ジャンル選択画面
	 * @return type
	 */
	public function step3() {
		if ($this->request->is('post')) {
			$this->CorpAgreement->step3Done($this->request->data);
			return $this->redirect(array('action' => 'step4'));
		}
		$m_corp_category_temp = $this->MCorpCategoriesTemp->find('count', array('conditions' => array('corp_id' => $this->corp_id, 'temp_id' => $this->temp_id)));
		if (!$m_corp_category_temp) {
			// 申請中データが無ければ、現在の本番からコピーして申請中データを作成
			$this->MCorpCategoriesTemp->copyFromMCorpTargetAndMCorpCategory($this->corp_id, $this->temp_id);
		}
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($this->corp_id, $this->temp_id);
		$this->set(compact('m_corp_categories_temp'));
	}

	/**
	 * ジャンルの登録画面
	 * @return type
	 */
	public function genre() {
		if ($this->request->is('post')) {
			if ($this->MCorpCategoriesTemp->saveAllCategories($this->request->data, $this->corp_id, $this->temp_id)) {
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'notice_inner'));
				return $this->redirect(array('action' => 'step3'));
			}
		}
		$m_genre = $this->MGenre->findAllTempCategoriesByCorpId($this->corp_id, $this->temp_id);
		$m_corp_categories_temp = array('MCorpCategoriesTemp' => Hash::extract($m_genre, '{n}.MGenre'));
		$this->set(compact('m_corp_categories_temp'));
	}

	public function step4() {
		if ($this->request->is('post')) {
			$this->CorpAgreement->step4Done($this->request->data);
			return $this->redirect(array('action' => 'step5'));
		}
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($this->corp_id);
		// 基本対応エリア反映状況を取得（STEP3でコピーされているのでtemp側だけで良い）
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($this->corp_id, $this->temp_id);
		$this->set(compact('m_corp_target_area', 'm_corp_categories_temp'));
	}

	/**
	 * 基本対応エリアの設定画面
	 */
	public function area_edit() {

		if ($this->request->is('post')) {
			if ($this->MCorpTargetArea->changeBasicSupportArea($this->corp_id, Hash::get($this->request->data, 'address1_text'), Hash::get($this->request->data, 'jis_cd'), $this->temp_id)) {
				$this->Session->setFlash('基本対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'area_edit'));
		}
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($this->corp_id);
		$this->set(compact('m_corp_target_area'));
	}

	/**
	 * エリア編集用のジャンル一覧
	 */
	public function area_genre() {
		if ($this->request->is('post')) {
			// ジャンル指定のエリアを基本対応エリアにリセット
			if ($this->MTargetAreasTemp->resetAreaGroupByGenreId($this->corp_id, $this->temp_id, Hash::extract($this->request->data, 'MGenre.{n}.id'))) {
				$this->Session->setFlash('基本対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'area_genre'));
		}

		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($this->corp_id);
		// 基本対応エリア反映状況を取得
		$m_corp_categories = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($this->corp_id, $this->temp_id);
		// エリア反映状況はカテゴリ別なので、ジャンル別に再集計する
		$m_corp_genres = array();
		foreach (Hash::extract($m_corp_categories, 'MCorpCategoriesTemp.{n}') as $m_corp_category) {
			if (!array_key_exists($m_corp_category['genre_id'], $m_corp_genres)) {
				$m_corp_genres[$m_corp_category['genre_id']] = array(
						'id' => $m_corp_category['genre_id'],
						'genre_name' => $m_corp_category['genre_name'],
						'target_area_type' => $m_corp_category['target_area_type'],
						'count' => 1,
				);
			} elseif ($m_corp_category['target_area_type'] !== 1) {
				$m_corp_genres[$m_corp_category['genre_id']]['target_area_type'] = $m_corp_category['target_area_type'];
				$m_corp_genres[$m_corp_category['genre_id']]['count'] ++;
			}
		}
		$this->set(compact('m_corp_target_area', 'm_corp_genres'));
	}

	/**
	 * ジャンルひとつのエリア編集画面
	 * @param type $genre_id
	 */
	public function area_genre_edit($genre_id) {
		if ($this->request->is('post')) {
			if ($this->area_genre_edit_post()) {
				$this->Session->setFlash('対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'area_genre_edit', $genre_id));
		}
		// ジャンルの存在確認（とジャンル名の取得を兼ねて）
		$m_genre = $this->MGenre->read(array('id', 'genre_name'), $genre_id);
		if (empty($m_genre)) {
			return $this->redirect(array('action' => 'area_genre'));
		}
		// ジャンルに含まれるカテゴリの取得
		$m_categories = $this->MCategory->find('all', array(
				'fields' => array('DISTINCT MCategory.category_name', 'MCorpCategoriesTemp.id'),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'm_corp_categories_temp',
								'alias' => 'MCorpCategoriesTemp',
								'conditions' => array(
										'MCategory.id = MCorpCategoriesTemp.category_id',
										'MCorpCategoriesTemp.corp_id' => $this->corp_id,
										'MCorpCategoriesTemp.temp_id' => $this->temp_id,
								),
						),
				),
				'conditions' => array(
						'MCorpCategoriesTemp.genre_id' => Hash::get($m_genre, 'MGenre.id'),
		)));
                
                $m_corp_categories_temp_id = Hash::get($m_categories, '0.MCorpCategoriesTemp.id');
                
		// ジャンルの対応エリアの取得
		$m_target_areas_temp = $this->MTargetAreasTemp->findAllAreaCountByGenreId($this->corp_id, $genre_id, $m_corp_categories_temp_id);
		$this->set(compact('m_categories', 'm_genre', 'm_target_areas_temp'));
	}

	/**
	 * ジャンルひとつのエリア編集
	 * @see AgreementsController::target_area($id)
	 */
	private function area_genre_edit_post() {
		$corp_id = $this->corp_id;
		$temp_id = $this->temp_id;
		$category_id_array = Hash::extract($this->MCorpCategoriesTemp->find('all', array(
												'fields' => array('category_id'),
												'conditions' => array(
														'corp_id' => $this->corp_id,
														'temp_id' => $this->temp_id,
														'genre_id' => Hash::get($this->request->data, 'genre_id'),
								))), '{n}.MCorpCategoriesTemp.category_id');
		$select_jis_cd_array = (array) Hash::get($this->request->data, 'jis_cd');
		$ken_cd = Hash::get($this->request->data, 'ken_cd');
		return $this->MTargetAreasTemp->updateAreaGroupByCategory($corp_id, $temp_id, $category_id_array, $ken_cd, $select_jis_cd_array);
	}

	/**
	 * エリア編集用のカテゴリ一覧
	 */
	public function area_category() {
		if ($this->request->is('post')) {
			// 指定カテゴリのエリアを基本対応エリアにリセット
			if ($this->MTargetAreasTemp->resetAreaGroupByCategoryId($this->corp_id, $this->temp_id, Hash::extract($this->request->data, 'MCorpCategoriesTemp.{n}.category_id'))) {
				$this->Session->setFlash('基本対応エリアを適用しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの適用に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'area_category'));
		}
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($this->corp_id);
		// 基本対応エリア反映状況を取得
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($this->corp_id, $this->temp_id);
		$this->set(compact('m_corp_target_area', 'm_corp_categories_temp'));
	}

	/**
	 * カテゴリひとつのエリア編集画面
	 * @param type $corp_category_id
	 */
	public function area_category_edit($corp_category_id) {
		if ($this->request->is('post')) {
			if ($this->area_category_edit_post()) {
				$this->Session->setFlash('対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'area_category_edit', $corp_category_id));
		}
		$this->MCorpCategoriesTemp->bindModel(array('belongsTo' => array(
						'MCategory' => array(
								'fields' => array('category_name'),
								'className' => 'MCategory',
								'foreignKey' => 'category_id',
						)
		)));
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->find('first', array('conditions' => array('MCorpCategoriesTemp.id' => $corp_category_id), 'recursive' => 0));
		if (empty($m_corp_categories_temp)) {
			$this->redirect(array('action' => 'area_category'));
		}
		$m_target_areas_temp = $this->MTargetAreasTemp->findAllAreaCountByCorpCategoryId($corp_category_id);
		$this->set(compact('m_target_areas_temp', 'm_corp_categories_temp'));
	}

	/**
	 * カテゴリひとつのエリア編集
	 */
	private function area_category_edit_post() {
		$corp_id = $this->corp_id;
		$temp_id = $this->temp_id;
		$category_id_array = (array) Hash::get($this->request->data, 'category_id');
		$select_jis_cd_array = (array) Hash::get($this->request->data, 'jis_cd');
		$ken_cd = Hash::get($this->request->data, 'ken_cd');
		return $this->MTargetAreasTemp->updateAreaGroupByCategory($corp_id, $temp_id, $category_id_array, $ken_cd, $select_jis_cd_array);
	}

	public function step5() {
		if ($this->request->is('post')) {
			if (0 < $this->AgreementAttachedFile->find('count', array('conditions' => array('corp_id' => $this->corp_id, 'kind' => 'Cert', 'delete_flag' => false)))) {
				$this->CorpAgreement->step5Done($this->request->data);
				return $this->redirect(array('action' => 'step6'));
			} else {
				$this->Session->setFlash('書類がアップロードされていません。', 'default', array('class' => 'error_inner'));
			}
		}
		$recent_acceptation_date = $this->CorpAgreement->getLastAcceptationDateByCorpId($this->corp_id);
		$attached_files = $this->AgreementAttachedFile->find('all', array('conditions' => array('corp_id' => $this->corp_id, 'kind' => 'Cert', 'delete_flag' => false), 'order' => array('id' => 'asc')));
		$this->set(compact('attached_files', 'recent_acceptation_date'));
	}

	/**
	 * 必要書類ファイルのアップロード
	 * @return type
	 * @throws Exception
	 */
	public function upload1() {

		if (!$this->request->is('post')) {
			return $this->redirect(array('action' => 'step5'));
		}

		if (!$this->isValidUploadFile($this->request->data)) {
			$this->Session->setFlash('ファイルのアップロードに失敗しました。', 'default', array('class' => 'error_inner'));
			return $this->redirect(array('action' => 'step5'));
		}

		$original_file_name = Hash::get($this->data, 'UploadFile.file.name');
		$original_file_extension = substr($original_file_name, strrpos($original_file_name, '.') + 1);

		$attached_file = $this->AgreementAttachedFile->create();
		$attached_file['AgreementAttachedFile']['corp_id'] = $this->corp_id;
		$attached_file['AgreementAttachedFile']['corp_agreement_id'] = $this->agreement_id;
		$attached_file['AgreementAttachedFile']['kind'] = 'Cert';
		$attached_file['AgreementAttachedFile']['name'] = $original_file_name;
		$attached_file['AgreementAttachedFile']['content_type'] = Hash::get($this->data, 'UploadFile.file.type');
		$attached_file['AgreementAttachedFile']['temp_flag'] = true;
		$attached_file['AgreementAttachedFile']['version_no'] = 1;

		try {
			$this->AgreementAttachedFile->begin();

			$attached_file = $this->AgreementAttachedFile->save($attached_file);

			if (!$attached_file) {
				throw new Exception('データの保存に失敗しました。');
			}

			App::uses('Folder', 'Utility');
			$new_file_folder = self::$file_save_directory . DS . $this->corp_id;
			$this->Folder = new Folder();
			$this->Folder->create($new_file_folder);
			$id = $this->AgreementAttachedFile->getLastInsertID();
			$old_file_fullpathe = Hash::get($this->data, 'UploadFile.file.tmp_name');
			$new_file_fullpath = $this->Folder->slashTerm($new_file_folder) . $id . '.' . $original_file_extension;

			if (!move_uploaded_file($old_file_fullpathe, $new_file_fullpath)) {
				throw new Exception('データの保存に失敗しました。');
			}

			$attached_file['AgreementAttachedFile']['path'] = $new_file_fullpath;

			if (!$this->AgreementAttachedFile->save($attached_file)) {
				throw new Exception('データの保存に失敗しました。');
			}

			$this->AgreementAttachedFile->commit();
			$this->Session->setFlash('ファイルをアップロードしました。', 'default', array('class' => 'notice_inner'));
		} catch (Exception $ex) {
			$this->AgreementAttachedFile->rollback();
			$this->Session->setFlash($ex->getMessage(), 'default', array('class' => 'error_inner'));
		}
		return $this->redirect(array('action' => 'step5'));
	}

	/**
	 * ファイルを削除する（証明書）
	 * @return type
	 * @throws ApplicationException
	 */
	public function detach1() {

		if (!$this->request->is('post')) {
			return $this->redirect(array('action' => 'step5'));
		}

		if ($this->agreementFileDetach($this->request->data)) {
			$this->Session->setFlash('ファイルを削除しました。', 'default', array('class' => 'notice_inner'));
		} else {
			$this->Session->setFlash('ファイルの削除に失敗しました。', 'default', array('class' => 'error_inner'));
		}

		return $this->redirect(array('action' => 'step5'));
	}

	public function step6() {
		if ($this->request->is('post')) {
			if (!$this->saveCorpLicenseLink($this->request->data)) {
				$this->Session->setFlash('各ライセンスについて「持っている」か「持っていない」を選択してください。', 'default', array('class' => 'error_inner'));
			} else {
				$this->CorpAgreement->step6Done($this->request->data);
				return $this->redirect(array('action' => 'confirm'));
			}
		}
		$recent_acceptation_date = $this->CorpAgreement->getLastAcceptationDateByCorpId($this->corp_id);
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllRequiredLicensesByTempId($this->temp_id);
		$held_license = $this->MCorpCategoriesTemp->findAllHeldLicenseByTempId($this->temp_id);
		$this->request->data = Hash::merge($held_license, $this->request->data);
		$this->set(compact('m_corp_categories_temp', 'recent_acceptation_date'));
	}

	public function upload2() {

		if (!$this->request->is('post')) {
			return $this->redirect(array('action' => 'step6'));
		}

		if (!$this->isValidUploadFile($this->request->data)) {
			$this->Session->setFlash('ファイルのアップロードに失敗しました。', 'default', array('class' => 'error_inner'));
			return $this->redirect(array('action' => 'step6'));
		}

		$original_file_name = Hash::get($this->data, 'UploadFile.file.name');
		$original_file_extension = substr($original_file_name, strrpos($original_file_name, '.') + 1);

		$attached_file = $this->AgreementAttachedFile->create();
		$attached_file['AgreementAttachedFile']['corp_id'] = $this->corp_id;
		$attached_file['AgreementAttachedFile']['license_id'] = Hash::get($this->request->data, 'UploadFile.license_id');
		$attached_file['AgreementAttachedFile']['corp_agreement_id'] = $this->agreement_id;
		$attached_file['AgreementAttachedFile']['kind'] = 'License';
		$attached_file['AgreementAttachedFile']['name'] = $original_file_name;
		$attached_file['AgreementAttachedFile']['content_type'] = Hash::get($this->request->data, 'UploadFile.file.type');
		$attached_file['AgreementAttachedFile']['temp_flag'] = true;
		$attached_file['AgreementAttachedFile']['version_no'] = 1;

		try {
			$this->AgreementAttachedFile->begin();

			if (!$this->saveCorpLicenseLink($this->request->data,false,Hash::get($this->request->data, 'UploadFile.license_id'))) {
				// ライセンスリンクは保存に失敗してもよい
			}

			$attached_file = $this->AgreementAttachedFile->save($attached_file);

			if (!$attached_file) {
				throw new Exception('データの保存に失敗しました。');
			}

			App::uses('Folder', 'Utility');
			$new_file_folder = self::$file_save_directory . DS . $this->corp_id;
			$this->Folder = new Folder();
			$this->Folder->create($new_file_folder);
			$id = $this->AgreementAttachedFile->getLastInsertID();
			$old_file_fullpathe = Hash::get($this->data, 'UploadFile.file.tmp_name');
			$new_file_fullpath = $this->Folder->slashTerm($new_file_folder) . $id . '.' . $original_file_extension;

			if (!move_uploaded_file($old_file_fullpathe, $new_file_fullpath)) {
				throw new Exception('データの保存に失敗しました。');
			}

			$attached_file['AgreementAttachedFile']['path'] = $new_file_fullpath;

			if (!$this->AgreementAttachedFile->save($attached_file)) {
				throw new Exception('データの保存に失敗しました。');
			}

			$this->AgreementAttachedFile->commit();
			$this->Session->setFlash('ファイルをアップロードしました。', 'default', array('class' => 'notice_inner'));
		} catch (Exception $ex) {
			$this->AgreementAttachedFile->rollback();
			$this->Session->setFlash($ex->getMessage(), 'default', array('class' => 'error_inner'));
		}
		return $this->redirect(array('action' => 'step6'));
	}

	/**
	 * ファイルを削除する（ライセンス）
	 * @return type
	 * @throws ApplicationException
	 */
	public function detach2() {

		if (!$this->request->is('post')) {
			return $this->redirect(array('action' => 'step6'));
		}

		if ($this->agreementFileDetach($this->request->data)) {
			$this->Session->setFlash('ファイルを削除しました。', 'default', array('class' => 'notice_inner'));
		} else {
			$this->Session->setFlash('ファイルの削除に失敗しました。', 'default', array('class' => 'error_inner'));
		}

		return $this->redirect(array('action' => 'step6'));
	}

	/**
	 * アップロードファイルをプレビューする
	 * ユーザーが加盟店の場合は自所属のファイルしか参照できない
	 * @param type $id AgreementAttachedFile.id
	 * @return type
	 * @throws NotFoundException ファイルが見つからない・権限が無くて参照できない場合
	 */
	public function file_preview($id = null) {
		$this->autoRender = false;
		$conditions = array('id' => $id);
		if ($this->User['auth'] == 'affiliation') {
			$conditions['corp_id'] = $this->User['affiliation_id'];
		}
		$attached_file = $this->AgreementAttachedFile->find('first', compact('conditions'));
		if ($attached_file && file_exists(Hash::get($attached_file, 'AgreementAttachedFile.path'))) {
			$this->response->type(Hash::get($attached_file, 'AgreementAttachedFile.content_type'));
			$this->response->file(Hash::get($attached_file, 'AgreementAttachedFile.path'), array('name' => Hash::get($attached_file, 'AgreementAttachedFile.path', $id), 'download' => false,));
			return;
		}
		throw New NotFoundException();
	}

	/**
	 * アップロードファイルの正常性を検査する
	 * @return boolean true:正常 false:異常
	 */
	private function isValidUploadFile($request_data) {
		$this->UploadFile->set($request_data);
		if ($this->UploadFile->validates(array('fieldList' => array('file')))) {
			return true;
		} else {
			$this->saveValidateErrors($this->UploadFile);
			return false;
		}
	}

	public function confirm() {
		if ($this->request->is('post')) {
			$this->CorpAgreement->confirmDone($this->request->data);
			return $this->redirect(array('action' => 'complete'));
		}

		$agreement = Hash::merge($this->Agreement->findCustomizedText($this->corp_id), $this->request->data);
		$holidays = $this->Holiday->find('all', array('conditions' => array('corp_id' => $this->corp_id)));
		$affiliation_info = $this->AffiliationInfo->findByCorpId($this->corp_id);
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($this->corp_id);
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($this->corp_id, $this->temp_id);
		$recent_acceptation_date = $this->CorpAgreement->getLastAcceptationDateByCorpId($this->corp_id);
		$attached_files = $this->AgreementAttachedFile->find('all', array('conditions' => array('corp_id' => $this->corp_id, 'delete_flag' => false), 'order' => array('id' => 'asc')));
		$held_license = $this->MCorpCategoriesTemp->findAllHeldLicenseByTempId($this->temp_id);
		$m_corp = array('MCorp' => $this->request->data('MCorp'));

		$this->set(compact('agreement', 'm_corp', 'holidays', 'affiliation_info', 'm_corp_target_area', 'm_corp_categories_temp', 'recent_acceptation_date', 'held_license', 'attached_files'));
	}

	public function complete() {
		
	}

	/**
	 * アップロード済みのファイルを削除する
	 * @param type $request_data
	 * @return boolean
	 * @throws ApplicationException
	 */
	private function agreementFileDetach($request_data) {
                
		$conditions = array(
				'id' => Hash::get($request_data, 'AgreementAttachedFile.id'),
				'corp_id' => $this->corp_id,
				'create_date > ' => $this->CorpAgreement->getLastAcceptationDateByCorpId($this->corp_id),
		);
		$attached_file = $this->AgreementAttachedFile->find('first', compact('conditions'));
		if (!$attached_file) {
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}
                
                //corp_lisense_linkの検索
                $corp_lisense_link = array();
                $license_id = Hash::get($attached_file, 'AgreementAttachedFile.license_id');
                if(!empty($license_id)){
                    $corp_lisense_Link_id = $this->CorpLisenseLink->find('all', array(
                            'conditions' => array(
                                'corps_id' => $this->corp_id,
                                'lisense_id' => $license_id,
                            ),
                        )
                    );
                    
                    //初期化用の保存内容を作成
                    $corp_lisense_link['id'] = Hash::get($corp_lisense_Link_id, '0.CorpLisenseLink.id');
                    $corp_lisense_link['lisense_check'] = 'None';
                }
                
		$file_path = Hash::get($attached_file, 'AgreementAttachedFile.path');

		$this->AgreementAttachedFile->begin();
		if (!$this->AgreementAttachedFile->delete(Hash::get($attached_file, 'AgreementAttachedFile.id'))) {
			$this->AgreementAttachedFile->rollback();
			return false;
		}

		if (file_exists($file_path) && !unlink($file_path)) {
			// ファイルがないレコードが削除できなくならないよう、ファイルが存在するが削除できなかった場合のみ失敗とする。
			$this->AgreementAttachedFile->rollback();
			return false;
		}

		$this->AgreementAttachedFile->commit();
                
                //ライセンスの初期化
                if(!empty($corp_lisense_link)){
                    if(!$this->CorpLisenseLink->save($corp_lisense_link)){
			return false;
                    }
                }
                
		return true;
	}

	private function saveCorpLicenseLink($data, $ignore_errors = false, $reset_license_no = null) {
		$has_error = array();
		$corp_lisense_links = Hash::extract($data, 'CorpLisenseLink.{n}');
		if (empty($corp_lisense_links)) {
			return true;
		}
		$existing_corp_lisense_links = $this->CorpLisenseLink->find('all', array('fields' => array('lisense_id', 'version_no', 'id'), 'conditions' => array('corps_id' => $this->corp_id)));
		$existing_corp_lisense_links = Hash::combine($existing_corp_lisense_links, '{n}.CorpLisenseLink.lisense_id', '{n}.CorpLisenseLink');
		foreach ($corp_lisense_links as &$corp_lisense_link) {
			$corp_lisense_link['id'] = Hash::get($existing_corp_lisense_links, $corp_lisense_link['lisense_id'] . '.id');
			$corp_lisense_link['corps_id'] = $this->corp_id;
			$corp_lisense_link['version_no'] = Hash::get($existing_corp_lisense_links, $corp_lisense_link['lisense_id'] . '.version_no', 0) + 1;
                        if($reset_license_no == $corp_lisense_link['lisense_id']){
			$corp_lisense_link['lisense_check'] = 'None';
                        }
			if ($ignore_errors) {
				// エラー無視なら１件ずつ登録する
				$has_error[] = $this->CorpLisenseLink->save($corp_lisense_link);
			}
		}
		if (!$ignore_errors) {
			$has_error[] = $this->CorpLisenseLink->saveMany($corp_lisense_links);
			$this->saveValidateErrors($this->CorpLisenseLink);
		}
		return !in_array(false, $has_error);
	}

	private function saveAffiliationInfo($data) {
		$this->CorpAgreement->begin();
		if (Hash::get($data, 'MCorp.corp_kind') === 'Corp') {
			$this->AffiliationInfo->validate['capital_stock'] = array('NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => false
			));
		}
		$this->AffiliationInfo->validate['employees'] = array('NoNumeric' => array(
						'rule' => array('numeric'),
						'allowEmpty' => false
		));

		if ($this->MCorp->save($data['MCorp']) === false) {
			$this->CorpAgreement->rollback();
			return false;
		}
		if ($this->AffiliationInfo->save($data['AffiliationInfo']) === false) {
			$this->CorpAgreement->rollback();
			return false;
		}
		if ($this->Holiday->saveAll($data['Holiday']) === false) {
			$this->CorpAgreement->rollback();
			return false;
		}
		$this->CorpAgreement->commit();
		return true;
	}

	private function convertRequestData($data) {
		$data['MCorp']['responsibility'] = Hash::get($data, 'MCorp.responsibility_sei') . ' ' . Hash::get($data, 'MCorp.responsibility_mei');
		$holidays = $this->Holiday->find('all', array('conditions' => array('corp_id' => $this->corp_id)));
		$checked = $data['Holiday']['checked'];
		$data['Holiday'] = Hash::map($holidays, '{n}.Holiday', function($holiday) use ($checked) {
							return array('checked' => (in_array(Hash::get($holiday, 'item_id'), (array) $checked))) + $holiday;
						});
		return $data;
	}

	private function stepCheck($current_step) {
		$status_list = $this->CorpAgreement->status_list;
		foreach ($status_list as $status) {
			if ($status_list) {
				
			}
		}
	}

	public function searchTargetAreaTemp($genre_id = null, $address1 = null) {
		if (!$this->request->isAll(array('get'))) {
			throw new BadRequestException();
		}
		if (is_numeric($address1)) {
			$address1 = Configure::read("STATE_LIST.{$address1}");
		}
		try {
                    // tempid取得
                    $m_categories = $this->MCategory->find('all', array(
                                    'fields' => array('DISTINCT MCategory.category_name', 'MCorpCategoriesTemp.id'),
                                    'joins' => array(
                                                    array(
                                                                    'type' => 'INNER',
                                                                    'table' => 'm_corp_categories_temp',
                                                                    'alias' => 'MCorpCategoriesTemp',
                                                                    'conditions' => array(
                                                                                    'MCategory.id = MCorpCategoriesTemp.category_id',
                                                                                    'MCorpCategoriesTemp.corp_id' => $this->corp_id,
                                                                                    'MCorpCategoriesTemp.temp_id' => $this->temp_id,
                                                                    ),
                                                    ),
                                    ),
                                    'conditions' => array(
                                        'MCorpCategoriesTemp.genre_id' => $genre_id,
                                    )
                        ));
                        $m_corp_categories_temp_id = Hash::get($m_categories, '0.MCorpCategoriesTemp.id');
                    
			$this->autoRender = false;
			$this->response->type('html');
			$results = $this->MPost->find('all', array(
					'fields' => array('MPost.address2', 'MPost.jis_cd', 'max(MTargetArea.id) as "MTargetArea__id"'),
					'joins' => array(
							array('fields' => array('id'),
									'type' => 'LEFT',
									'table' => 'm_target_areas_temp',
									'alias' => 'MTargetArea',
									'conditions' => array('MTargetArea.jis_cd = MPost.jis_cd', 'MTargetArea.genre_id' => $genre_id, 'MTargetArea.corp_category_id' => $m_corp_categories_temp_id)
							),
					),
					'conditions' => array('MPost.address1' => $address1),
					'group' => array('MPost.jis_cd', 'MPost.address2'),
					'order' => array('MPost.jis_cd' => 'asc'),
			));
			$this->set("list", $results);
			$this->render('/Ajax/target_area', false);
		} catch (Exception $e) {
			$this->response->statusCode(500);
		}
	}

	public function searchTargetAreaTempByCorpCategoryId($corp_category_id = null, $address1 = null) {
		if (!$this->request->isAll(array('get', 'ajax'))) {
			throw new BadRequestException();
		}
		if (is_numeric($address1)) {
			$address1 = Configure::read("STATE_LIST.{$address1}");
		}
		try {
			$this->autoRender = false;
			$this->response->type('html');
			$results = $this->MPost->find('all', array(
					'fields' => array('MPost.address2', 'MPost.jis_cd', 'max(MTargetArea.id) as "MTargetArea__id"'),
					'joins' => array(
							array('fields' => array('id'),
									'type' => 'LEFT',
									'table' => 'm_target_areas_temp',
									'alias' => 'MTargetArea',
									'conditions' => array('MTargetArea.jis_cd = MPost.jis_cd', 'MTargetArea.corp_category_id' => $corp_category_id)
							),
					),
					'conditions' => array('MPost.address1' => $address1),
					'group' => array('MPost.jis_cd', 'MPost.address2'),
					'order' => array('MPost.jis_cd' => 'asc'),
			));
			$this->set("list", $results);
			$this->render('/Ajax/target_area', false);
		} catch (Exception $e) {
			$this->response->statusCode(500);
		}
	}

}
