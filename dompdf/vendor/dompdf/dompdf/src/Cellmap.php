<?php

namespace Dompdf;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableCell as TableCellFrameDecorator;


class Cellmap
{
    
    protected const BORDER_STYLE_SCORE = [
        "double" => 8,
        "solid"  => 7,
        "dashed" => 6,
        "dotted" => 5,
        "ridge"  => 4,
        "outset" => 3,
        "groove" => 2,
        "inset"  => 1,
        "none"   => 0
    ];

    
    protected $_table;

    
    protected $_num_rows;

    
    protected $_num_cols;

    
    protected $_cells;

    
    protected $_columns;

    
    protected $_rows;

    
    protected $_borders;

    
    protected $_frames;

    
    private $__col;

    
    private $__row;

    
    private $_columns_locked = false;

    
    private $_fixed_layout = false;

    
    public function __construct(TableFrameDecorator $table)
    {
        $this->_table = $table;
        $this->reset();
    }

    public function reset(): void
    {
        $this->_num_rows = 0;
        $this->_num_cols = 0;

        $this->_cells = [];
        $this->_frames = [];

        if (!$this->_columns_locked) {
            $this->_columns = [];
        }

        $this->_rows = [];

        $this->_borders = [];

        $this->__col = $this->__row = 0;
    }

