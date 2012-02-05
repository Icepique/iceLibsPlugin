<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/Utf8.class.php';

$t = new lime_test(64, new lime_output_color());

$t->diag('::cyrillic2latin()');

  $text = 'аutоhоp';
  $latin = Utf8::cyrillic2latin($text, 'php');
  $t->is($latin, 'autohop', 'Checking with mixed character encoded string');

  $text = 'аутохоп';
  $latin = Utf8::cyrillic2latin($text, 'php');
  $t->is($latin, 'autohop', 'Checking with fully cirillic string');

$t->diag('::translit()');

  $text = 'НА ЧАСТИ';
  $t->is(Utf8::translit($text), 'na qasti, na chasti, na `asti, na 4asti');

  $text = 'Бетон-помпа CIFA K41L XRZ, монтирана на шаси: MERCEDES BENZ 3241K';
  $t->is(Utf8::translit($text), 'beton-pompa cifa k41l xrz, montirana na wasi: mercedes benz 3241k, beton-pompa cifa k41l xrz, montirana na shasi: mercedes benz 3241k, beton-pompa cifa k41l xrz, montirana na [asi: mercedes benz 3241k, beton-pompa cifa k41l xrz, montirana na 6asi: mercedes benz 3241k');

$t->diag('::slugify()');

  $strings = array(
    array(
      'input' => 'Infiniti с ръчен часовник Limited Edition BR03-92 Instrument Phantom Infiniti',
      'output' => 'Infiniti-с-ръчен-часовник-Limited-Edition-BR03-92-Instrument-Phantom-Infiniti',
      'lower' => 'infiniti-с-ръчен-часовник-limited-edition-br03-92-instrument-phantom-infiniti',
      'translit' => 'infiniti-s-rachen-chasovnik-limited-edition-br03-92-instrument-phantom-infiniti'
    ),
    // Test for the transliteration
    array(
      'input' => 'Мистериозен купувач поръча 10 бройки Aston Martin One-77', 
      'output' => 'Мистериозен-купувач-поръча-10-бройки-Aston-Martin-One-77',
      'lower' => 'мистериозен-купувач-поръча-10-бройки-aston-martin-one-77',
      'translit' => 'misteriozen-kupuvach-poracha-10-broyki-aston-martin-one-77'
    ),
    array(
      'input' => '1998 Mercedes Vito 110 DIESEL',
      'output' => '1998-Mercedes-Vito-110-DIESEL',
      'lower' => '1998-mercedes-vito-110-diesel',
      'translit' => '1998-mercedes-vito-110-diesel'
    ),
    // Test for the "dot" characted
    array(
      'input' => '1999 Opel Zafira 16',
      'output' => '1999-Opel-Zafira-16',
      'lower' => '1999-opel-zafira-16',
      'translit' => '1999-opel-zafira-16'
    ),
    // Test for /
    array(
      'input' => 'Кабрио/Roadster',
      'output' => 'Кабрио-Roadster',
      'lower' => 'кабрио-roadster',
      'translit' => 'kabrio-roadster'
    ),
    array(
      'input' => 'Преводач с ТУРСКИ език- София/Yemini Türkçe Tercüman- Sofya/Turkish Translator- Sofia',
      'output' => 'Преводач-с-ТУРСКИ-език-София-Yemini-Turkce-Tercuman-Sofya-Turkish-Translator-Sofia',
      'lower' => 'преводач-с-турски-език-софия-yemini-turkce-tercuman-sofya-turkish-translator-sofia',
      'translit' => 'prevodach-s-turski-ezik-sofiya-yemini-turkce-tercuman-sofya-turkish-translator-sofia'
    ),
    array(
      'input' => 'Άλλο λατρευτός μαϊμού μωρό Capuchin (Drek) για την πώληση',
      'output' => 'Hallo-latreythos-maimohu-morho-Capuchin-Drek-gia-ten-pholese',
      'lower' => 'hallo-latreythos-maimohu-morho-capuchin-drek-gia-ten-pholese',
      'translit' => 'capuchin-drek'
    ),
    array(
      'input' => 'Киногид.тв - Фильмы бесплатно. Актёры кино',
      'output' => 'Киногидтв-Фильмы-бесплатно-Актры-кино',
      'lower' => 'киногидтв-фильмы-бесплатно-актры-кино',
      'translit' => 'kinogidtv-filym-besplatno-aktr-kino'
    ),
    array(
      'input' => 'Английски и немски езикЦПО ЗЕНИТ-94  гр. ВАРНА  Представител за гр. Бургас  АЛФА ЦЕНТЪР- БУРГАС ул. „Цар СимеонІ” №11 Тел. 056/823201; 0878 813201; 0888 435603  ПРОГРАМА” АЗ МОГА” БЕЗПЛАТНО ОБУЧЕНИЕ ЗА ЗАЕТИ НА ТРУДОВ ДОГОВОР ЧРЕЗ ПРЕДОСТАВЯНЕ НА ВАУЧЕРИ',
      'output' => 'Английски-и-немски-езикЦПО-ЗЕНИТ-94-гр-ВАРНА-Представител-за-гр-Бургас-АЛФА-ЦЕНТЪР-БУРГАС-ул-Цар-Симеон-11-Тел-056-823201-0878-813201-0888-435603-ПРОГРАМА-АЗ-МОГА-БЕЗПЛАТНО-ОБУЧЕНИЕ-ЗА-ЗАЕТИ-НА-ТРУДОВ-ДОГОВОР-ЧРЕЗ-ПРЕДОСТАВЯНЕ-НА-ВАУЧЕРИ',
      'lower' => 'английски-и-немски-езикцпо-зенит-94-гр-варна-представител-за-гр-бургас-алфа-център-бургас-ул-цар-симеон-11-тел-056-823201-0878-813201-0888-435603-програма-аз-мога-безплатно-обучение-за-заети-на-трудов-договор-чрез-предоставяне-на-ваучери',
      'translit' => 'angliyski-i-nemski-eziktspo-zenit-94-gr-varna-predstavitel-za-gr-burgas-alfa-tsentar-burgas-ul-tsar-simeon-No11-tel-056-823201-0878-813201-0888-435603-programa-az-moga-bezplatno-obuchenie-za-zaeti-na-trudov-dogovor-chrez-predostavyane-na-vaucher'
    ),
    array(
      'input' => 'Бързи преводи и легализация от/на ВСИЧКИ езици- Yeminli Tercüme Bürosu',
      'output' => 'Бързи-преводи-и-легализация-от-на-ВСИЧКИ-езици-Yeminli-Tercume-Burosu',
      'lower' => 'бързи-преводи-и-легализация-от-на-всички-езици-yeminli-tercume-burosu',
      'translit' => 'barzi-prevodi-i-legalizatsiya-ot-na-vsichki-ezitsi-yeminli-tercume-burosu'
    ),
    array(
      'input' => 'Медицински център І - Костинброд ЕООД',
      'output' => 'Медицински-център-Костинброд-ЕООД',
      'lower' => 'медицински-център-костинброд-еоод',
      'translit' => 'meditsinski-tsentar-kostinbrod-eood'
    ),
    array(
      'input' => 'Изгодно под наем без посредник тел 0878359699',
      'output' => 'Изгодно-под-наем-без-посредник-тел-0878359699',
      'lower' => 'изгодно-под-наем-без-посредник-тел-0878359699',
      'translit' => 'izgodno-pod-naem-bez-posrednik-tel-0878359699'
    ),
    array(
      'input' => 'Склад на едро на марката Минтекс за хавлии, халати, детски халати, комплект халати, хавлиени комплекти, одеала pamuk/akril, плажни кърпи, комплект спално бельо ранфорс, хавлиени чехли за баня, кърпи, мъжки и дамски халати за баня, домашен тексил. . . При',
      'output' => 'Склад-на-едро-на-марката-Минтекс-за-хавлии-халати-детски-халати-комплект-халати-хавлиени-комплекти-одеала-pamuk-akril-плажни-кърпи-комплект-спално-бельо-ранфорс-хавлиени-чехли-за-баня-кърпи-мъжки-и-дамски-халати-за-баня-домашен-тексил-При',
      'lower' => 'склад-на-едро-на-марката-минтекс-за-хавлии-халати-детски-халати-комплект-халати-хавлиени-комплекти-одеала-pamuk-akril-плажни-кърпи-комплект-спално-бельо-ранфорс-хавлиени-чехли-за-баня-кърпи-мъжки-и-дамски-халати-за-баня-домашен-тексил-при',
      'translit' => 'sklad-na-edro-na-markata-minteks-za-havlii-halati-detski-halati-komplekt-halati-havlieni-komplekti-odeala-pamuk-akril-plazhni-karpi-komplekt-spalno-belyo-ranfors-havlieni-chehli-za-banya-karpi-mazhki-i-damski-halati-za-banya-domashen-teksil-pri'
    ),
    array(
      'input' => '„Вълците“ се готвят за щурм',
      'output' => 'Вълците-се-готвят-за-щурм',
      'lower' => 'вълците-се-готвят-за-щурм',
      'translit' => 'valtsite-se-gotvyat-za-shturm'
    ),
    array(
      'input' => 'Съобщения за Медиите',
      'output' => 'Съобщения-за-Медиите',
      'lower' => 'съобщения-за-медиите',
      'translit' => 'saobshteniya-za-mediite'
    ),
    array(
      'input' => 'ns:second_key=Toto',
      'output' => 'ns-second_key-Toto',
      'lower' => 'ns-second_key-toto',
      'translit' => 'ns-second_key-toto'
    ),
    array(
      'input' => 'ИТАЛИАНСКИ ЕЗИК І – ІІІ НИВО. ВИСОКО КВАЛИФИЦИРАНИ ПРЕПОДАВАТЕЛИ - ПЛОВДИВ	',
      'output' => 'ИТАЛИАНСКИ-ЕЗИК-НИВО-ВИСОКО-КВАЛИФИЦИРАНИ-ПРЕПОДАВАТЕЛИ-ПЛОВДИВ',
      'lower' => 'италиански-език-ниво-високо-квалифицирани-преподаватели-пловдив',
      'translit' => 'italianski-ezik-nivo-visoko-kvalifitsirani-prepodavateli-plovdiv'
    ),
    array(
      'input' => '',
      'output' => '',
      'lower' => '',
      'translit' => ''
    )
  );

  foreach ($strings as $string)
  {
    $slug = Utf8::slugify($string['input']);
    $t->is($slug, $string['output'], 'Checking string '. $string['input']);

    $slug = Utf8::slugify($string['input'], '-', true, false);
    $t->is($slug, $string['lower'], 'Checking string '. $string['input']);

    $slug = Utf8::slugify($string['input'], '-', true, true);
    $t->is($slug, $string['translit'], 'Checking string '. $string['input']);
  }

  $slug = Utf8::slugify('????', '-', true, true, 'default');
  $t->is($slug, 'default', 'Checking the default parameter');

