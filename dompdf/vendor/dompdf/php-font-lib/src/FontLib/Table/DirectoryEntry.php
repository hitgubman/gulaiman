<?php

namespace FontLib\Table;

use FontLib\TrueType\File;
use FontLib\Font;
use FontLib\BinaryStream;


class DirectoryEntry extends BinaryStream {
  
  protected $font;

  
  protected $font_table;

  public $entryLength = 4;

  public $tag;
  public $checksum;
  public $offset;
  public $length;

  protected $origF;

  
  static function computeChecksum($data) {
    $len = mb_strlen($data, '8bit');
    $mod = $len % 4;

    if ($mod) {
      $data = str_pad($data, $len + (4 - $mod), "\0");
    }

    $table = unpack("N*", $data);
    return array_sum($table);
  }

  function __construct(File $font) {
    $this->font = $font;
    $this->f    = $font->f;
  }

  function parse() {
    $this->tag = $this->font->read(4);
  }

  function open($filename, $mode = self::modeRead) {
    
  }

  function setTable(Table $font_table) {
    $this->font_table = $font_table;
  }

  function encode($entry_offset) {
    Font::d("\n==== $this->tag ====");
    

    $data = $this->font_table;
    $font = $this->font;

    $table_offset = $font->pos();
    $this->offset = $table_offset;
    $table_length = $data->encode();

    $font->seek($table_offset + $table_length);
    $pad = 0;
    $mod = $table_length % 4;
    if ($mod != 0) {
      $pad = 4 - $mod;
      $font->write(str_pad("", $pad, "\0"), $pad);
    }

    $font->seek($table_offset);
    $table_data = $font->read($table_length);

    $font->seek($entry_offset);

    $font->write($this->tag, 4);
    $font->writeUInt32(self::computeChecksum($table_data));
    $font->writeUInt32($table_offset);
    $font->writeUInt32($table_length);

    Font::d("Bytes written = $table_length");

    $font->seek($table_offset + $table_length + $pad);
  }

  
  function getFont() {
    return $this->font;
  }

  function startRead() {
    $this->font->seek($this->offset);
  }

  function endRead() {
    
  }

  function startWrite() {
    $this->font->seek($this->offset);
  }

  function endWrite() {
    
  }
}

