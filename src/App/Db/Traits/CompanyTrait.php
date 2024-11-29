<?php
namespace App\Db\Traits;

use App\Db\Company;

trait CompanyTrait
{
    private ?Company $_company = null;

    public function getCompany(): ?Company
    {
        if (!$this->_company) {
            $this->_company = Company::find($this->companyId);
        }
        return $this->_company;
    }

}
