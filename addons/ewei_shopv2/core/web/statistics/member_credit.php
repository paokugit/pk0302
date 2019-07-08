<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Member_credit_EweiShopV2Page extends WebPage
{
	public function get_c($type)
	{
		$sql = "select * from".tablename('ewei_shop_member').' order by '.$type.' desc limit 0,100';
		$list = pdo_fetchall($sql);
        return $list;
	}

	public function member_credit1(){
		$data['list']= $this->get_c('credit1');
		$data['type'] = '1';
		include $this->template('statistics/member_credit');
	}

	public function member_credit2(){
		$data['list']= $this->get_c('credit2');
		$data['type'] = '2';
		include $this->template('statistics/member_credit');
	}

	public function member_credit3(){
		$data['list']= $this->get_c('credit3');
		$data['type'] = '3';
		include $this->template('statistics/member_credit');
	}

	public function member_credit4(){
		$data['list']= $this->get_c('credit4');
		$data['type'] = '4';
		include $this->template('statistics/member_credit');
	}
}

?>
