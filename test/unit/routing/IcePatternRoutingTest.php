<?php

require_once dirname(__FILE__).'/../../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../../../../test/bootstrap/routing.php';

$t = new lime_test(9, array('output' => new lime_output_color(), 'error_reporting' => true));

$t->diag('::generate()');

  $configuration->loadHelpers('Url');

  $url = url_for('@sitemap');
  $t->is($url, '/карта-на-сайта');

  $url = url_for('@sitemap?sf_culture=en_US');
  $t->is($url, '/en_US/sitemap');

  $url = url_for('@sitemap_by_category?category=publisher');
  $t->is($url, '/карта-на-сайта/издател');

  $url = url_for('@sitemap_by_category?category=publisher&sf_culture=en_US');
  $t->is($url, '/en_US/sitemap/publisher');

  $url = url_for('@sitemap?page=test');
  $t->is($url, '/карта-на-сайта?page=test', 'Testing the passing of GET parameters');

  $url = url_for('@publisher_by_slug?id=1&slug=Ciela');
  $t->is($url, '/издател/Ciela-1.html');

  $url = url_for('@publisher_by_slug?id=1&slug=Ciela&sf_culture=en_US');
  $t->is($url, '/en_US/publisher/Ciela-1.html');

  $url = url_for('@publisher_by_slug?id=1&slug=Ciela&page=test');
  $t->is($url, '/издател/Ciela-1.html?page=test', 'Testing the passing of GET parameters');

  $url = url_for('@publisher_by_slug?id=1&slug=Ciela&page=test&encrypt=1');
  $t->is(substr($url, 0, 8), '/?ex=v1;', 'Testing the passing of GET parameters with encryption');
