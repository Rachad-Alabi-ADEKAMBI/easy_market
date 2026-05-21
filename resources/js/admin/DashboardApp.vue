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
                <span class="logo">EM</span>
                <span>EasyMarket</span>
            </a>
            <nav class="side-nav">
                <a href="/"><i class="fa-solid fa-house"></i>Accueil</a>
                <a :class="{ active: activeSection === 'expenses' }" :href="sectionUrl('charges')"><i class="fa-solid fa-wallet"></i>Charges</a>
                <a :class="{ active: activeSection === 'customers' }" :href="sectionUrl('clients')"><i class="fa-solid fa-users"></i>Clients & créances</a>
                <a :class="{ active: activeSection === 'sales' }" :href="sectionUrl('ventes')"><i class="fa-solid fa-cash-register"></i>Factures & ventes</a>
                <a :class="{ active: activeSection === 'suppliers' }" :href="sectionUrl('fournisseurs')"><i class="fa-solid fa-truck"></i>Fournisseurs & dettes</a>
                <a :class="{ active: activeSection === 'taxes' }" :href="sectionUrl('impots')"><i class="fa-solid fa-file-invoice-dollar"></i>Impôts & comptabilité</a>
                <a :class="{ active: activeSection === 'notifications' }" :href="sectionUrl('notifications')"><i class="fa-solid fa-bell"></i>Notifications</a>
                <a :class="{ active: activeSection === 'settings' }" :href="sectionUrl('parametres')"><i class="fa-solid fa-gear"></i>Paramètres</a>
                <a :class="{ active: activeSection === 'employees' }" :href="sectionUrl('personnel')"><i class="fa-solid fa-user-tie"></i>Personnel & paie</a>
                <a :class="{ active: activeSection === 'reports' }" :href="sectionUrl('rapports')"><i class="fa-solid fa-chart-column"></i>Rapports</a>
                <a :class="{ active: activeSection === 'stocks' }" :href="sectionUrl('stocks')"><i class="fa-solid fa-boxes-stacked"></i>Stocks</a>
                <a :class="{ active: activeSection === 'dashboard' }" :href="sectionUrl('tableau-de-bord')"><i class="fa-solid fa-chart-line"></i>Tableau de bord</a>
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
                            <input v-model="subscriptionForm.deposit_phone" type="tel" required placeholder="Ex. 019622860">
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
                        <p>Les opérations importantes et les échéances critiques apparaissent ici.</p>
                    </div>
                    <button class="btn btn-light" type="button" @click="loadDashboard"><i class="fa-solid fa-rotate"></i>Actualiser</button>
                </div>
                <div class="notifications">
                    <article v-for="notification in notifications" :key="notification.id" :class="['notification', notification.read_at ? 'read' : 'unread']">
                        <div>
                            <span class="status ok">{{ notificationLabel(notification.type) }}</span>
                            <h3>{{ notification.title }}</h3>
                            <p>{{ notification.message }}</p>
                            <small>{{ notification.channel }} - {{ formatDate(notification.created_at) }}</small>
                        </div>
                        <button v-if="!notification.read_at" class="btn btn-light" type="button" @click="markNotificationRead(notification)">
                            <i class="fa-solid fa-check"></i>Lu
                        </button>
                    </article>
                    <p v-if="!notifications.length" class="empty">Aucune notification pour le moment.</p>
                </div>
            </section>

            <section v-if="activeSection === 'settings'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Paramètres boutique</h2>
                        <p>Ces informations apparaissent sur les factures, rapports et documents imprimables.</p>
                    </div>
                </div>

                <form class="settings-form" @submit.prevent="saveSettings">
                    <label>Nom de la boutique
                        <input v-model="settingsForm.name" type="text" required>
                    </label>
                    <label>Téléphone
                        <input v-model="settingsForm.phone" type="tel" required>
                    </label>
                    <label>WhatsApp
                        <input v-model="settingsForm.whatsapp_phone" type="tel" placeholder="Ex. 019622860">
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
                    <label>Couleur principale
                        <input v-model="settingsForm.primary_color" type="color">
                    </label>
                    <div class="settings-checks">
                        <label><input v-model="settingsForm.show_ifu_on_documents" type="checkbox">Afficher l'IFU</label>
                        <label><input v-model="settingsForm.show_slogan_on_documents" type="checkbox">Afficher le slogan</label>
                        <label><input v-model="settingsForm.show_address_on_documents" type="checkbox">Afficher l'adresse</label>
                    </div>
                    <button class="btn btn-primary" type="submit" :disabled="savingSettings">
                        <i class="fa-solid fa-floppy-disk"></i>{{ savingSettings ? 'Enregistrement...' : 'Enregistrer les paramètres' }}
                    </button>
                </form>
                <p v-if="settingsMessage" class="message">{{ settingsMessage }}</p>
            </section>

            <section v-if="activeSection === 'stocks' || activeSection === 'sales'" class="content-grid">
                <article v-if="activeSection === 'stocks'" class="card">
                    <div class="section-title">
                        <div>
                            <h2>Ajouter un produit</h2>
                            <p>Renseignez les prix, le stock initial et le seuil d'alerte.</p>
                        </div>
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
                        <button class="btn btn-primary" type="submit" :disabled="saving">
                            <i class="fa-solid fa-plus"></i>{{ saving ? 'Enregistrement...' : 'Ajouter le produit' }}
                        </button>
                    </form>

                    <p v-if="message" class="message">{{ message }}</p>
                </article>

                <article v-if="activeSection === 'sales'" class="card">
                    <div class="section-title">
                        <div>
                            <h2>Nouvelle vente</h2>
                            <p>Sélectionnez un produit, ajoutez-le au panier, puis validez la facture.</p>
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
                            <input v-model="saleForm.customer_phone" type="tel" placeholder="Optionnel">
                        </label>
                        <label>Mode de paiement
                            <select v-model="saleForm.payment_method">
                                <option value="cash">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="credit">Crédit</option>
                            </select>
                        </label>
                        <label v-if="saleForm.payment_method === 'credit'">Échéance crédit
                            <input v-model="saleForm.credit_due_date" type="date" required>
                        </label>
                        <div class="sale-total">
                            <span>Total</span>
                            <strong>{{ formatMoney(cartTotal) }}</strong>
                        </div>
                        <button class="btn btn-primary" type="submit" :disabled="savingSale || !cart.length">
                            <i class="fa-solid fa-file-invoice"></i>{{ savingSale ? 'Validation...' : 'Valider la facture' }}
                        </button>
                    </form>

                    <p v-if="saleMessage" class="message">{{ saleMessage }}</p>
                </article>
            </section>

            <section v-if="activeSection === 'stocks'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Stocks enregistrés</h2>
                        <p>Les produits ajoutés ici seront utilisés par la caisse et les factures.</p>
                    </div>
                    <button class="btn btn-light" type="button" @click="loadDashboard"><i class="fa-solid fa-rotate"></i>Actualiser</button>
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
                            <tr v-for="product in products" :key="product.id">
                                <td><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></td>
                                <td>{{ product.category?.name || 'Non classé' }}</td>
                                <td>{{ product.stock_quantity }}</td>
                                <td>{{ product.alert_threshold }}</td>
                                <td>{{ formatMoney(product.purchase_price) }}</td>
                                <td>{{ formatMoney(product.sale_price) }}</td>
                                <td><span :class="['status', isLow(product) ? 'danger' : 'ok']">{{ isLow(product) ? 'Stock bas' : 'OK' }}</span></td>
                            </tr>
                            <tr v-if="!products.length">
                                <td colspan="7" class="empty">Aucun produit enregistré pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="activeSection === 'sales'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Dernières factures</h2>
                        <p>Chaque vente validée décrémente automatiquement le stock.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Facture</th>
                                <th>Paiement</th>
                                <th>Statut</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Facture</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in sales" :key="sale.id">
                                <td><strong>{{ sale.number }}</strong><small>{{ sale.items.length }} ligne(s)</small></td>
                                <td>{{ paymentLabel(sale.payment_method) }}</td>
                                <td><span class="status ok">{{ sale.status }}</span></td>
                                <td>{{ formatMoney(sale.total) }}</td>
                                <td>{{ formatDate(sale.sold_at) }}</td>
                                <td><a class="table-icon" :href="`/businesses/${businessId}/sales/${sale.id}/invoice`" target="_blank" title="Voir la facture" aria-label="Voir la facture"><i class="fa-solid fa-file-invoice"></i></a></td>
                            </tr>
                            <tr v-if="!sales.length">
                                <td colspan="6" class="empty">Aucune vente enregistrée pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="activeSection === 'customers'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Créances</h2>
                        <p>Montants dus, factures associées, relances et paiements clients.</p>
                    </div>
                    <div class="section-actions">
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
                                        <button class="table-icon" type="button" :disabled="!receivable.remaining" @click="openReceivablePayment(receivable)" title="Ajouter un paiement" aria-label="Ajouter un paiement">
                                            <i class="fa-solid fa-money-bill"></i>
                                        </button>
                                        <a v-if="receivable.status === 'overdue' && receivable.customer?.phone" class="table-icon whatsapp" :href="receivableWhatsappUrl(receivable)" target="_blank" title="Relancer sur WhatsApp" aria-label="Relancer sur WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                        <button v-else class="table-icon" type="button" disabled title="Relance WhatsApp indisponible" aria-label="Relance WhatsApp indisponible">
                                            <i class="fa-brands fa-whatsapp"></i>
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
                <p v-if="receivableMessage" class="message">{{ receivableMessage }}</p>
            </section>

            <section v-if="activeSection === 'customers'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Clients</h2>
                        <p>Fiches clients, achats, factures et situation des comptes.</p>
                    </div>
                </div>

                <div class="client-cards">
                    <article v-for="client in customerSummaries" :key="client.key" class="client-card">
                        <div class="client-card-head">
                            <div>
                                <h3>{{ client.name }}</h3>
                                <p>{{ client.phone || 'Téléphone non renseigné' }}</p>
                            </div>
                            <span :class="['status', client.hasDebt ? 'danger' : 'ok']">{{ client.situation }}</span>
                        </div>
                        <div class="client-metrics">
                            <div>
                                <span>Achats</span>
                                <strong>{{ formatMoney(client.totalPurchases) }}</strong>
                            </div>
                            <div>
                                <span>Créances</span>
                                <strong>{{ formatMoney(client.remainingDebt) }}</strong>
                            </div>
                        </div>
                        <div class="invoice-list">
                            <strong>Factures</strong>
                            <div v-for="invoice in client.invoices" :key="invoice.id" class="invoice-row">
                                <a :href="`/businesses/${businessId}/sales/${invoice.id}/invoice`" target="_blank">{{ invoice.number }}</a>
                                <span>{{ formatMoney(invoice.total) }}</span>
                                <small>{{ formatDate(invoice.sold_at) }} - {{ paymentLabel(invoice.payment_method) }}</small>
                                <small v-if="invoice.items.length">{{ invoiceDetails(invoice) }}</small>
                            </div>
                            <p v-if="!client.invoices.length" class="empty small-empty">Aucune facture récente.</p>
                        </div>
                    </article>
                    <p v-if="!customerSummaries.length" class="empty">Aucun client enregistré pour le moment.</p>
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
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-check"></i>Enregistrer
                            </button>
                        </div>
                    </form>
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

            <section v-if="activeSection === 'suppliers'" class="content-grid">
                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>Ajouter une dette fournisseur</h2>
                            <p>Enregistrez les montants dus et les échéances pour éviter les oublis.</p>
                        </div>
                    </div>

                    <form class="supplier-form" @submit.prevent="saveSupplierDebt">
                        <label>Fournisseur
                            <input v-model="supplierDebtForm.supplier_name" type="text" required placeholder="Ex. Grossiste Cotonou">
                        </label>
                        <label>Téléphone
                            <input v-model="supplierDebtForm.supplier_phone" type="tel" placeholder="Optionnel">
                        </label>
                        <label>Conditions
                            <input v-model="supplierDebtForm.payment_terms" type="text" placeholder="Ex. paiement sous 15 jours">
                        </label>
                        <label>Montant dû
                            <input v-model.number="supplierDebtForm.amount_due" type="number" required min="1">
                        </label>
                        <label>Échéance
                            <input v-model="supplierDebtForm.due_date" type="date">
                        </label>
                        <button class="btn btn-primary" type="submit" :disabled="savingSupplierDebt">
                            <i class="fa-solid fa-plus"></i>{{ savingSupplierDebt ? 'Enregistrement...' : 'Ajouter la dette' }}
                        </button>
                    </form>
                    <p v-if="supplierDebtMessage" class="message">{{ supplierDebtMessage }}</p>
                </article>

                <article class="card payment-card">
                    <i class="fa-solid fa-truck"></i>
                    <h2>Suivi fournisseurs</h2>
                    <p>Chaque dette fournisseur peut être remboursée en plusieurs paiements. Les statuts passent automatiquement à <strong>Payée</strong> ou <strong>En retard</strong> selon la situation.</p>
                </article>
            </section>

            <section v-if="activeSection === 'suppliers'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Fournisseurs & dettes</h2>
                        <p>Suivez les commandes reçues, les montants dus et les paiements effectués.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fournisseur</th>
                                <th>Montant dû</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Échéance</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="debt in supplierDebts" :key="debt.id">
                                <td><strong>{{ debt.supplier?.name || 'Fournisseur' }}</strong><small>{{ debt.supplier?.phone || 'Téléphone non renseigné' }}</small></td>
                                <td>{{ formatMoney(debt.amount_due) }}</td>
                                <td>{{ formatMoney(debt.amount_paid) }}</td>
                                <td>{{ formatMoney(debt.remaining) }}</td>
                                <td>{{ formatDateOnly(debt.due_date) }}</td>
                                <td><span :class="['status', receivableStatusClass(debt.status)]">{{ receivableStatusLabel(debt.status) }}</span></td>
                                <td>
                                    <form class="inline-payment" @submit.prevent="paySupplierDebt(debt)">
                                        <input v-model.number="debt.payment_amount" type="number" min="1" :max="debt.remaining" placeholder="Montant">
                                        <select v-model="debt.payment_method">
                                            <option value="cash">Espèces</option>
                                            <option value="mobile_money">Mobile Money</option>
                                        </select>
                                        <button class="table-icon" type="submit" :disabled="!debt.remaining" title="Enregistrer le paiement" aria-label="Enregistrer le paiement"><i class="fa-solid fa-money-bill"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <tr v-if="!supplierDebts.length">
                                <td colspan="7" class="empty">Aucune dette fournisseur pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

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

            <section v-if="activeSection === 'employees'" class="content-grid">
                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>Ajouter un employé</h2>
                            <p>Créez les fiches du personnel pour préparer la paie.</p>
                        </div>
                    </div>

                    <form class="employee-form" @submit.prevent="saveEmployee">
                        <label>Nom
                            <input v-model="employeeForm.name" type="text" required placeholder="Nom complet">
                        </label>
                        <label>Poste
                            <input v-model="employeeForm.position" type="text" required placeholder="Ex. Vendeur">
                        </label>
                        <label>Type
                            <select v-model="employeeForm.type" required>
                                <option value="seller">Vendeur</option>
                                <option value="cashier">Caissier</option>
                                <option value="accountant">Comptable</option>
                                <option value="observer">Observateur</option>
                            </select>
                        </label>
                        <label>Salaire mensuel
                            <input v-model.number="employeeForm.salary" type="number" required min="0">
                        </label>
                        <label>Date d'embauche
                            <input v-model="employeeForm.hired_at" type="date">
                        </label>
                        <button class="btn btn-primary" type="submit" :disabled="savingEmployee">
                            <i class="fa-solid fa-user-plus"></i>{{ savingEmployee ? 'Enregistrement...' : "Ajouter l'employé" }}
                        </button>
                    </form>
                    <p v-if="employeeMessage" class="message">{{ employeeMessage }}</p>
                </article>

                <article class="card">
                    <div class="section-title">
                        <div>
                            <h2>Avance & paie</h2>
                            <p>Enregistrez une avance, puis générez la paie du mois.</p>
                        </div>
                    </div>

                    <form class="employee-form" @submit.prevent="saveAdvance">
                        <label>Employé
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
                        <button class="btn btn-light" type="submit"><i class="fa-solid fa-hand-holding-dollar"></i>Enregistrer avance</button>
                    </form>

                    <form class="employee-form payroll-form" @submit.prevent="generatePayroll">
                        <label>Employé
                            <select v-model="payrollForm.employee_id" required>
                                <option value="">Choisir</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                            </select>
                        </label>
                        <label>Période
                            <input v-model="payrollForm.period" type="month" required>
                        </label>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-file-invoice-dollar"></i>Calculer paie</button>
                    </form>
                    <p v-if="payrollMessage" class="message">{{ payrollMessage }}</p>
                </article>
            </section>

            <section v-if="activeSection === 'employees'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Personnel & paie</h2>
                        <p>Suivi des employés, avances et paies générées.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Poste</th>
                                <th>Type</th>
                                <th>Salaire</th>
                                <th>Embauche</th>
                                <th>Avances récentes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="employee in employees" :key="employee.id">
                                <td><strong>{{ employee.name }}</strong></td>
                                <td>{{ employee.position }}</td>
                                <td><span class="status ok">{{ employeeTypeLabel(employee.type) }}</span></td>
                                <td>{{ formatMoney(employee.salary) }}</td>
                                <td>{{ formatDateOnly(employee.hired_at) }}</td>
                                <td>{{ formatMoney((employee.advances || []).reduce((sum, item) => sum + Number(item.amount), 0)) }}</td>
                            </tr>
                            <tr v-if="!employees.length">
                                <td colspan="6" class="empty">Aucun employé enregistré pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-wrap payroll-table">
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
                            <tr v-for="payroll in payrolls" :key="payroll.id">
                                <td>{{ payroll.period }}</td>
                                <td>{{ payroll.employee?.name }}</td>
                                <td>{{ formatMoney(payroll.gross_salary) }}</td>
                                <td>{{ formatMoney(payroll.salary_advance) }}</td>
                                <td><strong>{{ formatMoney(payroll.net_salary) }}</strong></td>
                                <td><span class="status ok">{{ payroll.status }}</span></td>
                            </tr>
                            <tr v-if="!payrolls.length">
                                <td colspan="6" class="empty">Aucune paie générée pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="activeSection === 'reports'" class="card">
                <div class="section-title">
                    <div>
                        <h2>Rapports</h2>
                        <p>Générez les rapports journalier, hebdomadaire et mensuel avec ventes, charges, créances, dettes et paies.</p>
                    </div>
                </div>
                <div class="report-actions">
                    <a class="btn btn-light" :href="`/businesses/${businessId}/reports/daily`" target="_blank"><i class="fa-solid fa-calendar-day"></i>Rapport journalier</a>
                    <a class="btn btn-light" :href="`/businesses/${businessId}/reports/weekly`" target="_blank"><i class="fa-solid fa-calendar-week"></i>Rapport hebdomadaire</a>
                    <a class="btn btn-primary" :href="`/businesses/${businessId}/reports/monthly`" target="_blank"><i class="fa-solid fa-calendar-days"></i>Rapport mensuel</a>
                    <a class="btn btn-light" :href="`/businesses/${businessId}/exports/products`"><i class="fa-solid fa-file-csv"></i>Export produits</a>
                    <a class="btn btn-light" :href="`/businesses/${businessId}/exports/sales`"><i class="fa-solid fa-file-csv"></i>Export ventes</a>
                    <a class="btn btn-light" :href="`/businesses/${businessId}/exports/expenses`"><i class="fa-solid fa-file-csv"></i>Export charges</a>
                </div>
            </section>

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
const receivables = ref([]);
const supplierDebts = ref([]);
const expenses = ref([]);
const employees = ref([]);
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
const saving = ref(false);
const savingSale = ref(false);
const message = ref('');
const saleMessage = ref('');
const receivableMessage = ref('');
const supplierDebtMessage = ref('');
const savingSupplierDebt = ref(false);
const expenseMessage = ref('');
const savingExpense = ref(false);
const showExpenseModal = ref(false);
const showExpenseDateModal = ref(false);
const showExpensePrintModal = ref(false);
const showExpenseFilterCategoryMenu = ref(false);
const showExpenseFormCategoryMenu = ref(false);
const expenseDateMode = ref('exact');
const showReceivableDateModal = ref(false);
const showReceivablePrintModal = ref(false);
const showReceivablePaymentModal = ref(false);
const receivableDateMode = ref('exact');
const selectedReceivable = ref(null);
const employeeMessage = ref('');
const payrollMessage = ref('');
const savingEmployee = ref(false);
const savingSubscription = ref(false);
const savingSettings = ref(false);
const subscriptionMessage = ref('');
const settingsMessage = ref('');
const dashboardLoaded = ref(false);
const toasts = ref([]);
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
};
const activeSection = ref(routeSectionMap[props.initialSection] || 'dashboard');
const sidebarOpen = ref(false);
const pageMeta = {
    dashboard: ['Tableau de bord', 'Vos statistiques essentielles.'],
    notifications: ['Notifications', 'Alertes, validations et messages importants.'],
    stocks: ['Stocks', "Produits, inventaire et seuils d'alerte."],
    sales: ['Factures & ventes', 'Caisse, panier et dernières factures.'],
    customers: ['Clients & créances', 'Crédits clients, échéances et remboursements.'],
    suppliers: ['Fournisseurs & dettes', 'Dettes fournisseurs, échéances et paiements.'],
    expenses: ['Charges', 'Dépenses classées par catégories OHADA.'],
    employees: ['Personnel & paie', 'Employés, avances et paies.'],
    reports: ['Rapports', 'Exports et rapports PDF ou Excel.'],
    taxes: ['Impôts & comptabilité', 'Bilan estimé et repères comptables.'],
    settings: ['Paramètres boutique', 'Informations affichées sur vos documents.'],
};
const pageTitle = computed(() => pageMeta[activeSection.value]?.[0] || 'Tableau de bord');
const pageSubtitle = computed(() => pageMeta[activeSection.value]?.[1] || 'Vos statistiques essentielles.');

