<?php

class Demo_EweiShopV2Page extends WebPage
{
	public function main(){
		$memberList = pdo_fetchall("select openid from " . tablename("ewei_shop_member"));
		foreach ($memberList as $key=>$res){
//			$sql = "UPDATE ims_ewei_shop_member set credit2=
//(SELECT sum(money) as money from ims_ewei_shop_member_log where openid='".$res['openid']."')
// where openid='".$res['openid']."'";
			$sql = "UPDATE ims_ewei_shop_member set credit2=
(SELECT credit2 from ims_ewei_shop_member_lao where openid='".$res['openid']."')
 where openid='".$res['openid']."'";
			$res = pdo_query($sql);
			var_dump($res);echo '==='.$key.'====';
		}
die();


	}
}

?>
