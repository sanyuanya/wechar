<?php
namespace app\controllers;
use yii;
use yii\web\Controller;
use app\commands\Hj;
use app\models\C;
use app\models\B;
use app\models\M;
class KsController extends Controller{
    public $enableCsrfValidation = false;
    public $session;
    public function init(){
       $this->session = yii::$app->session;
   }
    public function actionCz(){
     
//        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=ACCESS_TOKEN";
//        $url = " https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN";
       $g = [
           "button"=>[
               ['name'=>'看帅哥','type'=>'click','key'=>'k'],
               ['name'=>'报名','type'=>'view','url'=>'http://106.12.205.234/stu/web/index.php?r=ks/bb'],
               ['name'=>'表白说','sub_button'=>[
                                    ['name'=>'发表白','type'=>'click','key'=>'f'],
                                    ['name'=>'查表白','type'=>'click','key'=>'c']
                   ]
                             ]
           ],
           'matchrule'=>[
               "sex"=>"1"
           ]
       ];
       $data = json_encode($g,JSON_UNESCAPED_UNICODE);     
      // echo $data;
       $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".Hj::getToken();
       Hj::getCurl($url,$data,'post');  
    }
    public function actionWx(){
          $xml_str = file_get_contents("php://input");
//          file_put_contents("./test/xml.xml", $xml_str);
//          $xml_str = file_get_contents("./test/xml.xml");
          $xml = simplexml_load_string($xml_str);
          switch($xml->MsgType){
              case 'text': $this->text($xml);break;   
              case 'image':$this->image($xml);break;
              case 'voice':$this->voice($xml);break;
              case 'event':$this->event($xml);break;     
    }
}
    public function event($xml){
        $model = new C();
        switch($xml->Event){
            case "CLICK": 
                switch($xml->EventKey){
                    case "k":   
                       $arr = [
                           ['title'=>'哇咔咔','img'=>'https://t2.hddhhn.com/uploads/tu/201612/98/st93.png','desc'=>'哈哈哈','url'=>'http://www.baidu.com'],
                           ['title'=>'哇咔咔','img'=>'https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=2602558426,100251765&fm=26&gp=0.jpg','desc'=>'过来','url'=>'http://www.baidu.com']
                       ];
                        echo  $this->fun($xml,'news',$arr); exit;
                    case "f": 
                        $model->action = "f";
                        $model->openid = "$xml->FromUserName";
                        $model->insert();
                       echo $this->fun($xml,'text','请输入表白人的名字');exit;
                    case "c":
                        $model->action = "c";
                        $model->openid = "$xml->FromUserName";
                        $model->insert();
                        echo $this->fun($xml,'text','请输入要查询表白人的名字');exit;
                        
                }     
        }
        echo $this->fun($xml,'event');
    }
    public function voice($xml){
       echo $this->fun($xml,'voice',$xml->MediaId);
    }
    public function image($xml){
        
        echo $this->fun($xml,'image',$xml->MediaId);
    }
    public function text($xml){
        
       $model = new C();
       $models = new B(); 
       $sql = "select * from c where openid = '$xml->FromUserName' order by id desc limit 1";
       $data = Yii::$app->db->createCommand($sql)->queryOne();
      if($data['action'] == 'c'){
              $model->action = "end";
              $model->insert();
        	$sqltwo = "select * from b where name = '$xml->Content' limit 5";
        	$res = Yii::$app->db->createCommand($sqltwo)->queryAll();
            $msg = '';
        	foreach ($res as $value) {
        		$msg .= $value['name'].":".$value['content']."\n";
        	}
        	$msg .= "<a href='http://106.12.205.234/basic/web/index.php?r=wx/select&openid={$xml->FromUserName}'>个人信息</a>";
               echo $this->fun($xml,'text',$msg);exit; 
        }            
      if($data['action'] == "f2"){
        
        $model = new C();
        $model->action = "f3";
        $model->openid = "$xml->FromUserName";
        $model->insert();
       
        $sql = "update b set content='$xml->Content' where openid='$xml->FromUserName' and content is null";
        Yii::$app->db->createCommand($sql)->execute();
        echo $this->fun($xml,'text','表白成功');exit; 

    }     
      if($data['action'] == 'f'){
                $model->action = "f2";
                $model->openid = $xml->FromUserName;
                $model->insert();
                $models->name = $xml->Content;
                $models->openid = $xml->FromUserName;
                $models->insert();
                echo $this->fun($xml,'text','请输入你想对他说的话');exit; 
      }
            echo $this->fun($xml,'text',$xml->Content);  
      
    }  
  
