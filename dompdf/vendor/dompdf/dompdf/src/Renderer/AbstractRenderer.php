<?php

namespace Dompdf\Renderer;

use DOMElement;
use Dompdf\Adapter\CPDF;
use Dompdf\Css\Color;
use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Helpers;
use Dompdf\Image\Cache;


abstract class AbstractRenderer
{

    
    protected $_canvas;

    
    protected $_dompdf;

    
    function __construct(Dompdf $dompdf)
    {
        $this->_dompdf = $dompdf;
        $this->_canvas = $dompdf->getCanvas();
    }

    
    abstract function render(Frame $frame);

    
    protected function _render_background(Frame $frame, array $border_box): void
    {
        $style = $frame->get_style();
        $color = $style->background_color;
        $image = $style->background_image;
        [$x, $y, $w, $h] = $border_box;

        if ($color === "transparent" && $image === "none") {
            return;
        }

        if ($style->has_border_radius()) {
            [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box);
            $this->_canvas->clipping_roundrectangle($x, $y, $w, $h, $tl, $tr, $br, $bl);
        }

        if ($color !== "transparent") {
            $this->_canvas->filled_rectangle($x, $y, $w, $h, $color);
        }

        if ($image !== "none") {
            $this->_background_image($image, $x, $y, $w, $h, $style);
        }

        if ($style->has_border_radius()) {
            $this->_canvas->clipping_end();
        }
    }

    
    protected function _render_border(Frame $frame, array $border_box, string $corner_style = "bevel"): void
    {
        $style = $frame->get_style();
        $bp = $style->get_border_properties();
        [$x, $y, $w, $h] = $border_box;
        [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box);

        
        
        if ($bp["top"]["style"] === "solid" &&
            $bp["top"] === $bp["right"] &&
            $bp["right"] === $bp["bottom"] &&
            $bp["bottom"] === $bp["left"] &&
            !$style->has_border_radius()
        ) {
            $props = $bp["top"];
            if ($props["color"] === "transparent" || $props["width"] <= 0) {
                return;
            }

            $width = (float)$style->length_in_pt($props["width"]);
            $this->_canvas->rectangle($x + $width / 2, $y + $width / 2, $w - $width, $h - $width, $props["color"], $width);
            return;
        }

        
        $widths = [
            (float)$style->length_in_pt($bp["top"]["width"]),
            (float)$style->length_in_pt($bp["right"]["width"]),
            (float)$style->length_in_pt($bp["bottom"]["width"]),
            (float)$style->length_in_pt($bp["left"]["width"])
        ];

        foreach ($bp as $side => $props) {
            if ($props["style"] === "none" ||
                $props["style"] === "hidden" ||
                $props["color"] === "transparent" ||
                $props["width"] <= 0
            ) {
                continue;
            }

            [$x, $y, $w, $h] = $border_box;
            $method = "_border_" . $props["style"];

            switch ($side) {
                case "top":
                    $length = $w;
                    $r1 = $tl;
                    $r2 = $tr;
                    break;

                case "bottom":
                    $length = $w;
                    $y += $h;
                    $r1 = $bl;
                    $r2 = $br;
                    break;

                case "left":
                    $length = $h;
                    $r1 = $tl;
                    $r2 = $bl;
                    break;

                case "right":
                    $length = $h;
                    $x += $w;
                    $r1 = $tr;
                    $r2 = $br;
                    break;

                default:
                    break;
            }

            
            $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style, $r1, $r2);
        }
    }

    
    protected function _render_outline(Frame $frame, array $border_box, string $corner_style = "bevel"): void
    {
        $style = $frame->get_style();

        $width = $style->outline_width;
        $outline_style = $style->outline_style;
        $color = $style->outline_color;

        if ($outline_style === "none" || $color === "transparent" || $width <= 0) {
            return;
        }

        $offset = $style->outline_offset;

        [$x, $y, $w, $h] = $border_box;
        $d = $width + $offset;
        $outline_box = [$x - $d, $y - $d, $w + $d * 2, $h + $d * 2];
        [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box, $outline_box);

        $x -= $offset;
        $y -= $offset;
        $w += $offset * 2;
        $h += $offset * 2;

        
        if ($outline_style === "solid" && !$style->has_border_radius()) {
            $x -= $width / 2;
            $y -= $width / 2;
            $w += $width;
            $h += $width;

            $this->_canvas->rectangle($x, $y, $w, $h, $color, $width);
            return;
        }

        $x -= $width;
        $y -= $width;
        $w += $width * 2;
        $h += $width * 2;

        $method = "_border_" . $outline_style;
        $widths = array_fill(0, 4, $width);
        $sides = ["top", "right", "left", "bottom"];

        foreach ($sides as $side) {
            switch ($side) {
                case "top":
                    $length = $w;
                    $side_x = $x;
                    $side_y = $y;
                    $r1 = $tl;
                    $r2 = $tr;
                    break;

                case "bottom":
                    $length = $w;
                    $side_x = $x;
                    $side_y = $y + $h;
                    $r1 = $bl;
                    $r2 = $br;
                    break;

                case "left":
                    $length = $h;
                    $side_x = $x;
                    $side_y = $y;
                    $r1 = $tl;
                    $r2 = $bl;
                    break;

                case "right":
                    $length = $h;
                    $side_x = $x + $w;
                    $side_y = $y;
                    $r1 = $tr;
                    $r2 = $br;
                    break;

                default:
                    break;
            }

            $this->$method($side_x, $side_y, $length, $color, $widths, $side, $corner_style, $r1, $r2);
        }
    }

    
    protected function _background_image(string $url, float $x, float $y, float $width, float $height, Style $style): void
    {
        if (!function_exists("imagecreatetruecolor")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        $sheet = $style->get_stylesheet();

        
        if ($width == 0 || $height == 0) {
            return;
        }

        $box_width = $width;
        $box_height = $height;

        
        if ($this->_dompdf->getOptions()->getDebugPng()) {
            print '[_background_image ' . $url . ']';
        }

        list($img, $type, ) = Cache::resolve_url(
            $url,
            $sheet->get_protocol(),
            $sheet->get_host(),
            $sheet->get_base_path(),
            $this->_dompdf->getOptions()
        );

        
        if (Cache::is_broken($img)) {
            return;
        }

        
        
        
        
        

        list($img_w, $img_h) = Helpers::dompdf_getimagesize($img, $this->_dompdf->getHttpContext());
        if ($img_w == 0 || $img_h == 0) {
            return;
        }

        
        $org_img_w = $img_w;
        $org_img_h = $img_h;

        $repeat = $style->background_repeat;
        $dpi = $this->_dompdf->getOptions()->getDpi();

        
        
        $bg_width = round((float)($width * $dpi) / 72);
        $bg_height = round((float)($height * $dpi) / 72);

        list($img_w, $img_h) = $this->_resize_background_image(
            $img_w,
            $img_h,
            $bg_width,
            $bg_height,
            $style->background_size,
            $dpi
        );
        

        list($bg_x, $bg_y) = $style->background_position;

        if (Helpers::is_percent($bg_x)) {
            
            
            $p = ((float)$bg_x) / 100.0;
            $x1 = $p * $img_w;
            $x2 = $p * $bg_width;

            $bg_x = $x2 - $x1;
        } else {
            $bg_x = (float)($style->length_in_pt($bg_x) * $dpi) / 72;
        }

        $bg_x = round($bg_x + (float)$style->length_in_pt($style->border_left_width) * $dpi / 72);

        if (Helpers::is_percent($bg_y)) {
            
            
            $p = ((float)$bg_y) / 100.0;
            $y1 = $p * $img_h;
            $y2 = $p * $bg_height;

            $bg_y = $y2 - $y1;
        } else {
            $bg_y = (float)($style->length_in_pt($bg_y) * $dpi) / 72;
        }

        $bg_y = round($bg_y + (float)$style->length_in_pt($style->border_top_width) * $dpi / 72);

        
        
        
        

        if ($repeat !== "repeat" && $repeat !== "repeat-x") {
            
            if ($bg_x < 0) {
                $bg_width = $img_w + $bg_x;
            } else {
                $x += ($bg_x * 72) / $dpi;
                $bg_width = $bg_width - $bg_x;
                if ($bg_width > $img_w) {
                    $bg_width = $img_w;
                }
                $bg_x = 0;
            }

            if ($bg_width <= 0) {
                return;
            }

            $width = (float)($bg_width * 72) / $dpi;
        } else {
            
            if ($bg_x < 0) {
                $bg_x = -((-$bg_x) % $img_w);
            } else {
                $bg_x = $bg_x % $img_w;
                if ($bg_x > 0) {
                    $bg_x -= $img_w;
                }
            }
        }

        if ($repeat !== "repeat" && $repeat !== "repeat-y") {
            
            if ($bg_y < 0) {
                $bg_height = $img_h + $bg_y;
            } else {
                $y += ($bg_y * 72) / $dpi;
                $bg_height = $bg_height - $bg_y;
                if ($bg_height > $img_h) {
                    $bg_height = $img_h;
                }
                $bg_y = 0;
            }
            if ($bg_height <= 0) {
                return;
            }
            $height = (float)($bg_height * 72) / $dpi;
        } else {
            
            if ($bg_y < 0) {
                $bg_y = -((-$bg_y) % $img_h);
            } else {
                $bg_y = $bg_y % $img_h;
                if ($bg_y > 0) {
                    $bg_y -= $img_h;
                }
            }
        }

        
        if ($repeat === "repeat" && $bg_y <= 0 && $img_h + $bg_y >= $bg_height) {
            $repeat = "repeat-x";
        }

        if ($repeat === "repeat" && $bg_x <= 0 && $img_w + $bg_x >= $bg_width) {
            $repeat = "repeat-y";
        }

        if (($repeat === "repeat-x" && $bg_x <= 0 && $img_w + $bg_x >= $bg_width) ||
            ($repeat === "repeat-y" && $bg_y <= 0 && $img_h + $bg_y >= $bg_height)
        ) {
            $repeat = "no-repeat";
        }

        
        
        

        $key = implode("_", [$bg_width, $bg_height, $img_w, $img_h, $bg_x, $bg_y, $repeat]);
        
        
        $cpdfKey = $img . "_" . $key;
        $tmpFile = Cache::getTempImage($img, $key);
        $cached = ($this->_canvas instanceof CPDF && $this->_canvas->get_cpdf()->image_iscached($cpdfKey))
            || ($tmpFile !== null && file_exists($tmpFile));

        if (!$cached) {
            
            
            
            
            
            
            
            
            

            
            $bg = imagecreatetruecolor($bg_width, $bg_height);
            $cpdfFromGd = true;

            switch (strtolower($type)) {
                case "png":
                    $cpdfFromGd = false;
                    imagesavealpha($bg, true);
                    imagealphablending($bg, false);
                    $src = @imagecreatefrompng($img);
                    break;

                case "jpeg":
                    $src = @imagecreatefromjpeg($img);
                    break;

                case "webp":
                    $src = @imagecreatefromwebp($img);
                    break;

                case "gif":
                    $src = @imagecreatefromgif($img);
                    break;

                case "bmp":
                    $src = @Helpers::imagecreatefrombmp($img);
                    break;

                default:
                    return; 
            }

            if ($src == null) {
                return;
            }

            if ($img_w != $org_img_w || $img_h != $org_img_h) {
                $newSrc = imagescale($src, $img_w, $img_h);
                imagedestroy($src);
                $src = $newSrc;
            }

            if ($src == null) {
                return;
            }

            
            
            
            
            
            $ti = imagecolortransparent($src);
            $palletsize = imagecolorstotal($src);

            if ($ti >= 0 && $ti < $palletsize) {
                $tc = imagecolorsforindex($src, $ti);
                $ti = imagecolorallocate($bg, $tc['red'], $tc['green'], $tc['blue']);
                imagefill($bg, 0, 0, $ti);
                imagecolortransparent($bg, $ti);
            }

            
            
            if ($bg_x < 0) {
                $dst_x = 0;
                $src_x = -$bg_x;
            } else {
                $src_x = 0;
                $dst_x = $bg_x;
            }

            if ($bg_y < 0) {
                $dst_y = 0;
                $src_y = -$bg_y;
            } else {
                $src_y = 0;
                $dst_y = $bg_y;
            }

            
            
            $start_x = $bg_x;
            $start_y = $bg_y;

            
            if ($repeat === "no-repeat") {
                
                imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $img_h);

            } elseif ($repeat === "repeat-x") {
                for ($bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w) {
                    if ($bg_x < 0) {
                        $dst_x = 0;
                        $src_x = -$bg_x;
                        $w = $img_w + $bg_x;
                    } else {
                        $dst_x = $bg_x;
                        $src_x = 0;
                        $w = $img_w;
                    }
                    imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $img_h);
                }
            } elseif ($repeat === "repeat-y") {

                for ($bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h) {
                    if ($bg_y < 0) {
                        $dst_y = 0;
                        $src_y = -$bg_y;
                        $h = $img_h + $bg_y;
                    } else {
                        $dst_y = $bg_y;
                        $src_y = 0;
                        $h = $img_h;
                    }
                    imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $h);
                }
            } elseif ($repeat === "repeat") {
                for ($bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h) {
                    for ($bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w) {
                        if ($bg_x < 0) {
                            $dst_x = 0;
                            $src_x = -$bg_x;
                            $w = $img_w + $bg_x;
                        } else {
                            $dst_x = $bg_x;
                            $src_x = 0;
                            $w = $img_w;
                        }

                        if ($bg_y < 0) {
                            $dst_y = 0;
                            $src_y = -$bg_y;
                            $h = $img_h + $bg_y;
                        } else {
                            $dst_y = $bg_y;
                            $src_y = 0;
                            $h = $img_h;
                        }
                        imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $h);
                    }
                }
            } else {
                print 'Unknown repeat!';
            }

            imagedestroy($src);

            if ($cpdfFromGd && $this->_canvas instanceof CPDF) {
                
            } else {
                $tmpDir = $this->_dompdf->getOptions()->getTempDir();
                $tmpName = @tempnam($tmpDir, "bg_dompdf_img_");
                @unlink($tmpName);
                $tmpFile = "$tmpName.png";

                imagepng($bg, $tmpFile);
                imagedestroy($bg);

                Cache::addTempImage($img, $tmpFile, $key);
            }
        } else {
            $bg = null;
            $cpdfFromGd = $tmpFile === null;
        }

        if ($this->_dompdf->getOptions()->getDebugPng()) {
            print '[_background_image ' . $tmpFile . ']';
        }

        $this->_canvas->clipping_rectangle($x, $y, $box_width, $box_height);

        
        
        if ($cpdfFromGd && $this->_canvas instanceof CPDF) {
            
            $this->_canvas->get_cpdf()->addImagePng($bg, $cpdfKey, $x, $this->_canvas->get_height() - $y - $height, $width, $height);

            if (isset($bg)) {
                imagedestroy($bg);
            }
        } else {
            $this->_canvas->image($tmpFile, $x, $y, $width, $height);
        }

        $this->_canvas->clipping_end();
    }

    
    protected function _resize_background_image(
        float $img_width,
        float $img_height,
        float $container_width,
        float $container_height,
        $bg_resize,
        int $dpi
    ): array {
        
        if (is_array($bg_resize)) {
            $is_auto_width = $bg_resize[0] === 'auto';
            if ($is_auto_width) {
                $new_img_width = $img_width;
            } else {
                $new_img_width = $bg_resize[0];
                if (Helpers::is_percent($new_img_width)) {
                    $new_img_width = round(($container_width / 100) * (float)$new_img_width);
                } else {
                    $new_img_width = round($new_img_width * $dpi / 72);
                }
            }

            $is_auto_height = $bg_resize[1] === 'auto';
            if ($is_auto_height) {
                $new_img_height = $img_height;
            } else {
                $new_img_height = $bg_resize[1];
                if (Helpers::is_percent($new_img_height)) {
                    $new_img_height = round(($container_height / 100) * (float)$new_img_height);
                } else {
                    $new_img_height = round($new_img_height * $dpi / 72);
                }
            }

            
            if ($is_auto_width !== $is_auto_height) {
                if ($is_auto_height) {
                    $new_img_height = round($new_img_width * ($img_height / $img_width));
                } else {
                    $new_img_width = round($new_img_height * ($img_width / $img_height));
                }
            }
        } else {
            $container_ratio = $container_height / $container_width;

            if ($bg_resize === 'cover' || $bg_resize === 'contain') {
                $img_ratio = $img_height / $img_width;

                if (
                    ($bg_resize === 'cover' && $container_ratio > $img_ratio) ||
                    ($bg_resize === 'contain' && $container_ratio < $img_ratio)
                ) {
                    $new_img_height = $container_height;
                    $new_img_width = round($container_height / $img_ratio);
                } else {
                    $new_img_width = $container_width;
                    $new_img_height = round($container_width * $img_ratio);
                }
            } else {
                $new_img_width = $img_width;
                $new_img_height = $img_height;
            }
        }

        return [$new_img_width, $new_img_height];
    }

    

    
    protected function _border_dotted($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "dotted", $r1, $r2);
    }

    
    protected function _border_dashed($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "dashed", $r1, $r2);
    }

    
    protected function _border_solid($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "solid", $r1, $r2);
    }

    
    protected function _apply_ratio($side, $ratio, $top, $right, $bottom, $left, &$x, &$y, &$length, &$r1, &$r2)
    {
        switch ($side) {
            case "top":
                $r1 -= $left * $ratio;
                $r2 -= $right * $ratio;
                $x += $left * $ratio;
                $y += $top * $ratio;
                $length -= $left * $ratio + $right * $ratio;
                break;

            case "bottom":
                $r1 -= $right * $ratio;
                $r2 -= $left * $ratio;
                $x += $left * $ratio;
                $y -= $bottom * $ratio;
                $length -= $left * $ratio + $right * $ratio;
                break;

            case "left":
                $r1 -= $top * $ratio;
                $r2 -= $bottom * $ratio;
                $x += $left * $ratio;
                $y += $top * $ratio;
                $length -= $top * $ratio + $bottom * $ratio;
                break;

            case "right":
                $r1 -= $bottom * $ratio;
                $r2 -= $top * $ratio;
                $x -= $right * $ratio;
                $y += $top * $ratio;
                $length -= $top * $ratio + $bottom * $ratio;
                break;

            default:
                return;
        }
    }

    
    protected function _border_double($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $third_widths = [$top / 3, $right / 3, $bottom / 3, $left / 3];

        
        $this->_border_solid($x, $y, $length, $color, $third_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 2 / 3, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_solid($x, $y, $length, $color, $third_widths, $side, $corner_style, $r1, $r2);
    }

    
    protected function _border_groove($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $half_widths = [$top / 2, $right / 2, $bottom / 2, $left / 2];

        $this->_border_inset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 0.5, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_outset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);
    }

    
    protected function _border_ridge($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $half_widths = [$top / 2, $right / 2, $bottom / 2, $left / 2];

        $this->_border_outset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 0.5, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_inset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);
    }

    
    protected function _tint($c)
    {
        if (!is_numeric($c)) {
            return $c;
        }

        return min(1, $c + 0.16);
    }

    
    protected function _shade($c)
    {
        if (!is_numeric($c)) {
            return $c;
        }

        return max(0, $c - 0.33);
    }

    
    protected function _border_inset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        switch ($side) {
            case "top":
            case "left":
                $shade = array_map([$this, "_shade"], $color);
                $this->_border_solid($x, $y, $length, $shade, $widths, $side, $corner_style, $r1, $r2);
                break;

            case "bottom":
            case "right":
                $tint = array_map([$this, "_tint"], $color);
                $this->_border_solid($x, $y, $length, $tint, $widths, $side, $corner_style, $r1, $r2);
                break;

            default:
                return;
        }
    }

    
    protected function _border_outset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        switch ($side) {
            case "top":
            case "left":
                $tint = array_map([$this, "_tint"], $color);
                $this->_border_solid($x, $y, $length, $tint, $widths, $side, $corner_style, $r1, $r2);
                break;

            case "bottom":
            case "right":
                $shade = array_map([$this, "_shade"], $color);
                $this->_border_solid($x, $y, $length, $shade, $widths, $side, $corner_style, $r1, $r2);
                break;

            default:
                return;
        }
    }

    
    protected function dashPattern(string $style, float $width, float $length): array
    {
        if ($style === "dashed") {
            $w = 3 * $width;

            if ($length < $w) {
                $s = $w;
            } else {
                
                $r = round($length / $w);
                $r = $r % 2 === 0 ? $r + 1 : $r;
                $s = $length / $r;
            }

            return [[$s], "butt"];
        }

        if ($style === "dotted") {
            
            
            
            $gap = $width <= 1 ? 2 : 1;
            $w = ($gap + 1) * $width;

            if ($length < $w) {
                $s = $w;
            } else {
                
                $l = $length - $width;
                $r = max(round($l / $w), 1);
                $s = $l / $r;
            }

            return [[0, $s], "round"];
        }

        return [[], "butt"];
    }

    
    protected function _border_line($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $pattern_name = "none", $r1 = 0, $r2 = 0)
    {
        
        [$top, $right, $bottom, $left] = $widths;
        $width = $$side;

        
        $cornerClip = $corner_style === "bevel" && ($r1 < $width || $r2 < $width);
        $lineLength = $length - $r1 - $r2;
        [$pattern, $cap] = $this->dashPattern($pattern_name, $width, $lineLength);

        
        $halfWidth = $width / 2;
        $ar1 = max($r1 - $halfWidth, 0);
        $ar2 = max($r2 - $halfWidth, 0);

        
        $adj1 = $ar1 / 80;
        $adj2 = $ar2 / 80;

        
        
        
        
        $dl = $cap === "round" ? $halfWidth : 0;

        if ($cap === "round" && $ar1 > 0) {
            $adj1 -= rad2deg(asin($halfWidth / $ar1));
        }
        if ($cap === "round" && $ar2 > 0) {
            $adj2 -= rad2deg(asin($halfWidth / $ar2));
        }

        switch ($side) {
            case "top":
                if ($cornerClip) {
                    $points = [
                        $x, $y,
                        $x, $y - 1, 
                        $x + $length, $y - 1, 
                        $x + $length, $y,
                        $x + $length - max($right, $r2), $y + max($width, $r2),
                        $x + max($left, $r1), $y + max($width, $r1)
                    ];
                    $this->_canvas->clipping_polygon($points);
                }

                $y += $halfWidth;

                if ($ar1 > 0 && $adj1 > -22.5) {
                    $this->_canvas->arc($x + $r1, $y + $ar1, $ar1, $ar1, 90 - $adj1, 135 + $adj1, $color, $width, $pattern, $cap);
                }

                if ($lineLength > 0) {
                    $this->_canvas->line($x + $dl + $r1, $y, $x + $dl + $length - $r2, $y, $color, $width, $pattern, $cap);
                }

                if ($ar2 > 0 && $adj2 > -22.5) {
                    $this->_canvas->arc($x + $length - $r2, $y + $ar2, $ar2, $ar2, 45 - $adj2, 90 + $adj2, $color, $width, $pattern, $cap);
                }
                break;

            case "bottom":
                if ($cornerClip) {
                    $points = [
                        $x, $y,
                        $x, $y + 1, 
                        $x + $length, $y + 1, 
                        $x + $length, $y,
                        $x + $length - max($right, $r2), $y - max($width, $r2),
                        $x + max($left, $r1), $y - max($width, $r1)
                    ];
                    $this->_canvas->clipping_polygon($points);
                }

                $y -= $halfWidth;

                if ($ar1 > 0 && $adj1 > -22.5) {
                    $this->_canvas->arc($x + $r1, $y - $ar1, $ar1, $ar1, 225 - $adj1, 270 + $adj1, $color, $width, $pattern, $cap);
                }

                if ($lineLength > 0) {
                    $this->_canvas->line($x + $dl + $r1, $y, $x + $dl + $length - $r2, $y, $color, $width, $pattern, $cap);
                }

                if ($ar2 > 0 && $adj2 > -22.5) {
                    $this->_canvas->arc($x + $length - $r2, $y - $ar2, $ar2, $ar2, 270 - $adj2, 315 + $adj2, $color, $width, $pattern, $cap);
                }
                break;

            case "left":
                if ($cornerClip) {
                    $points = [
                        $x, $y,
                        $x - 1, $y, 
                        $x - 1, $y + $length, 
                        $x, $y + $length,
                        $x + max($width, $r2), $y + $length - max($bottom, $r2),
                        $x + max($width, $r1), $y + max($top, $r1)
                    ];
                    $this->_canvas->clipping_polygon($points);
                }

                $x += $halfWidth;

                if ($ar1 > 0 && $adj1 > -22.5) {
                    $this->_canvas->arc($x + $ar1, $y + $r1, $ar1, $ar1, 135 - $adj1, 180 + $adj1, $color, $width, $pattern, $cap);
                }

                if ($lineLength > 0) {
                    $this->_canvas->line($x, $y + $dl + $r1, $x, $y + $dl + $length - $r2, $color, $width, $pattern, $cap);
                }

                if ($ar2 > 0 && $adj2 > -22.5) {
                    $this->_canvas->arc($x + $ar2, $y + $length - $r2, $ar2, $ar2, 180 - $adj2, 225 + $adj2, $color, $width, $pattern, $cap);
                }
                break;

            case "right":
                if ($cornerClip) {
                    $points = [
                        $x, $y,
                        $x + 1, $y, 
                        $x + 1, $y + $length, 
                        $x, $y + $length,
                        $x - max($width, $r2), $y + $length - max($bottom, $r2),
                        $x - max($width, $r1), $y + max($top, $r1)
                    ];
                    $this->_canvas->clipping_polygon($points);
                }

                $x -= $halfWidth;

                if ($ar1 > 0 && $adj1 > -22.5) {
                    $this->_canvas->arc($x - $ar1, $y + $r1, $ar1, $ar1, 0 - $adj1, 45 + $adj1, $color, $width, $pattern, $cap);
                }

                if ($lineLength > 0) {
                    $this->_canvas->line($x, $y + $dl + $r1, $x, $y + $dl + $length - $r2, $color, $width, $pattern, $cap);
                }

                if ($ar2 > 0 && $adj2 > -22.5) {
                    $this->_canvas->arc($x - $ar2, $y + $length - $r2, $ar2, $ar2, 315 - $adj2, 360 + $adj2, $color, $width, $pattern, $cap);
                }
                break;
        }

        if ($cornerClip) {
            $this->_canvas->clipping_end();
        }
    }

    
    protected function _set_opacity(float $opacity): void
    {
        if ($opacity >= 0.0 && $opacity <= 1.0) {
            $this->_canvas->set_opacity($opacity);
        }
    }

    
    protected function addNamedDest(DOMElement $node): void
    {
        $id = $node->getAttribute("id");
        if ($id !== "") {
            $this->_canvas->add_named_dest($id);
        }

        if ($node->nodeName === "a") {
            $name = $node->getAttribute("name");
            if ($name !== "") {
                $this->_canvas->add_named_dest($name);
            }
        }
    }

    
    protected function addHyperlink(DOMElement $node, array $borderBox): void
    {
        if ($node->nodeName === "a" && ($href = $node->getAttribute("href")) !== "") {
            [$x, $y, $w, $h] = $borderBox;
            $dompdf = $this->_dompdf;
            $href = Helpers::build_url(
                $dompdf->getProtocol(),
                $dompdf->getBaseHost(),
                $dompdf->getBasePath(),
                $href,
                $dompdf->getOptions()->getChroot()
            ) ?? $href;
            $this->_canvas->add_link($href, $x, $y, $w, $h);
        }
    }

    
    protected function debugLayout(array $box, $color = "red", array $style = []): void
    {
        $this->_canvas->rectangle($box[0], $box[1], $box[2], $box[3], Color::parse($color), 0.1, $style);
    }
}
