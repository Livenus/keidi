<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 报单管理
 * @type menu
 * @login 1
 */
class ReportManage extends Common {
	public $SaleType = "业绩销售";
	public $StockRegionNo = "";
	public $Kits = "0";
	public $DebitMoney = "0";
	public $Disc_Trsfer = "0";
	public $Cash_USD = "0";
	public $ScBv = "0";
	public $ScPv = "0";
	public $ScNaira = "0";
	public $Area = "";
	public $Delete_View = "";
	public $PerameterDate = "";
	public $Check_Title = 0;
	public $IfMerged = 0;
	public $TellarMoney = "0";
	public $CashMoney = "0";
	public $toolStripTextBox1 = "";
	public $input;
	public $timer1;

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->FrontDeskCash = model("FrontDeskCash");
		$this->SaleEnteredByFront = model("SaleEnteredByFront");
		$this->input = input("post.");
	}

	public function button1_Click() {
		$status = $this->Check_If_ValidSaleNO($this->input["SC_A"]);
		if (isset($status['stat']) && $status['stat'] == 0) {
			return $status;
		}
		if ($this->input["SC_A"] == "") {
			return err(9001, "请输入报单编号！");

		} else if ($status && $this->Check_If_ValidSaleNO($this->input["SC_B"]) && $this->Check_If_ValidSaleNO($this->input["SC_C"]) && $this->Check_If_ValidSaleNO($this->input["SC_D"]) && $this->Check_If_ValidSaleNO($this->input["SC_E"]) && $this->Check_If_ValidSaleNO($this->input["SC_F"]) && $this->Check_If_ValidSaleNO($this->input["SC_G"]) && $this->Check_If_ValidSaleNO($this->input["SC_H"])) {
			$this->toolStripTextBox1 = "BIGSC";
			$data["err"] = "Already Merge\n已经合并过";
			$data["GeT_ReportToForm_ForBIGSC"] = $this->GeT_ReportToForm_ForBIGSC();
			if (isset($data["GeT_ReportToForm_ForBIGSC"]['stat']) && $data["GeT_ReportToForm_ForBIGSC"]['stat'] === 0) {
				return $data["GeT_ReportToForm_ForBIGSC"];
			}
			$this->ShowTotal();
			$this->Set_BigSC_NewGroupID();

			if ($this->input["Total_NR"] > 0) {
				if ($this->input["BigSCGroupID"] == "Ununique") {
					return err(9000, "部分专卖店所属不同");
				} else {
					$this->SetTextBoxEnable();
					$this->Insert_New_BigSC();
					$data["err"] = "Already Merge,Please click UpdateBigSc";
					$data["input"] = $this->input;
					return suc($data);
				}
			} else {
				$data["input"] = $this->input;
				return suc($data);
			}
		} else {
			if ($this->IfMerged == 0) {
				return err(9000, "报单编号不存在");
			}

		}
	}

	public function button7_Click() {
		if ((int) ($this->input["Total_NR"]) > 0) {
			$data["Update_FrontDesk_BigSC_Report"] = $this->Update_FrontDesk_BigSC_Report();
			$this->input["SC_A"] = "";
			$this->input["SC_B"] = "";
			$this->input["SC_C"] = "";
			$this->input["SC_D"] = "";
			$this->input["SC_E"] = "";
			$this->input["SC_F"] = "";
			$this->input["SC_G"] = "";
			$this->input["SC_H"] = "";
			return suc($data);
		}
		return err(9000, "Total_NR 为空");
	}

	public function button5_Click() {
		if (!$this->CheckIfhasRegion()) {
			return err(9000, "There is no achievement in region " . $this->input['Region'] . " on the date" . $this->input['From']);
		} else {
			$this->StockRegionNo = $this->GetRegionNO();
			if ($this->input['Region_index'] == -1) {
				return err(9000, "请选择区域店\nPls Select Regional Office");
			} else {
				if ($this->input['Region'] != "NONE") {
					$this->StockRegionNo = $this->GetRegionNO();
				}

				if ($this->StockRegionNo == "error") {
					return err(9000, "系统里出现两个区域店汇总！");
				} else {
					if ($this->input['From'] == $this->input['To'] && !empty($this->input['To'])) {
						$this->input['UpdateBigSC_status'] = false;
						$this->input['Regional'] = "Regional";
						$data["GeT_ReportToForm_ForRegional"] = $this->GeT_ReportToForm_ForRegional();
						$this->ShowTotal();
						if ($this->input['Region'] != "NONE") {
							$status = $this->SetRegionNOValue($this->StockRegionNo);
						}

						if (isset($status) && $status['stat'] === 0) {
							return $status;
						}
						$this->SetRegionIfGotoWarehouseStatus();
						$data["input"] = $this->input;
						return suc($data);
					} else {
						return err(9000, "请选择正确的日期");
					}

				}
			}
		}
	}

	public function button2_Click() {
		if ($this->CheckIfHasErrorTeller()) {
			return err(9000, 'There is a error in Tellersystem,pls contact with Eric');
		} else {
			$this->Get_ReportDetail_ForDaily();
			$status = $this->CheckIfNairaOK();
			if ($status['stat'] == 0) {
				return $status;
			}
			if ($status['stat'] == 1 && $status['data'] == true) {
				if ($this->input["From"] == $this->input["To"] || $this->Check_Title == 1) {
					if ($this->Check_Title == 0) {
						$this->input['toolStripTextBox1'] = "Daily";
					} else {
						$this->input['toolStripTextBox1'] = "Monthly";
					}

					$this->input['UpdateBigSC_status'] = false;
					$this->GeT_ReportToForm_ForDaily();
					$this->ShowTotal();
					$this->Check_Title = 0;
					$data['input'] = $this->input;
					return suc($data);
				} else {
					return err(9000, "请选择正确的日期！");
				}

			} else {
				return err(9000, "有部分专卖店未收款！");
			}

		}
	}

	public function button3_Click() {
		$month = explode("-", $this->input['From']);
		$month = $month[1];
		$month2 = explode("-", $this->input['To']);
		$month2 = $month2[1];
		if ($month != $month2 || $this->input['Non_Month']) {
			if ($this->CheckIfHasErrorTeller()) {
				return err(9000, "There is a error in Tellersystem,pls contact with Eric");
			} else {
				$this->Check_Title = 1;
				$data = $this->button2_Click();
				$this->Check_Title = 0;
				return $data;
			}
		} else {
			return err(9000, "请选择正确的日期范围");
		}

	}

	private function Get_ReportDetail_ForDaily() {
		try {
			$sql = "select sum(Kits) Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer," .
			"sum(cash_USD) as cash_USD,sum(scbv) as scbv,sum(scpv) as scpv,sum(scnaira) as scnaira from FrontDesk_Report where reporttype='SC' and  realdate>='" . $this->input['From'] . "'and realdate<='" . $this->input['To'] . "'";
			$data = $this->query_Sql($sql);
			$data = $data[0];
			$this->Kits = $data['Kits'];
			$this->CashMoney = $data["CashMoney"];
			$this->TellarMoney = $data["TellarMoney"];
			$this->DebitMoney = $data["debitmoney"];
			$this->Disc_Trsfer = $data["disc_trsfer"];
			$this->Cash_USD = $data["cash_USD"];
			$this->ScBv = $data["scbv"];
			$this->ScPv = $data["scpv"];
			$this->ScNaira = $data["scnaira"];
			if ($this->input['From'] == $this->input['To']) {
				$this->PerameterDate = $this->input['From'];
			} else {
				$this->PerameterDate = "Monthly";
			}

		} catch (\Exception $ee) {

			if (ee . Message . IndexOf("not set to") == -1) {
				MessageBox . Show(ee . Message);
			}

		}
	}

