(function () {
  const APP_CONFIG = {
    appName: "Up-Skill",
    tagline: "Secure sessions, quick subscriptions, and smooth credit management.",
    playStoreUrl: "/Ploofoodle/public/index.php?_route=/app/open&platform=android&channel=stable",
    appStoreUrl: "/Ploofoodle/public/index.php?_route=/app/open&platform=ios&channel=stable",
    websiteBaseUrl: "./",
    supportEmail: "support@felixeladi.co.ke",
    whatsappLink: "/Ploofoodle/public/index.php?_route=/app/support",
    supportPageUrl: "/Ploofoodle/public/index.php?_route=/app/support",
    privacyUrl: "/Ploofoodle/public/app/privacy.html",
    termsUrl: "/Ploofoodle/public/app/terms.html",
    enableManualApk: false,
    manualApkUrl: "",
    openPath: "open.html",
    social: {
      x: "",
      facebook: "",
      youtube: ""
    }
  };

  window.APP_CONFIG = APP_CONFIG;

  function detectOS() {
    const ua = navigator.userAgent || navigator.vendor || window.opera;
    if (/android/i.test(ua)) return "android";
    if (/iPad|iPhone|iPod/.test(ua)) return "ios";
    if (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) return "ios";
    return "desktop";
  }

  function applyConfigTextAndLinks() {
    document.querySelectorAll("[data-config-text]").forEach((node) => {
      const key = node.getAttribute("data-config-text");
      const value = APP_CONFIG[key];
      if (value) node.textContent = value;
    });

    document.querySelectorAll("[data-config-link]").forEach((node) => {
      const key = node.getAttribute("data-config-link");
      const value = APP_CONFIG[key];
      if (!value) return;

      if (key === "supportEmail") {
        const href = String(value).startsWith("mailto:") ? String(value) : "mailto:" + String(value);
        node.setAttribute("href", href);
        if (node.hasAttribute("data-config-text")) {
          node.textContent = String(value).replace(/^mailto:/, "");
        }
        return;
      }

      node.setAttribute("href", value);
    });
  }

  function setStoreButtons(os) {
    const buttons = document.querySelectorAll(".store-btn");
    buttons.forEach((button) => {
      const platform = button.getAttribute("data-platform");
      button.classList.remove("is-primary", "is-secondary");
      if (os === "desktop") {
        button.classList.add("is-primary");
      } else if (platform === os) {
        button.classList.add("is-primary");
      } else {
        button.classList.add("is-secondary");
      }
    });
  }

  function buildOpenLink() {
    const base = APP_CONFIG.websiteBaseUrl || "./";
    const cleanBase = base.endsWith("/") ? base : base + "/";
    return cleanBase + APP_CONFIG.openPath;
  }

  function renderQrPlaceholder() {
    const text = document.getElementById("open-link-text");
    if (!text) return;
    text.textContent = buildOpenLink();
  }

  function setupManualApkVisibility() {
    const block = document.getElementById("manual-apk");
    if (!block) return;
    if (APP_CONFIG.enableManualApk) {
      block.classList.remove("hidden");
      const link = block.querySelector("[data-manual-apk-link]");
      if (link && APP_CONFIG.manualApkUrl) {
        link.href = APP_CONFIG.manualApkUrl;
      }
    }
  }

  function appendAllowedParams(url) {
    try {
      const current = new URL(window.location.href);
      const out = new URL(url, window.location.href);
      ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "ref"].forEach((param) => {
        const value = current.searchParams.get(param);
        if (value) out.searchParams.set(param, value);
      });
      return out.toString();
    } catch (_) {
      return url;
    }
  }

  function setupOpenPageRedirect() {
    if (document.body.getAttribute("data-page") !== "open") return;

    const os = detectOS();
    const status = document.getElementById("redirect-status");
    const fallback = "./index.html";

    let target = fallback;
    if (os === "android" && APP_CONFIG.playStoreUrl) target = APP_CONFIG.playStoreUrl;
    if (os === "ios" && APP_CONFIG.appStoreUrl) target = APP_CONFIG.appStoreUrl;

    target = appendAllowedParams(target);

    const playBtn = document.getElementById("manual-play-btn");
    const iosBtn = document.getElementById("manual-ios-btn");
    if (playBtn) playBtn.href = appendAllowedParams(APP_CONFIG.playStoreUrl || fallback);
    if (iosBtn) iosBtn.href = appendAllowedParams(APP_CONFIG.appStoreUrl || fallback);

    if (status) {
      status.textContent =
        os === "desktop"
          ? "We could not detect a mobile OS. Choose your store below."
          : "Taking you to the best download option for your device...";
    }

    setTimeout(function () {
      if (os !== "desktop") {
        window.location.href = target;
      }
    }, 500);
  }

  async function renderLatestVersionFromJson() {
    const latestVersionNode = document.getElementById("latest-version");
    const latestDateNode = document.getElementById("latest-date");
    const latestNotesNode = document.getElementById("latest-highlights");
    if (!latestVersionNode) return;

    try {
      const response = await fetch("./data/releases.json", { cache: "no-store" });
      if (!response.ok) throw new Error("Failed to fetch release data");
      const data = await response.json();
      const latest = Array.isArray(data.releases) ? data.releases[0] : null;
      if (!latest) return;

      latestVersionNode.textContent = latest.version || "v1.0.0";
      if (latestDateNode) latestDateNode.textContent = latest.date || "TBD";
      if (latestNotesNode) {
        const text = Array.isArray(latest.highlights) ? latest.highlights.slice(0, 3).join(" • ") : "Release improvements and stability updates.";
        latestNotesNode.textContent = text;
      }
    } catch (_) {
      // Keep static fallback content.
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const os = detectOS();
    document.documentElement.setAttribute("data-os", os);

    applyConfigTextAndLinks();
    setStoreButtons(os);
    renderQrPlaceholder();
    setupManualApkVisibility();
    setupOpenPageRedirect();
    renderLatestVersionFromJson();
  });
})();
