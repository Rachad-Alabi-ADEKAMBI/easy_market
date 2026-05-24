<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Employee;
use App\Models\AppNotification;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SalaryAdvance;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

function normalizePhoneInput(?string $value): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $value);

    if ($digits === '') {
        return null;
    }

    if (strlen($digits) === 8) {
        return '01'.$digits;
    }

    return $digits;
}

function findUserByLogin(string $login): ?User
{
    $login = trim($login);

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        return User::where('email', $login)->first();
    }

    if (preg_match('/^[\d\s\-+.]+$/', $login)) {
        $phone = normalizePhoneInput($login);

        if ($phone && preg_match('/^01\d{8}$/', $phone)) {
            return User::where('phone', $phone)->first();
        }
    }

    return User::where('username', $login)->first();
}

function requiredPhoneRule(): array
{
    return ['required', 'regex:/^01\d{8}$/'];
}

function nullablePhoneRule(): array
{
    return ['nullable', 'regex:/^01\d{8}$/'];
}

function expenseCategories(): array
{
    $categories = [
        'Autres',
        'Entretien, réparations et maintenance',
        'Eau, électricité et autres énergies',
        'Honoraires et services extérieurs',
        'Impôts, taxes et droits',
        'Locations et charges locatives',
        'Matériel et fournitures',
        'Publicité, marketing et communication',
        'Télécommunications et internet',
        'Transport et déplacements',
    ];
    sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

    return $categories;
}

function defaultProductCategory(Business $business): Category
{
    return Category::firstOrCreate([
        'business_id' => $business->id,
        'name' => 'Catégorie par défaut',
    ]);
}

function preferredBusinessForUser(User $user, ?Request $request = null): ?Business
{
    $businesses = $user->ownedBusinesses()
        ->withCount('sales')
        ->get()
        ->merge($user->businesses()->withCount('sales')->get())
        ->unique('id')
        ->values();

    if ($businesses->isEmpty()) {
        return null;
    }

    $lastBusinessId = $request?->session()->get('last_business_id');
    if ($lastBusinessId) {
        $lastBusiness = $businesses->firstWhere('id', (int) $lastBusinessId);
        if ($lastBusiness) {
            return $lastBusiness;
        }
    }

    return $businesses
        ->sort(function (Business $first, Business $second) {
            return [(int) $second->sales_count, (int) $second->id] <=> [(int) $first->sales_count, (int) $first->id];
        })
        ->first();
}

function normalizeSalaryPaymentDateInput(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^(\d{2})\/(\d{2})$/', $value, $matches)) {
        $day = (int) $matches[1];
        $month = (int) $matches[2];

        return checkdate($month, $day, 2000)
            ? sprintf('2000-%02d-%02d', $month, $day)
            : '__invalid__';
    }

    if (preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $value, $matches)) {
        $month = (int) $matches[1];
        $day = (int) $matches[2];

        return checkdate($month, $day, 2000)
            ? sprintf('2000-%02d-%02d', $month, $day)
            : '__invalid__';
    }

    return '__invalid__';
}

function salaryPaymentDayMonth($value): string
{
    if (! $value) {
        return '';
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('d/m');
    }

    try {
        return \Carbon\Carbon::parse($value)->format('d/m');
    } catch (Throwable) {
        return '';
    }
}

Route::get('/', function () {
    $user = Auth::user();
    $csrf = csrf_token();
    $actions = '<a class="btn btn-soft" href="/connexion"><i class="fa-solid fa-right-to-bracket"></i>Connexion</a>'
        .'<a class="btn btn-primary" href="/inscription"><i class="fa-solid fa-arrow-right"></i>Inscription</a>';
    $heroSignup = '<a class="btn btn-primary" href="/inscription"><i class="fa-solid fa-user-plus"></i>Inscription</a>';

    $footerAuthLinks = '<a href="/inscription"><i class="fa-solid fa-user-plus"></i>Inscription</a>'
        .'<a href="/connexion"><i class="fa-solid fa-right-to-bracket"></i>Connexion</a>';
    $pricingCtas = [
        '__HOME_PRICING_CTA_MONTHLY__' => '<a class="btn btn-dark" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir mensuel</a>',
        '__HOME_PRICING_CTA_YEARLY__' => '<a class="btn btn-primary" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir annuel</a>',
        '__HOME_PRICING_CTA_LIFETIME__' => '<a class="btn btn-dark" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir licence</a>',
    ];

    if ($user) {
        $business = preferredBusinessForUser($user, request());
        $dashboardUrl = $user->role === 'super_admin'
            ? '/admin/abonnements'
            : ($business ? '/dashboard/'.$business->id : '/');
        $logoutForm = '<form class="logout-inline" method="post" action="/deconnexion"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn btn-danger" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Déconnexion</button></form>';
        $actions = '<a class="btn btn-primary" href="'.e($dashboardUrl).'"><i class="fa-solid fa-gauge-high"></i>Tableau de bord</a>'
            .'<form class="logout-inline" method="post" action="/deconnexion"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn btn-danger" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Déconnexion</button></form>';
        $heroSignup = '';
        $footerAuthLinks = '<a href="'.e($dashboardUrl).'"><i class="fa-solid fa-gauge-high"></i>Tableau de bord</a>'.$logoutForm;
        $pricingCtas = [
            '__HOME_PRICING_CTA_MONTHLY__' => '',
            '__HOME_PRICING_CTA_YEARLY__' => '',
            '__HOME_PRICING_CTA_LIFETIME__' => '',
        ];
    }

    $homeHtml = str_replace(
        array_merge(['__HOME_AUTH_ACTIONS__', '__HOME_FOOTER_AUTH_LINKS__', '__HOME_HERO_SIGNUP__', '__CSRF_TOKEN__'], array_keys($pricingCtas)),
        array_merge([$actions, $footerAuthLinks, $heroSignup, $csrf], array_values($pricingCtas)),
        file_get_contents(resource_path('views/welcome.blade.php'))
    );

    return response($homeHtml)
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::post('/contact', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
        'email' => ['required', 'email', 'max:255'],
        'message' => ['required', 'string', 'max:3000'],
    ]);

    if ($validator->fails()) {
        return redirect('/?contact=error#contact')->withErrors($validator)->withInput();
    }

    $payload = $validator->validated();
    Mail::raw(
        "Nouveau message depuis le formulaire EasyMarket\n\n"
        ."Nom : {$payload['name']}\n"
        ."Téléphone : ".($payload['phone'] ?: '-')."\n"
        ."Email : {$payload['email']}\n\n"
        ."Message :\n{$payload['message']}",
        function ($message) use ($payload) {
            $message->to('adekambirachad@gmail.com')
                ->replyTo($payload['email'], $payload['name'])
                ->subject('Nouveau message EasyMarket');
        }
    );

    return redirect('/?contact=sent#contact');
});

Route::get('/inscription', function () {
    return response(str_replace('__CSRF_TOKEN__', csrf_token(), file_get_contents(resource_path('views/register.blade.php'))))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/conditions-generales-utilisation', function () {
    return response(file_get_contents(resource_path('views/terms.blade.php')))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/politique-confidentialite', function () {
    return response(file_get_contents(resource_path('views/privacy.blade.php')))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/connexion', function () {
    return response(str_replace(
        ['__CSRF_TOKEN__', '__LOGIN_VALUE__'],
        [csrf_token(), e(old('login', session('login_input', '')))],
        file_get_contents(resource_path('views/login.blade.php'))
    ))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/mot-de-passe-oublie', function () {
    return response(str_replace('__CSRF_TOKEN__', csrf_token(), file_get_contents(resource_path('views/forgot-password.blade.php'))))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::post('/mot-de-passe-oublie', function (Request $request) {
    $request->validate(['login' => ['required', 'string', 'max:255']]);

    $login = trim((string) $request->input('login'));
    $user = findUserByLogin($login);

    if (! $user) {
        return redirect('/mot-de-passe-oublie?envoye=1');
    }

    $token = Str::random(64);
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => Hash::make($token),
            'created_at' => now(),
        ]
    );

    return redirect('/mot-de-passe-reinitialiser?email='.urlencode($user->email).'&token='.$token);
});

