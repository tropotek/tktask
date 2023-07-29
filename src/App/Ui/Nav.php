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
            'Home'        => ['url' => '/home'],
            'Contact Us'  => ['url' => '/contact'],
            'Dashboard'   => [
                'icon' => 'ri-dashboard-line me-1',
                'visible' => fn($i) => (bool)$this->getUser(),
                'url' => '/dashboard'
            ],
            'Examples' => [
                'icon' => 'ri-apps-2-line me-1',
                'DomTest' => ['url' => '/domTest'],
                'HTMX'    => ['url' => '/htmx'],
                'Form'    => ['url' => '/ui/form'],
                'Info'    => ['url' => '/info'],
            ],
            'User' => [
                'icon' => 'ri-stack-line me-1',
                'My Profile' => [
                    'icon' => 'ri-stack-line me-1',
                    'visible' => fn($i) => (bool)$this->getUser(),
                    'url'     => '/profile'
                ],
                'Dropdown Level' => [
                    'icon' => 'ri-stack-line me-1',
                    'DomTest' => ['url' => '/domTest'],
                    'HTMX'    => ['url' => '/htmx'],
                    'Form'    => ['url' => '/ui/form'],
                    'Info'    => ['url' => '/info'],
                ],
                'Site Settings' => [
                    'icon' => 'ri-stack-line me-1',
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_SYSADMIN),
                    'url'     => '/settings'
                ],
                'Users' => [
                    'icon' => 'ri-stack-line me-1',
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_ADMIN),
                    'url'  => '/user/manager'
                ],
                'Staff' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_MANAGE_STAFF),
                    'url'  => '/user/staffManager'
                ],
                'Members' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_MANAGE_MEMBER | User::PERM_MANAGE_STAFF),
                    'url'  => '/user/memberManager'
                ],
                'File Manager' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_ADMIN),
                    'url'  => '/fileManager'
                ],
                'Example Manager' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_ADMIN),
                    'url'  => '/exampleManager'
                ],
                'Tail Log' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_ADMIN),
                    'url'  => '/tailLog'
                ],
                'List Events' => [
                    'visible' => fn($i) => $this->getUser()->hasPermission(User::PERM_ADMIN),
                    'url'  => '/listEvents'
                ],
            ],
            'Login' => [
                'visible' => fn($i) => !$this->getUser(),
                'url'  => '/login'
            ],
            'Logout' => [
                'visible' => fn($i) => (bool)$this->getUser(),
                'url'  => '/logout'
            ],
        ];
    }


    protected function isVisible(array $item): bool
    {
        if (is_callable($item['visible'] ?? '')) {
            return $item['visible']($item);
        }
        return true;
    }

    public function getTopNav(): string
    {
        $nav = sprintf('<ul class="navbar-nav %s" %s>', $this->getCssString(), $this->getAttrString());
        foreach ($this->getNavList() as $name => $item) {
            if (!$this->isVisible($item)) continue;
            if (empty($item['url'])) {
                if (!count($item)) continue;
                $nav .= $this->makeTopDropdown($name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s"></i>', $item['icon']);
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
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
        $ico = '';
        if ($icon) {
            $ico = sprintf('<i class="%s"></i>', $icon);
        }
        $nav  = '<li class="nav-item dropdown">';
        $nav .= sprintf('<a class="nav-link dropdown-toggle arrow-none" href="javascript:;" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s %s <div class="arrow-down"></div></a>', $ico, $name);
        $nav .= '<div class="dropdown-menu">';
        foreach ($items as $sub_name => $item) {
            if (empty($item['url'])) {
                if (!count($item)) continue;
                $nav .= $this->makeTopSubDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s"></i>', $item['icon']);
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
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
        $ico = '';
        if ($icon) {
            $ico = sprintf('<i class="%s"></i>', $icon);
        }
        $nav  = '<div class="dropdown">';
        $nav .= sprintf('<a class="dropdown-item dropdown-toggle arrow-none" href="javascript:;" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s %s <div class="arrow-down"></div></a>', $ico, $name);
        $nav .= '<div class="dropdown-menu">';
        foreach ($items as $sub_name => $item) {
            if (empty($item['url'])) {
                if (!count($item)) continue;
                $nav .= $this->makeTopSubDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s"></i>', $item['icon']);
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
                if (!count($item)) continue;
                $nav .= $this->makeSideDropdown($name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s"></i>', $item['icon']);
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
        $items = array_filter($items, fn($itm) => $this->isVisible($itm));
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
                if (!count($item)) continue;
                $nav .= $this->makeSideDropdown($sub_name, $item['icon'] ?? '', $item);
            } else {
                $ico = '';
                if ($item['icon'] ?? false) {
                    $ico = sprintf('<i class="%s"></i>', $item['icon']);
                }
                $nav .= sprintf('<li><a href="%s">%s <span>%s</span></a></li>', $item['url'], $ico, $sub_name);
            }
        }
        $nav .= '</ul></div></li>';
        return $nav;
    }


    public function getUser(): User
    {
        return $this->getFactory()->getAuthUser();
    }
}