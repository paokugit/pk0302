<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Myown_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
        exit("Access Denied");
    }

    public function __construct()
    {
        global $_GPC;
        global $_W;
        parent::__construct();
    }

    /**
     * 生成个人收款码
     * 如果他有merchid  你就请求那个payment/index/qrcode
     * 如果没有merchid 且agentlevel!=5 is_own =0  请传参数openid  请求这个接口
     */
    public function qrcode()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,'参数不完整');
        }
        //折扣宝收款码
        $rebate_url = 'pages/discount/zkbscancode/zkbscancode';
        $rebate_back= 'zhekoubao';
        //卡路里收款码
        $calorie_url  = 'pages/discount/kllscancode/kllscancode';
        $calorie_back = 'kaluli';
        //生成二维码
        $rebate = m('qrcode')->createHelpPoster(['back'=>$rebate_back,'url'=>$rebate_url,'cate'=>2],$openid);
        $calorie =  m('qrcode')->createHelpPoster(['back'=>$calorie_back,'url'=>$calorie_url,'cate'=>1],$openid);
        if(!$rebate || !$calorie){
            show_json(0,'生成商家二维码错误');
        }
        show_json(1,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr'],'calorie'=>$calorie['qrcode'],'calorie_qr'=>$calorie['qr']]);
    }

    /**
     * 付99购买个人收款码
     */
    public function order()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        $money = $_GPC['money'];
        //$type == 1  微信支付   $type == 2   余额支付
        $type = $_GPC['type'];
        $uniacid = $_W['uniacid'];
        if($type == "" || $money == "" || $openid == ""){
            show_json(0,'参数不完整');
        }
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        if($member['agentlelvel'] == 5){
            show_json(0,'您已经是店主身份,不需要再购买个人收款码');
        }
        if($member['agentlelvel'] != 5 && $member['is_own'] == 1){
            show_json(0,"你已购买过个人收款码");
        }
        if($money != 99){
            show_json(0,"输入的金额不对");
        }
        //购买个人收款码的订单前缀 OWN  生成订单号
        $order_sn = "OWN".date('YmdHis',time()).random(12);
        //其中merchid  和  ismerch  都是有自己默认值的  因为 这个是平台的商品
        // 所以 ismerch  和 merchid 都用默认的0 type 是0 默认的  正常支付
        $add = [
            'openid'=>$openid,
            'uniacid'=>$uniacid,
            'ordersn'=>$order_sn,
            'price'=>$money,
            'goodsprice'=>$money,
            'status'=>0,
            'paytype'=>21,
            'createtime'=>time(),
        ];
        //加入订单记录
        pdo_insert('ewei_shop_order',$add);
        //用户付款的日志
        $add1= [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>'购买个人收款码',
            'createtime'=>time(),
            'money'=>-$money,
            'status'=>1,
            'rechargetype'=>'wxapp',
        ];
        if($type == 1){
            $payinfo = array( "openid" => substr($openid,7), "title" => "购买个人收款码", "tid" => $order_sn, "fee" =>$money );
            $res = $this->model->wxpay($payinfo, 31);
            if(is_error($res)){
                show_json(0,$res);
            }
            $add1['status'] = 0;
            //这个是用户的余额变化记录表
            pdo_insert('ewei_shop_member_log',$add1);
            show_json(1,$res);
        }
        //这个是用户的余额变化记录表
        pdo_insert('ewei_shop_member_log',$add1);
        //如果是余额付款的话 加上减余额记录
        $data = [
            'openid'=>$openid,
            'uniacid'=>$uniacid,
            'num'=>-$money,
            'createtime'=>time(),
            'module'=>"ewei_shopv2",
            'credittype'=>"credit2",
            'remark'=>"余额购买个人收款码",
        ];
        //这个是credit资产变化记录
        pdo_insert('mc_credits_record',$data);
        pdo_insert('ewei_shop_member_credit_record',$data);
        //如果是用户余额支付  可以减余额  并改变状态
        pdo_update('ewei_shop_member',['is_own'=>1,'credit2'=>bcsub($member['credit2'],$money,2)],['openid'=>$openid,'uniacid'=>$uniacid]);
        show_json(1,"支付成功");
    }

    /**
     * 查个人资金账户的余额
     */
    public function getCredit()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"参数不完整");
        }
        $credit5 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],'credit5');
        show_json(1,['credit5'=>$credit5]);
    }

    /**
     * 个人收款资产提现
     */
    public function own_draw()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $money = $_GPC['money'];
        if($openid == "" || $money == ""){
            show_json(0,"请完善参数信息");
        }
        if($money < 1){
            show_json(0,"最少提现1块");
        }
        $redis = redis();
        if($redis->get($openid.$money)){
            show_json(0,"申请处理中，请稍后...");
        }else{
            $token = md5($openid.$money.time());
            $redis->set($openid.$money,$token,30);
        }
        $credit5 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid],'credit5');
        //bccomp  比较 两个精确的小数的大小   == -1  是前者小于后者
        if(bccomp($credit5,$money,2) == -1){
            show_json(0,"资金余额不足");
        }
        //个人资产提现 logno的  开头是OW  own_withdraw
        $order_sn = "OW".date('YmdHis').random(12);
        $data = [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'type'=>1,
            'logno'=>$order_sn,
            'title'=>'个人资金提现',
            'createtime'=>time(),
            'status'=>0,
            'money'=>$money,
            'realmoney'=>bcsub($money,bcmul($money,0.03,2),2),
            'deductionmoney'=>bcmul($money,0.03,2),
            'draw_type'=>3,
        ];
        pdo_begin();
        try{
            pdo_insert('ewei_shop_member_log',$data);
            pdo_update('ewei_shop_member',['credit5'=>bcsub($credit5,$money,2)],['openid'=>$openid,'uniacid'=>$uniacid]);
            pdo_commit();
        }catch(Exception $exception){
            pdo_rollback();
        }
        show_json(1,"提现成功");
    }

    /**
     * 个人资产提现记录
     */
    public function draw_log()
    {
        global $_GPC;
        //获取参数信息
        $openid = $_GPC['openid'];
        $page = max(1,$_GPC['page']);
        if($openid == "" || $page == ""){
            show_json(0,"参数不完善");
        }
        //分页以及算总数
        $pageSize = 20;
        $psize = ($page - 1)*$pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_log')." where openid = :openid and title = '个人资金提现'",[':openid'=>$openid]);
        //查询提现记录  FROM_UNIXTIIME()    sql语句中 时间戳转换成时间格式
        $list = pdo_fetchall('select id,title,money,FROM_UNIXTIME(createtime) as createtime,status,refuse_reason from '.tablename('ewei_shop_member_log').' where openid = :openid and title = "个人资金提现" order by id desc LIMIT '.$psize.','.$pageSize,[':openid'=>$openid]);
        if(!$list){
            show_json(-1,"暂无信息");
        }
        show_json(1,['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize]);
    }

    /**
     * 商家提现页面
     */
    public function merch()
    {
        global $_W;
        global $_GPC;
        $merchid = $_GPC['merchid'];
        $item = p('merch')->getMerchPrice($merchid,1,1);
        show_json(1,['orderprice'=>number_format($item['orderprice'],2),'realpricerate'=>number_format($item['realpricerate'],2)]);
    }

    /**
     * 商家提现
     */
    public function merch_draw()
    {
        global $_W;
        global $_GPC;
        $merchid = $_GPC['merchid'];
        $item = p('merch')->getMerchPrice($merchid,1,1);
        $list = p('merch')->getMerchPriceList($merchid,0,0,1);
        $order_num = count($list);
        $redis = redis();
        if($redis->get($merchid.'merch_token')){
            show_json(0,"您的提现申请已提交，为防止重复操作,请1分钟后谨慎操作");
        }else{
            $token = md5($merchid.random(12));
            $redis->set($merchid.'merch_token',$token,30);
        }
        $cansettle = true;
        if ($item['realpricerate'] <= 0) {
            $cansettle = false;
        }
        if (($item['realprice'] <= 0)  || empty($list))
        {
            show_json(0, '您没有可提现的金额');
        }
        if($item['realpricerate'] < 0.3){
            show_json(0, '提现金额不足');
        }
        $applytype = intval($_GPC['applytype']);
        $insert = array();
        $insert['uniacid'] = $_W['uniacid'];
        $insert['merchid'] = $merchid;
        $insert['applyno'] = m('common')->createNO('merch_bill', 'applyno', 'MO');
        $insert['orderids'] = iserializer($item['orderids']);
        $insert['ordernum'] = $order_num;
        $insert['price'] = $item['price'];
        $insert['realprice'] = $item['realprice'];
        $insert['realpricerate'] = $item['realpricerate'];
        $insert['finalprice'] = $item['finalprice'];
        $insert['orderprice'] = $item['orderprice'];
        $insert['payrateprice'] = round(($item['realpricerate'] * $item['payrate']) / 100, 2);
        $insert['payrate'] = $item['payrate'];
        $insert['applytime'] = time();
        $insert['status'] = 1;
        $insert['applytype'] = $applytype;
        $insert['type'] = 1;

        pdo_insert('ewei_shop_merch_bill', $insert);
        $billid = pdo_insertid();
        foreach ($list as $k => $v )
        {
            $orderid = $v['id'];
            $insert_data = array();
            $insert_data['uniacid'] = $_W['uniacid'];
            $insert_data['billid'] = $billid;
            $insert_data['orderid'] = $orderid;
            $insert_data['ordermoney'] = $v['realprice'];
            pdo_insert('ewei_shop_merch_billo', $insert_data);
            $change_order_data = array();
            $change_order_data['merchapply'] = 1;
            pdo_update('ewei_shop_order', $change_order_data, array('id' => $orderid));
        }
        $merch_user = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where uniacid=:uniacid and id=' . $merchid, array(':uniacid' => $_W['uniacid']));
        p('merch')->sendMessage(array('merchname' => $merch_user['merchname'], 'money' => $insert['realprice'], 'realname' => $merch_user['realname'], 'mobile' => $merch_user['mobile'], 'applytime' => time()), 'merch_apply_money');

        if (!empty($merch_user["wxopenid"])){
            $postdata=array(
                'keyword1'=>array(
                    'value'=>$item['realprice'],
                    'color' => '#ff510'
                ),
                'keyword2'=>array(
                    'value'=>"提现申请",
                    'color' => '#ff510'
                ),
                'keyword3'=>array(
                    'value'=>date("Y-m-d",time()),
                    'color' => '#ff510'
                ),
                'keyword4'=>array(
                    'value'=>"商家已提现申请，等待管理员确认",
                    'color' => '#ff510'
                )

            );
            p("app")->mysendNotice($merch_user["wxopenid"], $postdata, "", "nSJSBKVYwLYN_LcsUXyvTLVjseO46nQA8RqKsRnsiRs");
        }

        show_json(1, "提现申请成功");
    }

    /**
     * 商家提现记录
     */
    public function merch_log()
    {
        global $_W;
        global $_GPC;
        $merchid = $_GPC['merchid'];
        $page = max(1,$_GPC['page']);
        $pageSize = 20;
        $pindex = ($page - 1)*$pageSize;
        if($page == "" || $merchid == ""){
            show_json(0,"参数不完整");
        }
        $uniacid = $_W['uniacid'];
        $total = pdo_count('ewei_shop_merch_bill',['uniacid'=>$uniacid,'merchid'=>$merchid]);
        $list = pdo_getall('ewei_shop_merch_bill','merchid="'.$merchid.'" and uniacid="'.$uniacid.'" and type = 1 order by id desc LIMIT '.$pindex.','.$pageSize,['id','realprice','realpricerate','status','applytime']);
        foreach ($list as $key=>$item){
            $list[$key]['applytime'] = date('Y-m-d H:i:s',$item['applytime']);
            $list[$key]['title'] = "资金提现";
        }
        if(!$list){
            show_json(-1,"暂无信息");
        }
        show_json(1,['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize]);
    }

    /**
     * 贡献机列表
     */
    public function devote()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"用户的openid不能为空");
        }
        $uniacid = $_W['uniacid'];
        //如果是贡献机用户
        $devote = pdo_getall('ewei_shop_devote_record',['uniacid'=>$_W['uniacid'],'openid'=>$openid,'status'=>1]);
        foreach ($devote as $key=>$item){
            if($item['expire'] < time()){
                pdo_update('ewei_shop_devote_record',['status'=>0],['id'=>$item['id']]);
            }
            //如果是我和郝艳萍同学 签到就给记录
            //if($openid == "sns_wa_owRAK44_gHTrMTJMVSxFy-jtNef8" || $openid == "sns_wa_owRAK43dDy1s6i0_rbVfZUqgx854"){
                if(pdo_exists('ewei_shop_devote_log',['devote_id'=>$item['id'],'openid'=>$openid,'day'=>date('Y-m-d')])){
                    continue;
                }else{
                    pdo_insert('ewei_shop_devote_log',['devote_id'=>$item['id'],'openid'=>$openid,'num'=>100,'day'=>date('Y-m-d'),'createtime'=>time()]);
                }
            //}
        }
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_devote_record').' where uniacid = "'.$uniacid.'" and openid = :openid and status = 1',[':openid'=>$openid]);
        $count = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_devote_record').'where uniacid = "'.$uniacid.'" and openid = :openid',[':openid'=>$openid]);
        $list = $this->getlist($total,$uniacid,$openid);
        foreach ($list as $key=>&$item){
            $item['id'] = implode(',',$item['id']);
            $num = array_count_values($item['log']);
            $item['devote'] = ($item['count'] - $num[1]) * 100;
            if($item['is_open'] != 0){
                $item['is_open'] = $item['devote'] == 0 ? 2 : 1;
            }
            unset($item['log']);
        }
        show_json(1,['valid'=>$total,'no_valid'=>$count-$total,'list'=>$list]);
    }

    /**
     * @param $total
     * @param $uniacid
     * @param $openid
     * @return int
     */
    public function getlist($total,$uniacid,$openid)
    {
        $list = [];
        $size = 1;
        for ($i=1;$i<=$total;$i++){
            $key = $i%8 != 0 ? $i%8 : 8;
            $num = ceil(bcdiv($i,8,2));
            $list[$key]['image'] = "https://www.paokucoin.com/img/backgroup/s-gxserve.gif";
            $id = pdo_fetchcolumn('select id from '.tablename('ewei_shop_devote_record').'where openid =:openid and uniacid = "'.$uniacid.'" and status = 1 LIMIT '.($i-1).','.$size,[':openid'=>$openid]);
            $list[$key]['log'][] = pdo_get('ewei_shop_devote_log',['openid'=>$openid,'uniacid'=>$uniacid,'devote_id'=>$id,'status'=>1,'day'=>date('Y-m-d',time())])?1:0;
            $list[$key]['id'][] = $id;
            $list[$key]['count'] = $num;
            $list[$key]['is_open'] = 1;
        }
        if($total < 8){
            for ($i = 0 ; $i < 8-$total; $i++){
                array_push($list,['image'=>"https://www.paokucoin.com/img/backgroup/n-gxserve@2x.png",'devote'=>0,'count'=>0,'is_open'=>0,'id'=>[]]);
            }
        }
        return $list;
    }

    /**
     * 领取贡献值
     */
    public function get_devote(){
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        $ids = $_GPC['ids'];
        if($openid == "" || $ids == ""){
            show_json(0,"参数不完整");
        }
        $uniacid = $_W['uniacid'];
        $redis = redis();
        if($redis->get($openid.$ids)){
            show_json(0,"领取中，请稍候...");
        }else{
            $token = md5($openid.$ids.time());
            $redis->set($openid.$ids,$token,60);
        }
        $ids = explode(',',$ids);
        $log = $this->devotelog($ids,$openid,$uniacid);
        show_json(1,"领取成功");
    }

    /**
     * @param $ids
     * @param $openid
     * @param $uniacid
     * @return bool
     */
    public function devotelog($ids,$openid,$uniacid)
    {
        $day = date('Y-m-d');
	$i = 0;
        foreach ($ids as $id){
            $log = pdo_get('ewei_shop_devote_log',['devote_id'=>$id,'openid'=>$openid,'uniacid'=>$uniacid,'day'=>$day]);
            if($log['status'] == 1){
                continue;
            }
            pdo_update('ewei_shop_devote_log',['status'=>1],['devote_id'=>$id,'openid'=>$openid,'uniacid'=>$uniacid,'day'=>$day]);
            $i+=100;
        }
	    //m('member')->setCredit($openid,'credit4',$i,"贡献机领取".$i."贡献值");
	    m('member')->setCredit($openid,'credit4',$i,"贡献机领取");
        return true;
    }
}
?>
