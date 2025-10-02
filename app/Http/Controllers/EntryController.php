<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\BaseEntityController;
use App\Models\Entry;
use App\Services\EntryService;

class EntryController extends BaseEntityController
{
    public function __construct(EntryService $entryService)
    {
        parent::__construct($entryService);
    }

    /**
     * Get the entity model class name
     */
    protected function getEntityModelClass(): string
    {
        return Entry::class;
    }

    /**
     * Get the form request class for this entity
     */
    protected function getFormRequestClass(): string
    {
        return \App\Http\Requests\StoreEntryRequest::class;
    }

    /**
     * Get the view name for index page
     */
    protected function getIndexView(): string
    {
        return 'Entries/Index';
    }

    /**
     * Get the view name for create page
     */
    protected function getCreateView(): string
    {
        return 'Entries/Create';
    }

    /**
     * Get the view name for show page
     */
    protected function getShowView(): string
    {
        return 'Entries/Show';
    }

    /**
     * Get the view name for edit page
     */
    protected function getEditView(): string
    {
        return 'Entries/Edit';
    }
}
