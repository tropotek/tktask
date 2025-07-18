<?php
namespace App\Table;

use Bs\Mvc\Table;
use Dom\Template;
use Tk\Alert;
use Tk\Uri;
use Tk\Db;
use Tk\Table\Action\Csv;
use Tk\Table\Action\Delete;
use Tk\Table\Cell;
use Tk\Table\Cell\RowSelect;

/**
 * @deprecated Remove when confident not used
 */
class File extends Table
{

    protected string $fkey = '';


    public function init(): static
    {
        $rowSelect = RowSelect::create('id', 'fileId');
        $this->appendCell($rowSelect);

        $this->appendCell('actions')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->addOnValue(function(\App\Db\File $file, Cell $cell) {
                $view = $file->getUrl();
                $del  = Uri::create()->set('del', strval($file->fileId));
                return <<<HTML
                    <a class="btn btn-success" href="$view" title="View" target="_blank"><i class="fa fa-fw fa-eye"></i></a> &nbsp;
                    <a class="btn btn-danger" href="$del" title="Delete" data-confirm="Are you sure you want to delete file {$file->filename}"><i class="fa fa-fw fa-trash"></i></a>
                HTML;
            });

        $this->appendCell('filename')
            ->addHeaderCss('max-width')
            ->setSortable(true)
            ->addOnValue(function(\App\Db\File $file, Cell $cell) {
                return sprintf('<a href="%s" target="_blank">%s</a>', $file->getUrl(), $file->filename);
            });

        $this->appendCell('userId')
            ->setSortable(true);
        $this->appendCell('fkey')->setHeader('Key')
            ->setSortable(true);
        $this->appendCell('fid')->setHeader('Key ID')
            ->setSortable(true);
        $this->appendCell('bytes')
            ->setSortable(true);

        $this->appendCell('selected')
            ->addHeaderCss('text-center')
            ->addCss('text-nowrap text-center')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->appendCell('created')
            ->addHeaderCss('text-end')
            ->addCss('text-nowrap text-end')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Date::getLongDateTime');


        // Add Table actions
        $this->appendAction(Delete::create()
            ->addOnExecute(function(Delete $action) use ($rowSelect) {
                $selected = $rowSelect->getSelected();
                foreach ($selected as $file_id) {
                    Db::delete('file', compact('file_id'));
                }
            }));

        $this->appendAction(Csv::create()
            ->addOnExecute(function(Csv $action) use ($rowSelect) {
                if (!$this->getCell(\App\Db\File::getPrimaryProperty())) {
                    $this->prependCell(\App\Db\File::getPrimaryProperty())->setHeader('id');
                }
                $selected = $rowSelect->getSelected();
                $filter = $this->getDbFilter()->resetLimits();
                if (count($selected)) {
                    $filter->set(\App\Db\File::getPrimaryProperty(), $selected);
                    $rows = \App\Db\File::findFiltered($filter);
                } else {
                    $rows = \App\Db\File::findFiltered($filter);
                }
                return $rows;
            }));

        return $this;
    }

    public function execute(?callable $onInit = null): static
    {
        if (isset($_GET['del'])) {
            $this->doDelete(intval($_GET['del']));
        }
        parent::execute();
        return $this;
    }

    private function doDelete(int $id): void
    {
        $file = \App\Db\File::find($id);
        if (is_object($file)) {
            $file->delete();
            Alert::addSuccess('File removed successfully.');
        }
        Uri::create()->reset()->redirect();
    }

    public function show(): ?Template
    {
        $renderer = $this->getRenderer();
        $renderer->setFooterEnabled(false);
        return $renderer->show();
    }

    public function getFkey(): string
    {
        return $this->fkey;
    }

    public function setFkey(string $fkey): File
    {
        $this->fkey = $fkey;
        return $this;
    }

}