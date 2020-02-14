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


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';


require_once __DIR__.'/custom/test_001.php';
require_once __DIR__.'/optionsstatususulandibuka/index.php';
require_once __DIR__.'/statususulan/index.php';
require_once __DIR__.'/tipeusulan/index.php';
require_once __DIR__.'/user_role_types/index.php';
require_once __DIR__.'/userlogin/index.php';
require_once __DIR__.'/userroles/index.php';
require_once __DIR__.'/tahun_usulan/index.php';
require_once __DIR__.'/usulandibuka/index.php';
require_once __DIR__.'/usulan/index.php';



$app->match('/', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');


$app->run();