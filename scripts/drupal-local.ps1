param(
  [ValidateSet('prepare', 'install', 'start', 'stop', 'status')]
  [string]$Action = 'status',
  [ValidateRange(1024, 65535)]
  [int]$Port = 4182
)
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path -Parent $PSScriptRoot
$taskDrupal = Join-Path $taskRoot 'drupal'
$taskTools = Join-Path $taskRoot '.local\drupal-tools'
$taskRuntime = Join-Path $taskRoot '.local\drupal-runtime'
$taskAdminFile = Join-Path $taskRoot '.local\drupal-admin.json'
$taskServerFile = Join-Path $taskRoot '.local\drupal-server.json'
$taskPhp = (Get-Command php.exe -ErrorAction Stop).Source
$taskIni = Join-Path $taskTools 'php.ini'
$taskUtf8 = [System.Text.UTF8Encoding]::new($false)
$taskOrigin = "http://127.0.0.1:$Port"

function Invoke-LocalPhp([string[]]$PhpArguments) {
  & $taskPhp -c $taskIni @PhpArguments
  if ($LASTEXITCODE -ne 0) { throw "PHP local a échoué (code $LASTEXITCODE)." }
}

if ($Action -eq 'prepare') {
  foreach ($taskPath in @($taskTools, $taskRuntime, (Join-Path $taskRuntime 'private-files'), (Join-Path $taskRuntime 'temp'), (Join-Path $taskRuntime 'config-sync'), (Join-Path $taskDrupal 'site-pages'))) {
    New-Item -ItemType Directory -Path $taskPath -Force | Out-Null
  }
  $taskExtensions = (Join-Path (Split-Path $taskPhp) 'ext').Replace('\', '/')
  $taskIniText = @"
extension_dir = "$taskExtensions"
extension = php_openssl.dll
extension = php_curl.dll
extension = php_mbstring.dll
extension = php_gd.dll
extension = php_zip.dll
extension = php_fileinfo.dll
extension = php_sodium.dll
extension = php_pdo_sqlite.dll
extension = php_sqlite3.dll
extension = php_pdo_mysql.dll
extension = php_intl.dll
memory_limit = 512M
max_execution_time = 120
upload_max_filesize = 8M
post_max_size = 12M
date.timezone = Europe/Paris
display_errors = Off
log_errors = On
zend.assertions = -1
disable_functions = mail
"@
  [System.IO.File]::WriteAllText($taskIni, $taskIniText, $taskUtf8)
  $taskComposer = Join-Path $taskTools 'composer.phar'
  if (!(Test-Path -LiteralPath $taskComposer)) {
    Invoke-WebRequest -Uri 'https://getcomposer.org/download/2.10.3/composer.phar' -OutFile $taskComposer
  }
  if ((Get-FileHash -LiteralPath $taskComposer -Algorithm SHA256).Hash.ToLowerInvariant() -ne '7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6') {
    throw 'Empreinte de Composer inattendue : aucune exécution.'
  }
  if (!(Test-Path -LiteralPath $taskAdminFile)) {
    $taskBytes = New-Object byte[] 36
    $taskRng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $taskRng.GetBytes($taskBytes)
    $taskPassword = [Convert]::ToBase64String($taskBytes)
    $taskRng.GetBytes($taskBytes)
    $taskSalt = [Convert]::ToBase64String($taskBytes)
    $taskRng.Dispose()
    [System.IO.File]::WriteAllText($taskAdminFile, (@{ username = 'tcl-local-admin'; password = $taskPassword; hashSalt = $taskSalt; localOnly = $true } | ConvertTo-Json), $taskUtf8)
    Remove-Variable taskPassword,taskSalt,taskBytes
  }
  foreach ($taskPage in @('index', 'competitions', 'calendrier', 'disponibilites', 'equipes', 'espace', 'contact')) {
    Copy-Item -LiteralPath (Join-Path $taskRoot "officiel\$taskPage.html") -Destination (Join-Path $taskDrupal "site-pages\$taskPage.html")
  }
  $taskSettingsPath = Join-Path $taskDrupal 'web\sites\default\settings.php'
  New-Item -ItemType Directory -Path (Split-Path -Parent $taskSettingsPath) -Force | Out-Null
  if (!(Test-Path -LiteralPath $taskSettingsPath)) {
    [System.IO.File]::WriteAllText($taskSettingsPath, "<?php`nrequire dirname(__DIR__, 3) . '/config/settings.local.php';`n", $taskUtf8)
  }
  Write-Output 'Préparation locale terminée. Les identifiants restent dans .local/drupal-admin.json.'
  exit
}

if ($Action -eq 'install') {
  if (!(Test-Path -LiteralPath $taskAdminFile) -or !(Test-Path -LiteralPath $taskIni)) { throw 'Exécuter prepare avant install.' }
  if (Test-Path -LiteralPath (Join-Path $taskRuntime 'site.sqlite')) { throw 'Une base locale existe déjà ; aucune réinstallation destructive automatique.' }
  $taskCredentials = Get-Content -LiteralPath $taskAdminFile -Raw | ConvertFrom-Json
  $env:PHPRC = $taskIni
  Push-Location $taskDrupal
  try {
    $env:COMPOSER_HOME = Join-Path $taskTools 'composer-home'
    $env:COMPOSER_CACHE_DIR = Join-Path $taskTools 'composer-cache'
    Invoke-LocalPhp @((Join-Path $taskTools 'composer.phar'), 'install', '--no-interaction', '--prefer-dist', '--no-progress')
    # Keep Drush's one-time login URL and account options out of the console.
    $taskInstallLog = & $taskPhp -c $taskIni 'vendor\drush\drush\drush.php' site:install minimal --yes --uri=$taskOrigin --account-name=$($taskCredentials.username) --account-pass=$($taskCredentials.password) --account-mail=local-admin@example.invalid --site-mail=local-admin@example.invalid '--site-name=TC Longages — démonstration locale' install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL 2>&1
    $taskInstallCode = $LASTEXITCODE
    if ($taskInstallCode -ne 0) {
      $taskSanitized = ($taskInstallLog | Out-String).Replace($taskCredentials.password, '[secret retiré]') -replace 'http://127\.0\.0\.1:\d+/user/reset/\S+', '[lien de connexion retiré]'
      [System.IO.File]::WriteAllText((Join-Path $taskRuntime 'install-error.log'), $taskSanitized, $taskUtf8)
      throw "Installation interrompue (code $taskInstallCode) ; diagnostic local dans .local/drupal-runtime/install-error.log."
    }
    Invoke-LocalPhp @('bin\configure-local.php')
  } finally {
    Remove-Variable taskCredentials,taskInstallLog -ErrorAction SilentlyContinue
    Pop-Location
  }
  exit
}

if ($Action -eq 'start') {
  if (!(Test-Path -LiteralPath (Join-Path $taskRuntime 'site.sqlite'))) { throw 'Installation locale absente.' }
  if (Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue) { throw "Le port $Port est déjà occupé ; aucun processus existant arrêté." }
  $taskArguments = @('-c', ('"' + $taskIni + '"'), '-S', "127.0.0.1:$Port", '-t', ('"' + (Join-Path $taskDrupal 'web') + '"'), ('"' + (Join-Path $taskDrupal 'local-router.php') + '"'))
  $taskProcess = Start-Process -FilePath $taskPhp -ArgumentList $taskArguments -WorkingDirectory $taskDrupal -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $taskRuntime 'server-output.log') -RedirectStandardError (Join-Path $taskRuntime 'server-error.log')
  [System.IO.File]::WriteAllText($taskServerFile, (@{ pid = $taskProcess.Id; origin = $taskOrigin; router = (Join-Path $taskDrupal 'local-router.php'); startedAt = (Get-Date).ToUniversalTime().ToString('o') } | ConvertTo-Json), $taskUtf8)
  Write-Output "Drupal démarre uniquement sur $taskOrigin. Connexion : /user/login ; maintenance : /admin/config/development/maintenance."
  exit
}

