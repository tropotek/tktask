<?php
namespace App\Db;

interface StatusInterface
{

    /**
     * Get the models current status
     */
    public function getStatus(): string;

    /**
     * Executed when objects status has changed and after new status log saved
     */
    public function onStatusChanged(StatusLog $statusLog): void;

}