<?php
class Credits_EweiShopV2Model
{
	public function get_sum_credit($type,$openid){
		$addwhere='';
		if (empty($type)){
			return 0;
		}elseif ($type==1){
			//好友助力
			$addwhere.=" and remark like '%好友%'";
			$addwhere.=" or remark like '%邀请%'";
		}elseif ($type==2){
			$addwhere.=" and remark like '%签到%'";
		}elseif ($type==3){
			//步数兑换
			$addwhere.=" and remark like '%步数%'";
		}elseif ($type==4){
			//订单消费
			$addwhere.=" and remark like '%消费%'";
		}

		$condition = " and openid=:openid and credittype=:credittype and module=:module   ".$addwhere;
		$params = array(':openid' => $openid, ':credittype' => 'credit1', ':module' => 'ewei_shopv2');
		$sum = pdo_fetchcolumn('select sum(a) from (select num as a from ' . tablename('mc_credits_record') . ' where 1 ' . $condition.') as b', $params);
		return $sum;
	}

}
?>
