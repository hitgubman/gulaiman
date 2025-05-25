<?php

namespace Dompdf;

use FontLib\Exception\FontNotFoundException;
use FontLib\Font;
use FontLib\BinaryStream;

class Cpdf
{
    const PDF_VERSION = '1.7';

    const ACROFORM_SIG_SIGNATURESEXISTS = 0x0001;
    const ACROFORM_SIG_APPENDONLY =       0x0002;

    const ACROFORM_FIELD_BUTTON =   'Btn';
    const ACROFORM_FIELD_TEXT =     'Tx';
    const ACROFORM_FIELD_CHOICE =   'Ch';
    const ACROFORM_FIELD_SIG =      'Sig';

    const ACROFORM_FIELD_READONLY =               0x0001;
    const ACROFORM_FIELD_REQUIRED =               0x0002;

    const ACROFORM_FIELD_TEXT_MULTILINE =         0x1000;
    const ACROFORM_FIELD_TEXT_PASSWORD =          0x2000;
    const ACROFORM_FIELD_TEXT_RICHTEXT =         0x10000;

    const ACROFORM_FIELD_CHOICE_COMBO =          0x20000;
    const ACROFORM_FIELD_CHOICE_EDIT =           0x40000;
    const ACROFORM_FIELD_CHOICE_SORT =           0x80000;
    const ACROFORM_FIELD_CHOICE_MULTISELECT =   0x200000;

    const XOBJECT_SUBTYPE_FORM = 'Form';

    
    public $numObj = 0;

    
    public $objects = [];

    
    public $catalogId;

    
    protected $indirectReferenceId = 0;

    
    protected $embeddedFilesId = 0;

    
    public $acroFormId;

    
    public $signatureMaxLen = 5000;

    
    public $pdfa = false;

    
    public $fonts = [];

    
    public $defaultFont = __DIR__ . '/fonts/Helvetica.afm';

    
    public $currentFont = '';

    
    public $currentBaseFont = '';

    
    public $currentFontNum = 0;

    
    public $currentNode;

    
    public $currentPage;

    
    public $currentContents;

    
    public $numFonts = 0;

    
    private $numStates = 0;

    
    private $gstates = [];

    
    public $currentColor = null;

    
    public $currentStrokeColor = null;

    
    public $fillRule = "nonzero";

    
    public $currentLineStyle = '';

    
    public $currentLineTransparency = ["mode" => "Normal", "opacity" => 1.0];

    
    public $currentFillTransparency = ["mode" => "Normal", "opacity" => 1.0];

    
    public $stateStack = [];

    
    public $nStateStack = 0;

    
    public $numPages = 0;

    
    public $stack = [];

    
    public $nStack = 0;

    
    public $looseObjects = [];

    
    public $addLooseObjects = [];

    
    public $infoObject = 0;

    
    public $numImages = 0;

    
    public $options = ['compression' => true];

    
    public $firstPageId;

    
    public $procsetObjectId;

    
    public $fontFamilies = [];

    
    public $fontcache = '';

    
    public $fontcacheVersion = 6;

    
    public $tmp = '';

    
    public $currentTextState = '';

    
    public $messages = '';

    
    public $arc4 = '';

    
    public $arc4_objnum = 0;

    
    public $fileIdentifier = '';

    
    public $encrypted = false;

    
    public $encryptionKey = '';

    
    public $callback = [];

    
    public $nCallback = 0;

    
    public $destinations = [];

    
    public $checkpoint = '';

    
    public $imagelist = [];

    
    protected $imageAlphaList = [];

    
    protected $imageCache = [];

    
    public $isUnicode = false;

    
    public $javascript = '';

    
    protected $compressionReady = false;

    
    protected $currentPageSize = ["width" => 0, "height" => 0];

    
    protected $stringSubsets = [];

    
    protected static $targetEncoding = 'Windows-1252';

    
    protected $byteRange = array();

    
    protected static $coreFonts = [
        'courier',
        'courier-bold',
        'courier-oblique',
        'courier-boldoblique',
        'helvetica',
        'helvetica-bold',
        'helvetica-oblique',
        'helvetica-boldoblique',
        'times-roman',
        'times-bold',
        'times-italic',
        'times-bolditalic',
        'symbol',
        'zapfdingbats'
    ];

    
    function __construct($pageSize = [0, 0, 612, 792], $isUnicode = false, $fontcache = '', $tmp = '')
    {
        $this->isUnicode = $isUnicode;
        $this->fontcache = rtrim($fontcache, DIRECTORY_SEPARATOR."/\\");
        $this->tmp = ($tmp !== '' ? $tmp : sys_get_temp_dir());
        $this->newDocument($pageSize);

        $this->compressionReady = function_exists('gzcompress');

        if (in_array('Windows-1252', mb_list_encodings())) {
            self::$targetEncoding = 'Windows-1252';
        }

        
        $this->setFontFamily('init');
    }

