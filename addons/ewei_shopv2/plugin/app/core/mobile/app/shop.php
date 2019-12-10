<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Shop_EweiShopV2Page extends AppMobilePage
{
    /**
     * 商场首页
     */
    public function main()
    {
        header("Access-Control-Allow-Origin:*");
        //头部轮播和头条  还有中间的四个入口
        $adv = m('app')->shop_adv();
        //ta的店
        $shop = m('app')->shop_shop();
        $cate = [
            [
                'cateid'=>0,
                'cate'=>'全部'
            ],
            [
                'cateid'=>1,
                'cate'=>'推荐'
            ],
            [
                'cateid'=>2,
                'cate'=>'上新'
            ],
            [
                'cateid'=>3,
                'cate'=>'热卖'
            ],
        ];
        app_error1(0,"",['adv'=>$adv,'shop'=>$shop,'cate'=>$cate]);
    }

    /**
     * 商城首页的商品分页
     */
    public function shop_goods()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //类型  总和3 价格2  销量1 最新0
        $type = $_GPC['type'] ? $_GPC['type'] : 3;
        //asc升序   降序desc
        $sort = $_GPC['sort'] ? $_GPC['sort'] : "desc";
        $page = max(1,$_GPC['page']);
        $cate = $_GPC['cate'] ? $_GPC['cate'] : 0;
        //商品列表
        $goods = m('app')->shop_shop_goods($type,$sort,$page,$cate);
        app_error1(0,"",$goods);
    }

    /**
     * 分类页面
     */
    public function shop_cate()
    {
        header("Access-Control-Allow-Origin:*");
        $data = m('app')->shop_cate();
        app_error1(0,"",$data);
    }

    /**
     * 商品搜索
     */
    public function shop_search()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //分页  关键词  分类
        $page = max(1,$_GPC['page']);
        $keywords = $_GPC['keywords'];
        $cate = $_GPC['cate'];
        //新品  热卖   推荐  折扣  限时购  包邮
        $isnew = $_GPC['isnew'];
        $ishot = $_GPC['ishot'];
        $isrecommand = $_GPC['isrecommand'];
        $isdiscount = $_GPC['isdiscount'];
        $istime = $_GPC['istime'];
        $issendfree = $_GPC['issendfree'];
        //order  类型  综合不传   销量sales  价格 minprice  by  升序asc   降序desc
        $order = $_GPC['order'];
        $by = $_GPC['by'];
        $data = m('app')->shop_search($keywords,$cate,$page,$isnew,$ishot,$isrecommand,$isdiscount,$istime,$issendfree,$order,$by);
        app_error1(0,"",$data);
    }

    /**
     * 商品详情
     */
    public function shop_goods_detail()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $id = $_GPC['id'];
        //登录token验证
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //$user_id
        $data = m('app')->shop_goods_detail($user_id,$id,$this->merch_user);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 商品评价
     */
    public function shop_goods_comment_list()
    {

    }

    /**
     * 获得商品的属性
     */
    public function shop_goods_options()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $data = m('app')->shop_goods_options($user_id,$id);
        app_error1(0,'',$data);
    }

    /**
     * 加入购物车
     */
    public function shop_cart_add()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $id = $_GPC['id'];
        if (empty($id)) app_error(AppError::$ParamsError);
        $total = $_GPC['total'];
        $optionid = $_GPC['optionid'];
        $data = m('app')->shop_add_cart($user_id,$id,$optionid,$total);
        app_error1($data['status'],"",empty($data['data']) ? $data['data'] : []);
    }

    /**
     * 购物车列表的选中状态
     */
    public function shop_cart_select()
    {
        header("Access-Control-Allow-Origin:*");
        //接受信息
        global $_GPC;
        $token = $_GPC['token'];
        $id = intval($_GPC['id']);
        $select = intval($_GPC['select']);
        $merchid = intval($_GPC['merchid']);
        //获取用户信息
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error1(2,'登录信息失效',[]);
        $member = m('member')->getMember($user_id);
        //如果是单个操作选中否
        if(!empty($id)){
            //查找购物车信息  并改变其状态
            $cart = pdo_fetch('select * from '.tablename('ewei_shop_member_cart').'where id = :id and deleted = 0 ',[':id'=>$id]);
            if(pdo_update('ewei_shop_member_cart',['selected'=>$select],['id'=>$cart['id']])){
                app_error1(0,'',[]);
            }
        }else{
            //查询条件
            $condition = "(openid = :openid or user_id = :user_id)";
            $param = [':openid'=>$member['openid'],':user_id'=>$member['id']];
            //如果店铺全选或者全不选  加条件
            if($merchid){
                $condition .= 'and merchid = :merchid';
                $param[':merchid'] = $merchid;
            }
            //更新语句
            $update = pdo_query('update '.tablename('ewei_shop_member_cart').'set selected = "'.$select.'" where '.$condition,$param);
            if($update){
                app_error1(0,'',[]);
            }
        }
    }

    /**
     * 活动的banner
     */
    public function shop_cate_banner(){
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        // 这个传id最好吧  然后 根据id查类别  cate == 1fruit水果美食   2city同城  3cash零元兑  4task任务赚  5share分享赚   6rank网红榜单
        $id = $_GPC['id'];
        if(empty($id)) app_error1(1,"参数错误",[]);
        $data = m('app')->shop_cate_banner($id);
        app_error1(0,"",$data);
    }

   /**
    * 活动列表
    */
   public function shop_cate_list()
   {
       header("Access-Control-Allow-Origin:*");
        global $_GPC;
        // 这个传id最好吧  然后 根据id查类别  cate == 1fruit水果美食   2city同城  3cash零元兑  4task任务赚  5share分享赚   6rank网红榜单
        $id = $_GPC['id'];
        if(empty($id)) app_error1(1,"参数错误",[]);
        $page = max(1,$_GPC['page']);
        $keywords = $_GPC['keywords'];
       //order  类型  综合不传   销量sales  价格 minprice  by  升序asc   降序desc
        $type = empty($_GPC['type']) ? 3 : $_GPC['type'];
        $sort = empty($_GPC['sort']) ? "desc" : $_GPC['sort'];
        $data = m('app')->shop_cate_list($id,$keywords,$page,$type,$sort);
        app_error1(0,"",$data);
   }

    /**
     * 任务领钱
     */
    public function shop_task_list()
    {
        global $_GPC;
        global $_W;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $data = m('app')->shop_task_list($user_id);
        app_error1(0,'',$data);
    }

    /**
     * 同城
     */
    public function shop_same_city()
    {
        global $_GPC;
//        //用户信息
//        $token = $_GPC['token'];
//        $user_id = m('app')->getLoginToken($token);
//        $member = m('member')->getMember($user_id);
        //类型  city_type == 1 附近的商店   附近的商品
        $city_type = $_GPC['city_type'] ? $_GPC['city_type'] : 1;
        $page = max(1,$_GPC['page']);
        $keywords = $_GPC['keywords'];
        //order  类型  综合不传   销量sales  价格 minprice  by  升序asc   降序desc
        $type = empty($_GPC['type']) ? 3 : $_GPC['type'];
        $sort = empty($_GPC['sort']) ? "desc" : $_GPC['sort'];
        $lng = $_GPC['lng'];
        $lat = $_GPC['lat'];
        $range = $_GPC['range'] ? $_GPC['range'] : 5;
        $data = m('app')->shop_same_city($user_id,$city_type,$lng,$lat,$page,$keywords,$type,$sort,$range);
        app_error1(0,'',$data);
    }

    /**
     *  ta的店 动态 列表
     */
    public function shop_shop_list()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //1全部店   2关注的店   3上新
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        $token = $_GPC['token'];
        $page = max(1,$_GPC['page']);
        $merch_id = $_GPC['merch_id'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0 && $type != 1) app_error1(2,"登录失效",[]);
        $data = m('app')->shop_shop_list($user_id,$type,$page,$merch_id);
        app_error1(0,'',$data);
    }

   /**
    * 他的店动态详情
    */
   public function shop_shop_detail()
   {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $id = $_GPC['id'];
        if(empty($id)) app_error1(1,'参数错误',[]);
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $data = m('app')->shop_shop_detail($user_id,$id);
        app_error1(0,"",$data);
   }

    /**
     * 评论列表
     */
    public function shop_shop_comment()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $id = $_GPC['id'];
        if(empty($id)) app_error1(1,'参数错误',[]);
        $page = max(1,$_GPC['page']);
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        $data = m('app')->shop_shop_comment($user_id,$id,$page);
        app_error1(0,"",$data);
    }

    /**
     * 评论详情
     */
    public function shop_shop_comment_detail()
    {
        global $_GPC;
        //用户的token
        $token = $_GPC['token'];
        //获取用户的user_id
        $user_id = m('app')->getLoginToken($token);
        //评论的id
        $comment_id = $_GPC["comment_id"];
        //排序类型  $type  == 1 倒序  == 2正序
        $type = $_GPC["type"] ? $_GPC['type'] : 1;
        //分页信息
        $page = max($_GPC["page"],1);
        $data = m('app')->shop_shop_comment_detail($user_id,$comment_id,$page,$type);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

   /**
    * 动态文章  文章评论的点赞
    */
    public function shop_choice_fav()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //要点赞的文章或者 评论的id
        $id = $_GPC['id'];
        //type  == 1 文章的点赞    == 2评论的点赞
        $type = $_GPC['type'] ? $_GPC['type'] : 1;
        if(empty($id) || empty($type)) app_error1(1,'参数错误',[]);
        $data = m('app')->shop_choice_fav($user_id,$id,$type);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 动态文章评论  评论已有的评论
     */
    public function shop_choice_comment()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //type  ==1 评论文章  ==2 评论已有的评论
        $type = $_GPC["type"];
        $parent_id = $_GPC["parent_id"];
        //评论的内容
        $content = $_GPC['content'];
        if($content == "" || empty($parent_id)) app_error1(1,'参数错误',[]);
        $data = m('app')->shop_choice_comment($user_id,$parent_id,$content,$type);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * RVC充值
     */
    public function rvc_pay()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $type = $_GPC['type'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //参数
        $amount= $_GPC['amount'];
        $data = m('game')->rvc_pay($user_id,$amount,$type);
        app_error1($data['status'],$data['message'],$data['data']);
    }

    /**
     * 会员RVC信息首页
     */
    public function shop_rvc()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token=$_GPC["token"];
        $user_id=m('member')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        $data = m('app')->shop_rvc($user_id);
        app_error1(0,'',['data' => $data]);
    }

    /**
     * RVC收支明细
     */
    public function shop_rvc_log()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //修改
        $token = $_GPC["token"];
        $user_id = m('member')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        $type = intval($_GPC['type']);
        $pindex = max(1, intval($_GPC['page']));
        $data = m('app')->shop_rvc_log($user_id,$pindex,$type);
        app_error1(0,"",$data);
    }
}
?>