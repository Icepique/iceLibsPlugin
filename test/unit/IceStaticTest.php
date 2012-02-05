<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceStatic.class.php';

$t = new lime_test(167, array(new lime_output_color()));

$t->diag('::extractPhoneNumbers()');

  $phones = array(
    array('string' => '0886-180-177 / 0898-26-96-64', 'numbers' => array('0886180177', '0898269664'), 'count' => 2),
    array('string' => '0876387848,029345488', 'numbers' => array('029345488', '0876387848'), 'count' => 2),
    array('string' => '02/928-70-50', 'numbers' => array('029287050'), 'count' => 1),
    array('string' => '+359/0889400460', 'numbers' => array('0889400460'), 'count' => 1),
    array('string' => '034 440132    0896 740440', 'numbers' => array('034440132', '0896740440'), 'count' => 2),
    array('string' => '0878/408120, 0898/262403,0878/408228', 'numbers' => array('0878408120', '0878408228', '0898262403'), 'count' => 3),
    array('string' => '032-588-644 0888907431', 'numbers' => array('032588644', '0888907431'), 'count' => 2),
    array('string' => '00971509387138', 'numbers' => array('00971509387138'), 'count' => 0, 'strict' => false),
    array('string' => '0884194759   /  0878899377', 'numbers' => array('0878899377', '0884194759'), 'count' => 2),
    array('string' => '088\77 52 132', 'numbers' => array('0887752132'), 'count' => 1),
    array('string' => '0888778714.  0886626241', 'numbers' => array('0886626241', '0888778714'), 'count' => 2),
    array('string' => '0878 599 171 , 0894 718671', 'numbers' => array('0878599171', '0894718671'), 'count' => 2),
    array('string' => '+359 2 8105037', 'numbers' => array('028105037'), 'count' => 1),
    array('string' => '(0885) 197 905', 'numbers' => array('0885197905'), 'count' => 1),
    array('string' => '02-840-60-23 i 0897-930-243', 'numbers' => array('028406023', '0897930243'), 'count' => 2),
    array('string' => '058/602961', 'numbers' => array('058602961'), 'count' => 1),
    array('string' => '02/ 952 94 29; 02/ 851 08 18', 'numbers' => array('028510818', '029529429'), 'count' => 2),
    array('string' => '0878-390-304', 'numbers' => array('0878390304'), 'count' => 1),
    array('string' => ' 032*648163  , 0885655293 ,  0878534880', 'numbers' => array('032648163', '0878534880', '0885655293'), 'count' => 3),
    array('string' => '0899/663-771', 'numbers' => array('0899663771'), 'count' => 1),
    array('string' => '00359898000000', 'numbers' => array('0898000000'), 'count' => 1),
    array('string' => '02/8508646,0879262023', 'numbers' => array('028508646', '0879262023'), 'count' => 2),
    array('string' => '028529655 / 0899770085', 'numbers' => array('028529655', '0899770085'), 'count' => 2),
    array('string' => '0888555683  .  0895949855', 'numbers' => array('0888555683', '0895949855'), 'count' => 2),
    array('string' => '+447035946550', 'numbers' => array('+447035946550'), 'count' => 0),
    array('string' => '+447035946550; +447031905713', 'numbers' => array('+447031905713', '+447035946550'), 'count' => 0),
    array('string' => '0878/408120, 0898/262403; 0878/408228', 'numbers' => array('0878408120', '0878408228', '0898262403'), 'count' => 3),
    array('string' => '0886859390 ???????? 0631/45968', 'numbers' => array('063145968', '0886859390'), 'count' => 2),
    array('string' => '+ 359 887 510 133', 'numbers' => array('0887510133'), 'count' => 1),
    array('string' => '082/24-98-02  GSM:0888019934', 'numbers' => array('082249802', '0888019934'), 'count' => 2),
    array('string' => '9500560 / 0889920626', 'numbers' => array('029500560', '0889920626'), 'count' => 2),
    array('string' => '08-9-5-8-0-7-2-2-7', 'numbers' => array('0895807227'), 'count' => 1),
    array('string' => '0887316406 032/622384', 'numbers' => array('032622384', '0887316406'), 'count' => 2),
    array('string' => '850 13 03 / 0888 37 62 44 ', 'numbers' => array('0888376244', '028501303'), 'count' => 2),
    array('string' => '032 940631, 032 990645, 0888 342 338, 0883 302 095', 'numbers' => array('032940631', '032990645', '0883302095', '0888342338'), 'count' => 4),
    array('string' => '02-983 94 18/0885 23 55 31/0885 444 072', 'numbers' => array('029839418', '0885444072', '0885235531'), 'count' => 3),
    array('string' => '4416091', 'numbers' => array('4416091'), 'count' => 0, 'strict' => false),
    array('string' => '062/28530; 089/9457440', 'numbers' => array('06228530', '0899457440'), 'count' => 2),
    array('string' => ' +359/ 2/930 55 94, 930  55 95, 930  55 96, 930  55 97, 930  55 98 GSM: +359/ 88/894 32 55', 'numbers' => array('029305594', '029305595', '029305596', '029305597', '029305598', '0888943255'), 'count' => 6),
    array('string' => ' +359/ 2/ 962 59 79, 0800/ 14 678 - безплатен тел.', 'numbers' => array('029625979', '080014678'), 'count' => 2),
    array('string' => ' +359/ 42/602 013, +359/ 89/ 983 99 63', 'numbers' => array('042602013', '0899839963'), 'count' => 2),
    array('string' => ' +359/ 2/462 71 77, 462 71 78; GSM:  +359/ 87/868 47 74', 'numbers' => array('024627177', '024627178', '0878684774'), 'count' => 3),
    array('string' => ' +359/ 34/448 330 GSM: +359/ 88/632 06 20', 'numbers' => array('034448330', '0886320620'), 'count' => 2),
    array('string' => '034/440132, 0351/10131, 10130', 'numbers' => array('034440132', '035110130', '035110131'), 'count' => 3),
    array('string' => ' +359/ 56/803 035, 294 111, 473 011', 'numbers' => array('056294111', '056473011', '056803035'), 'count' => 3),
    array('string' => '+359/ 2/ 975 19 82', 'numbers' => array('029751982'), 'count' => 1),
    array('string' => ' +359/ 3123/25 74 gsm: +359/ 88/834 75 03', 'numbers' => array('031232574', '0888347503'), 'count' => 2),
    array('string' => 'Globul: 0895 472 761, Mtel: 0888 250 177, Vivacom: 0878 553 144', 'numbers' => array('0878553144', '0888250177', '0895472761'), 'count' => 3),
    array('string' => '041489280', 'numbers' => array('041489280'), 'count' => 1),
    array('string' => '083872720', 'numbers' => array('083872720'), 'count' => 1),
    array('string' => '087872720, 088872720, 089872720', 'numbers' => array('0887872720', '0888872720', '0889872720'), 'count' => 3),
    array('string' => 'Коста Лулчев  15Телефон: +359/ 2/973 29 96', 'numbers' => array('029732996'), 'count' => 1),
    array('string' => ' +359/ 2/335 409, +359/ 727/27 32, +359/ 94/ 4 10 17', 'numbers' => array('02335409', '07272732', '09441017'), 'count' => 3),
    array('string' => '090 363 043, 090363055', 'numbers' => array('090363043', '090363055'), 'count' => 2),
    array('string' => '0988819970, 098/7459970', 'numbers' => array('0987459970', '0988819970'), 'count' => 2),
    array('string' => '', 'numbers' => array(), 'count' => 0),
  );

  foreach ($phones as $phone)
  {
    $count = null;
    $numbers = IceStatic::extractPhoneNumbers($phone['string'], isset($phone['strict']) ? $phone['strict'] : true, $count);

    $t->is($numbers, $phone['numbers'], 'Checking '. $phone['string']);
    $t->is($count, $phone['count']);
  }

