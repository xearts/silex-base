<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$app = new Xearts\SilexBase\Application([
    'app_dir' => __DIR__.'/../app',
    'src_dir' => __DIR__.'/../src',
]);

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

