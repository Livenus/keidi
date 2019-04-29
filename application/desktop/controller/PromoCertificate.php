<?php
namespace app\desktop\Controller;
use app\desktop\Controller\Common;
use think\Db;

/**
 * @title 促销标记
 * @type menu
 * @login 1
 */
class PromoCertificate extends Common {

	public function _initialize() {
		parent::_initialize();
		$this->Oper = self::$realname;
	}
//以下是汽车促销标记
	/**
	 * @title 汽车促销标记--search查询
	 * @type interface
	 * @login 1
	 * @param CustomerNO 会员编号
	 */
	public function car_search() {
		$input = input('post.');
		$rule = [
			'CustomerNO' => 'require|regex:/^kn\d{6}$/i',
		];
		$msg = [
			'CustomerNO.require' => '请填写需要查询的会员编号!',
			'CustomerNO.regex' => '会员编号格式错误,请重新填写!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$sql = "SELECT c.CustomerID,c.CustomerNO AS Code, ci.CustomerName AS NAME, c.Per_Grade AS Grade,ci.postcode as CarGrade,ci.Memo as CarYear,c.RegDate,c.ShopNo from tb_customer as c inner join tb_customerinfo as ci on ci.customerid=c.customerid where c.customerno='" . $input['CustomerNO'] . "'";
		$data = $this->query_Sql($sql);
		return suc($data);
	}
	/**
	 * @title 汽车促销标记--标记
	 * @type interface
	 * @login 1
	 * @param memo 发车年份
	 * @param CustomerID
	 * @param textBox4 发车级别
	 * @param CustomerNO 会员编号
	 * @param ShopNo 专卖店编号
	 * @param NAME 姓名
	 * @param textBox7 领取人
	 * @return data
	 */
	public function button2_Click() {
		$input = input('post.');
		$rule = [
			'CustomerNO' => 'require|regex:/^kn\d{6}$/i',
			'CustomerID' => 'require|number',
			'ShopNo' => 'require',
			'NAME' => 'require',
			'textBox4' => 'require',
			'memo' => 'require',
			'textBox7' => 'require',
		];
		$msg = [
			'CustomerNO.require' => '请填写需要查询的会员编号!',
			'CustomerNO.regex' => '会员编号格式错误,请重新填写!',
			'CustomerID.require' => '会员ID必须填写!',
			'CustomerID.number' => '会员ID必须数字!',
			'ShopNo' => '请填写专卖店编号!',
			'NAME' => '请填写姓名!',
			'textBox4' => '请填写发车级别!',
			'memo' => '请填写发车年份!',
			'textBox7' => '请填写领取人!',

		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		if (input['Grade'] >= 7) {
			if ($this->CheckIfMarked($input['CustomerID'])) {
				return err(9000, "已经发过汽车促销，标记过之前！");
			} else {
				return $this->SetCarInfo($input);
			}
		} else {
			return err(9000, '必须7星才可以领取促销车款！');
		}
	}
	//判断是否已经发过汽车促销
	private function CheckIfMarked($customerid) {
		$sql = "select postcode from tb_customerinfo where customerid=" . $customerid;
		$postcode = $this->GetStringData($sql);
		if ($postcode > 0) {
			return true;
		} else {
			return false;
		}

	}
	private function SetCarInfo($data) {
		Db::startTrans();
		try {
			$sql = "update tb_customerinfo set postcode='" . $data['textBox4'] . "',memo='" . $data['memo'] . "' where customerid=" . $data['CustomerID'];
			$this->Exc_Sql($sql);

			$GID = date('Ymd h:i:s');
			$sql = "INSERT promoinfo(ID,Shopno,customerNo,customername,status,collector,PromoID,PromoDate,Oper,Memo,InsertDate,groupid) values(" . $data['CustomerID'] . ",'" . $data['ShopNo'] . "','" . $data['CustomerNO'] . "','" . $data['NAME'] . "','1','" . $data['textBox7'] . "',6,'" . $data['memo'] . "','" . $this->Oper . "','','" . date('Y-m-d h:i:s') . "','" . $GID . "') ";
			$this->Exc_Sql($sql);

			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '标记失败,失败原因:' . $e->getMessage());
		}
		return suc('标记成功!');
	}
//以下是促销标记

	/**
	 * @title 促销标记--查询
	 * @type interface
	 * @login 1
	 * @param SC/Card
	 * @param Grade
	 * @param From 查询开始日期
	 * @param To 查询截止日期
	 * @param checkBox1 是否勾选模糊查询(0否,1是)
	 * @return data
	 */
	public function button3_Click() {
		$input = input('post.');
		$rule = [
			'SC/Card' => 'require',
			'Grade' => 'require',
			'From' => 'require',
			'To' => 'require',
		];
		$msg = [
			'SC/Card' => '请填写专卖店或者会员编号',
			'Grade' => '请选择会员星级',
			'From' => '请选择查询开始时间',
			'To' => '请选择查询截止时间',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$pattern = "/^\d{3}$|^kn\d{6}$/i";
		$check_sc = preg_match($pattern, $input['SC/Card']);
		if (!$check_sc) {
			return err(9000, '专卖店或者会员编号输入错误!正确格式:xxx或者KNxxxxxx.');
		}
		return $this->SearchPromo($input);
	}
	private function SearchPromo($input) {
		if ($input['checkBox1']) {
			if (strlen($input['SC/Card']) < 8) {
				$sql = "SELECT MainKey as Mk,CheckB as S,CustomerNo as Code,ShopNo as Sc,CustomerName as Name,PromoDate,Grade,isnull(Status,0) as Status,Oper from promoMonthly where grade='" . $input['Grade'] . "'and  shopno like'%" . $input['SC/Card'] . "%' and PromoDate >='" . $input['From'] . "' and PromoDate <='" . $input['To'] . "'";
			} else {
				$sql = "SELECT MainKey as Mk,CheckB as S,CustomerNo as Code,ShopNo as Sc,CustomerName as Name,PromoDate,Grade,Status,Oper from promoMonthly where grade='" . $input['Grade'] . "'and customerno like'%" . $input['SC/Card'] . "%' and PromoDate >='" . $input['From'] . "' and PromoDate <='" . $input['To'] . "'";
			}

		} else {
			if (strlen($input['SC/Card']) < 8) {
				$sql = "SELECT MainKey as Mk,CheckB as S,CustomerNo as Code,ShopNo as Sc,CustomerName as Name,PromoDate,Grade,isnull(Status,0) as Status,Oper from promoMonthly where grade='" . $input['Grade'] . "'and  shopno='" . $input['SC/Card'] . "' and PromoDate >='" . $input['From'] . "' and PromoDate <='" . $input['To'] . "'";
			} else {
				$sql = "SELECT MainKey as Mk,CheckB as S,CustomerNo as Code,ShopNo as Sc,CustomerName as Name,PromoDate,Grade,Status,Oper from promoMonthly where grade='" . $input['Grade'] . "'and customerno='" . $input['SC/Card'] . "' and PromoDate >='" . $input['From'] . "' and PromoDate <='" . $input['To'] . "'";
			}
		}
		$data = $this->query_Sql($sql);
		return suc($data);
	}
	/**
	 * @title 促销标记--中间标记箭头
	 * @type interface
	 * @login 1
	 * @param MKS 选择的数据的MK的集合
	 * @param mark 标志是左移(0)还是右移(1)
	 */
	public function Move() {
		$input = input('post.');
		if (empty($input['MKS'])) {
			return err(9000, '请选择要标记的数据!');
		}
		$MKS = json_decode($input['MKS'], true);
		if ($input['mark']) {
			$status = 1; //表示标记
		} else {
			$status = 0; //表示去标记
		}
		Db::startTrans();
		try {
			foreach ($MKS as $v) {
				$this->UpdateStatus($v, $status);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '标记失败,请重试!');
		}
		return suc('操作成功!');
	}
	private function UpdateStatus($Mk, $status) {
		$sql = "UPDATE promoMonthly set status='" . $status . "',collectDate='" . date('Y-m-d h:i:s') . "',oper='" . $this->Oper . "' where status<>'2' and mainkey='" . $Mk . "'";
		$this->Exc_Sql($sql);
	}

	/**
	 * @title 促销标记--打印
	 * @type interface
	 * @login 1
	 * @param  MKS 选择的数据的mk的集合
	 * @return result
	 */
	public function button5_Click() {
		$input = input('post.');
		if (empty($input['MKS'])) {
			return err(9000, '请选择要打印的数据!');
		}
		$data = json_decode($input['MKS'], true);
		$Sc_array = array_column($data, 'Sc');
		if (count(array_unique($Sc_array)) > 1) {
			return err(9000, '选择的数据不是同一个专卖店!');
		}
		$GID = date('Ymdhis') . '-' . $Sc_array[0];
		$result = [];
		Db::startTrans();
		try {
			foreach ($data as $v) {
				$v['GID'] = $GID;
				$v['SaleType'] = 'Promo';
				$result[] = $v;
				$this->CollectPromo($v['mk'], $GID);
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '操作失败,请重试!');
		}
		return suc($result);
	}
	private function CollectPromo($Mk, $GID) {
		$sql = "UPDATE promoMonthly set status='2',GroupID='" . $GID . "' where mainkey='" . $Mk . "'";
		$this->Exc_Sql($sql);
	}
//以下是促销标记--撤销窗口
	/**
	 * @title 促销标记--撤销窗口--搜索
	 * @type interface
	 * @login 1
	 * @param GID
	 */
	public function Search() {
		$input = input('post.');
		$check = $this->validate($input, ['GID' => 'require'], ['msg' => '请填写要查询的订单编号!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$sql = "SELECT MainKey as Mk,CheckB as S,CustomerNo as Code,ShopNo as Sc,CustomerName as Name,PromoDate,Grade,Status,Oper,GroupID from promoMonthly where groupid='" . $input['GID'] . "'";
		$data['left'] = $this->query_Sql($sql);

		$sql = "SELECT ProductNo,Name,Amount,a.Memo from stockoutdetail a,stockout b,stockproduct_material c where a.stockoutid=b.stockoutid and a.productid=c.productid and  groupid='" . $input['GID'] . "'";
		$data['right'] = $this->query_Sql($sql);
		return suc($data);
	}
	/**
	 * @title 促销标记--撤销窗口--撤销
	 * @type interface
	 * @login 1
	 * @param GID
	 */
	public function Delete() {
		$input = input('post.');
		$check = $this->validate($input, ['GID' => 'require'], ['msg' => '请填写要撤销的订单编号!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		if ($this->CheckIfstockOut($input['GID'])) {
			return err(9000, "已经发货，无法撤销！");
		} else {
			Db::startTrans();
			try {
				$sql = "delete from stockout where groupid='" . $input['GID'] . "'";
				$this->Exc_Sql($sql);

				$sql = "delete from promo_collect where groupid='" . $input['GID'] . "'";
				$this->Exc_Sql($sql);

				$sql = "update promomonthly set status='0' where groupid='" . $input['GID'] . "'";
				$this->Exc_Sql($sql);
				Db::commit();
			} catch (Exception $e) {
				Db::rollback();
				return err(9000, '撤销失败,请重试!失败原因:' . $e->getMessage());
			}
			return suc('恭喜您撤销成功!');
		}
	}
	private function CheckIfstockOut($GID) {
		$sql = "select status from stockout where groupid='" . $GID . "'";
		$status = $this->GetStringData($sql);
		if ($status == "2") {
			return true;
		} else {
			return false;
		}

	}

//以下是星级查询的数据上传
	/**
	 * @title 星级查询--UploadPromo
	 * @type interface
	 * @login 1
	 * @param cell 数据集合
	 */
	public function UploadPromo() {
		set_time_limit(0);
		$input = input('post.');
		$check = $this->validate($input, ['cell' => 'require'], ['msg' => '上传数据不能为空!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data = json_decode($input['cell'], true);
		Db::startTrans();
		try {
			$this->CreateNewTable();
			$this->InsertPromo($data);
			$this->InsertPromoMonthly();
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '操作失败,请重试!');
		}
		return suc('操作成功!');
	}
	//删除旧表创建新表
	private function CreateNewTable() {
		$this->Drop_Table1("Promo"); //删除旧表
		$sql = "CREATE TABLE Promo (
			MainKey VARCHAR(255) NULL DEFAULT NULL,
			ShopNO VARCHAR(255) NULL DEFAULT NULL,
			CustomerNO VARCHAR(255) NULL DEFAULT NULL,
			CustomerName VARCHAR(255) NULL DEFAULT NULL,
			PromoDate datetime,
			Grade VARCHAR(255) NULL DEFAULT NULL,
			Status VARCHAR(255) NULL DEFAULT '0',

		)";
		$this->Exc_Sql($sql); //创建新表
	}
	//向临时表Promo写入数据
	private function InsertPromo($data) {
		foreach ($data as $key => $v) {
			$ShopNO = $v['专卖店'];
			$CustomerNO = $v['卡号'];
			$CustomerName = $v['姓名'];
			$Grade = $v['级别'];
			$PromoDate = $v['日期'];
			$sql = "insert into Promo(ShopNO,CustomerNO,CustomerName,Grade,PromoDate) VALUES('" . $ShopNO . "','" . $CustomerNO . "','" . $CustomerName . "','" . $Grade . "','" . $PromoDate . "')";
			$this->Exc_Sql($sql);
		}
	}
	//向正式表promoMonthly写入数据
	private function InsertPromoMonthly() {
		$sql = "update promo set MainKey=(select customerid from tb_customer where tb_customer.customerno=promo.customerno)";
		$this->Exc_Sql($sql);

		$sql = "update promo set MainKey=MainKey+'_'+Grade";
		$this->Exc_Sql($sql);

		$sql = "insert promoMonthly(MainKey,ShopNo,CustomerNo,Customername,Promodate,Grade,Status) select * from promo";
		$this->Exc_Sql($sql);

	}
}