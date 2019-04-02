<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Balance_EweiShopV2Page extends AppMobilePage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
        $member = $this->member;
        $data['id'] = $member['id'];
        $data['openid'] = $member['openid'];
        $data['credit2'] = $member['credit2'];//账户余额


	}
}
?>