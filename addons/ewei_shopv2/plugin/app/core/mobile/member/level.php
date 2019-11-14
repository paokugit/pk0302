<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Level_EweiShopV2Page extends AppMobilePage
{
    /**
     * 年卡中心
     */
	public function main()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"用户的openid不能为空");
        }
        //用户的信息
        $member = pdo_get('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid],['id','nickname','realname','is_open',"FROM_UNIXTIME(expire_time) as expire"]);
        //待领取的优惠券  两个
        $coupon = pdo_fetchall('select cd.id,cd.used,co.deduct,co.enough,co.couponname from '.tablename('ewei_shop_coupon_data').'cd join '.tablename('ewei_shop_coupon').'co on co.id=cd.couponid'.' where (cd.openid = :openid or user_id = :user_id) and co.timeend > "'.time().'" order by id desc LIMIT 0,2',[':openid'=>$openid,':user_id'=>$member['id']]);
        //特权产品列表
        $goods = pdo_getall('ewei_shop_goods','status = 1 and is_right = 1 and total > 0 order by id desc LIMIT 0,8',['id','title','thumb','total','productprice','marketprice','bargain']);
        foreach ($goods as $key=>$item){
            $goods[$key]['thumb'] = tomedia($item['thumb']);
        }
        //本月的权益礼包
        $month = date('Ym',time());
        $level = pdo_get('ewei_shop_level_record',['openid'=>$openid,'uniacid'=>$uniacid,'month'=>$month],['id','openid','level_name','level_id','goods_id','status','month','FROM_UNIXTIME(updatetime) as updatetime']);
        $good = pdo_get('ewei_shop_goods',['id'=>$level['goods_id'],'uniacid'=>$uniacid],['thumb','productprice']);
        $level = array_merge($level,['thumb'=>tomedia($good['thumb']),'price'=>$good['productprice']]);
	//查询我的第一条记录
        $log = pdo_fetch('select * from '.tablename('ewei_shop_level_record').' where uniacid = "'.$uniacid.'" and level_id = "'.$level['level_id'].'" and openid = :openid order by month asc',[':openid'=>$openid]);
        //如果今天的年月份  大于记录中的 则更新他为失效   或者  月份相同  日期大于20  并把更新时间改成当月的21号为失效时间   并且状态为未领取
        $level['month'] = $level['month'] == $log['month'] ? date("Y年m月d日",strtotime($month."01"."+1 month -1 day")) : date("Y年m月20日",strtotime($month."01"));
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').'where openid = :openid and uniacid = "'.$uniacid.'" order by id desc',[':openid'=>$openid]);
        foreach ($record as $key => $item){
            //如果状态 == 0
            if($item['status'] == 0){
                //如果是第一个月  不更改状态  并继续
                if($item['month'] == $log['month']){
                    continue;
                    //break;
                }
                //当前年月 大于循环的年月  则改变状态为失效
                if(date('Ym',time()) > $item['month'] || (date('Ym',time()) == $item['month'] && date('d',time()) > 21)){
                    pdo_update('ewei_shop_level_record',['status'=>2,'updatetime'=>strtotime($item['month']."21")],['uniacid'=>$uniacid,'id'=>$item['id']]);
                }
            }
        }
        show_json(1,['member'=>$member,'coupon'=>$coupon,'goods'=>$goods,'level'=>$level]);
    }

    /**
     * 年卡详情
     */
    public function detail()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $level_id = empty($_GPC['id']) ? 5 : $_GPC['id'];
	//var_dump($level_id);exit;
        if($level_id == ""){
            show_json(0,"年卡礼包id不能为空");
        }
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $good = unserialize($level['goods_id']);
        $goods = pdo_get('ewei_shop_goods','uniacid="'.$uniacid.'" and id="'.$good[0].'" and status = 1 and total > 0',['id','thumb','title','marketprice']);
        show_json(1,['goods'=>$goods,'goods_id'=>$good[0]]);
    }

    /**
     * 领取记录
     */
    public function record()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $page = max(1,$_GPC['page']);
        if($openid == "" || $page == ""){
            show_json(0,"参数不完整");
        }
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        //计算记录总数
        $year_month = strtotime(date('Ym',time())."10");      //当前的年月份
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_level_record').'where openid = :openid and uniacid = "'.$uniacid.'" and  (createtime < "'.$year_month.'" or status > 0)',[':openid'=>$openid]);
        //查询记录以及分页
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').' where openid = :openid and uniacid = "'.$uniacid.'" and (createtime < "'.$year_month.'" or status > 0) order by id desc LIMIT '.$pindex.','.$pageSize,[':openid'=>$openid]);
        foreach ($record as $key=>$item) {
            $record[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $record[$key]['updatetime'] = date('Y年m月d日',$item['updatetime']);
            $record[$key]['month'] = date('Y年m月',$item['createtime']);
            $record[$key]['thumb'] = $item['goods_id'] ? tomedia(pdo_getcolumn('ewei_shop_goods',['id'=>$item['goods_id']],'thumb')) : "https://www.paokucoin.com/img/backgroup/libaoImg.png";
        }
        if(!$record){
            show_json(0,"暂无信息");
        }
        show_json(1,['record'=>$record,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize]);
    }

    /**
     * 购买年卡
     */
    public function order()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $money = $_GPC['money'];
        $level_id = $_GPC['level_id'];
        if($openid == "" || $money == "" || $level_id == ""){
            show_json(0,"参数不完整");
        }
	//查找用户信息
        $member = pdo_get('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid]);
        if($member['is_open'] == 1 && $member['expire_time'] - time() > 3600*10 ){
            show_json(0,'您已是年卡会员');
        }
        $level = pdo_get('ewei_shop_member_memlevel',['uniacid'=>$uniacid,'id'=>$level_id]);
        if($level['price'] != $money){
            show_json(0,"价格不正确");
        }
        if($openid == "sns_wa_owRAK46O_IFxtLx7GnznEPEcAXGE"){
            $money = 0.01;
        }
        //生成订单号
        $order_sn = "LEV".date('YmdHis').random(12);
        //添加订单
        $order_id = $this->addorder($openid,$order_sn,$money,$member,'','购买年卡id=5');
        //微信支付
        $payinfo = array( "openid" => substr($openid,7), "title" => "购买年卡", "tid" => $order_sn, "fee" =>$money );
        $res = $this->model->wxpay($payinfo, 32);
        if(is_error($res)){
            show_json(0,$res);
        }
        $this->addmemberlog($openid,$order_sn,$money,$level_id."购买年卡");
        $res['order_id'] = $order_id;
        show_json(1,$res);
    }

    /**
     * 我的年卡中心
     */
    public function my()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],['id','openid','nickname','avatar','realname','is_open','expire_time']);
        $member['is_expire'] = $member['is_open'] == 1 && $member['expire_time'] - time() <= 3600*10 ? 1 : 0;
	$member['expire'] = date('Y-m-d',$member['expire_time']);
        show_json(1,['member'=>$member]);
    }

    /**
     *  领取礼包
     */
    public function get()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        //接收参数 并判断参数的完整性
        $openid = $_GPC['openid'];
        $level_id = $_GPC['level_id'];
        $address_id = $_GPC['address_id'];
        $money = $_GPC['money'];
        //记录id
        $record_id = $_GPC['record_id'];
        $good_id = $_GPC['goods_id'];
        if($openid == "" || $level_id == "" || $record_id == "" || $address_id == "" || $money == "" || $good_id == ""){
            show_json(0,"参数不完善");
        }
        //判断支付金额  是否正确
        $price = $this->change_address($address_id,$openid,$uniacid);
        if($price['price'] != $money){
            show_json(0,"支付金额不正确");
        }
        //把礼包的信息查出来  然后 把他的商品转译出来  判断 要领取的商品在不在其中
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods_id = unserialize($level['goods_id']);
        if(!in_array($good_id,$goods_id)){
            show_json(0,"领取商品有误");
        }
        //把年里礼包的商品给查出来
        $goods = pdo_get('ewei_shop_goods','uniacid="'.$uniacid.'" and id="'.$good_id.'" and status = 1 and total > 0',['id','thumb','title','marketprice']);
        //查询该记录的信息
        $record = pdo_get('ewei_shop_level_record',['uniacid'=>$uniacid,'level_id'=>$level_id,'id'=>$record_id,'openid'=>$openid]);
        //判断这个月的记录状态
        if($record['status'] > 0){
	    show_json(0,"权益礼包已领取或过期");
        }
        //查询领取记录里面的已领过的状态
        $log = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').'where uniacid = "'.$uniacid.'" and openid = :openid and level_id = "'.$level_id.'" and status > 0',[':openid'=>$openid]);
        if(count($log) > 0 && (date('Ymd',time()) < $record['month']."10" || date('Ymd',time()) > $record['month']."21")){
	    show_json(0,"权益商品每月10日到20日领取");
        }
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        //生成订单号
        $order_sn = "LQ".$level_id.date('YmdHis').random(12);
        //添加订单
        $order_id = $this->addorder($openid,$order_sn,$money,$member,$address_id,"领取年卡".$record["month"]."权益",$goods);
        //如果是第一次支付   金额为零 不用唤醒支付  直接改变状态   然后 架订单的时候 也判断了  让status=1
        if($money == 0){
             pdo_update('ewei_shop_level_record',['goods_id'=>$good_id,'status'=>1,'updatetime'=>time()],['id'=>$record_id]);
            show_json(2,"领取成功");
        }
        //唤起微信支付
        $payinfo = array( "openid" => substr($openid,7), "title" => "领取年卡".$record["month"]."权益", "tid" => $order_sn, "fee" =>$money );
        $res = $this->model->wxpay($payinfo, 33);
        //唤醒支付修改记录里面的商品id
        pdo_update('ewei_shop_level_record',['goods_id'=>$good_id],['id'=>$record_id]);
        $res['order_id'] = $order_id;
        show_json(1,$res);
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $order_id = $_GPC['order_id'];
        if($openid == "" || $order_id == ""){
            show_json(-1,"参数信息不完整");
        }
        if(pdo_exists('ewei_shop_order',['uniacid'=>$uniacid,'openid'=>$openid,'id'=>$order_id])){
            pdo_update('ewei_shop_order',['status'=>-1,'canceltime'=>time()],['id'=>$order_id]);
            show_json(1,"取消支付成功");
        }else{
            show_json(0,"订单信息错误");
        }
    }

    /**
     * 地址列表接口
     */
    public function address_list()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        $uniacid = $_W['uniacid'];
        if($openid == ""){
            show_json(0,"用户openid不能为空");
        }
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_member_address').'where uniacid="'.$uniacid.'" and openid = :openid and deleted = 0 order by isdefault desc,id desc',[':openid'=>$openid]);
        if(!$list){
            show_json(-1,"暂无地址，请去添加地址");
        }
        $data = $this->change_address($list[0]['id'],$openid,$uniacid);
        show_json(1,['list'=>$list,'data'=>$data]);
    }

    /**
     * 地址切换接口
     */
    public function change()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $address_id = $_GPC['address_id'];
        $openid = $_GPC['openid'];
        $data = $this->change_address($address_id,$openid,$uniacid);
        show_json(1,['data'=>$data]);
    }

    /**
     * @param $address_id
     * @param $openid
     * @param $uniacid
     * @return int
     */
    public function change_address($address_id,$openid,$uniacid)
    {
        $record = pdo_fetch('select * from '.tablename('ewei_shop_level_record').'where openid = :openid and uniacid = "'.$uniacid.'" order by id asc',[':openid'=>$openid]);
        $user_address = pdo_get('ewei_shop_member_address',['openid'=>$openid,'uniacid'=>$uniacid,'id'=>$address_id,'deleted'=>0]);
        if(empty($user_address)){
            show_json(0,"用户地址错误");
        }
        $base_address = pdo_getcolumn('ewei_shop_express_set',['uniacid'=>$uniacid,'id'=>1],'express_set');
        $base_express = explode(';',$base_address);
        if(in_array($user_address['province'],$base_express)){
	        $data['is_remote'] = 0;
	        //这个人的第一条记录为0的话  说明是第一次领取
            $data['price'] = $record['status'] > 0 ? 10 : 0;
        }else{
            $data['is_remote'] = 1;
            //这个人的第一条记录为0的话  说明是第一次领取
            $data['price'] = $record['status'] > 0 ? 20 : 0;
        }
        return $data;

    }

    /**
     * 添加订单
     * @param $openid
     * @param $order_sn
     * @param $money
     * @param $member
     * @param $address_id
     * @param $remark
     * @param $goods
     * @return bool
     */
    public function addorder($openid,$order_sn,$money,$member,$address_id = "",$remark = "",$goods = [])
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //因为领取的权益是实物产品  所以需要地址
        $address = empty($address_id)?null:serialize(pdo_get('ewei_shop_member_address',['id'=>$address_id,'uniacid'=>$uniacid]));
        $data = [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'ordersn'=>$order_sn,
            'goodsprice'=>$goods['marketprice']?:0,
            'price'=>$money,
            'createtime'=>time(),
            'agentid'=>$member['agent_id'],
            'addressid'=>$address_id?:0,
            'address'=>$address,
            'dispatchprice'=>$money,
            'remark'=>$remark,
        ];
        $data['status'] = $money == 0 ? 1 :0;
        //查找订单号  里面有没有LQ  是不是  不等于false
