<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\EntityServiceContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        protected readonly EntityServiceContract $entityService
    ) {
    }

    /**
     * Get the entity model class name
     */
    abstract protected function getEntityModelClass(): string;

    /**
     * Get the form request class for this entity
     */
    abstract protected function getFormRequestClass(): string;

    /**
     * Get the view name for index page
     */
    abstract protected function getIndexView(): string;

    /**
     * Get the view name for create page
     */
    abstract protected function getCreateView(): string;

    /**
     * Get the view name for show page
     */
    abstract protected function getShowView(): string;

    /**
     * Get the view name for edit page
     */
    abstract protected function getEditView(): string;

    /**
     * Get the route name prefix (e.g., 'albums' for albums.show, albums.index, etc.)
     */
    abstract protected function getRouteNamePrefix(): string;

    /**
     * Get the relationship name on the User model (e.g., 'albums', 'mosaics', 'entries')
     */
    abstract protected function getRelationshipName(): string;

    /**
     * Get the entity name for view data keys (e.g., 'album', 'mosaic', 'entry')
     */
    abstract protected function getEntityName(): string;

    /**
     * Get additional data to pass to views
     */
    protected function getAdditionalViewData(): array
    {
        return [];
    }

    /**
     * Display a listing of the resource
     */
    public function index(Request $request): Response
    {
        $entities = $this->entityService->getAll(false);

        return Inertia::render($this->getIndexView(), [
            'entities' => $entities,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Show the form for creating a new resource
     */
    public function create(Request $request): Response
    {
        return Inertia::render($this->getCreateView(), [
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request): RedirectResponse
    {
        $formRequestClass = $this->getFormRequestClass();
        $formRequest = new $formRequestClass;
        $rules = $formRequest->rules();

        $validated = $request->validate($rules);

        $entityModelClass = $this->getEntityModelClass();
        $entity = $this->user()->{$this->getRelationshipName()}()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return $this->redirectWithSuccess(
            $this->getRouteNamePrefix().'.show',
            $entity,
            ucfirst($this->getEntityName()).' created successfully'
        );
    }

    /**
     * Display the specified resource
     */
    public function show(string $entityId): Response|RedirectResponse
    {
        $entityModelClass = $this->getEntityModelClass();
        $entity = $entityModelClass::find($entityId);

        if (! $entity) {
            return $this->redirectWithError(
                $this->getRouteNamePrefix().'.index',
                [],
                ucfirst($this->getEntityName()).' not found. You have been redirected to your '.$this->getRouteNamePrefix().'.'
            );
        }

        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        // Get entity with relationships using service
        $entity = $this->entityService->getById($entity, false);

        return Inertia::render($this->getShowView(), [
            $this->getEntityName() => $entity,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(string $entityId): Response|RedirectResponse
    {
        $entityModelClass = $this->getEntityModelClass();
        $entity = $entityModelClass::find($entityId);

        if (! $entity) {
            return $this->redirectWithError(
                $this->getRouteNamePrefix().'.index',
                [],
                ucfirst($this->getEntityName()).' not found. You have been redirected to your '.$this->getRouteNamePrefix().'.'
            );
        }

        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        // Get entity with relationships using service
        $entity = $this->entityService->getById($entity, false);

        return Inertia::render($this->getEditView(), [
            $this->getEntityName() => $entity,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, Model|int|string $entity): RedirectResponse|JsonResponse
    {
        Log::info($this->getEntityName().'Controller@update - Incoming request data:', $request->all());

        $entityModel = $this->resolveEntity($entity);

        // Check if user owns this entity
        $this->authorizeOwnership($entityModel);

        $formRequestClass = $this->getFormRequestClass();
        $formRequest = new $formRequestClass;
        $rules = $formRequest->rules();

        $validated = $request->validate($rules);

        Log::info($this->getEntityName().'Controller@update - Validated data:', $validated);

        // Update entity basic info
        $entityModel->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        Log::info($this->getEntityName().'Controller@update - '.ucfirst($this->getEntityName()).' updated successfully');

        return $this->redirectWithSuccess(
            $this->getRouteNamePrefix().'.show',
            $entityModel,
            ucfirst($this->getEntityName()).' updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(Model|int|string $entity): RedirectResponse
    {
        $entityModel = $this->resolveEntity($entity);

        // Check if user owns this entity
        $this->authorizeOwnership($entityModel);

        $entityModel->delete();

        return $this->redirectWithSuccess(
            $this->getRouteNamePrefix().'.index',
            [],
            ucfirst($this->getEntityName()).' deleted successfully'
        );
    }

    /**
     * Resolve an entity identifier to its corresponding model instance
     */
    protected function resolveEntity(Model|int|string $entity): Model
    {
        if ($entity instanceof Model) {
            return $entity;
        }

        /** @var class-string<Model> $entityModelClass */
        $entityModelClass = $this->getEntityModelClass();

        return $entityModelClass::query()->findOrFail($entity);
    }
}
