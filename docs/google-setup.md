# Google sign-in and Google Drive storage — setup

This turns on two things:

1. **Sign in with Google** for everyone in CPSU. Only institutional accounts the QA office registered under **Accounts** can get in. Accreditors from outside CPSU don't need a Google account; they keep using the link and one-time code sent to their email.
2. **Google Drive storage.** Every folder and file a college uploads is copied into the Drive of the system account `cpsu_accresys@…`, sorted like this:

```
CPSU OAS Accreditation Files/
└── SY 2026-2027/
    └── CAS – College of Arts and Sciences/
        └── Preliminary Survey Visit/
            └── Area II. Faculty/
                └── A. Academic Qualifications and Professional Experience/
                    └── Exhibit A – Board Resolutions/   ← folder the college made
                        └── BOR Resolution No. 12.pdf
```

Viewers never get a Drive link. Files open in the system's own viewer, and the server fetches them from Drive behind the scenes.

Allow about 30–45 minutes. You need the password of the system account and, for step 2, a Google Workspace admin if your domain restricts third-party apps.

---

## Before you start

- [ ] **Confirm the domain.** The system expects `cpsu.edu.ph`. If your accounts end in something else (for example `ccpsu.edu.ph`), use that everywhere below.
- [ ] **The system account exists** in your Google Workspace and you can sign in to it: `cpsu_accresys@cpsu.edu.ph`.
- [ ] **Know the address of the system.** Google only sends people back to addresses you register. For testing on this computer it is `http://localhost/...`; for real use it should be a proper address with HTTPS, e.g. `https://oas.cpsu.edu.ph`. Whatever it is, put the same value in `APP_URL` in `.env`.
- [ ] **Your own QA account uses your real institutional email.** The sample accounts (`qa_director@cpsu.edu.ph`, etc.) are placeholders. Open **Accounts**, edit your account, and change the email to your real one **before** step 5, or you will lock yourself out when passwords switch off.

---

## Step 1 — Create the Google Cloud project

1. Sign in to <https://console.cloud.google.com> **as the system account** (`cpsu_accresys@…`), so the project belongs to the institution and not to a person.
2. Top bar → project picker → **New project**. Name it `CPSU OAS`. Organization: your CPSU organization. **Create**, then select it.

## Step 2 — Enable the Google Drive API

1. Left menu → **APIs & Services → Library**.
2. Search **Google Drive API** → open it → **Enable**.

## Step 3 — Set up the consent screen

1. **APIs & Services → OAuth consent screen** (on newer consoles: **Google Auth Platform → Branding / Audience**).
2. **User type: Internal.** Only accounts in your Workspace can use the app, which is exactly what we want and needs no Google review.
3. App name `CPSU Online Accreditation System`, support email: the QA office address, logo optional, authorized domain `cpsu.edu.ph`.
4. **Scopes** (Data access): add
   - `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`
   - `.../auth/drive.file` — *"See, edit, create, and delete only the specific Google Drive files you use with this app"*. The system can only touch files it created, never anything else in that Drive.
5. Save.

> If your Workspace admin restricts apps: Admin console → **Security → Access and data control → API controls → Manage third-party app access** → add the OAuth client ID from step 4 as **Trusted**.

## Step 4 — Create the OAuth client

1. **APIs & Services → Credentials → Create credentials → OAuth client ID**.
2. Application type: **Web application**. Name: `CPSU OAS web`.
3. **Authorized redirect URIs** — add both, using your `APP_URL`:
   - `{APP_URL}/auth/google/callback`   ← staff sign-in
   - `{APP_URL}/qa/drive/callback`      ← connecting the Drive account

   Examples: `http://localhost/auth/google/callback`, or `https://oas.cpsu.edu.ph/auth/google/callback`.
   They must match character for character (http vs https, trailing slashes, port).
4. **Create** and copy the **Client ID** and **Client secret**.

## Step 5 — Put the keys in `.env`

Open `c:\xampp\htdocs\cpsu-oas\.env` and add or update:

```dotenv
APP_URL=http://localhost              # the same base you used in step 4

GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxx

OAS_ALLOWED_DOMAIN=cpsu.edu.ph        # only @cpsu.edu.ph Google accounts may sign in
OAS_DRIVE_ACCOUNT=cpsu_accresys@cpsu.edu.ph
OAS_DRIVE_ROOT="CPSU OAS Accreditation Files"
OAS_DRIVE_KEEP_LOCAL=false            # delete the server copy once a file is safely in Drive
OAS_ALLOW_PASSWORD_LOGIN=false        # set true only as an emergency fallback
```

Then in a terminal in the project folder:

```bash
php artisan config:clear
php artisan migrate
```

The sign-in page now shows only **Sign in with Google**.

> Never change `APP_KEY` after this point. It encrypts the saved Drive connection; changing it means reconnecting Drive.

## Step 6 — Register the people who may sign in

**Accounts → New account** for each QA staff member, administrator and campus/college in-charge:

- Email: their **institutional** address (anything else is rejected).
- Role, and for in-charges the college or campus they handle.

