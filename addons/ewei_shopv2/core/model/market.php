<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}

class Market_EweiShopV2Model{
    
    public function Post($curlPost,$url){
        header("Content-type:text/html; charset=UTF-8");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }
    public function xml_to_array($xml){
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }
    
    //短信发送
    public function send_out($mobile,$content){
        header("Content-type:text/html; charset=UTF-8");
        $target = "http://api.yx.ihuyi.com/webservice/sms.php?method=Submit";
//         $mobile = '136xxxxxxxx';//手机号码，多个号码请用,隔开
        $content=$content."【跑库】";
        $post_data = "account=M67752248&password=6ec49782f14727c76f1068f694a49bb4&mobile=".$mobile."&content=".rawurlencode($content);
        //用户名是登录用户中心->营销短信->产品总览->APIID
        //查看密码请登录用户中心->营销短信->产品总览->APIKEY
        $gets =  $this->xml_to_array($this->Post($post_data, $target));
        if($gets['SubmitResult']['code']==2){
            return true;
        }else{
            return $gets;
        }
    }
    
    //替换 $con模板 $c变量值数组
    public function replace($con,$c){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //         $con="尊敬的会员，您收到一份来自【店铺名称】店铺的好运祝福，请查收>>登录小程序店铺领取【金额】折现金券。快致电【联系方式】退订回T";
        $content=$this->handle($con);
        //         var_dump($content);
        //         $c="跑库,100,134060200";
//         $c=explode(",", $c);
        //         var_dump($c);
        
        foreach ($c as $k=>$v){
            $old="【".$content[$k]."】";
            //              var_dump($old);
            //              var_dump($v);
            $con= substr_replace($con,$v,strpos($con,$old),strlen($old));
            //              var_dump($con);
        }
        return $con;
    }
    //处理
    public function handle($content){
        header('Access-Control-Allow-Origin:*');
        preg_match_all('/\【.*?\】/i', $content,$res);
        
        $l=array();
        $i=0;
        foreach ($res[0] as $k=>$v){
            $str=str_replace("【",'', $v);
            $str=str_replace("】", '', $str);
            $l[$i]=$str;
            $i++;
        }
        //          var_dump($l);
        return $l;
    }
}