# Legacy Directory

This directory contains older API files that have been replaced by the newer, more organized API structure in `backend/api/`.

## Files

### user.php
- **Status**: Unused, no references found in codebase
- **Replacement**: Use `backend/api/users.php` and related user management APIs
- **Description**: Old user management API endpoint
- **Safe to remove**: Yes, after final verification

## Migration Notes

The modern API structure uses:
- Organized endpoints in `backend/api/`
- Consistent response formats with `ApiResponse` utility
- Proper input validation with `FluentValidator`
- Better error handling and logging
- RESTful design patterns

## Cleanup Recommendations

1. **auth_api.php** (still in backend root):
   - Currently used by frontend JavaScript files
   - Should be migrated to use `backend/api/login.php` and `backend/api/register.php`
   - Update frontend references before moving to legacy

2. **user.php** (moved to legacy):
   - Can be safely removed after final verification
   - No active references found in codebase

## Migration Process

When migrating from legacy APIs:
1. Update frontend JavaScript to use new API endpoints
2. Test thoroughly to ensure compatibility
3. Update any documentation or configuration files
4. Move legacy files to this directory
5. Add deprecation notices to files still in use
