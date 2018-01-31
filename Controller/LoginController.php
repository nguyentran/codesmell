<?php
App::uses('AppController', 'Controller');

class LoginController extends AppController {

	public $name = 'Login';
	public $helpers = array();
	public $components = array();

	// 2016.02.16 ORANGE-1247 k.iwai CHG(S).
// 2016.07.28 murata.s ORANGE-52 CHG(S)
// 	public $uses = array('MUser','MCorp','CorpAgreement', 'AffiliationInfo', 'CommissionInfo');
	public $uses = array('MUser','MCorp','CorpAgreement', 'AffiliationInfo', 'CommissionInfo', 'NoticeInfo');
// 2016.07.28 murata.s ORANGE-52 CHG(E)
	// 2016.02.16 ORANGE-1247 k.iwai CHG(S).

	//ログインが必要な処理か？
	//public $is_auth_page = true;

	public function beforeFilter() {
		parent::beforeFilter();
	}

// sasaki@tobila 2016.04.19 DEL Start ORANGE-1371
// 	public function confirm(){
// 		if (isset ( $this->request->data ['action'] ) && $this->request->data ['action'] = "cancel") {
// 			return $this->redirect('/top');
// 		}
// 		$user = $this->Auth->user();
// 		$agreement_cancel = time() < strtotime(Configure::read('agreement_grace_date'));
// 		$this->set ("agreement_grace_date", Configure::read('agreement_grace_date'));
// 		$this->set ("agreement_cancel", $agreement_cancel );
// 		$this->set ("agreement_login_url", Configure::read('agreement_login') .$user['id']);
// 		$this->set ("cookie_id", $_COOKIE["orange-single-sign-on"]);
// 	}
// sasaki@tobila 2016.04.19 DEL End ORANGE-1371

