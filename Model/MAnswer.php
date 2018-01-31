<?php
class MAnswer extends AppModel {

	public $validate = array(

	);

	public function dropDownAnswer($id){
		$conditions = array('inquiry_id' => $id);

		$answer = $this->find('all', array('conditions'=>$conditions, 'order'=>'MAnswer.id asc'));

		foreach ($answer as $val) {

			$answers_[] = array(
					"MAnswer" => array(
							"id" => $val['MAnswer']['id'],
							"answer_name" => $val['MAnswer']['answer_name'],
					)
			);
		}
		$answer_list = Set::Combine(@$answers_, '{n}.MAnswer.id', '{n}.MAnswer.answer_name');
		return $answer_list;
	}

}