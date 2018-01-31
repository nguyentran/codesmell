<?php

App::uses('AppController', 'Controller');

class AndroidLoginController extends AppController {
    public $name = 'AndroidLogin';
    public $uses = array('DeviceInfo');
    public $_os_type = 'Android';
    public $components = array('RequestHandler');

    public function beforeFilter(){
            parent::beforeFilter();
            $this->Security->csrfCheck = false;

            //ログイン認証なしでアクセス可能にする
            $this->Auth->allow('login');
    }

    public function login() {
        //JSONで返す配列
        $session = array(
            'session_key' => "",
            'session_id' => "",
        );

        if (!$_POST
                 || !$_POST['user_id']
                 || !$_POST['password']
                 || !$_POST['registration_id']) {
            $this->log(array('post error', $_POST), LOG_ERR);

            $this->set('posts', $session);
            $this->set('_serialize', array('posts'));
            return;
        }
        $this->log(array('post from android', $_POST), LOG_DEBUG);
        //ユーザID
        $user_id = $_POST['user_id'];
        //パスワード
        $password = $_POST['password'];
        //RegistrationID
        $registrationId = $_POST['registration_id'];

        //AmazonSNSのインスタンス生成
        $AmazonSnsUtil = new AmazonSnsUtil();
        //セッション情報

        //ログイン処理
        $session_id = $this->_app_login($user_id, $password);
        $this->log(array('session_id', $session_id), LOG_DEBUG);
        if ($session_id) {
            $session['session_key'] = session_name();
            $session['session_id'] = $session_id;

            //device_infosテーブルからデータ取得
            $ep_exist_fromDeviceToken_flag = false;
            //デバイストークンとエンドポイントはユニークな値
            $res_fromDeviceToken = $this->DeviceInfo->find(
                    'first',
                    array(
                        'conditions' => array(
                            'DeviceInfo.device_token' => $registrationId
                            ),
                        )
                    );
            if ($res_fromDeviceToken) {
                //エンドポイントはある(ユーザIDが分からない状態)
                $ep_exist_fromDeviceToken_flag = true;

                if ($res_fromDeviceToken['DeviceInfo']['user_id'] == $user_id) {
                    /*
                     * エンドポイントに紐づくユーザIDとPOSTされたユーザIDが等しいとき
                     */
                    //AmazonSNSの「Enabled」ステータスがfalseならtrueにする
                    $resGetEnd = $AmazonSnsUtil->getEndpoint($res_fromDeviceToken['DeviceInfo']['endpoint']);
                    if ($resGetEnd['Attributes']['Enabled'] == 'false') {
                        $resSetEnd = $AmazonSnsUtil->setEndpoint($res_fromDeviceToken['DeviceInfo']['endpoint']);
                    }
                    //アプリにセッション情報を送る
                    $this->set('posts', $session);
                    $this->set('_serialize', array('posts'));
                } else {
                    /*
                     * 同じ端末に前回と違うユーザIDでログインしてきた
                     */
                    //AmazonSNSの「Enabled」ステータスがfalseならtrueにする
                    $resGetEnd = $AmazonSnsUtil->getEndpoint($res_fromDeviceToken['DeviceInfo']['endpoint']);
                    if ($resGetEnd['Attributes']['Enabled'] == 'false') {
                        $resSetEnd = $AmazonSnsUtil->setEndpoint($res_fromDeviceToken['DeviceInfo']['endpoint']);
                    }
                    //user_idを後勝ちにする(update)
                    $set_data = array(
                        'user_id' => $user_id,
                    );
                    // 更新
                    $result = $this->DeviceInfo->updateDeviceInfo($res_fromDeviceToken['DeviceInfo']['id'], $set_data);
                    if ($result) {
                        $this->log(array('update user_id success', $set_data, $result), LOG_DEBUG);
                    } else {
                        $this->log(array('update user_id error', $set_data, $result), LOG_ERR);
                    }

                    //アプリにセッション情報を送る
                    $this->set('posts', $session);
                    $this->set('_serialize', array('posts'));
                }

            } else {
                /*
                 * エンドポイントがないので登録
                 */
                $res = $AmazonSnsUtil->createEndpoint($registrationId, $this->_os_type);

                // 登録する内容を設定
                $set_data = array(
                    'user_id' => $user_id,
                    'device_token' => $registrationId,
                    'endpoint' => $res['EndpointArn'],
                    'os_type' => $this->_os_type,
                    'push_cnt' => 0,
                );
                // DBにエンドポイント登録
                $result = $this->DeviceInfo->insertDeviceInfo($set_data);
                if ($result) {
                    $this->log(array('insert device_info success', $set_data, $result), LOG_DEBUG);
                } else {
                    $this->log(array('insert device_info error', $set_data, $result), LOG_ERR);
                }

                //アプリにセッション情報を送る
                $this->set('posts', $session);
                $this->set('_serialize', array('posts'));
            }
        } else {
            //ログイン失敗
            //アプリにセッション情報(空文字)を送る
            $this->set('posts', $session);
            $this->set('_serialize', array('posts'));

        }

    }

    /**
     * オレンジシステムへのログイン
     * @param type $userid
     * @param type $password
     * @return boolean
     */
    public function _app_login($userid = null, $password = null) {
		$this->request->data = array(
				'MUser' => array(
						'user_id' => $userid,
						'password' => $password
				)

		);
		if($this->Auth->login()) {
			// murata.s ORANGE-646 ADD(S)
			// Androidアプリからのログイン
			$this->Session->write(self::$__sessionKeyForCommissionAppLogined, true);
			// murata.s ORANGE-646 ADD(E)

			// murata.s ORANGE-430 ADD(S)
			// 最終ログイン日時の設定
			$user = $this->Auth->user();
			$this->MUser->updateLastLoginDate($user['id']);
			// murata.s ORANGE-430 ADD(E)
			$session_id = $this->Session->id();
			return $session_id;
		} else {
			return false;
		}
	}
}
