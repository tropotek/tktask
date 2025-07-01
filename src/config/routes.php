<?php
/**
 * Site Routes
 */

use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

/**
 * Remember to refresh the cache after editing,
 * Reload the page with <Ctrl>+<Shift>+R
 *
 * @see https://symfony.com/doc/current/routing.html
 */
return function (CollectionConfigurator $routes) {

    // Public
    $routes->add('home-base', '/')
        ->controller([\App\Controller\User\Login::class, 'doLogin']);
    $routes->add('home', '/home')
        ->controller([\App\Controller\User\Login::class, 'doLogin']);


    // User Public
    $routes->add('login', '/login')
        ->controller([\App\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\App\Controller\User\Login::class, 'doLogout']);
    $routes->add('login-ssi', '/_ssi')
        ->controller([\App\Controller\User\Ssi::class, 'doDefault']);
    $routes->add('recover', '/recover')
        ->controller([\App\Controller\User\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->controller([\App\Controller\User\Recover::class, 'doRecover']);
    $routes->add('register-activate', '/registerActivate')
        ->controller([\App\Controller\User\Register::class, 'doActivate']);
    $routes->add('register', '/register')
        ->controller([\App\Controller\User\Register::class, 'doDefault']);


    // Site Admin Pages
    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);
    $routes->add('user-profile', '/profile')
        ->controller([\App\Controller\User\Profile::class, 'doDefault']);
    $routes->add('notify-list', '/notifications')
        ->controller([\App\Controller\Notify\View::class, 'doDefault']);

    $routes->add('settings-edit', '/settings')
        ->controller([\App\Controller\Admin\Settings::class, 'doDefault']);
    $routes->add('user-type-manager', '/user/staffManager')
        ->controller([\App\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-type-edit', '/user/staffEdit')
        ->controller([\App\Controller\User\Edit::class, 'doDefault']);

    $routes->add('company-manager', '/companyManager')
        ->controller([\App\Controller\Company\Manager::class, 'doDefault']);
    $routes->add('company-edit', '/companyEdit')
        ->controller([\App\Controller\Company\Edit::class, 'doDefault']);

    $routes->add('task-category-manager', '/taskCategoryManager')
        ->controller([\App\Controller\TaskCategory\Manager::class, 'doDefault']);
    $routes->add('task-category-edit', '/taskCategoryEdit')
        ->controller([\App\Controller\TaskCategory\Edit::class, 'doDefault']);

    $routes->add('product-category-manager', '/productCategoryManager')
        ->controller([\App\Controller\ProductCategory\Manager::class, 'doDefault']);
    $routes->add('product-category-edit', '/productCategoryEdit')
        ->controller([\App\Controller\ProductCategory\Edit::class, 'doDefault']);

    $routes->add('product-manager', '/productManager')
        ->controller([\App\Controller\Product\Manager::class, 'doDefault']);
    $routes->add('product-edit', '/productEdit')
        ->controller([\App\Controller\Product\Edit::class, 'doDefault']);

    $routes->add('expense-category-manager', '/expenseCategoryManager')
        ->controller([\App\Controller\ExpenseCategory\Manager::class, 'doDefault']);
    $routes->add('expense-category-edit', '/expenseCategoryEdit')
        ->controller([\App\Controller\ExpenseCategory\Edit::class, 'doDefault']);

    $routes->add('project-manager', '/projectManager')
        ->controller([\App\Controller\Project\Manager::class, 'doDefault']);
    $routes->add('project-edit', '/projectEdit')
        ->controller([\App\Controller\Project\Edit::class, 'doDefault']);

    $routes->add('task-manager', '/taskManager')
        ->controller([\App\Controller\Task\Manager::class, 'doDefault']);
    $routes->add('task-edit', '/taskEdit')
        ->controller([\App\Controller\Task\Edit::class, 'doDefault']);

    $routes->add('task-log-manager', '/taskLogManager')
        ->controller([\App\Controller\TaskLog\Manager::class, 'doDefault']);
    $routes->add('task-log-edit', '/taskLogEdit')
        ->controller([\App\Controller\TaskLog\Edit::class, 'doDefault']);

    $routes->add('recurring-manager', '/recurringManager')
        ->controller([\App\Controller\Recurring\Manager::class, 'doDefault']);
    $routes->add('recurring-edit', '/recurringEdit')
        ->controller([\App\Controller\Recurring\Edit::class, 'doDefault']);

    $routes->add('expense-manager', '/expenseManager')
        ->controller([\App\Controller\Expense\Manager::class, 'doDefault']);
    $routes->add('expense-edit', '/expenseEdit')
        ->controller([\App\Controller\Expense\Edit::class, 'doDefault']);

    $routes->add('invoice-manager', '/invoiceManager')
        ->controller([\App\Controller\Invoice\Manager::class, 'doDefault']);
    $routes->add('invoice-edit', '/invoiceEdit')
        ->controller([\App\Controller\Invoice\Edit::class, 'doDefault']);

    $routes->add('reports-profit', '/profitReport')
        ->controller([\App\Controller\Reports\ProfitLoss::class, 'doDefault']);
    $routes->add('sales-profit', '/salesReport')
        ->controller([\App\Controller\Reports\Sales::class, 'doDefault']);

    $routes->add('domain-manager', '/domainManager')
        ->controller([\App\Controller\Domain\Manager::class, 'doDefault']);
    $routes->add('domain-edit', '/domainEdit')
        ->controller([\App\Controller\Domain\Edit::class, 'doDefault']);


    // PDF
    $routes->add('pdf-invoice', '/pdf/invoice')
        ->controller([\App\Pdf\Invoice::class, 'doDefault']);
    $routes->add('pdf-task-list', '/pdf/taskList')
        ->controller([\App\Pdf\TaskList::class, 'doDefault']);
    $routes->add('pdf-profit-loss', '/pdf/profitLoss')
        ->controller([\App\Pdf\ProfitLoss::class, 'doDefault']);


    // Components
    $routes->add('com-notify-nav', '/component/notify')
        ->controller([\App\Component\Notify::class, 'doDefault']);

    $routes->add('com-payment-table', '/component/paymentTable')
        ->controller([\App\Component\PaymentTable::class, 'doDefault']);

    $routes->add('com-task-log-table', '/component/taskLogTable')
        ->controller([\App\Component\TaskLogTable::class, 'doDefault']);

    $routes->add('com-item-edit-dialog', '/component/itemEditDialog')
        ->controller([\App\Component\ItemEditDialog::class, 'doDefault']);

    $routes->add('com-invoice-edit-dialog', '/component/invoiceEditDialog')
        ->controller([\App\Component\InvoiceEditDialog::class, 'doDefault']);
    $routes->add('com-invoice-outstanding-table', '/component/invoiceOutstandingTable')
        ->controller([\App\Component\InvoiceOutstandingTable::class, 'doDefault']);

    $routes->add('com-payment-edit-dialog', '/component/paymentAddDialog')
        ->controller([\App\Component\PaymentAddDialog::class, 'doDefault']);

    $routes->add('com-task-log-edit-dialog', '/component/taskLogEditDialog')
        ->controller([\App\Component\TaskLogEditDialog::class, 'doDefault']);

    $routes->add('com-company-edit-dialog', '/component/companyEditDialog')
        ->controller([\App\Component\CompanyEditDialog::class, 'doDefault']);

    $routes->add('com-company-select-dialog', '/component/companySelectDialog')
        ->controller([\App\Component\CompanySelectDialog::class, 'doDefault']);

    $routes->add('com-ping-table', '/component/pingTable')
        ->controller([\App\Component\PingTable::class, 'doDefault']);

    $routes->add('com-files', '/component/files')
        ->controller([\App\Component\Files::class, 'doDefault']);

    $routes->add('com-user-permissions', '/component/userPermissions')
        ->controller([\App\Component\UserPermissions::class, 'doDefault']);


    // API
    $routes->add('api-get-product', '/api/getProduct')
        ->controller([\App\Api\Product::class, 'doGetProduct']);
};