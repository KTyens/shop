(function () {
  const currencyKey = "crtlu-display-currency-v1";
  const localeKey = "crtlu-locale-v1";
  const couponKey = "crtlu-coupon-v1";

  const currencies = {
    USD: { label: "USD", symbol: "$", rate: 1, decimals: 2, suffix: "" },
    JPY: { label: "JPY", symbol: "¥", rate: 155, decimals: 0, suffix: " approx" },
    CNY: { label: "CNY", symbol: "¥", rate: 7.25, decimals: 2, suffix: " approx" },
    EUR: { label: "EUR", symbol: "€", rate: 0.93, decimals: 2, suffix: " approx" }
  };
  const locales = {
    en: "English",
    ja: "日本語",
    "zh-CN": "简体中文",
    "zh-TW": "繁體中文",
    es: "Español",
    pt: "Português",
    id: "Bahasa Indonesia",
    th: "ภาษาไทย",
    vi: "Tiếng Việt",
    ms: "Bahasa Melayu"
  };

  function read(key, fallback) {
    try {
      return localStorage.getItem(key) || fallback;
    } catch {
      return fallback;
    }
  }

  function write(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch {
      // Storage may be blocked in private previews.
    }
  }

  function getCurrency() {
    const value = read(currencyKey, "USD").toUpperCase();
    return currencies[value] ? value : "USD";
  }

  function getLocale() {
    const value = window.CRTLU_I18N ? window.CRTLU_I18N.normalize(read(localeKey, "en")) : read(localeKey, "en");
    return locales[value] ? value : "en";
  }

  function formatMoney(cents, currency) {
    const code = (currency || getCurrency()).toUpperCase();
    const meta = currencies[code] || currencies.USD;
    const amount = Number(cents || 0) / 100 * meta.rate;
    return `${meta.symbol}${amount.toFixed(meta.decimals)}${code === "USD" ? "" : " " + code}${meta.suffix}`;
  }

  function baseMoney(cents) {
    return `$${(Number(cents || 0) / 100).toFixed(2)}`;
  }

  function getCoupon() {
    try {
      const parsed = JSON.parse(localStorage.getItem(couponKey) || "null");
      return parsed && parsed.code ? parsed : null;
    } catch {
      return null;
    }
  }

  function setCoupon(coupon) {
    if (!coupon) {
      localStorage.removeItem(couponKey);
      return;
    }
    write(couponKey, JSON.stringify(coupon));
  }

  function discountCents(subtotal) {
    const coupon = getCoupon();
    if (!coupon) return 0;
    const cartSubtotal = Number(subtotal || 0);
    if (cartSubtotal < Number(coupon.min_subtotal_cents || 0)) return 0;
    let discount = Number(coupon.discount_cents || 0);
    if (coupon.type === "percent" && coupon.percent_off) {
      discount = Math.floor(cartSubtotal * Number(coupon.percent_off) / 100);
    } else if (coupon.type === "amount" && coupon.amount_off_cents) {
      discount = Number(coupon.amount_off_cents);
    }
    if (coupon.max_discount_cents) discount = Math.min(discount, Number(coupon.max_discount_cents));
    return Math.min(discount, Math.max(0, cartSubtotal - 100));
  }

  function checkoutMeta() {
    const coupon = getCoupon();
    return {
      coupon_code: coupon ? coupon.code : "",
      display_currency: getCurrency(),
      locale: getLocale()
    };
  }

  function initControls(options) {
    const currencySelect = document.getElementById("currencySelect");
    const localeSelect = document.getElementById("localeSelect");
    const couponInput = document.getElementById("couponCode");
    const applyCoupon = document.getElementById("applyCoupon");
    const clearCoupon = document.getElementById("clearCoupon");
    const couponStatus = document.getElementById("couponStatus");

    if (currencySelect) {
      currencySelect.innerHTML = Object.keys(currencies).map(code => `<option value="${code}">${currencies[code].label}</option>`).join("");
      currencySelect.value = getCurrency();
      currencySelect.addEventListener("change", () => {
        write(currencyKey, currencySelect.value);
        options.onChange && options.onChange();
      });
    }

    if (localeSelect) {
      const languageList = window.CRTLU_I18N?.languages || Object.keys(locales).map(code => ({ code, label: locales[code] }));
      localeSelect.innerHTML = languageList.map(language => `<option value="${language.code}">${language.label}</option>`).join("");
      localeSelect.value = getLocale();
      localeSelect.addEventListener("change", () => {
        if (window.CRTLU_I18N) window.CRTLU_I18N.setLocale(localeSelect.value);
        else write(localeKey, localeSelect.value);
        options.onChange && options.onChange();
      });
    }

    const existing = getCoupon();
    if (couponInput && existing) couponInput.value = existing.code;

    function setStatus(text, isError) {
      if (!couponStatus) return;
      couponStatus.textContent = text || "";
      couponStatus.className = isError ? "status error" : "status";
    }

    if (applyCoupon && couponInput) {
      applyCoupon.addEventListener("click", async () => {
        const code = couponInput.value.trim();
        if (!code) {
          setCoupon(null);
          setStatus(window.CRTLU_I18N?.t("coupon.enter") || "Enter a coupon code.", true);
          options.onChange && options.onChange();
          return;
        }
        setStatus(window.CRTLU_I18N?.t("coupon.checking") || "Checking coupon...", false);
        try {
          const response = await fetch(options.validateUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ coupon_code: code, items: options.getItems ? options.getItems() : [] })
          });
          const payload = await response.json();
          if (!response.ok || !payload.valid) throw new Error(payload.message || "Coupon is not valid.");
          setCoupon({
            ...payload.coupon,
            discount_cents: payload.discount_cents,
            currency: payload.currency
          });
          setStatus(payload.message || window.CRTLU_I18N?.t("coupon.applied") || "Coupon applied.", false);
          options.onChange && options.onChange();
        } catch (error) {
          setCoupon(null);
          setStatus(error.message, true);
          options.onChange && options.onChange();
        }
      });
    }

    if (clearCoupon && couponInput) {
      clearCoupon.addEventListener("click", () => {
        couponInput.value = "";
        setCoupon(null);
        setStatus(window.CRTLU_I18N?.t("coupon.removed") || "Coupon removed.", false);
        options.onChange && options.onChange();
      });
    }
  }

  function renderAdjustments(subtotal, shipping) {
    const discount = discountCents(subtotal);
    const line = document.getElementById("discountLine");
    const amount = document.getElementById("cartDiscount");
    const total = document.getElementById("cartTotal");
    if (line) line.hidden = discount < 1;
    if (amount) amount.textContent = discount > 0 ? `-${formatMoney(discount)}` : formatMoney(0);
    if (total) total.textContent = formatMoney(Math.max(0, subtotal - discount) + shipping);
    return discount;
  }

  window.CRTLU_PHASE4 = {
    baseMoney,
    checkoutMeta,
    discountCents,
    formatMoney,
    getCoupon,
    getCurrency,
    getLocale,
    initControls,
    renderAdjustments,
    setCoupon
  };
})();
