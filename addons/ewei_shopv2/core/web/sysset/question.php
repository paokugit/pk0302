<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Question_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_notive_question') . ('order by create_time desc limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $member=m("member")->getMember($v["openid"]);
            $list[$k]["nickname"]=$member["nickname"];
            
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_notive_question') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    //删除
    public function delete(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        pdo_delete('ewei_shop_notive_question', array('id' =>$id));
        
        show_json(1, array('url' => referer()));
        
    }
    //详情
    public function detail(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_notive_question",array("id"=>$id));
        if( $_W["ispost"] ){
        
            $answer=$_POST["answer"];
            if (empty($answer)){
                show_json(0);
            }
            $data["answer"]=$answer;
            $data["is_answer"]=1;
            if (pdo_update("ewei_shop_notive_question",$data,array("id"=>$id))){
                //消息提醒
                $member=m("member")->getMember($detail["openid"]);
                $xx["keyword1"]=$member["nickname"];
                $xx["keyword2"]=date("Y-m-d H:i:s",$detail["create_time"]);
                $xx["keyword3"]=$detail["content"];
                $xx["keyword4"]=$answer;
                $xx["keyword5"]="如有疑问，请联系跑库客服";
                $this->notice($detail["openid"], $xx);
                show_json(1);
            }else{
                show_json(0);
            }
            
            
        }
        
        $img=array();
        if ($detail["img"]){
            $img=unserialize($detail["img"]);
            foreach ($img as $k=>$v){
                $img[$k]=tomedia($v);
            }
        }
        //var_dump($img);
        include $this->template();
    }
    
    //消息提醒
    public function notice($openid,$data){
        $postdata=array(//问题提交者
            'keyword1'=>array(
                'value'=>$data["keyword1"],
                'color' => '#ff510'
            ),//提交时间
            'keyword2'=>array(
                'value'=>$data["keyword2"],
                'color' => '#ff510'
            ),//问题详情
            'keyword3'=>array(
                'value'=>$data["keyword3"],
                'color' => '#ff510'
            ),//问题回复
            'keyword4'=>array(
                'value'=>$data["keyword4"],
                'color' => '#ff510'
            ),
            'keyword5'=>array(
                'value'=>$data["keyword5"],
                'color' => '#ff510'
            )
        );
        p("app")->mysendNotice($openid, $postdata, "", "pwku4hZeb-4h85URhEFTNq17bX-EM70Pj7zHdkIgwm8");
        return true;
    }
}