<?php
App::uses('CommissionAlertTask', 'Console/Command/Task');

class CommissionAlertTelTask extends CommissionAlertTask
{

    public function execute($alert)
    {
        $this->out("This is Phase of Tel", 1, Shell::VERBOSE);
        if ($alert['MCommissionAlert']['correspond_status'] == self::STATUS_DEFAULT) {
            return $this->detectDefault($alert);
        }
        return $this->detectStatus($alert);
    }

    /**
     * isSkipTimeCondition
     * 電話対応の状態であるかを判定し処理を継続するかチェックする
     *
     * @return bool true:非対象 false:監視対象
     */
    public function isSkipTimeCondition($commission_info)
    {
        //比較に必要なデータを取得
        //電話対応の訪問日時
        $visitDesiredTime = $commission_info['CommissionInfo']['visit_desired_time'];
        //訪問日時データ
        $visitTimeData = $this->VisitTime->findById($commission_info['CommissionInfo']['commission_visit_time_id']);
        //訪問希望日時時間指定
        $visitTime = (isset($visitTimeData['VisitTime']['visit_time'])) ? $visitTimeData['VisitTime']['visit_time'] : false;

        // 電話対応の訪問日時の入力があれば訪問のタスクなのでスキップ
        if (!empty($visitDesiredTime)) {
            return true;
        }
        // 訪問日時の時間指定の入力があれば訪問のタスクなのでスキップ
        if ($visitTime) {
            return true;
        }

        return false;
    }

    /**
     * detectDefault
     * アラートメール設定
     * 電話対応1行目 初回連絡希望日時からx分後に履歴の更新が出来ていない場合アラートの判別
     *
     * @param $alert
     * @return bool
     */
    public function detectDefault($alert)
    {
        $this->out("This is Status of default", 1, Shell::VERBOSE);
        $commission_info = $this->CommissionInfo->findById($alert['MCommissionAlert']['commission_id']);
        if (!$commission_info) {
            return false;
        }
        if ($this->isSkipTimeCondition($commission_info)) {
            return false;
        }
        //案件データ
        $demandInfoData = $this->DemandInfo->findById($commission_info['CommissionInfo']['demand_id']);

        //履歴が存在する場合は対象外
        $param = array('conditions' => array('commission_id = ' . $commission_info['CommissionInfo']['id']));
        $tel_supports = $this->CommissionTelSupport->find('all', $param);
        if ($tel_supports) {
            $this->out("I found some logs.", 1, Shell::VERBOSE);
            return false;
        }
        //初回連絡希望日時からアラートマスタに設定されている時間経過した場合はアラート発生とする
        $targetTime = '';
        if (isset($demandInfoData['DemandInfo']['contact_desired_time'])) {
            $targetTime = $demandInfoData['DemandInfo']['contact_desired_time'];
        }
        if (isset($demandInfoData['DemandInfo']['contact_desired_time_from'])) {
            $targetTime = $demandInfoData['DemandInfo']['contact_desired_time_from'];
        }
        if (isset($visitTimeDate['VisitTime']['visit_adjust_time'])) {
            $targetTime = $visitTimeDate['VisitTime']['visit_adjust_time'];
        }
        // もし比較する時間が取れなければスキップ
        if ($targetTime) {
            return false;
        }
        $now = new DateTime();
        $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['condition_value'],
            $alert['MCommissionAlert']['condition_unit']);
        $contract_desired_time = new DateTime($targetTime);
        $limit_date = $contract_desired_time->add(new DateInterval($date_format));
        //すでに加盟店にメール送信済みの場合は、後追い時間で判定する
        if ($alert['MCommissionAlert']['commission_send_flg'] == 1) {
            $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['rits_follow_datetime'], 'M');
            $limit_date->add(new DateInterval($date_format));
        }

        $this->out("$date_format ({$limit_date->format('YmdHis')} < {$now->format('YmdHis')})", 1, Shell::VERBOSE);
        if ($limit_date->format('YmdHis') < $now->format('YmdHis')) {
            return true;
        }

        $this->out("NO alert is occurred", 1, Shell::VERBOSE);

        return false;
    }

    /**
     * detectStatus
     *
     * @param $alert
     * @return bool
     */
    public function detectStatus($alert)
    {
        $this->out("This is Status of others[{$alert['MCommissionAlert']['correspond_status']}]", 1, Shell::VERBOSE);
        $commission_info = $this->CommissionInfo->findById($alert['MCommissionAlert']['commission_id']);
        if (!$commission_info) {
            return false;
        }

        if ($this->isSkipTimeCondition($commission_info)) {
            return false;
        }
        //履歴が存在しない場合は対象外
        $param = array(
            'conditions' => array('commission_id = ' . $commission_info['CommissionInfo']['id']),
            'order' => array('modified desc')
        );
        $tel_supports = $this->CommissionTelSupport->find('all', $param);
        if (!$tel_supports) {
            $this->out("I found no log.", 1, Shell::VERBOSE);
            return false;
        }
        //最新の履歴が指定のステータスでない場合は対象外
        if ($tel_supports[0]['CommissionTelSupport']['correspond_status'] != $alert['MCommissionAlert']['correspond_status']) {
            $this->out("It is not same status.", 1, Shell::VERBOSE);
            return false;
        }
        //履歴から指定された時間経過している場合はアラート発生とする
        $now = new DateTime();
        $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['condition_value'],
            $alert['MCommissionAlert']['condition_unit']);
        $modified = new DateTime($tel_supports[0]['CommissionTelSupport']['modified']);
        $limit_date = $modified->add(new DateInterval($date_format));
        //すでに加盟店にメール送信済みの場合は、後追い時間で判定する
        if ($alert['MCommissionAlert']['commission_send_flg'] == 1) {
            $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['rits_follow_datetime'], 'M');
            $limit_date->add(new DateInterval($date_format));
        }
        if ($limit_date->format('YmdHis') < $now->format('YmdHis')) {
            return true;
        }

        $this->out("NO alert is occurred.", 1, Shell::VERBOSE);

        return false;
    }
}