$t->diag('::formatPhoneNumber()');

  $phones = array(
    array('number' => '0886180177', 'string' => '0886 180 177'),
    array('number' => '0896180177', 'string' => '0896 180 177'),
    array('number' => '0988819970', 'string' => '0988 819 970'),
  );

  foreach ($phones as $phone)
  {
    $string = IceStatic::formatPhoneNumber($phone['number']);
    $t->is($string, $phone['string'], 'Checking '. $phone['number']);
  }

$t->diag('::exractSearchEngineKeyword()');

  $urls = array(
    'http://beta.bezplatno.net/tag-mops.html' => 'mops',
    'http://beta.bezplatno.net/search.html?keyword=mops&x=0&y=0' => 'mops',
    'http://www.google.bg/webhp?hl=bg&source=hp&q=%D0%BA%D1%83%D1%87%D0%B5%D1%82%D0%B0&aq=f&aqi=g10&aql=&oq=&gs_rfai=&fp=dfb1036b26b07139#hl' => 'кучета',
    'http://www.bing.com/search?q=javascript+date+to+timestamp&src=IE-SearchBox&FORM=IE8SRC' => 'javascript date to timestamp',
    'http://www.google.de/search?q=apache+restart&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:de:official&client=firefox-a' => 'apache restart',
    'http://us.yhs.search.yahoo.com/avg/search?fr=yhs-avg-chrome&type=yahoo_avg_hs2-tb-web_chrome_us&p=concatenation+in+mysql' => 'concatenation in mysql',
    'http://beta.bezplatno.net/search.html?keyword=%D0%BC%D0%BE%D0%BF%D1%81&x=0&y=0' => 'мопс',
    'http://beta.bezplatno.net/search.html?keyword=Изгодно+под+наем+без+посредник&x=0&y=0' => 'Изгодно под наем без посредник',
    'http://www.google.de/search?q=Медицински+център+І+-+Костинброд+ЕООД' => 'Медицински център І - Костинброд ЕООД'
  );

  foreach ($urls as $url => $keyword)
  {
    $found_keyword = IceStatic::exractSearchEngineKeyword($url);
    $t->is($found_keyword, $keyword);
  }

