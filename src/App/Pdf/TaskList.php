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

class TaskList extends PdfInterface
{
    /**
     * @var array<int,Task>
     */
    protected array    $taskList = [];
    protected ?Project $project  = null;
    protected ?User    $user     = null;

    public function doDefault(): string
    {
        //@ini_set("memory_limit", "128M");

        $projectId = intval($_GET['projectId'] ?? $_POST['projectId'] ?? 0);
        $userId    = intval($_GET['userId'] ?? $_POST['userId'] ?? 0);
        $output    = trim($_GET['o'] ?? $_POST['o'] ?? PdfInterface::OUTPUT_PDF);

        $this->project = Project::find($projectId);
        if ($projectId && !($this->project instanceof Project)) {
            Log::error("invalid project id {$projectId}");
            Breadcrumbs::getBackUrl()->redirect();
        }

        $this->user = User::find($userId);
        if ($userId && !($this->user instanceof User)) {
            Log::error("invalid user id {$userId}");
            Breadcrumbs::getBackUrl()->redirect();
        }

        $filter = [
            'status' => [
                Task::STATUS_PENDING,
                Task::STATUS_HOLD,
                Task::STATUS_OPEN,
            ]
        ];
        if ($userId) {
            $filter['userId'] = $userId;
        }
        $this->taskList = Task::findFiltered(Filter::create($filter, '-created'));

        $siteCompany = Factory::instance()->getOwnerCompany();
        $this->SetTitle('Task List');
        $this->mpdf->SetAuthor($siteCompany->name);
        $dateStr = Date::create()->format(Date::FORMAT_ISO_DATE);
        $this->setFilename('TaskList_' . $dateStr . '.pdf');

        $this->mpdf->WriteHTML($this->show()->toString());
        return match ($output) {
            PdfInterface::OUTPUT_PDF => $this->getPdf() ?? '',
            PdfInterface::OUTPUT_ATTACH => $this->getPdfAttachment(),
            default => $this->getTemplate()->toString()
        };
    }

