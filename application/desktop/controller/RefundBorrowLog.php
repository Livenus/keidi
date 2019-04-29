<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;
use think\Exception;

/**
 * @title 借还款记录
 * @type menu
 * @login 1
 */
class RefundBorrowLog extends Common {
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->Oper = self::$realname;
	}
	//借款(还款)
	/**
	 * @title 借款(还款)录入
	 * @type interface
	 * @login 1
	 * @param log_time 日期时间
	 * @param sc 装卖店经销商标号
	 * @param typeid 借款还款的类型id
	 * @param direction 借款或者还款
	 * @param memo 备注
	 * @param amount 借还款金额
	 * @param id 指定还款记录的id
	 */
	public function addlog() {
		$this->verify_param();
		$re = $this->verifySC($this->input['sc']);
		if (!$re) {
			return err(9000, '专卖店或者经销商号格式不对!');
		}
		if ($this->input['amount'] <= 0) {
			return err(9000, '金额不能为0!');
		}
		unset($this->input['usertoken']);
		return $this->distinguish_direction();
	}
	//输入专卖店或者经销商编号返回对应名称
	/**
	 * @title 输入专卖店或者经销商编号返回对应名称
	 * @type interface
	 * @login 1
	 * @param sc 专卖店或者经销商编号
	 */
	public function get_scname() {
		$sc = strtoupper($this->input['sc']);
		$check = $this->validate($this->input, ["sc" => "require"], ["sc" => "请填写专卖店或者经销商号!"]);
		if ($check !== true) {
			return err(9000, $check);
		}
		$re = $this->verifySC($sc);
		if (!$re) {
			return err(9000, '专卖店或者经销商号格式不对!');
		}
		if (strlen($sc) == 5) {
			//代表是专卖店
			$sc = substr($sc, 2);
			$OwnerName = model("MarketShop")->getByWhereOne(['Shopno' => $sc], 'OwnerName');
			if ($OwnerName) {
				$scname = $OwnerName['OwnerName']; //专卖店店主姓名
			} else {
				return err(9000, '查不到此专卖店对应店主,请核对专卖店编号!');
			}

		} else {
			$CustomerID = model("Customer")->getByWhereOne(['CustomerNO' => $sc], 'CustomerID');
			if (!$CustomerID) {
				return err(9000, '没有此会员编号!');
			}
			$CustomerID = $CustomerID['CustomerID'];
			//会员姓名
			$CustomerName = model("CustomerInfo")->getByWhereOne(['CustomerID' => $CustomerID], 'CustomerName');
			$scname = $CustomerName['CustomerName'];
		}
		return suc($scname);
	}
	//返回还款列表
	/**
	 * @title 还款列表
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function get_refund_list() {
		$where = $this->get_condition();
		$refund_info = model('RefundBorrowLog')->getlistAC($where);
		if (!$refund_info) {
			return err(9000, '暂无还款记录!');
		}
		if ($this->input['id']) {
			$borrow_info = model('RefundBorrow')->getByWhere(['id' => $this->input['id']]);
		} else {
			foreach ($refund_info as $k => $v) {
				$id[$k] = $v['refundborrow_id'];
			}
			$map['id'] = ["in", $id];
			//var_export($map);die();
			$borrow_info = model('RefundBorrow')->getByWhere($map);
		}
		//var_export($borrow_info);die();
		$data['refund_info'] = $refund_info;
		$data['borrow_info'] = $borrow_info;
		return suc($data);
	}
	//还款列表删除
	/**
	 * @title 还款列表删除按钮
	 * @type interface
	 * @login 1
	 * @param id 选择的删除的数据的id
	 * @param amount 选择的删除数据的金额
	 * @param refundborrow_id 选择删除数据的refundborrow_id
	 * @return [type] [description]
	 */
	public function del_refund() {
		$rule = [
			"id" => "require",
			"refundborrow_id" => "require",
			"amount" => "require",
		];
		$msg = [
			"id" => "请选择删除数据的id",
			"refundborrow_id" => "请选择删除数据的refundborrow_id",
			"amount" => "请选择删除数据金额!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$a = model('RefundBorrowLog')->del(['id' => $this->input['id']]);
			if (!$a) {
				throw new Exception("删除数据失败", 1);
			}
			$result = model('RefundBorrow')->getByWhereOne(['id' => $this->input['refundborrow_id']]);
			if (empty($result)) {
				throw new Exception("没有对应的借款记录,请联系Eirc!", 2);
			}
			$data['surplus_money'] = $result['surplus_money'] + $this->input['amount'];
			if ($data['surplus_money'] == 0) {
				$data['is_over'] = 2; //还清
			} else {
				$data['is_over'] = 1; //未还清
			}
			$b = model('RefundBorrow')->editById($data, $this->input['refundborrow_id']);
			if ($b['stat'] != 1) {
				throw new Exception("修改对应借款数据失败,请重新操作!", 7);
			}
			Db::commit();
			return suc('删除成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
	}
	//判断方向是借款或者还款分别处理
	private function distinguish_direction() {
		$direction = $this->input['direction'];
		if ($direction == 1) {
			//1代表借款,2代表主动还款,3代表被动扣款
			//插入数据
			$this->borrow_insert();
			return suc('借款录入成功!');
		} else {
			if (!$this->input['id']) {
				return err(9000, '请选择借款记录!');
			}
			$surplusmoney = $this->get_surplus_money($this->input['id']);
			//die($surplusmoney);
			//判断还款金额是否大于剩余未还款金额
			$amount = $this->input['amount'];

			if ($amount > $surplusmoney) {
				return err(9000, "还款金额" . $amount . "大于此借款单剩余未还款金额" . $surplusmoney . "请重新选择借款记录或者重新输入还款金额!");
			}
			//插入数据
			$this->refund_insert($this->input['id'], $amount, $surplusmoney);
			return suc('还款录入操作成功!');
		}
	}
	//查询对应id的借款记录剩余还款金额
	private function get_surplus_money($id) {
		$surplusmoney = model('RefundBorrow')->getsurplus_money(['id' => $id]);
		$surplusmoney = $surplusmoney['surplus_money'];
		if ((bool) $surplusmoney === false) {
			return err(9000, '剩余还款余额为0,请联系Eric或者从新选择借款记录!');
		}
		return $surplusmoney;
	}
	//借款操作时插入数据
	private function borrow_insert() {
		$result['type_id'] = $data['type_id'] = $this->input['typeid'];
		$result['oper'] = $data['oper'] = $this->Oper;
		$result['memo'] = $data['memo'] = $this->input['memo'] ?: null;
		$result['date'] = $data['log_time'] = $this->input['log_time'];
		$result['sc'] = $data['sc'] = strtoupper($this->input['sc']);
		$data['direction'] = $this->input['direction'];
		$result['total_money'] = $result['surplus_money'] = $data['amount'] = $this->input['amount'];
		$result['is_over'] = 1;
		//插入修改数据
		Db::startTrans();
		try {
			$a = model('RefundBorrow')->add($result);
			if ($a['stat'] != 1) {
				throw new \Exception("向表RefundBorrowLog中添加记录失败!", 3);
			}
			$b = model('RefundBorrowLog')->add($data);
			if ($b['stat'] != 1) {
				throw new Exception("向表RefundBorrow中添加记录失败!", 4);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}

	}
	//还款操作时插入修改数据
	private function refund_insert($id, $amount, $surplusmoney) {
		$data['type_id'] = $this->input['typeid'];
		$data['oper'] = $this->Oper;
		$data['memo'] = $this->input['memo'] ?: null;
		$data['log_time'] = $this->input['log_time'];
		$data['sc'] = strtoupper($this->input['sc']);
		$data['direction'] = $this->input['direction'];
		$data['amount'] = $amount;
		$result['surplus_money'] = $surplusmoney - $amount;
		$result['is_over'] = 1;
		$data['refundborrow_id'] = $id;
		if (!$result['surplus_money']) {
			$result['is_over'] = 2;
		}
		//插入修改数据
		Db::startTrans();
		try {
			$a = model('RefundBorrowLog')->add($data);

			if ($a['stat'] != 1) {
				throw new \Exception("向表RefundBorrowLog中添加记录失败!", 5);
			}
			$b = model('RefundBorrow')->editById($result, $id);
			if ($b['stat'] != 1) {
				throw new Exception("更改表RefundBorrow中记录失败!", 6);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}

	}
	//验证前端参数
	private function verify_param() {
		$rule = [
			"log_time" => "require",
			"sc" => "require",
			"typeid" => "require|\d",
			"direction" => "require|\d",
			"amount" => "require",
		];
		$msg = [
			"log_time" => "时间日期必须填写!",
			"sc" => "请填写专卖店或者经销商号!",
			"typeid" => "请选择类型",
			"direction" => "请选择方向",
			"amount" => "请填写金额!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
	}
	//拼接查询还款列表的查询条件
	private function get_condition() {
		$map = [];
		if ($this->input['sc']) {
			$re = $this->verifySC($this->input['sc']);
			if (!$re) {
				return err(9000, '专卖店或者经销商号格式不对!');
			}
			$map['sc'] = strtoupper($this->input['sc']);
		}
		if ($this->input['typeid']) {
			$map['type_id'] = $this->input['typeid'];
		}
		if ($this->input['from'] && $this->input['to']) {
			$map['log_time'] = ["between", "{$this->input['from']},{$this->input['to']}"];
			//$mapstr = "date>='" . $this->input['from'] . "' and date<='" . $this->input['to'] . "'";
		}
		if ($this->input['id']) {
			$map['refundborrow_id'] = $this->input['id'];
		}
		$map['direction'] = ["neq", 1];
		//$data['mapstr'] = $mapstr;
		return $map;
	}
}