<?php
App::uses('AppShell', 'Console/Command');

class AuctionAutoCallShell extends AppShell{

	public $tasks = array('AuctionAutoCall');
	private static $user = 'system';

	public function startup(){
		parent::startup();
	}

	public function getOptionParser(){
		$parser = parent::getOptionParser();
		$parser->addArgument('auto_called', array(
				'help' => 'Target auto call type. (first or auto called)',
				'required' => false,
				'choices' => array('first', 'called'), // first: 初回のみを対象, called: 実施済みを対象
		));

		return $parser;
	}

	public function main(){

		try{
			$this->log('入札案件オートコール処理 start', SHELL_LOG);

			// 初回 or 定時オートコール
			$auto_called = Hash::get($this->args, '0', 'first') === 'called';

			// オートコール発信処理
			// オートコール済みの加盟店に対してオートコール発信を行う
			$this->AuctionAutoCall->execute($auto_called);

			$this->log('入札案件オートコール処理 end', SHELL_LOG);
		}catch(Exception $e){
			$this->log($e, SHELL_LOG);
		}
	}
}