<div class="wrap cms-cat-strip">
  @foreach(\App\Models\Category::parents()->get() as $cat)
    <a class="cms-cat-chip" href="{{ route('shop', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
  @endforeach
</div>