There is no password to hand out. They open the system, click **Sign in with Google**, and choose their CPSU account. The first sign-in links that Google account to the email; nobody else can use it afterwards. If someone's email changes, edit it in Accounts and the link resets.

Anyone not registered is turned away with *"isn't registered in the accreditation system"*.

## Step 7 — Connect the system Drive

1. Sign in as QA → sidebar **Google Drive** → **Connect cpsu_accresys@…**.
2. Choose the **system account** (not your own) and click **Allow**.
3. Back in the system you should see **Connected**. A folder named *CPSU OAS Accreditation Files* now exists in that account's **My Drive**.
4. If files were uploaded before connecting, click **Sync waiting files now** (it sends a batch per click; repeat until "Waiting" is 0).

> Don't move, rename or share the *CPSU OAS Accreditation Files* folder by hand. The system keeps track of it by ID; sharing it would bypass the access rules in the system.

## Step 8 — Keep uploads moving in the background (recommended)

Out of the box, files go to Drive right after each upload (`QUEUE_CONNECTION=sync`). That works, but large videos tie up the server for a while. For real use, run a background worker:

1. In `.env`: `QUEUE_CONNECTION=database`, then `php artisan config:clear`.
2. Start the worker and keep it running:

   ```bash
   php artisan queue:work --tries=5 --timeout=0
   ```

   On Windows, use **Task Scheduler** (trigger: *At startup*, action: `C:\xampp\php\php.exe`, arguments `artisan queue:work --tries=5 --timeout=0`, start in: `c:\xampp\htdocs\cpsu-oas`) so it survives restarts.

Failed uploads are retried automatically (after 30 s, 2 min, 10 min, 30 min). Anything still failing is listed under **Google Drive → Needs attention**. Each file in a folder shows a dot: green = in Drive, amber = waiting, red = failed (will retry).

## Step 9 — Email for accreditors

Accreditor links and one-time codes go out by email. Set real mail settings in `.env`. With the system account and Gmail:

1. Turn on 2-Step Verification for `cpsu_accresys@…`, then create an **App password** (Google Account → Security → App passwords).
2. In `.env`:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=cpsu_accresys@cpsu.edu.ph
   MAIL_PASSWORD=the-16-character-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=cpsu_accresys@cpsu.edu.ph
   MAIL_FROM_NAME="CPSU Office of Quality Assurance"
   ```

3. `php artisan config:clear`, then share a test visit with your own personal email to check.

(If your admin disabled app passwords, ask for the Workspace **SMTP relay** instead: host `smtp-relay.gmail.com`, port 587.)

## Step 10 — Try it end to end

1. Sign in with Google as a registered in-charge → open a visit → open a parameter → **New folder** → upload a PDF and a short video.
2. In the system account's Drive, confirm the path `SY … / college / visit / area / parameter / your folder / file`.
3. Back in the system, click the file: it opens in the viewer, and the address bar and page never show a Drive link.
4. Sign in with a Gmail address → you are refused. Sign in with an unregistered `@cpsu.edu.ph` account → refused.
5. As QA, share the visit with an outside email → open the link in a private window → enter the code → read-only access.

---

## Every school year

Each school year is its own cycle. When the new one starts:

**School years → Start a new school year** → pick it (e.g. SY 2027–2028) → *Carry over visit structures from* last year → keep **Keep the same colleges**, **Make it the current year** and **Close visits of earlier years** ticked → **Start school year**.

- Colleges now file into the new year's visits; Drive gets a new `SY 2027-2028` folder.
- Last year stays complete and browsable. Anyone can switch years with the **School year** picker at the top of the sidebar. Past years are read-only for colleges.

---

## Troubleshooting

| What you see | Fix |
|---|---|
| `Error 400: redirect_uri_mismatch` | The address in step 4 doesn't exactly match `APP_URL` + `/auth/google/callback` (or `/qa/drive/callback`). Fix one or the other, then `php artisan config:clear`. |
| `Error 403: org_internal` / "app is restricted" | You signed in with a non-CPSU account, or the consent screen isn't **Internal**. |
| "Access blocked: admin hasn't approved this app" | Workspace admin must mark the client ID as **Trusted** (step 3 note). |
| "isn't registered in the accreditation system" | Add that email under **Accounts**. |
| "linked to a different Google account" | Someone already signed in with that email from another Google account. Edit the user's email (or re-save it) in Accounts to reset the link. |
| Drive: "Google returned no offline access" | Go to <https://myaccount.google.com/permissions> as the system account, remove *CPSU Online Accreditation System*, then **Connect** again. |
| Drive: "Connect with cpsu_accresys@… instead" | You picked your own account in the Google chooser. Connect again and choose the system account. |
| Files stay amber | Drive isn't connected, or the queue worker isn't running (step 8). Check **Google Drive → Needs attention**. |
| Locked out after turning passwords off | Set `OAS_ALLOW_PASSWORD_LOGIN=true` in `.env`, `php artisan config:clear`, sign in with a password, fix the accounts, then set it back to `false`. |
