<?php
namespace app\desktop\controller;
use think\Db;
use think\Exception;

/**
 * @title 奖金计算
 * @type menu
 * @login 1
 */
class Bonus extends Common {
	public $NigeriaExchangeRate = "340";
	public $GhanaExchangeRate = "4";
	//public  $SAExchangeRate = "11";
	public $BeninExchangeRate = "700";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->TellerOper = self::$realname;
		$this->input = input("post.");
	}
	/**
	 * @title 获取日期
	 * @type interface
	 * @login 1
	 */
	public function getBonuDate() {
		$sql = "select fzkd1.dbo.GetYearMonth(max(tb_Bonus.caldate)) from tb_bonus";
		$data = $this->GetStringData($sql);
		if (!$data) {
			return err(9000, '日期获取失败!');
		}
		return suc($data);
	}
	/**
	 * @title 导出奖金原始表按钮(导出奖金数据储存在临时表BonusInfo)
	 * @type interface
	 * @login 1
	 * @param Date 日期
	 */
	public function button2_Click() {
		set_time_limit(0);
		$date = $this->getBonuDate();
		$datep1 = $date['data'];
		$this->Drop_Table1('BonusInfo'); //删除旧表
		try {
			$expbonus = "SELECT 计算日期 = '" . $datep1 . "', 所属机构 = case when tb_Customer.BranchID = 0 then '总公司' else tb_Branch.BranchName end,会员编号 = tb_Customer.CustomerNO,姓名 = tb_CustomerInfo.CustomerName,专卖店编号 = tb_Customer.ShopNO ,会员级别 = dbo.FormatDictText(tb_Customer.Grade, '会员级别'),下月真正级别 = dbo.FormatDictText(tb_Customer.Per_Grade, '会员级别'),会员荣衔 = dbo.FormatDictText( tb_Bonus. Job_Grade, '会员荣衔' ), 董事长荣衔 = dbo.FormatDictText( tb_Bonus. C_Grade, '董事长荣衔' ),个人本月业绩 = tb_Bonus. PeriodSale,个人累计业绩 = tb_Bonus. Accu_PeriodSale,级别业绩 = tb_Bonus.PeriodSale_Grade,累计级别业绩 = tb_Bonus.Accu_PeriodSale_Grade,小组业绩 = tb_Bonus.GroupSale,小组业绩累计 = tb_Bonus.Accu_GroupSale,整网业绩 = tb_Bonus.Net_PeriodSale,整网累计业绩 = tb_Bonus.Accu_Net_PeriodSale,直接业绩 = tb_Bonus.PeriodSale_Rdirect,间接业绩 = tb_Bonus.PeriodSale_indirect,直接奖 = tb_Bonus.Bonus_BringUp,间接奖 = tb_Bonus.Bonus_Grade,领导奖 = tb_Bonus.Bonus_Leader,总奖金 = tb_Bonus.totalBonus,荣衔奖 = tb_Bonus.Bonus_Title,自动扣除 = tb_Bonus.PeriodSale_Auto,收入 = tb_Bonus.totalGet,国家或地区 = dbo.FormatDictText2(tb_Customer.Nationality, '国家'),省份 = tb_CustomerInfo.Province into BonusInfo FROM tb_Bonus left JOIN tb_Customer ON tb_Customer.CustomerID = tb_Bonus. CustomerID left JOIN tb_CustomerInfo  ON  tb_Customer.CustomerID = tb_CustomerInfo.CustomerID left join tb_Branch  on tb_Customer.BranchID = tb_Branch.BranchID where fzkd1.dbo.GetYearMonth(tb_Bonus.CalDate)= '" . $datep1 . "'and tb_Bonus.totalBonus>=0.1"; // MessageBox.Show(expbonus);
			$this->Exc_Sql($expbonus);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '临时表BonusInfo创建失败' . $e->getMessage() . '请重试!');
		}
		return suc('生成临时表成功!');
	}
	/**
	 * @title 制作奖金按钮
	 * @type interface
	 * login 1
	 */
	public function button12_Click() {
		$re2 = $this->DBPV2();
		//exception("DBPV修改完毕！");
		$re3 = $this->SanxingJianjiejiang3();
		//exception("3星以下间接奖修改完毕！");
		$re4 = $this->Date_Honor4();
		//exception("日期和会员荣衔修改完毕！");
		$re5 = $this->IncreaseFourColumn5();
		//exception("添加MF,TAX,TOTAL,D_Sign四列！");
		$re6 = $this->AddThree6();
		//exception("补充三列MF,TAX,TOTAL！");
		$re7 = $this->AjustBonus7();
		//exception("删除奖金为0的项！");
		if ($re2 && $re3 && $re4 && $re5 && $re6 && $re7) {
			return suc('制作奖金完成,可以选择导出奖金!');
		} else {
			return err(9000, "制作奖金错误请重新操作!");
		}
	}
	//更新修改表BonusInfo数据
	private function DBPV2() {
		$result = true;
		try {
			$sqlup = "Alter Table BonusInfo Alter Column 总奖金 money";
			$this->SQLup($sqlup);

			$sqlup = "Alter Table BonusInfo Alter Column 个人本月业绩 money";
			$this->SQLup($sqlup);

			$sqlup = "Alter Table BonusInfo Alter Column 自动扣除 money";
			$this->SQLup($sqlup);

			$sqlup = "Alter Table BonusInfo Alter Column 收入 money";
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金,自动扣除=0 where 个人本月业绩>=100 and 会员级别=下月真正级别-1 and 会员级别=7"; //导致KN024980在201302奖金时多扣除100BV，因为个人当月业绩正好是100
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金+个人本月业绩-100,自动扣除=100-个人本月业绩 where  个人本月业绩<100 and 个人本月业绩+自动扣除>100 and 会员级别=下月真正级别-1 and 会员级别=7";
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金,自动扣除=0 where 个人本月业绩>=50 and 会员级别=下月真正级别-1 and 会员级别=6";
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金+个人本月业绩-50,自动扣除=50-个人本月业绩 where  个人本月业绩<50 and 个人本月业绩+自动扣除>50 and 会员级别=下月真正级别-1 and 会员级别=6";
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金,自动扣除=0 where 个人本月业绩>=20 and 会员级别=下月真正级别-1 and 会员级别=5";
			$this->SQLup($sqlup);

			$sqlup = "update BonusInfo set 收入=总奖金+个人本月业绩-20,自动扣除=20-个人本月业绩 where  个人本月业绩<20 and 个人本月业绩+自动扣除>20 and 会员级别=下月真正级别-1 and 会员级别=5";
			$this->SQLup($sqlup);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	//修改3星以下间接奖
	private function SanxingJianjiejiang3() {
		$result = true;
		try {
			$SQLIdbadjust = "update BonusInfo set 间接奖=0,收入=直接奖 where 会员级别<4 and 直接奖=间接奖";
			$this->SQLup($SQLIdbadjust);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	//修改日期和荣衔
	private function Date_Honor4() {
		$date = $this->input['Date'];
		if ($date) {
			return err(9000, '日期必须填写!');
		}
		$result = true;
		try {
			$sqldate = "update BonusInfo set 计算日期='" . $date . "'";
			$this->SQLup($sqldate);

			$sqlhonor = "update BonusInfo set 会员荣衔='STAR1' WHERE 会员荣衔='一星经理荣衔'";
			$this->SQLup($sqlhonor);

			$sqlhonor = "update BonusInfo set 会员荣衔='STAR2' WHERE 会员荣衔='二星经理荣衔'";
			$this->SQLup($sqlhonor);

			$sqlhonor = "update BonusInfo set 会员荣衔='STAR3' WHERE 会员荣衔='三星经理荣衔'";
			$this->SQLup($sqlhonor);

			$sqlhonor = "update BonusInfo set 会员荣衔='NO' WHERE 会员荣衔='无荣衔'";
			$this->SQLup($sqlhonor);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	//添加MF,TAX,TOTAL,D_Sign四个字段！
	private function IncreaseFourColumn5() {
		$result = true;
		try {
			$sql = " ALTER TABLE BonusInfo ADD MF float ";
			$this->SQLup($sql);

			$sql = " ALTER TABLE BonusInfo ADD TAX varchar(4)";
			$this->SQLup($sql);

			$sql = " ALTER TABLE BonusInfo ADD total money ";
			$this->SQLup($sql);

			$sql = " ALTER TABLE BonusInfo ADD D_Sign VARCHAR ";
			$this->SQLup($sql);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	//补充三列MF,TAX,TOTAL的数据值
	private function AddThree6() {
		$result = true;
		try {
			$sqlmf = "update BonusInfo set MF=200 where 所属机构='总公司' and 专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211'AND  专卖店编号='375'";
			$this->SQLup($sqlmf); //尼日利亚MF设置成功！

			$sqlmf = "update BonusInfo set MF=0.5 where 所属机构 like'%KEDI-A%' ";
			$this->SQLup($sqlmf); //加纳MF设置成功！

			$sqlmf = "update BonusInfo set MF=150 where 专卖店编号='209' or 专卖店编号='210' or 专卖店编号='211'or 专卖店编号='375'";
			$this->SQLup($sqlmf); //贝宁MF设置成功！

			$sqlmf = "update BonusInfo set MF=3.6 where 所属机构 like'%KEDI-B%' ";
			$this->SQLup($sqlmf); //南非MF设置成功！

			$sqltax = "update BonusInfo set TAX='5%'";
			$this->SQLup($sqltax); //TAX设置成功！

			$sqltax = "update BonusInfo set TAX='0%'where 所属机构 like'%KEDI-B%'";
			$this->SQLup($sqltax); //TAX设置成功！

			$sqltotal = "update BonusInfo set total=(收入*" . $this->NigeriaExchangeRate . ")*0.95-200 where 所属机构='总公司'"; // and 专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211'";
			$this->SQLup($sqltotal); //尼日利亚奖金计算完！

			$sqltotal = "update BonusInfo set total=(收入*" . $this->GhanaExchangeRate . ")*0.95-0.5 where 所属机构 like'%KEDI-A%'";
			$this->SQLup($sqltotal); //加纳奖金计算完！

			$sqltotal = "update BonusInfo set total=(收入*" . $this->BeninExchangeRate . ")*0.95-150 where 专卖店编号='209' or 专卖店编号='210' or 专卖店编号='211'";
			$this->SQLup($sqltotal); //贝宁奖金计算完！

			//sqltotal = "update BonusInfo set total=(收入*".$SAExchangeRate.")*1.0-3.6 where 所属机构 like'%KEDI-B%'";
			//$this->SQLup($sqltotal);//南非奖金计算完！
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	//删除奖金为0的项！
	private function AjustBonus7() {
		$result = true;
		try {
			$sqllastadjust = "update BonusInfo set total=0 where 自动扣除>0 and total<0";
			$this->SQLup($sqllastadjust); //奖金小于0，自动扣减大于0的奖金置零！

			$sqllastadjust = "delete from BonusInfo  where total<0 ";
			$this->SQLup($sqllastadjust); //奖金小于0的删除！

			$sqllastadjust = "delete from BonusInfo  where 自动扣除<=1 and total<10 and 专卖店编号<>'209' and 专卖店编号<'800'"; // and 专卖店编号<'800' and 专卖店编号<>'211'";
			$this->SQLup($sqllastadjust); //奖金小于10，自动扣减小于1的删除！

			$sqllastadjust = "update BonusInfo set total=0 where 所属机构='总公司' and  专卖店编号<'800' and 自动扣除>0 and total<10"; //专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211'and
			$this->SQLup($sqllastadjust); //尼日利亚奖金小于10，自动扣减大于0的奖金置零！
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
	/**
	 * @title 导出升级情况表
	 * @type  interface
	 * @login 1
	 * @return data 数据集合
	 */
	public function Shengji() {
		$sql = "select * from BonusInfo where 会员级别=下月真正级别-1";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 导出尼日利亚奖金
	 * @type  interface
	 * @login 1
	 * @return data 数据集合
	 */
	public function ExportNigeria() {
		set_time_limit(180);
		$sqlnigeria = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DPBV,收入 AS SUBTOTAL,MF,TAX,TOTAL FROM BonusInfo where 所属机构='总公司' and 专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211' order by 专卖店编号 asc,会员编号 asc "; //and 专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211'
		$data = $this->query_Sql($sqlnigeria);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 导出加纳奖金
	 * @type  interface
	 * @login 1
	 * @return data 数据集合
	 */
	public function ExportBonusGhana() {
		set_time_limit(180);
		$sqlghana = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DBPV,收入 AS
		SUBTOTAL,MF,''AS MEMO,TOTAL FROM BonusInfo where 所属机构 like'KEDI-A%' order by 专卖店编号 asc,会员编号 asc ";
		$data = $this->query_Sql($sqlghana);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 导出贝宁奖金
	 * @type  interface
	 * @login 1
	 * @return data 数据集合
	 */
	public function ExportBenninBonus() {
		set_time_limit(180);
		$sqlbennin = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DBPV,收入 AS SUBTOTAL,MF,TAX,TOTAL FROM BonusInfo where 专卖店编号='209' or 专卖店编号='210' or 专卖店编号='211' order by 专卖店编号 asc,会员编号 asc ";
		$data = $this->query_Sql($sqlbennin);
		$data = utf_8($data);
		return suc($data);
	}
//低于40万的专卖店列表数据获取
	/**
	 * @title 获取低于40万的专卖店列表
	 * @type interface
	 * @login 1
	 * @return data 数据集合
	 */
	public function getlowsc() {
		set_time_limit(0);
		try {
			$sql = "update MarketShop set MonthAchieve=B.amount from (select shopno,sum(total_Naira) as amount from tb_sale_entered_byfrontdesk where realdate between (select fromdate from monthlyreportInfo where dateadd( month, 1,monthly)=(select dateadd( day, 1,max(caldate)) from tb_bonus)) and (select todate from monthlyreportInfo where dateadd( month, 1,monthly)=(select dateadd( day, 1,max(caldate)) from tb_bonus)) group by shopno ) as B,MarketShop where MarketShop.Shopno=B.shopno";
			$this->Exc_Sql($sql); //更新MarketShop表中的当月业绩
			$sql = "select Shopno,isnull(MonthAchieve,0) as MonthAchieve from Marketshop where isnull(MonthAchieve,0)<400000 and isnull(direction,'0')<>'NORTH' and datediff(month,isnull(opendate,'2017-12-01'),(select max(caldate) from tb_bonus))>3"; //查询符合低于40万业绩的专卖店列表
			$data = $this->query_Sql($sql);
			return suc($data);
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '获取当月业绩低于40万专卖店列表失败,请重试!');
		}
	}

//奖金表上传C盘产生临时表nigeria
	/**
	 * @title 调整C盘奖金上传
	 * @type interface
	 * @login 1
	 * @param tablemark 上传那个奖金表标识(0=尼日利亚奖金,1=加纳奖金,2=贝宁奖金)
	 */
	public function button18_Click() {
		$check = $this->validate($this->input, ["tablemark" => "require|\d"], ["tablemark" => "请选择上传的奖金表!"]);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$this->Drop_Table1('nigeria'); //删除旧表
			$this->UploadNigeria($this->input['tablemark']);
			Db::commit();
			return suc('奖金表上传成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '奖金表上传失败,请重新操作!' . $e->getMessage());
		}
	}
	//判断上传尼日利亚,加纳,贝宁中那个奖金表数据以此来产生临时表
	private function UploadNigeria($tablemark) {
		if ($tablemark == 1) {
			$sql = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DBPV,收入 AS
		SUBTOTAL,MF,''AS MEMO,TOTAL into nigeria FROM BonusInfo where 所属机构 like'KEDI-A%' order by 专卖店编号 asc,会员编号 asc";
		} else if ($tablemark == 2) {
			$sql = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DBPV,收入 AS SUBTOTAL,MF,TAX,TOTAL into nigeria FROM BonusInfo where 专卖店编号='209' or 专卖店编号='210' or 专卖店编号='211' order by 专卖店编号 asc,会员编号 asc";
		} else {
			$sql = "select 所属机构 as NO,会员编号 AS CODE,姓名 AS NAME,计算日期 AS DATE,专卖店编号 AS SC,会员级别 AS GRADE,会员荣衔 AS HONORARY,个人本月业绩 AS PPV,个人累计业绩 AS CPPV,小组业绩 AS GBV,整网业绩 AS TNPV,整网累计业绩 AS CPV,直接奖 AS DB,间接奖 AS IDB,领导奖 AS LSB,自动扣除 AS DPBV,收入 AS SUBTOTAL,MF,TAX,TOTAL into nigeria FROM BonusInfo where 所属机构='总公司' and 专卖店编号<>'209' and 专卖店编号<>'210' and 专卖店编号<>'211' order by 专卖店编号 asc,会员编号 asc";
		}
		$this->Exc_Sql($sql);
	}
	//sql 事务操作
	private function SQLup($sql) {
		try {
			$this->Exc_Sql($sql);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
	}

	//以下是店费制作的程序
	/**
	 * @title 店费制作
	 * @type  interface
	 * @login 1
	 * @param from 计算业绩欠款开始日期
	 * @param to 计算业绩欠款的结束日期
	 * @return data
	 */
	public function button17_Click() {
		$rule = [
			"from" => "require",
			"to" => "require",
		];
		$msg = [
			"from" => "开始时间日期必须填写!",
			"to" => "结束时间日期必须填写!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$this->button16_Click();
			$this->button15_Click();
			$this->button14_Click($this->input['from'], $this->input['to']);
			//$this->button13_Click();
			Db::commit();
			return suc('店费制作成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '店费制作失败,请重试或者联系Eirc!' . $e->getMessage());
		}

	}
//导出NIGERIA店费表数据(最终店费表)
	/**
	 * @title 导出最终店费表
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function button13_Click() {
		set_time_limit(180);
		$result = $this->getlowsc(); //获取低于40万的装卖店
		$sc = array_column($result['data'], 'Shopno');
		$sqlnigeria = "select 机构 as NO,店铺 AS SC,日期 AS DATE,总PV AS [Total PV],总会员金额 AS [Sub Amount],App,[Total Amount],DEBT,MF,Bonus,D_Sign FROM ScInfo where 店铺 not in('" . $sc . "') and 店铺 like'___'";
		$data = $this->query_Sql($sqlnigeria);
		$data = utf_8($data);
		return $data;
	}

//创建临时表ScInfo
	private function button16_Click() {
		set_time_limit(0);
		$status = true;
		$this->Drop_Table1('scinfo'); //删除旧表
		$date = $this->getBonuDate();
		$datep1 = $date['data'];
		$sqlup = "SELECT 机构 = CASE WHEN s.BranchID = 0 THEN '总公司' ELSE b.BranchName END, 店铺 = s.ShopNO, 日期 = s.SaleDate, 总BV = s.TotalPV, 总PV = s.TotalRetail, 总会员金额 = s.TotalMember into ScInfo FROM (SELECT BranchID,ShopNO,fzkd1.dbo.GetYearMonth(SaleDate) AS SaleDate, SUM(ISNULL(TotalPV, 0)) AS TotalPV, SUM(ISNULL(TotalRetail, 0)) AS TotalRetail, SUM(ISNULL(TotalMember, 0)) AS TotalMember FROM fzkd1.dbo.tb_Sale WHERE (Status = 1) AND (SaleType <> - 1) AND (NetType = 2) GROUP BY BranchID, ShopNO, fzkd1.dbo.GetYearMonth(SaleDate)) AS s LEFT OUTER JOIN fzkd1.dbo.tb_Branch AS b ON s.BranchID = b.BranchID where s.SaleDate='" . $datep1 . "' and shopno<'800' and shopno<>'000' order by shopno"; // 导出最近的店费
		$this->Exc_Sql($sqlup);
	}
	//给表ScInfo添加5个字段
	private function button15_Click() {
		$sql = " ALTER TABLE ScInfo ADD App int ";
		$this->SQLup($sql);

		$sql = " ALTER TABLE ScInfo ADD [Total Amount] money";
		$this->SQLup($sql);

		$sql = " ALTER TABLE ScInfo ADD DEBT money ";
		$this->SQLup($sql);

		$sql = " ALTER TABLE ScInfo ADD MF int ";
		$this->SQLup($sql);

		$sql = " ALTER TABLE ScInfo ADD Bonus money ";
		$this->SQLup($sql);

		$sql = " ALTER TABLE ScInfo ADD D_Sign VARCHAR ";
		$this->SQLup($sql);
	}
	//获取业绩欠款数据写入表ScInfo中
	private function debt_data($from, $to) {
		//前端传参计算的时间段
		//$now = date('Y-m-1', strtotime('last month')); //上月的第一天
		//$last = date('Y-m-d', mktime(0, 0, 0, date('m'), 0, date('Y'))); //上个月最后一天
		$where['date'] = [['>=', $from], ['<=', $to]];
		$where['type_id'] = 3; //业绩
		$field = "sc ,sum(surplus_money) as debt";
		$data = model('RefundBorrow')->get_summarize($field, $where);
		$sc = model('RefundBorrow')->get_summarize('sc');
		$sc = array_column($sc, 'sc');
		//$sc = preg_replace('/^sc/i', '', $sc);
		foreach ($data as $v) {
			if (in_array($v['sc'], $sc)) {
				$sql = "update ScInfo set DEBT=" . $v['debt'] . " where 店铺=" . preg_replace('/^sc/i', '', $v['sc']);
			} else {
				$sql = "INSERT INTO ScInfo(店铺,DEBT) VALUES(" . preg_replace('/^sc/i', '', $v['sc']) . "," . $v['debt'] . ")";
			}
			$this->Exc_Sql($sql);
		}
	}
	//更改表ScInfo里3个字段的值
	private function button14_Click($from, $to) {
		$sql = "update ScInfo set MF=50 "; //设置MF
		$this->SQLup($sql);
		//计算会员方法
		$this->CreateView(); //增加临时视图
		$this->GetMemberNo(); //从临时视图里得到个数
		$sql = "update ScInfo set [total amount]=app*4000+总会员金额 ";
		$this->SQLup($sql); //尼日利亚店费计算完！
		$this->debt_data($from, $to);
		$sql = "update ScInfo set bonus=([total amount]-DEBT)*0.05-50 "; //修改于2013年5月14，将“（[total amount]-50）*0.05”改为“[total amount]*0.05-50”
		$this->SQLup($sql); //尼日利亚店费计算完！
	}
	//临时会员个数统计视图创建！
	private function CreateView() {
		$date = $this->getBonuDate();
		$datep1 = $date['data'];
		$ViewSql = "CREATE VIEW TEMP_Shop_Count as SELECT top 100 percent SHOPNO, COUNT(*)as noum  FROM fzkd1.dbo.TB_CUSTOMER WHERE SHOPNO<'800'AND fzkd1.dbo.GetYearMonth(regdate)='" . $datep1 . "' GROUP BY SHOPNO ORDER BY SHOPNO";
		$this->SQLup($ViewSql);
	}
	//更改表scinfo数据 删除临时视图TEMP_Shop_Count
	private function GetMemberNo() {
		$GetNo = "update scinfo set App=0";
		$this->SQLup($GetNo); //App全置零

		$GetNo = "update scinfo  set App=noum from TEMP_Shop_Count,scinfo where 店铺=shopno";
		$this->SQLup($GetNo); //更新各店会员数

		$GetNo = "drop VIEW TEMP_Shop_Count";
		$this->SQLup($GetNo); //删除临时视图
	}

//计算各店奖金和程序

	//  第一步  判断上传的奖金表的时间是否正确
	/**
	 * @title 判断上传的奖金表的时间是否正确
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function CheckBonusDate() {
		$BonusInfodate = $this->getBonuDate();
		$BonusInfodate = $BonusInfodate['data'];
		$sql = "select  [date] from nigeria";
		$data = $this->GetStringData($sql);
		if ($data == $BonusInfodate) {
			return suc('日期正确,可以继续下一步操作!');
		}
		return err(9000, '日期不正确，请使用奖金制作程序上传奖金表！');
	}
//  第二步 创建临时表tempsc
	/**
	 * @title 获取奖金本数据
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function create_tempsc() {
		Db::startTrans();
		try {
			$this->Drop_Table1('TEMPsc'); //去掉表
			$this->DropView(); //删掉视图
			$BonusSql = "CREATE VIEW TEMP0 as select top 100 percent sc, sum(total) as Bonus from nigeria group by sc order by sc ";
			$this->SQLup($BonusSql); //创建视图1，取自奖金本数据

			$BonusSql = "CREATE VIEW TEMP1 as select 店铺,bonus as Sc_Bonus from scinfo ";
			$this->SQLup($BonusSql); //取自数据2，取自店费数据

			$BonusSql = "CREATE VIEW TEMP2 as select * from temp0 full outer join TEMP1 on temp0.sc=TEMP1.店铺";
			$this->SQLup($BonusSql); //全外连接，产生视图

			$BonusSql = "select * into tempsc from temp2 ";
			$this->SQLup($BonusSql); //生成相应的表
			$this->DropView(); //删掉视图
			Db::commit();
			return suc('获取奖金本数据成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
	}
	//删除视图
	private function DropView() {
		$this->Drop_View('TEMP0'); //去掉表
		$this->Drop_View('TEMP1'); //去掉表
		$this->Drop_View('TEMP2'); //去掉表
		$this->Drop_Table1('tempdebt');
	}
	//删除视图操作
	private function Drop_View($view) {
		$sql = "if exists(Select * from sysobjects where Name='" . $view . "')
				drop view " . $view;
		$this->Exc_Sql($sql);
	}
	//完善表tempsc
	private function PerfectTable() {
		$perfect = "update tempsc set sc=店铺,Bonus=0 where sc is null";
		$this->SQLup($perfect);

		$perfect = "update tempsc set 店铺=sc,Sc_Bonus=0 where 店铺 is null";
		$this->SQLup($perfect);

		$perfect = "ALTER table tempsc ADD Total decimal DEFAULT 0";
		$this->SQLup($perfect);

		$perfect = "update tempsc set Total=Bonus+Sc_Bonus";
		$this->SQLup($perfect);

		$sql = "ALTER table tempsc ADD Debt decimal DEFAULT 0";
		$this->SQLup($sql);

		$sql = "ALTER table tempsc ADD Remain decimal DEFAULT 0";
		$this->SQLup($sql);

	}

// 第三步 将各店的店费录入表tempsc中
	/**
	 * @title 各店的店费录入
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function insert_tempsc() {
		Db::startTrans();
		try {
			$data = $this->button13_Click(); //获取店费表数据
			foreach ($data as $k => $v) {
				if ($v['Bonus'] == '' || $v['Bonus'] == null) {
					$v['Bonus'] = 0;
				}
				$sql = "UPDATE tempsc SET Sc_Bonus=" . $v['Bonus'] . " where sc=" . $v['SC'];
				$this->SQLup($sql);
			}
			$this->PerfectTable(); //完善表
			Db::commit();
			return suc('店费数据录入成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '录入失败,请重试或者联系Eirc!' . $e->getMessage());
		}
	}

//第四步 录入被动扣款总额数据
	/**
	 * @title 录入被动扣款总额数据
	 * @type interface
	 * @login 1
	 * @param from 起始时间
	 * @param to 截止时间
	 * @return data
	 */
	public function insert_debt_tempsc() {
		$rule = [
			'from' => 'require',
			'to' => 'require',
		];
		$msg = [
			'from' => '请选择计算月份的起始时间!',
			'to' => '请选择计算月份的截止时间!',
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$data = $this->get_debtinfo($this->input['from'], $this->input['to']);
			foreach ($data as $k => $v) {
				if ($v['amount'] == '' || $v['amount'] == NULL) {
					$v['amount'] = 0;
				}
				$sql = "UPDATE tempsc set Debt=" . $v['amount'] . " where sc=" . preg_replace('/^sc/i', '', $v['sc']);
				$this->SQLup($sql);
			}
			$sql = "update tempsc set Debt=0 where Debt is null";
			$this->SQLup($sql);
			////记录详细扣款信息到tempdebt表中
			$this->insert_data_tempdebt($this->input['from'], $this->input['to']);

			Db::commit();
			return suc('欠款总额数据录入成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '录入失败,请重试或者联系Eirc!' . $e->getMessage());
		}
	}
	//获取被动扣款信息
	private function get_debtinfo($from, $to) {
		$type_array = model('RefundBorrowType')->idname(['key' => 1]); //获取所有扣款类型和对应id
		foreach ($type_array as $key => $value) {
			$typeid[] = $key; //获取所有类型的id
		}
		$where['log_time'] = [['>=', $from], ['<=', $to]];
		$where['direction'] = 3; //代表被动扣款方向
		$where['type_id'] = ['in', $typeid];
		$field = "sc ,sum(amount) as amount";
		$data = model('RefundBorrowLog')->getgrouplist($field, $where, 'sc'); //返回查询时间段的扣款总额
		return $data;
	}
	//记录详细扣款信息到tempdebt表中
	private function insert_data_tempdebt($from, $to) {

		$this->Drop_Table1('tempdebt');
		$sql = " SELECT sc,Total,Debt into tempdebt FROM tempsc WHERE Total<Debt";
		$data = $this->SQLup($sql); //获取到奖金不足扣款的专卖店数据插入表tempdebt

		$sql = "ALTER table tempdebt ADD Surplus decimal DEFAULT 0";
		$this->SQLup($sql);

		$sql = "SELECT sc from tempdebt";
		$sc = $this->query_Sql($sql); //获取到奖金不足扣款的专卖店编号
		if (empty($sc)) {
			return true;
		}
		$type_array = model('RefundBorrowType')->getlistAC(['key' => 1], 'id,type_name,priority', 'priority asc');

		foreach ($type_array as $k => $v) {
			$sql = "ALTER table tempdebt ADD " . $v['type_name'] . " decimal DEFAULT 0";
			$this->SQLup($sql);
		} //完善表tempdebt

		$where['log_time'] = [['>=', $from], ['<=', $to]];
		$where['direction'] = 3; //代表被动扣款方向
		$field = "sum(amount) as amount";
		foreach ($sc as $kk => $vv) {
			$where['sc'] = 'SC' . $vv['sc'];
			foreach ($type_array as $key => $value) {
				$where['type_id'] = $value['id'];
				$amount = model('RefundBorrowLog')->getByWhereOne($where, $field);
				$amount = $amount['amount'] ?: 0;
				$sql = "UPDATE tempdebt set " . $value['type_name'] . "=" . $amount . " where sc=" . $vv['sc'];
				$this->SQLup($sql);
			}
		}
	}
//第五步统计Remain余额
	/**
	 * @title 统计Remain余额
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function insert_remain_tempsc() {
		Db::startTrans();
		try {

			$sql = "UPDATE tempsc set Remain=Total-Debt";
			$this->SQLup($sql);

			$sql = "UPDATE tempsc set Remain=0 WHERE Total<Debt";
			$this->SQLup($sql);
			Db::commit();
			return suc('统计余额成功!');
		} catch (Exception $e) {
			return err(9000, '操作失败,请重试!' . $e->getMessage());
		}
	}
//第六步 从表tempsc导出各店奖金和数据
	/**
	 * @title 导出各店奖金和
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function fresh() {
		Db::startTrans();
		try {
			$sql = "select sc as SC,Bonus,Sc_Bonus,Total,Debt,Remain from tempsc where sc like'___' order by sc";
			$data = $this->query_Sql($sql);
			Db::commit();
			return suc($data);
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '获取数据失败,请重新操作或者联系Eirc!');
		}
	}
//第七步 导出奖金不够扣的装卖店数据
	/**
	 * @title 导出奖金不足的装卖店
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function insufficient() {
		Db::startTrans();
		try {
			$sql = "SELECT * from tempdebt";
			$data = $this->query_Sql($sql);
			Db::commit();
			return suc($data);
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '获取数据失败,请重新操作或者联系Eirc!');
		}
	}

// 第八步 为奖金不足扣款的专卖店生成新的一条借款记录
	/**
	 * @title 为奖金不足的装卖店生成欠款记录
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function insert_borrow_data() {
		Db::startTrans();
		try {
			//先获取还款优先级顺序
			$type_array = model('RefundBorrowType')->getlistAC(['key' => 1], 'id,type_name,priority', 'priority asc');
			$sql = "update tempdebt set Surplus=Total";
			$this->SQLup($sql); //添加字段用来计算奖金不够扣除的部分

			$sql = "SELECT sc from tempdebt";
			$sc = $this->query_Sql($sql); //获取到奖金不足扣款的专卖店编号

			//将够扣除的奖金部分数据重置为0
			$array = []; //用来获取生成借款记录的数据
			foreach ($sc as $v) {
				foreach ($type_array as $kk => $vv) {
					$re = [];
					$sql = "SELECT Surplus," . $vv['type_name'] . " from tempdebt where sc='" . $v['sc'] . "'";
					$data = $this->query_Sql($sql);
					$data = $data[0];
					if ($data['Surplus'] >= $data[$vv['type_name']]) {
						$Surplus = $data['Surplus'] - $data[$vv['type_name']];
						$amount = 0;
					} else {
						$Surplus = 0;
						$amount = $data[$vv['type_name']] - $data['Surplus'];
						$re['type_id'] = $vv['id'];
						$re['amount'] = $amount;
						$re['sc'] = 'SC' . $v['sc'];
					}
					$sql = "UPDATE tempdebt set Surplus=" . $Surplus . "," . $vv['type_name'] . "=" . $amount . " where sc='" . $v['sc'] . "'";
					$this->SQLup($sql);
					if (!empty($re)) {
						$re['memo'] = $vv['type_name'] . '扣款所需奖金金额不足!';
						$re['direction'] = 1;
						$re['oper'] = $this->TellerOper;
						$re['log_time'] = date('Y-m-d');
						$array[] = $re;
					}
				}
			}
			//var_export($array);
			foreach ($array as $v) {
				$b = model('RefundBorrowLog')->add($v);
				if ($b['stat'] != 1) {
					throw new Exception("向表RefundBorrow中添加记录失败!", 4);
				}
			}
			Db::commit();
			return suc('生成记录成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}
	}
}