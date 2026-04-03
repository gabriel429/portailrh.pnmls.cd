@echo off
REM Remove obsolete files
if exist public\build-redirect.htaccess (
    del public\build-redirect.htaccess
    echo Removed: public\build-redirect.htaccess
) else (
    echo File not found: public\build-redirect.htaccess
)
echo Done
