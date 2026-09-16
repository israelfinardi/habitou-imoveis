/* =========================================================
   CUBONET · site institucional — v2
   Dados de contato, redes e preços copiados do site atual
   (cubonet.net.br). Reconfira antes de publicar, pois provedores
   costumam reajustar preços e fidelidade com frequência.
   ========================================================= */
const DATA = {
  /* ---- WhatsApp ---- */
  whatsappNumber: "554792573822",
  whatsappMsgPadrao: "Olá! Vim pelo site e quero saber mais sobre os planos de internet da CuboNET.",

  /* ---- Telefone ---- */
  telefone: "+554792573822",

  /* ---- Redes sociais ----
     null = o botão sai da grade (regra do template original:
     nunca mostrar link morto nem dado inventado). */
  instagram: "https://www.instagram.com/cubonet/",
  facebook: "https://www.facebook.com/cubonetworks",
  telegram: "https://t.me/cubonetworks",
  twitter: "https://twitter.com/cubonet",

  /* ---- E-mail ---- */
  email: "contato@cubonet.net.br",

  /* ---- Área do cliente (portal de boletos e suporte) ---- */
  areaClienteUrl: "http://186.233.53.238:1006/central/login.hhvm",

  /* ---- Teste de velocidade ---- */
  speedtestUrl: "https://www.speedtest.net/",

  /* ---- Endereço / mapa ---- */
  enderecoMapaUrl:
    "https://www.google.com/maps/search/?api=1&query=Rua+Germano+Niehues%2C+628%2C+Schreiber%2C+Salete+-+SC",

  /* ---- Rodapé ---- */
  rodapeLegal: "CUBONET TELECOMUNICAÇÕES · CNPJ 07.478.184/0001-49 · © 2026 CuboNET — Todos os direitos reservados.",
};

/* =========================================================
   ENDPOINT DOS FORMULÁRIOS

   Cole aqui a URL /exec de um Web App do Google Apps Script (ou
   outro endpoint que aceite x-www-form-urlencoded) para gravar os
   contatos recebidos. Enquanto for o placeholder abaixo, o envio é
   SIMULADO: a interface responde normalmente, mas nada é gravado.
   ========================================================= */
const SCRIPT_URL = "COLE_AQUI_A_URL_DO_APPS_SCRIPT";
const SCRIPT_URL_ATIVO = /^https:\/\/script\.google\.com\//.test(SCRIPT_URL);

const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/* =========================================================
   HIDRATAÇÃO
   ========================================================= */
function hydrate() {
  const waHref = (msg) =>
    `https://wa.me/${DATA.whatsappNumber}?text=${encodeURIComponent(msg || DATA.whatsappMsgPadrao)}`;

  document.querySelectorAll("[data-whatsapp]").forEach((el) => {
    const msg = el.dataset.whatsappMsg || DATA.whatsappMsgPadrao;
    el.setAttribute("href", waHref(msg));
  });

  document.querySelectorAll("[data-mapa]").forEach((el) => {
    el.setAttribute("href", DATA.enderecoMapaUrl);
  });

  document.querySelectorAll("[data-area-cliente]").forEach((el) => {
    el.setAttribute("href", DATA.areaClienteUrl);
  });

  document.querySelectorAll("[data-speedtest]").forEach((el) => {
    el.setAttribute("href", DATA.speedtestUrl);
  });

  document.querySelectorAll("[data-telefone]").forEach((el) => {
    el.setAttribute("href", `tel:${DATA.telefone}`);
  });

  document.querySelectorAll("[data-email]").forEach((el) => {
    el.setAttribute("href", `mailto:${DATA.email}`);
  });

  [
    ["instagram", DATA.instagram],
    ["facebook", DATA.facebook],
    ["telegram", DATA.telegram],
    ["twitter", DATA.twitter],
  ].forEach(([key, url]) => {
    document.querySelectorAll(`[data-key="${key}"]`).forEach((el) => {
      if (url) el.setAttribute("href", url);
      else el.remove();
    });
  });

  const grid = document.getElementById("social-grid");
  if (grid) {
    if (!grid.children.length) grid.closest(".social")?.remove();
    else grid.dataset.socialCount = String(grid.children.length);
  }

  setText("rodapeLegal", DATA.rodapeLegal);
}

function setText(key, value) {
  const el = document.querySelector(`[data-key="${key}"]`);
  if (!el || value === null || value === undefined) return;
  el.textContent = value;
}

/* =========================================================
   REVELAÇÃO POR SCROLL
   ========================================================= */
function initReveal() {
  const els = document.querySelectorAll(".reveal");
  if (prefersReduced || !("IntersectionObserver" in window)) {
    els.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries
        .filter((e) => e.isIntersecting)
        .forEach((entry) => {
          const el = entry.target;
          const siblings = Array.from(el.parentElement.children).filter((c) =>
            c.classList.contains("reveal")
          );
          const idx = Math.max(0, siblings.indexOf(el));
          el.style.transitionDelay = `${Math.min(idx, 5) * 70}ms`;
          el.classList.add("is-visible");
          io.unobserve(el);
        });
    },
    { threshold: 0.15 }
  );

  els.forEach((el) => io.observe(el));
}

