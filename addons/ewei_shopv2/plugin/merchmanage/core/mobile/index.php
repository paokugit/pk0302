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


    /**
     * 店主后台 客户接口
     */
    public function getclient()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $page = max(1,$_GPC['page']);
        $pageSize = 10;
        $pindex = ($page - 1)*$pageSize;
        $list = pdo_fetchall('select DISTINCT h.openid,g.merchid from '.tablename('ewei_shop_member_history').' h join '.tablename('ewei_shop_goods').' g on g.id = h.goodsid'.' where g.merchid = :merchid and h.uniacid=:uniacid',array(':merchid'=>$_W['merchmanage']['merchid'],':uniacid'=>$_W['uniacid']));
        $data = pdo_fetchall('select DISTINCT h.openid,g.merchid from '.tablename('ewei_shop_member_history').' h join '.tablename('ewei_shop_goods').' g on g.id = h.goodsid'.' where g.merchid = :merchid and h.uniacid=:uniacid limit '.$pindex.','.$pageSize,array(':merchid'=>$_W['merchmanage']['merchid'],':uniacid'=>$_W['uniacid']));
	$paid = 0;
        foreach ($data as $key=>$item){
            $user = pdo_get('ewei_shop_member',array('openid'=>$item['openid']),['mobile','nickname','avatar']);
            $data[$key]['mobile'] = $user['mobile']?$user['mobile']:'暂时没有获得';
            $data[$key]['nickname'] = $user['nickname'];
            $data[$key]['avatar'] = $user['avatar'];
	    $count= pdo_fetch('select count(1) as count,sum(price) as sum from '.tablename('ewei_shop_order').' where merchid=:merchid and openid=:openid and status=:status',array(':merchid'=>$_W['merchmanage']['merchid'],':status'=>3,':openid'=>$item['openid']));
            $data[$key]['count'] = $count['count']?:0;
            $data[$key]['sum'] = $count['sum']?:0;
        }
        foreach ($list as $item){
            $count= pdo_fetch('select count(1) as count,sum(price) as sum from '.tablename('ewei_shop_order').' where merchid=:merchid and openid=:openid and status=:status',array(':merchid'=>$_W['merchmanage']['merchid'],':status'=>3,':openid'=>$item['openid']));
            if($count['count'] > 0){
                $paid++;
            }
        }
        $fans = count($list);
        show_json(1,['fans'=>$fans,'paid'=>$paid,'list'=>$data,'page'=>$page,'pageSize'=>$pageSize]);
    }



    /**
     * 获取店铺二维码
     */
    public function getshopposter()
    {
        global $_GPC;
        global $_W;
        set_time_limit(0);
        @ini_set("memory_limit", "256M");
        $merchid = $_W['merchmanage']['merchid'];
        $path = IA_ROOT . "/addons/ewei_shopv2/data/shopcode/";
        if( !is_dir($path) )
        {
            load()->func("file");
            mkdirs($path);
        }
        //店铺名称
        $shopset = pdo_fetch('select * from '.tablename('ewei_shop_merch_user').' where id ="'.$merchid.'"');
        $md5 = md5(json_encode(array( "siteroot" => $_W["siteroot"],"id" => $merchid,"nickname"=>$shopset['merchname'], "page" => 'packageA/pages/changce/merch/detail')));
        $filename = $md5 . ".png";
        $filepath = $path . $filename;
        if( is_file($filepath) )
        {
            $imgurl = $_W["siteroot"] . "addons/ewei_shopv2/data/shopcode/".$filename;
            //app_json(array( "url" => $imgurl ));
            return $imgurl;
        }
        $target = imagecreatetruecolor(1118, 1534);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);

        //邀请微信扫码下单
        $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/PINGFANG_MEDIUM.TTF";
        if( !is_file($font) )
        {
            $font = IA_ROOT . "/addons/ewei_shopv2/static/fonts/msyh.ttf";
        }

        $merchname = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 98, 0, 200, 218, $merchname, $font,mb_substr($shopset['merchname'],0,6,"UTF-8") );

        $red = imagecolorallocate($target, 51, 51, 51);
        imagettftext($target, 58, 0, 309, 372, $red, $font,'邀请微信扫码下单' );
        //小程序码
        $qrcode = p("app")->getCodeUnlimit(array( "scene" => "&id=".$merchid."&fromid=".$merchid, "page" => 'packageA/pages/changce/merch/detail',"width"=>1118));
        if( !is_error($qrcode) )
        {
            $qrcode = imagecreatefromstring($qrcode);
            imagecopyresized($target, $qrcode, 255, 507, 0, 0, 608, 608, imagesx($qrcode), imagesy($qrcode));
        }
        imagepng($target, $filepath);
        imagedestroy($target);

        $imgurl =  $_W["siteroot"] . "addons/ewei_shopv2/data/shopcode/".$filename . "?v=1.0";
        //app_json(array( "url" => $imgurl ));
        return $imgurl;
    }

    public function shoppostercode(){
        $imgurl = $this->getshopposter();
        include $this->template();
    }

}


?>