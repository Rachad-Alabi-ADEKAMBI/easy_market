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
use App\Models\StockMovement;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

function requiredPhoneRule(): array
{
    return ['required', 'regex:/^01\d{8}$/'];
}

function nullablePhoneRule(): array
{
    return ['nullable', 'regex:/^01\d{8}$/'];
}

Route::get('/', function () {
    $user = Auth::user();
    $actions = '<a class="btn btn-soft" href="/connexion"><i class="fa-solid fa-right-to-bracket"></i>Connexion</a>'
        .'<a class="btn btn-primary" href="/inscription"><i class="fa-solid fa-arrow-right"></i>Inscription</a>';

    $footerAuthLinks = '<a href="/inscription"><i class="fa-solid fa-user-plus"></i>Inscription</a>'
        .'<a href="/connexion"><i class="fa-solid fa-right-to-bracket"></i>Connexion</a>';
    $pricingCtas = [
        '__HOME_PRICING_CTA_MONTHLY__' => '<a class="btn btn-dark" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir mensuel</a>',
        '__HOME_PRICING_CTA_YEARLY__' => '<a class="btn btn-primary" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir annuel</a>',
        '__HOME_PRICING_CTA_LIFETIME__' => '<a class="btn btn-dark" href="/inscription" style="margin-top:18px;width:100%"><i class="fa-solid fa-arrow-right"></i>Choisir licence</a>',
    ];

    if ($user) {
        $business = $user->ownedBusinesses()->first() ?: $user->businesses()->first();
        $dashboardUrl = $user->role === 'super_admin'
            ? '/admin/abonnements'
            : ($business ? '/dashboard/'.$business->id : '/');
        $actions = '<a class="btn btn-primary" href="'.e($dashboardUrl).'"><i class="fa-solid fa-gauge-high"></i>Tableau de bord</a>';
        $footerAuthLinks = '<a href="'.e($dashboardUrl).'"><i class="fa-solid fa-gauge-high"></i>Tableau de bord</a>';
        $pricingCtas = [
            '__HOME_PRICING_CTA_MONTHLY__' => '',
            '__HOME_PRICING_CTA_YEARLY__' => '',
            '__HOME_PRICING_CTA_LIFETIME__' => '',
        ];
    }

    $homeHtml = str_replace(
        array_merge(['__HOME_AUTH_ACTIONS__', '__HOME_FOOTER_AUTH_LINKS__'], array_keys($pricingCtas)),
        array_merge([$actions, $footerAuthLinks], array_values($pricingCtas)),
        file_get_contents(resource_path('views/welcome.blade.php'))
    );

    return response($homeHtml)
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/inscription', function () {
    return response(str_replace('__CSRF_TOKEN__', csrf_token(), file_get_contents(resource_path('views/register.blade.php'))))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/connexion', function () {
    return response(str_replace('__CSRF_TOKEN__', csrf_token(), file_get_contents(resource_path('views/login.blade.php'))))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/mot-de-passe-oublie', function () {
    return response(str_replace('__CSRF_TOKEN__', csrf_token(), file_get_contents(resource_path('views/forgot-password.blade.php'))))
        ->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::post('/mot-de-passe-oublie', function (Request $request) {
    $request->validate(['login' => ['required', 'string', 'max:255']]);

    $login = trim((string) $request->input('login'));
    $user = filter_var($login, FILTER_VALIDATE_EMAIL)
        ? User::where('email', $login)->first()
        : (preg_match('/^01\d{8}$/', $login) ? User::where('phone', $login)->first() : null);

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
    $user = filter_var($login, FILTER_VALIDATE_EMAIL)
        ? User::where('email', $login)->first()
        : (preg_match('/^01\d{8}$/', $login) ? User::where('phone', $login)->first() : null);

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return redirect('/connexion?erreur=1');
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    $business = $user->ownedBusinesses()->first() ?: $user->businesses()->first();

    if ($user->role === 'super_admin') {
        return redirect('/admin/abonnements');
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
        'business_phone' => normalizePhoneInput($request->input('business_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'civility' => ['required', 'string', 'max:10'],
        'phone' => requiredPhoneRule(),
        'login' => ['nullable', 'email', 'max:255'],
        'password' => ['required', 'confirmed', 'min:8'],
        'business_name' => ['required', 'string', 'max:255'],
        'business_phone' => requiredPhoneRule(),
        'business_address' => ['nullable', 'string', 'max:255'],
        'business_ifu' => ['nullable', 'string', 'max:255'],
        'business_slogan' => ['nullable', 'string', 'max:255'],
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

    if ($loginIsPhone && User::where('phone', $phone)->exists()) {
        return response()->json([
            'message' => 'Certaines informations sont invalides.',
            'errors' => ['phone' => ['Ce numéro de téléphone est déjà utilisé.']],
        ], 422);
    }

    $user = User::create([
        'civility' => $request->input('civility'),
        'name' => $request->input('name'),
        'phone' => $phone,
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
        'whatsapp_phone' => $request->input('business_phone'),
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
    $section = $section ?: 'tableau-de-bord';

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
        'stocks',
        'ventes',
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
            'stocks' => 'Stocks',
            'ventes' => 'Ventes',
        ][$section] ?? 'Tableau de bord',
        'section' => $section,
    ]))->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/api/businesses/{business}/dashboard', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $business->load('subscriptions');
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
            'logo_path' => $business->logo_path,
            'primary_color' => $business->primary_color,
            'secondary_color' => $business->secondary_color,
            'whatsapp_phone' => $business->whatsapp_phone,
            'show_logo_on_documents' => $business->show_logo_on_documents,
            'show_ifu_on_documents' => $business->show_ifu_on_documents,
            'show_slogan_on_documents' => $business->show_slogan_on_documents,
            'show_address_on_documents' => $business->show_address_on_documents,
        ],
        'subscription' => $subscription,
        'subscription_plans' => subscriptionPlans(),
        'summary' => [
            'products_count' => $business->products()->count(),
            'low_stock_count' => Product::query()
                ->where('business_id', $business->id)
                ->whereColumn('stock_quantity', '<=', 'alert_threshold')
                ->count(),
            'stock_value' => (int) Product::query()
                ->where('business_id', $business->id)
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
            ->latest()
            ->limit(20)
            ->get(),
        'sales' => Sale::query()
            ->where('business_id', $business->id)
            ->with(['items', 'customer', 'seller:id,name,phone'])
            ->latest()
            ->limit(20)
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
                'user:id,name,phone',
                'advances' => fn ($query) => $query->latest('advanced_on')->limit(5),
            ])
            ->latest()
            ->limit(20)
            ->get(),
        'payrolls' => Payroll::query()
            ->where('business_id', $business->id)
            ->with('employee')
            ->latest()
            ->limit(20)
            ->get(),
        'notifications' => notificationsForBusiness($business),
        'categories' => Category::query()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get(),
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
        'phone' => normalizePhoneInput($request->input('phone')),
        'whatsapp_phone' => normalizePhoneInput($request->input('whatsapp_phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => requiredPhoneRule(),
        'whatsapp_phone' => nullablePhoneRule(),
        'address' => ['nullable', 'string', 'max:255'],
        'ifu' => ['nullable', 'string', 'max:255'],
        'slogan' => ['nullable', 'string', 'max:255'],
        'logo' => ['nullable', 'image', 'max:2048'],
        'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'show_logo_on_documents' => ['boolean'],
        'show_ifu_on_documents' => ['boolean'],
        'show_slogan_on_documents' => ['boolean'],
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
        'primary_color' => $request->input('primary_color') ?: '#2f7d69',
        'secondary_color' => $request->input('secondary_color') ?: '#f5b84b',
        'show_logo_on_documents' => $request->boolean('show_logo_on_documents'),
        'show_ifu_on_documents' => $request->boolean('show_ifu_on_documents'),
        'show_slogan_on_documents' => $request->boolean('show_slogan_on_documents'),
        'show_address_on_documents' => $request->boolean('show_address_on_documents'),
    ];

    if ($request->hasFile('logo')) {
        $settings['logo_path'] = $request->file('logo')->store('business-logos', 'public');
    }

    $business->update($settings);

    return response()->json($business);
});

Route::post('/api/businesses/{business}/employees', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'phone' => normalizePhoneInput($request->input('phone')),
    ]);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => [...nullablePhoneRule(), Rule::unique('users', 'phone')->whereNotNull('phone')],
        'password' => ['nullable', 'string', 'min:8'],
        'position' => ['required', 'string', 'max:255'],
        'type' => ['required', 'in:seller,cashier,accountant,observer'],
        'salary' => ['required', 'integer', 'min:0'],
        'hired_at' => ['nullable', 'date'],
    ]);

    $validator->sometimes('phone', requiredPhoneRule(), fn ($input) => $input->type === 'seller');
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
            'phone' => $phone,
            'email' => $phone.'@phone.easymarket.local',
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
        'position' => $request->input('position'),
        'type' => $request->input('type'),
        'salary' => $request->integer('salary'),
        'hired_at' => $request->input('hired_at'),
        'is_active' => true,
    ]);

    return response()->json($employee->load('user'), 201);
});

