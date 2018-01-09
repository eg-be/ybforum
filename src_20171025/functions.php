<?php

include ("db_tools.php");
include ("tools.php");
include_once ("functions2.php");
include_once ("menus.php");


// youtube 

define('SITEBASE', '/home/ybforum/public_html/forum'); // path to the root of the site (not forcefully public)

define('VIDEO_EMBED_CONFIG_FILE', '/youtube/video_embed.yaml'); //path of video embed config file
define('DEBUG', false);
include_once("youtube/video_embed.class.php");


      function debug_log($msg, $file = "debug")
      {
  //    		echo $msg;
      }


  /*
function RemovePseudo3Tags($sText) {
  include ("pseudo3.php");  
  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {
    while (strpos($sText,$aTag[$i][0])) {
	  while ($sText[1]!="]") {
	    $sText=$sText[0].substr($sText,2,(strlen($sText)-2));
	  }
      $sText=$sText[0].substr($sText,2,(strlen($sText)-2));
	}
    while (strpos($sText,$aTag[$i][1])) {
	  $sText=KffReplace($sText,$aTag[$i][1],"");
	}
  }
  return($sText);
  return($sText);
}

function RemovePseudo2Tags($sText) {
  include ("pseudo2.php");
  include ("smilies.php");
  
  $iAnz=sizeof($aSmilies);
  for ($i=0;$i<$iAnz;$i++) {
    while (strpos($sText,$aSmilies[$i][0])) {
	  $sText=KffReplace($sText,$aSmilies[$i][0],"");
	}
  }
  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {
    while (strpos($sText,$aTag[$i][0])) {
	  $sText=KffReplace($sText,$aTag[$i][0],"");
	}
    while (strpos($sText,$aTag[$i][1])) {
	  $sText=KffReplace($sText,$aTag[$i][1],"");
	}
  }
  return($sText);
}
*/
function RemovePseudoTags($sText) {
  $sText=Pseudo2ToHtml($sText);
  $sText=strip_tags($sText);
  return($sText);
}
/*
function Pseudo3ToHtml($sText) {

  $sText="#".$sText;
  include("pseudo3.php");
    
  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {

    $sTag     =$aTag[$i][0];
    $sTagR    =$aTag[$i][1];
    $sHtmlTag =$aTag[$i][2];
    $sHtmlTagR=$aTag[$i][3];

    while ($iPos=strpos($sText,$sTag)) {
 
      $sBeforeTag=substr($sText,0,$iPos);
      $sFromTag  =substr($sText,$iPos,(strlen($sText)-$iPos));

      // Tagende finden
      if (!($iPosTagEnd=strpos($sFromTag,"]"))) {
        // Zwangsweise Schließung der Tag-Öffnung
        $sFromTag=$sFromTag."]";
      }

      // Jetzt wird der Öffnen Tag komplett bestimmt
      $sFromTag="#".$sFromTag;
      $iPosTagOpen= strpos($sFromTag,"[");
      $iPosTagClose=strpos($sFromTag,"]");
      $sTagComplete=substr($sFromTag,$iPosTagOpen,($iPosTagClose-$iPosTagOpen+1));
      // Aus diesem jetzt den eigentlichen Link bestimmen
      $iPosEq=strpos($sTagComplete,"=");
      $sLink=substr($sTagComplete,($iPosEq+1),($iPosTagClose-$iPosEq-2));
      // Wenn kein Schliessen-Tag vorhanden, diesen zwangsweise setzen 
      if (!(strpos($sFromTag,$sTagR))) {
        $sFromTag=$sFromTag.$sTagR;
      }
	
      $bRemove=false;
      switch ($sTag) {
        case "[email=": if (!CheckEmail($sLink)){$bRemove=true;}break;
        // case "[url="  : if (strpos($sLink,"?")) {$bRemove=true;}break;
        case "[color=": if ($sLink[0]=="#") {
                          $sCheckColor=substr($sLink,1,(strlen($sLink)-1));
                          if (!CheckColor($sCheckColor)) {
                            $bRemove=true;
                          }
                        }
                        else {
                          switch ($sLink) {
                            case "black":   $sLink="#000000"; break;
                            case "maroon";  $sLink="#800000"; break;
                            case "green";   $sLink="#008000"; break;
                            case "olive";   $sLink="#808000"; break;
                            case "navy";    $sLink="#000080"; break;
                            case "purple";  $sLink="#800080"; break;
                            case "teal";    $sLink="#008080"; break;
                            case "silver";  $sLink="#0c0c0c"; break;
                            case "gray";    $sLink="#0c0c0c"; break;
                            case "red";     $sLink="#ff0000"; break;
                            case "lime";    $sLink="#00ff00"; break;
                            case "yellow";  $sLink="#ffff00"; break;
                            case "blue";    $sLink="#0000ff"; break;
                            case "fuchsia"; $sLink="#ff00ff"; break;
                            case "aqua";    $sLink="#00ffff"; break;
                            case "white";   $sLink="#ffffff"; break;
                            default: $bRemove=true; break;
                          }
                        }
      }	
		
      if ($bRemove) {      
        $sHtmlTag="";
	    $sHtmlTagR="";
      }
      else {
        $sLink   =KffReplace($sLink,"@","&#64;");
		
		$sReplTmp="#".$sLink; 
		$iOrd_j=ord("j"); $s_j="%".strtoupper(dechex($iOrd_j));
		$iOrd_jj=ord("J"); $s_jj="%".strtoupper(dechex($iOrd_jj));
		  
		  
		while (strpos($sReplTmp,"j")) {
		  $sReplTmp=KffReplace($sReplTmp,"j",$s_j);
		}
		while (strpos($sReplTmp,"J")) {
		  $sReplTmp=KffReplace($sReplTmp,"J",$s_jj);
		}
		$sRepl=substr($sReplTmp,1,strlen($sReplTmp)-1);
		$sLink=KffStripTags($sRepl);
        $sHtmlTag=KffReplace($sHtmlTag,"REPL",$sLink);
      }

      $sFromTag=KffReplace($sFromTag,$sTagComplete,$sHtmlTag);
      $sFromTag=KffReplace($sFromTag,$sTagR,$sHtmlTagR);
      $sFromTag=substr($sFromTag,1,(strlen($sFromTag)-1));  
      $sText=$sBeforeTag.$sFromTag;
      
      $sTag     =$aTag[$i][0];
      $sTagR    =$aTag[$i][1];
      $sHtmlTag =$aTag[$i][2];
      $sHtmlTagR=$aTag[$i][3];
    }
  }
 
  $sText=substr($sText,1,(strlen($sText)-1)); 
  return ($sText);
}

*/

function GetThreadConfig($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbTh1,$DbTh2) {
  $bClose=false;
  if (!$Db) {
    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	  $bClose=true;
	}
	else {
	  return (false);
	}
  }
  
  $aThreadConfig=array();
  $aThreadConfig[kzactive]='';
    $aThreadConfig[kzbold]  ='X';
    $aThreadConfig[kzitalic]='';
    $aThreadConfig[kzulined]='';
    $aThreadConfig[kzframe] ='';
    $aThreadConfig[text]    ='';
    $aThreadConfig[bgcolor] ='';
      $aThreadConfig[sign0]='';
    $aThreadConfig[graf0]='';
    $aThreadConfig[sign1]='';
    $aThreadConfig[graf1]='';
    $aThreadConfig[sign2]='';
    $aThreadConfig[graf2]='';
    $aThreadConfig[sign3]='';
    $aThreadConfig[graf3]='';
    $aThreadConfig[sign4]='';
    $aThreadConfig[graf4]='';
    $aThreadConfig[sign5]='';
    $aThreadConfig[graf5]='';
 /* 
  $sQuery="select * from $DbTh1 where pk='1'";
  $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  if ($DbRow=mysql_fetch_row($DbQuery)) {
    $aThreadConfig[kzactive]=$DbRow[1];
    $aThreadConfig[kzbold]  =$DbRow[2];
    $aThreadConfig[kzitalic]=$DbRow[3];
    $aThreadConfig[kzulined]=$DbRow[4];
    $aThreadConfig[kzframe] =$DbRow[5];
    $aThreadConfig[text]    =$DbRow[6];
    $aThreadConfig[bgcolor] =$DbRow[7];
  }

  $sQuery="select * from $DbTh2 where pk='1'";
  $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  if ($DbRow=mysql_fetch_row($DbQuery)) {
    $aThreadConfig[sign0]=$DbRow[1];
    $aThreadConfig[graf0]=$DbRow[2];
    $aThreadConfig[sign1]=$DbRow[3];
    $aThreadConfig[graf1]=$DbRow[4];
    $aThreadConfig[sign2]=$DbRow[5];
    $aThreadConfig[graf2]=$DbRow[6];
    $aThreadConfig[sign3]=$DbRow[7];
    $aThreadConfig[graf3]=$DbRow[8];
    $aThreadConfig[sign4]=$DbRow[9];
    $aThreadConfig[graf4]=$DbRow[10];
    $aThreadConfig[sign5]=$DbRow[11];
    $aThreadConfig[graf5]=$DbRow[12];
  }
    
  if ($bClose) {
    mysql_close($Db);
  }*/
  return ($aThreadConfig);
}

