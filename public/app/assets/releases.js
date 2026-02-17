(function () {
  async function loadReleases() {
    const latestRoot = document.getElementById("latest-release");
    const historyRoot = document.getElementById("release-history");
    if (!latestRoot || !historyRoot) return;

    try {
      const response = await fetch("./data/releases.json", { cache: "no-store" });
      if (!response.ok) throw new Error("Failed to load releases");
      const payload = await response.json();
      const releases = Array.isArray(payload.releases) ? payload.releases : [];
      if (!releases.length) return;

      const latest = releases[0];
      latestRoot.innerHTML = renderReleaseCard(latest, true);

      const rest = releases.slice(1);
      historyRoot.innerHTML = rest.length
        ? rest.map((r) => renderReleaseCard(r, false)).join("")
        : '<p class="muted">No additional release history yet.</p>';
    } catch (_) {
      // Keep static fallback in HTML for no-js/error.
    }
  }

  function renderReleaseCard(release, isLatest) {
    const version = escapeHtml(release.version || "v1.0.0");
    const date = escapeHtml(release.date || "Unknown date");
    const highlights = Array.isArray(release.highlights) && release.highlights.length
      ? `<ul>${release.highlights.map((i) => `<li>${escapeHtml(i)}</li>`).join("")}</ul>`
      : '<p class="muted">General improvements and fixes.</p>';

    const issues = Array.isArray(release.knownIssues) && release.knownIssues.length
      ? `
        <h4>Known issues</h4>
        <ul>
          ${release.knownIssues.map((i) => `<li>${escapeHtml(i)}</li>`).join("")}
        </ul>
      `
      : '<p class="muted"><strong>Known issues:</strong> None reported.</p>';

    const latestBadge = isLatest ? '<span class="badge latest">Latest</span>' : '';

    return `
      <article class="card" aria-label="Release ${version}">
        <div class="badges">
          <span class="badge">${version}</span>
          <span class="badge">${date}</span>
          ${latestBadge}
        </div>
        <h3>${version} release notes</h3>
        ${highlights}
        ${issues}
      </article>
    `;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  document.addEventListener("DOMContentLoaded", loadReleases);
})();
