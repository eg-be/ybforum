<?php

include_once ("cfg/config.php");
include_once ("functions.php");

function ReadThread(&$aThread,$iNo,&$iCnt,$DbTab) {
  
  $iCnt=sizeof($aThread);
  
  $Q=@mysql_query("select no,author,date,time,subject,tclose from $DbTab where preno=$iNo and del<>'X' order by no desc");
  while ($R=@mysql_fetch_object($Q)) {
    $aThread[$iCnt][0]=$R->no;
    $aThread[$iCnt][1]=DecryptText($R->subject);
    $aThread[$iCnt][2]=DecryptText($R->author);
    $aThread[$iCnt][3]=$R->date;
    $aThread[$iCnt][4]=$R->time;
    $aThread[$iCnt][5]=$R->del;
	$iCnt++;
	ReadThread($aThread,$R->no,$iCnt,$DbTab);
  }
  @mysql_free_result($Q);
}

function EchoAdmPageBar($iActPage,$iMaxPages,$sPage,$sAdmAction,$sSessid,$sUser,$sSipaddr,$sInfo,&$sLimit) {
  global $ThreadsPerPage;

  $sLimit="";
  if ($iMaxPages>1) {
	echo "<center><b>Seiten des Forums</b>
	      <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			
    $iStartPage = $iActPage-12;	
	if ($iStartPage<1) {$iStartPage=1;}
    $iEndPage   = $iActPage+12;			
    if ($iEndPage>$iMaxPages) {$iEndPage=$iMaxPages;}
	
	echo "<tr>\n";
	
	if ($iActPage>1) {
	  echo "<td align=\"left\" valign=\"top\"><a href=\"$sPage?sAction=$sAdmAction&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo&iActPage=1\">&lt;&lt;</a>&nbsp;&nbsp;</td>\n";
	}
	$iPageJump=$iActPage-24;
	if ($iPageJump>0) {
	  echo "<td align=\"left\" valign=\"top\"><a href=\"$sPage?sAction=$sAdmAction&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo&iActPage=$iPageJump\">&lt;</a>&nbsp;&nbsp;</td>\n";
	}
	  
    for ($i=$iStartPage;$i<=$iEndPage;$i++) {
		
	  if ($i==$iActPage) {
		echo "<td align=\"left\" valign=\"top\"><b>$i</b>&nbsp;&nbsp;</td>\n";
	  }
	  else {
		echo "<td align=\"left\" valign=\"top\"><a href=\"$sPage?sAction=$sAdmAction&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo&iActPage=$i\">$i</a>&nbsp;&nbsp;</td>\n";
	  }
	}
	  $iPageJump=$iActPage+24;
	  if ($iPageJump<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"$sPage?sAction=$sAdmAction&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo&iActPage=$iPageJump\">&gt;</a>&nbsp;&nbsp;</td>\n";
	  }
	  if ($iActPage<$iMaxPages) {
	    echo "<td align=\"left\" valign=\"top\"><a href=\"$sPage?sAction=$sAdmAction&sSessid=$sSessid&sUser=$sUser&sSipaddr=$sSipaddr&sInfo=$sInfo&iActPage=$iMaxPages\">&gt;&gt;</a></td>\n";
	  }
	echo "</tr>\n";
			
    echo "</table></center><hr>\n";
	
    $iStart=($iActPage-1)*$ThreadsPerPage;
	$sLimit="limit $iStart,$ThreadsPerPage";
  }
}