    public function fun($xml,$type = '',$stu = ''){
        $str  ="<xml>
               <ToUserName><![CDATA[$xml->FromUserName]]></ToUserName>
               <FromUserName><![CDATA[$xml->ToUserName]]></FromUserName>
               <CreateTime>".time()."</CreateTime>";
        switch($type){
            case "news":  $str .="<MsgType><![CDATA[$type]]></MsgType><ArticleCount>".count($stu)."</ArticleCount><Articles>";                   
                    foreach($stu as $val){ 
                        $str .="<item><Title><![CDATA[".$val['title']."]]></Title><Description><![CDATA[".$val['desc']."]]></Description><PicUrl><![CDATA[".$val['img']."]]></PicUrl><Url><![CDATA[http://www.baidu.com]]></Url></item>";
                    }
                   $str .= "</Articles></xml>";break;
            case "text": $str .="<MsgType><![CDATA[$type]]></MsgType><Content><![CDATA[$stu]]></Content></xml>";break;
            case "image":$str .="<MsgType><![CDATA[$type]]></MsgType><Image><MediaId><![CDATA[$stu]]></MediaId></Image></xml>";break;
            case "voice":$str .="<MsgType><![CDATA[$type]]></MsgType><Voice><MediaId><![CDATA[$stu]]></MediaId></Voice></xml>";break;
            case "event":
                switch($xml->Event){
                case "subscribe": $str .="<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[$stu]]></Content></xml>";break;
                case "unsubscribe": $str .="<MsgType><![CDATA[text]]></MsgType><Content><![CDATA[$stu]]></Content></xml>";break;      
            } 
        
        } 
        return $str;
        
    }
 
    public function actionDel(){
        
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.Hj::getToken();
//        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token='.Hj::getToken();
        
        Hj::getCurl($url);
        
    }
    public function actionUp(){
        
        if(yii::$app->request->isPost){
            $post= yii::$app->request->post();
           
            $suf = trim(strstr($_FILES['filename']['type'],'/'),'/');
         
            $file =  __DIR__.'/../upload/media.'.$suf;
               
            move_uploaded_file($_FILES['filename']['tmp_name'], $file);
      
            $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.Hj::getToken().'&type='.$post['type'];
            $data = Hj::getCurl($url,['media'=>'@'.realpath($file)],'post');
            var_dump($data);die;
           // preg_match_all('/.*?media_id":"(.*?)".*?/', $data,$res);   
            $res = json_decode($data,true);
            $arr = [
                'name'=>$post['name'],
                'media_id'=>$res['media_id'],
                'type'=>$post['type']
            ];
            echo $arr['media_id'];
//            $model = new M();
//            $model->name = $post['name'];
//            $model->ctime = date("Y-m-d H:i:s",time());
//            $model->media_id = $res[1][0];
//            $model->insert();
            
        }else{
            
            return $this->render("upload");
       
        }
             
    }
    public function actionGsc(){
       
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".Hj::getToken()."&media_id=qIJ7-0PwxXeiOo1iujh1WFfbNIemwthOiehNswDlUtriM1HzhMSPtitCffK52V7z";
        $data = Hj::getCurl($url);
        var_dump($data);die;
        
     
    }
    public function actionBb(){
       $session = yii::$app->session;
       
      if($session->has('user')){
          $get = $session->get('user');
                
          $a=$get['openid'];
          $sql = "select * from d where sid = '$a'";
          $data = Yii::$app->db->createCommand($sql)->queryOne();
         
          if(empty($data)){
              return $this->render("add",['openid'=>$get['openid']]);
          }else{         
              return $this->render("forms",['data'=>$data]);
          }
       
//          var_dump($this->session['user']);exit;
          return $this->render("add");
      }else{
            Hj::auth();
      }
   
    }
    public function actionResult(){
       
        if(!$this->session->has('user')){
                $code = yii::$app->request->get('code');
                $param = yii::$app->params;
                $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$param['appId'].'&secret='.$param['appSecret'].'&code='.$code.'&grant_type=authorization_code';
                $data = Hj::getCurl($url);
                $data = json_decode($data,true);
                $this->session['user'] = $data;
        }else{
            $this->redirect(array('ks/bb'));
        }    
    }
    public function actionAdds(){
        $data = yii::$app->request->post();
        $sql = "insert into d (`name`,`sid`) value('".$data['name']."','".$data['sid']."')";
        $data = Yii::$app->db->createCommand($sql)->execute();
        if($data == 1){
            return $this->redirect(array('ks/ss'));
            
        }
        
    }
    
  
 
}
                


