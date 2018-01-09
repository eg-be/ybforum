<?php
// Cache-Lebensdauer (in Minuten)
$dauer = 0.5; 
$exp_gmt = gmdate("D, d M Y H:i:s", time() + $dauer * 60) ." GMT";
//$mod_gmt = gmdate("D, d M Y H:i:0", getlastmod()) ." GMT";

header("Expires: " . $exp_gm);
//hzader("Last-Modified: " . $mod_gmt);
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
  while (list ($sKey, $sVal) = each ($_COOKIE)) {
    $$sKey=$sVal;
  }

  include ("cfg/config.php");
  include ("functions.php");
  include ("prechecks.php");
  include_once("chkloginmode.php");

  $iNo=intval($sNo);
  /*if ($EnablePersonalNewInfo=="X") {
    if (!isset($aC[$iNo])) {
	  $sCookieName="aC[$iNo]";
	  setcookie($sCookieName,"gelesen",5*time());
	}
  }*/
  
//  $sSubTitle="Eintrag lesen";
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  
  if ($DbRow=GetEntry($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$iNo)) {
    $sClose=$DbRow[12];
    EchoShowMenu($sNo,$sClose);				   
	
    $sAuthor=DecryptText($DbRow[2]);
    if (($RegsActive=="X") && (($DbRow[4]=="R") || ($DbRow[4]=="A"))) {
	  $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$DbRow[4],$RegColor,$AdminColor,$RegsSameCol);
	}
   
    if (strpos($DbRow[3],"@")) {
      $sEmail=$DbRow[3];
	  if ($PosterSpamProtect!="X") {
	    $Needle="@";
	    $NewNeedle="&#64;";
	    $sShowMail=KffReplace($sEmail,$Needle,$NewNeedle);
	    $sAuthor="<a href=\"mailto:$sShowMail\">".$sAuthor."</a>";
	  }
	  else {
	    $sPicMail="";
		$iLength=strlen($sEmail);
		for ($i=0;$i<$iLength;$i++) {
		  $sDir="alphanum/layout1/";
		  $sLetter=$sEmail[$i];
		  $sPicLetter=GetPicLetter($sDir,$sLetter);
		  $sPicMail.=$sPicLetter;
		}
	  }
	}
  
//    echo "<center><font size=+1><b>".DecryptText($DbRow[10])."</b></font></center><br><b>";
echo "<h1>".DecryptText($DbRow[10])."</h1>";
	echo "geschrieben von&nbsp;".$sAuthor."&nbsp;".$sPicMail."&nbsp;am&nbsp;".$DbRow[5]."&nbsp;um&nbsp;".$DbRow[6]."</b>";
	
	if ($DbRow[1]!=0) {
	  if ($DbRow2=GetEntry($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRow[1])) {
	    $sPreNo=strval($DbRow[1]);
		$sUrl="showentry.php?sNo=".$sPreNo;
		
		$sAuthor=$DbRow2[2];
		
        if (($RegsActive=="X") && (($DbRow2[4]=="R") || ($DbRow2[4]=="A"))) {
		  $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$DbRow2[4],$RegColor,$AdminColor,$RegsSameCol);
		  $sAuthor=DecryptText($sAuthor);
	    }  
		echo "&nbsp;-&nbsp;als Antwort auf:&nbsp;"."<a href=\"$sUrl\"><b>".DecryptText($DbRow2[10])."</b></a>"."&nbsp;von&nbsp;".$sAuthor;
	  }
	}
	
	echo "<hr>";
	
	if ($PicLink=="X") {
	  if (strlen($DbRow[7])>2) {
	    $sPic=$DbRow[7];
	    echo "<center><img src=\"$sPic\" border=0></center><br>";
	  }
	}
	
	$bEchoNoText=true;
    $sText=DecryptText($DbRow[13]);
	$sText=Pseudo2toHtml($sText);
//	$sText=stripcslashes($sText);
        if (strlen($sText)>=1) {
          $sText=nl2br($sText);
		  $sText=str_replace("<br>","",$sText);
		  $sText=str_replace("<!-- AnfangLinie --><br />","",$sText);
		  $sText=str_replace("<!-- EndeLinie --><br />","",$sText);  
          echo $sText."<br>";
	  $bEchoNoText=false;          
        }
	
	if ($HomeLink=="X") {
	  if (strlen($DbRow[8])>2) {
	    $sUrl=$DbRow[8]; $sUrlName=$DbRow[9];
	    echo "<center><a href=\"$sUrl\" target=_blank>$sUrlName</a></center><br>";
	  }
	}
	
	if($bEchoNoText) {
	  echo "<center><font color=$InfoColor><b>Dieser Eintrag hat keinen Text!</b></font></center><br>";
	}
  }
  else {
    echo "<center><font color=$InfoColor><b>Dieser Eintrag hat keinen Text!</b></font></center><br>";
  }
  echo "<hr>";

  echo "<b>Antworten zu diesem Beitrag:</b><br>";
  
  $MsgNo=$iNo;
  $MsgDeep=0;
  $Db=NULL;
  $bFirst=true;
  $bThreadOnly=false;
  EchoForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbUsr,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive);

  EchoShowMenu($sNo,$sClose);				   
  EchoFooter();
?>
