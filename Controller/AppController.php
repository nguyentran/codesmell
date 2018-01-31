<?php

App::uses('Controller', 'Controller');
App::uses('Sanitize', 'Utility');

class AppController extends Controller {

	public $helpers = array('Session');
// 2016.10.20 murata.s ORANGE-208 CHG(S)
	public $components = array('Session','RequestHandler','Cookie',
		'Auth' => array(
			'authenticate' => array(
				'Form' => array(
					'userModel' => 'MUser',
					'fields' => array('username' => 'user_id', 'password'=>'password')
				)
			),
			'loginAction' => array('controller' => 'login','action' => 'index'),
			'logoutAction' => array('controller' => 'signin','action' => 'signout'),
			'loginRedirect' => array('controller' => 'top', 'action' => 'index'),
			),
		'Security' => array(
				'csrfUseOnce' => false,
				'validatePost' => false,
				'csrfExpires' => '+1 hour'
		)
#		'DebugKit.Toolbar'
	);
// 2016.10.20 murata.s ORANGE-208 CHG(E)

	//ORANGE-89 iwai 2016.09.14 ADD S
	// sasaki@tobila 2016.04.19 MOD Start ORANGE-1371
	// MCorp, CorpAgreementを追加
        // 2017.4.14 ichino ORANGE-381 CHG start NoticeInfoを追加
    // 2017/4/24 ichino ORANGE-402 CHG start CorpLisenseLinkを追加
	public $uses = array('MUser','MCorp','CorpAgreement', 'ProgCorp', 'ProgImportFile', 'NoticeInfo', 'CorpLisenseLink');
    // 2017/4/24 ichino ORANGE-402 CHG end
	// 2017.4.14 ichino ORANGE-381 CHG End
        // sasaki@tobila 2016.04.19 MOD End ORANGE-1371
	//ORANGE-89 iwai 2016.09.14 ADD E

	//SSLが必要なページか？
	public $is_ssl_page = false;
//	public $is_ssl_page = false;

	protected static $__sessionKeyForDemandSearch = 'datas@DemandSearch';
	protected static $__sessionKeyForDemandParameter = 'datas@DemandParameter';
	protected static $__sessionKeyForCommissionSearch = 'datas@CommissionSearch';
	protected static $__sessionKeyForCommissionParameter = 'datas@CommissionParameter';
	protected static $__sessionKeyForCommissionAffiliation = 'datas@CommissionAffiliation';
	protected static $__sessionKeyForAffiliationSearch = 'datas@AffiliationSearch';
	protected static $__sessionKeyForAffiliationParameter = 'datas@AffiliationParameter';
	protected static $__sessionKeyForReport = 'datas@Report';
	protected static $__sessionKeyForUserSearch = 'datas@UserSearch';
	protected static $__sessionKeyForCommissionSelect = 'datas@CommissionSelect';
	protected static $__sessionKeyForBillMcopSearch = 'datas@BillMcopSearch';
	protected static $__sessionKeyForBillSearch = 'datas@BillSearch';
	protected static $__sessionKeyForMoneyCorrespondSearch = 'datas@MoneyCorrespondSearch';
	protected static $__sessionKeyForAffiliationReturn = 'datas@AffiliationReturn';
	protected static $__sessionKeyForAutoSelect = 'datas@AutoSelect';
	protected static $__sessionKeyForAuctionSearch = 'datas@AuctionSearch';
	protected static $__sessionKeyForMobileTime = 'datas@MobileTime';
	// 2016.07.21 murata.s ORANGE-17, ORANGE-52 ADD(S) NoticeInfoのセッションキー不足
	protected static $__sessionKeyForNoticeInfo = 'datas@NoticeInfo';
	// 2016.07.12 murata.s ORANGE-17, ORANGE-52 ADD(S)
	// murata.s ORANGE-646 ADD(S)
	protected static $__sessionKeyForCommissionAppLogined = 'datas@CommissionAppLogined';
	// murata.s ORANGE-646 ADD(E)

