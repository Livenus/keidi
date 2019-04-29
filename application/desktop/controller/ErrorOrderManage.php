<?php
namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
use think\Exception;

/**
 * @title 错单管理
 * @type menu
 * @login 1
 */
class ErrorOrderManage extends Common {
	public function _initialize() {
		parent::_initialize();
		$this->Oper = self::$realname;
	}

	/**
	 * @title 错单管理--search
	 * @type interface
	 * @login 1
	 * @param condition 筛选条件(0,1,2,3,4)
	 * @param textBox1 筛选条件输入值
	 * @return data
	 */
	public function Search() {
		$input = input('post.');
		$rule = [
			'condition' => 'require|number',
			'textBox1' => 'require',
		];
		$msg = [
			'condition.require' => '请选择筛选条件!',
			'condition.number' => '筛选条件参数错误!',
			'textBox1' => '请输入筛选条件值!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$sql = $this->get_sql($input);
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	private function get_sql($input) {
		$string = '';
		switch ($input['condition']) {
		case '0':
			$string = "SaleNo='" . $input['textBox1'] . "'";
			break;
		case '1':
			$string = "ShopNO='" . $input['textBox1'] . "'";
			break;
		case '2':
			$string = "Total_BV=" . $input['textBox1'] . "'";
			break;
		case '3':
			$string = "Total_PV=" . $input['textBox1'] . "'";
			break;
		default:
			$string = "Total_NAIRA=" . $input['textBox1'] . "'";
			break;
		}
		$sql = "SELECT SaleNo as 报单编号,CustomerNO as 会员编号,ShopNO as 专卖店,convert(decimal(10,1),Total_BV) as 总BV,convert(decimal(10,1),Total_PV) as 总PV,convert(decimal(10,0),Total_NAIRA) as 总金额,RealDate as 购买日期,Oper_Name as 操作员,SaleID from tb_Sale_Entered_ByFrontDesk where Current_Status='3' and " . $string;
		return $sql;
	}
	/**
	 * @title 错单管理--左边submit按钮
	 * @type interface
	 * @login 1
	 * @param SaleID
	 * @param SaleNO
	 * @param ShopNO
	 * @param CustomerID
	 * @param CustomerNO
	 * @param BV
	 * @param PV
	 * @param NAIRA
	 * @param Name 收货人
	 */
	public function submit_left() {
		$input = input('post.');
		$check = $this->check_column($input);
		if ($check !== true) {
			return err(9000, $check);
		}
		$input['Date'] = date('Y-m-d h:i:s');
		Db::startTrans();
		try {
			$this->InsertOrder($input);
			$this->InsertOrderDetail($input);
			$this->InsertOrderInfo($input);
			$this->Remove_TopOne($input);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		return suc('操作成功!');
	}
	//验证参数
	private function check_column($input) {
		$rule = [
			'SaleID' => 'require|number',
			'SaleNO' => 'require',
			'ShopNO' => 'require|regex:^\d{3}$',
			'CustomerID' => 'require|number',
			'CustomerNO' => 'require',
			'BV' => 'require',
			'PV' => 'require',
			'NAIRA' => 'require',
			'Name' => 'require',
		];
		$msg = [
			'SaleID.require' => '请填写SaleID!',
			'SaleID.number' => 'SaleID必须是数字类型!',
			'SaleNO' => '请填写报单编号!',
			'ShopNO.require' => '请填写专卖店编号!',
			'ShopNO.regex' => '专卖店编号格式错误!',
			'CustomerID.require' => '请填写CustomerID!',
			'CustomerID.number' => 'CustomerID必须是数字类型!',
			'CustomerNO' => '请填写会员编号!',
			'BV' => '请填写总BV!',
			'PV' => '请填写总PV!',
			'NAIRA' => '请填写总NAIRA!',
			'Name' => '请填写收货人姓名!',

		];
		$check = $this->validate($input, $rule, $msg);
		return $check;
	}
	//插入数据
	private function InsertOrder($input) {
		Db::startTrans();
		try {
			$sql = "INSERT INTO tb_Sale(SaleID, SaleNO, CustomerID,BranchID,ShopNO,SaleDate,BuyDate,SendDate,SaleType,TotalPV,TotalRetail,TotalMember,NetType,Status,AccountMan,[ProcessStatus],[ProcessFlag],[AccountDate],[PayType],[SendType],[SendMoney] ,[TotalMoney],ShopID,CreateTime,LastEditTime) VALUES(" . $input['SaleID'] . "," . $input['SaleNO'] . "," . $input['CustomerID'] . ",0,'" . $input['ShopNO'] . "','" . $input['Date'] . "','" . $input['Date'] . "','" . $input['Date'] . "',1," . $input['BV'] . " ," . $input['PV'] . "," . $input['NAIRA'] . ",2,0,'" . $this->Oper . "',10,'已购买','',1,1," . $input['NAIRA'] . "," . $input['NAIRA'] . ",0,'" . $input['Date'] . "','" . $input['Date'] . "')";
			$this->Exc_sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			throw new Exception("录入失败,请重新录入!", 1);
		}
	}
	//录入详情
	private function InsertOrderDetail($input) {
		Db::startTrans();
		try {
			$sql = "INSERT INTO tb_saledetail select * from tb_saledetail_byfrontdesk where SaleID='" . $input['SaleID'] . "'";
			$this->Exc_sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			throw new Exception("录入详情失败,请重新录入!", 2);
		}
	}
	//录入订单信息
	private function InsertOrderInfo($input) {
		Db::startTrans();
		try {
			$sql = "INSERT INTO tb_SaleInfo(SaleID , Address, Phone, PostCode, ReceiveMan) VALUES(" . $input['SaleID'] . ",'','','0','" . $input['Name'] . "')";
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			throw new Exception("录入订单信息失败，请联系Eric！", 3);
		}
	}
	private function Remove_TopOne($input) {
		//设置状态，计算部日期，提交者姓名；状态1表示前台提交的，2表示卡号正确提交，3表示卡号不存在转到错误单
		$sql = "UPDATE tb_Sale_Entered_ByFrontDesk SET CustomerNO='" . $input['CustomerNO'] . "', Current_Status='2',Saledate='" . $input['Date'] . "' where SaleID='" . $input['SaleID'] . "'";
		$this->Exc_Sql($sql);
		$sqlorder = "DELETE from Calc_Error_Data where SaleNO='" . $input['SaleNO'] . "'";
		$this->Exc_Sql($sqlorder);
	}
}