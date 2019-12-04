<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Image_EweiShopV2Page extends WebPage{
    
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);

        //位置
        $type = $_GPC['type'];

        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and title  like :keyword';
            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
        }

        if(!empty($type)){
            $condition .= ' and type = :type';
            $params[':keyword'] = $type;
        }

        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_icon') . (' WHERE 1 ' . $condition . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
       
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_icon') . (' WHERE 1 ' . $condition), $params);
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
            $data = array('uniacid' => $_W['uniacid'], 'title' => trim($_GPC['title']), 'link' => trim($_GPC['link']), 'displayorder' =>$_GPC['displayorder'], 'image' => save_media($_GPC['image']),'type'=>$_GPC['type'], 'cate'=>$_GPC['cate']);
            $data["createtime"]=time();
            
            if (!empty($id)) {
                pdo_update('ewei_shop_icon', $data, array('id' => $id));
                plog('shop.image.edit', '修改 ID: ' . $id);
            }
            else {
                pdo_insert('ewei_shop_icon', $data);
                $id = pdo_insertid();
                plog('shop.image.add', '添加 ID: ' . $id);
            }
            show_json(1, array('url' => webUrl('shop/image')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_icon') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
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
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_icon') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_icon', array('id' => $item['id']));
            plog('shop.image.delete', '删除幻灯片 ID: ' . $item['id']);
        }
        
        show_json(1, array('url' => referer()));
    }

    public function enabled()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);

        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }

        $items = pdo_fetchall('SELECT id,status FROM ' . tablename('ewei_shop_icon') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        foreach ($items as $item) {
            $status = $item['status'] == 1 ? 0 : 1;
            $msg = $item['status'] == 1 ? "关闭" : "开启";
            pdo_update('ewei_shop_icon', ['status'=>$status] , array('id' => $item['id']));
            plog('shop.image.enabled', '修改幻灯片 ID: ' . $item['id'] . '的状态为'.$msg);
        }

        show_json(1, array('url' => referer()));
    }
}