<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class List_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
        global $_W;
        global  $_GPC;
        $type = $_GPC['type'];
        $uniacid = $_W['uniacid'];
        $page = max(1,$_GPC['page']);
        if($type == "" || $page == "") show_json(0,"参数不完整");
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $condition = "";
        //疯狂抢购中
        $time = time();
        if($type == 1){
            $condition .= " and uniacid = '".$uniacid."' and deleted = 0 and istime = 1 and status > 0 and timestart < '".$time."' and timeend > '".$time."'";
        }else{
            //即将开始
            $condition .= " and uniacid = '".$uniacid."' and istime = 1 and  deleted = 0 and status > 0 and timestart > '".$time."'";
        }
        $total = pdo_fetch('select count(*) as count from '.tablename('ewei_shop_goods').'where 1' .$condition);
        $list = pdo_fetchall('select id,title,thumb,productprice,marketprice,deduct,deduct_type,istime,timestart,timeend,sales,total,salesreal from '.tablename('ewei_shop_goods').' where 1' . $condition .'order by id desc LIMIT '.$pindex.','.$pageSize);
        foreach ($list as $key=>$item){
            $list[$key]['thumb'] = tomedia($item['thumb']);
            $list[$key]['sales'] = intval($item["sales"]);
            $list[$key]['total'] = intval($item["total"]);
            $list[$key]['salesreal'] = intval($item["salesreal"]);
            $list[$key]['showprice'] = bcsub($item['marketprice'],$item['deduct'],2);
        }
        if(!empty($list)){
            if($type == 1){
                $down_time = $list[0]['timeend'];
            }else{
                $down_time = $list[0]['timestart'];
            }
            show_json(1,['pageSize'=>$pageSize,'page'=>$page,'total'=>$total['count'],'list'=>$list,'end_time'=>$down_time]);
        }else{
            show_json(0,"暂无秒杀商品");
        }
    }

