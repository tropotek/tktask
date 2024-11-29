<?php
namespace App\Db;

interface StatusInterface
{

    /**
     * Get the models current status
     */
    public function getStatus(): string;

    /**
     * Return true when the status changes are required to trigger the status.change event
     *
     */
    public function hasStatusChanged(StatusLog $statusLog): bool;

}