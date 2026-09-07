<?php

namespace TetesDePioche\LaravelModularApi\Traits\Views;

use Illuminate\Support\Facades\File;
use LaravelModularApi;
use TetesDePioche\LaravelModularApi\Exceptions\InternalErrorException;

trait HasViews
{
    public function loadViews(): void
    {
        throw_if(
            ! method_exists($this, 'loadViewsFrom'),
            new InternalErrorException('Class needs to implement loadViewsFrom() method.')
        );

        foreach (LaravelModularApi::serviceTree() as $domain => $serviceList) {
            foreach ($serviceList as $service) {
                $this->loadServiceMails($domain, $service);
                $this->loadServiceViews($domain, $service);
            }
        }
    }

    private function loadServiceMails(string $domain, string $service): void
    {
        $mailsPath = LaravelModularApi::servicePath($domain, $service)
            . DIRECTORY_SEPARATOR . 'Mails'
            . DIRECTORY_SEPARATOR . 'Templates';

        if (File::isDirectory($mailsPath)) {
            $this->loadViewsFrom($mailsPath, $this->viewNamespace($domain, $service));
        }
    }

    private function loadServiceViews(string $domain, string $service): void
    {
        $viewsPath = LaravelModularApi::servicePath($domain, $service) . DIRECTORY_SEPARATOR . 'Views';

        if (File::isDirectory($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->viewNamespace($domain, $service));
        }
    }

    private function viewNamespace(string $domain, string $service): string
    {
        return $domain . '.' . $service;
    }
}
