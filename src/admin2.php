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
  
  if (!CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime)) {
    header("location: login.php");
  }
  $sLoggedIn="X";
    
  if (!isset($sAction)) {
    $sAction="LOGIN";
  }

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
    case "DELDB":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Datenbank s&auml;ubern</h3>";
	  echo "<p align=justify><b><font color=#ff0000>Achtung:</font> Hier l&ouml;schen Sie unwiderbringlich 
	  Daten!!!</b><br>
	  <br>
	  Diese Funktion l&ouml;scht alle Postings aus der Datenbank, die gel&ouml;scht sind. Hintergrund ist, 
	  dass beim L&ouml;schen von Beitr&auml;gen diese nur eine Gel&ouml;schtkennzeichnung bekommen, physikalisch 
	  sind diese Daten aber noch vorhanden und werden auch bei einem Archivierungslauf archiviert. Mit dieser 
	  Funktion k&ouml;nnen Sie als gel&ouml;scht gekennzeichnete Daten wirklich physikalisch l&ouml;schen. Danach 
	  ist es nicht mehr m&ouml;glich durch Eingriffe in die Datenbank diese Beitr&auml;ge wieder sichtbar zu 
	  machen, auch f&uuml;r eine Archivierung sind diese Daten dann nat&uuml;rlich nicht mehr zug&auml;nglich. 
	  Diese Funktion ist unter Umst&auml;nden dann sinnvoll, wenn sie viele Beitr&auml;ge l&ouml;schen, um die 
	  Datenbankzugriffe wieder zu beschleunigen (weniger Daten = reduzierte Zugriffszeiten), aber eben zum 
	  Preis eines echten Datenverlustes.</p>";
	  ?>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DODELDB">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><input type=submit value="L&ouml;schen"></center>
	  </form>
	  <?php 
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOREGSMAIL":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	    $sQuery="select * from $DbReg where state='A'";
	    $DbQuery1=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	    $sQuery="select * from $DbAdm where kzactiv='X' and level<>'1'";
	    $DbQuery2=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		mysql_close($Db);
  	    if (CheckEmail($SenderMail)) {
          $sHeader="From: \"Forum $Title\" <$SenderMail>";
	    }
	    else {
          $sHeader="From: \"Forum $Title\" <Keine-Antwortadresse@>";
	    }
		while ($DbRow1=mysql_fetch_row($DbQuery1)) {
		  mail($DbRow1[2],$sRegSubject,$sRegMail,$sHeader);
		}
		while ($DbRow2=mysql_fetch_row($DbQuery2)) {
		  mail($DbRow2[2],$sRegSubject,$sRegMail,$sHeader);
		}
		
	  }
      echo "<h3 align=center><font color=#0000ff>Email an Stammposter wurde versendet</font></h3>";	  
  	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;

    case "REGSMAIL":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("2",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Email an Stammposter</h3>";
	  ?>
	  <p align=justify>Hier k&ouml;nnen Sie eine einfache Textemail an alle aktiven Stammposter (auch aufgrund 
	  des Login-Mi&szlig;brauchschutzes gesperrte Stammposter) versenden. Alle aktiven Administratoren ab Level 2 
	  (Stammposterverwaltung), bekommen die Email ebenfalls. Bitte verwenden die Funktion zur&uuml;ckhaltend und 
	  nur, um wirklich wichtige Informationen zu senden, da die Stammposter das Empfangen dieser Emails mit 
	  Mitteln des Forums nicht verhindern k&ouml;nnen.</p>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DOREGSMAIL">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table border=0 cellspacing=0 cellpadding=3>
	  <tr>
	  <td align=left><b>Betreff:</b></td>
	  <td align=left><input type=text name=sRegSubject size=90 maxlength=100></td>
	  </tr>

	  <td align=left colspan=2><textarea name=sRegMail cols=85 rows=10></textarea></td>
	  
	  <tr>
	  <td align=center colspan=2><input type=submit value="Email senden"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "FREEPOSTS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Beitragsmoderation</h3>";
	  
	  $sQuery="select * from $DbTab where del='M' order by date, time";
	  $Db=NULL;$DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  echo "<div align=center><table border=1 cellspacing=0 cellpadding=3>";
	  echo "<tr>";
	  echo "<td><b>Nr.</b></td>";
	  echo "<td><b>Betreff</b></td>";
	  echo "<td><b>Autor</b></td>";
	  echo "<td><b>Zeit</b></td>";
	  echo "<tr>";
	  
	  while ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sNo      = stripcslashes($DbRow[0]);
		$sSubject = DecryptText(stripcslashes($DbRow[10]));
		$sAuthor  = DecryptText(stripcslashes($DbRow[2]));
		$sReg     = $DbRow[4];
		$Db=NULL; $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$sReg,$RegColor,$AdminColor,$RegsSameCol);
		
		$sDate    = $DbRow[5];
		$sTime    = $DbRow[6];
		echo "<tr>";
		echo "<td>$sNo</td>";
		echo "<td><a href=\"freepost.php?sNo=$sNo&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">$sSubject</a></td>";
		echo "<td>$sAuthor</td>";
		echo "<td>$sDate - $sTime</td>";
		echo "</tr>";
	  }
	  
	  echo "</table></div><br>";
	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOADMSETUP2":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aRamFile=ReadConfigFile();
	  echo "<h3 align=center>Funktionseinstellungen wurden gespeichert!</h3>";

      $sValue="-";
	  if (isset($sLoginRequired)) {
	    if (($sLoginRequired=="X") && ($RegsActive=="X")) {
          $sValue="X";
		}
	  }
	  $aRamFile=SetRamValue("\$LoginRequired",$sValue,$aRamFile);
	  
      $sValue="-";
	  if (isset($sQuotePostOnAnswer)) {
	    if ($sQuotePostOnAnswer=="X") {
          $sValue="X";
		}
	  }  
	  $aRamFile=SetRamValue("\$QuotePostOnAnswer",$sValue,$aRamFile);
	  
      $sValue="-";
	  if (isset($sDisableAutoSubject)) {
	    if ($sDisableAutoSubject=="X") {
          $sValue="X";
		}
	  }  
	  $aRamFile=SetRamValue("\$DisableAutoSubject",$sValue,$aRamFile);
	  
      $sValue="-";
	  if (isset($sModerateGuests)) {
	    if ($sModerateGuests=="X") {
          $sValue="X";
		}
	  }  
	  $aRamFile=SetRamValue("\$ModerateGuests",$sValue,$aRamFile);
	  
      $sValue="-";
	  if (isset($sModerateRegulars)) {
	    if ($sModerateRegulars=="X") {
          $sValue="X";
		}
	  }  
	  $aRamFile=SetRamValue("\$ModerateRegulars",$sValue,$aRamFile);
	  
      $sValue="-";
	  if (isset($sEnablePersonalNewInfo)) {
	    if ($sEnablePersonalNewInfo=="X") {
          $sValue="X";
		}
	  }  
	  $aRamFile=SetRamValue("\$EnablePersonalNewInfo",$sValue,$aRamFile);
	  
	  if (CheckEmail($sSenderMail)) {
	    $aRamFile=SetRamValue("\$SenderMail",$sSenderMail,$aRamFile);
	  }
	  else {
	    $aRamFile=SetRamValue("\$SenderMail","-",$aRamFile);
	  }
	  
      WriteConfigFile($aRamFile); 
	  
	  if ($hndFile=fopen("cfg/registertxt.txt","w+")) {
	    $sRegText=stripcslashes($sRegText);
		fwrite($hndFile,$sRegText);
	    fclose($hndFile);
		clearstatcache();
	  }
	   	  	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "ADMSETUP2":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Funktionseinstellungen</h3>";
