<?php

$app = require __DIR__.'/../app/bootstrap.php';


$app['debug'] = true;
$app->register(new \Sorien\Provider\PimpleDumpProvider());

$app->get('/', function () use ($app) {

    var_dump($app['orm.em']->getRepository(\Xearts\SilexBase\Entity\Test::class)->findAll());

    return $app->render('index.html.twig');
});

$app->get('/hello/{name}', function ($name) use ($app) {

    var_dump($app['orm.em']->getRepository(\Xearts\SilexBase\Entity\Test::class)->findAll());

    return $app->render('hello.html.twig', array('name' => $name));
});


$app->run();

