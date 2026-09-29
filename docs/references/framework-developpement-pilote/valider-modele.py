"""Valide le profil du framework et son suivi sans modifier de fichier ni appeler un service."""
from pathlib import Path
import argparse, copy, json, re, sys

try:
    from jsonschema import Draft202012Validator, FormatChecker
except ImportError:
    raise SystemExit("Installer les dépendances de vérification dans un environnement local : python -m pip install -r requirements-verification.txt")

ROOT=Path(__file__).resolve().parent

def validate(profile,tracker,schema):
    problems=[]
    Draft202012Validator.check_schema(schema)
    for error in Draft202012Validator(schema,format_checker=FormatChecker()).iter_errors(profile):
        problems.append('/'.join(map(str,error.absolute_path))+': '+error.message)
    if problems:return problems
    phases=tracker.get('phases',[])
    ids=[p.get('id') for p in phases]
    if ids!=list(range(len(ids))) or not ids:problems.append('Phases : identifiants entiers contigus et ordonnés attendus.')
    if tracker.get('project')!=profile['project']['id']:problems.append('Identité de projet divergente.')
    if tracker.get('currentPhase') not in ids:problems.append('Phase courante inconnue.')
    decisions=tracker.get('decisions',[])
    if len({x.get('id') for x in decisions})!=len(decisions):problems.append('Identifiant de décision répété.')
    approved={d.get('id'):d for d in decisions if d.get('status')=='approved'}
    for phase in phases:
        pid=phase.get('id')
        deps=phase.get('dependsOn',[])
        if any(type(d)!=int or d not in ids or d>=pid for d in deps):problems.append(f'Phase {pid} : dépendance invalide ou cyclique.')
        if not phase.get('exitCriteria'):problems.append(f'Phase {pid} : critères absents.')
        if phase.get('status') not in ['not_started','in_progress','awaiting_review','validated','blocked']:problems.append(f'Phase {pid} : état non reconnu.')
        for deliverable in phase.get('deliverables',[]):
            path=deliverable.get('path','')
            if not path.endswith('.md') or Path(path).is_absolute() or '..' in path.replace('\\','/').split('/'):
                problems.append(f'Phase {pid} : chemin de livrable hors contrat.')
        if phase.get('status')=='validated':
            ev=phase.get('validationEvidence') or {}
            decision=approved.get(ev.get('decisionId'))
            if not decision or decision.get('type')!='approval' or decision.get('phaseId')!=pid:
                problems.append(f'Phase {pid} : validation sans décision applicable.')
            elif not (decision.get('evidence') or {}).get('reviewedArtifacts'):
                problems.append(f'Phase {pid} : empreintes des livrables absentes.')
            if not phase.get('validatedOn'):problems.append(f'Phase {pid} : date de validation absente.')
        if phase.get('startedOn'):
            auth=approved.get((phase.get('startEvidence') or {}).get('decisionId'))
            if not auth or auth.get('type')!='authorization' or auth.get('phaseId')!=pid:
                problems.append(f'Phase {pid} : démarrage sans autorisation applicable.')
    if profile['template']:
        if tracker.get('template') is not True:problems.append('Le suivi associé doit rester un modèle vierge.')
        if profile['project']['initialAuthorizationRef'] is not None:problems.append('Aucune autorisation initiale héritée dans un modèle.')
        for key in ['decisions','history','reviewEvents','reviewFollowUps','reviewCommentResponses','evidence','testRuns','exceptions','requirementChanges']:
            if tracker.get(key):problems.append('Modèle : historique hérité interdit : '+key)
        for phase in phases:
            if phase.get('status')!='not_started' or any(phase.get(k) is not None for k in ['startedOn','authorizedOn','deliveredOn','validatedOn','startEvidence','validationEvidence','pullRequest']):
                problems.append(f"Modèle : phase {phase.get('id')} déjà engagée.")
        if any(tracker.get('publication',{}).values()):problems.append('Modèle : publication déjà déclarée.')
        if any(env['status']!='not_configured' or env['evidenceRefs'] for env in profile['environments'].values()):
            problems.append('Modèle : environnement déjà qualifié ou configuré.')
        if profile['implementationStatus']!='specification_to_adapt':problems.append('Modèle : capacité opérationnelle déclarée.')
    for item in tracker.get('decisions',[]):
        if item['status']=='approved' and item.get('type') in ['production_deployment','public_opening']:
            if item['actor'] not in profile['project']['humanApprovers']:problems.append('Approbateur hors liste : '+item['id'])
            if not item['sourceRef'] or not item['recordedAt']:problems.append('Décision sans source ou date : '+item['id'])
            if item['project']!=profile['project']['id']:problems.append('Décision issue d’un autre projet : '+item['id'])
            if item['type'] in ['production_deployment','public_opening'] and not all(item[k] for k in ['environment','sourceCommit','candidateId','artifactDigest','criteriaDigest']):
                problems.append('Livraison/ouverture sans version et périmètre complets : '+item['id'])
    for ev in tracker.get('evidence',[])+tracker.get('testRuns',[]):
        if ev['status']=='passed' and not all(ev[k] for k in ['sourceCommit','recordedAt','actor','artifactRef','actualResult']):
            problems.append('Preuve réussie sans contexte suffisant : '+ev['id'])
    return problems

