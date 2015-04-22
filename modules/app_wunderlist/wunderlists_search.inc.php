<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['wunderlists_qry'];
  } else {
   $session->data['wunderlists_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_wunderlists;
  if (!$sortby_wunderlists) {
   $sortby_wunderlists=$session->data['wunderlists_sort'];
  } else {
   if ($session->data['wunderlists_sort']==$sortby_wunderlists) {
    if (Is_Integer(strpos($sortby_wunderlists, ' DESC'))) {
     $sortby_wunderlists=str_replace(' DESC', '', $sortby_wunderlists);
    } else {
     $sortby_wunderlists=$sortby_wunderlists." DESC";
    }
   }
   $session->data['wunderlists_sort']=$sortby_wunderlists;
  }
  if (!$sortby_wunderlists) $sortby_wunderlists="TITLE";
  $out['SORTBY']=$sortby_wunderlists;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM wunderlists WHERE $qry ORDER BY ".$sortby_wunderlists);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
