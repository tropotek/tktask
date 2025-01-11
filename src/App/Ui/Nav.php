<?php

namespace App\Ui;

use App\Db\Invoice;
use App\Db\Task;
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
                'badge' => function() {
                    $open = Task::findFiltered([
                        'status' => [Task::STATUS_PENDING, Task::STATUS_OPEN, Task::STATUS_HOLD],
                        'assignedUserId' => User::getAuthUser()->userId,
                    ]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="badge bg-info rounded-pill float-end">%d</span>', count($open));
                },
            ],
            'Site Settings' => [
                'icon' => 'ri-settings-2-line',
                'visible' => fn($i) => $this->getUser()?->hasPermission(User::PERM_SYSADMIN),
                'url' => '/settings',
            ],
            'Tasks' => [
                'icon' => 'fas fa-tasks',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/taskManager',
                'badge' => function() {
                    $open = Task::findFiltered(['status' => [Task::STATUS_PENDING, Task::STATUS_OPEN, Task::STATUS_HOLD]]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="badge bg-info rounded-pill float-end">%d</span>', count($open));
                },
            ],
            'Projects' => [
                'icon' => 'fas fa-project-diagram',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/projectManager',
            ],

            'Accounts' => [
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
            ],
            'Invoices' => [
                'icon' => 'far fa-credit-card',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/invoiceManager',
                'badge' => function() {
                    $open = Invoice::findFiltered(['status' => [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="badge bg-info rounded-pill float-end">%d</span>', count($open));
                },
            ],
            'Recurring Billing' => [
                'icon' => 'fas fa-money-bill-wave',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/recurringManager',
            ],
            'Expenses' => [
                'icon' => 'fas fa-money-check-alt',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/expenseManager',
            ],

            'Reports' => [
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
            ],
            'Profit & Loss' => [
                'icon' => 'fas fa-dollar-sign',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/profitReport',
            ],
            'Sales' => [
                'icon' => 'fas fa-chart-line',
                'visible' => fn($i) => $this->getUser()?->isStaff(),
                'url' => '/salesReport',
            ],

            'System' => [
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
            ],
            'Admin' => [
                'icon' => 'ri-bug-line',
                'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                'PHP Info' => [
                    'icon' => 'ri-information-line',
                    'url' => '/info'
                ],
                'Tail Log' => [
                    'icon' => 'ri-terminal-box-fill',
                    'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/tailLog'
                ],
                'Inline Image' => [
                    'icon' => 'fas fa-image',
                    'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/util/inlineImage'
                ],
                'DB Search' => [
                    'icon' => 'fas fa-database',
                    'visible' => $this->getUser()?->hasPermission(User::PERM_ADMIN),
                    'url' => '/util/dbSearch'
                ],
            ],

        ];
    }

    public function getProfileNav(): Template
    {
        $html = <<<HTML
<div class="dropdown-menu dropdown-menu-end profile-dropdown">
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
                    $nav .= $this->makeTopDropdown($name, $item['icon'] ?? '', $item);
                } else {    // is title item
                    //$nav .= sprintf('<li class="menu-title">%s</li>', $name);
                }
            } else {

                $nav .= '<li class="nav-item">';
                $badge = '';
                if (!empty($item['badge'])) {
                    $badge = $item['badge']();
                }
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<a href="%s" class="nav-link">%s %s %s</a>', $item['url'], $ico, $badge, $name);
                $nav .= '</li>';
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
                $nav .= '<li>';
                $badge = '';
                if (!empty($item['badge'])) {
                    $badge = $item['badge']();
                }
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s me-1"></i>', $item['icon']);
                }
                $nav .= sprintf('<a href="%s">%s %s <span>%s</span></a>', $item['url'], $ico, $badge, $name);
                $nav .= '</li>';
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