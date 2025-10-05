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
    protected string $entityName;

    protected string $entityNamePlural;

    public function __construct(
        protected readonly EntityServiceContract $entityService
    ) {
        $this->entityName = $this->getEntityName();
        $this->entityNamePlural = $this->getEntityNamePlural();
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
     * Get additional data to pass to views
     */
    protected function getAdditionalViewData(): array
    {
        return [];
    }

    /**
     * Get the entity name for this controller
     */
    protected function getEntityName(): string
    {
        $className = class_basename($this->getEntityModelClass());

        return $className;
    }

    /**
     * Get the entity name plural for this controller
     */
    protected function getEntityNamePlural(): string
    {
        return $this->entityName.'s';
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
        $entity = $this->user()->{$this->entityNamePlural}()->create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return $this->redirectWithSuccess(
            $this->entityNamePlural.'.show',
            $entity,
            ucfirst($this->entityName).' created successfully'
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
                $this->entityNamePlural.'.index',
                [],
                ucfirst($this->entityName).' not found. You have been redirected to your '.$this->entityNamePlural.'.'
            );
        }

        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        // Get entity with relationships using service
        $entity = $this->entityService->getById($entity, false);

        return Inertia::render($this->getShowView(), [
            $this->entityName => $entity,
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
                $this->entityNamePlural.'.index',
                [],
                ucfirst($this->entityName).' not found. You have been redirected to your '.$this->entityNamePlural.'.'
            );
        }

        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        // Get entity with relationships using service
        $entity = $this->entityService->getById($entity, false);

        return Inertia::render($this->getEditView(), [
            $this->entityName => $entity,
            ...$this->getAdditionalViewData(),
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $entity): RedirectResponse|JsonResponse
    {
        Log::info($this->entityName.'Controller@update - Incoming request data:', $request->all());

        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        $formRequestClass = $this->getFormRequestClass();
        $formRequest = new $formRequestClass;
        $rules = $formRequest->rules();

        $validated = $request->validate($rules);

        Log::info($this->entityName.'Controller@update - Validated data:', $validated);

        // Update entity basic info
        $entity->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        Log::info($this->entityName.'Controller@update - '.ucfirst($this->entityName).' updated successfully');

        return $this->redirectWithSuccess(
            $this->entityNamePlural.'.show',
            $entity,
            ucfirst($this->entityName).' updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(Model $entity): RedirectResponse
    {
        // Check if user owns this entity
        $this->authorizeOwnership($entity);

        $entity->delete();

        return $this->redirectWithSuccess(
            $this->entityNamePlural.'.index',
            [],
            ucfirst($this->entityName).' deleted successfully'
        );
    }
}
