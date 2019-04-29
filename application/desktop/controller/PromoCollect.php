<?php
namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
use think\Exception;

/**
 * @title 促销系统--产品领取
 * @type menu
 * @login 1
 */
class PromoCollect extends Common {
	public function _initialize() {
		parent::_initialize();
		$this->Oper = self::$realname;
	}

//产品领取---submit按钮
	/**
	 * @title 产品领取---submit按钮
	 * @type interface
	 * @login 1
	 * @param SC/Card
	 * @param Date 日期
	 * @param Memo 备注
	 * @param PromoType
	 * @param SaleType
	 * @param GroupID
	 * @param GID
	 * @param cell 选择的产品数据集合
	 * @return data
	 */
	public function button2_Click() {
		$input = input('post.');
		$rule = [
			'SC/Card' => 'require',
			'Date' => 'require',
			'cell' => 'require',
		];
		$msg = [
			'SC/Card' => '请填写专卖店编号或者会员编号!(Please put Sc or CardNo!)',
			'Date' => '请选择日期!',
			'cell' => '请选择产品以及数量!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		unset($input['usertoken']);
		return $this->InsertPromoCollect($input);
	}
	private function InsertPromoCollect($input) {
		$Mac = $this->Local_Mac();
		$PromoTypeID = $this->GetPromoTypeID($input['PromoType']);
		Db::startTrans();
		try {
			if ($input['GroupID'] == "") {
				if (empty($input['GID'])) {
					$input['GroupID'] = date("Y-m-d h:i:s") . "-" . $input['SC/Card'];
				} else {
					$input['GroupID'] = $input['GID'];
				}
				$PromoID = $this->GetNewID('promo_collect');
				$sql = "INSERT INTO Promo_Collect(PromoID,Collector,Status,WarehouseMark,Oper,PromoTypeID,CollectDate,Memo,Mac,GroupID,SaleType) values('" . $PromoID . "','" . $input['SC/Card'] . "','0','1','" . $this->Oper . "','" . $PromoTypeID . "','" . $input['Date'] . "','" . $input['Memo'] . "','" . $Mac . "','" . $input['GroupID'] . "','" . $input['SaleType'] . "')";
				if ($this->Exc_Sql($sql) <= 0) {
					throw new Exception("Error sql:" . $sql, 1);
				} else {
					$mark = $this->InsertPromoDetail($PromoID, $input['cell']);
				}
			} else {
				$PromoID = $this->GetStringData("SELECT PromoID from Promo_Collect where GroupID='" . $input['GroupID'] . "'");
				$sql = "UPDATE Promo_Collect set PromoID=" . $PromoID . ",Collector='" . $input['SC/Card'] . "',Oper='" . $this->Oper . "',PromoTypeID='" . $PromoTypeID . "',CollectDate='" . $input['Date'] . "',Memo='" . $input['Memo'] . "',Mac='" . $Mac . "',SaleType='" . $input['SaleType'] . "' where GroupID='" . $input['GroupID'] . "'";
				if ($this->Exc_Sql($sql) <= 0) {
					throw new Exception("Error sql:" . $sql, 2);
				} else {
					$sql = "delete from promo_collectDetail where promoid=" . $PromoID;
					if ($this->Exc_Sql($sql) <= 0) {
						throw new Exception("操作失败,请重试!", 3);
					}
					$mark = $this->InsertPromoDetail($PromoID, $input['cell']);
				}
			}
			if ($mark) {
				Db::commit();
			} else {
				throw new Exception("报单详情信息录入失败", 4);
			}
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		$data['msg'] = '操作成功';
		$data['input'] = $input;
		return suc($data);
	}
	private function GetPromoTypeID($promoname) {
		$sql = "select promoid from promotype where promoname='" . $promoname . "' ";
		$promotypeid = $this->GetStringData($sql);
		return $promotypeid;
	}
	private function InsertPromoDetail($PromoID, $data) {
		$mark = true;
		$data = json_decode($data, true);
		//var_dump($data);die('123');
		foreach ($data as $k => $v) {
			if (preg_match('/^[1-9]\d$/', $v['productid']) && preg_match('/^[1-9]\d$/', $v['amount'])) {
				$sql = "INSERT into Promo_collectDetail(promodetailID,promoID,productid,amount,oper,memo) values(" . $this->GetNewID('promo_collectdetail') . "," . $PromoID . "," . $v['productid'] . "," . $v['amount'] . ",'" . $this->Oper . "','" . $v['memo'] . "') ";
				if ($this->Exc_Sql($sql) <= 0) {
					$mark = false;
					break;
				}
			}
		}
		return $mark;
	}
	/**
	 * @title 促销系统---GoTOWareHouse按钮
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param SC/Card
	 * @param SaleType
	 * @param Date
	 * @param CustomerID
	 * @param CustomerName
	 */
	public function button1_Click() {
		$input = input('post.');
		$rule = [
			'SC/Card' => 'require',
			'Date' => 'require',
			'GroupID' => 'require',
			'CustomerID' => 'require',
			'CustomerName' => 'require',
			'SaleType' => 'require',
		];
		$msg = [
			'SC/Card' => '请填写专卖店编号或者会员编号!(Please put Sc or CardNo!)',
			'Date' => '请选择日期!',
			'GroupID' => '请填写报单编号!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$input = input('post.');
		$BorrowCheck = controller('Borrow');
		$ReportManage = controller('ReportManage');
		Db::startTrans();
		try {
			$result = $BorrowCheck->CheckIfWenttoStock($input['GroupID']); //调用Borrow控制器方法,检查是否已经进入提货系统
			if ($result) {
				//表示没有进入提货系统
				$this->GotoWarehouse($input, $this->Oper, 0, 0); //进入提货系统
			}
			//表示已经进入提货系统
			if ($this->CheckIfWentStockIsCorrect($input['GroupID'])) {
				//检查进入提货系统的数据是否完全正确
				$this->SetManuelStockOut("2", $input['GroupID']); //更改标志位
			} else {
				throw new Exception("已经进入库存提货系统,核对不正确，联系Eric！", 7);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		return suc('操作成功!');
	}
	private function GotoWarehouse($input, $Oper, $SN, $BN) {
		Db::startTrans();
		try {
			$BorrowCheck = controller('Borrow');
			$NewStockOutId = $BorrowCheck->GetNewStockOutID();
			$where['TypeName'] = $input['SaleType'];
			$saletypeid = model('StockType')->where($where)->field('stocktypeid')->find();
			$saletypeid = $saletypeid['stocktypeid'];
			$sql = "INSERT stockout(stockoutid,lastedittime,groupid,status,toplace,insertperson,insertdate,saletype,saledate,shopno,memo,snylon,bnylon,saletypeid) values(" . $NewStockOutId . ",'" . $input['Date'] . "','" . $input['GroupID'] . "','1','" . $this->GetRecieveInfo($input['SC/Card'], "2", $input) . "','" . $Oper . "','" . $input['Date'] . "','" . $input['SaleType'] . "','" . $input['Date'] . "','" . $input['SC/Card'] . "','促销礼品'," . $SN . "," . $BN . "," . $saletypeid . ")"; //状态为0表示刚进入库存，未激活，库管无法看到，1表示可以获取，2表示发货完毕
			if ($this->Exc_Sql($sql) > 0) {
				$name = 'GotoWarehouse';

				$sql = "INSERT stockoutinfo(stockoutid,sendperson,sendmac,sendip,sendsoftware,sendfunction,customerid,customerno,recieveman) values(" . $NewStockOutId . ",'" . $Oper . "','" . $this->Local_Mac() . "','" . $this->Local_IP() . "','前台系统','" . $Name . "'," . $this->GetRecieveInfo($input['SC/Card'], "0", $input) . ",'" . $this->GetRecieveInfo($input['SC/Card'], "1", $input) . "','" . $this->GetRecieveInfo($input['SC/Card'], "2", $input) . "')";

				if ($this->Exc_Sql($sql) > 0) {

					$sql = "SELECT productid, amount from promo_collectDetail where promoid=(select promoid from promo_collect where groupid='" . $input['GroupID'] . "') order by productid"; //载入扣货后的销售单产品

					$ds = $this->GetDSData($sql);
					foreach ($ds as $k => $v) {
						$pid = $v[0];
						$amount = $v[1];
						$active = $BorrowCheck->GetNewStockOutDetailID();
						$sql = "INSERT INTO stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo) values(" . $active . "," . $NewStockOutId . "," . $pid . "," . $amount . ",'Promo') ";
						if ($this->Exc_Sql($sql) < 0) {
							throw new Exception("向表stockoutdetail中插入数据失败！Sql:" . $sql, 1);
						}
					}

				} else {
					$sql = "delete from stockout where stockoutid=" . $NewStockOutId;
					if ($this->Exc_Sql($sql) < 1) {
						throw new Exception("回滚清除表stockout数据失败！Sql:" . $sql, 3);
					}
					throw new Exception("向表stockoutinfo中插入数据失败！Sql:" . $sql, 2);
				}
			} else {
				throw new Exception("向表stockout中插入数据失败！Sql:" . $sql, 4);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
		}
	}
	private function GetRecieveInfo($customerno, $mark, $input) {
		if ($customerno == "") {
			return "";
		} else {
			if (($customerno != 8) || ($customerno != 6)) {
				if ($mark == "0") {
					return "0";
				} else {
					return $customerno;
				}
			} else {
				if ($mark == "1") {
					return $customerno;
				} else if ($mark == "0") {
					return $input['CustomerID'];
				} else {
					return $input['CustomerName'];
				}
			}
		}
	}
	//检查进入提货系统的数据是否完全正确
	private function CheckIfWentStockIsCorrect($GroupID) {
		//倒序排列，以前台数据为循环体，因为仓库数据比前台数据多些尼龙袋和kits5项

		$sql = "SELECT productid,amount from promo_collectdetail where promoid=(select promoid from promo_collect where groupid='" . $GroupID . "') order by productid desc";
		//载入扣货后的销售单产品,尼龙袋和kits编号是比较小的数字，故库存里多出了的数字是小数字，倒序可以提前循环比较完
		$Sale_DeductDS = $this->GetDSData($sql); //来促销产品和扣货产品之和
		$sql = "SELECT Productid,Amount from stockout,stockoutdetail where stockoutdetail.stockoutid=stockout.stockoutid and groupid='" . $GroupID . "' order by productid desc"; //来自提货系统的数据
		$StockDS = $this->GetDSData($sql);

		for ($i = 0; $i < count($Sale_DeductDS); $i++) {
			if ($Sale_DeductDS[$i]['Amount'] != $StockDS[$i]['Amount']) //两块数据逐个对比，不一致报警！
			{
				throw new Exception("数据不一致信息如下：\n" . $Sale_DeductDS[$i]['productid'] . ":" . $Sale_DeductDS[$i]['Amount'] . "\n" . $StockDS[$i]['Productid'] . ":" . $StockDS[$i]['Amount'], 5);

			}

		}
		return true;
	}
	//更改标志位
	private function SetManuelStockOut($mark, $Groupid) {
		$sql = "update promo_collect set warehousemark='" . $mark . "' where groupid='" . $Groupid . "'";
		if ($this->Exc_Sql($sql) < 1) {
			throw new Exception("标志位失败！Sql:" . $sql, 6);
		}
	}
	/**
	 * @title 促销系统---RevertWareHouse按钮
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param SaleType
	 */
	public function RevertWareHouse() {
		$input = input('post.');
		$rule = [
			'GroupID' => 'require',
			'SaleType' => 'require',
		];
		$msg = [
			'GroupID' => '请填写报单编号!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			if ($this->CheckIfStockedOut($input)) {
				return err(9000, "该编号订单已经发货，无法撤销！\n The Sale with the GroupID is already sent to Distributor!");
			} else {
				$this->RemoveToStock($input);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		return suc('撤销成功!');
	}

	private function RemoveToStock($input) {
		$sql = "DELETE from stockoutdetail where stockoutid in(select stockoutid from stockout where saletype='" . $input['SaleType'] . "'and groupid='" . $input['GroupID'] . "')";
		if ($this->Exc_Sql($sql) < 0) {
			throw new Exception("表stockoutdetail记录撤销失败！Sql:" . $sql, 1);
		} else {
			$sql = "DELETE from stockoutinfo where stockoutid in(select stockoutid from stockout where saletype='" . $input['SaleType'] . "'and groupid='" . $input['GroupID'] . "')";
			if ($this->Exc_Sql($sql) < 0) {
				throw new Exception("表stockoutinfo记录撤销失败！Sql:" . $sql, 1);

			} else {
				$sql = "DELETE from stockout where saletype='" . $input['SaleType'] . "'and groupid='" . $input['GroupID'] . "'";
				if ($this->Exc_Sql($sql) < 0) {
					throw new Exception("表stockoutinfo记录撤销失败！Sql:" . $sql, 1);
				} else {
					$this->SetManuelStockOut("1", $input['GroupID']);
				}
			}
		}

	}
//产品领取---P(PromoType)
	/**
	 * @title PromoType--SearchAll
	 * @type interface
	 * @login 1
	 */
	public function PromoTypeSearchAll() {
		$data = Db::table('promotype')->order('promoid desc')->select();
		return suc($data);
	}
	/**
	 * @title PromoType--Search
	 * @type interface
	 * @login 1
	 * @param Date
	 * @param PromoName
	 */
	public function PromoTypeSearch() {
		$input = input('post.');
		$check = $this->validate($input, ["Date" => "require"], ["Date" => "请选择查询时间!"]);
		if ($check !== true) {
			return err(9000, $check);
		}
		$where['validdatefrom'] = ['<=', "{$input['Date']}"];
		$where['validdateto'] = ['>=', "{$input['Date']}"];
		if (!empty($input['PromoName'])) {
			$where['promoname'] = ['like', "%" . $input['PromoName'] . "%"];
		}
		$data = Db::table('promotype')->where($where)->select();
		return suc($data);
	}
	/**
	 * @title PromoType--Start启用
	 * @type interface
	 * @login 1
	 * @param PromoID
	 */
	public function PromoTypeStart() {
		$input = input('post.');
		$check = $this->id_verify($input, 'PromoID');
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$sql = "update promotype set Status='1' where promoid=" . $input['PromoID'];
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '启用失败!' . $e->getMessage());
		}
		return suc('启用成功');
	}
	/**
	 * @title PromoType--Stop停用
	 * @type interface
	 * @login 1
	 * @param PromoID
	 */
	public function PromoTypeStop() {
		$input = input('post.');
		$check = $this->id_verify($input, 'PromoID');
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$sql = "update promotype set Status='0' where promoid=" . $input['PromoID'];
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '停用失败!' . $e->getMessage());
		}
		return suc('停用成功');
	}
	/**
	 * @title PromoType--Add添加
	 * @type interface
	 * @login 1
	 * @param Name
	 * @param From
	 * @param To
	 * @param Describe
	 * @param Memo
	 */
	public function PromoTypeAdd() {
		$input = input('post.');
		$check = $this->param_verify($input);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data['PromoName'] = $input['Name'];
		$data['Describe'] = $input['Describe'];
		$data['ValidDateFrom'] = $input['From'];
		$data['ValidDateTo'] = $input['To'];
		$data['Oper'] = $this->Oper;
		$data['Memo'] = $input['Memo'];
		$data['Status'] = 1;
		Db::startTrans();
		$id = $this->GetNewID('promotype');
		$data['PromoID'] = $id;
		$result = Db::table('promotype')->insert($data);
		if ($result > 0) {
			Db::commit();
		} else {
			Db::rollback();
			return err(9000, '添加失败!');
		}
		return suc('添加成功!');
	}
	/**
	 * @title PromoType--update修改
	 * @type interface
	 * @login 1
	 * @param PromoID
	 * @param Name
	 * @param From
	 * @param To
	 * @param Describe
	 * @param Memo
	 */
	public function PromoTypeUpdate() {
		$input = input('post.');
		$check = $this->param_verify($input);
		if ($check !== true) {
			return err(9000, $check);
		}
		$check_id = $this->id_verify($input, 'PromoID');
		if ($check_id !== true) {
			return err(9000, $check_id);
		}
		$data['PromoName'] = $input['Name'];
		$data['Describe'] = $input['Describe'];
		$data['ValidDateFrom'] = $input['From'];
		$data['ValidDateTo'] = $input['To'];
		$data['Oper'] = $this->Oper;
		$data['Memo'] = $input['Memo'];
		Db::startTrans();
		try {
			Db::table('promotype')->where(['promoid' => "{$input['PromoID']}"])->update($data);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '修改失败!');
		}
		return suc('修改成功!');
	}
	/**
	 * @title PromoType--返回PromoTypeName
	 * @type interface
	 * @login 1
	 */
	public function GetPromoTypeName() {
		$data = Db::table('promotype')
			->where(['Status' => 1])
			->field('PromoName')
			->group('PromoName')
			->select();
		if (empty($data)) {
			return err(9000, '没有数据,请联系Eirc');
		}
		return suc($data);
	}
	//验证参数id
	private function id_verify($input, $id) {
		$rule = [
			"$id" => "require|number|>:0",
		];
		$msg = [
			"$id.require" => "请选择启用的数据!",
			"$id.number" => "参数类型错误!",
		];
		$check = $this->validate($input, $rule, $msg);
		return $check;
	}
	//验证前端参数
	private function param_verify($input) {
		$rule = [
			"From" => "require",
			"To" => "require",
			"Describe" => "require",
			"Memo" => "require",
			"Name" => "require",

		];
		$msg = [
			"From" => "日期必须选择",
			"To" => "日期必须选择",
			"Describe" => "描述必须填写",
			"Memo" => "备注必须填写",
			"Name" => "姓名必须填写",
		];
		$check = $this->validate($input, $rule, $msg);
		return $check;
	}
	private function GetNewID($tablename) {
		if ($tablename == 'promotype') {
			$sql = "select isnull(max(promoid),0)+1 from promotype ";
		} else if ($tablename == 'promo_collect') {
			$sql = "select isnull(max(promoid),0)+1 from promo_collect";
		} else if ($tablename == 'promo_collectdetail') {
			$sql = "select isnull(max(promodetailid),0)+1 from promo_collectdetail";
		}
		$id = $this->GetStringData($sql);
		if ($id == '') {
			$this->GetNewID($tablename);
		}
		return $id;
	}
//产品领取---T(SaleType)
	/**
	 * @title SaleType--SearchAll
	 * @type interface
	 * @login 1
	 */
	public function SaleTypeSearchAll() {
		$data = model('StockType')->getlistAC('', '*', 'StockTypeID desc');
		if (empty($data)) {
			return err(9000, '没有数据请联系Eirc');
		}
		return suc($data);
	}
	/**
	 * @title SaleType--Search
	 * @type interface
	 * @login 1
	 * @param TypeName
	 */
	public function SaleTypeSearch() {
		$input = input('post.');
		$check = $this->validate($input, ["TypeName" => "require"], ["TypeName" => "请选择类型名称!"]);
		if ($check !== true) {
			return err(9000, $check);
		}
		$where['TypeName'] = $input['TypeName'];
		$data = model('StockType')->getlistAC($where, '*', 'StockTypeID desc');
		if (empty($data)) {
			return err(9000, '没有数据请联系Eirc');
		}
		return suc($data);
	}
	/**
	 * @title SaleType--Start启用
	 * @type interface
	 * @login 1
	 * @param StockTypeID
	 */
	public function SaleTypeStart() {
		$input = input('post.');
		$check = $this->id_verify($input, 'StockTypeID');
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			model('StockType')->editById(['Status' => 1], $input['StockTypeID']);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '启用失败!' . $e->getMessage());
		}
		return suc('启用成功');
	}
	/**
	 * @title SaleType--Stop停用
	 * @type interface
	 * @login 1
	 * @param StockTypeID
	 */
	public function SaleTypeStop() {
		$input = input('post.');
		$check = $this->id_verify($input, 'StockTypeID');
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			model('StockType')->editById(['Status' => 0], $input['StockTypeID']);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '停用用失败!' . $e->getMessage());
		}
		return suc('停用用成功');
	}
	/**
	 * @title SaleType--update修改
	 * @type interface
	 * @login 1
	 * @param StockTypeID
	 * @param TypeName
	 * @param Dept
	 */
	public function SaleTypeUpdate() {
		$input = input('post.');
		$rule = [
			'TypeName' => 'require',
			'Dept' => 'require',
		];
		$check = $this->validate($input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$check_id = $this->id_verify($input, 'StockTypeID');
		if ($check_id !== true) {
			return err(9000, $check_id);
		}
		$data['Dept'] = $input['Dept'];
		$data['TypeName'] = $input['TypeName'];
		$data['LastEditDate'] = date('Y-M-d h:i:s');
		$data['Oper'] = $this->Oper;
		Db::startTrans();
		try {
			model('StockType')->editById($data, $input['StockTypeID']);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '修改失败!');
		}
		return suc('修改成功!');
	}
	/**
	 * @title SaleType--Add添加
	 * @type interface
	 * @login 1
	 * @param TypeName
	 * @param Dept
	 */
	public function SaleTypeAdd() {
		$input = input('post.');
		$rule = [
			'TypeName' => 'require',
			'Dept' => 'require',
		];
		$check = $this->validate($input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$LastEditDate = $CreateTime = date('Y-m-d h:i:s');
		Db::startTrans();
		try {
			$StockTypeID = model('StockType')->newid();
			$sql = "insert into StockType(StockTypeId,TypeName,Status,Memo,Oper,CreateTime,LastEditDate,Dept) values(" . $StockTypeID . ",'" . $input['TypeName'] . "','1','出库类型','" . $this->Oper . "','" . $CreateTime . "','" . $LastEditDate . "','" . $input['Dept'] . "')";
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '添加失败!失败原因:', $e->getMessage());
		}
		return suc('添加成功!');
	}
	/**
	 * @title SaleType--返回SaleTypeName
	 * @type interface
	 * @login 1
	 */
	public function GetTypeName() {
		$data = model('StockType')
			->where(['Status' => 1])
			->field('TypeName,TypeName_E')
			->group('TypeName,TypeName_E')
			->select();
		if (empty($data)) {
			return err(9000, '没有数据,请联系Eirc');
		}
		return suc($data);
	}
