<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
class DistributorTeller extends Common {
    public $GID;
    public $realdate;
    public $oper_name;
    public $ShopNO;
    public $TellarMoney;
    public $CashMoney;
    public $TellerID_Delete = "";
    public $TellerType = "业绩销售";
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->FrontDeskCash = model("FrontDeskCash");
        $this->SaleEnteredByFront = model("SaleEnteredByFront");
        $this->TellerAccountReg = model("TellerAccountReg");
        $this->FDTellerdetail = model("FDTellerDetail");
        $this->FDTeller = model("FDTeller");
        $this->input = input("post.");
        self::$FormMark="";
    }


    public function DistributorTeller_Load()
        {
            $this->ProcessChangeTeller($this->input["SC"]);
            $data["Get_Tellar_Info"]=$this->Get_Tellar_Info();
            $data["Show_TellarTotal"]=$this->Show_TellarTotal();
            $this->GetRegPswfromSc();
            $this->GetTellerType();
            $data['input']=$this->input;
            return suc($data);
        }
        private function GetYearMonth()
        {
            return true;


        }
        private function Get_Tellar_Info()
        {
        $detail=$this->FDTellerdetail->getByWhere(["GroupID"=>$this->input ["GroupID"]]);
        return $detail;
        }
        private function Show_TellarTotal()
        {
        $total=$this->FDTellerdetail->sum(["GroupID"=>$this->input ["GroupID"]]);
        $this->input["Teller"]=$total;
         return $total;
        }
        private function GetRegPswfromSc()
        {
            try
            {
                $sql = "select password from telleraccountreg where regserviceno='" .$this->input ["SS"]. "'";
                $this->input["RegPsw"] = $this->GetStringData($sql);
            }
            catch (\Exception $ee)
            {
               

            }
        }
        private function  GetTellerType() 
        {
            if (self::$FormMark == "NewBorrow")
                $TellerType = "借货押金";
            else
                if (self::$FormMark == "UpdateReport")
                {
                    $TellerType = "业绩销售";

                }
                else if (self::$FormMark  == "UpdateBigSCReport")
                {
                    $TellerType = "业绩销售";

                }
                else if (self::$FormMark == "Material")
                {
                    $TellerType = "资料销售";


                }
                else
                {
                    $TellerType = "业绩销售";

                }
                $this->TellerType=$TellerType;
                return $TellerType;
        }
        public function search()
        {
        $rule=[
            "KEDI_Code"=>"require",
        ];
        $check=$this->validate($this->input, $rule);
        if($check!==true){
            return err(9000,$check);
        }

               $this->input["SC"] =$this->input["KEDI_Code"];
                $this->GetRegionMoney();
                $status=$this->Show_Distri_Name();
                if(isset($status['stat'])){
                    return $status;
                }
                $data['input']=$this->input;
                return suc($data);
            
        }
        private function Show_Distri_Name()
        {
            if(strlen($this->input['KEDI_Code'])<=3){
                return true;
            }
            $Name = "None";
                try
                {
                    $sql = "select customername from tb_customerinfo where customerid=(select customerid from tb_customer where customerno='" .$this->input["KEDI_Code"]. "')";

                    $Name =$this->GetStringData($sql);
                    if ($Name == "")
                        $Name = "None";
                    $this->input["DIS_NAME"]= $Name;
                }
                catch (\Exception $ee)
                {
                        return err(3000,"此人未注册，不是科迪会员！");
                }
            
        }
        private function GetRegionMoney(){
                           $RegionMoney=$this->FDTellerdetail->GetRegionMoney($this->input["SC"],$this->input["Region"]);
                           $banlance=$this->Get_RealBalance($this->input["SC"]);
                           if($banlance<$RegionMoney){
                               $this->input['Balance']=$banlance;
                           }else{
                               $this->input['Balance']=$RegionMoney;
                           }
        }
        private function Get_RealBalance($sc)
        {
            $banlance=$this->FDTeller->getByWhereOne(["ShopNO"=>$sc],"banlance");
            return $banlance["banlance"];
        }
}
