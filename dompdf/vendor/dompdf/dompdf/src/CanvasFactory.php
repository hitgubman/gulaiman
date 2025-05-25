<?php

namespace Dompdf;


class CanvasFactory
{
    
    private function __construct()
    {
    }

    
    static function get_instance(Dompdf $dompdf, $paper, string $orientation, ?string $class = null)
    {
        $backend = strtolower($dompdf->getOptions()->getPdfBackend());

        if (isset($class) && class_exists($class, false)) {
            $class .= "_Adapter";
        } else {
            if (($backend === "auto" || $backend === "pdflib") &&
                class_exists("PDFLib", false)
            ) {
                $class = "Dompdf\\Adapter\\PDFLib";
            }

            else {
                if ($backend === "gd" && extension_loaded('gd')) {
                    $class = "Dompdf\\Adapter\\GD";
                } else {
                    $class = "Dompdf\\Adapter\\CPDF";
                }
            }
        }

        return new $class($paper, $orientation, $dompdf);
    }
}
