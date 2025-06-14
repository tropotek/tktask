<?php
namespace App\Component;

use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Dom\Template;
use Tk\Date;
use Tk\Db;
use Tk\Db\Filter;
use Tk\Uri;

class Notify extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'notify-nav';

    protected ?User $user      = null;
    protected array $hxHeaders = [];
    protected array $notices   = [];

    public function doDefault(): ?Template
    {
        $this->user = User::getAuthUser();
        if (!$this->user) return null;

        $action = trim($_REQUEST['action'] ?? '');
        $notifyId = intval($_REQUEST['notifyId'] ?? 0);

        if ($action == 'clear') {
            \App\Db\Notify::markAllRead($this->user->userId);
        }

        if ($action == 'mark-read') {
            $notify = \App\Db\Notify::find($notifyId);
            if ($notify instanceof \App\Db\Notify && $notify->userId == User::getAuthUser()->userId) {
                $notify->readAt = Date::create();
                $notify->save();
            }

            $url = trim($_POST['url'] ?? $_GET['url'] ?? '');
            if ($url) {
                $this->hxHeaders['HX-Redirect'] = Uri::create($url)->toString();
            }
        }

        // Get a list of new browser notification notices
        $this->notices = \App\Db\Notify::findFiltered(Filter::create([
            'userId' => $this->user->userId,
            'isRead' => false,
            'isNotified' => false
        ], '-created'));
        \App\Db\Notify::setNotified(array_map(fn($n) => $n->notifyId, $this->notices));
        // limit notices to a maximum
        $this->notices = array_splice($this->notices, 0, 3);

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
                $url = Uri::create(null, ['notifyId' => $note->notifyId, 'action' => 'mark-read']);
                $item->setAttr('notice', 'hx-get', $url)->setAttr('notice', 'hx-target', '#'.self::CONTAINER_ID);
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
        $bNotices = json_encode($this->notices);

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
    const baseUrl   = '{$baseUrl}';
    let bNotices    = {$bNotices};

    // reload component
    $(document).on('notify:reload', function(e) {
        htmx.ajax('GET', baseUrl, {
            source: container
        });
    });

    // show notifications in browser
    showNotifications();
    function showNotifications() {
        if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
        for (let note of bNotices) {
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
                    let url = new URL(baseUrl);
                    url.searchParams.set('notifyId', note.notifyId);
                    url.searchParams.set('action', 'mark-read');
                    htmx.ajax('POST', url.toString(), {
                        source: container,
                        swap: 'none',
                        values: {
                            url: note.url
                        },
                    });
                    notification.close();
                };
            }

            setTimeout(function () {
                notification.close();
            }, 5000);
        }
    }

    // for notifications view page, mark clicked notifications as read
    $('.notify-click').on('click', function(e) {
        let href = $(this).attr('href') ?? '';
        let notifyId = $(this).data('notifyId');
        if (!href || !notifyId) return true;

        let url = new URL(baseUrl);
        url.searchParams.set('notifyId', notifyId);
        url.searchParams.set('action', 'mark-read');
        htmx.ajax('POST', url.toString(), {
            source: container,
            swap: 'none',
            values: {
                url: href
            },
        });
        return false;
    });

    // toggle browser notifications button (see app.js for gaining notification permission)
    $('.notify-toggle', container).on('click', function (e) {
        if (typeof Notification !== 'undefined' && Notification.permission !== 'granted') {
            let promise = Notification.requestPermission();
            promise.then(function () {
                if (Notification.permission === 'granted') {
                    $(document).trigger('notify:reload');
                }
            });
        }
    });

    // hide browser notification enable button if set
    if (typeof Notification === 'undefined' || Notification.permission === 'denied' || Notification.permission === 'granted') {
        $('.notify-toggle', container).closest('.noti-title').hide();
    }

});
</script>
</li>
HTML;
        return Template::load($html);
    }

}
