<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage
{
    /**
     * 首页
     */
    public function home()
    {
        global $_GPC;
        //鉴权验证的token
        $token = $_GPC['token'];
        //icon的类别
//        $icon_type = $_GPC['icon_type'];
        //秒杀的类型
        //$seckill_type = $_GPC['seckill_type'];
        //附近商家的定位
        $lat = $_GPC['lat'];
        $lng = $_GPC['lng'];
        //允许的距离范围
//        $range = $_GPC['range'];
//        $cateid = $_GPC['cate_id'];
//        $sorttype = intval($_GPC['sorttype']);
//        $keyword = $_GPC['keyword'];
        //消息信息id
        $level_id = $_GPC['level_id'];
        //鉴权验证
        $user_id = m('app')->getLoginToken($token);
        //签到得卡路里 和  年卡会员每日得折扣宝

        //获取用户卡路里   折扣宝  自身步数  和 邀请步数  以及是都绑定手机号
        $bushu = m('app')->getbushu($user_id);
        //小图标导航   快报   和  年卡入口
        $icon = m('app')->get_icon($user_id,1);
        //门店服务
        $merch = m('app')->merch($user_id);
        //附近商家
        $near = m('app')->near($user_id,$lat,$lng);
        //秒杀
        //$seckill = m('app')->seckill($seckill_type);
        $seckill = m('app')->seckill(1);
        //边看边买
        $look_buy = m('app')->look_buy();
        //每日一推
        $every = m('app')->every();
        //跑库精选
        $choice = m('app')->choice();
        //消息弹窗
        $level = m('app')->notice($user_id,$level_id);
        app_error(0,['bushu'=>$bushu,'icon'=>$icon,'merch'=>$merch,'near'=>$near,'seckill'=>$seckill,'look_buy'=>$look_buy,'every'=>$every,'choice'=>$choice,'level'=>$level]);
    }

    /**
     * 首页---领取卡路里  或者折扣宝  因为后期没有卡路里
     */
    public function index_getcredit()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $step_id =$_GPC['step_id'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $data = m('app')->getcredit($user_id,$step_id);
        app_error($data['status'],$data['msg']);
    }

    /**
     *  年卡中心
     */
    public function index_level()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $data = m('app')->index_level($user_id);
        app_error(0,$data);
    }

    /**
     * 每月礼包的商品列表
     */
    public function index_level_goods()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $level_id = empty($_GPC['level_id']) ? 5 :$_GPC['level_id'];
        $data = m('app')->index_level_goods($user_id,$level_id);
        app_error(0,$data);
    }

    /**
     * 年卡礼包领取记录
     */
    public function index_level_record()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $page = max($_GPC['page'],1);
        $data = m('app')->index_level_record($user_id,$page);
        app_error(0,$data);
    }

    /**
     * 年卡权益介绍
     */
    public function index_level_detail()
    {
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $level_id = empty($_GPC['id']) ? 5 : $_GPC['id'];
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods = m('app')->index_level_goods(0,$level_id);
        app_error(0,['goods'=>$goods,'level'=>$level]);
    }

    /**
     * 我的年卡中心
     */
    public function index_level_my()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $member = m('member')->getMember($user_id);
        $user = [
            'id'=>$member['id'],
            'openid'=>$member['openid'],
            'nickname'=>$member['nickname'],
            'avatar'=>$member['avatar'],
            'realname'=>$member['realname'],
            'is_open'=>$member['is_open'],
        ];
        $user['is_expire'] = $member['is_open'] == 1 && $member['expire_time'] - time() <= 3600*10 ? 1 : 0;
        $user['expire'] = date('Y-m-d',$member['expire_time']);
        app_error(0,['member'=>$user]);
    }

    /**
     * 领取年卡礼包
     */
    public function index_getLevel()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $level_id = empty($_GPC['level_id']) ? 5 : $_GPC['level_id'];
        $address_id = $_GPC['address_id'];
        $money = $_GPC['money'];
        $record_id = $_GPC['record_id'];
        $good_id = $_GPC['good_id'];
        $data = m('app')->index_getLevel($user_id,$level_id,$address_id,$money,$record_id,$good_id);
        app_error($data['status'],$data['msg']);
    }

    /**
     * 地址列表  和  切换地址
     */
    public function index_address()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        //1请求地址列表  并获得默认地址的邮费    2 切换地址
        $type = !empty($_GPC['type']) ? $_GPC['type'] : 1;
        $address_id = !empty($_GPC['address_id']) ? $_GPC['address_id'] : 0;
        $data = m('app')->index_address($user_id,$address_id,$type);
        app_error($data['status'],$data['msg']);
    }

    /**
     * 十人礼包
     */
    public function index_gift()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $data = m('app')->index_gift($user_id);
        app_error(0,$data);
    }

    /**
     * 十人礼包的助力记录
     */
    public function index_gift_help()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $page = max($_GPC['page'],1);
        $data = m('app')->index_gift_help($user_id,$page);
        app_error(0,$data);
    }

    /**
     * 十人礼包领取记录
     */
    public function index_gift_record()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $page = max($_GPC['page'],1);
        $data = m('app')->index_gift_record($user_id,$page);
        app_error(0,$data);
    }

    /**
     * 礼包海报
     */
    public function index_gift_share()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $member = m('member')->getMember($user_id);
        $imgurl = m('qrcode')->HelpPoster($member,$member['id'],['back'=>'/addons/ewei_shopv2/static/images/gift_share.png','type'=>"giftshare",'title'=>'真的一分钱也不要哟！','desc'=>'快来帮我助力一下吧！','con'=>'周周分享，周周领','url'=>'packageA/pages/gift/gift']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
        app_error(0,array( "url" => $imgurl));
    }

    /**
     * 跑库精选列表
     */
    public function index_choice()
    {
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $page = max(1,$_GPC['page']);
        $pageSize = 10;
        $pindex = ($page-1)*$pageSize;
        $total = pdo_count('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1]);
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_choice').'where uniacid = :uniacid and status = 1 order by displayorder desc limit '.$pindex.','.$pageSize,[':uniacid'=>$uniacid]);
        app_error(0,['list'=>$list,'page'=>$page,'total'=>$total,'pageSize'=>$pageSize]);
    }

    /**
     * 跑库精选详情
     */
    public function index_choice_detail()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $id = $_GPC['id'];
        $data = m('app')->index_choice_detail($user_id,$id);
        app_error(0,$data);
    }

    /**
     * 跑库精选  ----   关注和取消关注
     */
    public function index_choice_fav()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,"登录信息失效");
        $id = $_GPC['id'];
        $data = m('app')->index_choice_fav($user_id,$id);
        app_error($data['status'],$data['msg']);
    }
}
?>