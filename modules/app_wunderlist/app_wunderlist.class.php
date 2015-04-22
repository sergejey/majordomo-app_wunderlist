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
 //$this->admin($out);

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
  $task=array('title'=>$datat[$it]['title'], 'id'=>$datat[$it]['id'], 'due_date'=>$datat[$it]['due_date'], 'starred'=>$datat[$it]['starred']);
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