    function show(): ?Template
    {
        $template = $this->getTemplate();

        foreach ($this->taskList as $i => $task) {
            $css = (($i%2) == 0) ? 'even' : 'odd';

            $row = $template->getRepeat('row');
            $row->addCss('row', $css);
            $row->setText('taskId', $task->taskId);
            $row->setText('subject', $task->subject);

            $project = $this->project;
            if (is_null($project)) {
                $project = Project::find(intval($task->projectId));
            }

            if ($project instanceof Project) {
                $row->setText('project', $project->name);
            } else {
                $row->setText('project', 'N/A');
            }
            $row->setVisible('project');

//            $cat = $task->getTaskCategory();
//            if ($cat instanceof TaskCategory) {
//                $row->setText('category', e($cat->name));
//            }
            $row->setText('status', Task::STATUS_LIST[$task->status]);
            $row->addCss('status', Task::STATUS_CSS[$task->status]);
            $row->setText('priority', Task::PRIORITY_LIST[$task->priority]);
            $row->addCss('priority', Task::PRIORITY_CSS[$task->priority]);

            $row->setText('minutes', $this->secondsToDHM($task->minutes));
            $row->setText('minutesTotal', $this->secondsToDHM($task->getCompletedTime()));
            $row->setText('cost', $task->getEstimatedCost());
            $row->setText('costTotal', $task->getCost());

            $row->appendRepeat('hook');

            $desc = $template->getRepeat('row-desc');
            $desc->addCss('row-desc', $css);
            if (is_null($this->project)) {
                $desc->setAttr('desc', 'colspan', '8');
            }
            $html = '';
            if ($task->comments) {
                $html .= sprintf('<div class="comments">%s</div><br/>', Str::wordcat(trim(strip_tags($task->comments)), 500, ' ...'));
            }
            $logs = $task->getLogList();
            $str = '';
            foreach ($logs as $log) {
                $billed = '';
                $billedCss = '';
                $title = 'Time Worked';
                if ($log->billable) {
                    $billed = '*';
                    $billedCss = 'billed';
                    $title = 'Billable Time Worked';
                }
                $str .= sprintf('<div class="log" style="font-style: italic;font-size: 0.8em;"><span class="time %s" title="%s">[%s%s]</span> %s <br/><br/></div>',
                    $billedCss, $title, $billed, $this->secondsToDHM($log->minutes), Str::wordcat(trim(strip_tags($log->comment, '<strong><em><b><i><a>')), 300, ' ...'));
            }
            if ($str) $str = sprintf('<div class="logs">%s</div>', $str);
            $html .=  sprintf('<div class="task-logs">%s</div>', $str);

            $desc->setHtml('desc', $html);
            $desc->appendRepeat('hook');
        }

        if (count($this->taskList) && truefalse(Registry::instance()->get('site.invoice.enable', false))) {
            $template->setVisible('is-billable');
        }
        $template->setText('task-count', count($this->taskList));

        if ($this->project) {
            $template->setText('title', $this->project->name);
            $template->setText('project-name', $this->project->name);

            $list = Project::getMembers($this->project->projectId);
            $template->setText('user-count', count($list));

            $template->setText('quote', $this->project->quote);

            $list = Task::findFiltered([
                'projectId' => $this->project->getId(),
                'status' => [
                    Task::STATUS_PENDING,
                    Task::STATUS_OPEN,
                    Task::STATUS_HOLD,
                ]
            ]);

            $template->setText('tasks-open', count($list));
            $template->setText('task-time', $this->secondsToDHM($this->project->getTotalEstTime()));
            $template->setText('est-cost', $this->project->getEstimatedCost());

            $list = Task::findFiltered([
                'projectId' => $this->project->getId(),
                'status' => [
                    Task::STATUS_CLOSED
                ]
            ]);
            $template->setText('tasks-complete', count($list));
            $template->setVisible('has-project');
        } else {
            $template->setVisible('not-project');
        }

        if (is_file(Config::makePath('/src/App/Pdf/pdfStyles.css'))) {
            $pdfStyles = file_get_contents(Config::makePath('/src/App/Pdf/pdfStyles.css'));
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
.tk-table {
  font-size: 0.7em;
}
.tk-table .row-desc .mDesc {
  padding: 0.5em 2em 1em 0.5em;
  font-size: 1em;
}
.tk-table .row-desc .mDesc .log {
  padding-top: 3em;
  margin-top: 3em;
}
.tk-table .mSubject {
  width: 40%;
  font-weight: bold;
}
</style>
</head>
<body>

     <htmlpageheader name="myheader">
       <table class="w-100">
         <tr>
           <td class="" style="color:#0000BB;">
             <span style="font-weight: bold; font-size: 14pt;" var="head-title">Task List</span>
           </td>
         </tr>
       </table>
     </htmlpageheader>
     <htmlpagefooter name="myfooter" style="display: none;">
       <table class="w-100">
         <tr>
           <td class="w-50">
           <span style="font-size: 9pt;">Page {PAGENO} of {nb}</span>
           </td>
           <td class="w-50 text-end">
            <small>Tasks: <span var="task-count"></span></small>
           </td>
         </tr>
       </table>
     </htmlpagefooter>
     <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
     <sethtmlpagefooter name="myfooter" value="on" />

<div>
  <h3 class="">Project: <span var="title"></span></h3>
  <div class="details">

    <div choice="has-project">
      <div class="profile-details">
        <ul class="fa-ul">
          <li>Members: <span var="user-count">0</span></li>
          <li>Quote: <span var="quote">$0.00</span></li>
          <li>Est. Cost: <span var="est-cost">$0.00</span></li>
          <li>Time Remaining: <span var="task-time">0 min</span></li>
          <li>Tasks open: <span var="tasks-open">0</span></li>
          <li>Tasks completed: <span var="tasks-complete">0</span></li>
        </ul>
      </div>
      <hr/>
    </div>

    <table class="tk-table w-100">
      <thead>
        <tr>
          <th>ID</th>
          <th>Subject</th>
          <th choice="not-project">Project</th>
<!--          <th>Category</th>-->
          <th>Status</th>
          <th>Priority</th>
          <th>Est Mins.</th>
          <th>Curr Mins.</th>
          <th>Est $</th>
          <th>Curr $</th>
        </tr>
      </thead>
      <tbody>
        <tr var="hook"></tr>
        <tr repeat="row">
          <td class="mTaskId text-center" var="taskId"></td>
          <td class="mSubject" var="subject"></td>
          <td class="mProject" choice="project"></td>
<!--          <td class="mCategory text-center" var="category"></td>-->
          <td class="mStatus text-center" var="status"></td>
          <td class="mPriority text-center" var="priority"></td>
          <td class="mMinutes text-center" var="minutes"></td>
          <td class="mMinutesTotal text-center" var="minutesTotal"></td>
          <td class="mCost text-end" var="cost"></td>
          <td class="mCostTotal text-end" var="costTotal"></td>
        </tr>
        <tr class="row-desc" repeat="row-desc">
            <td>&nbsp;</td>
            <td class="mDesc" colspan="7" var="desc"></td>
        </tr>
      </tbody>
    </table>

    <p choice="is-billable"><small>* = Billable task</small></p>
  </div>
</div>

</body>
</html>
HTML;

        return Template::load($xhtml);
    }

    protected function secondsToDHM($minutes): string
    {
        $s = (int)$minutes*60;
        $str = '';
        if ((int)round($s/86400) > 0)
            $str .= sprintf('%d days ', round($s/86400));

        return sprintf('%s%02d:%02d', $str, round($s/3600)%24, round($s/60%60));
    }

}