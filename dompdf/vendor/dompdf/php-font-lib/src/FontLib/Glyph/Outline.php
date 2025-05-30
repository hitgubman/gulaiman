<?php

namespace FontLib\Glyph;

use FontLib\Table\Type\glyf;
use FontLib\TrueType\File;
use FontLib\BinaryStream;


class Outline extends BinaryStream {
  
  protected $table;

  protected $offset;
  protected $size;

  
  public $numberOfContours;
  public $xMin;
  public $yMin;
  public $xMax;
  public $yMax;

  
  public $raw;

  
  static function init(glyf $table, $offset, $size, BinaryStream $font) {
    $font->seek($offset);

    if ($size === 0 || $font->readInt16() > -1) {
      
      $glyph = new OutlineSimple($table, $offset, $size);
    }
    else {
      
      $glyph = new OutlineComposite($table, $offset, $size);
    }

    $glyph->parse($font);

    return $glyph;
  }

  
  function getFont() {
    return $this->table->getFont();
  }

  function __construct(glyf $table, $offset = null, $size = null) {
    $this->table  = $table;
    $this->offset = $offset;
    $this->size   = $size;
  }

  function parse(BinaryStream $font) {
    $font->seek($this->offset);

      $this->raw = $font->read($this->size);
  }

  function parseData() {
    $font = $this->getFont();
    $font->seek($this->offset);

    $this->numberOfContours = $font->readInt16();
    $this->xMin             = $font->readFWord();
    $this->yMin             = $font->readFWord();
    $this->xMax             = $font->readFWord();
    $this->yMax             = $font->readFWord();
  }

  function encode() {
    $font = $this->getFont();

    return $font->write($this->raw, mb_strlen((string) $this->raw, '8bit'));
  }

  function getSVGContours() {
    
  }

  function getGlyphIDs() {
    return array();
  }
}

