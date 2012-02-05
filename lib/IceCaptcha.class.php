<?php

class IceCaptcha
{
  /**
   * @var string
   */
  private $securityCode;

  /**
   * @var mixed
   */
  private
    $background_color,
    $fonts,
    $fonts_dir,
    $font_size,
    $font_color,
    $chars,
    $code_length,
    $image_width,
    $image_height;

  function __construct()
  {
    $this->background_color = sfConfig::get('app_ice_captcha_background_color', "FFFFFF");
    $this->fonts            = sfConfig::get('app_ice_captcha_fonts', array("akbar.ttf", "alanden.ttf", "arial.ttf", "brushcut.ttf", "bsurp.ttf", "elecha.ttf", "luggerbu.ttf", "molten.ttf", "rascal.ttf", "scraw.ttf", "wavy.ttf", "whoobub.ttf"));
    $this->fonts_dir        = sfConfig::get('app_ice_captcha_fonts_dir', sfConfig::get('sf_plugins_dir') . '/iceAssetsPlugin/data/fonts/');
    $this->font_size        = sfConfig::get('app_ice_captcha_font_size', "24");
    $this->font_color       = sfConfig::get('app_ice_captcha_font_color', array("252525", "550707", "3526E6", "88531E"));
    $this->chars            = sfConfig::get('app_ice_captcha_chars', "2345689");
    $this->code_length      = sfConfig::get('app_ice_captcha_length', 5);
    $this->image_width      = sfConfig::get('app_ice_captcha_image_width', 200);
    $this->image_height     = sfConfig::get('app_ice_captcha_image_height', 50);
  }

  public function setWidth($v)
  {
    $this->image_width = (int) $v;
  }

  public function setHeight($v)
  {
    $this->image_height = (int) $v;
  }

  public function setBackgroundColor($v)
  {
    $this->background_color = (string) $v;
  }

  public function setFontColor($v)
  {
    $this->font_color = (string) $v;
  }

  public function setFontSize($v)
  {
    $this->font_size = (int) $v;
  }

  public function setCodeLength($v)
  {
    $this->code_length = (int) $v;
  }

  public function generateImage()
  {
    $this->securityCode = $this->simpleRandString($this->code_length, $this->chars);

    $this->img = imagecreatetruecolor($this->image_width, $this->image_height);
    $bc_color = $this->allocateColor($this->img, $this->background_color);
    imagefill($this->img, 0, 0, $bc_color);

    $color = array();
    foreach ($this->font_color as $fcolor)
    {
      $color[] = $this->allocateColor($this->img, $fcolor);
    }

    $fw = imagefontwidth($this->font_size) + $this->image_width / 30;
    $fh = imagefontheight($this->font_size);

    // create a new string with a blank space between each letter so it looks better
    $newstr = "";
    for ($i = 0; $i < strlen($this->securityCode); $i++)
    {
      $newstr .= $this->securityCode[$i] . " ";
    }

    // remove the trailing blank
    $newstr = trim($newstr);

    // center the string
    $x = ($this->image_width * 0.95 - strlen($newstr) * $fw ) / 2;

    // create random lines over text
    $stripe_size_max = $this->image_height / 3;
    for ($i = 0; $i < 15; $i++)
    {
      $x2 = rand(0, $this->image_width);
      $y2 = rand(0, $this->image_height);

      imageline($this->img, $x2, $y2, $x2 + rand(-$stripe_size_max, $stripe_size_max), $y2 + rand(-$stripe_size_max, $stripe_size_max), $color[rand(0, count($color) - 1)]);
    }

    // output each character at a random height and standard horizontal spacing
    for ($i = 0; $i < strlen($newstr); $i++)
    {
      $hz = $fh + ($this->image_height - $fh) / 2 + mt_rand(-$this->image_height / 30, $this->image_height / 30);

      // randomize font size
      $newfont_size = $this->font_size + $this->font_size * (rand(0, 2) / 10);

      imagettftext($this->img, $newfont_size, 0, $x + ($fw * $i), $hz, $color[rand(0, count($color) - 1)], $this->fonts_dir . $this->fonts[rand(0, count($this->fonts) - 1)], $newstr[$i]);
    }

    imagepng($this->img);
    imagedestroy($this->img);
  }

  public function getSecurityCode()
  {
    return $this->securityCode;
  }

  private function simpleRandString($length, $list)
  {
    mt_srand((double) microtime() * 1000000);

    $newstring = "";

    if ($length > 0)
    {
      while (strlen($newstring) < $length)
      {
        $newstring .= $list[mt_rand(0, strlen($list) - 1)];
      }
    }

    return $newstring;
  }

  private function allocateColor($img, $color = "")
  {
    return imagecolorallocate(
      $img, hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2))
    );
  }
}
