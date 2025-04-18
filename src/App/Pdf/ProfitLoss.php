<?php

namespace App\Pdf;

use App\Db\Project;
use App\Db\Task;
use App\Db\User;
use App\Factory;
use Bs\Registry;
use Bs\Ui\Breadcrumbs;
use Dom\Template;
use Tk\Config;
use Tk\Date;
use Tk\Db\Filter;
use Tk\Log;
use Tk\Str;

class ProfitLoss extends PdfInterface
{

    protected ?\App\Ui\ProfitLoss $report = null;
    protected array $dateSet = [];

    public function doDefault(): string
    {
        //@ini_set("memory_limit", "128M");

        $date      = trim($_GET['date'] ?? $_POST['date'] ?? 'now');
        $output    = trim($_GET['o'] ?? $_POST['o'] ?? PdfInterface::OUTPUT_PDF);

        $this->dateSet = Date::getFinancialYear(new \DateTime($date));
        $this->report = new \App\Ui\ProfitLoss($this->dateSet);

        $siteCompany = Factory::instance()->getOwnerCompany();
        $this->SetTitle('Profit & Loss Report');
        $this->mpdf->SetAuthor($siteCompany->name);

        $dateStr = Date::create()->format(Date::FORMAT_ISO_DATE);
        $this->setFilename('ProfitReport-' . $dateStr . '.pdf');

        $this->mpdf->WriteHTML($this->show()->toString());
        return match ($output) {
            PdfInterface::OUTPUT_PDF => $this->getPdf(),
            PdfInterface::OUTPUT_ATTACH => $this->getPdfAttachment(),
            default => $this->getTemplate()->toString()
        };
    }

    function show(): ?Template
    {
        $template = $this->getTemplate();

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

        if (is_file(Config::makePath('/src/App/Pdf/pdfStyles.css'))) {
            $pdfStyles = (string)file_get_contents(Config::makePath('/src/App/Pdf/pdfStyles.css'));
            $template->appendCss($pdfStyles);
        }

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