	public function index() {

		$data = $this->params->query;
if (isset($data['user_id'])){
//			$data = $this->params->query;

$this->log($_SERVER['REQUEST_URI'], LOG_DEBUG); // h.hanaki 2016.01.27

			$userid    = $data['user_id'];
			$pass    = $data['password'];

			//          m_usersテーブルの検索
			$user_info = $this->MUser->find('first',array('conditions' => array('MUser.user_id' => $userid)));

			if ($user_info){
				$user = $user_info['MUser'];

				if ($user['password']==$pass){

					// murata.s ORANGE-430 ADD(S)
					// 最終ログイン日時の設定
					$this->MUser->updateLastLoginDate($user['id']);
					// murata.s ORANGE-430 ADD(E)

					// murata.s ORANGE-371 CHG(S)
					if(empty($this->User) || $this->User['user_id'] != $userid){
						$this->Session->renew();
						$this->Session->write(AuthComponent::$sessionKey, $user);
					}
					// murata.s ORANGE-371 CHG(E)
//					$url = '/demand/cti/%s/%s';
					$url = '/demand_list?cti=1&customer_tel=%s&site_tel=%s';
					return $this->redirect(sprintf($url, $data['tel_no'], $data['dialin_no']));


			}else{

//				print('変数user_idはテーブルにない<br><br>');
				$this->Session->setFlash('CTI連携でログインIDまたはパスワードに誤りがあります。1', 'default', array('class' => 'error-message'));


			}
			}else{

//				print('変数user_idはテーブルにない<br><br>');
				$this->Session->setFlash('CTI連携でログインIDまたはパスワードに誤りがあります。2', 'default', array('class' => 'error-message'));


			}


		}else{

// 2016.10.20 murata.s ORANGE-209 CHG(S)
// 			if ($this->Auth->user()) {
// 				// ログインしていれば、リダイレクト 2015.09.05 tabaka
// 				return $this->redirect($this->Auth->redirectUrl());
// 			} elseif(!empty($this->request->data)) {
// 				if($this->Auth->login()) {
// 					//ログイン成功
// 					$user = $this->Auth->user();

// 					// sasaki@tobila 2016.04.19 DEL Start ORANGE-1371
//  					//if ($this->_agreement_login()){
//   					//	return $this->redirect('/login/confirm');
//  					//}
// 					// sasaki@tobila 2016.04.19 DEL End ORANGE-1371

// 					// 2016.02.29 ORANGE-1247 k.iwai ADD(S).
// 					$this->_check_credit_limit();
// 					// 2016.02.29 ORANGE-1247 k.iwai ADD(E).

// // 2016.07.28 murata.s ORANGE-52 ADD(S)
// 					$this->_check_noticeinfo_unread();
// // 2016.07.28 murata.s ORANGE-52 ADD(E)

// 					return $this->redirect($this->Auth->redirectUrl());
// 				} else {
// 					$this->Session->setFlash('ログインIDまたはパスワードに誤りがあります。', 'default', array('class' => 'error-message text-danger'));
// 				}
// 			}
			if ($this->Auth->user()) {
				// ログインしていれば、リダイレクト 2015.09.05 tabaka
				return $this->redirect($this->Auth->redirectUrl());
			}else{
				if(!empty($this->request->data)){
					// ログイン処理
					if(!$this->__auth_login()){
						$this->request->data['MUser']['password'] = null;
					}
				}else if($this->Cookie->check('orange_remember_check')){
					$remember_check = $this->Cookie->read('orange_remember_check');
					$remember_id = $this->Cookie->read('orange_remember_id');
					$remember_pass = $this->Cookie->read('orange_remember_pass');
					//ORANGE-314 ADD S
					if($this->Cookie->check('orange_guideline')){
						$guideline = $this->Cookie->read('orange_guideline');
						$this->request->data['MUser']['guideline'] = $guideline;
					}
					//ONRAGE_314 ADD E

					$this->request->data['MUser']['user_id'] = $this->Cookie->read('orange_remember_id');
					$this->request->data['MUser']['password'] = $this->Cookie->read('orange_remember_pass');
					$this->request->data['MUser']['remember_check'] = true;

				}
			}
		}
// 2016.10.20 murata.s ORANGE-209 CHG(E)

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()) {
			$this->layout = 'default_m';
			$this->render('index_m');
		}
	}

	/**
	 * ログアウト
	 *
	 * @author tanaka
	 * @version 2015.09.05
	 */
	public function logout() {
		return $this->redirect($this->Auth->logout());
	}

    /**
     * アプリ側のログアウト
     * @author SAT
     * @version 2015.11.20
     */
	public function app_logout() {
		$this->Auth->logout();
		if(!empty($this->request->data)){
			if($this->Auth->login()) {

				//ログイン成功
				$user = $this->Auth->user();

				// murata.s ORANGE-430 ADD(S)
				// 最終ログイン日時の設定
				$this->MUser->updateLastLoginDate($user['id']);
				// murata.s ORANGE-430 ADD(E)

				if ($this->_agreement_login()){
					return $this->redirect('/login/confirm');
				}

				return $this->redirect($this->Auth->redirectUrl());
			}
			else{
				$this->Session->setFlash('ログインIDまたはパスワードに誤りがあります。', 'default', array('class' => 'error-message text-danger'));
			}
		}

		$this->layout = 'default_m';
		$this->render('app_index');
	}


// sasaki@tobila 2016.04.19 DEL Start ORANGE-1371
// 	private function _agreement_login(){
// 		$user = $this->Auth->user();
//     // 加盟店以外の場合
//     if($user['auth'] != 'affiliation'){
//       return false;
//     }
//     // 2016.04.06 ORANGE-1341 k.iwai CHG(S).
// 		// bootstrap add config
// 		$corp_agreement = $this->CorpAgreement->find('first', array ('fields' => '*',
// 			'conditions' => array (
// 					'CorpAgreement.corp_id' => $user['affiliation_id']
// 			),
// 			'order' => array (
// 					'CorpAgreement.id' => 'desc'
// 			)
// 		));
// 	// 2016.04.06 ORANGE-1341 k.iwai CHG(E).

// 		// 2016.03.07 ORANGE-1295 k.iwai CHG(S).
// 		// 締結済の場合
// 		if( $corp_agreement && ($corp_agreement['CorpAgreement']['status']=="Complete" || $corp_agreement['CorpAgreement']['status']=="Application")){
// 			return false;
// 		}
// 		// 2016.03.07 ORANGE-1295 k.iwai CHG(E).

