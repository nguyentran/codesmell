<?php

App::uses('AppController', 'Controller');

class WebviewDemandListController extends AppController {

    public $name = 'WebviewDemandList';
    public $uses = array('DemandInfo', 'CommissionInfo', 'MCorp', 'MSite', 'MGenre', 'MCategory', 'MUser');

    public function beforeFilter() {
        parent::beforeFilter();
    }

    public function index($user_id) {


        // 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(S)
        $this->redirect('/auction');
        // 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(E)

        //セッションから"app_logout"の値を取得(携帯端末側でログアウトの処理をする際に必要)
        $app_logout = $this->Session->read('app_logout');
        if (!$app_logout) {
            $this->Session->write('app_logout', 'app_logout');
            $app_logout = 'app_logout';
        }

        //ユーザIDに紐づく加盟店IDを取得
        $opts = array();

        $opts['conditions'] = array(
            'MUser.user_id' => $user_id
        );
        $opts['fields'] = array(
            'MUser.affiliation_id'
        );
        //加盟店ID
        $affiliation_id = $this->MUser->find('first', $opts);

// murata.s ORANGE-415 CHG(S)
// 2016.12.27 CHG(S)
// 2016.11.17 murata.s ORANGE-185 CHG(S)
        $sql = "SELECT DISTINCT ON (DI.id)
                 DI.id AS demand_info_id,
                 selection_system,
                 customer_name,
                 (SELECT site_name FROM m_sites WHERE id = DI.site_id),
                 (SELECT genre_name FROM m_genres WHERE id = DI.genre_id),
                 CI.id AS commission_info_id,
                 CI.corp_id,
                 commission_note_send_datetime,
                 CI.app_notread,
                 first_display_time
            FROM demand_infos DI
            LEFT OUTER JOIN commission_infos CI
              ON CI.corp_id = {$affiliation_id['MUser']['affiliation_id']}
             AND CI.demand_id = DI.id
            LEFT OUTER JOIN auction_infos AI
              ON AI.corp_id = {$affiliation_id['MUser']['affiliation_id']}
             AND AI.demand_id = DI.id
            WHERE DI.del_flg = 0
              AND (
                    (
                        selection_system IN (0, 4)
                    AND CI.commit_flg = 1
                    AND CI.lost_flg != 1
                    AND CI.del_flg = 0
                    AND CI.introduction_not != 1
                    AND CI.commission_note_send_datetime NOTNULL
                    )
                    OR
                    (
                        selection_system IN (2, 3)
                    AND CI.id IS NULL
                    AND NOT EXISTS (
                        SELECT *
                        FROM commission_infos CI2
                        WHERE CI2.demand_id = DI.id
                          AND CI2.corp_id != {$affiliation_id['MUser']['affiliation_id']}
                          AND CI2.commit_flg = 1)
                    AND AI.push_flg = 1
                    )
                )
            ORDER BY DI.id desc
            LIMIT 50";
// 2016.11.17 murata.s ORANGE-185 CHG(E)
// 2016.12.27 CHG(E)
// murata.s ORANGE-415 CHG(E)

        $results = $this->DemandInfo->query($sql, false);

$this->log($results, LOG_DEBUG);
$this->log($this->DemandInfo->getDataSource()->getLog(), LOG_DEBUG);

        $app_notread_cnt = 0;
        foreach ($results as $key => $result) {
            if ($result[0]['app_notread'] == 1) {
                //未読数(手動選定方式)
                $app_notread_cnt++;
            } elseif ($result[0]['first_display_time'] == null && $result[0]['commission_info_id'] == null) {
                //未読数(入札選定方式)
                $app_notread_cnt++;
            }
        }

        $this->layout = 'webview_list';
        $this->set('app_notread_cnt', $app_notread_cnt);
        $this->set('user_id', $user_id);
        $this->set('results', Sanitize::clean($results));
        //セッションID
        $sessionId = $this->Session->id();
        $this->set('session_id', $sessionId);

        $this->render('index');
    }

}