//        if(strpos('LQ',$order_sn) !== false){
//            $data['status'] = 1;
//        }
        pdo_insert('ewei_shop_order',$data);
        $orderid = pdo_insertid();
        if(!empty($goods)){
            $add = [
                'uniacid'=>$uniacid,
                'goodsid'=>$goods['id'],
                'orderid'=>$orderid,
                'price'=>$money,
                'total'=>1,
                'createtime'=>time(),
                'realprice'=>0,
                'changeprice'=>$goods['marketprice'],
                'oldprice'=>$goods['marketprice'],
                'openid'=>$openid,
                'optionname'=>'',
            ];
            pdo_insert('ewei_shop_order_goods',$add);
        }
        return $orderid;
    }

    /**
     * 添加用户日志
     * @param $openid
     * @param $order_sn
     * @param $money
     * @param $remark
     * @return bool
     */
    public function addmemberlog($openid,$order_sn,$money,$remark)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $data= [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>$remark,
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$money,
            'rechargetype'=>'waapp',
        ];
        return pdo_insert('ewei_shop_member_log',$data);
    }

    /**
     * 个人中心的年卡入口
     */
    public function mem_level()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        $uniacid = $_W['uniacid'];
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        show_json(1,['is_open'=>$member['is_open'],'expire_time'=>date('Y年m月d日',$member['expire_time'])]);
    }

    /**
     * 年卡弹窗  折扣宝弹窗  所有消息弹窗
     */
    public function level_alert(){
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        //no_id 的1是年卡
        $no_id = empty($_GPC['no_id']) ? 1 : $_GPC['no_id'];
        $openid = $_GPC['openid'];
        if(pdo_exists('notice',['uniacid'=>$uniacid,'openid'=>$openid,'status'=>1,'no_id'=>$no_id])){
            show_json(0,"已查看");
        }else{
            pdo_insert('notice',['uniacid'=>$uniacid,'openid'=>$openid,'status'=>1,'no_id'=>$no_id,'createtime'=>time()]);
            show_json(1);
        }
    }

    /**
     * 商品列表
     */
    public function goods_list()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $level_id = $_GPC['level_id'];
        if($openid == "" || $level_id == "") show_json(0,"参数不完整");
        //$is_open = pdo_getcolumn('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],'is_open');
        //if($is_open == 0) show_json(0,"您无权查看");
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods_id = unserialize($level['goods_id']);
        $img = unserialize($level['thumb_url']);
        array_unshift($img,$level['thumb']);
        $goods = [];
        $month = date('Ym');
        $record = pdo_get('ewei_shop_level_record',['openid'=>$openid,'month'=>$month,'status'=>1]);
        foreach ($goods_id as $key=>$item){
            $good = pdo_get('ewei_shop_goods',['uniacid'=>$uniacid,'id'=>$item],['id','title','thumb','total','productprice','marketprice','bargain']);
            $good['thumb'] = tomedia($good['thumb']);
            $good['image'] = tomedia($img[$key]);
            $good['is_get'] = !empty($record) ? $record['goods_id'] == $item ? 1 :2 : 0;
            $goods[] = $good;
        }
        show_json(1,['get'=>empty($record)?0:1,'goods'=>$goods]);
    }
}
?>