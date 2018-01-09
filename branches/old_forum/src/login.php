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

include("cfg/config.php");
include("functions.php");
include_once ("functions2.php");

include_once ("failloginstop.php");

if (!isset($sAction)) {
  $sAction="INI";
}

switch($sAction) {
  case "LOGIN":
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
      EchoLoginHeader();
      echo "<h3 align=center><font color=#ff0000><b>Fehler:</b> Verbindung zur Datenbank im Moment nicht m&ouml;glich!</font></h3>";
      echo "<center><a href=\"login.php?sAction=INI\">Zur&uuml;ck</a></center>";
      EchoLoginFooter();
      exit();
    }
    $bProceed=true;
    $DbQuery=mysql_db_query($DbName,"select * from $DbAdm where userid='$sUser'",$Db);
    if ($DbRow=mysql_fetch_row($DbQuery)) {
      if ($DbRow[5]!="X") {
        // User ist deaktiviert
        $bProceed=false;
      }
      if ($bProceed) {
//	    echo $sPassMd5."&nbsp".$DbRow[1]."<br>";
        $sPassMd5=md5($sPass);
        if ($sPassMd5!=$DbRow[1]) {
          // Das Passwort ist falsch
          $bProceed=false;
          $iCntMiss=$DbRow[6]+1;
          if ($iCntMiss>=$MaxLoginFails) {
            $sKzactive="1";
          }
          else {
            $sKzactive="X";
          }
          mysql_db_query($DbName,
                         "update $DbAdm set kzactiv='$sKzactive',
                                            cntmiss=$iCntMiss
                                 where userid='$sUser'"
                         ,$Db);
        }
      }
      if ($bProceed) {
        $sSessid=crypt($sUser);
        $iStime=time()+$MaxLoginTime;
        $iCntMiss=0;
        $sSipaddr=$REMOTE_ADDR;
        $sLevel=$DbRow[11];
        mysql_db_query($DbName,
                       "update $DbAdm set cntmiss=0,
		  			                      sessid='$sSessid',
                                          kzsval='X',
                                          sipadr='$sSipaddr',
                                          stime =$iStime
                               where userid='$sUser'"
                       ,$Db);
      }
      mysql_close($Db);
    }
    else {
      $bProceed=false;
    }

    if (!$bProceed) {
      EchoLoginHeader();
      echo "<h3 align=center><font color=#ff0000><b>Fehler:</b> Ung&uuml;ltiges Login!</font></h3>";
	  $Db=NULL; SetLoginLock($Db,$DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$LockTimeFail,$REMOTE_ADDR);
	  flush();
	  sleep(intval($LockTimeFail));
      echo "<center><a href=\"login.php?sAction=INI\">Zur&uuml;ck</a></center>";
      EchoLoginFooter();
    }
    else {
      header("Location: admin.php?sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr");
//      header("Location: admin.php");
    }
    break;

  case "INI":
    EchoLoginHeader();
    ?>
    <h3 align=center>Login</h3>
    <form action="login.php" method=post>
    <input type=hidden name=sAction value="LOGIN">
    <center><table cellspacing=0 cellpadding=5>
    <tr>
    <td><b>Username:</b></td>
    <td><input type=text name=sUser size=20 maxlength=20></td>
    </tr>
    <tr>
    <td><b>Passwort:</b></td>
    <td><input type=password name=sPass size=20 maxlength=20></td>
    </tr>
    
    <tr>
    <td colspan=2 align=center><input type=submit value="Login"></td>
    </tr>
    </table></center>
    </form>
    <?php
    EchoLoginFooter();
    break;
}

function EchoLoginHeader() {
  ?>
  <html>
  <head>
  <style type="text/css">
  <!-- 
  h1 {font-family:Arial; font-size:18pt;}
  h2 {font-family:Arial; font-size:16pt;}
  h3 {font-family:Arial; font-size:14pt;}
  h4 {font-family:Arial; font-size:12pt;}
  body, table, td, center, i, u, b, p, div {font-family:Arial; font-size:10pt;}
  // -->
  </style>
  <title>Forenadministration Login</title>
  </head>

  <body text=#000000 bgcolor=#fafafa link=#000000 alink=#000000 vlink=#666666>
  <center><table width=90%><tr><td><br>
  <h1 align=center>Forenadministration Login</h1>
  <?php
}

function EchoLoginFooter() {
  ?>
  </td></tr></table></center>
  </body>
  </html>
  <?php
}

?>
