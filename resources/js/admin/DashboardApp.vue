<template>
    <div class="dashboard-layout">
        <button
            class="dashboard-menu-toggle"
            type="button"
            :aria-label="sidebarOpen ? 'Fermer le menu' : 'Ouvrir le menu'"
            @click="sidebarOpen = !sidebarOpen"
        >
            <i :class="sidebarOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
        </button>
        <div class="toast-stack" aria-live="polite" aria-atomic="true">
            <div v-for="toast in toasts" :key="toast.id" :class="['toast', toast.type]">
                <i :class="toast.type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                <span>{{ toast.message }}</span>
            </div>
        </div>
        <div v-if="sidebarOpen" class="sidebar-backdrop" @click="sidebarOpen = false"></div>
        <aside :class="['sidebar', { 'sidebar-open': sidebarOpen }]">
            <a class="brand" href="/">
                <span class="logo">
                    <img v-if="businessSidebarLogoUrl" :src="businessSidebarLogoUrl" alt="Logo boutique">
                    <template v-else>EM</template>
                </span>
                <span>EasyMarket</span>
            </a>
            <nav class="side-nav">
                <a href="/"><i class="fa-solid fa-house"></i>Accueil</a>
                <template v-if="currentUserCanSell">
                    <a :class="{ active: activeSection === 'seller-cashier' }" :href="sectionUrl('caisse')"><i class="fa-solid fa-cash-register"></i>Caisse</a>
                    <a :class="{ active: activeSection === 'seller-proforma' }" :href="sectionUrl('proforma')"><i class="fa-solid fa-file-lines"></i>Proforma</a>
                    <a :class="{ active: activeSection === 'seller-sales' }" :href="sectionUrl('mes-ventes')"><i class="fa-solid fa-receipt"></i>Mes ventes</a>
                    <a :class="{ active: activeSection === 'seller-products' }" :href="sectionUrl('produits')"><i class="fa-solid fa-boxes-stacked"></i>Produits</a>
                    <a :class="{ active: activeSection === 'seller-profile' }" :href="sectionUrl('profil')"><i class="fa-solid fa-user-gear"></i>Profil</a>
                </template>
                <template v-else>
                    <a :class="{ active: activeSection === 'expenses' }" :href="sectionUrl('charges')"><i class="fa-solid fa-wallet"></i>Charges</a>
                    <a :class="{ active: activeSection === 'customers' }" :href="sectionUrl('clients')"><i class="fa-solid fa-users"></i>Clients & créances</a>
                    <a :class="{ active: activeSection === 'sales' }" :href="sectionUrl('ventes')"><i class="fa-solid fa-cash-register"></i>Factures & ventes</a>
                    <a :class="{ active: activeSection === 'suppliers' }" :href="sectionUrl('fournisseurs')"><i class="fa-solid fa-truck"></i>Fournisseurs & dettes</a>
                    <a :class="{ active: activeSection === 'taxes' }" :href="sectionUrl('impots')"><i class="fa-solid fa-file-invoice-dollar"></i>Impôts & comptabilité</a>
                    <a :class="{ active: activeSection === 'notifications' }" :href="sectionUrl('notifications')"><i class="fa-solid fa-bell"></i>Notifications{{ unreadNotifications ? ` (${unreadNotifications})` : '' }}</a>
                    <a :class="{ active: activeSection === 'settings' }" :href="sectionUrl('parametres')"><i class="fa-solid fa-gear"></i>Paramètres</a>
                    <a :class="{ active: activeSection === 'employees' }" :href="sectionUrl('personnel')"><i class="fa-solid fa-user-tie"></i>Personnel & paie</a>
                    <a :class="{ active: activeSection === 'reports' }" :href="sectionUrl('rapports')"><i class="fa-solid fa-chart-column"></i>Rapports</a>
                    <a :class="{ active: activeSection === 'stocks' }" :href="sectionUrl('stocks')"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                    <a :class="{ active: activeSection === 'dashboard' }" :href="sectionUrl('tableau-de-bord')"><i class="fa-solid fa-chart-line"></i>Tableau de bord</a>
                </template>
            </nav>
            <form class="sidebar-logout" method="post" action="/deconnexion">
                <input type="hidden" name="_token" :value="csrfToken">
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i>Déconnexion</button>
            </form>
        </aside>

        <main :class="['main', { 'main-locked': !subscriptionActive }]">
            <div v-if="showSubscriptionModal" class="activation-overlay">
                <section class="activation-modal">
                    <div v-if="subscriptionPending" class="activation-waiting">
                        <span class="waiting-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                        <span class="badge"><i class="fa-solid fa-clock"></i>Activation en attente</span>
                        <h2>Votre demande a bien été envoyée</h2>
                        <p>
                            L'activation de votre abonnement est en attente de validation par l'équipe EasyMarket.
                            Dès que le paiement sera vérifié, le statut passera à <strong>actif</strong> et vous pourrez commencer vos opérations.
                        </p>
                        <div class="activation-recap">
                            <div>
                                <span>Formule</span>
                                <strong>{{ selectedSubscriptionPlan?.label || subscription?.plan }}</strong>
                            </div>
                            <div>
                                <span>Montant</span>
                                <strong>{{ formatMoney(subscription?.amount || selectedSubscriptionPlan?.amount || 0) }}</strong>
                            </div>
                            <div>
                                <span>Numéro dépôt</span>
                                <strong>{{ subscription?.deposit_phone || subscription?.payment_reference || '-' }}</strong>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                    <div class="activation-head">
                        <span class="badge"><i class="fa-solid fa-lock"></i> Activation requise</span>
                        <h2>Activez votre abonnement EasyMarket</h2>
                        <p>
                            Pour commencer vos opérations, payez le montant de la formule choisie sur le
                            <strong>019622860</strong>, disponible sur WhatsApp, au nom de
                            <strong>Rachad ADEKAMBI</strong>. Renseignez ensuite le numéro utilisé pour le dépôt.
                        </p>
                    </div>

                    <div class="activation-plans">
                        <button
                            v-for="(plan, key) in subscriptionPlans"
                            :key="key"
                            :class="['plan-option', subscriptionForm.plan === key ? 'selected' : '']"
                            type="button"
                            @click="subscriptionForm.plan = key"
                        >
                            <i :class="planIcon(key)"></i>
                            <strong>{{ plan.label }}</strong>
                            <span>{{ formatMoney(plan.amount) }}</span>
                            <small>{{ plan.duration }}</small>
                        </button>
                    </div>

                    <form class="activation-form" @submit.prevent="requestSubscriptionActivation">
                        <label>Formule choisie
                            <select v-model="subscriptionForm.plan" required>
                                <option v-for="(plan, key) in subscriptionPlans" :key="key" :value="key">
                                    {{ plan.label }} - {{ formatMoney(plan.amount) }}
                                </option>
                            </select>
                        </label>
                        <label>Numéro utilisé pour le dépôt
                            <span class="phone-field">
                                <span>01</span>
                                <input :value="phoneInputValue(subscriptionForm.deposit_phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="subscriptionForm.deposit_phone = phoneWithPrefix($event.target.value)">
                            </span>
                        </label>
                        <div class="activation-summary">
                            <span>Montant à payer</span>
                            <strong>{{ formatMoney(selectedSubscriptionPlan?.amount || 0) }}</strong>
                        </div>
                        <button class="btn btn-primary" type="submit" :disabled="savingSubscription">
                            <i class="fa-solid fa-paper-plane"></i>{{ savingSubscription ? 'Envoi...' : 'OK, envoyer ma demande' }}
                        </button>
                    </form>

                    <p v-if="subscriptionMessage" class="message">{{ subscriptionMessage }}</p>
                    <p class="activation-note">
                        Votre abonnement passera en <strong>en attente</strong>. L'équipe EasyMarket le validera en
                        <strong>actif</strong> après vérification du paiement.
                    </p>
                    </template>
                </section>
            </div>

            <header class="top">
                <div>
                    <h1>{{ pageTitle }}</h1>
                    <p>{{ pageSubtitle }}</p>
                </div>
                <div class="top-actions">
                    <div class="user-chip">
                        <i class="fa-solid fa-user"></i>
                        <div>
                            <strong>{{ currentUser.name || 'Utilisateur' }}</strong>
                            <span>{{ business?.name || 'Boutique' }} - {{ currentUser.role || 'Admin' }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <section v-if="activeSection === 'notifications' && subscriptionPending" class="card activation-status-card">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <h2>Activation en attente</h2>
                    <p>Votre paiement a été déclaré. L'équipe EasyMarket doit maintenant valider l'abonnement pour débloquer les opérations.</p>
                </div>
            </section>

            <section v-if="activeSection === 'dashboard'" class="stats">
                <article class="card stat">
                    <i class="fa-solid fa-cash-register"></i>
                    <span>Ventes du jour</span>
                    <strong>{{ formatMoney(summary.today_sales_total) }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Stock bas</span>
                    <strong>{{ summary.low_stock_count }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-box"></i>
                    <span>Produits</span>
                    <strong>{{ summary.products_count }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-coins"></i>
                    <span>Valeur du stock</span>
                    <strong class="stock-value">{{ formatMoney(summary.stock_value) }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                    <span>Total ventes</span>
                    <strong>{{ formatMoney(summary.sales_total) }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Charges du mois</span>
                    <strong>{{ formatMoney(summary.monthly_expenses_total) }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-user-tag"></i>
                    <span>Vendeurs</span>
                    <strong>{{ summary.sellers_count }}</strong>
                </article>
                <article class="card stat">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Abonnement</span>
                    <strong class="subscription-expiry">{{ subscriptionExpiryLabel }}</strong>
                </article>
            </section>

            <section v-if="activeSection === 'notifications'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Notifications & alertes</h2>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" :disabled="!unreadNotifications" @click="markAllNotificationsRead">
                            <i class="fa-solid fa-check-double"></i>Tout lire
                        </button>
                    </div>
                </div>
                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="notificationFilters.search" type="search" placeholder="Titre ou message">
                    </label>
                    <label>Statut
                        <select v-model="notificationFilters.status">
                            <option value="">Tous les statuts</option>
                            <option value="unread">Non lues</option>
                            <option value="read">Lues</option>
                        </select>
                    </label>
                    <label>Type
                        <select v-model="notificationFilters.type">
                            <option value="">Tous les types</option>
                            <option v-for="type in notificationTypes" :key="type" :value="type">{{ notificationLabel(type) }}</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="notificationSort">
                            <option value="newest">Plus récentes</option>
                            <option value="oldest">Plus anciennes</option>
                            <option value="unread_first">Non lues d'abord</option>
                            <option value="type_asc">Type A-Z</option>
                        </select>
                    </label>
                </div>
                <div class="filtered-summary">
                    <span>{{ notificationsCountLabel }}</span>
                    <strong>{{ unreadNotifications }} non lue{{ unreadNotifications >= 2 ? 's' : '' }}</strong>
                </div>
                <div class="notifications">
                    <article v-for="notification in filteredNotifications" :key="notification.id" :class="['notification', notification.read_at ? 'read' : 'unread']">
                        <div>
                            <span class="status ok">{{ notificationLabel(notification.type) }}</span>
                            <h3>{{ notification.title }}</h3>
                            <p>{{ notification.message }}</p>
                            <small>{{ formatDate(notification.created_at) }}</small>
                        </div>
                        <button v-if="!notification.read_at" class="btn btn-light" type="button" @click="markNotificationRead(notification)">
                            <i class="fa-solid fa-check"></i>Lu
                        </button>
                    </article>
                    <p v-if="!filteredNotifications.length" class="empty">Aucune notification ne correspond aux filtres.</p>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredNotifications.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
            </section>

            <section v-if="activeSection === 'settings'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Paramètres boutique</h2>
                    </div>
                </div>

                <form class="settings-form" enctype="multipart/form-data" @submit.prevent="saveSettings">
                    <label>Nom de la boutique
                        <input v-model="settingsForm.name" class="readonly-input" type="text" readonly required>
                    </label>
                    <label>Téléphone
                        <span class="phone-field">
                            <span>01</span>
                            <input :value="phoneInputValue(settingsForm.phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="settingsForm.phone = phoneWithPrefix($event.target.value)">
                        </span>
                    </label>
                    <label>Téléphone WhatsApp
                        <span class="phone-field">
                            <span>01</span>
                            <input :value="phoneInputValue(settingsForm.whatsapp_phone)" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="settingsForm.whatsapp_phone = optionalPhoneWithPrefix($event.target.value)">
                        </span>
                    </label>
                    <label>Adresse
                        <input v-model="settingsForm.address" type="text">
                    </label>
                    <label>IFU
                        <input v-model="settingsForm.ifu" type="text">
                    </label>
                    <label>Slogan
                        <input v-model="settingsForm.slogan" type="text">
                    </label>
                    <label class="full">Logo de la boutique
                        <div class="logo-upload-row">
                            <div class="logo-preview">
                                <img v-if="settingsLogoUrl" :src="settingsLogoUrl" alt="Logo boutique">
                                <span v-else>EM</span>
                            </div>
                            <input type="file" accept="image/*" @change="onSettingsLogoChange">
                        </div>
                    </label>
                    <label class="full">Couleur principale (cliquez sur la couleur pour la modifier)
                        <div class="color-field">
                            <input v-model="settingsForm.primary_color" type="color">
                            <input v-model="settingsForm.primary_color" type="text" maxlength="7" placeholder="#2f7d69">
                        </div>
                    </label>
                    <div class="settings-color-panel full">
                        <div>
                            <strong>Couleur secondaire</strong>
                            <span>Choisissez une couleur qui accompagne bien la couleur principale.</span>
                        </div>
                        <div class="color-field">
                            <input v-model="settingsForm.secondary_color" type="color">
                            <input v-model="settingsForm.secondary_color" type="text" maxlength="7" placeholder="#f5b84b">
                        </div>
                        <div class="color-suggestions">
                            <button
                                v-for="color in secondaryColorSuggestions"
                                :key="color"
                                type="button"
                                :class="{ selected: settingsForm.secondary_color?.toLowerCase() === color.toLowerCase() }"
                                :style="{ backgroundColor: color }"
                                @click="settingsForm.secondary_color = color"
                                :title="color"
                                :aria-label="`Choisir ${color}`"
                            >
                                <span>{{ color }}</span>
                            </button>
                        </div>
                        <button class="btn btn-light color-reset-btn" type="button" @click="resetDefaultColors">
                            <i class="fa-solid fa-rotate-left"></i>Revenir aux paramètres par défaut
                        </button>
                    </div>
                    <div class="settings-checks full">
                        <strong>Informations à afficher sur les factures et vos documents</strong>
                        <label><input v-model="settingsForm.show_logo_on_documents" type="checkbox">Logo</label>
                        <label><input v-model="settingsForm.show_ifu_on_documents" type="checkbox">IFU</label>
                        <label><input v-model="settingsForm.show_slogan_on_documents" type="checkbox">Slogan</label>
                        <label><input v-model="settingsForm.show_address_on_documents" type="checkbox">Adresse</label>
                    </div>
                    <button class="btn settings-save-btn" type="submit" :style="settingsPrimaryButtonStyle" :disabled="savingSettings">
                        <i class="fa-solid fa-floppy-disk"></i>{{ savingSettings ? 'Enregistrement...' : 'Enregistrer les paramètres' }}
                    </button>
                </form>
                <p v-if="settingsMessage" class="message">{{ settingsMessage }}</p>
            </section>

            <section v-if="sellerCheckoutActive" class="content-grid">
                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>{{ activeSection === 'seller-proforma' ? 'Nouvelle proforma' : 'Nouvelle vente' }}</h2>
                            <p>{{ activeSection === 'seller-proforma' ? 'Préparez un devis sans mouvement de stock.' : 'Sélectionnez un produit, ajoutez-le au panier, puis validez la facture.' }}</p>
                        </div>
                    </div>

                    <form class="sale-form" @submit.prevent="addSaleLine">
                        <label>Produit
                            <select v-model="saleLine.product_id" required>
                                <option value="">Choisir un produit</option>
                                <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                                    {{ product.name }} - {{ formatMoney(product.sale_price) }} - stock {{ product.stock_quantity }}
                                </option>
                            </select>
                        </label>
                        <label>Quantité
                            <input v-model.number="saleLine.quantity" type="number" min="0.01" step="0.01" required>
                        </label>
                        <label>Remise
                            <input v-model.number="saleLine.discount" type="number" min="0">
                        </label>
                        <button class="btn btn-light" type="submit"><i class="fa-solid fa-cart-plus"></i>Ajouter</button>
                    </form>

                    <div class="cart" v-if="cart.length">
                        <div class="cart-row" v-for="(item, index) in cart" :key="index">
                            <div>
                                <strong>{{ item.product_name }}</strong>
                                <small>{{ item.quantity }} x {{ formatMoney(item.unit_price) }}</small>
                            </div>
                            <span>{{ formatMoney(item.total) }}</span>
                            <button type="button" @click="cart.splice(index, 1)" aria-label="Retirer"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>

                    <form class="checkout" @submit.prevent="saveSale">
                        <label>Nom du client
                            <input v-model="saleForm.customer_name" type="text" placeholder="Client comptoir">
                        </label>
                        <label>Téléphone client
                            <span class="phone-field">
                                <span>01</span>
                                <input :value="phoneInputValue(saleForm.customer_phone)" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="Optionnel" @input="saleForm.customer_phone = optionalPhoneWithPrefix($event.target.value)">
                            </span>
                        </label>
                        <label>Mode de paiement
                            <select v-model="saleForm.payment_method">
                                <option value="cash">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="credit">Crédit</option>
                            </select>
                        </label>
                        <label v-if="activeSection === 'sales'">Type de document
                            <select v-model="saleForm.type">
                                <option value="invoice">Facture</option>
                                <option value="proforma">Facture pro forma</option>
                            </select>
                        </label>
                        <div v-else class="sale-total">
                            <span>Document</span>
                            <strong>{{ activeSection === 'seller-proforma' ? 'Facture pro forma' : 'Facture' }}</strong>
                        </div>
                        <label v-if="saleForm.payment_method === 'credit'">Échéance crédit
                            <input v-model="saleForm.credit_due_date" type="date" required>
                        </label>
                        <div class="sale-total">
                            <span>Total</span>
                            <strong>{{ formatMoney(cartTotal) }}</strong>
                        </div>
                        <button class="btn btn-primary" type="submit" :disabled="savingSale || !cart.length">
                            <i class="fa-solid fa-file-invoice"></i>{{ savingSale ? 'Validation...' : sellerCheckoutSubmitLabel }}
                        </button>
                    </form>

                    <p v-if="saleMessage" class="message">{{ saleMessage }}</p>
                </article>
            </section>

            <section v-if="activeSection === 'sales' && !currentUserCanSell" class="card activation-status-card">
                <i class="fa-solid fa-user-tag"></i>
                <div>
                    <h2>Les ventes sont enregistrées par les vendeurs</h2>
                    <p>
                        L'admin suit les factures ici. Pour vendre, ajoutez un vendeur avec son nom, son téléphone et son mot de passe depuis
                        <a class="highlight-link" :href="sectionUrl('personnel')">Personnel & paie</a>.
                    </p>
                </div>
            </section>

            <section v-if="activeSection === 'stocks'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Stocks</h2>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openProductModal">
                            <i class="fa-solid fa-plus"></i>Ajouter le produit
                        </button>
                        <button class="btn btn-light" type="button" @click="showProductPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="productFilters.search" type="search" placeholder="Produit, catégorie ou unité">
                    </label>
                    <label>Catégorie
                        <select v-model="productFilters.category">
                            <option value="">Toutes les catégories</option>
                            <option v-for="category in productCategories" :key="category" :value="category">{{ category }}</option>
                        </select>
                    </label>
                    <label>État
                        <select v-model="productFilters.status">
                            <option value="">Tous les états</option>
                            <option value="low">Stock bas</option>
                            <option value="ok">OK</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="productSort">
                            <option value="name_asc">Produit A-Z</option>
                            <option value="stock_asc">Stock croissant</option>
                            <option value="stock_desc">Stock décroissant</option>
                            <option value="sale_desc">Prix vente décroissant</option>
                            <option value="category_asc">Catégorie A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ productsCountLabel }}</span>
                    <strong>{{ formatMoney(filteredProductsStockValue) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Stock</th>
                                <th>Seuil</th>
                                <th>Prix achat</th>
                                <th>Prix vente</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in filteredProducts" :key="product.id">
                                <td><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></td>
                                <td>{{ product.category?.name || 'Non classé' }}</td>
                                <td>{{ product.stock_quantity }}</td>
                                <td>{{ product.alert_threshold }}</td>
                                <td>{{ formatMoney(product.purchase_price) }}</td>
                                <td>
                                    <form v-if="currentUser.can_edit_prices" class="inline-price-form" @submit.prevent="updateSellerProductPrice(product)">
                                        <input v-model.number="productPriceForms[product.id]" type="number" min="0" required>
                                        <button class="table-icon" type="submit" title="Enregistrer le prix" aria-label="Enregistrer le prix">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <template v-else>{{ formatMoney(product.sale_price) }}</template>
                                </td>
                                <td><span :class="['status', isLow(product) ? 'danger' : 'ok']">{{ isLow(product) ? 'Stock bas' : 'OK' }}</span></td>
                            </tr>
                            <tr v-if="!filteredProducts.length">
                                <td colspan="7" class="empty">Aucun produit ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredProducts.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="message" class="message">{{ message }}</p>
            </section>

            <div v-if="showProductModal" class="modal-backdrop" @click.self="showProductModal = false">
                <section class="form-modal">
                    <div class="section-title">
                        <div>
                            <h2>Ajouter un produit</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showProductModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form class="product-form" @submit.prevent="saveProduct">
                        <label>Nom du produit
                            <input v-model="form.name" type="text" required placeholder="Ex. Riz premium 25kg">
                        </label>
                        <label>Catégorie
                            <input v-model="form.category_name" type="text" placeholder="Ex. Alimentation">
                        </label>
                        <label>Unité
                            <input v-model="form.unit" type="text" required placeholder="unité, kg, carton">
                        </label>
                        <label>Prix d'achat
                            <input v-model.number="form.purchase_price" type="number" required min="0">
                        </label>
                        <label>Prix de vente
                            <input v-model.number="form.sale_price" type="number" required min="0">
                        </label>
                        <label>Stock initial
                            <input v-model.number="form.stock_quantity" type="number" required min="0" step="0.01">
                        </label>
                        <label>Seuil d'alerte
                            <input v-model.number="form.alert_threshold" type="number" required min="0" step="0.01">
                        </label>
                        <div class="choice-actions full">
                            <button class="btn btn-light" type="button" @click="showProductModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="saving">
                                <i class="fa-solid fa-check"></i>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showProductPrintModal" class="modal-backdrop" @click.self="showProductPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showProductPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="printFilteredProducts">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="exportFilteredProductsExcel">
                            <i class="fa-solid fa-file-csv"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <section v-if="activeSection === 'seller-products'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Produits</h2>
                        <p>Catalogue disponible à la vente, avec les stocks et prix actuels.</p>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="productFilters.search" type="search" placeholder="Produit, catégorie ou unité">
                    </label>
                    <label>Catégorie
                        <select v-model="productFilters.category">
                            <option value="">Toutes les catégories</option>
                            <option v-for="category in productCategories" :key="category" :value="category">{{ category }}</option>
                        </select>
                    </label>
                    <label>État
                        <select v-model="productFilters.status">
                            <option value="">Tous les états</option>
                            <option value="low">Stock bas</option>
                            <option value="ok">OK</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="productSort">
                            <option value="name_asc">Produit A-Z</option>
                            <option value="stock_asc">Stock croissant</option>
                            <option value="stock_desc">Stock décroissant</option>
                            <option value="sale_desc">Prix vente décroissant</option>
                            <option value="category_asc">Catégorie A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ productsCountLabel }}</span>
                    <strong>{{ currentUser.can_edit_prices ? 'Modification prix autorisée' : 'Lecture seule' }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Stock</th>
                                <th>Seuil</th>
                                <th>Prix vente</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in filteredProducts" :key="product.id">
                                <td><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></td>
                                <td>{{ product.category?.name || 'Non classé' }}</td>
                                <td>{{ product.stock_quantity }}</td>
                                <td>{{ product.alert_threshold }}</td>
                                <td>{{ formatMoney(product.sale_price) }}</td>
                                <td><span :class="['status', isLow(product) ? 'danger' : 'ok']">{{ isLow(product) ? 'Stock bas' : 'OK' }}</span></td>
                            </tr>
                            <tr v-if="!filteredProducts.length">
                                <td colspan="6" class="empty">Aucun produit ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredProducts.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
            </section>

            <section v-if="activeSection === 'seller-profile'" class="card seller-profile-card">
                <div class="section-title">
                    <div>
                        <h2>Profil vendeur</h2>
                        <p>Gérez vos informations de connexion.</p>
                    </div>
                </div>
                <div class="profile-summary">
                    <div>
                        <span>Nom</span>
                        <strong>{{ currentUser.name || '-' }}</strong>
                    </div>
                    <div>
                        <span>Login</span>
                        <strong>{{ currentUser.username || currentUser.email || '-' }}</strong>
                    </div>
                    <div>
                        <span>Boutique</span>
                        <strong>{{ business?.name || '-' }}</strong>
                    </div>
                </div>
                <form class="payment-modal-form profile-password-form" @submit.prevent="changeProfilePassword">
                    <label>Mot de passe actuel
                        <span class="password-field">
                            <input v-model="profilePasswordForm.current_password" :type="showProfileCurrentPassword ? 'text' : 'password'" required autocomplete="current-password">
                            <button type="button" @click="showProfileCurrentPassword = !showProfileCurrentPassword" :title="showProfileCurrentPassword ? 'Masquer' : 'Afficher'" :aria-label="showProfileCurrentPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <i :class="showProfileCurrentPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </span>
                    </label>
                    <label>Nouveau mot de passe
                        <span class="password-field">
                            <input v-model="profilePasswordForm.password" :type="showProfilePassword ? 'text' : 'password'" required minlength="8" autocomplete="new-password">
                            <button type="button" @click="showProfilePassword = !showProfilePassword" :title="showProfilePassword ? 'Masquer' : 'Afficher'" :aria-label="showProfilePassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <i :class="showProfilePassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </span>
                    </label>
                    <label>Confirmer le mot de passe
                        <span class="password-field">
                            <input v-model="profilePasswordForm.password_confirmation" :type="showProfilePasswordConfirmation ? 'text' : 'password'" required minlength="8" autocomplete="new-password">
                            <button type="button" @click="showProfilePasswordConfirmation = !showProfilePasswordConfirmation" :title="showProfilePasswordConfirmation ? 'Masquer' : 'Afficher'" :aria-label="showProfilePasswordConfirmation ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <i :class="showProfilePasswordConfirmation ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                            </button>
                        </span>
                    </label>
                    <div class="choice-actions full">
                        <button class="btn btn-primary" type="submit" :disabled="savingProfilePassword">
                            <i class="fa-solid fa-key"></i>{{ savingProfilePassword ? 'Modification...' : 'Modifier le mot de passe' }}
                        </button>
                    </div>
                </form>
                <p v-if="profileMessage" class="message">{{ profileMessage }}</p>
            </section>

            <div v-if="activeSection === 'sales' && !currentUserCanSell" class="customer-view-switch">
                <button :class="['customer-switch-card', salesView === 'invoices' ? 'active' : '']" type="button" @click="salesView = 'invoices'">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Ventes</span>
                    <strong>{{ formatMoney(filteredSalesTotal) }}</strong>
                </button>
                <button :class="['customer-switch-card', salesView === 'proformas' ? 'active' : '']" type="button" @click="salesView = 'proformas'">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Factures Pro forma</span>
                    <strong>{{ filteredProformas.length }}</strong>
                </button>
            </div>

            <section v-if="salesHistoryActive" class="card">
                <div class="section-title">
                    <div>
                        <h2>{{ sellerSalesTitle }}</h2>
                        <p>{{ currentUserCanSell ? 'Consultez uniquement les documents que vous avez créés.' : (salesView === 'proformas' ? 'Suivez les pro formas établies avant validation de vente.' : 'Suivez toutes les ventes effectuées par vos vendeurs.') }}</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-light" type="button" @click="showSalesPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="salesFilters.search" type="search" :placeholder="currentUserCanSell ? 'Facture ou client' : 'Facture, vendeur, client'">
                    </label>
                    <label v-if="salesView !== 'proformas' && activeSection !== 'seller-proforma'">Paiement
                        <select v-model="salesFilters.payment">
                            <option value="">Tous les paiements</option>
                            <option value="cash">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="credit">Crédit</option>
                        </select>
                    </label>
                    <div class="filter-control">
                        <span>Trier par date</span>
                        <button class="btn filter-date-btn" type="button" @click="openSalesDateFilter">
                            <i class="fa-solid fa-calendar-days"></i>{{ salesDateFilterLabel }}
                        </button>
                    </div>
                    <label>Trier par
                        <select v-model="salesSort">
                            <option value="date_desc">Date récente</option>
                            <option value="date_asc">Date ancienne</option>
                            <option value="total_desc">Total décroissant</option>
                            <option value="total_asc">Total croissant</option>
                            <option value="seller_asc">Vendeur A-Z</option>
                            <option value="payment_asc">Paiement A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ salesView === 'proformas' ? proformasCountLabel : salesCountLabel }}</span>
                    <strong>{{ formatMoney(salesView === 'proformas' ? filteredProformasTotal : filteredSalesTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Facture</th>
                                <th v-if="!currentUserCanSell">Vendeur</th>
                                <th>Client</th>
                                <th v-if="salesView !== 'proformas'">Paiement</th>
                                <th v-if="salesView !== 'proformas'">Statut</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in displayedSalesRows" :key="sale.id">
                                <td>{{ formatDate(sale.sold_at) }}</td>
                                <td><strong>{{ sale.number }}</strong><small>{{ sale.items.length }} ligne(s)</small></td>
                                <td v-if="!currentUserCanSell"><strong>{{ sale.seller?.name || 'Vendeur' }}</strong><small>{{ sale.seller?.phone || '-' }}</small></td>
                                <td>{{ sale.customer?.name || 'Client comptoir' }}</td>
                                <td v-if="salesView !== 'proformas'">{{ paymentLabel(sale.payment_method) }}</td>
                                <td v-if="salesView !== 'proformas'"><span class="status ok">{{ sale.status }}</span></td>
                                <td>{{ formatMoney(sale.total) }}</td>
                                <td><a class="table-icon" :href="`/businesses/${businessId}/sales/${sale.id}/invoice`" target="_blank" title="Voir la facture" aria-label="Voir la facture"><i class="fa-solid fa-file-invoice"></i></a></td>
                            </tr>
                            <tr v-if="!displayedSalesRows.length">
                                <td :colspan="salesEmptyColspan" class="empty">{{ salesView === 'proformas' || activeSection === 'seller-proforma' ? 'Aucune facture pro forma ne correspond aux filtres.' : 'Aucune vente ne correspond aux filtres.' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(displayedSalesRows.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
            </section>

            <div v-if="showSalesDateModal" class="modal-backdrop" @click.self="showSalesDateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Trier par date</h2>
                            <p>Choisissez une date de vente précise ou une période.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSalesDateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button :class="['btn', salesDateMode === 'exact' ? 'btn-primary' : 'btn-light']" type="button" @click="salesDateMode = 'exact'">
                            Date précise
                        </button>
                        <button :class="['btn', salesDateMode === 'range' ? 'btn-primary' : 'btn-light']" type="button" @click="salesDateMode = 'range'">
                            Entre deux dates
                        </button>
                    </div>
                    <div class="choice-form">
                        <label v-if="salesDateMode === 'exact'">Date précise
                            <input v-model="salesDateDraft.exactDate" type="date">
                        </label>
                        <template v-else>
                            <label>Du
                                <input v-model="salesDateDraft.startDate" type="date">
                            </label>
                            <label>Au
                                <input v-model="salesDateDraft.endDate" type="date">
                            </label>
                        </template>
                    </div>
                    <div class="choice-actions">
                        <button class="btn btn-light" type="button" @click="clearSalesDateFilter">
                            <i class="fa-solid fa-eraser"></i>Effacer
                        </button>
                        <button class="btn btn-primary" type="button" @click="applySalesDateFilter">
                            <i class="fa-solid fa-check"></i>Appliquer
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showSalesPrintModal" class="modal-backdrop" @click.self="showSalesPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des ventes affichées.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSalesPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseSalesPrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseSalesPrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="activeSection === 'customers'" class="customer-view-switch">
                <button :class="['customer-switch-card', customerView === 'receivables' ? 'active' : '']" type="button" @click="customerView = 'receivables'">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Créances</span>
                    <strong>{{ formatMoney(filteredReceivablesTotal) }}</strong>
                </button>
                <button :class="['customer-switch-card', customerView === 'clients' ? 'active' : '']" type="button" @click="customerView = 'clients'">
                    <i class="fa-solid fa-users"></i>
                    <span>Clients</span>
                    <strong>{{ customerSummaries.length }}</strong>
                </button>
            </div>

            <section v-if="activeSection === 'customers' && customerView === 'receivables'" class="card receivables-section">
                <div class="section-title">
                    <div>
                        <h2>Créances</h2>
                        <p>Montants dus, factures associées, relances et paiements clients.</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openReceivableCreateModal">
                            <i class="fa-solid fa-plus"></i>Nouvelle créance
                        </button>
                        <button class="btn btn-light" type="button" @click="showReceivablePrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="receivableFilters.search" type="search" placeholder="Nom ou téléphone client">
                    </label>
                    <label>Statut
                        <select v-model="receivableFilters.status">
                            <option value="">Tous les statuts</option>
                            <option value="current">À jour</option>
                            <option value="overdue">En retard</option>
                            <option value="paid">Payée</option>
                        </select>
                    </label>
                    <div class="filter-control">
                        <span>Trier par date</span>
                        <button class="btn filter-date-btn" type="button" @click="openReceivableDateFilter">
                            <i class="fa-solid fa-calendar-days"></i>{{ receivableDateFilterLabel }}
                        </button>
                    </div>
                    <label>Trier par
                        <select v-model="receivableSort">
                            <option value="due_date_asc">Échéance proche</option>
                            <option value="due_date_desc">Échéance éloignée</option>
                            <option value="remaining_desc">Reste décroissant</option>
                            <option value="remaining_asc">Reste croissant</option>
                            <option value="customer_asc">Client A-Z</option>
                            <option value="status_asc">Statut A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ receivablesCountLabel }}</span>
                    <strong>{{ formatMoney(filteredReceivablesTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Échéance</th>
                                <th>Client</th>
                                <th>Montant dû</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Statut</th>
                                <th>Facture</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="receivable in filteredReceivables" :key="receivable.id">
                                <td>{{ formatDateOnly(receivable.due_date) }}</td>
                                <td><strong>{{ receivable.customer?.name || 'Client' }}</strong><small>{{ receivable.customer?.phone || 'Téléphone non renseigné' }}</small></td>
                                <td>{{ formatMoney(receivable.amount_due) }}</td>
                                <td>{{ formatMoney(receivable.amount_paid) }}</td>
                                <td>{{ formatMoney(receivable.remaining) }}</td>
                                <td><span :class="['status', receivableStatusClass(receivable.status)]">{{ receivableStatusLabel(receivable.status) }}</span></td>
                                <td>
                                    <a v-if="receivable.invoice" class="table-link" :href="`/businesses/${businessId}/sales/${receivable.invoice.id}/invoice`" target="_blank">
                                        {{ receivable.invoice.number }}
                                    </a>
                                    <span v-else>-</span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openReceivableDetails(receivable)" title="Voir la situation" aria-label="Voir la situation">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="table-icon" type="button" :disabled="!receivable.remaining" @click="openReceivablePayment(receivable)" title="Ajouter un paiement" aria-label="Ajouter un paiement">
                                            <i class="fa-solid fa-money-bill"></i>
                                        </button>
                                        <button class="table-icon" type="button" @click="openReceivableAmountEdit(receivable)" title="Modifier le montant" aria-label="Modifier le montant">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="table-icon danger-icon" type="button" @click="deleteReceivable(receivable)" title="Supprimer la dette" aria-label="Supprimer la dette">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredReceivables.length">
                                <td colspan="8" class="empty">Aucune créance client ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredReceivables.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="receivableMessage" class="message">{{ receivableMessage }}</p>
            </section>

            <section v-if="activeSection === 'customers' && customerView === 'clients'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Clients</h2>
                        <p>Fiches clients, achats, factures et situation des comptes.</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openCustomerCreateModal">
                            <i class="fa-solid fa-user-plus"></i>Nouveau client
                        </button>
                        <button class="btn btn-light" type="button" @click="showCustomerPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="customerFilters.search" type="search" placeholder="Nom, téléphone ou facture">
                    </label>
                    <label>Situation
                        <select v-model="customerFilters.situation">
                            <option value="">Toutes les situations</option>
                            <option value="debt">Créances en cours</option>
                            <option value="ok">Client OK</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="customerSort">
                            <option value="name_asc">Client A-Z</option>
                            <option value="purchases_desc">Achats décroissants</option>
                            <option value="debt_desc">Créances décroissantes</option>
                            <option value="invoices_desc">Nombre de factures</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ customersCountLabel }}</span>
                    <strong>{{ formatMoney(filteredCustomersTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Achats</th>
                                <th>Créances</th>
                                <th>Situation</th>
                                <th>Factures</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in filteredCustomers" :key="client.key">
                                <td><strong>{{ client.name }}</strong><small>{{ client.email || client.address || '-' }}</small></td>
                                <td>{{ client.phone || '-' }}</td>
                                <td>{{ formatMoney(client.totalPurchases) }}</td>
                                <td>{{ formatMoney(client.remainingDebt) }}</td>
                                <td><span :class="['status', client.hasDebt ? 'danger' : 'ok']">{{ client.situation }}</span></td>
                                <td>
                                    <div class="invoice-cell">
                                        <div v-for="invoice in client.invoices" :key="invoice.id" class="invoice-mini-row">
                                            <a :href="`/businesses/${businessId}/sales/${invoice.id}/invoice`" target="_blank">{{ invoice.number }}</a>
                                            <span>{{ formatMoney(invoice.total) }}</span>
                                            <small>{{ formatDate(invoice.sold_at) }} - {{ paymentLabel(invoice.payment_method) }}</small>
                                            <small v-if="invoice.items.length">{{ invoiceDetails(invoice) }}</small>
                                        </div>
                                        <span v-if="!client.invoices.length">Aucune facture récente</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openCustomerDetails(client)" title="Voir la fiche complète" aria-label="Voir la fiche complète">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a v-if="client.phone" class="table-icon" :href="`tel:${client.phone}`" title="Appeler" aria-label="Appeler">
                                            <i class="fa-solid fa-phone"></i>
                                        </a>
                                        <button v-else class="table-icon" type="button" disabled title="Téléphone indisponible" aria-label="Téléphone indisponible">
                                            <i class="fa-solid fa-phone"></i>
                                        </button>
                                        <a v-if="client.phone" class="table-icon whatsapp" :href="customerWhatsappUrl(client)" target="_blank" title="Contacter sur WhatsApp" aria-label="Contacter sur WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                        <button v-else class="table-icon" type="button" disabled title="WhatsApp indisponible" aria-label="WhatsApp indisponible">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredCustomers.length">
                                <td colspan="7" class="empty">Aucun client ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredCustomers.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
            </section>

            <div v-if="showReceivablePaymentModal" class="modal-backdrop" @click.self="closeReceivablePayment">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Nouveau paiement</h2>
                            <p>{{ selectedReceivable?.customer?.name || 'Client' }} - reste {{ formatMoney(selectedReceivable?.remaining || 0) }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeReceivablePayment" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="payReceivable(selectedReceivable)">
                        <label>Montant
                            <input v-model.number="selectedReceivable.payment_amount" type="number" min="1" :max="selectedReceivable.remaining" required>
                        </label>
                        <label>Moyen de paiement
                            <select v-model="selectedReceivable.payment_method" required>
                                <option value="cash">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeReceivablePayment">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingAdvance">
                                <i class="fa-solid fa-check"></i>{{ savingAdvance ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showReceivableDetailsModal" class="modal-backdrop" @click.self="closeReceivableDetails">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Situation créance</h2>
                        </div>
                        <div class="row-actions">
                            <button class="table-icon" type="button" @click="showReceivableDetailsPrintModal = true" title="Imprimer" aria-label="Imprimer">
                                <i class="fa-solid fa-print"></i>
                            </button>
                            <button class="table-icon" type="button" @click="closeReceivableDetails" title="Fermer" aria-label="Fermer">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    <div class="receivable-detail-grid">
                        <div><span>Client</span><strong>{{ selectedReceivableDetails?.customer?.name || 'Client' }}</strong></div>
                        <div><span>Date de contraction</span><strong>{{ formatDate(selectedReceivableDetails?.created_at) }}</strong></div>
                        <div><span>Montant initial</span><strong>{{ formatMoney(selectedReceivableDetails?.amount_due || 0) }}</strong></div>
                        <div><span>Solde dû</span><strong>{{ formatMoney(selectedReceivableDetails?.remaining || 0) }}</strong></div>
                        <div><span>Échéance</span><strong>{{ formatDateOnly(selectedReceivableDetails?.due_date) }}</strong></div>
                        <div><span>Statut</span><strong>{{ receivableStatusLabel(selectedReceivableDetails?.status) }}</strong></div>
                    </div>
                    <div v-if="selectedReceivableDetails?.notes" class="receivable-notes">
                        <strong>Note</strong>
                        <p>{{ selectedReceivableDetails.notes }}</p>
                    </div>
                    <div class="payment-history">
                        <strong>Historique des paiements</strong>
                        <div v-for="payment in selectedReceivableDetails?.payments || []" :key="payment.id" class="payment-history-row">
                            <span>{{ formatDate(payment.paid_at) }}</span>
                            <b>{{ formatMoney(payment.amount) }}</b>
                            <button class="table-icon payment-edit-icon" type="button" @click="openReceivablePaymentEdit(payment)" title="Modifier le paiement" aria-label="Modifier le paiement">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <small>{{ paymentLabel(payment.method) }}{{ payment.reference ? ' - ' + payment.reference : '' }}</small>
                        </div>
                        <p v-if="!(selectedReceivableDetails?.payments || []).length" class="empty small-empty">Aucun paiement enregistré.</p>
                    </div>
                </section>
            </div>

            <div v-if="showReceivableDetailsPrintModal" class="modal-backdrop" @click.self="showReceivableDetailsPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression de cette créance.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showReceivableDetailsPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseReceivableDetailsPrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseReceivableDetailsPrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showReceivableAmountEditModal" class="modal-backdrop" @click.self="closeReceivableAmountEdit">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Modifier la créance</h2>
                            <p>{{ editingReceivable?.customer?.name || 'Client' }} - payé {{ formatMoney(editingReceivable?.amount_paid || 0) }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeReceivableAmountEdit" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="updateReceivableAmount">
                        <label>Montant de la créance
                            <input v-model.number="receivableAmountForm.amount_due" type="number" :min="editingReceivable?.amount_paid || 1" required>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeReceivableAmountEdit">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showReceivablePaymentEditModal" class="modal-backdrop" @click.self="closeReceivablePaymentEdit">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Modifier le paiement</h2>
                            <p>{{ selectedReceivableDetails?.customer?.name || 'Client' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeReceivablePaymentEdit" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="updateReceivablePaymentAmount">
                        <label>Montant du paiement
                            <input v-model.number="receivablePaymentEditForm.amount" type="number" min="1" :max="maxEditableReceivablePaymentAmount" required>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeReceivablePaymentEdit">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showReceivableCreateModal" class="modal-backdrop" @click.self="showReceivableCreateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Nouvelle créance</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showReceivableCreateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="saveReceivable">
                        <label>Client existant
                            <select v-model="receivableForm.customer_id">
                                <option value="">Nouveau client</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }} - {{ customer.phone || 'sans téléphone' }}</option>
                            </select>
                        </label>
                        <template v-if="!receivableForm.customer_id">
                            <label>Nom du client
                                <input v-model="receivableForm.customer_name" type="text" required placeholder="Nom complet">
                            </label>
                            <label>Téléphone
                                <span class="phone-field">
                                    <span>01</span>
                                    <input :value="phoneInputValue(receivableForm.customer_phone)" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="receivableForm.customer_phone = optionalPhoneWithPrefix($event.target.value)">
                                </span>
                            </label>
                        </template>
                        <label>Montant dû
                            <input v-model.number="receivableForm.amount_due" type="number" min="1" required>
                        </label>
                        <label>Échéance
                            <input v-model="receivableForm.due_date" type="date">
                        </label>
                        <label>Note <span>Optionnel</span>
                            <textarea v-model="receivableForm.notes" rows="3" placeholder="Ex. Relancer après la livraison"></textarea>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-primary" type="button" @click="showReceivableCreateModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-light" type="submit" :disabled="savingReceivable">
                                <i class="fa-solid fa-check"></i>{{ savingReceivable ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showCustomerCreateModal" class="modal-backdrop" @click.self="showCustomerCreateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Nouveau client</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showCustomerCreateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="saveCustomer">
                        <label>Nom
                            <input v-model="customerForm.name" type="text" required placeholder="Nom complet">
                        </label>
                        <label>Téléphone
                            <span class="phone-field">
                                <span>01</span>
                                <input :value="phoneInputValue(customerForm.phone)" type="tel" inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="customerForm.phone = optionalPhoneWithPrefix($event.target.value)">
                            </span>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-primary" type="button" @click="showCustomerCreateModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-light" type="submit" :disabled="savingCustomer">
                                <i class="fa-solid fa-check"></i>{{ savingCustomer ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showCustomerPrintModal" class="modal-backdrop" @click.self="showCustomerPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des clients affichés.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showCustomerPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseCustomerPrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseCustomerPrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showCustomerDetailsModal" class="modal-backdrop" @click.self="closeCustomerDetails">
                <section class="choice-modal customer-detail-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Fiche client</h2>
                        </div>
                        <button class="table-icon" type="button" @click="closeCustomerDetails" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="receivable-detail-grid">
                        <div><span>Client</span><strong>{{ selectedCustomerDetails?.name || 'Client' }}</strong></div>
                        <div><span>Téléphone</span><strong>{{ selectedCustomerDetails?.phone || '-' }}</strong></div>
                        <div><span>Total achats</span><strong>{{ formatMoney(selectedCustomerDetails?.totalPurchases || 0) }}</strong></div>
                        <div><span>Créances</span><strong>{{ formatMoney(selectedCustomerDetails?.remainingDebt || 0) }}</strong></div>
                        <div><span>Situation</span><strong>{{ selectedCustomerDetails?.situation || '-' }}</strong></div>
                        <div><span>Nombre de factures</span><strong>{{ selectedCustomerDetails?.invoices?.length || 0 }}</strong></div>
                    </div>
                    <div class="customer-contact-actions">
                        <a v-if="selectedCustomerDetails?.phone" class="btn btn-light" :href="`tel:${selectedCustomerDetails.phone}`">
                            <i class="fa-solid fa-phone"></i>Appeler
                        </a>
                        <a v-if="selectedCustomerDetails?.phone" class="btn btn-light" :href="customerWhatsappUrl(selectedCustomerDetails)" target="_blank">
                            <i class="fa-brands fa-whatsapp"></i>WhatsApp
                        </a>
                    </div>
                    <div class="payment-history">
                        <strong>Avances / créances en cours</strong>
                        <div v-for="receivable in selectedCustomerDetails?.receivables || []" :key="receivable.id" class="customer-invoice-detail">
                            <div>
                                <b>{{ formatMoney(receivable.remaining || 0) }}</b>
                                <small>Initial : {{ formatMoney(receivable.amount_due || 0) }} - Payé : {{ formatMoney(receivable.amount_paid || 0) }}</small>
                                <small>Échéance : {{ formatDateOnly(receivable.due_date) }} - {{ receivableStatusLabel(receivable.status) }}</small>
                            </div>
                        </div>
                        <p v-if="!(selectedCustomerDetails?.receivables || []).length" class="empty small-empty">Aucune avance en cours.</p>
                    </div>
                    <div class="payment-history">
                        <strong>Historique des achats et factures</strong>
                        <div v-for="invoice in selectedCustomerDetails?.invoices || []" :key="invoice.id" class="customer-invoice-detail">
                            <div>
                                <a :href="`/businesses/${businessId}/sales/${invoice.id}/invoice`" target="_blank">{{ invoice.number }}</a>
                                <b>{{ formatMoney(invoice.total) }}</b>
                                <small>{{ formatDate(invoice.sold_at) }} - {{ paymentLabel(invoice.payment_method) }}</small>
                            </div>
                            <ul v-if="invoice.items.length">
                                <li v-for="item in invoice.items" :key="`${invoice.id}-${item.product_name}`">
                                    {{ item.product_name }} - {{ Number(item.quantity) }} x {{ formatMoney(item.unit_price) }}
                                </li>
                            </ul>
                        </div>
                        <p v-if="!(selectedCustomerDetails?.invoices || []).length" class="empty small-empty">Aucune facture récente.</p>
                    </div>
                </section>
            </div>

            <div v-if="showReceivableDateModal" class="modal-backdrop" @click.self="showReceivableDateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Trier par date</h2>
                            <p>Choisissez une date d’échéance précise ou une période.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showReceivableDateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button :class="['btn', receivableDateMode === 'exact' ? 'btn-primary' : 'btn-light']" type="button" @click="receivableDateMode = 'exact'">
                            Date précise
                        </button>
                        <button :class="['btn', receivableDateMode === 'range' ? 'btn-primary' : 'btn-light']" type="button" @click="receivableDateMode = 'range'">
                            Entre deux dates
                        </button>
                    </div>
                    <div class="choice-form">
                        <label v-if="receivableDateMode === 'exact'">Date précise
                            <input v-model="receivableDateDraft.exactDate" type="date">
                        </label>
                        <template v-else>
                            <label>Du
                                <input v-model="receivableDateDraft.startDate" type="date">
                            </label>
                            <label>Au
                                <input v-model="receivableDateDraft.endDate" type="date">
                            </label>
                        </template>
                    </div>
                    <div class="choice-actions">
                        <button class="btn btn-light" type="button" @click="clearReceivableDateFilter">
                            <i class="fa-solid fa-eraser"></i>Effacer
                        </button>
                        <button class="btn btn-primary" type="button" @click="applyReceivableDateFilter">
                            <i class="fa-solid fa-check"></i>Appliquer
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showReceivablePrintModal" class="modal-backdrop" @click.self="showReceivablePrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des créances affichées.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showReceivablePrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseReceivablePrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseReceivablePrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="activeSection === 'suppliers'" class="customer-view-switch">
                <button :class="['customer-switch-card', supplierView === 'debts' ? 'active' : '']" type="button" @click="supplierView = 'debts'">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Dettes</span>
                    <strong>{{ formatMoney(filteredSupplierDebtsTotal) }}</strong>
                </button>
                <button :class="['customer-switch-card', supplierView === 'suppliers' ? 'active' : '']" type="button" @click="supplierView = 'suppliers'">
                    <i class="fa-solid fa-truck"></i>
                    <span>Fournisseurs</span>
                    <strong>{{ supplierSummaries.length }}</strong>
                </button>
            </div>

            <section v-if="activeSection === 'suppliers' && supplierView === 'debts'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Dettes fournisseurs</h2>
                        <p>Filtrez, triez et exportez les montants dus aux fournisseurs.</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openSupplierDebtCreateModal">
                            <i class="fa-solid fa-plus"></i>Nouvelle dette
                        </button>
                        <button class="btn btn-light" type="button" @click="showSupplierDebtPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="supplierDebtFilters.search" type="search" placeholder="Fournisseur ou téléphone">
                    </label>
                    <label>Statut
                        <select v-model="supplierDebtFilters.status">
                            <option value="">Tous les statuts</option>
                            <option value="current">En cours</option>
                            <option value="overdue">En retard</option>
                            <option value="paid">Payée</option>
                        </select>
                    </label>
                    <div class="filter-control">
                        <span>Trier par date</span>
                        <button class="btn filter-date-btn" type="button" @click="openSupplierDebtDateFilter">
                            <i class="fa-solid fa-calendar-days"></i>{{ supplierDebtDateFilterLabel }}
                        </button>
                    </div>
                    <label>Trier par
                        <select v-model="supplierDebtSort">
                            <option value="due_date_asc">Échéance proche</option>
                            <option value="due_date_desc">Échéance éloignée</option>
                            <option value="remaining_desc">Reste décroissant</option>
                            <option value="remaining_asc">Reste croissant</option>
                            <option value="supplier_asc">Fournisseur A-Z</option>
                            <option value="status_asc">Statut A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ supplierDebtsCountLabel }}</span>
                    <strong>{{ formatMoney(filteredSupplierDebtsTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Échéance</th>
                                <th>Fournisseur</th>
                                <th>Montant dû</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="debt in filteredSupplierDebts" :key="debt.id">
                                <td>{{ formatDateOnly(debt.due_date) }}</td>
                                <td><strong>{{ debt.supplier?.name || 'Fournisseur' }}</strong><small>{{ debt.supplier?.phone || 'Téléphone non renseigné' }}</small></td>
                                <td>{{ formatMoney(debt.amount_due) }}</td>
                                <td>{{ formatMoney(debt.amount_paid) }}</td>
                                <td>{{ formatMoney(debt.remaining) }}</td>
                                <td><span :class="['status', receivableStatusClass(debt.status)]">{{ receivableStatusLabel(debt.status) }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openSupplierDebtDetails(debt)" title="Voir la situation" aria-label="Voir la situation">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="table-icon" type="button" :disabled="!debt.remaining" @click="openSupplierDebtPayment(debt)" title="Ajouter un paiement" aria-label="Ajouter un paiement">
                                            <i class="fa-solid fa-money-bill"></i>
                                        </button>
                                        <button class="table-icon" type="button" @click="openSupplierDebtAmountEdit(debt)" title="Modifier le montant" aria-label="Modifier le montant">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="table-icon danger-icon" type="button" @click="deleteSupplierDebt(debt)" title="Supprimer la dette" aria-label="Supprimer la dette">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredSupplierDebts.length">
                                <td colspan="7" class="empty">Aucune dette fournisseur ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredSupplierDebts.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="supplierDebtMessage" class="message">{{ supplierDebtMessage }}</p>
            </section>

            <div v-if="showSupplierDebtPaymentModal" class="modal-backdrop" @click.self="closeSupplierDebtPayment">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Nouveau paiement</h2>
                            <p>{{ selectedSupplierDebt?.supplier?.name || 'Fournisseur' }} - reste {{ formatMoney(selectedSupplierDebt?.remaining || 0) }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierDebtPayment" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="paySupplierDebt(selectedSupplierDebt)">
                        <label>Montant
                            <input v-model.number="selectedSupplierDebt.payment_amount" type="number" min="1" :max="selectedSupplierDebt.remaining" required>
                        </label>
                        <label>Moyen de paiement
                            <select v-model="selectedSupplierDebt.payment_method" required>
                                <option value="cash">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeSupplierDebtPayment">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showSupplierDebtDetailsModal" class="modal-backdrop" @click.self="closeSupplierDebtDetails">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Situation dette</h2>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierDebtDetails" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="receivable-detail-grid">
                        <div><span>Fournisseur</span><strong>{{ selectedSupplierDebtDetails?.supplier?.name || 'Fournisseur' }}</strong></div>
                        <div><span>Date de contraction</span><strong>{{ formatDate(selectedSupplierDebtDetails?.created_at) }}</strong></div>
                        <div><span>Montant initial</span><strong>{{ formatMoney(selectedSupplierDebtDetails?.amount_due || 0) }}</strong></div>
                        <div><span>Solde dû</span><strong>{{ formatMoney(selectedSupplierDebtDetails?.remaining || 0) }}</strong></div>
                        <div><span>Échéance</span><strong>{{ formatDateOnly(selectedSupplierDebtDetails?.due_date) }}</strong></div>
                        <div><span>Statut</span><strong>{{ receivableStatusLabel(selectedSupplierDebtDetails?.status) }}</strong></div>
                    </div>
                    <div v-if="selectedSupplierDebtDetails?.notes" class="receivable-notes">
                        <strong>Note</strong>
                        <p>{{ selectedSupplierDebtDetails.notes }}</p>
                    </div>
                    <div class="payment-history">
                        <strong>Historique des paiements</strong>
                        <div v-for="payment in selectedSupplierDebtDetails?.payments || []" :key="payment.id" class="payment-history-row">
                            <span>{{ formatDate(payment.paid_at) }}</span>
                            <b>{{ formatMoney(payment.amount) }}</b>
                            <button class="table-icon payment-edit-icon" type="button" @click="openSupplierDebtPaymentEdit(payment)" title="Modifier le paiement" aria-label="Modifier le paiement">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <small>{{ paymentLabel(payment.method) }}{{ payment.reference ? ' - ' + payment.reference : '' }}</small>
                        </div>
                        <p v-if="!(selectedSupplierDebtDetails?.payments || []).length" class="empty small-empty">Aucun paiement enregistré.</p>
                    </div>
                </section>
            </div>

            <div v-if="showSupplierDebtAmountEditModal" class="modal-backdrop" @click.self="closeSupplierDebtAmountEdit">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Modifier la dette</h2>
                            <p>{{ editingSupplierDebt?.supplier?.name || 'Fournisseur' }} - payé {{ formatMoney(editingSupplierDebt?.amount_paid || 0) }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierDebtAmountEdit" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="updateSupplierDebtAmount">
                        <label>Montant de la dette
                            <input v-model.number="supplierDebtAmountForm.amount_due" type="number" :min="editingSupplierDebt?.amount_paid || 1" required>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeSupplierDebtAmountEdit">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showSupplierDebtPaymentEditModal" class="modal-backdrop" @click.self="closeSupplierDebtPaymentEdit">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Modifier le paiement</h2>
                            <p>{{ selectedSupplierDebtDetails?.supplier?.name || 'Fournisseur' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierDebtPaymentEdit" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="updateSupplierDebtPaymentAmount">
                        <label>Montant du paiement
                            <input v-model.number="supplierDebtPaymentEditForm.amount" type="number" min="1" :max="maxEditableSupplierDebtPaymentAmount" required>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeSupplierDebtPaymentEdit">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <section v-if="activeSection === 'suppliers' && supplierView === 'suppliers'" class="card suppliers-section">
                <div class="section-title">
                    <div>
                        <h2>Fournisseurs</h2>
                        <p>Fiches fournisseurs et situation des comptes.</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openSupplierCreateModal">
                            <i class="fa-solid fa-plus"></i>Nouveau fournisseur
                        </button>
                        <button class="btn btn-light" type="button" @click="showSupplierPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="supplierFilters.search" type="search" placeholder="Nom ou téléphone">
                    </label>
                    <label>Situation
                        <select v-model="supplierFilters.situation">
                            <option value="">Toutes les situations</option>
                            <option value="debt">Dettes en cours</option>
                            <option value="ok">Fournisseur OK</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="supplierSort">
                            <option value="name_asc">Fournisseur A-Z</option>
                            <option value="debt_desc">Dettes décroissantes</option>
                            <option value="orders_desc">Nombre de dettes</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ suppliersCountLabel }}</span>
                    <strong>{{ formatMoney(filteredSuppliersDebtTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fournisseur</th>
                                <th>Téléphone</th>
                                <th>Total dû</th>
                                <th>Reste</th>
                                <th>Situation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="supplier in filteredSuppliers" :key="supplier.key">
                                <td><strong>{{ supplier.name }}</strong></td>
                                <td>{{ supplier.phone || '-' }}</td>
                                <td>{{ formatMoney(supplier.totalDebt) }}</td>
                                <td>{{ formatMoney(supplier.remainingDebt) }}</td>
                                <td><span :class="['status', supplier.hasDebt ? 'danger' : 'ok']">{{ supplier.situation }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openSupplierDetails(supplier)" title="Voir la fiche complète" aria-label="Voir la fiche complète">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a v-if="supplier.phone" class="table-icon" :href="`tel:${supplier.phone}`" title="Appeler" aria-label="Appeler">
                                            <i class="fa-solid fa-phone"></i>
                                        </a>
                                        <button v-else class="table-icon" type="button" disabled title="Téléphone indisponible" aria-label="Téléphone indisponible">
                                            <i class="fa-solid fa-phone"></i>
                                        </button>
                                        <a v-if="supplier.phone" class="table-icon whatsapp" :href="supplierWhatsappUrl(supplier)" target="_blank" title="Contacter sur WhatsApp" aria-label="Contacter sur WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                        <button v-else class="table-icon" type="button" disabled title="WhatsApp indisponible" aria-label="WhatsApp indisponible">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>
                                        <button class="table-icon" type="button" @click="openSupplierPhoneEdit(supplier)" title="Modifier le téléphone" aria-label="Modifier le téléphone">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredSuppliers.length">
                                <td colspan="6" class="empty">Aucun fournisseur ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredSuppliers.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
            </section>

            <div v-if="showSupplierDetailsModal" class="modal-backdrop" @click.self="closeSupplierDetails">
                <section class="choice-modal customer-detail-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="large-modal-title">Fiche fournisseur</h2>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierDetails" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="receivable-detail-grid">
                        <div><span>Fournisseur</span><strong>{{ selectedSupplierDetails?.name || 'Fournisseur' }}</strong></div>
                        <div><span>Téléphone</span><strong>{{ selectedSupplierDetails?.phone || '-' }}</strong></div>
                        <div><span>Total dû</span><strong>{{ formatMoney(selectedSupplierDetails?.totalDebt || 0) }}</strong></div>
                        <div><span>Dettes</span><strong>{{ formatMoney(selectedSupplierDetails?.remainingDebt || 0) }}</strong></div>
                        <div><span>Situation</span><strong>{{ selectedSupplierDetails?.situation || '-' }}</strong></div>
                        <div><span>Nombre de dettes</span><strong>{{ selectedSupplierDetails?.debts?.length || 0 }}</strong></div>
                    </div>
                    <div v-if="selectedSupplierDetails?.notes" class="receivable-notes">
                        <strong>Note</strong>
                        <p>{{ selectedSupplierDetails.notes }}</p>
                    </div>
                    <div class="customer-contact-actions">
                        <a v-if="selectedSupplierDetails?.phone" class="btn btn-light" :href="`tel:${selectedSupplierDetails.phone}`">
                            <i class="fa-solid fa-phone"></i>Appeler
                        </a>
                        <a v-if="selectedSupplierDetails?.phone" class="btn btn-light" :href="supplierWhatsappUrl(selectedSupplierDetails)" target="_blank">
                            <i class="fa-brands fa-whatsapp"></i>WhatsApp
                        </a>
                    </div>
                    <div class="payment-history">
                        <strong>Dettes récentes</strong>
                        <div v-for="debt in selectedSupplierDetails?.debts || []" :key="debt.id" class="customer-invoice-detail">
                            <div>
                                <strong>{{ formatMoney(debt.remaining) }}</strong>
                                <span>{{ receivableStatusLabel(debt.status) }}</span>
                            </div>
                            <small>Initial : {{ formatMoney(debt.amount_due) }} - Payé : {{ formatMoney(debt.amount_paid) }} - Échéance : {{ formatDateOnly(debt.due_date) }}</small>
                        </div>
                        <p v-if="!(selectedSupplierDetails?.debts || []).length" class="empty small-empty">Aucune dette récente.</p>
                    </div>
                </section>
            </div>

            <div v-if="showSupplierDebtCreateModal" class="modal-backdrop" @click.self="showSupplierDebtCreateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Nouvelle dette</h2>
                            <p>Enregistrez le montant dû et son échéance.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSupplierDebtCreateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form class="payment-modal-form" @submit.prevent="saveSupplierDebt">
                        <label>Fournisseur existant
                            <select v-model="supplierDebtForm.supplier_id">
                                <option value="">Nouveau fournisseur</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }} - {{ supplier.phone || 'sans téléphone' }}</option>
                            </select>
                        </label>
                        <template v-if="!supplierDebtForm.supplier_id">
                            <label>Nom du fournisseur
                                <input v-model="supplierDebtForm.supplier_name" type="text" required placeholder="Nom complet">
                            </label>
                            <label>Téléphone
                                <span class="phone-field">
                                    <span>01</span>
                                    <input :value="phoneInputValue(supplierDebtForm.supplier_phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="supplierDebtForm.supplier_phone = phoneWithPrefix($event.target.value)">
                                </span>
                            </label>
                        </template>
                        <label>Montant dû
                            <input v-model.number="supplierDebtForm.amount_due" type="number" required min="1">
                        </label>
                        <label>Échéance
                            <input v-model="supplierDebtForm.due_date" type="date">
                        </label>
                        <label class="full">Notes
                            <textarea v-model="supplierDebtForm.notes" rows="3" placeholder="Optionnel"></textarea>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="showSupplierDebtCreateModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingSupplierDebt">
                                <i class="fa-solid fa-check"></i>{{ savingSupplierDebt ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                    <p v-if="supplierDebtMessage" class="message">{{ supplierDebtMessage }}</p>
                </section>
            </div>

            <div v-if="showSupplierCreateModal" class="modal-backdrop" @click.self="showSupplierCreateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Nouveau fournisseur</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showSupplierCreateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form class="payment-modal-form" @submit.prevent="saveSupplier">
                        <label>Nom
                            <input v-model="supplierForm.name" type="text" required placeholder="Nom du fournisseur">
                        </label>
                        <label>Téléphone
                            <span class="phone-field">
                                <span>01</span>
                                <input :value="phoneInputValue(supplierForm.phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="supplierForm.phone = phoneWithPrefix($event.target.value)">
                            </span>
                        </label>
                        <label class="full">Notes
                            <textarea v-model="supplierForm.notes" rows="3" placeholder="Optionnel"></textarea>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="showSupplierCreateModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingSupplier">
                                <i class="fa-solid fa-check"></i>{{ savingSupplier ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                    <p v-if="supplierMessage" class="message">{{ supplierMessage }}</p>
                </section>
            </div>

            <div v-if="showSupplierPhoneEditModal" class="modal-backdrop" @click.self="closeSupplierPhoneEdit">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Modifier téléphone</h2>
                            <p>{{ editingSupplier?.name || 'Fournisseur' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeSupplierPhoneEdit" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form class="payment-modal-form" @submit.prevent="updateSupplierPhone">
                        <label>Téléphone
                            <span class="phone-field">
                                <span>01</span>
                                <input :value="phoneInputValue(supplierPhoneForm.phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="supplierPhoneForm.phone = phoneWithPrefix($event.target.value)">
                            </span>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeSupplierPhoneEdit">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingSupplier">
                                <i class="fa-solid fa-check"></i>{{ savingSupplier ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                    <p v-if="supplierMessage" class="message">{{ supplierMessage }}</p>
                </section>
            </div>

            <div v-if="showSupplierDebtDateModal" class="modal-backdrop" @click.self="showSupplierDebtDateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Trier par date</h2>
                            <p>Choisissez une date d’échéance précise ou une période.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSupplierDebtDateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button :class="['btn', supplierDebtDateMode === 'exact' ? 'btn-primary' : 'btn-light']" type="button" @click="supplierDebtDateMode = 'exact'">
                            Date précise
                        </button>
                        <button :class="['btn', supplierDebtDateMode === 'range' ? 'btn-primary' : 'btn-light']" type="button" @click="supplierDebtDateMode = 'range'">
                            Entre deux dates
                        </button>
                    </div>
                    <div class="choice-form">
                        <label v-if="supplierDebtDateMode === 'exact'">Date précise
                            <input v-model="supplierDebtDateDraft.exactDate" type="date">
                        </label>
                        <template v-else>
                            <label>Du
                                <input v-model="supplierDebtDateDraft.startDate" type="date">
                            </label>
                            <label>Au
                                <input v-model="supplierDebtDateDraft.endDate" type="date">
                            </label>
                        </template>
                    </div>
                    <div class="choice-actions">
                        <button class="btn btn-light" type="button" @click="clearSupplierDebtDateFilter">
                            <i class="fa-solid fa-eraser"></i>Effacer
                        </button>
                        <button class="btn btn-primary" type="button" @click="applySupplierDebtDateFilter">
                            <i class="fa-solid fa-check"></i>Appliquer
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showSupplierDebtPrintModal" class="modal-backdrop" @click.self="showSupplierDebtPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des dettes affichées.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSupplierDebtPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseSupplierDebtPrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseSupplierDebtPrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showSupplierPrintModal" class="modal-backdrop" @click.self="showSupplierPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des fournisseurs affichés.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showSupplierPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseSupplierPrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseSupplierPrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <section v-if="activeSection === 'expenses'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Charges enregistrées</h2>
                        <p>Filtrez, triez et exportez les charges affichées, sauf achat de marchandises et salaires du personnel.</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openExpenseModal">
                            <i class="fa-solid fa-plus"></i>Ajouter charge
                        </button>
                        <button class="btn btn-light" type="button" @click="showExpensePrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="expenseFilters.search" type="search" placeholder="Nom, note ou catégorie">
                    </label>
                    <label>Type de charge
                        <div class="category-picker">
                            <button class="category-picker-toggle" type="button" @click="showExpenseFilterCategoryMenu = !showExpenseFilterCategoryMenu">
                                <span><i :class="expenseCategoryIconClass(expenseFilters.category)"></i>{{ expenseFilters.category || 'Toutes les catégories' }}</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div v-if="showExpenseFilterCategoryMenu" class="category-picker-menu">
                                <button type="button" @click="selectExpenseFilterCategory('')">
                                    <i class="fa-solid fa-list"></i>Toutes les catégories
                                </button>
                                <button v-for="category in expenseCategories" :key="category" type="button" @click="selectExpenseFilterCategory(category)">
                                    <i :class="expenseCategoryIconClass(category)"></i>{{ category }}
                                </button>
                            </div>
                        </div>
                    </label>
                    <div class="filter-control">
                        <span>Trier par date</span>
                        <button class="btn filter-date-btn" type="button" @click="openExpenseDateFilter">
                            <i class="fa-solid fa-calendar-days"></i>{{ expenseDateFilterLabel }}
                        </button>
                    </div>
                    <label>Trier par
                        <select v-model="expenseSort">
                            <option value="date_desc">Date récente</option>
                            <option value="date_asc">Date ancienne</option>
                            <option value="amount_desc">Montant décroissant</option>
                            <option value="amount_asc">Montant croissant</option>
                            <option value="category_asc">Catégorie A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ expensesCountLabel }}</span>
                    <strong>{{ formatMoney(filteredExpensesTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table id="expenses-screen-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Charge</th>
                                <th>Catégorie</th>
                                <th>Montant</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in filteredExpenses" :key="expense.id">
                                <td>{{ formatDate(expense.created_at) }}</td>
                                <td><strong>{{ expense.name }}</strong></td>
                                <td>{{ expense.category }}</td>
                                <td>{{ formatMoney(expense.amount) }}</td>
                                <td>{{ expense.notes || '-' }}</td>
                            </tr>
                            <tr v-if="!filteredExpenses.length">
                                <td colspan="5" class="empty">Aucune charge ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="expenseMessage" class="message">{{ expenseMessage }}</p>
            </section>

            <div v-if="showExpenseDateModal" class="modal-backdrop" @click.self="showExpenseDateModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Trier par date</h2>
                            <p>Choisissez une date précise ou une période.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showExpenseDateModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button :class="['btn', expenseDateMode === 'exact' ? 'btn-primary' : 'btn-light']" type="button" @click="expenseDateMode = 'exact'">
                            Date précise
                        </button>
                        <button :class="['btn', expenseDateMode === 'range' ? 'btn-primary' : 'btn-light']" type="button" @click="expenseDateMode = 'range'">
                            Entre deux dates
                        </button>
                    </div>
                    <div class="choice-form">
                        <label v-if="expenseDateMode === 'exact'">Date précise
                            <input v-model="expenseDateDraft.exactDate" type="date">
                        </label>
                        <template v-else>
                            <label>Du
                                <input v-model="expenseDateDraft.startDate" type="date">
                            </label>
                            <label>Au
                                <input v-model="expenseDateDraft.endDate" type="date">
                            </label>
                        </template>
                    </div>
                    <div class="choice-actions">
                        <button class="btn btn-light" type="button" @click="clearExpenseDateFilter">
                            <i class="fa-solid fa-eraser"></i>Effacer
                        </button>
                        <button class="btn btn-primary" type="button" @click="applyExpenseDateFilter">
                            <i class="fa-solid fa-check"></i>Appliquer
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showExpensePrintModal" class="modal-backdrop" @click.self="showExpensePrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Imprimer</h2>
                            <p>Choisissez le format d’impression des charges affichées.</p>
                        </div>
                        <button class="table-icon" type="button" @click="showExpensePrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="chooseExpensePrint('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-light" type="button" @click="chooseExpensePrint('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showExpenseModal" class="modal-backdrop" @click.self="showExpenseModal = false">
                <section class="form-modal">
                    <div class="section-title">
                        <div class="modal-title-block">
                            <h2>Nouvelle charge</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showExpenseModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form class="expense-form" @submit.prevent="saveExpense">
                        <label>Nom
                            <input v-model="expenseForm.name" type="text" required placeholder="Ex. Loyer boutique">
                        </label>
                        <label>Catégorie
                            <div class="category-picker">
                                <input v-model="expenseForm.category" type="hidden" required>
                                <button class="category-picker-toggle" type="button" @click="showExpenseFormCategoryMenu = !showExpenseFormCategoryMenu">
                                    <span><i :class="expenseCategoryIconClass(expenseForm.category)"></i>{{ expenseForm.category || 'Choisir une catégorie' }}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div v-if="showExpenseFormCategoryMenu" class="category-picker-menu">
                                    <button v-for="category in expenseCategories" :key="category" type="button" @click="selectExpenseFormCategory(category)">
                                        <i :class="expenseCategoryIconClass(category)"></i>{{ category }}
                                    </button>
                                </div>
                            </div>
                        </label>
                        <label>Montant
                            <input v-model.number="expenseForm.amount" type="number" required min="1">
                        </label>
                        <label>Date
                            <input v-model="expenseForm.spent_on" type="date" required>
                        </label>
                        <label class="full">Notes
                            <input v-model="expenseForm.notes" type="text" placeholder="Optionnel">
                        </label>
                        <button class="btn btn-primary" type="submit" :disabled="savingExpense">
                            <i class="fa-solid fa-plus"></i>{{ savingExpense ? 'Enregistrement...' : 'Ajouter la charge' }}
                        </button>
                    </form>
                    <p v-if="expenseMessage" class="message">{{ expenseMessage }}</p>
                </section>
            </div>

            <div v-if="activeSection === 'employees'" class="customer-view-switch">
                <button :class="['customer-switch-card', employeeView === 'staff' ? 'active' : '']" type="button" @click="employeeView = 'staff'">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Personnel</span>
                    <strong>{{ filteredEmployees.length }}</strong>
                </button>
                <button :class="['customer-switch-card', employeeView === 'payroll' ? 'active' : '']" type="button" @click="employeeView = 'payroll'">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Paie</span>
                    <strong>{{ formatMoney(filteredPayrollsTotal) }}</strong>
                </button>
            </div>

            <section v-if="activeSection === 'employees' && employeeView === 'staff'" class="card employees-section">
                <div class="section-title">
                    <div>
                        <h2>Personnel</h2>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" type="button" @click="openEmployeeModal()">
                            <i class="fa-solid fa-user-plus"></i>Nouvel employé
                        </button>
                        <button class="btn btn-light" type="button" @click="showEmployeePrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="employeeFilters.search" type="search" placeholder="Nom, poste ou téléphone">
                    </label>
                    <label>Type
                        <select v-model="employeeFilters.type">
                            <option value="">Tous les types</option>
                            <option v-for="type in employeeTypeOptions" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="employeeSort">
                            <option value="name_asc">Nom A-Z</option>
                            <option value="salary_desc">Salaire décroissant</option>
                            <option value="salary_asc">Salaire croissant</option>
                            <option value="hired_desc">Embauche récente</option>
                            <option value="type_asc">Type A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ employeesCountLabel }}</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Type</th>
                                <th>Salaire</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="employee in filteredEmployees" :key="employee.id">
                                <td><strong>{{ employee.name }}</strong></td>
                                <td><span class="status ok">{{ employeeTypeLabel(employee.type) }}</span></td>
                                <td>{{ formatMoney(employee.salary) }}</td>
                                <td>
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openEmployeeDetails(employee)" title="Voir la fiche" aria-label="Voir la fiche">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="table-icon" type="button" @click="openEmployeeModal(employee)" title="Modifier" aria-label="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="table-icon danger-icon" type="button" @click="openEmployeeBanModal(employee)" title="Bannir" aria-label="Bannir">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredEmployees.length">
                                <td colspan="4" class="empty">Aucun employé ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredEmployees.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="employeeMessage" class="message">{{ employeeMessage }}</p>
            </section>

            <section v-if="activeSection === 'employees' && employeeView === 'payroll'" class="card payroll-section">
                <div class="section-title">
                    <div>
                        <h2>Paie</h2>
                    </div>
                    <div class="section-actions">
                        <button v-if="payrollSubView === 'payrolls'" class="btn btn-primary" type="button" @click="openPayrollModal">
                            <i class="fa-solid fa-file-invoice-dollar"></i>Nouveau salaire
                        </button>
                        <button v-if="payrollSubView === 'advances'" class="btn btn-advance" type="button" @click="openAdvanceModal()">
                            <i class="fa-solid fa-hand-holding-dollar"></i>Nouvelle avance
                        </button>
                        <button class="btn btn-light" type="button" @click="showPayrollPrintModal = true">
                            <i class="fa-solid fa-print"></i>Imprimer
                        </button>
                    </div>
                </div>

                <div class="payroll-subsection-switch">
                    <button :class="['customer-switch-card', payrollSubView === 'payrolls' ? 'active' : '']" type="button" @click="payrollSubView = 'payrolls'">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Paies</span>
                        <strong>{{ payrollsCountLabel }}</strong>
                    </button>
                    <button :class="['customer-switch-card', payrollSubView === 'advances' ? 'active' : '']" type="button" @click="payrollSubView = 'advances'">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        <span>Avances</span>
                        <strong>{{ formatMoney(filteredAdvancesTotal) }}</strong>
                    </button>
                </div>

                <template v-if="payrollSubView === 'payrolls'">
                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="payrollFilters.search" type="search" placeholder="Employé ou période">
                    </label>
                    <label>Statut
                        <select v-model="payrollFilters.status">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="paid">Payée</option>
                        </select>
                    </label>
                    <label>Trier par
                        <select v-model="payrollSort">
                            <option value="period_desc">Période récente</option>
                            <option value="period_asc">Période ancienne</option>
                            <option value="net_desc">Net décroissant</option>
                            <option value="employee_asc">Employé A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ payrollsCountLabel }}</span>
                    <strong>{{ formatMoney(filteredPayrollsTotal) }}</strong>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Période</th>
                                <th>Employé</th>
                                <th>Salaire brut</th>
                                <th>Avances</th>
                                <th>Net à payer</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payroll in filteredPayrolls" :key="payroll.id">
                                <td>{{ payroll.period }}</td>
                                <td>{{ payroll.employee?.name }}</td>
                                <td>{{ formatMoney(payroll.gross_salary) }}</td>
                                <td>{{ formatMoney(payroll.salary_advance) }}</td>
                                <td><strong>{{ formatMoney(payroll.net_salary) }}</strong></td>
                                <td><span class="status ok">{{ payroll.status }}</span></td>
                            </tr>
                            <tr v-if="!filteredPayrolls.length">
                                <td colspan="6" class="empty">Aucune paie ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredPayrolls.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="payrollMessage" class="message">{{ payrollMessage }}</p>
                </template>

                <template v-else>
                <div class="filters-grid">
                    <label>Rechercher
                        <input v-model="advanceFilters.search" type="search" placeholder="Employé, montant ou note">
                    </label>
                    <label>Trier par
                        <select v-model="advanceSort">
                            <option value="date_desc">Date récente</option>
                            <option value="date_asc">Date ancienne</option>
                            <option value="amount_desc">Montant décroissant</option>
                            <option value="employee_asc">Employé A-Z</option>
                        </select>
                    </label>
                </div>

                <div class="filtered-summary">
                    <span>{{ advancesCountLabel }}</span>
                    <strong>{{ formatMoney(filteredAdvancesTotal) }}</strong>
                </div>

                <div class="table-wrap advances-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-calendar-day"></i>Date</th>
                                <th><i class="fa-solid fa-user-tie"></i>Employé</th>
                                <th><i class="fa-solid fa-money-bill-wave"></i>Montant</th>
                                <th><i class="fa-solid fa-note-sticky"></i>Note</th>
                                <th><i class="fa-solid fa-screwdriver-wrench"></i>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="advance in filteredAdvances" :key="advance.id">
                                <td data-label="Date">
                                    <span class="advance-date-pill"><i class="fa-solid fa-calendar-check"></i>{{ formatDateOnly(advance.advanced_on) }}</span>
                                </td>
                                <td data-label="Employé">
                                    <span class="advance-employee-cell">
                                        <span class="advance-avatar"><i class="fa-solid fa-user"></i></span>
                                        <strong>{{ advance.employee?.name || '-' }}</strong>
                                    </span>
                                </td>
                                <td data-label="Montant">
                                    <span class="advance-amount-pill"><i class="fa-solid fa-coins"></i>{{ formatMoney(advance.amount) }}</span>
                                </td>
                                <td data-label="Note">
                                    <span :class="['advance-note-cell', advance.notes ? '' : 'muted']">
                                        <i class="fa-solid fa-comment-dots"></i>{{ advance.notes || 'Aucune note' }}
                                    </span>
                                </td>
                                <td data-label="Options">
                                    <div class="row-actions">
                                        <button class="table-icon" type="button" @click="openAdvanceEdit(advance)" title="Modifier" aria-label="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="table-icon danger-icon" type="button" @click="deleteAdvance(advance)" title="Supprimer" aria-label="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredAdvances.length">
                                <td colspan="5" class="empty">Aucune avance ne correspond aux filtres.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-pagination">
                    <span>{{ paginationLabel(filteredAdvances.length) }}</span>
                    <div>
                        <button type="button" disabled>Précédent</button>
                        <button type="button" disabled>Suivant</button>
                    </div>
                </div>
                <p v-if="payrollMessage" class="message">{{ payrollMessage }}</p>
                </template>
            </section>

            <div v-if="showEmployeeModal" class="modal-backdrop" @click.self="closeEmployeeModal">
                <section class="form-modal">
                    <div class="section-title">
                        <div>
                            <h2 class="employee-modal-title">
                                <i class="fa-solid fa-user-tie"></i>{{ editingEmployee ? 'Modifier employé' : 'Nouvel employé' }}
                            </h2>
                            <p class="employee-modal-subtitle">Renseignez les détails de l'employé. Seuls les vendeurs ont besoin d'un mot de passe pour se connecter et effectuer des ventes.</p>
                        </div>
                        <button class="table-icon modal-close" type="button" @click="closeEmployeeModal" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="employee-form" @submit.prevent="saveEmployee">
                        <div class="employee-info-section full">
                            <h3><i class="fa-solid fa-id-card"></i>Informations générales</h3>
                            <div class="employee-login-grid">
                                <label><span class="field-label"><i class="fa-solid fa-user"></i>Noms et prénoms</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-user"></i>
                                        <input v-model="employeeForm.name" type="text" required placeholder="Nom complet">
                                    </span>
                                </label>
                                <label><span class="field-label"><i class="fa-solid fa-briefcase"></i>Type de compte</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-briefcase"></i>
                                        <select v-model="employeeForm.type" required>
                                            <option v-for="type in employeeTypeOptions" :key="type.value" :value="type.value">{{ type.label }}</option>
                                            <option value="__other">Autre</option>
                                        </select>
                                    </span>
                                </label>
                                <label v-if="employeeForm.type === '__other'"><span class="field-label"><i class="fa-solid fa-pen-to-square"></i>Type de compte</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <input v-model="employeeForm.custom_type" type="text" required placeholder="Ex. Magasinier">
                                    </span>
                                </label>
                                <label v-if="employeeFormIsSeller"><span class="field-label"><i class="fa-solid fa-phone"></i>Téléphone</span>
                                    <span class="phone-field">
                                        <span>01</span>
                                        <input :value="phoneInputValue(employeeForm.phone)" type="tel" required inputmode="numeric" maxlength="8" pattern="\d{8}" placeholder="96228860" @input="employeeForm.phone = phoneWithPrefix($event.target.value)">
                                    </span>
                                </label>
                                <label><span class="field-label"><i class="fa-solid fa-money-bill-wave"></i>Salaire mensuel</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                        <input v-model.number="employeeForm.salary" type="number" required min="0">
                                    </span>
                                </label>
                                <label><span class="field-label"><i class="fa-solid fa-calendar-day"></i>Date d'embauche</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        <input v-model="employeeForm.hired_at" type="date">
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div v-if="employeeFormIsSeller" class="employee-info-section full">
                            <h3><i class="fa-solid fa-shield-halved"></i>Informations de connexion</h3>
                            <div class="employee-login-grid">
                                <label><span class="field-label"><i class="fa-solid fa-circle-user"></i>Nom d'utilisateur</span>
                                    <span class="input-icon-field">
                                        <i class="fa-solid fa-circle-user"></i>
                                        <input v-model="employeeForm.username" type="text" required autocomplete="username" placeholder="ex. vendeur1">
                                    </span>
                                </label>
                                <label v-if="!editingEmployee"><span class="field-label"><i class="fa-solid fa-lock"></i>Mot de passe</span>
                                    <span class="password-field">
                                        <input v-model="employeeForm.password" :type="employeePasswordVisible ? 'text' : 'password'" required minlength="8" placeholder="8 caractères minimum" autocomplete="new-password">
                                        <button type="button" @click="employeePasswordVisible = !employeePasswordVisible" :title="employeePasswordVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'" :aria-label="employeePasswordVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                            <i :class="employeePasswordVisible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                                        </button>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="choice-actions full">
                            <button class="btn btn-light" type="button" @click="closeEmployeeModal">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingEmployee">
                                <i class="fa-solid fa-check"></i>{{ savingEmployee ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showEmployeeDetailsModal" class="modal-backdrop" @click.self="closeEmployeeDetails">
                <section class="choice-modal customer-detail-modal">
                    <div class="section-title">
                        <div>
                            <h2>Fiche employé</h2>
                            <p>{{ selectedEmployeeDetails?.name || 'Employé' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeEmployeeDetails" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="receivable-detail-grid">
                        <div><span>Nom</span><strong>{{ selectedEmployeeDetails?.name || '-' }}</strong></div>
                        <div><span>Téléphone</span><strong>{{ selectedEmployeeDetails?.user?.phone || '-' }}</strong></div>
                        <div><span>Nom d'utilisateur</span><strong>{{ selectedEmployeeDetails?.user?.username || '-' }}</strong></div>
                        <div><span>Poste</span><strong>{{ selectedEmployeeDetails?.position || '-' }}</strong></div>
                        <div><span>Type de compte</span><strong>{{ employeeTypeLabel(selectedEmployeeDetails?.type) }}</strong></div>
                        <div><span>Salaire mensuel</span><strong>{{ formatMoney(selectedEmployeeDetails?.salary || 0) }}</strong></div>
                        <div><span>Paiement salaire habituel</span><strong>{{ formatDayMonth(selectedEmployeeDetails?.salary_payment_date) }}</strong></div>
                        <div><span>Date d'embauche</span><strong>{{ formatDateOnly(selectedEmployeeDetails?.hired_at) }}</strong></div>
                        <div><span>Statut</span><strong>{{ selectedEmployeeDetails?.is_active ? 'Actif' : 'Inactif' }}</strong></div>
                    </div>
                    <div v-if="selectedEmployeeDetails?.ban_reason" class="receivable-notes">
                        <strong>Motif du bannissement</strong>
                        <p>{{ selectedEmployeeDetails.ban_reason }}</p>
                    </div>
                    <div class="choice-actions">
                        <button class="btn btn-light" type="button" @click="closeEmployeeDetails">
                            <i class="fa-solid fa-xmark"></i>Fermer
                        </button>
                        <button class="btn btn-primary" type="button" @click="openEmployeeModal(selectedEmployeeDetails); closeEmployeeDetails()">
                            <i class="fa-solid fa-pen-to-square"></i>Modifier
                        </button>
                    </div>
                </section>
            </div>

            <div v-if="showAdvanceModal" class="modal-backdrop" @click.self="closeAdvanceModal">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>{{ editingAdvance ? 'Modifier avance' : 'Nouvelle avance' }}</h2>
                            <p>{{ selectedAdvanceEmployee?.name || 'Avance sur salaire' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeAdvanceModal" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="saveAdvance">
                        <label v-if="!selectedAdvanceEmployee">Employé
                            <select v-model="advanceForm.employee_id" required>
                                <option value="">Choisir</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                            </select>
                        </label>
                        <label>Montant avance
                            <input v-model.number="advanceForm.amount" type="number" required min="1">
                        </label>
                        <label>Date
                            <input v-model="advanceForm.advanced_on" type="date" required>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeAdvanceModal">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingAdvance">
                                <i class="fa-solid fa-check"></i>{{ savingAdvance ? 'Enregistrement...' : (editingAdvance ? 'Modifier' : 'Enregistrer') }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showEmployeeBanModal" class="modal-backdrop" @click.self="closeEmployeeBanModal">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Bannir employé</h2>
                            <p>{{ selectedBanEmployee?.name || 'Employé' }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="closeEmployeeBanModal" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="banEmployee">
                        <label>Motif
                            <textarea v-model="employeeBanForm.reason" required rows="4" placeholder="Indiquez le motif du bannissement"></textarea>
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="closeEmployeeBanModal">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit" :disabled="savingEmployeeBan">
                                <i class="fa-solid fa-user-slash"></i>{{ savingEmployeeBan ? 'Bannissement...' : 'Bannir' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showPayrollModal" class="modal-backdrop" @click.self="showPayrollModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Nouveau salaire</h2>
                        </div>
                        <button class="table-icon" type="button" @click="showPayrollModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <form class="payment-modal-form" @submit.prevent="paySalary">
                        <label>Employé
                            <select v-model="payrollForm.employee_id" required @change="payrollForm.paid_amount = ''">
                                <option value="">Choisir</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                            </select>
                        </label>
                        <label>Période
                            <input v-model="payrollForm.period" type="month" required @change="payrollForm.paid_amount = ''">
                        </label>
                        <div v-if="selectedPayrollEmployee" class="receivable-detail-grid full">
                            <div><span>Salaire mensuel</span><strong>{{ formatMoney(selectedPayrollEmployee.salary || 0) }}</strong></div>
                            <div><span>Avances en cours</span><strong>{{ formatMoney(selectedPayrollAdvanceTotal) }}</strong></div>
                            <div><span>Salaire net estimé</span><strong>{{ formatMoney(selectedPayrollNetEstimate) }}</strong></div>
                            <div><span>Avances trouvées</span><strong>{{ selectedPayrollAdvances.length }}</strong></div>
                        </div>
                        <label v-if="selectedPayrollEmployee">Salaire payé
                            <input v-model.number="payrollForm.paid_amount" type="number" min="0" :placeholder="String(selectedPayrollNetEstimate)">
                        </label>
                        <div class="choice-actions">
                            <button class="btn btn-light" type="button" @click="showPayrollModal = false">
                                <i class="fa-solid fa-xmark"></i>Annuler
                            </button>
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Payer salaire
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="showEmployeePrintModal" class="modal-backdrop" @click.self="showEmployeePrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div><h2>Imprimer</h2></div>
                        <button class="table-icon" type="button" @click="showEmployeePrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="printFilteredEmployees"><i class="fa-solid fa-file-pdf"></i>PDF</button>
                        <button class="btn btn-primary" type="button" @click="exportFilteredEmployeesExcel"><i class="fa-solid fa-file-excel"></i>Excel</button>
                    </div>
                </section>
            </div>

            <div v-if="showPayrollPrintModal" class="modal-backdrop" @click.self="showPayrollPrintModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div><h2>Imprimer</h2></div>
                        <button class="table-icon" type="button" @click="showPayrollPrintModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="printFilteredPayrolls"><i class="fa-solid fa-file-pdf"></i>PDF</button>
                        <button class="btn btn-primary" type="button" @click="exportFilteredPayrollsExcel"><i class="fa-solid fa-file-excel"></i>Excel</button>
                    </div>
                </section>
            </div>

            <section v-if="activeSection === 'reports'" class="reports-page">
                <div class="section-title">
                    <div>
                        <h2>Rapports</h2>
                        <p>Générez, imprimez et exportez les données utiles pour suivre la boutique.</p>
                    </div>
                </div>

                <div class="report-summary-grid">
                    <article v-for="item in reportSummaryCards" :key="item.label" class="report-summary-card">
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                        <strong>{{ item.value }}</strong>
                    </article>
                </div>

                <div class="reports-grid">
                    <article class="report-panel">
                        <div>
                            <h3>Rapport financier</h3>
                            <p>Choisissez la période puis ouvrez le rapport imprimable.</p>
                        </div>
                        <div class="report-form">
                            <label>Période
                                <select v-model="reportPeriod">
                                    <option v-for="period in reportPeriods" :key="period.value" :value="period.value">{{ period.label }}</option>
                                </select>
                            </label>
                            <label v-if="reportPeriod === 'custom'">Début
                                <input v-model="reportStartDate" type="date" required>
                            </label>
                            <label v-if="reportPeriod === 'custom'">Fin
                                <input v-model="reportEndDate" type="date" required>
                            </label>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-primary" type="button" :disabled="!canOpenSelectedReport" @click="openReportFormatModal(reportPeriod)">
                                <i class="fa-solid fa-print"></i>Ouvrir le rapport
                            </button>
                            <button class="btn btn-light" type="button" @click="openTaxFormatModal">
                                <i class="fa-solid fa-file-invoice-dollar"></i>Bilan comptable
                            </button>
                        </div>
                    </article>

                    <article class="report-panel">
                        <div>
                            <h3>Rapports rapides</h3>
                            <p>Accès direct aux périodes les plus utilisées.</p>
                        </div>
                        <div class="report-actions compact">
                            <button v-for="period in quickReportPeriods" :key="period.value" class="btn btn-light" type="button" @click="openReportFormatModal(period.value)">
                                <i :class="period.icon"></i>{{ period.label }}
                            </button>
                        </div>
                    </article>

                    <article class="report-panel full">
                        <div>
                            <h3>Rapports précis</h3>
                            <p>Cliquez pour télecharger des rapports précis</p>
                        </div>
                        <div class="report-export-grid">
                            <button v-for="item in reportExports" :key="item.type" class="report-export" type="button" @click="openExportFormatModal(item)">
                                <i :class="item.icon"></i>
                                <span>{{ item.label }}</span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <div v-if="showReportFormatModal" class="modal-backdrop" @click.self="showReportFormatModal = false">
                <section class="choice-modal">
                    <div class="section-title">
                        <div>
                            <h2>Exporter le rapport</h2>
                            <p>{{ selectedDownloadTitle }}</p>
                        </div>
                        <button class="table-icon" type="button" @click="showReportFormatModal = false" title="Fermer" aria-label="Fermer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="segmented-actions">
                        <button class="btn btn-light" type="button" @click="exportSelectedDownload('pdf')">
                            <i class="fa-solid fa-file-pdf"></i>PDF
                        </button>
                        <button class="btn btn-primary" type="button" @click="exportSelectedDownload('excel')">
                            <i class="fa-solid fa-file-excel"></i>Excel
                        </button>
                    </div>
                </section>
            </div>

            <section v-if="activeSection === 'taxes'" class="content-grid">
                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>Impôts & comptabilité</h2>
                            <p>Consultez le bilan auto-généré et les documents à transmettre au comptable.</p>
                        </div>
                    </div>
                    <div class="tax-grid" v-if="taxStatement">
                        <div><span>Recettes</span><strong>{{ formatMoney(taxStatement.sales_total) }}</strong></div>
                        <div><span>Dépenses</span><strong>{{ formatMoney(taxStatement.expenses_total) }}</strong></div>
                        <div><span>Paies</span><strong>{{ formatMoney(taxStatement.payrolls_total) }}</strong></div>
                        <div><span>Résultat estimé</span><strong>{{ formatMoney(taxStatement.net_result) }}</strong></div>
                    </div>
                    <a class="btn btn-primary" :href="`/businesses/${businessId}/taxes/statement`" target="_blank"><i class="fa-solid fa-print"></i>Imprimer le bilan</a>
                </article>

                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>FAQ fiscale</h2>
                            <p>Les réponses servent de repères. Le comptable reste la référence pour valider les obligations.</p>
                        </div>
                    </div>
                    <div class="tax-faq">
                        <details v-for="item in taxFaq" :key="item.question">
                            <summary>{{ item.question }}</summary>
                            <p>{{ item.answer }}</p>
                        </details>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    businessId: {
        type: [Number, String],
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    initialSection: {
        type: String,
        default: 'tableau-de-bord',
    },
});

const business = ref(null);
const currentUser = ref({});
const subscription = ref(null);
const products = ref([]);
const sales = ref([]);
const customers = ref([]);
const receivables = ref([]);
const suppliers = ref([]);
const supplierDebts = ref([]);
const expenses = ref([]);
const employees = ref([]);
const employeeTypes = ref([]);
const payrolls = ref([]);
const taxFaq = ref([]);
const taxStatement = ref(null);
const notifications = ref([]);
const subscriptionPlans = ref({});
const summary = reactive({
    products_count: 0,
    low_stock_count: 0,
    stock_value: 0,
    sales_count: 0,
    sales_total: 0,
    today_sales_total: 0,
    expenses_total: 0,
    monthly_expenses_total: 0,
    sellers_count: 0,
});
const today = new Date().toISOString().slice(0, 10);
const reportPeriod = ref('monthly');
const reportStartDate = ref(today);
const reportEndDate = ref(today);
const showReportFormatModal = ref(false);
const selectedReportPeriod = ref('monthly');
const selectedDownload = reactive({
    title: '',
    pdfUrl: '',
    excelUrl: '',
});
const saving = ref(false);
const savingSale = ref(false);
const message = ref('');
const saleMessage = ref('');
const receivableMessage = ref('');
const supplierDebtMessage = ref('');
const supplierMessage = ref('');
const profileMessage = ref('');
const savingSupplierDebt = ref(false);
const savingSupplier = ref(false);
const expenseMessage = ref('');
const savingExpense = ref(false);
const showProductModal = ref(false);
const showProductPrintModal = ref(false);
const showExpenseModal = ref(false);
const showExpenseDateModal = ref(false);
const showExpensePrintModal = ref(false);
const showSalesDateModal = ref(false);
const showSalesPrintModal = ref(false);
const showExpenseFilterCategoryMenu = ref(false);
const showExpenseFormCategoryMenu = ref(false);
const expenseDateMode = ref('exact');
const showReceivableDateModal = ref(false);
const showReceivablePrintModal = ref(false);
const showReceivablePaymentModal = ref(false);
const showReceivableDetailsModal = ref(false);
const showReceivableCreateModal = ref(false);
const showReceivableAmountEditModal = ref(false);
const showReceivablePaymentEditModal = ref(false);
const showReceivableDetailsPrintModal = ref(false);
const showSupplierDebtDateModal = ref(false);
const showSupplierDebtPrintModal = ref(false);
const showSupplierPrintModal = ref(false);
const showSupplierDebtCreateModal = ref(false);
const showSupplierCreateModal = ref(false);
const showSupplierPhoneEditModal = ref(false);
const showSupplierDebtPaymentModal = ref(false);
const showSupplierDebtDetailsModal = ref(false);
const showSupplierDebtAmountEditModal = ref(false);
const showSupplierDebtPaymentEditModal = ref(false);
const showSupplierDetailsModal = ref(false);
const showCustomerCreateModal = ref(false);
const showCustomerPrintModal = ref(false);
const showCustomerDetailsModal = ref(false);
const showEmployeeModal = ref(false);
const showEmployeeDetailsModal = ref(false);
const showAdvanceModal = ref(false);
const showEmployeeBanModal = ref(false);
const showPayrollModal = ref(false);
const showEmployeePrintModal = ref(false);
const showPayrollPrintModal = ref(false);
const showProfileCurrentPassword = ref(false);
const showProfilePassword = ref(false);
const showProfilePasswordConfirmation = ref(false);
const savingProfilePassword = ref(false);
const receivableDateMode = ref('exact');
const selectedReceivable = ref(null);
const selectedReceivableDetails = ref(null);
const editingReceivable = ref(null);
const editingReceivablePayment = ref(null);
const selectedCustomerDetails = ref(null);
const selectedSupplierDebt = ref(null);
const selectedSupplierDebtDetails = ref(null);
const editingSupplierDebt = ref(null);
const editingSupplierDebtPayment = ref(null);
const selectedSupplierDetails = ref(null);
const editingSupplier = ref(null);
const editingEmployee = ref(null);
const editingAdvance = ref(null);
const selectedAdvanceEmployee = ref(null);
const selectedBanEmployee = ref(null);
const selectedEmployeeDetails = ref(null);
const customerView = ref('receivables');
const supplierView = ref('debts');
const salesView = ref('invoices');
const employeeView = ref('staff');
const payrollSubView = ref('payrolls');
const savingReceivable = ref(false);
const savingCustomer = ref(false);
const employeeMessage = ref('');
const payrollMessage = ref('');
const savingEmployee = ref(false);
const savingAdvance = ref(false);
const savingEmployeeBan = ref(false);
const savingSubscription = ref(false);
const savingSettings = ref(false);
const subscriptionMessage = ref('');
const settingsMessage = ref('');
const dashboardLoaded = ref(false);
const toasts = ref([]);
const settingsLogoFile = ref(null);
const settingsLogoPreview = ref('');
let toastId = 0;

const routeSectionMap = {
    'tableau-de-bord': 'dashboard',
    charges: 'expenses',
    clients: 'customers',
    fournisseurs: 'suppliers',
    impots: 'taxes',
    notifications: 'notifications',
    parametres: 'settings',
    personnel: 'employees',
    rapports: 'reports',
    stocks: 'stocks',
    ventes: 'sales',
    caisse: 'seller-cashier',
    proforma: 'seller-proforma',
    'mes-ventes': 'seller-sales',
    produits: 'seller-products',
    profil: 'seller-profile',
};
const activeSection = ref(routeSectionMap[props.initialSection] || 'dashboard');
const sellerSections = ['seller-cashier', 'seller-proforma', 'seller-sales', 'seller-products', 'seller-profile'];
const sidebarOpen = ref(false);
const pageMeta = {
    dashboard: ['Tableau de bord', 'Vos statistiques essentielles.'],
    notifications: ['Notifications', 'Alertes, validations et messages importants.'],
    stocks: ['Stocks', "Produits, inventaire et seuils d'alerte."],
    sales: ['Factures & ventes', 'Suivi des ventes, factures et factures pro forma'],
    customers: ['Clients & créances', 'Crédits clients, échéances et remboursements.'],
    suppliers: ['Fournisseurs & dettes', 'Dettes fournisseurs, échéances et paiements.'],
    expenses: ['Charges', 'Dépenses classées par catégories OHADA.'],
    employees: ['Personnel & paie', 'Employés, avances et paies.'],
    reports: ['Rapports', 'Exports et rapports PDF ou Excel.'],
    taxes: ['Impôts & comptabilité', 'Bilan estimé et repères comptables.'],
    settings: ['Paramètres boutique', 'Informations affichées sur vos documents.'],
    'seller-cashier': ['Caisse', 'Nouvelle vente et facture rapide.'],
    'seller-proforma': ['Proforma', 'Préparation des devis avant validation.'],
    'seller-sales': ['Mes ventes', 'Historique de vos ventes et factures.'],
    'seller-products': ['Produits', 'Catalogue et stocks disponibles.'],
    'seller-profile': ['Profil', 'Paramètres de votre compte vendeur.'],
};
const pageTitle = computed(() => pageMeta[activeSection.value]?.[0] || 'Tableau de bord');
const pageSubtitle = computed(() => pageMeta[activeSection.value]?.[1] || 'Vos statistiques essentielles.');
const currentUserCanSell = computed(() => currentUser.value?.role === 'Vendeur');
const sellerCheckoutActive = computed(() => currentUserCanSell.value && ['seller-cashier', 'seller-proforma', 'sales'].includes(activeSection.value));
const salesHistoryActive = computed(() => activeSection.value === 'sales' || activeSection.value === 'seller-sales');
const sellerCheckoutSubmitLabel = computed(() => activeSection.value === 'seller-proforma' ? 'Enregistrer la proforma' : 'Valider la facture');
const sellerSalesTitle = computed(() => {
    if (currentUserCanSell.value) {
        return 'Mes ventes';
    }

    return salesView.value === 'proformas' ? 'Factures Pro forma' : 'Factures & ventes';
});
const salesEmptyColspan = computed(() => {
    if (currentUserCanSell.value) {
        return salesView.value === 'proformas' ? 5 : 7;
    }

    return salesView.value === 'proformas' ? 6 : 8;
});
const reportPeriods = [
    { value: 'daily', label: 'Journalier' },
    { value: 'weekly', label: 'Hebdomadaire' },
    { value: 'monthly', label: 'Mensuel' },
    { value: 'quarterly', label: 'Trimestriel' },
    { value: 'yearly', label: 'Annuel' },
    { value: 'custom', label: 'Période personnalisée' },
];
const quickReportPeriods = [
    { value: 'daily', label: 'Jour', icon: 'fa-solid fa-calendar-day' },
    { value: 'weekly', label: 'Semaine', icon: 'fa-solid fa-calendar-week' },
    { value: 'monthly', label: 'Mois', icon: 'fa-solid fa-calendar-days' },
    { value: 'quarterly', label: 'Trimestre', icon: 'fa-solid fa-chart-pie' },
    { value: 'yearly', label: 'Année', icon: 'fa-solid fa-calendar' },
];
const reportExports = [
    { type: 'products', label: 'Produits', icon: 'fa-solid fa-boxes-stacked' },
    { type: 'sales', label: 'Ventes', icon: 'fa-solid fa-receipt' },
    { type: 'expenses', label: 'Charges', icon: 'fa-solid fa-money-bill-wave' },
    { type: 'customers', label: 'Clients', icon: 'fa-solid fa-users' },
    { type: 'receivables', label: 'Créances', icon: 'fa-solid fa-hand-holding-dollar' },
    { type: 'supplier-debts', label: 'Dettes fournisseurs', icon: 'fa-solid fa-truck-field' },
    { type: 'employees', label: 'Employés', icon: 'fa-solid fa-user-tie' },
    { type: 'payrolls', label: 'Paies', icon: 'fa-solid fa-file-invoice-dollar' },
];
const canOpenSelectedReport = computed(() => reportPeriod.value !== 'custom' || (reportStartDate.value && reportEndDate.value && reportStartDate.value <= reportEndDate.value));
const selectedReportUrl = computed(() => buildReportUrl(reportPeriod.value));
const selectedDownloadTitle = computed(() => selectedDownload.title || 'Rapport');
const reportSummaryCards = computed(() => [
    { label: "Ventes aujourd'hui", value: formatMoney(summary.today_sales_total), icon: 'fa-solid fa-cash-register' },
    { label: 'Ventes totales', value: formatMoney(summary.sales_total), icon: 'fa-solid fa-chart-line' },
    { label: 'Charges du mois', value: formatMoney(summary.monthly_expenses_total), icon: 'fa-solid fa-money-bill-wave' },
    { label: 'Valeur du stock', value: formatMoney(summary.stock_value), icon: 'fa-solid fa-boxes-stacked' },
    { label: 'Créances ouvertes', value: formatMoney(receivables.value.reduce((sum, item) => sum + Number(item.remaining || 0), 0)), icon: 'fa-solid fa-file-invoice' },
    { label: 'Dettes fournisseurs', value: formatMoney(supplierDebts.value.reduce((sum, item) => sum + Number(item.remaining || 0), 0)), icon: 'fa-solid fa-truck-field' },
]);

function sectionUrl(section) {
    return `/dashboard/${props.businessId}/${section}`;
}

function buildReportUrl(period) {
    const base = `/businesses/${props.businessId}/reports/${period}`;
    if (period !== 'custom') {
        return base;
    }

    const params = new URLSearchParams({
        start: reportStartDate.value || today,
        end: reportEndDate.value || today,
    });

    return `${base}?${params.toString()}`;
}

function buildReportExportUrl(period) {
    const base = `/businesses/${props.businessId}/reports/${period}/export`;
    if (period !== 'custom') {
        return base;
    }

    const params = new URLSearchParams({
        start: reportStartDate.value || today,
        end: reportEndDate.value || today,
    });

    return `${base}?${params.toString()}`;
}

function openReportFormatModal(period) {
    selectedReportPeriod.value = period;
    const report = reportPeriods.find((item) => item.value === period);
    openDownloadFormatModal(
        report?.label || 'Rapport',
        buildReportUrl(period),
        buildReportExportUrl(period)
    );
}

function openTaxFormatModal() {
    openDownloadFormatModal(
        'Bilan comptable',
        `/businesses/${props.businessId}/taxes/statement`,
        `/businesses/${props.businessId}/taxes/statement/export`
    );
}

function openExportFormatModal(item) {
    openDownloadFormatModal(
        item.label,
        `/businesses/${props.businessId}/exports/${item.type}/pdf`,
        `/businesses/${props.businessId}/exports/${item.type}`
    );
}

function openDownloadFormatModal(title, pdfUrl, excelUrl) {
    selectedDownload.title = title;
    selectedDownload.pdfUrl = pdfUrl;
    selectedDownload.excelUrl = excelUrl;
    showReportFormatModal.value = true;
}

function exportSelectedDownload(format) {
    const url = format === 'excel' ? selectedDownload.excelUrl : selectedDownload.pdfUrl;

    showReportFormatModal.value = false;
    window.open(url, '_blank');
}

function notify(type, message) {
    const id = ++toastId;
    toasts.value.push({ id, type, message });
    setTimeout(() => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }, 1800);
}

function notifySuccess(message) {
    notify('success', message);
}

function notifyError(message) {
    notify('error', message);
}

function paginationLabel(count) {
    return `${count} élément${count >= 2 ? 's' : ''} sur 20`;
}

const form = reactive({
    name: '',
    category_name: '',
    unit: 'unité',
    purchase_price: '',
    sale_price: '',
    stock_quantity: '',
    alert_threshold: '',
});
const productFilters = reactive({
    search: '',
    category: '',
    status: '',
});
const productSort = ref('name_asc');
const productPriceForms = reactive({});

const saleLine = reactive({
    product_id: '',
    quantity: 1,
    discount: '',
});

const saleForm = reactive({
    customer_name: '',
    customer_phone: '',
    payment_method: 'cash',
    type: 'invoice',
    credit_due_date: '',
});

const cart = ref([]);

const salesFilters = reactive({
    search: '',
    payment: '',
    exactDate: '',
    startDate: '',
    endDate: '',
});
const salesDateDraft = reactive({
    exactDate: '',
    startDate: '',
    endDate: '',
});
const salesDateMode = ref('exact');
const salesSort = ref('date_desc');

const customerForm = reactive({
    name: '',
    phone: '',
});

const receivableForm = reactive({
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    amount_due: '',
    due_date: '',
    notes: '',
});

const receivableAmountForm = reactive({
    amount_due: '',
});

const receivablePaymentEditForm = reactive({
    amount: '',
});

const supplierDebtAmountForm = reactive({
    amount_due: '',
});

const supplierDebtPaymentEditForm = reactive({
    amount: '',
});

const supplierDebtForm = reactive({
    supplier_id: '',
    supplier_name: '',
    supplier_phone: '',
    amount_due: '',
    due_date: '',
    notes: '',
});

const supplierForm = reactive({
    name: '',
    phone: '',
    payment_terms: '',
    notes: '',
});

const supplierPhoneForm = reactive({
    phone: '',
});

const supplierDebtFilters = reactive({
    search: '',
    status: '',
    exactDate: '',
    startDate: '',
    endDate: '',
});
const supplierDebtDateDraft = reactive({
    exactDate: '',
    startDate: '',
    endDate: '',
});
const supplierDebtDateMode = ref('exact');
const supplierDebtSort = ref('due_date_asc');
const supplierFilters = reactive({
    search: '',
    situation: '',
});
const supplierSort = ref('name_asc');

const expenseForm = reactive({
    name: '',
    category: '',
    type: 'variable',
    amount: '',
    spent_on: new Date().toISOString().slice(0, 10),
    notes: '',
});

const expenseCategories = [
    'Autres',
    'Entretien, réparations et maintenance',
    'Honoraires et services extérieurs',
    'Impôts, taxes et droits',
    'Locations et charges locatives',
    'Matériel et fournitures',
    'Publicité, marketing et communication',
    'Télécommunications et internet',
    'Transport et déplacements',
    'Eau, électricité et autres énergies',
].sort((a, b) => a.localeCompare(b, 'fr'));

const expenseCategoryIcons = {
    'Autres': 'fa-solid fa-layer-group',
    'Entretien, réparations et maintenance': 'fa-solid fa-screwdriver-wrench',
    'Eau, électricité et autres énergies': 'fa-solid fa-bolt',
    'Honoraires et services extérieurs': 'fa-solid fa-handshake',
    'Impôts, taxes et droits': 'fa-solid fa-file-invoice-dollar',
    'Locations et charges locatives': 'fa-solid fa-building',
    'Matériel et fournitures': 'fa-solid fa-boxes-stacked',
    'Publicité, marketing et communication': 'fa-solid fa-bullhorn',
    'Télécommunications et internet': 'fa-solid fa-wifi',
    'Transport et déplacements': 'fa-solid fa-truck',
};

function expenseCategoryIconClass(category) {
    return expenseCategoryIcons[category] || 'fa-solid fa-list';
}

function selectExpenseFilterCategory(category) {
    expenseFilters.category = category;
    showExpenseFilterCategoryMenu.value = false;
}

function selectExpenseFormCategory(category) {
    expenseForm.category = category;
    showExpenseFormCategoryMenu.value = false;
}

const expenseFilters = reactive({
    search: '',
    category: '',
    exactDate: '',
    startDate: '',
    endDate: '',
});
const expenseDateDraft = reactive({
    exactDate: '',
    startDate: '',
    endDate: '',
});
const expenseSort = ref('date_desc');

const receivableFilters = reactive({
    search: '',
    status: '',
    exactDate: '',
    startDate: '',
    endDate: '',
});
const receivableDateDraft = reactive({
    exactDate: '',
    startDate: '',
    endDate: '',
});
const receivableSort = ref('due_date_asc');
const customerFilters = reactive({
    search: '',
    situation: '',
});
const customerSort = ref('name_asc');

const employeeForm = reactive({
    name: '',
    phone: '',
    username: '',
    password: '',
    type: 'seller',
    custom_type: '',
    salary: '',
    salary_payment_date: '',
    hired_at: '',
});
const employeePasswordVisible = ref(false);
const employeeFilters = reactive({
    search: '',
    type: '',
});
const employeeSort = ref('name_asc');

const advanceForm = reactive({
    employee_id: '',
    amount: '',
    advanced_on: new Date().toISOString().slice(0, 10),
    notes: '',
});
const employeeBanForm = reactive({
    reason: '',
});

const payrollForm = reactive({
    employee_id: '',
    period: new Date().toISOString().slice(0, 7),
    paid_amount: '',
});
const payrollFilters = reactive({
    search: '',
    status: '',
});
const payrollSort = ref('period_desc');
const advanceFilters = reactive({
    search: '',
});
const advanceSort = ref('date_desc');

const subscriptionForm = reactive({
    plan: 'monthly',
    deposit_phone: '',
});

const settingsForm = reactive({
    name: '',
    phone: '',
    whatsapp_phone: '',
    address: '',
    ifu: '',
    slogan: '',
    primary_color: '#2f7d69',
    secondary_color: '#f5b84b',
    show_logo_on_documents: true,
    show_ifu_on_documents: true,
    show_slogan_on_documents: true,
    show_address_on_documents: true,
});

const profilePasswordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const notificationFilters = reactive({
    search: '',
    status: '',
    type: '',
});
const notificationSort = ref('newest');

const productCategories = computed(() => {
    return [...new Set(products.value.map((product) => product.category?.name || 'Non classé'))]
        .sort((a, b) => a.localeCompare(b, 'fr'));
});
const filteredProducts = computed(() => {
    const term = productFilters.search.trim().toLowerCase();
    const rows = products.value.filter((product) => {
        const category = product.category?.name || 'Non classé';
        const matchesSearch = !term
            || [product.name, product.unit, category].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesCategory = !productFilters.category || category === productFilters.category;
        const matchesStatus = !productFilters.status
            || (productFilters.status === 'low' && isLow(product))
            || (productFilters.status === 'ok' && !isLow(product));

        return matchesSearch && matchesCategory && matchesStatus;
    });

    return [...rows].sort((a, b) => {
        if (productSort.value === 'stock_asc') return Number(a.stock_quantity || 0) - Number(b.stock_quantity || 0);
        if (productSort.value === 'stock_desc') return Number(b.stock_quantity || 0) - Number(a.stock_quantity || 0);
        if (productSort.value === 'sale_desc') return Number(b.sale_price || 0) - Number(a.sale_price || 0);
        if (productSort.value === 'category_asc') return String(a.category?.name || 'Non classé').localeCompare(String(b.category?.name || 'Non classé'), 'fr');
        return String(a.name || '').localeCompare(String(b.name || ''), 'fr');
    });
});
const filteredProductsStockValue = computed(() => filteredProducts.value.reduce((sum, product) => sum + (Number(product.stock_quantity || 0) * Number(product.purchase_price || 0)), 0));
const productsCountLabel = computed(() => {
    const count = filteredProducts.value.length;
    return `${count} produit${count >= 2 ? 's' : ''} affiché${count >= 2 ? 's' : ''}`;
});
const availableProducts = computed(() => products.value.filter((product) => Number(product.stock_quantity) > 0));

const cartTotal = computed(() => cart.value.reduce((total, item) => total + item.total, 0));
const settingsLogoUrl = computed(() => settingsLogoPreview.value || businessLogoPathUrl(business.value?.logo_path));
const businessSidebarLogoUrl = computed(() => businessLogoPathUrl(business.value?.logo_path));
const secondaryColorSuggestions = computed(() => suggestSecondaryColors(settingsForm.primary_color));
const settingsPrimaryButtonStyle = computed(() => ({
    backgroundColor: normalizedHexColor(settingsForm.primary_color, '#2f7d69'),
    color: readableTextColor(settingsForm.primary_color),
}));
const unreadNotifications = computed(() => notifications.value.filter((item) => !item.read_at).length);
const notificationTypes = computed(() => {
    return [...new Set(notifications.value.map((notification) => notification.type).filter(Boolean))]
        .sort((a, b) => notificationLabel(a).localeCompare(notificationLabel(b), 'fr'));
});
const filteredNotifications = computed(() => {
    const term = notificationFilters.search.trim().toLowerCase();
    const rows = notifications.value.filter((notification) => {
        const matchesSearch = !term
            || [notification.title, notification.message, notificationLabel(notification.type)]
                .some((value) => String(value || '').toLowerCase().includes(term));
        const matchesStatus = !notificationFilters.status
            || (notificationFilters.status === 'unread' && !notification.read_at)
            || (notificationFilters.status === 'read' && notification.read_at);
        const matchesType = !notificationFilters.type || notification.type === notificationFilters.type;

        return matchesSearch && matchesStatus && matchesType;
    });

    return [...rows].sort((a, b) => {
        if (notificationSort.value === 'oldest') {
            return String(a.created_at || '').localeCompare(String(b.created_at || ''));
        }
        if (notificationSort.value === 'unread_first') {
            return Number(!!a.read_at) - Number(!!b.read_at)
                || String(b.created_at || '').localeCompare(String(a.created_at || ''));
        }
        if (notificationSort.value === 'type_asc') {
            return notificationLabel(a.type).localeCompare(notificationLabel(b.type), 'fr')
                || String(b.created_at || '').localeCompare(String(a.created_at || ''));
        }

        return String(b.created_at || '').localeCompare(String(a.created_at || ''));
    });
});
const notificationsCountLabel = computed(() => {
    const count = filteredNotifications.value.length;
    return `${count} notification${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const filteredSales = computed(() => {
    const term = salesFilters.search.trim().toLowerCase();
    const rows = sales.value.filter((sale) => {
        if ((sale.type || 'invoice') !== 'invoice') {
            return false;
        }

        const soldDate = String(sale.sold_at || '').slice(0, 10);
        const matchesSearch = !term
            || [
                sale.number,
                sale.seller?.name,
                sale.seller?.phone,
                sale.customer?.name,
                sale.customer?.phone,
            ].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesPayment = !salesFilters.payment || sale.payment_method === salesFilters.payment;
        const matchesExactDate = !salesFilters.exactDate || soldDate === salesFilters.exactDate;
        const matchesStartDate = !salesFilters.startDate || (soldDate && soldDate >= salesFilters.startDate);
        const matchesEndDate = !salesFilters.endDate || (soldDate && soldDate <= salesFilters.endDate);

        return matchesSearch && matchesPayment && matchesExactDate && matchesStartDate && matchesEndDate;
    });

    return [...rows].sort((a, b) => {
        const aDate = String(a.sold_at || '');
        const bDate = String(b.sold_at || '');
        if (salesSort.value === 'date_asc') return aDate.localeCompare(bDate);
        if (salesSort.value === 'total_desc') return Number(b.total) - Number(a.total);
        if (salesSort.value === 'total_asc') return Number(a.total) - Number(b.total);
        if (salesSort.value === 'seller_asc') return String(a.seller?.name || '').localeCompare(String(b.seller?.name || ''), 'fr');
        if (salesSort.value === 'payment_asc') return paymentLabel(a.payment_method).localeCompare(paymentLabel(b.payment_method), 'fr');
        return bDate.localeCompare(aDate);
    });
});
const filteredSalesTotal = computed(() => filteredSales.value.reduce((sum, sale) => sum + Number(sale.total || 0), 0));
const salesCountLabel = computed(() => {
    const count = filteredSales.value.length;
    return `${count} vente${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const filteredProformas = computed(() => {
    const term = salesFilters.search.trim().toLowerCase();
    const rows = sales.value.filter((sale) => {
        if (sale.type !== 'proforma') {
            return false;
        }

        const soldDate = String(sale.sold_at || '').slice(0, 10);
        const matchesSearch = !term
            || [
                sale.number,
                sale.seller?.name,
                sale.seller?.phone,
                sale.customer?.name,
                sale.customer?.phone,
            ].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesExactDate = !salesFilters.exactDate || soldDate === salesFilters.exactDate;
        const matchesStartDate = !salesFilters.startDate || (soldDate && soldDate >= salesFilters.startDate);
        const matchesEndDate = !salesFilters.endDate || (soldDate && soldDate <= salesFilters.endDate);

        return matchesSearch && matchesExactDate && matchesStartDate && matchesEndDate;
    });

    return [...rows].sort((a, b) => {
        const aDate = String(a.sold_at || '');
        const bDate = String(b.sold_at || '');
        if (salesSort.value === 'date_asc') return aDate.localeCompare(bDate);
        if (salesSort.value === 'total_desc') return Number(b.total) - Number(a.total);
        if (salesSort.value === 'total_asc') return Number(a.total) - Number(b.total);
        if (salesSort.value === 'seller_asc') return String(a.seller?.name || '').localeCompare(String(b.seller?.name || ''), 'fr');
        return bDate.localeCompare(aDate);
    });
});
const filteredProformasTotal = computed(() => filteredProformas.value.reduce((sum, sale) => sum + Number(sale.total || 0), 0));
const proformasCountLabel = computed(() => {
    const count = filteredProformas.value.length;
    return `${count} pro forma affichée${count >= 2 ? 's' : ''}`;
});
const displayedSalesRows = computed(() => salesView.value === 'proformas' ? filteredProformas.value : filteredSales.value);
const salesDateFilterLabel = computed(() => {
    if (salesFilters.exactDate) {
        return salesFilters.exactDate;
    }

    if (salesFilters.startDate || salesFilters.endDate) {
        return `${salesFilters.startDate || 'Début'} - ${salesFilters.endDate || 'Fin'}`;
    }

    return 'Choisir';
});
const filteredExpenses = computed(() => {
    const term = expenseFilters.search.trim().toLowerCase();
    const rows = expenses.value.filter((expense) => {
        const matchesSearch = !term
            || [expense.name, expense.category, expense.notes].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesCategory = !expenseFilters.category || expense.category === expenseFilters.category;
        const matchesExactDate = !expenseFilters.exactDate || expense.spent_on === expenseFilters.exactDate;
        const matchesStartDate = !expenseFilters.startDate || expense.spent_on >= expenseFilters.startDate;
        const matchesEndDate = !expenseFilters.endDate || expense.spent_on <= expenseFilters.endDate;

        return matchesSearch && matchesCategory && matchesExactDate && matchesStartDate && matchesEndDate;
    });

    return [...rows].sort((a, b) => {
        if (expenseSort.value === 'date_asc') return String(a.spent_on).localeCompare(String(b.spent_on));
        if (expenseSort.value === 'amount_desc') return Number(b.amount) - Number(a.amount);
        if (expenseSort.value === 'amount_asc') return Number(a.amount) - Number(b.amount);
        if (expenseSort.value === 'category_asc') return String(a.category || '').localeCompare(String(b.category || ''));
        return String(b.spent_on).localeCompare(String(a.spent_on));
    });
});
const filteredExpensesTotal = computed(() => filteredExpenses.value.reduce((sum, expense) => sum + Number(expense.amount || 0), 0));
const expensesCountLabel = computed(() => {
    const count = filteredExpenses.value.length;
    return `${count} charge${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const expenseDateFilterLabel = computed(() => {
    if (expenseFilters.exactDate) {
        return expenseFilters.exactDate;
    }

    if (expenseFilters.startDate || expenseFilters.endDate) {
        return `${expenseFilters.startDate || 'Début'} - ${expenseFilters.endDate || 'Fin'}`;
    }

    return 'Choisir';
});
const filteredReceivables = computed(() => {
    const term = receivableFilters.search.trim().toLowerCase();
    const rows = receivables.value.filter((receivable) => {
        const customer = receivable.customer || {};
        const matchesSearch = !term
            || [customer.name, customer.phone, customer.email].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesStatus = !receivableFilters.status || receivable.status === receivableFilters.status;
        const dueDate = receivable.due_date || '';
        const matchesExactDate = !receivableFilters.exactDate || dueDate === receivableFilters.exactDate;
        const matchesStartDate = !receivableFilters.startDate || (dueDate && dueDate >= receivableFilters.startDate);
        const matchesEndDate = !receivableFilters.endDate || (dueDate && dueDate <= receivableFilters.endDate);

        return matchesSearch && matchesStatus && matchesExactDate && matchesStartDate && matchesEndDate;
    });

    return [...rows].sort((a, b) => {
        const aDueDate = a.due_date || '9999-12-31';
        const bDueDate = b.due_date || '9999-12-31';

        if (receivableSort.value === 'due_date_desc') return bDueDate.localeCompare(aDueDate);
        if (receivableSort.value === 'remaining_desc') return Number(b.remaining) - Number(a.remaining);
        if (receivableSort.value === 'remaining_asc') return Number(a.remaining) - Number(b.remaining);
        if (receivableSort.value === 'customer_asc') return String(a.customer?.name || '').localeCompare(String(b.customer?.name || ''), 'fr');
        if (receivableSort.value === 'status_asc') return receivableStatusLabel(a.status).localeCompare(receivableStatusLabel(b.status), 'fr');
        return aDueDate.localeCompare(bDueDate);
    });
});
const filteredReceivablesTotal = computed(() => filteredReceivables.value.reduce((sum, receivable) => sum + Number(receivable.remaining || 0), 0));
const receivablesCountLabel = computed(() => {
    const count = filteredReceivables.value.length;
    return `${count} créance${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const maxEditableReceivablePaymentAmount = computed(() => {
    if (!selectedReceivableDetails.value || !editingReceivablePayment.value) {
        return 1;
    }

    const otherPaymentsTotal = (selectedReceivableDetails.value.payments || [])
        .filter((payment) => payment.id !== editingReceivablePayment.value.id)
        .reduce((sum, payment) => sum + Number(payment.amount || 0), 0);

    return Math.max(1, Number(selectedReceivableDetails.value.amount_due || 0) - otherPaymentsTotal);
});
const receivableDateFilterLabel = computed(() => {
    if (receivableFilters.exactDate) {
        return receivableFilters.exactDate;
    }

    if (receivableFilters.startDate || receivableFilters.endDate) {
        return `${receivableFilters.startDate || 'Début'} - ${receivableFilters.endDate || 'Fin'}`;
    }

    return 'Choisir';
});
const filteredSupplierDebts = computed(() => {
    const term = supplierDebtFilters.search.trim().toLowerCase();
    const rows = supplierDebts.value.filter((debt) => {
        const supplier = debt.supplier || {};
        const matchesSearch = !term
            || [supplier.name, supplier.phone].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesStatus = !supplierDebtFilters.status || debt.status === supplierDebtFilters.status;
        const dueDate = debt.due_date || '';
        const matchesExactDate = !supplierDebtFilters.exactDate || dueDate === supplierDebtFilters.exactDate;
        const matchesStartDate = !supplierDebtFilters.startDate || (dueDate && dueDate >= supplierDebtFilters.startDate);
        const matchesEndDate = !supplierDebtFilters.endDate || (dueDate && dueDate <= supplierDebtFilters.endDate);

        return matchesSearch && matchesStatus && matchesExactDate && matchesStartDate && matchesEndDate;
    });

    return [...rows].sort((a, b) => {
        const aDueDate = a.due_date || '9999-12-31';
        const bDueDate = b.due_date || '9999-12-31';

        if (supplierDebtSort.value === 'due_date_desc') return bDueDate.localeCompare(aDueDate);
        if (supplierDebtSort.value === 'remaining_desc') return Number(b.remaining) - Number(a.remaining);
        if (supplierDebtSort.value === 'remaining_asc') return Number(a.remaining) - Number(b.remaining);
        if (supplierDebtSort.value === 'supplier_asc') return String(a.supplier?.name || '').localeCompare(String(b.supplier?.name || ''), 'fr');
        if (supplierDebtSort.value === 'status_asc') return receivableStatusLabel(a.status).localeCompare(receivableStatusLabel(b.status), 'fr');
        return aDueDate.localeCompare(bDueDate);
    });
});
const filteredSupplierDebtsTotal = computed(() => filteredSupplierDebts.value.reduce((sum, debt) => sum + Number(debt.remaining || 0), 0));
const supplierDebtsCountLabel = computed(() => {
    const count = filteredSupplierDebts.value.length;
    return `${count} dette${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const maxEditableSupplierDebtPaymentAmount = computed(() => {
    if (!selectedSupplierDebtDetails.value || !editingSupplierDebtPayment.value) {
        return 1;
    }

    const otherPaymentsTotal = (selectedSupplierDebtDetails.value.payments || [])
        .filter((payment) => payment.id !== editingSupplierDebtPayment.value.id)
        .reduce((sum, payment) => sum + Number(payment.amount || 0), 0);

    return Math.max(1, Number(selectedSupplierDebtDetails.value.amount_due || 0) - otherPaymentsTotal);
});
const supplierDebtDateFilterLabel = computed(() => {
    if (supplierDebtFilters.exactDate) {
        return supplierDebtFilters.exactDate;
    }

    if (supplierDebtFilters.startDate || supplierDebtFilters.endDate) {
        return `${supplierDebtFilters.startDate || 'Début'} - ${supplierDebtFilters.endDate || 'Fin'}`;
    }

    return 'Choisir';
});
const supplierSummaries = computed(() => {
    const rows = new Map();

    suppliers.value.forEach((supplier) => {
        rows.set(`supplier-${supplier.id}`, {
            key: `supplier-${supplier.id}`,
            id: supplier.id,
            name: supplier.name || 'Fournisseur',
            phone: supplier.phone || '',
            payment_terms: supplier.payment_terms || '',
            notes: supplier.notes || '',
            totalDebt: 0,
            remainingDebt: 0,
            hasDebt: false,
            debts: [],
        });
    });

    supplierDebts.value.forEach((debt) => {
        const supplier = debt.supplier || {};
        const key = supplier.id ? `supplier-${supplier.id}` : `anonymous-${supplier.name || debt.id}`;
        if (!rows.has(key)) {
            rows.set(key, {
                key,
                id: supplier.id || null,
                name: supplier.name || 'Fournisseur',
                phone: supplier.phone || '',
                payment_terms: supplier.payment_terms || '',
                notes: supplier.notes || '',
                totalDebt: 0,
                remainingDebt: 0,
                hasDebt: false,
                debts: [],
            });
        }

        const row = rows.get(key);
        const remaining = Number(debt.remaining || 0);
        row.totalDebt += Number(debt.amount_due || 0);
        row.remainingDebt += remaining;
        row.hasDebt = row.hasDebt || remaining > 0;
        row.debts.push(debt);
    });

    return Array.from(rows.values()).map((supplier) => ({
        ...supplier,
        situation: supplier.hasDebt ? 'Dettes en cours' : 'Fournisseur OK',
    }));
});
const filteredSuppliers = computed(() => {
    const term = supplierFilters.search.trim().toLowerCase();
    const rows = supplierSummaries.value.filter((supplier) => {
        const matchesSearch = !term
            || [supplier.name, supplier.phone, supplier.payment_terms, supplier.notes].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesSituation = !supplierFilters.situation
            || (supplierFilters.situation === 'debt' && supplier.hasDebt)
            || (supplierFilters.situation === 'ok' && !supplier.hasDebt);

        return matchesSearch && matchesSituation;
    });

    return [...rows].sort((a, b) => {
        if (supplierSort.value === 'debt_desc') return Number(b.remainingDebt) - Number(a.remainingDebt);
        if (supplierSort.value === 'orders_desc') return Number(b.debts.length) - Number(a.debts.length);
        return String(a.name || '').localeCompare(String(b.name || ''), 'fr');
    });
});
const filteredSuppliersDebtTotal = computed(() => filteredSuppliers.value.reduce((sum, supplier) => sum + Number(supplier.remainingDebt || 0), 0));
const suppliersCountLabel = computed(() => {
    const count = filteredSuppliers.value.length;
    return `${count} fournisseur${count >= 2 ? 's' : ''} affiché${count >= 2 ? 's' : ''}`;
});
const defaultEmployeeTypes = [
    { value: 'seller', label: 'Vendeur' },
    { value: 'cashier', label: 'Caissier' },
    { value: 'accountant', label: 'Comptable' },
    { value: 'observer', label: 'Observateur' },
];
const employeeTypeOptions = computed(() => {
    const options = [...defaultEmployeeTypes];
    const knownTypes = new Set(options.map((type) => type.value));

    [...employeeTypes.value, ...employees.value.map((employee) => employee.type)].forEach((type) => {
        const value = String(type || '').trim();
        if (value && !knownTypes.has(value)) {
            knownTypes.add(value);
            options.push({ value, label: value });
        }
    });

    return options;
});
const employeeFormIsSeller = computed(() => employeeForm.type === 'seller');
const filteredEmployees = computed(() => {
    const term = employeeFilters.search.trim().toLowerCase();
    const rows = employees.value.filter((employee) => {
        const matchesSearch = !term
            || [employee.name, employee.position, employee.user?.phone].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesType = !employeeFilters.type || employee.type === employeeFilters.type;

        return matchesSearch && matchesType;
    });

    return [...rows].sort((a, b) => {
        if (employeeSort.value === 'salary_desc') return Number(b.salary || 0) - Number(a.salary || 0);
        if (employeeSort.value === 'salary_asc') return Number(a.salary || 0) - Number(b.salary || 0);
        if (employeeSort.value === 'hired_desc') return String(b.hired_at || '').localeCompare(String(a.hired_at || ''));
        if (employeeSort.value === 'type_asc') return employeeTypeLabel(a.type).localeCompare(employeeTypeLabel(b.type), 'fr');
        return String(a.name || '').localeCompare(String(b.name || ''), 'fr');
    });
});
const filteredEmployeesSalaryTotal = computed(() => filteredEmployees.value.reduce((sum, employee) => sum + Number(employee.salary || 0), 0));
const employeesCountLabel = computed(() => {
    const count = filteredEmployees.value.length;
    return `${count} employé${count >= 2 ? 's' : ''} affiché${count >= 2 ? 's' : ''}`;
});
const filteredPayrolls = computed(() => {
    const term = payrollFilters.search.trim().toLowerCase();
    const rows = payrolls.value.filter((payroll) => {
        const matchesSearch = !term
            || [payroll.period, payroll.employee?.name, payroll.status].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesStatus = !payrollFilters.status || payroll.status === payrollFilters.status;

        return matchesSearch && matchesStatus;
    });

    return [...rows].sort((a, b) => {
        if (payrollSort.value === 'period_asc') return String(a.period || '').localeCompare(String(b.period || ''));
        if (payrollSort.value === 'net_desc') return Number(b.net_salary || 0) - Number(a.net_salary || 0);
        if (payrollSort.value === 'employee_asc') return String(a.employee?.name || '').localeCompare(String(b.employee?.name || ''), 'fr');
        return String(b.period || '').localeCompare(String(a.period || ''));
    });
});
const filteredPayrollsTotal = computed(() => filteredPayrolls.value.reduce((sum, payroll) => sum + Number(payroll.net_salary || 0), 0));
const payrollsCountLabel = computed(() => {
    const count = filteredPayrolls.value.length;
    return `${count} paie${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const employeeAdvances = computed(() => employees.value.flatMap((employee) => (employee.advances || []).map((advance) => ({
    ...advance,
    employee: {
        id: employee.id,
        name: employee.name,
    },
}))));
const filteredAdvances = computed(() => {
    const term = advanceFilters.search.trim().toLowerCase();
    const rows = employeeAdvances.value.filter((advance) => {
        return !term
            || [
                advance.employee?.name,
                advance.amount,
                advance.notes,
                formatDateOnly(advance.advanced_on),
            ].some((value) => String(value || '').toLowerCase().includes(term));
    });

    return [...rows].sort((a, b) => {
        if (advanceSort.value === 'date_asc') return String(a.advanced_on || '').localeCompare(String(b.advanced_on || ''));
        if (advanceSort.value === 'amount_desc') return Number(b.amount || 0) - Number(a.amount || 0);
        if (advanceSort.value === 'employee_asc') return String(a.employee?.name || '').localeCompare(String(b.employee?.name || ''), 'fr');
        return String(b.advanced_on || '').localeCompare(String(a.advanced_on || ''));
    });
});
const filteredAdvancesTotal = computed(() => filteredAdvances.value.reduce((sum, advance) => sum + Number(advance.amount || 0), 0));
const advancesCountLabel = computed(() => {
    const count = filteredAdvances.value.length;
    return `${count} avance${count >= 2 ? 's' : ''} affichée${count >= 2 ? 's' : ''}`;
});
const selectedPayrollEmployee = computed(() => employees.value.find((employee) => Number(employee.id) === Number(payrollForm.employee_id)) || null);
const selectedPayrollAdvances = computed(() => {
    if (!selectedPayrollEmployee.value || !payrollForm.period) {
        return [];
    }

    return (selectedPayrollEmployee.value.advances || []).filter((advance) => String(advance.advanced_on || '').startsWith(payrollForm.period));
});
const selectedPayrollAdvanceTotal = computed(() => selectedPayrollAdvances.value.reduce((sum, advance) => sum + Number(advance.amount || 0), 0));
const selectedPayrollNetEstimate = computed(() => Math.max(0, Number(selectedPayrollEmployee.value?.salary || 0) - selectedPayrollAdvanceTotal.value));
const customerSummaries = computed(() => {
    const customerRows = new Map();
    const ensureCustomer = (customer, fallbackName = 'Client comptoir') => {
        const key = customer?.id ? `customer-${customer.id}` : `anonymous-${fallbackName}`;
        if (!customerRows.has(key)) {
            customerRows.set(key, {
                key,
                id: customer?.id || null,
                name: customer?.name || fallbackName,
                phone: customer?.phone || '',
                email: customer?.email || '',
                address: customer?.address || '',
                totalPurchases: 0,
                remainingDebt: 0,
                hasDebt: false,
                invoices: [],
                receivables: [],
                situation: 'Client OK',
            });
        }

        return customerRows.get(key);
    };

    customers.value.forEach((customer) => {
        ensureCustomer(customer, customer.name || 'Client');
    });

    sales.value.forEach((sale) => {
        const customer = ensureCustomer(sale.customer, sale.customer?.name || 'Client comptoir');
        customer.totalPurchases += Number(sale.total || 0);
        customer.invoices.push({
            id: sale.id,
            number: sale.number,
            total: Number(sale.total || 0),
            sold_at: sale.sold_at,
            payment_method: sale.payment_method,
            items: sale.items || [],
        });
    });

    receivables.value.forEach((receivable) => {
        const customer = ensureCustomer(receivable.customer, receivable.customer?.name || 'Client crédit');
        const remaining = Number(receivable.remaining || 0);
        customer.remainingDebt += remaining;
        customer.hasDebt = customer.hasDebt || remaining > 0;
        if (remaining > 0) {
            customer.receivables.push(receivable);
        }
    });

    return [...customerRows.values()]
        .map((customer) => ({
            ...customer,
            situation: customer.hasDebt ? 'Créances en cours' : 'Client OK',
            invoices: customer.invoices.sort((a, b) => String(b.sold_at || '').localeCompare(String(a.sold_at || ''))),
            receivables: customer.receivables.sort((a, b) => String(a.due_date || '9999-12-31').localeCompare(String(b.due_date || '9999-12-31'))),
        }))
        .sort((a, b) => a.name.localeCompare(b.name, 'fr'));
});
const filteredCustomers = computed(() => {
    const term = customerFilters.search.trim().toLowerCase();
    const rows = customerSummaries.value.filter((customer) => {
        const matchesSearch = !term
            || [
                customer.name,
                customer.phone,
                customer.email,
                customer.address,
                ...customer.invoices.flatMap((invoice) => [
                    invoice.number,
                    invoice.items.map((item) => item.product_name).join(' '),
                ]),
            ].some((value) => String(value || '').toLowerCase().includes(term));
        const matchesSituation = !customerFilters.situation
            || (customerFilters.situation === 'debt' && customer.hasDebt)
            || (customerFilters.situation === 'ok' && !customer.hasDebt);

        return matchesSearch && matchesSituation;
    });

    return [...rows].sort((a, b) => {
        if (customerSort.value === 'purchases_desc') return Number(b.totalPurchases) - Number(a.totalPurchases);
        if (customerSort.value === 'debt_desc') return Number(b.remainingDebt) - Number(a.remainingDebt);
        if (customerSort.value === 'invoices_desc') return b.invoices.length - a.invoices.length;
        return a.name.localeCompare(b.name, 'fr');
    });
});
const filteredCustomersTotal = computed(() => filteredCustomers.value.reduce((sum, customer) => sum + Number(customer.totalPurchases || 0), 0));
const filteredCustomersDebtTotal = computed(() => filteredCustomers.value.reduce((sum, customer) => sum + Number(customer.remainingDebt || 0), 0));
const customersCountLabel = computed(() => {
    const count = filteredCustomers.value.length;
    return `${count} client${count >= 2 ? 's' : ''} affiché${count >= 2 ? 's' : ''}`;
});
const subscriptionActive = computed(() => {
    if (!['actif', 'active'].includes(subscription.value?.status)) {
        return false;
    }

    if (!subscription.value?.ends_at) {
        return true;
    }

    return new Date(subscription.value.ends_at) >= new Date();
});
const subscriptionPending = computed(() => ['pending', 'en attente'].includes(subscription.value?.status) && Boolean(subscription.value?.deposit_phone || subscription.value?.payment_reference));
const showSubscriptionModal = computed(() => dashboardLoaded.value && !subscriptionActive.value);
const selectedSubscriptionPlan = computed(() => subscriptionPlans.value[subscriptionForm.plan]);
const subscriptionExpiryLabel = computed(() => {
    if (!subscription.value) {
        return 'Aucun abonnement';
    }

    if (!['actif', 'active'].includes(subscription.value.status)) {
        return subscription.value.status || 'En attente';
    }

    if (!subscription.value.ends_at) {
        return 'Actif sans expiration';
    }

    const now = new Date();
    const end = new Date(subscription.value.ends_at);

    if (Number.isNaN(end.getTime()) || end < now) {
        return 'Expiré';
    }

    const totalDays = Math.max(1, Math.ceil((end - now) / 86400000));

    if (totalDays < 30) {
        return `Expire dans ${totalDays} jour${totalDays > 1 ? 's' : ''}`;
    }

    const months = Math.floor(totalDays / 30);
    const days = totalDays % 30;
    const monthLabel = `${months} mois`;
    const dayLabel = days ? ` et ${days} jour${days > 1 ? 's' : ''}` : '';

    return `Expire dans ${monthLabel}${dayLabel}`;
});

function formatMoney(value) {
    const amount = Math.round(Number(value || 0));
    return String(amount).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA';
}

function phoneInputValue(value) {
    const digits = String(value || '').replace(/\D/g, '');
    return digits.startsWith('01') ? digits.slice(2, 10) : digits.slice(0, 8);
}

function phoneWithPrefix(value) {
    return `01${String(value || '').replace(/\D/g, '').slice(0, 8)}`;
}

function optionalPhoneWithPrefix(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
    return digits ? `01${digits}` : '';
}

function normalizedHexColor(value, fallback = '#2f7d69') {
    const color = String(value || '').trim();
    return /^#[0-9A-Fa-f]{6}$/.test(color) ? color : fallback;
}

function hexToRgb(value) {
    const color = normalizedHexColor(value).slice(1);
    return {
        r: parseInt(color.slice(0, 2), 16),
        g: parseInt(color.slice(2, 4), 16),
        b: parseInt(color.slice(4, 6), 16),
    };
}

function rgbToHex({ r, g, b }) {
    return `#${[r, g, b].map((channel) => Math.max(0, Math.min(255, Math.round(channel))).toString(16).padStart(2, '0')).join('')}`;
}

function mixColor(color, target, ratio) {
    const source = hexToRgb(color);
    const destination = hexToRgb(target);
    return rgbToHex({
        r: source.r + (destination.r - source.r) * ratio,
        g: source.g + (destination.g - source.g) * ratio,
        b: source.b + (destination.b - source.b) * ratio,
    });
}

function rotateHue(value, degrees) {
    const { r, g, b } = hexToRgb(value);
    const max = Math.max(r, g, b) / 255;
    const min = Math.min(r, g, b) / 255;
    let h = 0;
    const l = (max + min) / 2;
    const d = max - min;
    let s = 0;

    if (d) {
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        if (max === r / 255) h = ((g / 255 - b / 255) / d + (g < b ? 6 : 0)) / 6;
        if (max === g / 255) h = ((b / 255 - r / 255) / d + 2) / 6;
        if (max === b / 255) h = ((r / 255 - g / 255) / d + 4) / 6;
    }

    h = (h * 360 + degrees + 360) % 360 / 360;

    const hueToRgb = (p, q, t) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    };

    if (!s) {
        return rgbToHex({ r, g, b });
    }

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    return rgbToHex({
        r: hueToRgb(p, q, h + 1 / 3) * 255,
        g: hueToRgb(p, q, h) * 255,
        b: hueToRgb(p, q, h - 1 / 3) * 255,
    });
}

function suggestSecondaryColors(primaryColor) {
    const color = normalizedHexColor(primaryColor);
    return [
        mixColor(color, '#f5b84b', 0.65),
        rotateHue(color, 35),
        mixColor(rotateHue(color, 180), '#ffffff', 0.25),
    ];
}

function readableTextColor(backgroundColor) {
    const { r, g, b } = hexToRgb(backgroundColor);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 150 ? '#10251f' : '#ffffff';
}

function resetDefaultColors() {
    settingsForm.primary_color = '#2f7d69';
    settingsForm.secondary_color = '#f5b84b';
}

function requestHeaders(extra = {}) {
    return {
        'X-CSRF-TOKEN': props.csrfToken,
        Accept: 'application/json',
        ...extra,
    };
}

async function enhanceTables() {
    await nextTick();

    document.querySelectorAll('.table-wrap table').forEach((table) => {
        const headers = Array.from(table.querySelectorAll('thead th')).map((item) => item.textContent.trim());
        table.querySelectorAll('tbody tr').forEach((row) => {
            row.querySelectorAll('td').forEach((cell, index) => {
                if (!cell.hasAttribute('data-label') && headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });

        const wrap = table.closest('.table-wrap');
        if (wrap) {
            const count = Array.from(table.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('.empty')).length;
            const existing = wrap.nextElementSibling?.classList?.contains('table-pagination') ? wrap.nextElementSibling : null;
            if (existing) {
                existing.querySelector('span').textContent = paginationLabel(count);
                return;
            }

            const pagination = document.createElement('div');
            pagination.className = 'table-pagination';
            pagination.innerHTML = `<span>${paginationLabel(count)}</span><div><button type="button" disabled>Précédent</button><button type="button" disabled>Suivant</button></div>`;
            wrap.insertAdjacentElement('afterend', pagination);
        }
    });
}

watch([activeSection, customerView, supplierView, salesView, employeeView, payrollSubView, filteredProducts, filteredSales, filteredProformas, filteredExpenses, filteredReceivables, filteredCustomers, filteredSupplierDebts, filteredSuppliers, filteredEmployees, filteredPayrolls, filteredAdvances], () => {
    enhanceTables();
}, { flush: 'post' });

watch(() => employeeForm.type, (type) => {
    if (type !== 'seller') {
        employeeForm.username = '';
        employeeForm.password = '';
        employeePasswordVisible.value = false;
    }
});

watch([currentUserCanSell, activeSection], () => {
    if (!currentUserCanSell.value) {
        return;
    }

    if (!sellerSections.includes(activeSection.value)) {
        activeSection.value = 'seller-cashier';
    }

    if (activeSection.value === 'seller-cashier') {
        saleForm.type = 'invoice';
    }

    if (activeSection.value === 'seller-proforma') {
        saleForm.type = 'proforma';
    }

    if (activeSection.value === 'seller-sales') {
        salesView.value = 'invoices';
    }
}, { immediate: true });

function isLow(product) {
    return Number(product.stock_quantity) <= Number(product.alert_threshold);
}

function paymentLabel(method) {
    return {
        cash: 'Espèces',
        mobile_money: 'Mobile Money',
        credit: 'Crédit',
    }[method] || method;
}

function receivableStatusLabel(status) {
    return {
        current: 'À jour',
        overdue: 'En retard',
        paid: 'Payée',
    }[status] || status;
}

function receivableStatusClass(status) {
    return {
        current: 'ok',
        overdue: 'danger',
        paid: 'ok',
    }[status] || 'ok';
}

function normalizeWhatsappPhone(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    if (!digits) {
        return '';
    }

    if (digits.startsWith('229')) {
        return digits;
    }

    return `229${digits.replace(/^0+/, '')}`;
}

function receivableWhatsappUrl(receivable) {
    const phone = normalizeWhatsappPhone(receivable.customer?.phone);
    const debtDate = formatDateOnly(receivable.created_at);
    const message = `Cher client, nous vous relancons au sujet de votre dette du ${debtDate}, le solde dû est de ${formatMoney(receivable.remaining)}.`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function customerWhatsappUrl(customer) {
    const phone = normalizeWhatsappPhone(customer?.phone);
    const message = `Bonjour ${customer?.name || 'cher client'}, nous vous contactons depuis EasyMarket.`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function supplierWhatsappUrl(supplier) {
    const phone = normalizeWhatsappPhone(supplier?.phone);
    const message = `Bonjour ${supplier?.name || 'cher fournisseur'}, nous vous contactons depuis EasyMarket.`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function invoiceDetails(invoice) {
    return (invoice.items || [])
        .map((item) => `${item.product_name} x ${Number(item.quantity)}`)
        .join(', ');
}

function expenseTypeLabel(type) {
    return {
        fixed: 'Fixe',
        variable: 'Variable',
    }[type] || type;
}

function notificationLabel(type) {
    return {
        stock_low: 'Stock',
        receivable_due: 'Créance',
        supplier_debt_due: 'Dette',
        payroll_pending: 'Paie',
        salary_advance: 'Avance',
        daily_report: 'Rapport',
    }[type] || type;
}

function planIcon(plan) {
    return {
        monthly: 'fa-solid fa-calendar-day',
        yearly: 'fa-solid fa-calendar-check',
        lifetime: 'fa-solid fa-infinity',
    }[plan] || 'fa-solid fa-credit-card';
}

function employeeTypeLabel(type) {
    return {
        seller: 'Vendeur',
        cashier: 'Caissier',
        accountant: 'Comptable',
        observer: 'Observateur',
    }[type] || type;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const time = new Intl.DateTimeFormat('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date);

    return `${day}/${month}/${year} à ${time}`;
}

function formatDateOnly(value) {
    if (!value) {
        return '-';
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]}/${match[1]}`;
    }

    return formatDate(value);
}

function formatDayMonth(value) {
    if (!value) {
        return '-';
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]}`;
    }

    return value;
}

function dateToDayMonth(value) {
    if (!value) {
        return '';
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]}`;
    }

    return String(value);
}

function formatDayMonthInput(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 4);
    if (digits.length <= 2) {
        return digits;
    }

    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
}

function printEscapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function businessLogoPathUrl(logoPath) {
    if (!logoPath) {
        return '';
    }

    if (String(logoPath).startsWith('http') || String(logoPath).startsWith('/')) {
        return logoPath;
    }

    return `/storage/${logoPath}`;
}

function businessLogoUrl() {
    const logoPath = business.value?.logo_path;
    if (!logoPath || !business.value?.show_logo_on_documents) {
        return '';
    }

    return businessLogoPathUrl(logoPath);
}

function printBusinessHeaderHtml() {
    const logoUrl = businessLogoUrl();
    const details = [
        business.value?.phone ? `Tél. ${business.value.phone}` : '',
        business.value?.show_address_on_documents && business.value?.address ? business.value.address : '',
        business.value?.show_ifu_on_documents && business.value?.ifu ? `IFU : ${business.value.ifu}` : '',
        business.value?.show_slogan_on_documents && business.value?.slogan ? business.value.slogan : '',
    ].filter(Boolean);

    return `
        <header class="doc-header">
            ${logoUrl ? `<img src="${printEscapeHtml(logoUrl)}" alt="Logo">` : '<div class="doc-logo">EM</div>'}
            <div>
                <h2>${printEscapeHtml(business.value?.name || 'Boutique')}</h2>
                ${details.map((item) => `<p>${printEscapeHtml(item)}</p>`).join('')}
            </div>
        </header>
    `;
}

function printDocumentFooterHtml() {
    return `<footer class="doc-footer">Document généré avec l'application Easy_Market le ${formatDate(new Date())}</footer>`;
}

function printDocumentStyles() {
    return `
        body{min-height:100vh;font-family:Poppins,Arial,sans-serif;color:#17211b;padding:24px;display:flex;flex-direction:column}
        .doc-header{display:flex;align-items:center;gap:14px;border-bottom:2px solid #10251f;padding-bottom:14px;margin-bottom:18px}
        .doc-header img,.doc-logo{width:58px;height:58px;border-radius:10px;object-fit:cover}
        .doc-logo{display:grid;place-items:center;background:linear-gradient(135deg,#2f7d69,#f5b84b);color:#10251f;font-weight:800}
        .doc-header h2{margin:0 0 4px;font-size:24px}
        .doc-header p{margin:1px 0;color:#52635b;font-size:13px}
        h1{margin:0 0 6px}
        p{margin:0 0 18px;color:#52635b}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #dfe7e2;padding:9px;text-align:left;vertical-align:top}
        th{background:#10251f;color:white}
        tfoot td{font-weight:800}
        .doc-footer{margin-top:auto;padding-top:12px;border-top:1px solid #dfe7e2;color:#52635b;font-size:12px;text-align:center}
        @media print{body{min-height:100vh}}
    `;
}

function writePrintableDocument(printWindow, title, summary, tableHtml) {
    printWindow.document.write(`
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>${printEscapeHtml(title)}</title>
            <style>${printDocumentStyles()}</style>
        </head>
        <body>
            ${printBusinessHeaderHtml()}
            <h1>${printEscapeHtml(title)}</h1>
            <p>${summary}</p>
            ${tableHtml}
            ${printDocumentFooterHtml()}
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

async function loadDashboard() {
    dashboardLoaded.value = false;
    const response = await fetch(`/api/businesses/${props.businessId}/dashboard`);
    const data = await response.json();

    business.value = data.business;
    currentUser.value = data.current_user || {};
    if (currentUser.value?.force_logout_message) {
        window.alert(currentUser.value.force_logout_message);
        await fetch('/deconnexion', {
            method: 'POST',
            headers: requestHeaders(),
        });
        window.location.href = '/connexion';
        return;
    }
    Object.assign(settingsForm, {
        name: data.business?.name || '',
        phone: data.business?.phone || '',
        whatsapp_phone: data.business?.whatsapp_phone || '',
        address: data.business?.address || '',
        ifu: data.business?.ifu || '',
        slogan: data.business?.slogan || '',
        primary_color: data.business?.primary_color || '#2f7d69',
        secondary_color: data.business?.secondary_color || '#f5b84b',
        show_logo_on_documents: Boolean(data.business?.show_logo_on_documents),
        show_ifu_on_documents: Boolean(data.business?.show_ifu_on_documents),
        show_slogan_on_documents: Boolean(data.business?.show_slogan_on_documents),
        show_address_on_documents: Boolean(data.business?.show_address_on_documents),
    });
    settingsLogoFile.value = null;
    settingsLogoPreview.value = '';
    subscription.value = data.subscription;
    subscriptionPlans.value = data.subscription_plans || {};
    if (subscription.value?.plan) {
        subscriptionForm.plan = subscription.value.plan;
    }
    if (subscription.value?.deposit_phone) {
        subscriptionForm.deposit_phone = subscription.value.deposit_phone;
    }
    products.value = data.products;
    products.value.forEach((product) => {
        productPriceForms[product.id] = Number(product.sale_price || 0);
    });
    sales.value = data.sales;
    customers.value = data.customers || [];
    suppliers.value = data.suppliers || [];
    receivables.value = data.receivables.map((receivable) => ({
        ...receivable,
        payment_amount: receivable.remaining || '',
        payment_method: 'cash',
    }));
    supplierDebts.value = data.supplier_debts.map((debt) => ({
        ...debt,
        payment_amount: debt.remaining || '',
        payment_method: 'cash',
    }));
    expenses.value = data.expenses;
    employees.value = data.employees;
    employeeTypes.value = data.employee_types || [];
    payrolls.value = data.payrolls;
    notifications.value = data.notifications;
    Object.assign(summary, data.summary);

    const taxesResponse = await fetch(`/api/businesses/${props.businessId}/taxes`);
    const taxes = await taxesResponse.json();
    taxFaq.value = taxes.faq;
    taxStatement.value = taxes.statement;
    dashboardLoaded.value = true;
    await enhanceTables();
}

function onSettingsLogoChange(event) {
    const file = event.target.files?.[0] || null;
    settingsLogoFile.value = file;
    settingsLogoPreview.value = file ? URL.createObjectURL(file) : '';
}

async function saveSettings() {
    savingSettings.value = true;
    settingsMessage.value = '';

    try {
        const payload = new FormData();
        Object.entries(settingsForm).forEach(([key, value]) => {
            payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value ?? ''));
        });

        if (settingsLogoFile.value) {
            payload.append('logo', settingsLogoFile.value);
        }

        const response = await fetch(`/api/businesses/${props.businessId}/settings`, {
            method: 'POST',
            headers: requestHeaders(),
            body: payload,
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Paramètres invalides.');
        }

        business.value = await response.json();
        settingsLogoFile.value = null;
        settingsLogoPreview.value = '';
        settingsMessage.value = 'Paramètres boutique enregistrés.';
        notifySuccess(settingsMessage.value);
    } catch (error) {
        settingsMessage.value = error.message;
        notifyError(settingsMessage.value);
    } finally {
        savingSettings.value = false;
    }
}

async function changeProfilePassword() {
    savingProfilePassword.value = true;
    profileMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/profile/password`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(profilePasswordForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Mot de passe invalide.');
        }

        Object.assign(profilePasswordForm, {
            current_password: '',
            password: '',
            password_confirmation: '',
        });
        showProfileCurrentPassword.value = false;
        showProfilePassword.value = false;
        showProfilePasswordConfirmation.value = false;
        profileMessage.value = 'Mot de passe modifié.';
        notifySuccess(profileMessage.value);
    } catch (error) {
        profileMessage.value = error.message;
        notifyError(profileMessage.value);
    } finally {
        savingProfilePassword.value = false;
    }
}

async function requestSubscriptionActivation() {
    savingSubscription.value = true;
    subscriptionMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/subscription-request`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(subscriptionForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || "La demande d'abonnement est invalide.");
        }

        subscription.value = await response.json();
        subscriptionMessage.value = "Demande envoyée. Votre abonnement est en attente de validation par l'équipe EasyMarket.";
        notifySuccess(subscriptionMessage.value);
    } catch (error) {
        subscriptionMessage.value = error.message;
        notifyError(subscriptionMessage.value);
    } finally {
        savingSubscription.value = false;
    }
}

async function markNotificationRead(notification) {
    await fetch(`/api/businesses/${props.businessId}/notifications/${notification.id}/read`, {
        method: 'POST',
        headers: requestHeaders(),
    });

    notification.read_at = new Date().toISOString();
    notifySuccess('Notification marquée comme lue.');
}

async function markAllNotificationsRead() {
    if (!unreadNotifications.value) {
        return;
    }

    await fetch(`/api/businesses/${props.businessId}/notifications/read-all`, {
        method: 'POST',
        headers: requestHeaders(),
    });

    const readAt = new Date().toISOString();
    notifications.value = notifications.value.map((notification) => ({
        ...notification,
        read_at: notification.read_at || readAt,
    }));
    notifySuccess('Toutes les notifications sont marquées comme lues.');
}

async function saveProduct() {
    saving.value = true;
    message.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/products`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(form),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Produit invalide.');
        }

        Object.assign(form, {
            name: '',
            category_name: '',
            unit: 'unité',
            purchase_price: '',
            sale_price: '',
            stock_quantity: '',
            alert_threshold: '',
        });

        message.value = 'Produit ajouté avec succès.';
        notifySuccess(message.value);
        await loadDashboard();
        showProductModal.value = false;
    } catch (error) {
        message.value = error.message;
        notifyError(message.value);
    } finally {
        saving.value = false;
    }
}

async function updateSellerProductPrice(product) {
    const salePrice = Number(productPriceForms[product.id] || 0);

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/products/${product.id}/price`, {
            method: 'PATCH',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ sale_price: salePrice }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Prix invalide.');
        }

        const updatedProduct = await response.json();
        const index = products.value.findIndex((item) => item.id === product.id);
        if (index !== -1) {
            products.value[index] = updatedProduct;
            productPriceForms[updatedProduct.id] = Number(updatedProduct.sale_price || 0);
        }
        notifySuccess('Prix de vente modifié.');
    } catch (error) {
        productPriceForms[product.id] = Number(product.sale_price || 0);
        notifyError(error.message);
    }
}

function openProductModal() {
    message.value = '';
    Object.assign(form, {
        name: '',
        category_name: '',
        unit: 'unité',
        purchase_price: '',
        sale_price: '',
        stock_quantity: '',
        alert_threshold: '',
    });
    showProductModal.value = true;
}

function printFilteredProducts() {
    showProductPrintModal.value = false;
    const rows = filteredProducts.value.map((product) => `<tr><td>${printEscapeHtml(product.name)}</td><td>${printEscapeHtml(product.category?.name || 'Non classé')}</td><td>${printEscapeHtml(product.unit || '-')}</td><td>${printEscapeHtml(product.stock_quantity)}</td><td>${printEscapeHtml(product.alert_threshold)}</td><td>${formatMoney(product.purchase_price)}</td><td>${formatMoney(product.sale_price)}</td><td>${printEscapeHtml(isLow(product) ? 'Stock bas' : 'OK')}</td></tr>`).join('')
        || '<tr><td colspan="8">Aucun produit affiché.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        message.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(message.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Stocks affichés',
        `Valeur du stock : ${formatMoney(filteredProductsStockValue.value)}`,
        `<table>
            <thead><tr><th>Produit</th><th>Catégorie</th><th>Unité</th><th>Stock</th><th>Seuil</th><th>Prix achat</th><th>Prix vente</th><th>État</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function exportFilteredProductsExcel() {
    showProductPrintModal.value = false;
    const rows = filteredProducts.value.map((product) => `<tr><td>${printEscapeHtml(product.name)}</td><td>${printEscapeHtml(product.category?.name || 'Non classé')}</td><td>${printEscapeHtml(product.unit || '-')}</td><td>${printEscapeHtml(product.stock_quantity)}</td><td>${printEscapeHtml(product.alert_threshold)}</td><td>${formatMoney(product.purchase_price)}</td><td>${formatMoney(product.sale_price)}</td><td>${printEscapeHtml(isLow(product) ? 'Stock bas' : 'OK')}</td></tr>`).join('')
        || '<tr><td colspan="8">Aucun produit affiché.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Produit</th><th>Catégorie</th><th>Unité</th><th>Stock</th><th>Seuil</th><th>Prix achat</th><th>Prix vente</th><th>État</th></tr></thead>
            <tbody>${rows}</tbody>
            <tfoot><tr><td colspan="5">Valeur du stock</td><td colspan="3">${formatMoney(filteredProductsStockValue.value)}</td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `stocks-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function addSaleLine() {
    const product = products.value.find((item) => item.id === Number(saleLine.product_id));

    if (!product) {
        saleMessage.value = 'Sélectionnez un produit.';
        notifyError(saleMessage.value);
        return;
    }

    if (Number(saleLine.quantity) > Number(product.stock_quantity)) {
        saleMessage.value = `Stock insuffisant pour ${product.name}.`;
        notifyError(saleMessage.value);
        return;
    }

    const subtotal = Number(saleLine.quantity) * Number(product.sale_price);
    const discount = Number(saleLine.discount || 0);

    cart.value.push({
        product_id: product.id,
        product_name: product.name,
        quantity: Number(saleLine.quantity),
        unit_price: Number(product.sale_price),
        discount,
        total: Math.max(0, subtotal - discount),
    });

    Object.assign(saleLine, {
        product_id: '',
        quantity: 1,
        discount: '',
    });
    saleMessage.value = '';
}

async function saveSale() {
    savingSale.value = true;
    saleMessage.value = '';

    try {
        if (activeSection.value === 'seller-cashier') {
            saleForm.type = 'invoice';
        }
        if (activeSection.value === 'seller-proforma') {
            saleForm.type = 'proforma';
        }

        const documentType = saleForm.type;
        const response = await fetch(`/api/businesses/${props.businessId}/sales`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                ...saleForm,
                items: cart.value.map((item) => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    discount: item.discount,
                })),
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Vente invalide.');
        }

        cart.value = [];
        Object.assign(saleForm, {
            customer_name: '',
            customer_phone: '',
            payment_method: 'cash',
            type: 'invoice',
            credit_due_date: '',
        });
        saleMessage.value = documentType === 'proforma' ? 'Facture pro forma enregistrée avec succès.' : 'Facture enregistrée avec succès.';
        notifySuccess(saleMessage.value);
        await loadDashboard();
    } catch (error) {
        saleMessage.value = error.message;
        notifyError(saleMessage.value);
    } finally {
        savingSale.value = false;
    }
}

function openSalesDateFilter() {
    salesDateMode.value = salesFilters.startDate || salesFilters.endDate ? 'range' : 'exact';
    Object.assign(salesDateDraft, {
        exactDate: salesFilters.exactDate,
        startDate: salesFilters.startDate,
        endDate: salesFilters.endDate,
    });
    showSalesDateModal.value = true;
}

function applySalesDateFilter() {
    if (salesDateMode.value === 'exact') {
        salesFilters.exactDate = salesDateDraft.exactDate;
        salesFilters.startDate = '';
        salesFilters.endDate = '';
    } else {
        salesFilters.exactDate = '';
        salesFilters.startDate = salesDateDraft.startDate;
        salesFilters.endDate = salesDateDraft.endDate;
    }

    showSalesDateModal.value = false;
}

function clearSalesDateFilter() {
    Object.assign(salesFilters, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    Object.assign(salesDateDraft, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    showSalesDateModal.value = false;
}

function chooseSalesPrint(format) {
    showSalesPrintModal.value = false;
    if (format === 'excel') {
        exportFilteredSalesExcel();
        return;
    }

    printFilteredSales();
}

function salesRowsForExport() {
    return displayedSalesRows.value.map((sale) => ({
        number: sale.number || '',
        seller: sale.seller?.name || 'Vendeur',
        client: sale.customer?.name || 'Client comptoir',
        payment: paymentLabel(sale.payment_method),
        status: sale.status || '',
        total: Number(sale.total || 0),
        date: formatDate(sale.sold_at),
        lines: sale.items?.length || 0,
    }));
}

function exportFilteredSalesExcel() {
    const rows = salesRowsForExport();
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.number)}</td><td>${printEscapeHtml(row.seller)}</td><td>${printEscapeHtml(row.client)}</td><td>${printEscapeHtml(row.payment)}</td><td>${printEscapeHtml(row.status)}</td><td>${row.total}</td><td>${printEscapeHtml(row.date)}</td><td>${row.lines}</td></tr>`).join('')
        : '<tr><td colspan="8">Aucune vente affichée.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Facture</th><th>Vendeur</th><th>Client</th><th>Paiement</th><th>Statut</th><th>Total</th><th>Date</th><th>Lignes</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="5">Total</td><td>${salesView.value === 'proformas' ? filteredProformasTotal.value : filteredSalesTotal.value}</td><td colspan="2"></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${salesView.value === 'proformas' ? 'factures-pro-forma' : 'ventes'}-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredSales() {
    const rows = salesRowsForExport();
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.number)}</td><td>${printEscapeHtml(row.seller)}</td><td>${printEscapeHtml(row.client)}</td><td>${printEscapeHtml(row.payment)}</td><td>${printEscapeHtml(row.status)}</td><td>${formatMoney(row.total)}</td><td>${printEscapeHtml(row.date)}</td><td>${row.lines}</td></tr>`).join('')
        : '<tr><td colspan="8">Aucune vente affichée.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        saleMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(saleMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        salesView.value === 'proformas' ? 'Factures Pro forma affichées' : 'Ventes affichées',
        `Total : ${formatMoney(salesView.value === 'proformas' ? filteredProformasTotal.value : filteredSalesTotal.value)}`,
        `<table>
            <thead><tr><th>Facture</th><th>Vendeur</th><th>Client</th><th>Paiement</th><th>Statut</th><th>Total</th><th>Date</th><th>Lignes</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="5">Total</td><td colspan="3">${formatMoney(salesView.value === 'proformas' ? filteredProformasTotal.value : filteredSalesTotal.value)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

async function payReceivable(receivable) {
    if (!receivable) {
        return;
    }

    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/receivables/${receivable.id}/payments`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount: receivable.payment_amount,
                method: receivable.payment_method,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Remboursement invalide.');
        }

        receivableMessage.value = 'Remboursement enregistré.';
        notifySuccess(receivableMessage.value);
        await loadDashboard();
        closeReceivablePayment();
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    }
}

async function updateReceivableAmount() {
    if (!editingReceivable.value) {
        return;
    }

    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/receivables/${editingReceivable.value.id}`, {
            method: 'PUT',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount_due: receivableAmountForm.amount_due,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Modification invalide.');
        }

        receivableMessage.value = 'Montant de la créance modifié.';
        notifySuccess(receivableMessage.value);
        const editedId = editingReceivable.value.id;
        closeReceivableAmountEdit();
        await loadDashboard();
        refreshSelectedReceivableDetails(editedId);
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    }
}

async function updateReceivablePaymentAmount() {
    if (!selectedReceivableDetails.value || !editingReceivablePayment.value) {
        return;
    }

    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/receivables/${selectedReceivableDetails.value.id}/payments/${editingReceivablePayment.value.id}`, {
            method: 'PUT',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount: receivablePaymentEditForm.amount,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Modification du paiement invalide.');
        }

        receivableMessage.value = 'Montant du paiement modifié.';
        notifySuccess(receivableMessage.value);
        const receivableId = selectedReceivableDetails.value.id;
        closeReceivablePaymentEdit();
        await loadDashboard();
        refreshSelectedReceivableDetails(receivableId);
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    }
}

function openReceivableCreateModal() {
    receivableMessage.value = '';
    Object.assign(receivableForm, {
        customer_id: '',
        customer_name: '',
        customer_phone: '',
        amount_due: '',
        due_date: '',
        notes: '',
    });
    showReceivableCreateModal.value = true;
}

function openCustomerCreateModal() {
    receivableMessage.value = '';
    Object.assign(customerForm, {
        name: '',
        phone: '',
    });
    showCustomerCreateModal.value = true;
}

async function saveReceivable() {
    savingReceivable.value = true;
    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/receivables`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(receivableForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Créance invalide.');
        }

        receivableMessage.value = 'Créance enregistrée.';
        notifySuccess(receivableMessage.value);
        showReceivableCreateModal.value = false;
        await loadDashboard();
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    } finally {
        savingReceivable.value = false;
    }
}

async function saveCustomer() {
    savingCustomer.value = true;
    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/customers`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(customerForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Client invalide.');
        }

        receivableMessage.value = 'Client enregistré.';
        notifySuccess(receivableMessage.value);
        showCustomerCreateModal.value = false;
        customerView.value = 'clients';
        await loadDashboard();
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    } finally {
        savingCustomer.value = false;
    }
}

function openReceivablePayment(receivable) {
    selectedReceivable.value = receivable;
    selectedReceivable.value.payment_amount = receivable.remaining || '';
    selectedReceivable.value.payment_method = receivable.payment_method || 'cash';
    showReceivablePaymentModal.value = true;
}

function closeReceivablePayment() {
    showReceivablePaymentModal.value = false;
    selectedReceivable.value = null;
}

function openReceivableAmountEdit(receivable) {
    editingReceivable.value = receivable;
    receivableAmountForm.amount_due = receivable.amount_due || '';
    showReceivableAmountEditModal.value = true;
}

function closeReceivableAmountEdit() {
    showReceivableAmountEditModal.value = false;
    editingReceivable.value = null;
    receivableAmountForm.amount_due = '';
}

function openReceivableDetails(receivable) {
    selectedReceivableDetails.value = receivable;
    showReceivableDetailsModal.value = true;
}

function closeReceivableDetails() {
    showReceivableDetailsModal.value = false;
    showReceivableDetailsPrintModal.value = false;
    selectedReceivableDetails.value = null;
    closeReceivablePaymentEdit();
}

function refreshSelectedReceivableDetails(receivableId) {
    if (!showReceivableDetailsModal.value || !selectedReceivableDetails.value) {
        return;
    }

    const refreshed = receivables.value.find((receivable) => receivable.id === receivableId);
    if (refreshed) {
        selectedReceivableDetails.value = refreshed;
    }
}

function openReceivablePaymentEdit(payment) {
    editingReceivablePayment.value = payment;
    receivablePaymentEditForm.amount = payment.amount || '';
    showReceivablePaymentEditModal.value = true;
}

function closeReceivablePaymentEdit() {
    showReceivablePaymentEditModal.value = false;
    editingReceivablePayment.value = null;
    receivablePaymentEditForm.amount = '';
}

function openCustomerDetails(customer) {
    selectedCustomerDetails.value = customer;
    showCustomerDetailsModal.value = true;
}

function closeCustomerDetails() {
    showCustomerDetailsModal.value = false;
    selectedCustomerDetails.value = null;
}

async function deleteReceivable(receivable) {
    const confirmed = window.confirm(`Supprimer la dette de ${receivable.customer?.name || 'ce client'} ?`);
    if (!confirmed) {
        return;
    }

    receivableMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/receivables/${receivable.id}`, {
            method: 'DELETE',
            headers: requestHeaders(),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Suppression impossible.');
        }

        receivableMessage.value = 'Créance supprimée.';
        notifySuccess(receivableMessage.value);
        await loadDashboard();
    } catch (error) {
        receivableMessage.value = error.message;
        notifyError(receivableMessage.value);
    }
}

function openReceivableDateFilter() {
    receivableDateMode.value = receivableFilters.startDate || receivableFilters.endDate ? 'range' : 'exact';
    Object.assign(receivableDateDraft, {
        exactDate: receivableFilters.exactDate,
        startDate: receivableFilters.startDate,
        endDate: receivableFilters.endDate,
    });
    showReceivableDateModal.value = true;
}

function applyReceivableDateFilter() {
    if (receivableDateMode.value === 'exact') {
        receivableFilters.exactDate = receivableDateDraft.exactDate;
        receivableFilters.startDate = '';
        receivableFilters.endDate = '';
    } else {
        receivableFilters.exactDate = '';
        receivableFilters.startDate = receivableDateDraft.startDate;
        receivableFilters.endDate = receivableDateDraft.endDate;
    }

    showReceivableDateModal.value = false;
}

function clearReceivableDateFilter() {
    Object.assign(receivableFilters, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    Object.assign(receivableDateDraft, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    showReceivableDateModal.value = false;
}

function chooseReceivablePrint(format) {
    showReceivablePrintModal.value = false;
    if (format === 'excel') {
        exportFilteredReceivablesExcel();
        return;
    }

    printFilteredReceivables();
}

function receivableRowsForExport() {
    return filteredReceivables.value.map((receivable) => ({
        dueDate: formatDateOnly(receivable.due_date),
        customer: receivable.customer?.name || 'Client',
        phone: receivable.customer?.phone || '',
        amountDue: Number(receivable.amount_due || 0),
        amountPaid: Number(receivable.amount_paid || 0),
        remaining: Number(receivable.remaining || 0),
        status: receivableStatusLabel(receivable.status),
    }));
}

function exportFilteredReceivablesExcel() {
    const rows = receivableRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.dueDate)}</td><td>${escapeHtml(row.customer)}</td><td>${escapeHtml(row.phone || '-')}</td><td>${row.amountDue}</td><td>${row.amountPaid}</td><td>${row.remaining}</td><td>${escapeHtml(row.status)}</td></tr>`).join('')
        : '<tr><td colspan="7">Aucune créance affichée.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Échéance</th><th>Client</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Statut</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="5">Total restant</td><td>${filteredReceivablesTotal.value}</td><td></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `creances-clients-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredReceivables() {
    const rows = receivableRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.dueDate)}</td><td>${escapeHtml(row.customer)}</td><td>${escapeHtml(row.phone || '-')}</td><td>${formatMoney(row.amountDue)}</td><td>${formatMoney(row.amountPaid)}</td><td>${formatMoney(row.remaining)}</td><td>${escapeHtml(row.status)}</td></tr>`).join('')
        : '<tr><td colspan="7">Aucune créance affichée.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        receivableMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(receivableMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Créances clients affichées',
        `Total restant : ${formatMoney(filteredReceivablesTotal.value)}`,
        `<table>
            <thead><tr><th>Échéance</th><th>Client</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Statut</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="5">Total restant</td><td colspan="2">${formatMoney(filteredReceivablesTotal.value)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function chooseReceivableDetailsPrint(format) {
    showReceivableDetailsPrintModal.value = false;
    if (format === 'excel') {
        exportReceivableDetailsExcel();
        return;
    }

    printReceivableDetails();
}

function receivableDetailsRowsForExport() {
    const receivable = selectedReceivableDetails.value;
    if (!receivable) {
        return [];
    }

    const payments = receivable.payments || [];
    return payments.length
        ? payments.map((payment) => ({
            date: formatDate(payment.paid_at),
            method: paymentLabel(payment.method),
            reference: payment.reference || '',
            amount: Number(payment.amount || 0),
        }))
        : [];
}

function exportReceivableDetailsExcel() {
    const receivable = selectedReceivableDetails.value;
    if (!receivable) {
        return;
    }

    const rows = receivableDetailsRowsForExport();
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.date)}</td><td>${printEscapeHtml(row.method)}</td><td>${printEscapeHtml(row.reference || '-')}</td><td>${row.amount}</td></tr>`).join('')
        : '<tr><td colspan="4">Aucun paiement enregistré.</td></tr>';
    const html = `
        <table>
            <thead><tr><th colspan="4">Situation créance - ${printEscapeHtml(receivable.customer?.name || 'Client')}</th></tr></thead>
            <tbody>
                <tr><td>Montant initial</td><td colspan="3">${Number(receivable.amount_due || 0)}</td></tr>
                <tr><td>Payé</td><td colspan="3">${Number(receivable.amount_paid || 0)}</td></tr>
                <tr><td>Solde dû</td><td colspan="3">${Number(receivable.remaining || 0)}</td></tr>
                <tr><td>Échéance</td><td colspan="3">${printEscapeHtml(formatDateOnly(receivable.due_date))}</td></tr>
                <tr><td>Statut</td><td colspan="3">${printEscapeHtml(receivableStatusLabel(receivable.status))}</td></tr>
                <tr><th>Date</th><th>Moyen</th><th>Référence</th><th>Montant</th></tr>
                ${bodyRows}
            </tbody>
            <tfoot><tr><td colspan="3">Total payé</td><td>${Number(receivable.amount_paid || 0)}</td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `situation-creance-${receivable.id}-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printReceivableDetails() {
    const receivable = selectedReceivableDetails.value;
    if (!receivable) {
        return;
    }

    const payments = receivable.payments || [];
    const printableRows = payments.length
        ? payments.map((payment) => `<tr><td>${printEscapeHtml(formatDate(payment.paid_at))}</td><td>${printEscapeHtml(paymentLabel(payment.method))}</td><td>${printEscapeHtml(payment.reference || '-')}</td><td>${formatMoney(payment.amount)}</td></tr>`).join('')
        : '<tr><td colspan="4">Aucun paiement enregistré.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        receivableMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(receivableMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        `Situation créance - ${receivable.customer?.name || 'Client'}`,
        `Montant initial : ${formatMoney(receivable.amount_due)} - Payé : ${formatMoney(receivable.amount_paid)} - Solde dû : ${formatMoney(receivable.remaining)} - Statut : ${printEscapeHtml(receivableStatusLabel(receivable.status))}`,
        `<table>
            <thead><tr><th>Date</th><th>Moyen</th><th>Référence</th><th>Montant</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="3">Total payé</td><td>${formatMoney(receivable.amount_paid)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function chooseCustomerPrint(format) {
    showCustomerPrintModal.value = false;
    if (format === 'excel') {
        exportFilteredCustomersExcel();
        return;
    }

    printFilteredCustomers();
}

function customerRowsForExport() {
    return filteredCustomers.value.map((customer) => ({
        name: customer.name || 'Client',
        phone: customer.phone || '',
        totalPurchases: Number(customer.totalPurchases || 0),
        remainingDebt: Number(customer.remainingDebt || 0),
        situation: customer.situation || '',
        invoices: customer.invoices.map((invoice) => `${invoice.number} (${formatMoney(invoice.total)})`).join(', '),
    }));
}

function exportFilteredCustomersExcel() {
    const rows = customerRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.name)}</td><td>${escapeHtml(row.phone || '-')}</td><td>${row.totalPurchases}</td><td>${row.remainingDebt}</td><td>${escapeHtml(row.situation)}</td><td>${escapeHtml(row.invoices || '-')}</td></tr>`).join('')
        : '<tr><td colspan="6">Aucun client affiché.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Client</th><th>Téléphone</th><th>Achats</th><th>Créances</th><th>Situation</th><th>Factures</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="2">Totaux</td><td>${filteredCustomersTotal.value}</td><td>${filteredCustomersDebtTotal.value}</td><td colspan="2"></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `clients-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredCustomers() {
    const rows = customerRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.name)}</td><td>${escapeHtml(row.phone || '-')}</td><td>${formatMoney(row.totalPurchases)}</td><td>${formatMoney(row.remainingDebt)}</td><td>${escapeHtml(row.situation)}</td><td>${escapeHtml(row.invoices || '-')}</td></tr>`).join('')
        : '<tr><td colspan="6">Aucun client affiché.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        receivableMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(receivableMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Clients affichés',
        `Achats : ${formatMoney(filteredCustomersTotal.value)} - Créances : ${formatMoney(filteredCustomersDebtTotal.value)}`,
        `<table>
            <thead><tr><th>Client</th><th>Téléphone</th><th>Achats</th><th>Créances</th><th>Situation</th><th>Factures</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="2">Totaux</td><td>${formatMoney(filteredCustomersTotal.value)}</td><td>${formatMoney(filteredCustomersDebtTotal.value)}</td><td colspan="2"></td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function openSupplierDebtDateFilter() {
    supplierDebtDateMode.value = supplierDebtFilters.startDate || supplierDebtFilters.endDate ? 'range' : 'exact';
    Object.assign(supplierDebtDateDraft, {
        exactDate: supplierDebtFilters.exactDate,
        startDate: supplierDebtFilters.startDate,
        endDate: supplierDebtFilters.endDate,
    });
    showSupplierDebtDateModal.value = true;
}

function applySupplierDebtDateFilter() {
    if (supplierDebtDateMode.value === 'exact') {
        supplierDebtFilters.exactDate = supplierDebtDateDraft.exactDate;
        supplierDebtFilters.startDate = '';
        supplierDebtFilters.endDate = '';
    } else {
        supplierDebtFilters.exactDate = '';
        supplierDebtFilters.startDate = supplierDebtDateDraft.startDate;
        supplierDebtFilters.endDate = supplierDebtDateDraft.endDate;
    }

    showSupplierDebtDateModal.value = false;
}

function clearSupplierDebtDateFilter() {
    Object.assign(supplierDebtFilters, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    Object.assign(supplierDebtDateDraft, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    showSupplierDebtDateModal.value = false;
}

function chooseSupplierDebtPrint(format) {
    showSupplierDebtPrintModal.value = false;
    if (format === 'excel') {
        exportFilteredSupplierDebtsExcel();
        return;
    }

    printFilteredSupplierDebts();
}

function supplierDebtRowsForExport() {
    return filteredSupplierDebts.value.map((debt) => ({
        supplier: debt.supplier?.name || 'Fournisseur',
        phone: debt.supplier?.phone || '',
        amountDue: Number(debt.amount_due || 0),
        amountPaid: Number(debt.amount_paid || 0),
        remaining: Number(debt.remaining || 0),
        dueDate: formatDateOnly(debt.due_date),
        status: receivableStatusLabel(debt.status),
    }));
}

function exportFilteredSupplierDebtsExcel() {
    const rows = supplierDebtRowsForExport();
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.supplier)}</td><td>${printEscapeHtml(row.phone || '-')}</td><td>${formatMoney(row.amountDue)}</td><td>${formatMoney(row.amountPaid)}</td><td>${formatMoney(row.remaining)}</td><td>${printEscapeHtml(row.dueDate)}</td><td>${printEscapeHtml(row.status)}</td></tr>`).join('')
        : '<tr><td colspan="7">Aucune dette affichée.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Fournisseur</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Échéance</th><th>Statut</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="4">Total restant</td><td>${formatMoney(filteredSupplierDebtsTotal.value)}</td><td colspan="2"></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `dettes-fournisseurs-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredSupplierDebts() {
    const rows = supplierDebtRowsForExport();
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.supplier)}</td><td>${printEscapeHtml(row.phone || '-')}</td><td>${formatMoney(row.amountDue)}</td><td>${formatMoney(row.amountPaid)}</td><td>${formatMoney(row.remaining)}</td><td>${printEscapeHtml(row.dueDate)}</td><td>${printEscapeHtml(row.status)}</td></tr>`).join('')
        : '<tr><td colspan="7">Aucune dette affichée.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        supplierDebtMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(supplierDebtMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Dettes fournisseurs affichées',
        `Total restant : ${formatMoney(filteredSupplierDebtsTotal.value)}`,
        `<table>
            <thead><tr><th>Fournisseur</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Échéance</th><th>Statut</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="4">Total restant</td><td colspan="3">${formatMoney(filteredSupplierDebtsTotal.value)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function chooseSupplierPrint(format) {
    showSupplierPrintModal.value = false;
    if (format === 'excel') {
        exportFilteredSuppliersExcel();
        return;
    }

    printFilteredSuppliers();
}

function supplierRowsForExport() {
    return filteredSuppliers.value.map((supplier) => ({
        name: supplier.name || 'Fournisseur',
        phone: supplier.phone || '',
        totalDebt: Number(supplier.totalDebt || 0),
        remainingDebt: Number(supplier.remainingDebt || 0),
        situation: supplier.situation || '',
    }));
}

function exportFilteredSuppliersExcel() {
    const rows = supplierRowsForExport();
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.name)}</td><td>${printEscapeHtml(row.phone || '-')}</td><td>${formatMoney(row.totalDebt)}</td><td>${formatMoney(row.remainingDebt)}</td><td>${printEscapeHtml(row.situation)}</td></tr>`).join('')
        : '<tr><td colspan="5">Aucun fournisseur affiché.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Fournisseur</th><th>Téléphone</th><th>Total dû</th><th>Reste</th><th>Situation</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="3">Reste dû</td><td>${formatMoney(filteredSuppliersDebtTotal.value)}</td><td></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `fournisseurs-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredSuppliers() {
    const rows = supplierRowsForExport();
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${printEscapeHtml(row.name)}</td><td>${printEscapeHtml(row.phone || '-')}</td><td>${formatMoney(row.totalDebt)}</td><td>${formatMoney(row.remainingDebt)}</td><td>${printEscapeHtml(row.situation)}</td></tr>`).join('')
        : '<tr><td colspan="5">Aucun fournisseur affiché.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        supplierDebtMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(supplierDebtMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Fournisseurs affichés',
        `Reste dû : ${formatMoney(filteredSuppliersDebtTotal.value)}`,
        `<table>
            <thead><tr><th>Fournisseur</th><th>Téléphone</th><th>Total dû</th><th>Reste</th><th>Situation</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="3">Reste dû</td><td colspan="2">${formatMoney(filteredSuppliersDebtTotal.value)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

function openSupplierDebtCreateModal() {
    supplierDebtMessage.value = '';
    Object.assign(supplierDebtForm, {
        supplier_id: '',
        supplier_name: '',
        supplier_phone: '',
        amount_due: '',
        due_date: '',
        notes: '',
    });
    showSupplierDebtCreateModal.value = true;
}

function openSupplierCreateModal() {
    supplierMessage.value = '';
    Object.assign(supplierForm, {
        name: '',
        phone: '',
        payment_terms: '',
        notes: '',
    });
    showSupplierCreateModal.value = true;
}

function openSupplierPhoneEdit(supplier) {
    supplierMessage.value = '';
    editingSupplier.value = supplier;
    supplierPhoneForm.phone = supplier.phone || '';
    showSupplierPhoneEditModal.value = true;
}

function closeSupplierPhoneEdit() {
    showSupplierPhoneEditModal.value = false;
    editingSupplier.value = null;
    supplierPhoneForm.phone = '';
}

function openSupplierDebtPayment(debt) {
    selectedSupplierDebt.value = debt;
    selectedSupplierDebt.value.payment_amount = debt.remaining || '';
    selectedSupplierDebt.value.payment_method = debt.payment_method || 'cash';
    showSupplierDebtPaymentModal.value = true;
}

function closeSupplierDebtPayment() {
    showSupplierDebtPaymentModal.value = false;
    selectedSupplierDebt.value = null;
}

function openSupplierDebtAmountEdit(debt) {
    editingSupplierDebt.value = debt;
    supplierDebtAmountForm.amount_due = debt.amount_due || '';
    showSupplierDebtAmountEditModal.value = true;
}

function closeSupplierDebtAmountEdit() {
    showSupplierDebtAmountEditModal.value = false;
    editingSupplierDebt.value = null;
    supplierDebtAmountForm.amount_due = '';
}

function openSupplierDebtDetails(debt) {
    selectedSupplierDebtDetails.value = debt;
    showSupplierDebtDetailsModal.value = true;
}

function closeSupplierDebtDetails() {
    showSupplierDebtDetailsModal.value = false;
    selectedSupplierDebtDetails.value = null;
    closeSupplierDebtPaymentEdit();
}

function refreshSelectedSupplierDebtDetails(debtId) {
    if (!showSupplierDebtDetailsModal.value || !selectedSupplierDebtDetails.value) {
        return;
    }

    const refreshed = supplierDebts.value.find((debt) => debt.id === debtId);
    if (refreshed) {
        selectedSupplierDebtDetails.value = refreshed;
    }
}

function openSupplierDebtPaymentEdit(payment) {
    editingSupplierDebtPayment.value = payment;
    supplierDebtPaymentEditForm.amount = payment.amount || '';
    showSupplierDebtPaymentEditModal.value = true;
}

function closeSupplierDebtPaymentEdit() {
    showSupplierDebtPaymentEditModal.value = false;
    editingSupplierDebtPayment.value = null;
    supplierDebtPaymentEditForm.amount = '';
}

function openSupplierDetails(supplier) {
    selectedSupplierDetails.value = supplier;
    showSupplierDetailsModal.value = true;
}

function closeSupplierDetails() {
    showSupplierDetailsModal.value = false;
    selectedSupplierDetails.value = null;
}

async function saveSupplierDebt() {
    savingSupplierDebt.value = true;
    supplierDebtMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/supplier-debts`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(supplierDebtForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Dette fournisseur invalide.');
        }

        Object.assign(supplierDebtForm, {
            supplier_id: '',
            supplier_name: '',
            supplier_phone: '',
            amount_due: '',
            due_date: '',
            notes: '',
        });
        supplierDebtMessage.value = 'Dette fournisseur enregistrée.';
        notifySuccess(supplierDebtMessage.value);
        await loadDashboard();
        showSupplierDebtCreateModal.value = false;
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    } finally {
        savingSupplierDebt.value = false;
    }
}

async function saveSupplier() {
    savingSupplier.value = true;
    supplierMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/suppliers`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(supplierForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Fournisseur invalide.');
        }

        Object.assign(supplierForm, {
            name: '',
            phone: '',
            payment_terms: '',
            notes: '',
        });
        supplierMessage.value = 'Fournisseur enregistré.';
        notifySuccess(supplierMessage.value);
        await loadDashboard();
        showSupplierCreateModal.value = false;
    } catch (error) {
        supplierMessage.value = error.message;
        notifyError(supplierMessage.value);
    } finally {
        savingSupplier.value = false;
    }
}

async function updateSupplierPhone() {
    if (!editingSupplier.value?.id) {
        return;
    }

    savingSupplier.value = true;
    supplierMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/suppliers/${editingSupplier.value.id}`, {
            method: 'PUT',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(supplierPhoneForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Téléphone fournisseur invalide.');
        }

        supplierMessage.value = 'Téléphone fournisseur modifié.';
        notifySuccess(supplierMessage.value);
        closeSupplierPhoneEdit();
        await loadDashboard();
    } catch (error) {
        supplierMessage.value = error.message;
        notifyError(supplierMessage.value);
    } finally {
        savingSupplier.value = false;
    }
}

async function paySupplierDebt(debt) {
    if (!debt) {
        return;
    }

    supplierDebtMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/supplier-debts/${debt.id}/payments`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount: debt.payment_amount,
                method: debt.payment_method,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Paiement fournisseur invalide.');
        }

        supplierDebtMessage.value = 'Paiement fournisseur enregistré.';
        notifySuccess(supplierDebtMessage.value);
        await loadDashboard();
        closeSupplierDebtPayment();
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    }
}

async function updateSupplierDebtAmount() {
    if (!editingSupplierDebt.value) {
        return;
    }

    supplierDebtMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/supplier-debts/${editingSupplierDebt.value.id}`, {
            method: 'PUT',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount_due: supplierDebtAmountForm.amount_due,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Modification invalide.');
        }

        supplierDebtMessage.value = 'Montant de la dette modifié.';
        notifySuccess(supplierDebtMessage.value);
        const editedId = editingSupplierDebt.value.id;
        closeSupplierDebtAmountEdit();
        await loadDashboard();
        refreshSelectedSupplierDebtDetails(editedId);
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    }
}

async function updateSupplierDebtPaymentAmount() {
    if (!selectedSupplierDebtDetails.value || !editingSupplierDebtPayment.value) {
        return;
    }

    supplierDebtMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/supplier-debts/${selectedSupplierDebtDetails.value.id}/payments/${editingSupplierDebtPayment.value.id}`, {
            method: 'PUT',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                amount: supplierDebtPaymentEditForm.amount,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Modification du paiement invalide.');
        }

        supplierDebtMessage.value = 'Montant du paiement fournisseur modifié.';
        notifySuccess(supplierDebtMessage.value);
        const debtId = selectedSupplierDebtDetails.value.id;
        closeSupplierDebtPaymentEdit();
        await loadDashboard();
        refreshSelectedSupplierDebtDetails(debtId);
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    }
}

async function deleteSupplierDebt(debt) {
    const confirmed = window.confirm(`Supprimer la dette de ${debt.supplier?.name || 'ce fournisseur'} ?`);
    if (!confirmed) {
        return;
    }

    supplierDebtMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/supplier-debts/${debt.id}`, {
            method: 'DELETE',
            headers: requestHeaders(),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Suppression impossible.');
        }

        supplierDebtMessage.value = 'Dette fournisseur supprimée.';
        notifySuccess(supplierDebtMessage.value);
        await loadDashboard();
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    }
}

function openExpenseModal() {
    expenseMessage.value = '';
    showExpenseModal.value = true;
}

function openExpenseDateFilter() {
    expenseDateMode.value = expenseFilters.startDate || expenseFilters.endDate ? 'range' : 'exact';
    Object.assign(expenseDateDraft, {
        exactDate: expenseFilters.exactDate,
        startDate: expenseFilters.startDate,
        endDate: expenseFilters.endDate,
    });
    showExpenseDateModal.value = true;
}

function applyExpenseDateFilter() {
    if (expenseDateMode.value === 'exact') {
        expenseFilters.exactDate = expenseDateDraft.exactDate;
        expenseFilters.startDate = '';
        expenseFilters.endDate = '';
    } else {
        expenseFilters.exactDate = '';
        expenseFilters.startDate = expenseDateDraft.startDate;
        expenseFilters.endDate = expenseDateDraft.endDate;
    }

    showExpenseDateModal.value = false;
}

function clearExpenseDateFilter() {
    Object.assign(expenseFilters, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    Object.assign(expenseDateDraft, {
        exactDate: '',
        startDate: '',
        endDate: '',
    });
    showExpenseDateModal.value = false;
}

function chooseExpensePrint(format) {
    showExpensePrintModal.value = false;
    if (format === 'excel') {
        exportFilteredExpensesExcel();
        return;
    }

    printFilteredExpenses();
}

function expenseRowsForExport() {
    return filteredExpenses.value.map((expense) => ({
        charge: expense.name || '',
        category: expense.category || '',
        amount: Number(expense.amount || 0),
        date: formatDate(expense.created_at),
        notes: expense.notes || '',
    }));
}

function exportFilteredExpensesExcel() {
    const rows = expenseRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const bodyRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.date)}</td><td>${escapeHtml(row.charge)}</td><td>${escapeHtml(row.category)}</td><td>${row.amount}</td><td>${escapeHtml(row.notes)}</td></tr>`).join('')
        : '<tr><td colspan="5">Aucune charge affichée.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Date</th><th>Charge</th><th>Catégorie</th><th>Montant</th><th>Notes</th></tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr><td colspan="3">Total</td><td>${filteredExpensesTotal.value}</td><td></td></tr></tfoot>
        </table>
    `;
    const blob = new Blob([`\uFEFF${html}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `charges-${new Date().toISOString().slice(0, 10)}.xls`;
    link.click();
    URL.revokeObjectURL(link.href);
    notifySuccess('Export Excel généré.');
}

function printFilteredExpenses() {
    const rows = expenseRowsForExport();
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const printableRows = rows.length
        ? rows.map((row) => `<tr><td>${escapeHtml(row.date)}</td><td>${escapeHtml(row.charge)}</td><td>${escapeHtml(row.category)}</td><td>${formatMoney(row.amount)}</td><td>${escapeHtml(row.notes || '-')}</td></tr>`).join('')
        : '<tr><td colspan="5">Aucune charge affichée.</td></tr>';
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        expenseMessage.value = 'Impossible d’ouvrir la fenêtre d’impression.';
        notifyError(expenseMessage.value);
        return;
    }

    writePrintableDocument(
        printWindow,
        'Charges affichées',
        `Total : ${formatMoney(filteredExpensesTotal.value)}`,
        `<table>
            <thead><tr><th>Date</th><th>Charge</th><th>Catégorie</th><th>Montant</th><th>Notes</th></tr></thead>
            <tbody>${printableRows}</tbody>
            <tfoot><tr><td colspan="3">Total</td><td colspan="2">${formatMoney(filteredExpensesTotal.value)}</td></tr></tfoot>
        </table>`
    );
    notifySuccess('Impression PDF lancée.');
}

async function saveExpense() {
    savingExpense.value = true;
    expenseMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/expenses`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(expenseForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Charge invalide.');
        }

        Object.assign(expenseForm, {
            name: '',
            category: '',
            type: 'variable',
            amount: '',
            spent_on: new Date().toISOString().slice(0, 10),
            notes: '',
        });
        expenseMessage.value = 'Charge enregistrée.';
        notifySuccess(expenseMessage.value);
        await loadDashboard();
        showExpenseModal.value = false;
    } catch (error) {
        expenseMessage.value = error.message;
        notifyError(expenseMessage.value);
    } finally {
        savingExpense.value = false;
    }
}

async function saveEmployee() {
    savingEmployee.value = true;
    employeeMessage.value = '';

    try {
        const type = employeeForm.type === '__other' ? employeeForm.custom_type.trim() : employeeForm.type;
        if (editingEmployee.value?.type === 'seller' && type !== 'seller') {
            const confirmed = window.confirm("Vous êtes en train de changer le type de compte d'un vendeur. Toutes ses actions en cours seront annulées et il ne pourra plus se connecter. Voulez-vous continuer ?");
            if (!confirmed) {
                return;
            }
        }

        const payload = {
            name: employeeForm.name,
            phone: type === 'seller' ? employeeForm.phone : '',
            username: type === 'seller' ? employeeForm.username : '',
            password: type === 'seller' ? employeeForm.password : '',
            type,
            salary: employeeForm.salary,
            salary_payment_date: employeeForm.salary_payment_date,
            hired_at: employeeForm.hired_at,
        };
        const url = editingEmployee.value
            ? `/api/businesses/${props.businessId}/employees/${editingEmployee.value.id}`
            : `/api/businesses/${props.businessId}/employees`;
        const response = await fetch(url, {
            method: editingEmployee.value ? 'PUT' : 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Employé invalide.');
        }

        Object.assign(employeeForm, {
            name: '',
            phone: '',
            username: '',
            password: '',
            type: 'seller',
            custom_type: '',
            salary: '',
            salary_payment_date: '',
            hired_at: '',
        });
        employeeMessage.value = 'Employé enregistré.';
        notifySuccess(employeeMessage.value);
        closeEmployeeModal();
        await loadDashboard();
    } catch (error) {
        employeeMessage.value = error.message;
        notifyError(employeeMessage.value);
    } finally {
        savingEmployee.value = false;
    }
}

function openEmployeeModal(employee = null) {
    employeeMessage.value = '';
    editingEmployee.value = employee;
    employeePasswordVisible.value = false;
    Object.assign(employeeForm, {
        name: employee?.name || '',
        phone: employee?.user?.phone || '',
        username: employee?.user?.username || '',
        password: '',
        type: employee?.type || 'seller',
        custom_type: '',
        salary: employee?.salary || '',
        salary_payment_date: dateToDayMonth(employee?.salary_payment_date),
        hired_at: employee?.hired_at || '',
    });
    showEmployeeModal.value = true;
}

function closeEmployeeModal() {
    showEmployeeModal.value = false;
    editingEmployee.value = null;
    employeePasswordVisible.value = false;
}

function openEmployeeDetails(employee) {
    selectedEmployeeDetails.value = employee;
    showEmployeeDetailsModal.value = true;
}

function closeEmployeeDetails() {
    showEmployeeDetailsModal.value = false;
    selectedEmployeeDetails.value = null;
}

function openAdvanceModal(employee = null) {
    editingAdvance.value = null;
    selectedAdvanceEmployee.value = employee;
    Object.assign(advanceForm, {
        employee_id: employee?.id || '',
        amount: '',
        advanced_on: new Date().toISOString().slice(0, 10),
        notes: '',
    });
    showAdvanceModal.value = true;
}

function openAdvanceEdit(advance) {
    editingAdvance.value = advance;
    selectedAdvanceEmployee.value = advance.employee || null;
    Object.assign(advanceForm, {
        employee_id: advance.employee_id || advance.employee?.id || '',
        amount: advance.amount || '',
        advanced_on: String(advance.advanced_on || '').slice(0, 10),
        notes: advance.notes || '',
    });
    showAdvanceModal.value = true;
}

function closeAdvanceModal() {
    showAdvanceModal.value = false;
    editingAdvance.value = null;
    selectedAdvanceEmployee.value = null;
}

function openEmployeeBanModal(employee) {
    selectedBanEmployee.value = employee;
    employeeBanForm.reason = '';
    showEmployeeBanModal.value = true;
}

function closeEmployeeBanModal() {
    showEmployeeBanModal.value = false;
    selectedBanEmployee.value = null;
    employeeBanForm.reason = '';
}

async function banEmployee() {
    if (!selectedBanEmployee.value) {
        return;
    }

    savingEmployeeBan.value = true;
    employeeMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/employees/${selectedBanEmployee.value.id}/ban`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(employeeBanForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Bannissement impossible.');
        }

        employeeMessage.value = 'Employé banni.';
        notifySuccess(employeeMessage.value);
        closeEmployeeBanModal();
        await loadDashboard();
    } catch (error) {
        employeeMessage.value = error.message;
        notifyError(employeeMessage.value);
    } finally {
        savingEmployeeBan.value = false;
    }
}

function openPayrollModal() {
    Object.assign(payrollForm, {
        employee_id: '',
        period: new Date().toISOString().slice(0, 7),
        paid_amount: '',
    });
    showPayrollModal.value = true;
}

async function saveAdvance() {
    savingAdvance.value = true;
    payrollMessage.value = '';
    employeeMessage.value = '';

    try {
        const url = editingAdvance.value
            ? `/api/businesses/${props.businessId}/salary-advances/${editingAdvance.value.id}`
            : `/api/businesses/${props.businessId}/employees/${advanceForm.employee_id}/advances`;
        const response = await fetch(url, {
            method: editingAdvance.value ? 'PUT' : 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(advanceForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Avance invalide.');
        }

        Object.assign(advanceForm, {
            employee_id: '',
            amount: '',
            advanced_on: new Date().toISOString().slice(0, 10),
            notes: '',
        });
        payrollMessage.value = editingAdvance.value ? 'Avance modifiée.' : 'Avance enregistrée.';
        employeeMessage.value = payrollMessage.value;
        notifySuccess(payrollMessage.value);
        closeAdvanceModal();
        payrollSubView.value = 'advances';
        await loadDashboard();
    } catch (error) {
        payrollMessage.value = error.message;
        employeeMessage.value = error.message;
        notifyError(payrollMessage.value);
    } finally {
        savingAdvance.value = false;
    }
}

async function deleteAdvance(advance) {
    const confirmed = window.confirm(`Supprimer cette avance de ${formatMoney(advance.amount)} ?`);
    if (!confirmed) {
        return;
    }

    payrollMessage.value = '';
    employeeMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/salary-advances/${advance.id}`, {
            method: 'DELETE',
            headers: requestHeaders(),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Suppression impossible.');
        }

        payrollMessage.value = 'Avance supprimée.';
        employeeMessage.value = payrollMessage.value;
        notifySuccess(payrollMessage.value);
        payrollSubView.value = 'advances';
        await loadDashboard();
    } catch (error) {
        payrollMessage.value = error.message;
        employeeMessage.value = error.message;
        notifyError(payrollMessage.value);
    }
}

async function paySalary() {
    payrollMessage.value = '';

    try {
        if (payrollForm.paid_amount === '' && selectedPayrollEmployee.value) {
            payrollForm.paid_amount = selectedPayrollNetEstimate.value;
        }

        const response = await fetch(`/api/businesses/${props.businessId}/payrolls`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payrollForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Paie invalide.');
        }

        payrollMessage.value = 'Salaire payé.';
        notifySuccess(payrollMessage.value);
        showPayrollModal.value = false;
        await loadDashboard();
    } catch (error) {
        payrollMessage.value = error.message;
        notifyError(payrollMessage.value);
    }
}

function printFilteredEmployees() {
    showEmployeePrintModal.value = false;
    const rows = filteredEmployees.value.map((employee) => `<tr><td>${printEscapeHtml(employee.name)}</td><td>${printEscapeHtml(employee.position)}</td><td>${printEscapeHtml(employeeTypeLabel(employee.type))}</td><td>${formatMoney(employee.salary)}</td><td>${printEscapeHtml(formatDateOnly(employee.hired_at))}</td><td>${formatMoney((employee.advances || []).reduce((sum, item) => sum + Number(item.amount), 0))}</td></tr>`).join('')
        || '<tr><td colspan="6">Aucun employé affiché.</td></tr>';
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    writePrintableDocument(
        printWindow,
        'Personnel affiché',
        `Masse salariale : ${formatMoney(filteredEmployeesSalaryTotal.value)}`,
        `<table><thead><tr><th>Employé</th><th>Poste</th><th>Type</th><th>Salaire</th><th>Embauche</th><th>Avances</th></tr></thead><tbody>${rows}</tbody></table>`
    );
}

function downloadExcelHtml(filename, tableHtml) {
    const blob = new Blob([`\uFEFF${tableHtml}`], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

function exportFilteredEmployeesExcel() {
    showEmployeePrintModal.value = false;
    const rows = filteredEmployees.value.map((employee) => `<tr><td>${printEscapeHtml(employee.name)}</td><td>${printEscapeHtml(employee.position)}</td><td>${printEscapeHtml(employeeTypeLabel(employee.type))}</td><td>${Number(employee.salary || 0)}</td><td>${printEscapeHtml(formatDateOnly(employee.hired_at))}</td><td>${(employee.advances || []).reduce((sum, item) => sum + Number(item.amount), 0)}</td></tr>`).join('')
        || '<tr><td colspan="6">Aucun employé affiché.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Employé</th><th>Poste</th><th>Type</th><th>Salaire</th><th>Embauche</th><th>Avances</th></tr></thead>
            <tbody>${rows}</tbody>
            <tfoot><tr><td colspan="3">Masse salariale</td><td>${filteredEmployeesSalaryTotal.value}</td><td colspan="2"></td></tr></tfoot>
        </table>
    `;
    downloadExcelHtml(`personnel-${new Date().toISOString().slice(0, 10)}.xls`, html);
    notifySuccess('Export Excel généré.');
}

function printFilteredPayrolls() {
    showPayrollPrintModal.value = false;
    const rows = filteredPayrolls.value.map((payroll) => `<tr><td>${printEscapeHtml(payroll.period)}</td><td>${printEscapeHtml(payroll.employee?.name || '-')}</td><td>${formatMoney(payroll.gross_salary)}</td><td>${formatMoney(payroll.salary_advance)}</td><td>${formatMoney(payroll.net_salary)}</td><td>${printEscapeHtml(payroll.status)}</td></tr>`).join('')
        || '<tr><td colspan="6">Aucune paie affichée.</td></tr>';
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    writePrintableDocument(
        printWindow,
        'Paies affichées',
        `Total net : ${formatMoney(filteredPayrollsTotal.value)}`,
        `<table><thead><tr><th>Période</th><th>Employé</th><th>Brut</th><th>Avances</th><th>Net</th><th>Statut</th></tr></thead><tbody>${rows}</tbody></table>`
    );
}

function exportFilteredPayrollsExcel() {
    showPayrollPrintModal.value = false;
    const rows = filteredPayrolls.value.map((payroll) => `<tr><td>${printEscapeHtml(payroll.period)}</td><td>${printEscapeHtml(payroll.employee?.name || '-')}</td><td>${Number(payroll.gross_salary || 0)}</td><td>${Number(payroll.salary_advance || 0)}</td><td>${Number(payroll.net_salary || 0)}</td><td>${printEscapeHtml(payroll.status)}</td></tr>`).join('')
        || '<tr><td colspan="6">Aucune paie affichée.</td></tr>';
    const html = `
        <table>
            <thead><tr><th>Période</th><th>Employé</th><th>Brut</th><th>Avances</th><th>Net</th><th>Statut</th></tr></thead>
            <tbody>${rows}</tbody>
            <tfoot><tr><td colspan="4">Total net</td><td>${filteredPayrollsTotal.value}</td><td></td></tr></tfoot>
        </table>
    `;
    downloadExcelHtml(`paies-${new Date().toISOString().slice(0, 10)}.xls`, html);
    notifySuccess('Export Excel généré.');
}

onMounted(loadDashboard);
</script>