function sectionUrl(section) {
    return `/dashboard/${props.businessId}/${section}`;
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

const saleLine = reactive({
    product_id: '',
    quantity: 1,
    discount: '',
});

const saleForm = reactive({
    customer_name: '',
    customer_phone: '',
    payment_method: 'cash',
    credit_due_date: '',
});

const cart = ref([]);

const supplierDebtForm = reactive({
    supplier_name: '',
    supplier_phone: '',
    payment_terms: '',
    amount_due: '',
    due_date: '',
});

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

const employeeForm = reactive({
    name: '',
    position: '',
    type: 'seller',
    salary: '',
    hired_at: '',
});

const advanceForm = reactive({
    employee_id: '',
    amount: '',
    advanced_on: new Date().toISOString().slice(0, 10),
    notes: '',
});

const payrollForm = reactive({
    employee_id: '',
    period: new Date().toISOString().slice(0, 7),
});

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
    show_ifu_on_documents: true,
    show_slogan_on_documents: true,
    show_address_on_documents: true,
});

const availableProducts = computed(() => products.value.filter((product) => Number(product.stock_quantity) > 0));

const cartTotal = computed(() => cart.value.reduce((total, item) => total + item.total, 0));
const unreadNotifications = computed(() => notifications.value.filter((item) => !item.read_at).length);
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
const receivableDateFilterLabel = computed(() => {
    if (receivableFilters.exactDate) {
        return receivableFilters.exactDate;
    }

    if (receivableFilters.startDate || receivableFilters.endDate) {
        return `${receivableFilters.startDate || 'Début'} - ${receivableFilters.endDate || 'Fin'}`;
    }

    return 'Choisir';
});
const customerSummaries = computed(() => {
    const customers = new Map();
    const ensureCustomer = (customer, fallbackName = 'Client comptoir') => {
        const key = customer?.id ? `customer-${customer.id}` : `anonymous-${fallbackName}`;
        if (!customers.has(key)) {
            customers.set(key, {
                key,
                id: customer?.id || null,
                name: customer?.name || fallbackName,
                phone: customer?.phone || '',
                totalPurchases: 0,
                remainingDebt: 0,
                hasDebt: false,
                invoices: [],
                situation: 'Client OK',
            });
        }

        return customers.get(key);
    };

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
    });

    return [...customers.values()]
        .map((customer) => ({
            ...customer,
            situation: customer.hasDebt ? 'Créances en cours' : 'Client OK',
            invoices: customer.invoices.sort((a, b) => String(b.sold_at || '').localeCompare(String(a.sold_at || ''))),
        }))
        .sort((a, b) => a.name.localeCompare(b.name, 'fr'));
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
            const count = table.querySelectorAll('tbody tr:not(:has(.empty))').length;
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