function GetPicLetter($sDir,$sLetter) {
  switch ($sLetter) {
    case "A": $sPicStr=$sDir."ua.jpg";break;
    case "B": $sPicStr=$sDir."ub.jpg";break;
    case "C": $sPicStr=$sDir."uc.jpg";break;
    case "D": $sPicStr=$sDir."ud.jpg";break;
    case "E": $sPicStr=$sDir."ue.jpg";break;
    case "F": $sPicStr=$sDir."uf.jpg";break;
    case "G": $sPicStr=$sDir."ug.jpg";break;
    case "H": $sPicStr=$sDir."uh.jpg";break;
    case "I": $sPicStr=$sDir."ui.jpg";break;
    case "J": $sPicStr=$sDir."uj.jpg";break;
    case "K": $sPicStr=$sDir."uk.jpg";break;
    case "L": $sPicStr=$sDir."ul.jpg";break;
    case "M": $sPicStr=$sDir."um.jpg";break;
    case "N": $sPicStr=$sDir."un.jpg";break;
    case "O": $sPicStr=$sDir."uo.jpg";break;
    case "P": $sPicStr=$sDir."up.jpg";break;
    case "Q": $sPicStr=$sDir."uq.jpg";break;
    case "R": $sPicStr=$sDir."ur.jpg";break;
    case "S": $sPicStr=$sDir."us.jpg";break;
    case "T": $sPicStr=$sDir."ut.jpg";break;
    case "U": $sPicStr=$sDir."uu.jpg";break;
    case "V": $sPicStr=$sDir."uv.jpg";break;
    case "W": $sPicStr=$sDir."uw.jpg";break;
    case "X": $sPicStr=$sDir."ux.jpg";break;
    case "Y": $sPicStr=$sDir."uy.jpg";break;
    case "Z": $sPicStr=$sDir."uz.jpg";break;
	
    case "a": $sPicStr=$sDir."la.jpg";break;
    case "b": $sPicStr=$sDir."lb.jpg";break;
    case "c": $sPicStr=$sDir."lc.jpg";break;
    case "d": $sPicStr=$sDir."ld.jpg";break;
    case "e": $sPicStr=$sDir."le.jpg";break;
    case "f": $sPicStr=$sDir."lf.jpg";break;
    case "g": $sPicStr=$sDir."lg.jpg";break;
    case "h": $sPicStr=$sDir."lh.jpg";break;
    case "i": $sPicStr=$sDir."li.jpg";break;
    case "j": $sPicStr=$sDir."lj.jpg";break;
    case "k": $sPicStr=$sDir."lk.jpg";break;
    case "l": $sPicStr=$sDir."ll.jpg";break;
    case "m": $sPicStr=$sDir."lm.jpg";break;
    case "n": $sPicStr=$sDir."ln.jpg";break;
    case "o": $sPicStr=$sDir."lo.jpg";break;
    case "p": $sPicStr=$sDir."lp.jpg";break;
    case "q": $sPicStr=$sDir."lq.jpg";break;
    case "r": $sPicStr=$sDir."lr.jpg";break;
    case "s": $sPicStr=$sDir."ls.jpg";break;
    case "t": $sPicStr=$sDir."lt.jpg";break;
    case "u": $sPicStr=$sDir."lu.jpg";break;
    case "v": $sPicStr=$sDir."lv.jpg";break;
    case "w": $sPicStr=$sDir."lw.jpg";break;
    case "x": $sPicStr=$sDir."lx.jpg";break;
    case "y": $sPicStr=$sDir."ly.jpg";break;
    case "z": $sPicStr=$sDir."lz.jpg";break;
	
    case "0": $sPicStr=$sDir."0.jpg";break;
    case "1": $sPicStr=$sDir."1.jpg";break;
    case "2": $sPicStr=$sDir."2.jpg";break;
    case "3": $sPicStr=$sDir."3.jpg";break;
    case "4": $sPicStr=$sDir."4.jpg";break;
    case "5": $sPicStr=$sDir."5.jpg";break;
    case "6": $sPicStr=$sDir."6.jpg";break;
    case "7": $sPicStr=$sDir."7.jpg";break;
    case "8": $sPicStr=$sDir."8.jpg";break;
    case "9": $sPicStr=$sDir."9.jpg";break;
	
    case ".": $sPicStr=$sDir."dot.jpg";break;
    case "@": $sPicStr=$sDir."at.jpg";break;
    case "-": $sPicStr=$sDir."minus.jpg";break;
    case "_": $sPicStr=$sDir."uscore.jpg";break;
  }
  
  $aPic=getimagesize($sPicStr);
  $iWidth=$aPic[0];
  $iHeight=$aPic[1];
  $sPic="<img src=\"$sPicStr\" width=$iWidth height=$iHeight border=0 alt=\"\">";
  return ($sPic);
}

function ClearLockTable ($DbHost,$DbName,$DbUser,$DbPass,$DbTab) {

  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $iTime = time();

    mysql_db_query($DbName,
                   "delete from $DbTab
                           where time      < $iTime
                           and   adminlock = '-'"
                   ,$Db);

    mysql_close($Db);
  }
}

function EnterLockSet ($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$LockTime,$sRemoteAddr) {

  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {

    $iTime = time()+$LockTime;

    mysql_db_query($DbName,
                   "insert into $DbTab
                           set   ipaddr    = '$sRemoteAddr',
                                 time      = $iTime,
                                 uses      = 1,
                                 adminlock = '-'"
                   ,$Db);

    mysql_close($Db);
    return (true);
  }
  return (false);
}

function CheckLockSet ($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$LockTime,$MaxUses,$sRemoteAddr) {

  ClearLockTable ($DbHost,$DbName,$DbUser,$DbPass,$DbTab);

  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $DbQuery=mysql_db_query($DbName,
                            "select * from $DbTab
                                      where ipaddr = '$sRemoteAddr'"
                            ,$Db);
    if ($DbRow=mysql_fetch_row($DbQuery)) {
      if ($DbRow[2]>=$MaxUses) {
        mysql_close($Db);
        return (false);
      }
      
      if ($DbRow[3]=="X") {
        mysql_close($Db);
        return (false);
      }

      $iUses = $DbRow[2] + 1;
      mysql_db_query($DbName,
                     "update $DbTab set uses=$iUses
                             where ipaddr = '$sRemoteAddr'"
                     ,$Db);  

      mysql_close($Db);
      return (true);
    }
    else {
      mysql_close($Db);
      $bResult = EnterLockSet ($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$LockTime,$sRemoteAddr);
      return ($bResult);
    }  
  }  
  return (false);
}

function CheckSessionValid($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser,$sSessid,$sSipaddr,$MaxLoginTime) {
  $bReturn=false;
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $iTime=time();
    $DbQuery=mysql_db_query($DbName,
	                        "select * from $DbAdm where userid ='$sUser'
							                      and   kzactiv='X'
							                      and   sessid ='$sSessid'
												  and   kzsval ='X'
												  and   sipadr ='$sSipaddr'
												  and   stime >=$iTime"
	                        ,$Db);
    if ($DbRow=mysql_fetch_row($DbQuery)) {
	  $iTime=$iTime+$MaxLoginTime;
	  $bReturn=mysql_db_query($DbName,"update $DbAdm set stime=$iTime where userid ='$sUser'",$Db);
	}
    mysql_close($Db);
  }
  return($bReturn);
}

function AddConfigParam($Param,$Value) {
  $aConfig=ReadConfigFile();
  if (!GetRamValue($Param,$aConfig)) {
    $iAnz=sizeof($aConfig);
    $iAnz_1=$iAnz-1;
    $aConfig[$iAnz]=$aConfig[$iAnz_1];
    $aConfig[$iAnz_1]=$Param."=\"$Value\";";
    WriteConfigFile($aConfig);
  }
}