// 		// 未締結の場合Cookieの発効
// 		$cookie_id = md5(uniqid(mt_rand(), true));
// 		setcookie('orange-single-sign-on',$cookie_id,time()+60*60*24*90);
// 		$data['MUser']['id'] = $user['id'];
// 		$data['MUser']['cookie_id'] = $cookie_id;
// 		$this->MUser->set($data);
// 		// エラーチェック
// 		if (!$this->MUser->validates()) {
// 			return;
// 		}
// 		$this->MUser->begin();
// 		if (!$this->MUser->save($data, false)) {
// 			// 登録失敗
// 			$this->MUser->rollback();
// 			return;
// 		}
// 		$this->MUser->commit();
// 		return true;
// 	}
// sasaki@tobila 2016.04.19 DEL End ORANGE-1371

    // 2016.02.29 ORANGE-1247 k.iwai ADD(S).
    private function _check_credit_limit(){
    	//Session初期化
    	if($this->Session->check(SESSION_CREDIT_WARNING)){
    		$this->Session->delete(SESSION_CREDIT_WARNING);
    	}
    	if($this->Session->check(SESSION_CREDIT_DANGER)){
    		$this->Session->delete(SESSION_CREDIT_DANGER);
    	}

    	if($this->Auth->user()){
    		$user = $this->Auth->user();
    		if($user['auth'] == 'affiliation'){
    			$affiliation_info = $this->AffiliationInfo->find('first', array('conditions' => array('AffiliationInfo.corp_id' => $user['affiliation_id'])));

    			//$result = $this->CommissionInfo->checkCreditSumPrice($affiliation_info['AffiliationInfo']['corp_id']);

    			/*
    			switch($result){
    				case CREDIT_WARNING:

    					$this->Session->write(SESSION_CREDIT_WARNING,
    					array(
    					'use' => $this->CommissionInfo->checkCreditSumPrice($affiliation_info['AffiliationInfo']['corp_id'], null, true),
    					'limit' => $affiliation_info['AffiliationInfo']['credit_limit'])
    					);
    					break;
    				case CREDIT_DANGER:
    					$this->Session->write(SESSION_CREDIT_DANGER, $affiliation_info['AffiliationInfo']['credit_limit']);
    					break;
    				default:
    					break;
    			}
    			*/

    			//2016.05.30 iwai メールフラグで処理分岐　仕様変更
    			switch($affiliation_info['AffiliationInfo']['credit_mail_send_flg']){
    				case 1:
    					$this->Session->write(SESSION_CREDIT_WARNING,
    					array(
    					'use' => $this->CommissionInfo->checkCreditSumPrice($affiliation_info['AffiliationInfo']['corp_id'], null, true),
    					'limit' => (int)$affiliation_info['AffiliationInfo']['credit_limit'] + (int)$affiliation_info['AffiliationInfo']['add_month_credit'])
    					);
    					break;
    				case 2:
    					$this->Session->write(SESSION_CREDIT_DANGER, (int)$affiliation_info['AffiliationInfo']['credit_limit'] + (int)$affiliation_info['AffiliationInfo']['add_month_credit']);
    					break;
    				default:
    					break;
    			}
    		}
    	}
    }
    // 2016.02.29 ORANGE-1247 k.iwai ADD(E).

// 2016.07.28 murata.s ORANGE-52 ADD(S)
	/**
	 * 未読の掲示板があるかどうかチェックする
	 */
	public function _check_noticeinfo_unread() {
		// Sessionの初期化
        // 2017.03.15 izumi.s ORANGE-350 CHG(S)
		if ($this->Session->check(SESSION_NOTICEINFO_UNREAD)) {
			$this->Session->delete(SESSION_NOTICEINFO_UNREAD);
		}

		if ($this->Auth->user()) {
			$user = $this->Auth->user();
			if ($user['auth'] == 'affiliation') {
				// 2017.04.03 SHTPART-10 ADD S
				$this->NoticeInfo->setUser($user);
				// 2017.04.03 SHTPART-10 ADD E
				$count = $this->NoticeInfo->countUnreadByCorpId($user['affiliation_id']);
				if ($count > 0)
					$this->Session->write(SESSION_NOTICEINFO_UNREAD, $count);
			}
		}
        // 2017.03.15 izumi.s ORANGE-350 CHG(E)
	}
// 2016.07.28 murata.s ORANGE-52 ADD(E)

