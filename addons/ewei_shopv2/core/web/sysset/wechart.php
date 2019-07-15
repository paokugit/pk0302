<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Wechart_EweiShopV2Page extends ComWebPage
{
	public function __construct($_com = 'qiniu')
	{
		parent::__construct($_com);
	}

	public function version(){
		global $_W;
		global $_GPC;
		$setting = pdo_fetch("select * from " . tablename("ewei_setting") . " where id=:id limit 1", array( ":id" => 7 ));
		$goodsshare = pdo_fetch("select * from " . tablename("ewei_setting") . " where id=:id limit 1", array( ":id" => 8 ));

		if ($_W['ispost'])
		{
			$data['value'] = $_GPC['storeshow'];
			pdo_update('ewei_setting',$data,array('id'=>7));
			$data1['value'] = $_GPC['goodsshare'];
			pdo_update('ewei_setting',$data1,array('id'=>8));
			show_json(1);
		}
		include($this->template());
	}

}

?>
