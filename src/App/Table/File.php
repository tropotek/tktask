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

class File extends Table
{

    protected string $fkey = '';


    public function init(): static
    {
        $rowSelect = RowSelect::create('id', 'fileId');
        $this->appendCell($rowSelect);

        $this->appendCell('actions')
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
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\Boolean::onValue');

        $this->appendCell('created')
            ->setSortable(true)
            ->addOnValue('\Tk\Table\Type\DateFmt::onValue');


        // Add Table actions
        $this->appendAction(Delete::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnDelete(function(Delete $action, array $selected) {
                foreach ($selected as $file_id) {
                    Db::delete('file', compact('file_id'));
                }
            })
        );

        $this->appendAction(Csv::create()
            ->addOnGetSelected([$rowSelect, 'getSelected'])
            ->addOnCsv(function(Csv $action, array $selected) {
                $action->setExcluded(['id', 'actions', 'permissions']);
                $this->getCell('username')->getOnValue()->reset();
                $filter = $this->getDbFilter();
                if (count($selected)) {
                    $filter['fileId'] = $selected;
                    $rows = \App\Db\File::findFiltered($filter);
                } else {
                    $rows = \App\Db\File::findFiltered($filter->resetLimits());
                }
                return $rows;
            })
        );


        return $this;
    }

    public function execute(): static
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