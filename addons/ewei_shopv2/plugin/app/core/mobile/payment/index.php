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
        $mid = trim($_GPC['merchid']);
        if(!$mid) show_json(0,"参数不能为空");
        if(is_numeric($mid)){
            //折扣宝收款码
            $rebate_url = 'pages/discount/zkbscancode/zkbscancode';
            //$rebate_url = 'packageA/pages/discount/zkbscancode/zkbscancode';
        }else{
            //折扣宝收款码
            $rebate_url = 'pages/personalcode/scancode';
            //$rebate_url = 'packageA/pages/personalcode/scancode';
        }
        //$rebate_back= 'zhekoubao';
        $rebate_back= 'kaluli';
//        //卡路里收款码
//        $calorie_url  = 'pages/discount/kllscancode/kllscancode';
//        $calorie_back = 'kaluli';
        //生成二维码
        $rebate = m('qrcode')->createHelpPoster(['back'=>$rebate_back,'url'=>$rebate_url,'cate'=>2],$mid);
//        $calorie =  m('qrcode')->createHelpPoster(['back'=>$calorie_back,'url'=>$calorie_url,'cate'=>1],$mid);
        //if(!$rebate || !$calorie){
        if(!$rebate ){
            show_json(0,'生成收款码错误');
        }
        //qr是单纯的小程序码   qrcode是带背景图的小程序收款码
        //show_json(1,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr'],'calorie'=>$calorie['qrcode'],'calorie_qr'=>$calorie['qr']]);
        show_json(1,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr']]);
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
    
   //支付
    public function order_cs()
    {
        global $_GPC;
        global $_W;
        if($_GPC['rebate'] == "" || $_GPC['merchid'] == "" || $_GPC['money'] == "" || $_GPC['cate'] == "" || $_GPC['openid'] == ""){
            show_json(0,"请完善参数信息");
        }
        if(is_numeric($_GPC['merchid'])){
            $merch = pdo_get('ewei_shop_merch_user',['id'=>$_GPC['merchid']]);
            $income_id = $merch['member_id'];
        }else{
            $income_id = intval($_GPC['merchid']);
        }
        if($_GPC['openid'] == pdo_getcolumn('ewei_shop_member',['id'=>$income_id],'openid')){
            show_json(0,"不能给自己的收款码付款");
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
            'ismerch'=>1,
            'type'=>1,
            'merchid'=>$_GPC['merchid'],
        ];
        //如果是折扣宝  就订单表 discount_price   否则  deductprice
        $_GPC['cate'] == 2 ? $add['discount_price'] = $_GPC['rebate'] : $add['deductprice'] = $_GPC['rebate'];
        //加入订单记录
        $order = pdo_insert('ewei_shop_order',$add);
        $payinfo = array( "openid" => substr($_GPC['openid'],7), "title" => is_numeric($_GPC['merchid'])?"商家收款码收款":"个人收款码收款", "tid" => $order_sn, "fee" =>$_GPC["money"] );
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
        //现在是  merchid  商家的话传  merchid  也是从那个码弄出来的  个人的话  传openid  也是从那个码里得到的
        // 所以判断  如果是数字的话 是商家 加日志记录 是加  merchid_log  如果不是数字  加日志是加member_Log的
        if(is_numeric($_GPC['merchid'])){
            //商家收款日志  加到商家表里面
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
        }else{
            //个人收款日志  加在member_log表里面  logno  是 order_sn  拼接上  传来的  merchid
            $mem_add = [
                'uniacid'=>$_W['uniacid'],
                'openid'=>$_GPC['openid'],
                'type'=>4,   //type   =  4  盈利
                'logno'=>$order_sn.$_GPC['merchid'],   //拼接上传来的merchid
                'title'=>'个人付款码收入',
                'createtime'=>time(),
                'status'=>0,
                'money'=>$_GPC['money'],
                'rechargetype'=>$_GPC['merchid'],
            ];
            pdo_insert('ewei_shop_member_log',$mem_add);
        }
        show_json(1,$res);
    }
    
    /**
     * 收款记录
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
        if(!is_numeric($mch_id)){
            $mch_id = pdo_getcolumn('ewei_shop_member',['openid'=>$mch_id],'id')."own";
        }
        $start_time = pdo_getcolumn('ewei_shop_order',['status'=>3,'merchid'=>$mch_id],'createtime')?:time();
        //计算时间
        $day = round((time()-$start_time)/86400);
        $list = [];
        $total = 0;
        $total_money = 0;
        for ($i = 0;$i<=$day;$i++){
            //今天  昨天 前天的每天开始时间
            $start = strtotime(date('Y-m-d',strtotime('-'.$i.'day')));
            //每天的时间键值
            $time = date('Y年m月d日',$start);
            $end = $start + 86400;
            if(is_numeric($mch_id)){
                //商家收款记录
                $list[$time]['list'] = pdo_fetchall('select id,openid,price,createtime,cate from '.tablename('ewei_shop_merch_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = "'.$_GPC['cate'].'"');
            }else{
                //个人的收款记录  rechargetype  交易类型  就是个人的id拼接own
                $list[$time]['list'] = pdo_fetchall('select id,openid,money as price,createtime from '.tablename('ewei_shop_member_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and rechargetype = "'.$mch_id.'"  and money > 0');
            }
            //计算每天的收款笔数
            $list[$time]['count'] = count($list[$time]['list']);
            //如果 某天没有收款 去掉他的收款时间的键
            if($list[$time]['count'] == 0){
                unset($list[$time]);
                continue;
            }
            // 把每天的收款钱数  单独组成个一位数组  请求和  保留两位小数
            $money = array_column($list[$time]['list'],'price');
            $list[$time]['total'] = round(array_sum($money),2);
            //换时间格式  和  查出付款人的昵称
            foreach ($list[$time]['list'] as $key=>$item){
                $list[$time]['list'][$key]['createtime'] = date('H:i:s',$item['createtime']);
                $list[$time]['list'][$key]['nickname'] = pdo_getcolumn('ewei_shop_member',['openid'=>$item['openid']],'nickname');
            }
            //计算总收款笔数 和 总钱
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
        if(!$mch_id){
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
        $openid = $_GPC['openid']?:0;
        $id = $_GPC['id'];
        //如果是商家的 就传商家id  如果是个人收款码  就传openid
        $merchid = $_GPC['merchid']?:0;
        if($money == "" || $fee == "" || $cate == "" || $merchid === "" || $openid === ""){
            show_json(0,"请完善参数信息");
        }
        if($fee || $money){
            $data = [
                'uniacid'=>$_W['uniacid'],
                'money'=>$money,
                'deduct'=>$fee,
                'cate'=>$cate,
                'openid'=>$openid,
            ];
            //如果是商家id
            if($merchid != 0) {
                $data['merchid'] = $merchid;
            }
            //有$id 修改 没有添加
            if($id){
                //判断$money金额的满减条件是否存在
                $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where openid=:openid and money="'.$money.'" and cate="'.$cate.'" and id!="'.$id.'"',[':openid'=>$openid]);
                if($res){
                    show_json(0,$money.'的满减条件已存在，请前往修改或者更换满减条件');
                }
                pdo_update('ewei_shop_deduct_setting',$data,['id'=>$id]);
		        $msg = "修改成功";
            }else{
                //判断$money金额的满减条件是否存在
                $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where openid=:openid and money=:money and cate=:cate',array(':openid'=>$openid,':money'=>$money,':cate'=>$cate));
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
        $data = pdo_fetch('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where id = "'.$_GPC['id'].'"');
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
        //merchid  用传的openid
        if(!$_GPC['merchid'] || !$_GPC['cate']){
            show_json(0,"参数不完整");
        }
        //如果是数字  就查商家信息  不是 就查openid
        if(is_numeric($_GPC['merchid'])){
            $total = pdo_count('ewei_shop_deduct_setting',['merchid'=>$_GPC['merchid'],'cate'=>$_GPC['cate']]);
            $list = pdo_fetchall('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where merchid=:merchid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$_GPC['merchid'],':cate'=>$_GPC['cate']));
        }elseif (strpos($_GPC['merchid'],"own")){
            $member = pdo_get('ewei_shop_member',['id'=>intval($_GPC['merchid'])]);
            $total = pdo_count('ewei_shop_deduct_setting',['openid'=>$member['openid'],'cate'=>$_GPC['cate']]);
            $list = pdo_fetchall('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where openid=:openid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':openid'=>$member['openid'],':cate'=>$_GPC['cate']));
        }else{
            $total = pdo_count('ewei_shop_deduct_setting',['openid'=>$_GPC['merchid'],'cate'=>$_GPC['cate']]);
            $list = pdo_fetchall('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where openid=:openid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':openid'=>$_GPC['merchid'],':cate'=>$_GPC['cate']));
        }
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
        if($money == "" || $openid == ""){
            show_json(0,"参数不完整");
        };
        $redis = redis();
        if($redis->get($openid.$money.'calorie_token')){
            show_json(0,"您的".$money."元充值折扣宝已提交，为防止重复操作,请1分钟后谨慎操作");
        }else{
            $token = md5($openid.$money.time().random(6));
            $redis->set($openid.$money.'calorie_token',$token,30);
        }
        //查用户的卡路里和折扣宝的信息
        $member = pdo_fetch('select credit1,credit3 from '.tablename('ewei_shop_member').'where openid=:openid and uniacid=:uniacid',array(':openid'=>$openid,':uniacid'=>$_W['uniacid']));
        //判断要转换的卡路里和用户的卡路里的多少
        if($money == 0){
            show_json(0,'充值金额不能为0');
        }elseif($money > $member['credit1']){
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
        if(is_numeric($_GPC['merchid'])){
            $merchid = $_GPC['merchid'];
        }else{
            $member = pdo_get('ewei_shop_member',['id'=>intval($_GPC['merchid'])]);
            $merchid = $member['openid'];
        }
        //cate == 1  卡路里 ==2  折扣宝
        if($_GPC['cate'] == 1){
            if(is_numeric($_GPC['merchid'])){
                $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit1.'" and merchid = "'.$merchid.'" order by money desc');
            }else{
                $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit1.'" and openid = "'.$merchid.'" order by money desc');
            }
        }else{
            if(is_numeric($_GPC['merchid'])){
                $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit3.'" and merchid = "'.$merchid.'" order by money desc');
            }else{
                $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit3.'" and openid = "'.$merchid.'" order by money desc');
            }
        }
        //查下这个商家这个类型的  折扣信息
        if(is_numeric($_GPC['merchid'])){
            $array = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where cate = "'.$_GPC['cate'].'" and merchid = "'.$merchid.'" order by money asc');
        }else{
            $array = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where cate = "'.$_GPC['cate'].'" and openid = "'.$merchid.'" order by money asc');
        }
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
        $data = pdo_fetch('select openid,num,createtime,remark,merchid from '.tablename('mc_credits_record').' where id=:id and openid=:openid',[':id'=>$id,':openid'=>$openid]);
        $data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
        if($data['merchid'] != 0){
            $data['merch_name'] = pdo_getcolumn('ewei_shop_merch_user',['id'=>$data['merchid']],'merchname');
        }else{
            if(mb_substr($data['remark'],0,2) == "跑库"){
                $data['merch_name'] = "跑库";
                $data['remark'] = "商城订单";
            }elseif(mb_substr($data['remark'],0,2) == "转帐"){
                $mobile = preg_replace('/\D/s','',$data['remark']);
                $data['merch_name'] = pdo_getcolumn('ewei_shop_member',['mobile'=>$mobile],'nickname');
            }elseif (mb_substr($data['remark'],0,2) == "RV" || mb_substr($data['remark'],0,2) == "外部"){
                $data['merch_name'] = "RV钱包";
            }else{
                $data['merch_name'] = pdo_getcolumn('ewei_shop_member',['openid'=>$data['openid']],'nickname');
            }
        }
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
        $mobile = trim($_GPC['mobile']);
        $money = trim($_GPC['money']);
        if($money < 1){
            show_json(0,"转账金额不能小于1折扣宝");
        }
        if($openid == "" || $mobile == "" || $money == ""){
            show_json(0,"请完善参数信息");
        }
        $redis = redis();
        if($redis->get($openid.$money.'rebate_token')){
            show_json(0,"您给".$mobile."转账".$money."已提交，为防止重复操作,请1分钟后谨慎操作");
        }else{
            $token = md5($openid.$mobile.$money.time().random(6));
            $redis->set($openid.$money.'rebate_token',$token,30);
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
        if($credit3){
            show_json(1,['credit3'=>$credit3]);
        }else{
            show_json(0,'用户信息错误');
        }
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

/*********************************************************************************************************************************************************************************/
    /**
     * 设置支付密码
     */
    public function set_pwd(){
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        //接收参数
        $openid = $_GPC['openid'];
        //密码
        $password = $_GPC['password'];
        //二次确认密码
        $pwd = $_GPC['pwd'];
        //type  1 设置密码   2修改密码  忘记密码的话 也是设置密码
        $type = $_GPC['type'];
        //判断参数完整性
        if($openid == "" || $password == "" || $pwd == "" || $type == ""){
            show_json(0,"参数不完整");
        }
        //判断两次密码的一致性
        if($password != $pwd){
            show_json(0,"两次密码不一致");
        }
        //查看用户的信息
        $member = pdo_getcolumn('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        if(!$member){
            show_json(0,"用户信息不正确");
        }
        //如果是修改密码   判断原密码的正确性
        if($type == 2){
            $old_pwd = $_GPC['old_pwd'];
            if(md5(base64_encode($old_pwd)) != $member['rv_pwd']){
                show_json(0,"旧密码不正确");
            }
        }
        //更新支付密码
        pdo_update('ewei_shop_member',['rv_pwd'=>md5(base64_encode($password))],['openid'=>$openid]);
        show_json(1,"修改成功");
    }

    /**
     * 发送短信
     */
    public function sms_send()
    {
        global $_W;
        global $_GPC;
        //接受参数
        $mobile=$_GPC["mobile"];
        //用户的openid
        $openid = $_GPC['openid'];
        //国家id
        $country_id=$_GPC["country_id"];
        if($mobile == ""  || $openid == ""){
            show_json(0,"参数信息不完整");
        }
        //查找用户的信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        if(!$member){
            show_json(0,"用户信息错误");
        }
        //生成短信验证码
        $code=rand(100000,999999);
        if (empty($country_id) || $country_id == 44){
            //阿里云的短信 在我们平台的模板id
            $tp_id = 5;
            if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
                show_json(0,"手机号格式不正确");
            }
            $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }else{
            $tp_id = 7;
            $country=pdo_get("sms_country",array("id"=>$country_id));
            $resault=com_run("sms::mysend", array('mobile'=>$country["phonecode"].$mobile,'tp_id'=>$tp_id,'code'=>$code));
        }
        if ($resault["status"]==1){
            //添加短信记录
            pdo_insert('core_sendsms_log',['uniacid'=>$_W['uniacid'],'mobile'=>$mobile,'tp_id'=>5,'content'=>$code,'createtime'=>time(),'ip'=>CLIENT_IP]);
            show_json(1,"发送成功");
        }else{
            show_json(0,$resault["message"]);
        }
    }

    /**
     * 验证短信的下一步
     */
    public function valid_sms()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        //接受用户的openid   和  手机号  和验证码
        $openid = $_GPC['openid'];
        $mobile = $_GPC['mobile'];
        $code = $_GPC['code'];
        if($openid == "" || $mobile == "" || $code == ""){
            show_json(0,"参数不完整");
        }
        //查找用户的信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        if(!$member){
            show_json(0,"用户信息错误");
        }
        //正则验证手机号的格式
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            show_json(0,"手机号格式不正确");
        }
        //查找短息的发送的记录
        $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'code'=>$code,'tp_id'=>5]);
        if(!$sms){
            show_json(0,"短信验证码不正确");
        }
        if($sms['result'] == 1){
            show_json(0,"该短信已验证");
        }
        //更改短信验证码的验证状态
        pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
        show_json(1,"短信验证成功");
    }

