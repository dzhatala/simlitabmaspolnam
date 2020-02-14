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

$app->match('/document/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'DOCUMENT_ID', 
		'PATH_COMPLETE', 
		'PATH_BACKUP', 
		'MD5SUM', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(1024)', 
		'varchar(1024)', 
		'varchar(255)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ( 1 AND ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";

    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `document`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `document`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
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
$app->match('/document/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . document . " WHERE ".$idfldname." = ?";
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



$app->match('/document', function () use ($app) {
    
	$table_columns = array(
		'DOCUMENT_ID', 
		'PATH_COMPLETE', 
		'PATH_BACKUP', 
		'MD5SUM', 

    );

    $primary_key = "DOCUMENT_ID";	

    return $app['twig']->render('document/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('document_list');



$app->match('/document/create', function () use ($app) {
    
    $initial_data = array(
		'PATH_COMPLETE' => '', 
		'PATH_BACKUP' => '', 
		'MD5SUM' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_COMPLETE', 'text', array_merge(array('required' => true),$field_default_ro));
	$form = $form->add('PATH_COMPLETE_MD5', 'text', array('read_only' => true));
	$form = $form->add('PATH_COMPLETE_UPLOAD', 'file', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_BACKUP', 'text', array_merge(array('required' => true),$field_default_ro));
	$form = $form->add('PATH_BACKUP_MD5', 'text', array('read_only' => true));
	$form = $form->add('PATH_BACKUP_UPLOAD', 'file', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('MD5SUM', 'text', array_merge(array('required' => true),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			/**__BEFORE_EXECUTE__UPDATE_CREATE__**/
            $update_query = "INSERT INTO `document` (`PATH_COMPLETE`, `PATH_BACKUP`, `MD5SUM`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['PATH_COMPLETE'], $data['PATH_BACKUP'], $data['MD5SUM']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'document created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('document_list'));

        }
    }

    return $app['twig']->render('document/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('document_create');



$app->match('/document/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `document` WHERE `DOCUMENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('document_list'));
    }

    
    $initial_data = array(
		'PATH_COMPLETE' => $row_sql['PATH_COMPLETE'], 
		'PATH_BACKUP' => $row_sql['PATH_BACKUP'], 
		'MD5SUM' => $row_sql['MD5SUM'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_COMPLETE', 'text', array_merge(array('required' => true),$field_default_ro));
	$form = $form->add('PATH_COMPLETE_MD5', 'text', array('read_only' => true));
	$form = $form->add('PATH_COMPLETE_UPLOAD', 'file', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PATH_BACKUP', 'text', array_merge(array('required' => true),$field_default_ro));
	$form = $form->add('PATH_BACKUP_MD5', 'text', array('read_only' => true));
	$form = $form->add('PATH_BACKUP_UPLOAD', 'file', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('MD5SUM', 'text', array_merge(array('required' => true),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			/**__BEFORE_EXECUTE__UPDATE_EDIT__**/

            $update_query = "UPDATE `document` SET `PATH_COMPLETE` = ?, `PATH_BACKUP` = ?, `MD5SUM` = ? WHERE `DOCUMENT_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['PATH_COMPLETE'], $data['PATH_BACKUP'], $data['MD5SUM'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'document edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('document_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('document/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('document_edit');



$app->match('/document/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `document` WHERE `DOCUMENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `document` WHERE `DOCUMENT_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'document deleted!',
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

    return $app->redirect($app['url_generator']->generate('document_list'));

})
->bind('document_delete');






