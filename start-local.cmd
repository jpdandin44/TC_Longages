@echo off
cd /d "%~dp0"
where node >nul 2>nul
if errorlevel 1 (
  echo Node.js 22 ou plus recent est requis pour ouvrir le prototype.
  pause
  exit /b 1
)
node scripts/build.mjs
if errorlevel 1 (
  pause
  exit /b 1
)
echo Ouvrez http://127.0.0.1:4173 dans votre navigateur.
echo Ce serveur reste sur votre ordinateur. Ctrl+C pour arreter.
node scripts/preview.mjs
pause
