<?php
/**
  * wechat php test
  */

//define your token
define("TOKEN", "wxstar404com");
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();


class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    //响应服务器发过来的消息
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //获取到无损的POST数据，存放到$postStr。
        $postStr = file_get_contents('php://input');

        //extract post data
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //判断消息类型
            $msgType = trim( $postObj->MsgType);
            switch( $msgType ){
            case "text":
                $resultStr = $this->handleText($postObj);
                break;
            case "event":
                $resultStr = $this->handleEvent($postObj);
                break;
            default:
                $resultStr = "No this message type:".$msgType;
            }
            echo $resultStr;
        }
        else{
            echo "";
            exit();
        }
    }


    //处理文本信息
    public function handleText($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
            </xml>";
        if(!empty( $keyword ))
        {
            $msgType = "text";

            //截取字符
            $weatherWeather = mb_substr( $keyword, -2, 2, "UTF-8" );
            $weatherCity =  mb_substr( $keyword, 0, -2, "UTF-8" );
            $tranFlag = mb_substr($keyword,0, 2, "UTF-8");
            $tranContent = mb_substr( $keyword,2, -2,"UTF-8" );
            //判断天气，若后两个字符为『天气』则进入
            if( $weatherWeather =="天气" && (! empty($weatherCity ) ))
            {
                $contentStr = $this->weather( $weatherCity );
            }
            if( $tranFlag == "翻译" )
            {
                $contentStr = $this->translate( $tranContent );
            }
            else if($keyword == "你好" ){
                $contentStr = "你好，感谢您的关注！";
            }
            else if( $keyword == "获取vpn" ){
                $contentStr = "感谢您的关注，送您一个vpn帐号，服务器在美国，能用来干什么你懂的。\n服务器:www.star404.com\n连接方式pptp\n帐号:forwechat\n密码star404com";
            }
            else if( $keyword == "帮助"|| $keyword == "help" ){
                $contentStr ="您可以试试回复:\n 1.你好\n 2.获取vpn\n 3.帮助";
            }
            else
                $contentStr = "收到!";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }else{
            echo "Input something...";
        }
    }

    /*天气查询API */
    private function weather($cityName)
    {
       include("./weather_cityId.php");
       @$c_name=$weather_cityId[$cityName];
       if(!empty($c_name)){
           $json=file_get_contents("http://m.weather.com.cn/data/".$c_name.".html");
           $data=json_decode($json); //返回stdClass
           if( !empty($data->weatherinfo ))
           {
               $contentStr = "【".$data->weatherinfo->city."天气预报】\n".$data->weatherinfo->date_y." ".$data->weatherinfo->fchh."时发布"."\n\n实时天气\n".$data->weatherinfo->weather1." ".$data->weatherinfo->temp1." ".$data->weatherinfo->wind1."\n\n温馨提示：".$data->weatherinfo->index_d."\n\n明天\n".$data->weatherinfo->weather2." ".$data->weatherinfo->temp2." ".$data->weatherinfo->wind2."\n\n后天\n".$data->weatherinfo->weather3." ".$data->weatherinfo->temp3." ".$data->weatherinfo->wind3; 
           }
           else 
           {
               $contentStr = "抱歉，没有".$cityName."的天气预报!";
           }

       } else {
           $contentStr="没有该城市".$cityName;
       }
       return $contentStr;
    }
    //翻译功能，调用谷歌接口
    private function translate( $tranContent )
    {
        /*
         include("google_api.php");
         $g = new Google_API_translator(); 
         $g->setText( $tranContent );   
         $g->translate();  
         $ret = $g->out;
         return $ret;
         */
        include("GoogleTranslate.php");
        $tr = new GoogleTranslate();
        $tr->setLangFrom("en");
        $tr->setLangTo("zh");
        $tranReturn = $tranContent.":".$tr->translate($tranContent);
        return $tranReturn;
        
    }

    //处理事件
    public function handleEvent($postObj)
    {
        $contentStr="";
        switch($postObj->Event){
        //订阅时发送的文字
        case "subscribe":
            $contentStr="大家好，欢迎关注星光尽头，这是星魂的个人微信公众帐号。您可以在这里获取到我提供的一些服务以及我的最新信息。感谢关注！";
            break;
        //取消订阅时发送的文字
        case "unsubscribe":
            $contentStr="很遗憾您取消了对我的关注，希望您能发邮件到janyucheng@gmail.com，告诉我哪里做得不够好，帮助我为大家提供真正有用的信息和服务！";
            break;
        default:
            $contentStr="Unknow event ".$postObj->Event;
        }
        $resultStr = $this->responseText( $postObj, $contentStr );
        return $resultStr;
    }

    function responseText( $postObj, $contentStr, $flag=0)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
            </xml>";

        $resultStr = sprintf($textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $contentStr, $flag);
        return $resultStr;
    }
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
