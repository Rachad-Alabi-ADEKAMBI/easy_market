(function () {
    const storageKey = 'easy_market_cookie_consent';

    if (window.__easyMarketCookieConsentLoaded) {
        return;
    }

    window.__easyMarketCookieConsentLoaded = true;

    const existingChoice = localStorage.getItem(storageKey);

    if (existingChoice) {
        return;
    }

    const style = document.createElement('style');
    style.textContent = `
        .cookie-consent {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 18px;
            z-index: 99999;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            max-width: 980px;
            margin: 0 auto;
            padding: 16px;
            border: 1px solid rgba(15,66,50,.14);
            border-radius: 14px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 22px 60px rgba(7,31,24,.22);
            color: #13231e;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .cookie-consent__text {
            display: grid;
            gap: 4px;
            line-height: 1.5;
        }
        .cookie-consent__title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            color: #0f4232;
        }
        .cookie-consent__title i {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #e1f3ee;
            color: #2f7d69;
        }
        .cookie-consent__text p {
            margin: 0;
            color: #5c6f67;
            font-size: 14px;
        }
        .cookie-consent__actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .cookie-consent__button {
            border: 0;
            border-radius: 10px;
            padding: 11px 15px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cookie-consent__button i {
            width: 16px;
            text-align: center;
            font-size: 15px;
        }
        .cookie-consent__button--accept {
            background: #2f7d69;
            color: white;
            box-shadow: 0 12px 28px rgba(47,125,105,.24);
        }
        .cookie-consent__button--reject {
            background: #eef5f2;
            color: #0f4232;
        }
        @media (max-width: 720px) {
            .cookie-consent {
                grid-template-columns: 1fr;
                left: 10px;
                right: 10px;
                bottom: 10px;
                padding: 14px;
            }
            .cookie-consent__actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
        }
    `;
    document.head.appendChild(style);

    const banner = document.createElement('section');
    banner.className = 'cookie-consent';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-live', 'polite');
    banner.setAttribute('aria-label', 'Préférences cookies');
    banner.innerHTML = `
        <div class="cookie-consent__text">
            <strong class="cookie-consent__title"><i class="fa-solid fa-cookie-bite" aria-hidden="true"></i>Cookies EasyMarket</strong>
            <p>Nous utilisons uniquement les cookies nécessaires au fonctionnement du site et à la mémorisation de votre choix. Vous pouvez accepter ou refuser les cookies optionnels.</p>
        </div>
        <div class="cookie-consent__actions">
            <button class="cookie-consent__button cookie-consent__button--reject" type="button" data-cookie-choice="rejected"><i class="fa-solid fa-ban" aria-hidden="true"></i>Refuser</button>
            <button class="cookie-consent__button cookie-consent__button--accept" type="button" data-cookie-choice="accepted"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Accepter</button>
        </div>
    `;

    const saveChoice = (choice) => {
        localStorage.setItem(storageKey, JSON.stringify({
            choice,
            savedAt: new Date().toISOString(),
        }));
        window.dispatchEvent(new CustomEvent('easyMarketCookieConsent', { detail: { choice } }));
        banner.remove();
    };

    banner.querySelectorAll('[data-cookie-choice]').forEach((button) => {
        button.addEventListener('click', () => saveChoice(button.dataset.cookieChoice));
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(banner);
    });
})();
