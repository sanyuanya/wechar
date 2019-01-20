<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use app\commands\Helper;
class KcController extends Controller{
   
public $session;
public function init(){
    $this->session = yii::$app->session;
}


public function actionL(){
 
    if($this->session->has("result")){
         $sql = "select * from kc";
         $data = yii::$app->db->createCommand($sql)->queryAll();
         return $this->render("index",['data'=>$data]);
    }else{
        $url = "http://106.12.205.234/xx/web/index.php?r=kc/node";
        $res = urlencode($url);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7c7dba436bf4cd81&redirect_uri=$res&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
        Helper::getCurl($url,'get');
        header("location:".$url);
       
    }
}
public function actionNode(){
   
     if($this->session->has("result")){
        
         return $this->redirect(array('kc/l'));
       
    }else{
       
       $code  = $_GET['code'];
       $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx7c7dba436bf4cd81&secret=b5690f176599c239fad3473090bf22b5&code={$code}&grant_type=authorization_code";
       $data =  Helper::getCurl($url,"get");
       $res = json_decode($data,true);
       $this->session->set("result",$res);
    }
       
}
    
  public function actionCd(){
      $button = [
          "button"=>[
              ["name"=>"查看课程","type"=>"click","key"=>"c"],
              ["name"=>"课程管理","type"=>"view","url"=>"http://106.12.205.234/xx/web/index.php?r=kc/l"]
          ]
      ];
   
      $res = json_encode($button,JSON_UNESCAPED_UNICODE);
      $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".Helper::getToken();
      echo Helper::getCurl($url,"post",$res);     
  }
 public function actionLists(){
     
     $s = $this->session->get("result");
     $openid =   $s['access_token'];
     
     $sql = "insert into s(`sess`) values('$openid')";
     yii::$app->db->createCommand($sql)->execute();
     
     $data = yii::$app->request->post();
      $arr = [1,2,3,4];
     foreach($data['name'] as $key => $val){
        
         $sql = "insert into kc1(`openid`,`k_id`,`kj`) values('$openid','$val','".$arr[$key]."')";
         $type = yii::$app->db->createCommand($sql)->execute();
     } 
 }
 
 public function actionIndex(){
    
    $xml_str = file_get_contents("php://input");
    file_put_contents('./kc/xml.xml', $xml_str);
//    $xml_str = file_get_contents('./kc/xml.xml');
    $xml = simplexml_load_string($xml_str);
    switch ($xml->MsgType) {
        case "event":$this->event($xml);break;
    }
    
}
 
 public function event($xml){
    
    switch ($xml->Event){
        
        case "CLICK":
            
            switch($xml->EventKey){
            
                case "c": 
                 $sql = "select sess from s order by id desc limit 1";
                 $num = yii::$app->db->createCommand($sql)->queryOne();
                 $sess = $num['sess'];   
                 $sql = "select * from kc1 where openid='$sess'";
                 $data = yii::$app->db->createCommand($sql)->queryAll();
                 if($data){
                    
                     $sql = "select * from kc1 k join kc c on k.k_id=c.id where openid='$sess'";
                     $res = yii::$app->db->createCommand($sql)->queryAll();
//                     echo "<pre>";
//                     print_r($res);
                     echo $this->fun($xml,'t',$res);
                     
                    
                 }else{
                      
                     echo $this->fun($xml,'text','请选择课程');
                 }
                 
                
                 
            }
            
    }
    
}
public function fun($xml,$type,$res){
    $str = "<xml><ToUserName><![CDATA[$xml->FromUserName]]></ToUserName><FromUserName><![CDATA[$xml->ToUserName]]></FromUserName><CreateTime>".time()."</CreateTime>";
    switch($type){   
        case "text": $str .= "<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[$res]]></Content></xml>";break;
        case "t":
            $arr = ['第一节','第二节','第三节','第四节'];
           $rew = '';
                foreach($res as $key => $val){
                        $rew .= $arr[$key].":".$val['name']."\n"; 
                }
             
            
            $str .= "<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[你好你的课程安排如下\n\n{$rew}]]></Content></xml>";break;
    }
    return $str;
}
  
    
}
        


