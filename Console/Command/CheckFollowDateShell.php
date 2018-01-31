<?php
App::uses('AppShell', 'Console/Command');

class CheckFollowDateShell extends AppShell
{
	public $uses = array('DemandInfo');

	public function main(){
		try{
			$this->log('後追い日時削除処理 start', SHELL_LOG);
			$this->execute();
			$this->log('後追い日時削除処理 end', SHELL_LOG);

		}catch(Exception $e){
			$this->log($e, SHELL_LOG);
		}
	}

	/**
	 * 後追い日時を過ぎた場合、後追い日時を削除する
	 */
	public function execute(){

		$this->DemandInfo->unbindModelAll(false);
		$fields = array('follow_date' => null);
		$conditions = array(
				'DemandInfo.follow_date is not null ',
				'DemandInfo.follow_date != ' => '',
				'DemandInfo.follow_date < ' => date('Y/m/d H:i'),
				'DemandInfo.del_flg' => 0
		);
		$this->DemandInfo->updateAll($fields, $conditions);
	}
}