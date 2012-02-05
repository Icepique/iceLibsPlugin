<?php

require_once dirname(__FILE__).'/../../../../test/bootstrap/unit.php';
require_once dirname(__FILE__).'/../../lib/IceFunctions.class.php';

$t = new lime_test(27, new lime_output_color());

$t->diag('array_filter_recursive()');

  $array = array('One', 'Two' => array('Four', null, '', 0), '', 'Three', null, 0);
  $clean = IceFunctions::array_filter_recursive($array);
  $t->is($clean, array(0 => 'One', 'Two' => array('Four'), 2 => 'Three'));

$t->diag('array_power_set()');

  $array = array('Apples', 'Oranges', 'Grapes');
  $set = IceFunctions::array_power_set($array);

  $t->is($set[0], array(), 'The power set includes the empty set');
  $t->is($set[3], array('Oranges', 'Apples'));

$t->diag('array_vertical_sort()');

  /**
   * A E H K N
   * C F I L O
   * D G J M P
   */
  $array = array('A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
  $sorted = IceFunctions::array_vertical_sort($array, 5, false);
  $t->is($sorted, array('A', 'E', 'H', 'K', 'N', 'C', 'F', 'I', 'L', 'O', 'D', 'G', 'J', 'M', 'P'), 'Testing with 5 columns');

  /**
   * A E I M
   * B F J
   * C G K
   * D H L
   */
  $array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');
  $sorted = IceFunctions::array_vertical_sort($array, 4, false);
  $t->is($sorted, array('A', 'E', 'I', 'M', 'B', 'F', 'J', null, 'C', 'G', 'K', null, 'D', 'H', 'L', null), 'Testing with 4 columns and 13 elements');

  /**
   * A D F H J L N P
   * C E G I K M O
   */
  $array = array('A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
  $sorted = IceFunctions::array_vertical_sort($array, 8, false);
  $t->is($sorted, array('A', 'D', 'F', 'H', 'J', 'L', 'N', 'P', 'C', 'E', 'G', 'I', 'K', 'M', 'O', null), 'Testing with 8 columns');

  /**
   * A D F H J L N P
   * C E G I K M O
   */
  $array = array(1 => 'A', 4 => 'C', 8 => 'D', 6 => 'E', 5 => 'F', 3 => 'G', 2 => 'H', 7 => 'I', 11 => 'J', 10 => 'K', 9 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 16 => 'P');
  $sorted = IceFunctions::array_vertical_sort($array, 8, true);
  $t->is($sorted, array(1 => 'A', 8 => 'D', 5 => 'F', 2 => 'H', 11 => 'J', 9 => 'L', 13 => 'N', 16 => 'P', 4 => 'C', 6 => 'E', 3 => 'G', 7 => 'I', 10 => 'K', 12 => 'M', 14 => 'O', null), 'The function should not reset the keys of the array');

$t->diag('number_format()');

  $formatted = IceFunctions::number_format(11101.01, 2, 'bg_BG');
  $t->is($formatted, '11 101,01', 'Formatting for Bulgarian');

  $formatted = IceFunctions::number_format(11101.01, 2, 'en_US');
  $t->is($formatted, '11,101.01', 'Formatting for English');

$t->diag('levenstein()');

  $text1 = 'изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $text2 = 'изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $t->is(IceFunctions::levenshtein($text1, $text2), 0);

  $text1 = 'изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $text2 = 'изкупува коли за скрап,издава документи за KAT и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $t->is(IceFunctions::levenshtein($text1, $text2), 3);

  $text1 = 'изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $text2 = 'изкупува коли за скрап,издава документи за KAT и данъчно,плаща на място,безплатен транспорт,тел 0895/423938';
  $t->is(IceFunctions::levenshtein($text1, $text2), 10);

  $text1 = '+ + изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861 + +';
  $text2 = 'изкупува коли за скрап,издава документи за КАТ и данъчно,плаща на място,безплатен транспорт,тел 0886/693861';
  $t->is(IceFunctions::levenshtein($text1, $text2), 8);

  $text1 = 'Formatting for English';
  $text2 = 'English for Formatting';
  $t->is(IceFunctions::levenshtein($text1, $text2), 18);

$t->diag('array_flatten()');

  $array = array(
    'foo' => 'foo',
    'bar' => array(
      'baz' => 'baz',
      'candy' => 'candy',
      'vegetable' => array(
        'carrot' => 'carrot',
      )
    ),
    'vegetable' => array(
      'carrot' => 'carrot2',
    ),
    'fruits' => 'fruits'
  );

  $t->is(
    IceFunctions::array_flatten($array, $keep_keys = true),
    array(
      'foo' => 'foo', 'baz' => 'baz', 'candy' => 'candy',
      'carrot' => 'carrot2', 'fruits' => 'fruits'
    )
  );

  $array = array(
    'mobile' => array('0895423938', '0895423939', '0895423940'),
    'landline' => array('02423938', '02423939', '02423940'),
    '0895999999'
  );

  $t->is(
    IceFunctions::array_flatten($array, $keep_keys = false),
    array('0895423938', '0895423939', '0895423940', '02423938', '02423939', '02423940', '0895999999')
  );

$t->diag('array_value_recursive()');

  $array = array(
    'foo' => 'foo',
    'bar' => array(
      'baz' => 'baz',
      'candy' => 'candy',
      'vegetable' => array(
        'carrot' => 'carrot',
      )
    ),
    'vegetable' => array(
      'carrot' => 'carrot2',
    ),
    'fruits' => 'fruits'
  );

  // $t->is(IceFunctions::array_value_recursive('carrot', $array), array('carrot', 'carrot2'));
  // $t->is(IceFunctions::array_value_recursive('apple', $array), null);
  // $t->is(IceFunctions::array_value_recursive('baz', $array), 'baz');

$t->diag('array_unique_recursive()');

  $array = array (
    'filters' =>
      array (
        'vehicle_type_id' => 1,
        'vehicle_make_id' => -1,
        'vehicle_condition_id' => array(0 => 1, 1 => 16),
        'condition' => array(0 => 'used', 1 => 'import'),
        'vehicle_type' => 'car',
        'vehicle_make' => '-1',
        'total' => '56',
        'fuel_type_id' => 1,
        'seller_type_id' => array(0 => 1, 1 => 3),
        'price' => array('min' => '0', 'max' => '448740'),
        'fuel_type' => 'gasoline',
        'year' => array('min' => '0'),
        'mileage' => array('max' => '0'),
        'geo_region_id' => '0',
        'seller_type' => array(0 => 'private', 1 => 'dealership'),
      ),
    'active' => true,
    'sortby' => 'date',
    'sort' => 'date',
    'order' => 'DESC',
    'limits' => array(1 => 15),
  );

  $unique = array (
    'filters' =>
      array (
        'vehicle_type_id' => 1,
        'vehicle_make_id' => -1,
        'vehicle_condition_id' => array(0 => 1, 1 => 16),
        'condition' => array(0 => 'used', 1 => 'import'),
        'vehicle_type' => 'car',
        'vehicle_make' => '-1',
        'total' => '56',
        'seller_type_id' => array(0 => 1, 1 => 3),
        'price' => array('min' => '0', 'max' => '448740'),
        'fuel_type' => 'gasoline',
        'year' => array('min' => '0'),
        'mileage' => array('max' => '0'),
        'geo_region_id' => '0',
        'seller_type' => array(0 => 'private', 1 => 'dealership'),
      ),
    'active' => true,
    'sortby' => 'date',
    'order' => 'DESC',
    'limits' => array(1 => 15),
  );

  $t->is(IceFunctions::array_unique_recursive($array), $unique);

$t->diag('udihash()');

  $ids = range(1, 10);
  $hashes = array(1 => 'cJio3', 'EdRc6', 'qxAQ9', 'TGtEC', '5ac2F', 'huKqI', 'KE3eL', 'wXmSO', 'YrVGR', 'BBE4U');

  foreach($ids as $id)
  {
    $t->is(IceFunctions::udihash($id, 5), $hashes[$id]);
  }
