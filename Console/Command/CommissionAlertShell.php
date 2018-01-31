<?php
App::uses('CommissionAlertTask', 'Console/Command/Task');
App::uses('CakeEmail', 'Network/Email');

class CommissionAlertShell extends CommissionAlertTask
{
    public $tasks = array(
        'View',
        'CommissionAlertTel',
        'CommissionAlertVisit',
        'CommissionAlertOrder'
    );

    /**
     * main
     * コマンドに --verboseを付与して実行するとメッセージを出力します
     * ex) cake commission_alert --verbose
     *
     */
    public function main()
    {

	/**
	 * 2重起動防止 自プロセスチェック
	*/
	//同じphpを起動している他のプロセスを探し
	$output = array();
	exec("ps aux | grep CommissionAlert", $output, $result);
	print_r($output);
	//5行以上見つかれば中止
	if(count($output) > 4){
		echo("CommissionAlertの多重起動はできません。[$result]\n");
		exit();
	}

        $now = new DateTime();

        $this->out("<info>【{$now->format('Y-m-d H:i:s')}】起動</info>", 1, Shell::VERBOSE);
        //データ取得 m_commission_alertsを基準に処理を行う
        // 処理対象データの条件
        //1.MCommissionAlertにデータの登録がある。
        //2.commission_infos.commit_flg = 1
        //3.commission_infos.commission_status != 3 and commission_infos.commission_status != 4
        //4.demand_infos.demand_status != 6 or demand_infos.del_flg != 1
        //5.アラートメール、後追いメール両方送信したものについても処理対象から除く
        $param = array(
            'fields' => ('MCommissionAlert.*'),
            'joins' => array(
                array(
                    'type' => 'inner',
                    'table' => 'commission_infos',
                    'alias' => 'CommissionInfo',
                    'conditions' => array(
                        'CommissionInfo.id = MCommissionAlert.commission_id'
                    )
                ),
                // DemandInfo.demand_statusが失注も削除対象
                array(
                    'fields' => 'DemandInfo.id,DemandInfo.demand_status,DemandInfo.del_flg',
                    'type' => 'inner',
                    "table" => "demand_infos",
                    "alias" => "DemandInfo",
                    "conditions" => array(
                        "DemandInfo.id = CommissionInfo.demand_id",
                    )
                ),
            ),
            'conditions' => array(
                'CommissionInfo.commit_flg = 1',
                'CommissionInfo.lost_flg = 0',
                'CommissionInfo.del_flg = 0',
                'CommissionInfo.commission_status != 3',
                'CommissionInfo.commission_status != 4',
                'CommissionInfo.commission_status != 5',
                "DemandInfo.del_flg != 1",
                'DemandInfo.demand_status != 6',
                'OR' => array(
                    'commission_send_flg = 0',
                    'rits_send_flg = 0'
                )
            ),
            'order' => array('commission_id', 'phase_id')
        );
        $alerts = $this->MCommissionAlert->find('all', $param);
        $d = array();
        $commission_id = 0;
        $phase_id = -1;

        foreach ($alerts as $alert) {
            $this->out("-----" . $alert['MCommissionAlert']['commission_id'] . "-----", 1, Shell::VERBOSE);

            if ($this->__occurCommissionAlert($alert)) {
                $this->__sendAlertMail($alert['MCommissionAlert']);
                $this->out("<info>アラート発生</info>", 1, Shell::VERBOSE);

                $this->__saveCommissionFlgSupport($alert['MCommissionAlert']['commission_id'],
                    (($alert['MCommissionAlert']['phase_id'] == self::PHASE_TEL) ? 1 : 0),
                    (($alert['MCommissionAlert']['phase_id'] == self::PHASE_VISIT) ? 1 : 0),
                    (($alert['MCommissionAlert']['phase_id'] == self::PHASE_ORDER) ? 1 : 0));

            }
        }

        $now = new DateTime();
        $this->out("<info>【{$now->format('Y-m-d H:i:s')}】終了</info>", 1, Shell::VERBOSE);
    }

    private function __occurCommissionAlert($alert)
    {
        if ($alert['MCommissionAlert']['phase_id'] == self::PHASE_TEL) {
            return $this->CommissionAlertTel->execute($alert);
        }
        if ($alert['MCommissionAlert']['phase_id'] == self::PHASE_VISIT) {
            return $this->CommissionAlertVisit->execute($alert);
        }
        if ($alert['MCommissionAlert']['phase_id'] == self::PHASE_ORDER) {
            return $this->CommissionAlertOrder->execute($alert);
        }

        return false;
    }