	/**
	 * @method beforeFilter
	 */
	public function beforeFilter(){

		// 2015.07.23 n.kai ADD start  ※問題再発防止
		// フラグを直接変更するため、戻し忘れ、falseのままリリースすると
		// 影響が大きいため、bootstrapにて制御する。
		// bootstrapは、環境毎に専用となっているため、上書きすることが
		// ないので、本PGを直接修正し切り替えるよりも安全である。
		// 要は、本PGを変更しなくてよくなる。
		$this->is_ssl_page = Configure::read('is_ssl_page');
		// 2015.07.23 n.kai ADD end

		// ハッシュタイプをsha256に設定
		Security::setHash('sha256');
		$this->set("title_for_layout", __('SiteTitle', false));

		$controllerName = strtolower($this->name);
		$actionName = strtolower($this->action);

		//強制SSL
		if($this->is_ssl_page && !$this->RequestHandler->isSSL()) {
			$url = 'https://' . $_SERVER['HTTP_HOST'] . $this->here;
			$this->redirect($url, null, true);
		} elseif(!$this->is_ssl_page && $this->RequestHandler->isSSL()) {
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $this->here;
				$this->redirect($url, null, true);
		}

		// スマホのみセッションの設定
		if(Util::isMobile()) {
			$MobileTimeData = $this->Session->read(self::$__sessionKeyForMobileTime);
			$MobileTimeData = empty($MobileTimeData) ? date('Y-m-d H:i:s') : $MobileTimeData;
			$limit_time = Configure::read('mobile_limit_time');
			$TargetTime = strtotime("+".$limit_time." minute", strtotime($MobileTimeData));

			if($TargetTime <= strtotime(date('Y-m-d H:i:s'))){
				$this->Session->destroy();
			} else {
				$this->Session->write(self::$__sessionKeyForMobileTime, date('Y-m-d H:i:s'));
			}
		}

		$this->User = $this->Auth->user();
		// 2015.04.10 h.hara
		//if($this->User["auth"] == 'popular'){
		// 2015.05.18 h.hara ORANGE-498(S)
		//if($this->User["auth"] == 'popular' || $this->User["auth"] == 'system' || $this->User["auth"] == 'admin' || $this->User["auth"] == 'accounting'){
		if(($this->User["auth"] == 'popular' || $this->User["auth"] == 'system' || $this->User["auth"] == 'admin' || $this->User["auth"] == 'accounting') && ($this->User["user_id"] <> 'hoshikawa' )){
		// 2015.05.18 h.hara ORANGE-498(E)

			// IPアクセス制限処理
			$allowIpAddress = Configure::read('allow_ip');
			$ip_check = Configure::read('ip_check');
			$clientIp = $this->request->clientIp(false);
			$isSafe = false;

			foreach ($allowIpAddress as $value) {
				if ($clientIp === $value) {
					$isSafe = true;
					break;
				}
			}

			if (!$ip_check) {
				$isSafe = true;
			}

			if (!$isSafe) {
				$this->Auth->logout();
				throw new BadRequestException();
			}

		}


 		if($this->Auth->user()){
			$this->set('user', $this->Auth->user());
 		}

 		$this->checkAuth($controllerName);

		/* 2015.12.10 Orange -743対応のため削除
		// セッションクリア処理
		if ($controllerName !== 'demandlist' && $controllerName !== 'demand'){
		$this->Session->delete(self::$__sessionKeyForDemandSearch);
		$this->Session->delete(self::$__sessionKeyForDemandParameter);
		}
		 */
		if ($controllerName !== 'report' &&
			// 2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
		$controllerName !== 'auctionsettingtg' &&
			// 2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13
		($controllerName !== 'demand' && $actionName !== 'detail') &&
			// 2015.4.14 n.kai ADD start JBR様領収書後追いレポートへ戻るため追加
			($controllerName !== 'commission' && $actionName !== 'detail') &&
			// 2015.4.14 n.kai ADD end
			($controllerName !== 'affiliation' && $actionName !== 'detail')){
				$this->Session->delete(self::$__sessionKeyForReport);
		}
		if ($controllerName !== 'commission') {
			$this->Session->delete(self::$__sessionKeyForCommissionSearch);
			$this->Session->delete(self::$__sessionKeyForCommissionParameter);
			$this->Session->delete(self::$__sessionKeyForCommissionAffiliation);
		}
		if ($controllerName !== 'affiliation') {
			$this->Session->delete(self::$__sessionKeyForAffiliationSearch);
			$this->Session->delete(self::$__sessionKeyForAffiliationParameter);
		}
		if ($controllerName !== 'commissionselect') {
			$this->Session->delete(self::$__sessionKeyForCommissionSelect);
		}
		if ($controllerName !== 'bill' && ($controllerName !== 'affiliation' && $actionName == 'detail')) {
			$this->Session->delete(self::$__sessionKeyForBillMcopSearch);
			$this->Session->delete(self::$__sessionKeyForBillSearch);
			$this->Session->delete(self::$__sessionKeyForMoneyCorrespondSearch);
		}
		if ($controllerName !== 'user' && ($controllerName !== 'affiliation' && $actionName == 'detail')) {
			$this->Session->delete(self::$__sessionKeyForUserSearch);
		}
		if(($controllerName == 'demand' && $actionName == 'detail') ||
			($controllerName == 'commission' && $actionName == 'detail') ||
			($controllerName == 'bill' && ($actionName == 'bill_list' || $actionName == 'bill_search')) ||
			($controllerName == 'user' && $actionName == 'search') ||
			($controllerName == 'report' && $actionName != 'index') ||
			// 2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
			($controllerName == 'auctionsetting' && $actionName == 'follow')){
			// 2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13

			// 2015.09.21 h.hanaki CHG(S) ORANGE_AUCTION-13 ※コントロールとURLが異なるため
				if ($controllerName == 'auctionsetting'){
				$url_array = explode('auction_setting'.'/', $this->here);
				$return_data['url'] = 'auction_setting'.'/'.$url_array[1];
			}else{
				$url_array = explode($controllerName.'/', $this->here);
				$return_data['url'] = $controllerName.'/'.$url_array[1];
			}
			// 2015.09.21 h.hanaki CHG(E) ORANGE_AUCTION-13
			$this->Session->delete(self::$__sessionKeyForAffiliationReturn );
			$this->Session->write(self::$__sessionKeyForAffiliationReturn , $return_data);
		}
		if ($controllerName !== 'auction') {
			$this->Session->delete(self::$__sessionKeyForAuctionSearch);
		}

		//ajaxの場合はレイアウトを使用しない
		if($this->RequestHandler->isAjax()){
			$this->layout = false;
			return;
		}

		// sasaki@tobila 2016.04.19 MOD Start ORANGE-1371
		/*
		 * 契約締結確認ダイアログの表示を判定。
		 * View/Elements/agreement_confirm_modal.ctp で使用するView変数を設定する。
		 * 上記のElementの中で、ログイン時のみダイアログ用のHTMLをレンダリングするようにしている。
		 */
		if($this->Auth->user())
		{
			$user = $this->Auth->user();
			$agreement_cancel = time() < strtotime(Configure::read('agreement_grace_date'));
			$this->set ("agreement_cancel", $agreement_cancel );
			$this->set ("agreement_grace_date", Configure::read('agreement_grace_date'));
// 2016.10.20 murata.s ORANGE-209 DEL(S)
//			if(!empty($_COOKIE["orange-single-sign-on"]))$this->set ("cookie_id", $_COOKIE["orange-single-sign-on"]);
// 2016.10.20 murata.s ORANGE-209 DEL(E)
			$this->set ("agreement_login_url", Configure::read('agreement_login') .$user['id']);
			$this->set('is_agreement_dialog_show', 'false');
			//ORANGE-89 2016.09.14 Add S
			$this->set ('import_file_id', 0);
			$this->set ('is_prog_dialog_show', 'false');
			//ORANGE-89 ADD E
			if ($this->_isForceAgreement()){
				if($this->_agreement_login()){
					$this->set ('is_agreement_dialog_show', 'true');
				}
				//ORANGE-89 2016.09.14 Add S
				$this->set ('import_file_id', 0);
				$this->set ('is_prog_dialog_show', 'false');
				if($this->_is_show_prog()){
					//ORANGE-218 CHG S release_flag追加
					$options = array(
							'conditions' => array('delete_flag' => 0, 'release_flag' => 1),
							'order' => array('id' => 'desc'), 'limit' => 1);
					//ORANGE-218 CHG E
					$pif = $this->ProgImportFile->find('first', $options);
					$this->set ('import_file_id', $pif['ProgImportFile']['id']);
					$this->set ('is_prog_dialog_show', 'true');
				}
				//ORANGE-89 ADD E

				//ORANGE-199 ADD S
				$this->_show_antisocaial_follow();
				//ONRAGE-199 ADD E
			}

			//2017.4.14 ichino ORANGE-381 ADD start
			//掲示版の未読、未回答を取得する
			if($this->User["auth"] == 'affiliation'){
				$this->NoticeInfo->setUser($this->Auth->user());
				$unreadUnreadCount = $this->NoticeInfo->countUnreadUnreadByCorpId($user['affiliation_id']);
				$this->set ('unreadUnreadCount', $unreadUnreadCount);

				// murata.s ORANGE-517 ADD(S)
				// 掲示板の未回答数を取得する
				if($controllerName != 'noticeinfos'){
					$unanswerCount = $this->NoticeInfo->countUnansweredByCorpId($user['affiliation_id']);
				}else{
					$unanswerCount = 0;
				}
				$this->set ('unanswerCount', $unanswerCount);
				// murata.s ORANGE-517 ADD(E)

				// 2017.10.16 ORANGE-568 h.miyake ADD(S)
				// 権限が加盟店の場合、新着のお知らせで掲示板が重要かつ未読の場合の処理
				$noticeInfoStatus = $this->getNoticeInfoStatus($user["affiliation_id"]);
				$modalStatus["boolNoticeInfoStatus"] = true;
				$modalStatus["dataBackdrop"] = '';
				foreach($noticeInfoStatus as $key => $value) {
					if($value["noticeinfo"]["status"] == "2" || $value["noticeinfo"]["status"] == "3") {
						if(in_array($value["noticeinfo"]["id"], Configure::read('arrNoticeInfoImportant'))) {
							$modalStatus["boolNoticeInfoStatus"] = false;
							$modalStatus["dataBackdrop"] = 'data-backdrop="static" data-keyboard="false"';
							break;
						}
					}
				}
				$this->set('modalStatus', $modalStatus);
				// 2017.10.16 ORANGE-568 h.miyake ADD(E)

			}
			//2017.4.14 ichino ORANGE-381 ADD end

			// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
            //// 2017/4/24 ichino ORANGE-402 ADD start
            //// 保持しているライセンスの有効期限を確認 加盟店のみ
            //if($this->User["auth"] == 'affiliation'){
            //    //データの取得
            //    $license_data = $this->_show_license_follow($user);
            //
            //    // true:表示 false: 非表示
            //    if(!empty($license_data)){
            //        $is_license_dialog_show = true;
            //    } else {
            //        $is_license_dialog_show = false;
            //    }
            //
            //    //データセット
            //    //is_license_dialog_show true:ダイアログ表示 false:ダイアログ非表示
            //    $this->set ('is_license_dialog_show', $is_license_dialog_show);
            //    $this->set ('license_data', $license_data);
            //}
            //// 2017/4/24 ichino  ORANGE-402 ADD end
			// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
		}
		// sasaki@tobila 2016.04.19 MOD End ORANGE-1371
	}