/* =========================================================
   ACORDEÕES (planos)
   ========================================================= */
function initAccordions() {
  document.querySelectorAll("[data-accordion]").forEach((acc) => {
    const head = acc.querySelector(".accordion__head");
    if (!head) return;
    head.addEventListener("click", () => {
      const open = head.getAttribute("aria-expanded") === "true";
      head.setAttribute("aria-expanded", String(!open));
    });
  });
}

/* =========================================================
   ABAS (CONTRATAR é a padrão)
   ========================================================= */
let selectTab = () => {};

function initTabs() {
  const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
  if (!tabs.length) return;

  const tablist = tabs[0].closest('[role="tablist"]');

  function select(tab) {
    const from = tabs.findIndex((t) => t.getAttribute("aria-selected") === "true");
    const to = tabs.indexOf(tab);
    if (to === from) return;

    const dir = to > from ? "right" : "left";
    if (tablist) tablist.dataset.active = String(to);

    tabs.forEach((t) => {
      const selected = t === tab;
      t.setAttribute("aria-selected", String(selected));
      t.tabIndex = selected ? 0 : -1;
      const panel = document.getElementById(t.getAttribute("aria-controls"));
      if (!panel) return;
      if (selected) panel.dataset.enter = dir;
      else delete panel.dataset.enter;
      panel.hidden = !selected;
    });
  }

  selectTab = (name) => {
    const tab = tabs.find((t) => t.dataset.tab === name);
    if (tab) select(tab);
  };

  tabs.forEach((tab, i) => {
    tab.addEventListener("click", () => select(tab));
    tab.addEventListener("keydown", (e) => {
      let next = null;
      if (e.key === "ArrowRight" || e.key === "ArrowDown") next = tabs[(i + 1) % tabs.length];
      else if (e.key === "ArrowLeft" || e.key === "ArrowUp")
        next = tabs[(i - 1 + tabs.length) % tabs.length];
      else if (e.key === "Home") next = tabs[0];
      else if (e.key === "End") next = tabs[tabs.length - 1];
      if (next) {
        e.preventDefault();
        select(next);
        next.focus();
      }
    });
  });

  document.querySelectorAll("[data-tab-target]").forEach((trigger) => {
    trigger.addEventListener("click", () => selectTab(trigger.dataset.tabTarget));
  });
}

/* =========================================================
   MODAIS (Termos / Privacidade)
   ========================================================= */
const modalStack = [];
const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function openModal(id, trigger) {
  const modal = document.getElementById(id);
  if (!modal || modalStack.some((e) => e.modal === modal)) return;

  modalStack.push({ modal, trigger: trigger || document.activeElement });
  modal.hidden = false;
  modal.style.zIndex = String(200 + modalStack.length * 10);
  document.body.dataset.modalOpen = "true";

  requestAnimationFrame(() => {
    modal.dataset.open = "true";
    const focusable = modal.querySelectorAll(FOCUSABLE);
    const first = Array.from(focusable).find((el) => !el.hasAttribute("data-close-modal"));
    (first || focusable[0] || modal).focus();
  });
}

function closeTopModal() {
  const entry = modalStack.pop();
  if (!entry) return;
  const { modal, trigger } = entry;

  modal.dataset.open = "false";
  const finish = () => {
    modal.hidden = true;
    modal.style.zIndex = "";
  };
  if (prefersReduced) finish();
  else setTimeout(finish, 280);

  if (!modalStack.length) delete document.body.dataset.modalOpen;
  if (trigger && typeof trigger.focus === "function") trigger.focus();
}

function initModals() {
  document.querySelectorAll("[data-open-modal]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(btn.dataset.openModal, btn);
    });
  });

  document.querySelectorAll("[data-close-modal]").forEach((btn) => {
    btn.addEventListener("click", () => closeTopModal());
  });

  document.addEventListener("keydown", (e) => {
    if (!modalStack.length) return;

    if (e.key === "Escape") {
      e.preventDefault();
      closeTopModal();
      return;
    }

    if (e.key !== "Tab") return;

    const { modal } = modalStack[modalStack.length - 1];
    const items = Array.from(modal.querySelectorAll(FOCUSABLE)).filter(
      (el) => el.offsetParent !== null || el === document.activeElement
    );
    if (!items.length) return;

    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    } else if (!modal.contains(document.activeElement)) {
      e.preventDefault();
      first.focus();
    }
  });
}

/* =========================================================
   MÁSCARA DE TELEFONE — (00) 00000-0000
   ========================================================= */
