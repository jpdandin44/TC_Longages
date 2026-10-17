"""Manual production orchestration. Disabled until the hosted adapter is qualified."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys
from urllib.request import Request, urlopen

from delivery_shared import require, validate_revision, setup_ssh, cleanup_ssh, ssh_options

ROOT = Path(__file__).resolve().parent.parent
REPOSITORY = 'jpdandin44/TC_Longages'
TARGET = 'https://tclongages.fr'
REMOTE = '/home2/daje5127/tcl-production/private/github-deployment/production_operator.py'


def identifier(value):
    require(isinstance(value, str) and re.fullmatch(r'[a-z0-9][a-z0-9-]{0,63}', value),
            'Invalid release or backup identifier.')
    return value


def sha256(value):
    require(isinstance(value, str) and re.fullmatch(r'[a-f0-9]{64}', value), 'SHA-256 required.')
    return value


def validate(env, config, root=ROOT):
    revision = validate_revision(env)
    require(env.get('GITHUB_EVENT_NAME') == 'workflow_dispatch'
            and env.get('GITHUB_REPOSITORY') == REPOSITORY, 'Manual TCL execution required.')
    require(config.get('format') == 'tcl-production-action-v1'
            and config.get('repository') == REPOSITORY and config.get('target') == TARGET,
            'Wrong production configuration.')
    operation = env.get('OPERATION')
    require(operation in ('plan', 'deploy', 'rollback'), 'Unknown operation.')
    plan = {'format': 'tcl-production-request-v1', 'target': TARGET, 'operation': operation,
            'workflowSha': revision, 'ready': False}
    if operation == 'plan':
        return plan
    require(config.get('enabled') is True, 'Production disabled: hosted qualification and review required.')
    require(env.get('CONFIRMATION') == 'PRODUCTION TC Longages', 'Explicit confirmation required.')
    for field in ('operatorSha256', 'adapterSha256'):
        plan[field] = sha256(config.get(field))
    plan['catalogSha256'] = hashlib.sha256(json.dumps(config, sort_keys=True, separators=(',', ':')).encode()).hexdigest()
    require(isinstance(config.get('qualificationRef'), str)
            and config['qualificationRef'].startswith('https://github.com/' + REPOSITORY + '/'),
            'Hosted qualification reference required.')
    environment_review = config.get('environmentReview') or {}
    require(environment_review.get('adminBypassDisabled') is True
            and isinstance(environment_review.get('evidenceRef'), str)
            and environment_review['evidenceRef'].startswith('https://github.com/' + REPOSITORY + '/'),
            'Manual review of the environment bypass setting required.')
    release_id = identifier(env.get('RELEASE_ID'))
    release = config.get('releases', {}).get(release_id, {})
    require(release.get('qualified') is True and release.get('schemaChanges') is False
            and release.get('codeRollbackCompatible') is True, 'Qualified code-only release required.')
    require(re.fullmatch(r'[a-f0-9]{40}', release.get('sourceCommit', '')), 'Full release source required.')
    for field in ('archiveSha256', 'expectedActiveSha256', 'qualificationSha256'):
        sha256(release.get(field))
    note = release.get('releaseNote', '')
    require(re.fullmatch(r'docs/notes-de-version/[a-z0-9-]+\.md', note or ''), 'Release note required.')
    require((root / note).is_file() and not (root / note).is_symlink(), 'Release note missing.')
    require(release['archiveSha256'] != release['expectedActiveSha256'], 'No-op release refused.')
    backup_id = env.get('BACKUP_ID', '')
    if operation == 'rollback':
        identifier(backup_id)
    else:
        require(not backup_id, 'Backup selection is only allowed for rollback.')
    run_id, attempt = env.get('GITHUB_RUN_ID', ''), env.get('GITHUB_RUN_ATTEMPT', '')
    require(re.fullmatch(r'[1-9][0-9]{0,19}', run_id) and attempt == '1',
            'New dispatch required; blind reruns are refused.')
    return dict(plan, ready=True, releaseId=release_id, release=release,
                backupId=backup_id, runId=run_id, authorizationRef=config['qualificationRef'])


def github_json(path, env):
    request = Request('https://api.github.com/repos/' + REPOSITORY + path,
                      headers={'Authorization': 'Bearer ' + env.get('GH_TOKEN', ''),
                               'Accept': 'application/vnd.github+json'})
    with urlopen(request, timeout=20) as response:
        return json.load(response)


def verify_environment(env, fetch=github_json):
    environment = fetch('/environments/tcl-production', env)
    require(environment.get('name') == 'tcl-production', 'Production environment missing.')
    rules = environment.get('protection_rules', [])
    require(any(rule.get('type') == 'required_reviewers' and rule.get('reviewers') for rule in rules),
            'A required production reviewer must be configured.')
    policy = environment.get('deployment_branch_policy') or {}
    require(policy.get('custom_branch_policies') is True, 'Explicit main branch policy required.')
    branches = fetch('/environments/tcl-production/deployment-branch-policies?per_page=100', env)
    require(branches.get('total_count') == 1 and len(branches.get('branch_policies', [])) == 1
            and branches['branch_policies'][0].get('name') == 'main'
            and branches['branch_policies'][0].get('type') == 'branch', 'Only main may deploy.')


def verify_run_approval(plan, env, fetch=github_json):
    reviews = fetch('/actions/runs/' + plan['runId'] + '/approvals', env)
    require(isinstance(reviews, list) and any(
        review.get('state') == 'approved' and review.get('user', {}).get('login')
        and any(item.get('name') == 'tcl-production' for item in review.get('environments', []))
        for review in reviews), 'This production run has no recorded approval.')


def verify_result(result, plan):
    require(result.get('format') == 'tcl-production-result-v1'
            and result.get('runId') == plan['runId'] and result.get('target') == TARGET
            and result.get('releaseId') == plan['releaseId']
            and result.get('operation') == plan['operation'], 'Remote receipt does not match the request.')
    require(result.get('status') == 'completed' and result.get('databaseRestored') is False
            and result.get('preproductionChanged') is False
            and result.get('backupVerified') is True and result.get('restoreTested') is True
            and result.get('healthChecked') is True, 'No verified production completion.')
    # Only these bounded fields may enter a public GitHub log or summary.
    return {key: result[key] for key in ('status', 'operation', 'runId', 'releaseId',
                                       'databaseRestored', 'preproductionChanged', 'backupVerified',
                                       'restoreTested', 'healthChecked')}


def execute(plan, env):
    require(env.get('SSH_USER') == 'daje5127', 'Only the qualified TC hosting account is allowed.')
    directory, endpoint = setup_ssh(env)
    try:
        # The fixed private entrypoint is read once, hashed, then executed from
        # those same bytes. No user input becomes a remote shell command.
        bootstrap = ("import hashlib,sys; from pathlib import Path; "
                     "p=Path(" + repr(REMOTE) + "); "
                     "assert all(not q.is_symlink() for q in (p, *p.parents)); "
                     "b=p.read_bytes(); "
                     "assert hashlib.sha256(b).hexdigest()==" + repr(plan['operatorSha256']) + "; "
                     "exec(compile(b,str(p),'exec'),{'__name__':'__main__','__file__':str(p)})")
        command = 'python3 -c ' + shlex.quote(bootstrap)
        result = subprocess.run(['ssh', *ssh_options(directory, env['SSH_PORT']),
                                 '-o', 'ServerAliveInterval=15', '-o', 'ServerAliveCountMax=3',
                                 endpoint, command], input=json.dumps(plan).encode('utf-8'),
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=1500)
        require(result.returncode == 0, 'Remote operation interrupted or refused; inspect the private journal. Do not retry blindly.')
        require(len(result.stdout) <= 8192, 'Unexpected remote output; inspect privately.')
        return verify_result(json.loads(result.stdout), plan)
    finally:
        cleanup_ssh(env)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=('prepare', 'execute', 'cleanup'))
    args = parser.parse_args()
    if args.command == 'cleanup':
        cleanup_ssh(os.environ)
        return
    config = json.loads((ROOT / 'config/production-action.json').read_text(encoding='utf-8'))
    plan = validate(os.environ, config)
    if plan['ready']:
        verify_environment(os.environ)
    if args.command == 'execute':
        require(plan['ready'], 'Plan cannot execute.')
        verify_run_approval(plan, os.environ)
        result = execute(plan, os.environ)
    else:
        result = {'operation': plan['operation'], 'ready': plan['ready'],
                  'hostingContacted': False, 'deploymentExecuted': False}
        if os.environ.get('GITHUB_OUTPUT'):
            with open(os.environ['GITHUB_OUTPUT'], 'a', encoding='utf-8') as output:
                output.write('ready=' + str(plan['ready']).lower() + '\n')
    print(json.dumps(result, sort_keys=True))
    if os.environ.get('GITHUB_STEP_SUMMARY'):
        with open(os.environ['GITHUB_STEP_SUMMARY'], 'a', encoding='utf-8') as output:
            output.write('## TC Longages\n\n```json\n' + json.dumps(result, indent=2) + '\n```\n')


if __name__ == '__main__':
    try:
        main()
    except Exception:
        # SSH/PHP/provider errors can contain credentials: never echo raw errors.
        print('Production gate refused or operation incomplete. Consult the guide and private journal; no automatic retry.', file=sys.stderr)
        sys.exit(1)
