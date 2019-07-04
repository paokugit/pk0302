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
     * 生成个人收款码
     * 如果他有merchid  你就请求那个payment/index/qrcode
     * 如果没有merchid 且agentlevel!=5 is_own =0  请传参数openid  请求这个接口
     */
    public function qrcode()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
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
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        if($member['agentlelvel'] == 5){
            show_json(0,'您已经是店主身份,不需要再购买个人收款码');
        }
        if($member['agentlelvel'] != 5 && $member['is_own'] == 1){
            show_json(0,"你已购买过个人收款码");
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
        if($type == 1){
            $payinfo = array( "openid" => substr($openid,7), "title" => "购买个人收款码", "tid" => $order_sn, "fee" =>$money );
            $res = $this->model->wxpay($payinfo, 31);
            if(is_error($res)){
                show_json(0,$res);
            }
            show_json(1,$res);
        }
        //用户付款的日志
        $add1= [
            'uniacid'=>$uniacid,
            'openid'=>$openid,
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>'购买个人收款码',
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$money,
            'rechargetype'=>'wechat',
        ];
        pdo_insert('ewei_shop_member_log',$add1);
        //加上减余额记录
        $data = [
            'openid'=>$openid,
            'uniacid'=>$uniacid,
            'num'=>-$money,
            'createtime'=>time(),
            'module'=>"ewei_shopv2",
            'credittype'=>"credit2",
            'remark'=>"用户购买个人收款码",
        ];
        pdo_insert('mc_credits_record',$data);
        pdo_insert('ewei_shop_member_credit_record',$data);
        show_json(1,"支付成功");
    }
}
?>