Route::put('/api/businesses/{business}/employees/{employee}', function (Request $request, Business $business, Employee $employee) {
    authorizeBusinessAccess($business, $request);
    abort_unless($employee->business_id === $business->id, 404);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'position' => ['required', 'string', 'max:255'],
        'type' => ['required', 'in:seller,cashier,accountant,observer'],
        'salary' => ['required', 'integer', 'min:0'],
        'hired_at' => ['nullable', 'date'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'La fiche employé est invalide.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $employee->update([
        'name' => $request->input('name'),
        'position' => $request->input('position'),
        'type' => $request->input('type'),
        'salary' => $request->integer('salary'),
        'hired_at' => $request->input('hired_at'),
    ]);

    if ($employee->user) {
        $employee->user->update(['name' => $request->input('name')]);
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

    $advance = SalaryAdvance::create([
        'business_id' => $business->id,
        'employee_id' => $employee->id,
        'amount' => $request->integer('amount'),
        'advanced_on' => $request->input('advanced_on'),
        'notes' => $request->input('notes'),
    ]);

    return response()->json($advance, 201);
});

Route::post('/api/businesses/{business}/payrolls', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'employee_id' => ['required', 'integer', 'exists:employees,id'],
        'period' => ['required', 'date_format:Y-m'],
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
    $net = max(0, $gross - $advanceTotal);

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
            'status' => 'pending',
        ]
    );

    return response()->json($payroll->load('employee'), 201);
});

