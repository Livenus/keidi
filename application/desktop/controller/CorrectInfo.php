<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class CorrectInfo extends Common {

public $OldContent = "";
public $NewContent = "";
public $Table = ["系统上传Teller", "手动确认Teller", "确认后Teller"];
public $field = ["金额", "专卖店", "银行", "确认人", "备注"];
public $bank = ["ACCESS",
 "UNION",
 "ZENITH",
 "FIRST",
 "SKYE",
 "KBANK",
 "A-L",
 "Z-L",
 "ECO",
 "GT",
 "G-L"];
public $Name = "CorrectInfo";
//初始化
public function _initialize() {
parent::_initialize();
$this->input = input("post.");

}
public function load(){
$data['Table'] = $this->Table;
$data['field'] = $this->field;
$data['bank'] = $this->bank;
return suc($data);
}
public function Search()
{
if ($this->input['Table'] == 2)
$sql = $this->GetDataTable() . " where tellerdetailid=" .$this->input['ID'];
else
$sql = $this->GetDataTable ()." where tellerid=".$this->input['ID'];
$data['list'] = $this->query_Sql($sql);
if($data['list']){
$data['list'] = utf_8($data['list']);
}
$data['input'] = $this->input;
return suc($data);
}
private function GetDataTable()
{
if ($this->input['Table'] == 0)
{
$this->input['tablespace'] = "Tb_systemteller";
return "select TellerID as [ID],Deposit as 数额,bank_name as Bank,确认日期,专卖店,确认,'' as POS from tb_systemteller";
}
else if ($this->input['Table'] == 1)
{
$this->input['tablespace'] = "Tb_tempteller";
return "select TellerID as [ID],Deposit as 数额,bank_name as Bank,确认日期,专卖店,确认,Memo as POS from tb_tempteller";
}
else
{
$this->input['tablespace'] = "FD_TellerDetail";
return "select TellerdetailID as [ID],Deposit as 数额,Bank,Date as 确认日期,ShopNo as 专卖店,Region,用途 from fd_tellerdetail";
}
}
public function button2_Click()
{
$rule = [
"Formid" => "require|number"
];
$check = $this->validate($this->input, $rule);
if($check !== true){
return err(9000, $check);
}
if ($this->CheckIfLocked($this->input['Formid'])){
$data = $this->Unlock();
return $data;
}
else return err(9000, "该编号的teller未锁定，无需解锁！");
}
private function Unlock()
{
$sql = "update tb_systemteller set lockbeforeconfirm='UnLock' where tellerid='".$this->input['Formid']."'";
$status = $this->Exc_Sql($sql);
if ($status > 0)
return suc("解锁成功！");
else return err(9000, "解锁失败！Sql:".$sql );
}
private function CheckIfLocked($tellerid)
{
$sql = "select lockbeforeconfirm from tb_systemteller where tellerid='".$tellerid."'";
$TID = $this->GetStringData($sql);
if (strpos($TID, "LOCKED") === false)
return false;
else return true;

}
public function button4_Click()
{
$rule = [
"ID" => "require|number",
 "Table" => "require|number"
];
$check = $this->validate($this->input, $rule);
if($check !== true){
return err(9000, $check);
}
if (trim(self::$realname) == "管理员"&&(strlen($this->input['shopno']) >0||$this->input['Table'] == 0))
{
if ($this->input['Permit'] == "70599")
{
if ($this->CheckIfItCanDelete() ||$this->CheckIfGotoFD_Teller($this->input['ID'], $this->input['Table']))
{
Db::startTrans();
try {
if ($this->input['Table'] == 0){
$data = $this->DeleteSystemTellerRecord($this->input['ID']);
}
else if ($this->input['Table'] == 1)
$data = $this->DeleteTempTellerRecord($this->input['ID']);
else
$data = $this->DeleteTellerDetailRecord($this->input['ID']);
Db::commit();
return suc($data);
} catch (\Exception $exc) {
Db::rollback();
return err(9000, $exc->getMessage());
}



}
else return err(9000, "不能删除该记录，否则余额为负！");
}
else return err(9000, "请输入许可码！");
}
else return err(9000, "您无权进行删除操作，请联系Eric！");
}
private function CheckIfItCanDelete()
{
$sql = "select banlance from fd_teller where tellerid=(select tellerid from fd_tellerdetail where tellerdetailid=".$this->input['ID'].")";
$balance = $this->GetStringData($sql);
$sql = "select isnull(deposit,0) as deposit from fd_tellerdetail where tellerdetailid=" .$this->input['ID'];
$deposit = $this->GetStringData($sql);
if ($balance >= $deposit)
return true;
else return false;
}
private function CheckIfGotoFD_Teller($systemtellerID, $selectIndex){
$usedfor = "";
$sql = "";
$result;
if ($selectIndex == 0)
$usedfor = "系统";
else if($selectIndex == 1)
$usedfor = "临时";
if ($selectIndex == 2)
$sql = "select count(*) as c from fd_tellerDetail where  TellerDetailID=" . $systemtellerID;
else
$sql = "select count(*) as c from fd_tellerDetail where  systemtellerID=".$systemtellerID ." and 用途='".$selectIndex."' ";
$result = $this->GetStringData($sql);
if ((int) $result == "0")
return true;
else return false;
}
private function DeleteSystemTellerRecord($TellerID)
{
$sql = "";
$old = "";
$sql = "delete from tb_systemTeller where tellerid='" .$TellerID. "'";
$status = $this->Exc_Sql($sql);
if ($status <= 0) exception("删除失败！");
else
{
$sql = "select count(*) as c from tb_systemTeller where TellerID='" .$TellerID. "'";
$old = $this->GetStringData($sql);
return suc("删除成功");
$this->RecordInLog("删除system表" . $old . "记录", str_ireplace("'", "''", $sql));
}
}
private function RecordInLog($oldinfo, $newinfo)
{
$logsql = "insert kedilog(LogId,Oldcontent,newcontent,recorddate,oper,recordip,recordmac,software,memo) ".
"values(" .$this->GetNewLogid() . ",'" .$oldinfo. "','" .$newinfo. "','" .date('Y-m-d H:i:s'). "','" .self::$realname. "','". $this->Local_IP() . "','" .$this->Local_Mac() . "','科迪账户管理中心','" . $this->Name . "')";
$this->Exc_Sql($logsql);
}
private function GetNewLogid()
{
$logid = "";
$sql = "";
$sql = "select isnull(max(logid),0)+1 from kedilog";
$logid = $this->GetStringData($sql);
return $logid;
}
private function DeleteTempTellerRecord($TellerID)
{
$sql = "";
$old = "";
$sql = "delete from tb_TempTeller where tellerid='" .$TellerID. "'";
$status = $this->Exc_Sql($sql);
if ($status < 0) exception("删除失败！");
else
{
$sql = "select count(*) as c from tb_TempTeller where TellerID='" .$TellerID. "'";
$old = $this->GetStringData($sql);
return suc("删除成功");
$this->RecordInLog("删除Temp表" .$old . "记录", str_ireplace("'", "''", $sql));
}
}
private function DeleteTellerDetailRecord($TellerdetailID)
{
$sql = "";
$old = "";
$sql = "delete from fd_tellerdetail where tellerdetailid='".$TellerdetailID. "'";
$status = $this->Exc_Sql($sql);
if ($status < 0) exception("删除失败！");
else
{
$this->ProcessChangeTeller(trim($this->input['shopno']));
$sql = "select count(*) from fd_tellerdetail where tellerdetailid='" .$TellerdetailID. "'";
$old = $this->GetStringData($sql);
return suc("删除成功");
$this->RecordInLog("删除detail表" .$old . "记录", str_ireplace("'", "''", $sql));
}
}
public function button3_Click()
{
    $rule = [
"ID" => "require|number",
 "Table" => "require|number",
 "shopno" => "require|number",
 "Formid" => "require|number",
 "bank" => "require",
 "bank_date" => "require",
 "regin" => "require",
];
$check = $this->validate($this->input, $rule);
if($check !== true){
return err(9000, $check);
}
if ($this->input['Table'] != 2 ||!$this->CheckIfTellerUsed())
{
try {
$data = $this->UpdateTellerInfo();
return suc($data);
} catch (\Exception $exc) {
return err(9000, $exc->getMessage());
}


}
else return err(9000, "该Teller已经使用，无法更改区域！");

}
private function CheckIfTellerUsed()
{
$tellerMoney = 0;
$AccountBanlanc = 0;
$sql = "";
$result = "";
$sql = "select banlance from fd_teller where shopno='".$this->input['shopno']."'";
$result = $this->GetStringData($sql );
$AccountBanlanc = (int) $result;
$sql = "select deposit from fd_tellerdetail where tellerdetailid='".$this->input['Formid']. "'";
$result = $this->GetStringData($sql);
$tellerMoney = (int) $result;
if ($AccountBanlanc > $tellerMoney)
return false;
else return true;
}
private function UpdateTellerInfo()
{
$OldContent = $this->GetLogContent();
$Update = "";
if($this->input['Table'] == 0)
$Update = "Update tb_systemteller set Bank_name='".$this->input['bank']. "',确认日期='" .$this->input['bank_date']. "' where tellerid='" .$this->input['Formid']. "'";
if ($this->input['Table'] == 1)
$Update = "Update tb_tempteller set Bank_name='".$this->input['bank']."',确认日期='" .$this->input['bank_date']. "',memo='" .$this->input['pos']. "' where tellerid='" .$this->input['Formid']. "'";
if ($this->input['Table'] == 2)
$Update = "Update tb_tempteller set Bank_name='" .$this->input['bank']. "',REGION='" .$this->input['regin']. "' where tellerid='" .$this->input['Formid']. "'";
$status = $this->Exc_Sql($Update);
if ($status > 0)
{ 
$NewContent = $this->GetLogContent();
$this->RecordInLog($OldContent, $NewContent );
return suc("更新成功！");
}
else
return err("更新失败！");
}
private function GetLogContent()
{
$sql = "";
if($this->input['Table'] == 2)
$sql = "select * from ".$this->input['tablespace']. " where tellerdetailid='" .$this->input['Formid'] . "'";
else
$sql = "select * from ".$this->input['tablespace']. " where tellerid='".$this->input['Formid'] . "'";
$data = $this->GetAllStringData($sql);
return $data;
}
private function GetAllStringData($sql)
{
$result = "";
$data = $this->query_Sql($sql);
$data = current($data);
foreach($data as $v){
$result .= $v.";";
;
}

}
}
