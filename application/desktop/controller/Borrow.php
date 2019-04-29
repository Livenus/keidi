<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
use think\Exception;

/**
 * @title 借货系统
 * @type menu
 * @login 1
 */
class Borrow extends Common {
	public $CheckLastBorrow = 0; //此标志位用于判断上次是否有未完成的借货，0表是没有，1表示有，有的话只能复位后选择新产品，或者直接打印；
	public $SaleType = '借货';
	public function _initialize() {
		parent::_initialize();
		$this->TellerOper = self::$realname;
		$this->input = input("post.");
	}
	/**
	 * @title 借货系统--获取产品列表以及遗留任务记录
	 * @type interface
	 * @login 1
	 */
	public function Form1_Load() {
		$data['Product_Info'] = $this->Get_Product_Info(); //获取产品列表
		$result = $this->LoadDiscompleteBorrow(); //获取表tb_SaleforBorrowReturn最后一条记录的shopno和groupid
		if ($result['shopno'] != "") {
			$data['HistoryBorrow'] = $this->GetHistoryBorrow($result['shopno']);
			$data['BorrowQty'] = $this->GetToBorrow($result['groupid']);
			$data['groupid'] = $result['groupid'];
			$data['shopno'] = $result['shopno'];
			$this->CheckLastBorrow = 1; //表示上次有遗留借货任务
		}
		return suc($data);
	}
	//获取产品列表
	private function Get_Product_Info() {
		$sql = "select productid,productno,CName,MemberPrice,PV as BV,RetailPrice as PV ,'   ' as HistoryQty,'   ' as BorrowQty,'   ' as NowQty from tb_Product where PV>0 and productno<>'L04' order by productno";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return $data;
	}
	//获取表tb_SaleforBorrowReturn最后一条记录的shopno和groupid
	private function LoadDiscompleteBorrow() {
		try
		{
			$sql = "";
			$data = '';
			$sql = "select top 1 shopno from tb_SaleforBorrowReturn where  current_status='0'and directstatus='借货' order by saleid desc";
			$data['shopno'] = $this->GetStringData($sql);
			$sql = "select top 1 groupid from tb_SaleforBorrowReturn where  current_status='0'and directstatus='借货' order by saleid desc";
			$data['groupid'] = $this->GetStringData($sql);
			return $data;
		} catch (Exception $ee) {
			return err(9000, $ee->getMessage());
		}
	}
	//查询汇总单号(groupid)的借货数量详情
	private function GetToBorrow($groupid) {
		try
		{
			$sql = "";
			$sql = "select productno,Amount as QTY from (select productid,sum(amount) as amount from tb_SaleforBorrowReturn,tb_SaleDetailforBR where tb_SaleforBorrowReturn.saleid=tb_SaleDetailforBR.saleid and current_status='0' and groupid='" . $groupid . "' group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";
			$data = $this->GetDSData($sql);
			return $data;
		} catch (Exception $ee) {
			return err(9000, $ee->getMessage());
		}
	}
	/**
	 * @title 借货系统 上方的SearchHistory按钮
	 * @param shopno 专卖店编号
	 * @return data  结果集合
	 */
	public function button2_Click() {
		$rule = [
			"shopno" => "require",
		];
		$msg = [
			"shopno" => "请输入查询专卖店编号!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		return $this->GetHistoryBorrow($this->input['shopno']);

	}
	//根据专卖店号查询每款产品的借货数量
	private function GetHistoryBorrow($shopno) {
		$sql = "select productno,Amount as QTY from (select productid,sum(amount) as amount from tb_SaleforBorrowReturn,tb_SaleDetailforBR where tb_SaleforBorrowReturn.saleid=tb_SaleDetailforBR.saleid and current_status>='1' and shopno='" . $shopno . "' group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";
		$data = $this->query_Sql($sql);
		if (count($data) < 1) {
			return err(9000, "Service Centre(专卖店):" . $shopno . " didn't borrow product before!(没有历史借货记录!)");
		}
		return suc($data);
	}
	/**
	 * @title 借货系统 下方的CreateReport按钮
	 * @param shopno 专卖店编号
	 * @param Date    Date日期
	 * @param totalBv
	 * @param totalpv
	 * @param totalnaira
	 * @param data 选择的数据集合
	 * @param GroupID 遗留任务有GroupID
	 */
	public function button6_Click() {
		$rule = [
			"totalnaira" => "require",
			"totalBv" => "require",
			"totalpv" => "require",
			"data" => "require",
			"shopno" => "require",
		];
		$msg = [
			"totalnaira" => "Pls select the product you wanna borrow(请选择您要借的产品)!",
			"totalBv" => "totalBv必填",
			"totalpv" => "totalpv必填",
			"data" => "借货数量必填",
			"shopno" => "专卖店必填",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		if (strlen($this->input['shopno']) == 3) {
			$code = "KN999999";
		} else {
			$code = $this->input['shopno'];
		}
		$pattern = "/^(k|K)(n|N|g|G)\d{6}$/";
		if (preg_match($pattern, $code) != 1) {
			return err(9000, "Please enter correct kedi NO.(请输入正确的专卖店或者经销商编号!)");
		}
		Db::startTrans();
		try {
			if ($this->input['totalnaira'] >= 0) {
				if ($this->LoadTheAmountStockHas()) {
					//判断库存数量是否大于等于借货数量
					if ($this->CheckLastBorrow == 0) {
						//判断是否有上次遗留任务,0没有,1有
						$GroupID = date('YmdHis', time()) . '-' . $this->input['shopno']; //产生订单编号
					} else {
						$GroupID = $this->input['GroupID']; //遗留任务订单号
					}
					$data = $this->InsertReport($GroupID);
				}
			} else {
				return err(9000, "Pls select the product you wanna borrow(请选择您要借的产品)!");
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		return suc($data);
	}
	//判断借货的数量是否小于等于库存数量返回bool
	private function LoadTheAmountStockHas() {
		$status = true;
		$data = $this->input['data'];
		//$data = json_decode($data, true);
		$sql = "select productno,amount from kedistock,stockproduct_material where stockproduct_material.productid=kedistock.productid";
		$result = $this->query_Sql($sql);
		for ($i = 0; $i < count($result); $i++) {
			$productno = $result[$i]['productno']; //产品编号
			$amount = $result[$i]['amount']; //库存数量
			foreach ($data as $k => $v) {
				if ($v['productno'] == $productno) {
					//借货的产品编号
					if ($v['BorrowQty'] > $amount) {
						//判断借货的数量是否大于库存
						throw new Exception("The balance of the product No.:" . $productno . " is " . $amount . ",it's not enough to borrow!", 1);
						$status = false;
						break;
					}
				}
			}
		}
		return $status;
	}
	//生成汇总//Borrow_Return_Report表存储借货的汇总单号
	private function InsertReport($GroupID) {
		$sql = "INSERT INTO [dbo].[Borrow_Return_Report](GroupID,sc_dist,oper_name,[date],totalBv,totalpv,totalnaira) values('" . $GroupID . "','" . $this->input['shopno'] . "','" . $this->TellerOper . "','" . $this->input['Date'] . "'," . $this->input['totalBv'] . "," . $this->input['totalpv'] . "," . $this->input['totalnaira'] . ")";
		$this->Exc_Sql($sql);
		return $this->InsertOrder($GroupID);
	}
	//调用存储过程插入借货订单
	private function InsertOrder($GroupID) {
		$SaleID = $this->GetNewSaleID('SaleID');
		$SaleNO = $this->GetNewSaleID('SaleNO');
		$sql = "INSERT tb_SaleforBorrowReturn(SaleID, SaleNO, CustomerID , BranchID, ShopNO, SaleDate , RealDate ,  Total_BV, Total_PV, Total_NAIRA, GroupID, Oper_Name, Current_Status) VALUES(" . $SaleID . ", " . $SaleNO . ", " . $this->GetCustomerID($code) . " ,'0', '" . $this->input['shopno'] . "', '" . $this->input['Date'] . "' , '" . $this->input['Date'] . "' , " . $this->input['totalBv'] . ",  " . $this->input['totalpv'] . ",  " . $this->input['totalnaira'] . ", '" . $GroupID . "', '" . $this->TellerOper . "','0')";
		$this->Exc_Sql($sql);
		return $this->Insert_SaleDetail($GroupID, $SaleID);
	}
	//把数据插入tb_SaleDetailforBR表中
	private function Insert_SaleDetail($GroupID, $SaleID) {
		$data = $this->input['data'];
		try {
			//$data = json_decode($this->input['data'], true);
			foreach ($data as $k => $v) {
				if ($v['BorrowQty'] > 0) {
					$productid = $v['productid'];
					$amount = $v['BorrowQty'];
					$PV = $v['BV'];
					$Retailprice = $v['PV'];
					$Memberprice = $v['MemberPrice'];
					$saledetailid = $this->Get_tb_SaleDetailforBRNewDetailID();
					$sql = "insert into [dbo].[tb_SaleDetailforBR](saledetailid,saleid,productid,amount,PV,Retailprice,Memberprice) values('" . $saledetailid . "','" . $SaleID . "','" . $productid . "','" . $amount . "','" . $PV . "','" . $Retailprice . "','" . $Memberprice . "')";
					$this->Exc_Sql($sql);
				}
			}
			$this->Set_SaleforBorrowReturn($GroupID); //变更表tb_SaleforBorrowReturn中的DirectStatus值为借货
			return $this->ShowReportForm($GroupID);
		} catch (Exception $e) {
			$this->DeleteSale($SaleID); //删除2表里报单
			return err(9000, "Can not create report,Please enter the saleform again!");
		}
	}
	//生成表tb_saleforborrowreturn的新SaleID或者SaleNO
	private function GetNewSaleID($field) {
		$sql = "";
		$data = "";
		$sql = "select isnull(max(" . $field . "),0)+1 from tb_saleforborrowreturn ";
		$data = $this->GetStringData($sql);
		return $data;
	}
	//从tb_customerinfo表中获取CustomerID
	private function GetCustomerID($code) {
		$CustomerID = 0;
		$pattern = "/^(k|K)(n|N|g|G)\d{6}$/";
		if (preg_match($pattern, $code) == 1) {
			try {
				$sql = "select customerid from tb_customerinfo where customerid=(select customerid from tb_customer where customerno='" . $code . "')";
				$CustomerID = $this->GetStringData($sql);
			} catch (Exception $e) {
				return err(9000, $e->getMessage() . "没有此号！");
			}
		}
		return $CustomerID;
	}

	//生成表tb_SaleDetailforBR新的Saledetailid
	private function Get_tb_SaleDetailforBRNewDetailID() {
		$sql = "";
		$sql = "select isnull(max(Saledetailid),0)+1 from tb_SaleDetailforBR";
		$data = $this->GetStringData($sql);
		return $data;
	}

	//变更表tb_SaleforBorrowReturn中的DirectStatus值为借货
	private function Set_SaleforBorrowReturn($GroupID) {
		$SetGpID_Sql = "update tb_SaleforBorrowReturn set DirectStatus='借货',current_status='0',GroupID='" . $GroupID . "' where current_status='0' and oper_name='" . $this->TellerOper . "'";
		//后期进入提货系统成功current_status会变为1
		$this->Exc_Sql($SetGpID_Sql);
	}
	//删除tb_SaleforBorrowReturn和tb_SaleDetailforBR两个表里报单
	private function DeleteSale($saleid) {
		$delsql = "delete from tb_SaleforBorrowReturn where saleid='" . $saleid . "'";
		$this->Exc_Sql($delsql);
		$delsql = "delete from tb_SaleDetailforBR where saleid='" . $saleid . "'";
		$this->Exc_Sql(delsql);
	}
	//返回数据
	private function ShowReportForm($GroupID) {
		$data['GroupID'] = $GroupID;
		$data['input'] = $this->input;
		return $data;
	}
	/**
	 * @tetile 借货系统 保存save
	 * @type interface
	 * @login 1
	 * @param GroupID 订单汇总编号
	 * @param kits kits(套件数量)
	 * @param SystemTotal 货物总价值
	 * @param Cash Cash(NR)现金金额
	 * @param TotalGurantee 现金和银行卡金额总和
	 * @param BankDeposit BankDeposit(银行卡支付金额)
	 * @param Percent 付款百分比
	 * @param percentstatus 付款状态(Low,Normal)
	 * @param memo 备注memo
	 */
	public function SaveReport() {
		$rule = [
			"GroupID" => "require",
			"SystemTotal" => "require",
			"Percent" => "require",
			"percentstatus" => "require",
		];
		$msg = [
			"GroupID" => "请填写订单汇总编号!",
			"SystemTotal" => "请填写货物总价值(SystemTotal)!",
			"Percent" => "请填写付款百分比!",
			"percentstatus" => "付款状态必须填写",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$GroupID = $this->input['GroupID'];
		return $this->UpdateBorrowReturnReport($GroupID); //执行保存操作
	}
	//保存操作表borrow_return_report
	private function UpdateBorrowReturnReport($GroupID) {
		//cashmoney现金金额,tellermoney刷卡金额,
		try {
			$kits = $this->input['kits'] ?: 0;
			$tellermoney = $this->input['BankDeposit'] ?: 0;
			$Percent = $this->input['Percent'];
			$memo = $this->input['memo'] ?: null;
			$sql = "update borrow_return_report set kits=" . $kits . ",totalmoney=" . $this->input['SystemTotal'] . ",cashmoney=" . $this->input['Cash'] . ",tellermoney=" . $tellermoney . ",cash_tellersum=" . $this->input['TotalGurantee'] . ",paid_value_percent='" . $Percent . "',percentstatus='" . $this->input['percentstatus'] . "' where groupid='" . $GroupID . "'";
			$this->Exc_Sql($sql); //更改表borrow_return_report数据
			$sql = "update tb_saleforborrowreturn set memo='" . $memo . "',TotalMoney=" . $this->input['SystemTotal'] . ",kits=" . $kits . " where groupid='" . $GroupID . "'";
			$this->Exc_Sql($sql); //更改表tb_saleforborrowreturn数据
			return suc('保存成功！Saved');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
	}

	/**
	 * @title 借货系统 订单入库提货系统(GoToWareHouse)
	 * @type interface
	 * @login 1
	 * @param Percent 付款百分比
	 * @param allowcode 是否有授权码
	 * @param GroupID 订单编号
	 * @param SystemTotal 货物总价值
	 * @param Date 日期
	 * @param shopno 专卖店编号
	 * @param French 是否勾选语言选择(0,1)
	 * @param kits  套件数量
	 */
	public function button5_Click() {
		$rule = [
			"GroupID" => "require",
			"SystemTotal" => "require",
			"Percent" => "require",
			"shopno" => "require",
		];
		$msg = [
			"GroupID" => "请填写订单汇总编号!",
			"SystemTotal" => "请填写货物总价值(SystemTotal)!",
			"Percent" => "请填写付款百分比!",
			"shopno" => "请填写专卖店或者经销商编号!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$GroupID = $this->input['GroupID'];
		$this->accredit(); //检查授权
		if ($this->CheckIfWenttoStock($GroupID)) {
			$this->GotoWarehouse($GroupID); //进入提货系统
			if ($this->CheckIfWentStockIsCorrect($GroupID)) {
				$this->Activate_FD_Sale($GroupID); //更改状态current_status为1
				//toolStripMenuItem1.Enabled = true;
				$this->SetMarkStandforInto($GroupID); //设置进入库存提货系统成功的标志位
				return suc("成功进入提货系统！");
			} else {
				return err(9000, "已经进入库存提货系统,核对不正确，联系Eric！");
			}
		} else {
			if (!$this->CheckIfWentStockIsCorrect($GroupID)) {
				return err(9000, "已经进入库存提货系统,核对不正确，联系Eric！");
			}
			return suc("此汇总单已经保存进入提货系统,无须再次保存！");
		}
	}
	//检查授权
	private function accredit() {
		$allowcode = $this->input['allowcode'] ?: 0; //是否有授权码
		$Percent = $this->input['Percent']; //付款百分比
		$Percent = str_replace('%', '', $Percent);
		if (!$allowcode) {
			if ($Percent < 30) {
				return err(9000, '您的付款比例低于百分之30,无法进入提货系统！');
			}
		} else {
			$sql = "SELECT BorrowPercent FROM EnterApp_Role WHERE RoleID=(SELECT RoleID FROM EnterApp_UserConnectRole WHERE UserID=(SELECT UserID FROM EnterApp_User WHERE BorrowAllowCode='" . $allowcode . "'))";
			$BorrowPercent = $this->GetStringData($sql);
			$BorrowPercent = $BorrowPercent * 100;
			if ($Percent < $BorrowPercent) {
				return err(9000, "授权人授权比例权限不足,无法进入提货系统！");
			}
		}
	}
	//检查是否已经进入提货系统
	public function CheckIfWenttoStock($GroupID) {
		$result = false;
		$sql = "";
		$StockInfo = "";
		$sql = "select count(*) from stockoutdetail where stockoutid in(select stockoutid from stockout where groupid='" . $GroupID . "') ";
		$StockInfo = $this->GetStringData($sql);
		if ($StockInfo == "0") {
			$result = true;
		}
		return $result;
	}
	//检查进入提货系统的数据是否完全正确
	private function CheckIfWentStockIsCorrect($GroupID) //倒序排列，以前台数据为循环体，因为仓库数据比前台数据多些尼龙袋和kits5项
	{
		$result = true;
		$sql = "";
		$sql = "select * from (select productid,sum(amount) as Amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where groupid='" . $GroupID . "') group by productid) a where amount>0 order by productid desc"; //载入扣货后的销售单产品,尼龙袋和kits编号是比较小的数字，故库存里多出了的数字是小数字，倒序可以提前循环比较完
		$Sale_DeductDS = $this->GetDSData($sql); //来自业绩销售产品和扣货产品之和
		$sql = "select Productid,Amount from stockout,stockoutdetail where stockoutdetail.stockoutid=stockout.stockoutid and groupid='" . $GroupID . "' order by productid desc"; //来自提货系统的数据
		$StockDS = $this->GetDSData($sql);
		for ($i = 0; $i < count($Sale_DeductDS); $i++) {
			if ($Sale_DeductDS[$i]['productid'] == $StockDS[$i]['Productid']) {
				if ($Sale_DeductDS[$i]['Amount'] != $StockDS[$i]['Amount']) //两块数据逐个对比，不一致报警！
				{
					exception("数据不一致信息如下：\n" . $Sale_DeductDS[$i]['productid'] . ":" . $Sale_DeductDS[$i]['Amount'] . "\n" . $StockDS[$i]['Productid'] . ":" . $StockDS[$i]['Amount']);
					$result = false;
				}

			}
		}
		return $result;
	}
	//进入提货系统
	private function GotoWarehouse($GroupID) {
		$NewStockOutId = $this->GetNewStockOutID();
		$countSnylon = $this->countSnylon($this->input['SystemTotal']);
		$sql = "INSERT stockout(stockoutid,lastedittime,groupid,status,toplace,insertperson,insertdate,saletype,saledate,shopno,memo,snylon,bnylon) values(" . $NewStockOutId . ",'" . date(Y - m - d) . "','" . $GroupID . "','0','Distributor','" . $this->TellerOper . "','" . date(Y - m - d) . "','" . $this->SaleType . "','" . $this->input['Date'] . "','" . $this->input['shopno'] . "','NoReturn','" . $countSnylon['snylon'] . "','" . $countSnylon['bnylon'] . "')"; //状态为0表示刚进入库存，未激活，库管无法看到，1表示可以获取，2表示发货完毕
		if ($this->Exc_Sql($sql) > 0) {
			$sql = "INSERT stockoutinfo(stockoutid,sendperson,sendip,sendsoftware,sendfunction) values(" . $NewStockOutId . ",'" . $this->TellerOper . "','" . $this->getIp() . "','借货系统','" . $this->get_operation() . "')";
			if ($this->Exc_Sql($sql) > 0) {
				$amount = "";
				$pid = "";
				$sql = "select * from (select productid,sum(amount) as Amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where groupid='" . $GroupID . "') group by productid) a where amount>0"; //载入扣货后的销售单产品
				$ds = $this->GetDSData($sql);
				foreach ($ds as $k => $v) {
					$pid = $v[0];
					$amount = $v[1];
					$sql = "INSERT INTO stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo) values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . "," . $pid . "," . $amount . ",'" . $this->SaleType . "') ";
					if ($this->Exc_Sql($sql) < 0) {
						return err(9000, "向表stockoutdetail中插入数据失败！Sql:" . $sql);
					}
				}
				$kitslanguage = $this->GetKitsLanguage();
				$kits = $this->input['kits'] ?: 0;
				$this->ProcessNylon_kitsIntoDetail($NewStockOutId, $countSnylon, $kits, $kitslanguage, $this->SaleType);
			} else {
				exception("向表stockoutinfo中插入数据失败！Sql:" . $sql);
				$sql = "delete from stockout where stockoutid=" . $NewStockOutId;
				if ($this->Exc_Sql($sql) < 1) {
					return err(9000, "回滚清除表stockout数据失败！Sql:" . $sql);
				}

			}
		} else {
			return err(9000, "向表stockout中插入数据失败！Sql:" . $sql);
		}
	}
	//把套件以及大小尼龙袋数据插入StockOutDetail表中
	public function ProcessNylon_kitsIntoDetail($NewStockOutId, $countSnylon, $kits, $kitslanguage, $SaleType) {
		$StockOutDetail = model("StockOutDetail");
		$data_d["StockOutID"] = $NewStockOutId;
		$data_d["Memo"] = $SaleType;
		$productid = [];
		Db::starttrans();
		try {
			if ($countSnylon['snylon'] != 0) {
				$data_d["Amount"] = $countSnylon['snylon'];
				$data_d["ProductID"] = 35;
				$status = $StockOutDetail->add($data_d);
				if ($status["stat"] == 0) {
					throw new Exception("向表stockoutdetail中插入小尼龙袋数据失败！", 1);
				}
				if ($countSnylon['bnylon'] != 0) {
					$data_d["Amount"] = $countSnylon['snylon'];
					$data_d["ProductID"] = 34;
					$status = $StockOutDetail->add($data_d);
					if ($status["stat"] == 0) {
						throw new Exception("向表stockoutdetail中插入大尼龙袋数据失败！", 2);
					}
				}
			}
			if ($kits > 0) {
				$data_d["Amount"] = $kits;
				if ($kitslanguage == 'French') {
					$productid = [61, 63, 64, 36, 60];
				} else {
					$productid = [61, 63, 64, 36, 59];
				}
				foreach ($productid as $v) {
					$data_d["ProductID"] = $v;
					$status = $StockOutDetail->add($data_d);
					if ($status["stat"] == 0) {
						throw new Exception("向表stockoutdetail中插入套件数据失败！", 3);
					}
				}
			}
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
		return suc($status);
	}
	//产生stockout表新的stockoutid
	public function GetNewStockOutID() {
		$sql = "select isnull(max(stockoutid),0)+1 from stockout";
		$outid = $this->GetStringData($sql);
		return $outid;
	}
	//产生stockoutdetail表新的stockoutdetailid
	public function GetNewStockOutDetailID() {
		$sql = "select isnull(max(stockoutdetailid),0)+1 from stockoutdetail";
		$outid = $this->GetStringData($sql);
		return $outid;
	}
	//判断计算赠送尼龙袋的数量
	private function countSnylon($SystemTotal) {
		if ($this->input['snylon']) {
			$num = round($SystemTotal / 10000);
			if ($num == 0) {
				$data['snylon'] = $data['bnylon'] = 0;
			} else if ($num > 0 && $num < 4) {
				$data['snylon'] = $num;
				$data['bnylon'] = 0;
			} else {
				$data['snylon'] = $num;
				$data['bnylon'] = floor($num / 4);
			}
		} else {
			$data['snylon'] = $data['bnylon'] = 0;
		}
		return $data;
	}
	//判断选择的套装语言版本
	private function GetKitsLanguage() {
		$KitsLanguage = $this->input['French'] ?: 0;
		if ($KitsLanguage) {
			return "French";
		} else {
			return "English";
		}
	}
	//变更表tb_SaleforBorrowReturn状态current_status为1
	public function Activate_FD_Sale($GroupID) {
		$SetGpID_Sql = "";
		$SetReportType = "";
		$SetGpID_Sql = "update tb_SaleforBorrowReturn set current_status='1' where current_status='0' and groupid='" . $GroupID . "'";
		$this->Exc_Sql(SetGpID_Sql); //状态变为1表示前台过程完毕
	}
	//更改stockout表中status的状态为1表示进入库存提货系统成功
	private function SetMarkStandforInto($GroupID) {
		$sql = "update stockout set status='1' where status<2 and groupid='" . $GroupID . "'";
		if ($this->Exc_Sql($sql) < 1) {
			return err(9000, "设置进入库存提货系统成功的标志位失败！");
		}
	}
	/**
	 * @title 借货系统 撤销订单(RevertFromWareHouse)
	 * @type interface
	 * @login 1
	 * @param GroupID 订单编号
	 */
	public function RevertFromWareHouse() {
		$GroupID = $this->input['GroupID'];
		if ($this->CheckIfStockedOut($GroupID)) {
			return err(9000, "该编号订单已经发货，无法撤销！\n The Sale with the GroupID is already sent to Distributor!");
		} else {
			$this->RemoveToStock($GroupID);

		}
	}
	//检查该订单是否已经发货
	private function CheckIfStockedOut($GroupID) {
		$stockoutstatus = false;
		$sql = "select count(*) from stockout where status='2' and saletype='" . $this->SaleType . "'and groupid='" . $GroupID . "'";
		if ($this->GetStringData($sql) > 0) {
			$stockoutstatus = true;
		}
		return $stockoutstatus;
	}
	//撤销订单
	private function RemoveToStock($GroupID) {
		$sql = "delete from stockoutdetail where stockoutid in(select stockoutid from stockout where saletype='" . $this->SaleType . "'and groupid='" . $GroupID . "')";
		if ($this->Exc_Sql($sql) < 0) {
			return err(9000, "表stockoutdetail记录撤销失败！Sql:" . $sql);
		} else {
			$sql = "delete from stockoutinfo where stockoutid in(select stockoutid from stockout where saletype='" . $this->SaleType . "'and groupid='" . $GroupID . "')";
			if ($this->Exc_Sql($sql) < 0) {
				return err(9000, "表stockoutinfo记录撤销失败！Sql:" . $sql);
			} else {
				$sql = "delete from stockout where saletype='" . $this->SaleType . "'and groupid='" . $GroupID . "'";
				if ($this->Exc_Sql($sql) < 0) {
					return err(9000, "表stockoutinfo记录撤销失败！Sql:" . $sql);
				} else {
					$this->UnActivate_FD_Sale($GroupID);
					return suc("撤销扣货成功！");
				}
			}
		}

	}
	//更改tb_SaleforBorrowReturn表current_status为0
	public function UnActivate_FD_Sale($GroupID) {
		$SetGpID_Sql = "";
		$SetGpID_Sql = "update tb_SaleforBorrowReturn set current_status='0' where current_status='1' and groupid='" . $GroupID . "'";
		$this->Exc_Sql($SetGpID_Sql); //状态变为1表示前台过程完毕
	}
}
