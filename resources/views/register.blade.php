<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription - EasyMarket</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/logo.png">
    <meta name="theme-color" content="#2f7d69">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        :root {
            --primary-950: #10251f;
            --primary-900: #193b32;
            --primary-800: #2e6557;
            --primary-700: #3f8f7b;
            --primary-600: #56ad96;
            --mint-100: #e1f3ee;
            --paper: #fbfdfb;
            --gold: #f5b84b;
            --ink: #17211b;
            --muted: #3f5048;
            --line: #dfe7e2;
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f4faf8, #e9f5f1);
            color: var(--ink);
            font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        input, select, textarea, button { font: inherit; }
        .container { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
        .topbar {
            min-height: 72px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--line);
            background: rgba(255,255,255,.78);
            backdrop-filter: blur(14px);
        }
        .nav { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 500; }
        .logo {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-600), var(--gold));
            color: white;
            font-weight: 500;
        }
        .btn {
            min-height: 42px;
            border-radius: 8px;
            border: 1px solid transparent;
            padding: 10px 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary { background: var(--gold); color: var(--primary-950); }
        .btn-dark { background: var(--primary-950); color: white; }
        .btn-light { border-color: transparent; background: var(--primary-800); color: white; }
        .page {
            padding: 46px 0 70px;
            display: grid;
            grid-template-columns: .85fr 1.15fr;
            gap: 28px;
            align-items: start;
        }
        .intro, .form-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: 0 20px 50px rgba(16,37,31,.08);
        }
        .intro { padding: 28px; position: sticky; top: 24px; }
        .eyebrow { color: var(--primary-700); font-weight: 500; text-transform: uppercase; font-size: 12px; letter-spacing: .08em; }
        h1 { margin: 12px 0 12px; font-size: clamp(34px, 5vw, 56px); line-height: 1; }
        p { color: var(--muted); }
        .steps { display: grid; gap: 12px; margin-top: 24px; }
        .step {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 12px;
            align-items: start;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #f8fcfa;
        }
        .icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--mint-100);
            color: var(--primary-800);
        }
        .step strong { display: block; margin-bottom: 3px; }
        .step span { color: var(--muted); font-size: 14px; }
        .form-card { overflow: hidden; }
        .form-head {
            padding: 24px;
            background: var(--primary-950);
            color: white;
        }
        .form-head p { color: rgba(255,255,255,.76); margin: 8px 0 0; }
        form { padding: 24px; display: grid; gap: 24px; }
        fieldset {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 18px;
            margin: 0;
        }
        legend {
            padding: 0 8px;
            font-weight: 500;
            color: var(--primary-900);
        }
        .fields { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        label { display: grid; gap: 7px; font-weight: 600; font-size: 14px; }
        .label-text { display: inline-flex; align-items: center; gap: 3px; }
        .field-help { color: var(--muted); font-size: 12px; font-weight: 500; }
        .required { color: #b42318; font-weight: 500; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 11px 12px;
            color: var(--ink);
            background: white;
        }
        .password-field {
            position: relative;
            display: block;
        }
        .password-field input {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 6px;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 8px;
            display: inline-grid;
            place-items: center;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }
        .password-toggle:hover,
        .password-toggle:focus {
            background: var(--mint-100);
            color: var(--primary-800);
            outline: none;
        }
        .phone-field {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr);
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: white;
            overflow: hidden;
        }
        .phone-field span {
            height: 100%;
            min-height: 42px;
            display: grid;
            place-items: center;
            background: #eef2f0;
            color: var(--muted);
            font-weight: 800;
            border-right: 1px solid var(--line);
        }
        .phone-field input {
            border: 0;
            border-radius: 0;
        }
        textarea { min-height: 94px; resize: vertical; }
        .full { grid-column: 1 / -1; }
        .note {
            border-radius: 8px;
            padding: 14px;
            background: var(--mint-100);
            color: var(--primary-900);
            font-weight: 500;
        }
        .form-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .help { color: var(--muted); font-size: 14px; }
        .whatsapp-help {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #bfe6d8;
            border-radius: 8px;
            padding: 9px 12px;
            background: #ecfbf5;
            color: #126c4d;
            font-weight: 500;
        }
        .whatsapp-help i { color: #25d366; font-size: 18px; }
        .whatsapp-help strong { color: var(--primary-950); font-size: 16px; font-weight: 600; }
        .error-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            place-items: center;
            padding: 20px;
            background: rgba(16, 37, 31, .62);
            backdrop-filter: blur(6px);
        }
        .error-modal.open { display: grid; }
        .error-modal-card {
            width: min(520px, 100%);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            border: 1px solid #ffd0c7;
            border-radius: 12px;
            background: white;
            box-shadow: 0 24px 70px rgba(16,37,31,.28);
        }
        .error-modal-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #ffe0d8;
            background: #fff5f2;
        }
        .error-modal-head h3 { margin: 0; color: #8a2418; font-size: 20px; }
        .error-modal-head p { margin: 4px 0 0; color: #7a5149; font-size: 14px; }
        .error-modal-close {
            flex: none;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 8px;
            display: inline-grid;
            place-items: center;
            background: #ffe6df;
            color: #8a2418;
            cursor: pointer;
        }
        .error-list {
            display: grid;
            gap: 9px;
            margin: 0;
            padding: 18px 20px 4px 38px;
            color: #4a2b25;
            font-weight: 600;
        }
        .error-modal-actions {
            display: flex;
            justify-content: flex-end;
            padding: 16px 20px 20px;
        }
        .field-invalid, .phone-field.field-invalid {
            border-color: #b42318;
            box-shadow: 0 0 0 3px rgba(180,35,24,.12);
        }

        @media (max-width: 900px) {
            .page { grid-template-columns: 1fr; }
            .intro { position: static; }
        }
        @media (max-width: 620px) {
            .fields { grid-template-columns: 1fr; }
            .nav { align-items: flex-start; flex-direction: column; padding: 14px 0; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="/">
                <span class="logo">EM</span>
                <span>EasyMarket</span>
            </a>
            <a class="btn btn-light" href="/"><i class="fa-solid fa-arrow-left"></i>Retour à l'accueil</a>
        </div>
    </header>

    <main class="container page">
        <section class="intro">
            <div class="eyebrow">Inscription</div>
            <h1>Créez votre espace EasyMarket</h1>
            <p>Renseignez vos informations et celles de votre boutique. Après l'inscription, vous accéderez à votre tableau de bord pour finaliser le premier paiement.</p>
            <div class="steps">
                <div class="step"><div class="icon"><i class="fa-solid fa-user"></i></div><div><strong>1. Vos informations</strong><span>Identité, téléphone personnel, WhatsApp, email et mot de passe.</span></div></div>
                <div class="step"><div class="icon"><i class="fa-solid fa-store"></i></div><div><strong>2. Votre boutique</strong><span>Nom, téléphone boutique, WhatsApp boutique, adresse, IFU, slogan et description.</span></div></div>
                <div class="step"><div class="icon"><i class="fa-solid fa-credit-card"></i></div><div><strong>3. Premier paiement</strong><span>Le formulaire de paiement sera disponible dans le tableau de bord.</span></div></div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-head">
                <h2 style="margin:0">Formulaire d'inscription</h2>
                <p>Les champs marqués comme obligatoires permettent de créer votre compte et votre boutique.</p>
            </div>
            <form method="POST" action="/inscription" id="registration-form" novalidate>
                <input type="hidden" name="_token" value="__CSRF_TOKEN__">
                <fieldset>
                    <legend><i class="fa-solid fa-id-card"></i> Informations personnelles</legend>
                    <div class="fields">
                        <label><span class="label-text">Civilité <span class="required">*</span></span>
                            <select name="civility" required>
                                <option value="M.">M.</option>
                                <option value="Mme">Mme</option>
                            </select>
                        </label>
                        <label><span class="label-text">Prénom <span class="required">*</span></span>
                            <input name="first_name" type="text" placeholder="Ex. Aïcha" required>
                        </label>
                        <label><span class="label-text">Nom <span class="required">*</span></span>
                            <input name="last_name" type="text" placeholder="Ex. Houngan" required>
                        </label>
                        <label><span class="label-text">Téléphone personnel <span class="required">*</span></span>
                            <span class="phone-field"><span>01</span><input name="phone" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" required></span>
                        </label>
                        <label><span class="label-text">WhatsApp personnel</span>
                            <span class="phone-field"><span>01</span><input name="whatsapp_phone" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860"></span>
                        </label>
                        <label><span class="label-text">Email</span>
                            <input name="login" type="email" placeholder="vous@exemple.com" autocomplete="username">
                        </label>
                        <label><span class="label-text">Mot de passe <span class="required">*</span></span>
                            <span class="password-field">
                                <input name="password" type="password" placeholder="Créer un mot de passe" required>
                                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe" title="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
                            </span>
                            <small class="field-help">Au moins 8 caractères.</small>
                        </label>
                        <label><span class="label-text">Confirmer le mot de passe <span class="required">*</span></span>
                            <span class="password-field">
                                <input name="password_confirmation" type="password" placeholder="Répéter le mot de passe" required>
                                <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Afficher la confirmation du mot de passe" title="Afficher la confirmation du mot de passe"><i class="fa-solid fa-eye"></i></button>
                            </span>
                            <small class="field-help">Répétez le même mot de passe.</small>
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><i class="fa-solid fa-shop"></i> Informations boutique</legend>
                    <div class="fields">
                        <label><span class="label-text">Nom de la boutique <span class="required">*</span></span>
                            <input name="business_name" type="text" placeholder="Ex. Market Plus" required>
                        </label>
                        <label><span class="label-text">Téléphone boutique <span class="required">*</span></span>
                            <span class="phone-field"><span>01</span><input name="business_phone" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" required></span>
                        </label>
                        <label><span class="label-text">WhatsApp boutique</span>
                            <span class="phone-field"><span>01</span><input name="business_whatsapp_phone" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860"></span>
                        </label>
                        <label><span class="label-text">Adresse</span>
                            <input name="business_address" type="text" placeholder="Quartier, ville">
                        </label>
                        <label><span class="label-text">IFU</span>
                            <input name="business_ifu" type="text" placeholder="Numéro IFU si disponible">
                        </label>
                        <label class="full"><span class="label-text">Slogan</span>
                            <input name="business_slogan" type="text" placeholder="Ex. Votre boutique de confiance">
                        </label>
                        <label class="full"><span class="label-text">Description de la boutique</span>
                            <textarea name="business_description" placeholder="Présentez brièvement votre activité, vos produits ou vos services."></textarea>
                        </label>
                        <label class="full"><span class="label-text">Informations complémentaires</span>
                            <textarea name="notes" placeholder="Type d'activité, nombre de produits, besoins particuliers..."></textarea>
                        </label>
                    </div>
                </fieldset>

                <div class="note">
                    <i class="fa-solid fa-circle-info"></i>
                    Vous pourrez procéder au 1er paiement directement après avoir créé votre compte.
                </div>

                <div class="form-actions">
                    <span class="help whatsapp-help"><i class="fa-brands fa-whatsapp"></i> Besoin d'aide ? WhatsApp : <strong>01 96 62 86 0</strong></span>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-arrow-right"></i>Créer mon compte</button>
                </div>
            </form>
        </section>
    </main>
    <div class="error-modal" id="registration-error-modal" aria-hidden="true">
        <section class="error-modal-card" role="dialog" aria-modal="true" aria-labelledby="registration-error-title">
            <div class="error-modal-head">
                <div>
                    <h3 id="registration-error-title"><i class="fa-solid fa-triangle-exclamation"></i> Corrigez ces erreurs</h3>
                    <p>Le compte ne peut pas être créé tant que ces informations ne sont pas corrigées.</p>
                </div>
                <button class="error-modal-close" type="button" id="registration-error-close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <ul class="error-list" id="registration-error-list"></ul>
            <div class="error-modal-actions">
                <button class="btn btn-primary" type="button" id="registration-error-ok"><i class="fa-solid fa-check"></i>J'ai compris</button>
            </div>
        </section>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?v=10');
        }

        const registrationForm = document.getElementById('registration-form');
        const errorModal = document.getElementById('registration-error-modal');
        const errorList = document.getElementById('registration-error-list');
        const errorClose = document.getElementById('registration-error-close');
        const errorOk = document.getElementById('registration-error-ok');
        const fieldLabels = {
            civility: 'Civilité',
            first_name: 'Prénom',
            last_name: 'Nom',
            phone: 'Téléphone personnel',
            whatsapp_phone: 'WhatsApp personnel',
            login: 'Email',
            password: 'Mot de passe',
            password_confirmation: 'Confirmation du mot de passe',
            business_name: 'Nom de la boutique',
            business_phone: 'Téléphone boutique',
            business_whatsapp_phone: 'WhatsApp boutique',
            business_address: 'Adresse',
            business_ifu: 'IFU',
            business_slogan: 'Slogan',
            business_description: 'Description de la boutique',
            notes: 'Informations complémentaires',
        };

        function fieldLabel(field) {
            return fieldLabels[field] || field;
        }

        function clearInvalidFields() {
            registrationForm?.querySelectorAll('.field-invalid').forEach((item) => {
                item.classList.remove('field-invalid');
            });
        }

        function markInvalidField(name) {
            const input = registrationForm?.querySelector(`[name="${CSS.escape(name)}"]`);
            if (!input) return;
            const wrapper = input.closest('.phone-field');
            (wrapper || input).classList.add('field-invalid');
        }

        function showRegistrationErrors(errors) {
            errorList.innerHTML = '';
            errors.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                errorList.appendChild(item);
            });
            errorModal.classList.add('open');
            errorModal.setAttribute('aria-hidden', 'false');
            errorOk.focus();
        }

        function closeRegistrationErrors() {
            errorModal.classList.remove('open');
            errorModal.setAttribute('aria-hidden', 'true');
        }

        function collectClientErrors() {
            const errors = [];
            clearInvalidFields();

            registrationForm.querySelectorAll('input, select, textarea').forEach((field) => {
                if (!field.willValidate || field.checkValidity()) return;

                markInvalidField(field.name);
                const label = fieldLabel(field.name);

                if (field.validity.valueMissing) {
                    errors.push(`${label} est obligatoire.`);
                } else if (field.validity.typeMismatch) {
                    errors.push(`${label} doit être valide.`);
                } else if (field.validity.patternMismatch) {
                    errors.push(`${label} doit contenir 8 chiffres après le préfixe 01.`);
                } else {
                    errors.push(`${label} est invalide.`);
                }
            });

            const password = registrationForm.elements.password?.value || '';
            const confirmation = registrationForm.elements.password_confirmation?.value || '';
            if (password && password.length < 8) {
                markInvalidField('password');
                errors.push('Le mot de passe doit contenir au moins 8 caractères.');
            }
            if (password && confirmation && password !== confirmation) {
                markInvalidField('password_confirmation');
                errors.push('La confirmation du mot de passe ne correspond pas.');
            }

            return [...new Set(errors)];
        }

        registrationForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const clientErrors = collectClientErrors();
            if (clientErrors.length) {
                showRegistrationErrors(clientErrors);
                return;
            }

            const submitButton = registrationForm.querySelector('[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(registrationForm.action, {
                    method: 'POST',
                    body: new FormData(registrationForm),
                    headers: { 'Accept': 'application/json' },
                });

                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    const serverErrors = [];
                    clearInvalidFields();

                    Object.entries(data.errors || {}).forEach(([field, messages]) => {
                        markInvalidField(field);
                        [].concat(messages).forEach((message) => {
                            serverErrors.push(message);
                        });
                    });

                    showRegistrationErrors(serverErrors.length ? serverErrors : ['Certaines informations sont invalides.']);
                    return;
                }

                window.location.href = response.url || '/';
            } catch (error) {
                showRegistrationErrors(['Une erreur réseau est survenue. Vérifiez votre connexion puis réessayez.']);
            } finally {
                submitButton.disabled = false;
            }
        });

        errorClose?.addEventListener('click', closeRegistrationErrors);
        errorOk?.addEventListener('click', closeRegistrationErrors);
        errorModal?.addEventListener('click', (event) => {
            if (event.target === errorModal) closeRegistrationErrors();
        });
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = registrationForm?.elements[button.dataset.passwordToggle];
                if (!input) return;

                const shouldShow = input.type === 'password';
                input.type = shouldShow ? 'text' : 'password';
                button.setAttribute('aria-label', shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                button.setAttribute('title', shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                button.querySelector('i').className = shouldShow ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeRegistrationErrors();
        });
    </script>
    <script src="/cookie-consent.js"></script>
</body>
</html>
