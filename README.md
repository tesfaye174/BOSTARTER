# BOSTARTER - Piattaforma di Crowdfunding

Piattaforma per crowdfunding di progetti hardware e software.

## Installazione

### Prerequisiti

- XAMPP con MySQL
- PHP 7.4+

### Setup

```bash
# Database
cd database
mysql -u root -p < schema_completo.sql
mysql -u root -p < stored.sql
mysql -u root -p < dati.sql
# Avvia XAMPP e vai su localhost/BOSTARTER/frontend/
```

## Account di Test

| Tipo | Email | Password |
|------|--------|----------|
| Admin | <admin@bostarter.com> | password |
| Creatore | <mario.rossi@email.com> | password |
| Standard | <giulia.bianchi@email.com> | password |
