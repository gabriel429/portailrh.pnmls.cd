@echo off
REM Batch script to mirror public\build into build for Hostinger deployments
SET SRC=%~dp0..\public\build
SET DEST=%~dp0..\build

IF NOT EXIST "%SRC%" (
	echo Source build directory not found: %SRC%
	exit /b 1
)

IF EXIST "%DEST%" rmdir /S /Q "%DEST%"
mkdir "%DEST%"
xcopy "%SRC%\*" "%DEST%\" /E /I /Y >nul
echo Mirror complete.
