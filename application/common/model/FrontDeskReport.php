<?php

namespace app\common\model;

use app\common\model\Base;

class FrontDeskReport extends Base {

    protected $table = 'FrontDesk_Report';
    protected $pk = 'GroupID';

    public function add($data) {
        $res = $this->insert($data);
        if($res===0&&$this->getCount($data)){
            return suc("新增成功");
        }
        return err(2000,"新增失败");
    }

    public function GetKitsAfterdeudct($GID, $showkit) {
        $sql = "select top 1 isnull(deductborrowkits,0) as kit from frontdesk_report where groupid='" . $GID . "' or regionno='" . $GID . "'";
        $data = $this->query($sql);
        $kit = $showkit - $data[0]["kit"];
        return $kit;
    }

    public function GetKitsLanguage($GID, $showkit) {
        $sql = "select kitslanguage from frontdesk_report where groupid='" . $GID . "' or regionno='" . $GID . "'";
        $data = $this->query($sql);
        $kit = $showkit - $data[0]["kitslanguage"];
        return $kit;
    }

    public function SetWareHouse($mark, $GroupID) {
        $data["WarehouseMark"] = $mark;
        return $this->editById($data, $GroupID);
    }

    public function Update_FrontDesk_Report($GroupID, $status, $CashMoney, $Oper_name, $Tellarmoney, $DebitMoney, $Disc_Trsfer, $Cash_USD, $Realdate, $ShopNo, $Area, $Kits, $ScBV, $ScPV, $ScNaira, $KitsValue, $TellerPasswordStatus,$GetKitsLanguage="") {
        $data["KitsLanguage"] =$GetKitsLanguage?$GetKitsLanguage: $this->GetKitsLanguage($GroupID, $Kits);
        $data["ReportStatus"] = $status;
        $data["CashMoney"] = $CashMoney;
        $data["Oper_Name"] = $Oper_name;
        $data["TellarMoney"] = $Tellarmoney;
        $data["DebitMoney"] = $DebitMoney;
        $data["Disc_Trsfer"] = $Disc_Trsfer;
        $data["Cash_USD"] = $Cash_USD;
        $data["Realdate"] = $Realdate;
        $data["ShopNo"] = $ShopNo;
        $data["Area"] = $Area;
        $data["Kits"] = $Kits;
        $data["ScBV"] = $ScBV;
        $data["ScPV"] = $ScPV;
        $data["ScNaira"] = $ScNaira;
        $data["ReportType"] = "SC";
        $data["BelongedSC"] = "None";
        $data["TotalMoney"] = $ScNaira + $Kits * $KitsValue;
        $data["Teller_Oper"] = $TellerPasswordStatus;
        $status = $this->editById($data, $GroupID);
        return $status;
    }
    public function Activate_FD_Sale($groupid, $oper_name, $realdate, $ReportType) {
        $data["ReportType"] = $ReportType;
        $where["GroupID"] = $groupid;
        $where["Oper_Name"] = $oper_name;
        $where["RealDate"] = $realdate;
        return $this->editByMap($data, $where);
    }

    public function Get_ReportDetail_Mem_Info($Area, $Realdate) {
        $field = "shopno as SC,reportstatus as Status,kits,totalmoney as Total,cashmoney as Cash,tellarmoney as Teller,debitmoney as Debit,disc_trsfer as Trans,area as Region,scbv as BV,scpv as PV,scnaira as NR,oper_name as Oper,groupid as ID,Cash_USD,WarehouseMark as WM";
        $where["ReportType"] = "SC";
        $where["Area"] = $Area;
        $where["Realdate"] = $Realdate;
        return $this->getByWhere($where, $field);
    }

    public function Get_ReportDetail_ForRegional($Area, $Realdate) {
        $field = "sum(Kits) as Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer,sum(cash_USD) as cash_USD,sum(scbv) as scbv,sum(scpv) as scpv,sum(scnaira) as scnaira";
        $where["ReportType"] = "SC";
        $where["Area"] = $Area;
        $where["Realdate"] = $Realdate;
        return $this->getByWhereOne($where, $field);
    }

    public function CheckIfDiff($Area, $Realdate) {
        $sql = "select A.GroupID,Sale,Report,Report-sale as Different from (select groupid,sum(total_Naira) as Sale from tb_sale_entered_byfrontdesk where Groupid in(select groupid from FrontDesk_Report where area='" . $Area . "') and realdate='" . $Realdate . "' group by groupid) A,"
                . " (select groupid,sum(scnaira) as Report from FrontDesk_Report where ReportType='SC' and area='" . $Area . "'  and  realdate>='" . $Realdate . "'and realdate<='" . $Realdate . "' group by groupid) B where a.GroupID=b.GroupID and sale<>report";
        $data = $this->query($sql);
        if (count($data) > 0) {
            write("货单检测" . serialize($data));
            return false;
        }

        return true;
    }

    public function remove($saletype, $groupid) {
        $sql = "delete from stockoutdetail where stockoutid in(select stockoutid from stockout where saletype='" . $saletype . "'and groupid='" . $groupid . "')";
        $sql1 = "delete from stockoutinfo where stockoutid in(select stockoutid from stockout where saletype='" . $saletype . "'and groupid='" . $groupid . "')";
        $sql2 = "delete from stockout where saletype='" . $saletype . "'and groupid='" . $groupid . "'";
        $status = $this->execute($sql);
        $status = $this->execute($sql1);
        $status = $this->execute($sql2);
        return $status;
    }

    public function sum_shop($Shopno, $start, $end) {
        $field = "{$Shopno} as ShopNO,sum(totalmoney) as Acheive";
        $where["ReportType"] = "SC";
        $where["Realdate"] = ["between", "{$start},{$end}"];
        $where["ShopNo"] = $Shopno;
        $data = $this->getByWhere($where, $field);
        return $data;
    }

    public function sum_shop_all($start, $end) {
        $field = "ShopNo,sum(totalmoney) as Acheive";
        $where["ReportType"] = "SC";
        $where["Realdate"] = ["between", "{$start},{$end}"];
        $data = $this->where($where)->field($field)->group("ShopNo")->order("ShopNo asc")->select();
        return $data;
    }

    public function AcheiveMentNow($start, $end, $KitsValue) {
        $field = "sum(kits) as kits,sum(scpv)as scpv,sum(tellarmoney)as tellarmoney,sum(cashmoney)as cashmoney,sum(debitmoney)as debitmoney,sum(scpv)+sum(kits)*15 as scpv_all,sum(scnaira)+sum(kits)*" . $KitsValue . " as kits_all";
        $where["ReportType"] = "SC";
        $where["Realdate"] = ["between", "{$start},{$end}"];
        $data = $this->getByWhereOne($where, $field);

        return $data;
    }
    public function UpdateReportDifferent($groupid){
        
    }
}
