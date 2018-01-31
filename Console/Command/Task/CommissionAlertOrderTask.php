<?php
App::uses('CommissionAlertTask', 'Console/Command/Task');

class CommissionAlertOrderTask extends CommissionAlertTask
{

    public function execute($alert)
    {
        $this->out("This is Phase of Order", 1, Shell::VERBOSE);
        if ($alert['MCommissionAlert']['correspond_status'] == self::STATUS_DEFAULT) {
            return $this->detectDefault($alert);
        }

        return $this->detectStatus($alert);
    }

    /**
     * detectDefault
     * アラートメール設定
     * 受注対応1行目 受注対応予定日時からx分後に履歴の更新が出来ていない場合アラートの判別
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

        //受注日時の設定がない場合は対象外
        if (strlen($commission_info['CommissionInfo']['order_respond_datetime']) == 0) {
            $this->out("CommissionInfo.order_respond_datetime not found.", 1, Shell::VERBOSE);
            return false;
        }
        //完了日の設定がある場合は対象外
        if (strlen($commission_info['CommissionInfo']['complete_date']) > 0) {
            $this->out("CommissionInfo.complete_date found.", 1, Shell::VERBOSE);
            return false;
        }
        //履歴が存在する場合は対象外
        $param = array('conditions' => array('commission_id = ' . $commission_info['CommissionInfo']['id']));
        $order_supports = $this->CommissionOrderSupport->find('all', $param);
        if ($order_supports) {
            $this->out("I found some logs.", 1, Shell::VERBOSE);
            return false;
        }
        //初回連絡希望日時からアラートマスタに設定されている時間経過した場合はアラート発生とする
        $now = new DateTime();
        $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['condition_value'],
            $alert['MCommissionAlert']['condition_unit']);
        $order_time = new DateTime($commission_info['CommissionInfo']['order_respond_datetime']);
        $limit_date = $order_time->add(new DateInterval($date_format));
        //すでに加盟店にメール送信済みの場合は、後追い時間で判定する
        if ($alert['MCommissionAlert']['commission_send_flg'] == 1) {
            $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['rits_follow_datetime'], 'M');
            $limit_date->add(new DateInterval($date_format));
        }
        $this->out("$date_format({$limit_date->format('YmdHis')} < {$now->format('YmdHis')})", 1, Shell::VERBOSE);
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

        //受注日時の設定がない場合は対象外
        if (strlen($commission_info['CommissionInfo']['order_respond_datetime']) == 0) {
            $this->out("CommissionInfo.order_respond_datetime  not found.", 1, Shell::VERBOSE);
            return false;
        }
        //完了日の設定がある場合は対象外
        if (strlen($commission_info['CommissionInfo']['complete_date']) > 0) {
            $this->out("CommissionInfo.complete_date found.", 1, Shell::VERBOSE);
            return false;
        }
        //履歴が存在する場合は対象外
        $param = array(
            'conditions' => array('commission_id = ' . $commission_info['CommissionInfo']['id']),
            'order' => array('modified desc')
        );
        $order_supports = $this->CommissionOrderSupport->find('all', $param);
        if (!$order_supports) {
            $this->out("I found no log.", 1, Shell::VERBOSE);
            return false;
        }
        //最新の履歴が指定のステータスでない場合は対象外
        if ($order_supports[0]['CommissionOrderSupport']['correspond_status'] != $alert['MCommissionAlert']['correspond_status']) {
            $this->out("It is not same status", 1, Shell::VERBOSE);
            return false;
        }
        //履歴から指定された時間経過している場合はアラート発生とする
        $now = new DateTime();
        $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['condition_value'],
            $alert['MCommissionAlert']['condition_unit']);
        $modified = new DateTime($order_supports[0]['CommissionOrderSupport']['modified']);
        $limit_date = $modified->add(new DateInterval($date_format));
        //すでに加盟店にメール送信済みの場合は、後追い時間で判定する
        if ($alert['MCommissionAlert']['commission_send_flg'] == 1) {
            $date_format = $this->__formatStringBuilder($alert['MCommissionAlert']['rits_follow_datetime'], 'M');
            $limit_date->add(new DateInterval($date_format));
        }
        if ($limit_date->format('YmdHis') < $now->format('YmdHis')) {
            return true;
        }

        $this->out("NO alert is occurred", 1, Shell::VERBOSE);

        return false;
    }
}