$t->diag('::excerpt()');

  $texts = array(
    array(
      'input' => '***HAPPY PUPPY*** предлага ПОМЕРАНИ и МИНИ ШПИЦОВЕ - продават се кученцата от СНИМКИТЕ! От 45 дни до 2 месеца, мъжки и женски, бели, крем и златисти, ваксинирани, обезпаразитени, с европейски паспорт и микрочип!
 Видео и още снимки можете да видите в нашия  фейсбук:
 www.facebook.com/pages/Happy-Puppy/127491390660019
  Кученцaта могат да се видят в София, по всяко време на деня! Повече информация можете да получите на нашите телефони; 0899207212 и 024709711

 www.dogshop-bg.com',
      'words' => 'София',
      'output' => '...шия фейсбук: www.facebook.com/pages/Happy-Puppy/127491390660019  Кученцaта могат да се видят в София, по всяко време на деня! Повече информация можете да получите на нашите телефони; 0899207212 и 0...'
    ),
    array(
      'input' => '***HAPPY PUPPY*** предлага ПОМЕРАНИ и МИНИ ШПИЦОВЕ - продават се кученцата от СНИМКИТЕ! От 45 дни до 2 месеца, мъжки и женски, бели, крем и златисти, ваксинирани, обезпаразитени, с европейски паспорт и микрочип!
 Видео и още снимки можете да видите в нашия  фейсбук:
 www.facebook.com/pages/Happy-Puppy/127491390660019
  Кученцaта могат да се видят в София, по всяко време на деня! Повече информация можете да получите на нашите телефони; 0899207212 и 024709711

 www.dogshop-bg.com',
      'words' => 'померан София',
      'output' => '...шия фейсбук: www.facebook.com/pages/Happy-Puppy/127491390660019  Кученцaта могат да се видят в София, по всяко време на деня! Повече информация можете да получите на нашите телефони; 0899207212 и 0...'
    ),
    array(
      'input' => 'ПРОДАВАТ СЕ КУЧЕТАТА ОТ СНИМКИТЕ 1 МЪЖКO И 1 ЖЕНСКО /РОДЕНИ НА 27.06.2011 Г ./ИСТИНСКИ МОЩНИ ,ТРУПНИ СТАРОНЕМСКИ ОВЧАРКИ.ЗА ХОРА,КОИТО РАЗБИРАТ ,ОБЕСНЕНИЯТА СА ПОВЕЧЕ ОТ ИЗЛИШНИ!!!НА СНИМКИТЕ СА НА 25 ДЕНА И ТЕЖАТ ПО 3.7 КГ.ВЪЗМОЖНО Е ПРЕДВАРИТЕЛНО ЗАПАЗВАНЕ НА КУЧЕНЦЕ .ДОСТАВКА ДО ВСЯКО КЪТЧЕ НА СТРАНАТА В ПОСОЧЕН ОТ ВАС ДЕН,ЧАС И АДРЕС!!!',
      'words' => 'ОВЧАРКИ',
      'output' => '...ТАТА ОТ СНИМКИТЕ 1 МЪЖКO И 1 ЖЕНСКО /РОДЕНИ НА 27.06.2011 Г ./ИСТИНСКИ МОЩНИ ,ТРУПНИ СТАРОНЕМСКИ ОВЧАРКИ.ЗА ХОРА,КОИТО РАЗБИРАТ ,ОБЕСНЕНИЯТА СА ПОВЕЧЕ ОТ ИЗЛИШНИ!!!НА СНИМКИТЕ СА НА 25 ДЕНА И ТЕЖАТ ПО...'
    ),
    array(
      'input' => 'Продавам Iphone 3gs Black в отлично състояние! Купен е от САЩ, Unlocked(отключен е и работи с всяка SIM карта) , няма договор към никой оператор. Телефона е и jailbreak- нат, т. е може да си сваляте хиляди application- и на телефона от 3- те портала на телефона- Appstore, Cydia, Installous. Като цяло това не е просто телефон, той е няколко неща в едно- телефон, апарат, Ipod, конзола за игри и джобен компютър за сърфиране в интернет.   В цената от 500 лв включвам: Телефон с чисто нов скрийнпротектор + Apple Cloth черен, за да си прибирате телефона на сигурно място, където няма да се издраска или намокри + USB Charger- зарядно което става и за компютър и за контакт със специална приставка, която я няма в България + Car charger- за зареждане в колата + чисто нови неизползвани слушалки Nokia = 500 лв.   Телефонът функционира както когато е бил купен! Ето и линк с характеристиките на телефона: http: //www. mobilebulgaria. com/mobiles/1841/apple- iphone- 3g- s',
      'words' => 'Iphone Ipod',
      'output' => 'Продавам Iphone 3gs Black в отлично състояние! Купен е от САЩ, Unlocked(отключен е и работи с всяка SIM карта) , няма договор към никой оператор. Телефона е и jailbreak- нат, т. е може да си сваляте х...'
    ),
    array(
      'input' => 'Продавам Iphone 3gs Black в отлично състояние! Купен е от САЩ, Unlocked(отключен е и работи с всяка SIM карта) , няма договор към никой оператор. Телефона е и jailbreak- нат, т. е може да си сваляте хиляди application- и на телефона от 3- те портала на телефона- Appstore, Cydia, Installous. Като цяло това не е просто телефон, той е няколко неща в едно- телефон, апарат, Ipod, конзола за игри и джобен компютър за сърфиране в интернет.   В цената от 500 лв включвам: Телефон с чисто нов скрийнпротектор + Apple Cloth черен, за да си прибирате телефона на сигурно място, където няма да се издраска или намокри + USB Charger- зарядно което става и за компютър и за контакт със специална приставка, която я няма в България + Car charger- за зареждане в колата + чисто нови неизползвани слушалки Nokia = 500 лв.   Телефонът функционира както когато е бил купен! Ето и линк с характеристиките на телефона: http: //www. mobilebulgaria. com/mobiles/1841/apple- iphone- 3g- s',
      'words' => 'iPhone Ipod',
      'output' => '...ydia, Installous. Като цяло това не е просто телефон, той е няколко неща в едно- телефон, апарат, Ipod, конзола за игри и джобен компютър за сърфиране в интернет.  В цената от 500 лв включвам: Телефо...'
    )
  );

  foreach ($texts as $i => $text)
  {
    $excerpt = Utf8::excerpt($text['input'], $text['words'], 200);
    $t->is(str_replace(array("\n", '  '), ' ', $excerpt), $text['output'], 'Checking excerpt #'. $i);
  }
