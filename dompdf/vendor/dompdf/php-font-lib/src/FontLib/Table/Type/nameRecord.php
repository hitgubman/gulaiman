<?php

namespace FontLib\Table\Type;

use FontLib\Font;
use FontLib\BinaryStream;


class nameRecord extends BinaryStream {
  public $platformID;
  public $platformSpecificID;
  public $languageID;
  public $nameID;
  public $length;
  public $offset;
  public $string;
  public $stringRaw;

  public static $format = array(
    "platformID"         => self::uint16,
    "platformSpecificID" => self::uint16,
    "languageID"         => self::uint16,
    "nameID"             => self::uint16,
    "length"             => self::uint16,
    "offset"             => self::uint16,
  );

  public function map($data) {
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
  }

  public function getUTF8() {
    return $this->string;
  }

  public function getUTF16() {
    return Font::UTF8ToUTF16($this->string);
  }

  function __toString() {
    return $this->string;
  }
}