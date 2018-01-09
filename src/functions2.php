<?php

include_once ("functions.php");

function ReadThreadsOrderByNewestsPost($Db,$DbHost,$DbUser,$DbPass,$DbName,$DbTab,$DelSign,$ThreadsPerPage,$ActPage) {
  $bOpened=false;
  if ($Db==null) {
    @mysql_connect($DbHost,$DbUser,$DbPass); @mysql_select_db($DbName); $bOpened=true;
  }
  
  $iThreads=0; $aThreads=array();
  $Q=@mysql_query("select no from $DbTab where preno=0 and del='$DelSign'");
  while ($R=@mysql_fetch_object($Q)) {
    $QQ=@mysql_query("select no,thread from $DbTab where thread=$R->no
	                                               and   del   ='$DelSign'
												   order by no desc limit 0,1");
    if ($RR=@mysql_fetch_object($QQ)) {
	  $aThreads[$iThreads][0]=$RR->no; $aThreads[$iThreads][1]=$RR->thread;
	  $iThreads++;
	}												   
  }
  $i_1=$iThreads-1;
  for ($i=0;$i<$i_1;$i++) {
    for ($k=$i;$k<$iThreads;$k++) {
	  if ($aThreads[$k][0]>$aThreads[$i][0]) {
	    $iHNo=$aThreads[$k][0]; $iHThread=$aThreads[$k][1];
		$aThreads[$k][0]=$aThreads[$i][0];$aThreads[$k][1]=$aThreads[$i][1];
		$aThreads[$i][0]=$iHNo; $aThreads[$i][1]=$iHThread;
	  }
	}
  }
  
  if ($bOpened) {
    @mysql_close();
  }

  $iStart=0; $iEnd=$iThreads;  
  if ($ThreadsPerPage>0) {
    $iStart=($ActPage-1)*$ThreadsPerPage;
	$iEnd  =$iStart+$ThreadsPerPage;
  }
  
  $aRet=array();$iRet=0;
  if ($iEnd>$iThreads) {
    $iEnd=$iThreads;
  }
  if ($iStart>$iThreads) {
    $iStart=$iThreads;
    return ($aRet);
  }
  for ($i=$iStart;$i<$iEnd;$i++) {
    $aRet[$iRet]=$aThreads[$i][1];
	$iRet++;
  }
  
  return($aRet);
}

function UpdateThreadField($DbHost,$DbUser,$DbPass,$DbName,$DbTab) {

  @mysql_connect($DbHost,$DbUser,$DbPass); @mysql_select_db($DbName);
  
  $Q=@mysql_query("select no,preno from $DbTab where del='-' or del='T'");
  while ($R=mysql_fetch_object($Q)) {
    $iPreno=$R->preno;$iNo=0;$iCnt=0;
	if ($iPreno==0) {$iNo=$R->no;}
	while (($iPreno!=0) && ($iCnt<100)) {
	  $iCnt++;
	  $QQ=@mysql_query("select no,preno from $DbTab where no=$iPreno
	                                                and   (del='-' or del='T')");
      if ($RR=@mysql_fetch_object($QQ))	{
	    $iPreno=$RR->preno;
		if ($iPreno==0) {$iNo=$RR->no;}
	  }
	  else {
	    break;
	  }
	}
	if ($iNo!=0) {
	  @mysql_query("update $DbTab set thread=$iNo where no=$R->no");
	}
  }
  
  @mysql_close();
}

function ReadThreadForum($iNo,$iPreno,&$aPosts,&$iCnt,$iDepth=0) {
  global $DbHost,$DbUser,$DbPass,$DbName,$DbTab;

  $bFirstCall=false;
  if ($iCnt==0) {
    if (isset($aPosts)) {unset($aPosts);}
    $aPosts = array();
    @mysql_connect($DbHost,$DbUser,$DbPass);
    @mysql_select_db($DbName);
    $bFirstCall=true;
    $Q=@mysql_query("select * from $DbTab where no    = $iNo
		  								  and   del   <>'X'");
  }
  else {  
    $Q=@mysql_query("select * from $DbTab where preno = $iPreno
		 								  and   del   <>'X'
										  order by no desc");
  }								
  $iNewDepth=$iDepth+1;		  
  while ($R=@mysql_fetch_object($Q)) {
    $aPosts[$iCnt][0] = $R->no;
    $aPosts[$iCnt][1] = $R->preno;
    $aPosts[$iCnt][2] = DecryptText(stripslashes($R->author));
	$aPosts[$iCnt][3] = $R->regular;
	$aPosts[$iCnt][4] = DecryptText(stripslashes($R->subject));
	$aPosts[$iCnt][5] = DecryptText(stripslashes($R->ptext));
	$aPosts[$iCnt][6] = $iDepth;
	$aPosts[$iCnt][7] = $R->date;
	$aPosts[$iCnt][8] = $R->time;
	$aPosts[$iCnt][9] = $R->tclose;
	$iCnt++;
	ReadThreadForum($R->no,$R->no,$aPosts,$iCnt,$iNewDepth);
  }
  
  if ($bFirstCall) {
    @mysql_close();
  }
}

function CheckRegSessionValid($sSessid,$sName,$DbHost,$DbName,$DbUser,$DbPass,$TabPrf,$MaxLoginTime) {
  $iSesstime=time();
  $sQuery="select * from $TabPrf where name   ='$sName' 
                                 and sessid   ='$sSessid'
								 and sesstime>=$iSesstime
								 and sessvalid='X'";
  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  if ($DbRow=mysql_fetch_object($DbQuery)) {
    $aUser = $DbRow;
    $iSesstime=$iSesstime+$MaxLoginTime;
	$sQuery="update $TabPrf set sesstime=$iSesstime where name='$sName'";
	$Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	return($aUser);
  }						 
  return (false);
}

function EchoFreePostArcMenu($sArcPostMen,$sNo,$sClose,$sLinkNo,$sArcFile) {
  $sArcPostMen=PseudoToHmtlMenu($sArcPostMen,"ARCPOST",$sNo,$sClose,$sLinkNo,$sArcFile);
  echo $sArcPostMen;
}

function EchoFreeShowArcMenu($sArcShowMen,$sNo,$sClose,$sLinkNo,$sArcFile) {
  $sArcShowMen=PseudoToHmtlMenu($sArcShowMen,"ARCSHOW",$sNo,$sClose,$sLinkNo,$sArcFile);
  echo $sArcShowMen;
}

function EchoFreeMainMenu($sIndexMen) {
  $sIndexMen=PseudoToHmtlMenu($sIndexMen,"MAIN");
  echo $sIndexMen;
}

function EchoFreeArcMenu($sArcMen,$sLinkNo,$sFile) {
  $sArcMen=PseudoToHmtlMenu($sArcMen,"ARCHIVE","0","-",$sLinkNo,$sFile);
  echo $sArcMen;
}

function EchoFreeRecentMenu($sRecentMen) {
  $sRecentMen=PseudoToHmtlMenu($sRecentMen,"RECENT");
  echo $sRecentMen;
}

function EchoFreeSearchMenu($sSearchMen) {
  $sSearchMen=PseudoToHmtlMenu($sSearchMen,"SEARCH");
  echo $sSearchMen;
}

function EchoFreeFormatMenu($sFormatMen) {
  $sFormatMen=PseudoToHmtlMenu($sFormatMen,"FORMAT");
  echo $sFormatMen;
}

function EchoFreePostMenu($sPostMen) {
  $sPostMen=PseudoToHmtlMenu($sPostMen,"POST");
  echo $sPostMen;
}

function EchoFreeShowMenu($sPostMen,$sNo,$sClose) {
  $sPostMen=PseudoToHmtlMenu($sPostMen,"SHOW",$sNo,$sClose);
  echo $sPostMen;
}

function EchoFreeThreadMenu($sThreadMen) {
  $sThreadMen=PseudoToHmtlMenu($sThreadMen,"THREAD");
  echo $sThreadMen;
}

function PseudoToHmtlMenu($sText,$sMenu,$sNo="0",$sClose=false,$sLinkNo=false,$sFile=false) {
  include ("menutags.php");
  
  $aConfig=ReadConfigFile();
  $RegsActive=GetRamValue("\$RegsActive",$aConfig);
  
  $sText="#".$sText;
  
  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {

    $sTag     =$aTag[$i][0];
    $sTagR    =$aTag[$i][1];
    $sHtmlTag =$aTag[$i][2];
    $sHtmlTagR=$aTag[$i][3];
	if (($sTag=="[post]") && (substr($sMenu,0,3)!="ARC")) {
	  $sHtmlTag=$sHtmlTag."$sNo\">";
	}
	if (($sTag=="[post]") && (substr($sMenu,0,3)=="ARC")) {
	  $sHtmlTag=$sHtmlTag."$sNo&sArcFile=$sFile&sLinkNo=$sLinkNo\">";
	}
	if ($sTag=="[atoggle]") {
	  $sHtmlTag=$sHtmlTag."sLinkNo=$sLinkNo&sFile=$sFile\">";
	}
	if ($sTag=="[archive]") {
	  $sHtmlTag=$sHtmlTag."sLinkNo=$sLinkNo&sFile=$sFile\">";
	}
	
    while ($iPos=strpos($sText,$sTag)) {
      $sBeforeTag=substr($sText,0,$iPos);
      $sFromTag  =substr($sText,$iPos,(strlen($sText)-$iPos));

      if (!(strpos($sFromTag,$sTagR))) {
        $sFromTag=$sFromTag.$sTagR;
      }
		
 	  $sFromTag="#".$sFromTag;
		
	  $iPosF=strpos($sFromTag,$sTag);
      $iPosR=strpos($sFromTag,$sTagR);
      
      $sFromTag=substr($sFromTag,1,(strlen($sFromTag)-1));
//    Effekt kein Selbstaufruf
      if ((($sTag=="[post]")) && ($sMenu=="THREAD")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[index]")) && ($sMenu=="MAIN")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[recent]")) && ($sMenu=="RECENT")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[search]")) && ($sMenu=="SEARCH")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }     	  	  
	  if ((($sTag=="[format]")) && ($sMenu=="FORMAT")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[post]")) && (($sMenu=="POST") || ($sMenu=="ARCHIVE") || ($sMenu=="ARCPOST"))) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[post]")) && ($sMenu=="SHOW") && ($sClose=="X")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[archive]")) && ($sMenu=="ARCHIVE")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  
//    Unsinniges verhindern
	  if (($sTag=="[archive]") && (substr($sMenu,0,3)!="ARC")) {
	    $sHtmlTag=$sHtmlTag."$sNo\">";
	  }
	  if (($sTag=="[regulars]") && ($RegsActive!="X")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[toggle]")) && ($sMenu!="MAIN")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  if ((($sTag=="[atoggle]")) && ($sMenu!="ARCHIVE")) {
	    $sHtmlTag=$sHtmlTagR="";
	  }
	  
      $sFromTag=KffReplace($sFromTag,$sTag, $sHtmlTag);
      $sFromTag=KffReplace($sFromTag,$sTagR,$sHtmlTagR);
	  
      $sText=$sBeforeTag.$sFromTag;
      
      $sTag     =$aTag[$i][0];
      $sTagR    =$aTag[$i][1];
      $sHtmlTag =$aTag[$i][2];
      $sHtmlTagR=$aTag[$i][3];
	  if (($sTag=="[post]") && (substr($sMenu,0,3)!="ARC")) {
	    $sHtmlTag=$sHtmlTag."$sNo\">";
	  }
	  if (($sTag=="[post]") && (substr($sMenu,0,3)=="ARC")) {
	    $sHtmlTag=$sHtmlTag."$sNo&sArcFile=$sArcFile&sLinkNo=$sLinkNo\">";
	  }
	  if ($sTag=="[atoggle]") {
	    $sHtmlTag=$sHtmlTag."sLinkNo=$sLinkNo&sFile=$sFile\">";
	  }
	  if ($sTag=="[archive]") {
	    $sHtmlTag=$sHtmlTag."sLinkNo=$sLinkNo&sFile=$sFile\">";
	  }
    }
  }
 
  $sText=substr($sText,1,(strlen($sText)-1)); 
  $sText=Pseudo3ToHtml($sText);
  return ($sText);
}

function ReadMenuFile($sMenu) {

  $bFound=false;

  switch ($sMenu) {
    case "THREAD":
	  $sFile="cfg/thread.txt";
	  break;
  
    case "REGISTERTEXT":
	  $sFile="cfg/registertxt.txt";
	  break;
  
    case "MAIN":
	  $sFile="cfg/index_men.txt";
	  break;
	  
	case "RECENT":
	  $sFile="cfg/recent_men.txt";
	  break;
	  
	case "SEARCH":
	  $sFile="cfg/search_men.txt";
	  break;
	  
	case "FORMAT":
	  $sFile="cfg/format_men.txt";
	  break;
	  
	case "POST":
	  $sFile="cfg/post_men.txt";
	  break;
	  
	case "SHOW":
	  $sFile="cfg/show_men.txt";
	  break;
	  
	case "ARCHIVE":
	  $sFile="cfg/archive_men.txt";
	  break;
	  
	case "ARCPOST":
	  $sFile="cfg/arcpost_men.txt";
	  break;
	  
	case "ARCSHOW":
	  $sFile="cfg/arcshow_men.txt";
	  break;
	  
	default:
	  return (false);
	  break;
  }
  
  if (file_exists($sFile)) {
	if ($hndFile=@fopen($sFile,"r-")) {
	  $iLen=filesize($sFile);
	  if ($iLen > 0) {
  	    $s=fread($hndFile,$iLen);
	  }
	  else {
	    $s="";
	  }
	  $sTitle=chop($s);
	  $sTitle=stripcslashes($sTitle);
	  fclose($hndFile);
	  $bFound=true;
	}
  }
  clearstatcache();
  
  if ($bFound) {
    return ($sTitle);
  }
  else {
    return (false);
  }
}

function CheckLoginLock($DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$RemoteAddr) {
  $sQuery="select * from $TabLoginLock where ipadr='$RemoteAddr'";
  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  if ($DbRow=mysql_fetch_row($DbQuery)) {
    if ($DbRow[1]>time()) {
	  $iReturn=$DbRow[1]-time();
	  return($iReturn);
	}
  }
  return(false);
}

function SetLoginLock($Db,$DbHost,$DbName,$DbUser,$DbPass,$TabLoginLock,$LockTimeFail,$RemoteAddr) {
  $bOpened=false;
  if (!$Db) {
    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	  $bOpen = true;
	}
  }
  
  $sQuery="delete from $TabLoginLock where ipadr='$RemoteAddr'";
  DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  $iLockTime=time()+intval($LockTimeFail);
  $sQuery="insert into $TabLoginLock set ipadr   ='$RemoteAddr',
                                         locktime=$iLockTime";
  DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
    
  if ($bOpen) {
    mysql_close ($Db);
  }
}

function KffStripTags($sText) {
  $sText="#".$sText;
  
  while ($iPos=strpos($sText,"<")) {
    $sText=KffReplace($sText,"<","&lt;");
  }
  while ($iPos=strpos($sText,">")) {
    $sText=KffReplace($sText,">","&gt;");
  }
  
  $sText=substr($sText,1,(strlen($sText)-1));
  
  return ($sText);
}

?>