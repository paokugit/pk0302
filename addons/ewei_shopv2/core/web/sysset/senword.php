<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Senword_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>2));
        $d=unserialize($notice["content"]);
        $d=implode(",", $d);
        if ($_W['ispost']){
            $detail=$_GPC["detail"];
            $detail=explode(",", $detail);
            foreach ($detail as $k=>$v)
            {
               $dd=ltrim($v);
               $detail[$k]=rtrim($dd);
            }           
            $detail=serialize($detail);
            pdo_update("ewei_shop_member_devote",array("content"=>$detail),array("id"=>2));
            show_json(1, array('url' => webUrl('sysset/senword')));
            
        }
        include $this->template();
    }
    
    public function circle(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and is_view=:is_view and is_del=0';
        $params = array(':is_view' =>0);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_drcircle') . (' WHERE 1 ' . $condition.'  ORDER BY create_time  DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_drcircle') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        
        //已下架处理的数量
        $is_view=pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_member_drcircle")." where is_view=1");
        
        include $this->template();
    }
    //删除
    public function delete(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        $data["is_del"]=1;
        if (pdo_update("ewei_shop_member_drcircle",$data,array("id"=>$id))){
        show_json(1, array('url' => referer()));
        }else{
            show_json(0,"删除失败");
        }
    }
    
    //详情
    public function detail(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_member_drcircle",array("id"=>$id));
        
        if( $_W["ispost"] ){
            $request=$_POST["request"];
            if (empty($request)){
                show_json(0,"请填写下架原因");
            }
            $data["request"]=$request;
            $data["is_view"]=1;
            if (pdo_update("ewei_shop_member_drcircle",$data,array("id"=>$id))){
                
                
                //消息提醒
                $notic["keyword2"]=date("Y-m-d",time());
                $notic["keyword3"]="您的动态被隐藏，原因是：".$request;
                $this->notice($detail["openid"], $notic);
                
                
                show_json(1,array("url"=>webUrl('sysset/senword/circle')));
            }else{
                show_json(0,"失败");
            }
            
        }
        $detail=pdo_get("ewei_shop_member_drcircle",array("id"=>$id));
        
        $img=unserialize($detail["img"]);
        foreach ($img as $k=>$v){
            $img[$k]=tomedia($v);
        }
        //获取商品
        if ($detail["goods_id"]){
            
            $good=pdo_get("ewei_shop_goods",array("id"=>$detail["goods_id"]));
        }
        
        include $this->template();
    }
    //评论列表
    public function comment(){
        
        global $_W;
        global $_GPC;
        
        //动态圈id
        $id = intval($_GPC['id']);
        $type=$_GPC["type"];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        if ($type==1){
        $condition = ' and is_view=:is_view and is_del=0 and type=:type and parent_id=:parent_id';
        $params = array(':is_view' =>0,':type'=>$type,':parent_id'=>$id);
        }else{
            $condition = ' and is_view=:is_view and is_del=0 and type=:type and classA_id=:parent_id';
            $params = array(':is_view' =>0,':type'=>$type,':parent_id'=>$id);
            
        }
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_drcomment') . (' WHERE 1 ' . $condition.'  ORDER BY create_time  DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
            $list[$k]["create_time"]=date("Y-m-d H:i:s",$v["create_time"]);
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_drcomment') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
        
    }
    //删除评论
    public function delete_comment(){
        global $_W;
        global $_GPC;
        
        $comment_id = intval($_GPC['id']);
        $comment=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment_id,"is_del"=>0,"is_view"=>0));
        if (pdo_update("ewei_shop_member_drcomment",array("is_del"=>1),array("id"=>$comment_id))){
            //更新上级评论数目
            $count=$comment["comment_count"]+1;
            if ($comment["levelid"]){
                $parent_id=unserialize($comment["levelid"]);
                $p="";
                foreach ($parent_id as $k=>$v){
                    if (empty($p)){
                        $p=$v;
                    }else{
                        $p=$p.",".$v;
                    }
                }
                pdo_query('update '.tablename("ewei_shop_member_drcomment").' set comment_count=comment_count-'.$count.' where id in('.$p.')');
            }
            //更新动态
            if ($comment["classA_id"]){
                $c=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment["classA_id"]));
                $circle_id=$c["parent_id"];
            }else{
                $circle_id=$comment["parent_id"];
            }
            pdo_query('update '.tablename("ewei_shop_member_drcircle").' set comment_count=comment_count-'.$count.'  where id='.$circle_id);
            
            show_json(1, array('url' => referer()));
        }else{
            show_json(0,"删除失败");
        } 
    }
    //评论详情
    public function detail_comment(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        if( $_W["ispost"] ){
            $request=$_POST["request"];
            if (empty($request)){
                show_json(0,"请填写下架原因");
            }
            $data["request"]=$request;
            $data["is_view"]=1;
            if (pdo_update("ewei_shop_member_drcomment",$data,array("id"=>$id))){
                //更新评论数据
                $comment=pdo_get("ewei_shop_member_drcomment",array("id"=>$id));
               
                //消息提醒
                $notic["keyword2"]=date("Y-m-d",time());
                $notic["keyword3"]="您的评论：".$comment["content"]."被隐藏，原因是：".$request;
                $this->notice($comment["openid"], $notic);
                
                
                //更新上级评论数目
                $count=$comment["comment_count"]+1;
                if ($comment["levelid"]){
                    $parent_id=unserialize($comment["levelid"]);
                    $p="";
                    foreach ($parent_id as $k=>$v){
                        if (empty($p)){
                            $p=$v;
                        }else{
                            $p=$p.",".$v;
                        }
                    }
                    pdo_query('update '.tablename("ewei_shop_member_drcomment").' set comment_count=comment_count-'.$count.' where id in('.$p.')');
                }
                //更新动态
                if ($comment["classA_id"]){
                    $c=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment["classA_id"]));
                    $circle_id=$c["parent_id"];
                }else{
                    $circle_id=$comment["parent_id"];
                }
                pdo_query('update '.tablename("ewei_shop_member_drcircle").' set comment_count=comment_count-'.$count.'  where id='.$circle_id);
                
                
                
                show_json(1,array('url' => referer()));
            }else{
                show_json(0,"失败");
            }
            
        }
        $comment_id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_member_drcomment",array("id"=>$comment_id));
        include $this->template();
    }
    
    //消息提醒
    public function notice($openid,$data){
        $postdata=array(//状态
            'keyword1'=>array(
                'value'=>"不通过",
                'color' => '#ff510'
            ),//提交时间
            'keyword2'=>array(
                'value'=>$data["keyword2"],
                'color' => '#ff510'
            ),//问题详情
            'keyword3'=>array(
                'value'=>$data["keyword3"],
                'color' => '#ff510'
            )
        );
        p("app")->mysendNotice($openid, $postdata, "", "idavg36TbDRU-xrLW7-5ULrHV14T2z6RJ66DX4xtkz8");
        return true;
    }
    //审核
    public function audit(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
    
        if (pdo_update("ewei_shop_member_drcircle",array("audit"=>1),array("id"=>$id))){
            show_json(1,array("url"=>webUrl('senword/circle')));
        }else{
            show_json(0,"失败");
        }
      
       
    }
}