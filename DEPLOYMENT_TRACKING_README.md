# Deployment Tracking System

## Overview
This system provides visual deployment tracking with push IDs, status monitoring, and admin panel integration.

## Files Created/Modified

### New Files:
- `deploy.bat` - Enhanced deployment script with progress tracking
- `RESTORE_POINT.md` - Documentation for rollback procedures
- `.last_push_id` - Local tracking file (created on deployment, gitignored)

### Modified Files:
- `laravel/app/Http/Controllers/SettingsController.php` - Added `getDeploymentInfo()` method
- `laravel/resources/views/admin/settings.blade.php` - Added "Deployment Status" card

## How It Works

### 1. Deployment Process (`deploy.bat`)
When you run `deploy.bat`, it:
1. **Generates a unique Push ID** (format: `YYYYMMDD-HHMMSS`)
2. **Validates all repositories** (Infrastructure, Laravel, Vue-Quasar)
3. **Pushes to GitHub** (commits and pushes each repo)
4. **Deploys to server** (SSH, pull, rebuild, start containers)
5. **Syncs databases** (runs `db:migrate-both`)
6. **Verifies deployment** (checks containers and website)
7. **Records tracking info** (creates `.last_deployment_id`, `.last_deployment_time`, `.deployment_status` on server)

### 2. Admin Panel Display
The admin Settings page now shows:
- **Last Deployment ID** - The Push ID from the last deployment
- **Deployment Time** - When the deployment completed
- **Status** - SUCCESS/FAILED/UNKNOWN
- **Current Commit** - Git commit hash of current server code

## Usage

### Running Deployment:
```batch
cd c:\docker\MyCities-Infrastructure
deploy.bat
```

### Viewing Deployment Status:
1. Log into admin panel: `https://your-domain/admin`
2. Navigate to: **Settings** (in sidebar)
3. Scroll to: **Deployment Status** card

## Tracking Files

### Server-Side (on server at `/opt/mycities/`):
- `.last_deployment_id` - Push ID
- `.last_deployment_time` - Timestamp
- `.deployment_status` - SUCCESS/FAILED

### Local-Side (in `MyCities-Infrastructure/`):
- `.last_push_id` - Last Push ID used
- `.last_push_time` - Local timestamp
- `.last_infra_commit` - Infrastructure commit hash
- `.last_laravel_commit` - Laravel commit hash
- `.last_vue_commit` - Vue-Quasar commit hash

## Rollback

If something goes wrong, rollback to the save point:
```bash
cd c:\docker\MyCities-Infrastructure
git reset --hard savepoint-before-deploy-tracking
git push origin main --force
```

See `RESTORE_POINT.md` for detailed rollback instructions.

## Features

✅ **Visual Progress Tracking** - See percentage and step-by-step progress  
✅ **Multi-Repo Support** - Handles all 3 repositories automatically  
✅ **Database Sync** - Automatically syncs schemas on deployment  
✅ **Health Verification** - Checks containers and website after deployment  
✅ **Admin Integration** - View deployment status in admin panel  
✅ **Error Handling** - Clear error messages and troubleshooting tips  

## Troubleshooting

### Deployment Fails:
1. Check SSH connection to server
2. Verify GitHub credentials
3. Check server disk space
4. Review Docker container logs: `docker logs mycities-laravel`

### Admin Panel Shows "No deployment tracking":
- This is normal if `deploy.bat` hasn't been run yet
- Run `deploy.bat` once to create tracking files
- Ensure Laravel can read files from `/opt/mycities/` directory

### Status Shows "UNKNOWN":
- Check if `.deployment_status` file exists on server
- Verify file permissions allow Laravel to read it
- Check Laravel logs for file read errors
