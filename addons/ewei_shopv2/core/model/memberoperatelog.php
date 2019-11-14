<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Memberoperatelog_EweiShopV2Model
{
  public function addlog($data){
      return pdo_insert('ewei_shop_member_operate_log',$data);
  }

}
?>