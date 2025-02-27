<?php
namespace App\Component;

use App\Db\Payment;
use App\Db\User;
use Dom\Template;
use Tk\Db\Filter;
use Tk\Money;


/**
 * Display the Profit and loss report table
 *
 * @todo, does this need to be in its own file??? I would not think so.
 * @todo This is not an HTMX component, this is a renderer and should be in a forlder /App/Renderers or /App/Ui
 */
class ProfitLoss extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{

    protected array $dateSet;


    public function __construct(array $dateset)
    {
        $this->dateSet = $dateset;
    }

    public function doDefault(): string
    {
        if (!User::getAuthUser()->isStaff()) return '';


        return $this->show()->toString();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        $paymentList = Payment::findFiltered([
            'dateStart' => $this->dateSet[0],
            'dateEnd' => $this->dateSet[1],
            'status' => Payment::STATUS_CLEARED,
        ]);
        $grossProfit = Money::create();
        foreach ($paymentList as $payment) {
            $grossProfit = $grossProfit->add($payment->amount);
        }
        $template->setText('profit', $grossProfit->toString());

        // Expenses
        $total = \Tk\Money::create();

        $categoryList = \App\Db\ExpenseCategory::findFiltered(Filter::create([], 'name'));
        foreach ($categoryList as $category) {
            $repeat = $template->getRepeat('expenseRow');
            $expenseList = \App\Db\Expense::findFiltered(array(
                'dateStart' => $this->dateSet[0],
                'dateEnd' => $this->dateSet[1],
                'expenseCategoryId' => $category->expenseCategoryId
            ));
            $catTotal = \Tk\Money::create();
            foreach ($expenseList as $expense) {
                $catTotal = $catTotal->add($expense->getBusinessTotal());
            }
            if ($catTotal->getAmount() <= 0) continue;

            $repeat->setText('total', $catTotal->toString());
            $repeat->setText('categoryName', $category->name);
            $total = $total->add($catTotal);

            $repeat->appendRepeat();
        }
        $netProfit = $grossProfit->subtract($total);

        $template->setText('totalExpenses', $total->toString());
        $template->setText('netProfit', $netProfit->toString());

        // Estimate the amount of tax payable for year
        $taxRatio = 0.20;
        $tax = $netProfit->multiply($taxRatio);
        $template->setText('tax-ratio', '('.($taxRatio*100).'%)');
        $template->setText('tax', $tax->toString());
        $template->setVisible('v-tax');

        return $template;
    }


    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div class="profit-loss">
  <table style="border-collapse: collapse;">
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td class="w-100">Gross Profit</td>
      <td class="currency" var="profit">_$0.00</td>
    </tr>
    <tr class="header">
      <td colspan="2"><b>Expenses</b></td>
    </tr>
    <tr repeat="expenseRow">
      <td var="categoryName">_Test</td>
      <td class="currency" var="total">_$0.00</td>
    </tr>
    <tr class="header">
      <td><b>Total Expenses</b></td>
      <td class="currency" var="totalExpenses">_$0.00</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td><b>Net Profit</b></td>
      <td class="currency" var="netProfit">_$0.00</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr choice="v-tax">
      <td><b>Payable Tax Est <span var="tax-ratio"></span></b></td>
      <td class="currency" var="tax">_$0.00</td>
    </tr>
  </table>
</div>
HTML;
        return Template::load($html);
    }

}
