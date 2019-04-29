<?php
namespace app\desktop\Controller;
use app\desktop\Controller\Common;
use think\Db;
use think\Exception;

/**
 *@title 促销
 *@type menu
 *@login 1
 */
class Promotion extends Common {

	public function _initialize() {
		parent::_initialize();
	}
//查询当月注册升级到各个新的级别的会员信息
	/**
	 * @title 当月注册级别查询
	 * @type interface
	 * @login 1
	 * @param comboBox1 下拉选择框
	 */
	public function GetCurrentRegGrade() {
		$input = input('post.');
		$check = $this->validate($input, ['comboBox1' => 'require'], ['msg' => '请选择要查询的会员级别!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$comboBox1 = $input['comboBox1'];
		Db::startTrans();
		try {
			if ($input['comboBox1'] < 9) {
				$sql = "SELECT customerno as 卡号,customername as 姓名,grade as 级别,Per_grade as 真正级别,mobile,phone,shopno as 专卖店,regdate as 日期 from tb_customer,tb_customerinfo where tb_customerinfo.customerid=tb_customer.customerid and regdate>='" . $this->GetCurrentDate() . "' and grade='" . $this->GetLevel($comboBox1) . "'";
			} else {
				$sql = "SELECT customerno as 卡号,customername as 姓名,'" . $comboBox1 . "' as 荣衔,mobile,phone,shopno as 专卖店,regdate as 日期 from tb_customer,tb_customerinfo where tb_customerinfo.customerid=tb_customer.customerid and regdate>='" . $this->GetCurrentDate() . "' and job_grade='" . $this->GetLevel($comboBox1) . "'";
			}
			$data = $this->query_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '查询出错,错误原因:' . $e->getMessage());
		}
		$data = utf_8($data);
		return suc($data);
	}
	private function GetCurrentDate() {
		$sql = "select dateadd(month,-1,max(caldate)+1)  from tb_bonus";
		$dates = $this->GetStringData($sql);
		//var_dump($dates);die();
		return $dates;
	}
	private function GetLevel($data) {
		if ($data < 9) {
			return $data;
		} else {
			return ($data - 8);
		}
	}

//查询往月注册升级到各个新的级别的会员信息
	/**
	 * @title 往月注册级别查询
	 * @type interface
	 * @login 1
	 * @param comboBox2 下拉选择框
	 */
	public function GetOldRegGrade() {
		set_time_limit(120);
		$input = input('post.');
		$check = $this->validate($input, ['comboBox2' => 'require'], ['msg' => '请选择要查询的会员级别!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$comboBox2 = $input['comboBox2'];
		Db::startTrans();
		try {
			if ($comboBox2 < 9) {

				$sql = "SELECT customerno as 卡号,customername as 姓名,grade as 级别,Per_grade as 真正级别,mobile,phone,shopno as 专卖店,regdate as 日期 from tb_customer,tb_customerinfo where tb_customerinfo.customerid=tb_customer.customerid and tb_customer.customerid in(select customerid from tb_promo where " . $this->GetLastMonth(1) . "<'" . $this->GetLevel($comboBox2) . "' and " . $this->GetLastMonth(0) . "='" . $this->GetLevel($comboBox2) . "')"; //and  (len(mobile)>10 or len(phone)>10)
			} else {
				$sql = "SELECT customerno as 卡号,customername as 姓名,'" . $comboBox2 . "' as 荣衔,mobile,phone,shopno as 专卖店,regdate as 日期 from tb_customer,tb_customerinfo where tb_customerinfo.customerid=tb_customer.customerid and regdate<'" . $this->GetCurrentDate() . "' and job_grade=" . $this->GetLevel($comboBox2) . " and tb_customer.customerid not in (select TB_BONUS.customerid from TB_BONUS where JOB_GRADE=" . $this->GetLevel($comboBox2) . " AND CALDATE='" . $this->GetLastCalDATE() . "')";
			}

			$data = $this->query_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '查询出错,请重试!或者联系Eirc!');
		}
		$data = utf_8($data);
		return suc($data);
	}

	private function GetLastMonth($mark) {
		$year = "";
		$month = "";
		$lastdate = "";

		$sql = "select year(max(caldate)) as year,month(max(caldate)) as month from tb_bonus where caldate<=(select max(caldate) from tb_bonus)";
		$data = $this->query_Sql($sql);
		$year = $data[0]['year'];
		$month = $data[0]['month'];
		if ($mark == 1) {
			if ($month > 1) {
				$month = ($month - 1);
			} else {
				$year = ($year - 1);
				$month = "12";
			}
		}

		$lastdate = $this->proccessdate($year, $month);

		return $lastdate;
	}
	private function proccessdate($year, $month) {
		$Month = $this->GetMonthByNumber($month);
		$data = "Grade" . $Month . substr($year, 2, 2);
		return $data;
	}

	private function GetLastCalDATE() {
		$sql = "select dateadd(month,-1,max(caldate)+1)-1  from tb_bonus";
		$date = $this->GetStringData($sql);
		return $date;
	}
	/**
	 * @title 发车查询
	 * @type interface
	 * @login 1
	 * @param comboBox2
	 */
	public function CarAwardGrade() {
		$input = input('post.');
		$check = $this->validate($input, ['comboBox2' => 'require'], ['msg' => '请选择要查询的会员级别!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$comboBox2 = $input['comboBox2'];

		if ($comboBox2 <= 8) {
			$sql = "select customerno as 卡号,customername as 姓名,grade as 级别,shopno As SC,memo as 历史发车年份,postcode as 发车级别,phone,mobile from tb_customer,tb_customerinfo where tb_customer.customerid=tb_customerinfo.customerid and grade='" . $comboBox2 . "'";
		} else {
			$sql = "select customerno as 卡号,customername as 姓名,grade as 级别,shopno As SC,memo as 历史发车年份,postcode as 发车级别,phone,mobile from tb_customer,tb_customerinfo where tb_customer.customerid=tb_customerinfo.customerid and isnull(postcode,0)>0";
		}
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
//更新字段
	/**
	 * @title 促销系统---更新字段
	 * @type interface
	 * @login 1
	 */
	public function AddColumn() {
		$date = $this->GetPostfix();
		//var_export($date);die();
		if (!$date) {
			return err(9000, '获取月份失败,请重试!');
		}
		$column1 = "Grade" . $date;
		$column2 = "Per_Grade" . $date;
		$column3 = "ParentGrade" . $date;
		$column4 = "ParentAccu_NetPeriodSale" . $date;
		$column5 = "Per_ParentGrade" . $date;
		$column6 = "Groupsale" . $date;
		$column7 = "Accu_Groupsale" . $date;
		$column8 = "Net_PeriodSale" . $date;
		$column9 = "Accu_Net_PeriodSale" . $date;
		$column10 = "PeriodSaleBV" . $date;
		$column11 = "Mark" . $date;
		Db::startTrans();
		try {
			$sql = "ALTER TABLE tb_promo ADD " . $column1 . " smallint ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column2 . " smallint ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column3 . " smallint ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column4 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column5 . " smallint ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column6 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column7 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column8 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column9 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column10 . " money ";
			$this->Exc_Sql($sql);
			$sql = "ALTER TABLE tb_promo ADD " . $column11 . " bit ";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('成功插入11条字段！');
		} catch (Exception $e) {
			Db::rollback();
			if (strpos($e->getMessage(), "唯一") === false) {
				return err(9000, '执行错误,错误原因:' . $e->getMessage());
			} else {
				return suc('已经插入该月字段,无需进行再次操作！');
			}
		}
	}
	private function GetPostfix() {
		$sql = "select max(caldate) from tb_bonus";
		$date = $this->GetStringData($sql);
		$date = explode(' ', $date)[0];
		$date = explode('-', $date);
		$Year = substr($date['0'], 2, 2);
		$number = $date['1'] + 0;
		//var_export($number);die();
		$Month = $this->GetMonthByNumber($number);
		if (!$Month) {
			return false;
		}
		return $Month . $Year;
	}
//以下是插入并更新当月数据操作
	/**
	 * @title 促销系统--插入更新数据
	 * @type interface
	 * @login 1
	 */

	public function InsertDate1() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$sql = "insert into tb_promo (customerid,customerno,customername,Branch,shopno,regdate,RecommendNo,recommendName,ParentNo,parentName)select c.customerid,customerno,customername,Branchid as Branch,shopno,regdate,RecommendNo,recommendName,ParentNo,parentName from tb_customer c,tb_customerinfo ci where c.customerid=ci.customerid and regdate between (select DATEADD(Month,-1,CONVERT(char(8),DATEADD(Month,1, max(caldate)),120)+'1') from tb_bonus) and (select  max(caldate) from tb_bonus)";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成20%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate2() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$lastdate = $this->GetSystemLastDateStr();
			$sql = "update tb_promo set grade" . $date . "=isnull(grade" . $lastdate . ",0),Per_Grade" . $date . "=isnull(Per_Grade" . $lastdate . ",0),GroupSale" . $date . "=isnull(GroupSale" . $lastdate . ",0),Accu_GroupSale" . $date . "=isnull(Accu_GroupSale" . $lastdate . ",0),Net_PeriodSale" . $date . "=isnull(Net_PeriodSale" . $lastdate . ",0),Accu_Net_PeriodSale" . $date . "=isnull(Accu_Net_PeriodSale" . $lastdate . ",0),Mark" . $date . "=isnull(Mark" . $lastdate . ",1)";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成40%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate3() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set grade" . $date . "=(select grade from tb_customer where customerid=tb_promo.customerid and grade>grade" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and grade>grade" . $date . ")"; //--更新与本月数据不一致的会员的本月字段
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成50%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate4() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set grade" . $date . "=(select grade from tb_customer where customerid=tb_promo.customerid and grade<>grade" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and grade<>grade" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成60%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate5() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set per_grade" . $date . "=(select per_grade from tb_customer where customerid=tb_promo.customerid and per_grade<>per_grade" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and per_grade<>per_grade" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成70%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate6() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set GroupSale" . $date . "=(select GroupSale from tb_customer where customerid=tb_promo.customerid and GroupSale<>GroupSale" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and GroupSale<>GroupSale" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成80%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate7() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set Accu_GroupSale" . $date . "=(select Accu_GroupSale from tb_customer where customerid=tb_promo.customerid and Accu_GroupSale<>Accu_GroupSale" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and Accu_GroupSale<>Accu_GroupSale" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成85%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate8() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set Net_PeriodSale" . $date . "=(select Net_PeriodSale from tb_customer where customerid=tb_promo.customerid and Net_PeriodSale<>Net_PeriodSale" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and Net_PeriodSale<>Net_PeriodSale" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成90%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}

	public function InsertDate9() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set Net_PeriodSale" . $date . "=(select Net_PeriodSale from tb_customer where customerid=tb_promo.customerid and Net_PeriodSale<>Net_PeriodSale" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and Net_PeriodSale<>Net_PeriodSale" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成90%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}

	public function InsertDate10() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set Accu_Net_PeriodSale" . $date . "=(select Accu_Net_PeriodSale from tb_customer where customerid=tb_promo.customerid and Accu_Net_PeriodSale<>Accu_Net_PeriodSale" . $date . ") where customerid in(select c.customerid from tb_customer c,tb_promo p where p.customerid=c.customerid and Accu_Net_PeriodSale<>Accu_Net_PeriodSale" . $date . ")";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成95%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	public function InsertDate11() {
		set_time_limit(0);
		Db::startTrans();
		try {
			$date = $this->GetSystemDateStr();
			$sql = "update tb_promo set PeriodSaleBV" . $date . "=(select periodsale_BV from tb_customer where periodsale_BV>0 and customerid=tb_promo.customerid) where customerid in(select customerid from tb_customer where periodsale_BV>0)";
			$this->Exc_Sql($sql);
			Db::commit();
			return suc('完成100%');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '插入更新失败请重试!失败sql' . $e->getMessage());
		}
	}
	//当前年月拼接的字符串
	private function GetSystemDateStr() {
		$SystemYear = date('y'); //当前年份
		$SystemMonth = $this->GetMonthByNumber(date('m')); //当前月份
		$date = $SystemMonth . $SystemYear;
		return $date;
	}
	//上个月的年月拼接的字符串
	private function GetSystemLastDateStr() {
		$SystemLast = date('y-m', strtotime('last month')); //上一个月的年月
		$SystemLast = explode('-', $SystemLast);
		$SystemLastYear = $SystemLast[0]; //上一个月的年份
		$SystemLastMonth = $this->GetMonthByNumber($SystemLast[1]); //上一个月的月份
		$lastdate = $SystemLastMonth . $SystemLastYear;
		return $lastdate;
	}
//以下是促销查询
	/**
	 * @title 促销系统---促销查询
	 * @type interface
	 * @login 1
	 * @param comboBox1_Grade 级别
	 * @param top_from 级别开始值
	 * @param top_to 级别结束值
	 * @param comboBox2_PerGrade 真正级别
	 * @param middle_from 真正级别开始值
	 * @param middle_to 真正级别结束值
	 * @param comboBox1_Money 数值
	 * @param below_from 数值开始值
	 * @param below_to 数值结束值
	 * @param selct 是否勾选(0否,1是)
	 * @param Regdate_from Regdate开始时间
	 * @param Regdate_to Regdate结束时间
	 * @param comboBox3_Member 会员信息下拉框
	 * @param textBox5_Member 内容输入框
	 */
	public function PromoSearch() {
		set_time_limit(0);
		$input = input('post.');
		$field = "customerno as Code,customername as Name,shopno as  SC,RegDate,parentno as Sponsor,parentName as Sname,recommendno as Place,recommendname as Pname";
		Db::startTrans();
		try {
			$data = $this->SearchCondition($input);
			$where = $data['where'];
			if (!empty($data['result_column'])) {
				$field .= $data['result_column'];
			}
			//$result = Db::table('tb_promo')->where($where)->field($field)->select();
			$result = model('Promotion')->getlistAC($where, $field);
			Db::commit();
		} catch (Exception $e) {
			return err(9000, '错误原因:' . $e->getMessage());
		}
		return suc($result);
	}
	//拼接查询条件
	private function SearchCondition($input) {
		$result_column = '';
		$where = [];
		if ($input['comboBox1_Grade']) {
			$data = $this->GetReslutFromTo($input['comboBox1_Grade'], $input['top_from'], $input['top_to']);
			$result_column .= "," . $data['result'];
			$where[] = $data['where']; //以上拼接级别查询条件
		}
		if ($input['comboBox2_PerGrade']) {
			$data1 = $this->GetReslutFromTo($input['comboBox2_PerGrade'], $input['middle_from'], $input['middle_to']);
			$result_column .= "," . $data1['result'];
			$where[] = $data1['where']; //以上拼接真正级别查询条件
		}
		if ($input['comboBox1_Money']) {
			$data2 = $this->GetReslutFromTo($input['comboBox1_Money'], $input['below_from'], $input['below_to']);
			$result_column .= "," . $data2['result'];
			$where[] = $data2['where']; //以上拼接数值的查询条件
		}
		if ($input['selct']) {
			$where['Regdate'] = ["between", "{$input['Regdate_from']},{$input['Regdate_to']}"]; //以上拼接Regdate日期的查询条件
		}
		if ($input['comboBox3_Member']) {
			$result_column .= "," . $input['comboBox3_Member'];
			$where[$input['comboBox3_Member']] = $input['textBox5_Member'];
		}
		$data4['result_column'] = $result_column;
		$data4['where'] = $where;
		return $data4;
	}
	//拼接查询某个字段从from开始到to结束的查询条件
	private function GetReslutFromTo($coulmn = '', $from = '', $to = '') {
		$result = '';
		$where = [];
		$data = [];
		if ($from != '' || $to != '') {
			$result .= "," . $coulmn; //拼接查询字段
			$where[$coulmn] = ['between', "$from,$to"];
		}
		if (!empty($where)) {
			$data['where'] = $where;
		}
		if (!empty($result)) {
			$data['result'] = $result;
		}
		return $data;
	}
	/**
	 * @title 获取促销查询下拉选择框内容
	 * @type interface
	 * @login 1
	 * @param mark 请求那个下拉选择框的数据(1:级别,2:真正级别,3:数值)
	 * @return data
	 */
	public function get_selects() {
		$input = input('post.');
		$check = $this->validate($input, ['mark' => 'require'], ['msg' => '请选择请求的下拉选择框!']);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data = $this->get_columon_name($input['mark']);
		return suc($data);
	}
	private function get_columon_name($mark) {
		switch ($mark) {
		case '1':
			$sql = "SELECT COLUMN_NAME as [级别] FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME='tb_promo' and COLUMN_NAME LIKE 'Grade%' ";

			break;
		case '2':
			$sql = "SELECT COLUMN_NAME as [真正级别] FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME='tb_promo' and COLUMN_NAME LIKE 'Per_Grade%' ";
			break;
		default:
			$sql = "SELECT COLUMN_NAME as 级别 FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME='tb_promo' and COLUMN_NAME LIKE 'ParentAccu_NetPeriodSale%' OR COLUMN_NAME LIKE 'PeriodSaleBV%' OR COLUMN_NAME LIKE 'GroupSale%' OR COLUMN_NAME LIKE 'Accu_Net_PeriodSale%' OR COLUMN_NAME LIKE 'Net_PeriodSale%' OR COLUMN_NAME LIKE 'Accu_Groupsale%' ";
			break;
		}
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return $data;
	}

}
