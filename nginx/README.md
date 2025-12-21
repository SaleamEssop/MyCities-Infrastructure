# Nginx Configuration

## IMPORTANT - DO NOT OVERCOMPLICATE

The `docker-entrypoint.sh` script has been broken multiple times by overengineering.

### What the entrypoint does:
1. Checks if SSL certificates exist
2. If NO SSL: removes HTTPS block from nginx config (allows HTTP-only mode)
3. If SSL exists: keeps full config (HTTPS enabled)
4. Starts nginx

### What NOT to do:
- DO NOT create multiple config file copies (.full, .http, etc.)
- DO NOT try to backup/restore configs
- DO NOT add complex state management
- KEEP IT SIMPLE

### Testing:
1. Run `Build_Local.ps1` and select nginx rebuild
2. Test http://localhost works
3. Only then deploy with `deploy.ps1`

### If nginx fails to start:
```bash
docker logs mycities-nginx
```
Usually caused by:
- Missing SSL certs (should fallback to HTTP-only)
- Syntax error in entrypoint script
- Invalid nginx config