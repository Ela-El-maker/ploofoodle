<?php $active = $activeNav ?? ''; ?>
<aside class="sidebar">
  <div class="brand">Ploofoodle</div>
  <div class="brand-sub">Admin Control Center</div>
  <ul class="nav-list">
    <li><a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a></li>
    <li><a class="nav-link <?= $active === 'releases' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/releases'), ENT_QUOTES, 'UTF-8') ?>">Releases</a></li>
    <li><a class="nav-link <?= $active === 'config' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/config'), ENT_QUOTES, 'UTF-8') ?>">Bootstrap Config</a></li>
    <li><a class="nav-link <?= $active === 'front_landing' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing'), ENT_QUOTES, 'UTF-8') ?>">Front Landing Page</a></li>
    <li><a class="nav-link <?= $active === 'audit' ? 'active' : '' ?>" href="<?= htmlspecialchars(ploo_route_url('/admin/audit'), ENT_QUOTES, 'UTF-8') ?>">Audit Log</a></li>
  </ul>
  <?php if ($active === 'front_landing'): ?>
    <ul class="nav-list" style="margin-top:10px">
      <li><a class="nav-link" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing'), ENT_QUOTES, 'UTF-8') ?>">Landing</a></li>
      <li><a class="nav-link" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing/get-started'), ENT_QUOTES, 'UTF-8') ?>">Get Started</a></li>
      <li><a class="nav-link" href="<?= htmlspecialchars(ploo_route_url('/admin/front-landing/support'), ENT_QUOTES, 'UTF-8') ?>">Support</a></li>
    </ul>
  <?php endif; ?>
</aside>