//从数据库得到各个汇总的cash,tellar信息

	public function button13_Click() {
		if ($this->CheckIfWentToStock($this->StockRegionNo)) {
			return err(9000, "已经进入库存系统，无法进入还货模块，请选退出库存再进行扣货！");
		} else {
			$url = url('ReturnBorrowed/ReturnBorrowed_Load');
			return suc($url);
		}
	}
//点击WentToWareHouse按钮触发此方法
	public function button10_Click() {
		if ($this->input['Region'] != "NONE") {
			if ($this->input['ReportStatus'] == "OK" && $this->input['Region_index'] != -1) {
				if ($this->input['From'] == $this->input['To']) {
					$check = $this->CheckIfSomeShopCollected();
					if (!$check) {
						$this->StockRegionNo = $this->GetRegionNO(); //获取区域店编号
						$stat = $this->AllRegionSCGotoWarehouse($this->StockRegionNo); //所有数据进入发货系统，检测没有进入的数据加入
						if ($stat['stat'] == 0) {
							return $stat;
						}
						$stat = $this->SetMarkForRegionTogether(); //置标志位可以发货
						if ($stat['stat'] == 0) {
							return $stat;
						}
						$this->SetRegionIfGotoWarehouseStatus();
					} else {
						return err(9000, "该区域部分店已经领货！");
					}

				} else {
					return err(9000, "进入提货系统，必须是同一天的某个区域店！");
				}

			} else {
				return err(9000, "Pls complete payement!");
			}

		} else {
			return err(9000, "零售不属于任何区域！");
		}

	}
	public function button11_Click() {
		if (!$this->CheckIfSomeStockedOut()) {
			Db::starttrans();
			try {
				$stats = $this->RevomeRegionSaleFromStock();
				$this->SetRegionIfGotoWarehouseStatus();
				Db::commit();
			} catch (\Exception $ee) {
				Db::rollback();
				return err(9000, $ee->getMessage());
			}
			$data['input'] = $this->input;
			$data['RevomeRegionSaleFromStock'] = $stats;
			return suc($data);
		} else {
			return err(9000, "无法撤销进入提货系统的业绩，已经发货！\n You can not revert from stock because it had been sent to d");
		}

	}
	private function CheckIfSomeStockedOut() {
		$result = true;
		$sql = "select count(*) as count from stockout where status='2' and groupid in(select regionno from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "')";
		$data = $this->GetStringData($sql);
		if ($data == "0") {
			$result = false;
		}

		return $result;
	}
	//查询分卖店编号按钮
	public function button8_Click() {
		$data = $this->Get_ReportNO_Mem_Info();
		return $data;
	}
	private function Get_ReportNO_Mem_Info() {
		try
		{
			$sqlorder = "select GroupID,shopno as SC,reportstatus as Status,kits,totalmoney as Total,cashmoney as Cash,tellarmoney as Teller,debitmoney as Debit,disc_trsfer as Trans,area as Region,scbv as BV,scpv as PV,scnaira as NR,oper_name as Oper,Cash_USD from FrontDesk_Report where reporttype='SC' and area='" . $this->input['Aera'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "' order by shopno";
			mlog($sqlorder);
			$data['input'] = $this->input;
			$data['list'] = $this->query_Sql($sqlorder);
			return suc($data);
		} catch (\Exception $ee) {
			return err(9000, $ee->getMessage());
		}
	}
	private function button15_Click() {
		$url = url('RegionLock/RegionLock_Load');
		return suc($url);
	}
	public function RevomeRegionSaleFromStock() {
		$sql = "delete from stockoutinfo where stockoutid in(select stockoutid from stockout where status<2 and groupid in(select regionno from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "'))";
		$status = $this->Exc_Sql($sql);
		if (!is_numeric($status)) {
			throw new \Exception($status['errmsg']);
		}
		if (is_numeric($status)) {
			$sql = "delete from stockoutdetail where stockoutid in(select stockoutid from stockout where status<2 and groupid in(select regionno from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "'))";
			$status = $this->Exc_Sql($sql);
			if (!is_numeric($status)) {
				throw new \Exception($status['errmsg']);
			}
			if (is_numeric($status)) {

				$sql = "delete from stockout where status<2 and groupid in(select regionno from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "')";
				$status = $this->Exc_Sql($sql);
				if (!is_numeric($status)) {
					throw new \Exception($status['errmsg']);
				}
				if (is_numeric($status)) {

					return suc("成功撤销提货系统的业绩订单，Reverted from Stock successfully！");
				}
			}
		} else {
			throw new \Exception("撤销失败！\n Revert stockout from Warehouse Unsuccessfully! Sql:" . $sql);
		}

	}
	private function SetMarkForRegionTogether() {
		$sql = "update stockout set status='1', regiontogether='" . $this->StockRegionNo . "' where status<2 and groupid in(select regionno from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "')";
		$status = $this->Exc_Sql($sql);
		if (!is_numeric($status)) {
			return err(9000, "进入提货系统失败，联系Eric！Sql:" . $sql);
		} else {
			return err(9000, "已经进入提货系统！\n Sent to Warehouse successfully!");
		}

	}
	#endregion

	private function CheckIfSomeShopCollected() {
		//判断是否区域店提货时已经有部门店已经取货，根据编号判断
		$colleced = false;
		$sql = "select groupid,status from stockout where groupid in(select groupid from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "')";
		$data = $this->query_Sql($sql);
		if (empty($data)) {
			return $colleced;
		}
		$data = $data[0];
		for ($i = 0; $i == count($data); $i++) {
			if ($data[$i]['status'] == "2") {
//状态0，进入未成为提货状态，1表示成为提货状态，2表示已经提货
				$colleced = true;
				break;
			}
		}
		return $colleced;
	}

	private function AllRegionSCGotoWarehouse($RegionNo) {
		//检查每一个区域下的专卖店，是否进入提货系统，是否已经提货
		$sql = "select groupid,shopno,scnaira,kits,kitsLanguage from frontdesk_report where reporttype='SC' and area='" . $this->input['Region'] . "' and realdate='" . $this->input['From'] . "'";
		$data = $this->query_Sql($sql);
		if (empty($data)) {
			return err(9000, "AllRegionSCGotoWarehouse没有此条数据");
		}
		$kitsLanguage = $data[0]['kitsLanguage'];
		$count = $this->GetStringData("select count(*) from stockout where groupid='" . $RegionNo . "'");
		if ($count == "0") {
			return $this->GotoWarehouse($RegionNo, $this->input['Region'], $this->input['S_Nylon'], $this->input['B_Nylon'], $this->input['Kits'], $kitsLanguage);
		}

		return suc("成功");
	}

	#region 进入提货系统

	private function GotoWarehouse($GID, $sc, $snylon, $bnylon, $kits, $kitslanguage) {
		try {
			Db::startTrans();
			$NewStockOutId = $this->GetNewStockOutID();
			$returnborrowstatus = $this->GetStringData("select top 1 ReturnBorrowedStatus from frontdesk_report where regionno='" . $GID . "'");
			$sql = "  insert stockout(stockoutid,lastedittime,groupid,status,toplace,insertperson,insertdate,saletype,saledate,shopno,memo,snylon,bnylon)"
			. " values(" . $NewStockOutId . ",'" . date("Y-m-d H:i:s") . "','" . $GID . "','0','Distributor','" . self::$realname . "','" . date("Y-m-d H:i:s") . "','" . $this->SaleType . "','" . $this->input['From'] . "','" . $sc . "','还货状态：" . $returnborrowstatus . "'," . $snylon . "," . $bnylon . ")"; //状态为0表示刚进入库存，未激活，库管无法看到，1表示可以获取，2表示发货完毕
			$status = $this->Exc_Sql($sql);
			if (is_numeric($status) > 0) {
				$name = 'GotoWarehouse';
				$sql = "insert stockoutinfo(stockoutid,sendperson,sendmac,sendip,sendsoftware,sendfunction)"
				. " values(" . $NewStockOutId . ",'" . self::$realname . "','" . $this->Local_Mac() . "','" . $this->Local_IP() . "','业绩销售系统','" . $name . "')";
				$status = $this->Exc_Sql($sql);
				if (is_numeric($status) > 0) {
					$ProcessOk = true;
					$sql = "select productid,sum(amount) as amount from tb_sale_Entered_ByFrontDesk,tb_saledetail_ByFrontDesk where tb_sale_Entered_ByFrontDesk.saleid=tb_saledetail_ByFrontDesk.saleid and Groupid in(select groupid from FrontDesk_Report where area='" . $this->input['Region'] . "') and realdate>='" . $this->input['From'] . "'and realdate<='" . $this->input['To'] . "' group by productid";
					$data = $this->query_Sql($sql);
					for ($i = 0; $i < count($data); $i++) {
						$pid = $data[$i]['productid'];
						$amount = $data[$i]['amount'];
						$pid = $this->ProccessSeperateProduct($pid, $amount, 1);
						if (isset($pid['stat']) && $pid['stat'] == 0) {
							return $pid;
						}
						$amount = $this->ProccessSeperateProduct($pid, $amount, 2);
						if (isset($pid['stat']) && $pid['stat'] == 0) {
							return $pid;
						}
						$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)"
						. " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . "," . $pid . "," . $amount . ",'" . $this->SaleType . "') ";
						$status = $this->Exc_Sql($sql);
						if (!is_numeric($status)) {
							$ProcessOk = false;
							break;
						}
					}
					if ($ProcessOk) {

						$stat = $this->ProcessNylon_kitsIntoDetail($NewStockOutId, $snylon, $kits, $kitslanguage);
						if ($stat['stat'] == 1) {

						} else {
							throw new \Exception($stat['errmsg']);
						}
					} else {
						throw new \Exception("取消处理kits和nylon");
					}
				} else {
					throw new \Exception("向表stockoutinfo中插入数据失败,数据回滚！Sql:" . $sql);
					$sql = "delete from stockout where stockoutid=" . $NewStockOutId;
					$status = $this->Exc_Sql($sql);
					if (!is_numeric($status)) {
						throw new \Exception("回滚清除表stockout数据失败！Sql:" . $sql);
					}

				}
			} else {
				throw new \Exception("向表stockout中插入数据失败！Sql:" . $sql);
			}

			Db::commit();
		} catch (\Exception $ee) {
			Db::rollback();
			return err(8000, $ee->getMessage());
		}
	}

	#region 处理编号为M041，M092等产品的数量

	private function ProccessSeperateProduct($pid, $amount, $mark) {
//1表示返回产品，2表示返回处理后的数量
		$sql = "select count(*) from kedi_productseperate where seperatePid=" . $pid;
		$count = $this->GetStringData($sql);
		if ($count == "0") {
			if ($mark == 1) {
				return $pid;
			} else {
				return $amount;
			}

		} else {
			if ($mark == 1) {
				return $this->GetStringData("select mergeproductid from kedi_productseperate where seperatePid=" . $pid);
			} else {
				$time = (int) $this->GetStringData("select SeperateTime from kedi_productseperate where seperatePid=" . $pid);

				$newamount = (int) $amount / $time;
				if ($amount % $time != 0) {
					return err(9000, "产品编号为" . $this->GetStringData("select productno from stockproduct_material where productid=" . $pid . "的产品数量不能被" . $time . "整除"));
				}

				return $newamount;
			}
		}
	}

	private function ProcessNylon_kitsIntoDetail($NewStockOutId, $snylon, $kits, $kitslanguage) {
		try {
			$qty1 = $snylon;
			if ($qty1 > 0) {
//两个数据不一致时，且stockout表里的数量不是0，表示未录入
				$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //小尼龙袋
				 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",65," . $qty1 . ",'" . $this->SaleType . "') "; //加小尼龙袋
				$status = $this->Exc_Sql($sql);
				if ($status['stat'] == 0) {
					throw new \Exception($status['errmsg']);
				}
				if (is_numeric($status)) {
					if (Convert . ToInt32($qty1) > 4) {
						$big = $qty1 / 4;
						$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //大尼龙袋
						 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",66," . $big . ",'" . $this->SaleType . "') ";
						$status = $this->Exc_Sql($sql);
						if ($status['stat'] == 0) {
							throw new \Exception($status['errmsg']);
						}
						if (is_numeric($status)) {

						} else {
							throw new \Exception("向表stockoutdetail中插入大尼龙袋数据失败！Sql:" . $sql);
						}

					}
				} else {
					throw new \Exception("向表stockoutdetail中插入小尼龙袋数据失败！Sql:" . $sql);
				}

			}

			if ($kits > 0) {
				$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //pin
				 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",61," . $kits . ",'" . $this->SaleType . "') ";
				$status = $this->Exc_Sql($sql);
				if ($status['stat'] == 0) {
					throw new \Exception($status['errmsg']);
				}
				if (is_numeric($status)) {
					$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //NonwovenBag
					 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",63," . $kits . ",'" . $this->SaleType . "') ";
					$status = $this->Exc_Sql($sql);
					if ($status['stat'] == 0) {
						throw new \Exception($status['errmsg']);
					}
					if (is_numeric($status)) {
						$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //Siliconebracelet手环
						 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",64," . $kits . ",'" . $this->SaleType . "') ";
						if (is_numeric($status)) {
							$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
							 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",36," . $kits . ",'" . $this->SaleType . "') ";
							if (is_numeric($status)) {
								if ($kitslanguage == "French") {
									$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
									 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",60," . $kits . ",'" . $this->SaleType . "') ";
									$status = $this->Exc_Sql($sql);
									if ($status['stat'] == 0) {
										throw new \Exception($status['errmsg']);
									}
									if (is_numeric($status)) {

									} else {
										throw new \Exception($status['errmsg']);
									}

								} else {
									$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
									 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",59," . $kits . ",'" . $this->SaleType . "') ";
									$status = $this->Exc_Sql($sql);
									if ($status['stat'] == 0) {
										throw new \Exception($status['errmsg']);
									}
									if (is_numeric($status)) {

									} else {
										throw new \Exception($status['errmsg']);
									}

								}
							} else {
								throw new \Exception($status['errmsg']);
							}

						} else {
							throw new \Exception($status['errmsg']);
						}

					} else {
						throw new \Exception($status['errmsg']);
					}

				} else {
					throw new \Exception($status['errmsg']);
				}

			}
			return suc("插入数据成功");
		} catch (\Exception $exc) {

			return err($exc->getMessage());
		}
	}

	private function GetNewStockOutID() {
		$sql = "select isnull(max(stockoutid),0)+1 from stockout";
		$outid = $this->GetStringData($sql);
		return $outid;
	}

	private function GetNewStockOutDetailID() {
		$sql = "select isnull(max(stockoutdetailid),0)+1 from stockoutdetail";
		$outid = $this->GetStringData($sql);
		return $outid;
	}

	private function CheckIfWentToStock($GID) {
		$sql = "select count(*) from stockout where regiontogether='" . $GID . "'";
		$count = $this->GetStringData($sql);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}

	}

	private function CheckIfHasErrorTeller() {
		$sql = "select count(*) from frontdesk_report where realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "' and reporttype='SC' and tellarmoney>0 and " .
			"groupid not in(select isnull(groupid,0) from fd_tellerDetail where used>0 and date>='2017-6-01' )";
		$result = $this->GetStringData($sql);
		if ((int) $result > 0) {
			return true;
		} else {
			return false;
		}

	}

	private function CheckIfNairaOK() {
		$NairaMark = false;
		$naira = "";
		try {
			$sql = "select sum(Total_naira) as Total_naira from tb_sale_entered_byfrontdesk where realdate>='" . $this->input["From"] . "'and realdate<='" . $this->input["To"] . "'";
			$data = $this->query_Sql($sql);
			$naira = $data[0]['Total_naira'];
			if ($this->ScNaira == $naira) {
				if ($this->ScNaira != 0) {
					$NairaMark = true;
				} else {
					return err(3000, "今天无单子！");
				}

			}
		} catch (\Exception $ee) {
			$msg = $ee->getMessage();
			if (strpos($msg, "not set to") === false) {
				return err(3000, $msg);
			}

		}
		return suc($NairaMark);
	}

	private function SetRegionNOValue($rgno) {
		$sql = "update frontdesk_report set regionno='" . $rgno . "' where area='" . $this->input['Region'] . "' and realdate='" . $this->input['From'] . "'";
		if ($this->Exc_Sql($sql)) {
			return err(9000, "设置RegionNO失败,Sql:" . $sql);
		}

		return suc("设置成功");
	}

	private function SetRegionIfGotoWarehouseStatus() {
		$check = $this->CheckIfAllRegionSaleWentToStock();
		if ($check) {
			$this->input['GotoWarehouse_color'] = 'Red';
			$this->input['GotoWarehouse_status'] = false;
			$this->input['RevertFromStcok_color'] = 'green';
			$this->input['RevertFromStcok_status'] = true;
		} else {
			$this->input['GotoWarehouse_color'] = 'green';
			$this->input['GotoWarehouse_status'] = true;
			$this->input['RevertFromStcok_color'] = 'Red';
			$this->input['RevertFromStcok_status'] = false;
		}
	}

	private function CheckIfAllRegionSaleWentToStock() {
		$status = true;
		try {
			$sql = "select groupid,regionno from frontdesk_report where area='" . $this->input['Region'] . "' and reporttype='SC' and realdate='" . $this->input['From'] . "'";
			$data = $this->query_Sql($sql);
			for ($i = 0; $i < count($data); $i++) {
				$GID = $data[$i]['groupid'];
				$RNo = $data[$i]['regionno'];
				$sql = "select count(*) as count from stockout where groupid='" . $RNo . "' or regiontogether='" . $RNo . "'";
				$result = $this->GetStringData($sql);
				if ($result == "0") {
					$status = false;
					break;
				}
			}
		} catch (\Exception $ee) {
			echo $ee->getMessage();
			exit();
		}
		return $status;
	}

	private function GeT_ReportToForm_ForRegional() {

		$this->Get_Result_Product_Info(); //得到所以产品信息
		$this->Get_SaleProduct_Info_ForRegional(); //得到销售的产品信息
		$this->Get_Sale_Report();
		$data["Get_Result_Mem_Info"] = $this->Get_Result_Mem_Info();
		$this->Set_Qty_0();
		$this->Get_ReportDetail_ForRegional();
		$this->Proccess_Empty();
		$this->Set_TextBoxGet_ForRegional();
		return $data;
	}

	private function GeT_ReportToForm_ForDaily() {

		$this->Get_Result_Product_Info(); //得到所以产品信息
		$this->Get_SaleProduct_Info_ForDaily(); //得到销售的产品信息
		$this->Get_Sale_Report();
		$data["Get_Result_Mem_Info"] = $this->Get_Result_Mem_Info();
		$this->Set_Qty_0();
		$this->Get_ReportDetail_ForDaily();
		$this->Proccess_Empty();
		$this->Set_TextBoxGet_ForDaily();
		return $data;
	}

	private function Set_TextBoxGet_ForDaily() {
		try {
			$this->input["Kits"] = $this->Kits;
			$this->input["CashMoney"] = $this->CashMoney;
			$this->input["TellarMoney"] = $this->TellarMoney;
			$this->input["DebitMoney"] = $this->DebitMoney;
			$this->input["Disc_Trsfer"] = $this->Disc_Trsfer;
			$this->input["Cash_USD"] = $this->Cash_USD;
			$this->input["S_Nylon"] = floor($this->ScNaira / 10000.0);
			$this->input["B_Nylon"] = floor($this->input["S_Nylon"] / 4);
			$this->input["Grand_Total"] = $this->input["Cash(NR)"] + $this->input["Bank_Deposit"] + $this->input["Debit"] + $this->input["D/Tsfer"] + $this->input["Cash(usd)"] * 150;
			$this->input["System_Total"] = $this->ScNaira + $this->input["Kits"] * $this->input["UnitPrice"];
			$this->input["Result"] = $this->input["Grand_Total"] - $this->input["$this->input"];
			$this->input["Result"] = "ALL";
		} catch (\Exception $e) {

		}
	}

	private function Get_ReportDetail_ForRegional() {
		try {
			$sql = "select sum(Kits) Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer," .
			"sum(cash_USD) as cash_USD,sum(scbv) as scbv,sum(scpv) as scpv,sum(scnaira) as scnaira from FrontDesk_Report where reporttype='SC' and area='" . $this->input['Area'] . "' "
			. " and  realdate>='" . $this->input['From'] . "'and realdate<='" . $this->input['To'] . "'";
			$data = $this->query_Sql($sql);
			$data = $data[0];
			$this->Kits = $data['Kits'];
			$this->CashMoney = $data["CashMoney"];
			$this->TellarMoney = $data["TellarMoney"];
			$this->DebitMoney = $data["debitmoney"];
			$this->Disc_Trsfer = $data["disc_trsfer"];
			$this->Cash_USD = $data["cash_USD"];
			$this->ScBv = $data["scbv"];
			$this->ScPv = $data["scpv"];
			$this->ScNaira = $data["scnaira"];
		} catch (\Exception $ee) {

		}
	}

	#region Daily

	private function Get_SaleProduct_Info_ForDaily() {

		$Delete_View = "drop view product_table1";
		$this->Exc_Sql($Delete_View);
		$Prod_Sql = "create view product_table1 as select productno as code,cname as Products,unit,productno,pv as BV,retailprice as PV,memberprice as Naira," .
		"Amount as QTY,[Total BV]=Amount*pv,[Total PV]=Amount*retailprice,[Total NR]=Amount*memberprice from (select productid,sum(amount) as amount from tb_sale_Entered_ByFrontDesk,tb_saledetail_ByFrontDesk " .
		"where tb_sale_Entered_ByFrontDesk.saleid=tb_saledetail_ByFrontDesk.saleid and current_status<>'0' and realdate>='" . $this->input['From'] . "'and realdate<='" . $this->input['To'] . "' group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";

		$this->Exc_Sql($Prod_Sql);
	}

