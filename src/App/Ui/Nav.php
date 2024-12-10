<?php

namespace App\Ui;

use Bs\Auth;
use App\Db\User;
use Dom\Template;
use Tk\Config;
use Tk\Ui\Traits\AttributesTrait;

class Nav
{
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

            'Task' => [
                'visible' => fn($i) => $this->getUser()?->isStaff(),
            ],
            'Projects' => [
                'icon' => 'fas fa-project-diagram',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/projectManager'
            ],
            'Tasks' => [
                'icon' => 'fas fa-tasks',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/taskManager'
            ],

            'Accounts' => [
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
            ],
            'Recurring' => [
                'icon' => 'far fa-money-bill-alt',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/recurringManager'
            ],

//            'Reports' => [
//                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
//            ],


            'Admin' => [
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
            ],
            'Application' => [
                'icon' => 'ri-apps-2-fill',
                'File Manager' => [
                    'icon' => 'ri-archive-drawer-line',
                    'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/fileManager'
                ],
            ],
            'Dev' => [
                'icon' => 'ri-bug-line',
                'visible' => fn($i) => Config::isDev() && $this->getUser()->isStaff(),
                'PHP Info' => [
                    'icon' => 'ri-information-line',
                    'url' => '/info'
                ],
                'Tail Log' => [
                    'icon' => 'ri-terminal-box-fill',
                    'url' => '/tailLog'
                ],
                'Inline Image' => [
                    'icon' => 'fas fa-image',
                    'url' => '/util/inlineImage'
                ],
                'DB Search' => [
                    'icon' => 'fas fa-database',
                    'url' => '/util/dbSearch'
                ],
            ],

        ];
    }

    public function getProfileNav(): Template
    {
        $html = <<<HTML
<div>
    <a href="/profile" class="dropdown-item notify-item">
        <i class="fe-user me-1"></i>
        <span>My Account</span>
    </a>
    <a href="/settings" class="dropdown-item notify-item" choice="sysadmin">
        <i class="fe-settings me-1"></i>
        <span>Settings</span>
    </a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item notify-item" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas" choice="sysadmin">
        <i class="ri-palette-line me-1"></i>
        <span>Customizer</span>
    </a>
    <a href="/logout" class="dropdown-item notify-item btn-logout">
        <i class="fe-log-out me-1"></i>
        <span>Logout</span>
    </a>
</div>
HTML;
        $template = Template::load($html);

        if ($this->getUser()) {
            $template->setVisible('sysadmin', $this->getUser()->hasPermission(User::PERM_SYSADMIN));
        }

        return $template;
    }


    public function getTopNav(): string
    {
        $nav = sprintf('<ul class="navbar-nav %s" %s>', $this->getCssString(), $this->getAttrString());
        foreach ($this->getNavList() as $name => $item) {
            if (!$this->isVisible($item)) continue;
            if (empty($item['url'])) {
                if ($this->hasItems($item)) { // is dropdown item
                    $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    $nav .= sprintf('<li class="menu-title">%s</li>', $name);
                }
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
            if (empty($item['url'])) {
                if ($this->hasItems($item)) { // is dropdown item
                    $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    $nav .= sprintf('<li class="menu-title">%s</li>', $name);
                }
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
            if (empty($item['url'])) {
                if ($this->hasItems($item)) { // is dropdown item
                    $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    $nav .= sprintf('<li class="menu-title">%s</li>', $name);
                }
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
            if (empty($item['url'])) {
                if ($this->hasItems($item)) { // is dropdown item
                    $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    $nav .= sprintf('<li class="menu-title">%s</li>', $name);
                }
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
            if (empty($item['url'])) {
                if ($this->hasItems($item)) { // is dropdown item
                    $nav .= $this->makeSideDropdown($sub_name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    $nav .= sprintf('<li class="menu-title">%s</li>', $sub_name);
                }
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

    protected function hasItems(array $item): bool
    {
        unset($item['icon']);
        unset($item['url']);
        unset($item['visible']);
        return count($item) > 0;
    }

    protected function isVisible(array $item): bool
    {
        if (is_callable($item['visible'] ?? '')) {
            return $item['visible']($item) ?? false;
        }
        return true;
    }

    public function getUser(): ?User
    {
        return User::getAuthUser();
    }
}