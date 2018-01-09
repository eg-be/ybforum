<?
	error_reporting (E_ALL);
	header("Content-type: text/xml"); 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Datum in der Vergangenheit
	include ("cfg/config.php");
  include ("functions.php");
  include ("prechecks.php");
  
  function DbError ($Db) {
  		echo mysql_error($Db); 
  }
  
  
  function FixForumText ($text) {
		$subject = $text;
  			if (version_compare(PHP_VERSION, '5.4.0','>='))  {
				$subject = utf8_encode($subject);
			}
    		$subject = DecryptText($subject);

    		$subject  = html_entity_decode($subject) ;   	
		
    		$subject = htmlspecialchars($subject);
			if (version_compare(PHP_VERSION, '5.4.0','<'))  {
				$subject = utf8_encode($subject);
			}
    			
			return $subject;
  }
  class Options {
  	 public $ShowReplys = false;
  	 public $LimitPostingCount = 20;
  	 public $DetailThread = 0;
  	 public $LastPosition = 0;
  	 
  	 public $Username = "";
  	 public $Password = "";
  	 
  }
  
  class Posting {
  	public $title = "";
  	public $text = "";
  	public $replyTo = 0;
  	
  	function ReadFromPostVars() {
  		$this->title = (isset($_POST['p_title']) ? $_POST['p_title'] : "");
			$this->text = (isset($_POST['p_text']) ? $_POST['p_text'] : "");  		
			$this->replyTo = (isset($_POST['replyTo']) ? $_POST['replyTo'] : 0);  		
  	}  	
  	
  }
  
  
class ybInterface {
		private $db;
		private $dom;
		private $root;
		public $Error="";

		
	public $Options;
		 function __construct() {
     		  $this->Options = new Options();     		  
   }
   
   
	
 public function Init() {
 	 	  
 	 	  global $DbHost;
 	 	  global $DbName;
 	 	   	   	  global $DbUser;
 	 	   	   	   	  global $DbPass;
 	 	   
   	if (!($this->db=OpenDb($DbHost,$DbName,$DbUser,$DbPass))) {	
   			echo "DB Failed";
		}
		mysql_select_db($DbName,$this->db);
		DbError($this->db);  
		
		
		 $this->dom = new DOMDocument('1.0','utf-8');
		$this->root = $this->dom->createElement('threads');
		$this->dom->appendChild($this->root);
}


