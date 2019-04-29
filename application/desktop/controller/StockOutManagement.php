<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class StockOutManagement extends Common {

	private $products; //当前页码
	private $LoadStockOutInfo;
	private $SaleType = "";
	private $SendOutGroup = "";
	private $SaleDate = "";
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}

	public function load() {
		$data['status'] = $this->status;
		$data['use_status'] = $this->use_status;
		$data['lock_status'] = $this->lock_status;
		$data['effective_date'] = $this->effective_date;
		return suc($data);
	}
	/**
	 * @title 出库管理--SearchSC
	 * @type interface
	 * @login 1
	 * @param NO 汇总编号
	 * @param Condition
	 */
	public function SearchSC() {
		$rule = [
			"NO" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		$GIDType = "SC";
		if ($this->CheckIfFromRegionTogether()) {
			$data['check'] = "该编号属于区域店提货，所属区域店编号：" . $this->GetStringData("select regiontogether from stockout where groupid='" . $this->input['NO'] . "'");
			$this->input['Region'] = $this->GetStringData("select regiontogether from stockout where groupid='" . $this->input['NO'] . "'"); //是区域店提货,返回区域店编号
		} else {
			$this->LoadUnStockOut();
			$data['LoadStockOutInfo'] = $this->LoadStockOutInfo;

		}
		$data['input'] = $this->input;
		return suc($data);
	}
	//检查是否属于区域店提货
	private function CheckIfFromRegionTogether() {
		$sql = "select isnull(regiontogether,'') from stockout where groupid='" . $this->input['NO'] . "'";
		$regiontogether = $this->GetStringData($sql);
		if ($regiontogether == "") {
			return false;
		} else {
			return true;
		}
	}
	private function LoadUnStockOut() {
		$SaleDate = $this->GetSaleDate("groupid", $this->input['NO']);
		$data['LoadStockOutInfo'] = $this->LoadStockOutInfo();
		$this->LoadStockOutInfo = $data['LoadStockOutInfo'];
		$this->LoadTheAmountStockHas();
		$data['LoadStockOutInfo'] = $this->LoadStockOutInfo;
		$this->TogetherSeperatedProduct();
		$GroupID = $this->input['NO'];
		$PrintTitle = $this->input['ScTitle'];
		try
		{
			$this->input['GID'] = $this->input['NO'];
			$this->input['SC/Region'] = $this->GetShopbyGID($this->input['NO']);
			$SaleType = $this->GetStringData("select saletype from stockout where groupid='" . $this->input['NO'] . "'");
			$SendOutGroup = "SC";
			$this->CheckStatus($this->input['NO']);
			//SetRegionOrSCNo();
		} catch (\Exception $ee) {}
	}
	private function GetSaleDate($GroupID_Region, $reportno) {
		$sql = "select distinct saledate from stockout where " . $GroupID_Region . "='" . $reportno . "'";
		$saledate = $this->GetStringData($sql);
		return $saledate;
	}
	//查询详细信息
	private function LoadStockOutInfo() {

		$sql = "select ProductNO,[Name] as PName,a.Amount as Sale,a.Amount as StockOut,''as BackOrder from (select Productid,Amount from stockout,stockoutdetail where status='1'and isnull(regiontogether,'')='' and stockoutdetail.stockoutid=stockout.stockoutid"
		. " and groupid='" . $this->input['NO'] . "') a,StockProduct_Material where a.productid=StockProduct_Material.productid order by productno";
		return $this->query_Sql($sql);
	}
	private function LoadTheAmountStockHas() {
		$stock = $this->GetStockName(); //此处需要用到$this->input['Condition']
		if (empty($stock)) {
			return false;
		}
		$sql = "select productno,amount from " . $stock . " a,stockproduct_material b where a.productid=b.productid";
		$ds = $this->query_Sql($sql);
		foreach ($ds as $v) {
			$vv = array_values($v);
			$pno = "";
			$amount = "";
			$pno = $vv[0];
			$amount = $vv[1];
			foreach ($this->LoadStockOutInfo as $k => $vvv) {
				$vvvv = array_values($vvv);
				if ($vvvv[0] == $pno) {
					if ($amount < $vvvv[3]) {
						$this->LoadStockOutInfo[$k]["BackOrder"] = $vvv["Sale"] - $amount;
						$this->LoadStockOutInfo[$k]["StockOut"] = $amount;

					}
				}
			}
		}
	}
	private function GetStockName() {
		$sql = "select stockName from stocklist where StockShow='" . $this->input['Condition'] . "'";
		return $this->GetStringData($sql);

	}
	private function TogetherSeperatedProduct() {
		foreach ($this->LoadStockOutInfo as $k => $v) {
			$PNO = "";
			$vv = array_values($v);
			$PNO = $vv[0];
			if ($PNO == "M041") {
				$this->LoadStockOutInfo[$k]["ProductNO"] = "M04";
				$this->LoadStockOutInfo[$k]["PName"] = "BLOOD CIRULATORY";
				$this->LoadStockOutInfo[$k]["Sale"] = $vv[2] / 2;
			}
			if ($PNO == "M051") {
				$this->LoadStockOutInfo[$k]["ProductNO"] = "M05";
				$this->LoadStockOutInfo[$k]["PName"] = "CAR&HOME USE MASSAGE CUSHION";
				$this->LoadStockOutInfo[$k]["Sale"] = $vv[2] / 3;
			}
			if ($PNO == "M092") {
				$this->LoadStockOutInfo[$k]["ProductNO"] = "M09";
				$this->LoadStockOutInfo[$k]["PName"] = "Massage Chair";
				$this->LoadStockOutInfo[$k]["Sale"] = $vv[2] / 3;
			}
		}
	}
	private function GetShopbyGID($GID) {
		$sql = "select shopno from stockout where groupid='" . $GID . "' or regiontogether='" . $GID . "'";
		$shop = $this->GetStringData($sql);
		return $shop;
	}
	private function CheckStatus($GID) {
		$sql = "select min(status) from stockout where regiontogether='" . $GID . "' or groupid='" . $GID . "'";
		if (trim($this->GetStringData($sql)) == "2") {
			$this->SetButtonAfterStockOut();
		} else if (trim($this->GetStringData($sql)) == "1") {
			$this->SetButtonBeforeStockOut();
		}

	}
	private function SetButtonAfterStockOut() {
		$this->input['button5.Enabled'] = false;
		$this->input['button6.Enabled'] = true;
		$this->input['button5.text'] = "AlreadyOut";
		$this->input['button6.text'] = "CancelStockOut";
	}
	private function SetButtonBeforeStockOut() {
		$this->input['button5.Enabled'] = true;
		$this->input['button6.Enabled'] = false;
		$this->input['button5.text'] = "StockOut";
		$this->input['button6.text'] = "AlreadyCanceled";
	}
	/**
	 * @title 出库管理 --SearchRegion
	 * @type interface
	 * @login 1
	 * @param Condition
	 * @param Region
	 */
	public function SearchRegion() {
		$rule = [
			"Region" => "require",
			"Condition" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		try {

			$GIDType = "REGION";
			if ($this->CheckIfStockOuted($this->input['Region'])) {
				$sql = "select ProductNo,name as Pname,amount as Sale,realamount as StockOut,backorderamount as BackOrder "
				. " from stockoutdetailreal a,stockproduct_Material b where groupid='" . $this->input['Region'] . "' and a.productid=b.productid order by type desc,productno";
				$this->LoadStockOutInfo = $this->query_sql($sql);
				$this->CheckStatus($this->input['Region']);
				$sql = "select top 1 outdate from stockout where groupid='" . $this->input['Region'] . "' or regiontogether='" . $this->input['Region'] . "'";
				$dateTimePicker1 = $this->GetStringData($sql);
			} else {
				if ($this->CheckIfAllRegionSCisTheSameStatus()) {
					$SaleDate = $this->GetSaleDate("RegionTogether", $this->input['Region']);
					$data["LoadRegionStockOutInfo"] = $this->LoadRegionStockOutInfo();
					$this->LoadStockOutInfo = $data["LoadRegionStockOutInfo"];
					$this->DeductRegionKits($this->input['Region']);
					$this->TogetherSeperatedProduct();
					$GroupID = $this->input['Region'];
					$PrintTitle = $this->input['ScTitle'];
					$this->LoadTheAmountStockHas();
					$this->CheckStatus($this->input['Region']);
					// try
					// {
					$this->input['ScTitle'] = $this->GetShopbyGID($this->input['Region']);
					$this->input['SC/Region'] = $this->input['Region'];
					$SaleType = $this->GetStringData("select top 1 saletype from stockout where regiontogether='" . $this->input['Region'] . "'");
					$SendOutGroup = "Region";

					// } catch (\Exception $ee) {}
				} else {
					return err(9000, "区域下的部分专卖店状态不一致！");
				}

			}
			$data["LoadRegionStockOutInfo"] = $this->LoadStockOutInfo;
			$data["input"] = $this->input;
			return suc($data);
		} catch (Exception $eee) {
			return err(9000, $eee->getMessage());

		}
	}
	private function CheckIfStockOuted($groupid) {
		$sql = "select count(*) from stockoutdetailreal where groupid='" . $groupid . "'";
		if (trim($this->GetStringData($sql)) == "0") {
			return false;
		} else {
			return true;
		}

	}
	private function CheckIfAllRegionSCisTheSameStatus() {
		$sql = "select distinct status from stockout where regiontogether='" . $this->input['Region'] . "'";
		if (trim($this->GetStringData($sql) > 1)) {
			return false;
		} else {
			return true;
		}

	}
	private function LoadRegionStockOutInfo() {
		$sql = "select ProductNO,[name] as PName,a.Amount as Sale,a.Amount as StockOut,''as BackOrder from "
		. "(select productid,sum(amount)as Amount from(select Productid,sum(Amount) as Amount from stockout,stockoutdetail where  stockoutdetail.stockoutid=stockout.stockoutid and groupid in(select groupid from stockout where  regiontogether='" . $this->input['Region'] . "') group by productid"
		. " union all select productid,sum(amount) as amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where deductgroupid='" . $this->input['Region'] . "' and (select count(*) from stockout where regiontogether='" . $this->input['Region'] . "')>0) group by productid) b group by productid) "
			. "a,StockProduct_Material where a.productid=StockProduct_Material.productid order by productno";
		return $this->query_Sql($sql);
	}
	private function DeductRegionKits($RegionNo) {
		$sql = "select top 1 isnull(deductborrowkits,0) from frontdesk_report where regionno='" . $RegionNo . "'";
		$kits = $this->GetStringData($sql);
		if ($kits > 0) {
			foreach ($this->LoadStockOutInfo as $k => $v) {
				$vv = array_values($v);
				$pno = $vv[0];
				if ("K01E" == $pno || "K02F" == $pno || "K04" == $pno || "K05" == $pno || "KITDVD" == $pno) {
					$this->LoadStockOutInfo[$k]["Sale"] = $vv[2] - $kits;
					$this->LoadStockOutInfo[$k]["StockOut"] = $vv[2];
				}

			}
		}

	}
	/**
	 * @title 出库管理--SearchAll
	 * @type interface
	 * @login 1
	 * @param F 查询开始日期
	 * @param  T 查询结束日期
	 * @return data
	 */
	public function SearchAll() {
		Db::startTrans();
		try {
			$this->AddSaleTypeID();
			$list = $this->SearchAlldata();
			$data['count'] = count($list);
			$data['list'] = $list;
			return suc($data);
		} catch (Exception $e) {
			Db::rollback();
			exception($e->getMessage());
		}
	}
	//出库管理--SearchAll 调用
	private function SearchAlldata() {
		$sql = "select GroupID,ShopNO,typeName_e as SaleType, SaleDate,a.Status,regiontogether from stockout a,stocktype b where a.saletypeid=b.stocktypeid and a.status>='1' and  saledate>='" . $this->input['F'] . "' and saledate<='" . $this->input['T'] . "'";
		$data = $this->query_Sql($sql);
		return $data;
	}
	/**
	 * @title 出库管理--Search
	 * @type interface
	 * @login 1
	 * @param Category
	 * @param F 查询开始日期
	 * @param T 查询结束日期
	 * @param Condition_no 左边的汇总编号NO
	 * @param SC_Distributor
	 * @return data
	 */
	public function SearchScStockOut() {
		$rule = [
			"Category" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		Db::startTrans();
		try {
			$this->AddSaleTypeID();
			return $this->SearchScStockOutdata();
		} catch (Exception $e) {
			Db::rollback();
			exception($e->getMessage());
		}
	}
	//出库管理--Search调用
	private function SearchScStockOutdata() {
		if ($this->input['Condition_no'] != "") {
			$sql = "select GroupID,ShopNO,typeName_e as SaleType,SaleDate,a.Status,regiontogether from stockout a,stocktype b where a.saletypeid=b.stocktypeid and a.status>='1' and groupid like'%" . $this->input['Condition_no'] . "%' ";
		} else {
			if ($this->input['SC_Distributor'] != "") {
				$sql = "select GroupID,ShopNO,typeName_e as SaleType,SaleDate,a.Status,regiontogether from stockout a,stocktype b where a.saletypeid=b.stocktypeid and a.status>='1' and shopno='" . $this->input['SC_Distributor'] . "' and  saledate>='" . $this->input['F'] . "' and saledate<='" . $this->input['T'] . "' and saletypeID ='" . $this->GetSaleTypeID() . "'";
			} else {
				$sql = "select GroupID,ShopNO,typeName_e as SaleType,SaleDate,a.Status,regiontogether from stockout a,stocktype b where a.saletypeid=b.stocktypeid and a.status>='1' and  saledate>='" . $this->input['F'] . "' and saledate<='" . $this->input['T'] . "' and saletypeID ='" . $this->GetSaleTypeID() . "'";
			}

		}
		$data = $this->query_Sql($sql);
		return suc($data);
	}
	//从表stocktype中获取stockTypeID
	private function GetSaleTypeID() {
		$Sql = "select stockTypeID from stocktype where TypeName_e='" . $this->input['Category'] . "'";
		$TypeID = $this->GetStringData($Sql);
		return $TypeID;

	}
	/**
	 * @title 出库管理--点击左边单条信息
	 * @type interface
	 * @login 1
	 * @param cell json字符串
	 * @param Condition
	 * @return data
	 */
	public function GridView1_CellClick() {
		$cell = json_decode($this->input['cell'], true);
		$cell_v = array_values($cell);
		if (empty($cell)) {
			return err(9000, "cell不能为空");
		}
		if (strlen($cell_v[5]) > 5) {
			$this->input['NO'] = "";
			$this->input['Region'] = $cell['regiontogether'];
		} else {
			$this->input['Region'] = "";
			$this->input['NO'] = $cell['GroupID'];
		}
		if ($cell_v[4] == 2) {
			$this->LoadStockOutedInfo();
			$this->CheckStatus($this->input['NO']);

		} else {
			$this->LoadUnStockOut();
		}
		$data['LoadStockOutInfo'] = $this->LoadStockOutInfo;
		$data["inpput"] = $this->input;
		return suc($data);
	}
	private function LoadStockOutedInfo() {
		if ($this->input['NO'] != "") {
			$sql = "select ProductNO,[name] as PName,a.Amount as Sale,a.realamount as StockOut,a.backorderamount as BackOrder from (select productid,amount,realamount,backorderamount from stockoutdetailreal where groupid='" . $this->input['NO'] . "') a,StockProduct_Material where a.productid=StockProduct_Material.productid order by productno";
		} else {
			$sql = "select ProductNO,[name] as PName,a.Amount as Sale,a.realamount as StockOut,a.backorderamount as BackOrder from (select productid,amount,realamount,backorderamount from stockoutdetailreal where groupid='" . $this->input['Region'] . "') a,StockProduct_Material where a.productid=StockProduct_Material.productid order by productno";
		}

		$this->LoadStockOutInfo = $this->query_Sql($sql);
	}
	/**
	 * @title 出库管理--StockOut
	 * @type interface
	 * @login 1
	 * @param Condition
	 * @param StockOutDate 出库日期
	 * @param cell
	 * @param SC/Region
	 * @param GID
	 * @param NO
	 * @param Region
	 */
	public function StockOut() {
		$rule = [
			"StockOutDate" => "require",
			"Condition" => "require",
			"SC/Region" => "require",
			"cell" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		if ($this->input['GID'] == "") {
			if ($this->input['NO'] == "") {
				$this->input['GID'] = $this->input['Region'];
			} else if ($this->input['Region'] == "") {
				$this->input['GID'] = $this->input['NO'];
			} else {
				return err(9000, "No GroupID");
			}

		}
		$bool = $this->CheckIfStockClose($this->input['StockOutDate'], $this->GetStockName());
		if ($bool) {
			return err(9000, "The date you operate is already completed!");
		}

		$this->GridView1_CellClick();
		if (count($this->LoadStockOutInfo) < 1) {
			return err(9000, "There is no sale data to stockout!");
		} else if ($this->CheckIfDeductBorrow($this->input['NO'])) {
			return err(9000, "The Sale belongs to New borrow for returning old borrow!");
		}

		if ($this->input['SC/Region'] == "000" && !preg_match("/^(k|K)(n|N)\d{6}$|^(k|K)(g|G)\d{6}$/", $this->input['RetailKEDICard'])) {
			return err(9000, "零售汇总需要录入购货人的卡号！");
		}

		if ($this->CheckIfAllStockOutLessthanKedistock()) //检查库存余额
		{
			try {
				if ($this->InsertIntoBackOrder()) //进backorder
				{
					if ($this->InsertStockOutDetailReal()) {
						if ($this->SetStockOutSucceedMark()) {
							if ($this->InsertKediStock()) {
								$data["msg"] = "StockOut successfully!";
								return suc($data);
							} else {
								$sql = "";
								$sql = "delete from stockoutdetailreal where groupid='" . $this->input['GID'] . "'";
								$this->Exc_Sql($sql);
								$sql = "  delete from kedi_backorder where groupid='" . $this->input['GID'] . "'";
								$this->Exc_Sql($sql);
								$sql = "  update stockout set status='1' where groupid='" . $this->input['GID'] . "' or regiontogether='" . $this->input['GID'] . "'";
								$this->Exc_Sql($sql);
							}
						} else {

							if ($this->Exc_Sql("delete from kedi_backorder where groupid='" . $this->input['GID'] . "' ") < -1) {
								exception("回滚清楚之前进入BackOrder的数据失败！");
							} else {
								exception("实际发货数量保存失败,已经回滚！");
							}

						}
					}

				} else {
					exception("backorder插入数据失败！");
				}

			} catch (Exception $exc) {
				return err(9000, $exc->getMessage());
			}

		}

	}
	private function CheckIfAllStockOutLessthanKedistock() {
		$right = $this->GridView1_CellClick();
		$right_data = $this->LoadStockOutInfo;
		$status = true;
		if (empty($this->GetStockName())) {
			return $status;
		}
		$sql = "select productno,amount from " . $this->GetStockName() . " a,stockproduct_material b where a.productid=b.productid";
		$ds = $this->query_Sql($sql);
		foreach ($ds as $v) {
			$vv = array_values($v);
			$pno = "";
			$amount = "";
			$pno = $vv[0];
			$amount = $vv[1];
			foreach ($right_data as $vv) {
				$vvv = array_values($vv);
				if ($vvv[0] == $pno) {
					if ($amount < $vvv[3]) {
						$status = false;
						$this->errmsg = "The amount of Product " . $vvv[0] . ":" . $vvv[1] . " in stock is only:" . $amount;
						break;

					}
				}
			}
			if (!$status) {
				break;
			}

		}
		return $status;
	}
	//region 插入数据进入backorder
	private function InsertIntoBackOrder() {
		$status = true;
		if ($this->GetStringData("select count(*) from kedi_backorder where groupid='" . $this->input['GID'] . "'") == "0") {
			foreach ($this->LoadStockOutInfo as $v) {
				$vv = array_values($v);
				if ($vv[4] > 0) {
					if ($this->GotoBackOrder($vv) < 0) //出现错误，进行回滚，删除之前进入的数据
					{
						$status = false;
						if ($this->Exc_Sql("delete from kedi_backorder where groupid='" . $this->input['GID'] . "'") < 0) {
							exception("backorder插入错误，回滚失败！");
						}

						break;

					}
				}

			}
			//Exc_Sql("set IDENTITY_INSERT kedi_backorder off");
		} else {
			exception("该数据已经进入BackOrder数据库，联系Eric！");
			$status = false;
		}
		return $status;
	}
	private function GotoBackOrder($row) {
		$process = 0;
		$sql = "";
		$PID = "";
		$PID = $this->GetProductID($row[0]);
		if ($this->ProcessEmptyPID($PID) == "0") {
			$process = -1;
			exception("编号为" . $row[0] . "产品ID获取失败");
		} else {
			if ($this->input['GID'] != "") {
				$sql = "insert kedi_backorder([date],[own whom],products,qty,status,[id],productid,groupid,oper,lasteditdate,retaildistributor,[collect date],fromtype) "
				. "values('" . $this->input['StockOutDate'] . "','" . $this->input['SC/Region'] . "','" . str_ireplace("''", "'", $row[1]) . "'," .
				$row[4] . ",'0'," . $this->GetNewBackOrderID() . "," . $PID . ",'" . $this->input['GID'] . "','" . self::$realname . "','" . $this->GetDate() . "','" . $this->input['RetailKEDICard'] . "','1900-01-01','" . $this->SaleType . "')";

			} else {exception("GroupID为空:");}
			if ($this->Exc_Sql($sql) < 0) {
				exception("进backorder系统失败！sql:" . $sql);
				$process = -1;
			}
		}
		return $process;
	}
	private function ProcessEmptyPID($NO) {
		if (trim($NO) == "") {
			return "0";
		} else {
			return $NO;
		}

	}
	//将实际发货数量插入该数据库，每次实际发货数量，发货方式包括按专卖店或者区域店发货两种方式
	private function InsertStockOutDetailReal() {
		$result = true;
		foreach ($this->LoadStockOutInfo as $v) {
			$vv = array_values($v);
			$PNO = "";
			$amount = "0";
			$realamount = "";
			$backorderamount = "";
			$PId = "";
			$PNO = $vv[0];
			$amount = $vv[2];
			$realamount = $vv[3];
			$backorderamount = $vv[4];
			$PId = $this->GetProductID($PNO);
			if ($this->ProcessEmptyPID($PId) == "0") {
				exception("产品列表里没有该产品：" . $PNO);
				$result = false;
				if ($this->Exc_Sql("delete from stockoutdetailreal where groupid='" . $this->input['GID'] . "'") < 0) {
					exception("error,rollback error");
				}

				break;
			} else {
				$sql = "insert stockoutdetailreal(detailid,groupid,productid,amount,realamount,backorderamount,sendoutgroup,memo,saledate)" .
				" values('" . $this->GetNewRealDetailID() . "','" . $this->input['GID'] . "','" . $PId . "','" . $amount . "','" . $realamount . "','" . $backorderamount . "','" . $this->SendOutGroup . "','" . $this->SaleType . "','" . $this->SaleDate . "')";
				if ($this->Exc_Sql($sql) < 1) {
					if ($this->Exc_Sql("delete from stockoutdetailreal where groupid='" . $this->input['GID'] . "'") < 0) {
						exception("SQL:" . $sql . "执行失败后回滚失败！");
					}

					$result = false;
					break;
				}
			}
		}
		return $result;

	}
	private function GetProductID($PNO) {
		$sql = "select productid from StockProduct_Material where productno='" . $PNO . "'";
		$productID = $this->GetStringData($sql);
		return $productID;
	}
	private function GetNewRealDetailID() {
		$sql = "select isnull(max(detailid),0)+1 from stockoutdetailreal ";
		$detailid = $this->GetStringData($sql);
		return $detailid;

	}
	private function SetStockOutSucceedMark() {
		$status = false;
		$sql = "";
		$sql = "update stockout set outdate='" . $this->input['StockOutDate'] . "',lastedittime='" . $this->GetDate() . "',outperson='" . self::$realname . "', status='2',OutStockName='" . $this->GetStockName() . "',OutStockNameID=" . $this->GetOutStockNameID() . " where groupid='" . $this->input['GID'] . "'or regiontogether='" . $this->input['GID'] . "'";
		if ($this->Exc_Sql($sql) < 1) {
			exception("库存发货后标志位设置失败！");
		} else {
			$status = true;
		}
		return $status;
	}
	private function GetOutStockNameID() {
		$sql = "select stockid from stocklist where stockshow='" . $this->input['Condition'] . "'";
		$outnameID = $this->GetStringData($sql);
		return $outnameID;
	}
	//region 改变公司库存的函数
	private function InsertKediStock() {
		$result = true;
		$InsertSql = "";
		$InsertSql = "update a set a.Outamount=isnull(a.Outamount,0)+b.realamount from " . $this->GetStockName() . " a,stockOutdetailreal b where a.productid=b.productid and b.groupid='" . $this->input['GID'] . "' and a.productid in"
		. " (select productid from stockOutdetailreal where groupid='" . $this->input['GID'] . "' and '2'=(select min(status) from stockout where  groupid='" . $this->input['GID'] . "' or regiontogether='" . $this->input['GID'] . "'))";
		if ($this->Exc_Sql($InsertSql) < 1) {
			exception("审核入库失败,即将回滚系统,请重新审核！Unable to Check,Pls contact with Eric!");
			$result = false;
		} else {
			$sql = "";
			$sql = "update " . $this->GetStockName() . " set amount=isnull(inamount,0)-isnull(outamount,0) ";
			if ($this->Exc_Sql($sql) < 1) {
				exception("Error:" . $sql);
				$result = false;
			}
		}
		return $result;
	}
	private function CheckIfDeductBorrow($GID) {
		$sql = "select count(*) from tb_saleforBorrowReturn where groupid='" . $GID . "' and DeductName='业绩还货'";
		if ($this->GetStringData($sql) == "0") {
			return false;
		} else {
			return true;
		}

	}
	/**
	 * @title 出库管理--CancelStockOut
	 * @type interface
	 * @login 1
	 * @param StockOutDate
	 * @param NO
	 * @param Region
	 * @param Condition
	 */
	public function CancelStockOut() {
		$rule = [
			"StockOutDate" => "require",
			"Condition" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		if ($this->input['NO'] == '' && $this->input['Region'] == '') {
			return err(9000, "汇总编号NO或者Region必选填一个!");
		}
		$bool = $this->CheckIfStockClose($this->input['StockOutDate'], $this->GetStockName());
		if ($bool) {
			return err(9000, "The date you operate is already completed!");
		} else {
			if ($this->input['NO'] != "") {
				if ($this->Cancel($this->input['NO'])) {
					$data["msg"] = "Cancelled Successfully!";
					$this->SetButtonBeforeStockOut();
				}
			} else {

				if ($this->Cancel($this->input['Region'])) {
					$data["msg"] = "Cancelled Successfully!";
					$this->SetButtonBeforeStockOut();
				}
			}
			return suc($data);
		}
	}
	private function Cancel($GID) {
		$result = true;
		if ($this->RomoveFromKediStock($GID)) {
			$sql = "delete from stockoutdetailreal where groupid='" . $GID . "'";
			if ($this->Exc_Sql($sql) > 0) {
				$sql = "  delete from kedi_backorder where groupid='" . $GID . "'";
				if ($this->Exc_Sql($sql) > -1) {
					$sql = "  update stockout set status='1' where groupid='" . $GID . "' or regiontogether='" . $GID . "'";
					if ($this->Exc_Sql($sql) > 0) {
						$result = true;
					}

				}
			}
		} else {
			$result = false;
		}

		return $result;
	}
	private function RomoveFromKediStock($GID) {
		$result = true;

		$InsertSql = "update a set a.Outamount=isnull(a.Outamount,0)-b.realamount from " . $this->GetStockName() . " a,stockOutdetailreal b where a.productid=b.productid and b.groupid='" . $GID . "' and a.productid in"
			. " (select productid from stockOutdetailreal where groupid='" . $GID . "' and '2'=(select min(status) from stockout where  groupid='" . $GID . "' or regiontogether='" . $GID . "'))";

		if ($this->Exc_Sql($InsertSql) < 1) {
			exception("撤销失败,即将回滚系统,请重新审核！Unable to Cancel,Pls contact with Eric!");
			$result = false;
		} else {

			$sql = "update " . $this->GetStockName() . " set amount=isnull(inamount,0)-isnull(outamount,0) ";
			if ($this->Exc_Sql($sql) < 1) {
				exception("Error:" . $sql);
				$result = false;
			}
		}
		return $result;
	}
	private function GetNewBackOrderID() {
		$BackId = "0";
		$sql = "select isnull(max([id]),0)+1 from kedi_backorder";
		$BackId = $this->GetStringData($sql);
		return $BackId;
	}
	/**
	 * @title 出库管理--SearchStockOut
	 * @type interface
	 * @login 1
	 * @param From 开始时间
	 * @param To 结束时间
	 * @param status (1代表按钮SearchUnStockOut,2代表按钮SearchStockOut)
	 * @return data
	 */
	public function SearchStockOut() {
		$rule = [
			"From" => "require",
			"To" => "require",
			"status" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		$sql = "select distinct regiontogether from stockout where regiontogether is not null and status='" . $this->input['status'] . "' and saledate>='" . $this->input['From'] . "' and saledate<='" . $this->input['To'] . "' order by regiontogether";
		$data = $this->query_Sql($sql);
		return suc($data);
	}

}
