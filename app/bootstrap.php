<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$app = new Xearts\SilexBase\Application([
    'app_dir' => __DIR__,
    'src_dir' => __DIR__.'/../src',
]);

return $app;
