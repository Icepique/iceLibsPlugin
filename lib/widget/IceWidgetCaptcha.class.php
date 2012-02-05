<?php

class IceWidgetCaptcha extends sfWidgetForm
{
  protected function configure($options = array(), $attributes = array())
  {
    parent::configure($options, $attributes);

    $this->addOption('width');
    $this->addOption('height');
    $this->addOption('background-color');
    $this->addOption('font-size');
    $this->addOption('code-length');
  }

  public function getLabel()
  {
    $url = sfContext::getInstance()->getRouting()->generate('ice_captcha_image', array(), true);

    $params = sprintf(
      'w=%d&h=%d&bc=%s&fs=%d&cl=%d',
      $this->getOption('width'), $this->getOption('height'),
      $this->getOption('background-color'), $this->getOption('font-size'),
      $this->getOption('code-length')
    );

    return sprintf(
      '<a href="#" onClick="return false;" style="text-decoration: none;">
         <img src="%s?r=\' + Math.random() + \'&%s" onClick="this.src=\'%s?r=\' + Math.random() + \'&amp;reload=1&%s\';" border="0" width="%d" height="%d" />
       </a>',

      $url, $params, $url, $params, $this->getOption('width'), $this->getOption('height')
    );
  }

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    $attributes = array_merge($attributes, array(
      'class' => 'captcha',
      'style' => sprintf('float: right; width: %dpx; height: %dpx; text-align: center;', $this->getOption('width') / 1.3, $this->getOption('height') / 1.3)
    ));

    return $this->renderTag('input', array_merge(array('type' => 'text', 'name' => $name, 'autocomplete' => 'off'), $attributes));
  }
}
