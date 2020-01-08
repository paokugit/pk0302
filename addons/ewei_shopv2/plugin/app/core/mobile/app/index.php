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
        header('Access-Control-Allow-Origin:*');
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
        $level_id = empty($_GPC['level_id']) ? 5 : $_GPC['level_id'];
        //鉴权验证
        $user_id = m('app')->getLoginToken($token);
        //签到得卡路里 和  年卡会员每日得折扣宝

        //获取用户卡路里   折扣宝  自身步数  和 邀请步数  以及是否绑定手机号
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
        $look_buy = m('app')->look_buy($user_id,1,8);
        //每日一推
        $every = m('app')->every();
        //跑库精选
        $choice = m('app')->choice();
        //消息弹窗
        $level = m('app')->notice($user_id,$level_id);
        //app_error1(0,'',['bushu'=>$bushu,'merch'=>$merch,'near'=>$near,'seckill'=>$seckill,'look_buy'=>$look_buy,'every'=>$every,'choice'=>$choice,'level'=>$level]);
        app_error1(0,'',['bushu'=>$bushu,'icon'=>$icon,'merch'=>$merch,'near'=>$near,'seckill'=>$seckill,'look_buy'=>$look_buy,'every'=>$every,'choice'=>$choice,'level'=>$level]);
    }

    /**
     * 首页---领取卡路里  或者折扣宝  因为后期没有卡路里
     */
    public function index_getcredit()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $step_id =$_GPC['step_id'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $credit = $_GPC['credit'];
        $data = m('app')->getcredit($user_id,$step_id,$credit);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     *  年卡中心
     */
    public function index_level()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $data = m('app')->index_level($user_id);
        app_error1(0,'',$data);
    }

    /**
     * 每月礼包的商品列表
     */
    public function index_level_goods()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $level_id = empty($_GPC['level_id']) ? 5 :$_GPC['level_id'];
        $data = m('app')->index_level_goods($user_id,$level_id);
        app_error1(0,'',$data);
    }

    /**
     * 年卡礼包领取记录
     */
    public function index_level_record()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $page = max($_GPC['page'],1);
        $data = m('app')->index_level_record($user_id,$page);
        app_error1(0,'',$data);
    }

    /**
     * 年卡权益介绍
     */
    public function index_level_detail()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $level_id = empty($_GPC['id']) ? 5 : $_GPC['id'];
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods = m('app')->index_level_goods(0,$level_id);
        app_error1(0,'',['goods'=>$goods,'level'=>$level]);
    }

    /**
     * 我的年卡中心
     */
    public function index_level_my()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $member = m('member')->getMember($user_id);
        $user = [
            'id'=>$member['id'],
            'openid'=>$member['openid'],
            'nickname'=>$member['nickname'],
            'avatar'=>$member['avatar'],
            'realname'=>$member['realname'],
            'is_open'=>$member['is_open'],
        ];
        $user['is_expire'] = $member['is_open'] == 1 && $member['expire_time'] - time() <= 3600*10*24 ? 1 : 0;
        $user['expire'] = date('Y-m-d',$member['expire_time']);
        app_error1(0,'',['member'=>$user]);
    }

    /**
     * 领取年卡礼包
     */
    public function index_getLevel()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $level_id = empty($_GPC['level_id']) ? 5 : $_GPC['level_id'];
        $address_id = $_GPC['address_id'];
        $money = $_GPC['money'];
        $record_id = $_GPC['record_id'];
        $good_id = $_GPC['good_id'];
        $data = m('app')->index_getLevel($user_id,$level_id,$address_id,$money,$record_id,$good_id);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 地址列表  和  切换地址
     */
    public function index_address()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //1请求地址列表  并获得默认地址的邮费    2 切换地址
        $type = !empty($_GPC['type']) ? $_GPC['type'] : 1;
        $address_id = !empty($_GPC['address_id']) ? $_GPC['address_id'] : 0;
        $data = m('app')->index_address($user_id,$address_id,$type);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 十人礼包
     */
    public function index_gift()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $data = m('app')->index_gift($user_id);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 十人礼包的助力记录
     */
    public function index_gift_help()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $page = max($_GPC['page'],1);
        $data = m('app')->index_gift_help($user_id,$page);
        app_error1(0,'',$data);
    }

    /**
     * 十人礼包领取记录
     */
    public function index_gift_record()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $page = max($_GPC['page'],1);
        $data = m('app')->index_gift_record($user_id,$page);
        app_error1(0,'',$data);
    }

    /**
     * 礼包海报
     */
    public function index_gift_share()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $member = m('member')->getMember($user_id);
        $imgurl = m('qrcode')->HelpPoster($member,$member['id'],['back'=>'/addons/ewei_shopv2/static/images/gift_share.png','type'=>"giftshare",'title'=>'真的一分钱也不要哟！','desc'=>'快来帮我助力一下吧！','con'=>'周周分享，周周领','url'=>'packageA/pages/gift/gift']);
        if( empty($imgurl))
        {
            app_error1(AppError::$PosterCreateFail, "海报生成失败",[]);
        }
        $data = ['title'=>'免费领礼包啦，商品免费领到手','image'=>"https://www.paokucoin.com/img/backgroup/free.jpg","path"=>"/packageA/pages/gift/gift?invitedid=".$member['openid']];
        $data['imgurl'] = $imgurl;
        app_error1(0,'',$data);
    }

    /**
     * 跑库精选列表
     */
    public function index_choice()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $page = max(1,$_GPC['page']);
        $pageSize = 10;
        $pindex = ($page-1)*$pageSize;
        $total = pdo_count('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1]);
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_choice').'where uniacid = :uniacid and status = 1 order by displayorder desc limit '.$pindex.','.$pageSize,[':uniacid'=>$uniacid]);
        foreach ($list as $key=>$value){
            $list[$key]['content'] = htmlspecialchars_decode($value['content']);
            $list[$key]['image'] = tomedia($value['image']);
            $list[$key]['thumb'] = tomedia($value['thumb']);
        }
        app_error1(0,'',['list'=>$list,'page'=>$page,'total'=>$total,'pageSize'=>$pageSize]);
    }

    /**
     * 跑库精选详情
     */
    public function index_choice_detail()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $id = $_GPC['id'];
        $data = m('app')->index_choice_detail($user_id,$id);
        app_error1(0,'',$data);
    }

    /**
     * 跑库精选  ----   关注和取消关注
     */
    public function index_choice_fav()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $id = $_GPC['id'];
        $data = m('app')->index_choice_fav($user_id,$id);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 秒杀列表
     */
    public function index_seckill()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        $merchid = $_GPC['merchid'] ? $_GPC['merchid'] : 0;
        $page = max(1,$_GPC['page']);
        $data = m('app')->seckill($type,$page,$merchid);
        app_error1(0,'',$data);
    }

    /**
     * 边看边买详情
     */
    public function look_buy()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //小程序的逻辑  每次一个加载更多   不要了
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        //$data = m('app')->look_buy_detail($user_id,$id,$type);
        //app_error1($data['status'],$data['msg'],$data['data']);
        //app逻辑
        $page = max(1,$_GPC['page']);
        $data = m('app')->look_buy($user_id,$page,6);
        app_error1(0,'',$data);
    }

    /**
     * 边看边买的分享
     */
    public function look_buy_share()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $video_id = $_GPC['video_id'];
        $goodsid = pdo_getcolumn('ewei_shop_look_buy',['id'=>$video_id],'goods_id');
        $data = m('app')->look_buy_share($video_id,$goodsid,$user_id);
        app_error1(0,'',$data);
    }

    /**
     * 边看边买的点赞
     */
    public function look_buy_zan()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //如果是点赞视频 就是视频id   如果是点赞商品 也是商品id
        $look_id = $_GPC['look_id'];
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $data = m('app')->look_buy_zan($user_id,$look_id);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 每日一推的列表
     */
    public function every()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $page = max(1,$_GPC["page"]);
        $data = m('app')->every($page);
        app_error1(0,'',$data);
    }

    /**
     * 每日一推详情
     */
    public function every_detail()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $id = $_GPC['id'];
        $detail=pdo_get("ewei_shop_member_reading",array("id"=>$id));
        if (empty($detail)) app_error1(1,"该文章不存在",[]);
        //添加view
        pdo_update("ewei_shop_member_reading",array("view" => $detail["view"] + 1),array("id" => $id));
        $detail["img"] = tomedia($detail["img"]);
        if (!empty($detail["detail_img"])) $detail["detail_img"] = tomedia($detail["detail_img"]);
        $detail["music"] = tomedia($detail["music"]);
        $detail["create_time"] = date("Y-m-d",$detail["create_time"]);
        $detail['path'] = '/packageA/pages/skyread/details/details?id='.$id;
	$detail['image'] = tomedia($detail["img"]);
        app_error1(0,'',$detail);
    }

    /**
     * 每日一推评论列表
     */
    public function every_comment_list()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //接受文章id  分页信息
        $readid = $_GPC["id"];
        $page = max(1,$_GPC["page"]);
        $pageSize = 10;
        $first = ($page-1) * $pageSize;
        //获取用户信息
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $member = m('member')->getMember($user_id);
        //查找评论列表
        $list = pdo_fetchall("select id,comment,zan,openid,user_id from ".tablename("ewei_shop_member_readcomment")." where read_id = :read_id order by create_time desc limit ".$first.",".$pageSize,array(":read_id"=>$readid));
        foreach ($list as $k=>$v){
            //查找回复信息
            $list[$k]["reply"] = pdo_fetchall("select reply from ".tablename("ewei_shop_member_readreply")." where comment_id = :comment_id",array(":comment_id"=>$v["id"]));
            $com_member = pdo_fetch('select * from '.tablename('ewei_shop_member').'where openid = :openid or id = :user_id ',[':openid'=>$v['openid'],':user_id'=>$v['user_id']]);
            //用户的昵称和头像
            $list[$k]["nickname"] = $com_member["nickname"];
            $list[$k]["avatar"] = $com_member["avatar"];
            //判断是否点赞
            $list[$k]["myzan"] = pdo_fetch('select * from '.tablename('ewei_shop_member_readzan').' where (openid = :openid or user_id = :user_id) and comment_id = :comment_id ',[':openid'=>$member['openid'],':user_id'=>$member['id'],':comment'=>$v["id"]]) ? 1 : 0;
        }
        //查看回复数
        $count = pdo_count("ewei_shop_member_readcomment",array("read_id"=>$readid));
        $pagetotal = ceil($count/$pageSize);
        app_error1(0,'',['total'=>$count,'pagesize'=>$pageSize,'page'=>$page,'pagetotal'=>$pagetotal,'list'=>$list]);
    }

    /**
     * 每日一推的评论
     */
    public function every_comment()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //接受文章id  和用户的token
        $readid = $_GPC["id"];
        $token = $_GPC['token'];
        //查找用户的信息
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $member = m('member')->getMember($user_id);
        //接收评论内容  并且 如果没有评论内容  就报错
        $data["comment"] = $_GPC["comment"];
        if (empty($data["comment"])) app_error1(1,"评论内容不可为空");
        $count = m('util')->sensitives($data["comment"]);
        if($count > 0) app_error1(1,"含有敏感词不可提交",[]);
        //查找文章信息   如果没有文章就报错
        $detail = pdo_get("ewei_shop_member_reading",array("id"=>$readid));
        if (empty($detail)) app_error1(1,"该文章不存在");
        //添加评论
        $data["read_id"] = $readid;
        $data["openid"] = $member["openid"];
        $data["user_id"] = $member["id"];
        $data["create_time"] = time();
        if (pdo_insert("ewei_shop_member_readcomment",$data)){
            app_error1(0,"评论成功");
        }else{
            app_error1(1,"评论失败");
        }
    }

    /**
     * 每日一推的评论删除
     */
    public function every_comment_delete()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //接受用户的token  和 评论的id
        $token = $_GPC['token'];
        $comment_id = $_GPC["comment_id"];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //获取用户的信息
        $member = m('member')->getMember($user_id);
        //获取评论信息
        $comment = pdo_get("ewei_shop_member_readcomment",array("id" => $comment_id));
        if (empty($comment))  app_error1(1,"不存在该评论",[]);
        //是不是自己的评论 可不可以删除
        if ($comment["openid"] != $member['openid'] || $comment['user_id'] != $member['id']) app_error1(1,"您无权限删除该评论");
        if (pdo_delete("ewei_shop_member_readcomment",array("id" => $comment_id))){
            app_error1(0,"删除成功");
        }else{
            app_error1(1,"删除失败");
        }
    }

    /**
     * 每日一推评论点赞
     */
    public function every_comment_zan()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        //接受用户token  和  评论id
        $token = $_GPC['token'];
        $comment_id = $_GPC["id"];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //获取用户的信息
        $member = m('member')->getMember($user_id);
        //看看用户有没有点赞   以及  查看当前的评论
        $comment_zan = pdo_fetch("select * from ".tablename("ewei_shop_member_readzan")." where comment_id = :comment_id and (openid = :openid or user_id = :user_id)",[':comment_id'=>$comment_id,':openid'=>$member['openid'],':user_id'=>$member['id']]);
        $comment = pdo_get("ewei_shop_member_readcomment",array("id"=>$comment_id));
        //如果有点赞信息  就删除  然后就是取消点赞
        if ($comment_zan){
            pdo_update("ewei_shop_member_readcomment",array("zan"=>$comment["zan"] - 1),array("id"=>$comment_id));
            app_error1(0,"取消成功",[]);
        }else{
            //如果没有点赞信息  就插入信息
            $data["comment_id"] = $comment_id;
            $data["openid"] = $member['openid'];
            $data["user_id"] = $member['id'];
            $data["create_time"] = time();
            pdo_insert("ewei_shop_member_readzan",$data);
            pdo_update("ewei_shop_member_readcomment",array("zan" => $comment["zan"] + 1),array("id" => $comment_id));
            app_error1(0,"点赞成功",[]);
        }
    }

    /**
     * 新手攻略  --- 热点关注
     */
    public function index_attention()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $page = max(1,$_GPC["page"]);
        $pageSize = 10;
        $first = ($page-1) * $pageSize;
        $total = pdo_count('ewei_shop_notive',['uniacid'=>$uniacid]);
        $list = pdo_fetchall("select id,title,photo,time,type,video from ".tablename("ewei_shop_notive")."order by sort desc limit ".$first." ,".$pageSize);
        foreach ($list as $k=>$v){
            $list[$k]["photo"]=tomedia($v["photo"]);
            $list[$k]["video"]=tomedia($v["video"]);
        }
        $pagetotal = ceil($total/$pageSize);
        app_error1(0,'',['list'=>$list,'total'=>$total,'pagesize'=>$pageSize,'page'=>$page,'pagetotal'=>$pagetotal]);
    }

    /*
     * 帮助指南
     */
    public function index_help()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        global $_W;
        $uniacid = $_W['uniacid'];
        $pageSize = 16;
        $page = max(1,$_GPC["page"]);
        $first = ($page-1) * $pageSize;
        $total = pdo_count('ewei_shop_notive_article',['uniacid'=>$uniacid]);
        $list=pdo_fetchall("select id,title from ".tablename("ewei_shop_notive_article")."order by sort desc limit ".$first." ,".$pageSize);
        $pagetoal = ceil($total/$pageSize);
        app_error1(0,'',['list'=>$list,'total'=>$total,'page'=>$page,'pagesize'=>$pageSize,'pagetotal'=>$pagetoal]);
    }

    /**
     * 帮助和热点的详情
     */
    public function index_detail()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $id = $_GPC["id"];
        $type = $_GPC['type'];
        //type等于1  是热点的详情  type等于2是帮助的详情
        if($type == 1){
            $detail = pdo_get("ewei_shop_notive",array("id"=>$id));
            $detail["photo"] = tomedia($detail["photo"]);
            $detail["video"] = tomedia($detail["video"]);
        }else{
            $detail=pdo_get("ewei_shop_notive_article",array("id"=>$id));
        }
        $detail["createtime"] = date("Y-m-d H:i:s",$detail["createtime"]);
        app_error1(0,'',$detail);
    }

    /**
     * 好友捐赠页面
     */
    public function index_friend()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        //获得用户id
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //获取用户信息
        $member = m('member')->getMember($user_id);
        if ($member["agentlevel"] == 0){
            $exchange = 0.5/1500;
        }else{
            $level = pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
            $exchange = $level["subscription_ratio"]/1500;
        }
        $exchange_step = m("member")->exchange_step($user_id);
        $step_count = ceil($exchange_step/$exchange);
        //获取用户今天总步数
        $day = date("Y-m-d",time());
        $step_today = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  (openid=:openid or user_id = :user_id) and type!=:type", array(':today' => $day, ':openid' => $member['openid'], ':user_id' => $member['id'],':type'=>2));
        $data["step_today"] = empty($step_today) ? 0 : $step_today;
        //还差多少步数
        $data["step"] = $step_count - $step_today < 0 ? 0 : $step_count - $step_today;
        $data["openid"] = $member['openid'];
        $data["user_id"] = $member['id'];
        //用户每日可最多兑换步数
        $data["step_count"] = $step_count;
        //累计邀请人数
        $ste_today = pdo_fetchcolumn("select count(*) from (select count(*) from " . tablename('ewei_shop_member_getstep') . " where (openid=:openid or user_id = :user_id) and type=:type group by bang) as a", array(':openid' => $member['openid'],':user_id' => $member['id'],':type'=>1));
        $data['friend_today'] = empty($ste_today) ? 0 : $ste_today;
        //助力获取的总卡路里
        $credit = m('credits')->get_sum_credit(1,$user_id);
        $data['credit_price'] = $data['credit_sum'] = $credit ? $credit : 0;
        //获取折扣宝奖励
        $discount=m('credits')->get_sum_creditdiscount(1,$user_id);
        $data['credit_pricediscount'] = $data['credit_sumdiscount'] = $discount ? $discount : 0;
        //分享人id
        $mid = $user_id;
        //好友捐赠所有的图
        $idarray = pdo_fetchall("select id from ".tablename("ewei_shop_share_help"));
        //随机获取他的键
        $k = array_rand($idarray);
        //得到键所对应的id
        $id = $idarray[$k]["id"];
        //查询的好友捐赠的id
        $data = pdo_get('ewei_shop_share_help',['id'=>$id],['title','image']);
        //分享的缩略图
        $data['image'] = tomedia($data['image']);
        //小程序分享链接
        $data['path'] = "/packageA/pages/helphand/helpshare/helpshare";
        //海报
        $imgurl = m('qrcode')->HelpPoster($member,$mid,['back'=>'/addons/ewei_shopv2/static/images/1.png','type'=>"helpposter",'title'=>'快来帮我助力一下','desc'=>'微信步数兑现金，收入可提现！','con'=>'每一步，都值得鼓励','url'=>'packageA/pages/helphand/helpshare/helpshare']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
        $data['imgurl'] = $imgurl;
        app_error1(0,'',$data);
    }

    /**
     * 好友捐赠分享
     */
    public function index_friend_share()
    {
        //用户信息
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $member = m('member')->getMember($user_id);
        //分享人id
        $mid = $user_id;
        //好友捐赠所有的图
        $idarray = pdo_fetchall("select id from ".tablename("ewei_shop_share_help"));
        //随机获取他的键
        $k = array_rand($idarray);
        //得到键所对应的id
        $id = $idarray[$k]["id"];
        //查询的好友捐赠的id
        $data = pdo_get('ewei_shop_share_help',['id'=>$id],['title','image']);
        //分享的缩略图
        $data['image'] = tomedia($data['image']);
        //小程序分享链接
        $data['path'] = "/packageA/pages/helphand/helpshare/helpshare";
        //海报
        $imgurl = m('qrcode')->HelpPoster($member,$mid,['back'=>'/addons/ewei_shopv2/static/images/1.png','type'=>"helpposter",'title'=>'快来帮我助力一下','desc'=>'微信步数兑现金，收入可提现！','con'=>'每一步，都值得鼓励','url'=>'packageA/pages/helphand/helpshare/helpshare']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
        $data['imgurl'] = $imgurl;
        app_error1(0,'',$data);
    }

    /**
     * 好友助力助力榜
     */
    public function index_friend_list()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        //获得用户id
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //用户信息
        $memberInfo = m('member')->getMember($user_id);
        //助力榜列表
        $data = m('getstep')->getHelpList($memberInfo["openid"]);
        //分享人id
        $mid = $user_id;
        //好友捐赠所有的图
        $idarray = pdo_fetchall("select id from ".tablename("ewei_shop_share_help"));
        //随机获取他的键
        $k = array_rand($idarray);
        //得到键所对应的id
        $id = $idarray[$k]["id"];
        //查询的好友捐赠的id
        $data = pdo_get('ewei_shop_share_help',['id'=>$id],['title','image']);
        //分享的缩略图
        $data['image'] = tomedia($data['image']);
        //小程序分享链接
        $data['path'] = "/packageA/pages/helphand/helpshare/helpshare";
        //海报
        $imgurl = m('qrcode')->HelpPoster($member,$mid,['back'=>'/addons/ewei_shopv2/static/images/1.png','type'=>"helpposter",'title'=>'快来帮我助力一下','desc'=>'微信步数兑现金，收入可提现！','con'=>'每一步，都值得鼓励','url'=>'packageA/pages/helphand/helpshare/helpshare']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
        $data['imgurl'] = $imgurl;
        app_error1(0,'',$data);
    }

    /**
     * 运动日记
     */
    public function index_sport()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        //获得用户id
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $num = (int)$_GPC['num'] ? (int)$_GPC['num'] : 0;
        $data = m('app')->index_sport($user_id,$num);
        app_error1(0,'',$data);
    }

    /**
     * 转盘游戏
     */
    public function index_game()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $type = $_GPC['type'] ? $_GPC['type'] : 2;
        $data = m('app')->index_game($user_id,$type);
        app_error1(0,'',$data);
    }

    /**
     * 转盘分享
     */
    public function index_game_share()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        $data = [
            'path'=>'/pages/index/index?scene='.$user_id,
            'title'=>'原来微信步数可以当钱用，快来和我一起薅羊毛',
            'image'=>'https://www.paokucoin.com/img/backgroup/lottary.png',
        ];
        app_error1(0,'',$data);
    }

    /**
     * 点击转盘
     */
    public function index_getreward()
    {
        header('Access-Control-Allow-Origin:*');
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,"登录信息失效",[]);
        //$type==2  免费抽奖   $type == 0 花钱抽奖
        $type = $_GPC['type'];
        $credit = $_GPC['credit'] ? $_GPC['credit'] : "credit1";
        $money = $_GPC['money'];
        $data = m('app')->index_getreward($user_id,$type,$credit,$money);
        app_error1($data['status'],$data['msg'],$data['data']);
    }
}
?>