if ($Action -eq 'stop') {
  if (!(Test-Path -LiteralPath $taskServerFile)) { Write-Output 'Aucun serveur local enregistré.'; exit }
  $taskServer = Get-Content -LiteralPath $taskServerFile -Raw | ConvertFrom-Json
  $taskProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $($taskServer.pid)" -ErrorAction SilentlyContinue
  $taskRecordedAuthority = ([uri]$taskServer.origin).Authority
  if ($taskProcess -and $taskProcess.Name -eq 'php.exe' -and $taskProcess.CommandLine.Contains($taskServer.router) -and $taskProcess.CommandLine.Contains($taskRecordedAuthority)) {
    Stop-Process -Id $taskServer.pid
    Write-Output 'Serveur Drupal local arrêté.'
  } elseif ($taskProcess) { throw 'Identité du processus différente : aucun arrêt exécuté.' }
  else { Write-Output 'Le serveur Drupal local est déjà arrêté.' }
  exit
}

$taskListener = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue
[ordered]@{ localDatabasePresent = (Test-Path -LiteralPath (Join-Path $taskRuntime 'site.sqlite')); listener = @($taskListener | Select-Object LocalAddress, LocalPort, OwningProcess); origin = $taskOrigin; externalDeployment = $false } | ConvertTo-Json -Depth 4
