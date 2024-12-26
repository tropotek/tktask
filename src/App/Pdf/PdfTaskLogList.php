<?php
namespace App\Pdf;

use App\Controller\TaskLog\Hours;
use App\Db\Permission;
use App\Db\Project;
use App\Db\TaskLog;
use App\Db\TaskLogMap;
use App\Factory;
use Bs\Uri;
use Dom\Renderer\Renderer;
use Dom\Template;
use JetBrains\PhpStorm\NoReturn;
use Mpdf\Mpdf;
use Tk\ConfigTrait;
use Tk\Date;
use Tk\Db\Map\ArrayObject;
use Tk\Form\Field\DateRange;
use Tk\Form\Field\Select;
use Tk\Str;

class PdfTaskLogList extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    protected Mpdf    $mpdf;
    protected bool    $rendered  = false;

    /**
     * @var array<int,TaskLog>
     */
    protected array $list;


    /**
     * @param array<int,TaskLog> $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
        $this->init();
    }

    protected function init(): void
    {
        $url = \Tk\Uri::create()->toString();

        $html = $this->show()->toString();
        $this->mpdf = new Mpdf([
            'margin_top' => 20,
        ]);
        $mpdf = $this->mpdf;
        $mpdf->setBasePath($url);

        $siteCompany = Factory::instance()->getOwnerCompany();
        $mpdf->SetTitle('Task Log List');
        $mpdf->SetAuthor($siteCompany->name);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
    }

    public function getFilename(): string
    {
        $dateStr = Date::create()->format(Date::FORMAT_ISO_DATE);
        return 'TaskList_' . $dateStr . '.pdf';
    }

    #[NoReturn] public function output(): void
    {
        $this->mpdf->Output($this->getFilename(), \Mpdf\Output\Destination::INLINE);
        exit;
    }

    #[NoReturn] public function download(): void
    {
        $this->mpdf->Output($this->getFilename(), \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    /**
     * Return the PDF as a string to attach to an email message
     */
    public function getPdfAttachment(string $filename = ''): ?string
    {
        if (!$filename) {
            $filename = $this->getFilename();
        }
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        if ($this->rendered) return $template;
        $this->rendered = true;
        $template->setText('shop-name', 'Time Worked');

//        $this->setTable(\App\Table\TaskLog::create('task-log-manager')->init());
//        $this->getTable()->removeCell('actions');
//        $this->getTable()->removeCell('id');
//        if (!$this->getConfig()->get('site.invoice.enable')) {
//            $this->getTable()->removeCell('productId');
//        }
//        $this->getTable()->findCell('comment')->addOnCellHtml(function (\Tk\Table\Cell\Iface $cell, TaskLog $obj, $html) {
//            if ($obj->getTask()) {
//                $url = Uri::create('/staff/taskEdit.html')->set('taskId', $obj->getTaskId());
//                $html = sprintf('<div><p>[TID%s]: <a href="%s" title="Task Title">%s</a></p><br/><div class="td-task-log">%s</div></div>', $obj->getTaskId(), $url, $obj->getTask()->getSubject(), $html);
//            }
//            return $html;
//        });

//        $this->getTable()->setFilterList();
//        $this->getTable()->setActionList();
//        $this->getTable()->setList($this->list);
//
//        $this->getTable()->getRenderer()->enableFooter(false);
//        $template->appendTemplate('table', $this->getTable()->show());

        $totalMinutes = 0;
        $taskIds = [];
        foreach ($this->list as $log) {
            $totalMinutes += $log->minutes;
            $taskIds[$log->taskId] = $log->taskId;
        }
        $totalTasks = count($taskIds);

        $template->setText('time', $this->secondsToDHM($totalMinutes));
        $template->setText('tasks', $totalTasks);
        $template->setText('taskLogs', count($this->list));



        // Get Dates searched:
//        $start = Hours::getPayWeekStart();
//        $end = $start->add(new \DateInterval('P13D'));
        if ($this->table) {
            $filterVals = $this->table->getFilterSession();
            $dates = '';
            if ($filterVals->get('dateStart')) {
                $dateStart = Date::createFormDate($filterVals->get('dateStart'));
                if ($dateStart instanceof \DateTime)
                    $dates .= $dateStart->format(Date::FORMAT_MED_DATE);
            }
            if ($filterVals->get('dateEnd')) {
                $dateEnd = Date::createFormDate($filterVals->get('dateEnd'));
                if ($dateEnd instanceof \DateTime)
                    $dates .= ' - ' . $dateEnd->format(Date::FORMAT_MED_DATE);
            }
            if ($dates) {
                $template->setText('dates', $dates);
                $template->setVisible('dates');
            }
        }


        $css = <<<CSS
.tk-foot, .modal {
  display: none;
  visibility: hidden;
}

.tk-table table {
  width: 100%;
}
.tk-table tr {
  background: #FEFEFE;
  margin-bottom: 15px;
  padding: 5px;
  page-break-inside: avoid;
}

.tk-table td, .tk-table th {
  padding: 5px 4px;
  text-align: center;
}
.tk-table th {
  font-size: 0.8em;
  background-color: #EFEFEF;
}
div.td-task-log {

}
.tk-table td {
  font-size: 0.8em;
  height: 90px;
  vertical-align: top;
  border-top: 1px solid #CCC;
}
.tk-table td.key {
  text-align: left;
}
.tk-table td.mProjectId {
}

.tk-table td.mSubject .logs .log {
  padding: 15px;
  border-bottom: 1px solid #DFDFDF;
  background-color: #EFEFEF;
  margin-bottom: 10px;
  font-weight: normal;
}

.tk-table .mSubject .subject {
  margin: 0 0 0 0;
  font-weight: bold;
}

.tk-table td.mSubject .logs {
  padding-left: 10px;
}


.tk-table td.mSubject .logs .log span.time {
  color: #333;
  padding: 0;
  margin: 0;
}

.tk-table td.mSubject .logs .log span.time.billed {
  color: #933;
  font-weight: 600;
}


.text-center { text-align: center }
.primary, .badge-primary, .label-primary { color: #0399e2; }
.secondary, .badge-secondary, .label-secondary { color: #5d6a72; }
.success, .badge-success, .label-success { color: #7cb044; }
.warning, .badge-warning, .label-warning { color: #e3ad07; }
.danger, .badge-danger, .label-danger { color: #e24c3f; }
CSS;
        $template->appendCss($css);

        return $template;
    }


    protected function secondsToDHM($minutes) {
        $s = (int)$minutes*60;
        $str = '';
        if ((int)($s/86400) > 0)
            $str .= sprintf('%d days ', $s/86400);

        return sprintf('%s%02d:%02d', $str, $s/3600%24, $s/60%60);
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title></title>
</head>
<body class="">

     <htmlpageheader name="myheader">
     <table width="100%" style="">
       <tr>
         <td width="50%" style="color:#0000BB; ">
           <span style="font-weight: bold; font-size: 14pt;" var="shop-name">tropotek.com.au</span> &nbsp;
           <span style="font-family:dejavusanscondensed;" var="abn" choice="abn">ABN: 01777 123 567</span>
         </td>
         <td width="50%" style="text-align: right;"></td>
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

<div>


  <h2 class="text-center"><span var="title"></span></h2>
  <div class="details">
    <div>
      <div class="profile-details">
        <ul class="fa-ul">
          <li choice="dates">Dates: <span var="dates"></span></li>
          <li>Time (hh:mm): <span var="time">00:00</span></li>
          <li>Tasks: <span var="tasks">0</span></li>
          <li>Task Logs: <span var="taskLogs">0</span></li>
        </ul>
      </div>
      <hr/>
    </div>
    <div var="table"></div>
  </div>
</div>

</body>
</html>
HTML;

        return \Dom\Loader::load($xhtml);
    }

    /**
     * @return \App\Table\TaskLog
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param \App\Table\TaskLog $table
     * @return PdfTaskLogList
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

}