Route::get('/mot-de-passe-reinitialiser', function (Request $request) {
    $html = str_replace(
        ['__CSRF_TOKEN__', '__EMAIL__', '__TOKEN__'],
        [csrf_token(), e($request->query('email', '')), e($request->query('token', ''))],
        file_get_contents(resource_path('views/reset-password.blade.php'))
    );

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::post('/mot-de-passe-reinitialiser', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'email' => ['required', 'email'],
        'token' => ['required', 'string'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    if ($validator->fails()) {
        return redirect('/mot-de-passe-reinitialiser?erreur=1');
    }

    $record = DB::table('password_reset_tokens')->where('email', $request->input('email'))->first();
    if (! $record || ! Hash::check($request->input('token'), $record->token)) {
        return redirect('/mot-de-passe-reinitialiser?erreur=1');
    }

    User::where('email', $request->input('email'))->update([
        'password' => Hash::make($request->input('password')),
    ]);
    DB::table('password_reset_tokens')->where('email', $request->input('email'))->delete();

    return redirect('/connexion?reset=1');
});

Route::post('/connexion', function (Request $request) {
    $credentials = $request->validate([
        'login' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string'],
        'remember' => ['nullable', 'boolean'],
    ]);

    $login = trim($credentials['login']);
    $user = findUserByLogin($login);

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return redirect('/connexion?erreur=1')
            ->withInput($request->only('login'))
            ->with('login_input', $login);
    }

    if (! $user->is_active) {
        $bannedEmployee = Employee::query()
            ->where('user_id', $user->id)
            ->whereNotNull('banned_at')
            ->latest('banned_at')
            ->first();

        if ($bannedEmployee) {
            return redirect('/connexion?banni=1&motif='.urlencode($bannedEmployee->ban_reason ?: 'Votre compte a été banni par l’administrateur.'))->withInput($request->only('login'));
        }

        return redirect('/connexion?inactif=1')
            ->withInput($request->only('login'))
            ->with('login_input', $login);
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    $business = preferredBusinessForUser($user, $request);

    if ($user->role === 'super_admin') {
        return redirect('/admin/abonnements');
    }

    $businessRole = $business
        ? $business->users()->where('users.id', $user->id)->first()?->pivot?->role
        : null;

    if ($business && ($user->role === 'seller' || $businessRole === 'seller')) {
        return redirect('/dashboard/'.$business->id.'/caisse');
    }

    return $business ? redirect('/dashboard/'.$business->id) : redirect('/');
});

Route::post('/deconnexion', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
});

Route::post('/inscription', function (Request $request) {
    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
        'whatsapp_phone' => normalizePhoneInput($request->input('whatsapp_phone')),
        'business_phone' => normalizePhoneInput($request->input('business_phone')),
        'business_whatsapp_phone' => normalizePhoneInput($request->input('business_whatsapp_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'civility' => ['required', 'string', 'max:10'],
        'first_name' => ['required', 'string', 'max:120'],
        'last_name' => ['required', 'string', 'max:120'],
        'phone' => requiredPhoneRule(),
        'whatsapp_phone' => nullablePhoneRule(),
        'login' => ['nullable', 'email', 'max:255'],
        'password' => ['required', 'confirmed', 'min:8'],
        'business_name' => ['required', 'string', 'max:255'],
        'business_phone' => requiredPhoneRule(),
        'business_whatsapp_phone' => nullablePhoneRule(),
        'business_address' => ['nullable', 'string', 'max:255'],
        'business_ifu' => ['nullable', 'string', 'max:255'],
        'business_slogan' => ['nullable', 'string', 'max:255'],
        'business_description' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $login = trim((string) $request->input('login'));
    $phone = trim((string) $request->input('phone'));
    $loginIsEmail = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);
    $loginIsPhone = $login === '' && (bool) preg_match('/^01\d{8}$/', $phone);

    if (! $loginIsEmail && ! $loginIsPhone) {
        return response()->json([
            'message' => 'Certaines informations sont invalides.',
            'errors' => ['phone' => ['Renseignez un email valide ou un numéro de téléphone commençant par 01 suivi de 8 chiffres.']],
        ], 422);
    }

    if ($loginIsEmail && User::where('email', $login)->exists()) {
        return response()->json([
            'message' => 'Certaines informations sont invalides.',
            'errors' => ['login' => ['Cet email est déjà utilisé.']],
        ], 422);
    }

    if (User::where('phone', $phone)->exists()) {
        return response()->json([
            'message' => 'Certaines informations sont invalides.',
            'errors' => ['phone' => ['Ce numéro de téléphone est déjà utilisé.']],
        ], 422);
    }

    $user = User::create([
        'civility' => $request->input('civility'),
        'first_name' => $request->input('first_name'),
        'last_name' => $request->input('last_name'),
        'name' => trim($request->input('first_name').' '.$request->input('last_name')),
        'phone' => $phone,
        'whatsapp_phone' => $request->input('whatsapp_phone'),
        'email' => $loginIsEmail ? $login : $phone.'@phone.easymarket.local',
        'password' => $request->input('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $business = Business::create([
        'owner_id' => $user->id,
        'name' => $request->input('business_name'),
        'phone' => $request->input('business_phone'),
        'address' => $request->input('business_address'),
        'ifu' => $request->input('business_ifu'),
        'slogan' => $request->input('business_slogan'),
        'description' => $request->input('business_description'),
        'whatsapp_phone' => $request->input('business_whatsapp_phone'),
    ]);

    $business->users()->attach($user->id, [
        'role' => 'admin',
        'can_edit_prices' => true,
    ]);

    Subscription::create([
        'business_id' => $business->id,
        'plan' => 'monthly',
        'amount' => 5000,
        'status' => 'en attente',
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect('/dashboard/'.$business->id);
});

Route::get('/dashboard/{business}/{section?}', function (Business $business, ?string $section = 'tableau-de-bord') {
    authorizeBusinessAccess($business, request());
    request()->session()->put('last_business_id', $business->id);
    $section = $section ?: 'tableau-de-bord';
    $currentUser = Auth::user();
    $currentUserPivot = $currentUser ? $business->users()->where('users.id', $currentUser->id)->first()?->pivot : null;
    $isSeller = $currentUser
        && $business->owner_id !== $currentUser->id
        && (($currentUserPivot?->role ?: $currentUser->role) === 'seller');
    $sellerSections = ['caisse', 'proforma', 'services', 'mes-ventes', 'produits', 'profil'];

    if ($isSeller && $section === 'proforma') {
        return redirect('/dashboard/'.$business->id.'/caisse');
    }

    if ($isSeller && ! in_array($section, $sellerSections, true)) {
        return redirect('/dashboard/'.$business->id.'/caisse');
    }

    $allowedSections = [
        'tableau-de-bord',
        'charges',
        'clients',
        'fournisseurs',
        'impots',
        'notifications',
        'parametres',
        'personnel',
        'rapports',
        'services',
        'stocks',
        'ventes',
        'vente-proforma',
        'caisse',
        'proforma',
        'mes-ventes',
        'produits',
        'profil',
    ];

    abort_unless(in_array($section, $allowedSections, true), 404);

    $manifestPath = public_path('build/manifest.json');
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $entry = $manifest['resources/js/app.js'];
    $css = collect($entry['css'] ?? [])
        ->map(fn ($path) => '<link rel="stylesheet" href="/build/'.$path.'">')
        ->implode('');
    $js = '<script type="module" src="/build/'.$entry['file'].'"></script>';

    return response(view('admin.pages.'.$section, [
        'businessId' => $business->id,
        'csrfToken' => csrf_token(),
        'currentUserRole' => $isSeller ? 'Vendeur' : ($currentUser?->role ?: 'Admin'),
        'css' => $css,
        'js' => $js,
        'pageTitle' => [
            'tableau-de-bord' => 'Tableau de bord',
            'charges' => 'Charges',
            'clients' => 'Clients',
            'fournisseurs' => 'Fournisseurs',
            'impots' => 'Impôts',
            'notifications' => 'Notifications',
            'parametres' => 'Paramètres',
            'personnel' => 'Personnel',
            'rapports' => 'Rapports',
            'stocks' => 'Stocks & Historique',
            'ventes' => 'Ventes',
            'vente-proforma' => 'Vente & Proforma',
            'caisse' => 'Vente & Proforma',
            'proforma' => 'Proforma',
            'mes-ventes' => 'Mes ventes',
            'produits' => 'Produits',
            'profil' => 'Profil',
        ][$section] ?? 'Tableau de bord',
        'section' => $section,
    ]))->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/api/businesses/{business}/dashboard', function (Business $business) {
    authorizeBusinessAccess($business, request());
    defaultProductCategory($business);
    $business->load('subscriptions');
    $currentUser = Auth::user();
    $currentUserPivot = $currentUser ? $business->users()->where('users.id', $currentUser->id)->first()?->pivot : null;
    $isSeller = $currentUser
        && $business->owner_id !== $currentUser->id
        && (($currentUserPivot?->role ?: $currentUser->role) === 'seller');
    $subscription = $business->subscriptions()
        ->whereIn('status', ['actif', 'active'])
        ->where(function ($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->latest('paid_at')
        ->latest()
        ->first() ?: $business->subscriptions()->latest()->first();

    return response()->json([
        'current_user' => currentUserPayload($business),
        'business' => [
            'id' => $business->id,
            'name' => $business->name,
            'phone' => $business->phone,
            'address' => $business->address,
            'ifu' => $business->ifu,
            'slogan' => $business->slogan,
            'description' => $business->description,
            'logo_path' => $business->logo_path,
            'primary_color' => $business->primary_color,
            'secondary_color' => $business->secondary_color,
            'whatsapp_phone' => $business->whatsapp_phone,
            'whatsapp_reports_enabled' => $business->whatsapp_reports_enabled,
            'whatsapp_report_time' => $business->whatsapp_report_time,
            'whatsapp_report_type' => $business->whatsapp_report_type,
            'whatsapp_report_phone' => $business->whatsapp_report_phone,
            'show_logo_on_documents' => $business->show_logo_on_documents,
            'show_ifu_on_documents' => $business->show_ifu_on_documents,
            'show_slogan_on_documents' => $business->show_slogan_on_documents,
            'show_description_on_documents' => $business->show_description_on_documents,
            'show_phone_on_documents' => $business->show_phone_on_documents,
            'show_whatsapp_on_documents' => $business->show_whatsapp_on_documents,
            'show_address_on_documents' => $business->show_address_on_documents,
        ],
        'subscription' => $subscription,
        'subscription_plans' => subscriptionPlans(),
        'summary' => [
            'products_count' => $business->products()->where('is_active', true)->count(),
            'low_stock_count' => Product::query()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->whereColumn('stock_quantity', '<=', 'alert_threshold')
                ->count(),
            'stock_value' => (int) Product::query()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as total')
                ->value('total'),
            'sales_count' => Sale::query()->where('business_id', $business->id)->count(),
            'sales_total' => (int) Sale::query()->where('business_id', $business->id)->where('status', 'completed')->sum('total'),
            'today_sales_total' => (int) Sale::query()
                ->where('business_id', $business->id)
                ->where('status', 'completed')
                ->whereDate('sold_at', now()->toDateString())
                ->sum('total'),
            'expenses_total' => (int) Expense::query()->where('business_id', $business->id)->sum('amount'),
            'monthly_expenses_total' => (int) Expense::query()
                ->where('business_id', $business->id)
                ->whereBetween('spent_on', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('amount'),
            'sellers_count' => Employee::query()
                ->where('business_id', $business->id)
                ->where('type', 'seller')
                ->count(),
        ],
        'products' => $business->products()
            ->with('category')
            ->where('is_active', true)
            ->latest()
            ->limit($isSeller ? 100 : 20)
            ->get(),
        'services' => $business->services()
            ->latest()
            ->limit($isSeller ? 100 : 50)
            ->get(),
        'sales' => Sale::query()
            ->where('business_id', $business->id)
            ->when($isSeller, fn ($query) => $query->where('seller_id', $currentUser->id))
            ->with(['items', 'customer', 'seller:id,name,username,phone', 'canceledBy:id,name'])
            ->latest()
            ->limit($isSeller ? 50 : 20)
            ->get(),
        'customers' => Customer::query()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->limit(50)
            ->get(),
        'receivables' => Receivable::query()
            ->where('business_id', $business->id)
            ->with('customer')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($receivable) => enrichReceivable($receivable)),
        'supplier_debts' => SupplierDebt::query()
            ->where('business_id', $business->id)
            ->with('supplier')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($debt) => enrichSupplierDebt($debt)),
        'suppliers' => Supplier::query()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get(),
        'expenses' => Expense::query()
            ->where('business_id', $business->id)
            ->latest('spent_on')
            ->limit(20)
            ->get(),
        'employees' => Employee::query()
            ->where('business_id', $business->id)
            ->with([
                'user:id,name,username,phone',
                'advances' => fn ($query) => $query->latest('advanced_on'),
            ])
            ->latest()
            ->limit(20)
            ->get(),
        'employee_types' => Employee::query()
            ->where('business_id', $business->id)
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values(),
        'payrolls' => Payroll::query()
            ->where('business_id', $business->id)
            ->with('employee')
            ->latest()
            ->limit(20)
            ->get(),
        'notifications' => $isSeller ? [] : notificationsForBusiness($business),
        'categories' => Category::query()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get(),
        'stock_movements' => StockMovement::query()
            ->where('business_id', $business->id)
            ->with(['product:id,name', 'user:id,name,username'])
            ->latest('moved_at')
            ->limit(200)
            ->get()
            ->map(fn ($movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason,
                'notes' => $movement->notes,
                'moved_at' => $movement->moved_at,
                'product' => $movement->product?->only(['id', 'name']),
                'user' => $movement->user?->only(['id', 'name', 'username']),
            ]),
    ]);
});

Route::post('/api/businesses/{business}/subscription-request', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);

    $request->merge([
        'deposit_phone' => normalizePhoneInput($request->input('deposit_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'plan' => ['required', 'in:monthly,yearly,lifetime'],
        'deposit_phone' => requiredPhoneRule(),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Les informations de paiement sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $plan = subscriptionPlans()[$request->input('plan')];

    $subscription = $business->subscriptions()
        ->whereIn('status', ['pending', 'en attente'])
        ->latest()
        ->first();

    if (! $subscription) {
        $subscription = new Subscription(['business_id' => $business->id]);
    }

    $subscription->fill([
        'plan' => $request->input('plan'),
        'amount' => $plan['amount'],
        'currency' => 'FCFA',
        'status' => 'en attente',
        'starts_at' => null,
        'ends_at' => plannedSubscriptionEnd($request->input('plan')),
        'paid_at' => null,
        'payment_reference' => $request->input('deposit_phone'),
        'deposit_phone' => $request->input('deposit_phone'),
    ])->save();

    return response()->json($subscription, 201);
});

Route::get('/_archive/abonnements-ancien', function () {
    requireSuperAdmin(request());

    $subscriptions = Subscription::query()
        ->with('business')
        ->latest()
        ->limit(20)
        ->get();

    $rows = $subscriptions->map(function (Subscription $subscription) {
        $action = $subscription->status === 'actif'
            ? '<span class="status ok">Actif</span>'
            : '<form method="post" action="/admin/abonnements/'.$subscription->id.'/activer"><input type="hidden" name="_token" value="'.e(csrf_token()).'"><button title="Activer" aria-label="Activer">&#10003;</button></form>';

        return '<tr>'
            .'<td>'.e($subscription->business?->name ?: 'Boutique').'</td>'
            .'<td>'.e(subscriptionPlanLabel($subscription->plan)).'</td>'
            .'<td>'.number_format($subscription->amount, 0, ',', ' ').' FCFA</td>'
            .'<td>'.e($subscription->deposit_phone ?: '-').'</td>'
            .'<td>'.e($subscription->status).'</td>'
            .'<td>'.e(optional($subscription->ends_at)->format('d/m/Y') ?: '-').'</td>'
            .'<td>'.$action.'</td>'
            .'</tr>';
    })->implode('');

    return response('<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Abonnements EasyMarket</title><style>@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap");body{font-family:"Poppins",Arial,sans-serif;margin:0;background:#f6faf8;color:#17211b}.wrap{max-width:1100px;margin:0 auto;padding:32px}h1{margin:0 0 18px}table{width:100%;border-collapse:collapse;background:white;border:1px solid #dfe7e2}th,td{padding:12px;border-bottom:1px solid #dfe7e2;text-align:left}button{border:0;border-radius:8px;background:#f5b84b;color:#10251f;font-weight:600;padding:10px 14px;cursor:pointer}.status{border-radius:999px;padding:6px 10px;font-weight:600}.ok{background:#2f7d69;color:white}</style></head><body><main class="wrap"><h1>Abonnements EasyMarket</h1><table><thead><tr><th>Boutique</th><th>Formule</th><th>Montant</th><th>Numéro dépôt</th><th>Statut</th><th>Fin prévue</th><th>Action</th></tr></thead><tbody>'.$rows.'</tbody></table></main></body></html>')
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('dashboard'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/dashboard', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('dashboard'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/abonnements', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('abonnements'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/boutiques', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('boutiques'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/utilisateurs', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('utilisateurs'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/revenus', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('revenus'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/admin/parametres', function () {
    requireSuperAdmin(request());

    return response(adminPageHtml('parametres'))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::post('/admin/parametres/mot-de-passe', function (Request $request) {
    $user = requireSuperAdmin($request);

    $validator = Validator::make($request->all(), [
        'current_password' => ['required'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    if ($validator->fails()) {
        return redirect('/admin/parametres')
            ->withErrors($validator);
    }

    if (! Hash::check($request->input('current_password'), $user->password)) {
        return redirect('/admin/parametres')
            ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
    }

    $user->update([
        'password' => $request->input('password'),
    ]);

    return redirect('/admin/parametres')->with('admin_settings_success', 'Mot de passe modifié.');
});

Route::post('/admin/abonnements/{subscription}/activer', function (Subscription $subscription) {
    requireSuperAdmin(request());

    $subscription->update([
        'status' => 'actif',
        'starts_at' => now(),
        'paid_at' => now(),
        'ends_at' => plannedSubscriptionEnd($subscription->plan, now()),
    ]);

    return redirect('/admin/abonnements');
});

Route::post('/admin/abonnements/{subscription}/suspendre', function (Subscription $subscription) {
    requireSuperAdmin(request());

    $subscription->update(['status' => 'suspendu']);

    return redirect('/admin/abonnements');
});

Route::post('/admin/abonnements/{subscription}/supprimer', function (Subscription $subscription) {
    requireSuperAdmin(request());

    $subscription->delete();

    return redirect('/admin/abonnements');
});

Route::post('/admin/utilisateurs/{user}/statut', function (User $user) {
    requireSuperAdmin(request());

    if ($user->role !== 'super_admin') {
        $user->update(['is_active' => ! $user->is_active]);
    }

    return redirect('/admin/utilisateurs');
});

Route::post('/api/businesses/{business}/notifications/read-all', function (Business $business) {
    authorizeBusinessAccess($business, request());

    AppNotification::query()
        ->where('business_id', $business->id)
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

    return response()->json(['message' => 'Notifications marquées comme lues.']);
});

Route::post('/api/businesses/{business}/notifications/{notification}/read', function (Business $business, AppNotification $notification) {
    authorizeBusinessAccess($business, request());
    abort_unless($notification->business_id === $business->id, 404);

    $notification->update(['read_at' => now()]);

    return response()->json($notification);
});

Route::post('/api/businesses/{business}/settings', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);

    $request->merge([
        'user_phone' => normalizePhoneInput($request->input('user_phone')),
        'user_whatsapp_phone' => normalizePhoneInput($request->input('user_whatsapp_phone')),
        'phone' => normalizePhoneInput($request->input('phone')),
        'whatsapp_phone' => normalizePhoneInput($request->input('whatsapp_phone')),
        'whatsapp_report_phone' => normalizePhoneInput($request->input('whatsapp_report_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'user_phone' => ['required', 'regex:/^01\d{8}$/', Rule::unique('users', 'phone')->ignore(Auth::id())],
        'user_whatsapp_phone' => nullablePhoneRule(),
        'phone' => requiredPhoneRule(),
        'whatsapp_phone' => nullablePhoneRule(),
        'whatsapp_report_phone' => ['nullable', 'required_if:whatsapp_reports_enabled,1', 'regex:/^01\d{8}$/'],
        'address' => ['nullable', 'string', 'max:255'],
        'ifu' => ['nullable', 'string', 'max:255'],
        'slogan' => ['nullable', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:1000'],
        'logo' => ['nullable', 'image', 'max:2048'],
        'remove_logo' => ['boolean'],
        'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'whatsapp_reports_enabled' => ['boolean'],
        'whatsapp_report_time' => ['nullable', 'required_if:whatsapp_reports_enabled,1', 'date_format:H:i'],
        'whatsapp_report_type' => ['nullable', 'in:global,sales,stock,receivables,supplier_debts,expenses,taxes'],
        'show_logo_on_documents' => ['boolean'],
        'show_ifu_on_documents' => ['boolean'],
        'show_slogan_on_documents' => ['boolean'],
        'show_description_on_documents' => ['boolean'],
        'show_phone_on_documents' => ['boolean'],
        'show_whatsapp_on_documents' => ['boolean'],
        'show_address_on_documents' => ['boolean'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Les paramètres de la boutique sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $settings = [
        'phone' => $request->input('phone'),
        'whatsapp_phone' => $request->input('whatsapp_phone'),
        'address' => $request->input('address'),
        'ifu' => $request->input('ifu'),
        'slogan' => $request->input('slogan'),
        'description' => $request->input('description'),
        'primary_color' => $request->input('primary_color') ?: '#2f7d69',
        'secondary_color' => $request->input('secondary_color') ?: '#f5b84b',
        'whatsapp_reports_enabled' => $request->boolean('whatsapp_reports_enabled'),
        'whatsapp_report_time' => $request->boolean('whatsapp_reports_enabled') ? $request->input('whatsapp_report_time') : null,
        'whatsapp_report_type' => $request->input('whatsapp_report_type') ?: 'global',
        'whatsapp_report_phone' => $request->boolean('whatsapp_reports_enabled') ? $request->input('whatsapp_report_phone') : null,
        'show_logo_on_documents' => $request->boolean('show_logo_on_documents'),
        'show_ifu_on_documents' => $request->boolean('show_ifu_on_documents'),
        'show_slogan_on_documents' => $request->boolean('show_slogan_on_documents'),
        'show_description_on_documents' => $request->boolean('show_description_on_documents'),
        'show_phone_on_documents' => $request->boolean('show_phone_on_documents'),
        'show_whatsapp_on_documents' => $request->boolean('show_whatsapp_on_documents'),
        'show_address_on_documents' => $request->boolean('show_address_on_documents'),
    ];

    if ($user = Auth::user()) {
        $user->update([
            'phone' => $request->input('user_phone'),
            'whatsapp_phone' => $request->input('user_whatsapp_phone'),
        ]);
    }

    if ($request->boolean('remove_logo')) {
        if ($business->logo_path && ! Str::startsWith((string) $business->logo_path, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($business->logo_path);
        }
        $settings['logo_path'] = null;
        $settings['show_logo_on_documents'] = false;
    }

    if ($request->hasFile('logo')) {
        if ($business->logo_path && ! Str::startsWith((string) $business->logo_path, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($business->logo_path);
        }
        $settings['logo_path'] = $request->file('logo')->store('business-logos', 'public');
        $settings['show_logo_on_documents'] = true;
    }

    $business->update($settings);

    return response()->json($business);
});

Route::post('/api/businesses/{business}/profile/password', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);

    $user = Auth::user();
    abort_unless($user, 403);

    $validator = Validator::make($request->all(), [
        'current_password' => ['required', 'string'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Les informations du mot de passe sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    if (! Hash::check($request->input('current_password'), $user->password)) {
        return response()->json([
            'message' => 'Les informations du mot de passe sont invalides.',
            'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']],
        ], 422);
    }

    $user->update([
        'password' => Hash::make($request->input('password')),
    ]);

    return response()->json(['message' => 'Mot de passe modifié.']);
});

Route::post('/api/businesses/{business}/employees', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
        'salary_payment_date' => normalizeSalaryPaymentDateInput($request->input('salary_payment_date')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => [...nullablePhoneRule(), Rule::unique('users', 'phone')->whereNotNull('phone')],
        'username' => ['nullable', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->whereNotNull('username')],
        'password' => ['nullable', 'string', 'min:8'],
        'type' => ['required', 'string', 'max:30'],
        'salary' => ['required', 'integer', 'min:0'],
        'salary_payment_date' => ['nullable', 'date_format:Y-m-d'],
        'hired_at' => ['nullable', 'date'],
    ]);

    $validator->sometimes('phone', requiredPhoneRule(), fn ($input) => $input->type === 'seller');
    $validator->sometimes('username', ['required'], fn ($input) => $input->type === 'seller');
    $validator->sometimes('password', ['required'], fn ($input) => $input->type === 'seller');

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La fiche employé est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = null;
    if ($request->input('type') === 'seller') {
        $phone = trim((string) $request->input('phone'));
        $user = User::create([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'phone' => $phone,
            'email' => $request->input('username').'@seller.easymarket.local',
            'password' => $request->input('password'),
            'role' => 'seller',
            'can_edit_prices' => false,
            'is_active' => true,
        ]);

        $business->users()->syncWithoutDetaching([
            $user->id => [
                'role' => 'seller',
                'can_edit_prices' => false,
            ],
        ]);
    }

    $employee = Employee::create([
        'business_id' => $business->id,
        'user_id' => $user?->id,
        'name' => $request->input('name'),
        'position' => employeeTypeLabel($request->input('type')),
        'type' => $request->input('type'),
        'salary' => $request->integer('salary'),
        'salary_payment_date' => $request->input('salary_payment_date'),
        'hired_at' => $request->input('hired_at'),
        'is_active' => true,
    ]);

    return response()->json($employee->load('user'), 201);
});

Route::put('/api/businesses/{business}/employees/{employee}', function (Request $request, Business $business, Employee $employee) {
    authorizeBusinessAccess($business, $request);
    abort_unless($employee->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
        'salary_payment_date' => normalizeSalaryPaymentDateInput($request->input('salary_payment_date')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => [...nullablePhoneRule(), Rule::unique('users', 'phone')->whereNotNull('phone')->ignore($employee->user_id)],
        'username' => ['nullable', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->whereNotNull('username')->ignore($employee->user_id)],
        'type' => ['required', 'string', 'max:30'],
        'salary' => ['required', 'integer', 'min:0'],
        'salary_payment_date' => ['nullable', 'date_format:Y-m-d'],
        'hired_at' => ['nullable', 'date'],
    ]);

    $validator->sometimes('phone', requiredPhoneRule(), fn ($input) => $input->type === 'seller');
    $validator->sometimes('username', ['required'], fn ($input) => $input->type === 'seller');

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La fiche employé est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $wasSeller = $employee->type === 'seller';
    $willBeSeller = $request->input('type') === 'seller';

    $employee->update([
        'name' => $request->input('name'),
        'position' => employeeTypeLabel($request->input('type')),
        'type' => $request->input('type'),
        'salary' => $request->integer('salary'),
        'salary_payment_date' => $request->input('salary_payment_date'),
        'hired_at' => $request->input('hired_at'),
    ]);

    if ($employee->user) {
        $updates = ['name' => $request->input('name')];

        if ($willBeSeller) {
            $updates['phone'] = normalizePhoneInput($request->input('phone'));
            $updates['username'] = $request->input('username');
        }

        if ($wasSeller && ! $willBeSeller) {
            $updates['is_active'] = false;
        }

        $employee->user->update($updates);
    }

    return response()->json($employee->fresh('user'));
});

Route::post('/api/businesses/{business}/employees/{employee}/ban', function (Request $request, Business $business, Employee $employee) {
    authorizeBusinessAccess($business, $request);
    abort_unless($employee->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'reason' => ['required', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le motif du bannissement est obligatoire.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $employee->update([
        'is_active' => false,
        'banned_at' => now(),
        'ban_reason' => $request->input('reason'),
    ]);

    if ($employee->user) {
        $employee->user->update(['is_active' => false]);
    }

    return response()->json($employee->fresh('user'));
});

Route::post('/api/businesses/{business}/employees/{employee}/advances', function (Request $request, Business $business, Employee $employee) {
    authorizeBusinessAccess($business, $request);
    abort_unless($employee->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
        'advanced_on' => ['required', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => "L'avance sur salaire est invalide.",
            'errors' => $validator->errors(),
        ], 422);
    }

    $monthStart = \Carbon\Carbon::parse($request->input('advanced_on'))->startOfMonth()->toDateString();
    $monthEnd = \Carbon\Carbon::parse($request->input('advanced_on'))->endOfMonth()->toDateString();
    $monthlyAdvanceTotal = (int) SalaryAdvance::query()
        ->where('business_id', $business->id)
        ->where('employee_id', $employee->id)
        ->whereBetween('advanced_on', [$monthStart, $monthEnd])
        ->sum('amount');

    if ($monthlyAdvanceTotal + $request->integer('amount') > (int) $employee->salary) {
        return response()->json([
            'message' => "Le total des avances du mois ne peut pas dépasser le salaire mensuel de l'employé.",
        ], 422);
    }

    $advance = SalaryAdvance::create([
        'business_id' => $business->id,
        'employee_id' => $employee->id,
        'amount' => $request->integer('amount'),
        'advanced_on' => $request->input('advanced_on'),
        'notes' => $request->input('notes'),
    ]);

    AppNotification::create([
        'business_id' => $business->id,
        'type' => 'salary_advance',
        'title' => 'Avance sur salaire enregistrée',
        'message' => sprintf(
            '%s a reçu une avance de %s FCFA.',
            $employee->name,
            number_format($advance->amount, 0, ',', ' ')
        ),
        'channel' => 'in_app',
    ]);

    return response()->json($advance, 201);
});

Route::put('/api/businesses/{business}/salary-advances/{advance}', function (Request $request, Business $business, SalaryAdvance $advance) {
    authorizeBusinessAccess($business, $request);
    abort_unless($advance->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
        'advanced_on' => ['required', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => "L'avance sur salaire est invalide.",
            'errors' => $validator->errors(),
        ], 422);
    }

    $employee = Employee::query()
        ->where('business_id', $business->id)
        ->findOrFail($advance->employee_id);
    $monthStart = \Carbon\Carbon::parse($request->input('advanced_on'))->startOfMonth()->toDateString();
    $monthEnd = \Carbon\Carbon::parse($request->input('advanced_on'))->endOfMonth()->toDateString();
    $monthlyAdvanceTotal = (int) SalaryAdvance::query()
        ->where('business_id', $business->id)
        ->where('employee_id', $employee->id)
        ->where('id', '!=', $advance->id)
        ->whereBetween('advanced_on', [$monthStart, $monthEnd])
        ->sum('amount');

    if ($monthlyAdvanceTotal + $request->integer('amount') > (int) $employee->salary) {
        return response()->json([
            'message' => "Le total des avances du mois ne peut pas dépasser le salaire mensuel de l'employé.",
        ], 422);
    }

    $advance->update([
        'amount' => $request->integer('amount'),
        'advanced_on' => $request->input('advanced_on'),
        'notes' => $request->input('notes'),
    ]);

    return response()->json($advance->fresh());
});

Route::delete('/api/businesses/{business}/salary-advances/{advance}', function (Request $request, Business $business, SalaryAdvance $advance) {
    authorizeBusinessAccess($business, $request);
    abort_unless($advance->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $advance->delete();

    return response()->json(['message' => 'Avance supprimée.']);
});

Route::post('/api/businesses/{business}/payrolls', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'employee_id' => ['required', 'integer', 'exists:employees,id'],
        'period' => ['required', 'date_format:Y-m'],
        'paid_amount' => ['nullable', 'integer', 'min:0'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La paie est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $employee = Employee::query()
        ->where('business_id', $business->id)
        ->findOrFail($request->integer('employee_id'));

    $period = $request->input('period');
    $advanceTotal = SalaryAdvance::query()
        ->where('business_id', $business->id)
        ->where('employee_id', $employee->id)
        ->whereBetween('advanced_on', [$period.'-01', date('Y-m-t', strtotime($period.'-01'))])
        ->sum('amount');

    $gross = $employee->salary;
    $estimatedNet = max(0, $gross - $advanceTotal);
    $net = $request->filled('paid_amount') ? $request->integer('paid_amount') : $estimatedNet;

    if ($net > $estimatedNet) {
        return response()->json([
            'message' => 'Le salaire payé ne peut pas dépasser le salaire net estimé après déduction des avances.',
        ], 422);
    }

    $payroll = Payroll::updateOrCreate(
        [
            'business_id' => $business->id,
            'employee_id' => $employee->id,
            'period' => $period,
        ],
        [
            'gross_salary' => $gross,
            'salary_advance' => $advanceTotal,
            'net_salary' => $net,
            'status' => 'paid',
            'paid_at' => now(),
        ]
    );

    return response()->json($payroll->load('employee'), 201);
});

Route::post('/api/businesses/{business}/expenses', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category' => ['required', 'string', Rule::in(expenseCategories())],
        'type' => ['nullable', 'in:fixed,variable'],
        'amount' => ['required', 'integer', 'min:1'],
        'spent_on' => ['required', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La charge est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $expense = Expense::create([
        'business_id' => $business->id,
        'name' => $request->input('name'),
        'category' => $request->input('category'),
        'type' => $request->input('type', 'variable'),
        'amount' => $request->integer('amount'),
        'spent_on' => $request->input('spent_on'),
        'notes' => $request->input('notes'),
    ]);

    return response()->json($expense, 201);
});

Route::put('/api/businesses/{business}/expenses/{expense}', function (Request $request, Business $business, Expense $expense) {
    authorizeBusinessAccess($business, $request);
    abort_unless($expense->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category' => ['required', 'string', Rule::in(expenseCategories())],
        'type' => ['nullable', 'in:fixed,variable'],
        'amount' => ['required', 'integer', 'min:1'],
        'spent_on' => ['required', 'date'],
        'notes' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La charge est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $expense->update([
        'name' => $request->input('name'),
        'category' => $request->input('category'),
        'type' => $request->input('type', 'variable'),
        'amount' => $request->integer('amount'),
        'spent_on' => $request->input('spent_on'),
        'notes' => $request->input('notes'),
    ]);

    return response()->json($expense->fresh());
});

Route::delete('/api/businesses/{business}/expenses/{expense}', function (Request $request, Business $business, Expense $expense) {
    authorizeBusinessAccess($business, $request);
    abort_unless($expense->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $expense->delete();

    return response()->json(['message' => 'Charge supprimée.']);
});

Route::post('/api/businesses/{business}/suppliers', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => requiredPhoneRule(),
        'payment_terms' => ['nullable', 'string', 'max:255'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $supplier = Supplier::updateOrCreate(
        [
            'business_id' => $business->id,
            'name' => $request->input('name'),
        ],
        [
            'phone' => $request->input('phone'),
            'payment_terms' => $request->input('payment_terms'),
            'notes' => $request->input('notes'),
        ]
    );

    return response()->json($supplier, 201);
});

Route::put('/api/businesses/{business}/suppliers/{supplier}', function (Request $request, Business $business, Supplier $supplier) {
    authorizeBusinessAccess($business, $request);
    abort_unless($supplier->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'phone' => requiredPhoneRule(),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le téléphone du fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $supplier->update([
        'phone' => $request->input('phone'),
    ]);

    return response()->json($supplier);
});

Route::post('/api/businesses/{business}/supplier-debts', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'supplier_phone' => normalizePhoneInput($request->input('supplier_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        'supplier_name' => ['required_without:supplier_id', 'nullable', 'string', 'max:255'],
        'supplier_phone' => ['required_without:supplier_id', ...nullablePhoneRule()],
        'amount_due' => ['required', 'integer', 'min:1'],
        'due_date' => ['nullable', 'date'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La dette fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $supplier = $request->filled('supplier_id')
        ? Supplier::query()->where('business_id', $business->id)->findOrFail($request->integer('supplier_id'))
        : Supplier::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => $request->input('supplier_name'),
            ],
            [
                'phone' => $request->input('supplier_phone'),
            ]
        );

    $debt = SupplierDebt::create([
        'business_id' => $business->id,
        'supplier_id' => $supplier->id,
        'amount_due' => $request->integer('amount_due'),
        'amount_paid' => 0,
        'due_date' => $request->input('due_date'),
        'notes' => $request->input('notes'),
        'status' => 'current',
    ]);

    return response()->json(enrichSupplierDebt($debt->load('supplier')), 201);
});

Route::put('/api/businesses/{business}/supplier-debts/{debt}', function (Request $request, Business $business, SupplierDebt $debt) {
    authorizeBusinessAccess($business, $request);
    abort_unless($debt->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount_due' => ['required', 'integer', 'min:1'],
    ]);

    $validator->after(function ($validator) use ($request, $debt) {
        if ($request->integer('amount_due') < (int) $debt->amount_paid) {
            $validator->errors()->add('amount_due', 'Le montant de la dette ne peut pas être inférieur au montant déjà payé.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le montant de la dette fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $debt->forceFill(['amount_due' => $request->integer('amount_due')]);
    $debt->status = supplierDebtStatus($debt);
    $debt->save();

    return response()->json(enrichSupplierDebt($debt->fresh('supplier')));
});

Route::post('/api/businesses/{business}/customers', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => nullablePhoneRule(),
        'email' => ['nullable', 'email', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le client est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $customer = Customer::create([
        'business_id' => $business->id,
        'name' => $request->input('name'),
        'phone' => $request->input('phone'),
        'email' => $request->input('email'),
        'address' => $request->input('address'),
    ]);

    return response()->json($customer, 201);
});

Route::put('/api/businesses/{business}/customers/{customer}', function (Request $request, Business $business, Customer $customer) {
    authorizeBusinessAccess($business, $request);
    abort_unless($customer->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'phone' => requiredPhoneRule(),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le téléphone du client est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $customer->update([
        'phone' => $request->input('phone'),
    ]);

    return response()->json($customer);
});

Route::post('/api/businesses/{business}/receivables', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'customer_phone' => normalizePhoneInput($request->input('customer_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
        'customer_phone' => nullablePhoneRule(),
        'amount_due' => ['required', 'integer', 'min:1'],
        'due_date' => ['nullable', 'date'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La créance est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $customer = $request->filled('customer_id')
        ? Customer::query()->where('business_id', $business->id)->findOrFail($request->integer('customer_id'))
        : Customer::create([
            'business_id' => $business->id,
            'name' => $request->input('customer_name') ?: 'Client crédit',
            'phone' => $request->input('customer_phone'),
        ]);

    $receivable = Receivable::create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
        'amount_due' => $request->integer('amount_due'),
        'amount_paid' => 0,
        'due_date' => $request->input('due_date'),
        'notes' => $request->input('notes'),
        'status' => 'current',
    ]);

    return response()->json(enrichReceivable($receivable->load('customer')), 201);
});

Route::put('/api/businesses/{business}/receivables/{receivable}', function (Request $request, Business $business, Receivable $receivable) {
    authorizeBusinessAccess($business, $request);
    abort_unless($receivable->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount_due' => ['required', 'integer', 'min:1'],
    ]);

    $validator->after(function ($validator) use ($request, $receivable) {
        if ($request->integer('amount_due') < (int) $receivable->amount_paid) {
            $validator->errors()->add('amount_due', 'Le montant de la créance ne peut pas être inférieur au montant déjà payé.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le montant de la créance est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $receivable->forceFill(['amount_due' => $request->integer('amount_due')]);
    $receivable->status = receivableStatus($receivable);
    $receivable->save();

    return response()->json(enrichReceivable($receivable->fresh('customer')));
});

Route::post('/api/businesses/{business}/supplier-debts/{debt}/payments', function (Request $request, Business $business, SupplierDebt $debt) {
    authorizeBusinessAccess($business, $request);
    abort_unless($debt->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
        'method' => ['required', 'in:cash,mobile_money'],
        'reference' => ['nullable', 'string', 'max:255'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le paiement fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $remaining = max(0, $debt->amount_due - $debt->amount_paid);
    $amount = min($request->integer('amount'), $remaining);

    $debt->increment('amount_paid', $amount);
    $debt->refresh();
    $debt->update(['status' => supplierDebtStatus($debt)]);

    Payment::create([
        'business_id' => $business->id,
        'supplier_debt_id' => $debt->id,
        'type' => 'supplier_debt',
        'method' => $request->input('method'),
        'amount' => $amount,
        'reference' => $request->input('reference'),
        'paid_at' => now(),
    ]);

    return response()->json(enrichSupplierDebt($debt->load('supplier')));
});

Route::put('/api/businesses/{business}/supplier-debts/{debt}/payments/{payment}', function (Request $request, Business $business, SupplierDebt $debt, Payment $payment) {
    authorizeBusinessAccess($business, $request);
    abort_unless($debt->business_id === $business->id, 404);
    abort_unless($payment->business_id === $business->id && $payment->supplier_debt_id === $debt->id && $payment->type === 'supplier_debt', 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
    ]);

    $otherPaymentsTotal = Payment::query()
        ->where('business_id', $business->id)
        ->where('supplier_debt_id', $debt->id)
        ->where('type', 'supplier_debt')
        ->whereKeyNot($payment->id)
        ->sum('amount');

    $validator->after(function ($validator) use ($request, $debt, $otherPaymentsTotal) {
        if ($request->integer('amount') + $otherPaymentsTotal > (int) $debt->amount_due) {
            $validator->errors()->add('amount', 'Le total des paiements ne peut pas dépasser le montant de la dette.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le montant du paiement fournisseur est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    DB::transaction(function () use ($request, $debt, $payment) {
        $payment->update(['amount' => $request->integer('amount')]);

        $amountPaid = Payment::query()
            ->where('business_id', $debt->business_id)
            ->where('supplier_debt_id', $debt->id)
            ->where('type', 'supplier_debt')
            ->sum('amount');

        $debt->forceFill(['amount_paid' => $amountPaid]);
        $debt->status = supplierDebtStatus($debt);
        $debt->save();
    });

    return response()->json(enrichSupplierDebt($debt->fresh('supplier')));
});

Route::delete('/api/businesses/{business}/supplier-debts/{debt}', function (Request $request, Business $business, SupplierDebt $debt) {
    authorizeBusinessAccess($business, $request);
    abort_unless($debt->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    Payment::query()
        ->where('business_id', $business->id)
        ->where('supplier_debt_id', $debt->id)
        ->update(['supplier_debt_id' => null]);

    $debt->delete();

    return response()->json(['message' => 'Dette fournisseur supprimée.']);
});

Route::post('/api/businesses/{business}/receivables/{receivable}/payments', function (Request $request, Business $business, Receivable $receivable) {
    authorizeBusinessAccess($business, $request);
    abort_unless($receivable->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
        'method' => ['required', 'in:cash,mobile_money'],
        'reference' => ['nullable', 'string', 'max:255'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le paiement de créance est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $remaining = max(0, $receivable->amount_due - $receivable->amount_paid);
    $amount = min($request->integer('amount'), $remaining);

    $receivable->increment('amount_paid', $amount);
    $receivable->refresh();
    $receivable->update([
        'status' => receivableStatus($receivable),
    ]);

    Payment::create([
        'business_id' => $business->id,
        'receivable_id' => $receivable->id,
        'type' => 'receivable',
        'method' => $request->input('method'),
        'amount' => $amount,
        'reference' => $request->input('reference'),
        'paid_at' => now(),
    ]);

    return response()->json(enrichReceivable($receivable->load('customer')));
});

Route::put('/api/businesses/{business}/receivables/{receivable}/payments/{payment}', function (Request $request, Business $business, Receivable $receivable, Payment $payment) {
    authorizeBusinessAccess($business, $request);
    abort_unless($receivable->business_id === $business->id, 404);
    abort_unless($payment->business_id === $business->id && $payment->receivable_id === $receivable->id && $payment->type === 'receivable', 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'amount' => ['required', 'integer', 'min:1'],
    ]);

    $otherPaymentsTotal = Payment::query()
        ->where('business_id', $business->id)
        ->where('receivable_id', $receivable->id)
        ->where('type', 'receivable')
        ->whereKeyNot($payment->id)
        ->sum('amount');

    $validator->after(function ($validator) use ($request, $receivable, $otherPaymentsTotal) {
        if ($request->integer('amount') + $otherPaymentsTotal > (int) $receivable->amount_due) {
            $validator->errors()->add('amount', 'Le total des paiements ne peut pas dépasser le montant de la créance.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le montant du paiement est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    DB::transaction(function () use ($request, $receivable, $payment) {
        $payment->update(['amount' => $request->integer('amount')]);

        $amountPaid = Payment::query()
            ->where('business_id', $receivable->business_id)
            ->where('receivable_id', $receivable->id)
            ->where('type', 'receivable')
            ->sum('amount');

        $receivable->forceFill(['amount_paid' => $amountPaid]);
        $receivable->status = receivableStatus($receivable);
        $receivable->save();
    });

    return response()->json(enrichReceivable($receivable->fresh('customer')));
});

Route::delete('/api/businesses/{business}/receivables/{receivable}', function (Request $request, Business $business, Receivable $receivable) {
    authorizeBusinessAccess($business, $request);
    abort_unless($receivable->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    Payment::query()
        ->where('business_id', $business->id)
        ->where('receivable_id', $receivable->id)
        ->update(['receivable_id' => null]);

    $receivable->delete();

    return response()->json(['message' => 'Créance supprimée.']);
});

Route::get('/businesses/{business}/sales/{sale}/invoice', function (Business $business, Sale $sale) {
    authorizeBusinessAccess($business, request());
    abort_unless($sale->business_id === $business->id, 404);

    return response(renderSaleInvoiceHtml($business, $sale))->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/factures/{sale}/telecharger', function (Sale $sale) {
    $business = Business::query()->findOrFail($sale->business_id);

    return response(renderSaleInvoiceHtml($business, $sale))->header('Content-Type', 'text/html; charset=UTF-8');
})->middleware('signed')->name('public.sales.invoice');

function salePublicInvoiceUrl(Sale $sale): string
{
    return URL::temporarySignedRoute('public.sales.invoice', now()->addDays(30), ['sale' => $sale->id]);
}

function renderSaleInvoiceHtml(Business $business, Sale $sale): string
{
    $sale->load(['items', 'seller']);
    $business->load('owner');

    $payload = urlencode(json_encode([
        'business_id' => $business->id,
        'sale_id' => $sale->id,
        'number' => $sale->number,
        'total' => $sale->total,
    ]));
    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=112x112&margin=1&data='.$payload;
    $documentTitle = $sale->type === 'proforma' ? 'Facture pro forma' : 'Facture';
    $branding = documentBranding($business, [
        'logo_img_class' => 'brand-logo-img',
        'fallback_logo_class' => 'logo',
        'details_class' => 'brand-details',
    ]);
    $seller = $sale->seller;
    $sellerPivot = $seller ? $business->users()->where('users.id', $seller->id)->first()?->pivot : null;
    $sellerIsVendor = $seller && ($seller->role === 'seller' || $sellerPivot?->role === 'seller');
    $sellerDisplayName = $sellerIsVendor
        ? ($seller->username ?: $seller->name)
        : Str::of((string) ($seller?->name ?: 'Admin'))->trim()->explode(' ')->first();
    $sellerMeta = '<div class="meta-card"><strong><i class="fa-solid fa-user-tie"></i>Vendeur</strong><br><span class="muted">'.e($sellerDisplayName).'</span></div>';
    $paymentMeta = '<section class="meta">'
        .$sellerMeta
        .($sale->type === 'proforma' ? '' : '<div class="meta-card"><strong><i class="fa-solid fa-wallet"></i>Mode de paiement</strong><br><span class="muted">'.e(paymentLabel($sale->payment_method)).'</span></div>')
        .'</section>';

    return str_replace(
        ['__BUSINESS_HEADER__', '__DOCUMENT_TITLE__', '__SALE_NUMBER__', '__SALE_DATE__', '__PAYMENT_META__', '__SUBTOTAL__', '__DISCOUNT__', '__TOTAL__', '__ITEM_ROWS__', '__QR_IMAGE__'],
        [
            $branding['header_html'],
            e($documentTitle),
            e($sale->number),
            e(optional($sale->sold_at)->format('d/m/Y H:i') ?: $sale->created_at->format('d/m/Y H:i')),
            $paymentMeta,
            number_format($sale->subtotal, 0, ',', ' ').' FCFA',
            number_format($sale->discount, 0, ',', ' ').' FCFA',
            number_format($sale->total, 0, ',', ' ').' FCFA',
            $sale->items->map(fn ($item) => '<tr><td class="col-product">'.e($item->product_name).'</td><td class="col-qty">'.e(rtrim(rtrim(number_format((float) $item->quantity, 2, ',', ' '), '0'), ',')).'</td><td class="col-price amount">'.number_format($item->unit_price, 0, ',', ' ').' FCFA</td><td class="col-total amount">'.number_format($item->total, 0, ',', ' ').' FCFA</td></tr>')->implode(''),
            e($qrImage),
        ],
        file_get_contents(resource_path('views/invoice.blade.php'))
    );
}

Route::get('/businesses/{business}/reports/{period}', function (Business $business, string $period) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'], true), 404);

    $range = reportRange($period, request());
    $data = reportData($business, $range[0], $range[1], $period);
    $branding = documentBranding($business);

    $rows = collect($data['top_products'])->map(fn ($item) => '<tr><td>'.e($item->product_name).'</td><td>'.e($item->quantity).'</td><td>'.number_format($item->total, 0, ',', ' ').' FCFA</td></tr>')->implode('');

    $html = str_replace(
        ['__PRIMARY_COLOR__', '__SECONDARY_COLOR__', '__BUSINESS_HEADER__', '__BUSINESS__', '__PERIOD_LABEL__', '__RANGE__', '__SALES_TOTAL__', '__EXPENSES_TOTAL__', '__NET_RESULT__', '__RECEIVABLES__', '__DEBTS__', '__PAYROLLS__', '__TOP_PRODUCTS__'],
        [
            e($branding['primary_color']),
            e($branding['secondary_color']),
            $branding['header_html'],
            e($business->name),
            e($data['period_label']),
            e($data['range_label']),
            number_format($data['sales_total'], 0, ',', ' ').' FCFA',
            number_format($data['expenses_total'], 0, ',', ' ').' FCFA',
            number_format($data['net_result'], 0, ',', ' ').' FCFA',
            number_format($data['receivables_remaining'], 0, ',', ' ').' FCFA',
            number_format($data['debts_remaining'], 0, ',', ' ').' FCFA',
            number_format($data['payrolls_total'], 0, ',', ' ').' FCFA',
            $rows ?: '<tr><td colspan="3">Aucun produit vendu sur cette période.</td></tr>',
        ],
        file_get_contents(resource_path('views/report.blade.php'))
    );

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/businesses/{business}/reports/{period}/export', function (Business $business, string $period) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'], true), 404);

    $range = reportRange($period, request());
    $data = reportData($business, $range[0], $range[1], $period);
    $rows = [
        ...documentCsvHeaderRows($business),
        ['Période', $data['period_label']],
        ['Dates', $data['range_label']],
        ['Ventes', $data['sales_total']],
        ['Charges', $data['expenses_total']],
        ['Paies', $data['payrolls_total']],
        ['Résultat net', $data['net_result']],
        ['Créances restantes', $data['receivables_remaining']],
        ['Dettes fournisseurs', $data['debts_remaining']],
        [],
        ['Top produits', 'Quantité', 'Total'],
    ];

    foreach ($data['top_products'] as $product) {
        $rows[] = [$product->product_name, $product->quantity, $product->total];
    }

    return csvResponse('rapport-'.$period.'.csv', ['Indicateur', 'Valeur', 'Montant'], $rows);
});

Route::get('/api/businesses/{business}/reports/{period}', function (Business $business, string $period) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'], true), 404);

    $range = reportRange($period, request());

    return response()->json(reportData($business, $range[0], $range[1], $period));
});

Route::get('/businesses/{business}/taxes/statement', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $period = taxRequestPeriod(request());
    $range = reportRange($period, request());
    $data = reportData($business, $range[0], $range[1], $period);
    $branding = documentBranding($business);
    $paymentLabels = [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'credit' => 'Crédit',
    ];
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ').' FCFA';
    $salesByPaymentRows = collect($data['sales_by_payment'])->map(fn ($item) => '<tr><td>'.e($paymentLabels[$item->payment_method] ?? $item->payment_method).'</td><td>'.number_format($item->count, 0, ',', ' ').'</td><td>'.$money($item->total).'</td></tr>')->implode('')
        ?: '<tr><td colspan="3">Aucune vente encaissée sur la période.</td></tr>';
    $expensesByCategoryRows = collect($data['expenses_by_category'])->map(fn ($item) => '<tr><td>'.e($item->category).'</td><td>'.number_format($item->count, 0, ',', ' ').'</td><td>'.$money($item->total).'</td></tr>')->implode('')
        ?: '<tr><td colspan="3">Aucune charge sur la période.</td></tr>';
    $stockByCategoryRows = collect($data['stock_by_category'])->map(fn ($item) => '<tr><td>'.e($item->category).'</td><td>'.number_format($item->products_count, 0, ',', ' ').'</td><td>'.number_format((float) $item->stock_quantity, 2, ',', ' ').'</td><td>'.$money($item->stock_value).'</td></tr>')->implode('')
        ?: '<tr><td colspan="4">Aucun stock valorisé.</td></tr>';
    $topProductsRows = collect($data['top_products'])->map(fn ($item) => '<tr><td>'.e($item->product_name).'</td><td>'.number_format((float) $item->quantity, 2, ',', ' ').'</td><td>'.$money($item->total).'</td></tr>')->implode('')
        ?: '<tr><td colspan="3">Aucune vente produit sur la période.</td></tr>';
    $fullTaxDocuments = request()->boolean('full') ? fullTaxDocumentsHtml($business, $range[0], $range[1], $money) : '';

    $html = str_replace(
        ['__PRIMARY_COLOR__', '__SECONDARY_COLOR__', '__BUSINESS_HEADER__', '__BUSINESS__', '__PERIOD__', '__SALES__', '__COGS__', '__GROSS_MARGIN__', '__EXPENSES__', '__PAYROLLS_GROSS__', '__SALARY_ADVANCES__', '__PAYROLLS__', '__NET__', '__RECEIVABLES__', '__DEBTS__', '__STOCK_VALUE__', '__ASSETS_TOTAL__', '__EQUITY_ESTIMATE__', '__PRODUCTS_COUNT__', '__LOW_STOCK_COUNT__', '__RECEIVABLES_COUNT__', '__DEBTS_COUNT__', '__SALES_BY_PAYMENT_ROWS__', '__EXPENSES_BY_CATEGORY_ROWS__', '__STOCK_BY_CATEGORY_ROWS__', '__TOP_PRODUCTS_ROWS__', '__FULL_TAX_DOCUMENTS__'],
        [
            e($branding['primary_color']),
            e($branding['secondary_color']),
            $branding['header_html'],
            e($business->name),
            e($data['range_label']),
            $money($data['sales_total']),
            $money($data['cost_of_goods_sold']),
            $money($data['gross_margin']),
            $money($data['expenses_total']),
            $money($data['payrolls_gross_total']),
            $money($data['salary_advances_total']),
            $money($data['payrolls_total']),
            $money($data['net_result']),
            $money($data['receivables_remaining']),
            $money($data['debts_remaining']),
            $money($data['stock_value']),
            $money($data['balance_assets_total']),
            $money($data['balance_equity_estimate']),
            number_format($data['products_count'], 0, ',', ' '),
            number_format($data['low_stock_count'], 0, ',', ' '),
            number_format($data['receivables_count'], 0, ',', ' '),
            number_format($data['debts_count'], 0, ',', ' '),
            $salesByPaymentRows,
            $expensesByCategoryRows,
            $stockByCategoryRows,
            $topProductsRows,
            $fullTaxDocuments,
        ],
        file_get_contents(resource_path('views/tax-statement.blade.php'))
    );

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/businesses/{business}/taxes/statement/export', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $period = taxRequestPeriod(request());
    $range = reportRange($period, request());
    $data = reportData($business, $range[0], $range[1], $period);
    $rows = [
        ...documentCsvHeaderRows($business),
        ['Avertissement', 'Document de travail EasyMarket non légal, à faire valider par un comptable ou l’administration compétente.'],
        ['Période', $data['range_label']],
        ['Recettes', $data['sales_total']],
        ['Coût d’achat des produits vendus', $data['cost_of_goods_sold']],
        ['Marge brute estimée', $data['gross_margin']],
        ['Dépenses', $data['expenses_total']],
        ['Salaires bruts générés', $data['payrolls_gross_total']],
        ['Avances sur salaire remises', $data['salary_advances_total']],
        ['Paies nettes', $data['payrolls_total']],
        ['Résultat net estimé', $data['net_result']],
        ['Créances clients', $data['receivables_remaining']],
        ['Dettes fournisseurs', $data['debts_remaining']],
        [],
        ['ACTIF'],
        ['Stock valorisé au coût d’achat', $data['stock_value']],
        ['Créances clients à encaisser', $data['receivables_remaining']],
        ['Total actif estimé', $data['balance_assets_total']],
        [],
        ['PASSIF ET SITUATION NETTE'],
        ['Dettes fournisseurs à payer', $data['debts_remaining']],
        ['Situation nette estimée', $data['balance_equity_estimate']],
        ['Total passif + situation nette', $data['balance_assets_total']],
        [],
        ['INDICATEURS'],
        ['Produits en stock', $data['products_count']],
        ['Produits en alerte stock', $data['low_stock_count']],
        ['Créances ouvertes', $data['receivables_count']],
        ['Dettes ouvertes', $data['debts_count']],
        [],
        ['VENTES PAR MOYEN DE PAIEMENT'],
        ...collect($data['sales_by_payment'])->flatMap(fn ($item) => [
            [$item->payment_method, $item->count, $item->total],
        ])->all(),
        [],
        ['CHARGES PAR CATÉGORIE'],
        ...collect($data['expenses_by_category'])->flatMap(fn ($item) => [
            [$item->category, $item->count, $item->total],
        ])->all(),
        [],
        ['STOCK PAR CATÉGORIE'],
        ...collect($data['stock_by_category'])->flatMap(fn ($item) => [
            [$item->category, $item->products_count, $item->stock_quantity, $item->stock_value],
        ])->all(),
        [],
        ['TOP PRODUITS VENDUS'],
        ...collect($data['top_products'])->flatMap(fn ($item) => [
            [$item->product_name, $item->quantity, $item->total],
        ])->all(),
    ];

    return csvResponse('bilan-comptable.csv', ['Indicateur', 'Valeur'], $rows);
});

Route::get('/api/businesses/{business}/taxes', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $period = taxRequestPeriod(request());
    $range = reportRange($period, request());
    $data = reportData($business, $range[0], $range[1], $period);

    return response()->json([
        'faq' => taxFaq(),
        'statement' => [
            'period' => $data['range_label'],
            'sales_total' => $data['sales_total'],
            'cost_of_goods_sold' => $data['cost_of_goods_sold'],
            'gross_margin' => $data['gross_margin'],
            'expenses_total' => $data['expenses_total'],
            'payrolls_gross_total' => $data['payrolls_gross_total'],
            'salary_advances_total' => $data['salary_advances_total'],
            'payrolls_total' => $data['payrolls_total'],
            'net_result' => $data['net_result'],
            'receivables_remaining' => $data['receivables_remaining'],
            'debts_remaining' => $data['debts_remaining'],
            'stock_value' => $data['stock_value'],
            'balance_assets_total' => $data['balance_assets_total'],
        ],
        'disclaimer' => 'Ces documents sont des supports de gestion générés par EasyMarket. Ils ne constituent pas des documents légaux, fiscaux ou comptables officiels et doivent être vérifiés par un comptable ou l’administration compétente avant toute déclaration.',
        'documents' => [
            ['title' => 'Bilan comptable mensuel', 'description' => 'Synthèse des recettes, dépenses, paies, créances, dettes et résultat estimé.'],
            ['title' => 'Journal des ventes', 'description' => 'Liste des factures, tickets, proformas validés, moyens de paiement et montants encaissés.'],
            ['title' => 'Rapport des charges', 'description' => 'Détail des dépenses par date, catégorie et montant.'],
            ['title' => 'État des créances clients', 'description' => 'Montants dus par les clients, échéances, remboursements reçus et soldes restants.'],
            ['title' => 'État des dettes fournisseurs', 'description' => 'Montants dus aux fournisseurs, échéances, paiements effectués et soldes restants.'],
            ['title' => 'Fiches de paie et avances', 'description' => 'Salaires, avances, paies nettes et historique du personnel.'],
            ['title' => 'Inventaire et valorisation du stock', 'description' => 'Produits, quantités disponibles, seuils d’alerte et valeur estimée du stock.'],
            ['title' => 'Exports PDF / Excel', 'description' => 'Fichiers à joindre au dossier du comptable pour contrôle et archivage.'],
        ],
    ]);
});

Route::post('/api/businesses/{business}/products', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category_name' => ['nullable', 'string', 'max:255'],
        'purchase_price' => ['required', 'integer', 'min:0'],
        'sale_price' => ['required', 'integer', 'min:0'],
        'stock_quantity' => ['required', 'numeric', 'min:0'],
        'alert_threshold' => ['required', 'numeric', 'min:0'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations produit sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $categoryName = trim((string) $request->input('category_name', ''));
    $category = $categoryName !== ''
        ? Category::firstOrCreate([
            'business_id' => $business->id,
            'name' => $categoryName,
        ])
        : defaultProductCategory($business);

    $product = Product::create([
        'business_id' => $business->id,
        'category_id' => $category?->id,
        'name' => $request->input('name'),
        'purchase_price' => $request->integer('purchase_price'),
        'sale_price' => $request->integer('sale_price'),
        'stock_quantity' => $request->input('stock_quantity'),
        'alert_threshold' => $request->input('alert_threshold'),
        'notes' => $request->input('notes'),
    ]);

    if ((float) $product->stock_quantity > 0) {
        StockMovement::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'type' => 'in',
            'quantity' => (float) $product->stock_quantity,
            'reason' => 'Stock initial',
            'notes' => 'Quantité renseignée à la création du produit.',
            'moved_at' => now(),
        ]);
    }

    return response()->json($product->load('category'), 201);
});

Route::put('/api/businesses/{business}/products/{product}', function (Request $request, Business $business, Product $product) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($product->business_id === $business->id, 404);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category_name' => ['nullable', 'string', 'max:255'],
        'purchase_price' => ['required', 'integer', 'min:0'],
        'sale_price' => ['required', 'integer', 'min:0'],
        'stock_quantity' => ['required', 'numeric', 'min:0'],
        'alert_threshold' => ['required', 'numeric', 'min:0'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations produit sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $hasSales = Sale::query()
        ->where('business_id', $business->id)
        ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
        ->exists();

    if ($hasSales && $request->integer('purchase_price') !== (int) $product->purchase_price) {
        return response()->json([
            'message' => 'Le prix d’achat ne peut plus être modifié après la première vente du produit.',
            'errors' => ['purchase_price' => ['Le prix d’achat est verrouillé car ce produit a déjà été vendu.']],
        ], 422);
    }

    $categoryName = trim((string) $request->input('category_name', ''));
    $category = $categoryName !== ''
        ? Category::firstOrCreate([
            'business_id' => $business->id,
            'name' => $categoryName,
        ])
        : defaultProductCategory($business);

    $product->update([
        'category_id' => $category?->id,
        'name' => $request->input('name'),
        'purchase_price' => $hasSales ? $product->purchase_price : $request->integer('purchase_price'),
        'sale_price' => $request->integer('sale_price'),
        'stock_quantity' => $request->input('stock_quantity'),
        'alert_threshold' => $request->input('alert_threshold'),
        'notes' => $request->input('notes'),
    ]);

    return response()->json($product->fresh()->load('category'));
});

Route::post('/api/businesses/{business}/categories', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('categories', 'name')->where(fn ($query) => $query->where('business_id', $business->id)),
        ],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La catégorie est invalide ou existe déjà.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $category = Category::create([
        'business_id' => $business->id,
        'name' => trim($request->input('name')),
    ]);

    return response()->json($category, 201);
});

Route::put('/api/businesses/{business}/categories/{category}', function (Request $request, Business $business, Category $category) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($category->business_id === $business->id, 404);

    $validator = Validator::make($request->all(), [
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('categories', 'name')
                ->where(fn ($query) => $query->where('business_id', $business->id))
                ->ignore($category->id),
        ],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La catégorie est invalide ou existe déjà.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $category->update([
        'name' => trim($request->input('name')),
    ]);

    return response()->json($category->fresh());
});

Route::patch('/api/businesses/{business}/categories/{category}/archive', function (Request $request, Business $business, Category $category) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($category->business_id === $business->id, 404);

    DB::transaction(function () use ($business, $category) {
        Product::query()
            ->where('business_id', $business->id)
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();
    });

    return response()->json(['message' => 'Catégorie archivée.']);
});

Route::post('/api/businesses/{business}/services', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    $user = Auth::user();
    $pivot = $user ? $business->users()->where('users.id', $user->id)->first()?->pivot : null;
    abort_unless($user && ($business->owner_id === $user->id || $pivot?->role === 'admin' || $user->role === 'admin'), 403);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'price' => ['required', 'integer', 'min:0'],
        'duration' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:active,paused'],
        'details' => ['nullable', 'string', 'max:2000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations service sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $service = Service::create([
        'business_id' => $business->id,
        'name' => trim($request->input('name')),
        'price' => $request->integer('price'),
        'duration' => trim((string) $request->input('duration')) ?: null,
        'status' => $request->input('status', 'active'),
        'details' => $request->input('details'),
    ]);

    return response()->json($service, 201);
});

Route::put('/api/businesses/{business}/services/{service}', function (Request $request, Business $business, Service $service) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($service->business_id === $business->id, 404);
    $user = Auth::user();
    $pivot = $user ? $business->users()->where('users.id', $user->id)->first()?->pivot : null;
    abort_unless($user && ($business->owner_id === $user->id || $pivot?->role === 'admin' || $user->role === 'admin'), 403);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'price' => ['required', 'integer', 'min:0'],
        'duration' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:active,paused'],
        'details' => ['nullable', 'string', 'max:2000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations service sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $service->update([
        'name' => trim($request->input('name')),
        'price' => $request->integer('price'),
        'duration' => trim((string) $request->input('duration')) ?: null,
        'status' => $request->input('status', 'active'),
        'details' => $request->input('details'),
    ]);

    return response()->json($service->fresh());
});

Route::patch('/api/businesses/{business}/services/{service}/archive', function (Request $request, Business $business, Service $service) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($service->business_id === $business->id, 404);
    $user = Auth::user();
    $pivot = $user ? $business->users()->where('users.id', $user->id)->first()?->pivot : null;
    abort_unless($user && ($business->owner_id === $user->id || $pivot?->role === 'admin' || $user->role === 'admin'), 403);

    $hasSales = Sale::query()
        ->where('business_id', $business->id)
        ->whereHas('items', fn ($query) => $query
            ->whereNull('product_id')
            ->where('product_name', $service->name))
        ->exists();

    if ($hasSales) {
        return response()->json([
            'message' => 'Impossible de supprimer ce service car il est déjà en train d’être vendu ou a déjà été vendu.',
        ], 422);
    }

    $service->update(['status' => 'paused']);

    return response()->json($service->fresh());
});

Route::patch('/api/businesses/{business}/products/{product}/price', function (Request $request, Business $business, Product $product) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($product->business_id === $business->id, 404);

    $user = Auth::user();
    $pivot = $user ? $business->users()->where('users.id', $user->id)->first()?->pivot : null;
    $canEditPrice = $user
        && ($business->owner_id === $user->id || $pivot?->role === 'admin' || (bool) $pivot?->can_edit_prices);

    if (! $canEditPrice) {
        return response()->json([
            'message' => 'Vous n’êtes pas autorisé à modifier les prix.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'sale_price' => ['required', 'integer', 'min:0'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Prix de vente invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $product->update([
        'sale_price' => $request->integer('sale_price'),
    ]);

    return response()->json($product->load('category'));
});

Route::post('/api/businesses/{business}/products/{product}/stock-movements', function (Request $request, Business $business, Product $product) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($product->business_id === $business->id, 404);

    $validator = Validator::make($request->all(), [
        'type' => ['required', Rule::in(['in', 'out'])],
        'quantity' => ['required', 'numeric', 'min:0.01'],
        'reason' => ['required', 'string', 'max:255'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le mouvement de stock est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $quantity = (float) $request->input('quantity');
    $type = $request->input('type');

    $updatedProduct = DB::transaction(function () use ($business, $product, $request, $quantity, $type) {
        $lockedProduct = Product::query()
            ->where('business_id', $business->id)
            ->lockForUpdate()
            ->findOrFail($product->id);

        if ($type === 'out' && (float) $lockedProduct->stock_quantity < $quantity) {
            abort(response()->json([
                'message' => 'Stock insuffisant pour ce retrait.',
            ], 422));
        }

        StockMovement::create([
            'business_id' => $business->id,
            'product_id' => $lockedProduct->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $request->input('reason'),
            'notes' => $request->input('notes'),
            'moved_at' => now(),
        ]);

        $lockedProduct->stock_quantity = $type === 'in'
            ? (float) $lockedProduct->stock_quantity + $quantity
            : (float) $lockedProduct->stock_quantity - $quantity;
        $lockedProduct->save();

        return $lockedProduct->load('category');
    });

    return response()->json($updatedProduct);
});

Route::get('/api/businesses/{business}/products/{product}/stock-movements', function (Business $business, Product $product) {
    authorizeBusinessAccess($business, request());
    abort_unless($product->business_id === $business->id, 404);

    return response()->json([
        'product' => $product->only(['id', 'name', 'stock_quantity']),
        'movements' => StockMovement::query()
            ->where('business_id', $business->id)
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest('moved_at')
            ->limit(50)
            ->get()
            ->map(fn ($movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason,
                'notes' => $movement->notes,
                'moved_at' => $movement->moved_at,
                'user' => $movement->user?->only(['id', 'name']),
            ]),
    ]);
});

Route::patch('/api/businesses/{business}/products/{product}/archive', function (Request $request, Business $business, Product $product) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);
    abort_unless($product->business_id === $business->id, 404);

    $hasSales = Sale::query()
        ->where('business_id', $business->id)
        ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
        ->exists();

    if ($hasSales) {
        return response()->json([
            'message' => 'Impossible de supprimer ce produit car il est déjà en train d’être vendu ou a déjà été vendu.',
        ], 422);
    }

    $product->update(['is_active' => false]);

    return response()->json($product->load('category'));
});

Route::post('/api/businesses/{business}/sales', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'customer_phone' => normalizePhoneInput($request->input('customer_phone')),
    ]);

    $seller = Auth::user();
    $sellerPivot = $seller ? $business->users()->where('users.id', $seller->id)->first()?->pivot : null;
    $isBusinessAdmin = $seller
        && ($business->owner_id === $seller->id || $sellerPivot?->role === 'admin' || $seller->role === 'admin');
    $isBusinessSeller = $seller
        && $business->owner_id !== $seller->id
        && ($sellerPivot?->role === 'seller' || $seller->role === 'seller');
    $canSell = $seller
        && $seller->is_active
        && ($isBusinessAdmin || $isBusinessSeller);

    if (! $canSell) {
        return response()->json([
            'message' => 'Seuls les administrateurs et vendeurs de la boutique peuvent enregistrer une vente.',
        ], 403);
    }

    $documentType = $request->input('type', 'invoice');
    if ($documentType === 'proforma') {
        $request->merge([
            'payment_method' => $request->input('payment_method', 'cash'),
            'credit_due_date' => null,
        ]);
    }

    $validator = Validator::make($request->all(), [
        'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        'customer_name' => ['nullable', 'string', 'max:255'],
        'customer_phone' => nullablePhoneRule(),
        'type' => ['nullable', 'in:invoice,proforma'],
        'payment_method' => ['required', 'in:cash,mobile_money,credit'],
        'credit_due_date' => ['nullable', 'date', 'required_if:payment_method,credit'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
        'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
        'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
        'items.*.discount' => ['nullable', 'integer', 'min:0'],
    ]);

    $validator->after(function ($validator) use ($request) {
        foreach ($request->input('items', []) as $index => $item) {
            if (empty($item['product_id']) && empty($item['service_id'])) {
                $validator->errors()->add("items.$index", 'Choisissez un produit ou un service.');
            }
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations de vente sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $sellerCanEditPrices = (bool) ($isBusinessAdmin || ($sellerPivot?->can_edit_prices ?? $seller->can_edit_prices ?? false));

    $sale = DB::transaction(function () use ($request, $business, $seller, $sellerCanEditPrices, $documentType) {
        $customer = null;

        if ($request->filled('customer_id')) {
            $customer = Customer::query()
                ->where('business_id', $business->id)
                ->findOrFail($request->integer('customer_id'));
        } elseif ($request->filled('customer_phone')) {
            $customer = Customer::query()
                ->where('business_id', $business->id)
                ->where('phone', $request->input('customer_phone'))
                ->first();

            if (! $customer) {
                $customer = Customer::create([
                    'business_id' => $business->id,
                    'name' => $request->input('customer_name') ?: 'Client comptoir',
                    'phone' => $request->input('customer_phone'),
                ]);
            } elseif ($request->filled('customer_name') && $customer->name !== $request->input('customer_name')) {
                $customer->update(['name' => $request->input('customer_name')]);
            }
        } elseif ($request->filled('customer_name') || $request->input('payment_method') === 'credit') {
            $customer = Customer::query()
                ->where('business_id', $business->id)
                ->where('name', $request->input('customer_name') ?: 'Client crédit')
                ->whereNull('phone')
                ->first();

            if (! $customer) {
                $customer = Customer::create([
                    'business_id' => $business->id,
                    'name' => $request->input('customer_name') ?: ($request->input('payment_method') === 'credit' ? 'Client crédit' : 'Client comptoir'),
                    'phone' => null,
                ]);
            }
        }

        $sale = Sale::create([
            'business_id' => $business->id,
            'customer_id' => $customer?->id,
            'seller_id' => $seller->id,
            'number' => nextSaleNumber($business),
            'type' => $documentType,
            'payment_method' => $request->input('payment_method'),
            'status' => $documentType === 'proforma' ? 'draft' : 'completed',
            'credit_due_date' => $documentType === 'invoice' && $request->input('payment_method') === 'credit' ? $request->input('credit_due_date') : null,
            'sold_at' => now(),
        ]);

        $subtotal = 0;
        $discountTotal = 0;

        foreach ($request->input('items') as $line) {
            $quantity = (float) $line['quantity'];
            $discount = (int) ($line['discount'] ?? 0);
            $product = null;
            $service = null;

            if (! empty($line['service_id'])) {
                $service = Service::query()
                    ->where('business_id', $business->id)
                    ->findOrFail($line['service_id']);

                if ($service->status !== 'active') {
                    abort(response()->json([
                        'message' => "Le service {$service->name} n’est pas disponible à la vente.",
                    ], 422));
                }
            } else {
                $product = Product::query()
                    ->where('business_id', $business->id)
                    ->lockForUpdate()
                    ->findOrFail($line['product_id']);
            }

            $defaultPrice = $service ? (int) $service->price : (int) $product->sale_price;
            $unitPrice = $sellerCanEditPrices && array_key_exists('unit_price', $line)
                ? (int) $line['unit_price']
                : $defaultPrice;

            if ($documentType === 'invoice' && $product && (float) $product->stock_quantity < $quantity) {
                abort(response()->json([
                    'message' => "Stock insuffisant pour {$product->name}.",
                ], 422));
            }

            $lineSubtotal = (int) round($quantity * $unitPrice);
            $lineTotal = max(0, $lineSubtotal - $discount);
            $subtotal += $lineSubtotal;
            $discountTotal += $discount;

            $sale->items()->create([
                'product_id' => $product?->id,
                'product_name' => $service ? $service->name : $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'total' => $lineTotal,
            ]);

            if ($documentType === 'invoice' && $product) {
                $product->decrement('stock_quantity', $quantity);

                StockMovement::create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
                    'user_id' => $seller->id,
                    'type' => 'sale',
                    'quantity' => -$quantity,
                    'reason' => 'Vente '.$sale->number,
                    'moved_at' => now(),
                ]);
            }
        }

        $total = max(0, $subtotal - $discountTotal);
        $sale->update([
            'subtotal' => $subtotal,
            'discount' => $discountTotal,
            'total' => $total,
        ]);

        if ($documentType === 'proforma') {
            return $sale->load(['items', 'customer', 'seller:id,name,username,phone']);
        }

        if ($request->input('payment_method') === 'credit') {
            Receivable::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'amount_due' => $total,
                'amount_paid' => 0,
                'due_date' => $request->input('credit_due_date'),
                'notes' => 'Achat du jour - Facture '.$sale->number,
                'status' => 'current',
            ]);
        } else {
            Payment::create([
                'business_id' => $business->id,
                'sale_id' => $sale->id,
                'type' => 'sale',
                'method' => $request->input('payment_method'),
                'amount' => $total,
                'paid_at' => now(),
            ]);
        }

        return $sale->load(['items', 'customer', 'seller:id,name,username,phone']);
    });

    $sale->setAttribute('public_invoice_url', salePublicInvoiceUrl($sale));

    return response()->json($sale, 201);
});

Route::post('/api/businesses/{business}/sales/{sale}/cancel', function (Request $request, Business $business, Sale $sale) {
    authorizeBusinessAccess($business, $request);
    abort_unless($sale->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $user = Auth::user();
    abort_unless($user, 403);

    $pivot = $business->users()->where('users.id', $user->id)->first()?->pivot;
    $canCancel = $business->owner_id === $user->id
        || $pivot?->role === 'admin'
        || ($sale->seller_id === $user->id && ($pivot?->role === 'seller' || $user->role === 'seller'));

    abort_unless($canCancel, 403);

    $validator = Validator::make($request->all(), [
        'reason' => ['required', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Le motif d’annulation est obligatoire.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $cancelledSale = DB::transaction(function () use ($request, $business, $sale, $user) {
        $sale = Sale::query()
            ->where('business_id', $business->id)
            ->with('items')
            ->lockForUpdate()
            ->findOrFail($sale->id);

        if ($sale->status === 'cancelled') {
            abort(response()->json([
                'message' => 'Cette vente est déjà annulée.',
            ], 422));
        }

        $receivable = null;
        if ($sale->payment_method === 'credit') {
            $receivable = Receivable::query()
                ->where('business_id', $business->id)
                ->where('customer_id', $sale->customer_id)
                ->where('notes', 'like', '%Facture '.$sale->number.'%')
                ->lockForUpdate()
                ->first();

            if ($receivable && (int) $receivable->amount_paid > 0) {
                abort(response()->json([
                    'message' => 'Impossible d’annuler cette vente à crédit : le client a déjà commencé à payer.',
                ], 422));
            }
        }

        if ($sale->type === 'invoice' && $sale->status === 'completed') {
            foreach ($sale->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()
                    ->where('business_id', $business->id)
                    ->lockForUpdate()
                    ->find($item->product_id);

                if (! $product) {
                    continue;
                }

                $product->increment('stock_quantity', (float) $item->quantity);

                StockMovement::create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => 'in',
                    'quantity' => (float) $item->quantity,
                    'reason' => 'Annulation vente '.$sale->number,
                    'notes' => $request->input('reason'),
                    'moved_at' => now(),
                ]);
            }
        }

        if ($receivable) {
            $receivable->delete();
        }

        Payment::query()
            ->where('business_id', $business->id)
            ->where('sale_id', $sale->id)
            ->delete();

        $sale->forceFill([
            'status' => 'cancelled',
            'canceled_by' => $user->id,
            'canceled_at' => now(),
            'cancellation_reason' => $request->input('reason'),
        ])->save();

        return $sale->fresh(['items', 'customer', 'seller:id,name,username,phone', 'canceledBy:id,name']);
    });

    return response()->json($cancelledSale);
});

Route::get('/businesses/{business}/exports/{type}', function (Business $business, string $type) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($type, ['products', 'profitability', 'sales', 'expenses', 'customers', 'receivables', 'supplier-debts', 'employees', 'payrolls'], true), 404);

    if ($type === 'products') {
        $rows = Product::query()
            ->where('business_id', $business->id)
            ->with('category')
            ->get()
            ->map(fn ($product) => [
                $product->name,
                $product->category?->name ?: '',
                $product->notes ?: '-',
                $product->stock_quantity,
                $product->alert_threshold,
                $product->purchase_price,
                $product->sale_price,
            ]);

        return csvResponse('produits.csv', ['Produit', 'Catégorie', 'Note', 'Stock', 'Seuil', 'Prix achat', 'Prix vente'], $rows);
    }

    if ($type === 'profitability') {
        $rows = Product::query()
            ->where('business_id', $business->id)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $purchasePrice = (int) $product->purchase_price;
                $salePrice = (int) $product->sale_price;
                $unitMargin = $salePrice - $purchasePrice;
                $stockQuantity = (float) $product->stock_quantity;

                return [
                    $product->name,
                    $product->category?->name ?: '',
                    $purchasePrice,
                    $salePrice,
                    $unitMargin,
                    $purchasePrice > 0 ? round(($unitMargin / $purchasePrice) * 100, 2).'%' : '-',
                    $stockQuantity,
                    (int) round($unitMargin * $stockQuantity),
                ];
            });

        return csvResponse('rentabilite-produits.csv', ['Produit', 'Categorie', 'Prix achat', 'Prix vente', 'Marge unitaire', 'Taux marge', 'Stock', 'Rentabilite stock'], $rows);
    }

    if ($type === 'sales') {
        $rows = Sale::query()
            ->where('business_id', $business->id)
            ->with(['customer', 'seller:id,name,username,phone'])
            ->latest()
            ->get()
            ->map(fn ($sale) => [
                $sale->number,
                $sale->seller?->username ?: $sale->seller?->name ?: '',
                $sale->customer?->name ?: '',
                paymentLabel($sale->payment_method),
                saleStatusLabel($sale->status),
                $sale->subtotal,
                $sale->discount,
                $sale->total,
                optional($sale->sold_at)->format('d/m/Y H:i'),
            ]);

        return csvResponse('ventes.csv', ['Facture', 'Vendeur', 'Client', 'Paiement', 'Statut', 'Sous-total', 'Remise', 'Total', 'Date'], $rows);
    }

    if ($type === 'customers') {
        $rows = Customer::query()
            ->where('business_id', $business->id)
            ->latest()
            ->get()
            ->map(fn ($customer) => [
                $customer->name,
                $customer->phone,
                $customer->email,
                optional($customer->created_at)->format('d/m/Y H:i'),
            ]);

        return csvResponse('clients.csv', ['Client', 'Téléphone', 'Email', 'Création'], $rows);
    }

    if ($type === 'receivables') {
        $rows = Receivable::query()
            ->where('business_id', $business->id)
            ->with('customer')
            ->latest()
            ->get()
            ->map(fn ($receivable) => [
                $receivable->customer?->name ?: '',
                $receivable->amount_due,
                $receivable->amount_paid,
                max(0, $receivable->amount_due - $receivable->amount_paid),
                optional($receivable->due_date)->format('d/m/Y'),
                $receivable->status,
                $receivable->notes,
            ]);

        return csvResponse('creances.csv', ['Client', 'Montant dû', 'Payé', 'Reste', 'Échéance', 'Statut', 'Notes'], $rows);
    }

    if ($type === 'supplier-debts') {
        $rows = SupplierDebt::query()
            ->where('business_id', $business->id)
            ->with('supplier')
            ->latest()
            ->get()
            ->map(fn ($debt) => [
                $debt->supplier?->name ?: '',
                $debt->amount_due,
                $debt->amount_paid,
                max(0, $debt->amount_due - $debt->amount_paid),
                optional($debt->due_date)->format('d/m/Y'),
                $debt->status,
                $debt->notes,
            ]);

        return csvResponse('dettes-fournisseurs.csv', ['Fournisseur', 'Montant dû', 'Payé', 'Reste', 'Échéance', 'Statut', 'Notes'], $rows);
    }

    if ($type === 'employees') {
        $rows = Employee::query()
            ->where('business_id', $business->id)
            ->with('user:id,name,phone')
            ->latest()
            ->get()
            ->map(fn ($employee) => [
                $employee->name,
                $employee->position,
                $employee->type,
                $employee->user?->phone ?: '',
                $employee->salary,
                salaryPaymentDayMonth($employee->salary_payment_date),
                optional($employee->hired_at)->format('d/m/Y'),
            ]);

        return csvResponse('employes.csv', ['Employé', 'Poste', 'Type', 'Téléphone', 'Salaire', 'Date paiement salaire', 'Embauche'], $rows);
    }

    if ($type === 'payrolls') {
        $rows = Payroll::query()
            ->where('business_id', $business->id)
            ->with('employee')
            ->latest()
            ->get()
            ->map(fn ($payroll) => [
                $payroll->period,
                $payroll->employee?->name ?: '',
                $payroll->gross_salary,
                $payroll->salary_advance,
                $payroll->net_salary,
                $payroll->status,
                optional($payroll->paid_at)->format('d/m/Y H:i'),
            ]);

        return csvResponse('paies.csv', ['Période', 'Employé', 'Brut', 'Avance', 'Net', 'Statut', 'Paiement'], $rows);
    }

    $rows = Expense::query()
        ->where('business_id', $business->id)
        ->latest('spent_on')
        ->get()
        ->map(fn ($expense) => [
            $expense->name,
            $expense->category,
            $expense->type,
            $expense->amount,
            optional($expense->spent_on)->format('d/m/Y'),
            $expense->notes,
        ]);

    return csvResponse('depenses.csv', ['Charge', 'Catégorie', 'Type', 'Montant', 'Date', 'Notes'], $rows);
});

Route::get('/businesses/{business}/exports/{type}/pdf', function (Business $business, string $type) {
    authorizeBusinessAccess($business, request());
    $export = exportDataset($business, $type);

    return printableDatasetResponse($business, $export['title'], $export['headers'], $export['rows']);
});

if (! function_exists('requireAuthenticatedUser')) {
function requireAuthenticatedUser(Request $request): User
{
    if (Auth::check()) {
        return Auth::user();
    }

    if ($request->is('api/*') || $request->expectsJson()) {
        abort(response()->json([
            'message' => 'Connexion requise.',
        ], 401));
    }

    abort(redirect('/connexion'));
}

function authorizeBusinessAccess(Business $business, Request $request): void
{
    $user = requireAuthenticatedUser($request);

    if ($user->role === 'super_admin') {
        return;
    }

    if ($business->owner_id === $user->id) {
        return;
    }

    if ($business->users()->where('users.id', $user->id)->exists()) {
        return;
    }

    if ($request->is('api/*') || $request->expectsJson()) {
        abort(response()->json([
            'message' => 'Accès non autorisé à cette boutique.',
        ], 403));
    }

    abort(403);
}

function requireSuperAdmin(Request $request): User
{
    $user = requireAuthenticatedUser($request);

    if ($user->role !== 'super_admin') {
        abort(403);
    }

    return $user;
}

function currentUserPayload(Business $business): array
{
    $user = Auth::user();
    $role = 'Admin';
    $pivot = null;

    if ($user?->role === 'super_admin') {
        $role = 'Super Admin';
    } elseif ($user) {
        $pivot = $business->users()->where('users.id', $user->id)->first()?->pivot;
        $role = match ($pivot?->role ?: $user->role) {
            'seller' => 'Vendeur',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin',
            default => ucfirst((string) ($pivot?->role ?: $user->role ?: 'Admin')),
        };
    }

    return [
        'id' => $user?->id,
        'name' => $user?->name ?: 'Utilisateur',
        'email' => $user?->email,
        'username' => $user?->username,
        'phone' => $user?->phone,
        'whatsapp_phone' => $user?->whatsapp_phone,
        'role' => $role,
        'can_edit_prices' => (bool) ($business->owner_id === $user?->id || $pivot?->role === 'admin' || ($pivot?->can_edit_prices ?? $user?->can_edit_prices ?? false)),
        'is_active' => (bool) $user?->is_active,
        'force_logout_message' => $user && ! $user->is_active
            ? "Votre compte a été désactivé par l'administrateur. Vous allez être déconnecté."
            : null,
    ];
}

function adminPageHtml(string $section = 'dashboard'): string
{
    $csrf = csrf_token();
    $currentUser = Auth::user();
    $paidRevenue = (int) Subscription::query()->where('status', 'actif')->sum('amount');
    $subscriptions = Subscription::query()->with(['business.owner'])->latest()->limit(20)->get();
    $businesses = Business::query()->with(['owner', 'subscriptions' => fn ($query) => $query->latest()->limit(1)])->latest()->limit(20)->get();
    $users = User::query()->latest()->limit(20)->get();

    $stats = [
        ['Boutiques', Business::query()->count(), 'fa-store'],
        ['Utilisateurs', User::query()->count(), 'fa-users'],
        ['Abonnements actifs', Subscription::query()->where('status', 'actif')->count(), 'fa-circle-check'],
        ['Demandes en attente', Subscription::query()->whereIn('status', ['pending', 'en attente'])->count(), 'fa-clock'],
        ['Revenus validés', number_format($paidRevenue, 0, ',', ' ').' FCFA', 'fa-coins'],
    ];
    $statsHtml = collect($stats)->map(fn ($stat) => '<article class="stat"><i class="fa-solid '.$stat[2].'"></i><span>'.e($stat[0]).'</span><strong>'.e((string) $stat[1]).'</strong></article>')->implode('');
    $revenueStatsHtml = collect($stats)
        ->map(fn ($stat) => '<article class="stat"><i class="fa-solid '.$stat[2].'"></i><span>'.e($stat[0]).'</span><strong>'.e((string) $stat[1]).'</strong></article>')
        ->implode('');

    $subscriptionRows = $subscriptions->map(function (Subscription $subscription) use ($csrf) {
        $business = $subscription->business;
        $action = $subscription->status === 'actif'
            ? '<form method="post" action="/admin/abonnements/'.$subscription->id.'/suspendre"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn danger icon-btn" style="width:38px;height:38px;min-height:38px;padding:0" type="submit" title="Suspendre" aria-label="Suspendre"><i class="fa-solid fa-ban"></i></button></form>'
            : '<form method="post" action="/admin/abonnements/'.$subscription->id.'/activer"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn icon-btn" style="width:38px;height:38px;min-height:38px;padding:0;background:#2f7d69;color:white" type="submit" title="Activer" aria-label="Activer"><i class="fa-solid fa-check"></i></button></form>';
        $deleteAction = '<form method="post" action="/admin/abonnements/'.$subscription->id.'/supprimer" onsubmit="return confirm(\'Supprimer cet abonnement ?\')"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn danger icon-btn" style="width:38px;height:38px;min-height:38px;padding:0" type="submit" title="Supprimer" aria-label="Supprimer"><i class="fa-solid fa-trash"></i></button></form>';

        return '<tr><td><strong>'.e($business?->name ?: 'Boutique supprimée').'</strong><small>'.e($business?->owner?->email ?: '-').'</small></td><td>'.e(subscriptionPlanLabel($subscription->plan)).'</td><td>'.number_format($subscription->amount, 0, ',', ' ').' FCFA</td><td>'.e($subscription->deposit_phone ?: $subscription->payment_reference ?: '-').'</td><td>'.adminStatusBadge($subscription->status).'</td><td>'.e(optional($subscription->ends_at)->format('d/m/Y') ?: '-').'</td><td class="actions">'.$action.$deleteAction.'</td></tr>';
    })->implode('');

    $businessRows = $businesses->map(function (Business $business) {
        $subscription = $business->subscriptions->first();

        return '<tr><td><strong>'.e($business->name).'</strong><small>'.e($business->address ?: 'Adresse non renseignée').'</small></td><td>'.e($business->phone ?: '-').'<small>WhatsApp: '.e($business->whatsapp_phone ?: '-').'</small></td><td>'.e($business->owner?->name ?: '-').'<small>'.e($business->owner?->email ?: '-').'</small></td><td>'.($subscription ? adminStatusBadge($subscription->status) : adminStatusBadge('aucun')).'</td><td>'.number_format($business->products()->count(), 0, ',', ' ').'</td><td><a class="btn light icon-btn" style="width:38px;height:38px;min-height:38px;padding:0" href="/dashboard/'.$business->id.'" title="Ouvrir" aria-label="Ouvrir"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td></tr>';
    })->implode('');

    $userRows = $users->map(function (User $user) use ($csrf) {
        $action = $user->role === 'super_admin'
            ? '<span class="muted">Protégé</span>'
            : '<form method="post" action="/admin/utilisateurs/'.$user->id.'/statut"><input type="hidden" name="_token" value="'.e($csrf).'"><button class="btn light icon-btn" style="width:38px;height:38px;min-height:38px;padding:0" type="submit" title="'.($user->is_active ? 'Désactiver' : 'Activer').'" aria-label="'.($user->is_active ? 'Désactiver' : 'Activer').'"><i class="fa-solid '.($user->is_active ? 'fa-user-slash' : 'fa-user-check').'"></i></button></form>';

        return '<tr><td><strong>'.e($user->name).'</strong><small>'.e($user->email).'</small></td><td>'.e($user->phone ?: '-').'</td><td>'.e($user->role).'</td><td>'.adminStatusBadge($user->is_active ? 'actif' : 'inactif').'</td><td>'.e(optional($user->created_at)->format('d/m/Y') ?: '-').'</td><td class="actions">'.$action.'</td></tr>';
    })->implode('');

    $revenueRows = collect(subscriptionPlans())->map(function ($plan, $key) {
        $activeCount = Subscription::query()->where('plan', $key)->where('status', 'actif')->count();
        $pendingCount = Subscription::query()->where('plan', $key)->whereIn('status', ['pending', 'en attente'])->count();
        $total = (int) Subscription::query()->where('plan', $key)->where('status', 'actif')->sum('amount');

        return '<tr><td><strong>'.e($plan['label']).'</strong><small>'.e($plan['duration']).'</small></td><td>'.number_format($plan['amount'], 0, ',', ' ').' FCFA</td><td>'.$activeCount.'</td><td>'.$pendingCount.'</td><td>'.number_format($total, 0, ',', ' ').' FCFA</td></tr>';
    })->implode('');
    $settingsMessage = session('admin_settings_success')
        ? '<p class="message success"><i class="fa-solid fa-circle-check"></i>'.e(session('admin_settings_success')).'</p>'
        : '';
    $errorBag = session('errors');
    $settingsErrors = $errorBag && $errorBag->any()
        ? '<p class="message error"><i class="fa-solid fa-triangle-exclamation"></i>'.e($errorBag->first()).'</p>'
        : '';

    $content = match ($section) {
        'abonnements' => '<section class="card"><div class="table-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Rechercher une boutique, une formule, un statut..." data-table-search></div><div class="table-wrap"><table><thead><tr><th>Boutique</th><th>Formule</th><th>Montant</th><th>Numéro dépôt</th><th>Statut</th><th>Fin</th><th>Actions</th></tr></thead><tbody>'.$subscriptionRows.'</tbody></table></div></section>',
        'boutiques' => '<section class="card"><div class="section-title"><div><h2>Boutiques</h2><p>Vue globale des commerces inscrits.</p></div></div><div class="table-wrap"><table><thead><tr><th>Boutique</th><th>Contact</th><th>Propriétaire</th><th>Abonnement</th><th>Produits</th><th>Action</th></tr></thead><tbody>'.$businessRows.'</tbody></table></div></section>',
        'utilisateurs' => '<section class="card"><div class="section-title"><div><h2>Utilisateurs</h2><p>Comptes administrateurs et accès EasyMarket.</p></div></div><div class="table-wrap"><table><thead><tr><th>Utilisateur</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Création</th><th>Action</th></tr></thead><tbody>'.$userRows.'</tbody></table></div></section>',
        'revenus' => '<section class="stats">'.$revenueStatsHtml.'</section><section class="card"><div class="section-title"><div><h2>Revenus</h2><p>Revenus validés et répartition par formule.</p></div></div><div class="table-wrap"><table><thead><tr><th>Formule</th><th>Prix</th><th>Actifs</th><th>En attente</th><th>Revenus validés</th></tr></thead><tbody>'.$revenueRows.'</tbody></table></div></section>',
        'parametres' => '<section class="card settings-card"><div class="section-title"><div><h2>Paramètres</h2><p>Modifiez le mot de passe du compte super admin.</p></div></div>'.$settingsMessage.$settingsErrors.'<form class="settings-form" method="post" action="/admin/parametres/mot-de-passe"><input type="hidden" name="_token" value="'.e($csrf).'"><label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label><label>Nouveau mot de passe<input type="password" name="password" required minlength="8" autocomplete="new-password"></label><label>Confirmer le nouveau mot de passe<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label><div class="form-actions"><button class="btn light" type="submit"><i class="fa-solid fa-key"></i>Modifier le mot de passe</button></div></form></section>',
        default => '<section class="stats">'.$statsHtml.'</section>',
    };

    $titles = [
        'dashboard' => ['Tableau de bord super admin', 'Statistiques globales EasyMarket.'],
        'abonnements' => ['Abonnements', 'Demandes, validations et suspensions.'],
        'boutiques' => ['Boutiques', 'Commerces inscrits sur EasyMarket.'],
        'utilisateurs' => ['Utilisateurs', "Comptes et statuts d'accès."],
        'revenus' => ['Revenus', 'Suivi financier des abonnements.'],
        'parametres' => ['Paramètres', 'Sécurité du compte super admin.'],
    ];
    $title = $titles[$section] ?? $titles['dashboard'];

    $nav = adminNavLink('/admin', 'fa-chart-line', 'Vue générale', $section === 'dashboard')
        .adminNavLink('/admin/abonnements', 'fa-credit-card', 'Abonnements', $section === 'abonnements')
        .adminNavLink('/admin/boutiques', 'fa-store', 'Boutiques', $section === 'boutiques')
        .adminNavLink('/admin/utilisateurs', 'fa-users', 'Utilisateurs', $section === 'utilisateurs')
        .adminNavLink('/admin/revenus', 'fa-coins', 'Revenus', $section === 'revenus')
        .adminNavLink('/admin/parametres', 'fa-gear', 'Paramètres', $section === 'parametres')
        .adminNavLink('/', 'fa-house', 'Accueil', false);

    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title[0]).' - EasyMarket</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap");:root{--primary:#2f7d69;--dark:#10251f;--ink:#17211b;--muted:#52635b;--line:#dfe7e2;--paper:#f6faf8;--gold:#f5b84b;--danger:#b42318}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Poppins,Arial,sans-serif}.layout{display:grid;grid-template-columns:270px 1fr;min-height:100vh}.side{background:var(--dark);color:#fff;padding:22px;position:sticky;top:0;height:100vh}.brand,.nav a,.logout button,.user-chip,.btn,.message{display:flex;align-items:center;gap:10px}.brand{font-weight:700;font-size:20px;margin-bottom:28px;text-decoration:none;color:#fff}.logo{width:38px;height:38px;border-radius:8px;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),var(--gold));color:var(--dark)}.nav{display:grid;gap:8px}.nav a,.logout button{border:0;border-radius:8px;padding:12px;background:transparent;color:rgba(255,255,255,.78);font-weight:600;text-decoration:none;cursor:pointer}.nav a.active,.nav a:hover{background:rgba(255,255,255,.12);color:#fff}.logout{margin-top:22px}.logout button{width:100%;background:var(--danger);color:#fff}.menu-toggle{display:none}.main{padding:28px}.top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:22px}.top h1{margin:0 0 8px;font-size:42px;line-height:1}.top p{margin:0;color:var(--muted)}.user-chip{border:1px solid var(--line);border-radius:8px;background:#fff;padding:8px 12px}.user-chip i{color:var(--primary)}.user-chip strong,.user-chip span{display:block}.user-chip span{color:var(--muted);font-size:12px;font-weight:600}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:14px;margin-bottom:18px}.stat,.card{background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 14px 36px rgba(16,37,31,.06)}.stat{padding:16px;display:grid;gap:7px}.stat i{color:var(--primary);font-size:22px}.stat span{color:var(--muted);font-weight:600}.stat strong{font-size:24px}.card{padding:18px;margin-bottom:18px}.section-title h2{margin:0}.section-title p{margin:4px 0 14px;color:var(--muted)}.table-search{min-height:46px;margin-bottom:14px;border:1px solid var(--line);border-radius:10px;background:#f8fcfa;display:flex;align-items:center;gap:10px;padding:0 13px}.table-search i{color:var(--primary)}.table-search input{width:100%;border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-weight:600}.table-wrap{overflow:auto;border-radius:10px}table{width:100%;min-width:900px;border-collapse:separate;border-spacing:0 8px}th,td{padding:14px 12px;text-align:left;vertical-align:middle}th{background:var(--dark);color:#fff;font-size:13px;font-weight:600;text-transform:uppercase}th:first-child{border-radius:8px 0 0 8px}th:last-child{border-radius:0 8px 8px 0}tbody tr{background:#fff;box-shadow:0 8px 20px rgba(16,37,31,.05)}td{border-top:1px solid var(--line);border-bottom:1px solid var(--line)}td:first-child{border-left:1px solid var(--line);border-radius:8px 0 0 8px}td:last-child{border-right:1px solid var(--line);border-radius:0 8px 8px 0}td small{display:block;color:var(--muted);margin-top:3px}.table-pagination{margin-top:12px;display:flex;justify-content:space-between;gap:10px;color:var(--muted);font-size:13px;font-weight:600}.table-pagination div{display:flex;gap:8px}.table-pagination button{border:1px solid var(--primary);border-radius:8px;background:var(--primary);color:#fff;padding:8px 10px;font-weight:600}.badge{display:inline-flex;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:600}.ok{background:#2f7d69;color:#fff}.wait{background:#fff4d8;color:#7a4d00}.bad{background:#ffe6df;color:#a33824}.neutral{background:#eef2f0;color:#3f5048}.btn{border:0;border-radius:8px;min-height:38px;padding:9px 12px;justify-content:center;font-weight:600;cursor:pointer;text-decoration:none}.primary{background:var(--gold);color:var(--dark)}.light{background:var(--primary);border:1px solid var(--primary);color:#fff}.danger{background:var(--danger);color:#fff}.actions{display:flex;gap:8px;flex-wrap:wrap}.muted{color:var(--muted);font-weight:600}.settings-card{max-width:760px}.settings-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px}.settings-form label{display:grid;gap:8px;color:var(--dark);font-weight:700}.settings-form input{min-height:46px;border:1px solid var(--line);border-radius:8px;background:#f8fcfa;padding:10px 12px;color:var(--ink)}.form-actions{grid-column:1/-1;display:flex;justify-content:flex-end}.message{border-radius:8px;padding:12px 14px;margin:14px 0;font-weight:700}.message.success{background:#e8f4ef;color:#193b32;border:1px solid #bfe3d7}.message.error{background:#ffe6df;color:#8a2418;border:1px solid #ffd0c7}@media(max-width:980px){.layout{grid-template-columns:1fr}.layout:before{content:"";position:fixed;top:0;left:0;right:0;height:76px;z-index:45;background:rgba(246,250,248,.94);border-bottom:1px solid rgba(223,231,226,.9);box-shadow:0 12px 30px rgba(16,37,31,.08);backdrop-filter:blur(10px)}.menu-toggle{width:44px;height:44px;border:0;border-radius:8px;background:var(--dark);color:#fff;display:inline-flex;align-items:center;justify-content:center;position:fixed;top:14px;left:14px;z-index:60;box-shadow:0 12px 30px rgba(16,37,31,.22);font-size:18px}.side{width:min(82vw,300px);height:100vh;position:fixed;top:0;left:0;z-index:50;padding:72px 18px 18px;display:block;transform:translateX(-105%);transition:transform .22s;overflow-y:auto;box-shadow:20px 0 50px rgba(16,37,31,.22)}body.menu-open .side{transform:translateX(0)}body.menu-open .menu-toggle{background:var(--danger)}.brand{margin-bottom:18px;white-space:nowrap}.nav{display:grid;gap:8px}.nav a,.logout button{width:100%;white-space:normal}.top{flex-direction:column}.top-actions{position:fixed;top:14px;left:66px;right:14px;z-index:49;justify-content:flex-end}.user-chip{min-height:44px;max-width:100%;padding:7px 10px;box-shadow:0 12px 30px rgba(16,37,31,.12)}.user-chip strong,.user-chip span{max-width:calc(100vw - 132px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.main{padding:76px 18px 18px}}@media(max-width:640px){.stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.stat{padding:12px}.stat strong{font-size:18px}.stat span{font-size:12px}.table-wrap{overflow:visible}table,thead,tbody,tr,th,td{display:block;width:100%;min-width:0}thead{display:none}tbody tr{border:1px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:12px}td,td:first-child,td:last-child{border-left:0;border-right:0;border-top:0;border-radius:0}td{border-bottom:1px solid var(--line);display:grid;grid-template-columns:42% 1fr;gap:10px;padding:10px 0}td:last-child{border-bottom:0}td:before{content:attr(data-label);color:var(--dark);font-weight:600}.settings-form{grid-template-columns:1fr}.form-actions{justify-content:stretch}.form-actions .btn{width:100%}}</style></head><body><button class="menu-toggle" type="button" aria-label="Ouvrir le menu" title="Menu"><i class="fa-solid fa-bars"></i></button><div class="layout"><aside class="side"><a class="brand" href="/admin"><span class="logo">EM</span><span>EasyMarket</span></a><nav class="nav">'.$nav.'</nav><form class="logout" method="post" action="/deconnexion"><input type="hidden" name="_token" value="'.e($csrf).'"><button type="submit"><i class="fa-solid fa-right-from-bracket"></i>Déconnexion</button></form></aside><main class="main"><header class="top"><div><h1>'.e($title[0]).'</h1><p>'.e($title[1]).'</p></div><div class="top-actions"><div class="user-chip"><i class="fa-solid fa-user"></i><div><strong>'.e($currentUser?->name ?: 'Utilisateur').'</strong><span>EasyMarket - Super Admin</span></div></div></div></header>'.$content.'</main></div><script>document.querySelectorAll(".table-wrap table").forEach(function(table){var headers=[].map.call(table.querySelectorAll("thead th"),function(th){return th.textContent.trim()});table.querySelectorAll("tbody tr").forEach(function(row){row.querySelectorAll("td").forEach(function(td,i){if(headers[i])td.setAttribute("data-label",headers[i])})});var wrap=table.closest(".table-wrap");if(wrap&&!wrap.nextElementSibling?.classList?.contains("table-pagination")){var count=table.querySelectorAll("tbody tr").length;var div=document.createElement("div");div.className="table-pagination";div.innerHTML="<span>"+count+" élément"+(count>=2?"s":"")+" sur 20</span><div><button disabled>Précédent</button><button disabled>Suivant</button></div>";wrap.insertAdjacentElement("afterend",div)}});document.querySelectorAll("[data-table-search]").forEach(function(input){var card=input.closest(".card");var rows=card?card.querySelectorAll("tbody tr"):[];input.addEventListener("input",function(){var term=input.value.trim().toLowerCase();rows.forEach(function(row){row.style.display=row.textContent.toLowerCase().includes(term)?"":"none"})})});var menuToggle=document.querySelector(".menu-toggle");if(menuToggle){menuToggle.addEventListener("click",function(){document.body.classList.toggle("menu-open");var open=document.body.classList.contains("menu-open");menuToggle.setAttribute("aria-label",open?"Fermer le menu":"Ouvrir le menu");menuToggle.querySelector("i").className=open?"fa-solid fa-xmark":"fa-solid fa-bars"});document.querySelectorAll(".side a").forEach(function(link){link.addEventListener("click",function(){document.body.classList.remove("menu-open");menuToggle.setAttribute("aria-label","Ouvrir le menu");menuToggle.querySelector("i").className="fa-solid fa-bars"})})}</script></body></html>';
}

function adminNavLink(string $href, string $icon, string $label, bool $active): string
{
    return '<a class="'.($active ? 'active' : '').'" href="'.$href.'"><i class="fa-solid '.$icon.'"></i>'.$label.'</a>';
}

function adminStatusBadge(?string $status): string
{
    $status = $status ?: 'inconnu';
    $class = match ($status) {
        'actif', 'active', 'paid' => 'ok',
        'pending', 'en attente' => 'wait',
        'suspendu', 'inactif', 'overdue' => 'bad',
        default => 'neutral',
    };

    return '<span class="badge '.$class.'" style="align-items:center;justify-self:start;width:max-content;max-width:100%;white-space:nowrap">'.e($status).'</span>';
}

function exportDataset(Business $business, string $type): array
{
    abort_unless(in_array($type, ['products', 'profitability', 'sales', 'expenses', 'customers', 'receivables', 'supplier-debts', 'employees', 'payrolls'], true), 404);

    if ($type === 'products') {
        return [
            'title' => 'Rapport produits',
            'headers' => ['Produit', 'Catégorie', 'Note', 'Stock', 'Seuil', 'Prix achat', 'Prix vente'],
            'rows' => Product::query()
                ->where('business_id', $business->id)
                ->with('category')
                ->get()
                ->map(fn ($product) => [
                    $product->name,
                    $product->category?->name ?: '',
                    $product->notes ?: '-',
                    $product->stock_quantity,
                    $product->alert_threshold,
                    $product->purchase_price,
                    $product->sale_price,
                ]),
        ];
    }

    if ($type === 'profitability') {
        return [
            'title' => 'Rapport rentabilite produits',
            'headers' => ['Produit', 'Categorie', 'Prix achat', 'Prix vente', 'Marge unitaire', 'Taux marge', 'Stock', 'Rentabilite stock'],
            'rows' => Product::query()
                ->where('business_id', $business->id)
                ->with('category')
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    $purchasePrice = (int) $product->purchase_price;
                    $salePrice = (int) $product->sale_price;
                    $unitMargin = $salePrice - $purchasePrice;
                    $stockQuantity = (float) $product->stock_quantity;

                    return [
                        $product->name,
                        $product->category?->name ?: '',
                        $purchasePrice,
                        $salePrice,
                        $unitMargin,
                        $purchasePrice > 0 ? round(($unitMargin / $purchasePrice) * 100, 2).'%' : '-',
                        $stockQuantity,
                        (int) round($unitMargin * $stockQuantity),
                    ];
                }),
        ];
    }

    if ($type === 'sales') {
        return [
            'title' => 'Rapport ventes',
            'headers' => ['Facture', 'Vendeur', 'Client', 'Paiement', 'Statut', 'Sous-total', 'Remise', 'Total', 'Date'],
            'rows' => Sale::query()
                ->where('business_id', $business->id)
                ->with(['customer', 'seller:id,name,username,phone'])
                ->latest()
                ->get()
                ->map(fn ($sale) => [
                    $sale->number,
                    $sale->seller?->username ?: $sale->seller?->name ?: '',
                    $sale->customer?->name ?: '',
                    paymentLabel($sale->payment_method),
                    saleStatusLabel($sale->status),
                    $sale->subtotal,
                    $sale->discount,
                    $sale->total,
                    optional($sale->sold_at)->format('d/m/Y H:i'),
                ]),
        ];
    }

    if ($type === 'customers') {
        return [
            'title' => 'Rapport clients',
            'headers' => ['Client', 'Téléphone', 'Email', 'Création'],
            'rows' => Customer::query()
                ->where('business_id', $business->id)
                ->latest()
                ->get()
                ->map(fn ($customer) => [
                    $customer->name,
                    $customer->phone,
                    $customer->email,
                    optional($customer->created_at)->format('d/m/Y H:i'),
                ]),
        ];
    }

    if ($type === 'receivables') {
        return [
            'title' => 'Rapport créances',
            'headers' => ['Client', 'Montant dû', 'Payé', 'Reste', 'Échéance', 'Statut', 'Notes'],
            'rows' => Receivable::query()
                ->where('business_id', $business->id)
                ->with('customer')
                ->latest()
                ->get()
                ->map(fn ($receivable) => [
                    $receivable->customer?->name ?: '',
                    $receivable->amount_due,
                    $receivable->amount_paid,
                    max(0, $receivable->amount_due - $receivable->amount_paid),
                    optional($receivable->due_date)->format('d/m/Y'),
                    $receivable->status,
                    $receivable->notes,
                ]),
        ];
    }

    if ($type === 'supplier-debts') {
        return [
            'title' => 'Rapport dettes fournisseurs',
            'headers' => ['Fournisseur', 'Montant dû', 'Payé', 'Reste', 'Échéance', 'Statut', 'Notes'],
            'rows' => SupplierDebt::query()
                ->where('business_id', $business->id)
                ->with('supplier')
                ->latest()
                ->get()
                ->map(fn ($debt) => [
                    $debt->supplier?->name ?: '',
                    $debt->amount_due,
                    $debt->amount_paid,
                    max(0, $debt->amount_due - $debt->amount_paid),
                    optional($debt->due_date)->format('d/m/Y'),
                    $debt->status,
                    $debt->notes,
                ]),
        ];
    }

    if ($type === 'employees') {
        return [
            'title' => 'Rapport employés',
            'headers' => ['Employé', 'Poste', 'Type', 'Téléphone', 'Salaire', 'Date paiement salaire', 'Embauche'],
            'rows' => Employee::query()
                ->where('business_id', $business->id)
                ->with('user:id,name,phone')
                ->latest()
                ->get()
                ->map(fn ($employee) => [
                    $employee->name,
                    $employee->position,
                    $employee->type,
                    $employee->user?->phone ?: '',
                    $employee->salary,
                salaryPaymentDayMonth($employee->salary_payment_date),
                    optional($employee->hired_at)->format('d/m/Y'),
                ]),
        ];
    }

    if ($type === 'payrolls') {
        return [
            'title' => 'Rapport paies',
            'headers' => ['Période', 'Employé', 'Brut', 'Avance', 'Net', 'Statut', 'Paiement'],
            'rows' => Payroll::query()
                ->where('business_id', $business->id)
                ->with('employee')
                ->latest()
                ->get()
                ->map(fn ($payroll) => [
                    $payroll->period,
                    $payroll->employee?->name ?: '',
                    $payroll->gross_salary,
                    $payroll->salary_advance,
                    $payroll->net_salary,
                    $payroll->status,
                    optional($payroll->paid_at)->format('d/m/Y H:i'),
                ]),
        ];
    }

    return [
        'title' => 'Rapport charges',
        'headers' => ['Charge', 'Catégorie', 'Type', 'Montant', 'Date', 'Notes'],
        'rows' => Expense::query()
            ->where('business_id', $business->id)
            ->latest('spent_on')
            ->get()
            ->map(fn ($expense) => [
                $expense->name,
                $expense->category,
                $expense->type,
                $expense->amount,
                optional($expense->spent_on)->format('d/m/Y'),
                $expense->notes,
            ]),
    ];
}

function printableDatasetResponse(Business $business, string $title, array $headers, $rows)
{
    $branding = documentBranding($business);
    $headerCells = collect($headers)->map(fn ($header) => '<th><i class="'.e(documentHeaderIcon($header)).'"></i> '.e($header).'</th>')->implode('');
    $bodyRows = collect($rows)->map(function ($row) {
        return '<tr>'.collect($row)->map(fn ($cell) => '<td>'.e((string) $cell).'</td>')->implode('').'</tr>';
    })->implode('');

    if ($bodyRows === '') {
        $bodyRows = '<tr><td colspan="'.count($headers).'">Aucune donnée disponible.</td></tr>';
    }

    $html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).' - EasyMarket</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap");:root{--primary:'.e($branding['primary_color']).';--accent:'.e($branding['secondary_color']).';--ink:#17211b;--muted:#3f5048;--line:#dfe7e2;--paper:#f6faf8}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Poppins,Arial,sans-serif}.toolbar{position:sticky;top:0;background:var(--primary);color:white;padding:12px 20px;display:flex;justify-content:space-between;gap:12px}.toolbar strong,.btn,h2{display:inline-flex;align-items:center;gap:8px}.btn{border:0;border-radius:8px;background:var(--accent);color:white;padding:10px 14px;font-weight:700}.sheet{width:min(1040px,calc(100% - 24px));min-height:calc(100vh - 48px);margin:24px auto;background:white;border:1px solid var(--line);border-radius:10px;padding:34px;box-shadow:0 20px 55px rgba(25,59,50,.12);display:flex;flex-direction:column}.head{border-bottom:2px solid var(--primary);padding-bottom:18px;margin-bottom:20px}.doc-brand{display:flex;align-items:flex-start;gap:14px}.doc-brand img,.doc-logo{width:62px;height:62px;object-fit:contain;border:1px solid var(--line);border-radius:8px;padding:5px}.doc-logo{display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;font-size:24px}.doc-brand h1{margin:0 0 6px}.doc-brand p{margin:2px 0;color:var(--muted)}.doc-detail{display:flex;align-items:center;gap:12px}.doc-detail i{width:18px;text-align:center;flex:0 0 18px}.doc-detail i,h2 i,th i,.footer i{color:var(--primary)}h2{margin:0 0 14px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid var(--line);padding:10px 8px;text-align:left;vertical-align:top}th{color:var(--primary);font-size:13px}.footer{margin-top:auto;padding-top:14px;border-top:1px solid var(--line);color:var(--muted);text-align:center;font-size:12px;display:flex;align-items:center;justify-content:center;gap:7px}@media print{body{background:white}.toolbar{display:none}.sheet{width:100%;min-height:100vh;margin:0;border:0;box-shadow:none;border-radius:0}}</style></head><body><div class="toolbar"><strong><i class="fa-solid fa-file-pdf"></i>'.e($title).'</strong><button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimer / PDF</button></div><main class="sheet"><section class="head">'.$branding['header_html'].'</section><h2><i class="fa-solid fa-table"></i>'.e($title).'</h2><table><thead><tr>'.$headerCells.'</tr></thead><tbody>'.$bodyRows.'</tbody></table><p class="footer"><i class="fa-solid fa-circle-check"></i>Document généré par l\'application EasyMarket.</p></main></body></html>';

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
}

function csvResponse(string $filename, array $header, $rows)
{
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header, ';');

    foreach ($rows as $row) {
        fputcsv($handle, $row, ';');
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return response("\xEF\xBB\xBF".$csv)
        ->header('Content-Type', 'text/csv; charset=UTF-8')
        ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
}

function subscriptionPlans(): array
{
    return [
        'monthly' => [
            'label' => 'Mensuel',
            'amount' => 5000,
            'duration' => '1 mois',
        ],
        'yearly' => [
            'label' => 'Annuel',
            'amount' => 50000,
            'duration' => '1 an',
        ],
        'lifetime' => [
            'label' => 'Licence à vie',
            'amount' => 100000,
            'duration' => 'à vie',
        ],
    ];
}

function subscriptionPlanLabel(?string $plan): string
{
    return subscriptionPlans()[$plan]['label'] ?? (string) $plan;
}

function plannedSubscriptionEnd(string $plan, $start = null)
{
    $date = $start ? $start->copy() : now();

    return match ($plan) {
        'monthly' => $date->addMonth(),
        'yearly' => $date->addYear(),
        'lifetime' => $date->addYears(100),
        default => $date->addMonth(),
    };
}

function hasActiveSubscription(Business $business): bool
{
    return $business->subscriptions()
        ->whereIn('status', ['actif', 'active'])
        ->where(function ($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->exists();
}

function ensureActiveSubscription(Business $business): void
{
    if (! hasActiveSubscription($business)) {
        abort(response()->json([
            'message' => 'Votre abonnement est en attente de validation. Activez-le avant de commencer les opérations.',
        ], 403));
    }
}

function nextSaleNumber(Business $business): string
{
    $prefix = 'FAC-'.now()->format('Y').'-';
    $lastNumber = Sale::query()
        ->where('number', 'like', $prefix.'%')
        ->lockForUpdate()
        ->orderByDesc('number')
        ->value('number');
    $next = $lastNumber && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $lastNumber, $matches)
        ? ((int) $matches[1]) + 1
        : 1;

    do {
        $number = $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        $next++;
    } while (Sale::query()->where('number', $number)->exists());

    return $number;
}

function paymentLabel(string $method): string
{
    return [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'credit' => 'Crédit',
    ][$method] ?? $method;
}

function saleStatusLabel(?string $status): string
{
    return [
        'completed' => 'Validée',
        'draft' => 'Brouillon',
        'cancelled' => 'Annulée',
        'paid' => 'Payée',
        'pending' => 'En attente',
    ][$status] ?? ($status ?: '-');
}

function employeeTypeLabel(?string $type): string
{
    return [
        'seller' => 'Vendeur',
        'cashier' => 'Caissier',
        'accountant' => 'Comptable',
        'observer' => 'Observateur',
    ][$type] ?? trim((string) $type);
}

function documentColor(?string $color, string $fallback): string
{
    return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color) ? (string) $color : $fallback;
}

function documentLogoUrl(Business $business): string
{
    if (! $business->show_logo_on_documents || ! $business->logo_path) {
        return '';
    }

    $logoPath = (string) $business->logo_path;
    if (Str::startsWith($logoPath, ['http://', 'https://', '/'])) {
        return $logoPath;
    }

    return asset('storage/'.$logoPath);
}

function documentBranding(Business $business, array $options = []): array
{
    $descriptionHtml = $business->show_description_on_documents && $business->description
        ? '<p class="muted doc-detail"><i class="fa-solid fa-align-left"></i>'.e($business->description).'</p>'
        : '';
    $details = array_values(array_filter([
        $business->show_phone_on_documents && $business->phone ? ['fa-solid fa-phone', 'Tél. boutique : '.$business->phone] : null,
        $business->show_whatsapp_on_documents && $business->whatsapp_phone ? ['fa-brands fa-whatsapp', 'WhatsApp boutique : '.$business->whatsapp_phone] : null,
        $business->show_address_on_documents && $business->address ? ['fa-solid fa-location-dot', $business->address] : null,
        $business->show_ifu_on_documents && $business->ifu ? ['fa-solid fa-id-card', 'IFU : '.$business->ifu] : null,
        $business->show_slogan_on_documents && $business->slogan ? ['fa-solid fa-quote-left', $business->slogan] : null,
    ]));
    $logoUrl = documentLogoUrl($business);
    $logoImgClass = $options['logo_img_class'] ?? '';
    $fallbackLogoClass = $options['fallback_logo_class'] ?? 'doc-logo';
    $detailsClass = $options['details_class'] ?? '';
    $logoHtml = $logoUrl
        ? '<img'.($logoImgClass ? ' class="'.e($logoImgClass).'"' : '').' src="'.e($logoUrl).'" alt="Logo '.e($business->name).'">'
        : '<span class="'.e($fallbackLogoClass).'"><i class="fa-solid fa-store"></i></span>';
    $detailsHtml = collect($details)->map(fn ($item) => '<p class="muted doc-detail"><i class="'.e($item[0]).'"></i>'.e($item[1]).'</p>')->implode('');
    $detailsHtml = $detailsHtml && $detailsClass ? '<div class="'.e($detailsClass).'">'.$detailsHtml.'</div>' : $detailsHtml;

    return [
        'primary_color' => documentColor($business->primary_color, '#193b32'),
        'secondary_color' => documentColor($business->secondary_color, '#f5b84b'),
        'header_html' => '<div class="doc-brand">'.$logoHtml.'<div><h1>'.e($business->name).'</h1>'.$descriptionHtml.$detailsHtml.'</div></div>',
    ];
}

function documentHeaderIcon(string $header): string
{
    $value = Str::lower(Str::ascii($header));

    if (str_contains($value, 'date') || str_contains($value, 'periode') || str_contains($value, 'echeance')) {
        return 'fa-solid fa-calendar-days';
    }

    if (str_contains($value, 'montant') || str_contains($value, 'total') || str_contains($value, 'prix') || str_contains($value, 'reste') || str_contains($value, 'solde') || str_contains($value, 'salaire')) {
        return 'fa-solid fa-coins';
    }

    if (str_contains($value, 'produit') || str_contains($value, 'stock') || str_contains($value, 'seuil')) {
        return 'fa-solid fa-box';
    }

    if (str_contains($value, 'client') || str_contains($value, 'employe') || str_contains($value, 'vendeur')) {
        return 'fa-solid fa-user';
    }

    if (str_contains($value, 'fournisseur')) {
        return 'fa-solid fa-truck-field';
    }

    if (str_contains($value, 'categorie') || str_contains($value, 'type') || str_contains($value, 'statut')) {
        return 'fa-solid fa-tag';
    }

    if (str_contains($value, 'telephone') || str_contains($value, 'phone')) {
        return 'fa-solid fa-phone';
    }

    return 'fa-solid fa-circle-info';
}

function documentCsvHeaderRows(Business $business): array
{
    $rows = [
        ['Boutique', $business->name],
    ];

    if ($business->show_phone_on_documents && $business->phone) {
        $rows[] = ['Téléphone boutique', $business->phone];
    }

    if ($business->show_whatsapp_on_documents && $business->whatsapp_phone) {
        $rows[] = ['WhatsApp boutique', $business->whatsapp_phone];
    }

    if ($business->show_address_on_documents && $business->address) {
        $rows[] = ['Adresse', $business->address];
    }

    if ($business->show_ifu_on_documents && $business->ifu) {
        $rows[] = ['IFU', $business->ifu];
    }

    if ($business->show_slogan_on_documents && $business->slogan) {
        $rows[] = ['Slogan', $business->slogan];
    }

    if ($business->show_description_on_documents && $business->description) {
        $rows[] = ['Description', $business->description];
    }

    $rows[] = [];

    return $rows;
}

function reportRange(string $period, ?Request $request = null): array
{
    if ($period === 'custom') {
        $start = $request?->date('start')?->startOfDay();
        $end = $request?->date('end')?->endOfDay();

        abort_unless($start && $end && $start->lte($end), 422, 'Période de rapport invalide.');

        return [$start, $end];
    }

    return match ($period) {
        'daily' => [now()->startOfDay(), now()->endOfDay()],
        'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
        'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
        'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
        'yearly' => [now()->startOfYear(), now()->endOfYear()],
    };
}

function taxRequestPeriod(Request $request): string
{
    $period = $request->query('period', 'monthly');
    abort_unless(in_array($period, ['monthly', 'quarterly', 'yearly', 'custom'], true), 404);

    return $period;
}

function fullTaxDocumentsHtml(Business $business, $start, $end, callable $money): string
{
    $sales = Sale::query()
        ->where('business_id', $business->id)
        ->where('status', 'completed')
        ->whereBetween('sold_at', [$start, $end])
        ->with(['customer:id,name,phone', 'seller:id,name,username'])
        ->oldest('sold_at')
        ->get();
    $expenses = Expense::query()
        ->where('business_id', $business->id)
        ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
        ->oldest('spent_on')
        ->get();
    $receivables = Receivable::query()
        ->where('business_id', $business->id)
        ->with('customer:id,name,phone')
        ->orderBy('due_date')
        ->get();
    $supplierDebts = SupplierDebt::query()
        ->where('business_id', $business->id)
        ->with('supplier:id,name,phone')
        ->orderBy('due_date')
        ->get();
    $payrolls = Payroll::query()
        ->where('business_id', $business->id)
        ->whereBetween('period', [$start->format('Y-m'), $end->format('Y-m')])
        ->with('employee:id,name')
        ->orderBy('period')
        ->get();

    $paymentLabels = [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'credit' => 'Crédit',
    ];
    $statusLabels = [
        'completed' => 'Validée',
        'current' => 'À jour',
        'overdue' => 'En retard',
        'paid' => 'Payée',
        'pending' => 'En attente',
    ];
    $status = fn ($value) => $statusLabels[$value] ?? (string) $value;
    $empty = fn (int $colspan, string $message) => '<tr><td colspan="'.$colspan.'">'.e($message).'</td></tr>';

    $salesRows = $sales->map(fn ($sale) => '<tr><td>'.e(optional($sale->sold_at)->format('d/m/Y H:i')).'</td><td>'.e($sale->number).'</td><td>'.e($sale->customer?->name ?: '-').'</td><td>'.e($sale->seller?->username ?: $sale->seller?->name ?: '-').'</td><td>'.e($paymentLabels[$sale->payment_method] ?? $sale->payment_method).'</td><td>'.$money($sale->total).'</td></tr>')->implode('')
        ?: $empty(6, 'Aucune recette sur la période.');
    $expenseRows = $expenses->map(fn ($expense) => '<tr><td>'.e(optional($expense->spent_on)->format('d/m/Y')).'</td><td>'.e($expense->name).'</td><td>'.e($expense->category ?: '-').'</td><td>'.$money($expense->amount).'</td></tr>')->implode('')
        ?: $empty(4, 'Aucune dépense sur la période.');
    $journalRows = $sales->map(fn ($sale) => '<tr><td>'.e(optional($sale->sold_at)->format('d/m/Y H:i')).'</td><td>'.e($sale->number).'</td><td>'.e($sale->customer?->name ?: '-').'</td><td>'.e($paymentLabels[$sale->payment_method] ?? $sale->payment_method).'</td><td>'.$money($sale->subtotal).'</td><td>'.$money($sale->discount).'</td><td>'.$money($sale->total).'</td></tr>')->implode('')
        ?: $empty(7, 'Aucune vente validée sur la période.');
    $receivableRows = $receivables->map(fn ($item) => '<tr><td>Client</td><td>'.e($item->customer?->name ?: '-').'</td><td>'.e($item->customer?->phone ?: '-').'</td><td>'.$money($item->amount_due).'</td><td>'.$money($item->amount_paid).'</td><td>'.$money(max(0, $item->amount_due - $item->amount_paid)).'</td><td>'.e(optional($item->due_date)->format('d/m/Y') ?: '-').'</td><td>'.e($status($item->status)).'</td></tr>')->implode('');
    $debtRows = $supplierDebts->map(fn ($item) => '<tr><td>Fournisseur</td><td>'.e($item->supplier?->name ?: '-').'</td><td>'.e($item->supplier?->phone ?: '-').'</td><td>'.$money($item->amount_due).'</td><td>'.$money($item->amount_paid).'</td><td>'.$money(max(0, $item->amount_due - $item->amount_paid)).'</td><td>'.e(optional($item->due_date)->format('d/m/Y') ?: '-').'</td><td>'.e($status($item->status)).'</td></tr>')->implode('');
    $balanceRows = ($receivableRows.$debtRows) ?: $empty(8, 'Aucune créance ni dette fournisseur enregistrée.');
    $payrollRows = $payrolls->map(fn ($payroll) => '<tr><td>'.e($payroll->period).'</td><td>'.e($payroll->employee?->name ?: '-').'</td><td>'.$money($payroll->gross_salary).'</td><td>'.$money($payroll->salary_advance).'</td><td>'.$money($payroll->net_salary).'</td><td>'.e($status($payroll->status)).'</td></tr>')->implode('')
        ?: $empty(6, 'Aucune fiche de paie sur la période.');

    return '
        <section class="tax-detail-section">
            <h2><i class="fa-solid fa-receipt"></i>Rapport des recettes</h2>
            <table><thead><tr><th>Date</th><th>Facture</th><th>Client</th><th>Vendeur</th><th>Paiement</th><th>Total</th></tr></thead><tbody>'.$salesRows.'</tbody></table>
        </section>
        <section class="tax-detail-section">
            <h2><i class="fa-solid fa-money-bill-wave"></i>Rapport des dépenses détaillées</h2>
            <table><thead><tr><th>Date</th><th>Charge</th><th>Catégorie</th><th>Montant</th></tr></thead><tbody>'.$expenseRows.'</tbody></table>
        </section>
        <section class="tax-detail-section">
            <h2><i class="fa-solid fa-book"></i>Journal des ventes</h2>
            <table><thead><tr><th>Date</th><th>Facture</th><th>Client</th><th>Paiement</th><th>Sous-total</th><th>Remise</th><th>Total</th></tr></thead><tbody>'.$journalRows.'</tbody></table>
        </section>
        <section class="tax-detail-section">
            <h2><i class="fa-solid fa-scale-balanced"></i>Bilan des créances clients et dettes fournisseurs</h2>
            <table><thead><tr><th>Type</th><th>Nom</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Échéance</th><th>Statut</th></tr></thead><tbody>'.$balanceRows.'</tbody></table>
        </section>
        <section class="tax-detail-section">
            <h2><i class="fa-solid fa-file-invoice-dollar"></i>Fiches de paie du personnel</h2>
            <table><thead><tr><th>Période</th><th>Employé</th><th>Brut</th><th>Avances</th><th>Net</th><th>Statut</th></tr></thead><tbody>'.$payrollRows.'</tbody></table>
        </section>
    ';
}

function reportData(Business $business, $start, $end, string $period): array
{
    $salesTotal = (int) Sale::query()
        ->where('business_id', $business->id)
        ->whereBetween('sold_at', [$start, $end])
        ->where('status', 'completed')
        ->sum('total');

    $salesByPayment = Sale::query()
        ->where('business_id', $business->id)
        ->whereBetween('sold_at', [$start, $end])
        ->where('status', 'completed')
        ->select('payment_method')
        ->selectRaw('COUNT(*) as count')
        ->selectRaw('COALESCE(SUM(total), 0) as total')
        ->groupBy('payment_method')
        ->orderByDesc('total')
        ->get();

    $expensesTotal = (int) Expense::query()
        ->where('business_id', $business->id)
        ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
        ->sum('amount');

    $expensesByCategory = Expense::query()
        ->where('business_id', $business->id)
        ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
        ->select('category')
        ->selectRaw('COUNT(*) as count')
        ->selectRaw('COALESCE(SUM(amount), 0) as total')
        ->groupBy('category')
        ->orderByDesc('total')
        ->get();

    $payrollsTotal = (int) Payroll::query()
        ->where('business_id', $business->id)
        ->whereBetween('period', [$start->format('Y-m'), $end->format('Y-m')])
        ->sum('net_salary');

    $payrollsGrossTotal = (int) Payroll::query()
        ->where('business_id', $business->id)
        ->whereBetween('period', [$start->format('Y-m'), $end->format('Y-m')])
        ->sum('gross_salary');

    $salaryAdvancesTotal = (int) SalaryAdvance::query()
        ->where('business_id', $business->id)
        ->whereBetween('advanced_on', [$start->toDateString(), $end->toDateString()])
        ->sum('amount');

    $costOfGoodsSold = (int) DB::table('sale_items')
        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
        ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
        ->where('sales.business_id', $business->id)
        ->where('sales.status', 'completed')
        ->whereBetween('sales.sold_at', [$start, $end])
        ->selectRaw('COALESCE(SUM(sale_items.quantity * COALESCE(products.purchase_price, 0)), 0) as total')
        ->value('total');

    $receivablesRemaining = (int) Receivable::query()
        ->where('business_id', $business->id)
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');

    $debtsRemaining = (int) SupplierDebt::query()
        ->where('business_id', $business->id)
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');

    $stockValue = (int) Product::query()
        ->where('business_id', $business->id)
        ->where('is_active', true)
        ->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as total')
        ->value('total');

    $productsCount = Product::query()
        ->where('business_id', $business->id)
        ->where('is_active', true)
        ->where('stock_quantity', '>', 0)
        ->count();

    $lowStockCount = Product::query()
        ->where('business_id', $business->id)
        ->where('is_active', true)
        ->whereColumn('stock_quantity', '<=', 'alert_threshold')
        ->count();

    $stockByCategory = Product::query()
        ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
        ->where('products.business_id', $business->id)
        ->where('products.is_active', true)
        ->selectRaw("COALESCE(categories.name, 'Non classé') as category")
        ->selectRaw('COUNT(*) as products_count')
        ->selectRaw('COALESCE(SUM(products.stock_quantity), 0) as stock_quantity')
        ->selectRaw('COALESCE(SUM(products.stock_quantity * products.purchase_price), 0) as stock_value')
        ->groupBy('category')
        ->orderByDesc('stock_value')
        ->limit(8)
        ->get();

    $receivablesCount = Receivable::query()
        ->where('business_id', $business->id)
        ->whereColumn('amount_paid', '<', 'amount_due')
        ->count();

    $debtsCount = SupplierDebt::query()
        ->where('business_id', $business->id)
        ->whereColumn('amount_paid', '<', 'amount_due')
        ->count();

    $balanceAssetsTotal = $stockValue + $receivablesRemaining;
    $balanceEquityEstimate = $balanceAssetsTotal - $debtsRemaining;

    $topProducts = DB::table('sale_items')
        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
        ->where('sales.business_id', $business->id)
        ->where('sales.status', 'completed')
        ->whereBetween('sales.sold_at', [$start, $end])
        ->select('sale_items.product_name')
        ->selectRaw('SUM(sale_items.quantity) as quantity')
        ->selectRaw('SUM(sale_items.total) as total')
        ->groupBy('sale_items.product_name')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    return [
        'period' => $period,
        'period_label' => [
            'daily' => 'Rapport journalier',
            'weekly' => 'Rapport hebdomadaire',
            'monthly' => 'Rapport mensuel',
            'quarterly' => 'Rapport trimestriel',
            'yearly' => 'Rapport annuel',
            'custom' => 'Rapport personnalisé',
        ][$period],
        'range_label' => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
        'sales_total' => $salesTotal,
        'sales_by_payment' => $salesByPayment,
        'expenses_total' => $expensesTotal,
        'expenses_by_category' => $expensesByCategory,
        'payrolls_gross_total' => $payrollsGrossTotal,
        'salary_advances_total' => $salaryAdvancesTotal,
        'payrolls_total' => $payrollsTotal,
        'cost_of_goods_sold' => $costOfGoodsSold,
        'gross_margin' => $salesTotal - $costOfGoodsSold,
        'net_result' => $salesTotal - $costOfGoodsSold - $expensesTotal - $payrollsGrossTotal,
        'receivables_remaining' => $receivablesRemaining,
        'debts_remaining' => $debtsRemaining,
        'stock_value' => $stockValue,
        'products_count' => $productsCount,
        'low_stock_count' => $lowStockCount,
        'stock_by_category' => $stockByCategory,
        'receivables_count' => $receivablesCount,
        'debts_count' => $debtsCount,
        'balance_assets_total' => $balanceAssetsTotal,
        'balance_equity_estimate' => $balanceEquityEstimate,
        'top_products' => $topProducts,
    ];
}

function taxFaq(): array
{
    return [
        [
            'question' => "Comment EasyMarket m'aide à préparer ma déclaration ?",
            'answer' => "EasyMarket centralise les ventes, charges, stocks, créances, dettes, paies et avances. Le module Impôts & comptabilité transforme ces données en bilan mensuel estimé et en liste de documents à transmettre au comptable. L'application ne remplace pas une déclaration officielle : elle prépare un dossier exploitable, limite les oublis et permet de justifier les montants. Exemple : avant de déclarer, imprimez le bilan, le journal des ventes, les charges et l'état des créances ; comparez ensuite ces documents avec la caisse, les relevés Mobile Money et les factures fournisseurs.",
            'paragraphs' => [
                "EasyMarket prépare un dossier de travail à partir des opérations enregistrées dans la boutique.",
                "L’application ne remplace pas le comptable ni la déclaration officielle, mais elle réduit les oublis et facilite les contrôles.",
            ],
            'points' => [
                'Centralisation des ventes, charges, stocks, créances, dettes, paies et avances.',
                'Bilan mensuel estimé.',
                'Documents imprimables ou exportables pour le comptable.',
                'Meilleur rapprochement entre activité réelle et justificatifs.',
            ],
            'example' => "Avant de déclarer, imprimez le bilan, le journal des ventes, les charges et l’état des créances, puis comparez avec la caisse et les relevés Mobile Money.",
            'links' => [],
        ],
        [
            'question' => "Qu'est-ce que l'IFU et à quoi ça sert ?",
            'answer' => "L'IFU est l'identifiant fiscal unique de l'entreprise ou du contribuable. Il sert à vous reconnaître auprès de la Direction Générale des Impôts, à créer ou accéder à certains services fiscaux, à établir des documents commerciaux et à faciliter les déclarations. En pratique, gardez votre IFU dans le dossier administratif de la boutique et vérifiez qu'il correspond au nom juridique de l'entreprise. Exemple : si votre boutique vend sous une enseigne commerciale mais que l'entreprise est enregistrée au nom d'une personne physique ou d'une société, le comptable doit utiliser l'identité fiscale correcte pour les déclarations.",
            'paragraphs' => [
                "L'IFU est l'identifiant fiscal unique de l'entreprise ou du contribuable. Il sert à vous reconnaître auprès de la Direction Générale des Impôts.",
                "Il doit être conservé dans le dossier administratif de la boutique et utilisé avec l'identité fiscale correcte de l'entreprise.",
            ],
            'points' => [
                'Créer ou accéder à certains services fiscaux.',
                'Identifier correctement l’entreprise sur les documents commerciaux.',
                'Faciliter les déclarations et échanges avec l’administration.',
            ],
            'example' => "Si la boutique vend sous une enseigne commerciale mais que l'activité est enregistrée au nom d'une personne physique, le comptable doit déclarer avec l'identité fiscale officielle.",
            'links' => [
                ['label' => 'IFU Online - DGI Bénin', 'url' => 'https://ifu.impots.bj/'],
            ],
        ],
        [
            'question' => "Comment ouvrir son compte e-MECeF ?",
            'answer' => "Pour utiliser e-MECeF, préparez d'abord les informations fiscales de l'entreprise : IFU, identité du contribuable, contacts, adresse et informations de l'activité. Accédez ensuite à la plateforme e-MECeF de la DGI, suivez la procédure d'inscription ou de connexion disponible, puis configurez le mode de facturation adapté à votre activité. Si vous utilisez un logiciel ou un Système de Facturation Électronique approuvé, vous pouvez avoir besoin d'un jeton d'accès pour connecter le logiciel à e-MECeF. Avant de commencer à facturer réellement, faites valider avec votre comptable ou votre fournisseur logiciel que les factures comportent bien les mentions attendues, notamment les éléments de sécurisation, le numéro unique et le QR code lorsque le système les génère.",
            'paragraphs' => [
                "e-MECeF sert à produire des factures normalisées via la plateforme de la DGI ou via un logiciel compatible.",
                "Avant l’ouverture ou la configuration, préparez vos informations fiscales : IFU, identité du contribuable, contacts, adresse et activité.",
            ],
            'points' => [
                'Accéder à la plateforme officielle e-MECeF.',
                'Suivre la procédure d’inscription ou de connexion disponible.',
                'Configurer le mode de facturation adapté à votre activité.',
                'Demander un jeton d’accès si un logiciel approuvé doit être connecté.',
            ],
            'example' => "Si vous utilisez un logiciel de caisse compatible, le fournisseur peut vous demander un jeton e-MECeF pour connecter la facturation au système de la DGI.",
            'links' => [
                ['label' => 'Plateforme e-MECeF', 'url' => 'https://e-mecef.impots.bj/'],
                ['label' => 'Guide de facturation normalisée', 'url' => 'https://api.impots.bj/media/62b9ccd77489a_GUIDE%20DE%20FACTURATION%20NORMALIS%C3%89E.pdf'],
            ],
        ],
        [
            'question' => "Quand et comment faire ses déclarations fiscales ?",
            'answer' => "Les déclarations dépendent de votre régime fiscal, de votre secteur, de votre chiffre d'affaires et des impôts auxquels l'entreprise est assujettie. En pratique, ne préparez pas les déclarations le dernier jour : clôturez vos ventes, charges, salaires, créances et dettes dès la fin de chaque mois, contrôlez les justificatifs, puis transmettez le dossier au comptable. Sur le portail e-services, l'espace fiscal permet de télédéclarer et de payer en ligne les impôts et taxes de l'entreprise. Exemple : pour une déclaration mensuelle, vous pouvez exporter le journal des ventes, le rapport des charges, les paies et l'état des créances le 1er ou le 2 du mois suivant, puis laisser le comptable vérifier les montants avant dépôt.",
            'paragraphs' => [
                "Les échéances dépendent du régime fiscal, du secteur, du chiffre d’affaires et des impôts auxquels l’entreprise est assujettie.",
                "La bonne pratique est de préparer le dossier dès la fin de chaque mois, puis de laisser le comptable vérifier avant dépôt.",
            ],
            'points' => [
                'Clôturer les ventes et encaissements de la période.',
                'Contrôler les charges, salaires, créances et dettes.',
                'Rapprocher les justificatifs avec la caisse, la banque ou Mobile Money.',
                'Télédéclarer et payer via le portail e-services lorsque l’entreprise y est assujettie.',
            ],
            'example' => "Pour une déclaration mensuelle, exportez le journal des ventes, les charges, les paies et les créances le 1er ou le 2 du mois suivant, puis transmettez-les au comptable.",
            'links' => [
                ['label' => 'Portail e-services DGI', 'url' => 'https://e-services.impots.bj/'],
                ['label' => 'Procédure de télédéclaration', 'url' => 'https://api.impots.bj/media/624b12102d0cb_PROC%C3%89DURE%20DE%20TELEDECLARATION%20SUR%20E-SERVICES.pdf'],
            ],
        ],
        [
            'question' => "Quels documents dois-je garder avant de déclarer ?",
            'answer' => "Conservez au minimum les factures de vente, factures d'achat, reçus de paiement, relevés Mobile Money ou bancaires, justificatifs de charges, fiches de paie, avances sur salaire, états de créances clients, dettes fournisseurs et inventaire du stock. Le plus important est la cohérence : une vente enregistrée doit pouvoir être rapprochée d'un encaissement ou d'une créance, et une charge doit avoir une preuve. Exemple : si vous payez le loyer en espèces, gardez le reçu signé ; si vous payez par Mobile Money, gardez la preuve de transaction et classez-la dans les charges du mois.",
            'paragraphs' => [
                "Gardez les pièces qui prouvent les ventes, les achats, les charges, les salaires et les mouvements d’argent.",
                "Le plus important est la cohérence : une vente doit pouvoir être rapprochée d’un encaissement ou d’une créance, et une charge doit avoir une preuve.",
            ],
            'points' => [
                'Factures de vente et factures d’achat.',
                'Reçus de paiement, relevés bancaires ou Mobile Money.',
                'Justificatifs de charges, loyers, transport, énergie et fournitures.',
                'Fiches de paie, avances sur salaire, créances clients et dettes fournisseurs.',
                'Inventaire et valorisation du stock.',
            ],
            'example' => "Si vous payez le loyer en espèces, gardez le reçu signé. Si vous payez par Mobile Money, gardez la preuve de transaction et classez-la dans les charges du mois.",
            'links' => [],
        ],
        [
            'question' => "Qu'est-ce qu'une facture normalisée et pourquoi est-elle importante ?",
            'answer' => "Une facture normalisée est une facture conforme aux exigences de l'administration fiscale. Elle permet de sécuriser la transaction, de réduire les contestations et de faciliter les contrôles. Pour les activités concernées par e-MECeF, la facture doit être produite dans le système prévu ou via un logiciel compatible. Exemple : lorsqu'un client demande une facture officielle, une simple note manuscrite ou un ticket interne ne suffit pas toujours ; il faut produire une facture conforme avec les mentions fiscales exigées.",
            'paragraphs' => [
                "Une facture normalisée est une facture conforme aux exigences de l’administration fiscale.",
                "Elle sécurise la transaction, réduit les contestations et facilite les contrôles.",
            ],
            'points' => [
                'Elle doit contenir les mentions fiscales exigées.',
                'Elle peut être produite via e-MECeF ou un logiciel compatible selon le cas.',
                'Elle est plus fiable qu’une simple note interne ou un reçu non conforme.',
            ],
            'example' => "Lorsqu’un client demande une facture officielle, une note manuscrite ne suffit pas toujours. Il faut produire une facture conforme avec les informations attendues.",
            'links' => [
                ['label' => 'e-MECeF DGI', 'url' => 'https://e-mecef.impots.bj/'],
            ],
        ],
        [
            'question' => "Qu'est-ce que le dépôt des états financiers ou e-Bilan ?",
            'answer' => "Le dépôt des états financiers concerne les entreprises qui doivent transmettre leurs états de synthèse à l'administration. Ces états ne se limitent pas au total des ventes : ils regroupent le bilan, le compte de résultat et les annexes préparés selon les règles comptables applicables. EasyMarket aide à préparer les chiffres opérationnels, mais le comptable doit les retraiter et les valider avant dépôt. Exemple : le stock valorisé, les créances clients et les dettes fournisseurs peuvent influencer le bilan même s'ils ne sont pas tous encaissés ou payés à la date de clôture.",
            'paragraphs' => [
                "Le dépôt des états financiers concerne les entreprises qui doivent transmettre leurs états de synthèse à l’administration.",
                "Ces états ne se limitent pas au total des ventes : ils regroupent le bilan, le compte de résultat et les annexes.",
            ],
            'points' => [
                'Préparer les ventes, charges, stocks, créances et dettes.',
                'Faire contrôler les chiffres par un comptable.',
                'Déposer les états financiers sur la plateforme e-Bilan lorsque l’entreprise est concernée.',
            ],
            'example' => "Le stock valorisé, les créances clients et les dettes fournisseurs peuvent influencer le bilan même s’ils ne sont pas encaissés ou payés à la date de clôture.",
            'links' => [
                ['label' => 'e-Bilan DGI', 'url' => 'https://ebilan.impots.bj/'],
            ],
        ],
        [
            'question' => "Quelle est la différence entre recettes, bénéfice et trésorerie ?",
            'answer' => "Les recettes correspondent aux ventes ou encaissements de l'activité. Le bénéfice est le résultat après déduction des charges, achats, salaires et autres coûts. La trésorerie correspond à l'argent réellement disponible en caisse, en banque ou sur Mobile Money. Exemple : vous vendez 500 000 FCFA à crédit ; votre chiffre d'affaires augmente, mais votre trésorerie n'augmente pas encore. À l'inverse, si vous remboursez une dette fournisseur, votre trésorerie baisse sans que cela soit forcément une nouvelle charge du mois.",
            'paragraphs' => [
                "Ces trois notions sont proches mais ne veulent pas dire la même chose.",
                "Les confondre peut donner une mauvaise lecture de la santé réelle de la boutique.",
            ],
            'points' => [
                'Recettes : ventes ou encaissements de l’activité.',
                'Bénéfice : ce qui reste après déduction des charges, achats et salaires.',
                'Trésorerie : argent réellement disponible en caisse, banque ou Mobile Money.',
            ],
            'example' => "Vous vendez 500 000 FCFA à crédit : le chiffre d’affaires augmente, mais la trésorerie n’augmente pas encore. Le client doit d’abord payer.",
            'links' => [],
        ],
        [
            'question' => "Quelles taxes s'appliquent à mon activité ?",
            'answer' => "Les taxes et impôts applicables varient selon le statut juridique, le régime fiscal, le chiffre d'affaires, la localisation et l'activité. Une petite boutique, un grossiste, un prestataire de service et une société structurée peuvent avoir des obligations différentes. Ne vous basez pas uniquement sur l'activité d'un voisin : demandez au comptable de confirmer votre régime et vos échéances. EasyMarket prépare les chiffres, mais la qualification fiscale reste à valider par un professionnel ou par le centre des impôts compétent.",
            'paragraphs' => [
                "Les taxes applicables varient selon le statut juridique, le régime fiscal, le chiffre d’affaires, la localisation et l’activité.",
                "Deux commerces qui vendent des produits similaires peuvent avoir des obligations différentes si leur régime fiscal ou leur structure n’est pas la même.",
                "Exemples à faire confirmer : la TVA peut concerner certaines entreprises selon leur régime et leur seuil de chiffre d'affaires ; l'impôt sur les bénéfices concerne le résultat imposable ; la patente ou contribution professionnelle peut dépendre de l'activité, de la taille et de la localisation ; les retenues à la source peuvent intervenir quand vous payez certains prestataires ; les taxes salariales et cotisations sociales peuvent s'appliquer si vous employez du personnel ; les droits de douane ou taxes à l'importation peuvent concerner les marchandises importées.",
            ],
            'points' => [
                'TVA : souvent liée au régime fiscal, au type d’opération et au chiffre d’affaires.',
                'Impôt sur les bénéfices : calculé à partir du résultat fiscal de l’activité.',
                'Patente ou contribution professionnelle : peut dépendre de l’activité exercée, du local et de la commune.',
                'Retenues à la source : possibles lors du paiement de certains fournisseurs ou prestataires.',
                'Taxes salariales et cotisations sociales : à prévoir si la boutique emploie du personnel.',
                'Droits et taxes à l’importation : à vérifier si les produits viennent de l’étranger.',
                'Vérifier le régime fiscal avec un comptable ou le centre des impôts.',
                'Identifier les impôts et taxes réellement applicables.',
                'Noter les échéances de déclaration et de paiement.',
                'Éviter de copier les pratiques fiscales d’un autre commerce sans validation.',
            ],
            'example' => "Une petite boutique, un grossiste et une société structurée peuvent avoir des obligations différentes même s’ils vendent dans le même secteur.",
            'links' => [
                ['label' => 'Direction Générale des Impôts', 'url' => 'https://www.impots.finances.gouv.bj/'],
            ],
        ],
    ];
}

function receivableStatus(Receivable $receivable): string
{
    if ($receivable->amount_paid >= $receivable->amount_due) {
        return 'paid';
    }

    if ($receivable->due_date && $receivable->due_date->isPast()) {
        return 'overdue';
    }

    return 'current';
}

function enrichReceivable(Receivable $receivable): array
{
    $status = receivableStatus($receivable);
    $invoiceNumber = preg_match('/Facture\s+([A-Z]+-\d{4}-\d+)/i', (string) $receivable->notes, $matches)
        ? $matches[1]
        : null;
    $invoice = $invoiceNumber
        ? Sale::query()
            ->where('business_id', $receivable->business_id)
            ->where('number', $invoiceNumber)
            ->first(['id', 'number', 'total', 'sold_at'])
        : null;
    $invoice ??= Sale::query()
        ->where('business_id', $receivable->business_id)
        ->where('customer_id', $receivable->customer_id)
        ->where('payment_method', 'credit')
        ->where('total', $receivable->amount_due)
        ->when($receivable->due_date, fn ($query) => $query->whereDate('credit_due_date', $receivable->due_date))
        ->latest('sold_at')
        ->first(['id', 'number', 'total', 'sold_at']);

    if ($receivable->status !== $status) {
        $receivable->forceFill(['status' => $status])->save();
    }

    return [
        'id' => $receivable->id,
        'customer' => $receivable->customer,
        'amount_due' => $receivable->amount_due,
        'amount_paid' => $receivable->amount_paid,
        'remaining' => max(0, $receivable->amount_due - $receivable->amount_paid),
        'due_date' => optional($receivable->due_date)->toDateString(),
        'created_at' => optional($receivable->created_at)->toISOString(),
        'notes' => $receivable->notes,
        'status' => $status,
        'invoice' => $invoice,
        'payments' => $receivable->payments()
            ->latest('paid_at')
            ->get(['id', 'amount', 'method', 'reference', 'paid_at'])
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'paid_at' => optional($payment->paid_at)->toISOString(),
            ]),
    ];
}

function supplierDebtStatus(SupplierDebt $debt): string
{
    if ($debt->amount_paid >= $debt->amount_due) {
        return 'paid';
    }

    if ($debt->due_date && $debt->due_date->isPast()) {
        return 'overdue';
    }

    return 'current';
}

function enrichSupplierDebt(SupplierDebt $debt): array
{
    $status = supplierDebtStatus($debt);

    if ($debt->status !== $status) {
        $debt->forceFill(['status' => $status])->save();
    }

    return [
        'id' => $debt->id,
        'supplier' => $debt->supplier,
        'amount_due' => $debt->amount_due,
        'amount_paid' => $debt->amount_paid,
        'remaining' => max(0, $debt->amount_due - $debt->amount_paid),
        'due_date' => optional($debt->due_date)->toDateString(),
        'notes' => $debt->notes,
        'status' => $status,
        'created_at' => optional($debt->created_at)->toISOString(),
        'payments' => Payment::query()
            ->where('business_id', $debt->business_id)
            ->where('supplier_debt_id', $debt->id)
            ->where('type', 'supplier_debt')
            ->latest('paid_at')
            ->get(),
    ];
}

function notificationsForBusiness(Business $business)
{
    syncBusinessAlerts($business);

    return AppNotification::query()
        ->where('business_id', $business->id)
        ->latest()
        ->limit(20)
        ->get();
}

function syncBusinessAlerts(Business $business): void
{
    Product::query()
        ->where('business_id', $business->id)
        ->whereColumn('stock_quantity', '<=', 'alert_threshold')
        ->get()
        ->each(function (Product $product) use ($business) {
            createUniqueNotification(
                $business,
                'stock_low',
                'Stock bas',
                "{$product->name} est sous le seuil d'alerte ({$product->stock_quantity} restant)."
            );
        });

    Receivable::query()
        ->where('business_id', $business->id)
        ->whereRaw('amount_paid < amount_due')
        ->whereNotNull('due_date')
        ->where('due_date', '<=', now()->addDays(3)->toDateString())
        ->with('customer')
        ->get()
        ->each(function (Receivable $receivable) use ($business) {
            $label = $receivable->due_date->isPast() ? 'Creance en retard' : 'Creance bientot due';
            createUniqueNotification(
                $business,
                'receivable_due',
                $label,
                ($receivable->customer?->name ?: 'Client')." doit encore ".number_format($receivable->amount_due - $receivable->amount_paid, 0, ',', ' ')." FCFA."
            );
        });

    SupplierDebt::query()
        ->where('business_id', $business->id)
        ->whereRaw('amount_paid < amount_due')
        ->whereNotNull('due_date')
        ->where('due_date', '<=', now()->addDays(3)->toDateString())
        ->with('supplier')
        ->get()
        ->each(function (SupplierDebt $debt) use ($business) {
            $label = $debt->due_date->isPast() ? 'Dette fournisseur en retard' : 'Dette fournisseur bientot due';
            createUniqueNotification(
                $business,
                'supplier_debt_due',
                $label,
                ($debt->supplier?->name ?: 'Fournisseur')." attend encore ".number_format($debt->amount_due - $debt->amount_paid, 0, ',', ' ')." FCFA."
            );
        });

    $pendingPayrolls = Payroll::query()
        ->where('business_id', $business->id)
        ->where('status', 'pending')
        ->count();

    if ($pendingPayrolls > 0) {
        createUniqueNotification(
            $business,
            'payroll_pending',
            'Paie a regler',
            "{$pendingPayrolls} fiche(s) de paie sont en attente de paiement."
        );
    }

    if ($business->whatsapp_reports_enabled && $business->whatsapp_report_phone) {
        createUniqueNotification(
            $business,
            'daily_report',
            'Rapport WhatsApp programmé',
            'Le '.whatsappReportTypeLabel($business->whatsapp_report_type).' sera préparé pour WhatsApp vers '.substr((string) ($business->whatsapp_report_time ?: '08:00'), 0, 5).' au '.$business->whatsapp_report_phone.'.'
        );
    }
}

function whatsappReportTypeLabel(?string $type): string
{
    return [
        'global' => 'rapport global',
        'sales' => 'rapport des ventes',
        'stock' => 'rapport du stock',
        'receivables' => 'rapport des créances',
        'supplier_debts' => 'rapport des dettes fournisseurs',
        'expenses' => 'rapport des charges',
        'taxes' => 'bilan impôts et comptabilité',
    ][$type] ?? 'rapport global';
}

function createUniqueNotification(Business $business, string $type, string $title, string $message): void
{
    AppNotification::firstOrCreate(
        [
            'business_id' => $business->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ],
        [
            'channel' => in_array($type, ['daily_report'], true) ? 'whatsapp' : 'in_app',
        ]
    );
}
}