    private function __sendAlertMail($alert)
    {
        //取次情報
        $commission_info = $this->CommissionInfo->findById($alert['commission_id']);
        //案件情報
        $param = array(
            'fields' => array('DemandInfo.*', 'MSite.site_name', 'MGenre.genre_name'),
            'joins' => array(
                array(
                    'table' => 'm_sites',
                    'alias' => 'MSite',
                    'type' => 'inner',
                    'conditions' => array('MSite.id = DemandInfo.site_id')
                ),
                array(
                    'table' => 'm_genres',
                    'alias' => 'MGenre',
                    'type' => 'inner',
                    'conditions' => array('MGenre.id = DemandInfo.genre_id')
                )

            ),
            'conditions' => array('DemandInfo.id = ' . $commission_info['CommissionInfo']['demand_id'])

        );
        $demand_info = $this->DemandInfo->find('first', $param);
        //企業情報
        $corp = $this->MCorp->findById($commission_info['CommissionInfo']['corp_id']);


        $config = Configure::read('commission_alert_mail_setting');
        $subject = sprintf(Util::getDivText('commission_alert_mail_setting', 'title'),
            $commission_info['CommissionInfo']['demand_id']);
        $from = array($config['from_address'] => $config['from_name']);

        if ($alert['commission_send_flg'] == 0) {
            //企業に送る
            try {
//kai                $corp['MCorp']['mailaddress'] = $corp['MCorp']['mailaddress_pc'] . ';' . $corp['MCorp']['mailaddress_mobile'];
                $corp['MCorp']['mailaddress'] = 'kai@8em.jp';
                $emails = explode(";", $corp['MCorp']['mailaddress']);
                $Email = new CakeEmail('default');
                $Email->from($from);
                $Email->subject($subject);
                $Email->template("commission_alert_corp");
                $Email->viewVars(array(
                    'commission_id' => $commission_info['CommissionInfo']['id'],
                    'data' => $demand_info + $commission_info,
                ));
                foreach ($emails as $email) {
                    $Email->to($email);
//kai                    $Email->bcc("timeout@rits-c.jp");
//kai                    $Email->bcc("nobuhisa.kai@gmail.com");
                    $Email->send();
                }
            } catch (Exception $e) {
                $this->log($e->getMessage(), LOG_CRIT);
            }
        } else {
            //管理者に送る
            try {
//kai                $emails = array("timeout@rits-c.jp");
                $emails = array("kai@8em.jp");
                $Email = new CakeEmail('default');
                $Email->from($from);
                $Email->subject($subject);
                $Email->template("commission_alert_system");
                $Email->viewVars(array(
                    'commission_id' => $commission_info['CommissionInfo']['id'],
                    'data' => $demand_info + $commission_info,
                ));
                foreach ($emails as $email) {
                    $Email->to($email);
                    $Email->send();
                }
            } catch (Exception $e) {
                $this->log($e->getMessage(), LOG_CRIT);
            }
        }

        //CommissionInfoのフラグ更新処理
        $this->__saveAlertSendFlg($alert['id'],
            (($alert['commission_send_flg'] == 0) ? 1 : 0),
            (($alert['commission_send_flg'] == 1) ? 1 : 0)
        );
    }

    private function __saveAlertSendFlg($id, $commission = 0, $rits = 0)
    {
        $malert = array(
            'MCommissionAlert' => array(
                'id' => $id
            )
        );
        if ($commission > 0) {
            $malert['MCommissionAlert']['commission_send_flg'] = 1;
        }
        if ($rits > 0) {
            $malert['MCommissionAlert']['rits_send_flg'] = 1;
        }
        $malert['MCommissionAlert']['modified_user_id'] = 'system';
        $malert['MCommissionAlert']['modified'] = date('Y-m-d H:i:s');

        $this->MCommissionAlert->save($malert);
    }

    private function __saveCommissionFlgSupport($id, $tel = 0, $visit = 0, $order = 0)
    {
        $commission = array('CommissionInfo' => array());
        $modified_fields = array();
        if ($tel > 0) {
            $commission['CommissionInfo']['tel_support'] = 1;
            $modified_fields[] = 'tel_support';
        }
        if ($visit > 0) {
            $commission['CommissionInfo']['visit_support'] = 1;
            $modified_fields[] = 'visit_support';
        }
        if ($order > 0) {
            $commission['CommissionInfo']['order_support'] = 1;
            $modified_fields[] = 'order_support';
        }

        $modified_fields[] = 'modified_user_id';
        $modified_fields[] = 'modified';

        $commission['CommissionInfo']['id'] = $id;
        $commission['CommissionInfo']['modified_user_id'] = 'system';
        $commission['CommissionInfo']['modified'] = date('Y-m-d H:i:s');

        $this->CommissionInfo->save($commission, false, $modified_fields);
    }
}
