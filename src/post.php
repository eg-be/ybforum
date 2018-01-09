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
  include_once ("admfunc.php");
  include_once ("functions.php");
  include_once ("functions2.php");
  include ("prechecks.php");
  include ("smilies.php");

  include_once ("failloginstop.php");
  if (!isset($sAdminEdit)) {
    include_once("chkloginmode.php");
  }
  elseif ($sAdminEdit!="X") {
    include_once("chkloginmode.php");
  }
  elseif ($sAdminEdit=="X") {
    if (!CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.KffDirname($PHP_SELF)."login.php";
      header("location: login.php");
	  exit();
	}
  }
  
  if ((isset($sNoSrc)) && (!isset($sAdminEdit))) {
    if (!CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.KffDirname($PHP_SELF)."login.php";
      header("location: login.php");
	  exit();
	}
    $sAdminEdit="X";
    $iSrcNo=intval($sNoSrc);
	$sFile="data/".$sNoSrc.".txt";
	$DbRow=GetEntry($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$iSrcNo);
	$sAuthor=  DecryptText($DbRow[2]);
	$sEmail=   $DbRow[3];
	$sSubject= DecryptText($DbRow[10]);
	$sHomeurl= $DbRow[8];
	$sHomename=$DbRow[9];
	$sPicurl=  $DbRow[7];
	$sRegPost= $DbRow[4];
	$sText   = DecryptText($DbRow[13]);
	//$sText   = stripcslashes($sText);
	$sText=HtmlToPseudo($sText);
  }
	
  if (!isset($sAdminEdit)) {
    $sAdminEdit="-"; $sNoSrc="0";
  }
  
  if (isset($sLinkNo)) {
    $sSubTitle="Antwort zu Archivbeitrag - Archiv $sLinkNo, Beitrag $sNo";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
    EchoArcPostMenu($sArcFile,$sLinkNo);

	if( $sAction != "POST") { //// hinzugefügt, um einige Fehler zu beseitigen (u.a. wurde Betreff ignoriert)
		$aPosts=ReadPosts($sArcFile);
		$sArcNo=$sNo;
		$sNo="0";
		$iAnz=sizeof($aPosts);
		for ($i=0;$i<$iAnz;$i++) {
		  if ($aPosts[$i][0]==$sArcNo) {
			$aPost=$aPosts[$i];
			break;
		  }
		}
		if ($DisableAutoSubject!="X") {
		  if (substr($aPost[10],0,3)=="Re:") {
			$sSubject=DecryptText($aPost[10]);
		  }
		  else {
			$sSubject="Re:&nbsp;".DecryptText($aPost[10]);
		  }
		}

		if (($sAdminEdit=="-") && ($QuotePostOnAnswer=="X")) { 
			$sText=ReadArcPostText($sArcFile,$sArcNo);
		  
			$sText=Pseudo2toHtml($sText);
			
			$sText="[i]".stripcslashes($sText)."[/i]";
		} 
	} 

  }
  else {
    if ($sAdminEdit=="-") {
      $sSubTitle="Beitrag schreiben";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
      EchoPostMenu();
    }
    else {
      $sSubTitle="Beitrag bearbeiten";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
      EchoEditMenu($sSessid,$sUser,$sSipaddr);
    }
  }
  
  $iNo=intval($sNo);
  if ($sNo!=0) {
    if ($DbRow=GetEntry($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$iNo)) {
	  if ((($sAdminEdit=="-") && ($DbRow[12]=="X")) || ($aPost[12]=="X")) {
	    echo "<h3 align=center><font color=$ErrColor>Fehler: Der Thread ist geschlossen</font></h3>";
		echo EchoFooter();
		exit();
	  }
		
	  if (($sAdminEdit=="-") && ($QuotePostOnAnswer=="X") && (!isset($sText))) {
	    $sText = "[i]".DecryptText($DbRow[13])."[/i]";
	  	 
	  }
	
	  if (($sAdminEdit=="-") && (!isset($sSubject))){
	    if ($DisableAutoSubject!="X") {
	      if (substr($DbRow[10],0,3)=="Re:") {
		    $sSubject=DecryptText($DbRow[10]);
		  }
		  else {
            $sSubject="Re:&nbsp;".DecryptText($DbRow[10]);
		  }
		}
	  }
    }
  }
 
  if (!isset($sAction)) {
    $sAction="INI";
	if ($sAdminEdit=="-") {
	  $sAuthor="";
      $sEmail="";
	  if (!isset($sText)) {
	    $sText="";
	  }
	  $sHomeurl="http://";
	  $sHomename="";
	  $sPicurl="";
	  if (!isset($sSubject)) {
	    $sSubject="";
	  }
	  $sRegName="";
	  $sRegPass="";
	}
  }
  
  switch ($sAction) {
    case "POST":	  
	  if (isset($sPreButton)) {
	    echo "<h3 align=center>Vorschau</h3>";
	    
	    $sPreText=KffStripTags($sText);
	    $sPreText=nl2br(PseudoToHtml($sPreText));
		$sPreText=Pseudo2ToHtml($sPreText);
		$sPreText=stripslashes($sPreText);
		
		if (strlen($sPicurl)>1) {
		  if (@getimagesize($sPicurl)) {
		    echo "<center><img src=\"$sPicurl\" border=0></center><br>";
		  }
		}
		echo $sPreText."<br>";
		if ((strlen($sHomename)>1) && (strlen($sHomeurl))>1) {
  		  echo "<center><a href=\"$sHomeurl\" target=_blank>$sHomename</a></center><br>";
		}
		echo "<hr>";
		break;
	  }
	  	  
	  $bPost=true;
	  $bReg=false;
	  $sReg = "-";
	  
	  // Diese IF überträgt den Namen nötigenfalls in die Stammpostervariable
      if (($sAdminEdit=="-") && ($RegsActive=="X") && ($LoginRequired!="X")&& (strlen($sRegPass)>=1)) {
	    $sRegName=$sAuthor; $sAuthor="";
	  }
	  
	  if ($sAdminEdit=="-") {
	    if ($RegsActive=="X") {
		  $Db=NULL;
		  if ($LoginRequired=="X") {
		    $sRegName=$sName;
		  }
		  if ($DbRow=GetRegUsr($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sRegName)) {
		    if ($DbRow[7]<$MaxLoginFails) {
 		      if (($DbRow[1]==md5($sRegPass)) || ($LoginRequired=="X")) {
			    $sAuthor=$DbRow[0];
			    $sReg="R";
			    $bReg=true;
				$sQuery="update $DbReg set miscnt=0 where name='$sRegName'";
				$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
			  }
			  else {
			    $iNewMiscnt=$DbRow[7]+1;
				$sQuery="update $DbReg set miscnt=$iNewMiscnt where name='$sRegName'";
				$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
			    echo "<center><font color=$ErrColor><b>Fehler:</b> Unbekannter Stammposter (Name und/oder Passwort falsch)</font></center><br>";
			    $bPost=false;
                $Db=NULL; SetLoginLock($Db,$DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$LockTimeFail,$REMOTE_ADDR);
				flush();
				sleep(intval($LockTimeFail));
			  }
			}
			else {
			  echo "<center><font color=$ErrColor><b>Fehler:</b> Stammposter wegen zu vieler Fehleingaben gesperrt</font></center><br>";
			  $bPost=false;
			}
		  }
		  elseif ($RegsOnly=="-") {
	        echo "<center><font color=$ErrColor><b>Fehler:</b> Es dürfen nur Stammposter schreiben</font></center><br>";
		    $bPost=false;
		  }
		  else {
	        if (strlen($sAuthor)<2) {
	          echo "<center><font color=$ErrColor><b>Fehler:</b> Kein Name</font></center><br>";
		      $bPost=false;
	        }
		  }
		}
		else {
	      if (strlen($sAuthor)<2) {
	        echo "<center><font color=$ErrColor><b>Fehler:</b> Kein Name</font></center><br>";
		    $bPost=false;
	      }
		}
	  }

  	  $sAuthor=KffStripTags($sAuthor);
	  if ($bPost) {
        if (strlen($sAuthor)<2) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Kein Name</font></center><br>";
	      $bPost=false;
        }
	  }
	  	  
	  if ($bPost) {
	    if ($sEmail!="") {
		  if (!CheckEmail($sEmail)) {
	        echo "<center><font color=$ErrColor><b>Fehler:</b> Ung&uuml;ltige Emailadresse</font></center><br>";
		    $bPost=false;
		  }
		}
	  }

	  if ($bPost) {
  	    if (!CheckIP($REMOTE_ADDR)) {
	      echo "<center><font color=$ErrColor><b>Fehler:</b> Ung&uuml;ltige IP-Adresse</font></center><br>";
		  $bPost=false;
		}
	  }
	  
  	  $sSubject=KffStripTags($sSubject);
	  if ($bPost) {
	    if (strlen($sSubject)<intval($MinSubjectLen)) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Ein Betreff in diesem Forum muss mindestens $MinSubjectLen Zeichen haben!</font></center><br>";
	      $bPost=false;
		}
	  }
	  
  	  $sText=KffStripTags($sText);
	  $sTmpText=$sText;
	  $sTmpText=RemovePseudoTags($sTmpText);
	  if ($bPost) {
	    if ((intval($MinPostLen)==0) && (strlen($sTmpText)<1)){
		  if (substr($sSubject,(strlen($sSubject)-strlen($EmptyPostExt)),strlen($EmptyPostExt))!=$EmptyPostExt) {
		    $sSubject=$sSubject."&nbsp;".$EmptyPostExt;
		  }
		}
	    elseif (strlen($sTmpText)<intval($MinPostLen)) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Ein Text in diesem Forum muss mindestens $MinPostLen Zeichen haben!</font></center><br>";
	      $bPost=false;
		}
	  }
	  
  	  $sHomename=KffStripTags($sHomename);
	  if ($bPost) {
	    if ((($sHomeurl!="") && (strtolower($sHomeurl!="http://")))&& ($sHomename=="")){
          echo "<center><font color=$ErrColor><b>Fehler:</b> Kein Homepagename</font></center><br>";
	      $bPost=false;
		}
	  }

      if (($bPost) && (!$bReg) && ($sAdminEdit!="X")) {
	    if ((IsBadword($sAuthor)) ||
		    (IsBadword($sEmail)) ||
			(IsBadword($sSubject)) ||
			(IsBadword($sText)) ||
			(IsBadword($sHomename)) ||
			(IsBadword($sHomeurl)) ||
			(IsBadword($sPicurl)) ||
                        (IsBadword($REMOTE_ADDR))) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Unerlaubtes Wort in Ihrem Beitrag</font></center><br>";
	      $bPost=false;
		}
	  }
	  
	  if ($bPost) {
	    $sTmp="#".strtolower($sHomeurl);
		if (strpos($sTmp,"?")) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Scripte mit Parametern sind als Links nicht erlaubt!</font></center><br>";
	      $bPost=false;
		}
	  }
	  
	  if ($bPost) {
	   
		if (strpos("#".$sHomeurl,"http://")!=1) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> URL Links müssen mit http:// beginnen!</font></center><br>";
	      $bPost=false;
		}
	  }
	  
	  if ($bPost) {
	    $sTmp="#".strtolower($sPicurl);
		if ((strpos($sTmp,"?")) || (strpos($sTmp,".php")) || (strpos($sTmp,".cgi"))  || (strpos($sTmp,".asp")) || (strpos($sTmp,".jsp"))) {
          echo "<center><font color=$ErrColor><b>Fehler:</b> Sripte sind als Bildlinks nicht erlaubt!</font></center><br>";
	      $bPost=false;
		}
	  }
	  
	  if ($bPost) {
	    if ($sAdminEdit!="X") {
		  $iLockTime=intval($LockTime);
		  $iMaxUses =intval($MaxUses);
		  if (($iLockTime!=0) && ($iMaxUses!=0)) {
	        if(!CheckLockSet ($DbHost,$DbName,$DbUser,$DbPass,$DbIpl,$iLockTime,$iMaxUses,$sRemoteAddr)) {
              echo "<center><font color=$ErrColor><b>Fehler:</b> In diesem Forum sind nur $iMaxUses Postings in $iLockTime Sekunden erlaubt!</font></center><br>";
	          $bPost=false;
		    }
		  }
	    }
	  }
	  
	  if ($bPost) {
	    if (isset($sLinkNo)) {
		  $sSubject="Arc $sLinkNo, No $sArcNo - ".$sSubject;
		  if (strlen($sSubject)>50) {
		    $sSubject=substr($sSubject,0,50);
		  }
		}
		$sText=$sText;
		if (strtolower($sHomeurl)=="http://") {$sHomeurl="";}
	    WritePosting($DbHost,$DbName,$DbUser,$DbPass,$DbTab,
		             $sAuthor,$sEmail,$sText,$sHomeurl,$sHomename,$sPicurl,$sSubject,
				     $sNo,$sNoSrc,$sReg,
					 $REMOTE_ADDR,$REMOTE_PORT,$HTTP_USER_AGENT);
        if (($sAdminEdit!="X") && (intval($sNo)!=0)) {
		  NotifyRegs($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbReg,$TabPrf,$sNo,$Title,$SERVER_NAME,$PHP_SELF,$AllowEmailNote);
		}
	    $sAction="DONE";
		if ($sAdminEdit=="X") {
		  echo "<center><font color=$InfoColor><b>Der Beitrag wurde ge&auml;ndert.</b></font></center><br>";
          if ($EnablePersonalNewInfo=="X") {
		    $iHelp=intval($sNoSrc);
            if (isset($aC[$iHelp])) {
	          $sCookieName="aC[$iHelp]";
	          setcookie($sCookieName,"gelesen",time()-3600);
	        }
          }
		}
		elseif ((($ModerateRegulars=="X") && ($sReg=="R")) || (($ModerateGuests=="X") && ($sReg!="R") && ($sReg!="A"))) {
		  echo "<center><font color=$InfoColor><b>Vielen Dank! Ihr Beitrag wird nach einer &Uuml;berpr&uuml;fung von der Forenadministration freigeschaltet.</b></font></center><br>";
		}
		else {
		  echo "<center><font color=$InfoColor><b>Vielen Dank! Dein Beitrag wurde aufgenommen.</b><br><div><font size+1>Zur Verbesserung der Geschwindigkeit des Forums werden die Seiten nicht sofort aktualisiert. Es kann daher bis zu einer Minute dauern, bis dein Posting überall sichtbar ist!</font></div></font></center><br>";
	    }
	  }
	  
	  break;
  }
  
  if ($sAction!="DONE") {
  ?>
  <form action="post.php" name=frmPost method=post>
  <input type=hidden name=sNo value="<?php echo $sNo;?>">
  <input type=hidden name=sAction value="POST">
  <input type=hidden name=sAdminEdit value="<?php echo $sAdminEdit;?>">
  <input type=hidden name=sNoSrc value="<?php echo $sNoSrc;?>">
  <?php if (isset($sSessid)) {EchoHiddenSessionP($sSessid,$sUser,$sSipaddr);}
  if ($sAdminEdit=="X") {
    ?>
    <input type=hidden name=sAuthor value="<?php echo $sAuthor;?>">
    <?php 
  }
  if (isset($sLinkNo)) {
    ?>
	<input type=hidden name=sLinkNo value="<?php echo $sLinkNo;?>">
	<input type=hidden name=sArcFile value="<?php echo $sArcFile;?>">
	<input type=hidden name=sArcNo value="<?php echo $sArcNo;?>">
	
	<?php 
  }
  ?>
  
  <center><table border=0 cellspacing=0 cellpadding=0>
  <?php 
  if ($sAdminEdit=="-") {
	if ($LoginRequired=="X") {
	  ?>
      <td align=left valign=top><b>Name:&nbsp;</b></td>
      <td align=left valign=top><b><?php echo $sName;?></b></td>
	  <?php 
	}
	else {// ($RegsOnly!="-") {
      ?>
      <tr>
      <td align=left valign=top><b>Name&nbsp;</b>(<a href="register.php" target=_blank>Stammposterregistrierung</a>):&nbsp;</td>
      <td align=left valign=top><input type=text name=sAuthor size=20 maxlength=60 value="<?php echo $sAuthor;?>"></td>
      </tr>
	  <?php 
	}
  }
  else {
    ?>
    <tr>
    <td align=left valign=top><b>Name:&nbsp;</b></td>
    <td align=left valign=top><?php echo stripslashes($sAuthor);?></td>
    </tr>
    <?php 
  }
  
  if (($sAdminEdit=="-") && ($RegsActive=="X") && ($LoginRequired!="X")) {
    ?>
    <tr>
    <td align=left valign=top><b>Stammposterpasswort:&nbsp;</b></td>
    <td align=left valign=top><input type=password name=sRegPass  size=20 maxlength=20 value="<?php echo $sRegPass;?>"></td>
    </tr>  
	<?php 
  }
  ?>
  
  <tr>
  <td align=left valign=top><b>Emailadresse</b> (freiwillig):&nbsp;</td>
  <td align=left valign=top><input type=text name=sEmail size=30 maxlength=75 value="<?php echo $sEmail;?>"></td>
  </tr>
  
  <tr>
  <td align=left valign=top><b>Betreff:&nbsp;</b></td>
  <td align=left valign=top><input type=text name=sSubject size=50 maxlength=100 value="<?php echo stripslashes($sSubject);?>"></td>
  </tr>
  
  <tr>
  <td align=left valign=top colspan=2>Textformatierung: <a href="javascript: AddTag('[b][/b]');"><img src="graphics/bold.gif" width="17" height="15" border="0" alt="fett"></a>&nbsp;<a href="javascript: AddTag('[i][/i]');"><img src="graphics/italic.gif" width="17" height="15" border="0" alt="kursiv"></a>&nbsp;<a href="javascript: AddTag('[u][/u]');"><img src="graphics/underline.gif" width="17" height="15" border="0" alt="unterstrichen"></a></td>
  </tr>
  
  <tr>
  <td colspan=2 align=left valign=top><textarea name=sText cols=85 rows=10><?php echo stripslashes($sText);?></textarea></td>
  </tr>

  <?php 
  if (($HomeLink=="X") || ($sAdminEdit=="X")) {
    ?>
    <tr>
    <td align=left valign=top><b>URL Link</b> (freiwillig):&nbsp;</td>
    <td align=left valign=top><input type=text name=sHomeurl  size=50 maxlength=250 value="<?php echo $sHomeurl;?>"></td>
    </tr>
  
    <tr>
    <td align=left valign=top><b>URL Link Text</b> (ohne erscheint URL nicht!):&nbsp;</td>
    <td align=left valign=top><input type=text name=sHomename  size=20 maxlength=100 value="<?php echo $sHomename;?>"></td>
    </tr>
    <?php 
  }
    
  if (($PicLink=="X") || ($sAdminEdit=="X")) {
    ?>
    <tr>
    <td align=left valign=top><b>URL eines Bildes</b> (freiwillig):&nbsp;</td>
    <td align=left valign=top><input type=text name=sPicurl  size=50 maxlength=100 value="<?php echo $sPicurl;?>"></td>
    </tr>
	<?php
  }
  ?>
  
  <tr>
  <td align=center align=left colspan=2><input type=submit name=sPostButton value="Eintrag senden">&nbsp;&nbsp;<input type=submit name=sPreButton value="Vorschau">&nbsp;&nbsp;<input type=reset value="Eintrag l&ouml;schen"></td>
  </tr>

  <?php 
  if ($Smilies=="X") {
    ?>
	<tr>
	<td colspan=2 align=left valign=top><b>Tags f&uuml;r Smilies</b></td>
	</tr>
    <tr>
    <td colspan=2 align=left valign=top>
	<?php
	echo "<table border=0 cellspacing=0 cellpadding=2>";
	$iAnz=sizeof($aSmilies);
	$iCount=0;
	for ($i=0;$i<$iAnz;$i++) {
	  if ($iCount==0) { echo "<tr>"; }
	   if ($aSmilies[$i][2]!="")
		$sSmiliePic=$aSmilies[$i][2];
	    else
       	      $sSmiliePic=$aSmilies[$i][1];
	  $sSmilieTag=$aSmilies[$i][0];
	  
	  $sJavaScriptLink="javascript: AddTag('$sSmilieTag');";
	  echo "<td align=left valign=top><a href=\"$sJavaScriptLink\">$sSmiliePic</a></td>";
	  echo "<td align=left valign=top><a href=\"$sJavaScriptLink\">$sSmilieTag</a></td>";
	  echo "<td>&nbsp;</td>";
	  $iCount++;
	  if ($iCount==3) {$iCount=0; echo "</tr>";}
	  }
	}

    while ($iCount!=0) {
	  echo "<td colspan=3 align=left valign=top>&nbsp;</td>";
	  $iCount++;
	  if ($iCount==3) {$iCount=0; echo "</tr>";}
	}
	
	echo "</table>";
	?>
	</td>
    </tr>
	<?php 
  }
  ?>
  
  </table></center>
  </form>
  <?php
  
  EchoFooter(); 
  
