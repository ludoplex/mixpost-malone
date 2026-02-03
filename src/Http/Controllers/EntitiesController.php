<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\EntityResource;
use Inovector\Mixpost\Models\Entity;

class EntitiesController extends Controller
{
    public function index(): Response
    {
        $entities = Entity::withCount('accounts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Entities', [
            'entities' => EntityResource::collection($entities),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'hex_color' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $maxOrder = Entity::max('sort_order') ?? 0;

        Entity::create([
            'name' => $validated['name'],
            'hex_color' => $validated['hex_color'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Entity created successfully.');
    }

    public function update(Request $request, Entity $entity): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'hex_color' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $entity->update($validated);

        return back()->with('success', 'Entity updated successfully.');
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        // Accounts will have entity_id set to null (nullOnDelete)
        $entity->delete();

        return back()->with('success', 'Entity deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entities' => ['required', 'array'],
            'entities.*.id' => ['required', 'exists:mixpost_entities,id'],
            'entities.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['entities'] as $item) {
            Entity::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Order updated.');
    }
}
