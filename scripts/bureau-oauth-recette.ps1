param([switch]$Prepare)
$ErrorActionPreference = 'Stop'
$taskRoot = Split-Path -Parent $PSScriptRoot
Push-Location $taskRoot
$taskProvider = $null
$taskTcProvider = $null
try {
  if ($Prepare) {
    & node scripts/build-officiel.mjs
    if ($LASTEXITCODE -ne 0) { throw 'Construction V1 échouée.' }
    & node scripts/build-officiel.mjs --calendar-preview
    if ($LASTEXITCODE -ne 0) { throw 'Construction agenda Mois échouée.' }
    & ./scripts/drupal-local.ps1 -Action prepare -Port 4186
    if ($LASTEXITCODE -ne 0) { throw 'Préparation Drupal échouée.' }
    & node scripts/build-drupal-public-pages.mjs --calendar-preproduction
    if ($LASTEXITCODE -ne 0) { throw 'Construction pages Drupal échouée.' }
    Copy-Item -Path .local/drupal-public-candidate/site-pages/*.html -Destination drupal/site-pages
    & ./scripts/drupal-local.ps1 -Action install -Port 4186
    if ($LASTEXITCODE -ne 0) { throw 'Installation Drupal échouée.' }
    & php -c .local/drupal-tools/php.ini drupal/bin/prepare-bureau-oauth-local.php
    if ($LASTEXITCODE -ne 0) { throw 'Préparation des identités fictives échouée.' }
  }
  if (Get-NetTCPConnection -LocalPort 4190 -State Listen -ErrorAction SilentlyContinue) { throw 'Port du fournisseur fictif occupé ; aucun processus existant arrêté.' }
  & php -c .local/drupal-tools/php.ini tests/bureau-oauth-token.php
  if ($LASTEXITCODE -ne 0) { throw 'Contrôles de jetons échoués.' }
  $taskProvider = Start-Process -FilePath (Get-Command node.exe).Source -ArgumentList 'tests/oauth-fixture-server.mjs' -WorkingDirectory $taskRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput .local/oauth-fixture-output.log -RedirectStandardError .local/oauth-fixture-error.log
  & ./scripts/drupal-local.ps1 -Action start -Port 4186
  if ($LASTEXITCODE -ne 0) { throw 'Démarrage Drupal échoué.' }
  & python -X utf8 tests/drupal-bureau-oauth-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette HTTP OAuth échouée.' }
  & python -X utf8 tests/flash-news-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette HTTP du bandeau échouée.' }
  & python -X utf8 tests/communication-pdf-channels-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette PDF et canaux échouée.' }
  & php -c .local/drupal-tools/php.ini tests/flash-news-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Intégration Drupal du bandeau échouée.' }
  & node scripts/build-season-seed.mjs --check
  if ($LASTEXITCODE -ne 0) { throw 'Catalogue initial incohérent.' }
  & python -X utf8 tests/registration-recipe-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette HTTP inscriptions/catalogue échouée.' }
  & php -c .local/drupal-tools/php.ini tests/recipe-mail-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Recipe mail checks failed.' }
  & php -c .local/drupal-tools/php.ini tests/recipe-password-mail.php
  if ($LASTEXITCODE -ne 0) { throw 'Contrôles de courriels de recette échoués.' }
  & php -c .local/drupal-tools/php.ini tests/season-catalogue-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Catalogue failed.' }
  & php -c .local/drupal-tools/php.ini tests/agenda-presentation-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Agenda presentation failed.' }
  & php -c .local/drupal-tools/php.ini tests/event-schedule-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Récurrences invalides.' }
  & python -X utf8 tests/events-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette événements échouée.' }
  & python -X utf8 tests/event-participation-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette des participations échouée.' }
  & python -X utf8 tests/event-signup-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette HTTP inscription événement échouée.' }
  & php -c .local/drupal-tools/php.ini tests/event-signup-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Recette inscription événement échouée.' }
  & php -c .local/drupal-tools/php.ini tests/member-mail-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Recette courriel adhérents échouée.' }
  & python -X utf8 tests/member-mail-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Parcours courriel adhérents échoué.' }
  & node --test tests/agenda-sharing.test.mjs
  if ($LASTEXITCODE -ne 0) { throw 'Contrôles catalogue échoués.' }
  & php -c .local/drupal-tools/php.ini drupal/bin/prepare-tc-identity-local.php
  if ($LASTEXITCODE -ne 0) { throw 'Préparation du fournisseur TC échouée.' }
  if (Get-NetTCPConnection -LocalPort 4191 -State Listen -ErrorAction SilentlyContinue) { throw 'Port du fournisseur TC occupé ; aucun processus existant arrêté.' }
  $taskPhpArguments = @('-c', ('"' + (Resolve-Path '.local/drupal-tools/php.ini').Path + '"'), '-S', '127.0.0.1:4191', '-t', ('"' + (Resolve-Path 'drupal/web').Path + '"'), ('"' + (Resolve-Path 'drupal/local-router.php').Path + '"'))
  $taskTcProvider = Start-Process -FilePath (Get-Command php.exe).Source -ArgumentList $taskPhpArguments -WorkingDirectory (Resolve-Path 'drupal').Path -WindowStyle Hidden -PassThru -RedirectStandardOutput .local/tc-provider-output.log -RedirectStandardError .local/tc-provider-error.log
  & python -X utf8 tests/drupal-tc-identity-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette du fournisseur TC dédié échouée.' }
  & python -X utf8 tests/invitation-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette invitations HTTP échouée.' }
  & python -X utf8 tests/drupal-password-setup-http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette de première saisie du mot de passe échouée.' }
  $env:TCL_HTTP_RECIPE_ORIGIN = 'http://127.0.0.1:4186'
  & python -X utf8 tests/test_contact_http.py
  if ($LASTEXITCODE -ne 0) { throw 'Recette de conservation du contact échouée.' }
  & python -X utf8 tests/test_editor_http.py
  if ($LASTEXITCODE -ne 0) { throw "Recette de conservation de l’édition Drupal échouée." }
} finally {
  if ($taskTcProvider -and !$taskTcProvider.HasExited) { Stop-Process -Id $taskTcProvider.Id }
  if ($taskProvider -and !$taskProvider.HasExited) { Stop-Process -Id $taskProvider.Id }
  & ./scripts/drupal-local.ps1 -Action stop -Port 4186
  Pop-Location
}