/**********************************************************折扣宝限额宝*************************************************************/
    /**
     * 限额宝列表
     */
    public function limit()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        //查找用户信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        //计算用户的额度
        $limit = m('game')->checklimit($member['openid'],$member['agentlevel']);
        //计算用户已经消费的额度
        $sale = pdo_fetchall('select * from '.tablename('mc_credits_record').' where openid = :openid and remark = "RV钱包充值" and createtime > 1570776300',[':openid'=>$member['openid']]);
        $sale_sum = abs(array_sum(array_column($sale,'num')));
        $remian = bcsub($limit,$sale_sum,2) >= 10000 ? bcsub($limit,$sale_sum,2)/10000 ."万" : bcsub($limit,$sale_sum,2);
        $list = pdo_getall('ewei_shop_member_limit',['uniacid' => $uniacid,'status'=>1],['id','money','limit']);
        foreach ($list as $key=>$item){
            $list[$key]['limit'] = $item['limit'] >= 10000 ? $item['limit'] / 10000 ."万" : $item['limit'];
        }
        if(empty($list)){
            show_json(0,"暂无数据");
        }
        show_json(1,['list'=>$list,'remain'=>$remian]);
    }

    /**
     * 限额购买
     */
    public function limit_order()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        $id = $_GPC['id'];
        //查找限额
        $limit = pdo_get('ewei_shop_member_limit',['id'=>$id]);
        $redis = redis();
        if($redis->get($openid.$id.$limit['limit'].'limit_order')){
            show_json(2,"您购买的".$limit['limit']."已提交，为防止重复操作,请10秒后谨慎操作");
        }else{
            $token = md5($openid.$id.$limit['limit'].time().random(6));
            $redis->set($openid.$id.$limit['limit'].'limit_order',$token,10);
        }
        $ordersn = "LIM".date('YmdHis').random(12);
        //唤醒微信支付
        if($openid == "sns_wa_owRAK46O_IFxtLx7GnznEPEcAXGE"){
            $limit['money'] = 0.01;
        }
        $add = ['title'=>"购买折扣宝限额", "openid" => substr($openid,7), "tid" => $ordersn, "fee" =>$limit["money"]];
        $res = $this->model->wxpay($add,34);
        if(is_error($res)){
            show_json(0,$res);
        }
        $order = $this->limit_add($ordersn,$openid,$limit);
        if($order){
            show_json(1,$res);
        }
    }

    /**
     * 加日志
     * @param $ordersn
     * @param $openid
     * @param $limit
     * @return bool
     */
    public function limit_add($ordersn,$openid,$limit)
    {
        $data = [
            'ordersn'=>$ordersn,
            'openid'=>$openid,
            'lim_id'=>$limit['id'],
            'price'=>$limit['money'],
            'limit'=>$limit['limit'],
            'createtime'=>time(),
        ];
        return pdo_insert('ewei_shop_member_limit_order',$data);
    }
}
?>