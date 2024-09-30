<?php
namespace App\Ui;

use App\Db\User;
use Dom\Renderer\DisplayInterface;
use Dom\Renderer\Renderer;
use Dom\Template;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Uri;

class Notify extends Renderer implements DisplayInterface
{

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $user = User::getAuthUser();
        $notices = [];
        $unread = 0;
        if ($user instanceof User) {
            $notices = \App\Db\Notify::findFiltered(Filter::create([
                'userId' => $user->userId,
                'isRead' => false,
            ], '-created', 10));
            $unread = Db::getLastStatement()->getTotalRows();

            if (isset($_GET['clear-all'])) {
                \App\Db\Notify::markAllRead($user->userId);
                Uri::create()->remove('clear-all')->redirect();
            }
        }

        $js = <<<JS
jQuery(function($) {
    // Hide enable notification button once enabled
    if (typeof Notification === 'undefined' || Notification.permission === 'granted') {
        $('.tk-notify .notify-toggle').closest('.noti-title').hide();
    }
});
JS;
        $template->appendJs($js);

        if ($unread == 0) return $template;

        $template->setText('unread', $unread);
        $template->setVisible('unread');
        $template->setVisible('view-all');
        $template->setVisible('show-all');

        $clearUrl = Uri::create()->set('clear-all');
        $template->setAttr('clear-all', 'href', $clearUrl);

        foreach ($notices as $note) {
            $item = $template->getRepeat('notice');
            $item->setText('title', $note->title);
            $item->setHtml('message', $note->message);
            if ($note->url) {
                $item->setAttr('notice', 'href', Uri::create($note->url));
            }
            if ($note->icon) {
                $item->setVisible('icon');
                $item->setAttr('icon-src', 'src', $note->icon);
            }
            $item->appendRepeat();
        }

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<li class="dropdown notification-list topbar-dropdown tk-notify">
    <a class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
        <i class="fe-bell noti-icon"></i>
        <span class="badge bg-danger rounded-circle noti-icon-badge" choice="unread">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-lg">
        <div class="dropdown-item noti-title">
            <h5 class="m-0">
                <span class="float-end"><a href="#" class="text-dark" var="clear-all" title="Mark All Messages Read" data-confirm="Mark all unread messages as read?"><small>Clear All</small></a></span>
                Notifications
            </h5>
        </div>
        <div class="dropdown-item noti-title bg-warning-subtle">
            <h5 class="m-0 text-center">
                <a href="#" class="btn btn-xs btn-outline-dark notify-toggle"><i class="fa fa-fw fa-bullhorn"></i> Enable Browser Notifications</a>
            </h5>
        </div>
        <div class="noti-scroll" data-simplebar="">
            <!-- item-->
            <a href="javascript:void(0);" class="dropdown-item notify-item" repeat="notice">
                <div class="notify-icon" choice="icon">
                    <img src="#" class="img-fluid rounded-circle" alt="" var="icon-src"/>
                </div>
                <p class="notify-details" var="title"></p>
                <p class="text-muted mb-0 user-msg" var="message"></p>
            </a>
        </div>
        <a href="javascript:void(0);" class="dropdown-item text-center text-primary notify-item notify-all" choice="view-all">
            View all <i class="fe-arrow-right"></i>
        </a>
    </div>
</li>
HTML;

        return Template::load($html);
    }

}