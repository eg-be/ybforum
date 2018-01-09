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
  include ("admfunc.php");

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
  
  if (!CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime)) {
    header("location: login.php");
  }
  $sLoggedIn="X";
  $LevelGiven=GetAdmLevel($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser);
  
  if (($sLoggedIn!="X") || (!CheckAdmRight("1",$LevelGiven))) {
    echo "<h3 align=center><font color=#ff0000>Fehler: Nicht eingeloggt</font></h3>";
	echo "</td></tr></table></center></body></html>";
	exit();
  }

  if (!isset($sAction)) {
    $sAction="INI";
  }

  if ($sAction=="DOFREE") {
    $iNo=intval($sNo);
	$sAction="DONE";
	if (isset($butAccept)) {
      echo "<h3 align=center><font color=#0000ff>Beitrag wurde freigeschaltet</font></h3>";
	  $sNewDel="-";
	}
	else {
      echo "<h3 align=center><font color=#0000ff>Beitrag wurde verworfen</font></h3>";
	  $sNewDel="X";
	}
	$sQuery="update $DbTab set del='$sNewDel' where no=$iNo";
	$Db=NULL; DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  }
  
  if ($sAction!="DONE") {
    echo "<h3 align=center>Beitrag moderieren</h3>";
  
    $iNo=intval($sNo); $sQuery="select * from $DbTab where no=$iNo";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	if ($DbRow=mysql_fetch_row($DbQuery)) {

      $iPreNo=intval($DbRow[1]);
	  if ($iPreNo!=0) {
	    $Db=NULL; $Q=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,"select author,date,time,subject from $DbTab where no=$iPreNo and del<>'X'");
		if ($R=@mysql_fetch_object($Q)) {
		  echo "<hr><center>
		        <b>Anwort auf:</b> <a href=\"showentry.php?sNo=$iPreNo\" target=\"_blank\">".DecryptText(stripcslashes($R->subject))."</a> - <b>".DecryptText(stripcslashes($R->author))."</b> - <i>$R->date $R->time</i>
		        </center><hr>\n";
		}
	  }
	
	  $sNo      = stripcslashes($DbRow[0]);
	  $sSubject = DecryptText(stripcslashes($DbRow[10]));
	  $sAuthor  = DecryptText(stripcslashes($DbRow[2]));
	  $sEmail   = $DbRow[3]; if (strlen($sEmail<1)) {$sEmail="&nbsp;";}
	  $sReg     = $DbRow[4];
	  $Db=NULL; $sAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$sReg,$RegColor,$AdminColor,$RegsSameCol);		
	  $sDate    = $DbRow[5];
	  $sTime    = $DbRow[6];
	  $sPicUrl  = $DbRow[7];
	  $sHomename= $DbRow[9]; if (strlen($sHomename<1)) {$sHomename="&nbsp;";}
	  $sHomeurl = $DbRow[8];
	  $sText    = DecryptText($DbRow[13]);
	  $sText=nl2br(Pseudo2toHtml($sText));
	  $sText=stripcslashes($sText);
	  
	  ?>
	  <form action="freepost.php" method=post>
      <?php EchoHiddenSession($sSessid,$sUser,$sSipaddr); ?>
      <input type=hidden name=sAction value="DOFREE">
      <input type=hidden name=sNo     value="<?php echo $sNo;?>">
	
	  <div align=center>
	  <table border=1 cellspacing=0 cellpadding=3 border=1>
	  
	  <tr>
	  <td><b>Autor:</b></td>
	  <td><?php echo $sAuthor;?></td>
	  </tr>
	  
	  <tr>
	  <td><b>Email:</b></td>
	  <td><?php echo $sEmail;?></td>
	  </tr>
	  
	  <tr>
	  <td><b>Betreff:</b></td>
	  <td><?php echo $sSubject;?></td>
	  </tr>
	  
	  <tr>
	  <td><b>Beitrag:</b></td>
	  <td><?php if (strlen($sText)<1) {echo "&nbsp;";} else {echo $sText;}?></td>
	  </tr>
	  
	  <tr>
	  <td><b>Homepagename:</b></td>
	  <td><?php echo $sHomename;?></td>
	  </tr>
	    
	  <tr>
	  <td><b>Homepage:</b></td>
	  <td><?php if (strlen($sHomeurl)>1) {echo "<a href=\"$sHomeurl\" target=_blank>$sHomeurl</a>";} else {echo "&nbsp;";}?></td>
	  </tr>
	  
	  <tr>
	  <td><b>Bild:</b></td>
	  <td><?php if (strlen($sPicUrl)>1) {echo "<img src=\"$sPicUrl\" border=0 alt=\"\">";} else {echo "&nbsp;";}?></td>
	  </tr>
	  
	  <tr>
	  <td><input type=submit name=butAccept value="Akzeptieren"></td>
	  <td><input type=submit name=butDeny value="Ablehnen"></td>
	  </tr>
	  </table>
	  </div>
	
	</form>
	<?php 
	}
  }
  echo "<center><font color=#0000ff><b><a href=\"admin2.php?sAction=FREEPOSTS&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\">Zur&uuml;ck</a></b></font></center><br>";
  echo "</td></tr></table></center></body></html>";
?>