    public function lock_columns(): void
    {
        $this->_columns_locked = true;
    }

    
    public function is_columns_locked()
    {
        return $this->_columns_locked;
    }

    
    public function set_layout_fixed(bool $fixed)
    {
        $this->_fixed_layout = $fixed;
    }

    
    public function is_layout_fixed()
    {
        return $this->_fixed_layout;
    }

    
    public function get_num_rows()
    {
        return $this->_num_rows;
    }

    
    public function get_num_cols()
    {
        return $this->_num_cols;
    }

    
    public function &get_columns()
    {
        return $this->_columns;
    }

    
    public function set_columns($columns)
    {
        $this->_columns = $columns;
    }

    
    public function &get_column($i)
    {
        if (!isset($this->_columns[$i])) {
            $this->_columns[$i] = [
                "x"          => 0,
                "min-width"  => 0,
                "max-width"  => 0,
                "used-width" => null,
                "absolute"   => 0,
                "percent"    => 0,
                "auto"       => true,
            ];
        }

        return $this->_columns[$i];
    }

    
    public function &get_rows()
    {
        return $this->_rows;
    }

    
    public function &get_row($j)
    {
        if (!isset($this->_rows[$j])) {
            $this->_rows[$j] = [
                "y"            => 0,
                "first-column" => 0,
                "height"       => null,
            ];
        }

        return $this->_rows[$j];
    }

    
    public function get_border($i, $j, $h_v, $prop = null)
    {
        if (!isset($this->_borders[$i][$j][$h_v])) {
            $this->_borders[$i][$j][$h_v] = [
                "width" => 0,
                "style" => "solid",
                "color" => "black",
            ];
        }

        if (isset($prop)) {
            return $this->_borders[$i][$j][$h_v][$prop];
        }

        return $this->_borders[$i][$j][$h_v];
    }

    
    public function get_border_properties($i, $j)
    {
        return [
            "top"    => $this->get_border($i, $j, "horizontal"),
            "right"  => $this->get_border($i, $j + 1, "vertical"),
            "bottom" => $this->get_border($i + 1, $j, "horizontal"),
            "left"   => $this->get_border($i, $j, "vertical"),
        ];
    }

    
    public function get_spanned_cells(Frame $frame)
    {
        $key = $frame->get_id();

        if (isset($this->_frames[$key])) {
            return $this->_frames[$key];
        }

        return null;
    }

    
    public function frame_exists_in_cellmap(Frame $frame)
    {
        $key = $frame->get_id();

        return isset($this->_frames[$key]);
    }

    
    public function get_frame_position(Frame $frame)
    {
        global $_dompdf_warnings;

        $key = $frame->get_id();

        if (!isset($this->_frames[$key])) {
            throw new Exception("Frame not found in cellmap");
        }

        
        [$table_x, $table_y] = $this->_table->get_position();
        $col = $this->_frames[$key]["columns"][0];
        $row = $this->_frames[$key]["rows"][0];

        if (!isset($this->_columns[$col])) {
            $_dompdf_warnings[] = "Frame not found in columns array.  Check your table layout for missing or extra TDs.";
            $x = $table_x;
        } else {
            $x = $table_x + $this->_columns[$col]["x"];
        }

        if (!isset($this->_rows[$row])) {
            $_dompdf_warnings[] = "Frame not found in row array.  Check your table layout for missing or extra TDs.";
            $y = $table_y;
        } else {
            $y = $table_y + $this->_rows[$row]["y"];
        }

        return [$x, $y, "x" => $x, "y" => $y];
    }

    
    public function get_frame_width(Frame $frame)
    {
        $key = $frame->get_id();

        if (!isset($this->_frames[$key])) {
            throw new Exception("Frame not found in cellmap");
        }

        $cols = $this->_frames[$key]["columns"];
        $w = 0;
        foreach ($cols as $i) {
            $w += $this->_columns[$i]["used-width"];
        }

        return $w;
    }

    
    public function get_frame_height(Frame $frame)
    {
        $key = $frame->get_id();

        if (!isset($this->_frames[$key])) {
            throw new Exception("Frame not found in cellmap");
        }

        $rows = $this->_frames[$key]["rows"];
        $h = 0;
        foreach ($rows as $i) {
            if (!isset($this->_rows[$i])) {
                throw new Exception("The row #$i could not be found, please file an issue in the tracker with the HTML code");
            }

            $h += $this->_rows[$i]["height"];
        }

        return $h;
    }

    
    public function set_column_width($j, $width)
    {
        if ($this->_columns_locked) {
            return;
        }

        $col =& $this->get_column($j);
        $col["used-width"] = $width;
        $next_col =& $this->get_column($j + 1);
        $next_col["x"] = $col["x"] + $width;
    }

    
    public function set_row_height($i, $height)
    {
        $row =& $this->get_row($i);
        if ($height > $row["height"]) {
            $row["height"] = $height;
        }
        $next_row =& $this->get_row($i + 1);
        $next_row["y"] = $row["y"] + $row["height"];
    }

    
    protected function resolve_border(int $i, int $j, string $h_v, array $border_spec): void
    {
        if (!isset($this->_borders[$i][$j][$h_v])) {
            $this->_borders[$i][$j][$h_v] = $border_spec;
            return;
        }

        $border = $this->_borders[$i][$j][$h_v];

        $n_width = $border_spec["width"];
        $n_style = $border_spec["style"];
        $o_width = $border["width"];
        $o_style = $border["style"];

        if ($o_style === "hidden") {
            return;
        }

        
        
        if ($n_style === "hidden" || $n_width > $o_width
            || ($o_width == $n_width
                && isset(self::BORDER_STYLE_SCORE[$n_style])
                && isset(self::BORDER_STYLE_SCORE[$o_style])
                && self::BORDER_STYLE_SCORE[$n_style] > self::BORDER_STYLE_SCORE[$o_style])
        ) {
            $this->_borders[$i][$j][$h_v] = $border_spec;
        }
    }

    
    protected function get_resolved_border(AbstractFrameDecorator $frame): array
    {
        $key = $frame->get_id();
        $columns = $this->_frames[$key]["columns"];
        $rows = $this->_frames[$key]["rows"];

        $first_col = $columns[0];
        $last_col = $columns[count($columns) - 1];
        $first_row = $rows[0];
        $last_row = $rows[count($rows) - 1];

        $max_top = null;
        $max_bottom = null;
        $max_left = null;
        $max_right = null;

        foreach ($columns as $col) {
            $top = $this->_borders[$first_row][$col]["horizontal"];
            $bottom = $this->_borders[$last_row + 1][$col]["horizontal"];

            if ($max_top === null || $top["width"] > $max_top["width"]) {
                $max_top = $top;
            }
            if ($max_bottom === null || $bottom["width"] > $max_bottom["width"]) {
                $max_bottom = $bottom;
            }
        }

        foreach ($rows as $row) {
            $left = $this->_borders[$row][$first_col]["vertical"];
            $right = $this->_borders[$row][$last_col + 1]["vertical"];

            if ($max_left === null || $left["width"] > $max_left["width"]) {
                $max_left = $left;
            }
            if ($max_right === null || $right["width"] > $max_right["width"]) {
                $max_right = $right;
            }
        }

        return [$max_top, $max_right, $max_bottom, $max_left];
    }

    
    public function add_frame(Frame $frame): void
    {
        $style = $frame->get_style();
        $display = $style->display;

        $collapse = $this->_table->get_style()->border_collapse === "collapse";

        
        if ($frame === $this->_table
            || $display === "table-row"
            || in_array($display, TableFrameDecorator::ROW_GROUPS, true)
        ) {
            $start_row = $this->__row;

            foreach ($frame->get_children() as $child) {
                $this->add_frame($child);
            }

            if ($display === "table-row") {
                $this->add_row();
            }

            $num_rows = $this->__row - $start_row - 1;
            $key = $frame->get_id();

            
            $this->_frames[$key]["columns"] = range(0, max(0, $this->_num_cols - 1));
            $this->_frames[$key]["rows"] = range($start_row, max(0, $this->__row - 1));
            $this->_frames[$key]["frame"] = $frame;

            if ($collapse) {
                $bp = $style->get_border_properties();

                
                for ($i = 0; $i < $num_rows + 1; $i++) {
                    $this->resolve_border($start_row + $i, 0, "vertical", $bp["left"]);
                    $this->resolve_border($start_row + $i, $this->_num_cols, "vertical", $bp["right"]);
                }

                
                for ($j = 0; $j < $this->_num_cols; $j++) {
                    $this->resolve_border($start_row, $j, "horizontal", $bp["top"]);
                    $this->resolve_border($this->__row, $j, "horizontal", $bp["bottom"]);
                }

                if ($frame === $this->_table) {
                    
                    
                    
                    [$top, $right, $bottom, $left] = $this->get_resolved_border($frame);

                    $style->set_used("border_top_width", $top["width"] / 2);
                    $style->set_used("border_right_width", $right["width"] / 2);
                    $style->set_used("border_bottom_width", $bottom["width"] / 2);
                    $style->set_used("border_left_width", $left["width"] / 2);
                    $style->set_used("border_style", "none");
                }
            }

            if ($frame !== $this->_table) {
                
                
                
                $style->set_used("border_width", 0);
                $style->set_used("border_style", "none");
            }

            if ($frame === $this->_table) {
                
                
                $this->calculate_column_widths();
            }
            return;
        }

        
        $key = $frame->get_id();
        $node = $frame->get_node();
        $bp = $style->get_border_properties();

        
        $colspan = max((int) $node->getAttribute("colspan"), 1);
        $rowspan = max((int) $node->getAttribute("rowspan"), 1);

        
        $ac = $this->__col;
        while (isset($this->_cells[$this->__row][$ac])) {
            $ac++;
        }

        $this->__col = $ac;

        
        for ($i = 0; $i < $rowspan; $i++) {
            $row = $this->__row + $i;

            $this->_frames[$key]["rows"][] = $row;

            for ($j = 0; $j < $colspan; $j++) {
                $this->_cells[$row][$this->__col + $j] = $frame;
            }

            if ($collapse) {
                
                $this->resolve_border($row, $this->__col, "vertical", $bp["left"]);
                $this->resolve_border($row, $this->__col + $colspan, "vertical", $bp["right"]);
            }
        }

        
        for ($j = 0; $j < $colspan; $j++) {
            $col = $this->__col + $j;
            $this->_frames[$key]["columns"][] = $col;

            if ($collapse) {
                
                $this->resolve_border($this->__row, $col, "horizontal", $bp["top"]);
                $this->resolve_border($this->__row + $rowspan, $col, "horizontal", $bp["bottom"]);
            }
        }

        $this->_frames[$key]["frame"] = $frame;

        $this->__col += $colspan;
        if ($this->__col > $this->_num_cols) {
            $this->_num_cols = $this->__col;
        }
    }

    
    protected function calculate_column_widths(): void
    {
        $table = $this->_table;
        $table_style = $table->get_style();
        $collapse = $table_style->border_collapse === "collapse";

        if ($collapse) {
            $v_spacing = 0;
            $h_spacing = 0;
        } else {
            
            [$h, $v] = $table_style->border_spacing;
            $v_spacing = $v / 2;
            $h_spacing = $h / 2;
        }

        foreach ($this->_frames as $frame_info) {
            
            $frame = $frame_info["frame"];
            $style = $frame->get_style();
            $display = $style->display;

            if ($display !== "table-cell") {
                continue;
            }

            if ($collapse) {
                
                [$top, $right, $bottom, $left] = $this->get_resolved_border($frame);

                $style->set_used("border_top_width", $top["width"] / 2);
                $style->set_used("border_top_style", $top["style"]);
                $style->set_used("border_top_color", $top["color"]);
                $style->set_used("border_right_width", $right["width"] / 2);
                $style->set_used("border_right_style", $right["style"]);
                $style->set_used("border_right_color", $right["color"]);
                $style->set_used("border_bottom_width", $bottom["width"] / 2);
                $style->set_used("border_bottom_style", $bottom["style"]);
                $style->set_used("border_bottom_color", $bottom["color"]);
                $style->set_used("border_left_width", $left["width"] / 2);
                $style->set_used("border_left_style", $left["style"]);
                $style->set_used("border_left_color", $left["color"]);
                $style->set_used("margin", 0);
            } else {
                
                $style->set_used("margin_top", $v_spacing);
                $style->set_used("margin_bottom", $v_spacing);
                $style->set_used("margin_left", $h_spacing);
                $style->set_used("margin_right", $h_spacing);
            }

            if ($this->_columns_locked) {
                continue;
            }

            $node = $frame->get_node();
            $colspan = max((int) $node->getAttribute("colspan"), 1);
            $first_col = $frame_info["columns"][0];

            
            if ($this->_fixed_layout) {
                list($frame_min, $frame_max) = [0, 10e-10];
            } else {
                list($frame_min, $frame_max) = $frame->get_min_max_width();
            }

            $width = $style->width;

            $val = null;
            if (Helpers::is_percent($width) && $colspan === 1) {
                $var = "percent";
                $val = (float)rtrim($width, "% ");
            } elseif ($width !== "auto" && $colspan === 1) {
                $var = "absolute";
                $val = $frame_min;
            }

            $min = 0;
            $max = 0;
            for ($cs = 0; $cs < $colspan; $cs++) {

                
                $col =& $this->get_column($first_col + $cs);

                
                
                
                if (isset($var) && $val > $col[$var]) {
                    $col[$var] = $val;
                    $col["auto"] = false;
                }

                $min += $col["min-width"];
                $max += $col["max-width"];
            }

            if ($frame_min > $min && $colspan === 1) {
                
                
                $inc = ($this->is_layout_fixed() ? 10e-10 : ($frame_min - $min));
                for ($c = 0; $c < $colspan; $c++) {
                    $col =& $this->get_column($first_col + $c);
                    $col["min-width"] += $inc;
                }
            }

            if ($frame_max > $max) {
                
                $inc = ($this->is_layout_fixed() ? 10e-10 : ($frame_max - $max) / $colspan);
                for ($c = 0; $c < $colspan; $c++) {
                    $col =& $this->get_column($first_col + $c);
                    $col["max-width"] += $inc;
                }
            }
        }

        
        
        
        foreach ($this->_columns as &$col) {
            if ($col["absolute"] > 0) {
                $col["absolute"] = $col["min-width"];
                $col["max-width"] = $col["min-width"];
            }
        }
    }

