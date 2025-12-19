@extends('admin.layouts.main')
@section('title', 'Application Settings')

@section('content')
    <div class="container-fluid">
        <div class="cust-page-head mb-3">
            <h1 class="h3 mb-2 custom-text-heading">Application Settings</h1>
        </div>

        @if(Session::has('alert-message'))
            <div class="alert {{ Session::get('alert-class') }}">
                {{ Session::get('alert-message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            <!-- Demo Mode Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Demo / Production Mode</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>Application Mode</strong></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="production_mode" name="demo_mode" value="0" 
                                       class="custom-control-input" {{ !($settings->demo_mode ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="production_mode">
                                    <strong>Production Mode</strong> (Live Data Only)
                                    <br>
                                    <small class="text-muted">Hides all demo/test users, ads, and pages. Only shows real production content.</small>
                                </label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input type="radio" id="demo_mode" name="demo_mode" value="1" 
                                       class="custom-control-input" {{ ($settings->demo_mode ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="demo_mode">
                                    <strong>Demo Mode</strong> (Include Test Content)
                                    <br>
                                    <small class="text-muted">Shows all content including seed users, test ads, and demo pages.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Configuration Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Database Configuration</h6>
                    <span class="badge badge-{{ ($settings->db_mode ?? 'internal') === 'internal' ? 'primary' : 'success' }}">
                        Currently: {{ ($settings->db_mode ?? 'internal') === 'internal' ? 'Internal (Container)' : 'External' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                        This controls which MySQL database Laravel connects to. Use <strong>Internal</strong> for testing with seed data. Switch to <strong>External</strong> for production with real users.
                    </div>

                    <div class="form-group">
                        <label><strong>Database Mode</strong></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="db_mode_internal" name="db_mode" value="internal" 
                                       class="custom-control-input" {{ ($settings->db_mode ?? 'internal') === 'internal' ? 'checked' : '' }}
                                       onchange="toggleExternalDbFields()">
                                <label class="custom-control-label" for="db_mode_internal">
                                    <strong>Internal Database (Container MySQL)</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-database text-primary"></i>
                                        Uses MySQL running inside Docker container. Perfect for testing and development.
                                        <br><strong>Data will be wiped</strong> when container is rebuilt with <code>docker compose down -v</code>
                                    </small>
                                </label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input type="radio" id="db_mode_external" name="db_mode" value="external" 
                                       class="custom-control-input" {{ ($settings->db_mode ?? 'internal') === 'external' ? 'checked' : '' }}
                                       onchange="toggleExternalDbFields()">
                                <label class="custom-control-label" for="db_mode_external">
                                    <strong>External Database (Production)</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-server text-success"></i>
                                        Connects to external MySQL server (e.g., separate DigitalOcean droplet). 
                                        <br><strong>Data is safe</strong> during container rebuilds. Use this for live production.
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- External Database Credentials (shown only when external mode selected) -->
                    <div id="external_db_fields" style="display: {{ ($settings->db_mode ?? 'internal') === 'external' ? 'block' : 'none' }};">
                        <hr class="my-4">
                        <h6 class="font-weight-bold mb-3">External MySQL Configuration</h6>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="external_db_host">Database Host <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('external_db_host') is-invalid @enderror" 
                                           id="external_db_host" name="external_db_host" 
                                           value="{{ old('external_db_host', $settings->external_db_host) }}"
                                           placeholder="e.g., 157.245.123.45 or db.mycities.co.za">
                                    @error('external_db_host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="external_db_port">Port <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('external_db_port') is-invalid @enderror" 
                                           id="external_db_port" name="external_db_port" 
                                           value="{{ old('external_db_port', $settings->external_db_port ?? 3306) }}"
                                           placeholder="3306">
                                    @error('external_db_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="external_db_database">Database Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('external_db_database') is-invalid @enderror" 
                                           id="external_db_database" name="external_db_database" 
                                           value="{{ old('external_db_database', $settings->external_db_database) }}"
                                           placeholder="mycities_production">
                                    @error('external_db_database')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="external_db_username">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('external_db_username') is-invalid @enderror" 
                                           id="external_db_username" name="external_db_username" 
                                           value="{{ old('external_db_username', $settings->external_db_username) }}"
                                           placeholder="mycities_user">
                                    @error('external_db_username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="external_db_password">Password 
                                @if($settings->external_db_password)
                                    <small class="text-muted">(leave blank to keep current password)</small>
                                @else
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="password" class="form-control @error('external_db_password') is-invalid @enderror" 
                                   id="external_db_password" name="external_db_password" 
                                   placeholder="Enter MySQL password">
                            @error('external_db_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <strong><i class="fas fa-shield-alt"></i> Security:</strong>
                            Password is encrypted before storage. Connection is tested before saving.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schema Sync Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-sync"></i> Database Schema Synchronization
                    </h6>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Important:</strong> When switching between databases, ensure both have identical schemas.
                    </p>
                    <p class="mb-0">
                        <strong>To keep schemas synchronized:</strong><br>
                        Run this command after any database migration:
                    </p>
                    <pre class="bg-light p-3 mt-2"><code>docker exec mycities-laravel php artisan db:migrate-both --force</code></pre>
                    <small class="text-muted">
                        This command runs migrations on BOTH internal and external databases automatically.
                    </small>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-right mb-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>

        </form>

        <script>
        function toggleExternalDbFields() {
            const isExternal = document.getElementById('db_mode_external').checked;
            document.getElementById('external_db_fields').style.display = isExternal ? 'block' : 'none';
        }
        </script>
    </div>
@endsection