    public function __destruct()
    {
        foreach ($this->imageCache as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    

    
    protected function o_destination($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'destination', 'info' => []];
                $tmp = '';
                switch ($options['type']) {
                    case 'XYZ':
                    
                    case 'FitR':
                        $tmp = ' ' . $options['p3'] . $tmp;
                    case 'FitH':
                    case 'FitV':
                    case 'FitBH':
                    
                    case 'FitBV':
                        $tmp = ' ' . $options['p1'] . ' ' . $options['p2'] . $tmp;
                    case 'Fit':
                    case 'FitB':
                        $tmp = $options['type'] . $tmp;
                        $this->objects[$id]['info']['string'] = $tmp;
                        $this->objects[$id]['info']['page'] = $options['page'];
                }
                break;

            case 'out':
                $o = &$this->objects[$id];

                $tmp = $o['info'];
                $res = "\n$id 0 obj\n" . '[' . $tmp['page'] . ' 0 R /' . $tmp['string'] . "]\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_viewerPreferences($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'viewerPreferences', 'info' => []];
                break;

            case 'add':
                $o = &$this->objects[$id];

                foreach ($options as $k => $v) {
                    switch ($k) {
                        
                        case 'HideToolbar':
                        case 'HideMenubar':
                        case 'HideWindowUI':
                        case 'FitWindow':
                        case 'CenterWindow':
                        case 'DisplayDocTitle':
                        case 'PickTrayByPDFSize':
                            $o['info'][$k] = (bool)$v;
                            break;

                        
                        case 'NumCopies':
                            $o['info'][$k] = (int)$v;
                            break;

                        
                        case 'ViewArea':
                        case 'ViewClip':
                        case 'PrintClip':
                        case 'PrintArea':
                            $o['info'][$k] = (string)$v;
                            break;

                        
                        case 'NonFullScreenPageMode':
                            if (!in_array($v, ['UseNone', 'UseOutlines', 'UseThumbs', 'UseOC'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'Direction':
                            if (!in_array($v, ['L2R', 'R2L'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'PrintScaling':
                            if (!in_array($v, ['None', 'AppDefault'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'Duplex':
                            if (!in_array($v, ['None', 'Simplex', 'DuplexFlipShortEdge', 'DuplexFlipLongEdge'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        
                        case 'PrintPageRange':
                            
                            foreach ($v as $vK => $vV) {
                                $v[$vK] = (int)$vV;
                            }
                            $o['info'][$k] = array_values($v);
                            break;
                    }
                }
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";

                foreach ($o['info'] as $k => $v) {
                    if (is_string($v)) {
                        $v = '/' . $v;
                    } elseif (is_int($v)) {
                        $v = (string) $v;
                    } elseif (is_bool($v)) {
                        $v = ($v ? 'true' : 'false');
                    } elseif (is_array($v)) {
                        $v = '[' . implode(' ', $v) . ']';
                    }
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_catalog($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'catalog', 'info' => []];
                $this->catalogId = $id;
                break;

            case 'acroform':
            case 'outlines':
            case 'pages':
            case 'openHere':
            case 'names':
                $o['info'][$action] = $options;
                break;

            case 'viewerPreferences':
                if (!isset($o['info']['viewerPreferences'])) {
                    $this->numObj++;
                    $this->o_viewerPreferences($this->numObj, 'new');
                    $o['info']['viewerPreferences'] = $this->numObj;
                }

                $vp = $o['info']['viewerPreferences'];
                $this->o_viewerPreferences($vp, 'add', $options);

                break;

            case 'outputIntents':
                if (!isset($o['info']['outputIntents'])) {
                    $o['info']['outputIntents'] = [];
                }

                $this->numObj++;
                $this->o_contents($this->numObj, 'new');
                $this->objects[$this->numObj]['c'] = $options['iccProfileData'];
                $this->o_contents($this->numObj, 'add', [
                    'N' => $options['colorComponentsCount'],
                ]);

                $o['info']['outputIntents'][] = [
                    'iccProfileName' => $options['iccProfileName'],
                    'destOutputProfile' => $this->numObj,
                ];

                break;

            case 'metadata':
                $this->numObj++;

                $o['info']['metadata'] = $this->numObj;

                $this->o_contents($this->numObj, 'new');
                $this->objects[$this->numObj]['c'] = $options;
                $this->o_contents($this->numObj, 'add', [
                    'Type' => '/Metadata',
                    'Subtype' => '/XML',
                ]);

                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Catalog";

                foreach ($o['info'] as $k => $v) {
                    switch ($k) {
                        case 'outlines':
                            $res .= "\n/Outlines $v 0 R";
                            break;

                        case 'pages':
                            $res .= "\n/Pages $v 0 R";
                            break;

                        case 'viewerPreferences':
                            $res .= "\n/ViewerPreferences $v 0 R";
                            break;

                        case 'openHere':
                            $res .= "\n/OpenAction $v 0 R";
                            break;

                        case 'names':
                            $res .= "\n/Names $v 0 R";
                            break;

                        case 'acroform':
                            $res .= "\n/AcroForm $v 0 R";
                            break;

                        case 'metadata':
                            $res .= "\n/Metadata $v 0 R";
                            break;

                        case 'outputIntents':
                            $res .= "\n/OutputIntents [";
                            foreach ($v as $intent) {
                                $res .= "\n<< /Type /OutputIntent /S /GTS_PDFA1 ";
                                $res .= "/OutputConditionIdentifier (" . $intent['iccProfileName'] . ") /Info (" . $intent['iccProfileName'] . ") ";
                                $res .= "/DestOutputProfile " . $intent['destOutputProfile'] . " 0 R >>";
                            }
                            $res .= "]";
                            break;
                    }
                }

                $res .= " >>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_pages($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'pages', 'info' => []];
                $this->o_catalog($this->catalogId, 'pages', $id);
                break;

            case 'page':
                if (!is_array($options)) {
                    
                    $o['info']['pages'][] = $options;
                } else {
                    
                    
                    if (isset($options['id']) && isset($options['rid']) && isset($options['pos'])) {
                        $i = array_search($options['rid'], $o['info']['pages']);
                        if (isset($o['info']['pages'][$i]) && $o['info']['pages'][$i] == $options['rid']) {

                            
                            
                            switch ($options['pos']) {
                                case 'before':
                                    $k = $i;
                                    break;

                                case 'after':
                                    $k = $i + 1;
                                    break;

                                default:
                                    $k = -1;
                                    break;
                            }

                            if ($k >= 0) {
                                for ($j = count($o['info']['pages']) - 1; $j >= $k; $j--) {
                                    $o['info']['pages'][$j + 1] = $o['info']['pages'][$j];
                                }

                                $o['info']['pages'][$k] = $options['id'];
                            }
                        }
                    }
                }
                break;

            case 'procset':
                $o['info']['procset'] = $options;
                break;

            case 'mediaBox':
                $o['info']['mediaBox'] = $options;
                
                $this->currentPageSize = ['width' => $options[2], 'height' => $options[3]];
                break;

            case 'font':
                $o['info']['fonts'][] = ['objNum' => $options['objNum'], 'fontNum' => $options['fontNum']];
                break;

            case 'extGState':
                $o['info']['extGStates'][] = ['objNum' => $options['objNum'], 'stateNum' => $options['stateNum']];
                break;

            case 'xObject':
                $o['info']['xObjects'][] = ['objNum' => $options['objNum'], 'label' => $options['label']];
                break;

            case 'out':
                if (count($o['info']['pages'])) {
                    $res = "\n$id 0 obj\n<< /Type /Pages\n/Kids [";
                    foreach ($o['info']['pages'] as $v) {
                        $res .= "$v 0 R\n";
                    }

                    $res .= "]\n/Count " . count($this->objects[$id]['info']['pages']);

                    if ((isset($o['info']['fonts']) && count($o['info']['fonts'])) ||
                        isset($o['info']['procset']) ||
                        (isset($o['info']['extGStates']) && count($o['info']['extGStates']))
                    ) {
                        $res .= "\n/Resources <<";

                        if (isset($o['info']['procset'])) {
                            $res .= "\n/ProcSet " . $o['info']['procset'] . " 0 R";
                        }

                        if (isset($o['info']['fonts']) && count($o['info']['fonts'])) {
                            $res .= "\n/Font << ";
                            foreach ($o['info']['fonts'] as $finfo) {
                                $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        if (isset($o['info']['xObjects']) && count($o['info']['xObjects'])) {
                            $res .= "\n/XObject << ";
                            foreach ($o['info']['xObjects'] as $finfo) {
                                $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        if (isset($o['info']['extGStates']) && count($o['info']['extGStates'])) {
                            $res .= "\n/ExtGState << ";
                            foreach ($o['info']['extGStates'] as $gstate) {
                                $res .= "\n/GS" . $gstate['stateNum'] . " " . $gstate['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        $res .= "\n>>";
                        if (isset($o['info']['mediaBox'])) {
                            $tmp = $o['info']['mediaBox'];
                            $res .= "\n/MediaBox [" . sprintf(
                                    '%.3F %.3F %.3F %.3F',
                                    $tmp[0],
                                    $tmp[1],
                                    $tmp[2],
                                    $tmp[3]
                                ) . ']';
                        }
                    }

                    $res .= "\n >>\nendobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Pages\n/Count 0\n>>\nendobj";
                }

                return $res;
        }

        return null;
    }

    
    protected function o_outlines($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'outlines', 'info' => ['outlines' => []]];
                $this->o_catalog($this->catalogId, 'outlines', $id);
                break;

            case 'outline':
                $o['info']['outlines'][] = $options;
                break;

            case 'out':
                if (count($o['info']['outlines'])) {
                    $res = "\n$id 0 obj\n<< /Type /Outlines /Kids [";
                    foreach ($o['info']['outlines'] as $v) {
                        $res .= "$v 0 R ";
                    }

                    $res .= "] /Count " . count($o['info']['outlines']) . " >>\nendobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Outlines /Count 0 >>\nendobj";
                }

                return $res;
        }

        return null;
    }

    
    protected function o_font($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'font',
                    'info' => [
                        'name'         => $options['name'],
                        'fontFileName' => $options['fontFileName'],
                        'SubType'      => 'Type1',
                        'isSubsetting'   => $options['isSubsetting']
                    ]
                ];
                $fontNum = $this->numFonts;
                $this->objects[$id]['info']['fontNum'] = $fontNum;

                
                if (isset($options['differences'])) {
                    
                    $this->numObj++;
                    $this->o_fontEncoding($this->numObj, 'new', $options);
                    $this->objects[$id]['info']['encodingDictionary'] = $this->numObj;
                } else {
                    if (isset($options['encoding'])) {
                        
                        switch ($options['encoding']) {
                            case 'WinAnsiEncoding':
                            case 'MacRomanEncoding':
                            case 'MacExpertEncoding':
                                $this->objects[$id]['info']['encoding'] = $options['encoding'];
                                break;

                            case 'none':
                                break;

                            default:
                                $this->objects[$id]['info']['encoding'] = 'WinAnsiEncoding';
                                break;
                        }
                    } else {
                        $this->objects[$id]['info']['encoding'] = 'WinAnsiEncoding';
                    }
                }

                if ($this->fonts[$options['fontFileName']]['isUnicode']) {
                    
                    
                    
                    
                    
                    
                    

                    $toUnicodeId = ++$this->numObj;
                    $this->o_toUnicode($toUnicodeId, 'new');
                    $this->objects[$id]['info']['toUnicode'] = $toUnicodeId;

                    $cidFontId = ++$this->numObj;
                    $this->o_fontDescendentCID($cidFontId, 'new', $options);
                    $this->objects[$id]['info']['cidFont'] = $cidFontId;
                }

                
                $this->o_pages($this->currentNode, 'font', ['fontNum' => $fontNum, 'objNum' => $id]);
                break;

            case 'add':
                $font_options = $this->processFont($id, $o['info']);

                if ($font_options !== false) {
                    foreach ($font_options as $k => $v) {
                        switch ($k) {
                            case 'BaseFont':
                                $o['info']['name'] = $v;
                                break;
                            case 'FirstChar':
                            case 'LastChar':
                            case 'Widths':
                            case 'FontDescriptor':
                            case 'SubType':
                                $this->addMessage('o_font ' . $k . " : " . $v);
                                $o['info'][$k] = $v;
                                break;
                        }
                    }

                    
                    if (isset($o['info']['cidFont'])) {
                        $this->o_fontDescendentCID($o['info']['cidFont'], 'add', $font_options);
                    }
                }
                break;

            case 'out':
                if ($this->fonts[$this->objects[$id]['info']['fontFileName']]['isUnicode']) {
                    
                    
                    
                    
                    
                    
                    

                    $res = "\n$id 0 obj\n<</Type /Font\n/Subtype /Type0\n";
                    $res .= "/BaseFont /" . $o['info']['name'] . "\n";

                    
                    
                    $res .= "/Encoding /Identity-H\n";
                    $res .= "/DescendantFonts [" . $o['info']['cidFont'] . " 0 R]\n";
                    $res .= "/ToUnicode " . $o['info']['toUnicode'] . " 0 R\n";
                    $res .= ">>\n";
                    $res .= "endobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Font\n/Subtype /" . $o['info']['SubType'] . "\n";
                    $res .= "/Name /F" . $o['info']['fontNum'] . "\n";
                    $res .= "/BaseFont /" . $o['info']['name'] . "\n";

                    if (isset($o['info']['encodingDictionary'])) {
                        
                        $res .= "/Encoding " . $o['info']['encodingDictionary'] . " 0 R\n";
                    } else {
                        if (isset($o['info']['encoding'])) {
                            
                            $res .= "/Encoding /" . $o['info']['encoding'] . "\n";
                        }
                    }

                    if (isset($o['info']['FirstChar'])) {
                        $res .= "/FirstChar " . $o['info']['FirstChar'] . "\n";
                    }

                    if (isset($o['info']['LastChar'])) {
                        $res .= "/LastChar " . $o['info']['LastChar'] . "\n";
                    }

                    if (isset($o['info']['Widths'])) {
                        $res .= "/Widths " . $o['info']['Widths'] . " 0 R\n";
                    }

                    if (isset($o['info']['FontDescriptor'])) {
                        $res .= "/FontDescriptor " . $o['info']['FontDescriptor'] . " 0 R\n";
                    }

                    $res .= ">>\n";
                    $res .= "endobj";
                }

                return $res;
        }

        return null;
    }

    protected function getFontSubsettingTag(array $font): string
    {
        
        $base_26 = strtoupper(base_convert($font['fontNum'], 10, 26));
        for ($i = 0; $i < strlen($base_26); $i++) {
            $char = $base_26[$i];
            if ($char <= "9") {
                $base_26[$i] = chr(65 + intval($char));
            } else {
                $base_26[$i] = chr(ord($char) + 10);
            }
        }

        return 'SUB' . str_pad($base_26, 3, 'A', STR_PAD_LEFT);
    }

    
    private function processFont(int $fontObjId, array $object_info)
    {
        $fontFileName = $object_info['fontFileName'];
        if (!isset($this->fonts[$fontFileName])) {
            return false;
        }

        $font = &$this->fonts[$fontFileName];

        $fileSuffix = $font['fileSuffix'];
        $fileSuffixLower = strtolower($font['fileSuffix']);
        $fbfile = "$fontFileName.$fileSuffix";
        $isTtfFont = $fileSuffixLower === 'ttf';
        $isPfbFont = $fileSuffixLower === 'pfb';

        $this->addMessage('selectFont: checking for - ' . $fbfile);

        if ($this->pdfa && !file_exists($fbfile)) {
            throw new \Exception("A fully embeddable font must be used when generating a document in PDF/A mode");
        } elseif (!$fileSuffix) {
            $this->addMessage(
                'selectFont: pfb or ttf file not found, ok if this is one of the 14 standard fonts'
            );

            return false;
        } else {
            $adobeFontName = isset($font['PostScriptName']) ? $font['PostScriptName'] : $font['FontName'];
            
            $this->addMessage("selectFont: adding font file - $fbfile - $adobeFontName");

            
            $firstChar = -1;
            $lastChar = 0;
            $widths = [];
            $cid_widths = [];

            foreach ($font['C'] as $num => $d) {
                if (intval($num) > 0 || $num == '0') {
                    if (!$font['isUnicode']) {
                        
                        if ($lastChar > 0 && $num > $lastChar + 1) {
                            for ($i = $lastChar + 1; $i < $num; $i++) {
                                $widths[] = 0;
                            }
                        }
                    }

                    $widths[] = $d;

                    if ($font['isUnicode']) {
                        $cid_widths[$num] = $d;
                    }

                    if ($firstChar == -1) {
                        $firstChar = $num;
                    }

                    $lastChar = $num;
                }
            }

            
            if (isset($object['differences'])) {
                foreach ($object['differences'] as $charNum => $charName) {
                    if ($charNum > $lastChar) {
                        if (!$object['isUnicode']) {
                            
                            for ($i = $lastChar + 1; $i <= $charNum; $i++) {
                                $widths[] = 0;
                            }
                        }

                        $lastChar = $charNum;
                    }

                    if (isset($font['C'][$charName])) {
                        $widths[$charNum - $firstChar] = $font['C'][$charName];
                        if ($font['isUnicode']) {
                            $cid_widths[$charName] = $font['C'][$charName];
                        }
                    }
                }
            }

            if ($font['isUnicode']) {
                $font['CIDWidths'] = $cid_widths;
            }

            $this->addMessage('selectFont: FirstChar = ' . $firstChar);
            $this->addMessage('selectFont: LastChar = ' . $lastChar);

            $widthid = -1;

            if (!$font['isUnicode']) {
                

                $this->numObj++;
                $this->o_contents($this->numObj, 'new', 'raw');
                $this->objects[$this->numObj]['c'] .= '[' . implode(' ', $widths) . ']';
                $widthid = $this->numObj;
            }

            $missing_width = 500;
            $stemV = 70;

            if (isset($font['MissingWidth'])) {
                $missing_width = $font['MissingWidth'];
            } elseif (isset($font['IsFixedPitch']) && strtolower($font['IsFixedPitch']) === "true" && isset($font['C'][32])) {
                $missing_width = $font['C'][32];
            }

            if (isset($font['StdVW'])) {
                $stemV = $font['StdVW'];
            } else {
                if (isset($font['Weight']) && preg_match('!(bold|black)!i', $font['Weight'])) {
                    $stemV = 120;
                }
            }

            
            
            
            if (!$font['isSubsetting']) {
                $data = file_get_contents($fbfile);
            } else {
                $adobeFontName = $this->getFontSubsettingTag($font) . '+' . $adobeFontName;
                $this->stringSubsets[$fontFileName][] = 32; 

                $subset = $this->stringSubsets[$fontFileName];
                sort($subset);

                
                $font_obj = Font::load($fbfile);
                $font_obj->parse();

                
                $font_obj->setSubset($subset);
                $font_obj->reduce();

                
                $tmp_name = @tempnam($this->tmp, "cpdf_subset_");
                $font_obj->open($tmp_name, BinaryStream::modeReadWrite);
                $font_obj->encode(["OS/2"]);
                $font_obj->close();

                
                $font_obj = Font::load($tmp_name);

                
                $subtable = null;
                foreach ($font_obj->getData("cmap", "subtables") as $_subtable) {
                    if ($_subtable["platformID"] == 0 || $_subtable["platformID"] == 3 && $_subtable["platformSpecificID"] == 1) {
                        $subtable = $_subtable;
                        break;
                    }
                }

                if ($subtable) {
                    $glyphIndexArray = $subtable["glyphIndexArray"];
                    $hmtx = $font_obj->getData("hmtx");

                    unset($glyphIndexArray[0xFFFF]);

                    $cidtogid = str_pad('', max(array_keys($glyphIndexArray)) * 2 + 1, "\x00");
                    $font['CIDWidths'] = [];
                    foreach ($glyphIndexArray as $cid => $gid) {
                        if ($cid >= 0 && $cid < 0xFFFF && $gid) {
                            $cidtogid[$cid * 2] = chr($gid >> 8);
                            $cidtogid[$cid * 2 + 1] = chr($gid & 0xFF);
                        }

                        $width = $font_obj->normalizeFUnit(isset($hmtx[$gid]) ? $hmtx[$gid][0] : $hmtx[0][0]);
                        $font['CIDWidths'][$cid] = $width;
                    }

                    $font['CIDtoGID'] = base64_encode(gzcompress($cidtogid));
                    $font['CIDtoGID_Compressed'] = true;

                    $data = file_get_contents($tmp_name);
                } else {
                    $data = file_get_contents($fbfile);
                }

                $font_obj->close();
                unlink($tmp_name);
            }

            
            $this->numObj++;
            $fontDescriptorId = $this->numObj;

            $this->numObj++;
            $pfbid = $this->numObj;

            
            $flags = 0;

            if ($font['ItalicAngle'] != 0) {
                $flags += pow(2, 6);
            }

            if ($font['IsFixedPitch'] === 'true') {
                $flags += 1;
            }

            $flags += pow(2, 5); 
            $list = [
                'Ascent'       => 'Ascender',
                'CapHeight'    => 'CapHeight',
                'MissingWidth' => 'MissingWidth',
                'Descent'      => 'Descender',
                'FontBBox'     => 'FontBBox',
                'ItalicAngle'  => 'ItalicAngle'
            ];
            $fdopt = [
                'Flags'    => $flags,
                'FontName' => $adobeFontName,
                'StemV'    => $stemV
            ];

            foreach ($list as $k => $v) {
                if (isset($font[$v])) {
                    $fdopt[$k] = $font[$v];
                }
            }
            if (!isset($fdopt['CapHeight']) && isset($fdopt['Ascender'])) {
                $fdopt['CapHeight'] = $fdopt['Ascender'];
            }

            if ($isPfbFont) {
                $fdopt['FontFile'] = $pfbid;
            } elseif ($isTtfFont) {
                $fdopt['FontFile2'] = $pfbid;
            }

            $this->o_fontDescriptor($fontDescriptorId, 'new', $fdopt);

            
            $this->o_contents($this->numObj, 'new');
            $this->objects[$pfbid]['c'] .= $data;

            
            if ($isPfbFont) {
                $l1 = strpos($data, 'eexec') + 6;
                $l2 = strpos($data, '00000000') - $l1;
                $l3 = mb_strlen($data, '8bit') - $l2 - $l1;
                $this->o_contents(
                    $this->numObj,
                    'add',
                    ['Length1' => $l1, 'Length2' => $l2, 'Length3' => $l3]
                );
            } elseif ($isTtfFont) {
                $l1 = mb_strlen($data, '8bit');
                $this->o_contents($this->numObj, 'add', ['Length1' => $l1]);
            }

            
            $options = [
                'BaseFont'       => $adobeFontName,
                'MissingWidth'   => $missing_width,
                'Widths'         => $widthid,
                'FirstChar'      => $firstChar,
                'LastChar'       => $lastChar,
                'FontDescriptor' => $fontDescriptorId
            ];

            if ($isTtfFont) {
                $options['SubType'] = 'TrueType';
            }

            $this->addMessage("adding extra info to font.($fontObjId)");

            foreach ($options as $fk => $fv) {
                $this->addMessage("$fk : $fv");
            }
        }

        return $options;
    }

    
    protected function o_toUnicode($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'toUnicode'
                ];
                break;
            case 'add':
                break;
            case 'out':
                $ordering = 'UCS';
                $registry = 'Adobe';

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $ordering = $this->ARC4($ordering);
                    $registry = $this->filterText($this->ARC4($registry), false, false);
                }

                $stream = <<<EOT
/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo
<</Registry ($registry)
/Ordering ($ordering)
/Supplement 0
>> def
/CMapName /Adobe-Identity-UCS def
/CMapType 2 def
1 begincodespacerange
<0000> <FFFF>
endcodespacerange
1 beginbfrange
<0000> <FFFF> <0000>
endbfrange
endcmap
CMapName currentdict /CMap defineresource pop
end
end
EOT;

                $res = "\n$id 0 obj\n";
                $res .= "<</Length " . mb_strlen($stream, '8bit') . " >>\n";
                $res .= "stream\n" . $stream . "\nendstream" . "\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_fontDescriptor($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontDescriptor', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /FontDescriptor\n";
                foreach ($o['info'] as $label => $value) {
                    switch ($label) {
                        case 'Ascent':
                        case 'CapHeight':
                        case 'Descent':
                        case 'Flags':
                        case 'ItalicAngle':
                        case 'StemV':
                        case 'AvgWidth':
                        case 'Leading':
                        case 'MaxWidth':
                        case 'MissingWidth':
                        case 'StemH':
                        case 'XHeight':
                        case 'CharSet':
                            if (mb_strlen($value, '8bit')) {
                                $res .= "/$label $value\n";
                            }

                            break;
                        case 'FontFile':
                        case 'FontFile2':
                        case 'FontFile3':
                            $res .= "/$label $value 0 R\n";
                            break;

                        case 'FontBBox':
                            $res .= "/$label [$value[0] $value[1] $value[2] $value[3]]\n";
                            break;

                        case 'FontName':
                            $res .= "/$label /$value\n";
                            break;
                    }
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_fontEncoding($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                
                $this->objects[$id] = ['t' => 'fontEncoding', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Encoding\n";
                if (!isset($o['info']['encoding'])) {
                    $o['info']['encoding'] = 'WinAnsiEncoding';
                }

                if ($o['info']['encoding'] !== 'none') {
                    $res .= "/BaseEncoding /" . $o['info']['encoding'] . "\n";
                }

                $res .= "/Differences \n[";

                $onum = -100;

                foreach ($o['info']['differences'] as $num => $label) {
                    if ($num != $onum + 1) {
                        
                        $res .= "\n$num /$label";
                    } else {
                        $res .= " /$label";
                    }

                    $onum = $num;
                }

                $res .= "\n]\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_fontDescendentCID($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontDescendentCID', 'info' => $options];

                
                $cidSystemInfoId = ++$this->numObj;
                $this->o_cidSystemInfo($cidSystemInfoId, 'new');
                $this->objects[$id]['info']['cidSystemInfo'] = $cidSystemInfoId;

                
                $cidToGidMapId = ++$this->numObj;
                $this->o_fontGIDtoCIDMap($cidToGidMapId, 'new', $options);
                $this->objects[$id]['info']['cidToGidMap'] = $cidToGidMapId;
                break;

            case 'add':
                foreach ($options as $k => $v) {
                    switch ($k) {
                        case 'BaseFont':
                            $o['info']['name'] = $v;
                            break;

                        case 'FirstChar':
                        case 'LastChar':
                        case 'MissingWidth':
                        case 'FontDescriptor':
                        case 'SubType':
                            $this->addMessage("o_fontDescendentCID $k : $v");
                            $o['info'][$k] = $v;
                            break;
                    }
                }

                
                $this->o_fontGIDtoCIDMap($o['info']['cidToGidMap'], 'add', $options);
                break;

            case 'out':
                $res = "\n$id 0 obj\n";
                $res .= "<</Type /Font\n";
                $res .= "/Subtype /CIDFontType2\n";
                $res .= "/BaseFont /" . $o['info']['name'] . "\n";
                $res .= "/CIDSystemInfo " . $o['info']['cidSystemInfo'] . " 0 R\n";
                
                
                

                
                
                
                if (isset($o['info']['FontDescriptor'])) {
                    $res .= "/FontDescriptor " . $o['info']['FontDescriptor'] . " 0 R\n";
                }

                if (isset($o['info']['MissingWidth'])) {
                    $res .= "/DW " . $o['info']['MissingWidth'] . "\n";
                }

                if (isset($o['info']['fontFileName']) && isset($this->fonts[$o['info']['fontFileName']]['CIDWidths'])) {
                    $cid_widths = &$this->fonts[$o['info']['fontFileName']]['CIDWidths'];
                    $w = '';
                    foreach ($cid_widths as $cid => $width) {
                        $w .= "$cid [$width] ";
                    }
                    $res .= "/W [$w]\n";
                }

                $res .= "/CIDToGIDMap " . $o['info']['cidToGidMap'] . " 0 R\n";
                $res .= ">>\n";
                $res .= "endobj";

                return $res;
        }

        return null;
    }

    
    protected function o_cidSystemInfo($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't' => 'cidSystemInfo'
                ];
                break;
            case 'add':
                break;
            case 'out':
                $ordering = 'UCS';
                $registry = 'Adobe';

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $ordering = $this->ARC4($ordering);
                    $registry = $this->ARC4($registry);
                }


                $res = "\n$id 0 obj\n";

                $res .= '<</Registry (' . $registry . ")\n"; 
                $res .= '/Ordering (' . $ordering . ")\n"; 
                $res .= "/Supplement 0\n"; 
                $res .= ">>";

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_fontGIDtoCIDMap($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontGIDtoCIDMap', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n";
                $fontFileName = $o['info']['fontFileName'];
                $tmp = $this->fonts[$fontFileName]['CIDtoGID'] = base64_decode($this->fonts[$fontFileName]['CIDtoGID']);

                $compressed = isset($this->fonts[$fontFileName]['CIDtoGID_Compressed']) &&
                    $this->fonts[$fontFileName]['CIDtoGID_Compressed'];

                if (!$compressed && isset($o['raw'])) {
                    $res .= $tmp;
                } else {
                    $res .= "<<";

                    if (!$compressed && $this->compressionReady && $this->options['compression']) {
                        
                        $compressed = true;
                        $tmp = gzcompress($tmp, 6);
                    }
                    if ($compressed) {
                        $res .= "\n/Filter /FlateDecode";
                    }

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                        $tmp = $this->ARC4($tmp);
                    }

                    $res .= "\n/Length " . mb_strlen($tmp, '8bit') . ">>\nstream\n$tmp\nendstream";
                }

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_procset($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'procset', 'info' => ['PDF' => 1, 'Text' => 1]];
                $this->o_pages($this->currentNode, 'procset', $id);
                $this->procsetObjectId = $id;
                break;

            case 'add':
                
                
                switch ($options) {
                    case 'ImageB':
                    case 'ImageC':
                    case 'ImageI':
                        $o['info'][$options] = 1;
                        break;
                }
                break;

            case 'out':
                $res = "\n$id 0 obj\n[";
                foreach ($o['info'] as $label => $val) {
                    $res .= "/$label ";
                }
                $res .= "]\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_info($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->infoObject = $id;
                $date = 'D:' . @date('Ymd');
                $this->objects[$id] = [
                    't'    => 'info',
                    'info' => [
                        'Producer'     => 'CPDF (dompdf)',
                        'CreationDate' => $date
                    ]
                ];
                break;
            case 'Title':
            case 'Author':
            case 'Subject':
            case 'Keywords':
            case 'Creator':
            case 'Producer':
            case 'CreationDate':
            case 'ModDate':
            case 'Trapped':
                $this->objects[$id]['info'][$action] = $options;
                break;

            case 'out':
                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                $res = "\n$id 0 obj\n<<\n";
                $o = &$this->objects[$id];
                foreach ($o['info'] as $k => $v) {
                    $res .= "/$k (";

                    
                    if ($k !== 'CreationDate' && $k !== 'ModDate') {
                        $v = $this->utf8toUtf16BE($v);
                    }

                    if ($encrypted) {
                        $v = $this->ARC4($v);
                    }

                    $res .= $this->filterText($v, false, false);
                    $res .= ")\n";
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_action($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                if (is_array($options)) {
                    $this->objects[$id] = ['t' => 'action', 'info' => $options, 'type' => $options['type']];
                } else {
                    
                    $this->objects[$id] = ['t' => 'action', 'info' => $options, 'type' => 'URI'];
                }
                break;

            case 'out':
                if ($this->encrypted) {
                    $this->encryptInit($id);
                }

                $res = "\n$id 0 obj\n<< /Type /Action";
                switch ($o['type']) {
                    case 'ilink':
                        if (!isset($this->destinations[(string)$o['info']['label']])) {
                            break;
                        }

                        
                        $res .= "\n/S /GoTo\n/D " . $this->destinations[(string)$o['info']['label']] . " 0 R";
                        break;

                    case 'URI':
                        $res .= "\n/S /URI\n/URI (";
                        if ($this->encrypted) {
                            $res .= $this->filterText($this->ARC4($o['info']), false, false);
                        } else {
                            $res .= $this->filterText($o['info'], false, false);
                        }

                        $res .= ")";
                        break;
                }

                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_annotation($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                
                $pageId = $this->currentPage;
                $this->o_page($pageId, 'annot', $id);

                
                switch ($options['type']) {
                    case 'link':
                        $this->objects[$id] = ['t' => 'annotation', 'info' => $options];
                        $this->numObj++;
                        $this->o_action($this->numObj, 'new', $options['url']);
                        $this->objects[$id]['info']['actionId'] = $this->numObj;
                        break;

                    case 'ilink':
                        
                        $label = $options['label'];
                        $this->objects[$id] = ['t' => 'annotation', 'info' => $options];
                        $this->numObj++;
                        $this->o_action($this->numObj, 'new', ['type' => 'ilink', 'label' => $label]);
                        $this->objects[$id]['info']['actionId'] = $this->numObj;
                        break;
                }
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Annot";
                switch ($o['info']['type']) {
                    case 'link':
                    case 'ilink':
                        $res .= "\n/Subtype /Link";
                        break;
                }
                $res .= "\n/F 28";
                $res .= "\n/A " . $o['info']['actionId'] . " 0 R";
                $res .= "\n/Border [0 0 0]";
                $res .= "\n/H /I";
                $res .= "\n/Rect [ ";

                foreach ($o['info']['rect'] as $v) {
                    $res .= sprintf("%.4F ", $v);
                }

                $res .= "]";
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_page($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->numPages++;
                $this->objects[$id] = [
                    't'    => 'page',
                    'info' => [
                        'parent'  => $this->currentNode,
                        'pageNum' => $this->numPages,
                        'mediaBox' => $this->objects[$this->currentNode]['info']['mediaBox']
                    ]
                ];

                if (is_array($options)) {
                    
                    $options['id'] = $id;
                    $this->o_pages($this->currentNode, 'page', $options);
                } else {
                    $this->o_pages($this->currentNode, 'page', $id);
                }

                $this->currentPage = $id;
                
                $this->numObj++;
                $this->o_contents($this->numObj, 'new', $id);
                $this->currentContents = $this->numObj;
                $this->objects[$id]['info']['contents'] = [];
                $this->objects[$id]['info']['contents'][] = $this->numObj;

                $match = ($this->numPages % 2 ? 'odd' : 'even');
                foreach ($this->addLooseObjects as $oId => $target) {
                    if ($target === 'all' || $match === $target) {
                        $this->objects[$id]['info']['contents'][] = $oId;
                    }
                }
                break;

            case 'content':
                $o['info']['contents'][] = $options;
                break;

            case 'annot':
                
                if (!isset($o['info']['annot'])) {
                    $o['info']['annot'] = [];
                }

                
                $o['info']['annot'][] = $options;
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Page";
                if (isset($o['info']['mediaBox'])) {
                    $tmp = $o['info']['mediaBox'];
                    $res .= "\n/MediaBox [" . sprintf(
                            '%.3F %.3F %.3F %.3F',
                            $tmp[0],
                            $tmp[1],
                            $tmp[2],
                            $tmp[3]
                        ) . ']';
                }
                $res .= "\n/Parent " . $o['info']['parent'] . " 0 R";

                if (isset($o['info']['annot'])) {
                    $res .= "\n/Annots [";
                    foreach ($o['info']['annot'] as $aId) {
                        $res .= " $aId 0 R";
                    }
                    $res .= " ]";
                }

                $count = count($o['info']['contents']);
                if ($count == 1) {
                    $res .= "\n/Contents " . $o['info']['contents'][0] . " 0 R";
                } else {
                    if ($count > 1) {
                        $res .= "\n/Contents [\n";

                        
                        
                        
                        foreach ($o['info']['contents'] as $cId) {
                            $res .= "$cId 0 R\n";
                        }
                        $res .= "]";
                    }
                }

                
                if ($this->pdfa) {
                    $pagesInfo = $this->objects[$this->currentNode]['info'];

                    if ((isset($pagesInfo['fonts']) && count($pagesInfo['fonts'])) ||
                        isset($pagesInfo['procset']) ||
                        (isset($pagesInfo['extGStates']) && count($pagesInfo['extGStates']))
                    ) {
                        $res .= "\n/Resources <<";
    
                        if (isset($pagesInfo['procset'])) {
                            $res .= "\n/ProcSet " . $pagesInfo['procset'] . " 0 R";
                        }
    
                        if (isset($pagesInfo['fonts']) && count($pagesInfo['fonts'])) {
                            $res .= "\n/Font << ";
                            foreach ($pagesInfo['fonts'] as $finfo) {
                                $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        if (isset($pagesInfo['xObjects']) && count($pagesInfo['xObjects'])) {
                            $res .= "\n/XObject << ";
                            foreach ($pagesInfo['xObjects'] as $finfo) {
                                $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        if (isset($pagesInfo['extGStates']) && count($pagesInfo['extGStates'])) {
                            $res .= "\n/ExtGState << ";
                            foreach ($pagesInfo['extGStates'] as $gstate) {
                                $res .= "\n/GS" . $gstate['stateNum'] . " " . $gstate['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        $res .= "\n>>";
                    }
                }

                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_contents($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'contents', 'c' => '', 'info' => []];
                if (mb_strlen($options, '8bit') && intval($options)) {
                    
                    $this->objects[$id]['onPage'] = $options;
                } else {
                    if ($options === 'raw') {
                        
                        $this->objects[$id]['raw'] = 1;
                    }
                }
                break;

            case 'add':
                
                foreach ($options as $k => $v) {
                    $o['info'][$k] = $v;
                }

            case 'out':
                $tmp = $o['c'];
                $res = "\n$id 0 obj\n";

                if (isset($this->objects[$id]['raw'])) {
                    $res .= $tmp;
                } else {
                    $res .= "<<";
                    if ($this->compressionReady && $this->options['compression']) {
                        
                        $res .= " /Filter /FlateDecode";
                        $tmp = gzcompress($tmp, 6);
                    }

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                        $tmp = $this->ARC4($tmp);
                    }

                    foreach ($o['info'] as $k => $v) {
                        $res .= "\n/$k $v";
                    }

                    $res .= "\n/Length " . mb_strlen($tmp, '8bit') . " >>\nstream\n$tmp\nendstream";
                }

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_embedjs($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'embedjs',
                    'info' => [
                        'Names' => '[(EmbeddedJS) ' . ($id + 1) . ' 0 R]'
                    ]
                ];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";
                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_javascript($id, $action, $code = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'javascript',
                    'info' => [
                        'S'  => '/JavaScript',
                        'JS' => '(' . $this->filterText($code, true, false) . ')',
                    ]
                ];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";

                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_image($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                
                $this->objects[$id] = ['t' => 'image', 'data' => &$options['data'], 'info' => []];

                $info =& $this->objects[$id]['info'];

                $info['Type'] = '/XObject';
                $info['Subtype'] = '/Image';
                $info['Width'] = $options['iw'];
                $info['Height'] = $options['ih'];

                if (isset($options['masked']) && $options['masked']) {
                    $info['SMask'] = ($this->numObj - 1) . ' 0 R';
                }

                if (!isset($options['type']) || $options['type'] === 'jpg') {
                    if (!isset($options['channels'])) {
                        $options['channels'] = 3;
                    }

                    switch ($options['channels']) {
                        case 1:
                            $info['ColorSpace'] = '/DeviceGray';
                            break;
                        case 4:
                            $info['ColorSpace'] = '/DeviceCMYK';
                            break;
                        default:
                            $info['ColorSpace'] = '/DeviceRGB';
                            break;
                    }

                    if ($info['ColorSpace'] === '/DeviceCMYK') {
                        if ($this->pdfa) {
                            throw new \Exception("CMYK images are not supported when generating a document in PDF/A mode");
                        }
                        $info['Decode'] = '[1 0 1 0 1 0 1 0]';
                    }

                    $info['Filter'] = '/DCTDecode';
                    $info['BitsPerComponent'] = 8;
                } else {
                    if ($options['type'] === 'png') {
                        $info['Filter'] = '/FlateDecode';
                        $info['DecodeParms'] = '<< /Predictor 15 /Colors ' . $options['ncolor'] . ' /Columns ' . $options['iw'] . ' /BitsPerComponent ' . $options['bitsPerComponent'] . '>>';

                        if ($options['isMask']) {
                            $info['ColorSpace'] = '/DeviceGray';
                        } else {
                            if (mb_strlen($options['pdata'], '8bit')) {
                                $tmp = ' [ /Indexed /DeviceRGB ' . (mb_strlen($options['pdata'], '8bit') / 3 - 1) . ' ';
                                $this->numObj++;
                                $this->o_contents($this->numObj, 'new');
                                $this->objects[$this->numObj]['c'] = $options['pdata'];
                                $tmp .= $this->numObj . ' 0 R';
                                $tmp .= ' ]';
                                $info['ColorSpace'] = $tmp;

                                if (isset($options['transparency'])) {
                                    $transparency = $options['transparency'];
                                    switch ($transparency['type']) {
                                        case 'indexed':
                                            $tmp = ' [ ' . $transparency['data'] . ' ' . $transparency['data'] . '] ';
                                            $info['Mask'] = $tmp;
                                            break;

                                        case 'color-key':
                                            $tmp = ' [ ' .
                                                $transparency['r'] . ' ' . $transparency['r'] .
                                                $transparency['g'] . ' ' . $transparency['g'] .
                                                $transparency['b'] . ' ' . $transparency['b'] .
                                                ' ] ';
                                            $info['Mask'] = $tmp;
                                            break;
                                    }
                                }
                            } else {
                                if (isset($options['transparency'])) {
                                    $transparency = $options['transparency'];

                                    switch ($transparency['type']) {
                                        case 'indexed':
                                            $tmp = ' [ ' . $transparency['data'] . ' ' . $transparency['data'] . '] ';
                                            $info['Mask'] = $tmp;
                                            break;

                                        case 'color-key':
                                            $tmp = ' [ ' .
                                                $transparency['r'] . ' ' . $transparency['r'] . ' ' .
                                                $transparency['g'] . ' ' . $transparency['g'] . ' ' .
                                                $transparency['b'] . ' ' . $transparency['b'] .
                                                ' ] ';
                                            $info['Mask'] = $tmp;
                                            break;
                                    }
                                }
                                $info['ColorSpace'] = '/' . $options['color'];
                            }
                        }

                        $info['BitsPerComponent'] = $options['bitsPerComponent'];
                    }
                }

                
                
                $this->o_pages($this->currentNode, 'xObject', ['label' => $options['label'], 'objNum' => $id]);

                
                $this->o_procset($this->procsetObjectId, 'add', 'ImageC');
                break;

            case 'out':
                $o = &$this->objects[$id];
                $tmp = &$o['data'];
                $res = "\n$id 0 obj\n<<";

                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $tmp = $this->ARC4($tmp);
                }

                $res .= "\n/Length " . mb_strlen($tmp, '8bit') . ">>\nstream\n$tmp\nendstream\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_extGState($id, $action, $options = "")
    {
        static $valid_params = [
            "LW",
            "LC",
            "LC",
            "LJ",
            "ML",
            "D",
            "RI",
            "OP",
            "op",
            "OPM",
            "Font",
            "BG",
            "BG2",
            "UCR",
            "TR",
            "TR2",
            "HT",
            "FL",
            "SM",
            "SA",
            "BM",
            "SMask",
            "CA",
            "ca",
            "AIS",
            "TK"
        ];

        switch ($action) {
            case "new":
                $this->objects[$id] = ['t' => 'extGState', 'info' => $options];

                
                $this->numStates++;
                $this->o_pages($this->currentNode, 'extGState', ["objNum" => $id, "stateNum" => $this->numStates]);
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /ExtGState\n";

                foreach ($o["info"] as $k => $v) {
                    if (!in_array($k, $valid_params)) {
                        continue;
                    }
                    $res .= "/$k $v\n";
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_xobject($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'xobject', 'info' => $options, 'c' => ''];
                break;

            case 'procset':
                $this->objects[$id]['procset'] = $options;
                break;

            case 'font':
                $this->objects[$id]['fonts'][$options['fontNum']] = [
                  'objNum' => $options['objNum'],
                  'fontNum' => $options['fontNum']
                ];
                break;

            case 'xObject':
                $this->objects[$id]['xObjects'][] = ['objNum' => $options['objNum'], 'label' => $options['label']];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /XObject\n";

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'Subtype':
                            $res .= "/Subtype /$v\n";
                            break;
                        case 'bbox':
                            $res .= "/BBox [";
                            foreach ($v as $value) {
                                $res .= sprintf("%.4F ", $value);
                            }
                            $res .= "]\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                            break;
                    }
                }
                $res .= "/Matrix[1.0 0.0 0.0 1.0 0.0 0.0]\n";

                $res .= "/Resources <<";
                if (isset($o['procset'])) {
                    $res .= "\n/ProcSet " . $o['procset'] . " 0 R";
                } else {
                    $res .= "\n/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]";
                }
                if (isset($o['fonts']) && count($o['fonts'])) {
                    $res .= "\n/Font << ";
                    foreach ($o['fonts'] as $finfo) {
                        $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                    }
                    $res .= "\n>>";
                }
                if (isset($o['xObjects']) && count($o['xObjects'])) {
                    $res .= "\n/XObject << ";
                    foreach ($o['xObjects'] as $finfo) {
                        $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                    }
                    $res .= "\n>>";
                }
                $res .= "\n>>\n";

                $tmp = $o["c"];
                if ($this->compressionReady && $this->options['compression']) {
                    
                    $res .= " /Filter /FlateDecode\n";
                    $tmp = gzcompress($tmp, 6);
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $tmp = $this->ARC4($tmp);
                }

                $res .= "/Length " . mb_strlen($tmp, '8bit') . " >>\n";
                $res .= "stream\n" . $tmp . "\nendstream" . "\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_acroform($id, $action, $options = '')
    {
        switch ($action) {
            case "new":
                $this->o_catalog($this->catalogId, 'acroform', $id);
                $this->objects[$id] = array('t' => 'acroform', 'info' => $options);
                break;

            case 'addfield':
                $this->objects[$id]['info']['Fields'][] = $options;
                break;

            case 'font':
                $this->objects[$id]['fonts'][$options['fontNum']] = [
                  'objNum' => $options['objNum'],
                  'fontNum' => $options['fontNum']
                ];
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<<";

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'Fields':
                            $res .= " /Fields [";
                            foreach ($v as $i) {
                                $res .= "$i 0 R ";
                            }
                            $res .= "]\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                    }
                }

                $res .= "/DR <<\n";
                if (isset($o['fonts']) && count($o['fonts'])) {
                    $res .= "/Font << \n";
                    foreach ($o['fonts'] as $finfo) {
                        $res .= "/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R\n";
                    }
                    $res .= ">>\n";
                }
                $res .= ">>\n";

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_field($id, $action, $options = '')
    {
        switch ($action) {
            case "new":
                $this->o_page($options['pageid'], 'annot', $id);
                $this->o_acroform($this->acroFormId, 'addfield', $id);
                $this->objects[$id] = ['t' => 'field', 'info' => $options];
                break;

            case 'set':
                $this->objects[$id]['info'] = array_merge($this->objects[$id]['info'], $options);
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /Annot /Subtype /Widget \n";

                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'pageid':
                            $res .= "/P $v 0 R\n";
                            break;
                        case 'value':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/V ($v)\n";
                            break;
                        case 'refvalue':
                            $res .= "/V $v 0 R\n";
                            break;
                        case 'da':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/DA ($v)\n";
                            break;
                        case 'options':
                            $res .= "/Opt [\n";
                            foreach ($v as $opt) {
                                if ($encrypted) {
                                    $opt = $this->filterText($this->ARC4($opt), false, false);
                                }
                                $res .= "($opt)\n";
                            }
                            $res .= "]\n";
                            break;
                        case 'rect':
                            $res .= "/Rect [";
                            foreach ($v as $value) {
                                $res .= sprintf("%.4F ", $value);
                            }
                            $res .= "]\n";
                            break;
                        case 'appearance':
                            $res .= "/AP << ";
                            foreach ($v as $a => $ref) {
                                $res .= "/$a $ref 0 R ";
                            }
                            $res .= ">>\n";
                            break;
                        case 'T':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/T ($v)\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                    }

                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_sig($id, $action, $options = '')
    {
        $sign_maxlen = $this->signatureMaxLen;

        switch ($action) {
            case "new":
                $this->objects[$id] = array('t' => 'sig', 'info' => $options);
                $this->byteRange[$id] = ['t' => 'sig'];
                break;

            case 'byterange':
                $o = &$this->objects[$id];
                $content =& $options['content'];
                $content_len = strlen($content);
                $pos = strpos($content, sprintf("/ByteRange [ %'.010d", $id));
                $len = strlen('/ByteRange [ ********** ********** ********** ********** ]');
                $rangeStartPos = $pos + $len + 1 + 10; 
                $content = substr_replace($content, str_pad(sprintf('/ByteRange [ 0 %u %u %u ]', $rangeStartPos, $rangeStartPos + $sign_maxlen + 2, $content_len - 2 - $sign_maxlen - $rangeStartPos), $len, ' ', STR_PAD_RIGHT), $pos, $len);

                $fuid = uniqid();
                $tmpInput = $this->tmp . "/pkcs7.tmp." . $fuid . '.in';
                $tmpOutput = $this->tmp . "/pkcs7.tmp." . $fuid . '.out';

                if (file_put_contents($tmpInput, substr($content, 0, $rangeStartPos)) === false) {
                    throw new \Exception("Unable to write temporary file for signing.");
                }
                if (file_put_contents($tmpInput, substr($content, $rangeStartPos + 2 + $sign_maxlen),
                    FILE_APPEND) === false) {
                    throw new \Exception("Unable to write temporary file for signing.");
                }

                if (openssl_pkcs7_sign($tmpInput, $tmpOutput,
                    $o['info']['SignCert'],
                    array($o['info']['PrivKey'], $o['info']['Password']),
                    array(), PKCS7_BINARY | PKCS7_DETACHED) === false) {
                    throw new \Exception("Failed to prepare signature.");
                }

                $signature = file_get_contents($tmpOutput);

                unlink($tmpInput);
                unlink($tmpOutput);

                $sign = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));
                list($head, $signature) = explode("\n\n", $sign);

                $signature = base64_decode(trim($signature));

                $signature = current(unpack('H*', $signature));
                $signature = str_pad($signature, $sign_maxlen, '0');
                $siglen = strlen($signature);
                if (strlen($signature) > $sign_maxlen) {
                    throw new \Exception("Signature length ($siglen) exceeds the $sign_maxlen limit.");
                }

                $content = substr_replace($content, $signature, $rangeStartPos + 1, $sign_maxlen);
                break;

            case "out":
                $res = "\n$id 0 obj\n<<\n";

                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                $res .= "/ByteRange " .sprintf("[ %'.010d ********** ********** ********** ]\n", $id);
                $res .= "/Contents <" . str_pad('', $sign_maxlen, '0') . ">\n";
                $res .= "/Filter/Adobe.PPKLite\n"; 
                $res .= "/Type/Sig/SubFilter/adbe.pkcs7.detached \n";

                $date = "D:" . substr_replace(date('YmdHisO'), '\'', -2, 0) . '\'';
                if ($encrypted) {
                    $date = $this->ARC4($date);
                }

                $res .= "/M ($date)\n";
                $res .= "/Prop_Build << /App << /Name /DomPDF >> /Filter << /Name /Adobe.PPKLite >> >>\n";

                $o = &$this->objects[$id];
                foreach ($o['info'] as $k => $v) {
                    switch ($k) {
                        case 'Name':
                        case 'Location':
                        case 'Reason':
                        case 'ContactInfo':
                            if ($v !== null && $v !== '') {
                                $res .= "/$k (" .
                                  ($encrypted ? $this->filterText($this->ARC4($v), false, false) : $v) . ") \n";
                            }
                            break;
                    }
                }
                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    
    protected function o_encryption($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                
                $this->objects[$id] = ['t' => 'encryption', 'info' => $options];
                $this->arc4_objnum = $id;
                break;

            case 'keys':
                
                $pad = chr(0x28) . chr(0xBF) . chr(0x4E) . chr(0x5E) . chr(0x4E) . chr(0x75) . chr(0x8A) . chr(0x41)
                    . chr(0x64) . chr(0x00) . chr(0x4E) . chr(0x56) . chr(0xFF) . chr(0xFA) . chr(0x01) . chr(0x08)
                    . chr(0x2E) . chr(0x2E) . chr(0x00) . chr(0xB6) . chr(0xD0) . chr(0x68) . chr(0x3E) . chr(0x80)
                    . chr(0x2F) . chr(0x0C) . chr(0xA9) . chr(0xFE) . chr(0x64) . chr(0x53) . chr(0x69) . chr(0x7A);

                $info = $this->objects[$id]['info'];

                $len = mb_strlen($info['owner'], '8bit');

                if ($len > 32) {
                    $owner = substr($info['owner'], 0, 32);
                } else {
                    if ($len < 32) {
                        $owner = $info['owner'] . substr($pad, 0, 32 - $len);
                    } else {
                        $owner = $info['owner'];
                    }
                }

                $len = mb_strlen($info['user'], '8bit');
                if ($len > 32) {
                    $user = substr($info['user'], 0, 32);
                } else {
                    if ($len < 32) {
                        $user = $info['user'] . substr($pad, 0, 32 - $len);
                    } else {
                        $user = $info['user'];
                    }
                }

                $tmp = $this->md5_16($owner);
                $okey = substr($tmp, 0, 5);
                $this->ARC4_init($okey);
                $ovalue = $this->ARC4($user);
                $this->objects[$id]['info']['O'] = $ovalue;

                
                $tmp = $this->md5_16(
                    $user . $ovalue . chr($info['p']) . chr(255) . chr(255) . chr(255) . hex2bin($this->fileIdentifier)
                );

                $ukey = substr($tmp, 0, 5);
                $this->ARC4_init($ukey);
                $this->encryptionKey = $ukey;
                $this->encrypted = true;
                $uvalue = $this->ARC4($pad);
                $this->objects[$id]['info']['U'] = $uvalue;
                
                break;

            case 'out':
                $o = &$this->objects[$id];

                $res = "\n$id 0 obj\n<<";
                $res .= "\n/Filter /Standard";
                $res .= "\n/V 1";
                $res .= "\n/R 2";
                $res .= "\n/O (" . $this->filterText($o['info']['O'], false, false) . ')';
                $res .= "\n/U (" . $this->filterText($o['info']['U'], false, false) . ')';
                
                $o['info']['p'] = (($o['info']['p'] ^ 255) + 1) * -1;
                $res .= "\n/P " . ($o['info']['p']);
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    protected function o_indirect_references($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
            case 'add':
                if ($id === 0) {
                    $id = ++$this->numObj;
                    $this->o_catalog($this->catalogId, 'names', $id);
                    $this->objects[$id] = ['t' => 'indirect_references', 'info' => $options];
                    $this->indirectReferenceId = $id;
                } else {
                    $this->objects[$id]['info'] = array_merge($this->objects[$id]['info'], $options);
                }
                break;
            case 'out':
                $res = "\n$id 0 obj\n<< ";

                foreach ($this->objects[$id]['info'] as $referenceObjName => $referenceObjId) {
                    $res .= "/$referenceObjName $referenceObjId 0 R ";
                }

                $res .= ">>\nendobj";
                return $res;
        }

        return null;
    }

    protected function o_names($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
            case 'add':
                if ($id === 0) {
                    $id = ++$this->numObj;
                    $this->objects[$id] = ['t' => 'names', 'info' => [$options]];
                    $this->o_indirect_references($this->indirectReferenceId, 'add', ['EmbeddedFiles' => $id]);
                    $this->embeddedFilesId = $id;
                } else {
                    $this->objects[$id]['info'][] = $options;
                }
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];
                $res = '';
                if (count($info) > 0) {
                    $res = "\n$id 0 obj\n<< /Names [ ";

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                    }

                    foreach ($info as $entry) {
                        if ($this->encrypted) {
                            $filename = $this->ARC4($entry['filename']);
                        } else {
                            $filename = $entry['filename'];
                        }

                        $res .= "($filename) " . $entry['dict_reference'] . " 0 R ";
                    }

                    $res .= "] >>\nendobj";
                }
                return $res;
        }

        return null;
    }

    protected function o_embedded_file_dictionary($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
                $embeddedFileId = ++$this->numObj;
                $options['embedded_reference'] = $embeddedFileId;
                $this->objects[$id] = ['t' => 'embedded_file_dictionary', 'info' => $options];
                $this->o_embedded_file($embeddedFileId, 'new', $options);
                $options['dict_reference'] = $id;
                $this->o_names($this->embeddedFilesId, 'add', $options);
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];
                $filename = $this->utf8toUtf16BE($info['filename']);
                $description = $this->utf8toUtf16BE($info['description']);

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $filename = $this->ARC4($filename);
                    $description = $this->ARC4($description);
                }

                $filename = $this->filterText($filename, false, false);
                $description = $this->filterText($description, false, false);

                $res = "\n$id 0 obj\n<</Type /Filespec /EF";
                $res .= " <</F " . $info['embedded_reference'] . " 0 R >>";
                $res .= " /F ($filename) /UF ($filename) /Desc ($description)";
                $res .= " >>\nendobj";
                return $res;
        }

        return null;
    }

    protected function o_embedded_file($id, $action, $options = null): ?string
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'embedded_file', 'info' => $options];
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];

                if ($this->compressionReady) {
                    $filepath = $info['filepath'];
                    $checksum = md5_file($filepath);
                    $f = fopen($filepath, "rb");

                    $file_content_compressed = '';
                    $deflateContext = deflate_init(ZLIB_ENCODING_DEFLATE, ['level' => 6]);
                    while (($block = fread($f, 8192))) {
                        $file_content_compressed .= deflate_add($deflateContext, $block, ZLIB_NO_FLUSH);
                    }
                    $file_content_compressed .= deflate_add($deflateContext, '', ZLIB_FINISH);
                    $file_size_uncompressed = ftell($f);
                    fclose($f);
                } else {
                    $file_content = file_get_contents($info['filepath']);
                    $file_size_uncompressed = mb_strlen($file_content, '8bit');
                    $checksum = md5($file_content);
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $checksum = $this->ARC4($checksum);
                    $file_content_compressed = $this->ARC4($file_content_compressed);
                }
                $file_size_compressed = mb_strlen($file_content_compressed, '8bit');

                $res = "\n$id 0 obj\n<</Params <</Size $file_size_uncompressed /CheckSum ($checksum) >>" .
                    " /Type/EmbeddedFile /Filter/FlateDecode" .
                    " /Length $file_size_compressed >> stream\n$file_content_compressed\nendstream\nendobj";

                return $res;
        }

        return null;
    }

    
    public function enablePdfACompliance()
    {
        $this->pdfa = true;

        $iccProfilePath = __DIR__ . '/res/sRGB2014.icc';
        $this->o_catalog($this->catalogId, 'outputIntents', [
            'iccProfileData' => file_get_contents($iccProfilePath),
            'iccProfileName' => basename($iccProfilePath),
            'colorComponentsCount' => '3',
        ]);
    }

    
    function getXmpMetadata()
    {
        $md = <<<EOT
<?xpacket begin="\xEF\xBB\xBF" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">

<rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" rdf:about="">
<pdfaid:part>3</pdfaid:part>
<pdfaid:conformance>B</pdfaid:conformance>
</rdf:Description>

<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" rdf:about="">
EOT;

        $info = $this->objects[$this->infoObject]["info"];

        if (isset($info['Title'])) {
            $md .= "\n<dc:title><rdf:Alt><rdf:li xml:lang=\"x-default\">";
            $md .= htmlspecialchars($info['Title'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Alt></dc:title>";
        }

        if (isset($info['Author'])) {
            $md .= "\n<dc:creator><rdf:Seq><rdf:li>";
            $md .= htmlspecialchars($info['Author'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Seq></dc:creator>";
        }

        if (isset($info['Subject'])) {
            $md .= "\n<dc:description><rdf:Alt><rdf:li xml:lang=\"x-default\">";
            $md .= htmlspecialchars($info['Subject'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Alt></dc:description>";
        }

        $md .= "\n</rdf:Description>";
        $md .= "\n<rdf:Description xmlns:pdf=\"http://ns.adobe.com/pdf/1.3/\" rdf:about=\"\">";

        if (isset($info['Producer'])) {
            $md .= "\n<pdf:Producer>";
            $md .= htmlspecialchars($info['Producer'], ENT_XML1, 'UTF-8');
            $md .= "</pdf:Producer>";
        }

        if (isset($info['Keywords'])) {
            $md .= "\n<pdf:Keywords>";
            $md .= htmlspecialchars($info['Keywords'], ENT_XML1, 'UTF-8');
            $md .= "</pdf:Keywords>";
        }

        $md .= "\n</rdf:Description>";
        $md .= "\n<rdf:Description xmlns:xmp=\"http://ns.adobe.com/xap/1.0/\" rdf:about=\"\">";

        if (isset($info['Creator'])) {
            $md .= "\n<xmp:CreatorTool>";
            $md .= htmlspecialchars($info['Creator'], ENT_XML1, 'UTF-8');
            $md .= "</xmp:CreatorTool>";
        }

        if (isset($info['CreationDate']) && $date = $this->parsePdfDate($info['CreationDate'])) {
            $md .= "\n<xmp:CreateDate>";
            $md .= $date->format("Y-m-d\TH:i:sP");
            $md .= "</xmp:CreateDate>";
        }

        if (isset($info['ModDate']) && $date = $this->parsePdfDate($info['ModDate'])) {
            $md .= "\n<xmp:ModifyDate>";
            $md .= $date->format("Y-m-d\TH:i:sP");
            $md .= "</xmp:ModifyDate>";
        }

        $md .= "\n</rdf:Description>\n</rdf:RDF>\n</x:xmpmeta>\n<?xpacket end=\"w\"?>";

        return $md;
    }

    
    function parsePdfDate($date)
    {
        $formats = [
            "Y",
            "Ym",
            "Ymd",
            "YmdH",
            "YmdHi",
            "YmdHis",
            "YmdHisO",
        ];

        $date = substr($date, 2);
        $date = str_replace("'", "", $date);

        if ($i = strpos($date, "Z")) {
            $date = substr($date, 0, $i + 1);
        }

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $date, new \DateTimeZone("UTC"));

            if ($parsedDate) return $parsedDate;
        }

        return false;
    }

    

    
    function md5_16($string)
    {
        $tmp = md5($string);
        $out = '';
        for ($i = 0; $i <= 30; $i = $i + 2) {
            $out .= chr(hexdec(substr($tmp, $i, 2)));
        }

        return $out;
    }

    
    function encryptInit($id)
    {
        $tmp = $this->encryptionKey;
        $hex = dechex($id);
        if (mb_strlen($hex, '8bit') < 6) {
            $hex = substr('000000', 0, 6 - mb_strlen($hex, '8bit')) . $hex;
        }
        $tmp .= chr(hexdec(substr($hex, 4, 2)))
            . chr(hexdec(substr($hex, 2, 2)))
            . chr(hexdec(substr($hex, 0, 2)))
            . chr(0)
            . chr(0)
        ;
        $key = $this->md5_16($tmp);
        $this->ARC4_init(substr($key, 0, 10));
    }

    
    function ARC4_init($key = '')
    {
        $this->arc4 = '';

        
        if (mb_strlen($key, '8bit') == 0) {
            return;
        }

        $k = '';
        while (mb_strlen($k, '8bit') < 256) {
            $k .= $key;
        }

        $k = substr($k, 0, 256);
        for ($i = 0; $i < 256; $i++) {
            $this->arc4 .= chr($i);
        }

        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $t = $this->arc4[$i];
            $j = ($j + ord($t) + ord($k[$i])) % 256;
            $this->arc4[$i] = $this->arc4[$j];
            $this->arc4[$j] = $t;
        }
    }

    
    function ARC4($text)
    {
        $len = mb_strlen($text, '8bit');
        $a = 0;
        $b = 0;
        $c = $this->arc4;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $a = ($a + 1) % 256;
            $t = $c[$a];
            $b = ($b + ord($t)) % 256;
            $c[$a] = $c[$b];
            $c[$b] = $t;
            $k = ord($c[(ord($c[$a]) + ord($c[$b])) % 256]);
            $out .= chr(ord($text[$i]) ^ $k);
        }

        return $out;
    }

    

    
    function addLink($url, $x0, $y0, $x1, $y1)
    {
        $this->numObj++;
        $info = ['type' => 'link', 'url' => $url, 'rect' => [$x0, $y0, $x1, $y1]];
        $this->o_annotation($this->numObj, 'new', $info);
    }

    
    function addInternalLink($label, $x0, $y0, $x1, $y1)
    {
        $this->numObj++;
        $info = ['type' => 'ilink', 'label' => $label, 'rect' => [$x0, $y0, $x1, $y1]];
        $this->o_annotation($this->numObj, 'new', $info);
    }

    
    function setEncryption($userPass = '', $ownerPass = '', $pc = [])
    {
        $p = bindec("11000000");

        $options = ['print' => 4, 'modify' => 8, 'copy' => 16, 'add' => 32];

        foreach ($pc as $k => $v) {
            if ($v && isset($options[$k])) {
                $p += $options[$k];
            } else {
                if (isset($options[$v])) {
                    $p += $options[$v];
                }
            }
        }

        
        if ($this->arc4_objnum == 0) {
            
            $this->numObj++;
            if (mb_strlen($ownerPass) == 0) {
                $ownerPass = $userPass;
            }

            $this->o_encryption($this->numObj, 'new', ['user' => $userPass, 'owner' => $ownerPass, 'p' => $p]);
        }
    }

    
    function checkAllHere()
    {
    }

    
    function output($debug = false)
    {
        if ($debug) {
            
            $this->options['compression'] = false;
        }

        if ($this->javascript) {
            $this->numObj++;

            $js_id = $this->numObj;
            $this->o_embedjs($js_id, 'new');
            $this->o_javascript(++$this->numObj, 'new', $this->javascript);

            $id = $this->catalogId;

            $this->o_indirect_references($this->indirectReferenceId, 'add', ['JavaScript' => $js_id]);
        }

        if ($this->pdfa) {
            $this->o_catalog($this->catalogId, 'metadata', $this->getXmpMetadata());
        }

        if ($this->fileIdentifier === '') {
            $tmp = implode('', $this->objects[$this->infoObject]['info']);
            $this->fileIdentifier = md5('DOMPDF' . __FILE__ . $tmp . microtime() . mt_rand());
        }

        if ($this->arc4_objnum) {
            $this->o_encryption($this->arc4_objnum, 'keys');
            $this->ARC4_init($this->encryptionKey);
        }

        $this->checkAllHere();

        $xref = [];
        $content = '%PDF-' . self::PDF_VERSION;

        if ($this->pdfa) {
            
            $content .= "\n%" . chr(rand(128, 255)) . chr(rand(128, 255)) . chr(rand(128, 255)) . chr(rand(128, 255));
        }

        $pos = mb_strlen($content, '8bit');

        
        foreach ($this->objects as $k => $v) {
            if ($v['t'] === 'font') {
                $this->o_font($k, 'add');
            }
        }

        foreach ($this->objects as $k => $v) {
            $tmp = 'o_' . $v['t'];
            $cont = $this->$tmp($k, 'out');
            $content .= $cont;
            $xref[] = $pos + 1; 
            $pos += mb_strlen($cont, '8bit');
        }

        $content .= "\nxref\n0 " . (count($xref) + 1) . "\n0000000000 65535 f \n";

        foreach ($xref as $p) {
            $content .= str_pad($p, 10, "0", STR_PAD_LEFT) . " 00000 n \n";
        }

        $content .= "trailer\n<<\n" .
            '/Size ' . (count($xref) + 1) . "\n" .
            '/Root 1 0 R' . "\n" .
            '/Info ' . $this->infoObject . " 0 R\n"
        ;

        
        if ($this->arc4_objnum > 0) {
            $content .= '/Encrypt ' . $this->arc4_objnum . " 0 R\n";
        }

        $content .= '/ID[<' . $this->fileIdentifier . '><' . $this->fileIdentifier . ">]\n";

        
        $pos++;

        $content .= ">>\nstartxref\n$pos\n%%EOF\n";

        if (count($this->byteRange) > 0) {
            foreach ($this->byteRange as $k => $v) {
                $tmp = 'o_' . $v['t'];
                $this->$tmp($k, 'byterange', ['content' => &$content]);
            }
        }

        return $content;
    }

    
    private function newDocument($pageSize = [0, 0, 612, 792])
    {
        $this->numObj = 0;
        $this->objects = [];

        $this->numObj++;
        $this->o_catalog($this->numObj, 'new');

        $this->numObj++;
        $this->o_outlines($this->numObj, 'new');

        $this->numObj++;
        $this->o_pages($this->numObj, 'new');

        $this->o_pages($this->numObj, 'mediaBox', $pageSize);
        $this->currentNode = 3;

        $this->numObj++;
        $this->o_procset($this->numObj, 'new');

        $this->numObj++;
        $this->o_info($this->numObj, 'new');

        $this->numObj++;
        $this->o_page($this->numObj, 'new');

        
        
        $this->firstPageId = $this->currentContents;
    }

    
    private function openFont($font)
    {
        
        $name = basename($font);
        $dir = dirname($font);

        $fontcache = $this->fontcache;
        if ($fontcache == '') {
            $fontcache = $dir;
        }

        
        
        
        
        

        $this->addMessage("openFont: $font - $name");

        if (!$this->isUnicode || in_array(mb_strtolower(basename($name)), self::$coreFonts)) {
            $metrics_name = "$name.afm";
        } else {
            $metrics_name = "$name.ufm";
        }

        $cache_name = "$metrics_name.json";
        $this->addMessage("metrics: $metrics_name, cache: $cache_name");

        if (file_exists($fontcache . '/' . $cache_name)) {
            $this->addMessage("openFont: json metrics file exists $fontcache/$cache_name");
            $cached_font_info = json_decode(file_get_contents($fontcache . '/' . $cache_name), true);
            if (!isset($cached_font_info['_version_']) || $cached_font_info['_version_'] != $this->fontcacheVersion) {
                $this->addMessage('openFont: font cache is out of date, regenerating');
            } else {
                $this->fonts[$font] = $cached_font_info;
            }
        }

        if (!isset($this->fonts[$font]) && file_exists("$dir/$metrics_name")) {
            
            $this->addMessage("openFont: build php file from $dir/$metrics_name");
            $data = [];

            
            $data['codeToName'] = [];

            
            
            $data['isUnicode'] = (strtolower(substr($metrics_name, -3)) !== 'afm');

            $cidtogid = '';
            if ($data['isUnicode']) {
                $cidtogid = str_pad('', 256 * 256 * 2, "\x00");
            }

            $file = file("$dir/$metrics_name");

            foreach ($file as $rowA) {
                $row = trim($rowA);
                $pos = strpos($row, ' ');

                if ($pos) {
                    
                    $key = substr($row, 0, $pos);
                    switch ($key) {
                        case 'FontName':
                        case 'FullName':
                        case 'FamilyName':
                        case 'PostScriptName':
                        case 'Weight':
                        case 'ItalicAngle':
                        case 'IsFixedPitch':
                        case 'CharacterSet':
                        case 'UnderlinePosition':
                        case 'UnderlineThickness':
                        case 'Version':
                        case 'EncodingScheme':
                        case 'CapHeight':
                        case 'XHeight':
                        case 'Ascender':
                        case 'Descender':
                        case 'StdHW':
                        case 'StdVW':
                        case 'StartCharMetrics':
                        case 'FontHeightOffset': 
                            $data[$key] = trim(substr($row, $pos));
                            break;

                        case 'FontBBox':
                            $data[$key] = explode(' ', trim(substr($row, $pos)));
                            break;

                        
                        case 'C': 
                            $bits = explode(';', trim($row));
                            $dtmp = ['C' => null, 'N' => null, 'WX' => null, 'B' => []];

                            foreach ($bits as $bit) {
                                $bits2 = explode(' ', trim($bit));
                                if (mb_strlen($bits2[0], '8bit') == 0) {
                                    continue;
                                }

                                if (count($bits2) > 2) {
                                    $dtmp[$bits2[0]] = [];
                                    for ($i = 1; $i < count($bits2); $i++) {
                                        $dtmp[$bits2[0]][] = $bits2[$i];
                                    }
                                } else {
                                    if (count($bits2) == 2) {
                                        $dtmp[$bits2[0]] = $bits2[1];
                                    }
                                }
                            }

                            $c = (int)$dtmp['C'];
                            $n = $dtmp['N'];
                            $width = floatval($dtmp['WX']);

                            if ($c >= 0) {
                                if (!ctype_xdigit($n) || $c != hexdec($n)) {
                                    $data['codeToName'][$c] = $n;
                                }
                                $data['C'][$c] = $width;
                            } elseif (isset($n)) {
                                $data['C'][$n] = $width;
                            }

                            if (!isset($data['MissingWidth']) && $c === -1 && $n === '.notdef') {
                                $data['MissingWidth'] = $width;
                            }

                            break;

                        
                        case 'U': 
                            if (!$data['isUnicode']) {
                                break;
                            }

                            $bits = explode(';', trim($row));
                            $dtmp = ['G' => null, 'N' => null, 'U' => null, 'WX' => null];

                            foreach ($bits as $bit) {
                                $bits2 = explode(' ', trim($bit));
                                if (mb_strlen($bits2[0], '8bit') === 0) {
                                    continue;
                                }

                                if (count($bits2) > 2) {
                                    $dtmp[$bits2[0]] = [];
                                    for ($i = 1; $i < count($bits2); $i++) {
                                        $dtmp[$bits2[0]][] = $bits2[$i];
                                    }
                                } else {
                                    if (count($bits2) == 2) {
                                        $dtmp[$bits2[0]] = $bits2[1];
                                    }
                                }
                            }

                            $c = (int)$dtmp['U'];
                            $n = $dtmp['N'];
                            $glyph = $dtmp['G'];
                            $width = floatval($dtmp['WX']);

                            if ($c >= 0) {
                                
                                if ($c >= 0 && $c < 0xFFFF && $glyph) {
                                    $cidtogid[$c * 2] = chr($glyph >> 8);
                                    $cidtogid[$c * 2 + 1] = chr($glyph & 0xFF);
                                }

                                if (!ctype_xdigit($n) || $c != hexdec($n)) {
                                    $data['codeToName'][$c] = $n;
                                }
                                $data['C'][$c] = $width;
                            } elseif (isset($n)) {
                                $data['C'][$n] = $width;
                            }

                            if (!isset($data['MissingWidth']) && $c === -1 && $n === '.notdef') {
                                $data['MissingWidth'] = $width;
                            }

                            break;

                        case 'KPX':
                            break; 
                            
                            
                    }
                }
            }

            if ($this->compressionReady && $this->options['compression']) {
                
                $data['CIDtoGID_Compressed'] = true;
                $cidtogid = gzcompress($cidtogid, 6);
            }
            $data['CIDtoGID'] = base64_encode($cidtogid);
            $data['_version_'] = $this->fontcacheVersion;
            $this->fonts[$font] = $data;

            
            
            if (is_dir($fontcache) && is_writable($fontcache)) {
                file_put_contents("$fontcache/$cache_name", json_encode($data, JSON_PRETTY_PRINT));
            }
            $data = null;
        }

        if (!isset($this->fonts[$font])) {
            $this->addMessage("openFont: no font file found for $font. Do you need to run load_font.php?");
        }
    }

    
    function selectFont($fontName, $encoding = '', $set = true, $isSubsetting = true)
    {
        $fontName = (string) $fontName;
        $ext = substr($fontName, -4);
        if ($ext === '.afm' || $ext === '.ufm') {
            $fontName = substr($fontName, 0, mb_strlen($fontName) - 4);
        }
        if ($fontName === '') {
            return $this->currentFontNum;
        }

        if (!isset($this->fonts[$fontName])) {
            $this->addMessage("selectFont: selecting - $fontName - $encoding, $set");

            
            $this->openFont($fontName);

            if (isset($this->fonts[$fontName])) {
                $this->numObj++;
                $this->numFonts++;

                $font = &$this->fonts[$fontName];

                $name = basename($fontName);
                $options = ['name' => $name, 'fontFileName' => $fontName, 'isSubsetting' => $isSubsetting];

                if (is_array($encoding)) {
                    
                    if (isset($encoding['encoding'])) {
                        $options['encoding'] = $encoding['encoding'];
                    }

                    if (isset($encoding['differences'])) {
                        $options['differences'] = $encoding['differences'];
                    }
                } else {
                    if (mb_strlen($encoding, '8bit')) {
                        
                        $options['encoding'] = $encoding;
                    }
                }

                $this->o_font($this->numObj, 'new', $options);

                if (file_exists("$fontName.ttf")) {
                    $fileSuffix = 'ttf';
                } elseif (file_exists("$fontName.TTF")) {
                    $fileSuffix = 'TTF';
                } elseif (file_exists("$fontName.pfb")) {
                    $fileSuffix = 'pfb';
                } elseif (file_exists("$fontName.PFB")) {
                    $fileSuffix = 'PFB';
                } else {
                    $fileSuffix = '';
                }

                $font['fileSuffix'] = $fileSuffix;

                $font['fontNum'] = $this->numFonts;
                $font['isSubsetting'] = $isSubsetting && $font['isUnicode'] && strtolower($fileSuffix) === 'ttf';

                
                
                if (isset($options['differences'])) {
                    $font['differences'] = $options['differences'];
                }
            }
        }

        if ($set && isset($this->fonts[$fontName])) {
            
            $this->currentBaseFont = $fontName;

            
            
            $this->currentFont = $this->currentBaseFont;
            $this->currentFontNum = $this->fonts[$this->currentFont]['fontNum'];
        }

        return $this->currentFontNum;
    }

    
    private function setCurrentFont()
    {
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        $this->currentFont = $this->currentBaseFont;
        $this->currentFontNum = $this->fonts[$this->currentFont]['fontNum'];
        
    }

    
    function getFirstPageId()
    {
        return $this->firstPageId;
    }

    
    private function addContent($content)
    {
        $this->objects[$this->currentContents]['c'] .= $content;
    }

    
    function setColor($color, $force = false)
    {
        $new_color = [$color[0], $color[1], $color[2], isset($color[3]) ? $color[3] : null];

        if (!$force && $this->currentColor == $new_color) {
            return;
        }

        if (isset($new_color[3])) {
            if ($this->pdfa) {
                throw new \Exception("CMYK colors are not supported when generating a document in PDF/A mode");
            }
            $this->currentColor = $new_color;
            $this->addContent(vsprintf("\n%.3F %.3F %.3F %.3F k", $this->currentColor));
        } else {
            if (isset($new_color[2])) {
                $this->currentColor = $new_color;
                $this->addContent(vsprintf("\n%.3F %.3F %.3F rg", $this->currentColor));
            }
        }
    }

    
    function setFillRule($fillRule)
    {
        if (!in_array($fillRule, ["nonzero", "evenodd"])) {
            return;
        }

        $this->fillRule = $fillRule;
    }

    
    function setStrokeColor($color, $force = false)
    {
        $new_color = [$color[0], $color[1], $color[2], isset($color[3]) ? $color[3] : null];

        if (!$force && $this->currentStrokeColor == $new_color) {
            return;
        }

        if (isset($new_color[3])) {
            if ($this->pdfa) {
                throw new \Exception("CMYK colors are not supported when generating a document in PDF/A mode");
            }
            $this->currentStrokeColor = $new_color;
            $this->addContent(vsprintf("\n%.3F %.3F %.3F %.3F K", $this->currentStrokeColor));
        } else {
            if (isset($new_color[2])) {
                $this->currentStrokeColor = $new_color;
                $this->addContent(vsprintf("\n%.3F %.3F %.3F RG", $this->currentStrokeColor));
            }
        }
    }

    
    function setGraphicsState($parameters)
    {
        
        if (($gstate = array_search($parameters, $this->gstates)) === false) {
            $this->numObj++;
            $this->o_extGState($this->numObj, 'new', $parameters);
            $gstate = $this->numStates;
            $this->gstates[$gstate] = $parameters;
        }
        $this->addContent("\n/GS$gstate gs");
    }

    
    public function setLineTransparency(string $mode, float $opacity): void
    {
        static $blendModes = [
            "Normal",
            "Multiply",
            "Screen",
            "Overlay",
            "Darken",
            "Lighten",
            "ColorDogde",
            "ColorBurn",
            "HardLight",
            "SoftLight",
            "Difference",
            "Exclusion"
        ];

        if (!in_array($mode, $blendModes, true)) {
            $mode = "Normal";
        }

        $newState = [
            "mode"    => $mode,
            "opacity" => $opacity
        ];

        if ($newState === $this->currentLineTransparency) {
            return;
        }

        $this->currentLineTransparency = $newState;

        $options = [
            "BM" => "/$mode",
            "CA" => $opacity
        ];

        $this->setGraphicsState($options);
    }

    
    public function setFillTransparency(string $mode, float $opacity): void
    {
        static $blendModes = [
            "Normal",
            "Multiply",
            "Screen",
            "Overlay",
            "Darken",
            "Lighten",
            "ColorDogde",
            "ColorBurn",
            "HardLight",
            "SoftLight",
            "Difference",
            "Exclusion"
        ];

        if (!in_array($mode, $blendModes, true)) {
            $mode = "Normal";
        }

        $newState = [
            "mode"    => $mode,
            "opacity" => $opacity
        ];

        if ($newState === $this->currentFillTransparency) {
            return;
        }

        $this->currentFillTransparency = $newState;

        $options = [
            "BM" => "/$mode",
            "ca" => $opacity,
        ];

        $this->setGraphicsState($options);
    }

    
    function line($x1, $y1, $x2, $y2, $stroke = true)
    {
        $this->addContent(sprintf("\n%.3F %.3F m %.3F %.3F l", $x1, $y1, $x2, $y2));

        if ($stroke) {
            $this->addContent(' S');
        }
    }

    
    function curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
    {
        
        
        $this->addContent(
            sprintf("\n%.3F %.3F m %.3F %.3F %.3F %.3F %.3F %.3F c S", $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
        );
    }

    
    function partEllipse($x0, $y0, $astart, $afinish, $r1, $r2 = 0, $angle = 0, $nSeg = 8)
    {
        $this->ellipse($x0, $y0, $r1, $r2, $angle, $nSeg, $astart, $afinish, false);
    }

    
    function filledEllipse($x0, $y0, $r1, $r2 = 0, $angle = 0, $nSeg = 8, $astart = 0, $afinish = 360)
    {
        $this->ellipse($x0, $y0, $r1, $r2, $angle, $nSeg, $astart, $afinish, true, true);
    }

    
    function lineTo($x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F l", $x, $y));
    }

    
    function moveTo($x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F m", $x, $y));
    }

    
    function curveTo($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F %.3F %.3F c", $x1, $y1, $x2, $y2, $x3, $y3));
    }

    
    function quadTo($cpx, $cpy, $x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F v", $cpx, $cpy, $x, $y));
    }

    function closePath()
    {
        $this->addContent(' h');
    }

    function endPath()
    {
        $this->addContent(' n');
    }

    
    function ellipse(
        $x0,
        $y0,
        $r1,
        $r2 = 0,
        $angle = 0,
        $nSeg = 8,
        $astart = 0,
        $afinish = 360,
        $close = true,
        $fill = false,
        $stroke = true,
        $incomplete = false
    ) {
        if ($r1 == 0) {
            return;
        }

        if ($r2 == 0) {
            $r2 = $r1;
        }

        if ($nSeg < 2) {
            $nSeg = 2;
        }

        $astart = deg2rad((float)$astart);
        $afinish = deg2rad((float)$afinish);
        $totalAngle = $afinish - $astart;

        $dt = $totalAngle / $nSeg;
        $dtm = $dt / 3;

        if ($angle != 0) {
            $a = -1 * deg2rad((float)$angle);

            $this->addContent(
                sprintf("\n q %.3F %.3F %.3F %.3F %.3F %.3F cm", cos($a), -sin($a), sin($a), cos($a), $x0, $y0)
            );

            $x0 = 0;
            $y0 = 0;
        }

        $t1 = $astart;
        $a0 = $x0 + $r1 * cos($t1);
        $b0 = $y0 + $r2 * sin($t1);
        $c0 = -$r1 * sin($t1);
        $d0 = $r2 * cos($t1);

        if (!$incomplete) {
            $this->addContent(sprintf("\n%.3F %.3F m ", $a0, $b0));
        }

        for ($i = 1; $i <= $nSeg; $i++) {
            
            $t1 = $i * $dt + $astart;
            $a1 = $x0 + $r1 * cos($t1);
            $b1 = $y0 + $r2 * sin($t1);
            $c1 = -$r1 * sin($t1);
            $d1 = $r2 * cos($t1);

            $this->addContent(
                sprintf(
                    "\n%.3F %.3F %.3F %.3F %.3F %.3F c",
                    ($a0 + $c0 * $dtm),
                    ($b0 + $d0 * $dtm),
                    ($a1 - $c1 * $dtm),
                    ($b1 - $d1 * $dtm),
                    $a1,
                    $b1
                )
            );

            $a0 = $a1;
            $b0 = $b1;
            $c0 = $c1;
            $d0 = $d1;
        }

        if (!$incomplete) {
            if ($fill) {
                $this->addContent(' f');
            }

            if ($stroke) {
                if ($close) {
                    $this->addContent(' s'); 
                } else {
                    $this->addContent(' S');
                }
            }
        }

        if ($angle != 0) {
            $this->addContent(' Q');
        }
    }

    
    function setLineStyle($width = 1, $cap = '', $join = '', $dash = '', $phase = 0)
    {
        
        $string = '';

        if ($width > 0) {
            $string .= "$width w";
        }

        $ca = ['butt' => 0, 'round' => 1, 'square' => 2];

        if (isset($ca[$cap])) {
            $string .= " $ca[$cap] J";
        }

        $ja = ['miter' => 0, 'round' => 1, 'bevel' => 2];

        if (isset($ja[$join])) {
            $string .= " $ja[$join] j";
        }

        if (is_array($dash)) {
            $string .= ' [ ' . implode(' ', $dash) . " ] $phase d";
        }

        if ($string === $this->currentLineStyle) {
            return;
        }

        $this->currentLineStyle = $string;
        $this->addContent("\n$string");
    }

    
    public function polygon(array $p, bool $fill = false): void
    {
        $this->addContent(sprintf("\n%.3F %.3F m ", $p[0], $p[1]));

        $n = count($p);
        for ($i = 2; $i < $n; $i = $i + 2) {
            $this->addContent(sprintf("%.3F %.3F l ", $p[$i], $p[$i + 1]));
        }

        if ($fill) {
            $this->addContent(' f');
        } else {
            $this->addContent(' S');
        }
    }

    
    function filledRectangle($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re f", $x1, $y1, $width, $height));
    }

    
    function rectangle($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re S", $x1, $y1, $width, $height));
    }

    
    function rect($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re", $x1, $y1, $width, $height));
    }

    function stroke(bool $close = false)
    {
        $this->addContent("\n" . ($close ? "s" : "S"));
    }

    function fill()
    {
        $this->addContent("\nf" . ($this->fillRule === "evenodd" ? "*" : ""));
    }

    function fillStroke(bool $close = false)
    {
        $this->addContent("\n" . ($close ? "b" : "B") . ($this->fillRule === "evenodd" ? "*" : ""));
    }

    
    function addXObject($subtype, $x, $y, $w, $h)
    {
        $id = ++$this->numObj;
        $this->o_xobject($id, 'new', ['Subtype' => $subtype, 'bbox' => [$x, $y, $w, $h]]);
        return $id;
    }

    
    function setXObjectResource($numXObject, $type, $options)
    {
        if (in_array($type, ['procset', 'font', 'xObject'])) {
            $this->o_xobject($numXObject, $type, $options);
        }
    }

    
    function addSignature($signcert, $privkey, $password = '', $name = null, $location = null, $reason = null, $contactinfo = null) {
        $sigId = ++$this->numObj;
        $this->o_sig($sigId, 'new', [
          'SignCert' => $signcert,
          'PrivKey' => $privkey,
          'Password' => $password,
          'Name' => $name,
          'Location' => $location,
          'Reason' => $reason,
          'ContactInfo' => $contactinfo
        ]);

        return $sigId;
    }

    
    public function addFormField($type, $name, $x0, $y0, $x1, $y1, $ff = 0, $size = 10.0, $color = [0, 0, 0])
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $color = implode(' ', $color) . ' rg';

        $currentFontNum = $this->currentFontNum;
        $font = array_filter(
            $this->objects[$this->currentNode]['info']['fonts'],
            function ($item) use ($currentFontNum) { return $item['fontNum'] == $currentFontNum; }
        );

        $this->o_acroform($this->acroFormId, 'font',
          ['objNum' => $font[0]['objNum'], 'fontNum' => $font[0]['fontNum']]);

        $fieldId = ++$this->numObj;
        $this->o_field($fieldId, 'new', [
          'rect' => [$x0, $y0, $x1, $y1],
          'F' => 4,
          'FT' => "/$type",
          'T' => $name,
          'Ff' => $ff,
          'pageid' => $this->currentPage,
          'da' => "$color /F$this->currentFontNum " . sprintf('%.1F Tf ', $size)
        ]);

        return $fieldId;
    }

    
    public function setFormFieldValue($numFieldObj, $value)
    {
        $this->o_field($numFieldObj, 'set', ['value' => $value]);
    }

    
    public function setFormFieldRefValue($numFieldObj, $numObj)
    {
        $this->o_field($numFieldObj, 'set', ['refvalue' => $numObj]);
    }

    
    public function setFormFieldAppearance($numFieldObj, $normalNumObj, $rolloverNumObj = null, $downNumObj = null)
    {
        $appearance['N'] = $normalNumObj;

        if ($rolloverNumObj !== null) {
            $appearance['R'] = $rolloverNumObj;
        }

        if ($downNumObj !== null) {
            $appearance['D'] = $downNumObj;
        }

        $this->o_field($numFieldObj, 'set', ['appearance' => $appearance]);
    }

    
    public function setFormFieldOpt($numFieldObj, $value)
    {
        $this->o_field($numFieldObj, 'set', ['options' => $value]);
    }

    
    public function addForm($sigFlags = 0, $needAppearances = false)
    {
        $this->acroFormId = ++$this->numObj;
        $this->o_acroform($this->acroFormId, 'new', [
          'NeedAppearances' => $needAppearances ? 'true' : 'false',
          'SigFlags' => $sigFlags
        ]);
    }

    
    function save()
    {
        $this->addContent("\nq");
    }

    
    function restore()
    {
        
        
        $this->currentColor = null;
        $this->currentStrokeColor = null;
        $this->currentLineStyle = '';
        $this->currentLineTransparency = null;
        $this->currentFillTransparency = null;
        $this->addContent("\nQ");
    }

    
    function clippingRectangle($x1, $y1, $width, $height)
    {
        $this->save();
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re W n", $x1, $y1, $width, $height));
    }

    
    function clippingRectangleRounded($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL)
    {
        $this->save();

        
        $this->addContent(sprintf("\n%.3F %.3F m ", $x1, $y1 - $rTL + $h));

        
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1, $y1 + $rBL));

        
        $this->ellipse($x1 + $rBL, $y1 + $rBL, $rBL, 0, 0, 8, 180, 270, false, false, false, true);

        
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $w - $rBR, $y1));

        
        $this->ellipse($x1 + $w - $rBR, $y1 + $rBR, $rBR, 0, 0, 8, 270, 360, false, false, false, true);

        
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $w, $y1 + $h - $rTR));

        
        $this->ellipse($x1 + $w - $rTR, $y1 + $h - $rTR, $rTR, 0, 0, 8, 0, 90, false, false, false, true);

        
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $rTL, $y1 + $h));

        
        $this->ellipse($x1 + $rTL, $y1 + $h - $rTL, $rTL, 0, 0, 8, 90, 180, false, false, false, true);

        
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $rBL, $y1));

        
        $this->addContent(" W n");
    }

    
    public function clippingPolygon(array $p): void
    {
        $this->save();

        $this->addContent(sprintf("\n%.3F %.3F m ", $p[0], $p[1]));

        $n = count($p);
        for ($i = 2; $i < $n; $i = $i + 2) {
            $this->addContent(sprintf("%.3F %.3F l ", $p[$i], $p[$i + 1]));
        }

        $this->addContent("W n");
    }

    
    function clippingEnd()
    {
        $this->restore();
    }

    
    function scale($s_x, $s_y, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $tm = [
            $s_x,
            0,
            0,
            $s_y,
            $x * (1 - $s_x),
            $y * (1 - $s_y)
        ];

        $this->transform($tm);
    }

    
    function translate($t_x, $t_y)
    {
        $tm = [
            1,
            0,
            0,
            1,
            $t_x,
            -$t_y
        ];

        $this->transform($tm);
    }

    
    function rotate($angle, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $a = deg2rad($angle);
        $cos_a = cos($a);
        $sin_a = sin($a);

        $tm = [
            $cos_a,
            -$sin_a,
            $sin_a,
            $cos_a,
            $x - $sin_a * $y - $cos_a * $x,
            $y - $cos_a * $y + $sin_a * $x,
        ];

        $this->transform($tm);
    }

    
    function skew($angle_x, $angle_y, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $tan_x = tan(deg2rad($angle_x));
        $tan_y = tan(deg2rad($angle_y));

        $tm = [
            1,
            -$tan_y,
            -$tan_x,
            1,
            $tan_x * $y,
            $tan_y * $x,
        ];

        $this->transform($tm);
    }

    
    function transform($tm)
    {
        $this->addContent(vsprintf("\n %.3F %.3F %.3F %.3F %.3F %.3F cm", $tm));
    }

    
    function newPage($insert = 0, $id = 0, $pos = 'after')
    {
        
        

        if ($this->nStateStack) {
            for ($i = $this->nStateStack; $i >= 1; $i--) {
                $this->restoreState($i);
            }
        }

        $this->numObj++;

        if ($insert) {
            
            
            $rid = $this->objects[$id]['onPage'];
            $opt = ['rid' => $rid, 'pos' => $pos];
            $this->o_page($this->numObj, 'new', $opt);
        } else {
            $this->o_page($this->numObj, 'new');
        }

        
        if ($this->nStateStack) {
            for ($i = 1; $i <= $this->nStateStack; $i++) {
                $this->saveState($i);
            }
        }

        
        if (isset($this->currentColor)) {
            $this->setColor($this->currentColor, true);
        }

        if (isset($this->currentStrokeColor)) {
            $this->setStrokeColor($this->currentStrokeColor, true);
        }

        
        if ($this->currentLineStyle !== '') {
            $this->addContent("\n$this->currentLineStyle");
        }

        
        return $this->currentContents;
    }

    
    function stream($filename = "document.pdf", $options = [])
    {
        if (headers_sent()) {
            die("Unable to stream pdf: headers already sent");
        }

        if (!isset($options["compress"])) $options["compress"] = true;
        if (!isset($options["Attachment"])) $options["Attachment"] = true;

        $debug = !$options['compress'];
        $tmp = ltrim($this->output($debug));

        header("Cache-Control: private");
        header("Content-Type: application/pdf");
        header("Content-Length: " . mb_strlen($tmp, "8bit"));

        $filename = str_replace(["\n", "'"], "", basename($filename, ".pdf")) . ".pdf";
        $attachment = $options["Attachment"] ? "attachment" : "inline";

        $encoding = mb_detect_encoding($filename);
        $fallbackfilename = mb_convert_encoding($filename, "ISO-8859-1", $encoding);
        $fallbackfilename = str_replace("\"", "", $fallbackfilename);
        $encodedfilename = rawurlencode($filename);

        $contentDisposition = "Content-Disposition: $attachment; filename=\"$fallbackfilename\"";
        if ($fallbackfilename !== $filename) {
            $contentDisposition .= "; filename*=UTF-8''$encodedfilename";
        }
        header($contentDisposition);

        echo $tmp;
        flush();
    }

    
    public function getFontHeight(float $size): float
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $font = $this->fonts[$this->currentFont];

        
        if (isset($font['Ascender']) && isset($font['Descender'])) {
            $h = $font['Ascender'] - $font['Descender'];
        } else {
            $h = $font['FontBBox'][3] - $font['FontBBox'][1];
        }

        
        
        if (isset($font['FontHeightOffset'])) {
            
            
            
            
            
            
            
            $h += (int)$font['FontHeightOffset'];
        }

        return $size * $h / 1000;
    }

    
    public function getFontXHeight(float $size): float
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $font = $this->fonts[$this->currentFont];

        
        if (isset($font['XHeight'])) {
            $xh = $font['Ascender'] - $font['Descender'];
        } else {
            $xh = $this->getFontHeight($size) / 2;
        }

        return $size * $xh / 1000;
    }

    
    public function getFontDescender(float $size): float
    {
        
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        
        $h = $this->fonts[$this->currentFont]['Descender'];

        return $size * $h / 1000;
    }

    
    function filterText($text, $bom = true, $convert_encoding = true)
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        if ($convert_encoding) {
            $cf = $this->currentFont;
            if (isset($this->fonts[$cf]) && $this->fonts[$cf]['isUnicode']) {
                $text = $this->utf8toUtf16BE($text, $bom);
            } else {
                
                $text = mb_convert_encoding($text, self::$targetEncoding, 'UTF-8');
            }
        } elseif ($bom) {
            $text = $this->utf8toUtf16BE($text, $bom);
        }

        
        return strtr($text, [')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r']);
    }

    
    function utf8toCodePointsArray(&$text)
    {
        $length = mb_strlen($text, '8bit'); 
        $unicode = []; 
        $bytes = []; 
        $numbytes = 1; 

        for ($i = 0; $i < $length; $i++) {
            $c = ord($text[$i]); 
            if (count($bytes) === 0) { 
                if ($c <= 0x7F) {
                    $unicode[] = $c; 
                    $numbytes = 1;
                } elseif (($c >> 0x05) === 0x06) { 
                    $bytes[] = ($c - 0xC0) << 0x06;
                    $numbytes = 2;
                } elseif (($c >> 0x04) === 0x0E) { 
                    $bytes[] = ($c - 0xE0) << 0x0C;
                    $numbytes = 3;
                } elseif (($c >> 0x03) === 0x1E) { 
                    $bytes[] = ($c - 0xF0) << 0x12;
                    $numbytes = 4;
                } else {
                    
                    $unicode[] = 0xFFFD;
                    $bytes = [];
                    $numbytes = 1;
                }
            } elseif (($c >> 0x06) === 0x02) { 
                $bytes[] = $c - 0x80;
                if (count($bytes) === $numbytes) {
                    
                    $c = $bytes[0];
                    for ($j = 1; $j < $numbytes; $j++) {
                        $c += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
                    }
                    if ((($c >= 0xD800) and ($c <= 0xDFFF)) or ($c >= 0x10FFFF)) {
                        
                        
                        
                        
                        $unicode[] = 0xFFFD; 
                    } else {
                        $unicode[] = $c; 
                    }
                    
                    $bytes = [];
                    $numbytes = 1;
                }
            } else {
                
                $unicode[] = 0xFFFD;
                $bytes = [];
                $numbytes = 1;
            }
        }

        return $unicode;
    }

    
    function utf8toUtf16BE(&$text, $bom = true)
    {
        $out = $bom ? "\xFE\xFF" : '';

        $unicode = $this->utf8toCodePointsArray($text);
        foreach ($unicode as $c) {
            if ($c === 0xFFFD) {
                $out .= "\xFF\xFD"; 
            } elseif ($c < 0x10000) {
                $out .= chr($c >> 0x08) . chr($c & 0xFF);
            } else {
                $c -= 0x10000;
                $w1 = 0xD800 | ($c >> 0x10);
                $w2 = 0xDC00 | ($c & 0x3FF);
                $out .= chr($w1 >> 0x08) . chr($w1 & 0xFF) . chr($w2 >> 0x08) . chr($w2 & 0xFF);
            }
        }

        return $out;
    }

    
    private function getTextPosition($x, $y, $angle, $size, $wa, $text)
    {
        
        $w = $this->getTextWidth($size, $text);

        
        $words = explode(' ', $text);
        $nspaces = count($words) - 1;
        $w += $wa * $nspaces;
        $a = deg2rad((float)$angle);

        return [cos($a) * $w + $x, -sin($a) * $w + $y];
    }

    
    function toUpper($matches)
    {
        return mb_strtoupper($matches[0]);
    }

    function concatMatches($matches)
    {
        $str = "";
        foreach ($matches as $match) {
            $str .= $match[0];
        }

        return $str;
    }

    
    function registerText($font, $text)
    {
        if (!$this->isUnicode || in_array(mb_strtolower(basename($font)), self::$coreFonts)) {
            return;
        }

        if (!isset($this->stringSubsets[$font])) {
            $base_subset = "\u{fffd}\u{fffe}\u{ffff}"; 
            $this->stringSubsets[$font] = $this->utf8toCodePointsArray($base_subset);
        }

        $this->stringSubsets[$font] = array_unique(
            array_merge($this->stringSubsets[$font], $this->utf8toCodePointsArray($text))
        );
    }

    
    function addText($x, $y, $size, $text, $angle = 0, $wordSpaceAdjust = 0, $charSpaceAdjust = 0, $smallCaps = false)
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $text = str_replace(["\r", "\n"], "", $text);

        
        
        
        

        
        
        

        
        

        
        if ($this->nCallback > 0) {
            for ($i = $this->nCallback; $i > 0; $i--) {
                
                $info = [
                    'x'         => $x,
                    'y'         => $y,
                    'angle'     => $angle,
                    'status'    => 'sol',
                    'p'         => $this->callback[$i]['p'],
                    'nCallback' => $this->callback[$i]['nCallback'],
                    'height'    => $this->callback[$i]['height'],
                    'descender' => $this->callback[$i]['descender']
                ];

                $func = $this->callback[$i]['f'];
                $this->$func($info);
            }
        }

        if ($angle == 0) {
            $this->addContent(sprintf("\nBT %.3F %.3F Td", $x, $y));
        } else {
            $a = deg2rad((float)$angle);
            $this->addContent(
                sprintf("\nBT %.3F %.3F %.3F %.3F %.3F %.3F Tm", cos($a), -sin($a), sin($a), cos($a), $x, $y)
            );
        }

        if ($wordSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tw", $wordSpaceAdjust));
        }

        if ($charSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tc", $charSpaceAdjust));
        }

        $len = mb_strlen($text);
        $start = 0;

        if ($start < $len) {
            $part = $text; 
            $place_text = $this->filterText($part, false);
            
            if ($this->fonts[$this->currentFont]['isUnicode'] && $wordSpaceAdjust != 0) {
                $space_scale = 1000 / $size;
                $place_text = str_replace("\x00\x20", "\x00\x20)\x00\x20" . (-round($space_scale * $wordSpaceAdjust)) . "\x00\x20(", $place_text);
            }
            $this->addContent(" /F$this->currentFontNum " . sprintf('%.1F Tf ', $size));
            $this->addContent(" [($place_text)] TJ");
        }

        if ($wordSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tw", 0));
        }

        if ($charSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tc", 0));
        }

        $this->addContent(' ET');

        
        if ($this->nCallback > 0) {
            for ($i = $this->nCallback; $i > 0; $i--) {
                
                $tmp = $this->getTextPosition($x, $y, $angle, $size, $wordSpaceAdjust, $text);
                $info = [
                    'x'         => $tmp[0],
                    'y'         => $tmp[1],
                    'angle'     => $angle,
                    'status'    => 'eol',
                    'p'         => $this->callback[$i]['p'],
                    'nCallback' => $this->callback[$i]['nCallback'],
                    'height'    => $this->callback[$i]['height'],
                    'descender' => $this->callback[$i]['descender']
                ];
                $func = $this->callback[$i]['f'];
                $this->$func($info);
            }
        }

        if ($this->fonts[$this->currentFont]['isSubsetting']) {
            $this->registerText($this->currentFont, $text);
        }
    }

    
    public function getTextWidth(float $size, string $text, float $wordSpacing = 0.0, float $charSpacing = 0.0): float
    {
        static $ord_cache = [];

        
        
        
        $store_currentTextState = $this->currentTextState;

        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

        
        
        $w = 0;
        $cf = $this->currentFont;
        $current_font = $this->fonts[$cf];
        $space_scale = 1000 / ($size > 0 ? $size : 1);

        if ($current_font['isUnicode']) {
            
            
            $unicode = $this->utf8toCodePointsArray($text);

            foreach ($unicode as $char) {
                
                if (isset($current_font['differences'][$char])) {
                    $char = $current_font['differences'][$char];
                }

                if (isset($current_font['C'][$char])) {
                    $char_width = $current_font['C'][$char];
                } elseif (isset($current_font['C'][0xFFFD])) {
                    
                    $char_width = $current_font['C'][0xFFFD];
                } else {
                    $char_width = $current_font['C'][0x0020];
                }

                
                $w += $char_width;

                
                if (isset($current_font['codeToName'][$char]) && $current_font['codeToName'][$char] === 'space') {  
                    $w += $wordSpacing * $space_scale;
                }
            }

            
            if ($charSpacing != 0) {
                $w += $charSpacing * $space_scale * count($unicode);
            }

        } else {
            
            if ($this->isUnicode) {
                $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
            }

            $len = mb_strlen($text, 'Windows-1252');

            for ($i = 0; $i < $len; $i++) {
                $c = $text[$i];
                $char = isset($ord_cache[$c]) ? $ord_cache[$c] : ($ord_cache[$c] = ord($c));

                
                if (isset($current_font['differences'][$char])) {
                    $char = $current_font['differences'][$char];
                }

                if (isset($current_font['C'][$char])) {
                    $char_width = $current_font['C'][$char];
                } elseif (isset($current_font['C'][0xFFFD])) {
                    
                    $char_width = $current_font['C'][0xFFFD];
                } else {
                    $char_width = $current_font['C'][0x0020];
                }

                
                $w += $char_width;

                
                if (isset($current_font['codeToName'][$char]) && $current_font['codeToName'][$char] === 'space') {  
                    $w += $wordSpacing * $space_scale;
                }
            }

            
            if ($charSpacing != 0) {
                $w += $charSpacing * $space_scale * $len;
            }
        }

        $this->currentTextState = $store_currentTextState;
        $this->setCurrentFont();

        return $w * $size / 1000;
    }

    
    function saveState($pageEnd = 0)
    {
        if ($pageEnd) {
            
            
            
            $opt = $this->stateStack[$pageEnd];
            
            $this->setColor($opt['col'], true);
            $this->setStrokeColor($opt['str'], true);
            $this->addContent("\n" . $opt['lin']);
            
        } else {
            $this->nStateStack++;
            $this->stateStack[$this->nStateStack] = [
                'col' => $this->currentColor,
                'str' => $this->currentStrokeColor,
                'lin' => $this->currentLineStyle
            ];
        }

        $this->save();
    }

    
    function restoreState($pageEnd = 0)
    {
        if (!$pageEnd) {
            $n = $this->nStateStack;
            $this->currentColor = $this->stateStack[$n]['col'];
            $this->currentStrokeColor = $this->stateStack[$n]['str'];
            $this->addContent("\n" . $this->stateStack[$n]['lin']);
            $this->currentLineStyle = $this->stateStack[$n]['lin'];
            $this->stateStack[$n] = null;
            unset($this->stateStack[$n]);
            $this->nStateStack--;
        }

        $this->restore();
    }

    
    function openObject()
    {
        $this->nStack++;
        $this->stack[$this->nStack] = ['c' => $this->currentContents, 'p' => $this->currentPage];
        
        $this->numObj++;
        $this->o_contents($this->numObj, 'new');
        $this->currentContents = $this->numObj;
        $this->looseObjects[$this->numObj] = 1;

        return $this->numObj;
    }

    
    function reopenObject($id)
    {
        $this->nStack++;
        $this->stack[$this->nStack] = ['c' => $this->currentContents, 'p' => $this->currentPage];
        $this->currentContents = $id;

        
        if (isset($this->objects[$id]['onPage'])) {
            $this->currentPage = $this->objects[$id]['onPage'];
        }
    }

    
    function closeObject()
    {
        
        
        if ($this->nStack > 0) {
            $this->currentContents = $this->stack[$this->nStack]['c'];
            $this->currentPage = $this->stack[$this->nStack]['p'];
            $this->nStack--;
            
            
        }
    }

    
    function stopObject($id)
    {
        
        
        if (isset($this->addLooseObjects[$id])) {
            $this->addLooseObjects[$id] = '';
        }
    }

    
    function addObject($id, $options = 'add')
    {
        
        if (isset($this->looseObjects[$id]) && $this->currentContents != $id) {
            
            switch ($options) {
                case 'all':
                    
                    
                    $this->addLooseObjects[$id] = 'all';

                case 'add':
                    if (isset($this->objects[$this->currentContents]['onPage'])) {
                        
                        
                        $this->o_page($this->objects[$this->currentContents]['onPage'], 'content', $id);
                    }
                    break;

                case 'even':
                    $this->addLooseObjects[$id] = 'even';
                    $pageObjectId = $this->objects[$this->currentContents]['onPage'];
                    if ($this->objects[$pageObjectId]['info']['pageNum'] % 2 == 0) {
                        $this->addObject($id);
                        
                    }
                    break;

                case 'odd':
                    $this->addLooseObjects[$id] = 'odd';
                    $pageObjectId = $this->objects[$this->currentContents]['onPage'];
                    if ($this->objects[$pageObjectId]['info']['pageNum'] % 2 == 1) {
                        $this->addObject($id);
                        
                    }
                    break;

                case 'next':
                    $this->addLooseObjects[$id] = 'all';
                    break;

                case 'nexteven':
                    $this->addLooseObjects[$id] = 'even';
                    break;

                case 'nextodd':
                    $this->addLooseObjects[$id] = 'odd';
                    break;
            }
        }
    }

    
    function serializeObject($id)
    {
        if (array_key_exists($id, $this->objects)) {
            return serialize($this->objects[$id]);
        }

        return null;
    }

    
    function restoreSerializedObject($obj)
    {
        $obj_id = $this->openObject();
        $this->objects[$obj_id] = unserialize($obj);
        $this->closeObject();

        return $obj_id;
    }

    
    public function addEmbeddedFile(string $filepath, string $embeddedFilename, string $description): void
    {
        $this->numObj++;
        $this->o_embedded_file_dictionary(
            $this->numObj,
            'new',
            [
                'filepath' => $filepath,
                'filename' => $embeddedFilename,
                'description' => $description
            ]
        );
    }

    
    public function addInfo($label, string $value = ""): void
    {
        
        
        
        
        if (is_array($label)) {
            foreach ($label as $l => $v) {
                $this->o_info($this->infoObject, $l, (string) $v);
            }
        } else {
            $this->o_info($this->infoObject, $label, $value);
        }
    }

    
    function setPreferences($label, $value = 0)
    {
        
        if (is_array($label)) {
            foreach ($label as $l => $v) {
                $this->o_catalog($this->catalogId, 'viewerPreferences', [$l => $v]);
            }
        } else {
            $this->o_catalog($this->catalogId, 'viewerPreferences', [$label => $value]);
        }
    }

    
    private function getBytes(&$data, $pos, $num)
    {
        
        $ret = 0;
        for ($i = 0; $i < $num; $i++) {
            $ret *= 256;
            $ret += ord($data[$pos + $i]);
        }

        return $ret;
    }

    
    function image_iscached($imgname)
    {
        return isset($this->imagelist[$imgname]);
    }

    
    function addImagePng(&$img, $file, $x, $y, $w = 0.0, $h = 0.0, $is_mask = false, $mask = null)
    {
        if (!function_exists("imagepng")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        
        if (isset($this->imagelist[$file])) {
            $data = null;
        } else {
            
            
            
            
            
            
            
            
            
            

            
            imagesavealpha($img, false);

            $error = 0;
            
            
            if (defined("DEBUGPNG") && DEBUGPNG) {
                print '[addImagePng ' . $file . ']';
            }

            ob_start();
            @imagepng($img);
            $data = ob_get_clean();

            if ($data == '') {
                $error = 1;
                $errormsg = 'trouble writing file from GD';
                
                
                if (defined("DEBUGPNG") && DEBUGPNG) {
                    print 'trouble writing file from GD';
                }
            }

            if ($error) {
                $this->addMessage('PNG error - (' . $file . ') ' . $errormsg);

                return;
            }
        }  

        $this->addPngFromBuf($data, $file, $x, $y, $w, $h, $is_mask, $mask);
    }

    
    protected function addImagePngAlpha($file, $x, $y, $w, $h, $byte)
    {
        
        $img = @imagecreatefrompng($file);

        if ($img === false) {
            return;
        }

        
        $eight_bit = ($byte & 4) !== 4;

        $wpx = imagesx($img);
        $hpx = imagesy($img);

        imagesavealpha($img, false);

        
        $tempfile_alpha = @tempnam($this->tmp, "cpdf_img_");
        @unlink($tempfile_alpha);
        $tempfile_alpha = "$tempfile_alpha.png";

        
        $tempfile_plain = @tempnam($this->tmp, "cpdf_img_");
        @unlink($tempfile_plain);
        $tempfile_plain = "$tempfile_plain.png";

        $imgalpha = imagecreate($wpx, $hpx);
        imagesavealpha($imgalpha, false);

        
        for ($c = 0; $c < 256; ++$c) {
            imagecolorallocate($imgalpha, $c, $c, $c);
        }

        
        if (extension_loaded("gmagick")) {
            $gmagick = new \Gmagick($file);
            $gmagick->setimageformat('png');

            
            $alpha_channel_neg = clone $gmagick;
            $alpha_channel_neg->separateimagechannel(\Gmagick::CHANNEL_OPACITY);

            
            $alpha_channel = new \Gmagick();
            $alpha_channel->newimage($wpx, $hpx, "#FFFFFF", "png");
            $alpha_channel->compositeimage($alpha_channel_neg, \Gmagick::COMPOSITE_DIFFERENCE, 0, 0);
            $alpha_channel->separateimagechannel(\Gmagick::CHANNEL_RED);
            $alpha_channel->writeimage($tempfile_alpha);

            
            $imgalpha_ = @imagecreatefrompng($tempfile_alpha);
            imagecopy($imgalpha, $imgalpha_, 0, 0, 0, 0, $wpx, $hpx);
            imagedestroy($imgalpha_);
            imagepng($imgalpha, $tempfile_alpha);

            
            $color_channels = new \Gmagick();
            $color_channels->newimage($wpx, $hpx, "#FFFFFF", "png");
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYRED, 0, 0);
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYGREEN, 0, 0);
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYBLUE, 0, 0);
            $color_channels->writeimage($tempfile_plain);

            $imgplain = @imagecreatefrompng($tempfile_plain);
        }
        
        elseif (extension_loaded("imagick")) {
            
            
            static $imagickClonable = null;
            if ($imagickClonable === null) {
                $imagickClonable = true;
                if (defined('Imagick::IMAGICK_EXTVER')) {
                    $imagickVersion = \Imagick::IMAGICK_EXTVER;
                } else {
                    $imagickVersion = '0';
                }
                if (version_compare($imagickVersion, '0.0.1', '>=')) {
                    $imagickClonable = version_compare($imagickVersion, '3.0.1rc1', '>=');
                }
            }

            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            $imagick->setFormat('PNG');
            $imagick->readImage($file);

            
            if ($imagick->getImageAlphaChannel()) {
                $alpha_channel = $imagickClonable ? clone $imagick : $imagick->clone();
                $alpha_channel->separateImageChannel(\Imagick::CHANNEL_ALPHA);
                
                if (\Imagick::getVersion()['versionNumber'] < 1800) {
                    $alpha_channel->negateImage(true);
                }

                try {
                    $alpha_channel->writeImage($tempfile_alpha);
                } catch (\ImagickException $th) {
                    
                    $alpha_channel->setFormat('png');
                    $alpha_channel->writeImage($tempfile_alpha);
                }

                
                $imgalpha_ = @imagecreatefrompng($tempfile_alpha);
                imagecopy($imgalpha, $imgalpha_, 0, 0, 0, 0, $wpx, $hpx);
                imagedestroy($imgalpha_);
                imagepng($imgalpha, $tempfile_alpha);
            } else {
                $tempfile_alpha = null;
            }

            
            $color_channels = new \Imagick();
            $color_channels->setRegistry('temporary-path', $this->tmp);
            $color_channels->newImage($wpx, $hpx, "#FFFFFF", "png");
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYRED, 0, 0);
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYGREEN, 0, 0);
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYBLUE, 0, 0);
            $color_channels->writeImage($tempfile_plain);

            $imgplain = @imagecreatefrompng($tempfile_plain);
        } else {
            
            $allocated_colors = [];

            
            for ($xpx = 0; $xpx < $wpx; ++$xpx) {
                for ($ypx = 0; $ypx < $hpx; ++$ypx) {
                    $color = imagecolorat($img, $xpx, $ypx);
                    $col = imagecolorsforindex($img, $color);
                    $alpha = $col['alpha'];

                    if ($eight_bit) {
                        
                        $gammacorr = 2.2;
                        $pixel = round(pow((((127 - $alpha) * 255 / 127) / 255), $gammacorr) * 255);
                    } else {
                        
                        $pixel = (127 - $alpha) * 2;

                        $key = $col['red'] . $col['green'] . $col['blue'];

                        if (!isset($allocated_colors[$key])) {
                            $pixel_img = imagecolorallocate($img, $col['red'], $col['green'], $col['blue']);
                            $allocated_colors[$key] = $pixel_img;
                        } else {
                            $pixel_img = $allocated_colors[$key];
                        }

                        imagesetpixel($img, $xpx, $ypx, $pixel_img);
                    }

                    imagesetpixel($imgalpha, $xpx, $ypx, $pixel);
                }
            }

            
            $imgplain = imagecreatetruecolor($wpx, $hpx);
            imagecopy($imgplain, $img, 0, 0, 0, 0, $wpx, $hpx);
            imagedestroy($img);

            imagepng($imgalpha, $tempfile_alpha);
            imagepng($imgplain, $tempfile_plain);
        }

        $this->imageAlphaList[$file] = [$tempfile_alpha, $tempfile_plain];

        
        if ($tempfile_alpha) {
            $this->addImagePng($imgalpha, $tempfile_alpha, $x, $y, $w, $h, true);
            imagedestroy($imgalpha);
            $this->imageCache[] = $tempfile_alpha;
        }

        
        $this->addImagePng($imgplain, $tempfile_plain, $x, $y, $w, $h, false, ($tempfile_alpha !== null));
        imagedestroy($imgplain);
        $this->imageCache[] = $tempfile_plain;
    }

    
    function addPngFromFile($file, $x, $y, $w = 0, $h = 0)
    {
        if (!function_exists("imagecreatefrompng")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        if (isset($this->imageAlphaList[$file])) {
            [$alphaFile, $plainFile] = $this->imageAlphaList[$file];

            if ($alphaFile) {
                $img = null;
                $this->addImagePng($img, $alphaFile, $x, $y, $w, $h, true);
            }

            $img = null;
            $this->addImagePng($img, $plainFile, $x, $y, $w, $h, false, ($plainFile !== null));
            return;
        }

        
        if (isset($this->imagelist[$file])) {
            $img = null;
        } else {
            $info = file_get_contents($file, false, null, 24, 5);
            $meta = unpack("CbitDepth/CcolorType/CcompressionMethod/CfilterMethod/CinterlaceMethod", $info);
            $bit_depth = $meta["bitDepth"];
            $color_type = $meta["colorType"];

            
            
            
            
            $is_alpha = in_array($color_type, [4, 6]) || ($color_type == 3 && $bit_depth != 4);

            if ($is_alpha) { 
                $this->addImagePngAlpha($file, $x, $y, $w, $h, $color_type);
                return;
            }

            
            
            
            
            
            
            
            
            
            
            $imgtmp = @imagecreatefrompng($file);
            if (!$imgtmp) {
                return;
            }
            $sx = imagesx($imgtmp);
            $sy = imagesy($imgtmp);
            $img = imagecreatetruecolor($sx, $sy);
            imagealphablending($img, true);

            
            $ti = imagecolortransparent($imgtmp);
            if ($ti >= 0) {
                $tc = imagecolorsforindex($imgtmp, $ti);
                $ti = imagecolorallocate($img, $tc['red'], $tc['green'], $tc['blue']);
                imagefill($img, 0, 0, $ti);
                imagecolortransparent($img, $ti);
            } else {
                imagefill($img, 1, 1, imagecolorallocate($img, 255, 255, 255));
            }

            imagecopy($img, $imgtmp, 0, 0, 0, 0, $sx, $sy);
            imagedestroy($imgtmp);
        }
        $this->addImagePng($img, $file, $x, $y, $w, $h);

        if ($img) {
            imagedestroy($img);
        }
    }

    
    function addSvgFromFile($file, $x, $y, $w = 0, $h = 0)
    {
        $doc = new \Svg\Document();
        $doc->loadFile($file);
        $dimensions = $doc->getDimensions();

        $this->save();

        $this->transform([$w / $dimensions["width"], 0, 0, $h / $dimensions["height"], $x, $y]);

        $surface = new \Svg\Surface\SurfaceCpdf($doc, $this);
        $doc->render($surface);

        $this->restore();
    }

    
    function addPngFromBuf(&$data, $file, $x, $y, $w = 0.0, $h = 0.0, $is_mask = false, $mask = null)
    {
        if (isset($this->imagelist[$file])) {
            $data = null;
            $info['width'] = $this->imagelist[$file]['w'];
            $info['height'] = $this->imagelist[$file]['h'];
            $label = $this->imagelist[$file]['label'];
        } else {
            if ($data == null) {
                $this->addMessage('addPngFromBuf error - data not present!');

                return;
            }

            $error = 0;

            if (!$error) {
                $header = chr(137) . chr(80) . chr(78) . chr(71) . chr(13) . chr(10) . chr(26) . chr(10);

                if (mb_substr($data, 0, 8, '8bit') != $header) {
                    $error = 1;

                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile this file does not have a valid header ' . $file . ']';
                    }

                    $errormsg = 'this file does not have a valid header';
                }
            }

            if (!$error) {
                
                $p = 8;
                $len = mb_strlen($data, '8bit');

                
                $haveHeader = 0;
                $info = [];
                $idata = '';
                $pdata = '';

                while ($p < $len) {
                    $chunkLen = $this->getBytes($data, $p, 4);
                    $chunkType = mb_substr($data, $p + 4, 4, '8bit');

                    switch ($chunkType) {
                        case 'IHDR':
                            
                            $info['width'] = $this->getBytes($data, $p + 8, 4);
                            $info['height'] = $this->getBytes($data, $p + 12, 4);
                            $info['bitDepth'] = ord($data[$p + 16]);
                            $info['colorType'] = ord($data[$p + 17]);
                            $info['compressionMethod'] = ord($data[$p + 18]);
                            $info['filterMethod'] = ord($data[$p + 19]);
                            $info['interlaceMethod'] = ord($data[$p + 20]);

                            
                            $haveHeader = 1;
                            if ($info['compressionMethod'] != 0) {
                                $error = 1;

                                
                                if (defined("DEBUGPNG") && DEBUGPNG) {
                                    print '[addPngFromFile unsupported compression method ' . $file . ']';
                                }

                                $errormsg = 'unsupported compression method';
                            }

                            if ($info['filterMethod'] != 0) {
                                $error = 1;

                                
                                if (defined("DEBUGPNG") && DEBUGPNG) {
                                    print '[addPngFromFile unsupported filter method ' . $file . ']';
                                }

                                $errormsg = 'unsupported filter method';
                            }
                            break;

                        case 'PLTE':
                            $pdata .= mb_substr($data, $p + 8, $chunkLen, '8bit');
                            break;

                        case 'IDAT':
                            $idata .= mb_substr($data, $p + 8, $chunkLen, '8bit');
                            break;

                        case 'tRNS':
                            
                            
                            $transparency = [];

                            switch ($info['colorType']) {
                                
                                case 3:
                                    
                                    
                                    
                                    $transparency['type'] = 'indexed';
                                    $trans = 0;

                                    for ($i = $chunkLen; $i >= 0; $i--) {
                                        if (ord($data[$p + 8 + $i]) == 0) {
                                            $trans = $i;
                                        }
                                    }

                                    $transparency['data'] = $trans;
                                    break;

                                
                                case 0:
                                    
                                    
                                    $transparency['type'] = 'indexed';
                                    $transparency['data'] = ord($data[$p + 8 + 1]);
                                    break;

                                
                                case 2:
                                    
                                    $transparency['r'] = $this->getBytes($data, $p + 8, 2);
                                    
                                    $transparency['g'] = $this->getBytes($data, $p + 10, 2);
                                    
                                    $transparency['b'] = $this->getBytes($data, $p + 12, 2);
                                    

                                    $transparency['type'] = 'color-key';
                                    break;

                                
                                default:
                                    if (defined("DEBUGPNG") && DEBUGPNG) {
                                        print '[addPngFromFile unsupported transparency type ' . $file . ']';
                                    }
                                    break;
                            }

                            
                            break;

                        default:
                            break;
                    }

                    $p += $chunkLen + 12;
                }

                if (!$haveHeader) {
                    $error = 1;

                    
                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile information header is missing ' . $file . ']';
                    }

                    $errormsg = 'information header is missing';
                }

                if (isset($info['interlaceMethod']) && $info['interlaceMethod']) {
                    $error = 1;

                    
                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile no support for interlaced images in pdf ' . $file . ']';
                    }