function ReadTitleCode() {
  $bFound=false;

  if (file_exists("cfg/title.txt")) {
    if (filesize("cfg/title.txt")>0) {
	if ($hndFile=fopen("cfg/title.txt","r-")) {
	  $iLen=filesize("cfg/title.txt");
	  $s=fread($hndFile,$iLen);
	  $sTitle=chop($s);
	  $sTitle=stripcslashes($sTitle);
	  fclose($hndFile);
	  $bFound=true;
	}
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

function ReadArcPostText($sFile,$sPostNo) {
  if ($hndFile=fopen($sFile,"r-")) {
    $bFound = false;
    while ($s=fgets($hndFile,100)) {
      if (chop($s)=="[BEGIN_DB]") {
        $s=fgets($hndFile,100);
        if (chop($s)==$sPostNo) {
          while ($s=fgets($hndFile,100)) {
            if (chop($s)=="[BEGIN_TEXT]") {
			  $sTmp=fgets($hndFile,100); $s=chop($sTmp);
			  $iLen=intval($s);
              $sText=@fread($hndFile,$iLen);
			  $bFound=true;
              break;
            }
          }
        }
      }
      if ($bFound) {
        break;
      }
    }
    fclose($hndFile);
  }

  if (!$bFound) {
    return (false);
  }
  return ($sText);
}

function ReadPosts($sFile) {
  $iCount=0;

  if ($hndFile=fopen($sFile,"r-")) {
    $aPosts=array();
    while ($s=fgets($hndFile,100)) {
      if (chop($s)=="[BEGIN_DB]") {
        $sTmp      = fgets($hndFile,100); $sNo=chop($sTmp);
        $sTmp    = fgets($hndFile,100); $sPreno=chop($sTmp);
        $sTmp   = fgets($hndFile,100); $sAuthor=chop($sTmp);
        $sTmp    = fgets($hndFile,100); $sEmail=chop($sTmp);
        $sTmp  = fgets($hndFile,100); $sRegular=chop($sTmp);
        $sTmp     = fgets($hndFile,100); $sDate=chop($sTmp);
        $sTmp     = fgets($hndFile,100); $sTime=chop($sTmp);
        $sTmp   = fgets($hndFile,100); $sPicurl=chop($sTmp);
        $sTmp  = fgets($hndFile,100); $sHomeurl=chop($sTmp);
        $sTmp = fgets($hndFile,100); $sHomename=chop($sTmp);
        $sTmp  = fgets($hndFile,100); $sSubject=chop($sTmp);
        $sTmp      = fgets($hndFile,100); $sDel=chop($sTmp);
        $sTmp   = fgets($hndFile,100); $sTclose=chop($sTmp);

        if ($sDel=="-") {
          $aPosts[$iCount][0]=intval($sNo);
          $aPosts[$iCount][1]=intval($sPreno);
          $aPosts[$iCount][2]=$sAuthor;
          $aPosts[$iCount][3]=$sEmail;
          $aPosts[$iCount][4]=$sRegular;
          $aPosts[$iCount][5]=$sDate;
          $aPosts[$iCount][6]=$sTime;
          $aPosts[$iCount][7]=$sPicurl;
          $aPosts[$iCount][8]=$sHomeurl;
          $aPosts[$iCount][9]=$sHomename;
          $aPosts[$iCount][10]=$sSubject;
          $aPosts[$iCount][11]=$sDel;
          $aPosts[$iCount][12]=$sTclose;
          $iCount++;
        }
      }
    }
    fclose($hndFile);
	clearstatcache();
  }
    
  if ($iCount>0) {
    rsort($aPosts);
    return ($aPosts);
  }

  return (false);
}


function EchoArcive($sFile,$bFirst,$aPosts,$MsgDeep,$MsgNo,$bThreadOnly,
                    $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
                    $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sLinkNo) {
  if ($bFirst) {
    if (!$aPosts) {
      unset($aPosts);
    }
    $aPosts=ReadPosts($sFile);
  }
  
  $bSubFirst=false;

  $iAnz=sizeof($aPosts);
  for ($i=0;$i<$iAnz;$i++) {
    if ($aPosts[$i][1] == $MsgNo) {
       
      // Den Beitrag darstellen
      if ($MsgNo==0) {
        // Sonderdarstellung Threadbegin ein
        $bThreadBegin=true;
	    if (($aThreadConfig[kzactive]=="X") && ($aThreadConfig[kzframe]=="X") && (!$bThreadOnly)) {
	      echo "<table cellspacing=2 cellpadding=0 border=1 width=100%><tr><td align=left valign=top>";
	    }
      }
      else {
      // Sonderdarstellung Threadbegin aus
        $bThreadBegin=false;
      }
      $aPosting=$aPosts[$i];
      EchoArcThread($aPosting,$bThreadBegin,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$MsgDeep,
                    $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sFile,$sLinkNo);
 
      // Die untergordneten Beiträge ausgeben wenn gewünscht
      if (!$bThreadOnly) {
        $iSubMsgDeep=$MsgDeep+1;
        $iSubMsgNo=$aPosts[$i][0];
        EchoArcive($sFile,$bSubFirst,$aPosts,$iSubMsgDeep,$iSubMsgNo,$bThreadOnly,
                   $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
                   $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sLinkNo);
      }
	  if ($MsgNo==0) {
	    if (($aThreadConfig[kzactive]=="X") && ($aThreadConfig[kzframe]=="X") && (!$bThreadOnly)) {
	      echo "</td></tr></table><br>";
	    }
      }
    }
  }
  return ($aPosts);
}

function EchoArcThread($aPosting,$bThreadBegin,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$MsgDeep,
              $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sFile,$sLinkNo) {

  global $aC;
  include ("cfg/config.php");
  
  $aThreadConfig=GetThreadConfig($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbTh1,$DbTh2);
  if (($RegsActive=="X") && (($aPosting[4]=="R") || ($aPosting[4]=="A"))) {
    $Db=NULL;
    $aPosting[2]=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$aPosting[2],$aPosting[4],$RegColor,$AdminColor,$RegsSameCol);
  }

  $sBlanks="";
  for ($i=0;$i<$MsgDeep;$i++) {
    $sBlanks.="&nbsp;&nbsp;&nbsp;&nbsp;";
  }

  if ($bThreadBegin) {
    if ($aThreadConfig[kzactive]=="X") {
	  if ($aThreadConfig[kzframe]!="X") {
	    echo "<br>";
	  }
	  if ($aThreadConfig[bgcolor]!=$BodyBgcolor) {
	    echo "<table cellspacing=0 cellpadding=0 width=100%><tr><td  align=left valign=top bgcolor=#".$aThreadConfig[bgcolor].">";
	  }
	  if ($aThreadConfig[kzbold]=="X") {
	    echo "<b>";
	  }
	  if ($aThreadConfig[kzitalic]=="X") {
	    echo "<i>";
	  }
	  if ($aThreadConfig[kzulined]=="X") {
	    echo "<u>";
	  }
	  echo "<font color=#".$aThreadConfig[text].">";
	}
	else {
      echo "<br><b>";
	}
  }
  echo $sBlanks;
  if (($aThreadConfig[kzactive]=="X") && (!$bNoSigns)) {
    switch($MsgDeep) {
	  case 0:
	    if (strlen($aThreadConfig[graf0])>1) {
		  $aPic=getimagesize($aThreadConfig[graf0]);
		  $sPic=$aThreadConfig[graf0];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign0]!=" ") {
		  echo $aThreadConfig[sign0]."&nbsp;";
		}
	    break;
		
	  case 1:
	    if (strlen($aThreadConfig[graf1])>1) {
		  $aPic=getimagesize($aThreadConfig[graf1]);
		  $sPic=$aThreadConfig[graf1];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign1]!=" ") {
		  echo $aThreadConfig[sign1]."&nbsp;";
		}
	    break;
		
	  case 2:
	    if (strlen($aThreadConfig[graf2])>1) {
		  $aPic=getimagesize($aThreadConfig[graf2]);
		  $sPic=$aThreadConfig[graf2];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign2]!=" ") {
		  echo $aThreadConfig[sign2]."&nbsp;";
		}
	    break;
		
	  case 3:
	    if (strlen($aThreadConfig[graf3])>1) {
		  $aPic=getimagesize($aThreadConfig[graf3]);
		  $sPic=$aThreadConfig[graf3];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign3]!=" ") {
		  echo $aThreadConfig[sign3]."&nbsp;";
		}
	    break;
		
	  case 4:
	    if (strlen($aThreadConfig[graf4])>1) {
		  $aPic=getimagesize($aThreadConfig[graf4]);
		  $sPic=$aThreadConfig[graf4];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign4]!=" ") {
		  echo $aThreadConfig[sign4]."&nbsp;";
		}
	    break;
		
	  case 5:
	  default:
	    if (strlen($aThreadConfig[graf5])>1) {
		  $aPic=getimagesize($aThreadConfig[graf5]);
		  $sPic=$aThreadConfig[graf5];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign5]!=" ") {
		  echo $aThreadConfig[sign5]."&nbsp;";
		}
	    break;
	}
  }

  $sNo=strval($aPosting[0]);
  $sUrl="showarcentry.php?sNo=$sNo&sFile=$sFile&sLinkNo=$sLinkNo";
  
  if ($EnablePersonalNewInfo=="X") {
    $iNo=intval($sNo);
	if (isset($aC[$iNo])) {
	  $aPic=getimagesize("graphics/old.gif"); $iWidth=$aPic[0]; $iHeight=$aPic[1];
	  echo "<img src=\"graphics/old.gif\" alt=\"gelesen\" width=\"$iWidth\" height=\"$iHeight\" border=\"0\">&nbsp;";
	}
	else {
	  $aPic=getimagesize("graphics/old.gif"); $iWidth=$aPic[0]; $iHeight=$aPic[1];
	  echo "<img src=\"graphics/new.gif\" alt=\"neu, ungelesen\" width=\"$iWidth\" height=\"$iHeight\" border=\"0\">&nbsp;";
	}
  }
  echo "<a href=\"$sUrl\">".$aPosting[10]."</a>";
  echo "&nbsp;-&nbsp;<b>".$aPosting[2]."</b>&nbsp;-&nbsp;".$aPosting[5]."&nbsp;".$aPosting[6];
  if ($aPosting[12]=="X") {
    echo "&nbsp;-&nbsp;(abgeschlossen)";
  }
  echo "<br>";
    
  if ($bThreadBegin) {
    if ($aThreadConfig[kzactive]=="X") {
	  echo "</font>";
	  if ($aThreadConfig[kzulined]=="X") {
	    echo "</u>";
	  }
	  if ($aThreadConfig[kzitalic]=="X") {
	    echo "</i>";
	  }
	  if ($aThreadConfig[kzbold]=="X") {
	    echo "</b>";
      }
	  if ($aThreadConfig[bgcolor]!=$BodyBgcolor) {
	    echo "</td></tr></table>";
	  }
	}
    else {
      echo "</b>";
	}
  }
}

//********************************************************

function EchoArchiveLinks($aArchive) {

/*  if (!$aArchive) {
    return;    
  }

  echo "<br><hr>";
  echo "<center><b>Archive des Forums</b>
        <table border=\"0\" cellsapcing=\"0\" cellpadding=\"0\">\n";
  $iAnz=sizeof($aArchive);
  $iCnt=0;
  for ($i=$iAnz;$i>=1;$i--) {
    if ($iCnt==0) {
	  echo "<tr>\n";
	}
	$iCnt++;
    $iIdx=$i-1;
    $sFile=$aArchive[$iIdx];
	$sLinkNo=substr($sFile,0,strpos($sFile,".txt"));
	$sLinkNo=substr($sLinkNo,5,(strlen($sLinkNo)-5));
    echo "<td align=\"left\" valign=\"top\">[ <a href=\"archive.php?sLinkNo=$sLinkNo&sFile=$sFile\">$sLinkNo</a> ]&nbsp;</td>\n";

	if ($iCnt==20) {
	  echo "</tr>\n";
	  $iCnt=0;
	}
  }
  if ($iCnt!=0) {
    while ($iCnt<20) {
	  $iCnt++;
	  echo "<td>&nbsp;</td>\n";
	}
	echo "</tr>\n";
  }
  echo "</table></center>\n";
  echo "<hr>\n";  */
}

function BuildArchiveList() {

  $iCount=0;

  if ($hndDir=opendir("arc/")) {

    $aTempArchive=array();

    while ($sFileName=readdir($hndDir)) {
      if (($sFileName!=".") && ($sFileName!="..") && ($sFileName!="dummy.dum")) {
        $sFile="arc/".$sFileName;
        $aTempArchive[$iCount]=$sFile;
        $iCount++;
      }
    }
    closedir($hndDir);
  }

  // Jetzt bestimmen, ob in den Archiven sichtbare Beiträge stehen
  $aArchive=Array(); $iCount=0;
  $iAnz=sizeof($aTempArchive);

  for ($i=0;$i<$iAnz;$i++) {
    if ($hndFile=fopen($aTempArchive[$i],"r-")) {
      while ($s=fgets($hndFile,100)) {
        if (chop($s)=="[BEGIN_DB]") {
          for ($k=0;$k<11;$k++) {
            $s=fgets($hndFile,100);
          }
          $s=fgets($hndFile,100);
          if (chop($s)=="-") {
            $aArchive[$iCount]=$aTempArchive[$i];
            $iCount++;
            break;
          } 
        }
      }
      fclose($hndFile);
    }
  }

  if ($iCount>0) {
    $iAnz=sizeof($aArchive);
	for ($i=0;$i<($iAnz-1);$i++) {
	  for ($k=($i+1);$k<$iAnz;$k++) {
		$sI=substr($aArchive[$i],0,strpos($aArchive[$i],".txt"));
		$iI=intval(substr($sI,5,(strlen($sI)-5)));
		$sK=substr($aArchive[$k],0,strpos($aArchive[$k],".txt"));
		$iK=intval(substr($sK,5,(strlen($sK)-5)));
		if ($iK<$iI) {
		  $sHelp=$aArchive[$i];
		  $aArchive[$i]=$aArchive[$k];
		  $aArchive[$k]=$sHelp;
		}
	  }
//	  echo "<br>";
	}
    return ($aArchive);
  }

  return (false);
}
function EncryptText($sText) {
  $sWrkTxt=$sText;

  $sWrkTxt=str_replace(";","&#59;",$sWrkTxt);
  $sWrkTxt=str_replace("\"","&#34;",$sWrkTxt);
  $sWrkTxt=str_replace("&","&#38;",$sWrkTxt);
  $sWrkTxt=str_replace("'","&#27;",$sWrkTxt);
  $sWrkTxt=str_replace("*","&#42;",$sWrkTxt);
  $sWrkTxt=str_replace(chr(13),"&#13;",$sWrkTxt);
  $sWrkTxt=str_replace(chr(10),"&#10;",$sWrkTxt);
  $sWrkTxt=str_replace("€","&#x80;",$sWrkTxt);
  
  return ($sWrkTxt);
}
function DecryptText($sText) {
  $sWrkTxt=$sText;

  $sWrkTxt=str_replace("&#x80;","€",$sWrkTxt);
  $sWrkTxt=str_replace("&#10;",chr(10),$sWrkTxt);
  $sWrkTxt=str_replace("&#13;",chr(13),$sWrkTxt);
  $sWrkTxt=str_replace("&#42;","*",$sWrkTxt);
  $sWrkTxt=str_replace("&#27;","'",$sWrkTxt);
  $sWrkTxt=str_replace("&#38;","&",$sWrkTxt);
  $sWrkTxt=str_replace("&#34;","\"",$sWrkTxt);
  $sWrkTxt=str_replace("&#59;",";",$sWrkTxt);
  
  return ($sWrkTxt);
}

