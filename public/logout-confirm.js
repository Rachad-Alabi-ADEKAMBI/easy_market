(function () {
    if (window.__easyMarketLogoutConfirmLoaded) {
        return;
    }

    window.__easyMarketLogoutConfirmLoaded = true;

    const style = document.createElement('style');
    style.textContent = `
        .logout-confirm-backdrop {
            position: fixed;
            inset: 0;
            z-index: 100000;
            display: none;
            place-items: center;
            padding: 18px;
            background: rgba(16, 37, 31, .54);
        }
        .logout-confirm-backdrop.open {
            display: grid;
        }
        .logout-confirm-modal {
            width: min(420px, 100%);
            border: 1px solid #dfe7e2;
            border-radius: 14px;
            background: #fff;
            padding: 22px;
            box-shadow: 0 22px 60px rgba(7, 31, 24, .24);
            color: #17211b;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .logout-confirm-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #ffe6df;
            color: #b42318;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .logout-confirm-modal h2 {
            margin: 0 0 8px;
            font-size: 24px;
            line-height: 1.15;
        }
        .logout-confirm-modal p {
            margin: 0;
            color: #52635b;
            line-height: 1.55;
        }
        .logout-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .logout-confirm-actions button {
            min-height: 42px;
            border: 0;
            border-radius: 9px;
            padding: 10px 14px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .logout-confirm-cancel {
            background: #eef5f2;
            color: #10251f;
        }
        .logout-confirm-submit {
            background: #b42318;
            color: white;
        }
    `;
    document.head.appendChild(style);

    const modal = document.createElement('div');
    modal.className = 'logout-confirm-backdrop';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <section class="logout-confirm-modal">
            <div class="logout-confirm-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></div>
            <h2>Confirmer la déconnexion</h2>
            <p>Voulez-vous vraiment vous déconnecter de votre espace EasyMarket ?</p>
            <div class="logout-confirm-actions">
                <button class="logout-confirm-cancel" type="button">Annuler</button>
                <button class="logout-confirm-submit" type="button">Déconnexion</button>
            </div>
        </section>
    `;

    let pendingForm = null;

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    }

    function openModal(form) {
        pendingForm = form;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(modal);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const action = form.getAttribute('action') || '';
        if (!action.endsWith('/deconnexion') || form.dataset.logoutConfirmed === '1') {
            return;
        }

        event.preventDefault();
        openModal(form);
    });

    modal.querySelector('.logout-confirm-cancel').addEventListener('click', closeModal);
    modal.querySelector('.logout-confirm-submit').addEventListener('click', () => {
        if (!pendingForm) {
            closeModal();
            return;
        }

        pendingForm.dataset.logoutConfirmed = '1';
        pendingForm.submit();
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
})();
