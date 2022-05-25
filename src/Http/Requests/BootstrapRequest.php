<?php

namespace Dominiquevienne\LaravelMagic\Http\Requests;

use Dominiquevienne\LaravelMagic\Exceptions\ControllerAutomationException;
use Dominiquevienne\LaravelMagic\Traits\ClassSuggestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BootstrapRequest extends FormRequest
{
    use ClassSuggestion;

    private string $requestClassName;
    private string $resourceName;
    private string $resourceKeyName;
    private string $modelClassName;
    private ?int $modelId;


    /**
     * @return void
     * @throws ControllerAutomationException
     */
    private function getDataFromRoute(): void
    {
        $controllerName = $this->route()->getController();

        $routeName = $this->route()->getName();
        $this->resourceKeyName = explode('.', $routeName)[0];
        $this->resourceName = Str::singular($this->resourceKeyName);
        $modelName = str_replace(
            '_',
            '',
            Str::title($this->resourceName)
        );

        $this->modelId = $this->route(strtolower($modelName));
        $this->modelClassName = $this->getSuggestedClassName('model', $controllerName);
        $this->requestClassName = $this->getSuggestedClassName('request', $controllerName);
    }

    /**
     * @return void
     * @throws ControllerAutomationException
     */
    public function prepareForValidation(): void
    {
        $this->getDataFromRoute();
        $requestContent = $this->getContent();
        $contentDecoded = json_decode($requestContent);
        $model = new $this->modelClassName;
        $resourceKeyName = $this->resourceKeyName;

        $fillables = $model->getFillable();
        $filteringArray = [];
        $fromJson = (property_exists($contentDecoded, $resourceKeyName)) ?? false;

        foreach ($fillables as $field) {
            $filteringArray[$field] = $fromJson ? $contentDecoded->$resourceKeyName->$field : $this->{$field};
        }

        $this->replace($filteringArray);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @todo Implement rights management
     */
    public function authorize(): bool
    {
        /** @var FormRequest $requestBasedOnRoute */
        $requestBasedOnRoute = new $this->requestClassName;
        return $requestBasedOnRoute->authorize();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        /** @var FormRequest $requestBasedOnRoute */
        $requestBasedOnRoute = new $this->requestClassName;
        $rules = $requestBasedOnRoute->rules($this->modelId);
        $modelId = $this->route()->parameters()[$this->resourceName] ?? null;

        $rulesTreated = $rules;
        foreach ($rules as $row => $rule) {
            if ($this->isMethod('put') || $this->isMethod('patch')) {
                if (!is_array($rule)) {
                    $rule = explode('|', $rule);
                }
                $key = array_search('required', $rule);
                if (is_int($key)) {
                    unset($rule[$key]);
                }
                $rule = implode('|', $rule);
                if (empty($rule)) {
                    unset($rulesTreated[$row]);
                } else {
                    $rulesTreated[$row] = $rule;
                }
            }
            $replace = null;
            if (array_key_exists($row, $rulesTreated)) {
                if ($modelId) {
                    $replace = ',' . $modelId;
                }
                $rulesTreated[$row] = str_replace(',#modelId', $replace, $rulesTreated[$row]);
            }
        }

        return $rulesTreated;
    }
}
