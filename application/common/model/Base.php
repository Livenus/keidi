<?php

namespace app\common\model;

use think\Db;
use think\Model;

class Base extends Model {
	public $Procedure;
	public static $ProductNumber = 43;
	protected function initialize() {
		$this->Procedure = model("Procedure");
		parent::initialize();

	}
	//列表获取分页显示
	public function get_limitlist($where = array(), $field = '*', $order = '', $offset = 0, $limit = 50) {

		$list = $this->where($where)->field($field)->order($order)->limit($offset, $limit)->select();
		$data['rows'] = $this->where($where)->count();
		$data['list'] = $this->obj2data($list);
		return $data;
	}
	//列表获取
	public function getlistAC($where = array(), $field = '*', $order = '') {
		$list = $this->where($where)->field($field)->order($order)->select();
		$data = $this->obj2data($list);
		$data = trimarray($data);
		return $data;
	}
	public function getlist($where = array(), $field = '*', $order = '', $page = null, $limit = 100) {
		$list = $this->where($where)->field($field)->order($order)->limit($limit)->select();
		return $this->obj2data($list);
	}

	//数据添加
	public function addItem($data, $mod = "") {
		if (empty($mod)) {
			$mod = $this;
		}
		$pk = $mod->getPk();
		$mod->data($data);
		try {
			$res = $mod->save();
			if ($res == 1) {
				return suc(array($pk => $mod->$pk));
			} else {
				throw new \Exception('出错了' . serialize($res));
			}
		} catch (\Exception $e) {
			return err(0, $e->getMessage());
		}
	}
	//更新数据
	public function editById($data, $id) {
		$pk = static::getPk();
		try {
			$res = static::where($pk, $id)->update($data);
			if (is_numeric($res)) {
				return suc("成功");
			} else {
				throw new \Exception('出错了' . $res);
			}
		} catch (\Exception $e) {
			return err(0, $e->getMessage());
		}
	}
	public function editByMap($data, $where) {
		$pk = static::getPk();
		if ($this->getCount($where) === 0) {
			return err(2000, "没有此条数据");
		}
		try {
			$res = static::where($where)->update($data);
			if (is_numeric($res)) {
				return suc("成功");
			} else {
				throw new \Exception('出错了' . $res);
			}
		} catch (\Exception $e) {
			return err(0, $e->getMessage());
		}
	}
	public function getById($id, $field = '*') {
		$pk = static::getPk();
		$item = static::where(array($pk => $id))->field($field)->limit(1)->select();
		$data = $item ? $item[0]->data : array();
		$data = trimarrayone($data);
		return $data;
	}
	function test_print($item2, $key) {
		echo "$key. $item2<br />\n";
	}
	//查询返回全部数据(返回数组格式)
	public function getByWhere($where, $field = '*', $limit = "0,100", $order = "", $fitter = "") {
		if ($fitter) {
			$items = static::where($where)->field($fitter, true)->order($order)->limit($limit)->select();
		} else {
			$items = static::where($where)->field($field)->order($order)->limit($limit)->select();
		}
		$data = $this->obj2data($items);
		$data = trimarray($data);
		return $data;
	}
	//查询返回一条数据
	public function getByWhereOne($where, $field = '*', $fitter = "", $order = "") {
		if ($fitter) {
			$items = static::where($where)->field($fitter, true)->order($order)->find();
		} else {
			$items = static::where($where)->field($field)->order($order)->find();
		}
		$data = $this->obj2data($items);
		$data = trimarrayone($data);
		return $data;
	}

	public function getCount($where) {
		$items = static::where($where)->count();
		return (int) $items;
	}
	//返回的结果集转化为数组
	protected function obj2data($obj) {
		$data = array();
		if (empty($obj)) {

		} else if (is_array($obj)) {
			foreach ($obj as $k => &$v) {
				$data[] = $v->data;
			}
		} else {
			$data = $obj->data;
		}
		return $data;
	}
	public function query($sql) {
		$data = Db::query($sql);
		return $data;
	}
	public function execute($sql) {
		$data = Db::execute($sql);
		return $data;
	}
	public function del($where) {
		return $this->where($where)->delete();
	}
	//生成新的主键id
	public function newid() {
		$pk = static::getPk();
		$data = $this->max($pk);
		return $data + 1;
	}

}
