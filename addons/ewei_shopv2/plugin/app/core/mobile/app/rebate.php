<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Rebate_EweiShopV2Page extends AppMobilePage
{
    /**
     * 折扣付
     */
    public function main()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //卡路里和折扣宝余额
        $credit = pdo_get('ewei_shop_member',['id'=>$user_id],['credit1','credit3']);
        if(!$credit) $credit = ['credit1'=>0,'credit3'=>0];
        //贡献机数量 和 运行状态
        $devote_machine = m('app')->devote_machine($user_id);
        //贡献值  和  是否绑定手机号 微信
        $devote = m('app')->devote($user_id);
        app_error(0,['credit'=>$credit,'devote_machine'=>$devote_machine,'devote'=>$devote]);
    }

    /**
     * 收款付
     */
    public function rebate_qrcode()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        if(!empty($merch_user)){
            //折扣宝商家收款码
            $mid = $merch_user['id'];
            $rebate_url = 'pages/discount/zkbscancode/zkbscancode';
        }else{
            //折扣宝个人收款码
            $mid = $user_id;
            $rebate_url = 'pages/personalcode/scancode';
        }
        $rebate_back= 'kaluli';
        //生成二维码
        $rebate = m('qrcode')->createHelpPoster(['back'=>$rebate_back,'url'=>$rebate_url,'cate'=>2],$mid);
        if(!$rebate ){
            app_error(1,'生成收款码错误');
        }
        app_error(0,['rebate'=>$rebate['qrcode'],'rebate_qr'=>$rebate['qr']]);
    }

    /**
     * 折扣列表
     */
    public function rebate_get()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $page = max(1,$_GPC['page']);
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->rebate_get($user_id,$page);
        app_error(0,$data);
    }

    /**
     * 设置折扣
     */
    public function rebate_set()
    {
        global $_GPC;
        $money = $_GPC['money'];
        $fee = $_GPC['deduct'];
        $id = $_GPC['id'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->rebate_set($user_id,$money,$fee,$id);
        app_error($data['status'],$data['msg']);
    }

    /**
     * 资金账户
     */
    public function rebate_credit()
    {
        global $_GPC;
        $type = $_GPC['type'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $member = m('member')->getMember($user_id);
        //$type == 1 商家收款码的资金账户
        if($type == 1){
            $merchid = pdo_getcolumn('ewei_shop_merch_user',['member_id'=>$user_id],'id');
            $item = p('merch')->getMerchPrice($merchid,1,1);
            $data = ['orderprice'=>number_format($item['orderprice'],2),'realpricerate'=>number_format($item['realpricerate'],2)];
        }else{
            $data = ['credit5'=>$member['credit5']];
        }
        app_error(0,$data);
    }

    /**
     * 输入金额  获得可用折扣
     */
    public function rebate_deduct()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getMember($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $money = $_GPC['money'];
        $merchid = $_GPC['merchid'];
        $data = m('app')->rebate_deduct($user_id,$merchid,$money);
        app_error($data['status'],$data['msg']);
    }

    /**
     * 收款记录
     */
    public function rebate_record()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->rebate_record($user_id);
        app_error(0,$data);
    }

    /**
     * 资产提现
     */
    public function rebate_draw()
    {
        global $_GPC;
        //接受参数
        $money = $_GPC['money'];
        $type = $_GPC['type'];
        $applytype = $_GPC['applytype'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        if($money < 10){
            app_error(1,"最少提现10块");
        }
        //防止联系请求
        $redis = redis();
        if($redis->get($user_id.$money)){
            show_json(0,"申请处理中，请稍后...");
        }else{
            $token = md5($user_id.$money.time());
            $redis->set($user_id.$money,$token,30);
        }
        //$type == 1 个人资金提现  2商家资金提现
        if($type == 1){
            $data = m('app')->rebate_owndraw($user_id,$money);
        }else{
            $data = m('app')->rebate_merchdraw($user_id,$applytype);
        }
        app_error($data['status'],$data['msg']);
    }

    /**
     * 资产提现记录
     */
    public function rebate_drawlog()
    {
        global $_GPC;
        $page = max(1,$_GPC['page']);
        $type = $_GPC['type'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        if($type == 1){
            $data = m('app')->rebate_owndraw_log($user_id,$page);
        }else{
            $data = m('app')->rebate_merchdraw_log($user_id,$page);
        }
        app_error($data['status'],$data['msg']);
    }

    /**
     * 折扣宝收支明细
     */
    public function rebate_log()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $type = $_GPC['type'];
        $page = max(1,$_GPC['page']);
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->rebate_log($user_id,$type,$page);
        app_error(0,$data);
    }

    /**
     *  贡献值解读
     */
    public function rebate_detail()
    {
        $detail = pdo_get("ewei_shop_member_devote",["id"=>1]);
        app_error(0,$detail);
    }

    /**
     * 绑定微信  手机号
     */
    public function rebate_bind()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $type = $_GPC['type'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        if($type == 1){
            $mobile = $_GPC['mobile'];
            $msg = $_GPC['msg'];
            //查找短息的发送的记录
            $sms = pdo_get('core_sendsms_log',['mobile'=>$mobile,'content'=>$msg,'tp_id'=>1]);
            if(!$sms){
                app_error(1,"短信验证码不正确");
            }
            if($sms['result'] == 1){
                app_error(1,"该短信已验证");
            }
            //更改短信验证码的验证状态
            pdo_update('core_sendsms_log',['result'=>1],['id'=>$sms['id']]);
            //更新手机号
            pdo_update('ewei_shop_member',['mobile'=>$mobile],['id'=>$user_id]);
        }else{
            $weixin = $_GPC['weixin'];
            pdo_update("ewei_shop_member",["weixin"=>$weixin],["id"=>$user_id]);
        }
        app_error(0,'修改成功');
    }

    /**
     * 贡献值记录
     */
    public function rebate_devote_log()
    {
        global $_GPC;
        $token = $_GPC['token'];
        //分页设置
        $page = max(1,$_GPC['page']);
        $pageSize = 8;
        $first=($page-1) * $pageSize;
        //用户信息
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $member = m('member')->getMember($user_id);
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_credit_record")." where (openid = :openid or user_id = :user_id) and credittype = :credittype order by createtime desc limit ".$first.",".$pageSize,array(":openid"=>$member['openid'],':user_id'=>$user_id,":credittype"=>"credit4"));
        foreach ($list as $k=>$v){
            $list[$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
        }
        app_error(0,$list);
    }

    /**
     * 资产设置
     */
    public function rebate_getcredit()
    {
        global $_GPC;
        $type = $_GPC['type'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $member = m('member')->getMember($user_id);
        if($type == 1){
            app_error(0,['credit1'=>$member['credit1']]);
        }elseif ($type == 2){
            app_error(0,['credit3'=>$member['credit3'],'credit4'=>$member['credit4']]);
        }elseif ($type == 3){
            app_error(0,['credit3'=>$member['credit3']]);
        }
    }

    /**
     * RV的额度设置
     */
    public function rebate_limit()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->rebate_limit($user_id);
        app_error(0,$data);
    }

    /**
     * 兑换  提现  转账
     */
    public function rebate_change()
    {
        global $_GPC;
        $type = $_GPC['type'];
        $token = $_GPC['token'];
        $money = $_GPC['money'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        if($type == 1){
            $data = m('app')->rebate_exchange($user_id,$money);
        }elseif ($type == 2){
            $data = m('app')->rebate_withdraw($user_id,$money);
        }elseif ($type == 3){
            $mobile = $_GPC['mobile'];
            $data = m('app')->rebate_change($user_id,$money,$mobile);
        }
        app_error($data['status'],$data['msg']);
    }

    /**
     * 领取贡献值
     */
    public function rebate_getdevote()
    {
        global $_GPC;
        $ids = $_GPC['ids'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $ids = explode(',',$ids);
        $data = m('payment')->devotelog($ids,$user_id);
        if($data){
            app_error(0,"领取成功");
        }else{
            app_error(1,"领取失败");
        }
    }
    /**
     * 专享
     */
    public function exclusive()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $type = $_GPC['type'];
        $user_id = m('app')->getLoginToken($token);
        $member = m('member')->getMember($user_id);
        if(empty($user_id) || $member['agentlevel'] == 0 || $type == 1){
            $data = m('app')->get_list();
        }else{
            $data = m('app')->get_list1($user_id);
        }
        app_error(0,$data);
    }
}
?>