<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title  查询入库
 * @type  menu
 * @login 1
 */
class InStockSearch extends Common {
	public $ReportNO = "";
	public $TransferValue = "";
	//初始化方法
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}
	/**
	 * @title  查询入库in
	 * @type  interface
	 * @login 1
	 * @param InStock  仓库名称
	 * @param From  查询开始日期
	 * @param From  查询结束日期
	 * @param Ok  是否勾选
	 * @param selected  筛选条件
	 * @param M_Box 详细筛选条件
	 * @return data  入库详细信息列表  array
	 */
	public function SearchOutStockReport() {
		$sql = "";
		$sql1 = "";
		$fromplace = "";
		$date1 = "";
		$date2 = "";
		$stockname = "";
		$stockshow = "";
		$instocknameid;
		$fromplace = $this->input['selected'];
		$stockshow = $this->input['InStock'];
		$instocknameid = $this->GetStringData("select stockid from stocklist where stockshow='" . $stockshow . "'");
		$stockname = $this->GetStringData("select stockname from stocklist where stockshow='" . $stockshow . "'");
		$date1 = $this->input['From'];
		$date2 = $this->input['To'];
		if ($this->input['Ok']) {

			$sql = "select GroupID,fromcountry as 国家,fromplace as 来源地,stocktype as 类型,overflowfromgroupid as 报溢所属,Indate as 入库日期,insertperson as 入库员,Stockin.memo as 备注Memo from stockin where status='1' " . $this->GetConditionResult() . " and instocknameid='" . $instocknameid . "' and indate>='" . $date1 . "' and indate<='" . $date2 . "' order by stockinid desc";
			$sql1 = "select ProductNo,[Name],Amount as StockIn from (select Productid,sum(amount) as Amount from stockindetail where stockinid in(select stockinid from stockin where status='1' and instocknameid='" . $instocknameid . "' and indate>='" . $date1 . "' and indate<='" . $date2 . "' " . $this->GetConditionResult() . ") group by productid) a,stockproduct_material b where a.productid=b.productid";
		} else {

			$sql = "select GroupID,fromcountry as 国家,fromplace as 来源地,stocktype as 类型,overflowfromgroupid as 报溢所属,Indate as 入库日期,insertperson as 入库员,Stockin.memo as 备注Memo from stockin where status='1' and instocknameid='" . $instocknameid . "' and indate>='" . $date1 . "' and indate<='" . $date2 . "' order by stockinid desc";
			$sql1 = "select ProductNo,[Name],Amount as StockIn from (select Productid,sum(amount) as Amount from stockindetail where stockinid in(select stockinid from stockin where " .
				"status='1'and instocknameid='" . $instocknameid . "' and indate>='" . $date1 . "'and indate<='" . $date2 . "') group by productid) a,stockproduct_material b where a.productid=b.productid";

		}

		$data['dataGridView2'] = $this->query_Sql($sql);
		$data['dataGridView1'] = $this->query_Sql($sql1);
		$data['dataGridView1_count'] = count($data['dataGridView1']);
		$data['dataGridView2_count'] = count($data['dataGridView2']);
		$data['dataGridView2'] = utf_8($data['dataGridView2']);
		$data['dataGridView1'] = utf_8($data['dataGridView1']);
		return suc($data);
	}
	//拼接查询条件字符串
	private function GetConditionResult() {
		$result = "";
		$result = $this->input['M_Box'];
		if ($this->input['selected'] == "货源地") {
			return " and fromplace='" . $result . "'";
		} else if ($this->input['selected'] == "入库类型") {
			return " and stocktype='" . $result . "'";
		} else if ($this->input['selected'] == "入库员") {
			return " and insertperson='" . $result . "'";
		} else if ($this->input['selected'] == "来源国家") {
			return " and fromcountry='" . $result . "'";
		} else if ($this->input['selected'] == "备注") {
			return " and memo='" . $result . "'";
		} else {
			return "";
		}

	}
	//查询返回详细筛选条件
	public function GetResult() {
		$condition = ['入库员', '来源国家', '备注'];
		$array = [
			'入库员' => 'insertperson',
			'来源国家' => 'fromcountry',
			'备注' => 'memo',
		];
		foreach ($condition as $key => $value) {
			$result = $array[$value];
			if ($value == '入库员') {
				$sql3 = "select realname as 入库员 from enterapp_user order by realname";
			} else if ($value == '来源国家') {
				$sql3 = "select fromcountry as 来源国家 from stockfrom order by fromid";
			} else if ($value == '备注') {
				$sql3 = "select distinct memo as 备注 from stockin where status='1'";
			}
			$data[$value] = $this->query_Sql($sql3);
			$data[$value] = utf_8($data[$value]);
		}
		return suc($data);
	}

	public function SearchProduct() {
		$rule = [
			"M_Box" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9100, $check);
		}
		if ($this->input['selected'] == "产品") {
			$data['SearchReportIncludingProduct'] = $this->SearchReportIncludingProduct();
			$data["SearchSumProducts"] = $this->SearchSumProducts();
			$data['SearchReportIncludingProduct_count'] = count($data['SearchReportIncludingProduct']);
			$data['SearchSumProducts_count'] = count($data['SearchSumProducts']);
			$data["SearchSumProducts"] = utf_8($data["SearchSumProducts"]);
			$data["SearchReportIncludingProduct"] = utf_8($data["SearchReportIncludingProduct"]);
			return suc($data);
		} else {
			return err(9000, "筛选条件应设置为产品！");
		}

	}
	private function SearchReportIncludingProduct() {
		$sql = "";
		$fromplace = "";
		$date1 = "";
		$date2 = "";
		$stockname = "";
		$ProductName = "";
		$fromplace = $this->input['selected'];
		$stockname = $this->input['InStock'];
		$date1 = $this->input['From'];
		$date2 = $this->input['To'];
		$ProductName = $this->input['M_Box'];
		$sql = "select '" . $ProductName . "' as Product,Amount as StockIn,GroupID,fromcountry as 国家,fromplace as 来源地,stocktype as 类型,overflowfromgroupid as 报溢所属,Indate as 入库日期,insertperson as 入库员 from stockin,stockindetail where"
			. " stockin.stockinid=stockindetail.stockinid and status='1' and instockname='" . $stockname . "' and indate>='" . $date1 . "' and indate<='" . $date2 . "' and productid=(select productid from"
			. " stockproduct_material where [name]='" . $ProductName . "') order by stockin.stockinid desc";
		$data = $this->query_Sql($sql);
		return $data;

	}
	private function SearchSumProducts() {
		$fromplace = $this->input['selected'];
		$stockname = $this->input['InStock'];
		$date1 = $this->input['From'];
		$date2 = $this->input['To'];
		$ProductName = $this->input['M_Box'];
		$sql = "select ProductNO,[Name],Amount as StockIn from (select Productid,sum(amount) as Amount from stockindetail where stockinid in(select stockinid from stockin where status='1' and instockname='" . $stockname . "' and indate>='" . $date1 . "'"
			. " and indate<='" . $date2 . "' ) and productid=(select productid from stockproduct_material where [name]='" . $ProductName . "') group by productid) a,stockproduct_material b where a.productid=b.productid";
		$data = $this->query_Sql($sql);
		return $data;
	}
	/**
	 * @title 查询入库-GetProductDetail
	 * @type  interface
	 * @login 1
	 * @param groupid 入库单GroupID 1
	 * @return data 对应GroupID的产品详细信息 array
	 */
	public function GetProductDetail() {
		$GroupID = $this->input['groupid'];
		$sql = "select ProductNo,[Name],Amount as StockIn,Memo from (select Productid, Amount,memo from stockindetail where stockinid in(select stockinid from stockin where groupid='" . $GroupID . "') ) a,stockproduct_material b where a.productid=b.productid";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
}
