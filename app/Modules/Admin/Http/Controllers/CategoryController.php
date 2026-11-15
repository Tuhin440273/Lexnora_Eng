<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\CategoryRequest;
use App\Modules\Admin\Http\Resources\CategoryResource;
use App\Modules\Admin\Services\SlugService;
use App\Modules\Admin\Services\SortOrderService;
use App\Modules\Courses\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly SortOrderService $order,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('courses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/categories/index', [
            'categories' => CategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->slugs->unique(Category::class, $data['name'], null, 120),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $this->order->next(Category::query()),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $category->slug,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->courses()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This category still has courses. Move or delete them first.'),
            ]);

            return back();
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
