<?php
namespace App\Component;

use App\Db\User;
use Dom\Template;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Uri;

class Notify extends \Dom\Renderer\Renderer
{
    const string CONTAINER_ID = 'notify-nav';

    protected ?User $user = null;
    protected array $hxHeaders = [];

    public function doDefault(): ?Template
    {
        $this->user = User::getAuthUser();
        if (!$this->user) return null;

        $action = trim($_POST['action'] ?? $_GET['action'] ?? '');
        $notifyId = intval($_POST['notifyId'] ?? $_GET['notifyId'] ?? 0);

        if ($action === 'clear') {
            \App\Db\Notify::markAllRead($this->user->userId);
        }

        $notify = \App\Db\Notify::find($notifyId);
        if ($notifyId && $notify instanceof \App\Db\Notify) {
            $notify->readAt = new \DateTime();
            $notify->save();
            if ($notify->url) {
                $this->hxHeaders['HX-Redirect'] = $notify->url;
            }
        }

        // Send HX event headers
        foreach ($this->hxHeaders as $name => $header) {
            header(sprintf('%s: %s', $name, $header));
        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $template->setAttr('content', 'id', self::CONTAINER_ID);

        $notices = [];
        $unread = 0;
        if ($this->user instanceof User) {
            $notices = \App\Db\Notify::findFiltered(Filter::create([
                'userId' => $this->user->userId,
                'isRead' => false,
            ], '-created', 10));
            $unread = Db::getLastStatement()->getTotalRows();

            if (isset($_GET['clear-all'])) {
                \App\Db\Notify::markAllRead($this->user->userId);
                Uri::create()->remove('clear-all')->redirect();
            }
        }

        $clearUrl = Uri::create()->set('action', 'clear');
        $template->setAttr('clear-all', 'hx-post', $clearUrl);
        $template->setAttr('clear-all', 'hx-target', '#'.self::CONTAINER_ID);

        $template->setText('unread', strval($unread));
        $template->setVisible('show-all');
        $template->setVisible('unread', $unread > 0);


        if ($unread == 0) return $template;

        foreach ($notices as $note) {
            $item = $template->getRepeat('notice');
            $item->setText('title', $note->title);
            $item->setHtml('message', $note->message);
            if ($note->url) {
                $url = Uri::create()->set('notifyId', $note->notifyId);
                $item->setAttr('notice', 'hx-get', $url);
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
        $baseUrl = Uri::create()->toString();
        $containerId = self::CONTAINER_ID;

        $html = <<<HTML
<li class="dropdown notification-list topbar-dropdown tk-notify" var="content"
     hx-get="{$baseUrl}"
     hx-swap="outerHTML"
     hx-trigger="every 60s">
    <a class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
        <i class="fe-bell noti-icon"></i>
        <span class="badge bg-danger rounded-circle noti-icon-badge" choice="unread">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-lg">
        <div class="dropdown-item noti-title">
            <h5 class="m-0">
                <span class="float-end">
                    <a href="#" class="text-dark" var="clear-all" title="Mark All Messages Read" hx-confirm="Mark all unread messages as read?">
                        <small>Clear All</small>
                    </a>
                </span>
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
            <a href="#" class="dropdown-item notify-item notify-click" repeat="notice">
                <div class="notify-icon" choice="icon">
                    <img src="#" class="img-fluid rounded-circle" alt="" var="icon-src"/>
                </div>
                <p class="notify-details" var="title"></p>
                <p class="text-muted mb-0 user-msg" var="message"></p>
            </a>
        </div>
        <a href="/notifications" class="dropdown-item text-center text-primary notify-item notify-all">
            View all <i class="fe-arrow-right"></i>
        </a>
    </div>

<script>
jQuery(function($) {
    const container = '#{$containerId}';
    const baseUrl = '{$baseUrl}';

    // reload component
    $(document).on('notify:reload', function(e) {
        htmx.ajax('GET', baseUrl, {
            source: container
        });
    });

    // hide browser notification enable button if set
    if (typeof Notification !== 'undefined' && (Notification.permission === 'denied' || Notification.permission === 'granted')) {
        $('.notify-toggle', container).closest('.noti-title').hide();
    }

    // toggle browser notifications button
    $('.notify-toggle', container).on('click', function (e) {
        if (Notification.permission !== 'granted') {
            let promise = Notification.requestPermission();
            promise.then(function () {
                if (Notification.permission === 'granted') {
                    $(document).trigger('notify:reload');
                }
            });
        }
    });

    showNotifications();

    // show notifications in browser
    function showNotifications() {
        if (Notification.permission !== 'granted') return;
        $.post(tkConfig.baseUrl + '/api/notify/getNotifications', {})
            .done(function (data) {
                let notices = data.notices;
                if (notices === undefined || notices.length) {
                    for (let note of notices) {
                        let notification = new Notification(
                            note.title,
                            {
                                icon: note.icon,
                                body: note.message
                            }
                        );
                        notification.notifyId = note.notifyId;

                        if (note.url !== '') {
                            notification.onclick = function () {
                                $.post(tkConfig.baseUrl + '/api/notify/markRead', {notifyId: notification.notifyId});
                                window.open(note.url);
                                notification.close();
                            };
                        }
                        setTimeout(function () {
                            notification.close();
                        }, 5000);
                    }
                }
            })
            .fail(function (data) {
                console.warn(arguments);
            });
    }

});
</script>
</li>
HTML;
        return Template::load($html);
    }

}
