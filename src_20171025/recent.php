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
  include ("prechecks.php");
  include_once("chkloginmode.php");
  
  $sSubTitle="Neue Beitr&auml;ge";
  EchoHeader($Title,$BodyText,$BodyBgcolor,$BodyLink,$BodyAlink,$BodyVlink,$BodyBackground,$sSubTitle,$Banner,$Font);
  
  EchoRecentMenu($DbHost,$DbName,$DbUser,$DbPass,$DbFnc);
  
  if ($Db=OpenDb($DbHost,$DbName,$DbUser,$DbPass)) {
    $iRecentLength=intval($RecentLength);
	if ($iRecentLength==0) {
	  $sQuery="select count(*) from $DbTab where del='-'";
	  $DbQuery=DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
	  $DbRow=mysql_fetch_row($DbQuery);
	  $iRecentLength=$DbRow[0];
	}
    $DbQuery=mysql_db_query($DbName,
	                        "select no,preno,author,email,regular,
                                      date,time,picurl,homeurl,homename,
                                      subject,del,tclose from $DbTab
							          where ((del='-') or (del='T'))
									  order by no desc
									  limit $iRecentLength"
	                        ,$Db);
	mysql_close($Db);
	
	while ($DbRow=mysql_fetch_row($DbQuery)) {
	  $MsgDeep=0;
      $bThreadBegin=false;
	  $Db=NULL;
      EchoThread($DbRow,$MsgDeep,$DbRegTab,$Db,$bThreadBegin,
                 $DbHost,$DbName,$DbUser,$DbPass,$DbReg,
				 $RegColor,$AdminColor,$RegsSameCol,$RegsActive,true);
    }
  }
  $aArchive=BuildArchiveList();
  EchoArchiveLinks($aArchive);	
  
  EchoFooter();
?>