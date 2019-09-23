<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Level_EweiShopV2Page extends PluginWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        global $_S;
        $set = $_S['commission'];
        $leveltype = $set['leveltype'];
        $default = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3'], 'duihuan' => $set['duihuan']);
        $others = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_commission_level') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY commission1 asc'));
        $list = array_merge(array($default), $others);
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
        global $_S;
        $set = $_S['commission'];
        $leveltype = $set['leveltype'];
        $id = trim($_GPC['id']);

        if ($id == 'default') {
            $level = array('id' => 'default', 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'limit'=>$_GPC['limit'],'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3']);
            $level['duihuan']=$set['duihuan'];
            //获取步数设置
            $set=pdo_get('ewei_setting',array('type'=>"level",'type_id'=>0));
            $level['step']=$set["value"];
        } else {
            $level = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_commission_level') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));
            $set=pdo_get('ewei_setting',array('type'=>"level",'type_id'=>$id));
            $level['step']=$set["value"];
        }
        
        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']),'limit'=>$_GPC['limit'], 'commission1' => trim(trim($_GPC['commission1']), '%'), 'commission2' => trim(trim($_GPC['commission2']), '%'), 'commission3' => trim(trim($_GPC['commission3']), '%'), 'commissionmoney' => trim($_GPC['commissionmoney'], '%'), 'ordermoney' => $_GPC['ordermoney'], 'ordercount' => intval($_GPC['ordercount']), 'downcount' => intval($_GPC['downcount']));

            $data['duihuan'] = $_GPC['duihuan'];
            $data['subscription_ratio']=$_GPC['subscription_ratio'];
            $data["accelerate"]=$_GPC['accelerate'];
            $data["accelerate_day"]=$_GPC['accelerate_day'];
            $step=$_GPC['step'];
            if (!empty($id)) {
                if ($id == 'default') {
                    $updatecontent = '<br/>等级名称: ' . $set['levelname'] . '->' . $data['levelname'] . ('<br/>一级佣金比例: ' . $set['commission1'] . '->' . $data['commission1']) . ('<br/>二级佣金比例: ' . $set['commission2'] . '->' . $data['commission2']) . ('<br/>三级佣金比例: ' . $set['commission3'] . '->' . $data['commission3']);
                    $set['levelname'] = $data['levelname'];
                    $set['commission1'] = $data['commission1'];
                    $set['commission2'] = $data['commission2'];
                    $set['duihuan'] = $data['duihuan'];
//                     $set['subscription_ratio']=$data['subscription_ratio'];
//                     $set['accelerate']=$data['accelerate'];
                    $set['commission3'] = $data['commission3'];
                    $this->updateSet($set);
                   
                    pdo_update('ewei_setting',array('value'=>$step),array('type'=>"level",'type_id'=>0));
                    plog('commission.level.edit', '修改分销商默认等级' . $updatecontent);
                } else {
                    $updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . ('<br/>一级佣金比例: ' . $level['commission1'] . '->' . $data['commission1']) . ('<br/>二级佣金比例: ' . $level['commission2'] . '->' . $data['commission2']) . ('<br/>三级佣金比例: ' . $level['commission3'] . '->' . $data['commission3']);
                    pdo_update('ewei_shop_commission_level', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                    pdo_update('ewei_setting',array('value'=>$step),array('type'=>"level",'type_id'=>$id));
                    plog('commission.level.edit', '修改分销商等级 ID: ' . $id . $updatecontent);
                }
            } else {
                pdo_insert('ewei_shop_commission_level', $data);
                
                $id = pdo_insertid();
                $setdate["value"]=$step;
                $setdate["status"]=1;
                $setdate["type"]="level";
                $setdate["type_id"]=$id;
                pdo_insert('ewei_setting',$setdate);
                plog('commission.level.add', '添加分销商等级 ID: ' . $id);
            }

            show_json(1, array('url' => webUrl('commission/level')));
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

        $items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('ewei_shop_commission_level') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('ewei_shop_commission_level', array('id' => $item['id']));
            plog('commission.level.delete', '删除分销商等级 ID: ' . $id . ' 等级名称: ' . $level['levelname']);
        }

        show_json(1);
    }
}

?>
