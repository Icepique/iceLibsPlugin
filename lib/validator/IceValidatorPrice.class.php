<?php

class IceValidatorPrice extends sfValidatorBase
{
  /**
   * @param array $options
   * @param array $messages
   *
   * @return void
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->setOption('empty_value', '');

    $this->addOption('min', 0);
    $this->addOption('max', 1000000);
    $this->addOption('integer', false);

    $this->addMessage('invalid', 'The price amount you have specified is not valid');
    $this->addMessage('required', 'The price amount is required');
    $this->addMessage('too low', 'Amount is too low');
    $this->addMessage('too high', 'Amount is too high');
  }

  /**
   * @see sfValidatorBase
   * @param $value
   *
   * @return float
   */
  protected function doClean($value)
  {
    $clean = str_ireplace(array('o', 'о'), '0', (string) $value);
    $price = IceStatic::floatval($clean, 2);

    if ($this->getOption('integer') == true)
    {
      $price = (int) $price;
    }

    if (empty($price))
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $clean));
    }
    else if ($price < $this->getOption('min'))
    {
      throw new sfValidatorError($this, 'too low', array('value' => $clean));
    }
    else if ($price > $this->getOption('max'))
    {
      throw new sfValidatorError($this, 'too high', array('value' => $clean));
    }

    return $price;
  }
}
