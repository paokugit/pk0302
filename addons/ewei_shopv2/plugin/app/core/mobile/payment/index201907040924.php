<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage 
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
     * 生成二维码
     */
	public function qrcode()
    {
        global $_GPC;
        $mid = $_GPC['merchid'];
        if(!$mid) show_json(0,"商家id不能为空");
        //折扣宝收款码
        $rebate_url = 'pages/discount/zkbscancode/zkbscancode';
        $rebate_back= 'zhekoubao';
        //卡路里收款码
        $calorie_url  = 'pages/discount/kllscancode/kllscancode';
        $calorie_back = 'kaluli';
        //生成二维码
        $rebate = m('qrcode')->createHelpPoster(['back'=>$rebate_back,'url'=>$rebate_url,'cate'=>2],$mid);
        $calorie =  m('qrcode')->createHelpPoster(['back'=>$calorie_back,'url'=>$calorie_url,'cate'=>1],$mid);
        if(!$rebate || !$calorie){
            show_json(0,'生成商家二维码错误');
        }
        show_json(1,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr'],'calorie'=>$calorie['qrcode'],'calorie_qr'=>$calorie['qr']]);
    }

    /**
     * 获得卡路里和折扣宝余额
     */
    public function getCredit()
    {
        global $_GPC;
        if(!$_GPC['openid']){
            show_json(0,"用户的openID不能为空");
        }
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],credit1);
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],credit3);
        if(!$credit1 || !$credit3){
            show_json(0,"余额获取失败");
        }
        show_json(1,['credit1'=>$credit1,'credit3'=>$credit3]);
    }

    /**
     *  支付生成订单
     */
    public function order()
    {
        global $_GPC;
        global $_W;
        if($_GPC['rebate'] == "" || $_GPC['merchid'] == "" || $_GPC['money'] == "" || $_GPC['cate'] == "" || $_GPC['openid'] ==""){
            show_json(0,"请完善参数信息");
        }
        //扫码订单前缀 SC  生成订单号
        $order_sn = "SC".$_GPC['cate'].date('YmdHis',time()).random(12);
        $add = [
            'openid'=>$_GPC['openid'],
            'uniacid'=>$_W['uniacid'],
            'ordersn'=>$order_sn,
            'price'=>$_GPC['money'],
            'goodsprice'=>bcadd($_GPC['money'],$_GPC['rebate'],2),
            'status'=>0,
            'paytype'=>21,
            'createtime'=>time(),
            'merchid'=>$_GPC['merchid'],
            'ismerch'=>1,
            'type'=>1
        ];
        //加入订单记录
        $order = pdo_insert('ewei_shop_order',$add);
        //微信支付的参数
//        $data = [
//            'random'=>random(32),
//            'body'=>'商家商户收款码收款',
//            'ip'=>CLIENT_IP,
//            'money'=>$_GPC['money'],
//            'url'=>$_W['siteroot'].'addons/ewei_shopv2/payment/wchat/notify/shopCode',   //回调地址
//            'openid'=>substr($_GPC['openid'],7),
//            'out_order'=>$order_sn,     //订单号
//        ];
//        //请求微信的支付接口    返回的是唤醒支付的参数
//        $res = m('pay')->pay($data);
        $payinfo = array( "openid" => substr($_GPC['openid'],7), "title" => "商家商户收款码收款", "tid" => $order_sn, "fee" =>$_GPC["money"] );
        $res = $this->model->wxpay($payinfo, 30);
        if(is_error($res)){
            show_json(0,$res);
        }
        //用户付款的日志
        $add2= [
            'uniacid'=>$_W['uniacid'],
            'openid'=>$_GPC['openid'],
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>'扫商家付款码支付',
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$_GPC['money'],
            'rechargetype'=>'wxscan',
        ];
        pdo_insert('ewei_shop_member_log',$add2);
//        //获得用户的卡路里  和  折扣宝
//        $member = pdo_get('ewei_shop_member',['openid'=>$_GPC['openid']],['credit1','credit3']);
//        if($_GPC['cate'] == 1){
//            $credit1 = $member['credit1'] - $_GPC['rebate'];
//        }elseif ($_GPC['cate'] == 2){
//            $credit3 = $member['credit3'] - $_GPC['rebate'];
//        }
//        pdo_update('ewei_shop_member',['openid'=>$_GPC['openid']],['credit1'=>$credit1,'credit3'=>$credit3]);
        //商家收款日志
        $mch_add = [
            'uniacid'=>$_W['uniacid'],
            'openid'=>$_GPC['openid'],
            'price'=>$_GPC['money'],
            'cate'=>$_GPC['cate'],
            'ordersn'=>$order_sn,
            'merchid'=>$_GPC['merchid'],
            'createtime'=>time(),
            'status'=>0,
        ];
        pdo_insert('ewei_shop_merch_log',$mch_add);
        show_json(1,$res);
    }
    
   //支付
    public function order_cs()
    {
        global $_GPC;
        global $_W;
        if($_GPC['rebate'] == "" || $_GPC['merchid'] == "" || $_GPC['money'] == "" || $_GPC['cate'] == "" || $_GPC['openid'] == ""){
            show_json(0,"请完善参数信息");
        }
        //扫码订单前缀 SC  生成订单号
        $order_sn = "SC".$_GPC['cate'].date('YmdHis',time()).random(12);
        $add = [
            'openid'=>$_GPC['openid'],
            'uniacid'=>$_W['uniacid'],
            'ordersn'=>$order_sn,
            'price'=>$_GPC['money'],
            'goodsprice'=>bcadd($_GPC['money'],$_GPC['rebate'],2),
            'status'=>0,
            'paytype'=>21,
            'createtime'=>time(),
            'merchid'=>$_GPC['merchid'],
            'ismerch'=>1,
            'type'=>1,
        ];
        //加入订单记录
        $order = pdo_insert('ewei_shop_order',$add);
        $payinfo = array( "openid" => substr($_GPC['openid'],7), "title" => "商家商户收款码收款", "tid" => $order_sn, "fee" =>$_GPC["money"] );
        $res = $this->model->wxpay($payinfo, 30);
        if(is_error($res)){
            show_json(0,$res);
        }
        //用户付款的日志
        $add2= [
            'uniacid'=>$_W['uniacid'],
            'openid'=>$_GPC['openid'],
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>'扫商家付款码支付',
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$_GPC['money'],
            'rechargetype'=>'wxscan',
        ];
        pdo_insert('ewei_shop_member_log',$add2);
        //商家收款日志
        $mch_add = [
            'uniacid'=>$_W['uniacid'],
            'openid'=>$_GPC['openid'],
            'price'=>$_GPC['money'],
            'cate'=>$_GPC['cate'],
            'ordersn'=>$order_sn,
            'merchid'=>$_GPC['merchid'],
            'createtime'=>time(),
            'status'=>0,
        ];
        pdo_insert('ewei_shop_merch_log',$mch_add);
        show_json(1,$res);
    }

    //支付
    public function order_css()
    {
        global $_GPC;
        global $_W;        
        if(!$_GPC['rebate'] || !$_GPC['merchid'] || !$_GPC['money'] || !$_GPC['cate'] || !$_GPC['openid']){
            show_json(0,"请完善参数信息");
        }
        //扫码订单前缀 SC
        $order_sn ="SC".date('YmdHis',time()).random(12);
        //如果没有这条日志  加日志  并  给商家加钱
        pdo_insert('ewei_shop_member_log',$add1);
        //用户付款的日志
        $add2= [
            'uniacid'=>$_W['uniacid'],
            'openid'=>$_GPC['openid'],
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>'扫商家付款码支付',
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$_GPC['money'],
            'rechargetype'=>'wxscan',
        ];
        pdo_insert('ewei_shop_member_log',$add2);
        $payinfo = array( "openid" => substr($_GPC['openid'],7), "title" => "商家商户收款码收款", "tid" => $order_sn, "fee" =>$_GPC["money"] );
        $res = $this->model->wxpay($payinfo, 30);
        if(!is_error($res)){
            show_json(1,$res);
        }
        show_json(0,['res'=>$res]);
    }
    
    /**
     * 收款记录  以前的
     */
    public function oldrecord()
    {
        global $_W;
        global $_GPC;
        $mch_id = $_GPC['merchid'];
        if(!$mch_id || !$_GPC['cate']){
            show_json(0,"请完善参数信息");
        }
        //计算这个店铺成交的第一个订单的日期
        $start_time = pdo_getcolumn('ewei_shop_order',['status'=>3,'merchid'=>$mch_id],'createtime');
        //计算时间
        $day = round((time()-$start_time)/86400);
        $list = [];
        $total = 0;
        $total_money = 0;
        for ($i = 0;$i<=$day;$i++){
            $start = strtotime(date('Y-m-d',strtotime('-'.$i.'day')));
            $time = date('Y年m月d日',$start);
            $end = $start + 86400;
            $list[$time]['list'] = pdo_fetchall('select id,openid,price,createtime,cate from '.tablename('ewei_shop_merch_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = "'.$_GPC['cate'].'"');
            $list[$time]['count'] = count($list[$time]['list']);
            if($list[$time]['count'] == 0){
                unset($list[$time]);
                continue;
            }
            $money = array_column($list[$time]['list'],'price');
            $list[$time]['total'] = round(array_sum($money),2);
            foreach ($list[$time]['list'] as $key=>$item){
                $list[$time]['list'][$key]['createtime'] = date('H:i:s',$item['createtime']);
                $list[$time]['list'][$key]['nickname'] = pdo_getcolumn('ewei_shop_member',['openid'=>$item['openid']],'nickname');
            }
            $total+=$list[$time]['count'];
            $total_money += $list[$time]['total'];
         }
        if(!$list){
            show_json(0,"暂无信息");
        }
        show_json(1,['list'=>$list,'total'=>$total,'total_money'=>$total_money,'cate'=>$_GPC['cate']]);
    }

    /**
     * 收款记录
     */
    public function record()
    {
        global $_W;
        global $_GPC;
        $mch_id = $_GPC['merchid'];
        //页数
        $page = max(1,($_GPC['page']));
        //每页显示条数
        $pageSize = 8;
        //第几页从第几个显示
        $psize = ($page-1)*$pageSize;
        if(!$mch_id || !$_GPC['openid']){
            show_json(0,"请完善参数信息");
        }
        $list = pdo_fetchall('select id,openid,price,createtime from '.tablename('ewei_shop_order').' where status = 3 and merchid = "'.$mch_id.'" LIMIT '.$psize.','.$pageSize);
        foreach ($list as $key=>$item){
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $list[$key]['nickname'] = pdo_getcolumn('ewei_shop_member',['openid'=>$item['openid']],'nickname');
        }
        if(!$list){
            show_json(0,"暂无信息");
        }
        show_json(1,['list'=>$list,'total'=>count($list),'pageSize'=>$pageSize,'page'=>$page]);
    }

    /**
     * 设置卡路里折扣接口
     */
    public function set()
    {
        global $_W;
        global $_GPC;
        //获取参数
        $money = $_GPC['money'];
        $fee = $_GPC['deduct'];
        $cate = $_GPC['cate'];
        $id = $_GPC['id'];
        if(!$money || !$fee || !$cate || !$_GPC['merchid']){
            show_json(0,"请完善参数信息");
        }
        if($fee || $money){
            $data = [
                'uniacid'=>$_W['uniacid'],
                'merchid'=>$_GPC['merchid'],
                'money'=>$money,
                'deduct'=>$fee,
                'cate'=>$cate,
            ];
            //有$id 修改 没有添加
            if($id){
                //判断$money金额的满减条件是否存在
                $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where merchid="'.$_GPC['merchid'].'" and money="'.$money.'" and cate="'.$cate.'" and id!="'.$_GPC['id'].'"');
                if($res){
                    show_json(0,$money.'的满减条件已存在，请前往修改或者更换满减条件');
                }
                pdo_update('ewei_shop_deduct_setting',$data,['id'=>$id]);
		        $msg = "修改成功";
            }else{
                //判断$money金额的满减条件是否存在
                $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where merchid=:merchid and money=:money and cate=:cate',array(':merchid'=>$_GPC['merchid'],':money'=>$money,':cate'=>$cate));
                if($res){
                    show_json(0,$money.'的满减条件已存在，请前往修改或者更换满减条件');
                }
                pdo_insert('ewei_shop_deduct_setting',$data);
		        $msg = "添加成功";
            }
            show_json(1,$msg);
        }else{
            show_json(0,'请填写完整参数');
        }
    }

    /**
     * 修改卡路里页面
     */
    public function edit()
    {
        global $_GPC;
        if(!$_GPC['id']) show_json(0,"参数信息不完整");
        $data = pdo_fetch('select id,money,merchid,deduct,cate from '.tablename('ewei_shop_deduct_setting').'where id = "'.$_GPC['id'].'"');
        if(!$data) show_json(0,'信息不存在');
        show_json(1,['data'=>$data]);
    }

    /**
     * 卡路里折扣列表
     */
    public function getset()
    {
        global $_GPC;
        $page = $_GPC['page']?intval($_GPC['page']):1;
        $pageSize = 8;
        $spage = ($page-1)*$pageSize;
        if(!$_GPC['merchid'] || !$_GPC['cate']){
            show_json(0,"参数不完整");
        }
        $total = pdo_count('ewei_shop_deduct_setting',['merchid'=>$_GPC['merchid'],'cate'=>$_GPC['cate']]);
        $list = pdo_fetchall('select id,money,merchid,deduct,cate from '.tablename('ewei_shop_deduct_setting').'where merchid=:merchid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$_GPC['merchid'],':cate'=>$_GPC['cate']));
        if(!$list){
            show_json(0,"暂无信息");
        }
        show_json(1,['list'=>$list,'pageSize'=>$pageSize,'total'=>$total,'page'=>$page]);
    }

    /**
     * 卡路里转换折扣宝余额
     */
    public function change()
    {
        global $_W;
        global $_GPC;
        $money = $_GPC['money'];
        $openid = $_GPC['openid'];
        if(!$money || !$openid){
            show_json(0,"参数不完整");
        }
        //查用户的卡路里和折扣宝的信息
        $member = pdo_fetch('select credit1,credit3 from '.tablename('ewei_shop_member').'where openid=:openid and uniacid=:uniacid',array(':openid'=>$openid,':uniacid'=>$_W['uniacid']));
        //判断要转换的卡路里和用户的卡路里的多少
        if($money > $member['credit1']){
            show_json(0,'您的卡路里不足');
        }else{
            //计算转换后的用户的卡路里和折扣宝的余额
            $credit1 = $member['credit1'] - $money;
            $credit3 = $member['credit3'] + $money*2;
            //更新用户的卡路里和折扣宝的余额
            $update = pdo_update('ewei_shop_member',['credit1'=>$credit1,'credit3'=>$credit3],['openid'=>$openid]);
            if(!$update){
                show_json(0,'卡路里转换成功');
            }
            $data = [
                'openid'=>$openid,
                'uniacid'=>$_W['uniacid'],
                'credittype'=>'credit1',
                'num'=>-$money,
                'createtime'=>time(),
                'remark'=>"卡路里转换折扣宝",
                'module'=>"ewei_shopv2",
            ];
            $add = [
                'openid'=>$openid,
                'uniacid'=>$_W['uniacid'],
                'credittype'=>'credit3',
                'num'=>$money*2,
                'createtime'=>time(),
                'remark'=>"卡路里转换折扣宝",
                'module'=>"ewei_shopv2",
            ];
            $record = pdo_insert('mc_credits_record',$data);
            $record = pdo_insert('mc_credits_record',$add);
            $member_record = pdo_insert('ewei_shop_member_credit_record',$data);
            $member_record = pdo_insert('ewei_shop_member_credit_record',$add);
            if(!$record || !$member_record){
                show_json(0,"加入记录失败");
            }
            show_json(1,"转账成功");
        }
    }

    /**
     * 输入买单金额  返回用户可用的折扣
     */
    public function getDeduct()
    {
        global $_GPC;
        if(!$_GPC['money'] || !$_GPC['openid'] || !$_GPC['cate'] || !$_GPC['merchid']){
            show_json(0,"参数不完整");
        }
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],'credit1');
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],'credit3');
        //cate == 1  卡路里 ==2  折扣宝
        if($_GPC['cate'] == 1){
            $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit1.'" and merchid = "'.$_GPC['merchid'].'" order by money desc');
        }else{
            $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit3.'"  and merchid = "'.$_GPC['merchid'].'" order by money desc');
        }
        //查下这个商家这个类型的  折扣信息
        $array = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where cate = "'.$_GPC['cate'].'" and merchid = "'.$_GPC['merchid'].'" order by money asc');
        //如果商家折扣信息数量小于等于0  等于说没有折扣信息
        if(count($array) <= 0){
            show_json(-1,'暂无折扣信息');
        }
        //折扣信息数大于0  且  最小的折扣信息money大于你输入的money 则  暂无符合的折扣信息  无所谓list有没有东西
        if(count($array) > 0 && $array[0]['money'] > $_GPC['money']){
            show_json(2,"暂无符合的折扣优惠");
        }
        //到这个时候 应该是  折扣信息数大于0  且 输入的金额大于最小金额  但是  list不存在数据
        if(!$list){
            show_json(0,"余额不足");
        }
        show_json(1,['list'=>$list]);
    }

    /**
     * 折扣宝的收支记录  之前的
     */
    public function rebate_record()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        if(!$openid){
            show_json(0,"用户信息错误");
        }
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit3');
        $fields = "id,num,createtime,remark,openid";
        //收入(也就是充值)  只有 后台充值和卡路里转换两种方式  和商家没关系
        $income = pdo_fetchall(' select '.$fields.' from '.tablename('mc_credits_record').' where credittype = "credit3" and num > 0 and openid = :openid',[':openid'=>$openid]);
        //支出  只有扫码折扣付支出   和商家有关 所以在回调时  加入merchid
        $pay = pdo_fetchall('select '.$fields.' from '.tablename('mc_credits_record').' where credittype = "credit3" and num < 0 and openid = :openid',[':openid'=>$openid]);
        foreach ($income as $key=>$item){
            $income[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
        }
        foreach ($pay as $key=>$item){
            $pay[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
        }
        show_json(1,['credit3'=>$credit3,'income'=>$income,'pay'=>$pay]);
    }

    /**
     * 折扣宝的收支记录
     */
    public function rebateRecord()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        $type = $_GPC['type'];
        if(!$openid || !$type){
            show_json(0,"参数信息不完善");
        }
        $page = max(1,$_GPC['page']);
        $pageSize = 8;
        $psize = ($page-1)*$pageSize;
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit3');
        $fields = "id,num,createtime,remark,openid";
        if($type == 1){
            $condition = ' and num > 0';
        }elseif ($type == 2){
            $condition = ' and num < 0';
        }
        $list = pdo_fetchall('select '.$fields.' from '.tablename('mc_credits_record').' where credittype ="credit3" and openid = :openid '.$condition  .' order by createtime desc LIMIT '.$psize .','.$pageSize,[':openid'=>$openid]);
        $total = pdo_fetchcolumn('select count(*) from '.tablename('mc_credits_record').' where credittype = "credit3" and openid = :openid '.$condition,[':openid'=>$openid]);
        foreach ($list as $key=>$item){
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            if(mb_substr($item['remark'],0,2) == "跑库"){
                if($item['num'] < 0){
                    $list[$key]['remark'] = "商城订单支付";
                }else{
                    $list[$key]['remark'] = "商城订单返还";
                }
            }
        }
        show_json(1,['credit3'=>$credit3,'list'=>$list,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total,'type'=>$type]);
    }

    /**
     * 扫码付款明细 详情
     */
    public function detail(){
        global $_GPC;
        $id = $_GPC['id'];
        $openid = $_GPC['openid'];
        if(!$id || !$openid) show_json(0,"请完善参数");
        $data = pdo_fetch('select openid,num,createtime,remark,merchid from '.tablename('mc_credits_record').' where id = "'.$id.'" and openid  = :openid',[':openid'=>$openid]);
        $data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
        $data['merch_name'] = pdo_getcolumn('ewei_shop_merch_user',['id'=>$data['merchid']],'merchname');
        if(mb_substr($data['remark'],0,2) == "跑库") $data['remark'] = "商城订单";
        if(!$data){
            show_json(0,"暂无信息");
        }
        show_json(1,$data);
    }

    /**
     * 折扣宝转账
     */
    public function rebate_change()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $mobile = $_GPC['mobile'];
        $money = $_GPC['money'];
        if($openid == "" || $mobile == "" || $money == ""){
            show_json(0,"请完善参数信息");
        }
        $to = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$uniacid]);
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        if(!$member){
            show_json(0,"用户信息不正确");
        }
        if(bccomp($member['credit3'],$money,2) == -1){
            show_json(0,"用户余额不足");
        }
        if(!$to){
            show_json(0,"收款人不存在");
        }
        if($to['openid'] == $member['openid']){
            show_json(0,'转账者和收款人相同');
        }
        //更新转账者折扣宝余额   减去  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcsub($member['credit3'],$money,2)],['openid'=>$member['openid'],'uniacid'=>$uniacid]);
        $this->addlog($member,$to,$money,1);
        //更新收款者  折扣宝余额   加上  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcadd($to['credit3'],$money,2)],['openid'=>$to['openid'],'uniacid'=>$uniacid]);
        $this->addlog($to,$member,$money,2);
        show_json(1);
    }

    /**
     * 转账页面
     */
    public function rebate()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"请完善参数");
        }
        $credit3 = pdo_getcolumn('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid],'credit3');
        show_json(1,['credit3'=>$credit3]);
    }

    /**
     * 查找手机号是否存在
     */
    public function check_phone()
    {
        global $_W;
        global $_GPC;
        if($_GPC['mobile'] == ""){
            show_json(0,"请完善参数");
        }
        if(pdo_exists('ewei_shop_member',['uniacid'=>$_W['uniacid'],'mobile'=>$_GPC['mobile']])){
            show_json(1);
        }else{
            show_json(0,"查无此人");
        }
    }

    /**
     * 添加折扣宝增减记录
     * @param $member
     * @param $to
     * @param $money
     * @param int $type   ==1  转账者加日志   ==2  收款者加日志
     */
    public function addlog($member,$to,$money,$type=1)
    {
        global $_W;
        $data = [
            'uniacid'=>$_W['uniacid'],
            'credittype'=>"credit3",
            'openid'=>$member['openid'],
            'module'=>"ewei_shopv2",
            'createtime'=>time(),
        ];
        if($type == 1){
            $data['num'] = -$money;
            $data['remark'] = "转帐给".$to['mobile'];
        }elseif ($type == 2){
            $data['num'] = $money;
            $data['remark'] = $to['nickname']."转入";
        }
        pdo_insert('mc_credits_record',$data);
        pdo_insert('ewei_shop_member_credit_record',$data);
    }
}
?>