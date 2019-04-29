<?php

namespace app\desktop\controller;

use think\Controller;
use think\Db;
use think\File;
use think\Request;

class Common extends Basic {

	static protected $usertoken;
	static protected $user;
	static protected $userID;
	static protected $realname;
	public static $KitsValue = "4000";
	public static $DataBaseName = "fzkd1";
	public static $PringRowCount = "39";
	public static $ConnectionSql1 = "server=211.149.198.76;database=fzkd1;uid=sa;pwd=onDlA9m0fMDKc3wnCfyi0OKro"; //注册时使用
	public static $FormMark = "", $versionid = "1", $FreshMark = "0";
	public static $AuthoritDataSet;
	public static $ProductNumber = "43";
	public static $Version = "";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->assign('usertoken', self::$usertoken);
		$Nconfig = Model("Nconfig")->getBywhereOne([]);
		self::$KitsValue = $Nconfig['Unit_Price'];
	}

	public function AddSaleTypeID() {
		$sql = "update stockout set saletypeid=(select stockTypeId from stockType where TypeName_e=stockout.saletype) where saletypeid is null";
		if ($this->Exc_Sql($sql) === false) {
			return err(3000, "Error sql:" . $sql, 1006);}
		$sql = "update stockout set saletypeid=(select stockTypeId from stockType where TypeName=stockout.saletype) where saletypeid is null";
		if ($this->Exc_Sql($sql) === false) {
			return err(3000, "Error sql:" . $sql, 1006);}
		return suc("添加成功");
	}
	//获取地区
	public function GetRegion() {
		$sql = "select region as result from regioninfo";
		return $this->query_Sql($sql);
	}
	//获取静态IP
	public static function Local_IP() {
		$request = request();
		return $request->ip();
	}
	//获取静态物理地址
	public static function Local_Mac() {
		$input = input("post.");
		$mac = isset($input['mac']) ? $input['mac'] : "";
		return $mac;
	}
	public function TellerLog($OperType, $SC, $Region, $Bank, $Amount, $OperTable, $MajorID, $ConfirmDate, $OperDate, $Oper, $SqlTxt) {
		$sql = "";
		$LogID = "";
		$sql = "select isnull(max(logid),0)+1 as logid from tellerLog";
		$LogID = $this->GetStringData($sql);
		$Amount = $Amount ? $Amount : 0;
		$sql = "insert tellerlog(LogId,操作类型,专卖店,区域,银行,数额,操作表,目标ID,确认日期,操作日期,操作人,MAC,IP,SqlTxt) "
		. "values(" . $LogID . ",'" . $OperType . "','" . $SC . "','" . $Region . "','" . $Bank . "'," . $Amount . ",'" . $OperTable . "','" . $MajorID . "','" . $ConfirmDate . "','" . $OperDate . "','" . $Oper . "','" . $this->Local_Mac() . "','" . $this->Local_IP() . "','" . str_ireplace("'", "", $SqlTxt) . "')";
		if ($this->Exc_Sql($sql) < 1) {
			exception("日志记录失败，sql:" . $sql);
		}

	}
	public function ProcessChangeTeller($UserAccount) {
		$sql = " update FD_Teller set Total=isnull((select sum(Deposit) from FD_tellerdetail where shopno='" . $UserAccount . "'and isnull(FreezeMark,0)<>1),0)," . "used=isnull((select sum(used) from FD_tellerdetail where shopno='" . $UserAccount . "'),0) where shopno='" . $UserAccount . "'";
		if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
			throw new \Exception($tatus);
		} else {
			$sql = "UPDATE  FD_TELLER SET BANLANCE=TOTAL-USED";
			if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
				throw new \Exception($tatus);
			} else {
				$sql = "update tb_systemteller set 使用='1' where 专卖店='" . $UserAccount . "' and 确认='1'";
				if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
					throw new \Exception($tatus);
				}

				$sql = "update tb_systemteller set 使用='0' where deposit<=(select banlance from FD_Teller" .
					" where shopno='" . $UserAccount . "') and 确认='1' and deposit>0 and 专卖店='" . $UserAccount . "'";
				if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
					throw new \Exception($tatus);
				} else {
					$sql = "update tb_tempteller set 使用='1' where 专卖店='" . $UserAccount . "' and 确认='1'";
					if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
						throw new \Exception($tatus);
					}

					$sql = "update tb_tempteller set 使用='0' where deposit<=(select banlance from FD_Teller" .
						" where shopno='" . $UserAccount . "') and 确认='1' and deposit>0 and 专卖店='" . $UserAccount . "'";
					if (!is_numeric($tatus = $this->Exc_Sql($sql))) {
						throw new \Exception($tatus);
					}

				}
			}
		}
	}
	public function CheckIfRegionLock($GroupID) {
		$sql = "select count(*)  as count from tb_regionlockrecord where lock='-1' and Groupid='" . $GroupID . "' ";
		$result = $this->GetStringData($sql);
		if ($result == "0") {
			return false;
		} else {
			return true;
		}

	}
	public function RecordInLog($OldContent, $NewContent, $windowname, $Oper) {

		$logsql = "insert kedilog(LogId,Oldcontent,newcontent,recorddate,oper,recordip,recordmac,software,memo) " .
		"values(" . $this->GetNewLogid() . ",'" . $OldContent . "','" . str_ireplace("'", "", $NewContent) . "','" . date('Y-m-d') . "','" . $Oper . "','" . $this->Local_IP() . "','" . $this->Local_Mac() . "','科迪Teller充值系统','" . $windowname . "')";
		return $this->Exc_Sql($logsql);
	}
	public function GetNewLogid() {
		$sql = "select isnull(max(logid),0)+1  as logid from kedilog";
		$logid = $this->GetStringData($sql);
		return $logid;
	}
	public function Exc_Sql($sql) {
		$data = model("Pp")->execute($sql);
		return $data;

	}
	public function query_Sql($sql) {
		$data = model("Pp")->query($sql);
		if (is_array($data)) {
			$data = trimarray($data);
		}

		return $data;
	}
	//执行sql语句并将数组中的第一个元素作为字符串类型返回
	public function GetStringData($sql) {
		$data = model("Pp")->query($sql);
		if (!is_array($data) | empty($data)) {
			return false;
		}
		$data = current($data); //返回数组中的第一个单元
		$data = current($data);
		return trim($data);
	}
	protected function ProccessNull($str) {
		if (trim($str) == "") {
			return "0";
		} else {
			return str_ireplace(",", "", $str);
		}

	}
	public function GetDSData($sql) {
		$data = model("Pp")->query($sql);
		return $data;
	}
	public function uplaod() {
		$file = request()->file('image');
		if ($file) {
			$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
			if ($info) {
				$files = "/uploads/" . $info->getSaveName();
				echo $files;

			} else {
				return err(9000, $file->getError());
			}
		}
	}
	public function uploadimg() {
		$file = request()->file('image');
		if ($file) {
			$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
			if ($info) {
				$files = "./uploads/" . $info->getSaveName();
				$str = file_get_contents($files);
				$str = "0x" . strtoupper(bin2hex($str));
				return suc($str);
			} else {
				return err(9000, $file->getError());
			}
		}
	}
	public function readimg() {
		$img = input("image");
		if (strlen($img) < 50) {
			return err(3000, "格式不正确");
		}
		$img = hex2bin(substr($img, 2));
		$file = "./uploads/image/" . date('ymdhis') . ".jpg";
		if (!is_dir(dirname($file))) {
			mkdir(dirname($file), true);
		}
		file_put_contents($file, $img);
		return suc(substr($file, 1));
	}
	public function limit($order) {
		$limit = "order by {$order} OFFSET  {$this->input['OFFSET']} ROWS
FETCH NEXT {$this->input['ROWS']} ROWS ONLY ";
		return $limit;
	}
	public function get_num($sql) {
		$sql = preg_replace("/select.*?from/", "select count(*) as c from", $sql);
		$num = $this->GetStringData($sql);
		return $num;
	}
	protected function Drop_Table1($TableName) {
		try {
			$sql = "if exists (select * from dbo.sysobjects where id = object_id(N'" . $TableName . "') and OBJECTPROPERTY(id, N'IsUserTable') = 1)	drop table " . $TableName;
			$this->Exc_Sql($sql);
			Db::commit();
			return true;
		} catch (Exception $e) {
			Db::rollback();
			return false;
		}
	}
	public function CheckIfStockClose($dt, $stockname) {
		$sql = "select max(date)+1 from stockdaily where stockname='" . $stockname . "'";
		$UnProccessDate = $this->GetStringData($sql);
		$datet = $this->GetDate($UnProccessDate);
		if ($datet > $dt) {
			return true;
		} else {
			return false;
		}

	}
	public function GetDate($GetDate = "") {
		if ($GetDate) {
			return $GetDate;
		}
		return date("Y-m-d H:i:s");
	}
	//传入数字类型的月份获取英文简称月份
	public function GetMonthByNumber($number) {
		if (!is_numeric($number)) {
			return false;
		}
		$number = $number - 1;
		$Month_array = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
		$Month = isset($Month_array[$number]) ? $Month_array[$number] : false;
		return $Month;
	}
	public function get_time() {
		return suc($this->GetDate());
	}

	//获取权限验证码
	public function GetTempPsw($username) {
		$sql = "select psw from temppsw where username='" . $username . "' and systemname='Teller充值系统'";
		$psw = $this->GetStringData($sql);
		if ($psw) {
			return $psw;
		} else {
			return err(9000, '请设置权限验证码');
		}

	}
	//读取xls表格数据
	public function get_data($filename) {
		require_once EXTEND_PATH . 'PHPExcel/PHPExcel.php';
		require_once EXTEND_PATH . "PHPExcel/PHPExcel/IOFactory.php";
		$type = \PHPExcel_IOFactory::identify($filename);
		$objReader = \PHPExcel_IOFactory::createReader($type);
		$objPHPExcel = $objReader->load($filename);
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			$worksheets[$worksheet->getTitle()] = $worksheet->toArray();
		}

		$datas = array_shift($worksheets);
		return $datas;
	}
	//上传电子表格保存并返回文件名称
	public function uploads($upfile) {
		if (request()->isPost()) {
			//获取表单上传文件
			$file = request()->file($upfile);
			var_export($file);die();
			if (empty($file)) {
				return err(9000, '请选择上传文件');
			}
			//上传验证后缀名,以及上传之后移动的地址
			$path = ROOT_PATH . 'public' . DS . 'uploads';
			$type = ['xlsx', 'xlsm', 'xltx', 'xltm', 'xls', 'xlt', 'xml'];
			$info = $file->validate(['size' => 1567118, 'ext' => $type])->move($path);
			if ($info) {
				//echo $info->getFilename();
				$exclePath = $info->getSaveName(); //获取文件名
				$file_name = $path . DS . $exclePath; //上传文件的地址
				return $file_name;
			} else {
				return err(9000, $file->getError());
			}
		} else {
			return err(9000, '上传文件异常,请重新上传!');
		}
	}
	//以下是 核销以及调整和POS台账部分代码
	//核销以及调整 搜索(POS台账 搜索)执行查询语句
	protected function SearchResult($TableName) {
		if ($this->input['TellerID'] == "") {
			$sql = "select  * from " . $TableName . " where 1=1 " . $this->GetDateCondition() . $this->GetfromInfo() . $this->GetWhereInfo() . $this->GetMarkInfo();
		} else {
			$sql = "select *  from " . $TableName . " where " . $this->GetDateCondition() . " tellerid=" . $this->input['TellerID'] . $this->GetfromInfo() . $this->GetWhereInfo() . $this->GetMarkInfo();
		}

		$data = $this->query_Sql($sql);
		return $data;
	}
	//拼接日期下拉选择框的查询条件
	protected function GetDateCondition() {
		if ($this->input['comboBox2'] == 0) {
			return "";
		} else {
			return " and " . $this->comboBox2[$this->input['comboBox2']] . ">='" . $this->input['from'] . "' and " . $this->comboBox2[$this->input['comboBox2']] . "<='" . $this->input['to'] . "' ";
		}

	}
	//拼接勾选系统和手动的查询条件
	protected function GetfromInfo() {
		if ($this->input['system'] && $this->input['hand']) {
			return "";
		} else if ($this->input['system']) {
			return " and [from]='系统上传'";
		} else {
			return " and [from]='手动确认'";
		}
	}
	//拼接勾选标记的查询条件
	protected function GetMarkInfo() {
		if ($this->input['mark']) {
			return "";
		} else {
			return " and isnull(mark,0)='0'";
		}

	}
	//拼接填写了 借方下拉选择框旁边的输入框的查询条件
	protected function GetWhereInfo() {
		if ($this->input['textBox1'] == "") {
			return "";
		} else {
			return " and " . $this->comboBox1[$this->input['comboBox1']] . " like'%" . $this->input['textBox1'] . "%'";
		}
	}
	//计算借方和贷方总额
	protected function GetTotal($data) {
		$tobal1 = 0;
		$tobal2 = 0;
		foreach ($data as $v) {
			$vv = array_values($v);
			$tobal1 += $vv[8];
			$tobal2 += $vv[9];
		}
		$this->input['debt'] = $tobal1; //借方总额
		$this->input['credit'] = $tobal2; //贷方总额
	}
	//执行去标记或者标记
	protected function Mark($mark, $tellerid, $direct, $TableName) {

		$sql = "update " . $TableName . " set mark='" . $mark . "' where tellerid='" . $tellerid . "' and 方向='" . $direct . "'";
		$this->Exc_Sql($sql);

	}
	//获取客户端IP
	protected function getIp() {
		$request = Request::instance();
		$ip = $request->ip();
		return $ip;
	}
	//获取服务器端IP
	protected function getSeverIp() {
		$ip = $_SEVER['SEVER_NAME'];
		return $ip;
	}
	//获取当前模块,控制器,方法名
	protected function get_operation() {
		$model = Request()->module();
		$controller = Request()->controller();
		$action = Request()->action();
		$result = $model . '_' . $controller . '_' . $action;
		return $result;
	}
	//验证专卖店或者经销商编号是否符合要求
	protected function verifySC($sc) {
		$pattern = "/^sc\d{3}$|^kn\d{6}$/i";
		$result = preg_match($pattern, $sc);
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
}
