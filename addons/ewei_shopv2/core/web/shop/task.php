<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Task_EweiShopV2Page extends WebPage{
    
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);

        //活动分类
        $icon = pdo_fetchall('select id,task_cate from '.tablename('ewei_shop_task_money_cate').'where status = 1 ');

        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and task  like :keyword';
            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
        }
        $cate = $_GPC['cate'];
        if(!empty($cate)){
            $condition .= ' and task_cate = :cate';
            $params[':cate'] = $cate;
        }

        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_task_money') . (' WHERE 1 ' . $condition . ' limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $key=>$value){
            $list[$key]['task_cate'] = pdo_getcolumn('ewei_shop_task_money_cate',['id'=>$value['task_cid']],'task_cate');
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_task_money') . (' WHERE 1 ' . $condition), $params);
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
        $icon = pdo_fetchall('select id,task_cate from '.tablename('ewei_shop_task_money_cate').'where status = 1 ');

        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'task' => trim($_GPC['task']),  'credit_type' => trim($_GPC['credit_type']),'task_cid'=>$_GPC['task_cid'],'desc'=>trim($_GPC['desc']),'content'=>trim($_GPC['content']),'status'=>trim($_GPC['status']));
            if(empty($_GPC['max'])){
                $data['max'] = $data['min'] = $_GPC['min'];
            }else{
                $data['min'] = $_GPC['min'];
                $data['max'] = $_GPC['max'];
            }

            $data['goodsids'] = !empty($_GPC['goodsid']) ? implode(',',$_GPC['goodsid']) : "";

            if (!empty($id)) {
                pdo_update('ewei_shop_task_money', $data, array('id' => $id));
                plog('shop.task.edit', '修改 ID: ' . $id);
            }
            else {
                $data["createtime"]=time();
                pdo_insert('ewei_shop_task_money', $data);
                $id = pdo_insertid();
                plog('shop.task.add', '添加 任务领钱 ID: ' . $id);
            }
            show_json(1, array('url' => webUrl('shop/task')));
        }
        
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_task_money') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        if(!empty($item['icon_id'])){
            $item['task_cate'] = pdo_getcolumn('ewei_shop_task_money_cate',['id'=>$item['task_cid']],'task_cate');
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
        
        $items = pdo_fetchall('SELECT id FROM ' . tablename('ewei_shop_task_money') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        
        foreach ($items as $item) {
            pdo_delete('ewei_shop_task_money', array('id' => $item['id']));
            plog('shop.task.delete', '删除任务领钱 ID: ' . $item['id']);
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

        $items = pdo_fetchall('SELECT id,status FROM ' . tablename('ewei_shop_task_money') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        foreach ($items as $item) {
            $status = $item['status'] == 1 ? 0 : 1;
            $msg = $item['status'] == 1 ? "关闭" : "开启";
            pdo_update('ewei_shop_task_money', ['status'=>$status] , array('id' => $item['id']));
            plog('shop.task.enabled', '修改任务领钱 ID: ' . $item['id'] . '的状态为'.$msg);
        }

        show_json(1, array('url' => referer()));
    }
}