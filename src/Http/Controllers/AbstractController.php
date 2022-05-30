<?php

namespace Dominiquevienne\LaravelMagic\Http\Controllers;

use Dominiquevienne\LaravelMagic\Http\Filters\GenericFilter;
use Dominiquevienne\LaravelMagic\Traits\ClassSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Dominiquevienne\LaravelMagic\Exceptions\ControllerAutomationException;
use Dominiquevienne\LaravelMagic\Http\Requests\BootstrapRequest;
use Dominiquevienne\LaravelMagic\Models\AbstractModel;
use Dominiquevienne\LaravelMagic\Models\Statistic;
use Dominiquevienne\LaravelMagic\Traits\HasPublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use App\Models\User;

class AbstractController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ClassSuggestion;

    private const SORTING_KEY_DEFAULT = 'name';
    private const PAGINATION_MAX_VALUE = 1000;

    protected string $modelName;
    protected string $resourceKey;
    protected ?string $sortingKey;
    protected string $sortingDirection = 'ASC';
    protected array $registeredEndpoints = [
        'index',
        'show',
        'destroy',
        'update',
        'store',
        'validationRules',
    ];
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
        $suggestedModelClassName = $this->getSuggestedClassName();
        $this->validateModel($suggestedModelClassName);
        $this->modelName = $suggestedModelClassName;
    }

    /**
     * @param $modelName
     * @return void
     * @throws ControllerAutomationException
     */
    private function validateModel($modelName): void
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
     * @throws ControllerAutomationException
     * @todo Check if implementing a validation on $filters should be done (available properties for filtering based on fillable)
     *
     */
    public function index(Request $request): Response
    {
        $this->recordCall(__METHOD__);
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

        /** Apply in-app provided filters */
        $filterClass = $this->getSuggestedClassName('filter');
        if (class_exists($filterClass)) {
            $query = $filterClass::applyFilter($query);
        } else {
            $query = GenericFilter::applyFilter($query);
        }

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
     * @throws ControllerAutomationException
     * @todo Implement with
     * @todo Implement rights management
     */
    public function show(Request $request, int $objectId): Response
    {
        $this->recordCall(__METHOD__);

        /** @var AbstractModel $modelName */
        $modelName = $this->modelName;

        $with = $request->get('with');
        $with = $this->filterWith($with);

        $fields = $request->get('fields');
        $fields = $this->filterFields($fields);

        $query = $modelName::query();

        /** Apply in-app provided filters */
        $filterClass = $this->getSuggestedClassName('filter');
        if (class_exists($filterClass)) {
            $query = $filterClass::applyFilter($query);
        } else {
            $query = GenericFilter::applyFilter($query);
        }

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
     * @throws ControllerAutomationException
     * @todo Implement PK usage
     */
    public function destroy(int $objectId): Response
    {
        $this->recordCall(__METHOD__);

        /** @var AbstractModel $modelName */
        $modelName = $this->modelName;
        try {
            $query = $modelName::query();

            /** Apply in-app provided filters */
            $filterClass = $this->getSuggestedClassName('filter');
            if (class_exists($filterClass)) {
                $query = $filterClass::applyFilter($query);
            } else {
                $query = GenericFilter::applyFilter($query);
            }
            $item = $query->where('id', '=', $objectId)->firstOrFail();

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
     * @throws ControllerAutomationException
     * @todo Implement PK usage
     */
    public function update(BootstrapRequest $request, int $objectId): Response
    {
        $this->recordCall(__METHOD__);

        /** @var AbstractModel $object */
        $modelName = new $this->modelName;
        try {
            $query = $modelName::query();

            /** Apply in-app provided filters */
            $filterClass = $this->getSuggestedClassName('filter');
            if (class_exists($filterClass)) {
                $query = $filterClass::applyFilter($query);
            } else {
                $query = GenericFilter::applyFilter($query);
            }
            $object = $query->where('id', '=', $objectId)->firstOrFail();

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
     * @throws ControllerAutomationException
     */
    public function store(BootstrapRequest $request): Response
    {
        $this->recordCall(__METHOD__);

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

    /**
     * @return Response
     * @throws ControllerAutomationException
     */
    public function validationRules(): Response
    {
        $this->recordCall(__METHOD__);
        $requestClass = $this->getSuggestedClassName('request');
        if (class_exists($requestClass)) {
            $requestObject = new $requestClass;
            return $this->sendResponse(collect($requestObject->rules()));
        }
        $meta = [
            'result' => false,
            'http-status' => 500,
            'errors' => [
                'Unable to find validation rules for class ' . $this->modelName,
            ],
        ];
        return $this->sendResponse(new Collection(), $meta);
    }

    /**
     * @param string $featureSlug
     * @return void
     * @throws ControllerAutomationException
     */
    protected function recordCall(string $featureSlug): void
    {
        $featureSlug = explode('::', $featureSlug);
        $featureSlug = $featureSlug[1];
        if (!in_array($featureSlug, $this->registeredEndpoints)) {
            return;
        }

        $tokenDecoded = Session::get('token_decoded');
        if (empty($tokenDecoded['sub'])) {
            throw new ControllerAutomationException('Unable to retrieve user ID');
        }
        $user = User::findOrFail($tokenDecoded['sub']);

        $statistic = new Statistic();
        $statistic->model_name = $this->modelName;
        $statistic->feature_slug = $featureSlug;
        $statistic->user_id = $user->id;
        $statistic->save();
    }
}