function EchoHiddenSessionP($sSessid,$sUser,$sSipaddr) {
  ?>
  <input type=hidden name=sSessid  value="<?php echo $sSessid;?>">
  <input type=hidden name=sUser    value="<?php echo $sUser;?>">
  <input type=hidden name=sSipaddr value="<?php echo $sSipaddr;?>">
  <?php 
}   

function NotifyRegs($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbReg,$TabPrf,$sNo,$sTitle,$sServer,$sPhpSelf,$AllowEmailNote,$bFirst=true,$aNotifyReg=false,$aDone=false) {

  if ($AllowEmailNote!="X") {
    return;
  }

  $iNo=intval($sNo);
  if ($iNo==0) {return;}

  if ($bFirst) {
    $sQuery="select * from $DbReg where state='A'";
    $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
    $iCount=0; $aNotifyReg=array(); $aDone=array();
    while ($DbRow=mysql_fetch_row($DbQuery)) {
      $sRegName=$DbRow[0];
  	  $sQuery="select * from $TabPrf where name='$sRegName' and kznotifyans='X'";
	  $Db=NULL; $DbQuery2=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  if ($DbRow2=mysql_fetch_row($DbQuery2)) {
	    $aNotifyReg[$iCount]=$DbRow;
		$aDone[$iCount]     ="-";
	    $iCount++;
	  }
    }
  }
  
  $sQuery="select * from $DbTab where no=$iNo";
  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    if ($DbRow[4]=="R") {
	  $iAnz=sizeof($aNotifyReg);
	  for ($i=0;$i<$iAnz;$i++) {
	    if (($aNotifyReg[$i][0]==$DbRow[2]) && ($aDone[$i]!="X")) {
		  $aDone[$i]="X";
		  $sSubject ="$sTitle - Neue Antwort";
		  
		  $sMsgSubject=$DbRow[10];
	      $iForumPos=strrpos($sPhpSelf,"/");
          $sForum=substr($sPhpSelf,0,$iForumPos)."/";
		  $sSrcNo=strval($DbRow[0]);
		  
		  $sMessage ="Zur Ihrem Beitrag - $sMsgSubject - gibt es ein neue Antwort:\n\n";
		  $sMessage.="http://$sServer".$sForum."showentry.php?sNo=$sSrcNo";
          $sHeader="From: \"Forum $sTitle\" <Keine-Antwortadresse@>";
	      mail($aNotifyReg[$i][2],$sSubject,$sMessage,$sHeader);
		}
	  }
	}
	$sSubNo=strval($DbRow[1]);
	$bSubFirst=false;
	NotifyRegs($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbReg,$TabPrf,$sSubNo,$sTitle,$sServer,$sPhpSelf,$AllowEmailNote,$bSubFirst,$aNotifyReg,$aDone);
  }
}
?>
