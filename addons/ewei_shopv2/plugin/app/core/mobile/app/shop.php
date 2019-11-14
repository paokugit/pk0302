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
        global $_GPC;
        //类型  总和3 价格2  销量1 最新0
        $type = $_GPC['type'];
        //asc升序   降序desc
        $sort = $_GPC['sort'];
        $page = max(1,$_GPC['page']);
        //头部轮播和头条  还有中间的四个入口
        $adv = m('app')->shop_adv();
        //ta的店
        $shop = m('app')->shop_shop();
        //商品列表
        $goods = m('app')->shop_goods($type,$sort,$page);
        app_error(0,['adv'=>$adv,'shop'=>$shop,'goods'=>$goods]);
    }

    /**
     * 分类页面
     */
    public function shop_cate()
    {
        $data = m('app')->shop_cate();
        app_error(0,$data);
    }

    /**
     * 商品搜索
     */
    public function shop_search()
    {
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
        app_error(0,$data);
    }

    /**
     * 商品详情
     */
    public function shop_goods_detail()
    {
        global $_GPC;
        $id = $_GPC['id'];
        //登录token验证
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        //$user_id
        $data = m('app')->shop_goods_detail($user_id,$id,$this->merch_user);
        app_error($data['status'],$data['msg']);
    }

    /**
     * 加入购物车
     */
    public function shop_cart_add()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0) app_error(2,'登录信息失效');
        $id = $_GPC['id'];
        if (empty($id)) app_error(AppError::$ParamsError);
        $total = $_GPC['total'];
        $optionid = $_GPC['optionid'];
        $data = m('app')->shop_add_cart($user_id,$id,$optionid,$total);
        app_error($data['status'],$data['msg']);
    }

   /**
    * 活动列表
    */
   public function shop_cate_list()
   {
        global $_GPC;
        // 这个传id最好吧  然后 根据id查类别  cate == 1fruit水果美食   2city同城  3cash零元兑  4task任务赚  5share分享赚   6rank网红榜单
        $id = $_GPC['id'];
        if(empty($id)) app_error(1,"参数错误");
        $page = max(1,$_GPC['page']);
        $keywords = $_GPC['keywords'];
       //order  类型  综合不传   销量sales  价格 minprice  by  升序asc   降序desc
        $type = empty($_GPC['type']) ? 3 : $_GPC['type'];
        $sort = empty($_GPC['sort']) ? "desc" : $_GPC['sort'];
        $data = m('app')->shop_cate_list($id,$keywords,$page,$type,$sort);
        app_error(0,$data);
   }

    /**
     *  ta的店列表
     */
    public function shop_shop_list()
    {
        global $_GPC;
        //1全部店   2关注的店   3上新
        $type = $_GPC['type'];
        $token = $_GPC['token'];
        $page = max(1,$_GPC['page']);
        $user_id = m('app')->getLoginToken($token);
        if($user_id == 0 && $type != 1) app_error(2,"登录失效");
        $data = m('app')->shop_shop_list($user_id,$type,$page);
        app_error(0);
    }

   /**
    * 他的店详情
    */
   public function shop_shop_detail()
   {
        global $_GPC;
        $id = $_GPC['id'];
        app_error(0);
   }

}
?>