function ArchiveThread($DbName,$DbTab,$Db,$iNo,$hndFile) {
   // Den Eintrag selbst sichern

   fwrite($hndFile,"V1.00\n");

   fwrite($hndFile,"[BEGIN_POST]\n");

   // Den Datenbankeinrag schreiben und aus der DB löschen
   fwrite($hndFile,"[BEGIN_DB]\n");
   $DbQuery=mysql_db_query($DbName,
                           "select * from $DbTab where no=$iNo and del<>'T'"
                           ,$Db);
   $DbRow=mysql_fetch_row($DbQuery);
   
// Encryptions des Imports decrypten   
   $DbRow[2] =DecryptText($DbRow[2]);
   $DbRow[10]=DecryptText($DbRow[10]);
   $DbRow[13]=DecryptText($DbRow[13]);
   
   for ($i=0;$i<14;$i++) {
     if ($i==13) {continue;}
     fwrite($hndFile,$DbRow[$i]);fwrite($hndFile,"\n");
   }
   mysql_db_query($DbName,
                  "delete from $DbTab where no=$iNo"
                  ,$Db);
   fwrite($hndFile,"[END_DB]\n");
   
   // Logfile archivieren
/*   $sNo=strval($iNo);
   $sLogFile="data/".$sNo.".log";
   if ($hndF=fopen($sLogFile,"r-")) {
     $sTmp=fread($hndF,filesize($sLogFile));
	 $sText=chop($sTmp);
	 fclose($hndF);
	 fwrite($hndFile,"[BEGIN_LOG]\n");
	 fwrite($hndFile,$sText);fwrite($hndFile,"\n");
	 fwrite($hndFile,"[END_LOG]\n");
	 unlink($sLogFile);
   }
   clearstatcache();*/
  
   // Textfile archivieren
//   $sNo=strval($iNo);
//   $sLogFile="data/".$sNo.".txt";*/
   $sText=$DbRow[13];
        $sText=stripcslashes($sText);
//   if ($hndF=fopen($sLogFile,"r-")) {
//     $iSize=filesize($sLogFile);
     fwrite($hndFile,"[BEGIN_TEXT]\n");
        $sSize=strval(strlen($sText));
     fwrite($hndFile,$sSize);fwrite($hndFile,"\n");
//     $sTmp=fread($hndF,$iSize);
//     $sText=chop($sTmp);
//     fclose($hndF);
     fwrite($hndFile,$sText);fwrite($hndFile,"\n");
     fwrite($hndFile,"[END_TEXT]\n");
//     unlink($sLogFile);
//   }
//   clearstatcache();  
   fwrite($hndFile,"[END_POST]\n");
   
  // Jetzt alle untergeordneten Beiträge Speichern   
  $DbQuery=mysql_db_query($DbName,
                          "select * from $DbTab where preno=$iNo"
						  ,$Db);
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    ArchiveThread($DbName,$DbTab,$Db,$DbRow[0],$hndFile);
  }					  
}

function ArchiveForum2 ($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbArc,
                      $PostsInForum,$PostsInArc) {
				
  $bArc=false;$iArcCnt=0;
  $Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass);					  
  while (!$bEnd) {
    $Q=@mysql_query("select no from $DbTab where preno=0 and del<>'T' order by no asc");
    if (@mysql_num_rows($Q)>=50) {
	  $bArc=true;
      $DbQuery=mysql_db_query($DbName,
                              "select * from $DbArc"
                              ,$Db);
      if ($DbRow=mysql_fetch_row($DbQuery)) {
        $iNoArc=$DbRow[0];$iArcCnt++;
        // Nummer für nächste Archivierung um 1 inkrementieren
        $iNoNewArc=$iNoArc+1;
        mysql_db_query($DbName,
                       "update $DbArc set arcno=$iNoNewArc"
                       ,$Db);
      }
      $sFile="arc/a".strval($iNoNewArc).".txt";
	  $hndFile=fopen($sFile,"w+");
      for ($i=0;$i<30;$i++) {
	    $R=@mysql_fetch_object($Q);
	    $iNo=$R->no;
	    ArchiveThread($DbName,$DbTab,$Db,$iNo,$hndFile);
	  }
	  fclose($hndFile);
    }
	else {
	  $bEnd = true;
	}
	if ($iArcCnt>=100) {$bEnd = true;}
	break;
  }
  @mysql_close();
  if ($bArc) {
    return ("ARC");
  }
  else {
    return ("NO_ARC");
  }
}
function ArchiveForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbArc,
                      $PostsInForum,$PostsInArc) {
					  
  ArchiveForum2($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbArc,
                $PostsInForum,$PostsInArc);					  
  return;				
					  
  $sMode='X';
  if (!SetArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbArc,$sMode)) {
    return ("NO_ARC");
  }

  $sCountWhat="A";
  if (!(CountArcPosts($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$PostsInForum,$PostsInArc,$sCountWhat)>=$PostsInArc)) {
    // Keine Archivierung nötig
    $sMode='-';
    SetArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbArc,$sMode);
    return ("NO_ARC");
  }

  if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
    return ("NO_ARC");
  }
  
  // Die Nummer des zu generierenden Archives lesen
  $DbQuery=mysql_db_query($DbName,
                          "select * from $DbArc"
                          ,$Db);
  if ($DbRow=mysql_fetch_row($DbQuery)) {
    $iNoArc=$DbRow[0];
    // Nummer für nächste Archivierung um 1 inkrementieren
    $iNoNewArc=$iNoArc+1;
    mysql_db_query($DbName,
                   "update $DbArc set arcno=$iNoNewArc"
                   ,$Db);
  }

  $sArcNo=strval($iNoArc);
  $sFileArc="arc/a".$sArcNo.".txt";
  
  $hndFile=fopen($sFileArc,"w+");

  $iForum=0;
  $DbQuery=mysql_db_query($DbName,
                          "select * from $DbTab
                                    where preno=0
									and   del<>'T'
                                    order by no desc"
                          ,$Db);

  $sDel="?";
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    if ($iForum<$PostsInForum) {
      $iForum=$iForum+CountPostsInThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$Db,$bFirst,$DbRow[0],$sDel);
    }
    else {
      ArchiveThread($DbName,$DbTab,$Db,$DbRow[0],$hndFile);
    }
  }

  fclose ($hndFile);
  $sMode='-';
  SetArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbArc,$sMode);
  return ("ARC");
}

