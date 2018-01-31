<?php
App::uses('AppShell', 'Console/Command');

class PeriodicallyShell extends AppShell
{
	public $uses = array(
					'AffiliationInfo',
					//ORANGE-199 ADD S
					'MCorp',
					//ORANGE-100 ADD E
	);

	/**
	 * main
	 * ex) cake commission_alert_create --verbose
	 *
	 */
	public function main() {
		$this->log('PeriodicallyShell Start : main', SHELL_LOG);

		if(date("d") == '01')$this->__init_add_month_credit();
		//ORANGE-199 ADD S
		$this->__init_antisocial_display_flag();
		//ORANGE-199 ADD E
                
                // 2017/4/24 ichino ORANGE-402 ADD start
                $this->__init_license_display_flag();
                // 2017/4/24 ichino ORANGE-402 ADD end
                
		$this->log('PeriodicallyShell End : main', SHELL_LOG);
	}

	/**
	 * 当月振込前払金
	 */
	private function __init_add_month_credit(){

		$this->log('PeriodicallyShell Start : __init_add_month_credit', SHELL_LOG);

		$conditions = array( 'AffiliationInfo.id > 0' );

		$updatefield = array( 'AffiliationInfo.add_month_credit' => 0, 'AffiliationInfo.credit_mail_send_flg' => 0);

		if($this->AffiliationInfo->updateAll($updatefield, $conditions)){
			$this->log('PeriodicallyShell Success : __init_add_month_credit', SHELL_LOG);
		}else{
			$this->log('PeriodicallyShell Failure : __init_add_month_credit', SHELL_LOG);
		}

		$this->log('PeriodicallyShell End : __init_add_month_credit', SHELL_LOG);

	}

	// ORANGE-199 ADD S
	/**
	 * 反社チェックポップアップフラグの初期化
	 */
	private function __init_antisocial_display_flag(){
		$this->log('PeriodicallyShell Start : __init_antisocial_display_flag', SHELL_LOG);

		$conditions = array('MCorp.antisocial_display_flag' => 0);
		$updatefield = array('MCorp.antisocial_display_flag' => 1);

		if($this->MCorp->updateAll($updatefield, $conditions)){
			$this->log('PeriodicallyShell Success : __init_antisocial_display_flag', SHELL_LOG);
		}else{
			$this->log('PeriodicallyShell Failure : __init_antisocial_display_flag', SHELL_LOG);
		}

		$this->log('PeriodicallyShell End : __init_antisocial_display_flag', SHELL_LOG);
	}
	// ORANGE-199 ADD E

        // 2017/4/24 ichino ORANGE-402 ADD start
        /*
         * ライセンスチェックポップアップ表示フラグの初期化
         */
        // 2017/4/24 ichino ORANGE-402 ADD end
        private function __init_license_display_flag(){
            $this->log('PeriodicallyShell Start : __init_license_display_flag', SHELL_LOG);
            
            $conditions = array('MCorp.license_display_flag' => 0);
            $updatefield = array('MCorp.license_display_flag' => 1);
            
            if($this->MCorp->updateAll($updatefield, $conditions)){
			$this->log('PeriodicallyShell Success : __init_license_display_flag', SHELL_LOG);
		}else{
			$this->log('PeriodicallyShell Failure : __init_license_display_flag', SHELL_LOG);
		}

            $this->log('PeriodicallyShell End : __init_license_display_flag', SHELL_LOG);
        }
}
