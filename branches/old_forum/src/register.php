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
  
  $sSubTitle="Stammposter Registrierungsantrag";
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
    
  if (!isset($sAction)) {
    $sAction="INI";
	$sName="";
	$sPasswd="";
	$sPass2="";
	$sEmail="";
	$sColor="";
	// Wenn wir schon mal hier sind, säubern wir die Datenbank gleich von allen,
	// die ihre Registrierung nicht bestätigt haben
	$iTime = time();
	$sQuery="select * from $DbReg where freetime<$iTime 
	                              and   state='4'";
	$Db=Null; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	while ($DbRow=mysql_fetch_row($DbQuery)) {
	  $sDName=$DbRow[0];
	  $sQuery="delete from $TabPrf where name='$sDName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $sQuery="delete from $TabBlk where name='$sDName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $sQuery="delete from $TabRcv where toname='$sDName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);	
	}
	$sQuery="delete from $DbReg where freetime < $iTime
	                            and   state    = '1'
								or    state    = '4'";
    $Db=Null; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  }
  if ($sAction=="INI") {
    echo "<div align=right><a href=\"reglogin.php\">Stammposter Login</a></div>";
  }

  switch ($sAction) {
    case "CONFIRM":
	  $bProceed=false;
	  $iTime=time();
	  $sName    =urldecode($sName);
	  $sFreecode=urldecode($sFreecode);
	  $sQuery="select * from $DbReg where name='$sName'
	                                and   ( state='1'
									or    state='4' )";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
      if ($DbRow=mysql_fetch_row($DbQuery)) {
	    if ($DbRow[5]>$iTime) {
		  if ($DbRow[6]=="1") {
		    $sNewState="2";
		  }
		  else {
		    $sNewState="A";
		  }
	      $sQuery="update $DbReg set state='$sNewState' where name    ='$sName'
	                                                    and   freecode='$sFreecode'
											            and   (state   ='1' or state='4')";
	      $Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	      $sQuery="select * from $DbReg where name='$sName'";
	      $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
          if ($DbRow=mysql_fetch_row($DbQuery)) {
	        if (($DbRow[6]=="2")||($DbRow[6]=="A")) {
		      $bProceed=true;
			  $sFromState=$DbRow[6];
		    }
	      }
		}
	  }
	  
	  if ($bProceed) {
	    if ($sNewState=="2") {
	      echo "<center><font color=$InfoColor><b>Vielen Dank $sName!</b> Ihre Registrierung wurde best&auml;tigt.</font></center><br>";
		  ?>
		  <p align=justify>Ihre Registrierung liegt jetzt dem Forenadministrator zur Begutachtung vor. Sobald er 
		  Sie best&auml;tigt werden Sie per Email informiert und k&ouml;nnen ab dann mit Ihrem gew&auml;hlten 
		  Nicknamen und Passwort Beitr&auml;ge im Forum schreiben.</p>
		  <?php 
		  if (CheckEmail($EmailNote)) {
		    // Email-Notice an den Admin
		    $sSubject = "$Title - Neue Stammposterregistrierung";
		    $sMessage.= "In Ihrem Forum $Title hat\n\n";
		    $sMessage.= "$sName\n\n";
		    $sMessage.= "beantragt, Stammposter zu werden.";
		    $sHeader="From: \"Info fuer Admin: Forum $Title\" <Keine-Antwortadresse@>";
	        mail($EmailNote,$sSubject,$sMessage,$sHeader);
		  }
	    }
		else {
	      echo "<center><font color=$InfoColor><b>Vielen Dank $sName!</b> Ihre Registrierung wurde best&auml;tigt.</font></center><br>";
		  ?>
		  <p align=justify>Sie k&ouml;nnen jetzt wieder als Stammposter agieren.</p>
		  <?php 
		}
	  }
	  else {
	    echo "<center><font color=$ErrColor><b>Ihre Registrierung wurde nicht best&auml;tigt</b></font></center><br>";
		?>
		<p align=justify>Als Ursache kommen in Frage: Sie haben keine Registrierungsantrag gestellt, Sie haben Ihre 
		Registrierung nicht innerhalb von 7 Tagen bestätigt, in seltenen F&auml;llen auch ein Systemfehler. 
		M&ouml;glicherweise haben Sie Ihre Registrierung aber auch schon best&auml;tigt.<br>
		<br>
		Versuchen Sie es sp&auml;ter noch einmal, bei andauerndem Misserfolg stellen Sie bitte (erneut) einen 
		Registrierungsantrag.</p>
		<?php 
	  }
	  $sAction="DONE";
  	  echo "<center><a href=\"index.php\">Zum Forum</a></center>";
	  break;
    case "REGISTER":
	  if (isset($butDel)) {
	    $sName="";
	    $sPasswd="";
	    $sPass2="";
	    $sEmail="";
	    $sColor="";
		break;
	  }
	  
	  $bProceed=true;
	  if ($bProceed) {
	    if (strlen($sName)<2) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ein Name muss mindestens 2 Zeichen haben!</font></center>";
		  $bProceed=false;
	    }
	  }
	  
	  if ($bProceed) {
	    if ($sPasswd!=$sPass2) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Passwort und -wiederholung nicht identisch!</font></center>";
		  $bProceed=false;
	    }
	  }
	  
	  if ($bProceed) {
	    if (strlen($sPasswd)<6) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Passwort muss mindestens 6 Zeichen haben!</font></center>";
		  $bProceed=false;
	    }
	  }
	  
	  if ($bProceed) {
	    if (!CheckEmail($sEmail)) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ung&uuml;ltige Emailadresse!</font></center>";
		  $bProceed=false;
	    }
	  }

	  if ($bProceed) {
	    if ($RegsSameCol=="X") {
		  $sColor=substr($RegColor,1,6);
		}
	    elseif (strlen($sColor)<6) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ung&uuml;ltiger Wert f&uuml;r Farbe!</font></center>";
		  $bProceed=false;
	    }
	  }
	  
	  if ($bProceed) {
	    if (!CheckColor($sColor)) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Ung&uuml;ltiger Wert f&uuml;r Farbe!</font></center>";
		  $bProceed=false;
	    }
	  }
	  
	  if ($bProceed) {
	    $sEmail=strtolower($sEmail);
	    $sQuery="select * from $DbReg
		                       where name ='$sName'
							   or    email='$sEmail'";
        $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Diesen Nicknamen gibt es hier schon!</font></center>";
		  $bProceed=false;
		}
	    $sQuery="select * from $DbAdm
		                       where userid ='$sName'
							   or    email  ='$sEmail'";
        $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
	      echo "<center><font color=$ErrColor><b>Fehler: </b>Diesen Nicknamen gibt es hier schon!</font></center>";
		  $bProceed=false;
		}		
	  }
	  
	  if ($bProceed) {
	    $iFreetime=time()+604800;
		$sFreecode=crypt($sEmail);
		$sFreecode=substr($sFreecode,(strlen($sFreecode)-6),6);
		$sMd5Passwd=md5($sPasswd);
		$sRegMsg=KffStripTags($sRegMsg);
		$sRegMsg=stripcslashes($sRegMsg);
		$sRegMsg=addslashes($sRegMsg);
		$sQuery="insert into $DbReg set name    ='$sName',
		                                passwd  ='$sMd5Passwd',
										email   ='$sEmail',
										color   ='$sColor',
										state   ='1',
										freecode='$sFreecode',
										freetime=$iFreetime,
										miscnt  =0,
										RegMsg  ='$sRegMsg'";
		$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		$sAction="DONE";
		
		$iForumPos=strrpos($PHP_SELF,"/");
		$sForum=substr($PHP_SELF,0,$iForumPos)."/";
		
		$sNameEnc    =urlencode($sName);
		$sFreecodeEnc=urlencode($sFreecode);
		
		$sSubject=$Title." - Antrag auf Stammposterregistrierung";
		$sMessage ="Vielen Dank $sName!\n\n";
		$sMessage.="Sie haben sich im Forum $Title als Stammposter registriert. Bitte bestätigen Sie nun diese ";
		$sMessage.="Registrierung, indem Sie innerhalb von 7 Tagen den folgenden Link besuchen:\n\n";
		$sMessage.="http://$SERVER_NAME"."$PHP_SELF?sAction=CONFIRM&sFreecode=$sFreecodeEnc&sName=$sNameEnc\n\n";
		$sMessage.="Sollten Sie den Link nicht besuchen, verfällt Ihre Registrierung. Wenn Sie Ihre Registrierung ";
		$sMessage.="bestätigen, wird der Forenadministrator nach Begutachtung Ihre Registrierung freischalten. ";
		$sMessage.="Sie bekommen dann Email und können ab dann mit Ihrem gewählten Nickanmen und Passwort ";
		$sMessage.="Beiträge schreiben.\n\n";
		$sMessage.="Mit freundlichen Grüßen\n";
		$sMessage.="Der Forenadministrator\n\n";
		$sMessage.="http://$SERVER_NAME.$sForum";
		if (CheckEmail($SenderMail)>=5) {
		  echo "$SenderMail";
          $sHeader="From: \"Forum $Title\" <$SenderMail>";
		}
		else {
          $sHeader="From: \"Forum $Title\" <Keine-Antwortadresse@>";
		}
		mail($sEmail,$sSubject,$sMessage,$sHeader);
        echo "<center><font color=$InfoColor><b>Vielen Dank $sName!</b> Ihr Antrag wurde übermittelt.</font></center><br>";
		?>
		<p align=justify>Das System hat Ihnen eine Email geschickt, die einen Best&auml;tigungslink enth&auml;lt. 
		Diesen Link m&uuml;ssen Sie innerhalb von 7 Tagen besuchen, ansonsten verf&auml;llt Ihr Registrierungsantrag.<br>
		<br>
		Wenn Sie durch Besuch des Links Ihren Antrag best&auml;tigt haben wird eine weitere Pr&uuml;fung durch den 
		Forenadministrator erfolgen. Sobald dieser Ihren Antrag akzeptiert, bekommen Sie eine Email zur Information und 
		k&ouml;nnen ab dann mit Ihrem gew&auml;hltem Nicknamen in Kombination mit Ihrem Beitrag im Forum posten.</p>
		<center><a href="index.php">Zur&uuml;ck zum Forum</a></center>
		<?php 
		
	  }
	  break;
  }
  
  if ($sAction!="DONE") {
  if ($LoginRequired=="X") {
    echo "<br><center><b><font color=$InfoColor>Auf dieses Forum haben nur registrierte Stammposter Zugriff.</font></b></center>";
  }
  $bDefault=true;
  if ($sRegText=ReadMenuFile("REGISTERTEXT")) {
    if (strlen($sRegText)>3) {
	  echo $sRegText;
	  $bDefault=false;
	}
  }
  if ($bDefault) {
    ?>
    <p align=justify>Als Stammpotser k&ouml;nnen Sie im Forum mit Ihrem Nicknamen und Ihrem Passwort in einer speziellen 
    Farbe schreiben. Da ein Stammpostername eindeutig ist und immer eine andere Farbe hat als ein Gastposting, ist 
    damit gew&auml;hrleistet, dass ein Beitrag wirklich von Ihnen stammt, falls es einen Gastbeitrag mit gleichem Namen 
    gibt. Sie k&ouml;nnen also nicht mehr gef&auml;lscht (gefaked) werden.<br>
    <br>
    Ihre hier angebenen Emailadresse wird nicht im Forum gezeigt. Sie dient lediglich dem Forenadministrator dazu einen 
    Anhaltspunkt zu haben, wer seine Stammposter eigentlich sind. Bevor Ihr Antrag vom Forenadministrator genehmigt 
    wird, wird Ihre Emailadresse verifiziert. An Ihre Emailadresse sendet das System Ihnen einen Link, den Sie besuchen 
    m&uuml;ssen. Damit wird die Korrektheit Ihrer Emailadresse best&auml;tigt.</p>
	<?php 
  }
  ?>
  
  <form action="register.php" method=post>
  <input type=hidden name=sAction value="REGISTER">
  <center><table cellspacing=0 cellpadding=5>
  <tr>
  <td align=left valign=top><b>Nickname:</b></td>
  <td align=left valign=top><input type=text name=sName size=20 maxlength=20 value="<?php echo $sName;?>"></td>
  </tr>
  
  <tr>
  <td align=left valign=top><b>Passwort (min. 6 Zeichen):</b></td>
  <td align=left valign=top><input type=password name=sPasswd size=20 maxlength=20 value="<?php echo $sPasswd;?>"></td>
  </tr>
  
  <tr>
  <td align=left valign=top><b>Passwortwiederholung:</b></td>
  <td align=left valign=top><input type=password name=sPass2 size=20 maxlength=20 value="<?php echo $sPass2;?>"></td>
  </tr>
  
  <tr>
  <td align=left valign=top><b>Emailadresse:</b></td>
  <td align=left valign=top><input type=text name=sEmail size=30 maxlength=30 value="<?php echo $sEmail;?>"></td>
  </tr>
  
  <tr>
  <td colspan=2 align=left valign=top><b>Nachricht an die Forenadministration (optional, kein HTML)</b></td>
  </tr>
  
  <tr>
  <td colspan=2 align=left valign=top><textarea name=sRegMsg cols=85 rows=10><?php echo $sRegMsg;?></textarea></td>
  </tr>
  
  <?php 
  if ($RegsSameCol!="X") {
    ?>
    <tr>
    <td align=left valign=top><b>Wunschfarbe (RGB Hexwerte):</b></td>
    <td align=left valign=top><input type=text name=sColor size=6 maxlength=6 value="<?php echo $sColor;?>"></td>
    </tr>
	<?php 
  }
  ?>
  
  <tr>
  <td align=left valign=top><input type=submit name=butReg value="Registrieren"></td>
  <td align=left valign=top><input type=submit name=butDel value="Eingaben l&ouml;schen"></td>
  </tr>
  </table></center>
  </form>
  <?php 
  }
  EchoFooter();
?>