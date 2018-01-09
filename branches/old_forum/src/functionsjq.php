<?php
  include_once ("functions.php");

function EchoForumLayoutParsimonyJq($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
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
      echo "<li>ss<a href=\"showentry.php?sNo=$R->no\" class=\"showentry\">".DecryptText($R->subject)."</a> - <b>".DecryptText($sShowAuthor)."</b> - <i>$R->date $R->time</i>\n";
	  $QQ=@mysql_query("select no from $DbTab where preno=$R->no and del<>'X' limit 0,1");
	  if ((@mysql_num_rows($QQ)>0) && (!$bThreadOnly)) {
        $iSubMsgDeep=$MsgDeep+1;
        $bSubFirst  =false;
        EchoForumLayoutParsimonyJq($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,$R->no,
	                             $iSubMsgDeep,$Db,$bSubFirst,$bThreadOnly,
	                             $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive);
	  }
	  echo "</li>";
    }
	echo "</ul>\n";
  }
	
  
  if ($bFirst) {
    // Auf Ebene des Erstaufrufes die Datenbankverbindung wieder schliessen
    mysql_close($Db);
  }
}

function EchoForumJq($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
                   $MsgNo,$MsgDeep,$Db,$bFirst,$bThreadOnly,
				   $DbReg,$RegColor,$AdminColor,$RegsSameCol,$RegsActive,$sDelSign="-",$iActPage=0,$iMaxPages=0) {

  global $ThreadsPerPage,$ParsimonyLayout;
  				    
  if ($ParsimonyLayout=="X") {					
    EchoForumLayoutParsimonyJq($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,
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
    EchoThreadJq($DbRow,$MsgDeep,$DbRegTab,$Db,$bThreadBegin,
	           $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
			   $RegColor,$AdminColor,$RegsSameCol,$RegsActive,$aC);
    // aber auch alle untergeordneten Nachrichten wenn gewünscht
	if (!$bThreadOnly) {
      $iSubMsgDeep=$MsgDeep+1;
      $bSubFirst  =false;
      EchoForumJq($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbRegTab,$DbRow[0],$iSubMsgDeep,$Db,$bSubFirst,$bThreadOnly,
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

function EchoThreadJq($DbRow,$MsgDeep,$DbRegTab,$Db,$bThreadBegin,
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
  echo "<a class=\"showentry\" href=\"$sUrl\">".DecryptText($DbRow[10])."</a>";
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
?>
