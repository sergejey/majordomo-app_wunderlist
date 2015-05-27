<?php

Define('WUNDERLIST_CLIENT_ID', '5b3ba85622d9dc71b8a0');

/**
* Wunderlist 
*
* App_wunderlist
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 14:04:19 [Apr 22, 2015])
*/
//
//
class app_wunderlist extends module {
/**
* app_wunderlist
*
* Module class constructor
*
* @access private
*/
function app_wunderlist() {
  $this->name="app_wunderlist";
  $this->title="Wunderlist";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }

 if ($this->view_mode=='refresh') {
  $this->refreshAll();
  $this->redirect("?");
 }

 if ($this->data_source=='wunderlists' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_wunderlists') {
   $this->search_wunderlists($out);
  }
  if ($this->view_mode=='edit_wunderlists') {
   $this->edit_wunderlists($out, $this->id);
  }
  if ($this->view_mode=='delete_wunderlists') {
   $this->delete_wunderlists($this->id);
   $this->redirect("?");
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 global $op;

 if ($op=='add') {
  global $title;
  global $list;
  global $wunder_account;
  if (!$title) {
   echo "Incorrect usage";
   return;
  }
  $res=$this->addTask($title, $list, $wunder_account);
  if ($res) {
   echo "OK";
  } else {
   echo "Error";
  }
 }

 if ($op=='complete') {
  global $title;
  global $wunder_account;
  if (!$title) {
   echo "Incorrect usage";
   return;
  }
  $res=$this->completeTask($title, $wunder_account);
  if ($res) {
   echo "OK";
  } else {
   echo "Error";
  }
 }

 exit;

}
/**
* wunderlists search
*
* @access public
*/
 function search_wunderlists(&$out) {
  require(DIR_MODULES.$this->name.'/wunderlists_search.inc.php');
 }
/**
* wunderlists edit/add
*
* @access public
*/
 function edit_wunderlists(&$out, $id) {
  require(DIR_MODULES.$this->name.'/wunderlists_edit.inc.php');
 }
/**
* wunderlists delete record
*
* @access public
*/
 function delete_wunderlists($id) {
  $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM wunderlists WHERE ID='".$rec['ID']."'");
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshAll() {
  $lists=SQLSelect("SELECT ID FROM wunderlists ORDER BY UPDATED");
  $total=count($lists);
  for($i=0;$i<$total;$i++) {
   $this->refreshData($lists[$i]['ID']);
  }
 }

/**
* Title
*
* Description
*
* @access public
*/
 function completeTask($title, $wunder_account='') {
  if (!$wunder_account) {
   $rec=SQLSelectOne("SELECT * FROM wunderlists ORDER BY ID LIMIT 1");
  } else {
   $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE TITLE LIKE '".DBSafe($wunder_account)."'");
  }

  if (!$rec['DATA']) {
   $this->refreshData($rec['ID']);
   $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE ID = '".$rec['ID']."'");
  }

  if (!$rec['ID']) {
   return 0;
  }

  $lists=unserialize($rec['DATA']);
  $list_id=0;




  $task_id=0;
  $revision=0;

  foreach($lists as $k=>$v) {
   $tasks=$v['TASKS'];
   if (is_array($tasks)) {
    $total=count($tasks);
    for($i=0;$i<$total;$i++) {
     if ($tasks[$i]['title']==$title) {
      $task_id=$tasks[$i]['id'];
      $revision=$tasks[$i]['revision'];
     }
    }
   }
  }



  if (!$task_id || !$revision) {
   return 0;
  }


//UPDATE TASK
$data = array(
 "revision" => $revision, 
 "completed" => true);                                                                    

$data_string = json_encode($data); 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://a.wunderlist.com/api/v1/tasks/".$task_id);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string),
    'X-Access-Token: '.$rec['TOKEN'],
    'X-Client-ID: '.WUNDERLIST_CLIENT_ID
    ));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$data=json_decode($server_output, TRUE);
curl_close ($ch);

  if ($data['created_at']) {
   $this->refreshData($rec['ID']);
   return 1;
  } else {
   return 0;
  }


  
 }

