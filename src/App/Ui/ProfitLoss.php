<?php
namespace App\Ui;

use App\Db\Payment;
use App\Db\User;
use Dom\Template;
use Tk\Db\Filter;
use Tk\Money;


/**
 * Display the Profit and loss report table
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
        ]);
        $grossProfit = Money::create();
        foreach ($paymentList as $payment) {
            $grossProfit = $grossProfit->add($payment->amount);
        }
        $template->setText('profit', $grossProfit->toString());

        // Expenses
        $total = \Tk\Money::create();
        $claimTotal = \Tk\Money::create();

        $categoryList = \App\Db\ExpenseCategory::findFiltered(Filter::create([], 'name'));
        foreach ($categoryList as $category) {
            $repeat = $template->getRepeat('expenseRow');
            $expenseList = \App\Db\Expense::findFiltered(array(
                'dateStart' => $this->dateSet[0],
                'dateEnd' => $this->dateSet[1],
                'expenseCategoryId' => $category->expenseCategoryId
            ));
            $catTotal = \Tk\Money::create();
            $catClaim = \Tk\Money::create();
            foreach ($expenseList as $expense) {
                $catTotal = $catTotal->add($expense->total);
                $catClaim = $catClaim->add($expense->getClaimableAmount());
            }
            if ($catTotal->getAmount() <= 0) continue;

            $repeat->setText('expense-claim', sprintf('[%s%%] ', ($category->claim*100)));
            $repeat->setText('total', $catTotal->toString());
            $repeat->setText('total-claim', $catClaim->toString());
            $repeat->setText('categoryName', $category->name);
            $total = $total->add($catTotal);
            $claimTotal = $claimTotal->add($catClaim);

            $repeat->appendRepeat();
        }
        $netProfit = $grossProfit->subtract($total);

        $template->setText('totalExpenses', $total->toString());
        $template->setText('totalExpenses-claim', $claimTotal->toString());
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
<style>
table td.currency {
    text-align: right;
}
table .head td {
    background-color: #F9F9F9;
}
</style>

  <table class="table table-borderless" style="border-collapse: collapse;">
    <tr>
      <td class="text-start" colspan="4"><h3>Income</h3></td>
    </tr>
    <tr>
      <td class="w-100">Gross Profit</td>
      <td></td>
      <td class="text-center" var="profit-claim"></td>
      <td class="currency" var="profit">$0.00</td>
    </tr>
    <tr>
      <td class="text-start" colspan="4"><h3>Expenses</h3></td>
    </tr>
    <tr>
      <th class="text-start">Description</th>
      <th>Claim&nbsp;%</th>
      <th>Claimable</th>
      <th>Total</th>
    </tr>
    <tr repeat="expenseRow">
      <td var="categoryName">Test</td>
      <td class="text-end text-muted" var="expense-claim">0%</td>
      <td class="currency text-nowrap" var="total-claim">$0.00</td>
      <td class="currency" var="total">$0.00</td>
    </tr>
    <tr class="head">
      <td><b>Total Expenses</b></td>
      <td></td>
      <td class="currency" var="totalExpenses-claim">$0.00</td>
      <td class="currency" var="totalExpenses">$0.00</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp;</td>
    </tr>
    <tr class="head">
      <td><b>Net Profit</b></td>
      <td></td>
      <td class="text-center" var="netProfit-claim"></td>
      <td class="currency" var="netProfit">$0.00</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp;</td>
    </tr>
    <tr class="head" choice="v-tax">
      <td><b>Payable Tax Est <span var="tax-ratio"></span></b></td>
      <td></td>
      <td class="text-center" var="tax-claim"></td>
      <td class="currency" var="tax">$0.00</td>
    </tr>
  </table>
</div>
HTML;
        return Template::load($html);
    }

}
