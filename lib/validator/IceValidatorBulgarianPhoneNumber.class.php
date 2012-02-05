<?php

class IceValidatorBulgarianPhoneNumber extends sfValidatorBase
{
  protected function configure($options = array(), $messages = array())
  {
    $this->addOption('stict', true);
    $this->setOption('empty_value', '');
  }

  /**
   * @see sfValidatorBase
   */
  protected function doClean($value)
  {
    $clean = (string) $value;

    $count = 0;
    $numbers = IceStatic::extractPhoneNumbers($clean, (bool) $this->getOption('strict'), $count);

    if ($count == 0 || empty($numbers))
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $value));
    }

    return $numbers;
  }
}
