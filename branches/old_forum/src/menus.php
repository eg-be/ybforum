<?php

include_once ("db_tools.php");
include_once ("tools.php");
include_once ("functions.php");
include_once ("functions2.php");

function EchoSearchMenu ($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  $sSearchMen=ReadMenuFile("SEARCH");
  if (strlen($sSearchMen)>3) {
    EchoFreeSearchMenu($sSearchMen);
  }
  else {
    echo "<hr><center>";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]";
    echo "</center><hr>";
  }
}
function EchoFormatMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  $sFormatMen=ReadMenuFile("FORMAT");
  if (strlen($sFormatMen)>3) {
    EchoFreeFormatMenu($sFormatMen);
  }
  else {
    $aConfig=ReadConfigFile();
    $RegsActive=GetRamValue("\$RegsActive",$aConfig);
    $LoginRequired=GetRamValue("\$LoginRequired",$aConfig);
    echo "<hr><center>";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"textformatierung.php\">Textformatierung</a> ]";
    if ($RegsActive=="X") {
	  if ($LoginRequired=="X") {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\">Userbereich</a> ]";
	  }
	  else {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\" target=_blank>Stammposter</a> ]";
	  }
    }
	
    EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
    echo "</center><hr>";
  }
}
function EchoRegisterMenu ($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  echo "<hr><center>";
  echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
  echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]&nbsp;&nbsp;";
  echo "[ <a href=\"search.php\">Suchen</a> ]&nbsp;&nbsp;";
  echo "[ <a href=\"textformatierung.php\">Textformatierung</a> ]";
  EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
  echo "</center><hr>";
}
function EchoMainMenu ($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  $sIndexMen=ReadMenuFile("MAIN");
  if (strlen($sIndexMen)>3) {
    EchoFreeMainMenu($sIndexMen);
  }
  else {
    $aConfig=ReadConfigFile();
    $RegsActive=GetRamValue("\$RegsActive",$aConfig);
    $LoginRequired=GetRamValue("\$LoginRequired",$aConfig);
    echo "<hr><center>";
    echo "[ <a href=\"post.php?sNo=0\">Beitrag schreiben</a> ]&nbsp;&nbsp;";
    if (!$GLOBALS["iphone"]) {
    echo "<span class=\"morecommands\">";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"search.php\">Suchen</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"textformatierung.php\">Textformatierung</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"index.php?sToogleThreadShow=X\">Nur Threads/alle Beitr&auml;ge</a> ]";
    if ($RegsActive=="X") {
	  if ($LoginRequired=="X") {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\">Userbereich</a> ]";
	  }
	  else {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\" target=_blank>Stammposter</a> ]";
	  }
    }
  
    echo "&nbsp;&nbsp;[ <a href=\"register.php\">Registrieren</a> ]";

	echo "&nbsp;&nbsp;[ <a href=\"showentry.php?sNo=958206\">WP8 App</a> ]";
    EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
    }
	echo "</span>";
    echo "</center><hr>";
  }
}
function EchoArcMenu ($DbHost,$DbName,$DbUser,$DbPass,$DbFnc,$sFile,$sLinkNo) {
  $sArcMen=ReadMenuFile("ARCHIVE");
  if (strlen($sArcMen)>3) {
    EchoFreeArcMenu($sArcMen,$sLinkNo,$sFile);
  }
  else {
    $aConfig=ReadConfigFile();
    $RegsActive=GetRamValue("\$RegsActive",$aConfig);
    $LoginRequired=GetRamValue("\$LoginRequired",$aConfig);
    echo "<hr><center>";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"search.php\">Suchen</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"textformatierung.php\">Textformatierung</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"archive.php?sToogleThreadShow=X&sLinkNo=$sLinkNo&sFile=$sFile\">Nur Threads/alle Beitr&auml;ge</a> ]";
    if ($RegsActive=="X") {
	  if ($LoginRequired=="X") {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\">Userbereich</a> ]";
	  }
	  else {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\" target=_blank>Stammposter</a> ]";
	  }
    }
    EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
    echo "</center><hr>";
  }
}

function EchoRecentMenu ($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  $sRecentMen=ReadMenuFile("RECENT");
  if (strlen($sRecentMen)>3) {
    EchoFreeRecentMenu($sRecentMen);
  }
  else {
    $aConfig=ReadConfigFile();
    $RegsActive=GetRamValue("\$RegsActive",$aConfig);
    $LoginRequired=GetRamValue("\$LoginRequired",$aConfig);
    echo "<hr><center>";
    echo "[ <a href=\"post.php?sNo=0\">Beitrag schreiben</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"search.php\">Suchen</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"textformatierung.php\">Textformatierung</a> ]";
    if ($RegsActive=="X") {
	  if ($LoginRequired=="X") {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\">Userbereich</a> ]";
	  }
	  else {
        echo "&nbsp;&nbsp;[ <a href=\"reglogin.php\" target=_blank>Stammposter</a> ]";
	  }
    }
    EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
    echo "</center><hr>";
  }
}

function EchoPostMenu () {
  $sPostMen=ReadMenuFile("POST");
  if (strlen($sPostMen)>3) {
    EchoFreePostMenu($sPostMen);
  }
  else {
    echo "<hr><center>";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]";
    echo "</center><hr>";
  }
}

function EchoArcPostMenu($sArcFile,$sLinkNo) {
  $sArcPostMen=ReadMenuFile("ARCPOST");
  if (strlen($sArcPostMen)>3) {
    EchoFreePostArcMenu($sArcPostMen,$sNo,$sClose,$sLinkNo,$sArcFile);
  }
  else {
    echo "<hr><center>";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"archive.php?sLinkNo=$sLinkNo&sFile=$sArcFile\">Archiv Nr. $sLinkNo</a> ]";
    echo "</center><hr>";
  }
}

function EchoShowMenu ($sNo,$sClose) {
  $sShowMen=ReadMenuFile("SHOW");
  if (strlen($sShowMen)>3) {
    EchoFreeShowMenu($sShowMen,$sNo,$sClose);
  }
  else {
    echo "<hr><center>";
    if ($sClose!="X") {
      echo "[ <a href=\"post.php?sNo=$sNo\">Antworten</a> ]&nbsp;&nbsp;";
    }
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]";
    echo "</center><hr>";
  }
}
function EchoShowArcMenu ($sNo,$sClose,$sArcFile,$sLinkNo) {
  $sArcShowMen=ReadMenuFile("ARCSHOW");
  if (strlen($sArcShowMen)>3) {
    EchoFreeShowArcMenu($sArcShowMen,$sNo,$sClose,$sLinkNo,$sArcFile);
  }
  else {
    echo "<hr><center>";
    if ($sClose!="X") {
      echo "[ <a href=\"post.php?sNo=$sNo&sArcFile=$sArcFile&sLinkNo=$sLinkNo\">Antworten</a> ]&nbsp;&nbsp;";
    }
    echo "[ <a href=\"archive.php?sLinkNo=$sLinkNo&sFile=$sArcFile\">Archiv Nr. $sLinkNo</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"index.php\">Forum</a> ]&nbsp;&nbsp;";
    echo "[ <a href=\"recent.php\">Neue Beitr&auml;ge</a> ]";
    echo "</center><hr>"; 
  }
}
function EchoEditMenu ($sSessid,$sUser,$sSipaddr) {
  echo "<hr><center>";
  echo "[ <a href=\"admin.php?sAction=CHGPOST&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr\">Zr&uuml;ck zur &Uuml;bersicht</a> ]";
  echo "</center><hr>";
}


?>
