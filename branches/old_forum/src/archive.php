<?php
  while (list ($sKey, $sVal) = each ($_POST)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_GET)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_SERVER)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_COOKIE)) {
    $$sKey=$sVal;
  }
  
  include ("cfg/config.php");
  include ("functions.php");
  include ("prechecks.php");
  include_once("chkloginmode.php");

  $sUrl="http://".$SERVER_NAME.$PHP_SELF;
  
  if (isset($sToogleThreadShow)) {
    if (!isset($sThreadOnly)) {
	  $sThreadOnly="X";
	  setcookie("sThreadOnly","X",(time()+1512000));
	}
	else {
	  if ($sThreadOnly=="X") {
	    $sThreadOnly="-";
	    setcookie("sThreadOnly","-",(time()+1512000));
	  }
	  else {
	    $sThreadOnly="X";
	    setcookie("sThreadOnly","X",(time()+1512000));
	  }
	}
	header ("location: $sUrl");
  }
  
  if (!isset($sThreadOnly)) {
    $bThreadOnly=false;
  }
  else {
    if ($sThreadOnly=="X") {
      $bThreadOnly=true;
	}
	else {
      $bThreadOnly=false;
	}
  }
  
  $sSubTitle="Archive Nr.&nbsp".$sLinkNo;
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  
  EchoArcMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc,$sFile,$sLinkNo);
  
  $MsgNo=0;
  $MsgDeep=0;
  $Db=NULL;
  $bFirst=true;
  
  $bFirst=true;
  $MsgDeep=0;
  $MsgNo=0;
  EchoArcive($sFile,$bFirst,$aPosts,$MsgDeep,$MsgNo,$bThreadOnly,
             $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
             $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sLinkNo);
			
  $aArchive=BuildArchiveList();
  EchoArchiveLinks($aArchive);	
  			   
  EchoFooter();
?>