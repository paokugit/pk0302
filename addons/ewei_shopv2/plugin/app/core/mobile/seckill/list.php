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
}
?>