	//参照権限のチェックを行う
	// 2016.04.12 murata.s CHG start 権限によるURLの制限
	//private function checkAuth($controller_name) {
	protected function checkAuth($controller_name) {
	// 2016.04.12 murata.s CHG end   権限によるURLの制限

		// ログイン情報取得
		$user = $this->Auth->user();

		// 2015.05.28 y.fujimori ADD start
		$actionName = strtolower($this->action);
		// 2015.05.28 y.fujimori ADD end
		switch ($controller_name) {

			// 加盟店管理
			case 'affiliation':
				// 2015.09.07 s.harada ADD start 加盟店が自社以外の情報閲覧時エラー
				// 2016.04.12 murata.s CHG start 権限によるURLの制限
				//if ($user['auth'] == 'affiliation' && ($actionName == 'category' || $actionName == 'detail' || $actionName == 'genre' || $actionName == 'corptargetarea')) {
				//	if ($user['affiliation_id'] != $this->request->params['pass'][0]) {
				//		throw new ApplicationException(__('NoReferenceAuthority', true));
				//	}
				//}
// 2016.12.27 CHG(S)
// 2016.10.13 murata.s CHG(S) 脆弱性 権限外の操作対応
				if ($user['auth'] == 'affiliation' && ($actionName == 'category' || $actionName == 'genre' || $actionName == 'corptargetarea' || $actionName == 'agreement_file_upload' || $actionName == 'agreement')) {
					if ($user['affiliation_id'] != $this->request->params['pass'][0]) {
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}
				}
// 2016.10.13 murata.s CHG(E) 脆弱性 権限外の操作対応
// 2016.12.27 CHG(E)
				// 2016.04.12 murata.s CHG end   権限によるURLの制限
				// 2015.09.07 s.harada ADD end 加盟店が自社以外の情報閲覧時エラー

				// 2015.05.28 y.fujimori ADD start
				//				if ($user['auth'] == 'affiliation') {
				// 2015.08.17 s.harada MOD start 画面デザイン変更対応
				// if ($user['auth'] == 'affiliation' && !($actionName == 'category' || $actionName == 'detail')) {
				// 2016.04.12 murata.s CHG start 権限によるURLの制限
				//if ($user['auth'] == 'affiliation' && !($actionName == 'category' || $actionName == 'detail' || $actionName == 'genre' || $actionName == 'corptargetarea' || $actionName == 'targetarea')) {
				//	// 2015.08.17 s.harada MOD end 画面デザイン変更対応
				//	throw new ApplicationException(__('NoReferenceAuthority', true));
				//}
				if ($user['auth'] == 'affiliation' && !($actionName == 'category' || $actionName == 'genre' || $actionName == 'corptargetarea' || $actionName == 'targetarea' || $actionName == 'agreement' || $actionName == 'agreement_file_download' || $actionName == 'agreement_file_upload' || $actionName == 'agreement_file_delete')) {
					// 2015.08.17 s.harada MOD end 画面デザイン変更対応
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
				// 2016.04.12 murata.s CHG end   権限によるURLの制限
			//2015.05.28 y.fujimori ADD end

			// 取次管理
			case 'commission':

				// ORANGE-13 iwai ADD S
				//申請
				if ($user['auth'] == 'affiliation' && $actionName == 'application') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				//承認
				if ($user['auth'] != 'system' && $user['auth'] != 'admin' && $actionName == 'approval') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				// ORANGE-13 iwai ADD E

				break;

			// 取次票印刷
			case 'commissionprint':
			//	2015.11.27 h.hanaki CHG ORANGE-805 【ID権限】経理・経理管理者ID権限で案件管理から「取次票印刷」が出来るようにしてほしい。
			//	if ($user['auth'] == 'affiliation' || strstr($user['auth'], 'accounting')) {
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 案件管理
			case 'demand':
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 案件一覧
			case 'demandlist':
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 紹介先
			case 'introduceselect':
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// ダウンロード
			case 'download':
				/*
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				*/
				break;

			// レポート
			case 'report':
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				// ORANGE-13 iwai ADD S
				if ($user['auth'] != 'system' && $user['auth'] != 'admin' && $actionName == 'application_admin') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				// ORANGE-13 iwai ADD E
				break;

			// ユーザー管理
			case 'user':
				if ($user['auth'] == 'affiliation' || $user['auth'] == 'popular' || $user['auth'] == 'accounting') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 請求管理
			case 'bill':
				if ($user['auth'] == 'affiliation' || $user['auth'] == 'popular' || $user['auth'] == 'admin') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 加盟店選択
			case 'commissionselect':
				if ($user['auth'] == 'affiliation') {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// 管理者メニュー
			case 'admin':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// ジャンル別選定設定
			case 'selection':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;

			// オークション選定設定
			case 'auctionsetting':
				// 2016.04.20 sasaki@tobila MOD start ORANGE-1358 権限によるURLの制限
				if (
						$user['auth'] == 'affiliation'
						||
						(
							!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin') 
							&&
							$actionName != 'follow'
						)
				){
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				// 2016.04.20 sasaki@tobila MOD end ORANGE-1358 権限によるURLの制限
				break;
			// 2016.04.12 murata.s ADD start 権限によるURLの制限
			// ジャンルマスタ
			case 'genre':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
			// 営業支援対象案件フラグマスタ
			case 'targetdemandflag':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
			// アラートメール設定
			case 'commissionalertsetting':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
			// 総合検索
			case 'generalsearch':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin' || $user['auth'] == 'popular' || $user['auth'] == 'accounting')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
			// デイリーリスト
			case 'dailylist':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
			case 'ajax':

				// 案件管理のみで使用されているaction
				if($actionName == 'sitedata' || $actionName == 'genre_list' || $actionName == 'genre_list2'
						|| $actionName == 'inquiry_item_data' || $actionName == 'attention_data'
						|| $actionName == 'commission_change' || $actionName == 'travel_expenses'
						|| $actionName == 'selection_system_list'){
					if ($user['auth'] == 'affiliation') {
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}
				}
				// 総合検索のみで使用されているaction
				if($actionName == 'searchmgeneralsearch' || $actionName == 'csvpreview'){
					if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin' || $user['auth'] == 'popular' || $user['auth'] == 'accounting')) {
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}
				}
				// オークション選定設定のみで使用されているaction
				if($actionName == 'exclusion_pattern'){
					if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}
				}

// 2016.10.13 murata.s ADD(S) murata.s 脆弱性 権限外の操作対応
				// 基本情報設定で使用されているaction
				if($actionName == 'searchcorptargetarea'){
					if($user['auth'] == 'affiliation'){
						if ($user['affiliation_id'] != $this->request->params['pass'][0])
							throw new ApplicationException(__('NoReferenceAuthority', true));
					}
				}
// 2016.10.13 murata.s ADD(E) murata.s 脆弱性 権限外の操作対応
				break;
			// 2016.04.12 murata.s ADD end   権限によるURLの制限
// 2017.02.20 murata.s ORANGE-328 ADD(S)
			case 'notcorrespond':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
// 2017.02.20 murata.s ORANGE-328 ADD(E)
                        
                        // 2017/05/10  ichino ORANGE-31 ADD start
                        case 'csvimport':
                            if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
                            break;
                        // 2017/05/10  ichino ORANGE-31 ADD end   
                        // 2017/06/08  ichino ORANGE-420 ADD start
                        case 'autocommissioncorp':
                            if (!($user['auth'] == 'system' || $user['auth'] == 'admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
                            break;
                        // 2017/06/08  ichino ORANGE-420 ADD end
                        // 2017/07/11 ichino ORANGE-459 ADD start
                        case 'vacationedit':
                            if (!($user['auth'] == 'system' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
                            break;
                        // 2017/07/11 ichino ORANGE-459 ADD end
// murata.s ORANGE-537 ADD(S)
			case 'autocall':
				if (!($user['auth'] == 'system' || $user['auth'] == 'admin' || $user['auth'] == 'accounting_admin')) {
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
				break;
// murata.s ORANGE-537 ADD(E)
		}

	}

	/**
	 * beforeRender
	 */
	function beforeRender() {
	}

	/**
	 * Queryパラメータのページ番号があればページ番号を返します。
	 * 無い場合はNullを返します。
	 */
	protected function __getPageNum() {
		$page = null;
		if (isset($this->request->query['page'])) {
			$page = $this->request->query['page'];
		}
		if (!isset($page) && isset($this->passedArgs['page'])) {
			$page = $this->passedArgs['page'];
		}
		return $page;
	}

	// sasaki@tobila 2016.04.19 ADD Start ORANGE-1371
	/**
	 * 加盟店の契約が締結ずみかどうかを判定する。
	 * 
	 * ログイン画面だけでなく全画面で契約締結要求ダイアログを表示させることから、
	 * LoginControllerから本Controllerへ移動。
	 * 
	 * 加盟店以外の権限でアクセスした場合はfalseを返す。
	 * 契約が締結済みの場合はfalseを返す。
	 * 契約が未締結の場合は、シングルサインオン用のMD5ハッシュを含むクッキーを発行し、DBに保存する。戻り値としてtrueを返す。
	 * 
	 * 【注意】
	 * LoginController::app_logout() でも本メソッドを参照しており、
	 * 互換性を保つためprotectedとしている。
	 * 
	 * @author sasaki@tobila.com
	 * @return void|boolean
	 */
	protected function _agreement_login(){
		$user = $this->Auth->user();
		// 加盟店以外の場合
		if($user['auth'] != 'affiliation'){
			return false;
		}
		// 2016.04.06 ORANGE-1341 k.iwai CHG(S).
		// bootstrap add config
		$corp_agreement = $this->CorpAgreement->find('first', array ('fields' => '*',
				'conditions' => array (
						'CorpAgreement.corp_id' => $user['affiliation_id']
				),
				'order' => array (
						'CorpAgreement.id' => 'desc'
				)
		));
		// 2016.04.06 ORANGE-1341 k.iwai CHG(E).
	
		// 2016.03.07 ORANGE-1295 k.iwai CHG(S).
		// 締結済の場合
		if( $corp_agreement && ($corp_agreement['CorpAgreement']['status']=="Complete" || $corp_agreement['CorpAgreement']['status']=="Application")){
			// 2016.05.30 murata.s ORANGE-75 CHG(S)
			//return false;
			$m_corp = $this->MCorp->read(null, $user['affiliation_id']);
			if($m_corp['MCorp']['commission_accept_flg'] != 2
					&& $m_corp['MCorp']['commission_accept_flg'] != 3)
						return false;
			// 2016.05.30 murata.s ORANGE-75 CHG(E)

		}
		// 2016.03.07 ORANGE-1295 k.iwai CHG(E).

// 2016.10.20 murata.s ORANGE-209 DEL(S)
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
// 2016.10.20 murata.s ORANGE-209 DEL(E)
		return true;
	}
	// sasaki@tobila 2016.04.19 ADD End ORANGE-1371

	/**
	 * 進捗表ダイアログ表示
	 *
	 */
	protected function _is_show_prog(){
		$user = $this->Auth->user();
		// 加盟店以外の場合
		if($user['auth'] != 'affiliation'){
			return false;
		}

		//メール送信済みの加盟店が対象
		$options = array(
				'conditions' => array(
					'ProgCorp.corp_id' => $user['affiliation_id'],
					'ProgCorp.progress_flag' => 2
				),
				//ORANGE-218 CHG S release_flag追加
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => '(select * from prog_import_files where delete_flag = 0 and release_flag = 1 order by id desc limit 1)',
								'alias' => 'ProgImportFile',
								'conditions' => 'ProgCorp.prog_import_file_id = ProgImportFile.id'
						)
				),
				//ORANGE-218 CHG E

		);
		$cnt = $this->ProgCorp->find('count', $options);

		if($cnt > 0)return true;
		return false;
	}

	//ORANGE-199 ADD S, ORANGE-346 CHG S
	/**
	 * 反社チェック後追い表示
	 * @see ReportController::__get_antisocial_check_model_options() 後追いレポート画面。抽出条件をこちらと合わせておく必要がある
	 */
	private function _show_antisocaial_follow(){
		$user = $this->Auth->user();

		//加盟店以外 抜ける
		if($user['auth'] != 'affiliation') return false;

		//ORANGE-346 反社チェック関連が別テーブルになった事により抽出条件を修正
		//契約更新フラグが契約完了であること、
		//表示フラグ(日の最初にONにし、表示したらOFFにされる)がONであること、
		//最終チェック日が「今年のチェック予定年月」よりも古いこと、
		//今日が「今年のチェック予定年月」以降であること。
		//加盟店側は反射チェック日のNULLはスルーする
		$options = array(
        'fields' => array(
            'MCorp.id'
        ),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'antisocial_checks',
								'alias' => 'AntisocialCheck',
								'conditions' => 'AntisocialCheck.corp_id = MCorp.id'
						)
				),
				'conditions' => array(
						'MCorp.id' => $user['affiliation_id'],
						'MCorp.antisocial_display_flag' => 1,
						'MCorp.affiliation_status' => 1,
						'MCorp.last_antisocial_check' => 'OK', 
						"now() >= to_timestamp((date_part('year', AntisocialCheck.date)+CASE WHEN date_part('month', AntisocialCheck.date) < MCorp.antisocial_check_month THEN 0 ELSE 1 END) || '/' || MCorp.antisocial_check_month || '/1','YYYY/MM/DD')",
						'NOT EXISTS( SELECT 1 FROM antisocial_checks WHERE antisocial_checks.corp_id = AntisocialCheck.corp_id AND antisocial_checks.created > AntisocialCheck.created )'
				),
				'order' => array('MCorp.id' => 'desc'),
				'limit' => 1,

		);
		$result = $this->MCorp->find('first', $options);
		$id = false;
		if(!empty($result['MCorp']['id'])){
			$id = $result['MCorp']['id'];
			//一度取得したら、表示フラグをOFFにする
			//$save_data = array();
			//$save_data['MCorp']['id'] = $id;
			//$save_data['MCorp']['antisocial_display_flag'] = 0;
			//if(!$this->MCorp->save($save_data)){
			//	$this->log('AppController Failure : _show_antisocaial_follow',LOG_ERR);
			//}
		}
		$this->set ('antisocaial_follow_id', $id);
	}
	//ORANGE-199 ADD E, ORANGE-346 CHG E

	// sasaki@tobila 2016.04.19 ADD Start ORANGE-1371
	/**
	 * 契約締結画面へリダイレクトするController／Actionかどうかを判定する。
	 * 
	 * 本メソッドではあくまでController/Actionが契約締結ダイアログへの
	 * リダイレクト対象かどうかの判定のみを行う。
	 * 権限の判定、締結済みかどうかの判定は_agreement_login()で行う。
	 * 
	 * true: 契約締結画面へのリダイレクト対象
	 * false: 対象外
	 * 
	 * @author sasaki@tobila.com
	 * @return boolean
	 */
	private function _isForceAgreement()
	{
		// Ajaxリクエストの場合は対象外とする。
		if($this->request->is('ajax')) return false;
		// GETメソッド以外の場合は対象外とする。
		if(!$this->request->is('get')) return false;
			
		/*
		 * 契約締結ダイアログへのリダイレクトを行う対象外のController, Actionを指定する。
		 * Actionは空の配列の場合、「すべてのAction」を意味する。
		 */
		$excludes = array(
			'Login' => array(),
			'Ajax' => array(),
			'IosLogin' => array(),
			'AndroidLogin' => array(),
			'AppLogin' => array(),
			'Download' => array(),
			'Affiliation' => array('agreement_file_download'),
		);
		
		if (key_exists($this->name, $excludes)){
			
			// Actionが空配列の場合は全てのActionを対象外とする。
			if(empty($excludes[$this->name])) return false;
			
			// Actionのリストが現在のActionを含む場合は対象外とする。
			if(in_array($this->action, $excludes[$this->name]))
				
			return false;
		}
		
		return true;
	}
	// sasaki@tobila 2016.04.19 ADD End ORANGE-1371

	//ORANGE-314 ADD S
	public function afterFilter(){
		//モバイル実装が同時にできないため一旦モバイルを除外
		// murata.s ORANGE-646 CHG(S)
		$commissionapp_logined = $this->Session->check(self::$__sessionKeyForCommissionAppLogined);
		if(!$commissionapp_logined && !Util::isMobile()) {
			if($this->Auth->user()){
				$user = $this->Auth->user();
				if($user['auth'] == 'affiliation' ){
					$affiliation = $this->MCorp->findById($user['affiliation_id']);
					if(empty($affiliation['MCorp']['guideline_check_date']) || strtotime($affiliation['MCorp']['guideline_check_date']) < strtotime(GUIDELINE_DATE)){
						$this->Session->setFlash('利用規約へ同意いただけないとサービスをご利用いただけません。', 'default', array('class' => 'error-message text-danger'));
						return $this->redirect($this->Auth->logout());
					}
				}
			}
		}
		// murata.s ORANGE-646 CHG(E)
		parent::afterFilter();

	}
	//OARNGE-314 ADD E

	// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
    //// 2017/4/24 ichino ORANGE-402 ADD start
    //// 保持ライセンスの有効期限確認ポップアップの表示
    //public function _show_license_follow($user){
    //    //データの取得
    //    $license_data = $this->CorpLisenseLink->licenseExpirationDateByCorpId($user['affiliation_id']);
    //    return $license_data;
    //}
    //// 2017/4/24 ichino ORANGE-402 ADD end
	// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
    
    // 2017.10.16 ORANGE-568 h.miyake ADD(S)
    // 未読/既読の取得
    private function getNoticeInfoStatus($corpId) {
    	$strSQL = "";
    	$strSQL = "SELECT ";
		$strSQL .= "	NoticeInfo.id AS NoticeInfo__id, ";
		$strSQL .= "	(CASE ";
		$strSQL .= "		WHEN (NoticeInfo.choices IS NOT NULL AND MCorpsNoticeInfo.answer_value IS NULL) THEN 3 ";
		$strSQL .= "		WHEN MCorpsNoticeInfo.id IS NULL THEN 2 ELSE 1 END) AS NoticeInfo__status ";
		$strSQL .= "FROM notice_infos AS NoticeInfo ";
		$strSQL .= "		LEFT JOIN m_corps_notice_infos AS MCorpsNoticeInfo ";
		$strSQL .= "			ON (NoticeInfo.id = MCorpsNoticeInfo.notice_info_id AND MCorpsNoticeInfo.m_corp_id = ?) ";
		$strSQL .= "WHERE ";
		$strSQL .= "	NoticeInfo.del_flg = 0 ";
		$strSQL .= "	AND ((((NoticeInfo.corp_commission_type = 1) ";
		$strSQL .= "	AND (NoticeInfo.is_target_selected = 'FALSE'))) OR (((NoticeInfo.corp_commission_type IS NULL) ";
		$strSQL .= "	AND (NoticeInfo.is_target_selected = 'FALSE'))) OR (((NoticeInfo.is_target_selected = 'TRUE') ";
		$strSQL .= "	AND (EXISTS ( ";
		$strSQL .= "		SELECT 1 ";
		$strSQL .= "		FROM notice_info_targets ";
		$strSQL .= "		WHERE notice_info_targets.notice_info_id = NoticeInfo.id ";
		$strSQL .= "			AND notice_info_targets.corp_id = ?))))) ";
		$strSQL .= "ORDER BY NoticeInfo.id";
		
		$param = array($corpId, $corpId);
		$result = $this->NoticeInfo->query($strSQL, $param);
		return $result;
    }
    // 2017.10.16 ORANGE-568 h.miyake ADD(S)
}
