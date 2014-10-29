<?php

namespace Chatter\Rot;

/**
 * Also known as Rotten Code.
 */
class RotEncode
{
  protected $count;

  public function __construct($count)
  {
      $this->count = $count;
  }

  public function rot($s)
  {
      $n = $this->count;
      $letters = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
      $n = (int) $n % 26;
      if (!$n) {
        return $s;
      }
      if ($n < 0) {
        $n += 26;
      }
      if ($n == 13) {
        return str_rot13($s);
      }
      $rep = substr($letters, $n * 2) . substr($letters, 0, $n * 2);

      return strtr($s, $letters, $rep);
  }

}