//以下是产品领取查询界面
	/**
	 * @title 产品领取查询---SearchAll
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param From
	 * @param To
	 */
	public function SearchAll() {
		$input = input('post.');
		if (empty($input['From']) && empty($input['To']) && empty($input['GroupID'])) {
			return err(9000, '亲,最少选择一个查询条件!');
		}
		$data = $this->GetDateByCondition($input);
		return suc($data);
	}
	/**
	 * @title 产品领取查询---Search
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param From
	 * @param To
	 */
	public function Search() {
		$input = input('post.');
		$input['oper'] = $this->Oper;
		$data = $this->GetDateByCondition($input);
		return suc($data);
	}
	//拼接查询条件进行查询
	private function GetDateByCondition($input, $del = false) {
		if (!empty($input['GroupID'])) {
			$where['a.GroupID'] = $input['GroupID'];
		}
		if (!empty($input['From']) && !empty($input['To'])) {
			$where['a.collectdate'] = ['between', "{$input['From']},{$input['To']}"];
		}
		if (!empty($input['oper'])) {
			$where['a.oper'] = $input['oper'];
		}
		if ($del) {
			//标志是删除管理的查询操作
			$where['a.status'] = '0';
		} else {
			$where['a.status'] = ['neq', '0'];
		}
		$field = 'a.collector,b.promoname,a.memo,a.saletype,a.collectdate,a.status,a.groupid';
		$data = Db::table('promo_collect')->alias('a')->join('promotype b', 'a.promotypeid=b.promoid')
			->field($field)->where($where)->select();
		return $data;
	}
	/**
	 * @title 产品领取查询---LastOne按钮
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @return data
	 */
	public function LastOne() {
		return $this->get_reuslt('Last');
	}
	/**
	 * @title 产品领取查询---NextOne按钮
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @return data
	 */
	public function NextOne() {
		return $this->get_reuslt('Next');
	}
	//执行查询操作
	private function get_reuslt($mark) {
		$input = input('post.');
		Db::startTrans();
		try {
			if ($input['GroupID'] == '') {
				if ($mark == 'Last') {
					//标志点击的是上一条
					$sql = "SELECT groupid from promo_collect where promoid=(select max(promoid) from promo_collect where oper='" . $this->Oper . "')";
				} else {
					//标志点击的是下一条
					$sql = "SELECT groupid from promo_collect where promoid=(select min(promoid) from promo_collect where oper='" . $this->Oper . "')";
				}
				$GroupID = $this->GetStringData($sql);
			} else {
				if ($mark == 'Last') {
					//标志点击的是上一条
					$sql = "SELECT groupid from promo_collect where promoid=(select max(promoid) from promo_collect where oper='" . $this->Oper . "' and promoid<(select promoid from promo_collect where groupid='" . $input['GroupID'] . "'))";
					$GroupID = $this->GetStringData($sql);
					if ($GroupID == '') {
						return suc('已经是第一条数据了!');
					}
				} else {
					//标志点击的是下一条
					$sql = "SELECT groupid from promo_collect where promoid=(select min(promoid) from promo_collect where oper='" . $this->Oper . "'and promoid>(select promoid from promo_collect where groupid='" . $input['GroupID'] . "'))";
					$GroupID = $this->GetStringData($sql);
					if ($GroupID == '') {
						return suc('已经是最后一条数据了!');
					}
				}

			}
			$result = $this->SetInfo($GroupID);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '查询出错,请重试或者联系Eirc!');
		}
		return suc($result);
	}
	private function SetInfo($GroupID) {
		$sql = "SELECT collector,promoname,a.memo,saletype,collectdate,a.status,a.groupid from promo_collect a,promotype b where a.promotypeid=b.promoid and a.groupid='" . $GroupID . "'";
		$data = $this->query_Sql($sql);
		return $data;
	}
	/**
	 * @title 产品领取查询--查询详细信息
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param status
	 * @return data
	 */
	public function GetDetailInfo() {
		$input = input('post.');
		$rule = [
			'status' => 'require|number|between:0,1',
			'GroupID' => 'require',
		];
		$msg = [
			'GroupID' => '请选择要查询详情的报单(GroupID)!',
			'status.require' => '参数报单状态缺失!',
			'status.number' => '参数报单状态类型错误!',
			'status.between' => '参数报单状态必须是0或1!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		if ($input['status'] == 0) {
			$list = $this->ShowPromoGift($input['GroupID']);
		} else {
			$list = $this->LoadPromoGift($input['GroupID']);
		}
		$list = utf_8($list);
		return suc($list);
	}
	private function ShowPromoGift($GroupID) {
		$sql = "SELECT ProductNO,Amount from promo_collectDetail a,stockproduct_material b where a.productid=b.productid and promoid=(select promoid from promo_collect where groupid='" . $GroupID . "')";
		$data = $this->query_Sql($sql);
		return $data;
	}
	private function LoadPromoGift($GroupID) {
		$sql = "SELECT c.ProductNO,c.Name,b.Amount as Amount,a.memo as 说明Memo,a.Oper as Maker from promo_Collect a,Promo_collectDetail b,stockproduct_material c where a.PromoID=b.PromoID and b.productid=c.productid and a.groupid='" . $GroupID . "' ";
		$data = $this->query_Sql($sql);
		return $data;
	}
