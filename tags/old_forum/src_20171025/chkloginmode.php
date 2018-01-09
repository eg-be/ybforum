<?php


 if (isset($aUser)) {unset($aUser);}

 if ($LoginRequired=="X") {
   if (!($aUser=CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime))) {
	 $iForumPos=strrpos($PHP_SELF,"/");
	 $sForum=substr($PHP_SELF,0,$iForumPos)."/";
     $sUrl="http://$SERVER_NAME"."$sForum"."reglogin.php";
	 header("location: $sUrl");
	 exit();
   }
   $iTime=time()+$MaxLoginTime;
   setcookie("sSessidReg",$sSessidReg,$iTime);
   setcookie("sName",$sName,$iTime);
 }

?>