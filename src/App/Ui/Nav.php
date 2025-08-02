<?php

namespace App\Ui;

use App\Db\Domain;
use App\Db\DomainPing;
use App\Db\Invoice;
use App\Db\Task;
use App\Db\User;
use Bs\Menu\Item;
use Tk\Uri;

class Nav
{
    public static function getNavMenu(): Item
    {
        $menu = new Item();
        $user = USer::getAuthUser();

        $menu->addLink('Dashboard', Uri::create('/dashboard'), 'ri-dashboard-line', ($user instanceof User),
            [
//                'badge' => function() {
//                    $open = Task::findFiltered([
//                        'status' => [Task::STATUS_OPEN],
//                        'assignedUserId' => User::getAuthUser()->userId,
//                    ]);
//                    if (count($open) == 0) return '';
//                    return sprintf('<span class="ms-1 badge bg-info rounded-pill float-end">%d</span>', count($open));
//                },
            ]
        );

        $menu->addLink('Tasks', Uri::create('/taskManager'), 'fas fa-tasks', fn($i) => (bool)$user?->isStaff(),
            [
                'badge' => function() {
                    $open = Task::findFiltered([
                        'status' => [Task::STATUS_OPEN],
                        //'assignedUserId' => User::getAuthUser()->userId,
                    ]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="ms-1 badge bg-info rounded-pill float-end">%d</span>', count($open));
                },
            ]
        );
        $menu->addLink('Invoices', Uri::create('/invoiceManager'), 'far fa-credit-card', (bool)$user?->isStaff(),
            [
                'badge' => function() {
                    $open = Invoice::findFiltered(['status' => [Invoice::STATUS_OPEN, Invoice::STATUS_UNPAID]]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="ms-1 badge bg-info rounded-pill float-end">%d</span>', count($open));
                },
            ]
        );
        $menu->addLink('Monitor', Uri::create('/domainManager'), 'fas fa-network-wired', (bool)$user?->hasPermission(User::PERM_SYSADMIN),
            [
                'badge' => function() {
                    $open = Domain::findFiltered(['active' => true, 'status' => DomainPing::STATUS_DOWN]);
                    if (count($open) == 0) return '';
                    return sprintf('<span class="ms-1 badge bg-danger rounded-pill float-end">%d</span>', count($open));
                },
            ]);

        $menu->addHeader('System', 'fas fa-cogs', (bool)$user?->isStaff());

        $menu->addLink('Projects', Uri::create('/projectManager'), 'fas fa-project-diagram', (bool)$user?->isStaff());
        $menu->addLink('Clients', Uri::create('/companyManager'), 'fa fa-fw fa-building', (bool)$user?->isStaff());
        $menu->addLink('Products', Uri::create('/productManager'), 'fa fa-fw fa-shopping-cart', (bool)$user?->isStaff());


        $menu->addHeader('Accounts', 'fas fa-money-bill-wave', (bool)$user?->isStaff());
        $menu->addLink('Recurring Billing', Uri::create('/recurringManager'), 'fas fa-money-bill-wave', (bool)$user?->isStaff() );
        $menu->addLink('Expenses', Uri::create('/expenseManager'), 'fas fa-money-check-alt', (bool)$user?->isStaff());

        $menu->addHeader('Reports', 'fas fa-certificate', (bool)$user?->isStaff());
        $menu->addLink('Profit & Loss', Uri::create('/profitReport'), 'fas fa-dollar-sign', (bool)$user?->isStaff());
        $menu->addLink('Client Sales', Uri::create('/salesReport'), 'fas fa-chart-line', (bool)$user?->isStaff());

        $menu->addHeader('Admin', 'fas fa-database', (bool)$user?->hasPermission(User::PERM_SYSADMIN));
        $menu->addLink('Settings', Uri::create('/settings'), 'ri-settings-2-line', (bool)$user?->hasPermission(User::PERM_SYSADMIN));

        $tools = $menu->addSubmenu('Tools', 'ri-bug-line', (bool)$user?->hasPermission(User::PERM_ADMIN));
        $tools->addLink('PHP Info', Uri::create('/info'), 'ri-information-line');
        $tools->addLink('Tail Log', Uri::create('/tailLog'), 'ri-terminal-box-fill');
        $tools->addLink('Inline Image', Uri::create('/util/inlineImage'), 'fas fa-image');
        $tools->addLink('DB Search', Uri::create('/util/dbSearch'), 'fas fa-database');

        return $menu;
    }

    public static function getProfileMenu(): Item
    {
        $menu = new Item('profile', 1);
        $user = User::getAuthUser();

        $menu->addLink('My Account', Uri::create('/profile'), 'fe-user', ($user instanceof User));
        $menu->addLink('Settings', Uri::create('/settings'), 'fe-settings', (bool)$user?->hasPermission(User::PERM_SYSADMIN));
        $menu->addSeparator(($user instanceof User));
        $menu->addLink('Customizer', null, 'ri-palette-line', (bool)$user?->hasPermission(User::PERM_SYSADMIN),
            [
                'attrs' => [
                    'data-bs-toggle' => 'offcanvas',
                    'href' => '#theme-settings-offcanvas',
                ]
            ]
        );
        $menu->addLink('About', null, 'fa fa-info-circle', (bool)$user?->hasPermission(User::PERM_SYSADMIN),
            [
                'attrs' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#about-modal',
                ]
            ]
        );
        $menu->addLink('Logout', Uri::create('/logout'), 'fe-log-out', (bool)$user?->hasPermission(User::PERM_SYSADMIN),
            [
                'css' => [
                    'btn-logout',
                ]
            ]
        );

        return $menu;
    }

}