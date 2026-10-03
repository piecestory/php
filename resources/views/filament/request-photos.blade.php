{{-- Request photos from the private disk, served only through the authorised admin route. --}}
@php($photos = $getRecord()->getMedia($collection))

@if ($photos->isEmpty())
    <p class="text-sm text-gray-500">{{ __('admin.requests.no_photos') }}</p>
@else
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($photos as $photo)
            <a href="{{ route('admin.private-media', $photo) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-gray-200">
                <img src="{{ route('admin.private-media', $photo) }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
            </a>
        @endforeach
    </div>
@endif
