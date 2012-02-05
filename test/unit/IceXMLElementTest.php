<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceXMLElement.class.php';

$t = new lime_test(1, new lime_output_color());

$t->diag('::join()');

  $xml_append = simplexml_load_string('<data><items><item id="1">value</item></items></data>');
  $xml_root = new SimpleXMLElement('<result></result>');

  $xml_child = $xml_root->addChild('clone');
  IceXMLElement::join($xml_child, $xml_append->items->item[0]);

  $t->is(trim($xml_root->asXML()), "<?xml version=\"1.0\"?>\n<result><clone><item id=\"1\">value</item></clone></result>");