Route::post('/api/businesses/{business}/expenses', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $expenseCategories = [
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
    sort($expenseCategories, SORT_NATURAL | SORT_FLAG_CASE);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category' => ['required', 'string', Rule::in($expenseCategories)],
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

    $sale->load(['items']);
    $business->load('owner');

    $payload = urlencode(json_encode([
        'business_id' => $business->id,
        'sale_id' => $sale->id,
        'number' => $sale->number,
        'total' => $sale->total,
    ]));

    $html = str_replace(
        ['__BUSINESS__', '__PHONE__', '__ADDRESS__', '__IFU__', '__SLOGAN__', '__SALE_NUMBER__', '__SALE_DATE__', '__PAYMENT__', '__SUBTOTAL__', '__DISCOUNT__', '__TOTAL__', '__ITEM_ROWS__', '__QR_PAYLOAD__'],
        [
            e($business->name),
            e($business->phone),
            e($business->address ?: '-'),
            e($business->ifu ?: '-'),
            e($business->slogan ?: ''),
            e($sale->number),
            e(optional($sale->sold_at)->format('d/m/Y H:i') ?: $sale->created_at->format('d/m/Y H:i')),
            e(paymentLabel($sale->payment_method)),
            number_format($sale->subtotal, 0, ',', ' ').' FCFA',
            number_format($sale->discount, 0, ',', ' ').' FCFA',
            number_format($sale->total, 0, ',', ' ').' FCFA',
            $sale->items->map(fn ($item) => '<tr><td>'.e($item->product_name).'</td><td>'.e($item->quantity).'</td><td>'.number_format($item->unit_price, 0, ',', ' ').' FCFA</td><td>'.number_format($item->discount, 0, ',', ' ').' FCFA</td><td>'.number_format($item->total, 0, ',', ' ').' FCFA</td></tr>')->implode(''),
            $payload,
        ],
        file_get_contents(resource_path('views/invoice.blade.php'))
    );

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/businesses/{business}/reports/{period}', function (Business $business, string $period) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($period, ['daily', 'weekly', 'monthly'], true), 404);

    $range = reportRange($period);
    $data = reportData($business, $range[0], $range[1], $period);

    $rows = collect($data['top_products'])->map(fn ($item) => '<tr><td>'.e($item->product_name).'</td><td>'.e($item->quantity).'</td><td>'.number_format($item->total, 0, ',', ' ').' FCFA</td></tr>')->implode('');

    $html = str_replace(
        ['__BUSINESS__', '__PERIOD_LABEL__', '__RANGE__', '__SALES_TOTAL__', '__EXPENSES_TOTAL__', '__NET_RESULT__', '__RECEIVABLES__', '__DEBTS__', '__PAYROLLS__', '__TOP_PRODUCTS__'],
        [
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

Route::get('/api/businesses/{business}/reports/{period}', function (Business $business, string $period) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($period, ['daily', 'weekly', 'monthly'], true), 404);

    $range = reportRange($period);

    return response()->json(reportData($business, $range[0], $range[1], $period));
});

Route::get('/businesses/{business}/taxes/statement', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $range = [now()->startOfMonth(), now()->endOfMonth()];
    $data = reportData($business, $range[0], $range[1], 'monthly');

    $html = str_replace(
        ['__BUSINESS__', '__PERIOD__', '__SALES__', '__EXPENSES__', '__PAYROLLS__', '__NET__', '__RECEIVABLES__', '__DEBTS__'],
        [
            e($business->name),
            e($data['range_label']),
            number_format($data['sales_total'], 0, ',', ' ').' FCFA',
            number_format($data['expenses_total'], 0, ',', ' ').' FCFA',
            number_format($data['payrolls_total'], 0, ',', ' ').' FCFA',
            number_format($data['net_result'], 0, ',', ' ').' FCFA',
            number_format($data['receivables_remaining'], 0, ',', ' ').' FCFA',
            number_format($data['debts_remaining'], 0, ',', ' ').' FCFA',
        ],
        file_get_contents(resource_path('views/tax-statement.blade.php'))
    );

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

Route::get('/api/businesses/{business}/taxes', function (Business $business) {
    authorizeBusinessAccess($business, request());
    $range = [now()->startOfMonth(), now()->endOfMonth()];
    $data = reportData($business, $range[0], $range[1], 'monthly');

    return response()->json([
        'faq' => taxFaq(),
        'statement' => [
            'period' => $data['range_label'],
            'sales_total' => $data['sales_total'],
            'expenses_total' => $data['expenses_total'],
            'payrolls_total' => $data['payrolls_total'],
            'net_result' => $data['net_result'],
            'receivables_remaining' => $data['receivables_remaining'],
            'debts_remaining' => $data['debts_remaining'],
        ],
        'documents' => [
            'Rapport des recettes',
            'Rapport des dépenses détaillées',
            'Journal des ventes',
            'Bilan des créances clients et dettes fournisseurs',
            'Fiches de paie du personnel',
            'Exports PDF / Excel à transmettre au comptable',
        ],
    ]);
});

Route::post('/api/businesses/{business}/products', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'category_name' => ['nullable', 'string', 'max:255'],
        'unit' => ['required', 'string', 'max:30'],
        'purchase_price' => ['required', 'integer', 'min:0'],
        'sale_price' => ['required', 'integer', 'min:0'],
        'stock_quantity' => ['required', 'numeric', 'min:0'],
        'alert_threshold' => ['required', 'numeric', 'min:0'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations produit sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $category = null;

    if ($request->filled('category_name')) {
        $category = Category::firstOrCreate([
            'business_id' => $business->id,
            'name' => $request->input('category_name'),
        ]);
    }

    $product = Product::create([
        'business_id' => $business->id,
        'category_id' => $category?->id,
        'name' => $request->input('name'),
        'unit' => $request->input('unit'),
        'purchase_price' => $request->integer('purchase_price'),
        'sale_price' => $request->integer('sale_price'),
        'stock_quantity' => $request->input('stock_quantity'),
        'alert_threshold' => $request->input('alert_threshold'),
    ]);

    return response()->json($product->load('category'), 201);
});

Route::post('/api/businesses/{business}/sales', function (Request $request, Business $business) {
    authorizeBusinessAccess($business, $request);
    ensureActiveSubscription($business);

    $request->merge([
        'customer_phone' => normalizePhoneInput($request->input('customer_phone')),
    ]);

    $seller = Auth::user();
    $sellerPivot = $seller ? $business->users()->where('users.id', $seller->id)->first()?->pivot : null;
    $canSell = $seller
        && $business->owner_id !== $seller->id
        && ($sellerPivot?->role === 'seller' || $seller->role === 'seller');

    if (! $canSell) {
        return response()->json([
            'message' => 'Seuls les vendeurs peuvent enregistrer une vente.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'customer_name' => ['nullable', 'string', 'max:255'],
        'customer_phone' => nullablePhoneRule(),
        'type' => ['nullable', 'in:invoice,proforma'],
        'payment_method' => ['required', 'in:cash,mobile_money,credit'],
        'credit_due_date' => ['nullable', 'date', 'required_if:payment_method,credit'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
        'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        'items.*.discount' => ['nullable', 'integer', 'min:0'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Certaines informations de vente sont invalides.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $sale = DB::transaction(function () use ($request, $business, $seller) {
        $customer = null;

        if ($request->filled('customer_name') || $request->filled('customer_phone')) {
            $customer = Customer::create([
                'business_id' => $business->id,
                'name' => $request->input('customer_name') ?: 'Client comptoir',
                'phone' => $request->input('customer_phone'),
            ]);
        }

        $documentType = $request->input('type', 'invoice');

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
            $product = Product::query()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->findOrFail($line['product_id']);

            $quantity = (float) $line['quantity'];
            $discount = (int) ($line['discount'] ?? 0);

            if ($documentType === 'invoice' && (float) $product->stock_quantity < $quantity) {
                abort(response()->json([
                    'message' => "Stock insuffisant pour {$product->name}.",
                ], 422));
            }

            $lineSubtotal = (int) round($quantity * $product->sale_price);
            $lineTotal = max(0, $lineSubtotal - $discount);
            $subtotal += $lineSubtotal;
            $discountTotal += $discount;

            $sale->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->sale_price,
                'discount' => $discount,
                'total' => $lineTotal,
            ]);

            if ($documentType === 'invoice') {
                $product->decrement('stock_quantity', $quantity);

                StockMovement::create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
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
            return $sale->load(['items', 'customer', 'seller:id,name,phone']);
        }

        if ($request->input('payment_method') === 'credit') {
            Receivable::create([
                'business_id' => $business->id,
                'customer_id' => $customer?->id ?? Customer::create([
                    'business_id' => $business->id,
                    'name' => 'Client crédit',
                ])->id,
                'amount_due' => $total,
                'amount_paid' => 0,
                'due_date' => $request->input('credit_due_date'),
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

        return $sale->load(['items', 'customer', 'seller:id,name,phone']);
    });

    return response()->json($sale, 201);
});

Route::get('/businesses/{business}/exports/{type}', function (Business $business, string $type) {
    authorizeBusinessAccess($business, request());
    abort_unless(in_array($type, ['products', 'sales', 'expenses'], true), 404);

    if ($type === 'products') {
        $rows = Product::query()
            ->where('business_id', $business->id)
            ->with('category')
            ->get()
            ->map(fn ($product) => [
                $product->name,
                $product->category?->name ?: '',
                $product->unit,
                $product->stock_quantity,
                $product->alert_threshold,
                $product->purchase_price,
                $product->sale_price,
            ]);

        return csvResponse('produits.csv', ['Produit', 'Catégorie', 'Unité', 'Stock', 'Seuil', 'Prix achat', 'Prix vente'], $rows);
    }

    if ($type === 'sales') {
        $rows = Sale::query()
            ->where('business_id', $business->id)
            ->latest()
            ->get()
            ->map(fn ($sale) => [
                $sale->number,
                paymentLabel($sale->payment_method),
                $sale->status,
                $sale->subtotal,
                $sale->discount,
                $sale->total,
                optional($sale->sold_at)->format('d/m/Y H:i'),
            ]);

        return csvResponse('ventes.csv', ['Facture', 'Paiement', 'Statut', 'Sous-total', 'Remise', 'Total', 'Date'], $rows);
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
        'name' => $user?->name ?: 'Utilisateur',
        'email' => $user?->email,
        'role' => $role,
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

    $content = match ($section) {
        'abonnements' => '<section class="card"><div class="table-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Rechercher une boutique, une formule, un statut..." data-table-search></div><div class="table-wrap"><table><thead><tr><th>Boutique</th><th>Formule</th><th>Montant</th><th>Numéro dépôt</th><th>Statut</th><th>Fin</th><th>Actions</th></tr></thead><tbody>'.$subscriptionRows.'</tbody></table></div></section>',
        'boutiques' => '<section class="card"><div class="section-title"><div><h2>Boutiques</h2><p>Vue globale des commerces inscrits.</p></div></div><div class="table-wrap"><table><thead><tr><th>Boutique</th><th>Contact</th><th>Propriétaire</th><th>Abonnement</th><th>Produits</th><th>Action</th></tr></thead><tbody>'.$businessRows.'</tbody></table></div></section>',
        'utilisateurs' => '<section class="card"><div class="section-title"><div><h2>Utilisateurs</h2><p>Comptes administrateurs et accès EasyMarket.</p></div></div><div class="table-wrap"><table><thead><tr><th>Utilisateur</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Création</th><th>Action</th></tr></thead><tbody>'.$userRows.'</tbody></table></div></section>',
        'revenus' => '<section class="stats">'.$revenueStatsHtml.'</section><section class="card"><div class="section-title"><div><h2>Revenus</h2><p>Revenus validés et répartition par formule.</p></div></div><div class="table-wrap"><table><thead><tr><th>Formule</th><th>Prix</th><th>Actifs</th><th>En attente</th><th>Revenus validés</th></tr></thead><tbody>'.$revenueRows.'</tbody></table></div></section>',
        default => '<section class="stats">'.$statsHtml.'</section>',
    };

    $titles = [
        'dashboard' => ['Tableau de bord super admin', 'Statistiques globales EasyMarket.'],
        'abonnements' => ['Abonnements', 'Demandes, validations et suspensions.'],
        'boutiques' => ['Boutiques', 'Commerces inscrits sur EasyMarket.'],
        'utilisateurs' => ['Utilisateurs', "Comptes et statuts d'accès."],
        'revenus' => ['Revenus', 'Suivi financier des abonnements.'],
    ];
    $title = $titles[$section] ?? $titles['dashboard'];

    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title[0]).' - EasyMarket</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap");:root{--primary:#2f7d69;--dark:#10251f;--ink:#17211b;--muted:#52635b;--line:#dfe7e2;--paper:#f6faf8;--gold:#f5b84b;--danger:#b42318}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:"Poppins",Inter,Arial,sans-serif}a{text-decoration:none;color:inherit}.menu-toggle{display:none}.layout{display:grid;grid-template-columns:270px 1fr;min-height:100vh}.side{background:var(--dark);color:white;padding:22px;position:sticky;top:0;height:100vh}.brand{display:flex;align-items:center;gap:10px;font-weight:500;font-size:20px;margin-bottom:28px}.logo{width:38px;height:38px;border-radius:8px;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),var(--gold));color:var(--dark)}.nav{display:grid;gap:8px}.nav a,.logout button{border:0;border-radius:8px;padding:12px;background:transparent;color:rgba(255,255,255,.78);display:flex;align-items:center;gap:10px;font-weight:600;cursor:pointer}.nav a.active,.nav a:hover{background:rgba(255,255,255,.12);color:white}.logout button,.logout button:hover{background:var(--danger);color:white}.logout{margin-top:22px}.main{padding:28px}.top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:22px}.top h1{margin:0 0 8px;font-size:42px;line-height:1}.top p{margin:0;color:var(--muted)}.top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.user-chip{border:1px solid var(--line);border-radius:8px;background:white;padding:8px 12px;display:flex;gap:10px;align-items:center}.user-chip i{color:var(--primary)}.user-chip strong,.user-chip span{display:block}.user-chip span{color:var(--muted);font-size:12px;font-weight:600}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:14px;margin-bottom:18px}.stat,.card{background:white;border:1px solid var(--line);border-radius:10px;box-shadow:0 14px 36px rgba(16,37,31,.06)}.stat{padding:16px;display:grid;gap:7px}.stat i{color:var(--primary);font-size:22px}.stat span{color:var(--muted);font-weight:600}.stat strong{font-size:24px}.card{padding:18px;margin-bottom:18px}.section-title h2{margin:0}.section-title p{margin:4px 0 14px;color:var(--muted)}.table-search{min-height:46px;margin-bottom:14px;border:1px solid var(--line);border-radius:10px;background:#f8fcfa;display:flex;align-items:center;gap:10px;padding:0 13px;box-shadow:inset 0 0 0 1px rgba(47,125,105,.03)}.table-search i{color:var(--primary)}.table-search input{width:100%;border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-weight:600}.table-search input::placeholder{color:var(--muted);font-weight:500}.table-wrap{overflow:auto;border-radius:10px}table{width:100%;min-width:900px;border-collapse:separate;border-spacing:0 8px}th,td{padding:14px 12px;text-align:left;vertical-align:middle}th{background:var(--dark);color:white;font-size:13px;font-weight:500;text-transform:uppercase}th:first-child{border-radius:8px 0 0 8px}th:last-child{border-radius:0 8px 8px 0}tbody tr{background:white;box-shadow:0 8px 20px rgba(16,37,31,.05)}tbody tr:hover{background:#f8fcfa}td{border-top:1px solid var(--line);border-bottom:1px solid var(--line)}td:first-child{border-left:1px solid var(--line);border-radius:8px 0 0 8px}td:last-child{border-right:1px solid var(--line);border-radius:0 8px 8px 0}td small{display:block;color:var(--muted);margin-top:3px}.table-pagination{margin-top:12px;display:flex;justify-content:space-between;gap:10px;color:var(--muted);font-size:13px;font-weight:600}.table-pagination div{display:flex;gap:8px}.table-pagination button{border:1px solid var(--primary);border-radius:8px;background:var(--primary);color:white;padding:8px 10px;font-weight:600}.badge{display:inline-flex;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:500}.ok{background:#2f7d69;color:white}.wait{background:#fff4d8;color:#7a4d00}.bad{background:#ffe6df;color:#a33824}.neutral{background:#eef2f0;color:#3f5048}.btn{border:0;border-radius:8px;min-height:38px;padding:9px 12px;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-weight:500;cursor:pointer}.primary{background:var(--gold);color:var(--dark)}.light{background:var(--primary);border:1px solid var(--primary);color:white}.danger{background:var(--danger);color:white}.actions{display:flex;gap:8px;flex-wrap:wrap}.muted{color:var(--muted);font-weight:600}@media(max-width:980px){.layout{grid-template-columns:1fr}.layout::before{content:"";position:fixed;top:0;left:0;right:0;height:76px;z-index:45;background:rgba(246,250,248,.94);border-bottom:1px solid rgba(223,231,226,.9);box-shadow:0 12px 30px rgba(16,37,31,.08);backdrop-filter:blur(10px)}.menu-toggle{width:44px;height:44px;border:0;border-radius:8px;background:var(--dark);color:white;display:inline-flex;align-items:center;justify-content:center;position:fixed;top:14px;left:14px;z-index:60;box-shadow:0 12px 30px rgba(16,37,31,.22);font-size:18px}.side{width:min(82vw,300px);height:100vh;position:fixed;top:0;left:0;z-index:50;padding:72px 18px 18px;display:block;transform:translateX(-105%);transition:transform .22s ease;overflow-y:auto;box-shadow:20px 0 50px rgba(16,37,31,.22)}body.menu-open .side{transform:translateX(0)}body.menu-open .menu-toggle{background:var(--danger)}.brand{margin-bottom:18px;white-space:nowrap}.nav{display:grid;gap:8px;overflow:visible;padding:0}.nav a,.logout button{width:100%;white-space:normal}.logout{margin-top:18px}.top{flex-direction:column}.top-actions{position:fixed;top:14px;left:66px;right:14px;z-index:49;justify-content:flex-end}.user-chip{min-height:44px;max-width:100%;padding:7px 10px;box-shadow:0 12px 30px rgba(16,37,31,.12)}.user-chip strong,.user-chip span{max-width:calc(100vw - 132px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.main{padding:76px 18px 18px}}@media(max-width:640px){.stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.stat{padding:12px}.stat strong{font-size:18px}.stat span{font-size:12px}.side{width:min(86vw,300px)}.brand{justify-content:flex-start}.nav{margin:0;padding:0}.table-wrap{overflow:visible}table,thead,tbody,tr,th,td{display:block;width:100%;min-width:0}thead{display:none}tbody tr{border:1px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:12px;background:white;box-shadow:0 8px 20px rgba(16,37,31,.05)}td,td:first-child,td:last-child{border-left:0;border-right:0;border-top:0;border-radius:0}td{border-bottom:1px solid var(--line);display:grid;grid-template-columns:42% 1fr;gap:10px;padding:10px 0}td:last-child{border-bottom:0}td::before{content:attr(data-label);color:var(--dark);font-weight:500}.table-pagination{align-items:flex-start;flex-direction:column}}</style></head><body><button class="menu-toggle" type="button" aria-label="Ouvrir le menu" title="Menu"><i class="fa-solid fa-bars"></i></button><div class="layout"><aside class="side"><a class="brand" href="/admin"><span class="logo">EM</span><span>EasyMarket</span></a><nav class="nav">'.adminNavLink('/admin', 'fa-chart-line', 'Vue générale', $section === 'dashboard').adminNavLink('/admin/abonnements', 'fa-credit-card', 'Abonnements', $section === 'abonnements').adminNavLink('/admin/boutiques', 'fa-store', 'Boutiques', $section === 'boutiques').adminNavLink('/admin/utilisateurs', 'fa-users', 'Utilisateurs', $section === 'utilisateurs').adminNavLink('/admin/revenus', 'fa-coins', 'Revenus', $section === 'revenus').adminNavLink('/', 'fa-house', 'Accueil', false).'</nav><form class="logout" method="post" action="/deconnexion"><input type="hidden" name="_token" value="'.e($csrf).'"><button type="submit"><i class="fa-solid fa-right-from-bracket"></i>Déconnexion</button></form></aside><main class="main"><header class="top"><div><h1>'.e($title[0]).'</h1><p>'.e($title[1]).'</p></div><div class="top-actions"><div class="user-chip"><i class="fa-solid fa-user"></i><div><strong>'.e($currentUser?->name ?: 'Utilisateur').'</strong><span>EasyMarket - Super Admin</span></div></div></div></header>'.$content.'</main></div><script>document.querySelectorAll(".table-wrap table").forEach(function(table){var headers=[].map.call(table.querySelectorAll("thead th"),function(th){return th.textContent.trim()});table.querySelectorAll("tbody tr").forEach(function(row){row.querySelectorAll("td").forEach(function(td,i){if(headers[i])td.setAttribute("data-label",headers[i])})});var wrap=table.closest(".table-wrap");if(wrap&&!wrap.nextElementSibling?.classList?.contains("table-pagination")){var count=table.querySelectorAll("tbody tr").length;var div=document.createElement("div");div.className="table-pagination";div.innerHTML="<span>"+count+" élément"+(count>=2?"s":"")+" sur 20</span><div><button disabled>Précédent</button><button disabled>Suivant</button></div>";wrap.insertAdjacentElement("afterend",div)}});document.querySelectorAll("[data-table-search]").forEach(function(input){var card=input.closest(".card");var rows=card?card.querySelectorAll("tbody tr"):[];input.addEventListener("input",function(){var term=input.value.trim().toLowerCase();rows.forEach(function(row){row.style.display=row.textContent.toLowerCase().includes(term)?"":"none"})})});var menuToggle=document.querySelector(".menu-toggle");if(menuToggle){menuToggle.addEventListener("click",function(){document.body.classList.toggle("menu-open");var open=document.body.classList.contains("menu-open");menuToggle.setAttribute("aria-label",open?"Fermer le menu":"Ouvrir le menu");menuToggle.querySelector("i").className=open?"fa-solid fa-xmark":"fa-solid fa-bars"});document.querySelectorAll(".side a").forEach(function(link){link.addEventListener("click",function(){document.body.classList.remove("menu-open");menuToggle.setAttribute("aria-label","Ouvrir le menu");menuToggle.querySelector("i").className="fa-solid fa-bars"})})}</script></body></html>';
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
    $next = Sale::query()->where('business_id', $business->id)->count() + 1;

    return 'FAC-'.now()->format('Y').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
}

function paymentLabel(string $method): string
{
    return [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'credit' => 'Crédit',
    ][$method] ?? $method;
}

function reportRange(string $period): array
{
    return match ($period) {
        'daily' => [now()->startOfDay(), now()->endOfDay()],
        'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
        'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
    };
}

function reportData(Business $business, $start, $end, string $period): array
{
    $salesTotal = (int) Sale::query()
        ->where('business_id', $business->id)
        ->whereBetween('sold_at', [$start, $end])
        ->where('status', 'completed')
        ->sum('total');

    $expensesTotal = (int) Expense::query()
        ->where('business_id', $business->id)
        ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
        ->sum('amount');

    $payrollsTotal = (int) Payroll::query()
        ->where('business_id', $business->id)
        ->where('period', $start->format('Y-m'))
        ->sum('net_salary');

    $receivablesRemaining = (int) Receivable::query()
        ->where('business_id', $business->id)
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');

    $debtsRemaining = (int) SupplierDebt::query()
        ->where('business_id', $business->id)
        ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
        ->value('total');

    $topProducts = DB::table('sale_items')
        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
        ->where('sales.business_id', $business->id)
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
        ][$period],
        'range_label' => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
        'sales_total' => $salesTotal,
        'expenses_total' => $expensesTotal,
        'payrolls_total' => $payrollsTotal,
        'net_result' => $salesTotal - $expensesTotal - $payrollsTotal,
        'receivables_remaining' => $receivablesRemaining,
        'debts_remaining' => $debtsRemaining,
        'top_products' => $topProducts,
    ];
}

function taxFaq(): array
{
    return [
        ['question' => "Qu'est-ce que l'IFU et à quoi ça sert ?", 'answer' => "L'IFU identifie l'entreprise auprès de l'administration fiscale. Il doit figurer sur les documents commerciaux lorsque l'entreprise en dispose."],
        ['question' => "Quelles taxes s'appliquent à mon activité ?", 'answer' => "Cela dépend du statut, du chiffre d'affaires et du secteur. EasyMarket prépare les chiffres utiles pour faciliter l'analyse par un comptable."],
        ['question' => "Quand et comment déclarer mes impôts au Bénin ?", 'answer' => "Les échéances varient selon les obligations de l'entreprise. Le module aide à rassembler ventes, dépenses, paies, créances et dettes."],
        ['question' => "Qu'est-ce qu'un bilan comptable ?", 'answer' => "C'est un document qui présente la situation financière de l'entreprise sur une période donnée."],
        ['question' => "Quelle est la différence entre recettes et bénéfices ?", 'answer' => "Les recettes sont les ventes encaissées. Le bénéfice correspond à ce qui reste après déduction des charges, achats et salaires."],
        ['question' => "Comment EasyMarket m'aide à préparer ma déclaration ?", 'answer' => "L'application centralise les ventes, charges, dettes, créances et paies puis génère des rapports exploitables."],
        ['question' => "Quels documents dois-je transmettre à mon comptable ?", 'answer' => "Rapport des recettes, dépenses, journal des ventes, créances, dettes fournisseurs et fiches de paie."],
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
    $invoice = Sale::query()
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

    createUniqueNotification(
        $business,
        'daily_report',
        'Rapport journalier disponible',
        'Le rapport journalier peut etre imprime ou transmis par WhatsApp.'
    );
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





