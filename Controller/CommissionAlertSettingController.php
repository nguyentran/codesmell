<?php
App::uses('AppController', 'Controller');

class CommissionAlertSettingController extends AppController {

	public $name = 'CommissionAlertSetting';
	// 2016.06.13 ota.r@tobila MOD START ORANGE-88 経理管理者の使用できる管理者機能を制限
//	public $helpers = array();
	public $helpers = array(
			'Form' => array(
					'className' => 'AdminForm',
			),
	);
	// 2016.06.13 ota.r@tobila MOD END ORANGE-88 経理管理者の使用できる管理者機能を制限
	public $components = array();
	public $uses = array("MCommissionAlertSetting");

	public function index() {
		$mcas = $this->MCommissionAlertSetting;

		if (isset($this->request->data['regist'])) {

			if (isset($this->request->data['data']) && is_array($this->request->data['data'])) {

				$mcas->begin();
				$mcas->deleteAll(array("1=1"), false);
				$result = true;
				if ($result) {
					$all_data = array();
					foreach ($this->request->data['data'] as $data) {
						if ($data["correspond_status"] === "") {
							continue;
						}

						$phase_list = array('tel', 'visit', 'order');
						$data['phase_name'] = $phase_list[$data['phase_id']];

						if ($data['condition_unit'] == 'M') {
							$data['condition_value_min'] = $data['condition_value'];
						}
						if ($data['condition_unit'] == 'H') {
							$data['condition_value_min'] = $data['condition_value'] * 60;
						}
						if ($data['condition_unit'] == 'D') {
							$data['condition_value_min'] = $data['condition_value'] * 1440;
						}

						$all_data[] = $data;

					}

//					echo "<pre>";
////					var_dump($all_data);
//					print_r($all_data);
//					echo "</pre>";
					
					if (!$mcas->saveAll($all_data)) {
						$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'message_inner'));
						$result = false;
					} else {
						$this->Session->setFlash(__('SuccessRegist', true), 'default', array('class' => 'message_inner'));
					}
					/*
				foreach ($this->request->data['data'] as $data) {
				if ($data["correspond_status"] === "") {
				continue;
				}
				$data = array("MCommissionAlertSetting" => $data);
				echo "<pre>";
				var_dump($data);
				echo "</pre>";
				if (!$mcas->save($data)) {
				$result = false;
				break;
				}
				}
				 */
				}
				if ($result) {
					$mcas->commit();
				} else {
					// エラーチェック
					$mcas->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			}
		}

		$results = array();
		$phase_ids = array(0, 1, 2);
		foreach ($phase_ids as $phase_id) {
			$results[$phase_id] = $mcas->find("all",
				array(
					'conditions' => array(
						'MCommissionAlertSetting.phase_id' => $phase_id,
					),
					"order" => array("MCommissionAlertSetting.id"),
				)
			);
			foreach ($results[$phase_id] as &$res) {
				$res = $res["MCommissionAlertSetting"];
			}
		}
		$this->set('validaterror', $mcas->validationErrors);
		$this->set('phase_ids', $phase_ids);
		$this->set('results', $results);
	}

}