function EchoForumLayoutParsimony($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sDelSign="-",$iActPage=0,$iMaxPages=0) {
				   
  global $ThreadsPerPage,$OrderThreadsByNewestsPost;

  $sLimit="";
  if ($bFirst) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
	  return;
	}
	if (($iMaxPages>1) && ($sDelSign=="T")) {
	  echo "<center><b>Seiten des Forums</b>
	        <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			
      $iStartPage = $iActPage-12;	
	  if ($iStartPage<1) {$iStartPage=1;}
      $iEndPage   = $iActPage+12;			
	  if ($iEndPage>$iMaxPages) {$iEndPage=$iMaxPages;}
			
	  echo "<tr>\n";
	  
	  if ($iActPage>1) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=1\">&lt;&lt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  $iPageJump=$iActPage-24;
	  if ($iPageJump>0) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iPageJump\">&lt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  

      for ($i=$iStartPage;$i<=$iEndPage;$i++) {
		
		if ($i==$iActPage) {
		  echo "<td align=\"left\" valign=\"top\"><b>$i</b>&nbsp;&nbsp;</td>\n";
		}
		else {
		  echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$i\">$i</a>&nbsp;&nbsp;</td>\n";
		}
	  }
	  $iPageJump=$iActPage+24;
	  if ($iPageJump<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iPageJump\">&gt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  if ($iActPage<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iMaxPages\">&gt;&gt;</a></td>\n";
	  }
	  echo "</tr>\n";
			
      echo "</table></center><hr>\n";
	}
	if (($iMaxPages>1) && ($sDelSign=="-")) {
      $iStart=($iActPage-1)*$ThreadsPerPage;
	  $sLimit="limit $iStart,$ThreadsPerPage";
	}
  }		   

  $aConfig=ReadConfigFile();
  $sSortDir=GetRamValue("\$ReverseOrderInThread",$aConfig);
  if (($sSortDir=="X") && ($MsgNo!=0)) {
    $sSortDir="asc";
  }
  else {
    $sSortDir="desc";
  }
				   
  if (($OrderThreadsByNewestsPost=="X") && ($bFirst) && ($MsgNo==0)) {
    $aThreads=ReadThreadsOrderByNewestsPost($Db,$DbHost,$DbUser,$DbPass,$DbName,$DbTab,$sDelSign,$ThreadsPerPage,$iActPage);
	$iThreads=sizeof($aThreads);
  }
  else {
    $DbQuery=mysql_db_query($DbName,
                            "select no from $DbTab
                                        where preno=$MsgNo
                                        and   del  ='$sDelSign'
                                        order by no $sSortDir
									    $sLimit",$Db);
    $aThreads=array();$iThreads=0;
	while ($R=@mysql_fetch_object($DbQuery)) {
	  $aThreads[$iThreads]=$R->no;
	  $iThreads++;
	}
  }				   
			
  if ($iThreads>0) {
    echo "<ul>\n";
    for ($i=0;$i<$iThreads;$i++) {
      $Q=@mysql_query("select no,preno,author,email,regular,
                                    date,time,picurl,homeurl,homename,
                                    subject,del,tclose
		  							    from $DbTab
                                        where no  =".$aThreads[$i]."
                                        and   del ='$sDelSign'");
      $R=@mysql_fetch_object($Q);										
      $sShowAuthor=$R->author;
	  $sShowAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sShowAuthor,$R->regular,$RegColor,$AdminColor,$RegsSameCol);
      echo "<li><a href=\"showentry.php?sNo=$R->no\">".DecryptText($R->subject)."</a> - <b>".DecryptText($sShowAuthor)."</b> - <i>$R->date $R->time</i>\n";
	  $QQ=@mysql_query("select no from $DbTab where preno=$R->no and del<>'X' limit 0,1");
	  if ((@mysql_num_rows($QQ)>0) && (!$bThreadOnly)) {
        $iSubMsgDeep=$MsgDeep+1;
        $bSubFirst  =false;
        EchoForumLayoutParsimony($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,$R->no,
	                             $iSubMsgDeep,$Db,$bSubFirst,$bThreadOnly,
	                             $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive);
	  }
	  echo "</li>";
    }
	echo "</ul>\n";
  }
				   
/*  $Q=@mysql_query("select no from $DbTab where preno=$MsgNo and del='$sDelSign' limit 0,1");
  
  if (@mysql_num_rows($Q)>0) {
    echo "<ul>\n";
	
    $DbQuery=mysql_db_query($DbName,
                            "select no,preno,author,email,regular,
                                    date,time,picurl,homeurl,homename,
                                    subject,del,tclose
		  							    from $DbTab
                                        where preno=$MsgNo
                                        and   del  ='$sDelSign'
                                        order by no $sSortDir
									    $sLimit",$Db);
										
    while ($R=mysql_fetch_object($DbQuery)) {
      $sShowAuthor=$R->author;
	  $sShowAuthor=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sShowAuthor,$R->regular,$RegColor,$AdminColor,$RegsSameCol);
      echo "<li><a href=\"showentry.php?sNo=$R->no\">".DecryptText($R->subject)."</a> - <b>".DecryptText($sShowAuthor)."</b> - <i>$R->date $R->time</i>\n";
	  $QQ=@mysql_query("select no from $DbTab where preno=$R->no and del<>'X' limit 0,1");
	  if ((@mysql_num_rows($QQ)>0) && (!$bThreadOnly)) {
        $iSubMsgDeep=$MsgDeep+1;
        $bSubFirst  =false;
        EchoForumLayoutParsimony($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,$R->no,
	                             $iSubMsgDeep,$Db,$bSubFirst,$bThreadOnly,
	                             $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive);
	  }
	  echo "</li>\n";
    }
	echo "</ul>\n";
  }	   */
  
  if ($bFirst) {
    // Auf Ebene des Erstaufrufes die Datenbankverbindung wieder schliessen
    mysql_close($Db);
  }
}

function EchoForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sDelSign="-",$iActPage=0,$iMaxPages=0) {

  global $ThreadsPerPage,$ParsimonyLayout;
  				    
  if ($ParsimonyLayout=="X") {					
    EchoForumLayoutParsimony($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sDelSign,$iActPage,$iMaxPages);
    return;				   
  }
					
  $sLimit="";
  if ($bFirst) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
	  return;
	}
	if (($iMaxPages>1) && ($sDelSign=="T")) {
	  echo "<center><b>Seiten des Forums</b>
	        <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			
      $iStartPage = $iActPage-12;	
	  if ($iStartPage<1) {$iStartPage=1;}
      $iEndPage   = $iActPage+12;			
	  if ($iEndPage>$iMaxPages) {$iEndPage=$iMaxPages;}
			
	  echo "<tr>\n";
	  
	  if ($iActPage>1) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=1\">&lt;&lt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  $iPageJump=$iActPage-24;
	  if ($iPageJump>0) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iPageJump\">&lt;</a>&nbsp;&nbsp;</td>\n";
	  }
      for ($i=$iStartPage;$i<=$iEndPage;$i++) {
		
		if ($i==$iActPage) {
		  echo "<td align=\"left\" valign=\"top\"><b>$i</b>&nbsp;&nbsp;</td>\n";
		}
		else {
		  echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$i\">$i</a>&nbsp;&nbsp;</td>\n";
		}
	  }
	  $iPageJump=$iActPage+24;
	  if ($iPageJump<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iPageJump\">&gt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  if ($iActPage<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"index.php?iActPage=$iMaxPages\">&gt;&gt;</a></td>\n";
	  }
	  echo "</tr>\n";
			
      echo "</table></center><hr>\n";
	}
	if (($iMaxPages>1) && ($sDelSign=="-")) {
      $iStart=($iActPage-1)*$ThreadsPerPage;
	  $sLimit="limit $iStart,$ThreadsPerPage";
	}
  }		   

  $aConfig=ReadConfigFile();
  $DbTh1=GetRamValue("\$DbTh1",$aConfig);
  $DbTh2=GetRamValue("\$DbTh2",$aConfig);
  $aThreadConfig=GetThreadConfig($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbTh1,$DbTh2);
  // Alle Nachrichten lesen, die nicht gelöscht sind und auf die zu
  // Bearbeitenen Nachricht verweisen
  // Bei Nachricht 0 sind das automatisch die Beginne von Threads
  $aConfig=ReadConfigFile();
  $sSortDir=GetRamValue("\$ReverseOrderInThread",$aConfig);
  if (($sSortDir=="X") && ($MsgNo!=0)) {
    $sSortDir="asc";
  }
  else {
    $sSortDir="desc";
  }
    $DbQuery=mysql_db_query($DbName,
                            "select no,preno,author,email,regular,
                                        date,time,picurl,homeurl,homename,
                                        subject,del,tclose
		  							  from $DbTab
                                      where preno=$MsgNo
                                      and   del  ='$sDelSign'
                                      order by no $sSortDir
									  $sLimit"
                          ,$Db);
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    // Der Reihe Nach alle Nachrichten auf gleicher Hierarchiebene schreiben
	if ($MsgNo==0) {
	  // Auf spezielle Darstellung Threadbeginn schalten
	  $bThreadBegin = true;	
	  if (($aThreadConfig[kzactive]=="X") && ($aThreadConfig[kzframe]=="X") && (!$bThreadOnly)) {
	    echo "<table cellspacing=2 cellpadding=0 border=1 width=100%><tr><td  align=left valign=top>";
	  }
	} 
	else {
	  // Kein Threadbeginn
	  $bThreadBegin = false;
	}
	$DbRow[2] = DecryptText($DbRow[2]);
	$DbRow[10]= DecryptText($DbRow[10]);
	$DbRow[13]= DecryptText($DbRow[13]);
    EchoThread($DbRow,$MsgDeep,$DbRegTab,$Db,$bThreadBegin,
	           $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
			   $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$aC);
    // aber auch alle untergeordneten Nachrichten wenn gewünscht
	if (!$bThreadOnly) {
      $iSubMsgDeep=$MsgDeep+1;
      $bSubFirst  =false;
      EchoForum($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,$DbRow[0],$iSubMsgDeep,$Db,$bSubFirst,$bThreadOnly,
	            $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive);
	}
	if ($MsgNo==0) {
	  if (($aThreadConfig[kzactive]=="X") && ($aThreadConfig[kzframe]=="X") && (!$bThreadOnly)) {
	    echo "</td></tr></table><br>";
	  }
    }
  }  

  if ($bFirst) {
    // Auf Ebene des Erstaufrufes die Datenbankverbindung wieder schliessen
    mysql_close($Db);
  }
}

