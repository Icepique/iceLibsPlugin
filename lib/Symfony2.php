<?php

require_once '/www/libs/symfony-2.0.x/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$autoloader = new UniversalClassLoader();

$autoloader->registerNamespaces(array(
  'Symfony' => '/www/libs/symfony-2.0.x/src',
  'Assetic' => '/www/libs/symfony-2.0.x/src'
));

$autoloader->registerPrefixes(array(
  'Swift_' => '/www/libs/symfony-2.0.x/vendor/swiftmailer/lib/classes',
  'Twig_'  => '/www/libs/symfony-2.0.x/vendor/twig/lib',
));

$autoloader->register();
