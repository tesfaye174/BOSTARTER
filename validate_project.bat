@echo off
echo === BOSTARTER - Validazione Finale ===
echo.

echo Controllo file essenziali...
if exist "c:\xampp\htdocs\BOSTARTER\backend\config\database.php" (
    echo ✅ Database config
) else (
    echo ❌ Database config mancante
)

if exist "c:\xampp\htdocs\BOSTARTER\frontend\home.php" (
    echo ✅ Homepage
) else (
    echo ❌ Homepage mancante
)

if exist "c:\xampp\htdocs\BOSTARTER\database\schema_completo.sql" (
    echo ✅ Schema database
) else (
    echo ❌ Schema database mancante
)

echo.
echo Struttura finale del progetto:
echo BOSTARTER/
echo ├── backend/ (API e modelli)
echo ├── frontend/ (Interfaccia utente)
echo ├── database/ (Schema e dati)
echo └── README.md (Documentazione)
echo.
echo === Progetto pulito e ottimizzato! ===
pause
