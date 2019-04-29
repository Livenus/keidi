<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class FrontDeskReport extends Common {

    public $GID;
    public $realdate;
    public $oper_name;
    public $ShopNO;
    public $TellarMoney;
    public $CashMoney;
    public $SaleType = "业绩销售";

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->FrontDeskReport = model("FrontDeskReport");
        $this->SaleEnteredByFront = model("SaleEnteredByFront");
    }

    //列表
    public function Get_ReportDetail_Mem_Info() {
        $input = input("post.");
        $data_report = $this->FrontDeskReport->Get_ReportDetail_Mem_Info($input["Area"], $input["Realdate"]);
        $data_report_sum = $this->FrontDeskReport->Get_ReportDetail_ForRegional($input["Area"], $input["Realdate"]);
        $check = $this->FrontDeskReport->CheckIfDiff($input["Area"], $input["Realdate"]);
        $data["data_report"] = $data_report;
        $data["data_report_sum"] = $data_report_sum;
        $data["check"] = $check;
        if ($data) {
            return suc($data);
        }
        return err(9000, "没有数据");
    }

    //详情
    public function RemoveToStock() {
        $input = input("post.");
        $data_report = $this->FrontDeskReport->remove($this->SaleType, $input["GroupID"]);
        $this->FrontDeskReport->SetWareHouse(0, $input["GroupID"]);
        return suc($data_report);
    }

    public function UpdateSale_FDSC() {
        $input = input("post.");
        $data["ShopNO"] = $input["newShopno"];
        $group = explode("-", $input["GroupID"]);
        $data["GroupID"] = $group[0] . "-" . $input["newShopno"];
        $where["GroupID"] = $input["GroupID"];
        $status = $this->SaleEnteredByFront->editByMap($data, $where);
        $data=array();
        $data["ShopNo"] = $input["newShopno"];
        $group = explode("-", $input["GroupID"]);
        $data["GroupID"] = $group[0] . "-" . $input["newShopno"];
        $where["GroupID"] = $input["GroupID"];
        $status = $this->FrontDeskReport->editByMap($data, $where);
        return $status;
    }

    //更新信息
    public function UpdateReportDifferent() {
        $input = input("post.");
        $status = $this->SaleEnteredByFront->UpdateReportDifferent($input["GroupID"],self::$KitsValue);
        return suc($status);
    }

    public function update() {
        $input = input("post.");
        $data = $this->FrontDeskReport->getBywhereOne(["GroupID" => $input["GroupID"]]);
        if (empty($data)) {
            return err(9000, "没有数据");
        }
        $status = $this->FrontDeskReport->Update_FrontDesk_Report($input["GroupID"], $input["ReportStatus"], $input["CashMoney"], self::$realname, $input["TellarMoney"], $input["DebitMoney"], $input["Disc_Trsfer"], $input["Cash_USD"], $data["Realdate"], $input["shopno"], $input["Area"], $input["Kits"], $data["ScBV"], $data["ScPV"], $data["ScNaira"], self::$KitsValue, "Normal");
        return $status;
    }

    public function search() {
        $input = input("post.");
        if(empty($input["shopno"])){
        $data = $this->FrontDeskReport->sum_shop_all($input["start"],$input["end"]);
        }else{
        $data = $this->FrontDeskReport->sum_shop($input["shopno"],$input["start"],$input["end"]);
        }
        return suc($data);
    }
    public function AcheiveMentNow(){
                $input = input("post.");
                $data = $this->FrontDeskReport->AcheiveMentNow($input["start"],$input["end"],self::$KitsValue);
                $rep["table_info"]=[
                   'kits'=>'加入数量',
                   'scpv'=>'美金',
                   'tellarmoney'=>'Teller(奈拉)',
                   'cashmoney'=>'现金(奈拉)',
                   'debitmoney'=>'欠款(奈拉)',
                   'scpv-all'=>'总业绩（美金含Kits）',
                   'kits-all'=>'总业绩（奈拉含Kits）',
           ];
                $rep["data"]=$data;
                return suc($rep);
    }

}