function maskPhone(value) {
  const d = value.replace(/\D/g, "").slice(0, 11);
  if (!d) return "";
  if (d.length <= 2) return `(${d}`;
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

function initMasks() {
  document.querySelectorAll('[data-mask="phone"]').forEach((input) => {
    input.addEventListener("input", () => {
      const atEnd = input.selectionStart === input.value.length;
      input.value = maskPhone(input.value);
      if (atEnd) input.setSelectionRange(input.value.length, input.value.length);
    });
  });
}

/* =========================================================
   BARRA FIXA
   ========================================================= */
function initTopbar() {
  const bar = document.getElementById("topbar");
  const hero = document.getElementById("hero");
  let lastY = window.scrollY;
  let ticking = false;

  function update() {
    ticking = false;
    const y = window.scrollY;
    const heroBottom = hero ? hero.offsetHeight - 70 : 0;
    if (bar) {
      bar.dataset.solid = String(y > heroBottom);
      bar.dataset.hidden = String(y > lastY && y > heroBottom + 120);
    }
    lastY = y;
  }

  window.addEventListener(
    "scroll",
    () => {
      if (!ticking) {
        requestAnimationFrame(update);
        ticking = true;
      }
    },
    { passive: true }
  );
  window.addEventListener("resize", update, { passive: true });
  update();
}

/* =========================================================
   FORMULÁRIOS — aceite obrigatório, validação e envio
   ========================================================= */
function initForms() {
  const isPhone = (v) => {
    const d = v.replace(/\D/g, "");
    return d.length >= 10 && d.length <= 11;
  };

  document.querySelectorAll("form[data-signup]").forEach((form) => {
    const btn = form.querySelector(".signup__btn");
    const status = form.querySelector(".signup__status");
    const consent = form.querySelector("[data-consent]");

    const setStatus = (msg, type) => {
      if (!status) return;
      status.textContent = msg;
      if (type) status.dataset.type = type;
      else status.removeAttribute("data-type");
    };

    const syncConsent = () => {
      if (!btn || !consent) return;
      btn.disabled = !consent.checked;
    };
    if (consent) {
      consent.addEventListener("change", () => {
        consent.closest(".consent")?.removeAttribute("data-invalid");
        syncConsent();
      });
      syncConsent();
    }

    form.addEventListener("input", (e) => {
      if (e.target.type === "checkbox") return;
      if (e.target.getAttribute("aria-invalid") === "true") {
        e.target.setAttribute("aria-invalid", "false");
        setStatus("", null);
      }
    });

    const fail = (field, msg) => {
      if (field) {
        field.setAttribute("aria-invalid", "true");
        field.focus();
      }
      setStatus(msg, "error");
    };

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const empty = Array.from(
        form.querySelectorAll("input[required], select[required], textarea[required]")
      ).find((f) => f.type !== "checkbox" && !f.value.trim());
      if (empty) return fail(empty, "Preencha os campos para enviar.");

      const phone = form.querySelector('[data-mask="phone"]');
      if (phone && !isPhone(phone.value)) {
        return fail(phone, "Confira o número de WhatsApp com DDD.");
      }

      if (consent && !consent.checked) {
        consent.closest(".consent")?.setAttribute("data-invalid", "true");
        consent.focus();
        setStatus("É preciso aceitar os termos para enviar.", "error");
        return;
      }

      btn.dataset.state = "loading";
      btn.disabled = true;
      setStatus("", null);

      try {
        const fd = new FormData(form);
        fd.append("submitted_at", new Date().toISOString());
        fd.append("assunto", form.dataset.assunto || "contato");
        if (consent && !fd.has("aceite")) fd.set("aceite", "false");

        const params = new URLSearchParams();
        for (const [key, value] of fd.entries()) params.append(key, value);

        if (SCRIPT_URL_ATIVO) {
          const res = await fetch(SCRIPT_URL, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
            body: params.toString(),
          });

          const text = await res.text();
          let out = null;
          try {
            out = JSON.parse(text);
          } catch (_) {
            out = null;
          }

          if (!res.ok) {
            throw new Error(`HTTP ${res.status} ${res.statusText} — ${text.slice(0, 200)}`);
          }
          if (out && out.ok === false) {
            throw new Error(out.error || "Endpoint retornou ok:false");
          }
        } else {
          await new Promise((r) => setTimeout(r, prefersReduced ? 200 : 800));
        }

        btn.dataset.state = "done";
        setStatus("Recebido! Nossa equipe entra em contato pelo WhatsApp informado.", null);
        form.reset();
        syncConsent();
        setTimeout(() => {
          btn.dataset.state = "idle";
        }, 2600);
      } catch (err) {
        console.error("Erro ao enviar formulário:", err);
        btn.dataset.state = "idle";
        btn.disabled = false;
        setStatus("Não foi possível enviar agora. Tente de novo em instantes.", "error");
      }
    });
  });
}

/* =========================================================
   INIT
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
  hydrate();
  initReveal();
  initAccordions();
  initTabs();
  initModals();
  initMasks();
  initTopbar();
  initForms();
});
