<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceWebBrowser.class.php';

$t = new lime_test(15, array(new lime_output_color()));

$t->diag('::isUrl()');

  $urls = array(
    array('url' => 'http://www.youtube.com/watch?v=Elfr-uU73qs', 'valid' => true),
    array('url' => 'http://metafrasiorg.blogspot.com/2010/05/blog-post.html', 'valid' => true),
    array('url' => 'http://ifiremember.blogspot.com/?expref=next-blog', 'valid' => true),
    array('url' => 'http://partnerpage.google.com/icepique.com', 'valid' => true),
    array('url' => 'http://www.mobilenews.bg/bg/articles/view/6228/rim_raboti_vyrhu_black_berry_za_nezrjashti/', 'valid' => true),
    array('url' => 'http://www.akademika.bg/wp-content/uploads/2010/05/10-2_BEL_DZI__klyuch.pdf', 'valid' => true),
    array('url' => 'https://www.akademika.bg/wp-content/uploads/2010/05/10-2_BEL_DZI__klyuch.pdf', 'valid' => true),
    array('url' => 'http://auto-press.net/%D0%A2%D0%B5%D1%81%D1%82%D0%BE%D0%B2%D0%B5__Test/a:Renault_Scenic_Conquest___%D0%B5%D0%BD%D0%B5%D1%80%D0%B3%D0%B8%D1%87%D0%BD%D0%B0%D1%82%D0%B0_%D0%BA%D0%BE%D0%BB%D0%B0', 'valid' => true),
    array('url' => 'http://auto-press.net/Тестове__Test/a:Renault_Scenic_Conquest___енергичната_кола', 'valid' => true),
    array('url' => '/www/vhosts/default/image.png', 'valid' => false),
    array('url' => 'www.ka2motors.com', 'valid' => false),
    array('url' => 'www.autoitbg.com', 'valid' => false),
    array('url' => 'markoniauto.cars.bg', 'valid' => false),
    array('url' => 'www.londoncity.bg', 'valid' => false),
    array('url' => 'http://', 'valid' => false),
  );

  foreach ($urls as $url)
  {
    $numbers = IceWebBrowser::isUrl($url['url']);
    $t->is(IceWebBrowser::isUrl($url['url']), $url['valid'], 'Checking '. $url['url']);
  }