/***************************************************************边看边买****************************************************************************************/
    /**
     * 首页的边看边买qqq
     */
    public function index_sale111()
    {
        //查看有视频的  有库存的  在售的所有商品
        $list = pdo_fetchall('select id,thumb,title,marketprice,productprice,total,sales,video from '.tablename('ewei_shop_goods').'where video!="" and total > 0 and status = 1 group by video order by id desc limit 5');
        foreach ($list as $key=>$item){
            $list[$key]['video'] = tomedia($item['video']);
	        $list[$key]['thumb'] = tomedia($item['thumb']);
        }
        if(empty($list)){
            show_json(0,"暂无信息");
        }else{
            show_json(1,['list'=>$list]);
        }
    }

    /**
     * 首页的边看边买
     */
    public function index_sale()
    {
        //查看有视频的  有库存的  在售的所有商品
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_look_buy').'where status = 1 order by displayorder desc limit 5');
        foreach ($list as $key=>$item){
            $list[$key]['video'] = tomedia($item['video']);
            $list[$key]['thumb'] = tomedia($item['thumb']);
            $goods = pdo_get('ewei_shop_goods',['id'=>$item['goods_id']]);
            $list[$key]['marketprice'] = $goods['marketprice'];
            $list[$key]['productprice'] = $goods['productprice'];
            $list[$key]['sales'] = $goods['sales']+$goods['realsales'];
	    $list[$key]['deduct'] = $goods['deduct_type'];
        }
        if(empty($list)){
            show_json(0,"暂无信息");
        }else{
            show_json(1,['list'=>$list]);
        }
    }

    /**
     * 边看边买详情111
     */
     public function sale_detail111()
     {
         global $_GPC;
         //获取商品id
         $id = $_GPC['id'];
         //获得查看视频的上下拉  up是看下一条  down 是看上一条
         $type = $_GPC['type'];
         //如果商品id存在
         if(!empty($id)){
             //没有上看下凑的类型  就是查看  点击进去的商品
             if(empty($type)){
                 $detail = pdo_fetch('select id,title,thumb,marketprice,productprice,total,sales,salesreal,video from '.tablename('ewei_shop_goods').'where id = :id and video != "" and total > 0 and status = 1',[':id'=>$id]);
             }else{
                 //如果是下一条  就取当前这个商品  倒序  id小于当前商品
                 $now = pdo_fetch('select id,title,thumb,marketprice,productprice,total,sales,salesreal,video from '.tablename('ewei_shop_goods').'where id = :id and video != "" and total > 0 and status = 1',[':id'=>$id]);
                 if($type == "up"){
                     $detail = pdo_fetch('select id,title,thumb,marketprice,productprice,total,sales,salesreal,video from '.tablename('ewei_shop_goods').'where video != "" and total > 0 and status = 1 and id < :id and video != "'.$now["video"].'" order by id desc',[':id'=>$id]);
                 }elseif($type == "down"){
                     //如果是下一条  就取当前这个商品  倒序  id大于当前商品
                     $detail = pdo_fetch('select id,title,thumb,marketprice,productprice,total,sales,salesreal,video from '.tablename('ewei_shop_goods').'where video != "" and total > 0 and status = 1 and id > :id and video != "'.$now["video"].'" order by id asc',[':id'=>$id]);
                 }
             }
         }else{
             //如果商品id不存在  就倒序取第一个视频信息
             $detail = pdo_fetch('select id,title,thumb,marketprice,productprice,total,sales,salesreal,video from '.tablename('ewei_shop_goods').'where video != "" and total > 0 and status = 1 order by id desc');
         }
         if(empty($detail)){
             show_json(2,"信息获取失败");
         }else{
             $comment = pdo_fetchall('select oc.nickname,oc.content,oc.headimgurl from '.tablename('ewei_shop_order_comment').'oc join '.tablename('ewei_shop_order_goods').('g on g.goodsid = oc.goodsid').' where oc.goodsid = :goods_id and oc.level > 3',[':goods_id'=>$detail['id']]);
             $favorite = pdo_get('ewei_shop_goods_zan',['openid'=>$_GPC['openid'],'goodsid'=>$detail['id'],'status'=>1]);
             $detail['fav'] = empty($favorite) ? 0 : 1;
             $detail['fav_count'] = pdo_count('ewei_shop_goods_zan',['goodsid'=>$detail['id'],'status'=>1]);
             $detail['video'] = tomedia($detail['video']);
             $detail['sales'] = $detail['sales'] + $detail['salesreal'];
             if($detail['sales'] > 9999){
                 $detail['sales'] = $detail['sales']/10000 ."万";
             }
	     if($detail['fav_count'] > 9999){
                 $detail['fav_count'] = $detail['fav_count']/10000 ."W";
             }
	     $detail['thumb'] = tomedia($detail['thumb']);
             show_json(1,['detail'=>$detail,'comment'=>$comment]);
         }
     }

    /**
     * 边看边买详情
     */
    public function sale_detail()
    {
        global $_GPC;
        //获取商品id
        $id = $_GPC['id'];
        //获得查看视频的上下拉  up是看下一条  down 是看上一条
        $type = $_GPC['type'];
        //如果商品id存在
        if(!empty($id)){
            //没有上看下凑的类型  就是查看  点击进去的商品
            if(empty($type)){
                $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where id = :id  and status = 1',[':id'=>$id]);
            }else{
                $now = pdo_get('ewei_shop_look_buy',['id'=>$id]);
                if($type == "up"){
                    $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 and displayorder < :displayorder order by displayorder desc',[':displayorder'=>$now['displayorder']]);
                }elseif($type == "down"){
                    //如果是下一条  就取当前这个商品  倒序  id大于当前商品
                    $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 and displayorder > :displayorder order by displayorder asc',[':displayorder'=>$now['displayorder']]);
                }
            }
        }else{
            //如果商品id不存在  就倒序取第一个视频信息
            $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 order by displayorder desc');
        }
        if(empty($detail)){
            show_json(2,"信息获取失败");
        }else{
            $goods = pdo_get('ewei_shop_goods',['id'=>$detail['goods_id']]);
            $detail['marketprice'] = $goods['marketprice'];
            $detail['productprice'] = $goods['productprice'];
            $comment = pdo_fetchall('select oc.nickname,oc.content,oc.headimgurl from '.tablename('ewei_shop_order_comment').'oc join '.tablename('ewei_shop_order_goods').('g on g.goodsid = oc.goodsid').' where oc.goodsid = :goods_id and oc.level > 3',[':goods_id'=>$detail['goods_id']]);
            $favorite = pdo_get('ewei_shop_goods_zan',['openid'=>$_GPC['openid'],'goodsid'=>$detail['goods_id'],'status'=>1]);
            $detail['fav'] = empty($favorite) ? 0 : 1;
            $detail['fav_count'] = pdo_count('ewei_shop_goods_zan',['goodsid'=>$detail['goods_id'],'status'=>1]);
            $detail['video'] = tomedia($detail['video']);
            $detail['sales'] = $goods['sales'] + $goods['salesreal'];
            if($detail['sales'] > 9999){
                $detail['sales'] = $detail['sales']/10000 ."万";
            }
            if($detail['fav_count'] > 9999){
                $detail['fav_count'] = $detail['fav_count']/10000 ."W";
            }
            $detail['thumb'] = tomedia($goods['thumb']);
            show_json(1,['detail'=>$detail,'comment'=>$comment]);
        }
    }

    /**
     * 点赞接口
     */
    public function zan()
    {
        global $_GPC;
        $goods_id = $_GPC['goodsid'];
        $openid = $_GPC['openid'];
        //$status = $_GPC['status'];
        if($openid == "" || $goods_id == ""){
            show_json(0,"参数不完整");
        }
        $zan = pdo_get('ewei_shop_goods_zan',['openid'=>$openid,'goodsid'=>$goods_id]);
        if(!empty($zan)){
            $status = $zan['status'] == 1 ? 0 : 1;
            $msg = $zan['status'] == 1 ? "取消点赞成功" : "点赞成功";
            pdo_update('ewei_shop_goods_zan',['status'=>$status],['id'=>$zan['id']]);
        }else{
            $status = 1;
            $msg = "点赞成功";
            pdo_insert('ewei_shop_goods_zan',['status'=>1,'openid'=>$openid,'uniacid'=>1,'goodsid'=>$goods_id,'createtime'=>time()]);
        }
        show_json(1,['msg'=>$msg,'status'=>$status]);
    }

     /**
      * 通讯快报
      */
     public function notice()
     {

     }
}
?>