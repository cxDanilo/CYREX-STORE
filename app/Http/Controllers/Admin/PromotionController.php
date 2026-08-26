<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::orderBy('is_recurring', 'desc')->orderBy('name')->get();

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        $categories = Category::orderBy('parent_id')->orderBy('name')->get();
        $promotion = new Promotion(['active' => true, 'effect' => 'none']);

        return view('admin.promotions.form', compact('promotion', 'categories'));
    }

    public function store(Request $request)
    {
        Promotion::create($this->validated($request));

        return redirect()->route('admin.promociones.index')->with('status', 'Promoción creada.');
    }

    public function edit(Promotion $promotion)
    {
        $categories = Category::orderBy('parent_id')->orderBy('name')->get();

        return view('admin.promotions.form', compact('promotion', 'categories'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $promotion->update($this->validated($request, $promotion));

        return redirect()->route('admin.promociones.index')->with('status', 'Promoción actualizada.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return back()->with('status', 'Promoción eliminada.');
    }

    public function toggleActive(Promotion $promotion)
    {
        $promotion->update(['active' => ! $promotion->active]);

        return back()->with('status', $promotion->active ? 'Promoción activada.' : 'Promoción desactivada.');
    }

    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('promotions', 'slug')->ignore($promotion?->id),
            ],
            'banner_text' => ['required', 'string', 'max:255'],
            'teaser_text' => ['nullable', 'string', 'max:255'],
            'teaser_starts_at' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'discount_label' => ['nullable', 'string', 'max:100'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_month' => ['nullable', 'required_if:is_recurring,1', 'integer', 'min:1', 'max:12'],
            'recurring_day' => ['nullable', 'required_if:is_recurring,1', 'integer', 'min:1', 'max:31'],
            'show_as_modal' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'active' => ['nullable', 'boolean'],
            'effect' => ['required', Rule::in(array_keys(Promotion::EFFECTS))],
            'custom_css' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['is_recurring'] = $request->boolean('is_recurring');
        $data['show_as_modal'] = $request->boolean('show_as_modal');
        $data['active'] = $request->boolean('active');

        if (! $data['is_recurring']) {
            $data['recurring_month'] = null;
            $data['recurring_day'] = null;
        }

        // $request->validate() no convierte '' en null por sí solo (solo
        // "nullable" salta las DEMÁS reglas cuando está vacío) — sin esto,
        // un select/date/input vacío guarda '' literal, y category_id=''
        // rompe la FK contra categories.
        foreach (['category_id', 'teaser_text', 'teaser_starts_at', 'discount_label', 'custom_css'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
