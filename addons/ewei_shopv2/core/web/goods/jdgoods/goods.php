<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}

class Goods_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        //获取分类
        $category=pdo_fetchall("select * from ".tablename("ewei_shop_jdgoods_cate"));
//         var_dump($category);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition='and isdelete=:delete';
        $param=array(':delete'=>0);
        if ($_GPC["onsale"]){
            if ($_GPC["onsale"]==2){
                $onsale=0;
            }else{
                $onsale=1;
            }
            $condition=$condition." and onsale=:onsale";
            $param[":onsale"]=$onsale;
        }
        if ($_GPC["cateid"]){
            $condition=$condition." and cateid=:cateid";
            $param[":cateid"]=$_GPC["cateid"];
        }
        $keyword=$_GPC["keyword"];
        if ($keyword){
            $condition=$condition." and keyword like %".":keyword"."%";
            $param[":keyword"]=$keyword;
        }
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_jdgoods') . (' WHERE 1 ' . $condition. " order by id desc " . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $param);
        $url=m("jdgoods")->homeaddr();
        $sku="";
        foreach ($list as $k=>$v){
            $list[$k]["imagePath"]=$url.$v["imagePath"];
            //获取分类
            if ($v["cateid"]){
                $cate=pdo_get("ewei_shop_jdgoods_cate",array("id"=>$v["cateid"]));
                $list[$k]["cate_name"]=$cate["catename"];
            }else{
                $list[$k]["cate_name"]="暂未设置";
            }
            if (empty($sku)){
                $sku=$v["sku"];
            }else{
                $sku=$sku.",".$v["sku"];
            }
        }
        //获取价格
        $res=m("jdgoods")->batch_price($sku);
       
       if ($res["success"]){
           $price=$res["result"];
           //更新数据库
           foreach ($price as $k=>$v){
               $d["price"]=$v["price"];
               $d["jdprice"]=$v["jdPrice"];
               pdo_update("ewei_shop_jdgoods",$d,array("sku"=>$v["skuId"]));
               
           }
       }
       $sale=m("jdgoods")->sale($sku);
       $sale=$sale["result"];
//        var_dump($sale);die;
       foreach ($sale as $k=>$v){
           $dd["is7ToReturn"]=$v["is7ToReturn"];
           $dd["saleState"]=$v["saleState"];
           pdo_update("ewei_shop_jdgoods",$dd,array("sku"=>$v["skuId"]));
       }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_jdgoods')." where 1 ".$condition,$param);
        $pager = pagination2($total, $pindex, $psize);
       
        include $this->template();
    }
    //数据更新
    public function change()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if( empty($id) )
        {
            show_json(0, array( "message" => "参数错误" ));
        }
     
        $type = trim($_GPC["type"]);
        $value = trim($_GPC["value"]);
        if( !in_array($type, array( "ptprice","virtual_sales" )) )
        {
            show_json(0, array( "message" => "参数错误" ));
        }
        $goods = pdo_fetch("select * from " . tablename("ewei_shop_jdgoods") . " where id=:id  limit 1", array( ":id" => $id ));
        if( empty($goods) )
        {
            show_json(0, array( "message" => "参数错误" ));
        }
//         var_dump($goods);
//         var_dump($goods["price"]>$value);
//         die;
        if( $type == "ptprice" )
        {
            if( $goods["price"] >$value )
            {
                show_json(0, array( "message" => "定价不能小于成本价" ));
            }
        }
       
        $result = pdo_update("ewei_shop_jdgoods", array( $type => $value ), array( "id" => $id ));
        plog('jdgoods.change', '编辑优品商品 ID: ' . $id . '<br>' . "更新".$type);
        show_json(1);
    }
    //更新状态
    public function status(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        $goods=pdo_fetch("select * from ".tablename("ewei_shop_jdgoods")." where id=:id",array(":id"=>$id));
        $status=$_GPC["status"];
        if (($goods["ptprice"]<$goods["price"])&&$status==1){
            show_json(0, "商品售价不可低于成本价");
        }
         $data["onsale"]=$status;
         pdo_update("ewei_shop_jdgoods",$data,array("id"=>$id));
         plog('jdgoods.change', '编辑优品商品 ID: ' . $id . '<br>' . "更新状态");
        show_json(1, array( "url" => referer() ));
        
    }
    //删除
    public function delete(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if( empty($id) )
        {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }
        $items = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_jdgoods") . " WHERE id in( " . $id . " ) ");
        foreach( $items as $item )
        {
            pdo_update("ewei_shop_jdgoods", array( "isdelete" => 1 ), array( "id" => $item["id"] ));
           
        }
        plog('jdgoods.delete', '删除优品商品 ID' );
        show_json(1, array( "url" => referer() ));
        
    }
    //编辑
    public function edit(){
        $this->post();
    }
    public function post(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        $detail=pdo_get("ewei_shop_jdgoods",array("id"=>$id));
        if ($_W['ispost']) {
            $data["cateid"]=$_GPC["cateid"];
            $data["ptprice"]=$_GPC["ptprice"];
            if ($data["ptprice"]<$detail["price"]){
                show_json(0,"售卖价格不可小于成本价");
            }
            $data["onsale"]=$_GPC["onsale"];
            $data["virtual_sales"]=$_GPC["virtual_sales"];
            $data["level"]=$_GPC["level"];
            if (pdo_update("ewei_shop_jdgoods",$data,array("id"=>$id))){
                plog('jdgoods.post', '编辑优品商品 ID'.$id );
                show_json(1, array('url' => webUrl('goods/jdgoods/goods')));
            }else{
                show_json(0,"失败");
            }
        }
       
        $cate=pdo_fetchall("select * from ".tablename("ewei_shop_jdgoods_cate")." order by sort desc");
        $levels=pdo_fetchall("select * from ".tablename("ewei_shop_commission_level")." where id<6 order by id asc");
        include $this->template();
    }
}