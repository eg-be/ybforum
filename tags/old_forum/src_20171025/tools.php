<?php

function KffDirname($sDir) {
  $sRetDir=dirname($sDir);
  if ($sRetDir[strlen($sRetDir)-1]!="/") {
    $sRetDir.="/";
  }
  return($sRetDir);
}

function CheckIP($sRemoteAddr) {

/*10.0.0.0 bis 10.255.255.255
172.16.0.0 bis 172.31.255.255
192.168.0.0 bis 192.168.255.255*/

return true;
  $aIp=explode(".",$sRemoteAddr);

  if (sizeof($aIp)!=4) {
    // IP besteht nicht aus 4 Teilen  
    return (false);
  }
  
  if ((strlen($aIp[0])>3) || (strlen($aIp[0])==0)) {
    // 1. Teil zu lang oder nicht vorhanden
	return (false);
  }
  if ((strlen($aIp[1])>3) || (strlen($aIp[1])==0)) {
    // 2. Teil zu lang oder nicht vorhanden
	return (false);
  }
  if ((strlen($aIp[2])>3) || (strlen($aIp[3])==0)) {
    // 3. Teil zu lang oder nicht vorhanden
	return (false);
  }
  if ((strlen($aIp[2])>3) || (strlen($aIp[3])==0)) {
    // 4. Teil zu lang oder nicht vorhanden
	return (false);
  }
  
  for ($i=0;$i<=3;$i++) {
    $sIp=$aIp[$i];
	$iCnt=strlen($sIp);
	for ($k=0;$k<$iCnt;$k++) {
	  if (($sIp[$k]!="0") && ($sIp[$k]!="1") && ($sIp[$k]!="2") &&
	      ($sIp[$k]!="3") && ($sIp[$k]!="4") && ($sIp[$k]!="5") &&
		  ($sIp[$k]!="6") && ($sIp[$k]!="7") && ($sIp[$k]!="8") &&
		  ($sIp[$k]!="9")) {
		// Keine Ziffer in einer Stelle
	    return (false);
	  }
	}
  }
  
  if ($aIp[0]=="10") {
    // Privater Bereich
    return (false);
  }
/*  if ($sRemoteAddr=="127.0.0.1") {
    // Local Host
    return (false);
  }*/
  if ($sRemoteAddr=="168.143.113.10") {
    // anonymizer.com
    return (false);
  }
  if ($sRemoteAddr=="209.126.198.23") {
    // anonymizer.com
    return (false);
  }
  if (($aIp[0]=="192") && ($aIp[1]=="168")) {
    // Privater Bereich
    return (false);
  } 
  if (($aIp[0]=="172") && ($aIp[1]>="16") && ($aIp[1]<="31")) {
    // Privater Bereich
    return (false);
  }

  return (true);
}

function CheckEmail($sEmail) {
  if (strlen($sEmail) < 5) {
	  return (false);
	}

  $iOrd_UA=ord("A"); $iOrd_UZ=ord("Z");
  $iOrd_da=ord("a"); $iOrd_dz=ord("z");
  $iOrd_0=ord("0");  $iOrd_9=ord("9");
  
  $iOrd_at    =ord("@");
  $iOrd_dot   =ord(".");
  $iOrd_minus =ord("-");
  $iOrd_uscore=ord("_");

  for ($i=0;$i<$iLen;$i++) {
    $iSign=ord($sEmail[$i]);
	if (
	   ($iSign!=$iOrd_at) && ($iSign!=$iOrd_dot) && ($iSign!=$iOrd_minus) && ($iSign!=$iOrd_uscore) &&
	   (($iSign<$iOrd_da) || ($iSign>$iOrd_dz)) &&
	   (($iSign<$iOrd_UA) || ($iSign>$iOrd_UZ)) &&
	   (($iSign<$iOrd_0) || ($iSign>$iOrd_9))
	   ) {
	  return (false);
	}
  }
	
  $iFirstAt = strpos($sEmail,"@");
  $iFirstPt = strpos($sEmail,".");
  $iLastAt = strrpos($sEmail,"@");
  $iLastPt = strrpos($sEmail,".");
  $iLen = strlen($sEmail)-1;
	
	if ((!$iFirstPt) || (!$iLastPt)) {
	  return (false);
	}

	if ((!$iFirstAt) || (!$iLastAt)) {
	  return (false);
	}
	
  if (($iFirstAt!=$iLastAt) || ($iFirstAt==0)) {
	  return (false);
  }
  if ($iLastAt==$iLen) {
	  return (false);
	}
	
	if ($iLastPt>($iLen-2)) {
	  return (false);
	}
	
	return(true); 
}

function MakeGermanTime($aTime) {
  $sHours=$aTime[hours]; if (strlen($sHours)==1) {$sHours="0".$sHours;}
  $sMinutes=$aTime[minutes]; if (strlen($sMinutes)==1) {$sMinutes="0".$sMinutes;}
  $sSeconds=$aTime[seconds]; if (strlen($sSeconds)==1) {$sSeconds="0".$sSeconds;}
  $sTime=$sHours.":".$sMinutes.":".$sSeconds;
  return($sTime);
}

function MakeGermanDate($aTime) {
  $sDay=$aTime[mday]; if (strlen($sDay)==1) {$sDay="0".$sDay;}
  $sMonth=$aTime[mon]; if (strlen($sMonth)==1) {$sMonth="0".$sMonth;}
  $sDate=$sDay.".".$sMonth.".".$aTime[year];
  return($sDate);
}

function KffReplace($sString,$sNeedle,$sNewNeedle) {
  $sString="#".$sString;

  if ($iPos=strpos($sString,$sNeedle)) {
  
    $sBegin=substr($sString,0,$iPos);
    $sEnd  =substr($sString,$iPos+strlen($sNeedle),strlen($sString)-$iPos-strlen($sNeedle));

    $sString=$sBegin.$sNewNeedle.$sEnd;
  }

  $sString=substr($sString,1,(strlen($sString)-1)); 
  return ($sString);
}

?>
