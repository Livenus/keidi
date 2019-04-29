<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class UpdateRegionDate extends Common {

	public $newborrowok = 0;
	public $deductMark = 0;
	public $error = false;
	public $SaleType = "业绩销售";

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}

	public function UpdateRegionDate_Load() {

	}
	public function button1_Click() {
		if ($this->CheckIfDateValid() || $this->input['textBox2'] == "70599") {
			$data['search'] = $this->Search();
			$this->input['textBox2'] = "";
			$data['input'] = $this->input;
			return suc($data);
		} else {
			return err(9000, "The date you choosed is too long time from today,Pls Contact with Eric!");
		}

	}
	private function CheckIfDateValid() {
		return true;
	}
	private function Search() {

		$sql = "select * from frontdesk_report where area='" . $this->input['Region'] . "' and realdate>='" . $this->input['OldDate'] . "' and realdate<='" . $this->input['OldDate'] . "' order by shopno";
		$data = $this->query_Sql($sql);

		return $data;
	}
	public function button2_Click() {
		Db::startTrans();
		try
		{
			$data['UpdateTeller'] = $this->UpdateTeller();
			Db::commit();
			return $data['UpdateTeller'];
		} catch (\Exception $ee) {
			Db::rollback();
			return err(9000, $ee->getMessage());
		}

	}
	private function UpdateTeller() {
		$tellerno = 0;
		$updatesql = "update fd_tellerdetail set [date]='" . $this->input['NewDate'] . "'where groupid in(select groupid from frontdesk_report where area='" . $this->input['Region'] . "' and realdate>='" . $this->input['OldDate'] . "' and realdate<='" . $this->input['OldDate'] . "')";
		$status = $this->Exc_Sql($updatesql);
		if (is_numeric($status)) {
			return $this->UpdateSale();

		} else {
			throw new Exception("Error" . $updatesql);
		}

	}
	private function UpdateSale() {
		$updatesql = "update tb_sale_entered_byfrontdesk set realdate='" . $this->input['NewDate'] . "' where saleid in(select saleid from tb_sale_entered_byfrontdesk,frontdesk_report where frontdesk_report.groupid=tb_sale_entered_byfrontdesk.groupid and area='" . $this->input['Region'] . "' and tb_sale_entered_byfrontdesk.realdate>='" . $this->input['OldDate'] . "' and tb_sale_entered_byfrontdesk.realdate<='" . $this->input['OldDate'] . "')";
		$status = $this->Exc_Sql($updatesql);
		if (is_numeric($status)) {
			return $this->UpdateReport();

		} else {
			throw new Exception("Error" . $updatesql);
		}

	}
	private function UpdateReport() {

		$updatesql = "update frontdesk_report set realdate='" . $this->input['NewDate'] . "'where area='" . $this->input['Region'] . "' and realdate>='" . $this->input['OldDate'] . "' and realdate<='" . $this->input['OldDate'] . "'";
		$status = $this->Exc_Sql($updatesql);
		if (is_numeric($status)) {
			return $this->RecordOper($this->input['Region']);

		} else {
			throw new Exception("Error" . $updatesql);
		}

	}
	#region 转移区域店日期
	private function RecordOper($date) {
		$id = $this->GetOperID();
		if (!is_numeric($id)) {
			throw new Exception("Error" . $id['errmsg']);
		}
		$updatesql = "insert changedatelog(operid,operdate,operobject,fromdate,todate,oper) values(" . $id . ",'" . date('Y-m-d') . "','" . $date . "','" . $this->input['OldDate'] . "','" . $this->input['NewDate'] . "','" . self::$realname . "') ";

		$status = $this->Exc_Sql($updatesql);
		if (is_numeric($status)) {
			return suc("更新成功！");
		} else {
			throw new Exception("Error" . $updatesql);
		}

	}
	private function GetOperID() {
		$id = "1000";
		try
		{
			$sql = "select isnull(max(operid),0)+1 as operid from changedatelog";
			$id = $this->GetStringData($sql);
		} catch (\Exception $ee) {
			return err(3000, $ee->getMessage());
		}
		return $id;
	}
	#endregion

	public function button3_Click() {
		try
		{
			if (strlen($this->input['textBox1']) > 10) {
				$data['UpdateTellerDateForSC'] = $this->UpdateTellerDateForSC();
				$this->input['textBox1'] = "";
				$data['button1_Click'] = $this->button1_Click();
				return suc($data);
			} else {
				return err(9000, "没有专卖店要更新日期");
			}

		} catch (\Exception $ee) {return err(9000, $ee->getMessage());}

	}
	private function UpdateTellerDateForSC() {
		$sql = "update fd_tellerdetail set [date]='" . $this->input['NewDate'] . "'where (groupid='" . $this->input['textBox1'] . "'or groupid in(select groupid from frontdesk_report where belongedsc='" . $this->input['textBox1'] . "')) ";
		$status = $this->Exc_Sql($sql);
		if (is_numeric($status)) {
			return $this->UpdateSaleForSC();

		} else {
			throw new \Exception("Error" . $sql);
		}

	}
	private function UpdateSaleForSC() {
		$sql = "update tb_sale_entered_byfrontdesk set realdate='" . $this->input['NewDate'] . "' where (groupid='" . $this->input['textBox1'] . "'or groupid in(select groupid from frontdesk_report where belongedsc='" . $this->input['textBox1'] . "'))";
		$status = $this->Exc_Sql($sql);
		if (is_numeric($status)) {
			return $this->UpdateReportForSC();

		} else {
			throw new \Exception("Error" . $sql);
		}

	}
	private function UpdateReportForSC() {
		$updatesql = "update frontdesk_report set realdate='" . $this->input['NewDate'] . "'where (groupid='" . $this->input['textBox1'] . "'or groupid in(select groupid from frontdesk_report where belongedsc='" . $this->input['textBox1'] . "')) and realdate>='" . $this->input['OldDate'] . "' and realdate<='" . $this->input['OldDate'] . "'";
		$status = $this->Exc_Sql($updatesql);
		if (is_numeric($status)) {
			return $this->RecordOper($this->input['textBox1']);

		} else {
			throw new \Exception("Error" . $updatesql);
		}

	}
}
