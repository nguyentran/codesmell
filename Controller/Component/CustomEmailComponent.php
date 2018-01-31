<?php
App::uses('CakeEmail', 'Network/Email');

class CustomEmailComponent extends Component {

	/**
	 * メール送信
	 *
	 */
	public function send($subject, $template, $data, $to_mail, $to_name=null, $from_mail=null, $from_name=null) {
		try{
			// 送受信者設定
			if( is_null($to_name) ) {
				$to_name = $to_mail;
			}
			if(is_null($from_mail)){
				$from_mail = $this->from_mail;
			}

			// 送信処理
			$email = new CakeEmail();
			$email
			->template($template)
			->viewVars(array('data' => $data))
			->emailFormat('text')
			->to(array($to_mail => $to_name))
			->from(array($from_mail => $from_name))
			->subject($subject);

			if($email->send()){
				return true;
			}else{
				return false;
			}
		}catch(Exception $e){
			$this->log($e->getMessage(), LOG_ERR);
			return false;
		}


	}

	/**
	 * テンプレートなし 本文のみ
	 * @param unknown $subject
	 * @param unknown $body
	 * @param unknown $to_mail
	 * @param unknown $from_mail
	 */
	public function simple_send($subject=null, $body=null, $to_mail=null, $from_mail=null){
		try{
			$email = new CakeEmail();

			$email
			->emailFormat('text')
			->to($to_mail)
			->from($from_mail)
			->subject($subject);

			if($email->send($body)){
				return true;
			}else{
				return false;
			}

		}catch(Exception $e) {
			$this->log($e->getMessage(), LOG_ERR);
			return false;
		}
	}

// murata.s ORANGE-351 ADD(S)
	/**
	 * メール送信
	 *
	 * @param string $subject 件名
	 * @param string $template テンプレート
	 * @param array $data データ
	 * @param string $to_mail 送信先メールアドレス
	 * @param string $to_name 送信先名
	 * @param string $from_mail 送信元メールアドレス
	 * @param string $from_name 送信元名
	 * @param string $bcc_mail BCCメールアドレス
	 *
	 * @return array 送信結果
	 */
	public function bcc_send($subject, $template, $data, $to_mail, $to_name=null, $from_mail=null, $from_name=null, $bcc_mail=null) {
		try{
			// 送受信者設定
			if( is_null($to_name) ) {
				$to_name = $to_mail;
			}
			if(is_null($from_mail)){
				$from_mail = $this->from_mail;
			}

			// 送信処理
			$email = new CakeEmail();
			$email
			->template($template)
			->viewVars(array('data' => $data))
			->emailFormat('text')
			->to(array($to_mail => $to_name))
			->bcc($bcc_mail)
			->from(array($from_mail => $from_name))
			->subject($subject);

			$result = $email->send();
			return array(
					'result' => $result,
					'mail' => array(
							'from' => $from_mail,
							'to' => $to_mail,
							'bcc' => $bcc_mail,
							'subject' => $subject,
							'message' => $email->message(CakeEmail::MESSAGE_TEXT)
					)
			);

		}catch(Exception $e){
			$this->log($e->getMessage(), LOG_ERR);
			return false;
		}


	}
// murata.s ORANGE-351 ADD(E)

	/**
	 * テンプレートなし 本文のみ
	 * @param String $subject
	 * @param String $body
	 * @param String $to_mail
	 * @param String $from_mail
	 * @param String $bcc_mail
	 */
	public function bcc_simple_send($subject=null, $body=null, $to_mail=null, $from_mail=null, $bcc_mail=null){
		try{
			$email = new CakeEmail();
			$email
			->emailFormat('text')
			->to($to_mail)
			->bcc($bcc_mail)
			->from($from_mail)
			->subject($subject);

			if($email->send($body)){
				return true;
			}else{
				return false;
			}

		}catch(Exception $e) {
			$this->log($e->getMessage(), LOG_ERR);
			return false;
		}
	}

}