    protected function add_row(): void
    {
        $this->__row++;
        $this->_num_rows++;

        
        $i = 0;
        while (isset($this->_cells[$this->__row][$i])) {
            $i++;
        }

        $this->__col = $i;
    }

    
    public function remove_row(Frame $row)
    {
        $key = $row->get_id();
        if (!isset($this->_frames[$key])) {
            return; 
        }

        $this->__row = $this->_num_rows--;

        $rows = $this->_frames[$key]["rows"];
        $columns = $this->_frames[$key]["columns"];

        
        foreach ($rows as $r) {
            foreach ($columns as $c) {
                if (isset($this->_cells[$r][$c])) {
                    $id = $this->_cells[$r][$c]->get_id();

                    $this->_cells[$r][$c] = null;
                    unset($this->_cells[$r][$c]);

                    
                    if (isset($this->_frames[$id]) && count($this->_frames[$id]["rows"]) > 1) {
                        
                        if (($row_key = array_search($r, $this->_frames[$id]["rows"])) !== false) {
                            unset($this->_frames[$id]["rows"][$row_key]);
                        }
                        continue;
                    }

                    $this->_frames[$id] = null;
                    unset($this->_frames[$id]);
                }
            }

            $this->_rows[$r] = null;
            unset($this->_rows[$r]);
        }

        $this->_frames[$key] = null;
        unset($this->_frames[$key]);
    }

    
    public function remove_row_group(Frame $group)
    {
        $key = $group->get_id();
        if (!isset($this->_frames[$key])) {
            return; 
        }

        $iter = $group->get_first_child();
        while ($iter) {
            $this->remove_row($iter);
            $iter = $iter->get_next_sibling();
        }

        $this->_frames[$key] = null;
        unset($this->_frames[$key]);
    }

    
    public function update_row_group(Frame $group, Frame $last_row)
    {
        $g_key = $group->get_id();

        $first_index = $this->_frames[$g_key]["rows"][0];
        $last_index = $first_index;
        $row = $last_row;
        while ($row = $row->get_prev_sibling()) {
            $last_index++;
        }

        $this->_frames[$g_key]["rows"] = range($first_index, $last_index);
    }

