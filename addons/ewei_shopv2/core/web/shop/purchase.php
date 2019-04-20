<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Purchase_EweiShopV2Page extends WebPage{
    
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
       
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_merch_purchase') . (' WHERE 1 ' . $condition . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_merch_purchase') . (' WHERE 1 ' . $condition), $params);
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
    
    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'],'money'=>trim($_GPC["money"]),'give'=>trim($_GPC["give"]));
            
            if (!empty($id)) {
                pdo_update('ewei_shop_merch_purchase', $data, array('id' => $id));
                plog('shop.purchase.edit', '修改: ' . $id);
            }
            else {
                pdo_insert('ewei_shop_merch_purchase', $data);
                $id = pdo_insertid();
                plog('shop.purchase.add', '添加: ' . $id);
            }
            
            show_json(1, array('url' => webUrl('shop/purchase')));
        }
        
        $purchase = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_merch_purchase') . ' WHERE id =:id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
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
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_merch_purchase') . (' WHERE id in( ' . $id . ' )  AND uniacid=') . $_W['uniacid']);
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_merch_purchase', array('id' => $item['id']));
            plog('shop.purchase.delete', '删除充值设置 ID: ' . $item['id']);
        }
        
        show_json(1, array('url' => referer()));
    }
}