//以下是删除管理
	/**
	 * @title 产品领取查询---Del删除按钮
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param SaleType
	 * @return data
	 */
	public function del_data() {
		$input = input('post.');
		$rule = [
			'GroupID' => 'require',
			'SaleType' => 'require',
		];
		$msg = [
			'GroupID' => '请选择要删除的单条数据!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$sql = "SELECT oper from promo_collect where groupid='" . $input['GroupID'] . "'";
		$oper = $this->GetStringData($sql);
		if ($this->Oper == '管理员' || $this->Oper == $oper) {
			//判断是管理员或者操作者删除的是自己的报单
			if ($this->CheckIfStockedOut($input)) {
				//结果为真,证明已经出库
				return err(9000, "该编号订单已经发货，无法撤销！\n The Sale with the GroupID is already sent to Distributor!");
			} else {
				return $this->Delete($input['GroupID']);
			}
		} else {
			return err(9000, "您没有权限进行此操作! \n You don't have the right to delete the form created by others!");
		}
	}
	//判断编号订单是否已经发货
	private function CheckIfStockedOut($input) {
		$mark = false;
		$sql = "SELECT count(*) from stockout where status='2' and saletype='" . $input['SaleType'] . "'and groupid='" . $input['GroupID'] . "'";
		if ($this->GetStringData($sql) > 0) {
			$mark = true;
		}
		return $mark;
	}
	private function Delete($GroupID) {
		Db::startTrans();
		try {
			$sql = "UPDATE promo_collect SET status='0' where groupid='" . $GroupID . "'";
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, "删除失败,失败原因: \n Delete it unsuccessfully,Error:" . $e->getMessage());
		}
		return suc("删除成功! \n Delete it successfully!");
	}
	/**
	 * @title 删除管理--查询search
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param From
	 * @param To
	 */
	public function del_search() {
		$input = input('post');
		$input['oper'] = $this->Oper;
		$data = $this->GetDateByCondition($input, true);
		return suc($data);
	}
	/**
	 * @title 删除管理---SearchAll
	 * @type interface
	 * @login 1
	 * @param GroupID
	 * @param From
	 * @param To
	 */
	public function del_search_all() {
		$input = input('post.');
		$data = $this->GetDateByCondition($input, true);
		return suc($data);
	}
	/**
	 * @title 删除管理--撤销删除
	 * @type interface
	 * @login 1
	 * @param GroupID
	 */
	public function repal_del() {
		$input = input('post.');
		$check = $this->validate($input, ['GroupID' => 'require'], ['GroupID' => '请选择要撤销删除的数据!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$sql = "UPDATE promo_collect SET status='1' where groupid='" . $input['GroupID'] . "' and status='0'";
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '撤销删除失败,请重试!失败原因:' . $e->getMessage());
		}
		return suc('恭喜,撤销删除成功!');
	}
	/**
	 * @title 删除管理--永久删除
	 * @type interface
	 * @login 1
	 * @param GroupID
	 */
	public function del_perpetual() {
		$input = input('post.');
		$check = $this->validate($input, ['GroupID' => 'require'], ['GroupID' => '请选择要永久删除的数据!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$sql = "DELETE FROM promo_collect WHERE groupid='" . $input['GroupID'] . "' and status='0'";
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '操作失败,请重试!或者联系Eirc!失败原因:' . $e->getMessage());
		}
		return suc('操作成功!');
	}
}