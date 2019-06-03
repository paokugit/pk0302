<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage 
{
    /**
     * 生成二维码
     */
	public function qrcode()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        $mid = $_W['merchmanage']['merchid'];
        $mid = 31;
        //折扣宝收款码
        $rebate_url = 'pages/discount/zkbscancode/zkbscancode';
        $rebate_back= 'zhekoubao';
        //卡路里收款码
        $calorie_url  = 'pages/discount/kllscancode/kllscancode';
        $calorie_back = 'kaluli';
        //生成二维码
        $rebate = m('qrcode')->createHelpPoster(['back'=>$rebate_back,'url'=>$rebate_url],$mid);
        $calorie =  m('qrcode')->createHelpPoster(['back'=>$calorie_back,'url'=>$calorie_url],$mid);
        show_json(1,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr'],'calorie'=>$calorie['qrcode'],'calorie_qr'=>$calorie['qr']]);
    }

    /**
     * 获得卡路里和折扣宝余额
     */
    public function getCredit(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],credit1);
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],credit3);
        show_json(1,['credit1'=>$credit1,'credit3'=>$credit3]);
    }

    /**
     *  支付生成订单
     */
    public function order()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        global $_W;
        $order_sn = base64_encode(date('Ymd',time()).'_'.'31'.'_'.$_GPC['money'].'_'.$_GPC['rebate'].'_'.$_GPC['cate']);
        $data = [
            'random'=>random(32),
            'body'=>'商家商户收款码收款',
            'ip'=>CLIENT_IP,
            'money'=>$_GPC['money'],
            'rebate'=>$_GPC['rebate'],
            'url'=>$_W['siteroot'].'addons/ewei_shopv2/payment/wchat/notify/shopCode',   //回调地址
            'openid'=>substr($_GPC['openid'],7),
            'out_order'=>$order_sn,     //订单号
        ];
        $res = m('pay')->pay($data);
        show_json(1,['res'=>$res]);
    }

    /**
     * 收款记录
     */
    public function record()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $mch_id = $_GPC['merchid'];
        //页数
        $page = max(1,($_GPC['page']));
        //每页显示条数
        $pageSize = 2;
        //第几页从第几个显示
        $psize = ($page-1)*$pageSize;
        $openid = pdo_fetch('select m.openid from '.tablename('ewei_shop_member').'m join '.tablename('ewei_shop_merch_user').('mu on m.id=mu.member_id').' where mu.id = "'.$mch_id.'"');
        $list =  pdo_fetchall('select logno from '.tablename('ewei_shop_member_log') . ' where uniacid ="'.$_W['uniacid'].'" and openid = "'.$openid['openid'].'" and type = 3 LIMIT '.$psize. ','.$pageSize);
        foreach ($list as $key=>$item){
            //$user = pdo_get('ewei_shop_member_log',['logno'=>substr($item['logno'],7)],['openid','money','createtime']);
            $user = pdo_get('ewei_shop_member_log',['logno'=>$item['logno']],['openid','money','createtime']);
            $list[$key]['logno'] = substr($item['logno'],7);
            $list[$key]['openid'] = $user['openid'];
            $list[$key]['money'] = $user['money'];
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$user['createtime']);
            $list[$key]['nickname'] = pdo_getcolumn('ewei_shop_member',['openid'=>$user['openid']],'nickname');
        }
        show_json(1,['list'=>$list]);
    }

    /**
     * 设置卡路里折扣接口
     */
    public function set()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //判断提交方式
        //if(!$_W['ispost']){
        //    app_error(AppError::$RequestError);
        //}
        //获取参数
        $money = $_GPC['money'];
        $fee = $_GPC['deduct'];
        $cate = $_GPC['cate'];
        $id = $_GPC['id'];
        if($fee && $money){
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
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //判断提交方式
        if(!$_W['ispost']){
            app_error(AppError::$RequestError);
        }
        $data = pdo_fetch('select id,money,merchid,deduct,cate from '.tablename('ewei_shop_deduct_setting').'where id = "'.$_GPC['id'].'" and merchid = "'.$_GPC['merchid'].'"');
        show_json(1,['data'=>$data]);
    }

    /**
     * 卡路里折扣列表
     */
    public function getset()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $page = intval($_GPC['page']);
        $pageSize = 8;
        $spage = ($page-1)*$pageSize;
        $list = pdo_fetchall('select id,money,merchid,deduct,cate from '.tablename('ewei_shop_deduct_setting').'where merchid=:merchid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$_GPC['merchid'],':cate'=>$_GPC['cate']));
        show_json(1,['list'=>$list]);
    }

    /**
     * 卡路里转换折扣宝余额
     */
    public function change()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        if($_W['ispost']){
            app_error(AppError::$RequestError);
        }
        $money = $_GPC['money'];
        $openid = $_GPC['openid'];
        $openid = "sns_wa_owRAK4-smphSYPkphpDAFOnsuy08";
        //查用户的卡路里和折扣宝的信息
        $member = pdo_fetch('select credit1,credit3 from '.tablename('ewei_shop_member').'where openid=:openid and uniacid=:uniacid',array(':openid'=>$openid,':uniacid'=>$_W['uniacid']));
        //判断要转换的卡路里和用户的卡路里的多少
        if($money > $member['credit1']){
            show_json(0,'您的卡路里不足');
        }else{
            //计算转换后的用户的卡路里和折扣宝的余额
            $credit1 = $member['credit1'] - $money;
            $credit3 = $member['credit3'] + $money;
            //更新用户的卡路里和折扣宝的余额
            pdo_update('',['credit1'=>$credit1,'credit3'=>$credit3],['openid'=>$openid]);
            $data = [
                'openid'=>$openid,
                'uniacid'=>$_W['uniacid'],
                'credittype'=>'credit1',
                'num'=>$money,
                'createtime'=>time(),
                'remark'=>"卡路里转换折扣宝",
                'module'=>"ewei_shopv2",
            ];
            pdo_insert('ewei_shop_credit_record',$data);
            show_json(1);
        }
    }

    /**
     * 输入买单金额  返回用户可用的折扣
     */
    public function getDeduct(){
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],'credit1');
        $credit3 = pdo_getcolumn('ewei_shop_member',['openid'=>$_GPC['openid']],'credit3');
        //cate == 1  卡路里 ==2  折扣宝
        if($_GPC['cate'] == 1){
            $list = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit1.'" and merchid = "'.$_GPC['merchid'].'" order by money desc');
        }else{
            $list = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$_GPC['money'].'" and cate = "'.$_GPC['cate'].'" and deduct <="'.$credit3.'"  and merchid = "'.$_GPC['merchid'].'" order by money desc');
        }
        show_json(1,['list'=>$list]);
    }
}
?>