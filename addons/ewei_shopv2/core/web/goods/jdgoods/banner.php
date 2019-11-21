<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Banner_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_jdbanner') .(' ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_jdbanner'));
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    public function add(){
        $this->post();
    }
    public function edit(){
        $this->post();
    }
    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $data = array('sort' =>$_GPC['sort'], 'banner' => save_media($_GPC['thumb']));
            
            if (!empty($id)) {
                pdo_update('ewei_shop_jdbanner', $data, array('id' => $id));
               
            }
            else {
                pdo_insert('ewei_shop_jdbanner', $data);
               
            }
            
            show_json(1, array('url' => webUrl('goods/jdgoods/banner')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_jdbanner') . ' where id=:id  limit 1', array(':id' => $id));
        
        include $this->template();
    }
    
    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_jdbanner') . (' WHERE id in( ' . $id . ' ) ') );
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_jdbanner', array('id' => $item['id']));
          
        }
        
        show_json(1, array('url' => referer()));
    }
}