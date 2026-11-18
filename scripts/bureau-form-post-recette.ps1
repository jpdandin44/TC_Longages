param([switch]$Basic)
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path -Parent $PSScriptRoot
$taskProcesses = @()
Push-Location $taskRoot
try {
  foreach ($taskPort in @(4186,4190)) {
    if (Get-NetTCPConnection -LocalPort $taskPort -State Listen -ErrorAction SilentlyContinue) { throw "Port $taskPort occupé : aucun processus existant arrêté." }
  }
  if (!(Test-Path .local/drupal-runtime/site.sqlite) -or !(Test-Path .local/bureau-oauth-fixtures.json)) { throw 'Préparer une installation locale neuve et ses identités fictives selon docs/diagnostic-formulaires-locaux.md.' }
  Copy-Item tests/fixtures/tcl_post_fixture drupal/web/modules/custom/ -Recurse -Force
  & php -c .local/drupal-tools/php.ini tests/prepare-post-fixture.php
  if ($LASTEXITCODE -ne 0) { throw 'Préparation isolée refusée.' }
  $taskProcesses += Start-Process (Get-Command node.exe).Source -ArgumentList 'tests/oauth-fixture-server.mjs' -WorkingDirectory $taskRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput .local/post-oauth-output.log -RedirectStandardError .local/post-oauth-error.log
  $taskRouter = if ($Basic) { 'tests/post-fixture-router.php' } else { 'drupal/local-router.php' }
  $taskArgs = @('-c', ('"'+$taskRoot+'/.local/drupal-tools/php.ini"'), '-S', '127.0.0.1:4186', '-t', ('"'+$taskRoot+'/drupal/web"'), ('"'+$taskRoot+'/'+$taskRouter+'"'))
  $taskProcesses += Start-Process (Get-Command php.exe).Source -ArgumentList $taskArgs -WorkingDirectory $taskRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput .local/post-server-output.log -RedirectStandardError .local/post-server-error.log
  $taskReady = $false
  for ($taskAttempt=0; $taskAttempt -lt 40; $taskAttempt++) {
    if ((Get-NetTCPConnection -State Listen -LocalPort 4186 -ErrorAction SilentlyContinue) -and (Get-NetTCPConnection -State Listen -LocalPort 4190 -ErrorAction SilentlyContinue)) { $taskReady=$true; break }
    Start-Sleep -Milliseconds 100
  }
  if (!$taskReady) { throw 'Serveurs de recette non disponibles.' }
  $taskTestArgs = @('-X','utf8','tests/bureau-form-post-http.py')
  if ($Basic) { $taskTestArgs += '--basic' }
  & python @taskTestArgs
  if ($LASTEXITCODE -ne 0) { throw 'Recette des formulaires échouée.' }
} finally {
  foreach ($taskProcess in $taskProcesses) { if (!$taskProcess.HasExited) { $taskProcess.Kill(); $taskProcess.WaitForExit() } }
  Pop-Location
}