$t->diag('::crypt(), ::decrypt()');

  $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin at libero mi, quis luctus massa.";
  $encrypted = 'e740b972ae170188b22381983cb47362107e22646744dd04138e8383df549152a3d6447088113566760c37bbd6ea9bb122ffd94ffae8467ca53fd21b5162dd95ca22e175b72b7b8b77b9bb3ab33c98f9188ecd276bb704a736dff1d82acde115';

  $t->is(IceStatic::crypt($text, 'S3cr3T'), $encrypted);
  $t->is(IceStatic::decrypt($encrypted, 'S3cr3T'), $text);

  $text = '0895423938';
  $encrypted = '51374cabf9701fd0ab3bf684b3a083f4';

  $t->is(IceStatic::crypt($text, 'S3cr3T'), $encrypted);
  $t->is(IceStatic::decrypt($encrypted, 'S3cr3T'), $text);

$t->diag('::cleanTitle()');

   $titles = array(
     0 => array(
       'dirty' => '- =. , /=- , . /=, . /, /- ```БЕЗПЛАТНА``ДОСТАВКА``НА``МАТРАЦИ```9201498==============================================',
       'clean' => 'БЕЗПЛАТНА ДОСТАВКА НА МАТРАЦИ 9201498'
     ),
     array(
       'dirty' => 'A, a. aA, . A. `````МАТРАЦИ``НА``ПРОМОЦИЯ``9201498`````````````````````',
       'clean' => 'A, a. aA, . A. МАТРАЦИ НА ПРОМОЦИЯ 9201498'
     ),
     array(
       'dirty' => '- =- =- , . , . , . , //////БЕЗПЛАТНА``ДОСТАВКА``НА``МАТРАЦИ```9201498====================================================',
       'clean' => 'БЕЗПЛАТНА ДОСТАВКА НА МАТРАЦИ 9201498'
     ),
     array(
       'dirty' => '``````МАТРАЦИ```НА``ТОП```ЦЕНИ```9201498`````````````````````````````',
       'clean' => 'МАТРАЦИ НА ТОП ЦЕНИ 9201498'
     ),
     array(
       'dirty' => 'ХАМАЛИ СТУДЕНТИ ПРЕМЕСТВАНЕ =-=--0893252042',
       'clean' => 'ХАМАЛИ СТУДЕНТИ ПРЕМЕСТВАНЕ - -0893252042'
     ),
     array(
       'dirty' => 'Пренася и транспортира пиана - 927- 99- 76',
       'clean' => 'Пренася и транспортира пиана - 927- 99- 76'
     ),
     array(
       'dirty' => '= , = - =БОРСА==ЗА==МАТРАЦИ==9201498=========================================',
       'clean' => 'БОРСА ЗА МАТРАЦИ 9201498',
     ),
     array(
       'dirty' => 'Електромотори 7.5 (фл), 13, 18.5, 37, 55 kw 1500об.',
       'clean' => 'Електромотори 7.5 (фл), 13, 18.5, 37, 55 kw 1500об'
     ),
     array(
       'dirty' => 'Трансформатор 1600/20/04',
       'clean' => 'Трансформатор 1600/20/04'
     ),
     array(
       'dirty' => '',
       'clean' => ''
     )
   );

   foreach ($titles as $title)
   {
     $t->is(IceStatic::cleanTitle($title['dirty']), $title['clean']);
   }

