<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Shoptop_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and is_del=:is_del';
        $params = array(':is_del' => 0);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_goodtop') . (' WHERE 1 ' . $condition.' ORDER BY sort DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $good=pdo_get("ewei_shop_goods",array("id"=>$v["goodid"]));
            $list[$k]["goodname"]=$good["title"];
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_goodtop') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    
    public function add()
    {
        $this->post();
    }
    
    public function edit()
    {
        $this->post();
    }
    
    
    //添加
    public function post(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $goods_id=$_GPC['goodsid'];
            if (sizeof($goods_id)>1){
                show_json(0, "只可选择一件商品");
            }
            $main_target=trim($_GPC["main_target"]);
            if (mb_strlen($main_target, 'UTF8')>8){
                show_json(0,"主标的长度最长为8");
            }
            $substandard=trim($_GPC["substandard"]);
            if (mb_strlen($substandard, 'UTF8')>10){
                show_json(0,"副标的长度最长为10");
            }
            $day=date("Y-m-d");
            
            if ($id){
                //判断是否有该位置商品
                $g=pdo_fetch("select * from ".tablename("ewei_shop_goodtop")." where end_date>:end_date and is_del=0 and sort=:sort and id!=:id",array(":end_date"=>$day,':sort'=>$_GPC["sort"],':id'=>$id));
            }else {
                $g=pdo_fetch("select * from ".tablename("ewei_shop_goodtop")." where end_date>:end_date and is_del=0 and sort=:sort",array(":end_date"=>$day,':sort'=>$_GPC["sort"]));
            }
            if ($g){
                show_json(0,"该位置已有商品，请先结束再添加");
            }
            $goodid=$goods_id[0];
            $data = array( 'sort' => intval($_GPC['sort']), 'main_target' =>$main_target, 'substandard' =>$substandard,  'goodid' => $goodid, 'start_date' =>$_GPC["start_date"], 'end_date' => $_GPC["end_date"],'create_time'=>time());
            if (!empty($id)) {
                pdo_update('ewei_shop_goodtop', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_goodtop', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/shoptop')));
        }
        if ($id){
        $detail = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_goodtop') . ' WHERE id =:id  limit 1', array(':id' => $id));
        $goods=pdo_fetchall("select * from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$detail["goodid"]));
        }
        include $this->template();
    }
    //删除
    public function delete(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        
        
        pdo_update("ewei_shop_goodtop",array("is_del"=>1),array("id"=>$id));
        
        
        show_json(1, array('url' => referer()));
        
    }
}