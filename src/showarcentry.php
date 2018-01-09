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

  $iNo=intval($sNo);
  if ($EnablePersonalNewInfo=="X") {
    if (!isset($aC[$iNo])) {
	  $sCookieName="aC[$iNo]";
	  setcookie($sCookieName,"gelesen",5*time());
	}
  }
  
  $sSubTitle="Archiveintrag lesen";
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  EchoShowArcMenu($sNo,$sClose,$sFile,$sLinkNo);				   

  if ($aPosts=ReadPosts($sFile)) {
    $iAnz=sizeof($aPosts);
	for ($i=0;$i<$iAnz;$i++) {
	  if ($aPosts[$i][0]==$sNo) {
	    $sClose=$aPosts[$i][12];
        $sAuthor=$aPosts[$i][2];
        if (($RegsActive=="X") && (($aPosts[$i][4]=="R") || ($aPosts[$i][4]=="A"))) {
		  $Db=NULL;
	      $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$aPosts[$i][4],$RegColor,$AdminColor,$RegsSameCol);
	    }
        if (strpos($aPosts[$i][3],"@")) {
	      $sEmail=$aPosts[$i][3];
	  	  if ($PosterSpamProtect!="X") {
	        $Needle="@";
	        $NewNeedle="&#64;";
	        $sShowMail=KffReplace($sEmail,$Needle,$NewNeedle);
	        $sAuthor="<a href=\"mailto:$sEmail\">".$sAuthor."</a>";
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
        echo "<center><font size=+1><b>".$aPosts[$i][10]."</b></font></center><br><b>";
	    echo "geschrieben von&nbsp;".$sAuthor."&nbsp;".$sPicMail."&nbsp;am&nbsp;".$aPosts[$i][5]."&nbsp;um&nbsp;".$aPosts[$i][6]."</b>";
		
		if ($aPosts[$i][1]!=0) {
		  $sPreNo=$aPosts[$i][1];
		  $sUrl="showarcentry.php?sNo=$sPreNo&sFile=$sFile&sLinkNo=$sLinkNo";
		  for ($k=0;$k<$iAnz;$k++) {
		    if ($aPosts[$k][0]=="$sPreNo") {
			  $aOrig=$aPosts[$k];
			  break;
			}
		  }
		  $sAuthor=$aOrig[2];
          if (($RegsActive=="X") && (($aOrig[4]=="R") || ($aOrig[4]=="A"))) {
		    $Db=NULL;
		    $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$aOrig[4],$RegColor,$AdminColor,$RegsSameCol);
	      }  
		  echo "&nbsp;-&nbsp;als Antwort auf:&nbsp;"."<a href=\"$sUrl\"><b>".$aOrig[10]."</b></a>"."&nbsp;von&nbsp;".$sAuthor;
		}
	    echo "<hr>";
		if ($PicLink=="X") {
	      if (strlen($aPosts[$i][7])>2) {
	        $sPic=$aPosts[$i][7];
	        echo "<center><img src=\"$sPic\" border=0></center><br>";
	      }
		}
        $sText=ReadArcPostText($sFile,$sNo);
		$sText=Pseudo2toHtml($sText);
		$sText=stripcslashes($sText);
		echo $sText."<br>";	
		if ($HomeLink=="X") {
	      if (strlen($aPosts[$i][8])>2) {
	        $sUrl=$aPosts[$i][8]; $sUrlName=$aPosts[$i][9];
	        echo "<center><a href=\"$sUrl\" target=_blank>$sUrlName</a></center><br>";
	      }
		}
	    break;
	  }
	}
  }  
  
  echo "<hr>";

  echo "<b>Antworten zu diesem Beitrag:</b><br>";
  
  $MsgNo=intval($sNo);
  $MsgDeep=0;
  $bFirst=true;
  $bThreadOnly=false;
  
  EchoArcive($sFile,$bFirst,$aPosts,$MsgDeep,$MsgNo,$bThreadOnly,
             $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
             $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sLinkNo);

  EchoShowArcMenu($sNo,$sClose,$sFile,$sLinkNo);				   
  EchoFooter();
?>