//从销售库取数据

	private function Set_TextBoxGet_ForRegional() {
		try {
			$this->input["Kits"] = $this->Kits;
			$this->input["CashMoney"] = $this->CashMoney;
			$this->input["TellarMoney"] = $this->TellarMoney;
			$this->input["DebitMoney"] = $this->DebitMoney;
			$this->input["Disc_Trsfer"] = $this->Disc_Trsfer;
			$this->input["Cash_USD"] = $this->Cash_USD;
			$this->input["S_Nylon"] = floor($this->ScNaira / 10000.0);
			$this->input["B_Nylon"] = floor($this->input["S_Nylon"] / 4);
			$this->input["Grand_Total"] = $this->input["Cash(NR)"] + $this->input["Bank_Deposit"] + $this->input["Debit"] + $this->input["D/Tsfer"] + $this->input["Cash(usd)"] * 150;
			$this->input["System_Total"] = $this->ScNaira + $this->input["Kits"] * $this->input["UnitPrice"];
			$this->input["Result"] = $this->input["Grand_Total"] - $this->input["$this->input"];
			$this->input["ScNaira"] = $this->Get_Area_ForBIGSC();
		} catch (\Exception $ee) {

		}
	}

	private function CheckIfhasRegion() {
		if (empty($this->input['From'])) {
			return false;
		}
		$sql = "select count(*) from frontdesk_report where area='" . $this->input['Region'] . "' and realdate='" . $this->input['From'] . "'";
		mlog($sql);
		$result = $this->GetStringData($sql);

		if ($result == "0") {
			return false;
		} else {
			return true;
		}

	}

	private function Update_FrontDesk_BigSC_Report() {
		Db::startTrans();
		try {
			$SetKits_Area_Sql = "update FrontDesk_Report set realdate='" . $this->input['Date'] . "',scbv='" . $this->input['Total_BV'] . "',scpv='" . $this->input['Total_PV'] . "',scnaira='" . $this->input['Total_NR'] . "',CashMoney='" . $this->CashMoney . "',ReportType='SC',Oper_name='" . self::$realname . "',Tellarmoney='" . $this->TellarMoney . "',DebitMoney='" . $this->DebitMoney . "',Disc_Trsfer='" . $this->Disc_Trsfer . "',Cash_USD='" . $this->Cash_USD . "',BelongedSC='None',shopno='" . substr($this->input['SC_A'], -3, 3) . "',Area='" . $this->input['Region'] . "',Kits='" . $this->input['Kits'] . "',ReportStatus='" . $this->input['Kits'] . "' where groupid='" . $this->input['BigSCGroupID'] . "'";
			$status = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status)) {
				throw new Exception($SetKits_Area_Sql);
			}
			$SetKits_Area_Sql = "update FrontDesk_Report set TotalMoney=kits*" . self::$KitsValue . "+scnaira where groupid='" . $this->input['BigSCGroupID'] . "'";
			$status2 = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status2)) {
				throw new Exception($SetKits_Area_Sql);
			}
			$SetKits_Area_Sql = "update FrontDesk_Tellar set GROUPID='" . $this->input['BigSCGroupID'] .
			"' where groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "'";

			$status3 = $this->Exc_Sql($SetKits_Area_Sql); //合并TELLAR
			if (!is_numeric($status3)) {
				throw new Exception($SetKits_Area_Sql);
			}
			$SetKits_Area_Sql = "update FrontDesk_CASH set GROUPID='" . $this->input['BigSCGroupID'] .
			"' where groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "'";
			$status4 = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status4)) {
				throw new Exception($SetKits_Area_Sql);
			}

			$SetKits_Area_Sql = "update FrontDesk_Report set ReportType='BigSC',BelongedSC='" . $this->input['BigSCGroupID'] .
			"' where groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "'";
			$status4 = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status4)) {
				throw new Exception($SetKits_Area_Sql);
			}
			$SetKits_Area_Sql = "update frontdesk_tellar set groupid='" . $this->input['BigSCGroupID'] .
			"' where groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "'";
			$status4 = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status4)) {
				throw new Exception($SetKits_Area_Sql);
			}
			$SetKits_Area_Sql = "update frontdesk_cash set groupid='" . $this->input['BigSCGroupID'] .
			"' where groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "'";
			$status4 = $this->Exc_Sql($SetKits_Area_Sql);
			if (!is_numeric($status4)) {
				throw new Exception($SetKits_Area_Sql);
			}
			Db::commit();
			return suc("Create BigSC Successfully");
		} catch (\Exception $ee) {
			Db::rollback();
			return err(3000, $ee->getMessage());
		}
	}

	public function timer1_Tick() {
		try {
			$this->input['Date'] = $this->PerameterDate;
			if ($this->input['Result'] == "0" && $this->Proccess_Null($this->input['System_Total'] != "0")) {
				$this->input['ReportStatus'] = "OK";
			} else {
				$this->input['ReportStatus'] = "NO";
			}

			if (self::$realname != "") {
				$this->input["System_Total"] = $this->input["Kits"] * $this->input["UnitPrice"] + $this->ScNaira;
			} else {
				$this->input["System_Total"] = $this->ScNaira;

			}
			$this->Set_GrandTotal();
			$this->SetResult();
			$this->input['Cash(NR)'] = $this->CashMoney;
			$this->input['Bank_Deposit'] = $this->TellarMoney;
			if (strlen($this->input['SC_A']) > 10) {
				$this->input['SC_B_status'] = true;
			} else {
				$this->input['SC_B_status'] = false;
			}

			if (strlen($this->input['SC_B']) > 10) {
				$this->input['SC_C_status'] = true;
			} else {
				$this->input['SC_C_status'] = false;
			}

			if (strlen($this->input['SC_C']) > 10) {
				$this->input['SC_D_status'] = true;
			} else {
				$this->input['SC_D_status'] = false;
			}

			if (strlen($this->input['SC_D']) > 10) {
				$this->input['SC_E_status'] = true;
			} else {
				$this->input['SC_E_status'] = false;
			}

			if (strlen($this->input['SC_E']) > 10) {
				$this->input['SC_F_status'] = true;
			} else {
				$this->input['SC_F_status'] = false;
			}

			return suc($this->input);
		} catch (\Exception $ee) {
			$this->timer1 = false;
			$msg = $ee->getMessage();
			return err(9000, $msg);
			if (strpos("not set to", $msg) === false) {
				$this->timer1 = true;
			}
		}
	}

	private function Proccess_Null($str) {

		if ($str == "") {
			return "0";
		} else if ($str == "-") {
			return "-0";
		} else {
			return $str;
		}

	}

	private function Set_GrandTotal() {
		$this->input['Grand_Total'] = (int) $this->input['Cash(NR)'] + (int) $this->input['Bank_Deposit'] + (int) $this->input['D/Tsfer'] + (int) $this->input['Debit'] + (int) $this->input['Cash(usd)'];
	}

	private function SetResult() {
		$this->input['Result'] = $this->input['Grand_Total'] - $this->input['System_Total'];
	}

	private function Check_If_ValidSaleNO($gpid) {

		$sql = "select count(*) count from FrontDesk_Report where groupid ='" . $gpid . "'";
		$sql1 = "select count(*) count from frontdesk_report where belongedsc='" . $gpid . "'";
		$data = $this->query_Sql($sql);
		if (empty($data)) {
			$this->IfMerged = 0;
			return err(5000, "编号为" . $gpid . "单据已经进行过合并，请取消后合并！");
		}
		$data1 = $this->query_Sql($sql1);
		if ($data1[0]["count"] != 0) {
			$this->IfMerged = 1;
			return err(5000, "编号为" . $gpid . "单据已经进行过合并，请取消后合并！");
		}
		return true;
	}

	private function GeT_ReportToForm_ForBIGSC() {
		$this->Get_Result_Product_Info(); //得到所以产品信息
		$this->Get_SaleProduct_Info_ForBIGSC(); //得到销售的产品信息
		$status = $this->Get_Sale_Report();
		if ($status['stat'] === 0) {
			return $status;
		}
		$data["Get_Result_Mem_Info"] = $this->Get_Result_Mem_Info();
		$this->Set_Qty_0();

		$data["Get_ReportDetail_ForBIGSC"] = $this->Get_ReportDetail_ForBIGSC();
		$this->Proccess_Empty();
		$data["Set_TextBoxGet_ForBIGSC"] = $this->Set_TextBoxGet_ForBIGSC();
		return $data;
	}

	private function ShowTotal() {
		$this->input["Total_NR"] = $this->ScNaira;
		$this->input["Total_BV"] = $this->ScBv;
		$this->input["Total_PV"] = $this->ScPv;
		return true;
	}

	private function Set_BigSC_NewGroupID() {
		if (strlen($this->Check_If_Belong()) < 5) {
			$this->input['BigSCGroupID'] = date('YmdHis') . "-" . substr($this->input["SC_A"], -3, 3);
		} else {
			$this->input['BigSCGroupID'] = $this->Check_If_Belong();
		}
	}

	private function Get_Result_Product_Info() {
		$sql = "drop view Product_Table";
		$this->Exc_Sql($sql);
		$sql = "create view Product_Table as select top " . self::$ProductNumber . " Productno as Code,cname as Products,unit,convert(decimal(10,1),PV) AS 'BV(USD)',MEMO as 'BV/PV(%)',convert(decimal(10,1),RETAILPRICE) AS 'PV(USD)',str(memberprice) as 'Naira PRICE','0' as QTY" .
			"  from tb_product where memberprice>0 AND status='1' and productno<>'p10' and productno<>'m04' and productno<>'m10' and productno<>'m091' and productno<>'m09'and productno<>'m05' and productno<>'AJ01' and productno<>'Ajust3' order by unit,code";

		$this->Exc_Sql($sql);
	}

	private function Get_SaleProduct_Info_ForRegional() {

		$Delete_View = "drop view product_table1";
		$this->Exc_Sql($Delete_View);
		$Prod_Sql = "create view product_table1 as select productno as code,cname as Products,unit,productno,pv as BV,retailprice as PV,memberprice as Naira," .
		"Amount as QTY,[Total BV]=Amount*pv,[Total PV]=Amount*retailprice,[Total NR]=Amount*memberprice from (select productid,sum(amount) as amount from tb_sale_Entered_ByFrontDesk,tb_saledetail_ByFrontDesk " .
		"where tb_sale_Entered_ByFrontDesk.saleid=tb_saledetail_ByFrontDesk.saleid and Groupid in(select groupid from FrontDesk_Report where area='" . $this->input['Region'] . "') and realdate>='" . $this->input['From'] . "'and realdate<='" . $this->input['To'] . "' group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";

		$this->Exc_Sql($Prod_Sql);
	}

	private function Get_Sale_Report() {
		$Delete_View = "IF EXISTS(SELECT * FROM sysobjects WHERE name='Sale_Report') drop table Sale_Report";
		$this->Exc_Sql($Delete_View);
		$status = $this->CheckIfFrontDesk_reportprintOK($this->input["BigSCGroupID"]);
		if ($status['stat'] === 0) {
			return $status;
		}
		if (!$status) {
			$Prod_Sql = "delete from FrontDesk_reportprint where groupid='" . $this->StockRegionNo . "'";
			$this->Exc_Sql($Prod_Sql);

			$Prod_Sql = "insert FrontDesk_reportprint(Code,Products,unit,[BV(USD)],[BV/PV(%)],[PV(USD)],[Naira PRICE],QTY,[Total BV],[Total PV],[Total NR],GroupID,printtype) " . "select product_table.Code,product_table.Products,'个'as unit,product_table.[BV(USD)],product_table.[BV/PV(%)],product_table.[PV(USD)],product_table.[Naira PRICE],product_table1.QTY" .
			",convert(decimal(19,1),[Total BV]) as [Total BV],convert(decimal(19,1) ,[Total PV])as [Total PV],convert(decimal(19,0),[Total NR]) as [Total NR],'" . $this->StockRegionNo . "'as GroupID,'Region'as printtype  from product_table left join product_table1 on product_table.code=product_table1.code";
			$this->Exc_Sql($Prod_Sql);
		}
		$Prod_Sql = "select product_table.Code,product_table.Products,'个'as unit,product_table.[BV(USD)],product_table.[BV/PV(%)],product_table.[PV(USD)],product_table.[Naira PRICE],product_table1.QTY" .
			",convert(decimal(19,1),[Total BV]) as [Total BV],convert(decimal(19,1) ,[Total PV])as [Total PV],convert(decimal(19,0),[Total NR]) as [Total NR] into Sale_Report from product_table left join product_table1 on product_table.code=product_table1.code";
		mlog($Prod_Sql);
		$data = $this->Exc_Sql($Prod_Sql);
		return suc($data);
	}

	private function CheckIfFrontDesk_reportprintOK($GID) {
		$count = model("FrontDeskReportPrint")->where(["GroupID" => $GID])->count();
		if ($count != 0) {
			$sql = "";
			$printnaira = "0";
			$reportnaira = "0";

			$sql = "select sum([total nr]) as total from FrontDesk_reportprint where groupid='" . $GID . "'";
			$printnaira = $this->GetStringData($sql);
			if (empty($printnaira)) {
				return err(9000, 'CheckIfFrontDesk_reportprintOK数据出错');
			}
			$sql = "select sum(scnaira)  as scnaira from frontdesk_report where regionno='" . $GID . "'";
			mlog($GID);
			$reportnaira = $this->GetStringData($sql);
			if (empty($reportnaira)) {
				return false;
			}
			if ((($printnaira != $reportnaira && $this->CheckIfDateNewPrice($GID)) || $reportnaira == 0) && $this->toolStripTextBox1 != "Daily" && $this->toolStripTextBox1 != "Monthly") {
				return false;
			} else {
				return err(9000, '汇总出错');
			}

		} else {
			return false;
		}

	}

	private function CheckIfDateNewPrice($GID) {
		$sql = "select count(*) from frontdesk_report where groupid='" . $GID . "' and realdate>='2015-07-10'";
		if ($this->GetStringData($sql) == "0") {
			return false;
		} else {
			return true;
		}

	}

	private function GetRegionNO() {
		$sql = "select distinct regionno from frontdesk_report where isnull(regionno,'')<>'' and area='" . $this->input['Region'] . "' and realdate='" . $this->input['From'] . "'";
		$ds = $this->GetDSData($sql);
		if (count($ds) > 1) {
			return false;
		} else if (count($ds) == 1) {
			return $ds[0]["regionno"];
		} else {
			return $this->input['Region'] . date("yyyyMMddhhmmss");
		}

	}

	#region BIGSC

	private function Get_SaleProduct_Info_ForBIGSC() {
		Db::startTrans();
		try {
			$Delete_View = "drop view product_table1";
			$tatus = $this->Exc_Sql($Delete_View);
			if (!is_numeric($tatus)) {
				throw new \Exception($tatus);
			}
			$Prod_Sql = "create view product_table1 as select productno as code,cname as Products,unit,productno,pv as BV,retailprice as PV,memberprice as Naira," .
			"Amount as QTY,[Total BV]=Amount*pv,[Total PV]=Amount*retailprice,[Total NR]=Amount*memberprice from (select productid,sum(amount) as amount from tb_sale_Entered_ByFrontDesk,tb_saledetail_ByFrontDesk " .
			"where tb_sale_Entered_ByFrontDesk.saleid=tb_saledetail_ByFrontDesk.saleid and current_status<>'0' and  (groupid='"
			. $this->input['SC_A'] . "' or groupid='" . $this->input['SC_B'] . "' or groupid='" . $this->input['SC_C'] . "' or groupid='" . $this->input['SC_D'] . "' or groupid='" . $this->input['SC_E'] . "' or groupid='" . $this->input['SC_F'] . "' or groupid='" . $this->input['SC_G'] . "' or groupid='" . $this->input['SC_H'] . "') group by productid) temp_1,tb_product where tb_product.productid=temp_1.productid";

			$tatus = $this->Exc_Sql($Prod_Sql);
			if (!is_numeric($tatus)) {
				throw new \Exception($tatus);
			}
			$this->RemoveTeller($this->input['SC_A']);
			$this->RemoveTeller($this->input['SC_B']);
			$this->RemoveTeller($this->input['SC_C']);
			$this->RemoveTeller($this->input['SC_D']);
			$this->RemoveTeller($this->input['SC_E']);
			$this->RemoveTeller($this->input['SC_F']);
			$this->RemoveTeller($this->input['SC_G']);
			$tatus = $this->RemoveTeller($this->input['SC_H']);
			if (!is_numeric($tatus)) {
				throw new \Exception($tatus);
			}
			Db::commit();
		} catch (\Exception $exc) {
			Db::rollback();

			return err(9000, $exc->getMessage());
		}
	}

