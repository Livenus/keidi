<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;

class SaleEnteredByFront extends Base {

    protected $table = 'tb_Sale_Entered_ByFrontDesk';
    protected $pk = 'SaleID';

    public function add($data) {
        $data["Current_Status"]=0;
        $data["SaleNO"]=$this->_setSaleNo();
        $data["GroupID"]="";
         $res=$this->Procedure->sp_Import_FrontDesk_Sale($data);
         if($res){
             return $data;
         }
        return 0;
    }
    public function  _setSaleID($SaleNO){
        $last=$this->where(["SaleNO"=>$SaleNO])->find();
        return $last["SaleID"];
    }
    public function  _setSaleNo(){
        return $this->Procedure->sp_GetNewSaleNO();
    }
    public  function _set_group_id($shopno,$data){
                 $time=strtotime($data['RealDate']);
               return date("Ynj",$time).(int)date('H').(int)date('i').(int)date('s')."-".$shopno;
    }
    public  function Set_GroupID($GroupID,$realdate,$oper_name,$ShopNO){
           $sql= "update tb_sale_entered_byfrontdesk set current_status='0',GroupID='" . $GroupID . "' where current_status='0' and oper_name='" .$oper_name. "'and realdate>='" .$realdate. "'and shopno='".$ShopNO."' and realdate<='" .$realdate. "'";
           $update=Db::execute($sql);
           $new=[];
           $count=model("FrontDeskReport")->getCount(["GroupID"=>$GroupID]);

           if($count==0){
                          $new=model("FrontDeskReport")->add(["GroupID"=>$GroupID]);
           }
          if(!empty($new)&&$new['stat']==1||$count){
              return suc("生成group成功");
          }
           return err(9000,"生成失败".$new['errmsg']);
        
    }
    public function  select_reload($realdate,$oper_name="",$shopno="",$status=0,$groupid=""){
        //$sql= "select   from tb_sale_entered_byfrontdesk,tb_customer where tb_sale_entered_byfrontdesk.customerno=tb_customer.customerno and tb_sale_entered_byfrontdesk.current_status='0' and oper_name='" .$oper_name. "'and realdate>='" .$realdate. "' and realdate<='".$realdate. "' order by saleno desc";
       if($oper_name){
        $where["Oper_Name"]=$oper_name; 
       }
       $where["Current_Status"]=$status;
       if($groupid){
       $where["GroupID"]=$groupid;
       }else{
        $where["RealDate"]=$realdate;

       }
       if($shopno){
       $where["tb_sale_entered_byfrontdesk.ShopNO"]=$shopno; 
       }
        $data=$this->where($where)->join("tb_customer","tb_sale_entered_byfrontdesk.customerno=tb_customer.customerno")->field("saleid,saleno ,tb_customer.customerno ,tb_sale_entered_byfrontdesk.shopno ,convert(decimal(10,1),tb_sale_entered_byfrontdesk.total_bv) as Total_BV,convert(decimal(10,1),tb_sale_entered_byfrontdesk.total_pv) as Total_PV,convert(decimal(10,0),tb_sale_entered_byfrontdesk.Total_naira) as Total_naira,realdate ,oper_name")->order('saleno desc')->select();
        return $data;
        }
        //查询未激活gid
        public function Get_UnActive_GroupID($ShopNO,$realdate,$oper_name){
          $sql="select distinct groupid from tb_sale_entered_byfrontdesk ".
" where  tb_sale_entered_byfrontdesk.current_status='0' ".
"and tb_sale_entered_byfrontdesk.shopno='" .$ShopNO. "'and oper_name='" .$oper_name."'and realdate>='" .$realdate. "' and realdate<='" .$realdate."'order by tb_sale_entered_byfrontdesk.groupid desc";
        $data=$this->query($sql);
        if(empty($data)){
            return false;
        }
        return $data[0]["groupid"];
        }
        public function Set_Total($ShopNO,$realdate,$GID){
                $CommandText = "select sum(Total_BV) as Total_BV,sum(Total_PV) as Total_PV,sum(Total_Naira) as Total_Naira from tb_sale_Entered_ByFrontDesk where  current_status='0' and realdate>='" .$realdate. "' and realdate<='".$realdate. "'and groupid='" .$GID. "' and shopno='" .$ShopNO. "'";
               mlog($CommandText);
                $data=$this->query($CommandText);
               return $data[0];
        }
        public  function report_detail($GID){
         $sql = "select productno,sum(amount) as amount from tb_sale_Entered_ByFrontDesk a,tb_saledetail_ByFrontDesk b,tb_product c where a.saleid=b.saleid and b.productid=c.productid and groupid in(select groupid from frontdesk_report where groupid='" .$GID . "'or belongedsc='" .$GID. "') group by productno";
         return $this->query($sql);
        }
        public  function Search($from,$to,$area){
                        $sql = "select shopno as SC,sum(TotalMoney) as Achievement,sum(debitMoney)as Debit,' ' as Memo from frontdesk_report where realdate between '" .$from. "' and '" .$to. "' and area='" .$area. "'  and reporttype='SC' group by shopno order by shopno";
            
                        return $this->query($sql);
        }
        public  function get_sale_out($groupid){
                                    $sql = "select productid,sum(amount) as amount from "
                            . "( select productid,sum(amount) as amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where deductgroupid='" .$groupid. "' and current_status='1') group by productid"
                            . " union all "
                            . "select productid,sum(amount)as amount from tb_saledetail_byfrontdesk where saleid in(select saleid from tb_sale_entered_byfrontdesk where groupid in(select groupid from frontdesk_report where belongedsc='" .$groupid. "') or groupid='" .$groupid. "') group by productid) a"
                            . " group by productid";
                                    $sql1="select * from (" .$sql. ") b where amount>0";
                                    return $this->query($sql1);
            
        }
        public function Check_If_Order_Sameto_Detail($groupid){
            
            $sqlcmd = "select A,B FROM (select sum(total_naira) as A from tb_sale_entered_byfrontdesk where groupid='" .$groupid. "') AS C,(select sum(amount*memberprice) AS B from tb_saledetail_byfrontdesk where saleid in(select saleid from tb_sale_entered_byfrontdesk where groupid='" .$groupid. "')) AS D";
            $data=$this->query($sqlcmd);
            if($data[0]['A']==$data[0]['B']){
                return true;
            }else{
                return false;
            }
        }
        public function Activate_FD_Sale($groupid,$oper_name,$realdate,$ReportType){
            $FrontDeskReport=model("FrontDeskReport");
            $data["Current_Status"]=1;
            $where["Current_Status"]=0;
            $where["GroupID"]=$groupid;
            $where["Oper_Name"]=$oper_name;
            $where["RealDate"]=$realdate;
            $FrontDeskReport->Activate_FD_Sale($groupid,$oper_name,$realdate,$ReportType);
            return $this->save($data,$where);
        }
        public function  UpdateReportDifferent($groupid,$KitsValue){
            $sql="update frontdesk_report set ".
            " ScBV=(select sum(Total_BV) as ScBV from tb_sale_entered_byfrontdesk where groupid='" . $groupid . "')," .
            " ScPV=(select sum(Total_PV) as ScPV from tb_sale_entered_byfrontdesk where groupid='" .$groupid. "')," .
            " ScNaira=(select sum(total_Naira) as ScNaira from tb_sale_entered_byfrontdesk where groupid='" .$groupid. "')" .
            " where groupid='" .$groupid. "'";
            $price=$KitsValue;
            $sql1="update frontdesk_report set totalmoney=scnaira+kits*{$price} where Groupid='".$groupid."'";
                        $this->execute($sql);
                        $this->execute($sql);
            $status=$this->execute($sql);
            $status1=$this->execute($sql1);
            return $status.$status1;
        }
        public function Get_ErrorBV($groupid){
            
            $sql= "select convert(decimal(19,1) ,sum(Total_BV)) as Total_BV from tb_sale_entered_byfrontdesk where current_status='3' and groupid='" .$groupid. "'";
            $data=$this->query($sql);
            return $data[0];
        }
        public function GetTotalResult($groupid){
                 $sql= "select sum(convert(decimal(10,1),tb_sale.totalpv)) as totalpv,sum(convert(decimal(10,1),tb_sale.totalretail)) as totalretail,sum(convert(decimal(10,0),tb_sale.Totalmember) ) as Totalmember from tb_sale,tb_customer where tb_sale.customerid=tb_customer.customerid and tb_sale.status='0' and saleno in(select saleno from tb_sale_entered_byfrontdesk where groupid='" .$groupid. "')";
                 $data=$this->query($sql);
                 return $data[0];
            
        }
        public function ProcessMix($groupid){
                         $sql = "select s.saleno,s.TotalPV as BV,t.total_Bv from tb_sale s,tb_sale_entered_byfrontdesk t where s.saleid=t.saleid and groupid='".$groupid."' and s.TotalPV<>t.total_Bv";
                         $data=$this->query($sql);
                         if(empty($data)){
                             return false;
                         }
                         return $data[0];
        }
}
