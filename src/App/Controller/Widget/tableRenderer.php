<?php

/** @var array $rows */
/** @var \Tt\Table\Cell $cell */


/** @var \Tt\Table $table */
$table = $this->table;



function e(mixed $s): string
{
    if (is_numeric($s)) return strval($s);
    if (empty($s)) return '';
    return htmlentities($s, ENT_QUOTES | ENT_HTML401, "UTF-8", false);
}

?>
<table class="tk-table table table-bordered table-hover <?= $table->getCssString() ?>" <?= $table->getAttrString() ?>>
    <thead class="table-light">
        <tr>
            <? foreach ($table->getCells() as $cell): ?>
                <th <?= $cell->getHeaderAttrs()->getAttrString(true) ?>>
                    <?
                        // todo mm:
                        $orderUrl = '#';
                    ?>
                    <? if ($cell->isSortable()): ?>
                        <a class="noblock" href="<?= $orderUrl ?>"><?= e($cell->getHeader()) ?></a>
                    <? else: ?>
                        <?= e($cell->getHeader()) ?>
                    <? endif ?>
                </th>
            <? endforeach ?>
        </tr>
    </thead>
    <tbody>
        <? foreach ($rows as $row): ?>
            <tr>
                <? foreach ($table->getCells() as $cell): ?>
                    <td <?= $cell->getAttrString(true) ?>>
                        <?= $cell->getValue($row) ?>
                    </td>
                <? endforeach ?>
            </tr>
        <? endforeach ?>
    </tbody>
</table>