function EchoThread($DbRow,$MsgDeep,$DbRegTab,$Db,$bThreadBegin,
                    $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
					$RegColor,$AdminColor,$RegsSameCol,$RegsActive,
					$bNoSigns=false) {

  global $aC;
  include("cfg/config.php");
					
  $aThreadConfig=GetThreadConfig($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbTh1,$DbTh2);
					
  if (($RegsActive=="X") && (($DbRow[4]=="R") || ($DbRow[4]=="A"))) {
    $DbRow[2]=ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,DecryptText($DbRow[2]),$DbRow[4],$RegColor,$AdminColor,$RegsSameCol);
  }
    
  $sBlanks="";
  for ($i=0;$i<$MsgDeep;$i++) {
    $sBlanks.="&nbsp;&nbsp;&nbsp;&nbsp;";
  }
  
  if ($bThreadBegin) {
    if ($aThreadConfig[kzactive]=="X") {
	  if ($aThreadConfig[kzframe]!="X") {
	    echo "<br>";
	  }
	  if ($aThreadConfig[bgcolor]!=$BodyBgcolor) {
	    echo "<table cellspacing=0 cellpadding=0 width=100%><tr><td  align=left valign=top bgcolor=#".$aThreadConfig[bgcolor].">";
	  }
	  if ($aThreadConfig[kzbold]=="X") {
	    echo "<b>";
	  }
	  if ($aThreadConfig[kzitalic]=="X") {
	    echo "<i>";
	  }
	  if ($aThreadConfig[kzulined]=="X") {
	    echo "<u>";
	  }
  	  echo "<font color=#".$aThreadConfig[text].">";
	}
	else {
      echo "<br><b>";
	}
  }
  echo $sBlanks;
  if (($aThreadConfig[kzactive]=="X") && (!$bNoSigns)) {
    switch($MsgDeep) {
	  case 0:
	    if (strlen($aThreadConfig[graf0])>1) {
		  $aPic=getimagesize($aThreadConfig[graf0]);
		  $sPic=$aThreadConfig[graf0];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign0]!=" ") {
		  echo $aThreadConfig[sign0]."&nbsp;";
		}
	    break;
		
	  case 1:
	    if (strlen($aThreadConfig[graf1])>1) {
		  $aPic=getimagesize($aThreadConfig[graf1]);
		  $sPic=$aThreadConfig[graf1];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign1]!=" ") {
		  echo $aThreadConfig[sign1]."&nbsp;";
		}
	    break;
		
	  case 2:
	    if (strlen($aThreadConfig[graf2])>1) {
		  $aPic=getimagesize($aThreadConfig[graf2]);
		  $sPic=$aThreadConfig[graf2];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign2]!=" ") {
		  echo $aThreadConfig[sign2]."&nbsp;";
		}
	    break;
		
	  case 3:
	    if (strlen($aThreadConfig[graf3])>1) {
		  $aPic=getimagesize($aThreadConfig[graf3]);
		  $sPic=$aThreadConfig[graf3];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign3]!=" ") {
		  echo $aThreadConfig[sign3]."&nbsp;";
		}
	    break;
		
	  case 4:
	    if (strlen($aThreadConfig[graf4])>1) {
		  $aPic=getimagesize($aThreadConfig[graf4]);
		  $sPic=$aThreadConfig[graf4];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign4]!=" ") {
		  echo $aThreadConfig[sign4]."&nbsp;";
		}
	    break;
		
	  case 5:
	  default:
	    if (strlen($aThreadConfig[graf5])>1) {
		  $aPic=getimagesize($aThreadConfig[graf5]);
		  $sPic=$aThreadConfig[graf5];
		  $iWidth=$aPic[0]; $iHeight=$aPic[1];
		  echo "<img src=\"$sPic\" width=$iWidth height=$iHeight borde=0 alt=\"\">&nbsp;";
		}
		elseif ($aThreadConfig[sign5]!=" ") {
		  echo $aThreadConfig[sign5]."&nbsp;";
		}
	    break;
	}
  }
    
  $sNo=strval($DbRow[0]);
  $sUrl="showentry.php?sNo=".$sNo;
  
  if ($EnablePersonalNewInfo=="X") {
    $iNo=intval($sNo);
	if (isset($aC[$iNo])) {
	  $aPic=getimagesize("graphics/old.gif"); $iWidth=$aPic[0]; $iHeight=$aPic[1];
	  echo "<img src=\"graphics/old.gif\" alt=\"gelesen\" width=\"$iWidth\" height=\"$iHeight\" border=\"0\">&nbsp;";
	}
	else {
	  $aPic=getimagesize("graphics/old.gif"); $iWidth=$aPic[0]; $iHeight=$aPic[1];
	  echo "<img src=\"graphics/new.gif\" alt=\"neu, ungelesen\" width=\"$iWidth\" height=\"$iHeight\" border=\"0\">&nbsp;";
	}
  }
  echo "<a href=\"$sUrl\">".DecryptText($DbRow[10])."</a>";
  echo "&nbsp;-&nbsp;<b>".DecryptText($DbRow[2])."</b>&nbsp;-&nbsp;".$DbRow[5]."&nbsp;".$DbRow[6];
  if ($DbRow[12]=="X") {
    echo "&nbsp;-&nbsp;(abgeschlossen)";
  }
  echo "<br>";
  
  if ($bThreadBegin) {
    if ($aThreadConfig[kzactive]=="X") {
  	  echo "</font>";
	  if ($aThreadConfig[kzulined]=="X") {
	    echo "</u>";
	  }
	  if ($aThreadConfig[kzitalic]=="X") {
	    echo "</i>";
	  }
	  if ($aThreadConfig[kzbold]=="X") {
	    echo "</b>";
      }
	  if ($aThreadConfig[bgcolor]!=$BodyBgcolor) {
	    echo "</td></tr></table>";
	  }
	}
    else {
      echo "</b>";
	}
  }
}

function My_ObStart() {
  $encoding = getenv("HTTP_ACCEPT_ENCODING");

  if (eregi("gzip",$encoding)) {
    ob_start("ob_gzhandler");
  } 
  else {
    ob_start();
  }
}


function EchoHeader($Title,
                    $BodyText,
					$BodyBgcolor,
					$BodyLink,
					$BodyAlink,
					$BodyVlink,
					$BodyBackground,
					$sSubTitle,
					$Banner,
					$Font) {

  global $sAbsPath;
  global $_SERVER;
  
	
  My_ObStart();
	
  	
	$container = $_SERVER["HTTP_USER_AGENT"];
$useragents = array (
"iPhone","iPod","Android");

$GLOBALS["iphone"] = false;
if ((stripos ($container,"iphone")>0) ||  (stripos ($container,"ipod")>0) ||  (stripos ($container,"ndroid")>0) )
   $GLOBALS["iphone"] = true;



  echo "<!doctype html public \"-//W3C//DTD HTML 4.0 //EN\">\n";
  echo "<html><head>\n";
  echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\">\n";
  echo "<meta name=\"robots\" content=\"noarchive\">\n";
  if (isset($sAbsPath)) {
	echo "<meta name=\"robots\" content=\"noindex,nofollow\">\n";
  }
  echo '<meta name="description" content="BSC Young Boys 1898 Forum">';
 // for iphone  
     echo '<meta name="viewport" content="width=320; user-scalable=no" />';
  echo "\n";
	echo '<meta name="keywords" content="YB, BSC YB, Young Boys, 1898, Forum, Bern, Fussball">';
  echo "\n";
  echo "<script language=javascript>
        <!--
		function cursor() {
		  if (( navigator.userAgent.indexOf(\"Opera\" ) != -1) || ( navigator.userAgent.indexOf(\"Netscape\" ) != -1)) {
            text_before = document.formular.message.value; 
            text_after = \"\"; 
		  }
		  else {
            document.frmPost.sText.focus(); 
            var sel = document.selection.createRange(); 
            sel.collapse(); 
            var sel_before = sel.duplicate(); 
            var sel_after = sel.duplicate(); 
            sel.moveToElementText(document.frmPost.sText); 
            sel_before.setEndPoint(\"StartToStart\",sel); 
            sel_after.setEndPoint(\"EndToEnd\",sel); 
            text_before = sel_before.text; 
            text_after = sel_after.text; 
		  }
		}
		
        function AddTag(sTag) { 
          cursor(); 
          document.frmPost.sText.value = text_before + sTag + text_after; 
          document.frmPost.sText.focus(); 
        } 
		
		
		//function AddTag(sTag) {
		//  document.frmPost.sText.value+=sTag;
		//}
		//-->
        </script>
        ";
  
  $bDefaultHead=true;	
  if ($sHead=ReadHeadTxt()) {
      $sHead=stripcslashes($sHead);
      if (strlen($sHead)>3) {
          echo $sHead;
          $bDefaultHead=false;
      }
  }					
  if ($bDefaultHead) {
    echo "<title>$Title</title>\n";
    if (strlen($Font)>3) {
      echo "<style type=\"text/css\">";
	  echo "<!--";
	  echo " body, table, td, center, h1, h2, h3, h4, i, u, b, p, div {font-family: $Font;}";
	 echo " h1 {text-align:center; font-size:14pt;}";
  echo "// --> </style>";
      echo '<link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="css/mobile.css" type="text/css" />';

}
  }
  
  echo "</head>";
 
  
  echo "<body text=$BodyText bgcolor=$BodyBgcolor link=$BodyLink alink=$BodyAlink vlink=$BodyVlink background=\"$BodyBackground\">";
  
  echo "<center><table width=\"95%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=\"left\" valign=\"top\" width=\"100%\">";
  $bDefaultTitle=true;
  /*if ($sTitle=ReadTitleCode()) {
    $sTitle=stripcslashes($sTitle);
    if (strlen($sTitle)>=3) {
      $bDefaultTitle=false;
	  echo $sTitle;
	}
  }*/
  if ($bDefaultTitle) {
   // echo "<h1 align=center>$Title</h1>";
   
    if (strlen($Banner)>3) {
   //   $aPic=getimagesize($Banner);
//	  $iWidth=$aPic[0]; $iHeight=$aPic[1];
    $iWidth=730; $iHeight=117;
	  echo "<h1><center><img class=\"banner\" src=\"$Banner\" width=$iWidth height=$iHeight border=0 alt=\"$Title\"></center></h1>";
 /* echo "<div><center><b><a href=\"http://www.konkordatnein.ch\">Nein zur Konkordats-Verschärfung</a>  - SCHLUSSSPURT! </b> - <a href=\"http://www.konkordatnein.ch/wp-content/uploads/2013/04/referendumsbogen.pdf\" target=\"_blank\">Sammelt noch Unterschriften</a> und sendet diese jetzt an:</b></center></div>";
echo '<div style="font-size:9pt;text-align:center">Komitee «Nein zur Konkordats-Verschärfung!», c/o Fussball-Lokal HalbZeit, Beundenfeldstr. 13, 3013 Bern</div>';*/
  }
  //
    if ($sSubTitle=="Startseite") 	
    	$sSubTitle="";
    	
    if (strlen($sSubTitle)>1) {
      echo "<h3 align=center>$sSubTitle</h3>";
    }
  }

 
          

}

function ReadHeadTxt() {
  $bFound=false;
  if (file_exists("cfg/head.txt")) {
    $hndFile=fopen("cfg/head.txt","r-");
	$sHead=fread($hndFile,filesize("cfg/head.txt"));
	$sHead=chop($sHead);
	$sHead=stripcslashes($sHead);
	fclose($hndFile);
	clearstatcache();
	$bFound=true;
  }
  if ($bFound) {
    return($sHead);
  }
  else {
    return (false);
  }
}

function ReadFooterTxt() {
  $bFound=false;
  if (file_exists("cfg/footer.txt")) {
    $hndFile=fopen("cfg/footer.txt","r-");
	$sFooter=@fread($hndFile,filesize("cfg/footer.txt"));
	$sFooter=chop($sFooter);
	$sFooter=stripcslashes($sFooter);
	fclose($hndFile);
	clearstatcache();
	$bFound=true;
  }
  if ($bFound) {
    return($sFooter);
  }
  else {
    return (false);
  }
}

function EchoFooter() {
  if ($sFooter=ReadFooterTxt()) {
    $sFooter=stripcslashes($sFooter);
	if (strlen($sFooter)>=3) {
	  echo $sFooter;
	}
  }

  echo "</td></tr></table></body></html>";
}

function GetEntry($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$iNo,$bText=true) {
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    
    if ($bText) {
      $DbQuery=mysql_db_query($DbName,
	                          "select * from $DbTab
							            where no =$iNo
									    and   ((del='-') or (del='T'))"
	                          ,$Db);
	}
	else {
      $DbQuery=mysql_db_query($DbName,
	                          "select no,preno,author,email,regular,
                                      date,time,picurl,homeurl,homename,
                                      subject,del,tclose  
							            where no =$iNo
									    and   ((del='-') or (del='T'))"
	                          ,$Db);
	}
    mysql_close($Db);
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  return ($DbRow);
	}
  }
  
  return (false);
}

function EchoUserMen($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $DbQuery=mysql_db_query($DbName,"select * from $DbFnc",$Db);
	mysql_close($Db);
	$bBr=false;
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  if ($DbRow[5]=="X") {
	    if (!$bBr) {echo "<br>"; $bBr=true;} else {echo "&nbsp;&nbsp;";}
		$sName=$DbRow[2]; $sUrl=$DbRow[3]; $sTarget=$DbRow[4];
		echo "[ <a href=\"$sUrl\" target=\"$sTarget\">$sName</a> ]";
	  }
	  if ($DbRow[9]=="X") {
	    if (!$bBr) {echo "<br>"; $bBr=true;} else {echo "&nbsp;&nbsp;";}
		$sName=$DbRow[6]; $sUrl=$DbRow[7]; $sTarget=$DbRow[8];
		echo "[ <a href=\"$sUrl\" target=\"$sTarget\">$sName</a> ]";
	  }
	  if ($DbRow[13]=="X") {
	    if (!$bBr) {echo "<br>"; $bBr=true;} else {echo "&nbsp;&nbsp;";}
		$sName=$DbRow[10]; $sUrl=$DbRow[11]; $sTarget=$DbRow[12];
		echo "[ <a href=\"$sUrl\" target=\"$sTarget\">$sName</a> ]";
	  }
	  if ($DbRow[17]=="X") {
	    if (!$bBr) {echo "<br>"; $bBr=true;} else {echo "&nbsp;&nbsp;";}
		$sName=$DbRow[14]; $sUrl=$DbRow[15]; $sTarget=$DbRow[16];
		echo "[ <a href=\"$sUrl\" target=\"$sTarget\">$sName</a> ]";
	  }
	}
  }
}

