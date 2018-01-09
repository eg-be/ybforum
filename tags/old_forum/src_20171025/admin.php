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
  
  include_once ("cfg/config.php");
  include_once ("functions.php");
  include_once ("functions2.php");
  include_once ("admfunc.php");

  include_once ("failloginstop.php");
  include_once ("get_page.php");
  
  if (!CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime)) {
    header("location: login.php");
  }
  $sLoggedIn="X";
    
  if (!isset($sAction)) {
    $sAction="LOGIN";
  }
  
  if ($sAction=="LOGOUT") {
    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	  mysql_db_query($DbName,"update $DbAdm set kzsval='-' where userid='$sUser'",$Db);
	}
	header("location: index.php");
  }

  $aConfig=ReadConfigFile();
  $Version=GetRamValue("\$Version",$aConfig);
  $PatchLevel=GetRamValue("\$PatchLevel",$aConfig);
  if (($Version=="1.75") && ($PatchLevel=="0") && (file_exists("patch_175_1.txt"))) {
    $aConfig=SetRamValue("\$Version","1.75",$aConfig);
    $aConfig=SetRamValue("\$PatchLevel","1",$aConfig);
	WriteConfigFile($aConfig);
  }
  clearstatcache();
  
  echo "<html><head><title>Administration Forum -$Title-</title>
  <style type=\"text/css\">
  <!-- 
  h1 {font-family:Arial; font-size:18pt;}
  h2 {font-family:Arial; font-size:16pt;}
  h3 {font-family:Arial; font-size:14pt;}
  h4 {font-family:Arial; font-size:12pt;}
  body, table, td, center, i, u, b, p, div {font-family:Arial; font-size:10pt;}
  // -->
  </style>
  </head>";
  echo "<body text=#000000 bgcolor=#fafafa link=#000000 alink=#000000 vlink=#666666>";
  echo "<center><table width=90%><tr><td><br>";
  
  echo "<h1 align=center>Administration Forum &quot;$Title&quot;</h1>";

  $aConfig=ReadConfigFile();
  $Version=GetRamValue("\$Version",$aConfig);
  $PatchLevel=GetRamValue("\$PatchLevel",$aConfig);
  
  $LevelGiven=GetAdmLevel($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser);
  
  switch ($sAction) {
    case "DOCONFIRMREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }	
	  $iAnz=sizeof($aConf);
	  for ($i=0;$i<$iAnz;$i++) {
	    $sName=substr($aConf[$i],1,(strlen($aConf[$i])-1));
	    switch($aConf[$i][0]) {
		  case "a":
		    $sQuery="update $DbReg set state='A' where name='$sName'";
			$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
			
		    $sQuery="select * from $DbReg where name='$sName'";
			$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
			if ($DbRow=mysql_fetch_row($DbQuery)) {$sEmail=$DbRow[2];}
			
			$sSubject = "$Title - Der Forenadministrator hat Sie akzeptiert";
			$sMessage = "Herzlichen Glückwunsch $sName!\n\n";
			$sMessage.= "Sie wurden im Forum $Title als Stammposterantrag akzeptiert.\n\n";
			$sMessage.= "Ab sofort können Sie mit Ihrem mit Ihrem gewählten Nicknamen und Passwort Beiträge ";
			$sMessage.= "schreiben.\n\n";
			$sMessage.= "Mit freundlichen Grüßen\n";
			$sMessage.= "Der Forenadministrator\n\n";
		    $iForumPos=strrpos($PHP_SELF,"/");
		    $sForum=substr($PHP_SELF,0,$iForumPos)."/";
			$sMessage.= "http://".$SERVER_NAME.$sForum;
			if (CheckEmail($SenderMail)) {
			  $sHeader="From: \"Forum $Title\" <$SenderMail>";
			}
			else {
			  $sHeader="From: \"Forum $Title\" <Keine-Antwortadresse@>";
			}
			mail($sEmail,$sSubject,$sMessage,$sHeader);			
		    break;
			
		  case "d":
		    $sQuery="delete from $DbReg where name='$sName'";
			$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		    break;
		}
	  }
      echo "<h3 align=center><font color=#0000ff>Ihre Begutachtung wurde gespeichert</font></h3>";
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "CONFIRMREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $sQuery="select * from $DbReg where state='2' order by name";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  ?>
	  <h3 align=center>Offene Stammposteranträge</h3>
	  <center><b>Akzeptierte Stammposter erhalten automatisch eine Emailbenachrichtung.</b></center><br>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOCONFIRMREG">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table border=1 cellspacing=0 cellpadding=4>
	  <tr>
	  <td><b>Nickname</b></td>
	  <td><b>Emailadresse</b></td>
	  <td><b>akzeptieren</b></td>
	  <td><b>ablehnen</b></td>
	  <td><b>sp&auml;ter entscheiden</b></td>
	  </tr>
	  
	  <?php
	  $iCount=0;
	  while ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sName= $DbRow[0];
	    $sEmail=$DbRow[2];
		$sText =$DbRow[9];
		$sText =stripcslashes($sText);
		echo "<tr>";
		echo "<td>$sName</td>";
		echo "<td>$sEmail</td>";
		echo "<td align=center><input type=radio name=aConf[$iCount] value=\"a$sName\"></td>";
		echo "<td align=center><input type=radio name=aConf[$iCount] value=\"d$sName\"></td>";
		echo "<td align=center><input type=radio name=aConf[$iCount] value=\"n$sName\" checked></td>";
		echo "</tr>";
		if (strlen($sText)>0) {
		  echo "<tr><td colspan=5>$sText</td><tr>";
		  echo "<tr><td colspan=5>&nbsp;</td><tr>";
		}
		$iCount++;
	  }
	  ?>
	  
	  <tr>
	  <td colspan=5 align=center><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php
	  
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	
	case "DOTHREAD":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aConfig=ReadConfigFile();
	  if ($sReverseOrderInThread!="X") {
	    $sReverseOrderInThread="-";
	  }
	  $aConfig=SetRamValue("\$ReverseOrderInThread",$sReverseOrderInThread,$aConfig);
	  WriteConfigFile($aConfig);
	  $bOk=true;
	  if ($bOk) {
	    if ($kzactive!="X") {$kzactive="-";}
	    if ($kzbold!="X")   {$kzbold="-";}
	    if ($kzitalic!="X") {$kzitalic="-";}
	    if ($kzulined!="X") {$kzulined="-";}
	    if ($kzframe!="X")  {$kzframe="-";}
		
		if (!CheckColor($text))    {$text=$BodyText;}
		if (!CheckColor($bgcolor)) {$bgcolor=$BodyBgcolor;}
		
		if (isset($butDefault)) {
		  $text=substr($BodyText,1,6);
		  $bgcolor=substr($BodyBgcolor,1,6);
	      $sQuery="update $DbTh1 set kzactive = '-',
                                     kzbold   = 'X',
                                     kzitalic = '-', 
                                     kzulined = '-', 
                                     kzframe  = '-',
                                     text     = '$text',
                                     bgcolor  = '$bgcolor'
						  where pk='1'";
		}
		else {
	      $sQuery="update $DbTh1 set kzactive = '$kzactive',
                                     kzbold   = '$kzbold',
                                     kzitalic = '$kzitalic', 
                                     kzulined = '$kzulined', 
                                     kzframe  = '$kzframe',
                                     text     = '$text',
                                     bgcolor  = '$bgcolor'
						  where pk='1'";
		}
		$Db=NULL; $bOk=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  }
	  if ($bOk) {
	    if (strlen($sign0)<1) {$sign0=" ";}
	    if (strlen($sign1)<1) {$sign1=" ";}
	    if (strlen($sign2)<1) {$sign2=" ";}
	    if (strlen($sign3)<1) {$sign3=" ";}
	    if (strlen($sign4)<1) {$sign4=" ";}
	    if (strlen($sign5)<1) {$sign5=" ";}
		
		if (isset($butDefault)) {
	      $sQuery="update $DbTh2 set sign0 = ' ',
                                     graf0 = '',
								     sign1 = ' ',
                                     graf1 = '',
								     sign2 = ' ',
                                     graf2 = '',
								     sign3 = ' ',
                                     graf3 = '',
								     sign4 = ' ',
                                     graf4 = '',
								     sign5 = ' ',
                                     graf5 = ''
						  where pk='1'";
		}
		else {
	      $sQuery="update $DbTh2 set sign0 = '$sign0',
                                     graf0 = '$graf0',
								     sign1 = '$sign1',
                                     graf1 = '$graf1',
								     sign2 = '$sign2',
                                     graf2 = '$graf2',
								     sign3 = '$sign3',
                                     graf3 = '$graf3',
								     sign4 = '$sign4',
                                     graf4 = '$graf4',
								     sign5 = '$sign5',
                                     graf5 = '$graf5'
						  where pk='1'";
		}
		$Db=NULL; $bOk=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  }
	  if ($bOk) {
	    if (isset($butDefault)) {
          echo "<h3 align=center><font color=#0000ff>Threadlayout - Defaulteinstellungen wurden gesetzt, Einstellungen sind dekativiert.</font></h3>";
		}
		else {
          echo "<h3 align=center><font color=#0000ff>Threadlayout - Einstellungen wurden gespeichert</font></h3>";
		}
	  }
	  else {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Die Datenbankverbindung war nicht erfolgreich!</font></h3>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "THREAD":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Threadlayout</h3>";
	  echo "<p align=justify>Hier haben Sie M&ouml;glichkeit das Layout der Diskussionsdarstellung im Forum zu 
	        beinflussen. Die Funktion kann ein- oder abgeschaltet werden, Ihre Einstellungen bleiben aber auch bei 
			Abschaltung erhalten. Mit dem Button &quot;Standard&quot;, k&ouml;nnen Sie eine Einstellung wiederherstellen, 
			die bei eingeschalteter Funktion dasselbe Layout ergibt, wie bei abgeschalteter Funktion, es ergibt 
			sich also dann kein Unterschied zwischen dem Ein- und Auszustand.</p>";
			
	  $Db=NULL;$sQuery="select * from $DbTh1";$DbQuery1=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $Db=NULL;$sQuery="select * from $DbTh2";$DbQuery2=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  
	  if ((!($DbRow1=mysql_fetch_row($DbQuery1))) || (!($DbRow2=mysql_fetch_row($DbQuery2)))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Kein Verbindung zur Datenbank!</font></h3>";
  	    echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
		break;
	  }  
      $kzactive = $DbRow1[1];
      $kzbold   = $DbRow1[2];
      $kzitalic = $DbRow1[3]; 
      $kzulined = $DbRow1[4]; 
      $kzframe  = $DbRow1[5];
      $text     = $DbRow1[6];
      $bgcolor  = $DbRow1[7];
	  
      $sign0 = $DbRow2[1]; 
      $graf0 = $DbRow2[2];  
      $sign1 = $DbRow2[3];  
      $graf1 = $DbRow2[4];  
      $sign2 = $DbRow2[5];  
      $graf2 = $DbRow2[6];  
      $sign3 = $DbRow2[7];  
      $graf3 = $DbRow2[8];  
      $sign4 = $DbRow2[9];  
      $graf4 = $DbRow2[10];  
      $sign5 = $DbRow2[11];  
      $graf5 = $DbRow2[12];  
	  
	  $aConfig=ReadConfigFile();
	  $sReverseOrderInThread=GetRamValue("\$ReverseOrderInThread",$aConfig);
	  
	  ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOTHREAD">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table cellspacing=0 cellpadding=5>
	  <tr>
	  <td><input type=checkbox name=sReverseOrderInThread value="X" <?php if ($sReverseOrderInThread=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Beitragschronologie innerhalb eines Threads umkehren.</b></p></td>
	  </tr>
	  
	  <tr><td colspan=2>&nbsp;</td></tr>
	  
	  <tr>
	  <td><input type=checkbox name=kzactive value="X" <?php if ($kzactive=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Einstellungen aktiv:</b> Die Einstellungen, die Sie ab hier machen, wirken sich nur 
	  im Forum aus, wenn Sie diese Option aktivieren.</p></td>
	  </tr>
	  
	  <tr>
	  <td><input type=checkbox name=kzbold value="X" <?php if ($kzbold=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Erster Beitrag fett:</b> In der Diskussion&uuml;bersicht, der Indexseite, wird der
	  erste Beitrag eines Threads fett geschrieben. Dies ist die Defaulteinstellung des Forums.</p></td>
	  </tr>
	  
	  <tr>
	  <td><input type=checkbox name=kzitalic value="X" <?php if ($kzitalic=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Erster Beitrag kurisv:</b> In der Diskussion&uuml;bersicht, der Indexseite, wird der
	  erste Beitrag eines Threads kursiv geschrieben. Dies ist keine Defaulteinstellung des Forums.</p></td>
	  </tr>
	  
	  <tr>
	  <td><input type=checkbox name=kzulined value="X" <?php if ($kzulined=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Erster Beitrag unterstrichen:</b> In der Diskussion&uuml;bersicht, der Indexseite, wird der
	  erste Beitrag eines Threads unterstrichen. Dies ist keine Defaulteinstellung des Forums.</p></td>
	  </tr>
	  
	  <tr>
	  <td><input type=text name=text size=6 maxlength=6 value="<?php echo $text;?>"></td>
	  <td><p align=justify><b>Erster Beitrag in dieser Farbe:</b> In der Diskussion&uuml;bersicht, der Indexseite, wird der
	  erste Beitrag eines Threads in dieser Farbe dargestellt. Dies keine Defaulteinstellung des Forums. 
	  Eine ung&uuml;ltige Eingabe setzt die Farbe auf die Textfarbe der Grundeinstellungen.</p></td>
	  </tr>
	  
	  <tr>
	  <td><input type=text name=bgcolor size=6 maxlength=6 value="<?php echo $bgcolor;?>"></td>
	  <td><p align=justify><b>Erster Beitrag in dieser Farbe:</b> In der Diskussion&uuml;bersicht, der Indexseite, wird der
	  erste Beitrag eines Threads mit dieser Farbe als Hintergrund dargestellt, wenn die hier eingebene Farbe von
	  der Hintergrundfarbe in den Grundeinstellungen abweicht. Dies keine Defaulteinstellung des Forums. 
	  Eine ung&uuml;ltige Eingabe setzt die Farbe auf die Textfarbe der Grundeinstellungen.</p></td>
	  </tr>
	  	  
	  <tr>
	  <td><input type=checkbox name=kzframe value="X" <?php if ($kzframe=="X") {echo "checked";}?>></td>
	  <td><p align=justify><b>Rahmen um einen Thread:</b> Um jeden Thread des Forums wird ein Rahmen mit ein 
	  Pixel breite gelegt. Die wird erreicht durch die Schreiben des Threads in eine einzellige Tabelle. Dies ist 
	  keine Defaulteinstellung des Forums.</p></td>
	  </tr>
	  <tr><td colspan=2>&nbsp;</td></tr>

	  <tr>
	  <td colspan=2><p align=justify>Hier können zusätzlich zu dem automatischen Einrücken von Antworten bis zu 
	  einer Einrücktiefe von 5 zusätzliche Kennzeichen für diese Einrücktiefe angeben. Ab einer Tiefe von 
	  5 werden tiefere Ebene wie die Ebene 5 dargestellt. Sie können entweder ein Zeichen eingeben oder eine 
	  Grafik. Wenn Sie beides in einer Ebene angeben, wird dir Grafik genommen. Wollen Sie keine zusätzliche 
	  Kennzeichung, lassen Sie die Felder einfach leer.</p></td>
	  </tr>
	  
	  <tr>
	  <td width=15%><b>Ebene Zeichen</b></td>
	  <td width=85%><b>Ebene Grafik</b></td>
	  </tr>
	  
	  <tr>
	  <td> Zeichen Ebene 0 <input type=text name=sign0 size=1 maxlength=1   value="<?php echo $sign0;?>"></td>
	  <td> Grafik Ebene  0 <input type=text name=graf0 size=70 maxlength=70 value="<?php echo $graf0;?>"></td>
	  </tr>
	  
	  <tr>
	  <td> Zeichen Ebene 1 <input type=text name=sign1 size=1 maxlength=1   value="<?php echo $sign1;?>"></td>
	  <td> Grafik Ebene  1 <input type=text name=graf1 size=70 maxlength=70 value="<?php echo $graf1;?>"></td>
	  </tr>

	  <tr>
	  <td> Zeichen Ebene 2 <input type=text name=sign2 size=1 maxlength=1   value="<?php echo $sign2;?>"></td>
	  <td> Grafik Ebene  2 <input type=text name=graf2 size=70 maxlength=70 value="<?php echo $graf2;?>"></td>
	  </tr>

	  <tr>
	  <td> Zeichen Ebene 3 <input type=text name=sign3 size=1 maxlength=1   value="<?php echo $sign3;?>"></td>
	  <td> Grafik Ebene  3 <input type=text name=graf3 size=70 maxlength=70 value="<?php echo $graf3;?>"></td>
	  </tr>
	  
	  <tr>
	  <td> Zeichen Ebene 4 <input type=text name=sign4 size=1 maxlength=1   value="<?php echo $sign4;?>"></td>
	  <td> Grafik Ebene  4 <input type=text name=graf4 size=70 maxlength=70 value="<?php echo $graf4;?>"></td>
	  </tr>
	  
	  <tr>
	  <td> Zeichen Ebene 5 <input type=text name=sign5 size=1 maxlength=1   value="<?php echo $sign5;?>"></td>
	  <td> Grafik Ebene  5 <input type=text name=graf5 size=70 maxlength=70 value="<?php echo $graf5;?>"></td>
	  </tr>
	  <tr><td colspan=2>&nbsp;</td></tr>
	  	  
      <tr>
	  <td><input type=submit value="Speichern"></td>
	  <td><input type=submit name=butDefault value="Defaulteinstellungen setzen"></td>
	  <td></td>
	  </tr>
	  
	  </table></center>
	  
	  </form>
	  <?php 
			
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOANTISPAM":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aConfig=ReadConfigFile();
	  
	  $sLockTime=intval($sLockTime); $sLockTime=strval($sLockTime);
	  $sMaxUses =intval($sMaxUses);  $sMaxUses=strval($sMaxUses);
	  if ($sPosterSpamProtect!="X") {
	    $sPosterSpamProtect="-";
	  }
	  
	  $Key="\$PosterSpamProtect"; $aConfig=SetRamValue($Key,$sPosterSpamProtect,$aConfig);
	  $Key="\$LockTime";          $aConfig=SetRamValue($Key,$sLockTime,$aConfig);
	  $Key="\$MaxUses";           $aConfig=SetRamValue($Key,$sMaxUses,$aConfig);
	  
	  WriteConfigFile($aConfig);
      echo "<h3 align=center><font color=#0000ff>Spamschutzeinstellungen wurden gespeichert</font></h3>";
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "ANTISPAM":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Spamschutz</h3>";
	  
	  $aConfig=ReadConfigFile();
	  
	  $Key="\$PosterSpamProtect"; $sPosterSpamProtect=GetRamValue($Key,$aConfig);
	  $Key="\$LockTime";          $sLockTime=GetRamValue($Key,$aConfig);
	  $Key="\$MaxUses";           $sMaxUses=GetRamValue($Key,$aConfig);
	  ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOANTISPAM">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table cellspacing=0 cellpadding=5>
	  <tr>
	  <td colspan=2><input type=checkbox name=sPosterSpamProtect value="X" <?php if ($sPosterSpamProtect=="X"){echo "checked";}?>><b>Spamschutz f&uuml;r Poster aktiv</b></td>
	  </tr>
	  <tr>
	  <td colspan=2><p align=justify>Wenn diese Option aktiv ist, wird die Emailadressen, die ein Beitragsschreiber 
	  im entsprechenden Formularfeld optional hinterlassen kann, als Bild dargestellt. Damit k&ouml;nnen die Adressen nicht mehr von Suchrobots gefunden werden, 
	  um Sie mit Spam zuzum&uuml;llen. Ein ehrliche Schreiber aber muss im Gegenzug für diesen Schutz die Emailadresse 
	  vom Bild abtippen.</p></td>
	  </tr>
	  <tr><td colspan=2>&nbsp;</td></tr>
	  
	  <tr>
	  <td><b>Sperrzeit in Sekunden</b></td>
	  <td><input type=text name=sLockTime size=6 maxlength=6 value="<?php echo $sLockTime;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Anzahl Postings pro Sperrzeit</b></td>
	  <td><input type=text name=sMaxUses size=6 maxlength=6 value="<?php echo $sMaxUses;?>"></td>
	  </tr>
	  <tr>
	  <td colspan=2><p align=justify>Sind diese Werte beide ungleich null, k&ouml;nnen von einer IP-Adresse aus innerhalb 
	  der Sperrzeit nur die Anzahl Postings geschrieben werden, die Sie im zweiten Feld eingetragen haben. Die 
	  Sperrzeit l&auml;uft ab dem 1. Posting.</p></td>
	  </tr>
	  <tr>
	  <td colspan=2 align=center><input type=submit value="Einstellungen speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 
	  
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOEMAIL":
	  if (($sLoggedIn!="X") || (!(CheckAdmRight("PRIMARY",$LevelGiven)))){
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  if ((!CheckEmail($sEmail)) && (!($sEmail==""))) {
      echo "<h3 align=center><font color=#ff0000>Fehler: Ung&uuml;ltige Emailadresse</font></h3>";
	  }
	  else {
	    echo "<h3 align=center>Emailbenachrichtung gespeichert</h3>";
		$aConfig=ReadConfigFile();

	  	$sKey="\$EmailNote";
		$sValue=$sEmail;
	    $aConfig=SetRamValue($sKey,$sValue,$aConfig);

		WriteConfigFile($aConfig);
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "EMAIL":
	  if (($sLoggedIn!="X") || (!(CheckAdmRight("PRIMARY",$LevelGiven)))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Emailbenachrichtung</h3>";
	  $aConfig=ReadConfigFile();
	  $sKey="\$EmailNote";
	  $sEmail=GetRamValue($sKey,$aConfig);
	  if ($sEmail=="-") {
	    $sEmail="";
	  }
	  ?>
	  <p align=justify>Hier k&ouml;nnen Sie eine Emailadresse angeben, an die Sie jedesmal eine Email bekommen, wenn
	  in Ihrem Forum ein neuer Beitrag gepostet wurde. Bitte seien Sie mit dieser Funktion vorsichtig und stellen 
	  Sie sicher, eine g&uuml;ltige Emailadresse von sich angeben zu haben. Wenn Sie keine Benachrichtigung erhalten 
	  wollen, lassen Sie das Feld einfach leer.</p>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOEMAIL">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center>
	  <b>Ihre Emailadresse: </b><input type=text name=sEmail size=50 maxlength=50 value="<?php echo $sEmail;?>"><br><br>
	  <input type=submit value="Speichern">
	  </center>
	  </form>
	  <?php
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOTITLE":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  if ($hndFile=fopen("cfg/title.txt","w+")) {
	    $sTitle=stripcslashes($sTitle);
	    fwrite($hndFile,$sTitle);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/footer.txt","w+")) {
	    $sFooter=stripcslashes($sFooter);
	    fwrite($hndFile,$sFooter);
	    fclose($hndFile);
	  }	  
	  if ($hndFile=fopen("cfg/head.txt","w+")) {
	    $sFooter=stripcslashes($sHead);
	    fwrite($hndFile,$sHead);
	    fclose($hndFile);
	  }	  
	  clearstatcache();
      echo "<h3 align=center>Seitenlayout gespeichert</h3>";
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
  
    case "TITLE":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Seitengestaltung</h3>";
	  if (!($sTitle=ReadTitleCode())) {
	    $sTitle="";
	  }
	  if (!($sFooter=ReadFooterTxt())) {
	    $sFooter="";
	  }
	  if (!($sHead=ReadHeadTxt())) {
	    $sHead="";
	  }
	  ?>
	  <p align=justify>Hier k&ouml;nnen Sie HTML-Code eingeben, der den Titel bzw. den Fu&szlig; Ihres Forums bilden 
	  soll. Sobald einer der Codes l&auml;nger als 3 Zeichen ist, wird er als Titel bzw. Fu&szlig; verwendet. Der
	  Titel &uuml;bersteuert dabei die Grundeinstellungen Forentitel, Banner und die festcodierten Untertitel. Eine 
	  Fusszeile gibt es im Default nicht, sie wird einfach auf den Forenseiten eingef&uuml;gt, wenn Sie sie hier 
	  festlegen.</p>
	  <form action="admin.php" method=post>
  	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <input type=hidden name=sAction value="DOTITLE">
	  <center><table>
	  <tr>
	  <td align=center><b>HTML-Code f&uuml;r Forenkopf (HTML-Head)</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sHead cols=85 rows=10><?php echo $sHead;?></textarea></td>
	  </tr>
	  <tr>
	  <tr><td>&nbsp;</td></tr>
	  
	  <tr>
	  <td align=center><b>HTML-Code f&uuml;r Forentitel (HTML-Body)</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sTitle cols=85 rows=10><?php echo $sTitle;?></textarea></td>
	  </tr>
	  <tr>
	  <tr><td>&nbsp;</td></tr>
	  
	  <tr>
	  <td align=center><b>HTML-Code für Fu&szlig;zeile</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sFooter cols=85 rows=10><?php echo $sFooter;?></textarea></td>
	  </tr>
	  <tr>
	  
	  <td align=center><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  
	  <?php
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  	  
	  break;
  
    case "DOLENGTH":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  
	  if ((intval($sPostsInForum)<150) || (intval($sPostsInArc)<150)) {
	    echo "<h3 align=center><font color=#ff0000>Die L&auml;ngen für Form und Archiv m&uuml;ssen mindestens 150 sein!</font></h3>";
	  }
	  else {
	    $aRamFile=ReadConfigFile();
	  	$sKey="\$PostsInForum";
		$sValue=$sPostsInForum;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$PostsInArc";
		$sValue=$sPostsInArc;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
		WriteConfigFile($aRamFile);
	    echo "<h3 align=center>Archiv- und Forenl&auml;nge wurden gespeichert!</h3>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  	  
	  break;
  
    case "LENGTH":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Archiv- und Forenl&auml;nge</h3>";
	  $aRamFile=ReadConfigFile();
	  $sKey="\$PostsInForum"; $sPostsInForum=GetRamValue($sKey,$aRamFile);
      $sKey="\$PostsInArc";   $sPostsInArc=GetRamValue($sKey,$aRamFile);
	  
	  ?>
	  <form action="admin.php" medthod=post>
	  <input type=hidden name=sAction value="DOLENGTH">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table>
	  <tr>
	  <td><b>Beiträge im Forum (min. 150)</b></td>
	  <td><input type=text name=sPostsInForum size=6 maxlength=6 value="<?php echo $sPostsInForum;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Beiträge im Archiv (min. 150)</b></td>
	  <td><input type=text name=sPostsInArc size=6 maxlength=6 value="<?php echo $sPostsInArc;?>"></td>
	  </tr>
	  
	  <tr>
	  <td align=center colspan=2><input type=submit value="Speichern"></td>
	  </tr>
	  
	  </table></center>
	  </form>
	  <?php
	  
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  	  
	  break;
  
    case "DOBADWORDS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("3",$LevelGiven))){
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Badwords Liste gespeichert</h3>";
	  if ($hndFile=fopen("cfg/badwords.txt","w+")) {
	    $sBadWords=strtolower($sBadWords);
	    fwrite($hndFile,$sBadWords);
		fclose($hndFile);
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  	  
	  break;
	  
    case "BADWORDS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("3",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Badwords Liste</h3>";
      if ($aBadWords=ReadBadWords()) {
	    $sBadWords=implode(",",$aBadWords);
	  }
	  else {
	    $sBadWords="";
	  }
	  ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOBADWORDS">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table>
	  <tr><td align=center><textarea name=sBadWords cols=85 rows=10><?php echo $sBadWords;?></textarea></td></tr>
	  <tr><td align=center><input type=submit value="Speichern"></td></tr>
	  </table></center>
	  </form>
	  <?php 
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;
  
    case "DOMENU":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Userdefinierte Men&uuml;-Links gespeichert</h3>";
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    mysql_db_query($DbName,
		               "update $DbFnc set murlname1 ='$sName1',
					                      url1      ='$sUrl1',
										  target1   ='$sTarget1',
										  active1   ='$sActive1',
					                      murlname2 ='$sName2',
					                      url2      ='$sUrl2',
										  target2   ='$sTarget2',
										  active2   ='$sActive2',
					                      murlname3 ='$sName3',
					                      url3      ='$sUrl3',
										  target3   ='$sTarget3',
										  active3   ='$sActive3',
					                      murlname4 ='$sName4',
					                      url4      ='$sUrl4',
										  target4   ='$sTarget4',
										  active4   ='$sActive4'"
		               ,$Db);
		mysql_close($Db);
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;
  
    case "MENU":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }

	  echo "<h3 align=center>Userdefinierte Men&uuml;-Links</h3>";  

	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    $DbQuery=mysql_db_query($DbName,"select * from $DbFnc",$Db);
		$DbRow=mysql_fetch_row($DbQuery);
		mysql_close($Db);
		$sName1=$DbRow[2]; $sUrl1=$DbRow[3]; $sTarget1=$DbRow[4]; $sActive1=$DbRow[5];
		$sName2=$DbRow[6]; $sUrl2=$DbRow[7]; $sTarget2=$DbRow[8]; $sActive2=$DbRow[9];
		$sName3=$DbRow[10];$sUrl3=$DbRow[11];$sTarget3=$DbRow[12];$sActive3=$DbRow[13];
		$sName4=$DbRow[14];$sUrl4=$DbRow[15];$sTarget4=$DbRow[16];$sActive4=$DbRow[17];
	
	    echo "<form action=\"admin.php\" method=post>";
		echo "<input type=hidden name=sAction value=\"DOMENU\">";
		EchoHiddenSession($sSessid,$sUser,$sSipaddr);
		echo "<center><table border=1 cellpadding=5>";
		echo "<tr>";
		echo "<td><b>Name des Links</b></td>";
		echo "<td><b>Url des Links</b></td>";
		echo "<td><b>Target</b></td>";
		echo "<td><b>Aktiv</b></td>";
		echo "</tr>";
		
		echo "<tr>";
		echo "<td><input type=text name=sName1 size=20 maxlengt=20 value=\"$sName1\"></td>";
		echo "<td><input type=text name=sUrl1 size=40 maxlengt=40 value=\"$sUrl1\"></td>";
		echo "<td><input type=text name=sTarget1 size=15 maxlengt=15 value=\"$sTarget1\"></td>";
		?>
		<td align=center><input type=checkbox name=sActive1 value="X" <?php if ($sActive1=="X") {echo "checked";}?>></td>
		<?php
		echo "</tr>";
		
		echo "<tr>";
		echo "<td><input type=text name=sName2 size=20 maxlengt=20 value=\"$sName2\"></td>";
		echo "<td><input type=text name=sUrl2 size=40 maxlengt=40 value=\"$sUrl2\"></td>";
		echo "<td><input type=text name=sTarget2 size=15 maxlengt=15 value=\"$sTarget2\"></td>";
		?>
		<td align=center><input type=checkbox name=sActive2 value="X" <?php if ($sActive2=="X") {echo "checked";}?>></td>
		<?php		
		echo "</tr>";
		
		echo "<tr>";
		echo "<td><input type=text name=sName3 size=20 maxlengt=20 value=\"$sName3\"></td>";
		echo "<td><input type=text name=sUrl3 size=40 maxlengt=40 value=\"$sUrl3\"></td>";
		echo "<td><input type=text name=sTarget3 size=15 maxlengt=15 value=\"$sTarget3\"></td>";
		?>
		<td align=center><input type=checkbox name=sActive3 value="X" <?php if ($sActive3=="X") {echo "checked";}?>></td>
		<?php
		echo "</tr>";
		
		echo "<tr>";
		echo "<td><input type=text name=sName4 size=20 maxlengt=20 value=\"$sName4\"></td>";
		echo "<td><input type=text name=sUrl4 size=40 maxlengt=40 value=\"$sUrl4\"></td>";
		echo "<td><input type=text name=sTarget4 size=15 maxlengt=15 value=\"$sTarget4\"></td>";
		?>
		<td align=center><input type=checkbox name=sActive4 value="X" <?php if ($sActive4=="X") {echo "checked";}?>></td>
		<?php
		echo "</tr>";
		
		echo "<tr><td colspan=4 align=center><input type=submit value=\"Speichern\"></td></tr>";
		
	    echo "</table></center></form>";	
  				
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;

    case "ARCHIVE2":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Archivierungslauf - BITTE UNBEDINGT WARTEN!!!</h3>";
	  $Result=ArchiveForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbFnc,$PostsInForum,$PostsInArc);
	  if ($Result=="ARC") {
        echo "<h3 align=center>Forenbeiträge wurden archiviert und aus dem Forum entfernt.</h3>";
	  }
	  else {
        echo "<h3 align=center>Ein Archivierungslauf war noch nicht nötig.</h3>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
      break;
  
    case "ARCHIVE":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Archivierungslauf - BITTE UNBEDINGT WARTEN!!!</h3>";
	  $Result=ArchiveForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbFnc,$PostsInForum,$PostsInArc);
	  if ($Result=="ARC") {
        echo "<h3 align=center>Forenbeiträge wurden archiviert und aus dem Forum entfernt.</h3>";
	  }
	  else {
        echo "<h3 align=center>Ein Archivierungslauf war noch nicht nötig.</h3>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
      break;
	    
    case "UPDREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  
	  $bProceed=CheckRegData($sRegName,$sRegPass,$sRegEmail,$sRegColor);
	  
	  if ($bProceed) {
        echo "<h3 align=center>Stammposter $sRegName ge&auml;ndert</h3>";
	    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
		  $sMd5Pass=md5($sRegPass);
		  mysql_db_query($DbName,
		                 "update $DbReg set passwd='$sMd5Pass',
											email ='$sRegEmail',
											color ='$sRegColor'
									  where name='$sRegName'"
		                 ,$Db);
		}
	  }
	  else {
	    echo "<br>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=REGSADMIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;
	  
    case "INSREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  
	  $bProceed=CheckRegData($sRegName,$sRegPass,$sRegEmail,$sRegColor);
	  
	  if ($bProceed) {
        echo "<h3 align=center>Stammposter $sRegName eingerichtet</h3>";
	    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
		  $sMd5Pass=md5($sRegPass);
		  mysql_db_query($DbName,
		                 "insert into $DbReg set name  ='$sRegName',
						                         passwd='$sMd5Pass',
												 email ='$sRegEmail',
												 color ='$sRegColor',
												 state ='A',
												 miscnt=0"
		                 ,$Db);
		}
	  }
	  else {
	    echo "<br>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=REGSADMIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;
  
    case "NEWREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Neuen Stammposter einrichten</h3>";
	  
	  ?>
	  <form action="admin.php" method=post>
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); 
	  if (isset($sRegName)) {
	    echo "<input type=hidden name=sAction value=\"UPDREG\">";
	    echo "<input type=hidden name=sRegName value=\"$sRegName\">";
	  }
	  else {
	    echo "<input type=hidden name=sAction value=\"INSREG\">";
	  }
	  ?>
	  <center><table>
	  <tr>
	  <td><b>Nickname:&nbsp;</b></td>
	  
	  <?php 
	  if (isset($sRegName)) {
	    echo "<td>$sRegName</td>";
	  }
	  else {
	    echo "<td><input type=text name=sRegName size=20 maxlength=20 value=\"$sRegName\"></td>";
	  }
	  ?>
	  </tr>
	  
	  <tr>
	  <td><b>Passwort:&nbsp;</b></td>
	  <td><input type=text name=sRegPass size=20 maxlength=20 value="<?php echo $sRegPass;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Email:&nbsp;</b></td>
	  <td><input type=text name=sRegEmail size=30 maxlength=30 value="<?php echo $sRegEmail;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Farbe:&nbsp;</b></td>
	  <td><input type=text name=sRegColor size=6 maxlength=6 value="<?php echo $sRegColor;?>"></td>
	  </tr>

      <tr>
	  <td colspan=2 align=center><input type=submit value="Speichern"></td>
	  </tr>
 	  
	  </table>
	  </center>
	  </form>
	  <?php 
	  
  	  echo "<center><a href=\"admin.php?sAction=REGSADMIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
      break;
	    
    case "ACTREG":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    $DbQuery=mysql_db_query($DbName,
		                        "select * from $DbReg
								    where name='$sRegName'"
		                        ,$Db);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  if ($DbRow[7]>=$MaxLoginFails) {
		    $sNewState=$DbRow[6];
			$iNewMisCnt=0;
            echo "<h3 align=center>Stammposter $sRegName wurde entsperrt</h3>";
		  }
		  elseif ($DbRow[6]=="A") {
		    $sNewState="D";
			$iNewMisCnt=$DbRow[7];
            echo "<h3 align=center>Stammposter $sRegName wurde deaktiviert</h3>";
		  }
		  else {
		    $sNewState="A";
			$iNewMisCnt=$DbRow[7];
            echo "<h3 align=center>Stammposter $sRegName wurde aktiviert</h3>";
		  }
		  mysql_db_query($DbName,
		                 "update $DbReg set state='$sNewState',
						                    miscnt=$iNewMisCnt
						         where name='$sRegName'"
		                 ,$Db);
		}
		
		mysql_close($Db);
	  }
  	  echo "<center><a href=\"admin.php?sAction=REGSADMIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
  
    case "REGSADMIN":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Stammposterverwaltung</h3>";
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    $DbQuery=mysql_db_query($DbName,
		                        "select * from $DbReg
								          where state='A'
										  or    state='D'  order by name"
		                        ,$Db);
		mysql_close($Db);
		
		echo "<center><table border=1>";
		echo "<tr>";
		echo "<td><b>Nickname</b></td>";
		echo "<td><b>Email</b></td>";
		echo "<td><b>Farbe</b></td>";
		echo "<td><b>Passw&ouml;rter</b></td>";
		echo "<td><b>Status</b></td>";
		echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>";
		echo "</tr>";
		while ($DbRow=mysql_fetch_row($DbQuery)) {
		  echo "<tr>";
		  $sRegName=$DbRow[0]; echo "<td>$sRegName</td>";
		  $sRegEmail=$DbRow[2]; echo "<td><a href=\"mailto:$sRegEmail\">$sRegEmail</a></td>";
		  $sRegColor=$DbRow[3]; echo "<td>$sRegColor</td>";
		  $sPassCnt=strval($DbRow[8]); echo "<td align=right>$sPassCnt</td>";
		  if ($DbRow[7]<$MaxLoginFails) {
 		    if ($DbRow[6]=="A") {$Value="aktiv";} else {$Value="inaktiv";}
		  }
		  else {
		    $Value="Loginsperre";
		  }
		  echo "<td>$Value</td>";
		  echo "<td><a href=\"admin.php?sAction=ACTREG&sRegName=$sRegName&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">aktiveren/deaktiviern/entsperren</a></td>";
		  echo "<td><a href=\"admin.php?sAction=NEWREG&sRegName=$sRegName&sRegPass=$sRegPass&sRegEmail=$sRegEmail&sRegColor=$sRegColor&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">bearbeiten</a></td>";
		  echo "</tr>";
		}
		echo "<tr><td align=center colspan=7><a href=\"admin.php?sAction=NEWREG&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">Neuer Stammposter</a></td></tr>";
		echo "</table></center><br>";
	  }
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
      break;
  
    case "DOTCLOSE":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))){
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Threads wurden geschlossen/ge&ouml;ffnet</h3>";

	  $iAnz=sizeof($aClose);
	  $bFirst=true;
	  $Db=NULL;
	  for ($i=0;$i<$iAnz;$i++) {
	    $sString=$aClose[$i];
		$sClose=$sString[0];
		$sNo=substr($sString,1,(strlen($sString)-1));
		
		CloseThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,
		            $sNo,$sClose,$bFirst,$Db);
	  }
	        	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
  
    case "DOADMSETUP":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aRamFile=ReadConfigFile();
	  echo "<h3 align=center>Funktionseinstellungen wurden gespeichert!</h3>";
	  
      $sKey="\$RegsActive";
	  $sValue=$sRegsActive;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
      $sKey="\$RegsSameCol";
	  $sValue=$sRegsSameCol;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
      $sKey="\$RegsOnly";
	  $sValue=$sRegsOnly;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  
      $sKey="\$PicLink";
	  $sValue=$sPicLink;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
      $sKey="\$HomeLink";
	  $sValue=$sHomeLink;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
      $sKey="\$Smilies";
	  $sValue=$sSmilies;
	  $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);

	  $sMinPostLen=strval(intval($sMinPostLen));
	  $aRamFile=SetRamValue("\$MinPostLen",$sMinPostLen,$aRamFile);	  
	  $aRamFile=SetRamValue("\$EmptyPostExt",$sEmptyPostExt,$aRamFile);	  
	  $aRamFile=SetRamValue("\$AllowEmailNote",$sAllowEmailNote,$aRamFile);	  

	  $sMinSubjectLen=intval($sMinSubjectLen);
	  if ($sMinSubjectLen==0) { $sMinSubjectLen=5; }
	  $sMinSubjectLen=strval($sMinSubjectLen);
	  $aRamFile=SetRamValue("\$MinSubjectLen",$sMinSubjectLen,$aRamFile);
	  
	  $sRecentLength=strval(intval($sRecentLength));
	  $aRamFile=SetRamValue("\$RecentLength",$sRecentLength,$aRamFile);
	  
	  $sThreadsPerPage=strval(intval($sThreadsPerPage));
	  if ($sThreadsPerPage<"1") {$sThreadsPerPage="20";}
	  $aRamFile=SetRamValue("\$ThreadsPerPage",$sThreadsPerPage,$aRamFile);
	  
	  if ($sParsimonyLayout!="X") {$sParsimonyLayout="-";}
	  $aRamFile=SetRamValue("\$ParsimonyLayout",$sParsimonyLayout,$aRamFile);
	  
      WriteConfigFile($aRamFile);  	  	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "ADMSETUP":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Funktionseinstellungen</h3>";
	  $aRamFile=ReadConfigFile();
	  
	  $sKey="\$RegsActive";  $sRegsActive=GetRamValue($sKey,$aRamFile);
	  $sKey="\$RegsSameCol"; $sRegsSameCol=GetRamValue($sKey,$aRamFile);
	  $sKey="\$RegsOnly";    $sRegsOnly=GetRamValue($sKey,$aRamFile);
	  
	  $sKey="\$PicLink";  $sPicLink=GetRamValue($sKey,$aRamFile);
	  $sKey="\$HomeLink"; $sHomeLink=GetRamValue($sKey,$aRamFile);
	  $sKey="\$Smilies";  $sSmilies=GetRamValue($sKey,$aRamFile);
	  
	  $sKey="\$ThreadsPerPage"; $sThreadsPerPage=GetRamValue($sKey,$aRamFile);
	  $sKey="\$ParsimonyLayout"; $sParsimonyLayout=GetRamValue($sKey,$aRamFile);
	  
	  $sAllowEmailNote = GetRamValue("\$AllowEmailNote",$aRamFile);
	  
	  $sMinPostLen=GetRamValue("\$MinPostLen",$aRamFile);
	  $sEmptyPostExt=GetRamValue("\$EmptyPostExt",$aRamFile);
	  $sMinSubjectLen=GetRamValue("\$MinSubjectLen",$aRamFile);	  
	  $sRecentLength=GetRamValue("\$RecentLength",$aRamFile);	  
	  ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOADMSETUP">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table border=1>
	  <tr>
	  <td><b>Funktion</b></td>
	  <td><b>Ein</b></td>
	  <td><b>Aus</b></td>
	  </tr>
	  
	  <tr>
	  <td><b>Stammpostereinstellungen</b></td><td>&nbsp;</td><td>&nbsp;</td>
	  </tr>
	  <tr>
	  <td>Stammposter aktiv</td>
	  <td><input type=radio name=sRegsActive value="X"<?php if ($sRegsActive=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sRegsActive value="-"<?php if ($sRegsActive=="-") {echo "checked";}?>></td>
	  </tr>
	  <tr>
	  <td>Alle Stammposter Defaultfarbe</td>
	  <td><input type=radio name=sRegsSameCol value="X"<?php if ($sRegsSameCol=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sRegsSameCol value="-"<?php if ($sRegsSameCol=="-") {echo "checked";}?>></td>
	  </tr>
	  <tr>
	  <td>Gastposter erlaubt</td>
	  <td><input type=radio name=sRegsOnly value="X"<?php if ($sRegsOnly=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sRegsOnly value="-"<?php if ($sRegsOnly=="-") {echo "checked";}?>></td>
	  </tr>
	  
	  <tr>
	  <td><b>Feldeinstellungen für Links</b></td><td>&nbsp;</td><td>&nbsp;</td>
	  </tr>
	  <tr>
	  <td>Feld f&uuml;r Bild Url</td>
	  <td><input type=radio name=sPicLink value="X"<?php if ($sPicLink=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sPicLink value="-"<?php if ($sPicLink=="-") {echo "checked";}?>></td>
	  </tr>
	  <tr>
	  <td>Felder f&uuml;r Link zu Homepage</td>
	  <td><input type=radio name=sHomeLink value="X"<?php if ($sHomeLink=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sHomeLink value="-"<?php if ($sHomeLink=="-") {echo "checked";}?>></td>
	  </tr>
	  
	  <tr>
	  <td><b>Eingabel&auml;ngen</b></td><td>&nbsp;</td><td>&nbsp;</td>
	  </tr>
	  <tr>
	  <td valign=top>Minimale Zeichenanzahl in einem Beitrag<br>und Kennung f&uuml;r leeren Beitrag</td>
	  <td valign=top><input type=text name=sMinPostLen size=3 maxlen=3 value="<?php echo $sMinPostLen;?>"></td>
	  <td valign=top><input type=text size=5 maxlength=9 name=sEmptyPostExt value="<?php echo $sEmptyPostExt;?>"></td>
	  </tr>
	 
	  <tr>
	  <td valign=top>Minimale Zeichenanzahl im Betreff</td>
	  <td valign=top><input type=text name=sMinSubjectLen size=2 maxlen=2 value="<?php echo $sMinSubjectLen;?>"></td>
	  <td valign=top colspan=2>&nbsp;</td>
	  </tr>
	  
	  <tr>
	  <td><b>Sonstiges</b></td><td>&nbsp;</td><td>&nbsp;</td>
	  </tr>
	  <tr>
	  <td>Smilies aktivieren</td>
	  <td><input type=radio name=sSmilies value="X"<?php if ($sSmilies=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sSmilies value="-"<?php if ($sSmilies=="-") {echo "checked";}?>></td>
	  </tr>	  
	  <tr>
	  
	  <tr>
	  <td>Emailbenachrichtigung f&uuml;r Stammposter</td>
	  <td><input type=radio name=sAllowEmailNote value="X"<?php if ($sAllowEmailNote=="X") {echo "checked";}?>></td>
	  <td><input type=radio name=sAllowEmailNote value="-"<?php if ($sAllowEmailNote=="-") {echo "checked";}?>></td>
	  </tr>	  
	  
	  <tr>
	  <td valign=top>Anzahl Postings in &quot;Neue Beitr&auml;ge&quot;:<br>0=alle, Fehleingabe setzt 0</td>
	  <td valign=top colspan=2><input type=text name=sRecentLength size=3 maxlength=3 value="<?php echo $sRecentLength;?>"></td>
	  </tr>	  

	  <tr>
	  <td valign=top>Anzahl Threads pro Seite:<br>Fehleingabe setzt 20</td>
	  <td valign=top colspan=2><input type=text name=sThreadsPerPage size=3 maxlength=3 value="<?php echo $sThreadsPerPage;?>"></td>
	  </tr>	  

	  <?php 
	    $sParsimonyChecked="";
		if ($sParsimonyLayout=="X") {$sParsimonyChecked="checked";}
	  ?>
	  <tr>
	  <td valign=top><a href="http://www.parsimony.net" target="parsimony">Parsimony</a> Forenlayout:</td>
	  <td valign=top colspan=2><input type="checkbox" name="sParsimonyLayout" value="X" <?php echo $sParsimonyChecked;?>></td>
	  </tr>	  
	  
	  <tr>
	  <td align=center colspan=3><input type=submit value="Speichern"></td>
	  </tr>
	  
	  </table></center>
	  </form>
	  <?php 	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
	case "DOTOPLEVEL":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $iFound=0; $iNoSrc=intval($sNoSrc);
	  $Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass);
	  while ($iFound==0) {
		$sQuery="select * from $DbTab where no=$iNoSrc";
		$DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  if ($DbRow[1]==0) {
		    $iFound=1;
		  }
		  else {
		    $iNoSrc=$DbRow[1];
		  }
		}
		else {
		  $iFound=-1;
		}
	  }
	  if ($iFound==-1) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Inkonsitenter Datenbankzugriff</font></h3>";
	  }
	  else {
	    if ($DbRow[11]=="T") {
		  $sNew="-";
		}
		else {
		  $sNew="T";
		}
	    $sQuery="update $DbTab set del='$sNew' where no=$iNoSrc"; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($sNew=="T") {
	      echo "<h3 align=center><font color=#000000>Thread des Beitrags wurde als Top-Thread an den Beginn des Forums gestellt.</font></h3>";
		}
		else {
	      echo "<h3 align=center><font color=#000000>Thread des Beitrags wurde als Top-Thread am Beginn des Forums aufgel&ouml;st.</font></h3>";
		}
	  }
	  mysql_close($Db);
	  echo "<center><a href=\"admin.php?sAction=CHGPOST&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck zur &Uuml;bersicht</b></a></center>";
	  break;
	  
    case "TCLOSE":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Threads schliessen/&ouml;ffnen</h3>";
	  EchoAdmPageBar($iActPage,$iMaxPages,"admin.php","TCLOSE",$sSessid,$sUser,$sSipaddr,$sInfo,$sLimit);
	  
	  $aDel[0]="T";$aDel[1]="-";
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
        $iCnt=0;								
	    for ($k=0;$k<2;$k++) {
		  $sSqlLimit=$sLimit;
		  if ($aDel[$k]=="T") {$sSqlLimit="";}
	      $DbQuery=mysql_db_query($DbName,
		                          "select * from $DbTab
								            where preno=0
										    and   del='".$aDel[$k]."'
										    order by no desc $sSqlLimit"
		                          ,$Db);
			
          while ($R=@mysql_fetch_object($DbQuery)) {
		    $aThread[$iCnt][0]=$R->no;
		    $aThread[$iCnt][1]=DecryptText($R->subject);
		    $aThread[$iCnt][2]=DecryptText($R->author);
		    $aThread[$iCnt][3]=$R->date;
		    $aThread[$iCnt][4]=$R->time;
		    $aThread[$iCnt][5]=$R->tclose;
		    $iCnt++;
		    // ReadThread($aThread,$R->no,$iCnt,$DbTab);
		  }	
		  @mysql_free_result($DbQuery);	
		}				
	    mysql_close($Db);
		?>
		<form action="admin.php" method=post>
		<input type=hidden name=sAction value="DOTCLOSE">
		<?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);
		echo "<center><table border=1>";
		echo "<tr>";
		echo "<td><b>Zu</b></td>";
		echo "<td><b>Auf</b></td>";
		echo "<td><b>Betreff</b></td>";
		echo "<td><b>Autor</b></td>";
		echo "<td><b>Datum</b></td>";
		echo "<td><b>Zeit</b></td>";
		echo "</tr>";
		$iCount=0;
		for ($i=0;$i<$iCnt;$i++) {
		  $iNo=$aThread[$i][0];
		  $sNo=strval($iNo);
		  echo "<tr>";
		  ?>
		  <td><input type=radio name=aClose[<?php echo $iCount;?>] value="X<?php echo $sNo;?>" <?php if ($aThread[$i][5]=="X") {echo "checked";}?>></td>
		  <td><input type=radio name=aClose[<?php echo $iCount;?>] value="-<?php echo $sNo;?>" <?php if ($aThread[$i][5]!="X") {echo "checked";}?>></td>
		  <td><?php echo $aThread[$i][1];?></td>
		  <td><?php echo $aThread[$i][2];?></td>
		  <td><?php echo $aThread[$i][3];?></td>
		  <td><?php echo $aThread[$i][4];?></td>
		  <?php 
		  echo "</tr>";
		  $iCount++;
		}
		?>
		<tr>
		<td align=center colspan=6><input type=submit value="Speichern"></td>
		</tr>
		<?php 
		echo "</table></center></form>";
	  }
	  	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "SELDEL":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Beitr&auml;ge l&ouml;schen</h3>";
      EchoAdmPageBar($iActPage,$iMaxPages,"admin.php","SELDEL",$sSessid,$sUser,$sSipaddr,$sInfo,$sLimit);
	  
	  $aDel[0]="T";$aDel[1]="-";
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
        $iCnt=0;								
	    for ($k=0;$k<2;$k++) {
		  $sSqlLimit=$sLimit;
		  if ($aDel[$k]=="T") {$sSqlLimit="";}
	      $DbQuery=mysql_db_query($DbName,
		                          "select * from $DbTab
								            where preno=0
										    and   del='".$aDel[$k]."'
										    order by no desc $sSqlLimit"
		                          ,$Db);
			
          while ($R=@mysql_fetch_object($DbQuery)) {
		    $aThread[$iCnt][0]=$R->no;
		    $aThread[$iCnt][1]=DecryptText($R->subject);
		    $aThread[$iCnt][2]=DecryptText($R->author);
		    $aThread[$iCnt][3]=$R->date;
		    $aThread[$iCnt][4]=$R->time;
		    $aThread[$iCnt][5]=$R->del;
		    $iCnt++;
		    ReadThread($aThread,$R->no,$iCnt,$DbTab);
		  }	
		  @mysql_free_result($DbQuery);	
		}				
	    mysql_close($Db);
		?>
		<form action="admin.php" name=frmSelDel method=post>
		<input type=hidden name=sAction value="DODEL">
		<?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);
		echo "<center><table border=1>";
		echo "<tr>";
		echo "<td><b>&nbsp;</b></td>";
		echo "<td><b>Thread</b></td>";
		echo "<td><b>Beitrag</b></td>";
		echo "<td><b>Betreff</b></td>";
		echo "<td><b>Autor</b></td>";
		echo "<td><b>Datum</b></td>";
		echo "<td><b>Zeit</b></td>";
		echo "</tr>";
		$iCount=0;
		for ($i=0;$i<$iCnt;$i++) {
		  $iNo=$aThread[$i][0];
		  $sNo=strval($iNo);
		  echo "<tr>";
		  ?>
		  <td><input type=radio name=aDel[<?php echo $iCount;?>] value="-<?php echo $sNo;?>" checked></td>
		  <td><input type=radio name=aDel[<?php echo $iCount;?>] value="T<?php echo $sNo;?>"></td>
		  <td><input type=radio name=aDel[<?php echo $iCount;?>] value="B<?php echo $sNo;?>"></td>
		  <?php
		  echo "<td align=\"left\" valign=\"top\">".$aThread[$i][1]."</td>
		        <td align=\"left\" valign=\"top\">".$aThread[$i][2]."</td>
				<td align=\"left\" valign=\"top\">".$aThread[$i][3]."</td>
				<td align=\"left\" valign=\"top\">".$aThread[$i][4]."</td>
		        </tr>\n";
		  $iCount++;
		}
		?>
		<tr>
		<td align=center colspan=7><input type=submit value="L&ouml;schen">&nbsp;&nbsp;&nbsp;<input type=reset value="Abbrechen"></td>
		</tr>
		<?php 
		echo "</table></center></form>";
	  }
	  ?>
	  <center><?php echo"<a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">";?><b>Zur&uuml;ck</b></a></center>
	  <?php
	  break;
  
    case "CHGPOST":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Beitr&auml;ge bearbeiten</h3>";
      EchoAdmPageBar($iActPage,$iMaxPages,"admin.php","CHGPOST",$sSessid,$sUser,$sSipaddr,$sInfo,$sLimit);

	  $aDel[0]="T";$aDel[1]="-";
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
        $iCnt=0;								
	    for ($k=0;$k<2;$k++) {
		  $sSqlLimit=$sLimit;
		  if ($aDel[$k]=="T") {$sSqlLimit="";}
	      $DbQuery=mysql_db_query($DbName,
		                          "select * from $DbTab
								            where preno=0
										    and   del='".$aDel[$k]."'
										    order by no desc $sSqlLimit"
		                          ,$Db);
			
          while ($R=@mysql_fetch_object($DbQuery)) {
		    $aThread[$iCnt][0]=$R->no;
		    $aThread[$iCnt][1]=DecryptText($R->subject);
		    $aThread[$iCnt][2]=DecryptText($R->author);
		    $aThread[$iCnt][3]=$R->date;
		    $aThread[$iCnt][4]=$R->time;
		    $aThread[$iCnt][5]=$R->del;
		    $iCnt++;
		    ReadThread($aThread,$R->no,$iCnt,$DbTab);
		  }	
		  @mysql_free_result($DbQuery);	
		}				
	    mysql_close($Db);
		echo "<center><table border=1>";
		echo "<tr>";
		echo "<td><b>Betreff</b></td>";
		echo "<td><b>Autor</b></td>";
		echo "<td><b>Datum</b></td>";
		echo "<td><b>Zeit</b></td>";
		echo "<td>&nbsp;</td>";
		if (CheckAdmRight("2",$LevelGiven)) {
		  echo "<td><b>Top-Thread</b></td>";		
		  echo "<td>&nbsp;</td>";		
		}
		echo "</tr>";
		$iCount=0;
		for ($i=0;$i<$iCnt;$i++) {
		  $iNo=$aThread[$i][0];
		  $sNoSrc=strval($iNo);
		  echo "<tr>\n";
		  echo "<td align=\"left\" valign=\"top\">".$aThread[$i][1]."</td>
		        <td align=\"left\" valign=\"top\">".$aThread[$i][2]."</td>
				<td align=\"left\" valign=\"top\">".$aThread[$i][3]."</td>
				<td align=\"left\" valign=\"top\">".$aThread[$i][4]."</td>
				<td align=\"left\"><a href=\"post.php?sNoSrc=$sNoSrc&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">Bearbeiten</a></td>
		        \n";
		  
		  if (CheckAdmRight("2",$LevelGiven)) {
		    if ($aThread[$i][5]=="T") {
			  echo "<td align=center>X</center>";
			}
			else {
			  echo "<td align=center>&nbsp;</center>";
			}
		    echo "<td><a href=\"admin.php?sAction=DOTOPLEVEL&sNoSrc=$sNoSrc&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">Top-Thread</a></td>";
		  }
		  echo "</tr>";
		  $iCount++;
		}
		echo "</table></center><br>\n";
	  }
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;

    case "DOSETUP":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))){
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aRamFile=ReadConfigFile();
	  $bProceed=true;
	  
	  if ($bProceed) {
	    if (!CheckColor($sBodyText)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Textfarbe (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sBodyBgcolor)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Hintergrungfarbe (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sBodyLink)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Farbe Links (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sBodyAlink)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Farbe aktiver Link (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sBodyVlink)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Besuchte Links (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sInfoColor)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Infomeldungen (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sErrColor)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Fehlermeldungen (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sRegColor)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Defaultfarbe Stammposter (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  if ($bProceed) {
	    if (!CheckColor($sAdminColor)) {
		  $bProceed=false;
	      echo "<h3 align=center><font color=#ff0000>Fehler: Farbe Forenmaster (Farbe bitte In RGB Hexcode)</font></h3>";
		}
	  }
	  
	  if ($bProceed) {
	    echo "<h3 align=center>Grundeinstellungen wurden gesichert!</h3>";
	  	$sKey="\$Title";
		$sValue=$sTitle;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyText";
		$sValue="#".$sBodyText;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyBgcolor";
		$sValue="#".$sBodyBgcolor;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyLink";
		$sValue="#".$sBodyLink;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyAlink";
		$sValue="#".$sBodyAlink;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyVlink";
		$sValue="#".$sBodyVlink;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$BodyBackground";
		$sValue=$sBodyBackground;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$InfoColor";
		$sValue="#".$sInfoColor;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$ErrColor";
		$sValue="#".$sErrColor;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$RegColor";
		$sValue="#".$sRegColor;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$AdminColor";
		$sValue="#".$sAdminColor;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$Banner";
		$sValue=$sBanner;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
	  	$sKey="\$Font";
		$sValue=$sFont;
	    $aRamFile=SetRamValue($sKey,$sValue,$aRamFile);
 	    WriteConfigFile($aRamFile);
	  }
   	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "SETUP":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Grundeinstellungen</h3>";
	  
	  $aRamFile=ReadConfigFile();
	  $sKey="\$Title";          $sTitle=GetRamValue($sKey,$aRamFile);
      $sKey="\$BodyText";       $sBodyText=GetRamValue($sKey,$aRamFile);       $sBodyText=substr($sBodyText,1,(strlen($sBodyText)-1));
      $sKey="\$BodyBgcolor";    $sBodyBgcolor=GetRamValue($sKey,$aRamFile);    $sBodyBgcolor=substr($sBodyBgcolor,1,(strlen($sBodyBgcolor)-1));
      $sKey="\$BodyLink";       $sBodyLink=GetRamValue($sKey,$aRamFile);       $sBodyLink=substr($sBodyLink,1,(strlen($sBodyLink)-1));
      $sKey="\$BodyAlink";      $sBodyAlink=GetRamValue($sKey,$aRamFile);      $sBodyAlink=substr($sBodyAlink,1,(strlen($sBodyAlink)-1));
      $sKey="\$BodyVlink";      $sBodyVlink=GetRamValue($sKey,$aRamFile);      $sBodyVlink=substr($sBodyVlink,1,(strlen($sBodyVlink)-1));
      $sKey="\$BodyBackground"; $sBodyBackground=GetRamValue($sKey,$aRamFile);
      $sKey="\$InfoColor";      $sInfoColor=GetRamValue($sKey,$aRamFile);      $sInfoColor=substr($sInfoColor,1,(strlen($sInfoColor)-1));
      $sKey="\$ErrColor";       $sErrColor=GetRamValue($sKey,$aRamFile);       $sErrColor=substr($sErrColor,1,(strlen($sErrColor)-1));
      $sKey="\$RegColor";       $sRegColor=GetRamValue($sKey,$aRamFile);       $sRegColor=substr($sRegColor,1,(strlen($sRegColor)-1));
      $sKey="\$AdminColor";     $sAdminColor=GetRamValue($sKey,$aRamFile);     $sAdminColor=substr($sAdminColor,1,(strlen($sAdminColor)-1));

      $sKey="\$Banner";         $sBanner=GetRamValue($sKey,$aRamFile);
      $sKey="\$Font";           $sFont=GetRamValue($sKey,$aRamFile);
      ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOSETUP">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table>
	  
	  <tr>
	  <td><b>Titel des Forums:&nbsp;</b></td>
	  <td><input type=text name=sTitle size=50 maxlength=50 value="<?php echo $sTitle;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Textfarbe:&nbsp;</b></td>
	  <td><input type=text name=sBodyText size=6 maxlength=6 value="<?php echo $sBodyText;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Hintergrundfarbe:&nbsp;</b></td>
	  <td><input type=text name=sBodyBgcolor size=6 maxlength=6 value="<?php echo $sBodyBgcolor;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Farbe von Links:&nbsp;</b></td>
	  <td><input type=text name=sBodyLink size=6 maxlength=6 value="<?php echo $sBodyLink;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Farbe des aktiven Links:&nbsp;</b></td>
	  <td><input type=text name=sBodyAlink size=6 maxlength=6 value="<?php echo $sBodyAlink;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Farbe besuchter Links:&nbsp;</b></td>
	  <td><input type=text name=sBodyVlink size=6 maxlength=6 value="<?php echo $sBodyVlink;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Hintergrundbild:&nbsp;</b></td>
	  <td><input type=text name=sBodyBackground size=30 maxlength=75 value="<?php echo $sBodyBackground;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Farbe f&uuml;r Infomeldungen:&nbsp;</b></td>
	  <td><input type=text name=sInfoColor size=6 maxlength=6 value="<?php echo $sInfoColor;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Farbe f&uuml;r Fehlermeldungen:&nbsp;</b></td>
	  <td><input type=text name=sErrColor size=6 maxlength=6 value="<?php echo $sErrColor;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Defaultfarbe f&uuml;r für Stammposter:&nbsp;</b></td>
	  <td><input type=text name=sRegColor size=6 maxlength=6 value="<?php echo $sRegColor;?>"></td>
	  </tr>
	  <tr>
	  <td><b>Farbe des Forenmasters:&nbsp;</b></td>
	  <td><input type=text name=sAdminColor size=6 maxlength=6 value="<?php echo $sAdminColor;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>URL eines Banners:&nbsp;</b></td>
	  <td><input type=text name=sBanner size=30 maxlength=50 value="<?php echo $sBanner;?>"></td>
	  </tr>
	  
	  <tr>
	  <td><b>Schriftart:&nbsp;</b></td>
	  <td><input type=text name=sFont size=20 maxlength=20 value="<?php echo $sFont;?>"></td>
	  </tr>
	  
	  <tr>
	  <td align=center colspan=2><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 
   	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOCHGPASS":
	  if (($sLoggedIn!="X") || (!(CheckAdmRight("PRIMARY",$LevelGiven)))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $bProceed=false;
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    $DbQuery=mysql_db_query($DbName,"select * from $DbAdm where kzactiv='X'
		                                                      and   level  ='PRIMARY'",$Db);
        if ($DbRow=mysql_fetch_row($DbQuery)) {
		  $sAdmPassOldMD5=md5($sAdmPassOld);
		  $bProceed2=true;
//		  echo $DbRow[0]."&nbsp;".$DbRow[1]."&nbsp;".$DbAdm;
		  if (($sAdmUsrOld!=$DbRow[0]) || ($sAdmPassOldMD5!=$DbRow[1])) {
		    echo "<h3 align=center><font color=#ff0000>Fehler: Alte Anmeldedaten falsch</font></h3>";
		    $bProceed2=false;
		  }
		  if ($bProceed2) {
	        if ($sAdmPass1New!=$sAdmPass2New) {
	          $bProceed2=false;
	          echo "<h3 align=center><font color=#ff0000>Fehler: Neue Passwörter stimmen nicht überein</font></h3>";
		    }
		  }
	      if ($bProceed2) {
	        if (strlen($sAdmUsrNew)<2) {
	          $bProceed2=false;
	          echo "<h3 align=center><font color=#ff0000>Fehler: Neuer Admin-User muss mindestens 2 Zeichen haben</font></h3>";
		    }
	      }
	      if ($bProceed2) {
	        if (strlen($sAdmPass1New)<5) {
	          $bProceed2=false;
	          echo "<h3 align=center><font color=#ff0000>Fehler: Neues Passwort muss mindestens 5 Zeichen haben</font></h3>";
		    }
	      }
		  if ($bProceed2) {
		    $bProceed=mysql_db_query($DbName,
			                         "delete from $DbAdm where userid='$sAdmUsrOld'"
			                         ,$Db);
            if ($bProceed)  {		
		      $sNewPassMD5=md5($sAdmPass1New);
			  $sEmail=$DbRow[2];	
			  $sDate=$DbRow[3];
			  $sTime=$DbRow[4];			 
		      $bProceed=mysql_db_query($DbName,
			                           "insert into $DbAdm set userid='$sAdmUsrNew',
									                           passwd='$sNewPassMD5',
															   email ='$sEmail',
															   sinced='$sDate',
										   					   sincet='$sTime',
                                                               level ='PRIMARY'"
			                           ,$Db);
		    }
		  }
		  mysql_close($Db);
		}												  
	  }	  
	  if ($bProceed) {
	    echo "<h3 align=center><font color=$InfoColor>Die Anmeldedaten wurden ge&auml;ndert!</font></h3>";
	  }
	  else {
	    if ($bProceed2) {
	      echo "<h3 align=center><font color=#ff0000><b>Fehler:</b> Die Anmeldedaten konnten nicht aktualisiert werden!</font></h3>";
		}
	  }
	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "CHGPASS":
	  if (($sLoggedIn!="X") || (!(CheckAdmRight("PRIMARY",$LevelGiven)))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Admin-User und Passwort &auml;ndern</h3>";
	 
	  ?>
	  <form action="admin.php" method=post>
	  <input type=hidden name=sAction value="DOCHGPASS">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table>
	  <tr>
	  <td align=center colspan=2><b>Altes Login</b></td>
	  </tr>
	  <tr>
	  <td><b>Admin-User:&nbsp;</b></td>
	  <td><input type=text name=sAdmUsrOld size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Passwort:&nbsp;</b></td>
	  <td><input type=password name=sAdmPassOld size=20 maxlength=20></td>
	  </tr>
	  
	  <tr>
	  <td align=center colspan=2>&nbsp;</td>
	  </tr>
	  <tr>
	  <td align=center colspan=2><b>Neues Login</b></td>
	  </tr>
	  <tr>
	  <td><b>Admin-User:&nbsp;</b></td>
	  <td><input type=text name=sAdmUsrNew size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Passwort:&nbsp;</b></td>
	  <td><input type=password name=sAdmPass1New size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Passwortwiederholung:&nbsp;</b></td>
	  <td><input type=password name=sAdmPass2New size=20 maxlength=20></td>
	  </tr>	  
	  
	  <tr>
	  <td><input type=submit value="&Auml;ndern"></td>
	  <td><input type=reset value="L&ouml;schen"></td>
	  </tr>
	  </table></center>
	  </form>
	  <center><?php echo"<a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">";?><b>Zur&uuml;ck</b></a></center>	  
	  <?php 
	  	  
	  break;
  
    case "DODEL":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  
	  $bFirst=true; $Db=NULL;
	  DeletePostings($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$aDel,$bFirst,$Db);
	  echo "<h3 align=center>Beitr&auml;ge wurden gel&ouml;scht</h3>";
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
  
    case "LOGIN":
	  // Hier den IP-Lock einbauen
	  if ($sLoggedIn!="X") {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  ?>
	  <h3 align=center>Funktionsübersicht</h3>
	  <?php 
	  if (CheckAdmRight("2",$LevelGiven)) {
	    $sQuery="select * from $DbReg where state=2";
	    $Db=Null; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	    if (mysql_fetch_row($DbQuery)) {
	      echo "<center><font color=#0000ff><b>Es liegen <a href=\"admin.php?sAction=CONFIRMREG&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\">Stammposterantr&auml;ge zur Begutachtung</a> vor!</b></font></center><br>";
	    }
	  }
	  
	  if (CheckAdmRight("1",$LevelGiven)) {
	    $sQuery="select count(*) from $DbTab where del='M'";
		$Db=Null; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	    if ($DbRow=mysql_fetch_row($DbQuery)) {
		  if ($DbRow[0]>0) {
	        echo "<center><font color=#0000ff><b>Es liegen <a href=\"admin2.php?sAction=FREEPOSTS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\">Beitr&auml;ge zur Begutachtung</a> vor!</b></font></center><br>";
		  }
	    }
	  }
	  
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  if (file_exists("setup.php")) {
	    echo "<font color=#ff0000><b>Achtung Sicherheitsl&uuml;cke:</b> Das Script <i>setup.php</i> ist in Ihrer Installation, Sie sollten es unbedingt entfernen!</font><br>";
	  }
	  if (file_exists("reset_passwd.php")) {
	    echo "<font color=#ff0000><b>Achtung schwere Sicherheitsl&uuml;cke:</b> Das Script <i>reset_passwd.php</i> ist in Ihrer Installation, Sie sollten es unbedingt sofort entfernen!</font><br>";
	  }
	  $hndDir=opendir(".");
	  while ($sFile=readdir($hndDir)) {
	    if ((substr($sFile,0,7)=="install") || (substr($sFile,0,7)=="upgrade")) {
	      echo "<font color=#ff0000><b>Achtung Sicherheitsl&uuml;cke:</b> Das Script <i>$sFile</i> ist in Ihrer Installation, Sie sollten es unbedingt entfernen!</font><br>";
		}
	  }
	  clearstatcache();
	  }
	  ?>	  
	  <center><table>
	  
	  <?php 
	  if (CheckAdmRight("1",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><b>Beitragsadministration</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=TCLOSE&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Threads schliessen/&ouml;ffnen</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=CHGPOST&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Beitr&auml;ge bearbeiten</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=SELDEL&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Beitr&auml;ge l&ouml;schen</a></td>
	  </tr>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <?php 
	  }
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><b>Layout</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=SETUP&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Grundeinstellungen</a></td>
	  </tr>
	  <tr>
	  </tr>
	  <td><a href="admin.php?sAction=TITLE&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Seitengestaltung</a></td>
	  </tr>	  
	  <tr>
	  <td><a href="admin.php?sAction=MENU&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Userdefinierte Men&uuml;-Links</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin2.php?sAction=FREEMENU&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Freie Men&uuml;gestaltung</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=THREAD&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Threadlayout</a></td>
	  </tr>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <?php 
	  }
	  
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><b>Einstellungen</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=ADMSETUP&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Funktionseinstellungen 1</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin2.php?sAction=ADMSETUP2&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Funktionseinstellungen 2</a></td>
	  </tr>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <?php 
	  }
	  
	  if (CheckAdmRight("2",$LevelGiven)) {
      ?>	  
	  <tr>
	  <td><b>Stammposter</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=REGSADMIN&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Stammposterverwaltung</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin2.php?sAction=REGSMAIL&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Email an Stammposter</a></td>
	  </tr>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>	  
	  <?php 
	  }
	  if (CheckAdmRight("3",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><b>Forenschutz</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=BADWORDS&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Badwords Liste</a></td>
	  </tr>
	  <?php
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><a href="admin.php?sAction=ANTISPAM&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Spamschutz</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin2.php?sAction=LOGINPROTEC&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Missbrauchschutz Login</a></td>
	  </tr>
	  <?php 
	  }
	  ?>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <?php 
	  }
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><b>Archivierung</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=ARCHIVE&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Archivierungslauf</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=LENGTH&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Archiv- und Forenl&auml;nge</a></td>
	  </tr>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <?php 
	  }
	  
	  if (CheckAdmRight("ADMALL",$LevelGiven)) {
	  ?>
	  
	  <tr>
	  <td><b>Administratoren</b></td>
	  </tr>
	  <tr>
	  <td><a href="admin2.php?sAction=ADMINADMS&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Administratorenverwaltung</a></td>
	  </tr>
	  <?php 
	  if (CheckAdmRight("PRIMARY",$LevelGiven)) {
	  ?>
	  <tr>
	  <td><a href="admin2.php?sAction=TRANSFER&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Forenbesitz &uuml;bergeben</a></td>
	  </tr>
	  <?php
	  }
	  ?>
	  <tr>
	  <td>&nbsp;</td>
	  </tr>	  
      <?php 
	  }	  

	  ?>
  
	  <tr>
	  <td><b>Sonstiges</b></td>
	  </tr>
	  
	  <?php 
	  if (CheckAdmRight("PRIMARY",$LevelGiven)) {
	  if (file_exists("versionsrv.php")) {
	    ?>
	    <tr>
	    <td><a href="versionsrv.php?sAction=INI&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Versionsservice</a></td>
	    </tr>
		<?php 
	  }
	  clearstatcache();
	  ?>
	  <tr>
	  <td><a href="admin.php?sAction=EMAIL&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Emailbenachrichtigung</a></td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=CHGPASS&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>">Admin-User und Passwort &auml;ndern</a></td>
	  </tr>
	  <?php 
	  }
	  ?>
	  
	  <tr>
	  <td><a href="index.php" target=_blank>Forum</a></td>
	  </tr>
	  
	  <tr>
	  <td>&nbsp;</td>
	  </tr>
	  <tr>
	  <td><a href="admin.php?sAction=LOGOUT&sSessid=<?php echo $sSessid;?>&sUser=<?php echo $sUser;?>&sSipaddr=<?php echo $sSipaddr;?>&sInfo=<?php echo $sInfo;?>"><b>Logout</b></a></td>
	  </tr>
	  </table></center>
	  <?php
	  break;
  }
  echo "</td></tr></table></center></body></html>";  
?>
