# Tests Directory

This directory contains all test files and debugging scripts for the BOSTARTER platform.

## Test Files

### Authentication Tests
- `test_simple_auth.php` - Basic authentication functionality tests
- `test_full_auth_flow.php` - Complete authentication flow testing (registration + login)
- `test_register_direct.php` - Direct user registration testing
- `debug_login_api.php` - Debug script for login API troubleshooting

### API Tests
- `test_http_apis.php` - HTTP API endpoint testing
- `test_projects_api.php` - Project management API tests
- `test_projects_direct.php` - Direct project model testing

### Database Tests
- `test_stored_procedures.php` - Database stored procedures testing
- `test_project_model.php` - Project model and database integration tests

## Usage

These test files can be run individually via browser or command line to verify specific functionality:

```bash
# Run via PHP CLI
php test_simple_auth.php

# Or access via browser
http://localhost/BOSTARTER/tests/test_simple_auth.php
```

## Test Environment

- Requires XAMPP/WAMP server running
- Database connection configured in `backend/config/database.php`
- MongoDB connection for logging (optional)

## Notes

- All tests use the same database configuration as the main application
- Tests may create/modify test data - use with caution in production
- Debug files contain sensitive information and should not be deployed