$t->diag('::truncateText()');

  $text = 'Renault Scenic Conquest - енергичната кола';
  $t->is(IceStatic::truncateText($text, 255, '', false), 'Renault Scenic Conquest - енергичната кола');
  $t->is(IceStatic::truncateText($text, 30, '', false), 'Renault Scenic Conquest - енер');

$t->diag('::getUniquePassword()');

  $password = IceStatic::getUniquePassword(6, 1, '23456789');
  $t->is(6, strlen($password));
  $t->like($password, '/^[2-9]+$/i');

  $password = IceStatic::getUniquePassword(8, 2);
  $t->is(8, strlen($password));
  $t->like($password, '/^[\w\W]+$/');

  $password = IceStatic::getUniquePassword(13, 4);
  $t->is(13, strlen($password));
  $t->like($password, '/^[\w\d]+$/i');

$t->diag('::reduceText()');

  $text = 'Редактирай Текста';
  $t->is(IceStatic::reduceText($text, 16, '[...]'), 'Редак[...]Текста');

  $text = 'Редактирай Текста';
  $t->is(IceStatic::reduceText($text, 1, '[...]'), null);

  $text = 'Редактирай Текстът';
  $t->is(IceStatic::reduceText($text, 16, '[...]'), 'Редак[...]екстът');

$t->diag('::floatval()');

  $t->is(IceStatic::floatval('8,500лв.'), 8500);
  $t->is(IceStatic::floatval('1 179.00'), 1179.00);
  $t->is(IceStatic::floatval('1,000.76'), 1000.76);
  $t->is(IceStatic::floatval('490.85 лв.'), 490.85);
  $t->is(IceStatic::floatval('179,99 лв'), 179.99);
  $t->is(IceStatic::floatval('17,999 лв'), 17999);
  $t->is(IceStatic::floatval('1 970,35 лв с ддс'), 1970.35);
  $t->is(IceStatic::floatval('962...BGN'), 962.00);
  $t->is(IceStatic::floatval('962.99...BGN'), 962.99);
  $t->is(IceStatic::floatval('цена: 203.40лв. с ДДС '), 203.40);
  $t->is(IceStatic::floatval('179,00 лв.'), 179.00);
  $t->is(IceStatic::floatval('179 00'), 179.00);
  $t->is(IceStatic::floatval('7,8'), 7.8);
  $t->is(IceStatic::floatval('2.629,90лв'), 2629.90);
  $t->is(IceStatic::floatval(32.388), 32.39);
  $t->is(IceStatic::floatval('148 лв., 24 месеца гаранция'), '148.00');
  $t->is(IceStatic::floatval('70.8000030517578'), '70.80');
  $t->is(IceStatic::floatval(70.8000030517578), '70.80');
