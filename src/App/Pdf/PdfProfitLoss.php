<?php
namespace App\Pdf;

use App\Factory;
use Dom\Renderer\Renderer;
use Dom\Template;
use JetBrains\PhpStorm\NoReturn;
use Mpdf\Mpdf;
use Tk\Config;
use Tk\Date;
use Tk\Uri;

class PdfProfitLoss extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Mpdf  $mpdf;
    protected bool  $rendered = false;
    protected ?\App\Component\ProfitLoss $report = null;
    protected array $dateSet;


    public function __construct(array $dateset)
    {
        $this->dateSet = $dateset;
        $this->report = new \App\Component\ProfitLoss($dateset);
        $this->init();
    }

    protected function init(): void
    {
        $url = Uri::create()->toString();
        $html = $this->show()->toString();
        $this->mpdf = new Mpdf(array(
            'margin_top' => 20,
        ));
        $mpdf = $this->mpdf;
        $mpdf->setBasePath($url);

        $siteCompany = Factory::instance()->getOwnerCompany();
        $mpdf->SetTitle('Profit & Loss Report');
        $mpdf->SetAuthor($siteCompany->name);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
    }

    #[NoReturn] public function output(): void
    {
        $dateStr = (new \DateTime())->format(Date::FORMAT_ISO_DATE);
        $filename = 'ProfitReport-' . $dateStr . '.pdf';
        $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    public function getPdfAttachment(string $filename = ''): string
    {
        $dateStr = (new \DateTime())->format(Date::FORMAT_ISO_DATE);
        if (!$filename) $filename = 'ProfitReport-' . $dateStr . '.pdf';
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        if ($this->rendered) return $template;
        $this->rendered = true;

        $siteCompany = Factory::instance()->getOwnerCompany();
        $template->setText('shop-name', $siteCompany->name);
        $template->setHtml('shop-address', nl2br($siteCompany->address));
        if ($siteCompany->abn) {
            $template->setText('abn', 'ABN: ' . $siteCompany->abn);
            $template->setVisible('abn');
        }
        $template->setText('date', $this->dateSet[0]->format('Y') . '-' . $this->dateSet[1]->format('Y'));

        // Setup page
        $template->setTitleText('Profit Report');

        if ($this->report) {
            $template->appendTemplate('table', $this->report->show());
        }

        $pdfStyles = file_get_contents(Config::makePath('/src/App/Pdf/pdfStyles.css'));
        $template->appendCss($pdfStyles);

        return $template;
    }

    public function __makeTemplate(): Template
    {
        $xhtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title></title>

<style>
.pr-table h1,
.pr-table h2,
.pr-table h3,
.pr-table h4 {
  text-align: center;
}
.pr-table table {
  width: 70%;
  margin: 30px auto;
}
.pr-table table td {
  padding-top: 10px;
}
.pr-table table .header td {
  padding-top: 20px;
}

</style>

</head>
<body class="">

   <htmlpageheader name="myheader">
     <table class="w-100">
       <tr>
         <td style="color:#0000BB;">
           <span style="font-weight: bold; font-size: 14pt;" var="shop-name">Acme Trading Co.</span> &nbsp;
           <span style="font-family:dejavusanscondensed,sans-serif;" var="abn" choice="abn">ABN: 01777 123 567</span>
         </td>
       </tr>
     </table>
   </htmlpageheader>
   <htmlpagefooter name="myfooter" style="display: none;">
     <div style=" font-size: 9pt; text-align: center; padding-top: 3mm; ">
       Page {PAGENO} of {nb}
     </div>
   </htmlpagefooter>
   <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
   <sethtmlpagefooter name="myfooter" value="on" />

  <div var="table" class="pr-table">
    <h2>Profit &amp; Loss Statement</h2>
    <h3 var="date">2016-2017</h3>
  </div>

</body>
</html>
HTML;

        return Template::load($xhtml);
    }

}