function WritePosting($DbHost,$DbName,$DbUser,$DbPass,$DbTab,
		              $sAuthor,$sEmail,$sText,$sHomeurl,$sHomename,$sPicurl,$sSubject,
					  $sNo,$sNoSrc,$sReg,
					  $sRemoteAddr,$sRemotePort,$sHttpUserAgent,$sClient="web") {
					  
  global $SenderMail;					  
					  
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $sText=addslashes($sText);
    $aConfig=ReadConfigFile();
	$Key="\$EmailNote";
	$sEmailNote=GetRamValue($Key,$aConfig);
    $sModerateRegulars= GetRamValue("\$ModerateRegulars",$aConfig);
    $sModerateGuests=   GetRamValue("\$ModerateGuests",$aConfig);
	
  
    $iPreNo=intval($sNo);
	$iNoSrc=intval($sNoSrc);

	$aTime=getdate(time());	
	$sDate=MakeGermanDate($aTime);
	$sTime=MakeGermanTime($aTime);
	
    if (strlen($sSubject)>100) {
	  $sSubject=substr($sSubject,0,100);
	}
  
    $sSubject=EncryptText(stripslashes($sSubject));
    $sAuthor=EncryptText(stripslashes($sAuthor));
    $sText=EncryptText(stripslashes($sText));
	$sClient=EncryptText(stripslashes($sClient));
	  $sIp=$_SERVER['REMOTE_ADDR'];
  
	if ($iNoSrc==0) {
	  $sSessd=base64_encode(crypt(strval(time())));
	  mysql_db_query($DbName,
	                 "insert into $DbTab set preno   =$iPreNo,
					                         author  ='$sAuthor',
					                         email   ='$sEmail',
											 regular ='$sReg',
										     date    ='$sDate',
										     time    ='$sTime',
										     picurl  ='$sPicurl',
										     homeurl ='$sHomeurl',
										     homename='$sHomename',
										     subject ='$sSubject',
										     del     ='1',
                         					 ptext   ='$sText',
											 sessd   ='$sSessd',
											 ip      ='$sIp',
											 client  ='$sClient'
											 "
	                 ,$Db);
	
//    Die genrierte Beitragsnummer bestimmen		
	  $DbQuery=mysql_db_query($DbName,
	                          "select * from $DbTab 
							            where sessd   ='$sSessd'"
	                          ,$Db);
	
	  if ($DbRow=mysql_fetch_row($DbQuery)) {

	    $iNo=$DbRow[0];
		
        if ($iPreNo==0) {
		  $iThread=$DbRow[0];
		}
		else {
          $QQ=@mysql_query("select thread from $DbTab where no=$iPreNo");
		  $RR=mysql_fetch_object($QQ);
		  $iThread=$RR->thread;
		}
		@mysql_query("update $DbTab set thread=$iThread where no=$iNo");
		
        $aConfig=ReadConfigFile();
	    $Key="\$EmailNote";
	    $sEmailNote=GetRamValue($Key,$aConfig);
	    if (CheckEmail($sEmailNote)) {
	      $Key="\$Title";
	      $sEmailTitle=GetRamValue($Key,$aConfig);
	      $sEmailSubject ="Forum $sEmailTitle: ".DecryptText($sSubject);
	      $sEmailMessage ="Name: ".DecryptText($sAuthor)."\n";
	      $sEmailMessage.="Email: ".$sEmail."\n";
	      $sEmailMessage.="Zeit: ".$sDate." ".$sTime."\n";	  
	      $sEmailMessage.="Betreff: ".DecryptText($sSubject)."\n\n";
		  $sEmailMessage.="Beitrag:\n";
		  $sEmailMessage.="========\n";
		  $sEmailMessage.="".DecryptText($sText);
		  $sHeader="From: \"Forum $sEmailTitle\" <$SenderMail>";
		  mail($sEmailNote,$sEmailSubject,$sEmailMessage,$sHeader);
	    }
        if (($sModerateRegulars=="X") && ($sReg=="R")) {
		  $sNewDel="M";
		}
		elseif (($sModerateGuests=="X") && ($sReg!="R") && ($sReg!="A")) {
		  $sNewDel="M";
		}
		else {
		  $sNewDel="-";
		}
	    mysql_db_query($DbName,
	                   "update $DbTab set del='$sNewDel'
					                  where no=$iNo"
	                   ,$Db);
	  }
	}
	else {
	  mysql_db_query($DbName,
	                 "update $DbTab set author  ='$sAuthor',
					                    email   ='$sEmail',
									    picurl  ='$sPicurl',
										homeurl ='$sHomeurl',
										homename='$sHomename',
										subject ='$sSubject',
										ptext   ='$sText'
								    where no=$iNoSrc"
	                 ,$Db);
	  $iNo=$iNoSrc;
	}
					  
/*	$sFile="data/".strval($iNo).".txt";
	if ($hndFile=fopen($sFile,"w+")) {
	  $sText=stripcslashes($sText);
      $sText=PseudoToHtml($sText);
	  fwrite($hndFile,$sText);
	  fclose($hndFile);
	}*/
	$sFile="data/".strval($iNo).".log";
	if ($iNoSrc==0) {
	  if ($hndFile=fopen($sFile,"w+")) {
	    fwrite($hndFile,$sRemoteAddr);fwrite($hndFile,"\n");
	    fwrite($hndFile,$sRemotePort);fwrite($hndFile,"\n");
	    fwrite($hndFile,$sHttpUserAgent);fwrite($hndFile,"\n");
	    fwrite($hndFile,$sDate);fwrite($hndFile,"\n");
	    fwrite($hndFile,$sTime);fwrite($hndFile,"\n");
	    fclose($hndFile);
	  }	
	}
    mysql_close($Db);
  }				  
}

function ReadConfigFile($sFile="") {
  global $sAbsPath;

  $sOpenFile=$sAbsPath."cfg/config.php";
  
  if ($hndFile=fopen($sOpenFile,"r-")) {
    $aRamFile=array(); $iCount=0;
    while ($sLine=fgets($hndFile,200)) {
	  $aRamFile[$iCount]=chop($sLine);
	  $iCount++;
	}
    fclose($hndFile);
	return($aRamFile);
  }
  return (false);
}

function GetRamValue($sKey,$aRamFile) {
  $iAnz=sizeof($aRamFile);
  $iKeyLen=strlen($sKey);
  for ($i=1;$i<($iAnz-1);$i++) {
    $sCurrentKey=substr($aRamFile[$i],0,$iKeyLen);
    if ($sCurrentKey == $sKey) {
	  if (($sKey=="\$PostsInForum") || ($sKey=="\$PostsInArc") || ($sKey=="\$MaxLoginFails") || ($sKey=="\$ThreadsPerPage")) {
	    $iValBegin=strpos($aRamFile[$i],"=");
	    $iValEnd  =strrpos($aRamFile[$i],";");
	    $sValue=substr($aRamFile[$i],($iValBegin+1),($iValEnd-$iValBegin-1));
	  }
	  else {
	    $iValBegin=strpos($aRamFile[$i],"\"");
	    $iValEnd  =strrpos($aRamFile[$i],"\"");
	    $sValue=substr($aRamFile[$i],($iValBegin+1),($iValEnd-$iValBegin-1));
	  }
	  return ($sValue);
	}
  }
  return (false);
}

function SetRamValue($sKey,$sValue,$aRamFile) {
  $iAnz=sizeof($aRamFile);
  $iKeyLen=strlen($sKey);
  for ($i=1;$i<($iAnz-1);$i++) {
    $sCurrentKey=substr($aRamFile[$i],0,$iKeyLen);
    if ($sCurrentKey == $sKey) {
	  if (($sKey=="\$PostsInForum") || ($sKey=="\$PostsInArc")  || ($sKey=="\$MaxLoginFails") || ($sKey=="\$ThreadsPerPage")) {
	    $aRamFile[$i]=$sKey."=".$sValue.";";
	    return ($aRamFile);
	  }
	  else {
	    $aRamFile[$i]=$sKey."=\"".$sValue."\";";
	    return ($aRamFile);
	  }
	}
  }
}

function WriteConfigFile($aRamFile) {
  if ($hndFile=fopen("cfg/config.php","w+")) { 
    $iAnz=sizeof($aRamFile);
	for ($i=0;$i<$iAnz;$i++) {
	  fwrite($hndFile,$aRamFile[$i]);
	  fwrite($hndFile,"\n");
	}
	fclose($hndFile);
  }
}

function CheckColor($sColor) {
  if (strlen($sColor)!=6) {
    return (true);
  }
  $sColor=strtolower($sColor);
  for ($i=0;$i<6;$i++) {
    if (($sColor[$i]!="0") && ($sColor[$i]!="1") && ($sColor[$i]!="2") && ($sColor[$i]!="3") &&
	    ($sColor[$i]!="4") && ($sColor[$i]!="5") && ($sColor[$i]!="6") && ($sColor[$i]!="7") &&
	    ($sColor[$i]!="8") && ($sColor[$i]!="9") && ($sColor[$i]!="a") && ($sColor[$i]!="b") &&
		($sColor[$i]!="c") && ($sColor[$i]!="d") && ($sColor[$i]!="e") && ($sColor[$i]!="f")) {
	  return (false);
    }
  }
  return (true);
}

