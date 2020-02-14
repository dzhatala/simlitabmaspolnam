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

$app->match('/per_class_active_students/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'PERCLASS_ACTIVE_STUDENT_ID', 
		'CLASS_SECTION_ID', 
		'STUDENT_ID', 
		'STATUS', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'varchar(30)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `per_class_active_students`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `per_class_active_students`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'CLASS_SECTION_ID'){
			    $findexternal_sql = "select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   "  . " AND CLASS_SECTION_ID=? " ;
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
			}
			else if($table_columns[$i] == 'STUDENT_ID'){
			    $findexternal_sql = "select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students  WHERE `STUDENT_ID`=?   ";
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
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
$app->match('/per_class_active_students/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . per_class_active_students . " WHERE ".$idfldname." = ?";
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



$app->match('/per_class_active_students', function () use ($app) {
    
	$table_columns = array(
		'PERCLASS_ACTIVE_STUDENT_ID', 
		'CLASS_SECTION_ID', 
		'STUDENT_ID', 
		'STATUS', 

    );

    $primary_key = "PERCLASS_ACTIVE_STUDENT_ID";	

    return $app['twig']->render('per_class_active_students/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('per_class_active_students_list');



$app->match('/per_class_active_students/create', function () use ($app) {
    
    $initial_data = array(
		'CLASS_SECTION_ID' => '', 
		'STUDENT_ID' => '', 
		'STATUS' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   " ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['CLASS_SECTION_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('CLASS_SECTION_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('CLASS_SECTION_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students order by `ADMISSION_NO`  ";
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STUDENT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('STUDENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('STUDENT_ID', 'text', array('required' => true));
	}



	$form = $form->add('STATUS', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `per_class_active_students` (`CLASS_SECTION_ID`, `STUDENT_ID`, `STATUS`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['CLASS_SECTION_ID'], $data['STUDENT_ID'], $data['STATUS']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'per_class_active_students created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('per_class_active_students_list'));

        }
    }

    return $app['twig']->render('per_class_active_students/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('per_class_active_students_create');



$app->match('/per_class_active_students/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `per_class_active_students` WHERE `PERCLASS_ACTIVE_STUDENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('per_class_active_students_list'));
    }

    
    $initial_data = array(
		'CLASS_SECTION_ID' => $row_sql['CLASS_SECTION_ID'], 
		'STUDENT_ID' => $row_sql['STUDENT_ID'], 
		'STATUS' => $row_sql['STATUS'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   " ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['CLASS_SECTION_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('CLASS_SECTION_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('CLASS_SECTION_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students order by `ADMISSION_NO`  ";
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STUDENT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('STUDENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('STUDENT_ID', 'text', array('required' => true));
	}


	$form = $form->add('STATUS', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `per_class_active_students` SET `CLASS_SECTION_ID` = ?, `STUDENT_ID` = ?, `STATUS` = ? WHERE `PERCLASS_ACTIVE_STUDENT_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['CLASS_SECTION_ID'], $data['STUDENT_ID'], $data['STATUS'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'per_class_active_students edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('per_class_active_students_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('per_class_active_students/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('per_class_active_students_edit');



$app->match('/per_class_active_students/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `per_class_active_students` WHERE `PERCLASS_ACTIVE_STUDENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `per_class_active_students` WHERE `PERCLASS_ACTIVE_STUDENT_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'per_class_active_students deleted!',
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

    return $app->redirect($app['url_generator']->generate('per_class_active_students_list'));

})
->bind('per_class_active_students_delete');






