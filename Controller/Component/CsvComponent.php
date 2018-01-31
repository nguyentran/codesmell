<?php
App::uses('Component', 'Controller');
class CsvComponent extends Component {
	public $current_controller;

	public function startup(Controller $controller) {
		$this->current_controller = $controller;
	}

	public function download($model, $target, $file_name, $data_list){

		App::import('Model', $model);
		$field_list = $this->current_controller->$model->csvFormat[$target];

		$this->current_controller->set('data_list', $data_list);
		$this->current_controller->set('fields', $field_list);
		$this->current_controller->set('file_name', $file_name);

		Configure::write('debug', '0');
		$this->current_controller->layout = false;

		$this->current_controller->render('/Elements/csv');

	}
}