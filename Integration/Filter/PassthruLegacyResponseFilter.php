<?php

namespace Webfactory\Bundle\LegacyIntegrationBundle\Integration\Filter;

use Webfactory\Bundle\LegacyIntegrationBundle\Integration\Filter as FilterInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ein LegacyIntergration-Filter, der die Response der Altanwendung
 * unverändert zurückgibt.
 *
 * Subklassen können die check() Methode überschreiben um die
 * legacy-Response nur unter bestimmten Bedingungen zurückzugeben.
 */
class PassthruLegacyResponseFilter implements FilterInterface {

    public function filter(FilterControllerEvent $event, Response $response) {
        if ($this->check($response)) {
            $event->setController(function() use ($response) {
                return $response;
            });
            $event->stopPropagation();
        }
    }

    protected function check(Response $response) {
        return true;
    }

}