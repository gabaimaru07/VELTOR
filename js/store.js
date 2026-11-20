(() => {
  "use strict";

  const CART_KEY = "veltor.cart.v2";
  const readCart = () => {
    try {
      const saved = JSON.parse(localStorage.getItem(CART_KEY) || "{}");
      return saved && typeof saved === "object" && !Array.isArray(saved) ? saved : {};
    } catch (error) {
      console.error("Could not read the VELTOR cart.", error);
      return {};
    }
  };
  const saveCart = (cart) => {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCount(cart);
  };
  const updateCount = (cart = readCart()) => {
    const count = Object.values(cart).reduce((sum, quantity) => sum + Number(quantity || 0), 0);
    document.querySelectorAll("[data-cart-count]").forEach((node) => { node.textContent = String(count); });
  };
  if (document.body.dataset.clearCart === "1") localStorage.removeItem(CART_KEY);
  const addItem = (id, stock, quantity = 1) => {
    const cart = readCart();
    const current = Number(cart[id] || 0);
    if (current + quantity > Math.min(stock, 50)) {
      window.alert(document.documentElement.lang === "ar" ? "الكمية المطلوبة غير متوفرة." : document.documentElement.lang === "fr" ? "La quantité demandée n’est pas disponible." : "That quantity is not available.");
      return;
    }
    cart[id] = current + quantity;
    saveCart(cart);
    const button = document.querySelector(`[data-add-cart="${CSS.escape(String(id))}"]`);
    if (button) {
      const original = button.textContent;
      button.textContent = document.documentElement.lang === "ar" ? "تمت الإضافة" : document.documentElement.lang === "fr" ? "Ajouté" : "Added";
      window.setTimeout(() => { button.textContent = original; }, 1200);
    }
  };
  const formatMoney = (amount) => `${Number(amount).toLocaleString(document.documentElement.lang === "ar" ? "ar-MA" : document.documentElement.lang === "fr" ? "fr-MA" : "en-MA", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MAD`;

  function renderCart() {
    const host = document.querySelector("[data-cart-list]");
    if (!host) return;
    const cart = readCart();
    let subtotal = 0;
    let items = 0;
    host.querySelectorAll("[data-cart-row]").forEach((row) => {
      const id = row.dataset.id;
      const quantity = Math.min(Number(row.dataset.stock), 50, Number(cart[id] || 0));
      if (quantity < 1) {
        row.hidden = true;
        delete cart[id];
        return;
      }
      row.hidden = false;
      items += quantity;
      const line = Number(row.dataset.price) * quantity;
      subtotal += line;
      const field = row.querySelector("[data-quantity]");
      if (document.activeElement !== field) field.value = String(quantity);
      const total = row.querySelector("[data-line-total]");
      if (total) total.textContent = formatMoney(line);
    });
    saveCart(cart);
    const empty = document.querySelector("[data-cart-empty]");
    const summary = document.querySelector("[data-cart-summary]");
    if (empty) empty.hidden = items > 0;
    if (summary) summary.hidden = items === 0;
    document.querySelectorAll("[data-subtotal]").forEach((node) => { node.textContent = formatMoney(subtotal); });
    document.querySelectorAll("[data-total]").forEach((node) => { node.textContent = formatMoney(subtotal); });
  }

  function syncCheckout() {
    const form = document.querySelector("[data-checkout-form]");
    if (!form) return;
    const cart = readCart();
    const payload = Object.entries(cart)
      .map(([id, quantity]) => [Number(id), Number(quantity)])
      .filter(([id, quantity]) => Number.isInteger(id) && id > 0 && Number.isInteger(quantity) && quantity > 0);
    const input = form.elements.namedItem("cart_json");
    if (input) input.value = JSON.stringify(payload);
    const rows = document.querySelectorAll("[data-checkout-row]");
    let subtotal = 0;
    let count = 0;
    rows.forEach((row) => {
      const quantity = Number(cart[row.dataset.id] || 0);
      row.hidden = quantity < 1;
      if (quantity > 0) {
        count += quantity;
        subtotal += Number(row.dataset.price) * quantity;
        row.querySelector("[data-checkout-quantity]").textContent = String(quantity);
        row.querySelector("[data-checkout-line]").textContent = formatMoney(Number(row.dataset.price) * quantity);
      }
    });
    document.querySelectorAll("[data-checkout-total]").forEach((node) => { node.textContent = formatMoney(subtotal); });
    const submit = form.querySelector("[type=submit]");
    if (submit) submit.disabled = count === 0;
  }

  document.addEventListener("click", (event) => {
    const add = event.target.closest("[data-add-cart]");
    if (add) {
      const row = add.closest("[data-product-detail]");
      const quantityInput = row?.querySelector("[data-detail-quantity]");
      addItem(Number(add.dataset.addCart), Number(add.dataset.stock), Math.max(1, Number(quantityInput?.value || 1)));
      return;
    }
    const remove = event.target.closest("[data-remove-item]");
    if (remove) {
      const row = remove.closest("[data-cart-row]");
      const cart = readCart();
      delete cart[row.dataset.id];
      saveCart(cart);
      renderCart();
    }
    const menuButton = event.target.closest("[data-menu-toggle]");
    if (menuButton) {
      const menu = document.querySelector("[data-menu]");
      const open = menuButton.getAttribute("aria-expanded") !== "true";
      menuButton.setAttribute("aria-expanded", String(open));
      menu?.classList.toggle("is-open", open);
    }
  });
  document.addEventListener("input", (event) => {
    if (event.target.matches("[data-quantity]")) {
      const row = event.target.closest("[data-cart-row]");
      const quantity = Math.max(1, Math.min(Number(row.dataset.stock), 50, Math.floor(Number(event.target.value) || 1)));
      const cart = readCart();
      cart[row.dataset.id] = quantity;
      saveCart(cart);
      renderCart();
    }
  });
  document.addEventListener("change", (event) => {
    if (!event.target.matches("[data-image-input]")) return;
    const file = event.target.files?.[0];
    const preview = document.querySelector("[data-image-preview]");
    if (!file || !preview) return;
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 3 * 1024 * 1024) {
      event.target.value = "";
      window.alert(document.documentElement.lang === "ar" ? "اختر صورة JPG أو PNG أو WebP لا يتجاوز حجمها 3 ميغابايت." : document.documentElement.lang === "fr" ? "Choisissez une image JPG, PNG ou WebP de 3 Mo maximum." : "Choose a JPG, PNG, or WebP image up to 3 MB.");
      return;
    }
    if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
    preview.dataset.objectUrl = URL.createObjectURL(file);
    preview.src = preview.dataset.objectUrl;
    preview.hidden = false;
  });
  document.addEventListener("submit", (event) => {
    const confirmation = event.target.dataset.confirm;
    if (confirmation && !window.confirm(confirmation)) event.preventDefault();
    if (event.target.matches("[data-checkout-form]")) syncCheckout();
  });
  updateCount();
  renderCart();
  syncCheckout();
})();
