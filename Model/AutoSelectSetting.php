<?php
class AutoSelectSetting extends AppModel {

	public $validate = array();




	public $csvFormat = array(
	);



	/**
	 * 選定フラグの更新を行う
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 */
	public function saveSelectFlg($genre_id, $prefecture_cd) {

		$params['conditions'] = array('AutoSelectSetting.genre_id' => $genre_id, 'AutoSelectSetting.prefecture_cd' => $prefecture_cd);

		// 件数を取得
		$count = $this->find('count', $params);

		// 現在の選定フラグの位置を取得
		$data = $this->findByGenreIdAndPrefectureCdAndSelectFlg($genre_id, $prefecture_cd, 1);

		$seq_no = $data['AutoSelectSetting']['seq_no'];

		if ($seq_no == $count) {
			$seq_no = 1;
		} else {
			$seq_no++;
		}

		$data['AutoSelectSetting']['select_flg'] = 0;

		// 現在の選定フラグを「0」に
		$this->save($data, false);

		// 次の選定先の選定フラグを「1」に
		$params['conditions']['AutoSelectSetting.seq_no'] = $seq_no;
		$this->updateAll(array('select_flg' => 1), $params['conditions']);

	}
}