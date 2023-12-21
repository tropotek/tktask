<?php

namespace App\Ui;

use Bs\Db\User;
use Tk\Traits\SystemTrait;
use Tk\Ui\Traits\AttributesTrait;
use Tk\Ui\Traits\CssTrait;

class Nav
{
    use SystemTrait;
    use CssTrait;
    use AttributesTrait;

    protected function getNavList(): array
    {
        return [
            'Dashboard' => [
                'icon' => 'ri-dashboard-line',
                'visible' => fn($i) => (bool)$this->getUser(),
                'url' => '/dashboard',
            ],
            'Site Settings' => [
                'icon' => 'ri-settings-2-line',
                'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_SYSADMIN),
                'url' => '/settings'
            ],
            'Application' => [
                'icon' => 'ri-apps-2-fill',
                'Example Manager' => [
                    'icon' => 'ri-file-list-3-line',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/exampleManager'
                ],
                'File Manager' => [
                    'icon' => 'ri-archive-drawer-line',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/fileManager'
                ],
            ],
            'Users' => [
                'icon' => 'ri-team-fill',
                'Users' => [
                    'icon' => 'ri-team-fill',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/user/manager'
                ],
                'Staff' => [
                    'icon' => 'ri-team-fill',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_MANAGE_STAFF),
                    'url' => '/user/staffManager'
                ],
                'Members' => [
                    'icon' => 'ri-team-fill',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_MANAGE_MEMBER | User::PERM_MANAGE_STAFF),
                    'url' => '/user/memberManager'
                ]
            ],

            // Visible in Debug mode only
            'Examples' => [
                'icon' => 'ri-apps-2-line',
                'visible' => fn($i) => $this->getConfig()->isDebug(),
                'Test' => ['url' => '/test'],
                'DomTest' => ['url' => '/domTest'],
                'HTMX' => ['url' => '/htmx'],
                'Form Elements' => ['url' => '/ui/form'],
            ],
            'Dev' => [
                'icon' => 'ri-bug-line',
                'visible' => fn($i) => $this->getConfig()->isDebug(),
                'PHP Info' => [
                    'icon' => 'ri-information-line',
                    'url' => '/info'
                ],
                'Tail Log' => [
                    'icon' => 'ri-terminal-box-fill',
                    'url' => '/tailLog'
                ],
                'List Events' => [
                    'icon' => 'ri-timer-flash-line',
                    'url' => '/listEvents'
                ],
            ],

        ];
    }

    public function getProfileNav(): string
    {
        $html = <<<HTML
<div>
    <a href="/profile" class="dropdown-item notify-item">
        <i class="fe-user me-1"></i>
        <span>My Account</span>
    </a>
    <a href="/settings" class="dropdown-item notify-item" app-has-perm="PERM_SYSADMIN">
        <i class="fe-settings me-1"></i>
        <span>Settings</span>
    </a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item notify-item" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas" app-has-perm="PERM_SYSADMIN">
        <i class="ri-palette-line me-1"></i>
        <span>Customizer</span>
    </a>
    <a href="/logout" class="dropdown-item notify-item">
        <i class="fe-log-out me-1"></i>
        <span>Logout</span>
    </a>
</div>
HTML;
        return $html;
    }


    public function getTopNav(): string
    {
        $nav = sprintf('<ul class="navbar-nav %s" %s>', $this->getCssString(), $this->getAttrString());
        foreach ($this->getNavList() as $name => $item) {
            if (!$this->isVisible($item)) continue;
            if (empty($item['url'])) {  // is dropdown item
                if (!count($item)) continue;
                $nav .= $this->makeTopDropdown($name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<li class="nav-item"><a class="nav-link" href="%s">%s %s</a></li>', $item['url'], $ico, $name);
            }
        }
        $nav .= '</ul>';
        return $nav;
    }

    protected function makeTopDropdown(string $name, string $icon, array $items): string
    {
        unset($items['icon']);
        unset($items['visible']);
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
        if (!count($items)) return '';
        $ico = '';
        if ($icon) {
            $ico = sprintf('<i class="%s"></i>', $icon);
        }
        $nav  = '<li class="nav-item dropdown">';
        $nav .= sprintf('<a class="nav-link dropdown-toggle arrow-none" href="javascript:;" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s %s <div class="arrow-down"></div></a>', $ico, $name);
        $nav .= '<div class="dropdown-menu">';
        foreach ($items as $sub_name => $item) {
            if (!$this->isVisible($item)) continue;
            if (empty($item['url'])) {  // is dropdown item
                if (!count($item)) continue;
                $nav .= $this->makeTopSubDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<a class="dropdown-item" href="%s">%s %s</a>', $item['url'], $ico, $sub_name);
            }
        }
        $nav .= '</div></li>';
        return $nav;
    }

    protected function makeTopSubDropdown(string $name, string $icon, array $items): string
    {
        unset($items['icon']);
        unset($items['visible']);
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
        if (!count($items)) return '';
        $ico = '';
        if ($icon) {
            $ico = sprintf('<i class="%s"></i>', $icon);
        }
        $nav  = '<div class="dropdown">';
        $nav .= sprintf('<a class="dropdown-item dropdown-toggle arrow-none" href="javascript:;" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s %s <div class="arrow-down"></div></a>', $ico, $name);
        $nav .= '<div class="dropdown-menu">';
        foreach ($items as $sub_name => $item) {
            if (empty($item['url'])) {  // is dropdown item
                if (!count($item)) continue;
                $nav .= $this->makeTopSubDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<a class="dropdown-item" href="%s">%s %s</a>', $item['url'], $ico, $sub_name);
            }
        }
        $nav .= '</div></div>';
        return $nav;
    }


    public function getSideNav(): string
    {
        $nav = sprintf('<ul class="%s" %s>', $this->getCssString(), $this->getAttrString());
        foreach ($this->getNavList() as $name => $item) {
            if (!$this->isVisible($item)) continue;
            if (empty($item['url'])) {  // is dropdown item
                if (!count($item)) continue;
                $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<li><a href="%s">%s <span>%s</span></a></li>', $item['url'], $ico, $name);
            }
        }
        $nav .= '</ul>';
        return $nav;
    }

    protected function makeSideDropdown(string $name, string $icon, array $items): string
    {
        unset($items['icon']);
        unset($items['visible']);
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
        if (!count($items)) return '';
        $ico = '';
        if ($icon) {
            $ico = sprintf('<i class="%s"></i>', $icon);
        }
        $id = strtolower(preg_replace('/[^a-z0-9_-]+/i', '', $name));
        $nav  = '<li>';
        $nav .= sprintf('<a href="#%s" class="waves-effect" data-bs-toggle="collapse" aria-expanded="false">%s <span>%s</span> <span class="menu-arrow"></span></a>', $id, $ico, $name);
        $nav .= sprintf('<div class="collapse" id="%s"><ul class="nav-second-level">', $id);
        foreach ($items as $sub_name => $item) {
            if (empty($item['url'])) {  // is dropdown item
                if (!count($item)) continue;
                $nav .= $this->makeSideDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<li><a href="%s">%s <span>%s</span></a></li>', $item['url'], $ico, $sub_name);
            }
        }
        $nav .= '</ul></div></li>';
        return $nav;
    }

    protected function isVisible(array $item): bool
    {
        if (isset($item['visible']) && is_callable($item['visible'])) {
            return $item['visible']($item);
        }
        return true;
    }

    public function getUser(): ?User
    {
        return $this->getFactory()->getAuthUser();
    }
}