function MoveThread($DbHost,$DbName,$DbUser,$DbPass,$iNo,$SrcTab,$DstTab,$Db=null,$bFirstCall=true,$iNewPre=0) {

  $bOpened = false;
  if (($Db==null) && ($bFirstCall)) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
	  return;
	}
	$bOpened = true;
  }

  
  $Q=@mysql_query("select * from $SrcTab where no=$iNo");
  if (@mysql_num_rows($Q)!=1) {return;}
  if (!($R=@mysql_fetch_object($Q))) {
    return;
  }
 
  $sSessd=base64_encode(crypt(strval(time())));
  if ($iNo>=50000) {
    @mysql_query("insert into $DstTab set sessd='$sSessd'");
    $QB=@mysql_query("select no from $DstTab where sessd='$sSessd'");
    $RB=@mysql_fetch_object($QB);
	$iNewPreNo=$RB->no;
    @mysql_query("update $DstTab set preno    = $iNewPre,
                                   author   = '$R->author',              
                                   email    = '$R->email',            
                                   regular  = '$R->regular',              
                                   date     = '$R->date',            
                                   time     = '$R->time',            
                                   picurl   = '$R->picurl',             
                                   homeurl  = '$R->homeurl',              
                                   homename = '$R->homename',
                                   subject  = '$R->subject',               
                                   del      = '$R->del',          
                                   tclose   = '$R->tclose',             
                                   ptext    = '$R->ptext'
								where no = $RB->no");
    $QT=@mysql_query("select no from $DstTab where no=$RB->no");
    if (@mysql_num_rows($QT)==1) {
      @mysql_query("delete from $SrcTab where no=$iNo");
    }
    @mysql_free_result($QT);
  }
  else {
    $iNewPreNo=$iNo;
    @mysql_query("insert into $DstTab set no     = $iNo,
	                               preno    = $R->preno,
                                   author   = '$R->author',              
                                   email    = '$R->email',            
                                   regular  = '$R->regular',              
                                   date     = '$R->date',            
                                   time     = '$R->time',            
                                   picurl   = '$R->picurl',             
                                   homeurl  = '$R->homeurl',              
                                   homename = '$R->homename',
                                   subject  = '$R->subject',               
                                   del      = '$R->del',          
                                   tclose   = '$R->tclose',             
                                   ptext    = '$R->ptext',
								   sessd    = '$sSessd'");
								   
    $QT=@mysql_query("select no from $DstTab where no=$iNo");
    if (@mysql_num_rows($QT)==1) {
      @mysql_query("delete from $SrcTab where no=$iNo");
    }
    @mysql_free_result($QT);
  }
  
  $QQ=@mysql_query("select * from $SrcTab where preno=$iNo");
  while ($RR=@mysql_fetch_object($QQ)) {
	MoveThread($DbHost,$DbName,$DbUser,$DbPass,$RR->no,$SrcTab,$DstTab,$Db,false,$iNewPreNo);
  }
  @mysql_free_result($QQ);
  @mysql_free_result($Q);
  @mysql_free_result($QB);
  
  if ($bOpened) {
    @mysql_close();
  }
}

function TranslateAdmKzactiv($AdmKzactiv) {
  $sReturn = "inaktiv";
  if ($AdmKzactiv=="X") {
    $sReturn = "aktiv";
  }
  return ($sReturn);
}

function GetAdmLevel($DbHost,$DbName,$DbUser,$DbPass,$DbAdm,$sUser) {
  $sLevel=false;
  $sQuery="select level from $DbAdm where userid='$sUser'";
  $Db=NULL; $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
  if ($DbRow=mysql_fetch_row($DbQuery)) {
    $sLevel=$DbRow[0];
  }
  return($sLevel);
}

function TranslateAdmLevel($Level) {
  switch ($Level) {
    case "2"      : $sReturn="Beitr&auml;ge und Stammposter";break;
    case "3"      : $sReturn="Beitr&auml;ge,Stammposter und Badwordliste";break;
    case "ADMALL" : $sReturn="Volladministrator";break;
    case "PRIMARY": $sReturn="Eigent&uuml;mer (alle Recht)";break;
	default       :	$sReturn="Beitr&auml;ge";break;
  }
  return ($sReturn);
}

function CheckAdmRight($LevelNeeded,$LevelGiven) {
  if ($LevelGiven=="PRIMARY") {
    return (true);
  }
  if (($LevelNeeded=="PRIMARY") && ($LevelGiven!="PRIMARY")) {
    return (false);
  }
    
  if ($LevelGiven=="ADMALL") {
    return (true);
  }
  
  if (($LevelNeeded=="ADMALL") && ($LevelGiven!="ADMALL")) {
    return (false);
  }
  
  $iLevelNeeded=intval($LevelNeeded);
  $iLevelGiven =intval($LevelGiven);
  if ($iLevelGiven>=$iLevelNeeded) {
    return (true);
  }
  
  return (false);
}

function DeletePostings($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$aDel,$bFirst,$Db) {

  if ($bFirst) {
    if (!($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {
      return;	    
	}
  }
   
  $iAnz=sizeof($aDel);
  
  for ($i=0;$i<$iAnz;$i++) {
    $sString=$aDel[$i];
	$iNo=intval(substr($sString,1,(strlen($sString)-1)));
    $DbQuery=mysql_db_query($DbName,
	                        "select * from $DbTab
							          where no=$iNo"
	                        ,$Db);
	if ($DbRow=mysql_fetch_row($DbQuery)) {
	  $iPreNo=$DbRow[1];
	}
	
	if ($sString[0]=="T") {
	  
	  $DbQuery=mysql_db_query($DbName,
	                          "select * from $DbTab
							            where preno=$iNo"
	                          ,$Db);
	  $aSubDel=array(); $iCount=0;
	  while($DbRow=mysql_fetch_row($DbQuery)){
	    $sNo=strval($DbRow[0]);
	    $aSubDel[$iCount]="T".$sNo;
	    $iCount++;
	  }
	  $bSubFirst=false;
      DeletePostings($DbHost,$DbName,$DbUser,$DbPass,$DbTab,$aSubDel,$bSubFirst,$Db);	
	  
	  mysql_db_query($DbName,
	                 "update $DbTab set del='X'
					                where no=$iNo"
	                 ,$Db);      	  
	}
	elseif ($sString[0]=="B") {
	  mysql_db_query($DbName,
	                 "update $DbTab set preno=$iPreNo
					                where preno=$iNo"
	                 ,$Db);
	  mysql_db_query($DbName,
	                 "update $DbTab set del='X'
					                where no=$iNo"
	                 ,$Db);
	}
  }
  
  if ($bFirst) {
    mysql_close($Db);
  }
}
function EchoHiddenSession($sSessid,$sUser,$sSipaddr) {
  ?>
  <input type=hidden name=sSessid  value="<?php echo $sSessid;?>">
  <input type=hidden name=sUser    value="<?php echo $sUser;?>">
  <input type=hidden name=sSipaddr value="<?php echo $sSipaddr;?>">
  <input type=hidden name=sInfo    value="<?php echo $sInfo;?>">
  <?php 
}
?>