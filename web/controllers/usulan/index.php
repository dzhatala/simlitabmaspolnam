<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/usulan/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'USULAN_ID', 
		'PENGUSUL', 
		'USULANDIBUKA_ID', 
		'TANGGAL_USUL', 
		'JUDUL', 
		'RINGKASAN', 
		'BIAYA', 
		'PATH_PROPOSAL', 
		'REVIEWER1', 
		'NILAI_REVIEW_1', 
		'PATH_REVIEW_1', 
		'REVIEWER2', 
		'NILAI_REVIEW_2', 
		'PATH_REVIEW_2', 
		'STATUSUSULAN_ID', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'date', 
		'varchar(255)', 
		'text', 
		'float', 
		'varchar(1024)', 
		'int(11)', 
		'float', 
		'varchar(1024)', 
		'int(11)', 
		'float', 
		'varchar(1024)', 
		'int(11)', 

    );    
    
	
    $whereClause = "";
    $transform_text_to_key=""; /**  ... to enable search for externals ...**/
	
	/** find externals fields for search key and passing it to like as and id **/
	
	/**Creating enabler .. for  usulan:pengusul  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `USERLOGIN_ID` FROM `userlogin` WHERE `LOGIN` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####pengusul=>userlogin######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  pengusul=".$search_row['USERLOGIN_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulan:usulandibuka_id  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `USULANDIBUKA_ID` FROM `usulandibuka` WHERE `NAMA` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####usulandibuka_id=>usulandibuka######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  usulandibuka_id=".$search_row['USULANDIBUKA_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulan:reviewer1  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `USERLOGIN_ID` FROM `userlogin` WHERE `LOGIN` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####reviewer1=>userlogin######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  reviewer1=".$search_row['USERLOGIN_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulan:reviewer2  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `USERLOGIN_ID` FROM `userlogin` WHERE `LOGIN` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####reviewer2=>userlogin######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  reviewer2=".$search_row['USERLOGIN_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulan:statususulan_id  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `STATUSUSULAN_ID` FROM `statususulan` WHERE `NAMA` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####statususulan_id=>statususulan######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  statususulan_id=".$search_row['STATUSUSULAN_ID']; 
	      } 
	    }
	 } 


    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ( 1 AND ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        //external search  version
		$whereClause =  $whereClause . "   usulan.".$col . " LIKE '%". $searchValue ."%' ".$transform_text_to_key;
        
		//non external search version ...
		//$whereClause =  $whereClause . "   usulan.".$col . " LIKE '%". $searchValue ."%' ";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";
    
	
    
	if ($app['credentials']['current_role']=="Pengusul"){
		  $whereClause .= " And Pengusul=".$app['credentials']['userlogin_id'] ; 
	} ;

	if ($app['credentials']['current_role']=="Reviewer"){
		  $whereClause .= " And (REVIEWER1=".$app['credentials']['userlogin_id'].  
		   " OR REVIEWER2=".$app['credentials']['userlogin_id']." )  "    ;
	} ;

	
	
	
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `usulan`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `usulan`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'PENGUSUL'){
			    $findexternal_sql = 'SELECT `LOGIN` FROM `userlogin` WHERE `USERLOGIN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOGIN'];
			}
			else if($table_columns[$i] == 'USULANDIBUKA_ID'){
			    $findexternal_sql = 'SELECT `NAMA` FROM `usulandibuka` WHERE `USULANDIBUKA_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['NAMA'];
			}
			else if($table_columns[$i] == 'REVIEWER1'){
			    $findexternal_sql = 'SELECT `LOGIN` FROM `userlogin` WHERE `USERLOGIN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOGIN'];
			}
			else if($table_columns[$i] == 'REVIEWER2'){
			    $findexternal_sql = 'SELECT `LOGIN` FROM `userlogin` WHERE `USERLOGIN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOGIN'];
			}
			else if($table_columns[$i] == 'STATUSUSULAN_ID'){
			    $findexternal_sql = 'SELECT `NAMA` FROM `statususulan` WHERE `STATUSUSULAN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['NAMA'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
			}


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/usulan/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . usulan . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/usulan', function () use ($app) {
    
	$table_columns = array(
		'USULAN_ID', 
		'PENGUSUL', 
		'USULANDIBUKA_ID', 
		'TANGGAL_USUL', 
		'JUDUL', 
		'RINGKASAN', 
		'BIAYA', 
		'PATH_PROPOSAL', 
		'REVIEWER1', 
		'NILAI_REVIEW_1', 
		'PATH_REVIEW_1', 
		'REVIEWER2', 
		'NILAI_REVIEW_2', 
		'PATH_REVIEW_2', 
		'STATUSUSULAN_ID', 

    );
	
	/**translating here ...**/
	$tr_table_columns=array();
	foreach ($table_columns as $col){
		//var_dump($col) ;die;
		//array_push($tr_table_columns,$app['translator']->trans("usulan".$col));
		array_push($tr_table_columns,$col);
	}
	$table_columns=$tr_table_columns;
	/****/

    $primary_key = "USULAN_ID";	

    return $app['twig']->render('usulan/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('usulan_list');



$app->match('/usulan/create', function () use ($app) {
    
    $initial_data = array(
		'PENGUSUL' => '', 
		'USULANDIBUKA_ID' => '', 
		'TANGGAL_USUL' => '', 
		'JUDUL' => '', 
		'RINGKASAN' => '', 
		'BIAYA' => '', 
		'PATH_PROPOSAL' => '', 
		'REVIEWER1' => '', 
		'NILAI_REVIEW_1' => '', 
		'PATH_REVIEW_1' => '', 
		'REVIEWER2' => '', 
		'NILAI_REVIEW_2' => '', 
		'PATH_REVIEW_2' => '', 
		'STATUSUSULAN_ID' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']=="Pengusul"){
			$limiter=' WHERE userlogin.userlogin_id ='.$app['credentials']['userlogin_id']  ;
	}
	if($app['credentials']['current_role']=="Reviewer"){
			$limiter=' WHERE userlogin.userlogin_id ='.$row_sql['PENGUSUL']  ;
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=2" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if(count($options) > 0){
	    $form = $form->add('PENGUSUL', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('PENGUSUL', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE USULANDIBUKA_ID ='".$row_sql['USULANDIBUKA_ID']. "'"  ;
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND usulandibuka.status= optionsstatususulandibuka. Optionsstatususulandibuka_id and Optionsstatususulandibuka_id=1" ; 	
	$options = array();
	$findexternal_sql = 'SELECT usulandibuka.USULANDIBUKA_ID, usulandibuka.NAMA FROM usulandibuka , optionsstatususulandibuka   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USULANDIBUKA_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('USULANDIBUKA_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USULANDIBUKA_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= false  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER1']. "'"  ;
		}
	}
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")===false){
	if($row_sql['STATUSUSULAN_ID']!=1){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER1']. "'"  ;
		}
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=3" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if($app['credentials']['current_role']!="Administrator"){
	if (strpos($app['request']->getRequestUri(),"edit")!==false){
		$field_nullable = true;
	} else { 
		$options = array();
	} 
	} 
	if(count($options) > 0){
	    $form = $form->add('REVIEWER1', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('REVIEWER1', 'text', array_merge(array('required' => false),$field_default_ro));
	}

	$field_nullable= false  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER2']. "'"  ;
		}
	}
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")===false){
	if($row_sql['STATUSUSULAN_ID']!=1){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER2']. "'"  ;
		}
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=3" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if($app['credentials']['current_role']!="Administrator"){
	if (strpos($app['request']->getRequestUri(),"edit")!==false){
		$field_nullable = true;
	} else { 
		$options = array();
	} 
	} 
	if(count($options) > 0){
	    $form = $form->add('REVIEWER2', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('REVIEWER2', 'text', array_merge(array('required' => false),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")!==false){
			$limiter=' WHERE statususulan_id =1'  ;
		}
		else if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=' WHERE statususulan_id ='.$row_sql['STATUSUSULAN_ID']  ;
		}
	}
	$options = array();
	$findexternal_sql = 'SELECT statususulan.STATUSUSULAN_ID, statususulan.NAMA FROM statususulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STATUSUSULAN_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('STATUSUSULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('STATUSUSULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('TANGGAL_USUL', 'text', 
 		  $field_default_ro);
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('JUDUL', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('RINGKASAN', 'textarea', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('BIAYA', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_PROPOSAL', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Pengusul"){
		if (strpos($app['request']->getRequestUri(),"create")!==false){
			unset($field_default_ro['disabled']);
		}
		else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if($row_sql['STATUSUSULAN_ID']==1){
			 unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_PROPOSAL_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['REVIEWER1']==$app['credentials']['userlogin_id']){
		if ($row_sql['STATUSUSULAN_ID']=="2")
			unset($field_default_ro['disabled']);
		} 
		} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	$form = $form->add('NILAI_REVIEW_1', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_REVIEW_1', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Reviewer"){
		if($row_sql['STATUSUSULAN_ID']==2){
		if ($row_sql['REVIEWER1']==$app['credentials']['userlogin_id']){
			unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_REVIEW_1_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['REVIEWER2']==$app['credentials']['userlogin_id']){
		if ($row_sql['STATUSUSULAN_ID']=="2")
			unset($field_default_ro['disabled']);
		} 
		} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	$form = $form->add('NILAI_REVIEW_2', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_REVIEW_2', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Reviewer"){
		if($row_sql['STATUSUSULAN_ID']==2){
		if ($row_sql['REVIEWER2']==$app['credentials']['userlogin_id']){
			unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_REVIEW_2_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			
			if($data['PATH_PROPOSAL_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_PROPOSAL_UPLOAD']->getClientOriginalName();
				$data['PATH_PROPOSAL_UPLOAD']->move($app['uploaded_dir']."/".$app['userlogin_id']['login'],$forig);
				$data['PATH_PROPOSAL']=$forig ; 
			}
			if($data['PATH_REVIEW_1_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_REVIEW_1_UPLOAD']->getClientOriginalName();
				$data['PATH_REVIEW_1_UPLOAD']->move($app['uploaded_dir']."/".$app['userlogin_id']['login'],$forig);
				$data['PATH_REVIEW_1']=$forig ; 
			}
			if($data['PATH_REVIEW_2_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_REVIEW_2_UPLOAD']->getClientOriginalName();
				$data['PATH_REVIEW_2_UPLOAD']->move($app['uploaded_dir']."/".$app['userlogin_id']['login'],$forig);
				$data['PATH_REVIEW_2']=$forig ; 
			}
            $update_query = "INSERT INTO `usulan` (`PENGUSUL`, `USULANDIBUKA_ID`, `TANGGAL_USUL`, `JUDUL`, `RINGKASAN`, `BIAYA`, `PATH_PROPOSAL`, `REVIEWER1`, `NILAI_REVIEW_1`, `PATH_REVIEW_1`, `REVIEWER2`, `NILAI_REVIEW_2`, `PATH_REVIEW_2`, `STATUSUSULAN_ID`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['PENGUSUL'], $data['USULANDIBUKA_ID'], $data['TANGGAL_USUL'], $data['JUDUL'], $data['RINGKASAN'], $data['BIAYA'], $data['PATH_PROPOSAL'], $data['REVIEWER1'], $data['NILAI_REVIEW_1'], $data['PATH_REVIEW_1'], $data['REVIEWER2'], $data['NILAI_REVIEW_2'], $data['PATH_REVIEW_2'], $data['STATUSUSULAN_ID']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usulan created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usulan_list'));

        }
    }

    return $app['twig']->render('usulan/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('usulan_create');



$app->match('/usulan/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `usulan` WHERE `USULAN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('usulan_list'));
    }

    
    $initial_data = array(
		'PENGUSUL' => $row_sql['PENGUSUL'], 
		'USULANDIBUKA_ID' => $row_sql['USULANDIBUKA_ID'], 
		'TANGGAL_USUL' => $row_sql['TANGGAL_USUL'], 
		'JUDUL' => $row_sql['JUDUL'], 
		'RINGKASAN' => $row_sql['RINGKASAN'], 
		'BIAYA' => $row_sql['BIAYA'], 
		'PATH_PROPOSAL' => $row_sql['PATH_PROPOSAL'], 
		'REVIEWER1' => $row_sql['REVIEWER1'], 
		'NILAI_REVIEW_1' => $row_sql['NILAI_REVIEW_1'], 
		'PATH_REVIEW_1' => $row_sql['PATH_REVIEW_1'], 
		'REVIEWER2' => $row_sql['REVIEWER2'], 
		'NILAI_REVIEW_2' => $row_sql['NILAI_REVIEW_2'], 
		'PATH_REVIEW_2' => $row_sql['PATH_REVIEW_2'], 
		'STATUSUSULAN_ID' => $row_sql['STATUSUSULAN_ID'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']=="Pengusul"){
			$limiter=' WHERE userlogin.userlogin_id ='.$app['credentials']['userlogin_id']  ;
	}
	if($app['credentials']['current_role']=="Reviewer"){
			$limiter=' WHERE userlogin.userlogin_id ='.$row_sql['PENGUSUL']  ;
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=2" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if(count($options) > 0){
	    $form = $form->add('PENGUSUL', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('PENGUSUL', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE USULANDIBUKA_ID ='".$row_sql['USULANDIBUKA_ID']. "'"  ;
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND usulandibuka.status= optionsstatususulandibuka. Optionsstatususulandibuka_id and Optionsstatususulandibuka_id=1" ; 	
	$options = array();
	$findexternal_sql = 'SELECT usulandibuka.USULANDIBUKA_ID, usulandibuka.NAMA FROM usulandibuka , optionsstatususulandibuka   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USULANDIBUKA_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('USULANDIBUKA_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USULANDIBUKA_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= false  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER1']. "'"  ;
		}
	}
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")===false){
	if($row_sql['STATUSUSULAN_ID']!=1){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER1']. "'"  ;
		}
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=3" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if($app['credentials']['current_role']!="Administrator"){
	if (strpos($app['request']->getRequestUri(),"edit")!==false){
		$field_nullable = true;
	} else { 
		$options = array();
	} 
	} 
	if(count($options) > 0){
	    $form = $form->add('REVIEWER1', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('REVIEWER1', 'text', array_merge(array('required' => false),$field_default_ro));
	}

	$field_nullable= false  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER2']. "'"  ;
		}
	}
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")===false){
	if($row_sql['STATUSUSULAN_ID']!=1){
			$limiter=" WHERE userlogin.userlogin_id ='".$row_sql['REVIEWER2']. "'"  ;
		}
		}
	}

if((strpos(strtolower($limiter),"where"))===false){ 
$limiter .= " where  1 " ; 
}
$limiter .= " AND userroles.userlogin_id=userlogin.userlogin_id and userroles.user_role_type_id=3" ; 	
	$options = array();
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin , userroles   '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if($app['credentials']['current_role']!="Administrator"){
	if (strpos($app['request']->getRequestUri(),"edit")!==false){
		$field_nullable = true;
	} else { 
		$options = array();
	} 
	} 
	if(count($options) > 0){
	    $form = $form->add('REVIEWER2', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('REVIEWER2', 'text', array_merge(array('required' => false),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	if($app['credentials']['current_role']!="Administrator"){
		if (strpos($app['request']->getRequestUri(),"create")!==false){
			$limiter=' WHERE statususulan_id =1'  ;
		}
		else if (strpos($app['request']->getRequestUri(),"edit")!==false){
			$limiter=' WHERE statususulan_id ='.$row_sql['STATUSUSULAN_ID']  ;
		}
	}
	$options = array();
	$findexternal_sql = 'SELECT statususulan.STATUSUSULAN_ID, statususulan.NAMA FROM statususulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STATUSUSULAN_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('STATUSUSULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('STATUSUSULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('TANGGAL_USUL', 'text', 
 		  $field_default_ro);
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('JUDUL', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('RINGKASAN', 'textarea', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
	} 
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['STATUSUSULAN_ID']=="1"){
			unset($field_default_ro['disabled']);
		} 
	} 
	$form = $form->add('BIAYA', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_PROPOSAL', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Pengusul"){
		if (strpos($app['request']->getRequestUri(),"create")!==false){
			unset($field_default_ro['disabled']);
		}
		else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if($row_sql['STATUSUSULAN_ID']==1){
			 unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_PROPOSAL_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['REVIEWER1']==$app['credentials']['userlogin_id']){
		if ($row_sql['STATUSUSULAN_ID']=="2")
			unset($field_default_ro['disabled']);
		} 
		} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	$form = $form->add('NILAI_REVIEW_1', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_REVIEW_1', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Reviewer"){
		if($row_sql['STATUSUSULAN_ID']==2){
		if ($row_sql['REVIEWER1']==$app['credentials']['userlogin_id']){
			unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_REVIEW_1_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	else if (strpos($app['request']->getRequestUri(),"edit")!==false){
		if ($row_sql['REVIEWER2']==$app['credentials']['userlogin_id']){
		if ($row_sql['STATUSUSULAN_ID']=="2")
			unset($field_default_ro['disabled']);
		} 
		} 
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		unset($field_default_ro['disabled']);
		unset($field_default_ro['read_only']);
		$field_default_ro=array_merge($field_default_ro,array('read_only'=>true));
	} 
	$form = $form->add('NILAI_REVIEW_2', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_REVIEW_2', 'text', array_merge(array('required' => false),$field_default_ro));

	if($app['credentials']['current_role']=="Reviewer"){
		if($row_sql['STATUSUSULAN_ID']==2){
		if ($row_sql['REVIEWER2']==$app['credentials']['userlogin_id']){
			unset($field_default_ro['disabled']);
		}
		}
	}
	$form = $form->add('PATH_REVIEW_2_UPLOAD', 'file', array_merge(array('required' => false),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			
	if($data['PATH_PROPOSAL_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_PROPOSAL_UPLOAD']->getClientOriginalName();
			$data['PATH_PROPOSAL_UPLOAD']->move($app['uploaded_dir']."/".$app['credentials']['userlogin_id'],$forig);
				$data['PATH_PROPOSAL']=$forig ; 
	}
	if($data['PATH_REVIEW_1_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_REVIEW_1_UPLOAD']->getClientOriginalName();
			$data['PATH_REVIEW_1_UPLOAD']->move($app['uploaded_dir']."/".$app['credentials']['userlogin_id'],$forig);
				$data['PATH_REVIEW_1']=$forig ; 
	}
	if($data['PATH_REVIEW_2_UPLOAD']){
				$forig=$app['credentials']['userlogin_id']."__".date("Y_m_d_h_m_s__").$data['PATH_REVIEW_2_UPLOAD']->getClientOriginalName();
			$data['PATH_REVIEW_2_UPLOAD']->move($app['uploaded_dir']."/".$app['credentials']['userlogin_id'],$forig);
				$data['PATH_REVIEW_2']=$forig ; 
	}

            $update_query = "UPDATE `usulan` SET `PENGUSUL` = ?, `USULANDIBUKA_ID` = ?, `TANGGAL_USUL` = ?, `JUDUL` = ?, `RINGKASAN` = ?, `BIAYA` = ?, `PATH_PROPOSAL` = ?, `REVIEWER1` = ?, `NILAI_REVIEW_1` = ?, `PATH_REVIEW_1` = ?, `REVIEWER2` = ?, `NILAI_REVIEW_2` = ?, `PATH_REVIEW_2` = ?, `STATUSUSULAN_ID` = ? WHERE `USULAN_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['PENGUSUL'], $data['USULANDIBUKA_ID'], $data['TANGGAL_USUL'], $data['JUDUL'], $data['RINGKASAN'], $data['BIAYA'], $data['PATH_PROPOSAL'], $data['REVIEWER1'], $data['NILAI_REVIEW_1'], $data['PATH_REVIEW_1'], $data['REVIEWER2'], $data['NILAI_REVIEW_2'], $data['PATH_REVIEW_2'], $data['STATUSUSULAN_ID'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usulan edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usulan_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('usulan/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('usulan_edit');



$app->match('/usulan/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `usulan` WHERE `USULAN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `usulan` WHERE `USULAN_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'usulan deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('usulan_list'));

})
->bind('usulan_delete');






