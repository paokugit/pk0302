<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}

class Category_EweiShopV2Page extends WebPage
{
    //列表
    public function main(){
        global $_W;
        global $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_jdgoods_cate') .(' ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_jdgoods_cate'));
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
        
    }
    //添加
    public function add(){
        $this->post();
    }
    //编辑
    public function edit(){
        $this->post();
    }
    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            
            
            $data = array( 'catename' => trim($_GPC['catename']),'sort'=>$_GPC['sort']);
            
            if (!empty($id)) {
                pdo_update('ewei_shop_jdgoods_cate', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_jdgoods_cate', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('goods/jdgoods/category')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_jdgoods_cate') . ' where id=:id limit 1', array(':id' => $id));
        include $this->template();
    }
    //删除
    public function delete(){
        
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        
        $items = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_jdgoods_cate') . (' WHERE id in( ' . $id . ' ) '));
        
        foreach ($items as $item) {
           pdo_delete("ewei_shop_jdgoods_cate",array("id"=>$item["id"]));  
        }
        
        show_json(1, array('url' => referer()));
        
        
    }
}