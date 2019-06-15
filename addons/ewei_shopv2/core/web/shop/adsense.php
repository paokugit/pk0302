<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Adsense_EweiShopV2Page extends WebPage{
    
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
       
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and title  like :keyword';
            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
        }
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_adsense') . (' WHERE 1 ' . $condition . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
       
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_adsense') . (' WHERE 1 ' . $condition), $params);
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
            $data = array('uniacid' => $_W['uniacid'], 'title' => trim($_GPC['title']), 'url' => trim($_GPC['url']), 'sort' =>$_GPC['sort'], 'thumb' => save_media($_GPC['thumb']),'type'=>$_GPC['type']);
            $data["create_time"]=time();
            
            if (!empty($id)) {
                pdo_update('ewei_shop_adsense', $data, array('id' => $id));
                plog('shop.adsense.edit', '修改 ID: ' . $id);
            }
            else {
                pdo_insert('ewei_shop_adsense', $data);
                $id = pdo_insertid();
                plog('shop.adsense.add', '添加 ID: ' . $id);
            }
            
            show_json(1, array('url' => webUrl('shop/adsense')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_adsense') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
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
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_adsense') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_adsense', array('id' => $item['id']));
            plog('shop.adsense.delete', '删除幻灯片 ID: ' . $item['id']);
        }
        
        show_json(1, array('url' => referer()));
    }
    
}