//从销售库取数据

	private function RemoveTeller($GroupID) {
		if (strlen($GroupID) > 4) {
			$sql = "delete from fd_tellerdetail where groupid='" . $GroupID . "'";
			$tatus = $this->Exc_Sql($sql);
			if (!is_numeric($tatus)) {
				throw new \Exception($tatus);
			}
			$sql = "update frontdesk_report set tellarMoney=0 where groupid='" . $GroupID . "'";
			$tatus = $this->Exc_Sql($sql);
			if (!is_numeric($tatus)) {
				throw new \Exception($tatus);
			}
			$this->ProcessChangeTeller(substr($GroupID, strlen($GroupID) - 3, 3));
			return 1;
		}
	}

	private function Get_Result_Mem_Info() {

		$sqlorder = "select a.Code,Products,unit,[BV(USD)],[BV/PV(%)],[PV(USD)],[Naira Price],Qty,[Total BV],[Total PV],[Total NR] from Sale_Report a,frontdesk_reportprintsort b where a.code=b.code order by codeid";
		return $this->query_Sql($sqlorder);
	}

	private function Set_Qty_0() {

	}

	private function Get_ReportDetail_ForBIGSC() {
		$CommandText = "select sum(Kits) as Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer," .
		"sum(cash_USD) as cash_USD,sum(scbv) as scbv,sum(scpv) as scpv,sum(scnaira) as scnaira from FrontDesk_Report where reporttype='SC' and groupid in(select groupid from tb_sale_entered_byFrontdesk where current_status<>'0') "
		. " and(FrontDesk_Report.groupid='" . $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "' )";
		mlog($CommandText);
		$data = $this->query_Sql($CommandText);

		$data = array_pop($data);
		$this->Kits = $this->Get_ReportKits_ForBIGSC();
		$this->CashMoney = $data["CashMoney"];
		$this->TellarMoney = $data["TellarMoney"];
		$this->DebitMoney = $data["debitmoney"];
		$this->Disc_Trsfer = $data["disc_trsfer"];
		$this->Cash_USD = $data["cash_USD"];
		$this->ScBv = $data["scbv"];
		$this->ScPv = $data["scpv"];
		$this->ScNaira = $data["scnaira"];
		return $data;
	}

	private function Proccess_Empty() {
		if ($this->Kits == "") {
			$this->Kits = "0";
		}

		if ($this->CashMoney == "") {
			$this->CashMoney = "0";
		}

		if ($this->TellarMoney == "") {
			$this->TellarMoney = "0";
		}

		if ($this->DebitMoney == "") {
			$this->DebitMoney = "0";
		}

		if ($this->Disc_Trsfer == "") {
			$this->Disc_Trsfer = "0";
		}

		if ($this->Cash_USD == "") {
			$this->Cash_USD = "0";
		}

		if ($this->ScBv == "") {
			$this->ScBv = "0";
		}

		if ($this->ScPv == "") {
			$this->ScPv = "0";
		}

		if ($this->ScNaira == "") {
			$this->ScNaira = "0";
		}

	}

	private function Set_TextBoxGet_ForBIGSC() {
		try {
			$this->input["Kits"] = $this->Kits;
			$this->input["CashMoney"] = $this->CashMoney;
			$this->input["TellarMoney"] = $this->TellarMoney;
			$this->input["DebitMoney"] = $this->DebitMoney;
			$this->input["Disc_Trsfer"] = $this->Disc_Trsfer;
			$this->input["Cash_USD"] = $this->Cash_USD;
			$this->input["S_Nylon"] = floor($this->ScNaira / 10000.0);
			$this->input["B_Nylon"] = floor($this->input["S_Nylon"] / 4);
			$this->input["Grand_Total"] = $this->input["Cash(NR)"] + $this->input["Bank_Deposit"] + $this->input["Debit"] + $this->input["D/Tsfer"] + $this->input["Cash(usd)"] * 150;
			$this->input["System_Total"] = $this->ScNaira + $this->input["Kits"] * $this->input["UnitPrice"];
			$this->input["Result"] = $this->input["Grand_Total"] - $this->input["$this->input"];
			$this->input["ScNaira"] = $this->Get_Area_ForBIGSC();
			$this->Get_Date_ForBIGSC();
		} catch (\Exception $e) {

		}
	}

	private function Get_Area_ForBIGSC() {

		$sql = "select distinct area from FrontDesk_Report where groupid in(select groupid from tb_sale_entered_byFrontdesk where current_status<>'0') "
		. " and(FrontDesk_Report.groupid='" . $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "' )";
		$data = $this->query_Sql($sql);
		$this->Area = $data[0]["area"];

		return $this->Area;
	}

	private function Get_Date_ForBIGSC() {
		$sql = "select distinct realdate from FrontDesk_Report where (FrontDesk_Report.groupid='"
		. $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "' )";
		$data = $this->query_Sql($sql);
		$this->PerameterDate = $data[0]["realdate"];
		$this->timer1_Tick();
		return $this->PerameterDate;
	}

	private function SetTextBoxEnable() {
		return true;
	}

	private function Insert_New_BigSC() {
		if (strlen($this->Check_If_Belong()) < 5) {

			$InsertSql = "insert FrontDesk_Report(GroupID) values('" . $this->input['BigSCGroupID'] . "') ";
			$this->Exc_Sql($InsertSql);
		}
		$InsertSql = "update FrontDesk_Report set ReportType='BigSC' where groupid='" . $this->input['BigSCGroupID'] . "'";
		$this->Exc_Sql($InsertSql);
		$InsertSql = "update FrontDesk_Report set BelongedSC='" . $this->input['BigSCGroupID'] . "' where (groupid='"
		. $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "' )";
		return $this->Exc_Sql($InsertSql);
	}

	private function Check_If_Belong() {
		$sql = "select BelongedSC from FrontDesk_Report where groupid ='"
		. $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "' ";
		$data = $this->query_Sql($sql);
		if (empty($data)) {
			return 0;
		}
		return $data[0]['BelongedSC'];
	}

	private function Get_ReportKits_ForBIGSC() {
		$sql = "select sum(Kits) as Kits from FrontDesk_Report where reporttype='SC'"
		. " and(FrontDesk_Report.groupid='" . $this->input['SC_A']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_B']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_C']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_D']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_E']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_F']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_G']
		. "' or FrontDesk_Report.groupid='" . $this->input['SC_H'] . "')";
		$data = $this->query_Sql($sql);
		return (int) $data[0]["Kits"];
	}

}