def self_test(profile,tracker,schema):
    tests=[]
    def check(name,mutate):
        p,t=copy.deepcopy(profile),copy.deepcopy(tracker)
        mutate(p,t)
        errors=validate(p,t,schema)
        tests.append({'name':name,'passed':bool(errors),'expected':'rejected'})
    tests.append({'name':'Modèle vierge accepté','passed':not validate(profile,tracker,schema),'expected':'accepted'})
    check('Déploiement automatique au merge refusé',lambda p,t:p['governance'].__setitem__('deployOnMerge',True))
    check('Sauvegarde fraîche désactivée refusée',lambda p,t:p['delivery'].__setitem__('freshBackupBeforeWrite',False))
    check('Restauration désactivée refusée',lambda p,t:p['delivery'].__setitem__('restoreExactBackupBeforeWrite',False))
    check('Ouverture automatique refusée',lambda p,t:p['delivery'].__setitem__('openPublicAutomatically',True))
    check('Champ contenant une valeur de secret refusé',lambda p,t:p['security'].__setitem__('password','fictional-test-only'))
    check('Validation de phase sans décision refusée',lambda p,t:t['phases'][2].__setitem__('status','validated'))
    check('Journal hérité refusé',lambda p,t:t['reviewEvents'].append({'id':'fictif'}))
    check('Dépendance cyclique refusée',lambda p,t:t['phases'][1].__setitem__('dependsOn',[1]))
    check('Mauvais projet refusé',lambda p,t:t.__setitem__('project','autre-projet'))
    check('Livrable hors dossier refusé',lambda p,t:t['phases'][0]['deliverables'][0].__setitem__('path','../secret.md'))
    check('Production déjà livrée dans un modèle refusée',lambda p,t:t['publication'].__setitem__('productionDeploymentExecuted',True))
    check('Date invalide refusée',lambda p,t:p.__setitem__('created','2026-99-99'))
    check('Démarrage hérité sans accord refusé',lambda p,t:t['phases'][0].__setitem__('startedOn','2026-09-29'))
    check('Protection de l’hôte désactivée refusée',lambda p,t:p['security'].__setitem__('strictHostVerification',False))
    return tests

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--profile',type=Path,default=ROOT/'modele-projet.json')
    parser.add_argument('--schema',type=Path,default=ROOT/'schema-projet.json')
    parser.add_argument('--tracker',type=Path,default=ROOT/'modeles/docs/suivi-chantier/suivi-chantier.json')
    parser.add_argument('--self-test',action='store_true')
    a=parser.parse_args()
    try:
        profile,tracker,schema=[json.loads(p.read_text(encoding='utf-8-sig')) for p in [a.profile,a.tracker,a.schema]]
        errors=validate(profile,tracker,schema)
        tests=self_test(profile,tracker,schema) if a.self_test else []
        result={'valid':not errors,'errors':errors,'tests':tests,
          'limits':'Contrôle de modèle et cohérence; aucune authentification d’accord, vérification distante ou livraison.'}
        print(json.dumps(result,ensure_ascii=False,indent=2))
        return 1 if errors or any(not t['passed'] for t in tests) else 0
    except (OSError,ValueError,KeyError,TypeError) as error:
        print(json.dumps({'valid':False,'error':str(error)},ensure_ascii=False))
        return 2

if __name__=='__main__':raise SystemExit(main())

