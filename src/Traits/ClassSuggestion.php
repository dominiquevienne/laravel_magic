<?php

namespace Dominiquevienne\LaravelMagic\Traits;

use Dominiquevienne\LaravelMagic\Exceptions\ControllerAutomationException;

trait ClassSuggestion
{
    private string $regexController = '/Controller$/s';


    /**
     * Will determine a suggested class name for model based on Controller
     *
     * @param string $type
     * @param object|null $controller
     * @return string
     * @throws ControllerAutomationException
     */
    private function getSuggestedClassName(string $type = 'model', ?object $controller = null): string
    {
        if (!is_object($controller)) {
            $controller = $this;
        }
        $availableTypes = [
            'model' => 'Models',
            'request' => 'Http\\Requests',
            'filter' => 'Http\\Filters',
        ];
        if (!array_key_exists($type, $availableTypes)) {
            throw new ControllerAutomationException('The class type ' . $type . ' is not supported');
        }

        $controllerClassName = get_class($controller);

        /**
         * Get Model namespace based on controller namespace
         */
        preg_match('/^(.*)\\Http/', $controllerClassName, $m);
        $suggestedNameSpace = $m[1] . $availableTypes[$type];

        /**
         * Get Model name based on controller base name
         */
        $controllerBaseClassName = class_basename($controllerClassName);
        $suggestedModelBaseClassName = preg_replace($this->regexController, '', $controllerBaseClassName);
        if ($type === 'request') {
            $suggestedModelBaseClassName .= 'Request';
        } elseif ($type === 'filter') {
            $suggestedModelBaseClassName .= 'Filter';
        }

        return $suggestedNameSpace . '\\' . $suggestedModelBaseClassName;
    }
}
