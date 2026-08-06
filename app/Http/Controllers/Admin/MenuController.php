<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::withCount('items')->orderBy('name')->get();

        return view('admin.menus.index', compact('menus'));
    }

    public function create()
    {
        $menu = new Menu();
        $pages = Page::orderBy('title')->get();

        return view('admin.menus.form', compact('menu', 'pages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('menus', 'key')],
        ]);

        $menu = Menu::create($data);
        $this->syncItems($menu, $request);

        return redirect()->route('admin.menus.index')->with('status', 'Menú creado.');
    }

    public function edit(Menu $menu)
    {
        $menu->load('items');
        $pages = Page::orderBy('title')->get();

        return view('admin.menus.form', compact('menu', 'pages'));
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('menus', 'key')->ignore($menu->id)],
        ]);

        $menu->update($data);
        $this->syncItems($menu, $request);

        return redirect()->route('admin.menus.index')->with('status', 'Menú actualizado.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return back()->with('status', 'Menú eliminado.');
    }

    private function syncItems(Menu $menu, Request $request): void
    {
        $keptIds = [];

        foreach ($request->input('items', []) as $i => $item) {
            if (empty($item['label'])) {
                continue;
            }

            $payload = [
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'page_id' => ($item['page_id'] ?? '') !== '' ? $item['page_id'] : null,
                'sort_order' => $i,
            ];

            $id = $item['id'] ?? null;

            if ($id && $menu->items()->where('id', $id)->exists()) {
                $menu->items()->where('id', $id)->update($payload);
                $keptIds[] = $id;
            } else {
                $keptIds[] = $menu->items()->create($payload)->id;
            }
        }

        $menu->items()->whereNotIn('id', $keptIds)->delete();
    }
}
