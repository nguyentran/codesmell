<?php

class Agreement extends AppModel {

	public $useTable = 'agreement';
	public $User;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		if (empty($this->User) && !empty($_SESSION)) {
			$this->User = Hash::get($_SESSION, 'Auth.User');
		}
	}

	/**
	 * 契約約款全文を連想配列で取得
	 * @return type 契約約款全文
	 */
	public function findFullText() {
		$this->recursive = 2;
		$this->hasMany = array(
				'AgreementProvision' => array(
						'foreignKey' => 'agreement_id',
						'order' => array('sort_no'),
		));
		$this->AgreementProvision->hasMany = array(
				'AgreementProvisionsItem' => array(
						'foreignKey' => 'agreement_provisions_id',
						'order' => array('sort_no'),
		));
		$agreement = $this->find('first');
		return $agreement;
	}

	/**
	 * 契約約款全文をテキスト形式で取得
	 * @return string 契約約款全文
	 */
	public function findFullPlainText() {
		$agreement = self::findFullText();
		$agreement_text = '';
		foreach ($agreement['AgreementProvision'] as $provision) {
			$agreement_text .= $provision['provisions'] . "\r\n";
			foreach ($provision['AgreementProvisionsItem'] as $item) {
				$agreement_text .= '　' . $item['item'] . "\r\n";
			}
			$agreement_text .= "\r\n";
		}
		return $agreement_text;
	}

	/**
	 * 加盟店別の特約を含めた契約約款全文を連想配列で取得
	 * @param type $corp_id 加盟店ID
	 * @return type 契約約款全文
	 */
	public function findCustomizedText($corp_id) {
		App::import('Model', 'AgreementCustomize');
		$this->AgreementCustomize = new AgreementCustomize();
		$agreement_customizes = $this->AgreementCustomize->find('all', array(
				'conditions' => array(
						'corp_id' => $corp_id,
						'delete_flag' => false,
		)));
		$agreement = $this->findFullText();
		if (!empty($agreement_customizes)) {
			foreach (Hash::extract($agreement_customizes, '{n}.AgreementCustomize') as $patch) {
				$agreement = $this->patchedAgreement($agreement, $patch);
			}
			$agreement['AgreementProvision'] = Hash::sort($agreement['AgreementProvision'], '{n}.sort_no');
			foreach ($agreement['AgreementProvision'] as &$provision) {
				if (array_key_exists('AgreementProvisionsItem', $provision)) {
					$provision['AgreementProvisionsItem'] = Hash::sort($provision['AgreementProvisionsItem'], '{n}.sort_no');
				}
			}
		}
		return $agreement;
	}

	/**
	 * 加盟店別の特約を含めた契約約款全文をテキスト形式で取得
	 * @param type $corp_id 加盟店ID
	 * @return string 契約約款全文
	 */
	public function findCustomizedPlainText($corp_id) {
		$agreement = self::findCustomizedText($corp_id);
		$agreement_text = '';
		foreach ((array) Hash::get($agreement, 'AgreementProvision') as $provision) {
			$agreement_text .= $provision['provisions'] . "\r\n";
			foreach ((array) Hash::get($provision, 'AgreementProvisionsItem') as $item) {
				$agreement_text .= '　' . $item['item'] . "\r\n";
			}
			$agreement_text .= "\r\n";
		}
		return $agreement_text;
	}

	/**
	 * 契約約款に加盟店別の特約を適用する
	 * @param type $agreement 約款
	 * @param type $patch 特約
	 * @return type 特約適用後の約款
	 */
	private function patchedAgreement($agreement, $patch) {
		switch ($patch['table_kind']) {
			case 'AgreementProvisions':
				$agreement = $this->patchedProvisions($agreement, $patch);
				break;
			case 'AgreementProvisionsItem':
				$agreement = $this->patchedItems($agreement, $patch);
				break;
		}
		return $agreement;
	}

	/**
	 * 各条文に加盟店別の特約を適用する
	 * @param type $agreement 約款
	 * @param type $patch 特約
	 * @return type 特約適用後の約款
	 */
	private function patchedProvisions($agreement, $patch) {
		switch ($patch['edit_kind']) {
			case 'Delete':
				$agreement = Hash::remove($agreement, 'AgreementProvision.{n}[id=' . $patch['original_provisions_id'] . ']');
				break;
			case 'Add':
				$agreement['AgreementProvision'][] = $this->patchedProvision(array(), $patch);
				break;
			case 'Update':
				foreach ($agreement['AgreementProvision'] as &$provision) {
					if ($this->provisionKeyExists($provision, $patch)) {
						$provision = $this->patchedProvision($provision, $patch);
					}
				}
				break;
		}
		return $agreement;
	}

	/**
	 * 各項目に加盟店別の特約を適用する
	 * @param type $agreement 約款
	 * @param type $patch 特約
	 * @return type 特約適用後の約款
	 */
	private function patchedItems($agreement, $patch) {
		switch ($patch['edit_kind']) {
			case 'Delete':
				$agreement = Hash::remove($agreement, 'AgreementProvision.{n}.AgreementProvisionsItem.{n}[id=' . $patch['original_item_id'] . '][agreement_provisions_id=' . $patch['original_provisions_id'] . ']');
				break;
			case 'Add':
				foreach ($agreement['AgreementProvision'] as &$provision) {
					if ($this->provisionKeyExists($provision, $patch)) {
						$provision['AgreementProvisionsItem'][] = $this->patchedItem(array(), $patch);
					}
				}
				break;
			case 'Update':
				foreach ($agreement['AgreementProvision'] as &$provision) {
					if ($this->provisionKeyExists($provision, $patch)) {
						foreach ($provision['AgreementProvisionsItem'] as &$item) {
							if ($this->itemKeyExists($item, $patch)) {
								$item = $this->patchedItem($item, $patch);
							}
						}
					}
				}
				break;
		}
		return $agreement;
	}

	/**
	 * 条文ひとつに加盟店別の特約を適用する
	 * @param type $provision 条文
	 * @param type $patch 特約
	 * @return type 特約適用後の条文
	 */
	private function patchedProvision($provision, $patch) {
		$provision['id'] = $patch['original_provisions_id'];
		$provision['provisions'] = $patch['content'];
		$provision['customize_provisions_id'] = $patch['customize_provisions_id'];
		$provision['sort_no'] = $patch['sort_no'];
		return $provision;
	}

	/**
	 * 項目ひとつに加盟店別の特約を適用する
	 * @param type $item 項目
	 * @param type $patch 特約
	 * @return type 特約適用後の項目
	 */
	private function patchedItem($item, $patch) {
		$item['id'] = $patch['original_item_id'];
		$item['provisions_id'] = $patch['original_provisions_id'];
		$item['item'] = $patch['content'];
		$item['customize_item_id'] = $patch['customize_item_id'];
		$item['customize_provisions_id'] = $patch['customize_provisions_id'];
		$item['sort_no'] = $patch['sort_no'];
		return $item;
	}

	/**
	 * 特約($patch)を適用するべき条文($provision)が存在するかをチェックする
	 * @param type $provision 条文
	 * @param type $patch 特約
	 * @return boolean 適用可
	 */
	private function provisionKeyExists($provision, $patch) {
		if (Hash::get($provision, 'id') === $patch['original_provisions_id']) {
			if (!array_key_exists('customize_provisions_id', $provision) || $provision['customize_provisions_id'] === $patch['customize_provisions_id']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 特約を適用するべき項目が存在するかをチェックする
	 * @param type $item 項目
	 * @param type $patch 特約
	 * @return boolean 適用可
	 */
	private function itemKeyExists($item, $patch) {
		if (Hash::get($item, 'id') === $patch['original_item_id']) {
			if (!array_key_exists('original_item_id', $item) || $item['customize_item_id'] === $patch['customize_item_id']) {
				return true;
			}
		}
		return false;
	}

}
