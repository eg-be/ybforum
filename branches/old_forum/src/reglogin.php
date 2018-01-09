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
  include_once ("functions2.php");
  include ("prechecks.php");

  include_once ("failloginstop.php");

if ($LoginRequired=="X") {
  if (CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
    $iTime=time()+$MaxLoginTime;
    setcookie("sSessidReg",$sSessidReg,$iTime);
    setcookie("sName",$sName,$iTime);
  }
}
  
if (!isset($sAction)) {
  if ($LoginRequired=="X") {
    if (CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
      $iTime=time()+$MaxLoginTime;
      setcookie("sSessidReg",$sSessidReg,$iTime);
      setcookie("sName",$sName,$iTime);
	  $sAction="MENU";
    }
	else {
      $sAction="INI";
	}
  }
  else {
    $sAction="INI";
  }
}

switch ($sAction) {
  case "DOADMMAIL":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	$sSubTitle="<font color=$InfoColor>Email an die Administratoren wurde verschickt</font>";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);	
	
	$Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass);
	$sQuery="select * from $DbReg where name='$sName'";
	$DbQuery1=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	$DbRow1=mysql_fetch_row($DbQuery1); $sRegEmail=$DbRow1[2];
    $sQuery="select * from $DbAdm where kzactiv='X' and level<>'1'";
	$DbQuery2=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	mysql_close($Db);
	$sHeader="From: \"Stammposter $sName\" <$sRegEmail>";
	while ($DbRow2=mysql_fetch_row($DbQuery2)) {
	  mail($DbRow2[2],$sAdmSubject,$sAdmMail,$sHeader);
	}
	
    echo "<br><center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";	
	EchoFooter();
    break;
  case "ADMMAIL":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	$sSubTitle="Email an die Administratoren";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	echo "<p align=justify>Sie k&ouml;nnen hier eine einfache Textemail an die Administratoren des Forums schreiben, 
	wenn Sie ein Anliegen das Forum betreffend haben, dass Sie aber nicht &ouml;ffentlich im Forum darstellen wollen. 
	Es wird empfohlen, diese Funktion zur&uuml;ckhaltend und nur in wichtigen F&auml;llen zu nutzen, da die 
	Administratoren, falls Sie sich bel&auml;stigt f&uuml;hlen w&uuml;rden, Ihren Zugang sperren k&ouml;nnten.</p>";
    ?>	
    <form action="reglogin.php" method=post>
	<input type=hidden name=sAction value="DOADMMAIL">
    <input type=hidden name=sName value="<?php echo $sName;?>">
    <input type=hidden name=sSessidReg value="<?php echo $sSessidReg;?>">
	<center><table border=0 cellspacing=0 cellpadding=3>
	<tr>
	<td align=left valign=top><b>Betreff:</b></td>
	<td align=left valign=top><input type=text name=sAdmSubject size=90 maxlength=100></td>
	</tr>

	<td align=left colspan=2 valign=top><textarea name=sAdmMail cols=85 rows=10></textarea></td>
	  
	<tr>
	<td align=center colspan=2 valign=top><input type=submit value="Email senden"></td>
	</tr>
	</table></center>
	</form>
	<?php
    echo "<br><center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";	
	EchoFooter();
    break;
	 
   case "DOCHGSET":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	if (!isset($sKznotifyans)) {$sKznotifyans="-";}
	if (!isset($sKzactive)) {$sKzactive="-";}
	if ($sKznotifyans!='X'){$sKznotifyans="-";}
	if ($sKzactive!='X'){$sKzactive="-";}
	$sQuery="update $TabPrf set kzactive   ='$sKzactive',
	                            kznotifyans='$sKznotifyans'
	                        where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	$sSubTitle="Einstellungen von $sName gespeichert";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
    echo "<br><center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";
	EchoFooter();
    break;
	
  case "CHGSET":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	$sQuery="select * from $TabPrf where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  $sKznotifyans=$DbRow[11];
	  $sKzactive=$DbRow[8];
	}
	
	$sSubTitle="Einstellungen von $sName";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	
	echo "<form action=\"reglogin.php\" method=post>";
    echo "<input type=hidden name=sAction value=\"DOCHGSET\">";
    echo "<input type=hidden name=sName value=\"$sName\">";
    echo "<input type=hidden name=sSessidReg value=\"$sSessidReg\">";
	echo "<center><table border=1 cellspacing=0 cellpadding=2>";
	echo "<tr><td colspan=2 align=center valign=top><b><font size=+1>Funktionen</font></b></td></tr>";
	echo "<tr><td valign=top align=left><b>Emailbenachrichtigung bei Antworten zu Ihren Beitr&auml;gen:</b>";
	if ($AllowEmailNote!='X') {
	  echo "<br>Diese Funktion ist momentan durch den Forenadministrator deaktiviert.";
	}
	echo "</td>";
	?>
	<td valign=top align=left><input type=checkbox name=sKznotifyans value="X" <?php if ($sKznotifyans=="X") {echo "checked";}?>></td></tr>
	
	<?php 
	
	echo "<tr><td colspan=2 align=center valign=top><input type=submit value=\"Speichern\"</td></tr>";
	echo "</table></center>";
	echo "</form>";
	
    echo "<br><center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";
	
	EchoFooter();
    break;
  
  case "DOCHGLOG":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	$bProceed=true; $sSubTitle="Sorry $sName";
	if ($bProceed) {
	  if ($sPasswd!=$sPass2) {
	    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	    echo "<center><font color=$ErrColor><b>Fehler: </b>Passwort und -wiederholung nicht identisch</font></center>";
	    $bProceed=false;
	  }
	  elseif (strlen($sPasswd)>=1) {
	    if (strlen($sPasswd)<6) {
		  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Passwort muss mindestens 6 Zeichen lang sein</font></center>";
	      $bProceed=false;
		}
		else {
		  $sPasswd=md5($sPasswd);
		}
	  }
	  else {
	    $sPasswd=$sOldPasswd;
	  }
	}
	if ($bProceed) {
	  if (strlen($sEmail)>1) {
   	    if (!CheckEmail($sEmail)) {
		  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ung&uuml;ltige Emailadresse</font></center>";
	      $bProceed=false;
	    }
	  }
	  else {
	    $sEmail=$sOldEmail;
	  }
	}
	if ($bProceed) {
	  if (strlen($sColor)>1) {
  	    if ((!CheckColor($sColor)) || (strlen($sColor)<6)) {
		  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ung&uuml;ltige Farbe (Bitte Hex-RGB)</font></center>";
	      $bProceed=false;
	    }
	  }
	  else {
	    $sColor=$sOldColor;
	  }
	}
	if (!$bProceed) {
  	  echo "<br><center><a href=\"reglogin.php?sAction=CHGLOG&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";
	  EchoFooter();
	  break;
	}
	$sNewState='A';
	if ($sEmail!=$sOldEmail) {	
	  $sNewState='4';
      $iFreetime=time()+604800;
	  $sFreecode=crypt($sEmail);
	  $sFreecode=substr($sFreecode,(strlen($sFreecode)-6),6);
	}
	else {
      $iFreetime=time();
      $sFreecode='------';
	}
	$sQuery="update $DbReg set passwd='$sPasswd',
	                           email ='$sEmail',
							   color ='$sColor',
							   freecode='$sFreecode',
							   freetime=$iFreetime,
							   state ='$sNewState'
						   where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	if ($sNewState=="A") {
	  $sSubTitle="Änderungen wurden gespeichert";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  	  echo "<center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";
	  EchoFooter();
	}
	else {
	  $iForumPos=strrpos($PHP_SELF,"/");
      $sForum=substr($PHP_SELF,0,$iForumPos)."/";
	  
	  $sQuery="update $TabPrf set sessvalid='-' where name='$sName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $sSubTitle="Änderungen wurden gespeichert";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	  $sSubject=$Title." - Bestätigung Ihrer Emailadresse";
	  $sMessage ="Vielen Dank $sName!\n\n";
	  $sMessage.="Sie haben sich im Forum $Title als Stammposter registriert und Ihre Emailadresse geändert. ";
	  $sMessage.="Bitte bestätigen Sie nun diese Änderung, indem Sie innerhalb von 7 Tagen den folgenden Link besuchen:\n\n";
	  $sMessage.="http://$SERVER_NAME"."$sForum"."register.php?sAction=CONFIRM&sFreecode=$sFreecode&sName=$sName\n\n";
	  $sMessage.="Sollten Sie den Link nicht besuchen, verfällt Ihre Registrierung. Wenn Sie Ihre Registrierung ";
	  $sMessage.="bestätigen, können Sie sofort wieder als Stammposter agieren.\n\n";
	  $sMessage.="Mit freundlichen Grüßen\n";
	  $sMessage.="Der Forenadministrator\n\n";
	  $sMessage.="http://$SERVER_NAME.$sForum";
	  if (CheckEmail($SenderMail)) {
        $sHeader="From: \"Forum $Title\" <$SenderMail>";
	  }
	  else {
        $sHeader="From: \"Forum $Title\" <Keine-Antwortadresse@>";
	  }
	  mail($sEmail,$sSubject,$sMessage,$sHeader);
	  ?>
	  <p align=justify>Da Sie Ihre Emailadresse ge&auml;ndert haben, wurden Sie ausgeloggt. An Ihre neue Emailadresse 
	  wurde ein neuer Best&auml;tigungslink gesendet, den Sie innerhalb von 7 Tagen besuchen m&uuml;ssen. Wenn Sie 
	  durch Besuch des Links Ihre neue Emailadresse best&auml;tig haben, k&ouml;nnen Sie sofort wieder als Stammposter 
	  agieren. Ein Begutachtung durch den Forenadministrator wie bei Ihrer Erstregistrierung. entf&auml;llt.</p>
	  <?php 
	  EchoFooter();
	}
    break;
	
  case "CHGLOG":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	$sQuery="select * from $DbReg where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  $sOldPasswd=$DbRow[1];
	  $sEmail=$DbRow[2];
	  $sOldEmail=$sEmail;
	  $sColor=$DbRow[3];
	  $sOldColor=$sColor;
	}
    $sSubTitle="Stammposter Zugangsdaten f&uuml;r $sName &auml;ndern";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	echo "<center><b>Einstellungen, die sich nicht &auml;ndern wollen, bitte in den Eingabefeldern nicht &auml;ndern oder die Eingafelder leer lassen.</b></center>";
    echo "<form action=\"reglogin.php\" method=post>";
    echo "<input type=hidden name=sAction value=\"DOCHGLOG\">";
    echo "<input type=hidden name=sName value=\"$sName\">";
    echo "<input type=hidden name=sOldPasswd value=\"$sOldPasswd\">";
    echo "<input type=hidden name=sOldEmail value=\"$sOldEmail\">";
    echo "<input type=hidden name=sOldColor value=\"$sOldColor\">";
    echo "<input type=hidden name=sSessidReg value=\"$sSessidReg\">";
	echo "<center><table border=0 cellspacing=0 cellpadding=2>";
	echo "<tr>";
	echo "<td align=left valign=top><b>Neues Password:</b></td>";
	echo "<td align=left valign=top><input type=password name=sPasswd size=20 maxlength=20></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td align=left valign=top><b>Wiederholung Password:</b></td>";
	echo "<td align=left valign=top><input type=password name=sPass2 size=20 maxlength=20></td>";
	echo "</tr>";	
	
	echo "<tr>";
	echo "<td align=left valign=top><b>Emailadresse: <sup>1)</sup></b></td>";
	echo "<td align=left valign=top><input type=text name=sEmail size=30 maxlength=30 value=\"$sEmail\"></td>";
	echo "</tr>";	
	
	echo "<tr>";
	echo "<td align=left valign=top><b>Farbe:</b></td>";
	echo "<td align=left valign=top><input type=text name=sColor size=6 maxlength=6 value=\"$sColor\"></td>";
	echo "</tr>";		
	
	echo "<tr>";
	echo "<td colspan=2 align=left valign=top><input type=submit value=\"&Auml;nderungen speichern\"></td>";
	echo "</tr>";
	
	echo "</table></center>";
	echo "<p align=justify><font size=-1><sup>1)</sup> Wenn Sie Ihre Emailadresse &auml;ndern, wird Ihnen wieder ein 
	Best&auml;tigungslink an diese neue Emailadress gesendet, den Sie innerhalb von 7 Tagen besuchen 
	m&uuml;ssen. Wenn Sie den Link nicht besuchen, wird Ihre Stammposterregistrierung unwiderbringbar
	deaktiviert. Vor einem Besuch des Best&auml;tigungslink k&ouml;nnen Sie Ihre Stammposterregistrierung 
	leider nicht mehr verwenden. Wenn Sie Ihre neuer Emailadresse best&auml;tigt haben, sind Sie wieder 
	freigeschaltet, eine Begutachtung durch den Forenadministrator wie bei Ihrer Erstregistrierung steht dann 
	nicht mehr an.</font></p>";
	echo "</form>";
	
	echo "<center><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\"><b>Zur&uuml;ck</b></a></center>";
	
	EchoFooter();
    break;

  case "DODELETE":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
	// Raus aus den PMS-Tabellen
	$sQuery="delete from $TabPrf where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	$sQuery="delete from $TabBlk where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
