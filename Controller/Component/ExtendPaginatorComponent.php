<?php
App::uses('PaginatorComponent','Controller/Component');
/**
 * Paginatorのデフォルトのソートを拡張
 *
 * Class ExtendPaginatorComponent
 */
class ExtendPaginatorComponent extends PaginatorComponent
{

    /**
     * 付与したorderの追加文字列
     * paginateメソッド後に$this->Controller->request['paging']を戻すために保持
     * @var
     */
    public $appendString;

    /**
     * orderを付与するキー(sort名)、値を設定
     * ex)
     *  DemandInfo.auction => array('desc'=>'NULLS FIRST','asc'=>'NULLS LAST')
     * @var array
     */
    public $appendOrder = array();

    /**
     * paginateメソッドを拡張
     * asc NULLS LAST
     * desc NULLS FIRST
     * などのnullsの条件を追加で付与する
     *
     * @param null $object
     * @param array $scope
     * @param array $whitelist
     */
	public function validateSort(Model $object, array $options, array $whitelist = array()) {
        $options = parent::validateSort($object, $options, $whitelist);
        if (!isset($options['sort'])) {
            return $options;
        }
        if (array_key_exists($options['sort'], $this->appendOrder)) {
            $key = $options['sort'];
            if ($options['direction'] == 'asc') {
                $this->appendString = ' ' . $this->appendOrder[$key]['asc'];
                $options['order'][$key] = 'asc' . $this->appendString;
            }
            if ($options['direction'] == 'desc') {
                $this->appendString = ' ' . $this->appendOrder[$key]['desc'];
                $options['order'][$key] = 'desc' . $this->appendString;
            }
        }
        return $options;
    }

    /**
     * paginate
     * 追加したorderの条件がPaginatorHelperに引き継がないように変数を戻す
     *
     * @param null $object
     * @param array $scope
     * @param array $whitelist
     */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
        $results = parent::paginate($object, $scope, $whitelist);
        $paging = $this->Controller->request['paging'];
        foreach ($paging as $key => &$options) {
            if (isset($options['order']) && is_array($options['order'])) {
                foreach ($options['order'] as $sortKey => $value) {
                    $options['order'][$sortKey] = str_replace($this->appendString, '', $value);
                }
            }
            if (isset($options['options']['order']) && is_array($options['options']['order'])) {
                foreach ($options['options']['order'] as $sortKey => $value) {
                    $options['options']['order'][$sortKey] = str_replace($this->appendString, '', $value);
                }
            }
        }
        $this->Controller->request['paging'] = $paging;
        return $results;
    }
}