watch([filteredExpenses, filteredReceivables], () => {
    enhanceTables();
}, { flush: 'post' });

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
    const customerName = receivable.customer?.name || 'cher client';
    const invoice = receivable.invoice?.number ? ` liée à la facture ${receivable.invoice.number}` : '';
    const message = `Bonjour ${customerName}, votre échéance${invoice} est dépassée. Il reste ${formatMoney(receivable.remaining)} à régler. Merci de passer au paiement.`;

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

async function loadDashboard() {
    dashboardLoaded.value = false;
    const response = await fetch(`/api/businesses/${props.businessId}/dashboard`);
    const data = await response.json();

    business.value = data.business;
    currentUser.value = data.current_user || {};
    Object.assign(settingsForm, {
        name: data.business?.name || '',
        phone: data.business?.phone || '',
        whatsapp_phone: data.business?.whatsapp_phone || '',
        address: data.business?.address || '',
        ifu: data.business?.ifu || '',
        slogan: data.business?.slogan || '',
        primary_color: data.business?.primary_color || '#2f7d69',
        show_ifu_on_documents: Boolean(data.business?.show_ifu_on_documents),
        show_slogan_on_documents: Boolean(data.business?.show_slogan_on_documents),
        show_address_on_documents: Boolean(data.business?.show_address_on_documents),
    });
    subscription.value = data.subscription;
    subscriptionPlans.value = data.subscription_plans || {};
    if (subscription.value?.plan) {
        subscriptionForm.plan = subscription.value.plan;
    }
    if (subscription.value?.deposit_phone) {
        subscriptionForm.deposit_phone = subscription.value.deposit_phone;
    }
    products.value = data.products;
    sales.value = data.sales;
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

async function saveSettings() {
    savingSettings.value = true;
    settingsMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/settings`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(settingsForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Paramètres invalides.');
        }

        business.value = await response.json();
        settingsMessage.value = 'Paramètres boutique enregistrés.';
        notifySuccess(settingsMessage.value);
    } catch (error) {
        settingsMessage.value = error.message;
        notifyError(settingsMessage.value);
    } finally {
        savingSettings.value = false;
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
    } catch (error) {
        message.value = error.message;
        notifyError(message.value);
    } finally {
        saving.value = false;
    }
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
            credit_due_date: '',
        });
        saleMessage.value = 'Facture enregistrée avec succès.';
        notifySuccess(saleMessage.value);
        await loadDashboard();
    } catch (error) {
        saleMessage.value = error.message;
        notifyError(saleMessage.value);
    } finally {
        savingSale.value = false;
    }
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

    printWindow.document.write(`
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Créances clients affichées</title>
            <style>
                body{font-family:Poppins,Arial,sans-serif;color:#17211b;padding:24px}
                h1{margin:0 0 6px}
                p{margin:0 0 18px;color:#52635b}
                table{width:100%;border-collapse:collapse}
                th,td{border:1px solid #dfe7e2;padding:9px;text-align:left}
                th{background:#10251f;color:white}
                tfoot td{font-weight:800}
            </style>
        </head>
        <body>
            <h1>Créances clients affichées</h1>
            <p>Total restant : ${formatMoney(filteredReceivablesTotal.value)}</p>
            <table>
                <thead><tr><th>Échéance</th><th>Client</th><th>Téléphone</th><th>Montant dû</th><th>Payé</th><th>Reste</th><th>Statut</th></tr></thead>
                <tbody>${printableRows}</tbody>
                <tfoot><tr><td colspan="5">Total restant</td><td colspan="2">${formatMoney(filteredReceivablesTotal.value)}</td></tr></tfoot>
            </table>
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
    notifySuccess('Impression PDF lancée.');
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
            supplier_name: '',
            supplier_phone: '',
            payment_terms: '',
            amount_due: '',
            due_date: '',
        });
        supplierDebtMessage.value = 'Dette fournisseur enregistrée.';
        notifySuccess(supplierDebtMessage.value);
        await loadDashboard();
    } catch (error) {
        supplierDebtMessage.value = error.message;
        notifyError(supplierDebtMessage.value);
    } finally {
        savingSupplierDebt.value = false;
    }
}

async function paySupplierDebt(debt) {
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

    printWindow.document.write(`
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Charges affichées</title>
            <style>
                body{font-family:Poppins,Arial,sans-serif;color:#17211b;padding:24px}
                h1{margin:0 0 6px}
                p{margin:0 0 18px;color:#52635b}
                table{width:100%;border-collapse:collapse}
                th,td{border:1px solid #dfe7e2;padding:9px;text-align:left}
                th{background:#10251f;color:white}
                tfoot td{font-weight:800}
            </style>
        </head>
        <body>
            <h1>Charges affichées</h1>
            <p>Total : ${formatMoney(filteredExpensesTotal.value)}</p>
            <table>
                <thead><tr><th>Date</th><th>Charge</th><th>Catégorie</th><th>Montant</th><th>Notes</th></tr></thead>
                <tbody>${printableRows}</tbody>
                <tfoot><tr><td colspan="3">Total</td><td colspan="2">${formatMoney(filteredExpensesTotal.value)}</td></tr></tfoot>
            </table>
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
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
        const response = await fetch(`/api/businesses/${props.businessId}/employees`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(employeeForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Employé invalide.');
        }

        Object.assign(employeeForm, {
            name: '',
            position: '',
            type: 'seller',
            salary: '',
            hired_at: '',
        });
        employeeMessage.value = 'Employé enregistré.';
        notifySuccess(employeeMessage.value);
        await loadDashboard();
    } catch (error) {
        employeeMessage.value = error.message;
        notifyError(employeeMessage.value);
    } finally {
        savingEmployee.value = false;
    }
}

async function saveAdvance() {
    payrollMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/employees/${advanceForm.employee_id}/advances`, {
            method: 'POST',
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
        payrollMessage.value = 'Avance enregistrée.';
        notifySuccess(payrollMessage.value);
        await loadDashboard();
    } catch (error) {
        payrollMessage.value = error.message;
        notifyError(payrollMessage.value);
    }
}

async function generatePayroll() {
    payrollMessage.value = '';

    try {
        const response = await fetch(`/api/businesses/${props.businessId}/payrolls`, {
            method: 'POST',
            headers: requestHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payrollForm),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Paie invalide.');
        }

        payrollMessage.value = 'Paie générée.';
        notifySuccess(payrollMessage.value);
        await loadDashboard();
    } catch (error) {
        payrollMessage.value = error.message;
        notifyError(payrollMessage.value);
    }
}

onMounted(loadDashboard);
</script>