                    $errormsg = 'There appears to be no support for interlaced images in pdf.';
                }
            }

            if (!$error && $info['bitDepth'] > 8) {
                $error = 1;

                
                if (defined("DEBUGPNG") && DEBUGPNG) {
                    print '[addPngFromFile bit depth of 8 or less is supported ' . $file . ']';
                }

                $errormsg = 'only bit depth of 8 or less is supported';
            }

            if (!$error) {
                switch ($info['colorType']) {
                    case 3:
                        $color = 'DeviceRGB';
                        $ncolor = 1;
                        break;

                    case 2:
                        $color = 'DeviceRGB';
                        $ncolor = 3;
                        break;

                    case 0:
                        $color = 'DeviceGray';
                        $ncolor = 1;
                        break;

                    default:
                        $error = 1;

                        
                        if (defined("DEBUGPNG") && DEBUGPNG) {
                            print '[addPngFromFile alpha channel not supported: ' . $info['colorType'] . ' ' . $file . ']';
                        }

                        $errormsg = 'transparency alpha channel not supported, transparency only supported for palette images.';
                }
            }

            if ($error) {
                $this->addMessage('PNG error - (' . $file . ') ' . $errormsg);

                return;
            }

            
            
            $this->numImages++;
            $im = $this->numImages;
            $label = "I$im";
            $this->numObj++;

            
            $options = [
                'label'            => $label,
                'data'             => $idata,
                'bitsPerComponent' => $info['bitDepth'],
                'pdata'            => $pdata,
                'iw'               => $info['width'],
                'ih'               => $info['height'],
                'type'             => 'png',
                'color'            => $color,
                'ncolor'           => $ncolor,
                'masked'           => $mask,
                'isMask'           => $is_mask
            ];

            if (isset($transparency)) {
                $options['transparency'] = $transparency;
            }

            $this->o_image($this->numObj, 'new', $options);
            $this->imagelist[$file] = ['label' => $label, 'w' => $info['width'], 'h' => $info['height']];
        }

        if ($is_mask) {
            return;
        }

        if ($w <= 0 && $h <= 0) {
            $w = $info['width'];
            $h = $info['height'];
        }

        if ($w <= 0) {
            $w = $h / $info['height'] * $info['width'];
        }

        if ($h <= 0) {
            $h = $w * $info['height'] / $info['width'];
        }

        $this->addContent(sprintf("\nq\n%.3F 0 0 %.3F %.3F %.3F cm /%s Do\nQ", $w, $h, $x, $y, $label));
    }

    
    function addJpegFromFile($img, $x, $y, $w = 0, $h = 0)
    {
        
        

        if (substr($img, 0, 5) == 'data:') {
            $filename = 'data-' . hash('md4', $img);
        } else {
            if (!file_exists($img)) {
                return;
            }
            $filename = $img;
        }

        if ($this->image_iscached($filename)) {
            $data = null;
            $imageWidth = $this->imagelist[$filename]['w'];
            $imageHeight = $this->imagelist[$filename]['h'];
            $channels = $this->imagelist[$filename]['c'];
        } else {
            $tmp = getimagesize($img);
            $imageWidth = $tmp[0];
            $imageHeight = $tmp[1];

            if (isset($tmp['channels'])) {
                $channels = $tmp['channels'];
            } else {
                $channels = 3;
            }

            $data = file_get_contents($img);
        }

        if ($w <= 0 && $h <= 0) {
            $w = $imageWidth;
        }

        if ($w == 0) {
            $w = $h / $imageHeight * $imageWidth;
        }

        if ($h == 0) {
            $h = $w * $imageHeight / $imageWidth;
        }

        $this->addJpegImage_common($data, $filename, $imageWidth, $imageHeight, $x, $y, $w, $h, $channels);
    }

    
    private function addJpegImage_common(
        &$data,
        $imgname,
        $imageWidth,
        $imageHeight,
        $x,
        $y,
        $w = 0,
        $h = 0,
        $channels = 3
    ) {
        if ($this->image_iscached($imgname)) {
            $label = $this->imagelist[$imgname]['label'];
            
            

        } else {
            if ($data == null) {
                $this->addMessage('addJpegImage_common error - (' . $imgname . ') data not present!');

                return;
            }

            
            
            $this->numImages++;
            $im = $this->numImages;
            $label = "I$im";
            $this->numObj++;

            $this->o_image(
                $this->numObj,
                'new',
                [
                    'label'    => $label,
                    'data'     => &$data,
                    'iw'       => $imageWidth,
                    'ih'       => $imageHeight,
                    'channels' => $channels
                ]
            );

            $this->imagelist[$imgname] = [
                'label' => $label,
                'w'     => $imageWidth,
                'h'     => $imageHeight,
                'c'     => $channels
            ];
        }

        $this->addContent(sprintf("\nq\n%.3F 0 0 %.3F %.3F %.3F cm /%s Do\nQ ", $w, $h, $x, $y, $label));
    }

    
    function openHere($style, $a = 0, $b = 0, $c = 0)
    {
        
        
        
        
        
        
        
        
        
        
        $this->numObj++;
        $this->o_destination(
            $this->numObj,
            'new',
            ['page' => $this->currentPage, 'type' => $style, 'p1' => $a, 'p2' => $b, 'p3' => $c]
        );
        $id = $this->catalogId;
        $this->o_catalog($id, 'openHere', $this->numObj);
    }

    
    function addJavascript($code)
    {
        $this->javascript .= $code;
    }

    
    function addDestination($label, $style, $a = 0, $b = 0, $c = 0)
    {
        
        
        
        $this->numObj++;
        $this->o_destination(
            $this->numObj,
            'new',
            ['page' => $this->currentPage, 'type' => $style, 'p1' => $a, 'p2' => $b, 'p3' => $c]
        );
        $id = $this->numObj;

        
        $this->destinations["$label"] = $id;
    }

    
    function setFontFamily($family, $options = '')
    {
        if (!is_array($options)) {
            if ($family === 'init') {
                
                
                
                $this->fontFamilies['Helvetica.afm'] =
                    [
                        'b'  => 'Helvetica-Bold.afm',
                        'i'  => 'Helvetica-Oblique.afm',
                        'bi' => 'Helvetica-BoldOblique.afm',
                        'ib' => 'Helvetica-BoldOblique.afm'
                    ];

                $this->fontFamilies['Courier.afm'] =
                    [
                        'b'  => 'Courier-Bold.afm',
                        'i'  => 'Courier-Oblique.afm',
                        'bi' => 'Courier-BoldOblique.afm',
                        'ib' => 'Courier-BoldOblique.afm'
                    ];

                $this->fontFamilies['Times-Roman.afm'] =
                    [
                        'b'  => 'Times-Bold.afm',
                        'i'  => 'Times-Italic.afm',
                        'bi' => 'Times-BoldItalic.afm',
                        'ib' => 'Times-BoldItalic.afm'
                    ];
            }
        } else {

            
            
            if (mb_strlen($family)) {
                $this->fontFamilies[$family] = $options;
            }
        }
    }

    
    function addMessage($message)
    {
        $this->messages .= $message . "\n";
    }

    
    function transaction($action)
    {
        switch ($action) {
            case 'start':
                
                $data = get_object_vars($this);
                $this->checkpoint = $data;
                unset($data);
                break;

            case 'commit':
                if (is_array($this->checkpoint) && isset($this->checkpoint['checkpoint'])) {
                    $tmp = $this->checkpoint['checkpoint'];
                    $this->checkpoint = $tmp;
                    unset($tmp);
                } else {
                    $this->checkpoint = '';
                }
                break;

            case 'rewind':
                
                if (is_array($this->checkpoint)) {
                    
                    $tmp = $this->checkpoint;

                    foreach ($tmp as $k => $v) {
                        if ($k !== 'checkpoint') {
                            $this->$k = $v;
                        }
                    }
                    unset($tmp);
                }
                break;

            case 'abort':
                if (is_array($this->checkpoint)) {
                    
                    $tmp = $this->checkpoint;
                    foreach ($tmp as $k => $v) {
                        $this->$k = $v;
                    }
                    unset($tmp);
                }
                break;
        }
    }
}
