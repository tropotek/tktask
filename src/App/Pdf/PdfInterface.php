<?php
namespace App\Pdf;

use Bs\Registry;
use Dom\Template;
use Mpdf\Mpdf;
use Tk\Config;

abstract class PdfInterface extends \Dom\Renderer\Renderer
{
    const string OUTPUT_PDF    = 'pdf';
    const string OUTPUT_HTML   = 'html';
    const string OUTPUT_ATTACH = 'attach';

    protected ?Mpdf   $mpdf      = null;
    protected string  $filename  = 'untitled.pdf';
    protected string  $title     = 'Untitled';


    public function __construct()
    {
        $tempDir = Config::makePath(Config::getTempPath());
        $this->mpdf = new \Mpdf\Mpdf(array(
			'format' => 'A4-P',
            'orientation' => 'P',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 20,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => $tempDir
        ));

        $this->mpdf->SetTitle($this->title);
        $this->mpdf->SetAuthor(Registry::instance()->get('site.name', 'Unknown'));
        $this->mpdf->curlAllowUnsafeSslRequests = true;
        //$this->mpdf->showImageErrors = true;

        $this->mpdf->showWatermarkText = true;
        $this->mpdf->watermark_font = 'DejaVuSansCondensed';
        $this->mpdf->watermarkTextAlpha = 0.08;

        $this->mpdf->SetDisplayMode('fullpage');

    }

    /**
     * Output the pdf to the browser
     */
    public function getPdf(): string
    {
        if (!$this->filename) {
            $this->filename = basename(\Tk\Uri::create()->getPath()) . '.pdf';
        }
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies
        $this->mpdf->Output($this->filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    /**
     * Return the PDF as a string to attach to an email message
     */
    public function getPdfAttachment(): string
    {
        if (!$this->filename) {
            $this->filename = basename(\Tk\Uri::create()->getPath()) . '.pdf';
        }
        return $this->mpdf->Output($this->filename, \Mpdf\Output\Destination::STRING_RETURN);
    }


    public function getWatermark(): ?string
    {
        return $this->mpdf->watermarkText;
    }

    public function setWatermark(string $watermark): PdfInterface
    {
        $this->mpdf->SetWatermarkText($watermark);

        if (!empty($this->getWatermark())) {
            $this->mpdf->showWatermarkText = true;
        } else {
            $this->mpdf->showWatermarkText = false;
        }
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title></title>
</head>
<body class="" style="" var="body">
  <div var="content"></div>
</body>
</html>
HTML;
        return Template::load($html);
    }

}