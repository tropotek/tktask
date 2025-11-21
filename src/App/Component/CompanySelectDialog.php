<?php
namespace App\Component;

use App\Db\Company;
use App\Db\User;
use Bs\Mvc\ComponentInterface;
use Bs\Mvc\Table;
use Dom\Template;
use Tk\Db;
use Tk\Exception;
use Tk\Form\Field\Input;
use Tk\Table\Cell;

class CompanySelectDialog extends \Dom\Renderer\Renderer implements ComponentInterface
{
    const string CONTAINER_ID = 'company-select-dialog';

    protected array  $hxEvents  = [];
    protected string $type      = Company::TYPE_CLIENT;
    protected Table  $table;


    public function doDefault(): ?Template
    {
        if (!User::getAuthUser()->isStaff()) return null;

        $this->type = trim($_REQUEST['type'] ?? Company::TYPE_CLIENT);
        if (!in_array($this->type, Company::TYPE_LIST)) {
            throw new Exception("Invalid company type");
        }

        $this->table = new Table('company-select-tbl');
        $this->table->hideReset();
        $this->table->setOrderBy('name');
        $this->table->setLimit(10);
        $this->table->addCss('tk-table-sm');

        $this->table->appendCell('name')
            ->addCss('text-nowrap')
            ->setSortable(true)
            ->addHeaderCss('max-width')
            ->addOnValue(function(\App\Db\Company $obj, Cell $cell) {
                return sprintf('<a class="company-item" data-company-id="%s" href="#" title="Select %s">%s</a>',
                    $obj->companyId,
                    $obj->name,
                    $obj->name
                );
            });

        // Add Filter Fields
        $this->table->getForm()->appendField(new Input('search'))
            ->setAttr('placeholder', 'Search');

        // execute table
        $this->table->execute();

        // Set the table rows
        $filter = $this->table->getDbFilter();
        $filter->replace([
            'active' => true,
            'type' => $this->type,
        ]);
        $rows = Company::findFiltered($filter);
        $this->table->setRows($rows, Db::getLastStatement()->getTotalRows());

        // Send HX event headers
        if (count($this->hxEvents)) {
            header(sprintf('HX-Trigger: %s', json_encode($this->hxEvents)));
        }

        return $this->show();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();
        $template->setAttr('dialog', 'id', $this->getDialogId());
        $template->setText('title', 'Select ' . $this->type);

        $template->appendTemplate('content', $this->table->htmxShow());

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="modal fade" tabindex="-1" var="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" var="title">Select Client</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" var="content"> </div>
        </div>
    </div>

<script>
jQuery(function($) {
    const dialog = '#{$this->getDialogId()}';

    $('.company-item', dialog).on('click', function() {
        $(document).trigger('companySelect', [
            $(this).data('companyId'),
            $(this).text()
        ]);
    });
});
</script>
</div>
HTML;
        return Template::load($html);
    }

    public function getDialogId(): string
    {
        return self::CONTAINER_ID;
    }

}
