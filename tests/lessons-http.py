"""Exercise lesson management against a loopback Drupal and fictitious OAuth."""
import datetime as dt
import importlib.util
import secrets
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("oauth", ROOT / "tests/drupal-bureau-oauth-http.py")
oauth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(oauth)
check = oauth.check

def path_of(response):
    return oauth.urllib.parse.urlsplit(response[2]["Location"]).path

def run():
    bureau, anonymous, captain = oauth.Session(), oauth.Session(), oauth.Session()
    listing = "/bureau/cours"
    create = listing + "/ajouter"
    check("Anonymous POST denied", anonymous.get(create, {"name": "forbidden"})[0] == 403)
    check("Bureau OAuth succeeds", bureau.oauth()[0] == 303)
    check("Captain OAuth succeeds", captain.oauth("captain-fixture")[0] == 303)
    check("Captain cannot view or create groups", captain.get(listing)[0] == 403 and captain.get(create, {"name": "Forbidden"})[0] == 403)
    check("Course link appears in Bureau", "/bureau/cours" in bureau.get("/bureau/evenements")[1])

    today = dt.date.today()
    season = today.year if today.month >= 9 else today.year - 1
    historical = dt.date(season - 1, 11, 5)
    excluded = historical + dt.timedelta(days=7)
    name = "Groupe test " + secrets.token_hex(4)
    values = {"name": name, "coach": "Enseignant fictif", "court": "Court 1",
              "season": str(season - 1), "goal": "24", "baseline": "8", "date": historical.isoformat(),
              "until": (historical + dt.timedelta(days=14)).isoformat(), "start": "10:00", "end": "11:00",
              "excluded": excluded.isoformat(), "prior_cancelled": (historical - dt.timedelta(days=7)).isoformat()}
    original_form = bureau.get(create)[1]
    response = bureau.form(create, values, original_form)
    check("Group created", response[0] == 303 and path_of(response).startswith(listing + "/"))
    group = path_of(response)
    check("Repeated create resisted", bureau.form(create, values, original_form)[0] != 303)
    detail = bureau.get(group)[1]
    check("Recurrence and balance displayed", "8 cours assurés sur 24" in detail and excluded.strftime("%d/%m/%Y") not in detail)
    check("Season filter selects group", name in bureau.get(listing + "?saison=" + str(season - 1))[1] and name not in bureau.get(listing + "?saison=2000")[1])
    check("Unknown group returns 404", bureau.get(listing + "/" + "0" * 32)[0] == 404)
    calendar = "/calendrier.html?mois=" + historical.strftime("%Y-%m")
    check("Private calendar lists the group", name in bureau.get(calendar)[1])
    check("Public calendar hides the group", name not in anonymous.get(calendar)[1])

    hold = group + "/seances/" + historical.isoformat()
    check("Completed lesson saved using native form", bureau.form(hold + "/hold", {})[0] == 303)
    check("Completion counted exactly once", "9 cours assurés sur 24" in bureau.get(group)[1] and bureau.form(hold + "/hold", {})[0] != 303)

    future = today + dt.timedelta(days=2)
    shift = future + dt.timedelta(days=1)
    if future.month == 8 and shift.month == 9:
        future, shift = shift, shift + dt.timedelta(days=1)
    upcoming_season = future.year if future.month >= 9 else future.year - 1
    future_values = dict(values, name=name + " futur", season=str(upcoming_season), baseline="0",
                         date=future.isoformat(), until=future.isoformat(), excluded="", prior_cancelled="")
    group = path_of(bureau.form(create, future_values))
    session = group + "/seances/" + future.isoformat()
    cancel = session + "/cancel"
    check("GET opens form without cancelling", bureau.get(cancel)[0] == 200 and "À reprogrammer" not in bureau.get(session)[1])
    check("Captain cannot alter a session", captain.get(cancel, {"reason": "Forbidden"})[0] == 403)
    bureau.form(cancel, {"reason": "CSRF overwrite", "form_token": "invalid"})
    check("Invalid CSRF cannot cancel", "CSRF overwrite" not in bureau.get(session)[1])
    cancelled = bureau.form(cancel, {"reason": "Court impraticable"})
    check("Cancellation saved and draft available", cancelled[0] == 303 and "Texte à copier" in bureau.get(session)[1] and "est annulé" in bureau.get(session)[1])
    check("Cancelled session enters recovery queue", "Court impraticable" in bureau.get(listing)[1])

    recovery_path = session + "/reschedule"
    data = {"new_date": shift.isoformat(), "start": "11:00", "end": "12:00", "court": "Court 2"}
    form = bureau.get(recovery_path)[1]
    check("Replacement scheduled", bureau.form(recovery_path, data, form)[0] == 303)
    check("Replayed scheduling denied", bureau.form(recovery_path, data, form)[0] != 303)
    check("Original and replacement remain linked", "Court impraticable" in bureau.get(session)[1] and "Rattrapage programmé" in bureau.get(session)[1])
    check("Scheduling does not add a held lesson", "0 cours assurés sur 24" in bureau.get(group)[1])
    check("Cancelling the replacement reopens queue", bureau.form(cancel, {"reason": "Pluie"})[0] == 303 and "À reprogrammer" in bureau.get(session)[1])
    check("Group can close a cancellation with a decision", bureau.form(session + "/close", {"reason": "Décision de recette"})[0] == 303 and "Clôturé sans rattrapage" in bureau.get(session)[1])
    check("Group can reopen a cancellation", bureau.form(session + "/reopen", {"reason": "Nouveau créneau"})[0] == 303 and "À reprogrammer" in bureau.get(session)[1])

if __name__ == "__main__":
    run()
    print(f"{len(oauth.CHECKS)} lesson HTTP checks passed.")