/**
* Title
*
* Description
*
* @access public
*/
 function addTask($title, $list='', $wunder_account='', $when=0) {
  if (!$wunder_account) {
   $rec=SQLSelectOne("SELECT * FROM wunderlists ORDER BY ID LIMIT 1");
  } else {
   $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE TITLE LIKE '".DBSafe($wunder_account)."'");
  }

  if (!$rec['DATA']) {
   $this->refreshData($rec['ID']);
   $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE ID = '".$rec['ID']."'");
  }

  if (!$rec['ID']) {
   return 0;
  }

  if ($when) {
   $tm=$when;
  } else {
   
        if (preg_match('/завтра/isu', $title, $m)) { //tomorrow
         $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
        } elseif (preg_match('/послезавтра/isu', $title, $m)) { // day after tomorrow
         $title=trim(str_replace($m[0], '', $title));
         $tm=time()+2*24*60*60;
        } elseif (preg_match('/понедельник/isu', $title, $m)) { // monday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=1) {
                  $tm+=1*24*60*60;
                 }
        } elseif (preg_match('/вторник/isu', $title, $m)) { // tuesday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=2) {
                  $tm+=1*24*60*60;
                 }
        } elseif (preg_match('/сред(а|у)/isu', $title, $m)) { // wednesday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=3) {
                  $tm+=1*24*60*60;
                 }               
        } elseif (preg_match('/четверг/isu', $title, $m)) { // thursday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=4) {
                  $tm+=1*24*60*60;
                 }                               
        } elseif (preg_match('/пятниц(а|у)/isu', $title, $m)) { // friday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=5) {
                  $tm+=1*24*60*60;
                 }      
        } elseif (preg_match('/суббот(а/у)/isu', $title, $m)) { // saturday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=6) {
                  $tm+=1*24*60*60;
                 }      
        } elseif (preg_match('/воскресенье/isu', $title, $m)) { // sunday
                 $title=trim(str_replace($m[0], '', $title));
                 $tm=time()+1*24*60*60;
                 while(date('w',$tm)!=0) {
                  $tm+=1*24*60*60;
                 }                       
        } else {
          $tm=time();
        }   
   
  }

  if (!$list) {
   $list='inbox';
  }

  $due_date = date("Y-m-d",$tm);

  $lists=unserialize($rec['DATA']);
  $list_id=0;

  foreach($lists as $k=>$v) {
   if ($v['title']==$list) {
    $list_id=$k;
   }
  }

  if (!$list_id) {
   return 0;
  }
  
//ADD TASK
$data = array(
 "list_id" => $list_id, 
 "title" => $title, 
 "due_date" => $due_date);                                                                    

$data_string = json_encode($data); 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://a.wunderlist.com/api/v1/tasks");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string),
    'X-Access-Token: '.$rec['TOKEN'],
    'X-Client-ID: '.WUNDERLIST_CLIENT_ID
    ));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$data=json_decode($server_output, TRUE);
curl_close ($ch);

  if ($data['created_at']) {
   $this->refreshData($rec['ID']);
   return 1;
  } else {
   return 0;
  }


 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshData($id) {
   $rec=SQLSelectOne("SELECT * FROM wunderlists WHERE ID='".(int)$id."'");
   if (!$rec['ID'] || !$rec['TOKEN']) {
    return;
   }

//GET LISTS
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://a.wunderlist.com/api/v1/lists");

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-Access-Token: '.$rec['TOKEN'],
    'X-Client-ID: '.WUNDERLIST_CLIENT_ID
    ));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$data=json_decode($server_output, TRUE);
curl_close ($ch);

  if (!is_array($data)) {
   return 0;
  }

  $total=count($data);
  $lists=array();
  for($i=0;$i<$total;$i++) {
   $lists[$data[$i]['id']]=array('title'=>$data[$i]['title']);

//GET TASKS
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://a.wunderlist.com/api/v1/tasks?list_id=".$data[$i]['id']."&completed=false");

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-Access-Token: '.$rec['TOKEN'],
    'X-Client-ID: '.WUNDERLIST_CLIENT_ID
    ));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$datat=json_decode($server_output, TRUE);
curl_close ($ch);

//$lists[$data[$i]['id']]['tasks']=$data;
if (is_array($datat)) {
 $totalt=count($datat);
 $lists[$data[$i]['id']]['TASKS']=array();
 for($it=0;$it<$totalt;$it++) {
  $task=array('title'=>$datat[$it]['title'], 'id'=>$datat[$it]['id'], 'due_date'=>$datat[$it]['due_date'], 'starred'=>$datat[$it]['starred'], 'revision'=>$datat[$it]['revision']);
  $lists[$data[$i]['id']]['TASKS'][]=$task;
  if ($datat[$it]['due_date'] && strtotime($datat[$it]['due_date'])<=time()) {
   $task['list_title']=$data[$i]['title'];
   $lists['TODAY']['TASKS'][]=$task;
  }
 }
}

  }


   $rec['DATA']=serialize($lists);
   $rec['UPDATED']=date('Y-m-d H:i:s');
   SQLUpdate('wunderlists', $rec);

   if ($rec['LINKED_OBJECT']) {
    
    if (!$lists['TODAY']['TASKS']) {
     $today_list='';
    } else {
     $list=array();
     $total=count($lists['TODAY']['TASKS']);
     for($i=0;$i<$total;$i++) {
      $list[]=$lists['TODAY']['TASKS'][$i]['title'];
     }
     $today_list=implode("\n", $list);
    }
    setGlobal($rec['LINKED_OBJECT'].'.WunderlistTasksToday', $today_list, array($this->name=>'0'));
    setGlobal($rec['LINKED_OBJECT'].'.WunderlistTasks', $rec['DATA'], array($this->name=>'0'));

   }
 }

/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS wunderlists');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
wunderlists - Wunderlist
*/
  $data = <<<EOD
 wunderlists: ID int(10) unsigned NOT NULL auto_increment
 wunderlists: TITLE varchar(255) NOT NULL DEFAULT ''
 wunderlists: TOKEN varchar(255) NOT NULL DEFAULT ''
 wunderlists: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 wunderlists: DATA text
 wunderlists: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDIyLCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
