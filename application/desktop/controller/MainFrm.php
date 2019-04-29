<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class MainFrm extends Common {

        private$PrintIndex;     
        private  $dataIndex;      
        private $Count = 10;
        private  $PrintMark = 0;
        private $Name="提款界面";
        private $comboBox1=[
            "借方",
            "贷方",
            "Bank",
            "确认人",
            "摘要"];
        private $comboBox2=[
             "无日期",
            "EffectDate",
            "确认日期",
            "POS日期"];
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }
    public function load(){
        $data['comboBox1']=$this->comboBox1;
        $data['comboBox2']=$this->comboBox2;
        return suc($data);
    }
    public function button1_Click_1()
        {
           
            $condition="";
            if (trim($this->input['SC'])!= "")
                $condition = "sc='" .$this->input['SC']. "'";
                
            if(trim($this->input['code']) !="")
                $condition = "code like'%" .$this->input['code']. "%'";
            if (trim($this->input['SC']) != "" || trim($this->input['code'])!= "")
            {
                    $condition =$condition ." and [date]>='" . $this->input['From'] . "' and [date]<='". $this->input['To'] . "'";
                $csalary = "";
                $csalary = "select * from Dpbv_Check where " .$condition. " order by collectmark,[date] desc"." OFFSET  {$this->input['OFFSET']} ROWS 
FETCH NEXT {$this->input['ROWS']} ROWS ONLY ";
                $count= "select count(*) as c from Dpbv_Check  " .$condition;
                $num=$this->GetStringData($count);
                $data=$this->query_Sql($csalary);
            }
            else
            {
                   
                    $condition = "where [date]>='". $this->input['From'] . "' and [date]<='" . $this->input['To'] . "'";
                $csalary = "select * from Dpbv_Check " .$condition. " order by collectmark,[date] desc"." OFFSET  {$this->input['OFFSET']} ROWS 
FETCH NEXT {$this->input['ROWS']} ROWS ONLY ";
                $count= "select count(*) as c from Dpbv_Check  " .$condition;
                $num=$this->GetStringData($count);

                $data=$this->query_Sql($csalary);
                
            }
            $rep["num"]=$num;
            $rep["list"]=$data;
         return suc($rep);
            
            
        }
        public function button4_Click()
        {
           $rule=[
               "left_check"=>"require"
           ];
           $msg=[
                "left_check"=>"请选择要领的DPBV"
           ];
           $check=$this->validate($this->input, $rule,$msg);
           if($check!==true){
               return err(9000,$check);
               
           }
            $data=$this->DpbvLeftToRight();
            return suc($data);
            
        }
        private function DpbvLeftToRight()
        {
            $left_check= json_decode($this->input['left_check'],true);
            foreach ($left_check as $v)
            {

                    if (!$this->CheckIfSomehasGID())
                    {

                       
                        $this->SetDpbvCollectMark($v);
                    }

            }
            $data['Load_DatagridView2']=$this->Load_DatagridView2();
           return $data;
        }
        private function CheckIfSomehasGID()
        {
            $status = false ;
           $data=$this->Load_DatagridView2();
            foreach ($data as $v)
            {
              

               $vv= array_values($v);
                    if ($this->CheckIfGotoDpbvProducts($vv[11]))
                    {
                        exception("无法选个人消费，部分个人消费已经选择产品，未打印！");
                        $status = true;
                    }
                    else
                    {

                    }


            }
            return $status;
        }
        public function Load_DatagridView2(){
            $name=self::$realname;
            $sql = "select * from dpbv_check WHERE COLLECTMARK='1' and oper_name='".$name."'";
           $data=$this->query_sql($sql);
           return $data;

        }
        private function CheckIfGotoDpbvProducts($GID) {
            $sql = "select count(*) from dpbv_products where groupid='".$GID. "'";
            $result = $this->GetStringData($sql );
            if (trim($result) == "0")
                return false;
            else return true;

        }
        private function SetDpbvCollectMark($DpbvID)
        {
            $sql = "update Dpbv_Check set comment='" .date('Y-m-d H:i:s'). "', CollectMark='1',Oper_Name='".self::$realname."',Remark='Collected on " .date('Y-m-d H:i:s'). " By" .self::$realname. "' where DpbvID='" .$DpbvID . "'";
          $status=$this->Exc_Sql($sql);
            if( $status<1)
            exception("更改标志位collectmark失败！\n Sql:".$sql) ;
        }
        public function button6_Click()
        {
           $rule=[
               "right_check"=>"require"
           ];
           $msg=[
                "right_check"=>"请选择要领的DPBV"
           ];
           $check=$this->validate($this->input, $rule,$msg);
           if($check!==true){
               return err(9000,$check);
               
           }
            $data=$this->DpbvRightToLeft();
            return$data;
        }
        private function DpbvRightToLeft()
        {
            $Load_DatagridView2=$this->Load_DatagridView2();
            if(empty($Load_DatagridView2)){
                return err(9000,"数据为空");
            }
            $right_check= json_decode($this->input['right_check'],true);
            foreach($Load_DatagridView2 as $v)
            {

            $vv= array_values($v);
                   
                    if (in_array($vv, $right_check)&&$this->CheckIfGotoDpbvProducts($vv[11]))
                        exception("无法撤回，已经提交，请进入反提交！\n The dpbv is already submited,Pls cancel it!");
                    else
                    {

                        $this->Set_UnCollectMark($vv[0]);
                    }

                

            }
            return suc("成功");
        }
        private function Set_UnCollectMark($DpbvID)
        {
            $sql="update Dpbv_Check set CollectMark='0',Remark='uncollect',groupid='' where DpbvID='" .$DpbvID. "'";
            $status=$this->Exc_Sql($sql);
            if ($status<= 0)
                exception("撤销领取个人消费标志位失败！\n Sql:".$sql);



        }
        public function ToolStripMenuItem_Click()
        {
            $rule=['file'=>'require'];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
               $rep['Drop_Table']=$this->Drop_Table("Uncollect_dpbv");

                $data=$this->get_data(".".$this->input['file']);

                $field=[];
                $insert=[];
                $i=0;
                foreach($data as $v){
                    if(is_numeric($v[1])){
                        foreach ($v as $k=>$vv){
                            $kk=$field[$k];
                            $vvv["$kk"]=$vv;
                        }
                 $sql="insert Uncollect_dpbv(".implode(",", $field).") values('".implode("','", $vvv)."')";
                 $this->Exc_Sql($sql);
                 $i++;
                    }else{
                        $field=$v;
                        $col="";
                        foreach($v as $vo){
                                                    $col.="[{$vo}] [varchar](255) NULL,";
                        }
           $sql="
CREATE TABLE [dbo].[Uncollect_dpbv](
{$col}
) ON [PRIMARY]
";
                 $this->Exc_Sql($sql);
                    }
                }

                return suc("上传".count($data)."数据新增".$i);

        }
        private function Drop_Table($TB_Name)
        {

            parent::Drop_Table1($TB_Name);
            return suc("旧表已去");


        }
        private function get_data($filename){
         require_once  EXTEND_PATH."PHPExcel/PHPExcel/IOFactory.php";
         $type = \PHPExcel_IOFactory::identify($filename);
         $objReader = \PHPExcel_IOFactory::createReader($type);
         $objPHPExcel = $objReader->load($filename);
         foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
          $worksheets[$worksheet->getTitle()] = $worksheet->toArray();
          }

         $datas=array_shift($worksheets);
         return $datas;
        }
}
