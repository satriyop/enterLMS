<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Category::class);

        $query = Category::query()
            ->withCount('courses')
            ->with('parent');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('order')->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('admin/categories/Edit', [
            'category' => null,
            'parentCategories' => Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('admin/categories/Edit', [
            'category' => $category,
            'parentCategories' => Category::whereNull('parent_id')
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->courses()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki kursus.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
