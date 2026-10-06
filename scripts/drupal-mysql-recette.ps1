param([string]$PhpPath = 'C:/Users/examg/AppData/Local/Programs/PHP/php.exe')
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path -Parent $PSScriptRoot
$taskRuntime = Join-Path $taskRoot '.local/mysql-recette'
$taskTools = Join-Path $taskRoot '.local/mysql-tools'
$taskIni = Join-Path $taskRoot '.local/drupal-tools/php.ini'
$taskMaria = Join-Path $taskTools 'mariadb-11.4.9-winx64/bin'
$taskUtf8 = [System.Text.UTF8Encoding]::new($false)
$taskPhp = (Resolve-Path -LiteralPath $PhpPath).Path
$taskOriginalPath = $env:PATH
$taskOriginalPhprc = $env:PHPRC
$taskShellDirectory = @((Join-Path (Split-Path -Parent (Split-Path -Parent (Get-Command git.exe).Source)) 'bin'), 'C:/Program Files/Git/bin') | Where-Object { Test-Path -LiteralPath (Join-Path $_ 'sh.exe') } | Select-Object -First 1
if (!$taskShellDirectory) { throw 'Git pour Windows avec sh.exe est requis par les sous-commandes Drush.' }
if (!(Test-Path -LiteralPath $taskIni) -or !(Test-Path -LiteralPath (Join-Path $taskRoot 'drupal/vendor/drush/drush/drush.php'))) { throw 'Préparer les dépendances Drupal locales avant cette recette.' }
if (Get-NetTCPConnection -State Listen -LocalPort 33080 -ErrorAction SilentlyContinue) { throw 'Port de recette déjà occupé : aucun serveur existant modifié.' }
New-Item -ItemType Directory -Path $taskTools -Force | Out-Null
$taskZip = Join-Path $taskTools 'mariadb-11.4.9-winx64.zip'
if (!(Test-Path -LiteralPath $taskZip)) {
  Invoke-WebRequest -Uri 'https://archive.mariadb.org/mariadb-11.4.9/winx64-packages/mariadb-11.4.9-winx64.zip' -OutFile $taskZip
}
if ((Get-FileHash -LiteralPath $taskZip -Algorithm SHA256).Hash.ToLowerInvariant() -ne '802f9f40a9dca774a3ba62f39c21093942954f178d6d7d458dc51453929bcdda') { throw 'Empreinte officielle MariaDB différente : aucune exécution.' }
if (!(Test-Path -LiteralPath (Join-Path $taskMaria 'mariadbd.exe'))) { Expand-Archive -LiteralPath $taskZip -DestinationPath $taskTools }
foreach ($taskDirectory in @($taskRuntime, "$taskRuntime/private", "$taskRuntime/temp", "$taskRuntime/config-sync")) { New-Item -ItemType Directory -Path $taskDirectory -Force | Out-Null }
$taskAccessPath = Join-Path $taskRuntime 'access.json'
$taskDbId = [Guid]::NewGuid().ToString('N').Substring(0,8)
$taskData = Join-Path $taskRuntime ('server-' + $taskDbId)
New-Item -ItemType Directory -Path $taskData | Out-Null
$taskSecretBytes = [System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32)
$taskPassword = [Convert]::ToHexString($taskSecretBytes).ToLowerInvariant()
$taskAccess = [ordered]@{scope='local-disposable-mysql';host='127.0.0.1';port=33080;database=('tcl_recette_' + $taskDbId);restoredDatabase=('tcl_recette_' + $taskDbId + '_restored');password=$taskPassword;accountPassword=[Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32));salt=[Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32))}
[IO.File]::WriteAllText($taskAccessPath, ($taskAccess | ConvertTo-Json), $taskUtf8)
$taskBootstrap = & (Join-Path $taskMaria 'mariadb-install-db.exe') --datadir=$taskData --port=33080 --password=$taskPassword --silent 2>&1
if ($LASTEXITCODE -ne 0) { throw 'Initialisation MariaDB interrompue ; aucune base existante réutilisée.' }
$taskConfigPath = Join-Path $taskData 'local.ini'
[IO.File]::WriteAllText($taskConfigPath, ("[mysqld]`nbasedir=" + (Split-Path -Parent $taskMaria).Replace('\','/') + "`ndatadir=" + $taskData.Replace('\','/') + "`nport=33080`nbind-address=127.0.0.1`ncharacter-set-server=utf8mb4`ncollation-server=utf8mb4_unicode_ci`n"), $taskUtf8)
$taskClientConfig = Join-Path $taskData 'client.ini'
[IO.File]::WriteAllText($taskClientConfig, ("[client]`nuser=root`npassword=$taskPassword`nhost=127.0.0.1`nport=33080`nprotocol=tcp`n"), $taskUtf8)
$taskServer = Start-Process -FilePath (Join-Path $taskMaria 'mariadbd.exe') -ArgumentList @('--defaults-file="' + $taskConfigPath + '"','--console') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $taskData 'server-output.log') -RedirectStandardError (Join-Path $taskData 'server-error.log')
function Invoke-RecettePhp([string[]]$Arguments) {
  $taskOutput = & $taskPhp -c $taskIni @Arguments 2>&1
  if ($LASTEXITCODE -ne 0) {
    $taskSafe = ($taskOutput | Out-String).Replace($taskPassword, '[secret retiré]').Replace($taskAccess.accountPassword, '[secret retiré]') -replace 'http://127\.0\.0\.1:4183/user/reset/\S+', '[lien privé retiré]'
    [IO.File]::WriteAllText((Join-Path $taskRuntime 'test-error.log'), $taskSafe, $taskUtf8)
    throw 'Recette PHP interrompue ; consulter .local/mysql-recette/test-error.log.'
  }
  return ($taskOutput | Out-String)
}
try {
  Push-Location (Join-Path $taskRoot 'drupal')
  $env:PATH = (Split-Path -Parent $taskPhp) + ';' + $taskShellDirectory + ';' + $env:PATH
  $env:PHPRC = $taskIni
  $taskReady = $false
  for ($taskTry=0; $taskTry -lt 40; $taskTry++) {
    & (Join-Path $taskMaria 'mariadb-admin.exe') --defaults-extra-file=$taskClientConfig ping --silent 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) { $taskReady=$true; break }
    if ($taskServer.HasExited) { throw 'Le serveur de recette a quitté avant sa disponibilité.' }
    Start-Sleep -Milliseconds 250
  }
  if (!$taskReady) { throw 'Serveur de recette non disponible.' }
  $taskGenerated = Join-Path $taskRuntime 'database-fixture.php'
  $taskGeneratedPhp = @'
<?php
$a = json_decode(file_get_contents(__DIR__ . '/access.json'), true, 512, JSON_THROW_ON_ERROR);
if ($a['scope'] !== 'local-disposable-mysql' || $a['host'] !== '127.0.0.1' || $a['port'] !== 33080 || !preg_match('/^tcl_recette_[a-f0-9]{8}$/D', $a['database'])) { throw new RuntimeException('Fixture refusée.'); }
$pdo = new PDO('mysql:host=127.0.0.1;port=33080;charset=utf8mb4', 'root', $a['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (($argv[1] ?? '') === 'create') {
  foreach ([$a['database'], $a['database'] . '_restored'] as $name) { $pdo->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); }
} else {
  $tables = $pdo->query('SHOW TABLES FROM `' . $a['database'] . '`')->fetchAll(PDO::FETCH_COLUMN);
  $restoredTables = $pdo->query('SHOW TABLES FROM `' . $a['database'] . '_restored`')->fetchAll(PDO::FETCH_COLUMN);
  sort($tables); sort($restoredTables);
  if ($tables !== $restoredTables) { throw new RuntimeException('Liste des tables restaurées différente.'); }
  foreach ($tables as $table) {
    $schema = $pdo->query('SHOW CREATE TABLE `' . $a['database'] . '`.`' . $table . '`')->fetch(PDO::FETCH_NUM)[1];
    $restoredSchema = $pdo->query('SHOW CREATE TABLE `' . $a['database'] . '_restored`.`' . $table . '`')->fetch(PDO::FETCH_NUM)[1];
    if ($schema !== $restoredSchema) { throw new RuntimeException('Schéma restauré différent : ' . $table); }
    $original = $pdo->query('SELECT * FROM `' . $a['database'] . '`.`' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    $restored = $pdo->query('SELECT * FROM `' . $a['database'] . '_restored`.`' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    $normal = static function(array $rows): array { $hashes = array_map(static fn($row) => hash('sha256', serialize($row)), $rows); sort($hashes); return $hashes; };
    if ($normal($original) !== $normal($restored)) { throw new RuntimeException('Restauration différente : ' . $table); }
  }
  echo json_encode(['status'=>'passed','tablesCompared'=>count($tables),'comparison'=>'exact table list and schemas, every row including binary values','scope'=>'local-fictitious-only']);
}
'@
  [IO.File]::WriteAllText($taskGenerated, $taskGeneratedPhp, $taskUtf8)
  Invoke-RecettePhp @($taskGenerated, 'create') | Out-Null
  $taskSites = Join-Path $taskRoot 'drupal/web/sites/sites.php'
  $taskSitesSource = '<?php // TCL disposable local multisite only.' + "`n" + '$sites["4183.127.0.0.1"] = "tcl-mysql-recette";' + "`n"
  if ((Test-Path -LiteralPath $taskSites) -and [IO.File]::ReadAllText($taskSites) -ne $taskSitesSource) { throw 'Mapping multisite existant différent : aucune réécriture.' }
  [IO.File]::WriteAllText($taskSites, $taskSitesSource, $taskUtf8)
  $taskSite = Join-Path $taskRoot 'drupal/web/sites/tcl-mysql-recette'
  New-Item -ItemType Directory -Path $taskSite -Force | Out-Null
  [IO.File]::WriteAllText((Join-Path $taskSite 'settings.php'), "<?php`nrequire dirname(__DIR__, 3) . '/config/settings.mysql-recette.php';`n", $taskUtf8)
  $taskDrush = Join-Path $taskRoot 'drupal/vendor/drush/drush/drush.php'
  $taskDrupalRoot = '--root=' + (Join-Path $taskRoot 'drupal/web')
  $taskUri = '--uri=http://127.0.0.1:4183'
  Invoke-RecettePhp @($taskDrush, $taskDrupalRoot, $taskUri, 'site:install', 'minimal', '--sites-subdir=tcl-mysql-recette', '--yes', '--account-name=mysql-recette-admin', ('--account-pass=' + $taskAccess.accountPassword), '--account-mail=admin@example.invalid', '--site-mail=admin@example.invalid', '--site-name=TC fixture MySQL fictive', 'install_configure_form.enable_update_status_module=NULL', 'install_configure_form.enable_update_status_emails=NULL') | Out-Null
  $taskTest = Join-Path $taskRoot 'tests/drupal-mysql-update.php'
  $taskSeed = Invoke-RecettePhp @($taskTest, 'seed') | ConvertFrom-Json
  Invoke-RecettePhp @($taskDrush, $taskDrupalRoot, $taskUri, 'updatedb', '--yes') | Out-Null
  $taskVerify = Invoke-RecettePhp @($taskTest, 'verify') | ConvertFrom-Json
  $taskDump = Join-Path $taskData 'fixture-backup.sql'
  & (Join-Path $taskMaria 'mariadb-dump.exe') --defaults-extra-file=$taskClientConfig --single-transaction --hex-blob --result-file=$taskDump $taskAccess.database
  if ($LASTEXITCODE -ne 0) { throw 'Sauvegarde SQL de recette interrompue.' }
  # Stream binary-safe input directly, without cmd /c or a shell-built pipeline.
  $taskImportInfo = [Diagnostics.ProcessStartInfo]::new((Join-Path $taskMaria 'mariadb.exe'))
  $taskImportInfo.UseShellExecute=$false; $taskImportInfo.CreateNoWindow=$true; $taskImportInfo.RedirectStandardInput=$true; $taskImportInfo.RedirectStandardError=$true
  $taskImportInfo.ArgumentList.Add('--defaults-extra-file=' + $taskClientConfig)
  $taskImportInfo.ArgumentList.Add($taskAccess.restoredDatabase)
  $taskImport = [Diagnostics.Process]::Start($taskImportInfo)
  $taskDumpStream = [IO.File]::OpenRead($taskDump)
  try { $taskDumpStream.CopyTo($taskImport.StandardInput.BaseStream) } finally { $taskDumpStream.Dispose(); $taskImport.StandardInput.Close() }
  $taskImportError=$taskImport.StandardError.ReadToEnd(); $taskImport.WaitForExit()
  if ($taskImport.ExitCode -ne 0) { throw 'Restauration de la sauvegarde locale interrompue.' }
  $taskRestore = Invoke-RecettePhp @($taskGenerated, 'compare') | ConvertFrom-Json
  $taskReport = [ordered]@{checkedAt=(Get-Date).ToUniversalTime().ToString('o');status='passed';scope='isolated-local-fictitious-mariadb';hostingVerified=$false;deployed=$false;server=$taskVerify.server;drupal=$taskVerify.drupal;php=$taskVerify.php;checks=@($taskSeed.checks)+@($taskVerify.checks);restoration=$taskRestore;archiveSha256='802f9f40a9dca774a3ba62f39c21093942954f178d6d7d458dc51453929bcdda';backupSha256=(Get-FileHash -LiteralPath $taskDump -Algorithm SHA256).Hash.ToLowerInvariant()}
  [IO.File]::WriteAllText((Join-Path $taskRuntime 'verification.json'), ($taskReport | ConvertTo-Json -Depth 7), $taskUtf8)
  [ordered]@{status='passed';checks=$taskReport.checks.Count;restoredTables=$taskRestore.tablesCompared;server=$taskVerify.server;scope=$taskReport.scope;hostingVerified=$false} | ConvertTo-Json
} finally {
  Pop-Location
  $env:PATH = $taskOriginalPath
  $env:PHPRC = $taskOriginalPhprc
  $taskOwned = Get-CimInstance Win32_Process -Filter "ProcessId = $($taskServer.Id)" -ErrorAction SilentlyContinue
  if ($taskOwned -and $taskOwned.Name -eq 'mariadbd.exe' -and $taskOwned.CommandLine.Contains($taskConfigPath)) {
    & (Join-Path $taskMaria 'mariadb-admin.exe') --defaults-extra-file=$taskClientConfig shutdown 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) { Stop-Process -Id $taskServer.Id }
  }
  Remove-Variable taskPassword,taskSecretBytes,taskAccess -ErrorAction SilentlyContinue
}
