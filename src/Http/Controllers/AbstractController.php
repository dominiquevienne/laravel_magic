<?php

namespace Dominiquevienne\LaravelMagic\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Dominiquevienne\LaravelMagic\Exceptions\ControllerAutomationException;
use Dominiquevienne\LaravelMagic\Http\Requests\BootstrapRequest;
use Dominiquevienne\LaravelMagic\Models\AbstractModel;
use Dominiquevienne\LaravelMagic\Traits\HasPublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

class AbstractController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private const REGEX_CONTROLLER = '/Controller$/s';
    private const SORTING_KEY_DEFAULT = 'name';
    private const PAGINATION_MAX_VALUE = 100;

    protected string $modelName;
    protected string $resourceKey;
    protected ?string $sortingKey;
    protected string $sortingDirection = 'ASC';
    private bool $usePublicationStatusTrait = false;

    /**
     * @throws ControllerAutomationException
     */
    #[NoReturn] public function __construct()
    {
        $this->getModelName();
        $this->isUsingPublicationStatusTrait();
        $this->getResourceKey();
        $this->getSortingKey();
    }

    /**
     * @throws ControllerAutomationException
     */
    private function getModelName():void
    {
        if (!empty($this->modelName)) {
            $modelName = $this->modelName;
            $this->validateModel($modelName);
            return;
        }
        $controllerBaseClassName = class_basename(get_class($this));
        $suggestedModelBaseClassName = preg_replace(self::REGEX_CONTROLLER, '', $controllerBaseClassName);
        $suggestedModelClassName = 'App\\Models\\' . $suggestedModelBaseClassName;
        $this->validateModel($suggestedModelClassName);
        $this->modelName = $suggestedModelClassName;
    }

    /**
     * @param $modelName
     * @return void
     * @throws ControllerAutomationException
     */
    private function validateModel($modelName)
    {
        if (!class_exists($modelName)) {
            throw new ControllerAutomationException('The ' . $modelName . ' class does not exist');
        }
    }

    /**
     * @return void
     */
    private function getResourceKey():void
    {
        if (!empty($this->resourceKey)) {
            return;
        }
        $suggestedModelObject = new $this->modelName;
        $modelBaseClassName = class_basename($suggestedModelObject::class);
        $this->resourceKey = Str::of($modelBaseClassName)->plural()->snake();
    }

    /**
     * @return void
     */
    private function getSortingKey():void
    {
        if (empty($this->sortingKey)) {
            $this->sortingKey = self::SORTING_KEY_DEFAULT;
        }
        $object = new $this->modelName;
        if(Schema::hasColumn($object->getTable(), $this->sortingKey)) {
            return;
        }
        $this->sortingKey = null;
    }

    /**
     * @return void
     */
    private function isUsingPublicationStatusTrait():void
    {
        $classUses = class_uses($this->modelName);
        $this->usePublicationStatusTrait = array_key_exists(HasPublicationStatus::class, $classUses);
    }

    /**
     * @param AbstractModel|Collection|LengthAwarePaginator $items
     * @param array $meta
     * @return Response
     * @todo Implement metadata feature
     */
    protected function sendResponse(AbstractModel|Collection|LengthAwarePaginator $items, array $meta = []): Response
    {
        $meta['http-status'] = $meta['http-status'] ?? 200;
        $meta['result'] = $meta['result'] ?? ($meta['http-status']===200);

        $responseBody = [
            $this->resourceKey => $items,
            'meta' => $meta,
        ];
        $httpStatus = $meta['http-status'];


        return response($responseBody, $httpStatus);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Response
     * @todo Check if implementing a validation on $filters should be done (available properties for filtering based on fillable)
     *
     */
    public function index(Request $request): Response
    {
        $fields = $request->get('fields');
        $fields = $this->filterFields($fields);

        $filter = json_decode(
            urldecode(
                $request->get('filter')
            )
        );
        $modelName = $this->modelName;
        $with = $request->get('with');
        $with = $this->filterWith($with);

        /** @var Builder $query */
        $query = $modelName::query();
        if ($this->usePublicationStatusTrait) {
            $query = $query->published();
        }
        foreach ($with as $relationshipName) {
            $query = $query->with([$relationshipName => function($query) {
                $query->take(self::PAGINATION_MAX_VALUE);
            }]);
        }

        if($fields) {
            $query = $query->select($fields);
        }

        if ($filter) {
            if ($filter->fields) {
                if (is_array($filter->fields)) {
                    foreach ($filter->fields as $fieldEntity) {
                        if ($this->filterFields($fieldEntity->field)) {
                            $query = $query->where($fieldEntity->field, $fieldEntity->operator, $fieldEntity->value);
                        }
                    }
                } elseif (is_object($filter->fields)) {
                    foreach ($filter->fields as $field => $value) {
                        $query = $query->where($field, 'LIKE', $value);
                    }
                }
            }
            if (property_exists($filter, 'order')) {
                $this->sortingKey = $filter->order;
                if (preg_match('/(.+)\s+((?:ASC)|(?:DESC))/si', $filter->order, $m)) {
                    $this->sortingKey = $m[1];
                    $this->sortingDirection = $m[2];
                }
            }
        }
        if (!empty($this->sortingKey)) {
            $query = $query->orderBy(trim($this->sortingKey), $this->sortingDirection);
        }
        $items = $query->paginate(self::PAGINATION_MAX_VALUE);

        return $this->sendResponse($items);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int $objectId
     * @return Response
     * @todo Implement rights management
     * @todo Implement with
     */
    public function show(Request $request, int $objectId): Response
    {
        /** @var AbstractModel $modelName */
        $modelName = $this->modelName;

        $with = $request->get('with');
        $with = $this->filterWith($with);

        $fields = $request->get('fields');
        $fields = $this->filterFields($fields);

        $query = $modelName::query();
        foreach ($with as $relationshipName) {
            $query = $query->with([$relationshipName => function($query) {
                $query->take(self::PAGINATION_MAX_VALUE);
            }]);
        }
        if ($this->usePublicationStatusTrait) {
            $query = $query->published();
        }

        if ($fields) {
            $query = $query->select($fields);
        }

        try {
            $item = $query->findOrFail($objectId);

            $meta = [
                'http-status' => 200,
                'result' => true,
            ];
        } catch (ModelNotFoundException $exception) {
            $item = new $modelName;
            $meta = [
                'http-status' => 404,
                'result' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return $this->sendResponse($item, $meta);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $objectId
     * @return Response
     * @todo Implement rights management
     */
    public function destroy(int $objectId): Response
    {
        /** @var AbstractModel $modelName */
        $modelName = $this->modelName;
        try {
            $item = $modelName::findOrFail($objectId);

            if ($item->delete()) {
                $meta = [
                    'http-status' => 200,
                    'result' => true,
                    'message' => 'The resource has been deleted',
                ];
            } else {
                $meta = [
                    'http-status' => 500,
                    'result' => false,
                    'message' => 'An error occurred',
                ];
            }
        } catch (ModelNotFoundException $exception) {
            $item = new $modelName;
            $meta = [
                'http-status' => 404,
                'result' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return $this->sendResponse($item, $meta);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BootstrapRequest $request
     * @param int $objectId
     * @return Response
     */
    public function update(BootstrapRequest $request, int $objectId): Response
    {
        /** @var AbstractModel $object */
        $object = new $this->modelName;
        try {
            $object = $object->findOrFail($objectId);
            $meta = $this->saveByFillable($object, $request);
        } catch (ModelNotFoundException $exception) {
            $meta = [
                'http-status' => 404,
                'result' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return $this->sendResponse($object, $meta);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BootstrapRequest $request
     * @return Response
     */
    public function store(BootstrapRequest $request): Response
    {
        $object = new $this->modelName;
        $meta = $this->saveByFillable($object, $request);
        return $this->sendResponse($object, $meta);
    }

    /**
     * @param AbstractModel $object
     * @param Request $request
     * @return array
     */
    private function saveByFillable(AbstractModel $object, Request $request): array
    {
        foreach ($object->getFillable() as $field) {
            $object->$field = $request->input($field);
        }
        $meta = [];
        if (!$object->save()) {
            $meta = [
                'http-status' => 500,
                'result' => false,
                'message' => 'An error occurred',
            ];
        }

        return $meta;
    }

    /**
     * This method will filter the with value in order to only get the relations the model has
     *
     * @param string|null $with
     * @return array
     */
    private function filterWith(?string $with): array
    {
        if ($with) {
            $with = explode(',', $with);
            /** @var AbstractModel $object */
            $object = new $this->modelName;
            foreach ($with as $key => $relationshipName) {
                if (!$object->hasRelation($relationshipName)) {
                    unset($with[$key]);
                }
            }

        } else {
            $with = [];
        }
        return $with;
    }

    /**
     * @param string|null $fields
     * @return string[]
     */
    private function filterFields(?string $fields): array
    {
        $fields = explode(',', $fields);
        /** @var AbstractModel $object */
        $object = new $this->modelName;
        $fillable = $object->getFillable();

        foreach ($fields as $key => $field) {
            if (!in_array($field, $fillable) && !Schema::hasColumn($object->getTable(), $field)) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}
