<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';
class Index_EweiShopV2Page extends MerchmanageMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		
		$shopset = $_W['shopset']['shop'];
		global $_W;
		global $_GPC;
		// 	    var_dump($_W['merchmanage']['merchid']);die;
		if (empty($_GPC["merchid"])){
		    $merchid = $_W['merchmanage']['merchid'];
		}else{
		    $merchid=$_GPC["merchid"];
		}
		
		$merchshop = pdo_fetch('select * from '.tablename('ewei_shop_merch_user').' where id ="'.$merchid.'"');
		
		$logo=tomedia($merchshop["logo"]);
		
		//店铺下数据
		
		//访问次数
		$viewcount = $this->sale_analysis_count('SELECT sum(viewcount) FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'');
		
		//今日订单
		$order = $this->order(0);
		$today_order=$order["count"];
		//代发货
		$totals = $this->model->getTotals($merchid);
		$substitute_shipment=$totals["status1"];
		//累计订单
		$ordercount = $this->sale_analysis_count('SELECT count(*) FROM ' . tablename('ewei_shop_order') . ' WHERE status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'');
		
		//店铺数据
		
		//今日成交额
		$today_price=$order["price"];
		//累计成交
		$orderprice = $this->sale_analysis_count('SELECT sum(price) FROM ' . tablename('ewei_shop_order') . ' WHERE  status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\' ');
		
		//订单转化率
		$percent=round( $ordercount/($viewcount==0?1:$viewcount),2);
		if ($percent>1){
		    $percent+=100;
		}else {
		    $percent*=100;
		}
		$order_percent=empty($percent)?'':$percent.'%';
		//会员消费率
		$member_count = $this->sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . ' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ' . tablename('ewei_shop_order') . '   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'  )');
		$member_buycount = $this->sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . ' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ' . tablename('ewei_shop_order') . '   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\' and status>=1 )');
		$percent=round( $member_buycount/($member_count==0?1:$member_count),2);
		if ($percent>1){
		    $percent+=100;
		}else{
		    $percent*=100;
		}
		$vip_percent=empty($percent)?'':$percent.'%';
		
		//在售商品
		$goods = $this->model->getMerchTotals($merchid);
		$goodscount = $goods['sale'] + $goods['out'] + $goods['stock'] + $goods['cycle'];
		include $this->template();
	}

	public function get_today()
	{
		$order = $this->order(0);
		show_json(1, array('today_count' => $order['count'], 'today_price' => $order['price']));
	}

	public function get_order()
	{
		global $_W;
		$merchid = $_W['merchmanage']['merchid'];
		$totals = $this->model->getTotals($merchid);
		show_json(1, $totals);
	}

	public function get_shop()
	{
		global $_W;
		$merchid = $_W['merchmanage']['merchid'];

		$goods = $this->model->getMerchTotals($merchid);
		$goodscount = $goods['sale'] + $goods['out'] + $goods['stock'] + $goods['cycle'];
		
		show_json(1, array('goods_count' => $goodscount));
	}

	/**
     * ajax return 交易订单
     */
	protected function order($day)
	{
		global $_GPC;
		$day = (int) $day;
		$orderPrice = $this->selectOrderPrice($day);
		$orderPrice['avg'] = ((empty($orderPrice['count']) ? 0 : round($orderPrice['price'] / $orderPrice['count'], 1)));
		unset($orderPrice['fetchall']);
		return $orderPrice;
	}

	protected function selectOrderPrice($day = 0)
	{
		global $_W;
		$day = (int) $day;
		$merchid = $_W['merchmanage']['merchid'];
		if ($day != 0) {
			$createtime1 = strtotime(date('Y-m-d', time() - ($day * 3600 * 24)));
			$createtime2 = strtotime(date('Y-m-d', time()));
		}else {
			$createtime1 = strtotime(date('Y-m-d', time()));
			$createtime2 = strtotime(date('Y-m-d', time() + (3600 * 24)));
		}

		$sql = 'select id,price,createtime from ' . tablename('ewei_shop_order') . ' where uniacid = :uniacid and ismr=0 and isparent=0 and (status > 0 or ( status=0 and paytype=3)) and merchid =:merchid and deleted=0 and createtime between :createtime1 and :createtime2';
		$param = array(':uniacid' => $_W['uniacid'], ':createtime1' => $createtime1, ':createtime2' => $createtime2,':merchid'=>$merchid);
		$pdo_res = pdo_fetchall($sql, $param);
		$price = 0;

		foreach ($pdo_res as $arr ) {
			$price += $arr['price'];
		}

		$result = array('price' => round($price, 1), 'count' => count($pdo_res), 'fetchall' => $pdo_res);
		return $result;
	}
	
	public function sale_analysis_count($sql)
	{
	    $c = pdo_fetchcolumn($sql);
	    return intval($c);
	}
	//首页接口
	public function indexapi(){
	    global $_W;
	    global $_GPC;
// 	    var_dump($_W['merchmanage']['merchid']);die;
	    if (empty($_GPC["merchid"])){
	        $merchid = $_W['merchmanage']['merchid'];
	    }else{
	        $merchid=$_GPC["merchid"];
	    }
	    
	    $merchshop = pdo_fetch('select * from '.tablename('ewei_shop_merch_user').' where id ="'.$merchid.'"');
	  
	    $logo=tomedia($merchshop["logo"]);
	    
	    //店铺下数据
	  
	      //访问次数
	    $viewcount = $this->sale_analysis_count('SELECT sum(viewcount) FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'');
	    
	      //今日订单
	    $order = $this->order(0);
	    $today_order=$order["count"];
	      //代发货
	    $totals = $this->model->getTotals($merchid);
	    $substitute_shipment=$totals["status1"];
	      //累计订单
	    $ordercount = $this->sale_analysis_count('SELECT count(*) FROM ' . tablename('ewei_shop_order') . ' WHERE status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'');
	    
	    //店铺数据

	       //今日成交额
	    $today_price=$order["price"];
	      //累计成交
	    $orderprice = $this->sale_analysis_count('SELECT sum(price) FROM ' . tablename('ewei_shop_order') . ' WHERE  status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\' ');
	   
	      //订单转化率
	    $percent=round( $ordercount/($viewcount==0?1:$viewcount),2);
	    if ($percent>1){
	        $percent+=100;
	    }else {
	        $percent*=100;
	    }
	    $order_percent=empty($percent)?'':$percent.'%';
	      //会员消费率
	    $member_count = $this->sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . ' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ' . tablename('ewei_shop_order') . '   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\'  )');
	    $member_buycount = $this->sale_analysis_count('select count(*) from ' . tablename('ewei_shop_member') . ' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ' . tablename('ewei_shop_order') . '   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $merchid . '\' and status>=1 )');
	    $percent=round( $member_buycount/($member_count==0?1:$member_count),2);
	    if ($percent>1){
	        $percent+=100;
	    }else{
	        $percent*=100;
	    }
	    $vip_percent=empty($percent)?'':$percent.'%';
	    
	      //在售商品
	    $goods = $this->model->getMerchTotals($merchid);
	    $goodscount = $goods['sale'] + $goods['out'] + $goods['stock'] + $goods['cycle'];
	    
	    
// 	    show_json(1,$resault);
	    include $this->template();
	}
	
	
}


?>