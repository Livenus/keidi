<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 选择扣货
 * @type menu
 * @login 1
 */
class ReturnBorrowed extends Common {

	public $deductMark = 0;
	public $error = false;
	public $SaleType = "DPBV";
	public $dataGridView2;
	public $SaleDate = "SaleDate";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}

	public function ReturnBorrowed_Load() {
		$data['Fresh'] = $this->Fresh();
		$this->Set_Qty(); //设置DPBV表的扣货
		$this->GetKits_ReleaseMoney(); //获取kits和释放的押金数额
		$data['input'] = $this->input;
		return $data;
	}
	private function Fresh() {
		$data["GetBorrowedRecord"] = $this->GetBorrowedRecord(); //获取借货信息
		$this->Get_DpbvReport_Info(); //获取个人消费产品
		$data['Get_DpbvReport_Info'] = $this->dataGridView2;
		$this->SetdeductMark();
		return $data;
	}
	private function Get_DpbvReport_Info() {

		$sqlorder = "select Productno as ID,cname as Name,'' as Dpbv,''as ToReturn,''as Balance from tb_product where (memberprice>0 AND status='1'and productno<>'M051') or productno='M05' ";

		$data = $this->query_Sql($sqlorder);
		$this->dataGridView2 = $data;
		$this->Set_Qty();
		// GetTotalResult();
	} //录入成功后显示在表单2上
	private function Set_Qty() {
		$this->SetDpbvQty();
		$this->GetSelectBorrowToDataGrid2();
		$this->CalcBalance();

	}
	private function SetDpbvQty() {
		$sql = "select productno,dpbv_productsdetail.amount as qty from dpbv_productsdetail,tb_product where saleid in"
		. " (select saleid from dpbv_products where groupid='" . $this->input['GID'] . "')and dpbv_productsdetail.productid=tb_product.productid";
		$data = $this->query_Sql($sql);
		foreach ($this->dataGridView2 as $k => $v) {
			$vv = array_values($v);
			$PNO = $vv[0];
			foreach ($data as $vvv) {
				$vvvv = array_values($vvv);
				$PNO1 = $vvvv[0];
				if ($PNO == $PNO1) {
					$this->dataGridView2[$k]["Dpbv"] = $vvvv[1];
					break;
				}

			}
		}

	}
	private function GetKits_ReleaseMoney() {

		$sql = "select sum(cashmoney)as Cash,sum(tellermoney)as Teller from borrow_return_report where returnstatus='0'and groupid " //returnstatus 表明押金未释放，returnpercent表示还货的比例，当还货比例为1时returnstatus变成1,标志着押金可以释放，当标志变成2时表示押金已经释放
		 . " in(select BorrowedGID from BorrowedSelectedGID where saleGID='" . $this->input['GID'] . "')";
		$data = $this->query_Sql($sql);
		$data = $data[0];
		$this->input['ReleaseTeller'] = $data["Cash"];
		$this->input['ReleaseCash'] = $data["Teller"];
		$sql = "select sum(kits) as kits from tb_SaleforBorrowReturn where current_status='1' and groupid in(select groupid from borrow_return_report "
		. "where returnstatus='0' and groupid in(select BorrowedGID from BorrowedSelectedGID where saleGID='" . $this->input['GID'] . "'))";
		$data = $this->query_Sql($sql);
		$data = $data[0];
		$this->input['BorrowedKits'] = $data['kits']; //kits
		$this->input['BalanceKits'] = $this->input['SaleKits'] - $this->input['BorrowedKits'];
		$this->input['TotalRelease'] = $this->input['ReleaseTeller'] + $this->input['ReleaseCash'];
	}
	private function GetSelectBorrowToDataGrid2() {

		$sql = "select productno,Amount as QTY from (select productid,sum(amount) as amount from tb_SaleforBorrowReturn,tb_SaleDetailforBR " .
		"where tb_SaleforBorrowReturn.saleid=tb_SaleDetailforBR.saleid and directstatus='借货' and isnull(deductgroupid,'')<>'" . $this->input['GID'] . "' and current_status>='1' and groupid in(select BorrowedGID from BorrowedSelectedGID where saleGID='" . $this->input['GID'] . "') group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";
		$data = $this->query_Sql($sql);
		if (count($data) < 1) {

		} else {
			foreach ($data as $v) {
				foreach ($this->dataGridView2 as $k => $vv) {

					if ($v['productno'] == $vv['Productno']) {
						$this->dataGridView2[$k]["ToReturn"] = $v["QTY"];
					}

				}

			}
		}

	}
	private function CalcBalance() {
		foreach ($this->dataGridView2 as $k => $v) {
			$vv = array_values($v);
			if ($vv[2] != "" || $vv[3]) {
				$this->dataGridView2[$k]['Balance'] = $vv[2] - $vv[3];

				if ($this->dataGridView2[$k]['Balance'] == "0") {
					$this->dataGridView2[$k]['Balance'] = "";

				}
			}
		}

	}
	private function SetdeductMark() {
		$sql = "select count(*) from borrow_return_report where returnstatus='1' and groupid in(select groupid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "')";
		if ($this->GetStringData($sql) == 0) {
			$deductMark = 0;
			//SetButtonColor();
		} else {
			$this->deductMark = 1;
			//SetButtonColor();
		}
	}
	private function GetBorrowedRecord() {
		$sql = "select groupid as GID,choose as [Select],totalnaira as ProductNaira,Kits,TotalMoney,cashmoney as PayCash,"
		. "tellermoney as PayTeller,cash_tellersum as TotalPay,paid_value_percent as PaidPercent,Sc_dist as SC,[Date],returnstatus as Status from borrow_return_report where ( returnstatus='0' and sc_dist='" . $this->input['SC'] . "') or groupid in(select groupid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "')";
		$data = $this->query_Sql($sql);
		return $data;

	}
	public function button3_Click() {
		if ($this->deductMark == 0) {
			Db::startTrans();
			try {
				$this->Get_DpbvReport_Info();
				$this->Update_borrowedselectedGID();
				$this->GetKits_ReleaseMoney();
				$this->Set_Qty();

				if ($this->CheckIfSaleCanDeduct()) {
					$data['dataGridView2'] = $this->dataGridView2;
					$data["input"] = $this->input;
				} else {
					exception("您的购货不够还欠货，无法使用押金，请重新选择扣货！\n The products you bought are not enough to return the ones you borrowed before!!");
				}

				Db::commit();
				return suc($data);
			} catch (Exception $exc) {

				Db::rollback();
				return err(9000, $exc->getMessage());
			}

		} else {
			return err(9000, "您已经扣货完毕，如需重新扣货需要取消之前的扣货！\n You already completed the deduct,if you want to cancel it please click button[取消扣货CancelDeduct]");
		}

	}
	private function Update_borrowedselectedGID() //存储临时选中的借货GID

	{

		$sql = "delete from borrowedselectedGID where saleGID='" . $this->input['GID'] . "'";
		$status = $this->Exc_Sql($sql);
		if ($this->GetStringData("select count(*) from borrowedselectedGID where saleGID='" . $this->input['GID'] . "'") != "0") {
			$status = $this->Exc_Sql($sql);
			exception("上次选项未清零！");
		} else {
			$data = $this->GetBorrowedRecord();
			foreach ($data as $v) {
				$vv = array_values($v);
				if ($vv[1] == "TRUE") {
					InsertBorrowedGID($vv[0]);
				}
			}
		}
	}
	private function InsertBorrowedGID($GID) {
		$sql = "insert borrowedselectedGID(BorrowedGID,saleGID) values('" . $GID . "','" . $this->input["GID"] . "')";
		$status = $this->Exc_Sql($sql);
		if ($status < 1) {
			exception("插入扣借货GID失败！Sql:" . $sql);
		}

	}
	private function CheckIfSaleCanDeduct() {
		$deduct = true;
		foreach ($this->dataGridView2 as $v) {
			$vv = array_values($v);
			if ($vv[4] < 0) {
				$deduct = false;
				$this->dataGridView2[$k]["color"] = ' Color.Red';
				break;

			}

		}
		if ($this->input['BalanceKits'] < 0) {
			$deduct = false;
		}

		return $deduct;
	}
	//region 扣货
	public function button4_Click() {
		if ($this->deductMark == 0) {
			Db::startTrans();

			try {
				$data = $this->DoReturn_Release();
				Db::commit();
				return $data;
			} catch (Exception $exc) {
				Db::rollback();
				return err(9000, $exc->getMessage());
			}

		} else {
			return err(9000, "您已经扣货完毕，如需重新扣货需要取消之前的扣货！\n You already completed the deduct,if you want to cancel it please click button[取消扣货CancelDeduct]");
		}

	}
	//region 生成还货明细
	private function DoReturn_Release() {
		$status = true;
		$sql = "select BorrowedGID from BorrowedSelectedGID where saleGID='" . $this->input['GID'] . "'";
		$data = $this->query_Sql($sql);
		foreach ($data as $v) {
			$GID = $v['BorrowedGID'];
			if ($this->GetStringData("select count(*) from borrow_return_report where returnstatus='0' and groupid='" . $GID . "'") == "0") //判断该借货是否释放押金
			{
				exception("编号为的" . $GID . "借货押金已经释放！");
				//break;
			} else {
				if (!$this->CreateDeductBorrowRecord($GID)) {
					$status = false;
					break;
				}
			}
		}
		if ($status) {
			return suc("扣货成功！");
		}

	}
	private function CreateDeductBorrowRecord($BorrowGID) {
		$status = true;
		$sql = "";
		$deductbv = "";
		$deductpv = "";
		$deductnaira = "";
		$kits = "";
		$saleid = $this->Get_NewID();
		$sql = "select -sum(Total_BV) as Total_BV,-sum(Total_PV) as Total_PV,-sum(Total_Naira)as Total_Naira,-sum(kits) as kits from tb_SaleforBorrowReturn where current_status='1' "
			. "and groupid =(select groupid from borrow_return_report where returnstatus='0' and groupid='" . $BorrowGID . "') ";
		$data = $this->query_Sql($sql);
		$data = $data[0];
		$deductbv = $data[0];
		$deductpv = $data[1];
		$deductnaira = $data[2];
		$kits = $data[3];
		$sql = "insert tb_SaleforBorrowReturn(saleid,saleno,oper_name,customerid,branchid,shopno,saledate,realdate,Total_BV,Total_PV,Total_Naira,kits,groupid,current_status,directstatus,deductgroupid,deductname,memo) "
		. "values(" . $saleid . "," . $this->Get_NewSaleNO() . ",'" . self::$realname . "',1436017,0,'" . $this->input['SC'] . "','" . $this->SaleDate . "','" . date('Y-m-d') . "'," . $deductbv . "," . $deductpv . "," . $deductnaira . "," . $kits . ",'" . $BorrowGID . "','0','还货','" . $this->input['GID'] . "','DPBV还货','')";
		$status = $this->Exc_Sql($sql);
		if ($status > 0) {
			$sql = "update tb_SaleforBorrowReturn set totalmoney=-abs(total_naira+kits*2500) where saleid=" . $saleid;
			$status = $this->Exc_Sql($sql);
			if ($status > 0) {
				CreateDeductBorrowRecordDetail($saleid, $BorrowGID);
			} else {
				exception("设置扣货totalmoney失败：" . $sql);
			}

		} else {
			$status = false;
			$error = true;
			exception("插入还货记录失败！Sql:" . $sql . "进行回滚！");
			$sql = "update tb_SaleforBorrowReturn set current_status='1' where groupid in(select groupid from tb_SaleforBorrowReturn where deductname='DPBV还货' and deductgroupid='" . $this->input['GID'] . "')";
			$status = $this->Exc_Sql($sql);
			if ($status < 0) {
				exception("回滚失败，回滚命令:" . $sql);
			} else {
				$sql = "delete from tb_SaleforBorrowReturn where deductname='DPBV还货' and deductgroupid='" . $this->input['GID'] . "'";
				$status = $this->Exc_Sql($sql);
				if ($status < 0) {
					exception("回滚失败，回滚命令:" . $sql);
				} else {
					exception("回滚成功！");
				}

			}

		}
		return $status;
	}
	private function Get_NewID() {
		return model("Procedure")->Get_NewID();
	}
	private function Get_NewSaleNO() {
		$sql = "select isnull(max(saleno),0)+1 from tb_SaleforBorrowReturn";
		$saleno = $this->GetStringData($sql);
		return $saleno;
	}
	private function CreateDeductBorrowRecordDetail($saleid, $BorrowedGID) {
		$DeductStatus = true;
		$sql = "select tb_product.Productid,0-amount as Amount,PV as BV, Retailprice as PV,Memberprice as Memberprice  from (select productid,sum(amount) as amount from tb_SaleforBorrowReturn,tb_SaleDetailforBR where tb_SaleforBorrowReturn.saleid=tb_SaleDetailforBR.saleid and current_status='1' "
			. "and groupid =(select groupid from borrow_return_report where returnstatus='0' and groupid='" . $BorrowedGID . "') group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid ";
		$data = $this->query_Sql($sql);
		foreach ($data as $v) {
			$vv = array_values($v);
			$insertsql = "";
			$saledetailid = "";
			$productid = "";
			$amount = "";
			$BV = "";
			$PV = "";
			$memberprice = "";
			$saledetailid = $this->Get_NewID();
			$productid = $vv[0];
			$amount = $vv[1];
			$BV = $vv[2];
			$PV = $vv[3];
			$memberprice = $vv[4];
			$insertsql = "insert tb_SaleDetailforBR(saledetailid,saleid,productid,amount,pv,retailprice,memberprice)"
				. " values(" . $saledetailid . "," . $saleid . "," . $productid . "," . $amount . "," . $BV . "," . $PV . "," . $memberprice . ")";
			$status = $this->Exc_Sql($insertsql);
			if ($status < 1) {
				$DeductStatus = false;
				$error = true; //出现错误，错误标志位置1
				exception("还货明细生成失败，请联系Eric！Sql:" . $insertsql . ",进行数据回滚！");
				$insertsql = "delete from tb_SaleforBorrowReturn where deductgroupid=" . $this->input['GID']; //删除还货条目
				$status = $this->Exc_Sql($insertsql);
				if ($status > 0) {
					$insertsql = "delete from tb_SaleDetailforBR where saleid=" . $saleid; //删除还货明细
					$status = $this->Exc_Sql($insertsql);
					if ($status < 0) {
						exception("回滚失败！");
					} else {
						return suc("回滚成功！");
					}

				}

				break;
			}

		}
		if ($DeductStatus) {
			BackBorrowMoney(saleid, BorrowedGID);
		} else {
			exception("还货明细插入失败，因此中断teller释放！");
			$error = true;
		}

	}
	//region 释放押金，teller押金释放到teller账户，现金押金直接用于DPBV或者转到teller账户
	private function BackBorrowMoney($saleid, $BorrowedGID) {

		if ($this->GetStringData("select count(*) as c from borrow_return_report where isnull(returnstatus,0)<>0 and groupid='" . $BorrowedGID . "'") > 0) {
			exception("押金已经释放，禁止重复释放！");
		} else {
			$cash_teller = 0;
			$sql1 = "";
			$deposit = "";
			$region = "";
			$cash = "";
			$sql1 = "select isnull(tellermoney,0) as teller,area as region,isnull(cashmoney,0) as cash from borrow_return_report where returnstatus='0' and groupid='" . $BorrowedGID . "'";
			$ds1 = $this->GetDSData($sql1);
			$ds1 = $ds1[0];
			$deposit = $ds1[0];
			$region = $ds1[1];
			$cash = $ds1[2];
			if ($deposit > 0) {
				$cash_teller = 0;
				ReleaseTeller($deposit, $BorrowedGID, $region, $cash_teller);

			}
			if ($cash > 0) {
				$cash_teller = 1;
				ReleaseTeller($deposit, $BorrowedGID, $region, $cash_teller);

			}
			$sql1 = "update tb_saleforborrowreturn set current_status='1' where saleid='" . $saleid . "'";
			if ($this->Exc_Sql($sql1) < 1) {
				exception("扣货成功标志位出错，命令：" . $sql1);
			}

		}
	}
	//region 取消扣货
	private function button5_Click() {
		if ($this->deductMark == 1) {
			if ($this->CheckIfReleasedMoneyUsed()) {
				if (!$this->CheckIfWentToStock()) {
					if ($this->deductMark == 0) {
						Db::startTrans();

						try {
							$data = $this->RemoveReleaseMoney();
							Db::commit();
							return $data;
						} catch (Exception $exc) {
							Db::rollback();
							return err(9000, $exc->getMessage());
						}} else {
						return err(9000, "DPBV已经入库，请先从库存撤出！");
					}
				}

			} else {
				return err(9000, "释放的押金已经使用，无法撤销，请联系Eric！");
			}

		} else {
			return err(9000, "您未进行扣货，无法取消！\n You are unable to cancel the deduct borrowed products before you do it!");
		}

	}
	private function CheckIfReleasedMoneyUsed() {
		$sql = "";
		$releasedmoney = "";
		$balance = "";
		$sql = "select sum(deposit) from fd_tellerdetail where deposit>0 and groupid in(select groupid from tb_saleforborrowreturn "+
		"where deductgroupid='" . $this->input["GID"] . "' and deductname='DPBV还货' ) and 用途='借货押金'";
		$releasedmoney = $this->GetStringData($sql);
		$sql = "select banlance from fd_teller where shopno='" . $this->input["SC"] . "'";
		$balance = $this->GetStringData($sql);
		if ($releasedmoney > $balance) {
			return false;
		} else {
			return true;
		}

	}
	private function CheckIfWentToStock() {
		$sql = "";
		$count = "";
		$sql = "select count(*) from stockout where groupid='" . $sql . "'";
		$count = $this->GetStringData($sql);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}

	}
	private function RemoveReleaseMoney() {
		$sql = "delete from fd_tellerdetail where deposit>0 and groupid in(select groupid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "' and deductname='DPBV还货' ) and 用途='借货押金'";
		if ($this->Exc_Sql($sql) > -1) {
			RemoveReturnBorrow();
		} else {
			exception("押金释放失败！Sql:" . $sql);
		}

	}
	private function RemoveReturnBorrow() {
		$sql = "update tb_saleforborrowreturn set current_status='1' where groupid in(select groupid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "' and deductname='DPBV还货' )";
		if ($this->Exc_Sql($sql) > -1) {
			$sql = "delete from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "' and deductname='DPBV还货' )";
			if ($this->Exc_Sql($sql) > -1) {
				$sql = "update borrow_return_report set returnstatus='0' where groupid in(select groupid from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "' and deductname='DPBV还货' )"; //可以设置还货比例，有空了
				if ($this->Exc_Sql($sql) > 0) {

					$sql = " delete from tb_saleforborrowreturn where deductgroupid='" . $this->input['GID'] . "' and deductname='DPBV还货'";
					if ($this->Exc_Sql(sql) > -1) {
						return suc("撤销扣货成功！");
					} else {
						exception("清除表tb_saleforborrowreturn记录失败！Sql:" . $sql);
					}

				} else {
					exception("标志位ReturnStatus失败！Sql:" . $sql);
				}

			} else {
				exception("清除表tb_saledetailforbr记录失败！Sql:" . $sql);
			}

		} else {
			MessageBox . Show("取消还货标志位失败，sql:"+sql);
		}

	} //清除本次还货记录
}
