<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class MaterialMs extends Common {


        public $Bank =["ACCESS", "NEWFIRST",
            "OLDFIRST",
            "SKYE",
            "UNION",
            "ZENITH",
            "ALL"];
        public $CommissionID="";
         public $dataGridView1="";
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }
    public function LoadMaterial(){
        try {
           $data['Get_Material_Info']=$this->Get_Material_Info();
            $id=$this->GetNewFormID();
            if(isset($id['GetMaterialReport'])){
                $data['GetMaterialReport']=$id["GetMaterialReport"];
            }
            if(isset($id['MaxID'])){
                $this->input["ReportNO"]=$id["MaxID"];
            }
            $sc=$this->GetStringData("select buyer from materialForm where formid=" .$this->input["ReportNO"]);
            $this->input["SC_CARD"]= $sc;
            $this->input["Memo"]=$this->GetStringData("select memo from materialForm where formid=" .$this->input['ReportNO']);
            $this->input["GID"]= $this->GetStringData("select groupid from materialForm where formid=" .$this->input['ReportNO']);
            $this->input["Date"]= $this->GetStringData("select date from materialForm where formid=" .$this->input['ReportNO']);
            $data['input']=$this->input;
          return suc($data);
        } catch (\Exception $exc) {
            $data["err"]=$exc->getMessage();
            return suc($data);
        }


        
        
    }
        private function Get_Material_Info()
        {

            $sqlorder = "select MaterialNo as ITEM, Name AS Products,str(Price) as [U/Price NR],'       ' as Qty,'        ' as [Total NR]  from kediMaterial where status='1'and materialno like'zl%'or materialno='K01F' order by materialno";
         
            $data=$this->query_Sql($sqlorder);
            return $data;
        }//录入成功后显示在表单上
        private function GetNewFormID(){ 
            $sql = "select isnull(max(formID),0) as FID from materialForm where isnull(status,0)=0";
            $MaxID =$this->GetStringData($sql);
            if ($MaxID == 0)
            {
              
            $sql = "select isnull(max(formID),0)+1 as FID from materialForm";
            $MaxID = $this->GetStringData($sql);
            }
            else
            {
                
                $data["GetMaterialReport"]=$this->GetMaterialReport($MaxID);
            }
            $data["MaxID"]=$MaxID;
            return $data;
        }
        private function GetMaterialReport($FormID)
        {
            try
            {
                $sql = "select MaterialNo as ITEM, Name AS Products,str(Price) as [U/Price NR],'       ' as Qty,'        ' as [Total NR]  from kediMaterial where status='1'and materialno like'zl%' order by materialno";


                $data=$this->query_Sql($sql);
                $sql = "select MaterialID,Qty,Money from materialFormDetail where formid=" .$FormID;
                $data_2 = $this->query_Sql($sql);
                if (count($data_2)>= 1)
                {
                   
                        $materialid = "";
                       
                        foreach ($data_2 as $v)
                        {
                            $DSmid = $v["MaterialID"];
                            $DSqty = $v["Qty"];
                            $DSmoney =$v["Money"];
                            foreach ($data as $k=>$vv)
                            {
                                $materialid = $this->GetMID($vv["ITEM"]);
                              if ($DSmid == $materialid)
                               {
                                $data[$k]['Qty']= $DSqty;
                                $data[$k]['Total NR']= $DSmoney;
                                break;
                               }
                              }

                    }
                }
                return $data;
            }
            catch (\Exception $ee) { }
        }
        private function GetMID($MNO) 
        {
            $sql = "select materialid from kedimaterial where materialno='".$MNO."'";
            $MID = $this->GetStringData($sql);
            return $MID;
            
        }
        public function button2_Click()
        {
            $rule=[

                "SC_CARD"=>"require",
                "TotalMoney"=>"require"
                
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
            $this->GetGID($this->input['ReportNO']);
           

                
              
                $this->input['ReportNO']= $this->GetNewFormID();
                $count=$this->GetStringData("select count(*) from materialForm where formid=" .$this->input['ReportNO']);
                    if ($count== "0")
                    {
                       $this->input['SC_CARD.Enabled'] = false;
                        $this->input['Memo.Enabled']  = false;
                        $Get_Material_Info=$this->Get_Material_Info();
                        $this->dataGridView1=$Get_Material_Info;
                        $this->GetEachValue($Get_Material_Info);
                        $this->InsertToMaterilForm();
                        $data['url']=url('MaterialReport');
                        $data['input']=$this->input;
                        return suc($data);
                    }
                    else
                    {
                       
                        $this->Update($this->input['ReportNO']);
                        $this->input['SC_CARD']= $this->GetStringData("select buyer from materialForm where formid=" .$this->input['ReportNO']);
                       $this->input['Memo']= $this->GetStringData("select memo from materialForm where formid=" .$this->input['ReportNO']);
                       $this->input['GID']=$this->GetStringData("select groupid from materialForm where formid=" .$this->input['ReportNO']);
                        $data['url']=url('MaterialReport');
                        $data['input']=$this->input;
                        return suc($data);
                    }
                
  
        }
        private function  GetGID($FID)
        {
            $sql = "select groupid from materialForm where formid=".$FID;
            $GID = $this->GetStringData($sql);
            if($GID=="")
                $this->input['GID']= date('yMdhis')+ "-" .$this->input['SC_CARD'];
            else $this->input['GID']= $GID ;
            
          

        }
        private function GetEachValue($Get_Material_Info)
        {
            try
            {
                $total = 0;
               foreach($Get_Material_Info as $k=>$v)
                {   if($v['Qty']!=0)
                    $Get_Material_Info[$k]['Total NR'] =$v['U/Price N']*$v['Qty'];

                }
              foreach($Get_Material_Info as $v)
                {
                    $total += $v['Total NR'];
                }
                $this->input['TotalMoney']= $total;
            }
            catch (\Exception $ee) { }
        }
        private function InsertToMaterilForm()
        {

            $sql = "insert into materialForm(FormID,Buyer,Date,TotalMoney,teller,cash,operater,Memo,status,groupid) values(" .$this->input['ReportNO']. ",'"  .$this->input['SC_CARD']. "','" .$this->input['Date']. "'," .$this->input['TotalMoney'].  ",0,0,'" .self::$realname.  "','".$this->input['Memo']. "','0','".$this->input['GID']. "')";
         $status=$this->Exc_Sql($sql);
            if (!is_numeric($status))
                throw  new Exception("Error,contact with Eric!\n " .$sql);
            else { $this->InsertToMaterialFormDetail($this->input['ReportNO']); }
        }
        private function InsertToMaterialFormDetail($FormID)
        {
           foreach($this->dataGridView1 as $v)
            {
                $qty = "";$money="";$MaterialID="";
                $qty = $v['Qty'];
                if ((int)$qty!= 0)
                {
                    $money =$v['Total NR'];
                    $MaterialID = $this->GetMID($v['ITEM']);

                    $sql = "insert into MaterialFormDetail(DetailID,MaterialID,Qty,Money,FormID,Status,Operater) " .
                               "values(" .$this->GetNewDetailID(). ",".$MaterialID."," .$qty. "," .$money. "," .$FormID. ",'0','" .self::$realname."')";
                    $status=$this->Exc_Sql($sql);
                    if (!is_numeric($status))
                    {
                        throw  new \Exception("Error,contact with Eric!\n " .$sql);
                    }

                }
            }
        }
        private function GetNewDetailID()
        {
              $sql = "select isnull(max(DetailID),0)+1 as FID from MaterialFormDetail";
                $MaxID = $this->GetStringData($sql);
           
            return $MaxID;
        }
        private function Update($formid) 
        {
            $sql = "update materialForm set buyer='".$this->input['SC_CARD']."' where formid=".$formid ;
           $status=$this->Exc_Sql($sql);
            if (!is_numeric($status))
                throw  new \Exception("Error,contact with Eric!\n " .$sql);
            else
            {
                $sql = "delete from MaterialFormDetail where formid=" .$formid;
                           $status=$this->Exc_Sql($sql);
                if (!is_numeric($status))
                    throw  new \Exception("Error,contact with Eric!\n " .$sql);
                else
                    $this->InsertToMaterialFormDetail($formid); 
            }

        }
        public function timer1_Tick()
        {
            $this->dataGridView1=$this->Get_Material_Info();
            $this->GetEachValue($this->dataGridView1);
            $data['input']=$this->input;
            $data['dataGridView1']=$this->dataGridView1;
            return suc($data);
        }
        public function toolStripMenuItem2_Click()
        {
            try {
            $data['Get_Material_Info']=$this->Get_Material_Info();
            $this->input["ReportNO"] = $this->GetNewFormID();
            $this->input["SC_CARD"] = $this->GetStringData("select buyer from materialForm where formid=" .$this->input["ReportNO"]);
             $this->input["Memo"]=$this->GetStringData("select memo from materialForm where formid=" .$this->input["ReportNO"]);
             $this->input["GID"] = $this->GetStringData("select groupid from materialForm where formid=" .$this->input["ReportNO"]);
             $data['input']=$this->input;
             return suc($data);
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }

        }

        public function toolStripMenuItem3_Click()
        {
            try {
            if ($this->input["Date"] == date('Y-m-d') || self::$realname == "管理员")
               $data["Delete"]=$this->Delete($this->input["GID"]);
             else return err(9000,"The form you delete is not today's!");
             $data['input']=$this->input;
             return suc($data);
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }


        }
        private function Delete($Gid) 
        {
            $sql = "delete from materialForm where groupid='".$Gid."' and status='0'";
            $status=$this->Exc_Sql($sql);
            if (is_numeric($status))
            {
               
                    return suc("Delete it successfully!");
                  
               
            }
            else                exception("Please revert from warehouse before you delete it!");

        }  
}