    public function assign_x_positions(): void
    {
        
        

        if ($this->_columns_locked) {
            return;
        }

        $x = $this->_columns[0]["x"];
        foreach (array_keys($this->_columns) as $j) {
            $this->_columns[$j]["x"] = $x;
            $x += $this->_columns[$j]["used-width"];
        }
    }

    public function assign_frame_heights(): void
    {
        
        
        foreach ($this->_frames as $arr) {
            $frame = $arr["frame"];

            $h = 0.0;
            foreach ($arr["rows"] as $row) {
                if (!isset($this->_rows[$row])) {
                    
                    continue;
                }

                $h += $this->_rows[$row]["height"];
            }

            if ($frame instanceof TableCellFrameDecorator) {
                $frame->set_cell_height($h);
            } else {
                $frame->get_style()->set_used("height", $h);
            }
        }
    }

    
    public function set_frame_heights(float $table_height, float $content_height): void
    {
        
        foreach ($this->_frames as $arr) {
            $frame = $arr["frame"];

            $h = 0.0;
            foreach ($arr["rows"] as $row) {
                if (!isset($this->_rows[$row])) {
                    continue;
                }

                $h += $this->_rows[$row]["height"];
            }

            if ($content_height > 0) {
                $new_height = ($h / $content_height) * $table_height;
            } else {
                $new_height = 0.0;
            }

            if ($frame instanceof TableCellFrameDecorator) {
                $frame->set_cell_height($new_height);
            } else {
                $frame->get_style()->set_used("height", $new_height);
            }
        }
    }

    
    public function __toString(): string
    {
        $str = "";
        $str .= "Columns:<br/>";
        $str .= Helpers::pre_r($this->_columns, true);
        $str .= "Rows:<br/>";
        $str .= Helpers::pre_r($this->_rows, true);

        $str .= "Frames:<br/>";
        $arr = [];
        foreach ($this->_frames as $key => $val) {
            $arr[$key] = ["columns" => $val["columns"], "rows" => $val["rows"]];
        }

        $str .= Helpers::pre_r($arr, true);

        if (php_sapi_name() == "cli") {
            $str = strip_tags(str_replace(["<br/>", "<b>", "</b>"],
                ["\n", chr(27) . "[01;33m", chr(27) . "[0m"],
                $str));
        }

        return $str;
    }
}
