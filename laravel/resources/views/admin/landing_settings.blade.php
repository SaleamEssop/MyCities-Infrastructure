@extends('admin.layouts.main')
@php use Illuminate\Support\Facades\Storage; @endphp
@section('title', 'Landing Page Settings')

@section('content')
    <div class="container-fluid">
        <div class="cust-page-head mb-3">
            <h1 class="h3 mb-2 custom-text-heading">Landing Page Settings</h1>
        </div>

        @if(Session::has('alert-message'))
            <div class="alert {{ Session::get('alert-class') }}">
                {{ Session::get('alert-message') }}
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Hero Content</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('ads.landing-settings.save') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label><strong>Background Image</strong></label>
                        <div class="mb-2">
                            @if(!empty($settings?->landing_background))
                                <img src="{{ Storage::url($settings->landing_background) }}" alt="Landing background" style="max-width: 300px; height: auto; border-radius: 6px;">
                            @else
                                <p class="text-muted mb-0">No image uploaded. Using default.</p>
                            @endif
                        </div>

                        <input id="landing_background" type="file" name="landing_background" accept="image/*" style="position: absolute; left: -9999px;" tabindex="-1">
                        <label for="landing_background" class="btn btn-secondary mb-2">Choose file</label>
                        <span id="file-name" class="text-muted ml-2" style="font-size: 0.9rem;"
                              data-initial="{{ $settings->landing_background ? pathinfo($settings->landing_background)['basename'] : 'No file chosen' }}">
                        </span>
                        <small class="form-text text-muted">JPEG/PNG/WebP up to 4MB.</small>

                        <div class="mt-3">
                            <label><strong>Or image URL</strong></label>
                            <input id="landing_background_url" type="url" name="landing_background_url" class="form-control" placeholder="https://example.com/image.jpg" value="{{ old('landing_background_url') }}">
                            <small class="form-text text-muted">Paste a direct image URL to fetch and store.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Title</strong></label>
                        <input type="text" name="landing_title" class="form-control" value="{{ old('landing_title', $settings->landing_title ?? 'Welcome to MyCities') }}">
                    </div>

                    <div class="form-group">
                        <label><strong>Subtitle / Body</strong></label>
                        <textarea name="landing_subtitle" class="form-control" rows="5">{{ old('landing_subtitle', $settings->landing_subtitle ?? 'Welcome to the MyCities App where you can manage your meters and much more free of cost!') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-level-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('landing_background');
    const fileName = document.getElementById('file-name');
    if (input && fileName) {
        fileName.textContent = fileName.dataset.initial || 'No file chosen';
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                const urlInput = document.getElementById('landing_background_url');
                if (urlInput) {
                    urlInput.value = '';
                }
            } else {
                fileName.textContent = 'No file chosen';
            }
        });
    }
});
</script>
@endsection