//	  $aRamFile=ReadConfigFile();
	  
/*	  $sLoginRequired     =GetRamValue("\$LoginRequired",$aRamFile);
      $sQuotePostOnAnswer =GetRamValue("\$QuotePostOnAnswer",$aRamFile);
      $sDisableAutoSubject=GetRamValue("\$DisableAutoSubject",$aRamFile);*/

	  $sLoginRequired     =$LoginRequired;
      $sQuotePostOnAnswer =$QuotePostOnAnswer;
      $sDisableAutoSubject=$DisableAutoSubject;
	  $sSenderMail        =$SenderMail;
      $sModerateRegulars = $ModerateRegulars;
      $sModerateGuests   = $ModerateGuests;
	  $sEnablePersonalNewInfo = $EnablePersonalNewInfo;

	  ?>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DOADMSETUP2">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
	  <center><table border=1 cellspacing=0 cellpadding=5>
	  <tr>
	  <td align=left valign=top><b>Funktion</b></td>
	  <td align=left valign=top><b>Ein</b></td>
	  </tr>
	  
	  <tr>
	  <td align=left valign=top><b>Gesch&uuml;tzes Forum:</b><br>
	  Diese Einstellung wird nur aktiv, wenn Sie auch die<br>
	  Stammposterfunktion aktiviert haben. Sie bewirkt,<br>
	  dass auch der Lesezugriff auf Stammposter begrenzt<br>
	  wird. <b>Achtung:</b> Diese Funktion ben&ouml;tigt Cookies.</td>
	  <td align=left valign=top><input type=checkbox name=sLoginRequired value="X"<?php if ($sLoginRequired=="X") {echo "checked";}?>></td>
	  </tr>
	  
	  <tr>
	  <td align=left valign=top><b>Beim Antworten zitieren:</b><br>
	  Beim Antworten wird er ursprüngliche Beitrag im<br>
	  Textfeld des Beitragsschreibens zitiert.</td>
	  <td align=left valign=top><input type=checkbox name=sQuotePostOnAnswer value="X"<?php if ($sQuotePostOnAnswer=="X") {echo "checked";}?>></td>
	  </tr>
	  
	  <tr>
	  <td align=left valign=top><b>Kein automatischer Betreff:</b><br>
	  Beim Antworten wird kein automatischer Betreff der<br>
	  Form &quot;Re: &lt;Original Betreff&gt;&quot; gebildet.</td>
	  <td align=left valign=top><input type=checkbox name=sDisableAutoSubject value="X"<?php if ($sDisableAutoSubject=="X") {echo "checked";}?>></td>
	  </tr>	  	  
	  
	  <tr>
	  <td align=left valign=top><b>Absender Email:</b><br>
	  Vom System verschickte Emails bekommen als Alias<br>
	  automatisch den Titel <i><?php echo $Title;?></i><br>
	  Ihres Forums, aber keine eigentliche Emailadresse.<br>
	  Wenn Sie hier eine plausbible Emailadresse eintragen,<br>
	  wird diese die Absenderadresse f&uuml;r Sytsemnachrichten.</td>
	  <td align=left valign=top><input type=text name=sSenderMail size=20 maxlength=30 value="<?php echo $sSenderMail?>"></td>
	  </tr>	  	  
	  
	  <tr>
	  <td colspan=2>Text für die Seite der Stammposterregistrierung.<br>(Leer=Defaultext)</td>
	  </tr>
	  <tr>
	  <?php 
	    if (!($sRegText=ReadMenuFile("REGISTERTEXT"))) {
		  $sRegText="";
		}
	  ?>
	  <td colspan=2><textarea name=sRegText cols=85 rows=10><?php echo $sRegText;?></textarea></td>
	  </tr>
	  
	  <tr>
	  <td align=left valign=top><b>Gastbeitr&auml;ge moderieren:</b><br>
	  Beitr&auml;ge von G&auml;sten erscheinen nicht sofort, sondern<br>
	  m&uuml;ssen erst durch einen Administrator gepr&uuml;ft und<br>
	  freigeschaltet werden.</td>
	  <td valign=top><input type=checkbox name=sModerateGuests value="X"<?php if ($sModerateGuests=="X") {echo "checked";}?>></td>
	  </tr>	  	  
	  
	  <tr>
	  <td align=left valign=top><b>Stammposter Moderieren:</b><br>
	  Beitr&auml;ge von Stammpostern erscheinen nicht sofort, sondern<br>
	  m&uuml;ssen erst durch einen Administrator gepr&uuml;ft und<br>
	  freigeschaltet werden.</td>
	  <td align=left valign=top valign=top><input type=checkbox name=sModerateRegulars value="X"<?php if ($sModerateRegulars=="X") {echo "checked";}?>></td>
	  </tr>	  	  
	  
	  <tr>
	  <td align=left valign=top valign=top><b>Symbolik neuer Thread/Beitrag:</b><br>
	  Aktivierung dieser Funktion erlaubt es Ihren Besuchern anhand von<br>
	  Symbolen zu erkennen, ob ein Thread/Beitrag von Ihnen schon gelesen wurde<br>
	  oder nicht. Diese Funktion basiert auf Cookies, die Aktivierung n&uuml;tzt<br>
	  also keinem Besucher etwas, der keine Cookies annimmt.</td>
	  <td align=left valign=top valign=top><input type=checkbox name=sEnablePersonalNewInfo value="X"<?php if ($sEnablePersonalNewInfo=="X") {echo "checked";}?>></td>
	  </tr>	  	  
	  
	  <tr>
	  <td align=center colspan=2><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
	  
    case "DOFREEMENU":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  if ($hndFile=fopen("cfg/index_men.txt","w+")) {
	    $sIndexMen=stripcslashes($sIndexMen);
	    $sIndexMen=str_replace("  ","&nbsp;&nbsp;",$sIndexMen);
	    fwrite($hndFile,$sIndexMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/recent_men.txt","w+")) {
	    $sRecentMen=stripcslashes($sRecentMen);
	    $sRecentMen=str_replace("  ","&nbsp;&nbsp;",$sRecentMen);
	    fwrite($hndFile,$sRecentMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/search_men.txt","w+")) {
	    $sSearchMen=stripcslashes($sSearchMen);
	    $sSearchMen=str_replace("  ","&nbsp;&nbsp;",$sSearchMen);
	    fwrite($hndFile,$sSearchMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/format_men.txt","w+")) {
	    $sFormatMen=stripcslashes($sFormatMen);
	    $sFormatMen=str_replace("  ","&nbsp;&nbsp;",$sFormatMen);
	    fwrite($hndFile,$sFormatMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/post_men.txt","w+")) {
	    $sPostMen=stripcslashes($sPostMen);
	    $sPostMen=str_replace("  ","&nbsp;&nbsp;",$sPostMen);
	    fwrite($hndFile,$sPostMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/show_men.txt","w+")) {
	    $sShowMen=stripcslashes($sShowMen);
	    $sShowMen=str_replace("  ","&nbsp;&nbsp;",$sShowMen);
	    fwrite($hndFile,$sShowMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/archive_men.txt","w+")) {
	    $sArchiveMen=stripcslashes($sArchiveMen);
	    $sArchiveMen=str_replace("  ","&nbsp;&nbsp;",$sArchiveMen);
	    fwrite($hndFile,$sArchiveMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/arcpost_men.txt","w+")) {
	    $sArcPostMen=stripcslashes($sArcPostMen);
	    $sArcPostMen=str_replace("  ","&nbsp;&nbsp;",$sArcPostMen);
	    fwrite($hndFile,$sArcPostMen);
	    fclose($hndFile);
	  }
	  if ($hndFile=fopen("cfg/arcshow_men.txt","w+")) {
	    $sArcShowMen=stripcslashes($sArcShowMen);
	    $sArcShowMen=str_replace("  ","&nbsp;&nbsp;",$sArcShowMen);
	    fwrite($hndFile,$sArcShowMen);
	    fclose($hndFile);
	  }
	  
      echo "<h3 align=center><font color=#0000ff>Einstellungen der Men&uuml;gestaltung gespeichert</font></h3>";
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
  
    case "FREEMENU":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
      echo "<h3 align=center>Freie Men&uuml;gestaltung</h3>";
	  echo "<p align=justify>Sie k&ouml;nnen hier die Men&uuml;s des Forums frei mit HTML-Code gestalten, 
	  d.h. Sie k&ouml;nnen beliebig viele eigene Links hinzufügen. Für Forenfunktinalit&auml;ten stehen 
	  spezielle <a href=\"menutags_hlp.php\" target=_blank><b>Tags</b></a> (<a href=\"menutags_hlp.php\" target=_blank><b>Hilfe</b></a>) zur Verf&uuml;gung, die Sie auch unbedingt verwenden sollten, zum einen, um zu weiteren 
	  Versionen kompatibel bleiben zu k&ouml;nnen und zum anderen, weil Sie sich bei Verwendung dieser Tags nicht 
	  selber um die richtige &Uuml;bergabe von Parametern k&uuml;mmen m&uuml;ssen.</p>";
	  $sIndexMen  =ReadMenuFile("MAIN");
	  $sRecentMen =ReadMenuFile("RECENT");
	  $sSearchMen =ReadMenuFile("SEARCH");
	  $sFormatMen =ReadMenuFile("FORMAT");
	  $sPostMen   =ReadMenuFile("POST");
	  $sShowMen   =ReadMenuFile("SHOW");
	  $sArchiveMen=ReadMenuFile("ARCHIVE");
	  $sArcPostMen=ReadMenuFile("ARCPOST");
	  $sArcShowMen=ReadMenuFile("ARCSHOW");
	  ?>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DOFREEMENU">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);?>
	  <center><table>
	  <tr>
	  <td><b>Indexseite</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sIndexMen cols=85 rows=10><?php echo $sIndexMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Neueste Beitr&auml;ge</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sRecentMen cols=85 rows=10><?php echo $sRecentMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Suchen</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sSearchMen cols=85 rows=10><?php echo $sSearchMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Textformatierung</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sFormatMen cols=85 rows=10><?php echo $sFormatMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Posten</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sPostMen cols=85 rows=10><?php echo $sPostMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Anzeige Beitrag</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sShowMen cols=85 rows=10><?php echo $sShowMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Archiv</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sArchiveMen cols=85 rows=10><?php echo $sArchiveMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Antworten im Archiv</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sArcPostMen cols=85 rows=10><?php echo $sArcPostMen;?></textarea></td>
	  </tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr>
	  <td><b>Anzeige Beitrag im Archiv</b></td>
	  </tr>
	  <tr>
	  <td><textarea name=sArcShowMen cols=85 rows=10><?php echo $sArcShowMen;?></textarea></td>
	  </tr>

	  <tr>
	  <td align=center><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
  
    case "DOLOGINPROTEC":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }

      echo "<h3 align=center><font color=#0000ff>Einstellungen Missbrauchschutz Login gespeichert</font></h3>";
	  
	  $aConfig=ReadConfigFile();
	  
	  $iMaxLoginFails=intval($sMaxLoginFails);
	  if (($iMaxLoginFails<3) || ($iMaxLoginFails>100)) {
	    $sMaxLoginFails=3;
	  }
	  
	  $iLockTimeFail=intval($sLockTimeFail);
	  if ($iMaxLoginFails>300) {
	    $sMaxLoginFails=5;
	  }
	  
	  if ($sUseLoginLock!="X") {
	    $sUseLoginLock="-";
	  }
	  
	  $aConfig=SetRamValue("\$MaxLoginFails",$sMaxLoginFails,$aConfig);
	  $aConfig=SetRamValue("\$LockTimeFail",$sLockTimeFail,$aConfig);
	  $aConfig=SetRamValue("\$UseLoginLock",$sUseLoginLock,$aConfig);
	  
	  WriteConfigFile($aConfig);
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "LOGINPROTEC":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aConfig=ReadConfigFile();
	  $sMaxLoginFails=GetRamValue("\$MaxLoginFails",$aConfig);
	  $sLockTimeFail=GetRamValue("\$LockTimeFail",$aConfig);
	  $sUseLoginLock=GetRamValue("\$UseLoginLock",$aConfig);
	  echo "<h3 align=center>Missbrauchschutz Login</h3>";
	  ?>
	  <p align=justify>Es gibt Assoziale, die sich einen Scherz daraus machen, Stammposterpassw&ouml;rter 
	  absichtlich falsch einzugeben, so dass die Stammposter immer wieder gesperrt werden. Hier k&ouml;nnen Sie die
	  Einstellungen treffen, dass nach einer Fehleingabe das Script f&uuml;r eine bestimmte Zeit blockiert,
	  dann machen solche "Scherze" keinen Spa&szlig; mehr.</p>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DOLOGINPROTEC">
	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);?>
	  <center><table cellpadding=3 border=1>
	  <tr>
	  <td><b>Funktion</b></td>
	  <td><b>Einstellung</b></td>
	  </tr>
	  
	  <tr>
	  <td valign=top><p align=justify>Anzahl Fehllogins bis Accountsperrung:<br>
	  (3-100, Fehleingaben setzen 3)</p></td>
	  <td valign=top><input type=text name=sMaxLoginFails size=3 maxlength=2 value="<?php echo $sMaxLoginFails;?>"></td>
	  </tr>
	  
	  <tr>
	  <td valign=top><p align=justify>Anzahl Sekunden Scripsperre nach Fehllogin:<br>
	  (0-300, Fehleingaben setzen 5)</p></td>
	  <td valign=top><input type=text name=sLockTimeFail size=3 maxlength=2 value="<?php echo $sLockTimeFail;?>"></td>
	  </tr>
	  
	  <tr>
	  <td valign=top><p align=justify>Scriptsperre in DB nachhalten:<br>
	  Achtung: Soll Seitenaufbau innerhalb der eingestellten Sperrfrist 
	  verhindern, würde aber bewirken, dass bei Forenbesuchern 
	  mit identischer IP-Adress (z.B. aus Firmen heraus), die Sperre 
	  für alle gilt.</p></td>
	  <td valign=top><input type=checkbox name=sUseLoginLock value="X" <?php if ($sUseLoginLock=="X") {echo "checked";}?>></td>
	  </tr>
	  
	  <tr>
	  <td align=center colspan=2><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>
	  </form>
	  <?php 
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "PMSON":
	case "PMSOFF":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("PRIMARY",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  $aConfig=ReadConfigFile();
	  if ($sAction=="PMSON") {
  	    $sState="eingeschaltet.";
		$aConfig=SetRamValue("\$PmsActive","X",$aConfig);
	  }
	  else {
  	    $sState="abgeschaltet.";
		$aConfig=SetRamValue("\$PmsActive","-",$aConfig);
	  }
	  WriteConfigFile($aConfig);
	  echo "<h3 align=center>Das PMS ist jetzt $sState</h3>";
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";	  
	  break;
  
    case "DONEW_ADMINADMS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Administratorenverwaltung - Neuer Administrator</h3>";
	  $bProceed=true;
	  if ($bProceed) {
	    if (strlen($sAdmName)<=1) {
		  echo "<center><font color=#ff0000><b>Fehler: </b>Name muss mindestens 2 Zeichen lang sein</font></center><br>";
		  $bProceed=false;
		}
	  }
	  if ($bProceed) {
	    if (strlen($sAdmPass)<6) {
		  echo "<center><font color=#ff0000><b>Fehler: </b>Passwort muss mindestens 6 Zeichen lang sein</font></center><br>";
		  $bProceed=false;
		}
	  }
	  if ($bProceed) {
	    if (!CheckEmail($sAdmEmail)) {
		  echo "<center><font color=#ff0000><b>Fehler: </b>Ung&uuml;ltige Emailadresse</font></center><br>";
		  $bProceed=false;
		}
	  }
	  if ($bProceed) {
	    $sNewPass=md5($sAdmPass);
		$aDate=getdate(time());
		$sSinced=MakeGermanDate($aDate);
		$sSincet=MakeGermanTime($aDate);
		$sQuery="insert into $DbAdm set userid ='$sAdmName',
		                                passwd ='$sNewPass',
										email  ='$sAdmEmail',
										sinced ='$sSinced',
										sincet ='$sSincet',
										kzactiv='$sAdmKzactiv',
										level  ='$sAdmLevel'";
	    $Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		$sQuery="select * from $DbAdm where userid='$sAdmName'";
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
		if ($DbRow=mysql_fetch_row($DbQuery)) {
		  echo "<center><font color=#0000ff><b>$sAdmName wurde angelegt</b></font></center><br>";
		}
		else {
		  echo "<center><font color=#ff0000><b>Fehler: </b>$sAdmName wurde nicht angelegt</font></center><br>";
		  echo "<center>Wahrscheinlich existiert der Name schon.</center><br>";
		}
	  }
	  echo "<center><a href=\"admin2.php?sAction=ADMINADMS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "NEW_ADMINADMS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Administratorenverwaltung - Neuer Administrator</h3>";
	  ?>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DONEW_ADMINADMS">
	  <input type=hidden name=sAdmName value="<?php echo $sAdmName;?>">
   	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);?>
      <center><table cellspacing=0 cellpadding=5 border=0>
	  <tr>
	  <td><b>Name:</b></td>
	  <td><input type=text name=sAdmName size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Passwort:</b></td>
	  <td><input type=text name=sAdmPass size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Email:</b></td>
	  <td><input type=text name=sAdmEmail size=35 maxlength=35></td>
	  </tr>	  
	  <tr>
	  <td><b>Level</b></td>
	  <td><select name=sAdmLevel size=1>
	  <option value="1"><?php echo TranslateAdmLevel("1");?></option>
	  <option value="2"><?php echo TranslateAdmLevel("2");?></option>
	  <option value="3"><?php echo TranslateAdmLevel("3");?></option>
	  <option value="ADMALL"><?php echo TranslateAdmLevel("ADMALL");?></option>
	  </select></td>
	  </tr>
	  <tr>
	  <td><b>aktiv/inaktiv</b></td>
	  <td><select name=sAdmKzactiv size=1>
	  <option value="X"><?php echo TranslateAdmKzactiv("X");?></option>
	  <option value="-"><?php echo TranslateAdmKzactiv("-");?></option>
	  </select></td>
	  </tr>
	  <tr>
	  <td colspan=2 align=center><input type=submit value="Anlegen"></td>
	  </tr>
	  </table></center>	  
	  </form>
	  <?php 
	  echo "<center><a href=\"admin2.php?sAction=ADMINADMS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "DOCHG_ADMINADMS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Administratorenverwaltung - &Auml;ndern</h3>";
	  $bProceed=true;
	  if ($bProceed) {
	    if ((strlen($sAdmPass)>=1) && (strlen($sAdmPass)<6)) {
		  echo "<center><font color=#ff0000><b>Fehler: </b>Passwort muss mindestens 6 Zeichen lang sein</font></center><br>";
		  $bProceed=false;
		}
	  }
	  if ($bProceed) {
	    if (!CheckEmail($sAdmEmail)) {
		  echo "<center><font color=#ff0000><b>Fehler: </b>Ung&uuml;ltige Emailadresse</font></center><br>";
		  $bProceed=false;
		}
	  }
	  if ($bProceed) {
	    if (strlen($sAdmPass)>=1) {
		  $sNewPass=md5($sAdmPass);
		  $sQuery="update $DbAdm set passwd  ='$sNewPass',
		                             email   ='$sAdmEmail',
									 kzactiv ='$sAdmKzactiv',
									 level   ='$sAdmLevel'
							     where userid='$sAdmName'";
		}
		else {
		  $sQuery="update $DbAdm set email   ='$sAdmEmail',
									 kzactiv ='$sAdmKzactiv',
									 level   ='$sAdmLevel'
							     where userid='$sAdmName'";
		}
		$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	    echo "<center><font color=#0000ff><b>Administrator $sAdmName wurde ge&auml;ndert</b></font></center><br>";	    
	  }
	  echo "<center><a href=\"admin2.php?sAction=CHG_ADMINADMS&sAdmName=$sAdmName&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "CHG_ADMINADMS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Administratorenverwaltung - &Auml;ndern</h3>";
	  $sQuery="select * from $DbAdm where userid='$sAdmName'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $DbRow=mysql_fetch_row($DbQuery);
	  $sAdmEmail   = $DbRow[2];
	  $sAdmDate    = $DbRow[3];
	  $sAdmTime    = $DbRow[4];
	  $sAdmLevel   = TranslateAdmLevel($DbRow[11]);
	  $sAdmKzactiv = TranslateAdmKzactiv($DbRow[5]);
	  ?>
	  <form action="admin2.php" method=post>
	  <input type=hidden name=sAction value="DOCHG_ADMINADMS">
	  <input type=hidden name=sAdmName value="<?php echo $sAdmName;?>">
   	  <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);?>
      <center><table cellspacing=0 cellpadding=5 border=0>
	  <tr>
	  <td><b>Name:</b></td>
	  <td><?php echo $sAdmName;?></td>
	  </tr>
	  <tr>
	  <td><b>Passwort:</b></td>
	  <td><input type=text name=sAdmPass size=20 maxlength=20></td>
	  </tr>
	  <tr>
	  <td><b>Email:</b></td>
	  <td><input type=text name=sAdmEmail size=35 maxlength=35 value="<?php echo $sAdmEmail;?>"></td>
	  </tr>	  
	  <tr>
	  <td><b>seit:</b></td>
	  <td><?php echo $sAdmDate."&nbsp;-&nbsp;".$sAdmTime?></td>
	  </tr>	  
	  <tr>
	  <td><b>Level</b></td>
	  <td><select name=sAdmLevel size=1>
	  <option value="1" <?php if ($DbRow[11]=="1") {echo "selected";}?>><?php echo TranslateAdmLevel("1");?></option>
	  <option value="2" <?php if ($DbRow[11]=="2") {echo "selected";}?>><?php echo TranslateAdmLevel("2");?></option>
	  <option value="3" <?php if ($DbRow[11]=="3") {echo "selected";}?>><?php echo TranslateAdmLevel("3");?></option>
	  <option value="ADMALL" <?php if ($DbRow[11]=="ADMALL") {echo "selected";}?>><?php echo TranslateAdmLevel("ADMALL");?></option>
	  </select></td>
	  </tr>
	  <tr>
	  <td><b>aktiv/inaktiv</b></td>
	  <td><select name=sAdmKzactiv size=1>
	  <option value="X" <?php if ($DbRow[5]=="X") {echo "selected";}?>><?php echo TranslateAdmKzactiv("X");?></option>
	  <option value="-" <?php if ($DbRow[5]=="-") {echo "selected";}?>><?php echo TranslateAdmKzactiv("-");?></option>
	  </select></td>
	  </tr>
	  <tr>
	  <td colspan=2 align=center><input type=submit value="Speichern"></td>
	  </tr>
	  </table></center>	  
	  </form>
	  <?php 
	  echo "<center><a href=\"admin2.php?sAction=ADMINADMS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
  
    case "ADMINADMS":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("ADMALL",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Administratorenverwaltung</h3>";
	  ?>
	  <p align=justify>Sie k&ouml;nnen hier alle Administratoren ausser den Foreneigent&uuml;mer verwalten. 
	  Solange Sie nicht der Foreneigent&uuml;mer sind, k&ouml;nnen Sie auch sich selbst verwalten, d.h. <b>Sie 
	  k&ouml;nnen sich selbst zur&uuml;ckstufen oder deaktivieren. Sie werden dabei keine weitere Warnung 
	  erhalten.</b></p>
	  <?php 
	  $sQuery="select * from $DbAdm order by userid";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  
	  echo "<center><table cellspacing=0 cellpadding=2 border=1>";
	  echo "<tr>";
	  echo "<td><b>Name</b></td>";
	  echo "<td><b>Email</b></td>";
	  echo "<td><b>seit</b></td>";
	  echo "<td><b>Level</b></td>";
	  echo "<td><b>aktiv/inaktiv</b></td>";
	  echo "<td><b>&auml;ndern</b></td>";
	  echo "</tr>";
	  
	  while ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sAdmName    = $DbRow[0];
		$sAdmEmail   = $DbRow[2];
		$sAdmDate    = $DbRow[3];
		$sAdmTime    = $DbRow[4];
		$sAdmLevel   = TranslateAdmLevel($DbRow[11]);
		$sAdmKzactiv = TranslateAdmKzactiv($DbRow[5]);
		echo "<tr>";
		echo "<td>$sAdmName</td>";
		echo "<td>$sAdmEmail</td>";
		echo "<td>$sAdmDate - $sAdmTime</td>";
		echo "<td>$sAdmLevel</td>";
		echo "<td>$sAdmKzactiv</td>";
		if ($DbRow[11]!="PRIMARY") {
		  echo "<td><a href=\"admin2.php?sAdmName=$sAdmName&sAction=CHG_ADMINADMS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">&auml;ndern</a></td>";
		}
		else {
		  echo "<td>&nbsp;</td>";
		}
		echo "</tr>";
	  }
	  echo "<tr>";
	  echo "<td colspan=6 align=center><a href=\"admin2.php?sAction=NEW_ADMINADMS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\">Neuer Administrator</a></td>";
	  echo "<tr>";
	  
	  echo "</table></center><br>";
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
	  
    case "DODOTRANSFER":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("PRIMARY",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Forenbesitz &uuml;bergeben</h3>";
	  if (isset($butNo)) {
	    echo "<center><b><font color=#0000ff>Aktion abgebrochen!</font></b></center><br>";
	    echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
		break;
	  }
	  $sQuery="update $DbAdm set level='PRIMARY' where userid='$sNewOwner'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $sQuery="update $DbAdm set level='ADMALL' where userid='$sUser'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery); 
      echo "<center><b><font color=#0000ff>Sie wurden zum Volladministraor gemacht, $sNewOwner ist jetzt Foreneigent&uuml;mer.</font></b></center><br>";
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>"; 	    
	  break;
  
    case "DOTRANSFER":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("PRIMARY",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Forenbesitz &uuml;bergeben</h3>";
	  if (strlen($sNewOwner)>=1) {
	    echo "<center><b><font color=#ff0000>ACHTUNG: Wollen Sie den Forenbesitz wirklich an $sNewOwner &uuml;bergeben?</font></b></center>"
		?>
		<form action="admin2.php" method=post>
	    <input type=hidden name=sAction value="DODOTRANSFER">
		<input type=hidden name=sNewOwner value="<?php echo $sNewOwner;?>">
   	    <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr);?>
        <center><input type=submit name=butYes value="Ja">&nbsp;&nbsp;&nbsp;<input type=submit name=butNo value="Nein"></center>
		</form>
		<?php
	  }
	  else {
	    echo "<center><b><font color=#0000ff>Kein neuer Besitzer gew&auml;hlt.</font></b></center><br>";
	    echo "<center><a href=\"admin2.php?sAction=TRANSFER&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  }
	  break;
  
    case "TRANSFER":
	  if (($sLoggedIn!="X") || (!CheckAdmRight("PRIMARY",$LevelGiven))) {
	    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
		echo "</td></tr></table></center></body></html>";
		exit();
	  }
	  echo "<h3 align=center>Forenbesitz &uuml;bergeben</h3>";
	  ?>
	  <center><b><font color=#ff0000>ACHTUNG: Bitte genau lesen!</font></b></center>
	  <p align=justify>Sie sind Eigent&uuml;mer des Forums und haben damit alle Rechte der Administration und 
	  k&ouml;nnen auch von anderen Volladministratoren nicht deaktiviert werden. Wenn Sie jetzt das Forum als 
	  Eigentum an einen anderen Administrator &uuml;bertragen, wird dieser zum Eigent&uuml;mer des Forums mit 
	  allen Rechten. Sie selber behalten alle Administrationsrechte, k&ouml;nnen aber vom neuen Eigent&uuml;mer 
	  und anderen Volladministratoren dann editiert und deaktiviert werden, d.h. Ihre Eigentumsrechte erl&ouml;schen 
	  mit der &Uuml;bergabe und Sie werden zum Volladministrator.</p>
	  <?php 
	  
	  $sQuery="select * from $DbAdm where kzactiv='X' and level <>'PRIMARY'";
	  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  
	  echo "<form action=\"admin2.php\" method=post>";
	  echo "<input type=hidden name=sAction value=\"DOTRANSFER\">";
	  EchoHiddenSession($sSessid,$sUser,$sSipaddr);
	  
	  echo "<center><table cellspacing=0 cellpadding=2 border=1>";
	  echo "<tr>";
	  echo "<td><b>Name</b></td>";
	  echo "<td><b>Email</b></td>";
	  echo "<td><b>seit</b></td>";
	  echo "<td><b>Level</b></td>";
	  echo "<td>&nbsp;</td>";
	  echo "</tr>";
	  
	  while ($DbRow=mysql_fetch_row($DbQuery)) {
	    $sAdmName =$DbRow[0];
		$sAdmEmail=$DbRow[2];
		$sAdmDate =$DbRow[3];
		$sAdmTime =$DbRow[4];
		$sAdmLevel=TranslateAdmLevel($DbRow[11]);
		echo "<tr>";
		echo "<td>$sAdmName</td>";
		echo "<td>$sAdmEmail</td>";
		echo "<td>$sAdmDate - $sAdmTime</td>";
		echo "<td>$sAdmLevel</td>";
		echo "<td><input type=radio name=sNewOwner value=\"$sAdmName\"></td>";
		echo "</tr>";
	  }
	  
	  echo "<tr><td colspan=5 align=center><input type=submit value=\"&Uuml;bergeben\"></td></tr>";
	  echo "</table></center>";
	  echo "</form>";
	  
	  echo "<center><a href=\"admin.php?sAction=LOGIN&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo\"><b>Zur&uuml;ck</b></a></center>";
	  break;
  }
  echo "</td></tr></table></center></body></html>";
  
?>
