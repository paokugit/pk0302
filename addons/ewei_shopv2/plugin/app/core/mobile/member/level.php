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
        $member = pdo_get('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid],['nickname','realname','is_open',"FROM_UNIXTIME(expire_time) as expire"]);
        //待领取的优惠券  两个
        $coupon = pdo_fetchall('select cd.id,cd.used,co.deduct,co.enough,co.couponname from '.tablename('ewei_shop_coupon_data').'cd join '.tablename('ewei_shop_coupon').'co on co.id=cd.couponid'.' where cd.gettype = 1 and cd.openid = "'.$openid.'" and cd.used = 0 order by id desc LIMIT 0,2');
        //特权产品列表
        $goods = pdo_getall('ewei_shop_goods','status = 1 and is_right = 1 and total > 0 order by id desc LIMIT 0,8',['id','title','thumb','total','productprice','marketprice']);
        foreach ($goods as $key=>$item){
            $goods[$key]['thumb'] = tomedia($item['thumb']);
        }
        //本月的权益礼包
        $month = date('Ym',time());
        $level = pdo_get('ewei_shop_level_record',['openid'=>$openid,'uniacid'=>$uniacid,'month'=>$month],['id','openid','level_name','level_id','goods_id','status','month','FROM_UNIXTIME(updatetime) as updatetime']);
        $good = pdo_get('ewei_shop_goods',['id'=>$level['goods_id'],'uniacid'=>$uniacid],['thumb','productprice']);
        $level = array_merge($level,['thumb'=>tomedia($good['thumb']),'price'=>$good['productprice']]);
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
        $level_id = $_GPC['id'];
        if($level_id == ""){
            show_json(0,"年卡礼包id不能为空");
        }
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        show_json(1,['level'=>$level]);
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
        $pindex = ($page - 1) * pageSize;
        //计算记录总数
        $year_month = date('Ym',time());      //当前的年月份
        $total = pdo_count('ewei_shop_level_record','openid = "'.$openid.'" and uniacid = "'.$uniacid.'" and month <= "'.$year_month.'"');
        //查询记录以及分页
        $record = pdo_getall('ewei_shop_level_record','openid = "'.$openid.'" and uniacid = "'.$uniacid.'" and month <= "'.$year_month.'" order by id desc LIMIT '.$pindex.','.$pageSize);
        foreach ($record as $key=>$item) {
            $record[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $record[$key]['updatetime'] = date('Y年m月d日',$item['updatetime']);
            $record[$key]['month'] = substr($item['month'],4);
            $record[$key]['thumb'] = tomedia(pdo_getcolumn('ewei_shop_goods',['id'=>$item['goods_id']],'thumb'));
            //如果今天的年月份  大于记录中的 则更新他为失效   或者  月份相同  日期大于20  并把更新时间改成当月的21号为失效时间   并且状态为未领取
            if((date('Ym',time()) > $item['month'] || date('Ym',time()) == $item['month'] && date('d',time()) > 20) && $item['status'] == 0){
                pdo_update('ewei_shop_level_record',['status'=>2,'updatetime'=>strtotime($item['month']."21")],['uniacid'=>$uniacid,'id'=>$item['id']]);
                //给记录改变状态  并且给失效时间
                $record[$key]['status'] = 2;
                $record[$key]['updatetime'] = date('Y-m-d H:i:s',strtotime($item['month']."21"));
            }
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
        $level = pdo_get('ewei_shop_member_memlevel',['uniacid'=>$uniacid,'id'=>$level_id]);
        if($level['price'] != $money){
            show_json(0,"价格不正确");
        }
        //生成订单号
        $order_sn = "LEV".date('YmdHis').random(12);
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid]);
        //添加订单
        $this->addorder($openid,$order_sn,$money,$member,'','购买年卡id=5');
        //微信支付
        $payinfo = array( "openid" => substr($openid,7), "title" => "购买年卡", "tid" => $order_sn, "fee" =>$money );
        $res = $this->model->wxpay($payinfo, 32);
        if(is_error($res)){
            show_json(0,$res);
        }
        $this->addmemberlog($openid,$order_sn,$money,$level_id."购买年卡");
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
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],['id','openid','nickname','avatar','realname','is_open','FROM_UNIXTIME(expire_time) as expire']);
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
        $openid = $_GPC['openid'];
        $level_id = $_GPC['level_id'];
        $address_id = $_GPC['address_id'];
        //记录id
        $record_id = $_GPC['record_id'];
        if($openid == "" || $level_id == "" || $record_id == "" || $address_id == ""){
            show_json(0,"参数不完善");
        }
        //查询该记录的信息
        $record = pdo_get('ewei_shop_level_record',['uniacid'=>$uniacid,'level_id'=>$level_id,'id'=>$record_id]);
        //if(date('Ymd',time()) < $record['month']."10" || date('md',time()) > $record['month']."20"){
        if(date('Ymd',time()) < $record['month']."10" || date('Ymd',time()) > $record['month']."30"){
            show_json(0,$record['month']."权益礼包不在领取日期");
        }
        if($record['status'] > 0){
            show_json(0,$record['month']."权利礼包已领取");
        }
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        //生成订单号
        $order_sn = "LQ".date('YmdHis').random(12);
        //更新领取记录的状态
        pdo_update('ewei_shop_level_record',['status'=>1,'updatetime'=>time()],['id'=>$record_id,'level_id'=>$level_id,'uniacid'=>$uniacid]);
        //因为领取的权益是实物产品
        $address = serialize(pdo_get('ewei_shop_member_address',['id'=>$address_id,'uniacid'=>$uniacid]));
        $this->addorder($openid,$order_sn,0,$member,$address,"领取年卡".$record["month"]."权益");
        show_json(1,"领取成功");
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
        $list = pdo_getall('ewei_shop_member_address',['uniacid'=>$uniacid,'openid'=>$openid,'deleted'=>0]);
        if(!$list){
            show_json(-1,"暂无地址，请去添加地址");
        }
        show_json(1,['list'=>$list]);
    }

    /**
     * 添加订单
     * @param $openid
     * @param $order_sn
     * @param $money
     * @param $member
     * @param $address
     * @param $remark
     * @return bool
     */
    public function addorder($openid,$order_sn,$money,$member,$address = "",$remark = "")
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $data = [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'ordersn'=>$order_sn,
            'goodsprice'=>$money,
            'price'=>$money,
            'status'=>0,
            'createtime'=>time(),
            'agentid'=>$member['agent_id'],
            'address'=>$address?:null,
            'remark'=>$remark,
        ];
        //查找订单号  里面有没有LQ  是不是  不等于false
        if(strpos('LQ',$order_sn) !== false){
            $data['status'] = 1;
        }
        return pdo_insert('ewei_shop_order',$data);
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
}
?>