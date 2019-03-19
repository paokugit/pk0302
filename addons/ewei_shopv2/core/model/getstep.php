<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Getstep_EweiShopV2Model
{
	/**
	 * 获取帮助列表
	 * @param $openid
	 * @return bool
	 */
	public function getHelpList($openid,$day=1,$pindex=1,$psize=20)
	{
		if($day==1){
			$day=date('Y-m-d');
			return pdo_fetchall('select s.id,s.openid,s.day,s.step,s.bang,s.step,s.remark,m.openid as mopenid,m.nickname,m.avatar  from ' . tablename('ewei_shop_member_getstep') . '  s left join  ' . tablename('ewei_shop_member') . ' m on s.bang = m.openid  where s.openid=:openid and s.day=:day and s.bang is not null and s.type=1 order by s.step desc', array(':openid' => $openid, ':day' => $day));

		}else{
			$star = ($pindex - 1) * $psize;
			return pdo_fetchall('select s.id,s.openid,s.day,s.step,s.bang,s.step,s.remark,m.openid as mopenid,m.nickname,m.avatar  from ' . tablename('ewei_shop_member_getstep') . '  s left join  ' . tablename('ewei_shop_member') . ' m on s.bang = m.openid  where s.openid=:openid and s.bang is not null and s.type=1 order by s.day desc limit '.$star.','.$psize, array(':openid' => $openid));
		}

	}
}



?>