//	$sQuery="delete from $TabMsg where fromname='$sName'";
//	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	$sQuery="delete from $TabRcv where toname='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);	
	// Und deaktivieren als Stammposter
	$sQuery="update $DbReg set state='3' where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);	
	
    $sSubTitle="Bye Stammposter $sName!";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	echo "<center><font color=$InfoColor><b>Ihre Registrierung wurde dauerhaft deaktiviert.</b></font></center>";
    EchoFooter();
    break;

  case "DELETE":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
    $sSubTitle="Stammposter Registrierung l&ouml;schen";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	echo "<center><b><font color=$ErrColor>Achtung $sName:</font> <font color=$InfoColor>Wollen Sie Ihre Registrierung wirklich l&ouml;schen?</font></b><br><br>";
	echo "Ihr Account wird deaktiviert und Sie k&ouml;nnen Sich dann unter gleichem Namen nicht mehr wieder registrieren.<br><br>";
	echo "<b><font size=+1><a href=\"reglogin.php?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName\">Nein</a>&nbsp;&nbsp;&nbsp;<a href=\"reglogin.php?sAction=DODELETE&sSessidReg=$sSessidReg&sName=$sName\">Ja, wirklich l&ouml;schen</a></font></b></center>";
    EchoFooter();
    break;

  case "LOGOUT":
    if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	  header("location: $sUrl");
    }
    $iTime=time()-(2*$MaxLoginTime);
    setcookie("sSessidReg",$sSessidReg,$iTime);
    setcookie("sName",$sName,$iTime);
	$sQuery="update $TabPrf set sessvalid='-' where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
    $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
	header("location: $sUrl");
    break;
	
  case "MENU":
      if (!CheckRegSessionValid($sSessidReg,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime)) {
	    $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=INI";
		header("location: $sUrl");
	  }  
      $sSubTitle="Stammposter Bereich, hallo $sName";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	  echo "<center><table border=0 cellspacing=0 cellpadding=2>";
	  echo "<tr><td align=left valign=top><a href=\"reglogin.php?sAction=CHGSET&sName=$sName&sSessidReg=$sSessidReg\">Einstellungen &auml;ndern</td></tr>";
	  echo "<tr><td align=left valign=top><a href=\"reglogin.php?sAction=CHGLOG&sName=$sName&sSessidReg=$sSessidReg\">Zugangsdaten &auml;ndern</td></tr>";
	  echo "<tr><td align=left valign=top><a href=\"reglogin.php?sAction=ADMMAIL&sName=$sName&sSessidReg=$sSessidReg\">Email an die Administratoren</td></tr>";
	  echo "<tr><td align=left valign=top><a href=\"reglogin.php?sAction=DELETE&sName=$sName&sSessidReg=$sSessidReg\">Registrierung l&ouml;schen</td></tr>";
	  if ($LoginRequired=="X") {
	    echo "<tr><td align=left valign=top><a href=\"index.php\"><b>Forum</b></td></tr>";
	  }
	  echo "<tr><td align=left valign=top><a href=\"reglogin.php?sAction=LOGOUT&sName=$sName&sSessidReg=$sSessidReg\"><b>Logout</b></td></tr>";
	  if ((file_exists("pms")) && ($PmsActive=="X")) {
	    $sQuery="select * from $TabPrf where name='$sName'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  if ($DbRow[8]=="X") {
		    echo "<tr><td>&nbsp;</td></tr>";
	        echo "<tr><td align=left valign=top><a href=\"pms/pms.php?sAction=MENU&sName=$sName&sSessidReg=$sSessidReg\"><b>PMS</b> - Private Message System</td></tr>";
		  }
		}
	  }
	  clearstatcache();
	  echo "</table></center>";
	  EchoFooter();
      break;  
	  
  case "LOGIN":
    if (isset($butReminder)) {
	  $bProceed=true;
	  if (strpos($sRemind,"@")) {
	    $sEmail=strtolower($sRemind);
	    $sQuery="select * from $DbReg where email='$sEmail' and state='A'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  $sName=$DbRow[0];
		}
		else {
		  $bProceed=false;
		}
	  }
	  else {
	    $sName=$sRemind;
		echo $sName."&nbsp;".$DbHost."&nbsp;".$DbName."&nbsp;".$DbUser."&nbsp;".$DbPass."&nbsp;".$sQuery."&nbsp;".$bProceed."<br>";
	    $sQuery="select * from $DbReg where name='$sName' and state='A'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  $sEmail=$DbRow[2];
		}
		else {
		  $bProceed=false;
		}
	  }
	  
      $sSubTitle="Stammposter Neues Passwort";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
	  if ($bProceed) {
	    echo "<center><font color=$InfoColor>An Ihre Emailadresse wurde ein neues Passwort gesendet.</font></center>";
		
		$sNewPass=crypt(substr($sEmail,(strlen($sEmail)-6),6));
		$sNewPass=substr($sNewPass,(strlen($sNewPass)-6),6);
		
		$sNewPassDb=md5($sNewPass);
		$iNewCnt=$DbRow[8]+1;
		$sQuery="update $DbReg set passwd='$sNewPassDb',
		                           miscnt=0,
								   npcnt =$iNewCnt
							   where name='$sName'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery); 
		
		$sSubject ="$Title - Neues Passwort";
		$sMessage ="Hallo $sName,\n\n";
		$sMessage.="Ihr neues Passwort für $Title lautet:\n\n";
		$sMessage.="$sNewPass\n\n";
		$sMessage.="Mit freundlichen Grüßen\n";
		$sMessage.="Der Forenadministrator\n\n";
		$sMessage.="http://".$SERVER_NAME.$PHP_SELF;
		$sHeader="From: \"Forum $Title\" <Keine-Antwortadresse@>";
		mail($sEmail,$sSubject,$sMessage,$sHeader);
		?>
		<p align=justify>Mit diesem Passwort k&ouml;nnen Sie sich wieder hier einloggen. Eine eventuelle Sperre 
		wegen zu vieler fehlgeschlagener Logins wurde aufgehoben.</p>
		<center><a href="reglogin.php?sAction=INI">Zum Login</a></center>
		<?php 
	  }	  
	  else {
	    echo "<center><font color=$ErrColor><b>Fehler:</b> Unbekannte Stammposterregistrierung</font></center>";
		?>
		<p align=justify>Sie haben Sich vielleicht beim Stammposternamen bzw. der Emailadresse vertippt. 
		Versuchen Sie es einfach noch einmal. Weitere Ursachen des Fehlers k&ouml;nnen sein, dass Sie nicht
		als Stammposter registriert sind oder Sie wurden vom Forenadministrator deaktiviert. Wenn Sie 
		einen Stammpostertrag neu gestellt haben, beachten Sie bitte, dass Sie Ihren Antrag erst durch 
		Besuch des an Ihre Emailadresse gesendeten Best&auml;tigungslink verifizieren m&uuml;ssen und danach auf 
		eine Best&auml;tigung des Forenadministrators warten m&uuml;ssen.</p>
		<center><a href="reglogin.php?sAction=INI">Neuer Versuch</a></center>
		<?php 
	  }
      EchoFooter();
	  break;
	}
	
	$sQuery="select * from $DbReg where name='$sName' and state='A'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	$bProceed=true;

	if ($DbRow=mysql_fetch_row($DbQuery)) {	

	  if ($DbRow[7]<$MaxLoginFails) {
	    if (md5($sPasswd)!=$DbRow[1]) {
		  $bProceed=false;
		  $iNewMissCnt=$DbRow[7]+1;
		  $sQuery="update $DbReg set miscnt=$iNewMissCnt where name='$sName'";
		  $Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		}
	  }
	  else {
	    $bProceed=false;
	  }
	}
	else {
      $bProceed=false;
	}
	if (!$bProceed) {
	  $sSubTitle="Stammposter Login";
      EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
      echo "<center><font color=$ErrColor><b>Fehler:</b> Unbekannte Stammposterregistrierung</font><br><br>";
	  $Db=NULL; SetLoginLock($Db,$DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$LockTimeFail,$REMOTE_ADDR);
	  flush();
	  sleep(intval($LockTimeFail));
	  echo "<a href=\"reglogin.php?sAction=INI\">Neuer Versuch</a></center>";
      EchoFooter();
	}
	if ($bProceed) {
	  $sSessidReg=crypt($sName); $iSesstime=time()+$MaxLoginTime;
	  $sQuery="select * from $TabPrf where name='$sName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  if ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sQuery="update $TabPrf set sessid   ='$sSessidReg',
		                            sesstime =$iSesstime,
									sessvalid='X'
								where name='$sName'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  }
	  else {
	    $sQuery="insert into $TabPrf set name     ='$sName',
		                                 sessid   ='$sSessidReg',
		                                 sesstime =$iSesstime,
									     sessvalid='X'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  }
	  if ($LoginRequired=="X") {
	    $iTime=$iSesstime-(2*$MaxLoginTime);
        setcookie("sSessidReg",$sSessidReg,$iTime);
        setcookie("sName",$sName,$iTime);
		
        setcookie("sSessidReg",$sSessidReg,$iSesstime);
        setcookie("sName",$sName,$iSesstime);
	    $iForumPos=strrpos($PHP_SELF,"/");
	    $sForum=substr($PHP_SELF,0,$iForumPos)."/";
        $sUrl="http://$SERVER_NAME"."$sForum"."index.php";
	    header("location: $sUrl");		
		exit();
	  }
	  $sUrl="http://".$SERVER_NAME.$PHP_SELF."?sAction=MENU&sSessidReg=$sSessidReg&sName=$sName";
	  header("location: $sUrl");
	}	
    break;
	
  case "INI":
    $sSubTitle="Stammposter Login";
    EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
    echo "<div align=right><a href=\"index.php\">zum freien Forum</a></div>";
    if ($LoginRequired=="X") {
      echo "<br><center><b><font color=$InfoColor>Auf dieses Forum haben nur registrierte Stammposter Zugriff.</font></b></center>";
    }
    ?>
	<p align=justify>Als Stammposter k&ouml;nnen Sie sich hier in einen Bereich nur für Stammposter einloggen, und 
	selbst Einstellungen &auml;ndern oder Sie k&ouml;nnen Sich an Ihre registrierte Emailadresse ein neues Passwort 
	senden lassen, wenn Sie es vergessen haben bzw. Sie wegen 3 maliger Fehleingabe des Passwortes gesperrt sind.</p>
	<form action="reglogin.php" method=post>
	<input type=hidden name=sAction value="LOGIN">
	<center><table cellspacing=0 cellpadding=5 border=0>
	<tr><td colspan=2 align=left valign=top><b><font size=+1>Login</font></b></td></tr>
	<tr>
	<td align=left valign=top><b>Stammpostername:</b></td>
	<td align=left valign=top><input type=text name=sName size=20 maxlength=20></td>
	</tr>
	<tr>
	<td align=left valign=top><b>Passwort:</b></td>
	<td align=left valign=top><input type=password name=sPasswd size=20 maxlength=20></td>
	</tr>
	<tr><td colspan=2 align=left valign=top><input type=submit name=butLogin value="Login"></td></tr>
	
	<tr><td colspan=2 align=left valign=top>&nbsp;</td></tr>
	
	<tr><td colspan=2 align=left valign=top><b><font size=+1>Neues Passwort</font></b></td></tr>
	<tr>
	<td valign=top align=left><b>Stammpostername<br>oder Emailadresse:</b></td>
	<td valign=top align=left><input type=text name=sRemind size=30 maxlength=30></td>
	<tr><td colspan=2 align=left valign=top><input type=submit name=butReminder value="Neues Passwort"></td></tr>
	</tr>
	</table></center>
	</form>
	<?php 
    EchoFooter();
    break;
}

?>