function GetRegUsr($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sRegName) {
 
  $bCloseDb=false;
  if (!$Db) {
    if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
	  $bCloseDb=true;
	}
  }
  $sRegName=EncryptText($sRegName);
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $DbQuery=mysql_db_query($DbName,
	                        "select * from $DbReg
							          where name ='$sRegName'
									  and   state='A'"
	                        ,$Db);
    if ($bCloseDb) {						
	  mysql_close($Db);
	}
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  return($DbRow);
	}
  }
  return(false);
}

function ColorAuthor($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor,$sReg,$RegColor,$AdminColor,$RegsSameCol) {

  if ($sReg=="R") {
	if ($RegsSameCol=="X") {
	  $sNewAuthor="<font color=$RegColor>".$sAuthor."</font>";
	  $sAuthor=$sNewAuthor;
	}
	else {
	  if ($DbReg=GetRegUsr($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$sAuthor)) {
	    $sRegColor="#".$DbReg[3];
		$sNewAuthor="<font color=$sRegColor>".$sAuthor."</font>";
		$sAuthor=$sNewAuthor;
	  }
	}
  }
  elseif($sReg=="A") {
 	$sNewAuthor="<font color=$AdminColor>".$sAuthor."</font>";
	$sAuthor=$sNewAuthor;
  }
  return ($sAuthor);
}

function CloseThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,
		             $sNo,$sClose,$bFirst,$Db) {					 
  if ($bFirst) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
	  return;
	}
  }

  $iNo=intval($sNo);
  $bSubFirst=false;
  // Die untergeordenten Beiträge schließen
  $DbQuery=mysql_db_query($DbName,
                          "select * from $DbTab
						            where preno=$iNo
									and   ((del  ='-') or (del='T'))"
                          ,$Db);
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    $sSubNo=strval($DbRow[0]);
	CloseThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$sSubNo,$sClose,$bSubFirst,$Db);
  }
  
  // Den Beitrag selbst schliessen
  mysql_db_query($DbName,
                 "update $DbTab set tclose='$sClose'
				         where no=$iNo"
                 ,$Db);
  
  if ($bFirst) {
    mysql_close($Db);
  }
}

function CheckArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbFnc) {
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $DbQuery=mysql_db_query($DbName,
	                        "select * from $DbFnc"
	                        ,$Db);
	mysql_close($Db);
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  if ($DbRow[1]=="X") {
	    return (true);
	  }
	}
  }
  return (false);
}

function CheckRegData($sRegName,$sRegPass,$sRegEmail,$sRegColor) {

  $bProceed=true;
	  
  if (strlen($sRegName)<2) {
    echo "<center><font color=#ff0000><b>Fehler:</b> Kein Name</font></center>";
	if ($bProceed) {$bProceed=false;}
  }
  if (strlen($sRegPass)<6) {
    echo "<center><font color=#ff0000><b>Fehler:</b> Passwort muss mindestens 6 Zeichen lang sein</font></center>";
	if ($bProceed) {$bProceed=false;}
  }
  if (!CheckEmail($sRegEmail)) {
    echo "<center><font color=#ff0000><b>Fehler:</b> Ung&uuml;ltige Emailadresse</font></center>";
	if ($bProceed) {$bProceed=false;}
  }
  if (!CheckColor($sRegColor)) {
    echo "<center><font color=#ff0000><b>Fehler:</b> Ung&uuml;ltige Farbcode</font></center>";
	if ($bProceed) {$bProceed=false;}
  }
  
  return ($bProceed);
}

function SetArchiveMode($DbHost,$DbName,$DbUser,$DbPass,$DbArc,$sMode) {
  if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass,$DbArc))) {
    return(false);
  }

  mysql_db_query($DbName,
                 "update $DbArc set arcmode='$sMode'"
                 ,$Db);

  mysql_close($Db);
  return (true);
}

function CountArcPosts($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$PostsInForum,$PostsInArc,$sCountWhat) {
  if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
    return (false);
  }

  $iForum=0; $iArc=0; $bFirst=false; $sDel='?';

  $DbQuery=mysql_db_query($DbName,
                          "select * from $DbTab
                                    where preno=0
                                    order by no desc"
                          ,$Db);
  while ($DbRow=mysql_fetch_row($DbQuery)) {
    if ($iForum<$PostsInForum) {
      $iForum=$iForum+CountPostsInThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$Db,$bFirst,$DbRow[0],$sDel);
    }
    else {
      $iArc=$iArc+CountPostsInThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$Db,$bFirst,$DbRow[0],$sDel);
    }
  }

  mysql_close($Db);

  if     ($sCountWhat=="F") {
    return ($iForum);
  }
  elseif ($sCountWhat=="A") {
    return ($iArc);
  }
  else {
    $aRet=array();
    $aRet[0]=$iForum;
    $aRet[1]=$iArc;
    return ($aRet);
  }
}

function CountPostsInThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$Db,$bFirst,$iNo,$sDel) {

  if ($bFirst) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
      return (0);
    }
  }

  // Den Beitrag selbst zählen;
  $iCount=1;

  $bSubFirst=false;

  // Die untergeordneten Beiträge zählen
  if ($sDel=="?") {
    $DbQuery=mysql_db_query($DbName,
                            "select * from $DbTab
                                      where preno=$iNo"
           ,$Db);
  }
  else {
    $DbQuery=mysql_db_query($DbName,
                            "select * from $DbTab
                                      where preno=$iNo
                                      and   del  ='$sDel'"
                            ,$Db);
   }
   while ($DbRow=mysql_fetch_row($DbQuery)) {
     $iCount = $iCount + CountPostsInThread($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$Db,$bSubFirst,$DbRow[0],$sDel);
   }
 
  if ($bFirst) {
    mysql_close ($Db);
  }

  return ($iCount);
}

function HtmlToPseudo($sText) {

  include ("pseudotag.php");
  include ("smilies.php");

  $sText="#".$sText;

  $iAnz=sizeof($aSmilies);
  for ($i=0;$i<$iAnz;$i++) {
    $sTag=$aSmilies[$i][0];
    $sPic=$aSmilies[$i][1];
    while ($iPos=strpos($sText,$sPic)) {
	  $sText=KffReplace($sText,$sPic,$sTag);
	}
  }

  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {
    $sTag     =$aTag[$i][0];
    $sTagR    =$aTag[$i][1];
    $sHtmlTag =$aTag[$i][2];
    $sHtmlTagR=$aTag[$i][3];

    while ($iPos=strpos($sText,$sHtmlTag)) {
      $sText=KffReplace($sText,$sHtmlTag,$sTag);
      $sText=KffReplace($sText,$sHtmlTagR,$sTagR);
    }
  }

  $sText=substr($sText,1,(strlen($sText)-1)); 
  return ($sText);

}

function Pseudo2ToHtml($sText) {
  include ("pseudo2.php");
  include ("smilies.php");
//  $sText="#".$sText;

  $aConfig=ReadConfigFile();

  $sSmilies=GetRamValue("\$Smilies",$aConfig);
  if ($sSmilies=="X") {
    $iAnz=sizeof($aSmilies);
    for ($i=0;$i<$iAnz;$i++) {
      $sTag=$aSmilies[$i][0];
      $sPic=$aSmilies[$i][1];
	  while ($iPos=strpos($sText,$sTag)) {
	    $sText=KffReplace($sText,$sTag,$sPic);
	  }
    }
  }
    $iAnz=sizeof($aTagR);
  for ($i=0;$i<$iAnz;$i++) {
  	
  	$sText=eregi_replace($aTagR[$i][0],$aTagR[$i][1],$sText);
  	
  }
    if (preg_match("/(<a href=\"(http\:\/\/www\.youtube\.com\/watch\?v\=[\w\d\_]+).+\<\/a\>)/i",$sText,$regs)) {
     $embed=$regs[2];
     $videoEmbed = new VideoEmbed($embed); //optional width and height may be passed to the constructor
     $sText=str_replace($regs[1],$videoEmbed->embed,$sText);
  }
  
  return $sText;
}

function PseudoToHtml($sText) {

  return($sText);

  include ("pseudotag.php");
  $sText="#".$sText;
  
  $iAnz=sizeof($aTag);
  for ($i=0;$i<$iAnz;$i++) {

    $sTag     =$aTag[$i][0];
    $sTagR    =$aTag[$i][1];
    $sHtmlTag =$aTag[$i][2];
    $sHtmlTagR=$aTag[$i][3];

    while ($iPos=strpos($sText,$sTag)) {

      $sBeforeTag=substr($sText,0,$iPos);
      $sFromTag  =substr($sText,$iPos,(strlen($sText)-$iPos));
      if (!(strpos($sFromTag,$sTagR))) {
        $sFromTag=$sFromTag.$sTagR;
      }
      $sFromTag=KffReplace($sFromTag,$sTag, $sHtmlTag);
      $sFromTag=KffReplace($sFromTag,$sTagR,$sHtmlTagR);

      $sText=$sBeforeTag.$sFromTag;
      
      $sTag     =$aTag[$i][0];
      $sTagR    =$aTag[$i][1];
      $sHtmlTag =$aTag[$i][2];
      $sHtmlTagR=$aTag[$i][3];
    }
  }
 
  $sText=substr($sText,1,(strlen($sText)-1)); 
  
 
  
  return ($sText);
}

function ReadBadWords() {

  $bList=false;

  if (file_exists("cfg/badwords.txt")) {
    if ($hndFile=fopen("cfg/badwords.txt","r-")) {
	  $sTmp=fread($hndFile,filesize("cfg/badwords.txt"));
	  $sBadWords=chop($sTmp);
	  fclose($hndFile);
	  $aBadWords=explode(",",$sBadWords);
	  if (sizeof($aBadWords)>=1) {
	    $bList=true;
	  }
	}
  }
  clearstatcache();
  if ($bList) {
    return ($aBadWords);
  }
  else {
    return (false);
  }
}

function IsBadword($sText) {
  if ($aBadWords=ReadBadWords()) {
    $iAnz=sizeof($aBadWords);
	$sCmpText="#".strtolower($sText);
	for ($i=0;$i<$iAnz;$i++) {
	  if (strpos($sCmpText,$aBadWords[$i])) {
	    return (true);
	  }
	}
  }
  return (false);
}
?>