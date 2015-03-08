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

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents('php://input');

        //extract post data
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //判断消息类型
            $msgType = trim( $postObj->MsgType);
            switch( $msgType ){
            case "text":
                $resultStr = $this->handleText();
                break;
            case "event":
                $resultStr = $this->handleEvent();
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
            $contentStr = "Welcome to wechat world!";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }else{
            echo "Input something...";
        }
    }

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
            $contentStr="听说您取消了对我的关注，希望您能发邮件到janyucheng@gmail.com，告诉我哪里做得不好，帮助我为大家提供真正有用的信息和服务！";
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

        $resultStr = sprintf($textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $content, $flag);
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
