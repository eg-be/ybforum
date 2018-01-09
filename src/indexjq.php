<?php

 ini_set('display_errors', 1);

// Cache-Lebensdauer (in Minuten)
$dauer = 0; 
$exp_gmt = gmdate("D, d M Y H:i:s", time() + $dauer * 60) ." GMT";

//header("Expires: " . $exp_gm);
header("Cache-Control: public, max-age=" . $dauer * 60);
// Speziell für MSIE 5
header("Cache-Control: pre-check=" . $dauer * 60, FALSE);

  while (list ($sKey, $sVal) = each ($_POST)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_GET)) {
    $$sKey=$sVal;
  }
  while (list ($sKey, $sVal) = each ($_SERVER)) {
    $$sKey=$sVal;
  }
  include ("cfg/config.php");
  include ("functions.php");
  include ("functionsjq.php");
  include ("prechecks.php");


  include_once("chkloginmode.php");
  
  $sUrl="http://".$SERVER_NAME.$PHP_SELF;

  if (isset($_GET['OrderThreadsByNewestsPost'])) {
    if (isset($_COOKIE['OrderThreadsByNewestsPost'])) {
	  if ($_COOKIE['OrderThreadsByNewestsPost']=="X") {
	    setcookie("OrderThreadsByNewestsPost","-",time()+1512000,"/");
		$OrderThreadsByNewestsPost="-";
	  }
	  else {
	    setcookie("OrderThreadsByNewestsPost","X",time()+1512000,"/");
		$OrderThreadsByNewestsPost="X";
	  }
	}
	else {
      setcookie("OrderThreadsByNewestsPost","X",time()+1512000,"/");
	  $OrderThreadsByNewestsPost="X";
	}
  }
  
  if (isset($_COOKIE['OrderThreadsByNewestsPost'])) {
    if ($_COOKIE['OrderThreadsByNewestsPost']=="X") {
 	  $OrderThreadsByNewestsPost="X";
	}
	else {
 	  $OrderThreadsByNewestsPost="-";
	}
  }
  
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
  
  
  
  
  

  
  include ("get_page.php");
  $sSubTitle="Startseite";
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  echo '  <script src="http://code.jquery.com/jquery-latest.js"></script>';
	
  EchoMainMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
  
  $MsgNo=0;
  $MsgDeep=0;
  $Db=NULL;
  $bFirst=true;
  ?>
  <div class="posting" style="float:left ; heigth: 100px; position:absolute; border:2px solid yellow; height: 100%">
Hallo
</div>
  <?
  EchoForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbUsr,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,"T",$iActPage,$iMaxPages);
 EchoForumJQ($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbUsr,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,"-",$iActPage,$iMaxPages);

 // $aArchive=BuildArchiveList();
  //EchoArchiveLinks($aArchive);	
?>
	
  
	
	<script>
	
	
	jQuery.fn.showcenter = function () {
	this.fadeIn(500);
	this.visible = true;
      return this;
}

	

//li with fade class
$("a.showentry").hover(function(){$("div.posting").showcenter()});
//$("a.showentry").hover(function(){$("div.posting").fadeOut(100);$("div.posting").fadeIn(500);});

</script>
	
	
	
	
	
	
	<?
  EchoFooter();
?>