<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\EntityServiceContract;
use App\Models\BaseEntity;
use App\Services\Plans\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Base controller for all entities providing common CRUD operations
 * Extend this class for entity-specific controllers
 */
abstract class BaseEntityController extends BaseController
{
    public function __construct(
        protected readonly EntityServiceContract $entityService,
        protected readonly PlanLimitService $planLimitService,
    ) {}

    /**
     * @return class-string<BaseEntity>
     */
    abstract protected function getEntityModelClass(): string;

    /**
     * @return class-string
     */
    abstract protected function getFormRequestClass(): string;

    abstract protected function getIndexView(): string;

    abstract protected function getCreateView(): string;

    abstract protected function getShowView(): string;

    abstract protected function getEditView(): string;

    abstract protected function getRouteNamePrefix(): string;

    abstract protected function getRelationshipName(): string;

    abstract protected function getEntityName(): string;

    /**
     * @return array<string, mixed>
     */
    protected function getAdditionalViewData(): array
    {
        return [];
    }

    /**
     * Extra attributes merged into the create payload after title/description.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function extraCreationAttributes(array $validated): array
    {
        return [];
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, mixed>  $validated
     */
    protected function fillValidatedAttributes(BaseEntity $entity, array $validated, array $keys): void
    {
        $updateData = [];

        foreach ($keys as $key) {
            if (isset($validated[$key])) {
                $updateData[$key] = $validated[$key];
            }
        }

        if ($updateData !== []) {
            $entity->update($updateData);
        }
    }

    public function index(Request $request): Response
    {
        $entities = $this->entityService->getAll(false);

        return Inertia::render($this->getIndexView(), [
            'entities' => $entities,
            $this->getRelationshipName() => $entities,
            ...$this->getAdditionalViewData(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render($this->getCreateView(), [
            ...$this->getAdditionalViewData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $formRequestClass = $this->getFormRequestClass();
        $formRequest = new $formRequestClass;
        $validated = $request->validate($formRequest->rules());

        $resource = $this->getRelationshipName();

        if ($this->planLimitService->hasReached($this->user(), $resource)) {
            return $this->redirectBackWithError(
                $this->planLimitService->limitMessage($this->user(), $resource)
            );
        }

        $entity = $this->user()->{$this->getRelationshipName()}()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            ...$this->extraCreationAttributes($validated),
        ]);

        return $this->redirectWithSuccess(
            $this->getRouteNamePrefix().'.show',
            $entity,
            ucfirst($this->getEntityName()).' created successfully'
        );
    }

    public function show(string $entityId): Response|RedirectResponse
    {
        return $this->renderOwnedEntity($entityId, $this->getShowView());
    }

    public function edit(string $entityId): Response|RedirectResponse
    {
        return $this->renderOwnedEntity($entityId, $this->getEditView());
    }

    protected function deleteOwned(BaseEntity $entity): RedirectResponse
    {
        $this->authorizeOwnership($entity);

        $entity->delete();

        return $this->redirectWithSuccess(
            $this->getRouteNamePrefix().'.index',
            [],
            ucfirst($this->getEntityName()).' deleted successfully'
        );
    }

    private function renderOwnedEntity(string $entityId, string $view): Response|RedirectResponse
    {
        $entityClass = $this->getEntityModelClass();
        $entity = $entityClass::query()->find($entityId);

        if (! $entity instanceof BaseEntity) {
            return $this->redirectWithError(
                $this->getRouteNamePrefix().'.index',
                [],
                ucfirst($this->getEntityName()).' not found. You have been redirected to your '.$this->getRouteNamePrefix().'.'
            );
        }

        $this->authorizeOwnership($entity);

        return Inertia::render($view, [
            $this->getEntityName() => $this->entityService->getById($entity, false),
            ...$this->getAdditionalViewData(),
        ]);
    }
}
