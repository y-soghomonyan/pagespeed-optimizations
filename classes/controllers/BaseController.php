<?php
namespace PSO\Controllers;

abstract class BaseController
{
    public static function init(): void
    {
        new static();
    }

    public function __construct()
    {
        $this->addActions();
        $this->addFilters();
    }

    public function addActions(): void
    {
    }

    public function addFilters(): void
    {
    }

    protected function dump(...$vars): void
    {
        echo "<pre>";
        var_dump($vars);
        echo "</pre>";
    }
}