// 2016.10.20 murata.s ORANGE-209 ADD(S)
	private function __auth_login(){
		if($this->Auth->login()){
			//ログイン成功
			$user = $this->Auth->user();
			// 2016.10.20 murata.s ORANGE-209 ADD(S)
			if(isset($this->request->data['MUser']['remember_check'])){
				$remember_id = $this->request->data['MUser']['user_id'];
				$remember_pass = $this->request->data['MUser']['password'];
				$remember_check = $this->request->data['MUser']['remember_check'];

				$this->Cookie->write('orange_remember_id', $remember_id, true, '30 Days');
				$this->Cookie->write('orange_remember_pass', $remember_pass, true, '30 Days');
				$this->Cookie->write('orange_remember_check', 'checked', false, '30 Days');
				//ORANGE-314 ADD S
				if(!empty($this->request->data['MUser']['guideline'])){
					$guideline = $this->request->data['MUser']['guideline'];
					$this->Cookie->write('orange_guideline', $guideline, false, '30 Days');
				}
				//ORANGE-314 ADD E
			}else{

				$this->Cookie->delete('orange_remember_id');
				$this->Cookie->delete('orange_remember_pass');
				$this->Cookie->delete('orange_remember_check');
				//ORANGE-314 ADD S
				if($this->Cookie->check('orange_guideline'))$this->Cookie->delete('orange_guideline');
				//ORANGE-314 ADD E
			}
			// 2016.10.20 murata.s ORANGE-209 ADD(E)

			//ORANGE-314 ADD S
			if(!$this->__check_guideline()){
				$this->Session->setFlash('利用規約へ同意いただけないとサービスをご利用いただけません。', 'default', array('class' => 'error-message text-danger'));
				return $this->redirect($this->Auth->logout());
			}
			//ORAAGE-314 ADD E

			// 2016.02.29 ORANGE-1247 k.iwai ADD(S).
			$this->_check_credit_limit();
			// 2016.02.29 ORANGE-1247 k.iwai ADD(E).

// 2016.07.28 murata.s ORANGE-52 ADD(S)
			$this->_check_noticeinfo_unread();
// 2016.07.28 murata.s ORANGE-52 ADD(E)

			// murata.s ORANGE-430 ADD(S)
			// 最終ログイン日時の設定
			$this->MUser->updateLastLoginDate($user['id']);
			// murata.s ORANGE-430 ADD(E)

			return $this->redirect($this->Auth->redirectUrl());
		}else{
			$this->Session->setFlash('ログインIDまたはパスワードに誤りがあります。', 'default', array('class' => 'error-message text-danger'));
			return false;
		}
	}
// 2016.10.20 murata.s ORANGE-209  ADD(E)

	//ORANGE-314 ADD S
	private function __check_guideline(){
		if($this->Auth->login()){

			$user = $this->Auth->user();

			if($user['auth'] == 'affiliation'){

				$affiliation = $this->MCorp->findById($user['affiliation_id']);

				//Form入力なし
				if(empty($this->request->data['MUser']['guideline'])){
					//過去に利用規約確認無し OR 利用規約確認古い
					if(empty($affiliation['MCorp']['guideline_check_date']) || (!empty($affiliation['MCorp']['guideline_check_date']) && strtotime($affiliation['MCorp']['guideline_check_date']) < strtotime(GUIDELINE_DATE))){
						return false;
					}else{
						return true;
					}
				}
				//過去に利用規約確認無し AND 今回利用規約チェックなし
				if(empty($affiliation['MCorp']['guideline_check_date']) && !$this->request->data['MUser']['guideline']){
					return false;
				}

				//利用規約確認古い AND 今回利用規約チェックなし
				elseif(!empty($affiliation['MCorp']['guideline_check_date']) && strtotime($affiliation['MCorp']['guideline_check_date']) < strtotime(GUIDELINE_DATE) && !$this->request->data['MUser']['guideline']){
					return false;
				}

				//今回利用規約チェックあり
				elseif($this->request->data['MUser']['guideline']){
					//過去に利用規約確認無し OR 利用規約確認古い
					if(empty($affiliation['MCorp']['guideline_check_date']) || (!empty($affiliation['MCorp']['guideline_check_date']) && strtotime($affiliation['MCorp']['guideline_check_date']) < strtotime(GUIDELINE_DATE))){
						try{
							//利用規約確認日の更新
							$save_data['MCorp']['id'] = $affiliation['MCorp']['id'];
							$save_data['MCorp']['guideline_check_date'] = date('Y-m-d');
							if(!$this->MCorp->save($save_data)){
								//エラー処理
								$this->log('guideline_check_date update error mcorp_id:'.$affiliation['MCorp']['id']);
							}
						}catch(Exception $e){
							$this->log($e->getMessage());
							return false;
						}
					}
				}

			}
		}
		return true;
	}

	//ORANGE-314 ADD E
}
