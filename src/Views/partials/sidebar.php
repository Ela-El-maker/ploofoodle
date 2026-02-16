<?php
$active = $activeNav ?? '';
$base = '/Pandipoodle/Ploofoodle/public/index.php?_route=';
?>
<aside class="sidebar">
  <div class="brand">Ploofoodle</div>
  <div class="brand-sub">Admin Control Center</div>
  <ul class="nav-list">
    <li><a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= $base ?>/admin">Dashboard</a></li>
    <li><a class="nav-link <?= $active === 'releases' ? 'active' : '' ?>" href="<?= $base ?>/admin/releases">Releases</a></li>
    <li><a class="nav-link <?= $active === 'config' ? 'active' : '' ?>" href="<?= $base ?>/admin/config">Bootstrap Config</a></li>
    <li><a class="nav-link <?= $active === 'audit' ? 'active' : '' ?>" href="<?= $base ?>/admin/audit">Audit Log</a></li>
  </ul>
</aside>