 public function ListTopics( $ParentNode=0, $RelativeRoot=NULL, $level=0 ) {
 	  global $DbTab;
 	  global $sDelSign; 
 	  
 	  if ($this->Options->DetailThread > 0)
 	  	$detailthreadfilter = "and ff.thread=". (int) $this->Options->DetailThread;
 	  else
 	  	$detailthreadfilter ="";
 	  
 	  // , 	(select count(*) from forum_forum rep where rep.preno=no) as replycnt
 	  $sql =  "SELECT ff.* 	  
 	           ,(select count(*) from forum_forum rep where rep.thread=ff.thread and del  = '-')-1 as replycnt 	   	  			
 	  		 FROM 
 	  		 		forum_forum ff
 	  		 	where  
				
				  ff.preno=".($ParentNode+0)."				  
				  $detailthreadfilter
				  and 	ff.del  = '-'";
			
    if 	($ParentNode>0) 
		$sql .="	  order by ff.no asc    ";
	else
		$sql .="	  order by ff.no desc    ";
	$sql .="		limit  ". (int) $this->Options->LastPosition.",". (int) $this->Options->LimitPostingCount;	

     $DbQueryThreads=mysql_query($sql,$this->db );
     if (!$DbQueryThreads) 
     	  DbError($this->db );                      
                   
     if (isset($RelativeRoot))             
     	$myRootNode = $RelativeRoot;
     else
      $myRootNode = $this->root;              
    while ($DbRow=mysql_fetch_array($DbQueryThreads)) {
    	
    		$threadid=mysql_real_escape_string ($DbRow['thread']);
    		$athread = $this->dom->createElement('thread');
    		$athread -> setAttribute('threadid',$DbRow['thread']);
      	$athread -> setAttribute('replycount',$DbRow['replycnt']);
      	$athread -> setAttribute('level',$level);
      	$myRootNode->appendChild($athread);
    
   
				$datesplit = split('\.',$DbRow['date']); 
				$time = $DbRow['time'];
				$xmldate = $datesplit[2].'-'.$datesplit[1].'-'.$datesplit[0].'T'.$time;
				
				$date= $this->dom->createElement('date',$xmldate);
    		$athread->appendChild($date);    		
    		
    		$author= $this->dom->createElement('author',utf8_encode($DbRow['author']));
    		$athread->appendChild($author);    		
    		
    		
    		$subjectnode= $this->dom->createElement('subject',FixForumText($DbRow['subject']));
    		$athread->appendChild($subjectnode);
    		
    		
    		$textnode= $this->dom->createElement('text',FixForumText($DbRow['ptext']));
    		$athread->appendChild($textnode);    		
    		
    		$textnode= $this->dom->createElement('picture',FixForumText($DbRow['picurl']));
    		$athread->appendChild($textnode);    		 
    		
    		$textnode= $this->dom->createElement('link',FixForumText($DbRow['homeurl']));
    		$athread->appendChild($textnode);    		
    		$textnode= $this->dom->createElement('linkname',FixForumText($DbRow['homename']));
    		$athread->appendChild($textnode);    		
    		
    		
    		if (($this->Options->ShowReplys) && ($DbRow['replycnt']>0))  {
    			$replysnode= $this->dom->createElement('replys'); 
    	  	$athread->appendChild($replysnode);    		
    			 $this->ListTopics($DbRow['no'],$replysnode ,$level+1);
    		}
    
    		
    }                
                         
    
                         
 }   
 
 
  function CheckLogin() {
    global $DbTab;
     
	  $sql =  "SELECT count(*) as cnt
 	  		 FROM  forum_regusr
 	  		 	where  				
				  name='".mysql_real_escape_string($this->Options->Username)."'				  
				  and
				  passwd='".md5($this->Options->Password)."'";
				  
     $DbQueryThreads=mysql_query($sql,$this->db );
     if (!$DbQueryThreads) 
     	  DbError($this->db );                      
      
		$arr=mysql_fetch_array($DbQueryThreads);
		$myRootNode = $this->root;   
		if ($arr['cnt'] == 1)
		 		$athread = $this->dom->createElement('login','ok');
		 else {
		 	 $athread = $this->dom->createElement('login','incorrect');
		 	 sleep(2);
		 }
		$myRootNode->appendChild($athread);
   }
 
 
 function Posting ($posting) {
 	
		  global $DbHost,$DbName,$DbUser,$DbPass,$DbTab,$DbReg;
		  
		  global $Db,$MaxLoginFails;
				
				
		  $sAuthor= $this->Options->Username;
		  
		  $sEmail = "";
		  $sHomeurl = "";
		  $sHomename ="";
		  $sPicurl ="";
		  $sSubject=$posting->title;
		  $sText=$posting->text;
		  
		   //$sNo,$sNoSrc,
		  $sReg = "-";
		  $sRemoteAddr= $_SERVER['REMOTE_ADDR'];			
		  $sRemotePort= $_SERVER['REMOTE_PORT'];
		  $sHttpUserAgent =  $_SERVER['REMOTE_PORT'];
		  $bReg = false;
		  
		  
		  $sNo =  $posting->replyTo;
		  $sNoSrc =0;
		  
		  		  
		   if ($DbRow=GetRegUsr($Db,$DbHost,$DbName,$DbUser,$DbPass,$DbReg,$this->Options->Username)) {
		     if ($DbRow[7]<$MaxLoginFails) {
 		      if (($DbRow[1]==md5($this->Options->Password))) {
			      $sAuthor=$DbRow[0];
			      $sReg="R";
			      $bReg=true;
				    $sQuery="update $DbReg set miscnt=0 where name='".$this->Options->Username."'";				    
				    $Db=NULL; 
				    DbQuery($Db,$DbHost,$DbName,$DbUser,$DbPass,$sQuery);
					}
				}
			}
			
			if (!$bReg)
				throw new Exception("invalid login");
	
 		 @WritePosting($DbHost,$DbName,$DbUser,$DbPass,$DbTab,
		              $sAuthor,$sEmail,$sText,$sHomeurl,$sHomename,$sPicurl,$sSubject,
					  $sNo,$sNoSrc,$sReg,
					  $sRemoteAddr,$sRemotePort,$sHttpUserAgent,"wp8");
					  
		$errorNode=$this->dom->createElement('status', "ok");
 		$this->root->appendChild($errorNode);			  
					  
}
 
 	function EchoXML () {
 	if ($this->Error!="") {
 		$errorNode=$this->dom->createElement('error',utf8_encode($this->Error));
 		$this->root->appendChild($errorNode);
 	}
 		
 		 echo $this->dom->saveXML();
 	}                       
       
   }
       
  
  
  
  $ybI = new ybInterface();
	 
	$mode="ListTopics";
	if (isset($_GET['replys']))
		$ybI->Options->ShowReplys=true;
	if (isset($_GET['detailthread']))
		$ybI->Options->DetailThread=(int) $_GET['detailthread'] ;		
		
	if (isset($_GET['limitpostingcount']))
		$ybI->Options->LimitPostingCount=(int) $_GET['limitpostingcount'] ;		
	
	if (isset($_GET['lastposition']))
		$ybI->Options->LastPosition=(int) $_GET['lastposition'] ;		

	
	$ybI->Options->Username=(isset($_GET['username']) ? $_GET['username'] : (isset($_POST['username']) ? $_POST['username'] : ""));
	$ybI->Options->Password=(isset($_GET['password']) ? $_GET['password'] : (isset($_POST['password']) ? $_POST['password'] : ""));
  	
  
  if (isset($_GET['checklogin'])) 
  	$mode="CheckLogin";  	
 
  if (isset($_GET['posting'])) 
  	$mode="Posting";  	
 
	try
  {	
    $ybI->Init(); 
    if ($mode == "ListTopics")
  		$ybI->ListTopics();
  	if ($mode == "CheckLogin")
  		$ybI->CheckLogin();
  		
  	if ($mode == "Posting") {  		
  		$post = new Posting();
  		$post->ReadFromPostVars();
  		$ybI->Posting($post);
  	}
  } 
  catch(Exception $e)
  {
    $ybI->Error=$e->getMessage();
  }
   
	$ybI->EchoXML();
	
?>
