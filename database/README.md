# Database Directory

This directory contains all database-related files for the BOSTARTER platform.

## Schema Files

### Main Schema
- `bostarter_schema.sql` - Main database schema (Italian table names)
- `bostarter_schema_english.sql` - Alternative schema with English table names
- `bostarter_schema_fixed.sql` - Updated schema with fixes and improvements

### Extensions and Enhancements
- `bostarter_extensions.sql` - Additional database extensions
- `notifications_enhancement.sql` - Notification system enhancements
- `frontend_fixes.sql` - Frontend-related database fixes
- `views_progetti.sql` - Database views for project management

### Setup Scripts
- `complete_setup.sql` - Complete database setup in one file
- `quick_setup.sql` - Quick setup for development
- `setup_database.php` - PHP script for database initialization

## Table Creation Scripts

- `add_missing_tables.sql` - SQL script to add missing tables
- `candidature_table.sql` - Candidature (applications) table creation
- `create_notifications_table.sql` - Notifications table setup

## PHP Setup Scripts

- `add_missing_tables.php` - PHP script to add missing tables
- `add_ultimo_accesso.php` - Add last access column to users table
- `create_stored_procedures.php` - Create authentication stored procedures
- `fix_utenti_table.php` - Fix users table structure
- `check_table_structure.php` - Utility to inspect table structure

## Stored Procedures

- `stored_procedures.php` - Authentication and user management procedures

## Usage

### Initial Setup
1. Run `complete_setup.sql` for full database setup
2. Or use `setup_database.php` for PHP-based setup

### Development Setup
1. Use `quick_setup.sql` for minimal setup
2. Add additional tables with `add_missing_tables.sql`

### Incremental Updates
- Run individual PHP scripts for specific updates
- Apply SQL files for schema changes

## Database Configuration

Configure database connection in:
- `../backend/config/database.php`
- `../backend/config/db_config.php`

## Notes

- Main schema uses Italian table names (progetti, utenti, finanziamenti)
- Stored procedures handle authentication and user management
- All scripts are designed for MySQL/MariaDB
