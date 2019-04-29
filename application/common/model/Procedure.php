<?php

namespace app\common\model;
use think\Db;

class Procedure  {


    public function sp_GetNewSaleNO() {
          $sql="EXEC sp_GetNewSaleNO @IDCount=1";
          $data=Db::query($sql);
          return $data[1][0]["CurrentSaleNO"];
    }
        public function sp_Import_FrontDesk_Sale($para) {
          $sql="EXEC sp_Import_FrontDesk_Sale @SaleNO='{$para['SaleNO']}',@CustomerNo='{$para['CustomerNO']}',@BranchID='{$para['BranchID']}',@SaleDate='{$para['SaleDate']}',@RealDate='{$para['RealDate']}'"
          . ",@ShopNO='{$para['ShopNO']}',@Total_BV='{$para['Total_BV']}',@Total_PV='{$para['Total_PV']}',@Total_NAIRA='{$para['Total_NAIRA']}',@GroupID='{$para['GroupID']}',@Oper_Name='{$para['Oper_Name']}',@Current_Status='{$para['Current_Status']}'";
          $data=Db::execute($sql);
          return $data;
    }
        public function sp_Import_Customer($para) {
          $sql="EXEC sp_Import_Customer @CustomerNo='{$para['CustomerNO']}',@Status='{$para['Status']}',@BranchID='{$para['BranchID']}',@RegDate='{$para['RegDate']}'"
          . ",@TurnDate='{$para['TurnDate']}',@ApplyGrade='{$para['ApplyGrade']}',@Grade='{$para['Grade']}',@Job_Grade='{$para['Job_Grade']}',@C_Grade='{$para['C_Grade']}',@ParentNO='{$para['ParentNO']}',@ParentName='{$para['ParentName']}'"
          . ",@Depth='{$para['Depth']}',@RecommendNO='{$para['RecommendNO']}',@RecommendName='{$para['RecommendName']}',@Depth_r='{$para['Depth_r']}',@NetType='{$para['NetType']}',@CustomerName='{$para['CustomerName']}',@Sex='{$para['Sex']}'"
            . ",@CardType='{$para['CardType']}',@CardNO='{$para['CardNO']}',@BankName='{$para['BankName']}',@BankCard='{$para['BankCard']}',@Nation='{$para['Nation']}',@Province='{$para['Province']}',@Address='{$para['Address']}'"
           . ",@Address2='{$para['Address2']}',@PostCode='{$para['PostCode']}',@Mobile='{$para['Mobile']}',@Phone='{$para['Phone']}',@EditFlag='{$para['EditFlag']}',@ShopNO='{$para['ShopNO']}'";
   try {
    $data=Db::execute($sql);
} catch (\Exception $exc) {
       return err(2000,$exc->getMessage());
   }

   
          return suc($data);
    }
    public function Get_NewID(){
                $sql="EXEC sp_GetNewID @IDCount=1";
          $data=Db::execute($sql);
          return $data;
        
    }
}
