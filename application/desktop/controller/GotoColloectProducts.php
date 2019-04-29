<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class GotoColloectProducts extends Common {
        private $SaleType = "DPBV";
        private $PrintIndex;     //当前页码
        private $dataIndex;      //打印开始的数据行
        private $Count=0;
        private $Count_AddColor = 0;
        private $If_Print = true ;
        private $DpBVNO = "";
        private $PrintMark = 0;
        private $pv = "0";
        private $bv = "0";
        private $naira = "0";
        private $mark = "0";
        private $ExchangeRate = 340;
        private $SubmitMark = "0";
        private $dataGridView1;
        //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        $this->TellerOper= self::$realname;
        
        
    }
    public function load(){
        $data['comboBLcondition']=$this->comboBLcondition;
        return suc($data);
    }
    public function Form2_Load()
        {
            $this->input['PrintForWarehouse']=$this->GetGroupID();
            $data['Get_Product_Info']=$this->Get_Product_Info();
            $this->dataGridView1=$data['Get_Product_Info'];
            $this->Set();
            $data['Get_Product_Info']=$this->Set_Qty_0($data['Get_Product_Info']);
            $this->LoadCollectedProducts();
            $data['Get_Product_Info']=$this->dataGridView1;
            $this->SetDpbvIfGotoWarehouseStatus();  
            $data['input']=$this->input;
            return suc($data);
        }
        private function Set_Qty_0($data)
        {
            foreach ($data as $k=>$v)
            {

                if ($v["ID"] == "M041")
                {
                    $data[$k]["ID"] = "M04";
                    $data[$k]["Name"] = "Blood Circulattory";
                   $data[$k]["BV"] = "500";
                    $data[$k]["PV"]  = "500";
                    $data[$k]["MemberPrice"]= "105000";
                    
                }
                if ($data[$k]["ID"] == "M051")
                {
                    $data[$k]["ID"]  = "M05";
                    $data[$k]["Name"] = "CAR&HOME USE MASSAGE CUSHION";
                     $data[$k]["BV"] = "375";
                     $data[$k]["PV"]  = "375";
                     $data[$k]["MemberPrice"]= "78750";
                }
                if ($data[$k]["ID"] == "M092")
                {
                    $data[$k]["ID"] = "M09";
                    $data[$k]["Name"] = "Massage Chair";
                     $data[$k]["BV"]  = "3000";
                     $data[$k]["PV"] = "3000";
                    $data[$k]["MemberPrice"]= "630000";
                }
               
            }
        return $data;
        }
        private function LoadCollectedProducts() 
        {
            $sql = "select productno,dpbv_productsdetail.amount as qty from dpbv_productsdetail,tb_product where saleid in"
                ." (select saleid from dpbv_products where groupid='".$this->input['PrintForWarehouse']."')and dpbv_productsdetail.productid=tb_product.productid";
            $ds = $this->query_Sql($sql);
            foreach ($this->dataGridView1 as $k=>$v)
            {
                foreach ($ds as $vv)
                {
                    if ($v["ID"] == $vv["productno"])
                    { $this->dataGridView1[$k]['Qty']= $vv['qty'];
                      break;
                    }

                }
            }
        }
         private function SetDpbvIfGotoWarehouseStatus()
         {
             if ($this->CheckIfWenttoStock())
             {
                $this->input['button3.BackColor']= 'Color.Red';
                 $this->input['button3.Enabled']=false;
                 $this->input['button3.Text']='WentToWarehouse';
                $this->input['button5.BackColor ']= 'Color.Green';
                 $this->input['button5.Enabled']=true;
                 $this->input['button5.Text']='RevertFromStcok';
             }
             else
             {
                $this->input['button3.BackColor']= 'Color.Green';
                 $this->input['button3.Enabled']=true;
                 $this->input['button3.Text']='WentToWarehouse';
                $this->input['button5.BackColor ']= 'Color.Red';
                 $this->input['button5.Enabled']=false;
                 $this->input['button5.Text']='RevertFromStcok';
             }
         }
         private function CheckIfWenttoStock()
         {
             $result = true;
             $sql = "select count(*) as c from stockoutdetail where stockoutid in(select stockoutid from stockout where groupid='" .$this->input['PrintForWarehouse']. "') ";
             $StockInfo = $this->GetStringData($sql);
             if ($StockInfo == "0")
                 $result = false;
             return $result;


         }
         public function Get_Product_Info()
        {
            $sqlorder = "select Productno as ID,cname as Name,str(memberprice) as MemberPrice,convert(decimal(10,1),PV) AS BV,convert(decimal(10,1),RETAILPRICE) AS PV,'  ' as Qty  from tb_product where (memberprice>0 AND status='1'and productno<>'M051') or productno='M05' ";
            $data=$this->query_Sql($sqlorder);
            $products= json_decode($this->input["products"],true);
            foreach($data as $k=>$v){
            if(isset($products[$v["ID"]])){
                    $data[$k]["Qty"]=$products[$v["ID"]];
                } 
            }

            return $data;
        }
        private function GetGroupID() 
        {
            $sql = "select groupid from dpbv_check where dpbvid='".$this->input['PrintForWarehouse']."'";
            $GID = $this->GetStringData($sql);
            if (strlen($GID) > 5)
                return $GID;
            else return date("Y-m-dH:i:s");
        }
        public function button1_Click()
        {
           $bv_Sum = 0; $pv_Sum = 0; $naira_Sum = 0;

           $data=$this->Get_Product_Info();
            foreach ($data as $v)
            {
                $qty = "";
                $vv= array_values($v);
                $qty = $vv[2];
                if ($this->ProccessNull($qty) != "0")
                {
                    $naira_Sum += $this->QTY_Fun($vv[2]) * $this->QTY_Fun($vv[5]);
                    $bv_Sum += $this->QTY_Fun($vv[3]) * $this->QTY_Fun($vv[5]);
                    $pv_Sum += $this->QTY_Fun($vv[4]) * $this->QTY_Fun($vv[5]);
             
                }
            }
            $bv = $bv_Sum;
            $pv = $pv_Sum;
             $naira  = $naira_Sum;
             $this->input['ProductValue'] = $naira_Sum;
             $REMAIN=0;$kitsvalue=0;
             $REMAIN =$this->input['Remain'] - $this->input['ProductValue']- $this->input['KitsQty']* self::$KitsValue;
             $kitsvalue = $this->ProccessNull($this->input['KitsQty']) * self::$KitsValue;
             if ($REMAIN < 0)
             {
                 $this->input['Balance']= $REMAIN;
             }
             else
                $this->input['Balance']= $REMAIN;
            return suc($this->input);
        }
        public  function QTY_Fun($QTY)
        {
            if (trim($QTY)== "" || $QTY == null)
                return 0.0;
            else return (float)$QTY;
        }
        public function button2_Click()
        {
            $rule=[
                "ProductValue"=>"require|number",
                "CODE(SC)"=>"require|number",
            ];
            $msg=[
                "ProductValue"=>"未选择产品，无法进行扣货！\n You didn't select product,can not deduct borrow!",
                "CODE(SC)"=>"请设置专卖店号！\n Pls enter shopno or card no!",
                
            ];
            $check=$this->validate($this->input, $rule,$msg);
            if($check!==true){
                return err(9000,$check);
            }
            if (!$this->CheckIfWenttoStock())
            {

                        if ($this->CheckIfSubmitDpbv())
                        {
                            $data["action"]='ReturnBorrowed';
                            return suc($data);

                        }
                        else  return err(9000,"该DPBV数据未提交！\n Pls submit products");
                    
                
            }
            else return err(9000,"已经进入仓库，无法进行扣货！\n The product has gone to warehouse,you can not deduct borrowed!");
        }
        private function CheckIfSubmitDpbv() 
        {
                    $sql = "select count(*) from dpbv_products where groupid='" .$this->input['PrintForWarehouse']."'";
                    $result = $this->GetStringData($sql);
                    if (trim($result)== "0")
                        return false;
                    else return true;
                
        }
        public function button3_Click()
        {
            $_POST['SC']=$this->input['CODE(SC)'];
            $_POST['Date']=$this->input['DATE'];
            $_POST['NO']=$this->input['PrintForWarehouse'];
           $_POST['Kits']=$this->input['KitsQty'];
            return action("CancelDpbv/button3_Click",[]);
        }
        private function button5_Click()
        {

            return action("CancelDpbv/button1_Click",[]);
        }
        public function button4_Click()
        {
            if ($this->SubmitMark == "1")
                return err(9000,"已经提交，不能重复提交！Already submit,can not submit again!");
            else
            {
            $this->button1_Click();
            if ($this->input['Remain']== $this->input['Balance'])
                return err(9000,"没有选择产品！You did not select product!");
            else
            {
                Db::startTrans();
                try {
                    $data=$this->InsertDpbv_Products();
                    Db::commit();
                    return suc($data);
                } catch (\Exception $exc) {
                    Db::rollback();
                    return err(9000,$exc->getMessage());
                }

                

            }
            }
        }
        private function InsertDpbv_Products() 
        {
            $saleid = $this->GetNewSaleIDforDpbv_Products();
            $this->button1_Click();
            $sql = "insert dpbv_products(sc_code,saleid,status,processstatus,totalbv,totalpv,totalmember,createtime,lastedittime,ip,mac,operman,groupid,remainnaira,paymoney,kits) "
               . "values('".$this->input['CODE(SC)']."'," .$saleid. ",'0','0'," .$this->bv . "," .$this->pv. "," .$this->naira . ",'" . date('Y-m-d'). "','" .date('Y-m-d'). "','" .$this->Local_IP() . "','" .$this->Local_Mac() . "','" .self::$realname. "','" .$this->input['PrintForWarehouse']."'," .$this->ProccessNull($this->input['Balance']) . "," .$this->GetPayMoney (). ",".$this->ProccessNull ($this->input['KitsQty'] ).")";
          $status=$this->Exc_Sql($sql);
            if ($status> -1)
            {
                            $data=$this->Get_Product_Info();
                foreach ($data as $v)
                {
                    $vv= array_values($v);
                    if ($this->QTY_Fun(trim($vv[5])) > 0)
                        $this->InsertDpbv_ProductsDetail($saleid, $this->GetProductid($vv[0]), $vv[5], $vv[3], $vv[4], $vv[2]);
                }
                $sql = "update dpbv_products set totalmoney=totalmember+kits*" .self::$KitsValue. " where groupid='" .$this->input['PrintForWarehouse']. "'";
                $status=$this->Exc_Sql($sql);
                if ($status< 1)
                {
                    exception("更新totalmoney失败！sql:" .$sql);
                    $sql = "delete from dpbv_products where groupid='"  .$this->input['PrintForWarehouse'].  "'";
                 $status=$this->Exc_Sql($sql);
                    if ($status< 1)
                        exception("回滚失败！");
                }
                else return suc("提交成功！Successful!");
            }
            else
            {
                if ($this->GetStringData("select count(*) from dpbv_products where groupid='"  .$this->input['PrintForWarehouse'].   "'") == "0")
                {
                    exception("个人消费产品存储失败！Sql:" .$sql);
                }
                else                    exception("个人消费产品存储失败！已经有该编号数据，Sql:" .$sql);
               
                
            }
            
        }
        private function GetNewSaleIDforDpbv_Products()
        {
            $sql = "select isnull(max(SaleID),0)+1 as SaleID from Dpbv_Products";
            $SaleID = $this->GetStringData($sql);
            return $SaleID;

       
        }
        private function GetPayMoney() 
        {
            $balance = $this->input['Balance'];
            if ($balance < 0)
                return (0 - $balance);
            else return "0";
        }
        private function InsertDpbv_ProductsDetail($saleid,$productid,$amount,$bv,$pv,$naira) 
        {  
           $sql = "insert dpbv_productsdetail(saledetailid,saleid,productid,amount,pv,retailprice,memberprice)"
               . " values(" .$this->GetNewSaleDetalIDforDpbv_Products (). ",".$saleid .",".$productid .",".$amount .",".$bv.",".$pv.",".$naira .")";
           $status=$this->Exc_Sql($sql);
            if ($status< 0)
            {
                exception("个人消费产品详细插入失败！");
            }

        }
        private function GetProductid($productno)
        { 
        $sql = "select productid from tb_product where productno='".$productno ."'";
        $pid = $this->GetStringData($sql);
        return $pid;
        }
        public  function toolStripMenuItem4_Click()
        {
            if ($this->SubmitMark == "0")
                return err(9000,"没有提交过数据，无法反提交！\n You did not submit it!");
            else
            {
                if ($this->CheckIfReturnBorrow($this->input['PrintForWarehouse']))
                    return err(9000,"无法取消提交，已经进行完扣货，请反扣货后再反提交！\n You can not cancel the submit,because you already do the return products!");
                else
                {
                    $sql = "delete from dpbv_products where groupid='" . $this->input['PrintForWarehouse']. "'";
                    $status=$this->Exc_Sql($sql);
                    if ($status> 0)
                    {
                        return suc("操作成功");
                    }
                }
            }
        }
        private function CheckIfReturnBorrow($GID) 
        {
            $sql = "select returnborrowedstatus from dpbv_products where groupid='".$GID."'";
            $status = $this->GetStringData($sql);
            if (trim($status)== "OK")
                return true;
            else return false;
        }
        private function GetNewSaleDetalIDforDpbv_Products()
        {
            $sql = "select isnull(max(SaledetailID),0)+1 as SaledetailID from dpbv_productsdetail";
            $SaleID = $this->GetStringData($sql);
            return $SaleID;


        }
        public function Set() 
        {
            if ($this->input['DPBV'] != "")
                $this->input['NAIRA']  = $this->input['DPBV']* $this->ExchangeRate;
            $this->input['Remain'] = $this->input['NAIRA'] ;
        }
}
