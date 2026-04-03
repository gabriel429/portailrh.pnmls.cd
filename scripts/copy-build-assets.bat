@echo off
REM Batch script to copy build\assets to public\build\assets
SET SRC=%~dp0..\build\assets
SET DEST=%~dp0..\public\build\assets
IF NOT EXIST "%DEST%" mkdir "%DEST%"
xcopy "%SRC%\*" "%DEST%\" /E /I /Y
echo Copy complete.
