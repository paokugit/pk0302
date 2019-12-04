<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Choice_EweiShopV2Page extends WebPage{
    
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);

        //活动分类
        $icon = pdo_fetchall('select id,title from '.tablename('ewei_shop_icon').'where cate in (1,2) ');

        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and title  like :keyword';
            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
        }
        $cate = $_GPC['cate'];
        if(!empty($cate)){
            $condition .= ' and cate = :cate';
            $params[':cate'] = $cate;
        }

        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_choice') . (' WHERE 1 ' . $condition . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $key=>$value){
            $list[$key]['cate'] = pdo_getcolumn('ewei_shop_icon',['id'=>$value['icon_id']],'title');
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_choice') . (' WHERE 1 ' . $condition), $params);
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

        //活动分类
        $icon = pdo_fetchall('select id,title from '.tablename('ewei_shop_icon').'where cate in (1,2) ');

        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'title' => trim($_GPC['title']), 'displayorder' =>$_GPC['displayorder'], 'image' => save_media($_GPC['image']),'thumb' => save_media($_GPC['thumb']),'icon_id'=>$_GPC['icon_id'],'content'=>trim($_GPC['content']));
            $data['goodsids'] = implode(',',$_GPC['goodsid']);
            if (!empty($id)) {
                pdo_update('ewei_shop_choice', $data, array('id' => $id));
                plog('shop.choice.edit', '修改 ID: ' . $id);
            }
            else {
                $data["createtime"]=time();
                pdo_insert('ewei_shop_choice', $data);
                $id = pdo_insertid();
                plog('shop.choice.add', '添加 ID: ' . $id);
            }
            show_json(1, array('url' => webUrl('shop/choice')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_choice') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        if(!empty($item['icon_id'])){
            $item['cate'] = pdo_getcolumn('iwei_shop_icon',['id'=>$item['icon_id']],'title');
        }
        if (!empty($item)) {
            if (!empty($item['goodsids'])) {
                $goods = pdo_fetchall('SELECT id,uniacid,title,thumb FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid=:uniacid AND id IN (' . $item['goodsids']. ')', array(':uniacid' => $_W['uniacid']));
            }
        }
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
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_choice') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_choice', array('id' => $item['id']));
            plog('shop.choice.delete', '删除跑库精选 ID: ' . $item['id']);
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

        $items = pdo_fetchall('SELECT id,status FROM ' . tablename('ewei_shop_choice') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        foreach ($items as $item) {
            $status = $item['status'] == 1 ? 0 : 1;
            $msg = $item['status'] == 1 ? "关闭" : "开启";
            pdo_update('ewei_shop_choice', ['status'=>$status] , array('id' => $item['id']));
            plog('shop.choice.enabled', '修改跑库精选 ID: ' . $item['id'] . '的状态为'.$msg);
        }

        show_json(1, array('url' => referer()));
    }
}