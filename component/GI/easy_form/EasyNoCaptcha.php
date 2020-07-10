<?php
  @session_start();
  $_ENC_Before = '';
  $_ENC_shuffle = array();
  $_ENC_GR_key = '';
  $_ENC_GR_secret = '';
  $_ENC_AllReadyFunc = array();
  /**********************/
  function AddToShuffle ($text){
    global $_ENC_shuffle;
    $_ENC_shuffle[] = $text; 
  }
  /**********************/
  function ShuffleText () {
    global $_ENC_shuffle;
    $result = '';
    foreach ($_ENC_shuffle as $t) {
      if (rand(0,100) < 50) {
        $result .= $t;
      } else {
        $result = $t.$result ;
      }
    }
    return $result;
  }
  /**********************/
  function AddGoogleRecaptcha($key, $secret) {
    global $_ENC_GR_key;
    global $_ENC_GR_secret;
    $_ENC_GR_key = $key;
    $_ENC_GR_secret = $secret;
  }
  /**********************/
  function EncodeJsString($string, $level=0){
    global $_ENC_string;
    global $_ENC_Before;
    global $_ENC_AllReadyFunc;
    $i = 0;
    $result = '';
    $needEncode = true;
    if (!$needEncode) {
      $result = $string;
    }
    while ($needEncode) {
      $needEncode = false;
      while ($i <= strlen($string)) {
        $char = substr($string, $i, 1);
        if (($i > 0) and ($i < strlen($string) - 1)) {
          if ((rand(0,100) < 20) || ($char == '<')) {
            $d = dechex(ord($char));
            $c = (strlen($d) == 1) ? '0'.$d : $d;
            $char = '\x'.$c;
          } else if (rand(0,100) < 20) {
            $char = "'+/*".substr(md5(uniqid()) , 0 ,rand(1,32) )."*/'".$char;
          } else if (((rand(0,100) < 20) && ($level < 3)) && (true)) {
             $val = substr('bf'.md5(uniqid()) , 0 ,rand(10,32) );
             $func = substr('bf'.md5(uniqid()) , 0 ,rand(10,32) );
             while (in_array($func, $_ENC_AllReadyFunc)) {
               $func = substr('bf'.md5(uniqid()) , 0 ,rand(10,32) );
             }
             $_ENC_AllReadyFunc[] = $func;
             $c = rand(0, strlen($string) - 2 - $i);
             if ($c > 0) {
               $char = substr($string, $i, $c);
               $i += $c-1;
               $ftext =  'function '.$func.'(){var '.$val.'=\''.EncodeJsString($char, $level+1).'\'; return '.$val.';}'."\n";
               $char = "'+".$func."()+'";
               AddToShuffle($ftext);
             }
          } 
        }
        $i ++;
        $result .= $char;
      }
    }
    return $result;
  }
  /**********************/
  function SetEasyNoCaptcha($_protect = 3, $_form = 'form') {
    global $_ENC_string;
    global $_ENC_Before;    
    global $_ENC_GR_key;
    global $_ENC_GR_secret;  
    $_ENC_string = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) );
    $_ENC_this = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) );
    $_ENC_form = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) );
    $_ENC_l = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) );
    $_ENC_c = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) );
    $_ENC_function = 'function';
    $CODE = substr(md5(uniqid()) , 0 ,rand(10,32) );
    $_SESSION['MYHASH'][$CODE] = substr(md5(uniqid()) , 0 ,rand(10,32) );
    $_ADDNOCAPTHCA =  substr( base64_encode( md5( uniqid() ) ),0, rand(10,32) );   
    $_SETNOCAPTHCA =  substr( base64_encode( md5( uniqid() ) ),0, rand(10,32) );   
    $result = 
    "$(".EncodeJsString("'document'", 2).")[".EncodeJsString("'ready'",2)."](function(){"."\n".
      "var ".$_ENC_l."=0,s=0,".$_ENC_form.";"."\n";
    if ($_ENC_GR_key != '') {
      $result .= 
      "$(".EncodeJsString("'".$_form."'").")[".EncodeJsString("'each'")."](".$_ENC_function."(){"."\n".
        "".$_ENC_form." = this;"."\n".
        "if ($(".$_ENC_form.")[".EncodeJsString("'find'")."]('.g-recaptcha').length == 0) {"."\n".
          "if ($(".$_ENC_form.")[".EncodeJsString("'find'")."]('input[type=submit]').length > 0) {"."\n".
            "$(".$_ENC_form.")[".EncodeJsString("'find'")."](".EncodeJsString("'input[type=submit]'").").first().before('<div class=\"g-recaptcha\" data-sitekey=\"".$_ENC_GR_key."\"></div>');"."\n".
          "} else if ($(".$_ENC_form.")[".EncodeJsString("'find'")."]('input[type=button]').length > 0) {"."\n".
            "$(".$_ENC_form.")[".EncodeJsString("'find'")."](".EncodeJsString("'input[type=button]'").").first().before('<div class=\"g-recaptcha\" data-sitekey=\"".$_ENC_GR_key."\"></div>');"."\n".
          "} else {"."\n".
            "$(".$_ENC_form.")[".EncodeJsString("'append'")."]('<div class=\"g-recaptcha\" data-sitekey=\"".$_ENC_GR_key."\"></div>');"."\n".
          "}"."\n".
        "}"."\n".
        "$('body')[".EncodeJsString("'append'")."](".EncodeJsString("'<script src=\"https://www.google.com/recaptcha/api.js\"></script>'").");"."\n".
      "});"."\n";
    }
    $result .= 
      "setTimeout(".$_ENC_function."(){"."\n".
        "$(".EncodeJsString("'".$_form."'").")[".EncodeJsString("'each'")."](".$_ENC_function."(){"."\n".
          "".$_ENC_form." = this;"."\n".
          "if(!$(".$_ENC_form.")[".EncodeJsString("'hasClass'")."]('".$_ADDNOCAPTHCA."')){"."\n".
              "$(".$_ENC_form.")[".EncodeJsString("'on'")."](".EncodeJsString("'click touchstart touchmove touchend keydown keyup mouseenter mouseleave mousemove'").",function(){"."\n".
                "var ".$_ENC_this." = this;"."\n".
                "var ".$_ENC_c." = ".$_protect." * $(".$_ENC_this.")[".EncodeJsString("'find'")."](".EncodeJsString("'input, textarea, select'").")[".EncodeJsString("'length'")."];"."\n".
                "".$_ENC_l."++;"."\n".
                "if(".$_ENC_l." > ".$_ENC_c."){"."\n".
                  "if(!$(".$_ENC_this.")[".EncodeJsString("'hasClass'")."]('".$_SETNOCAPTHCA."')){"."\n".
                    "$(".$_ENC_this.")[".EncodeJsString("'append'")."](".EncodeJsString("'<input type=\"hidden\" name=\"HASH\" value=\"".$_SESSION['MYHASH'][$CODE]."\">'").");"."\n".
                    "$(".$_ENC_this.")[".EncodeJsString("'append'")."](".EncodeJsString("'<input type=\"hidden\" name=\"HASHCODE\" value=\"".$CODE."\">'").");"."\n".
                    "$(".$_ENC_this.")[".EncodeJsString("'addClass'")."]('".$_SETNOCAPTHCA."');"."\n".
                  "};"."\n".
                "};"."\n".
              "});"."\n".
              "$(".$_ENC_form.")[".EncodeJsString("'addClass'")."]('".$_ADDNOCAPTHCA."');"."\n".
          "}"."\n".
        "})"."\n".
      "},1000);"."\n".
    "});"."\n";
    AddToShuffle($result);
    $result = str_replace("\n", '', ShuffleText());
    $result = '<script type="text/javascript">'.$result.'</script>';
    return $result;
  }
  /**********************/
  function CheckEasyNoCaptha () {
    global $_ENC_GR_key;
    global $_ENC_GR_secret; 
    $result = false;
    if ($_REQUEST['HASHCODE'] != '') {
      if (($_SESSION['MYHASH'][$_REQUEST['HASHCODE']] == $_REQUEST['HASH']) && ($_SESSION['MYHASH'][$_REQUEST['HASHCODE']] != '')){
        $result = true;
        if ($_ENC_GR_key != '') {
          $secret = $_ENC_GR_secret;
          $g_response = $_REQUEST['g-recaptcha-response'];
          $remoteip = $_SERVER['REMOTE_ADDR'];
          $url = 'https://www.google.com/recaptcha/api/siteverify?&secret=' . $secret . '&response=' . $g_response . '&remoteip=' . $remoteip;
          $curl = curl_init($url);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
          curl_setopt($curl, CURLOPT_TIMEOUT, 1);
          $curl_json = curl_exec($curl);
          curl_close($curl);
          $g_verify = json_decode($curl_json, true);
          if ($g_verify['success'] == false) {
            $result = false;
          } 
        } 
      };
      unset($_SESSION['MYHASH'][$_REQUEST['HASHCODE']]);
    };
    return $result;
    
  }
?>