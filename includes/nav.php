<?php
// Shared navigation — include at the top of every page
// Requires $basePath to be set before including:
//   root pages  → $basePath = '';
//   admin pages → $basePath = '../';

$currentProgram = (int)($_GET['p'] ?? 1);
$currentPage    = basename($_SERVER['PHP_SELF']);

$programs = [
    1 => ['name' => 'Morning Pass',    'icon' => '🌅', 'color' => '#B8232a'],
    2 => ['name' => 'Academia',        'icon' => '🏫', 'color' => '#27393f'],
    3 => ['name' => 'Team Competition','icon' => '🏆', 'color' => '#1a6a8a'],
];

$navPages = [
    'index.php'          => 'Horario',
    'professor.php'      => 'Por Profesor',
    'students.php'       => 'Estudiantes',
    'admin/slots.php'    => 'Admin Horarios',
];
?>
<header class="site-header">
  <div class="header-brand">
    <span class="brand-icon">⚽</span>
    <span class="brand-name">Morning Pass</span>
  </div>

  <div class="program-tabs">
    <?php foreach ($programs as $pid => $prog): ?>
      <a href="<?= $basePath ?>index.php?p=<?= $pid ?>"
         class="prog-tab <?= $pid === $currentProgram ? 'active' : '' ?>"
         style="--prog-color:<?= $prog['color'] ?>">
        <?= $prog['icon'] ?> <?= $prog['name'] ?>
      </a>
    <?php endforeach; ?>
  </div>

  <nav class="site-nav">
    <?php foreach ($navPages as $file => $label):
        $href     = $basePath . $file . '?p=' . $currentProgram;
        $isActive = ($currentPage === basename($file)) || ($currentPage === 'slots.php' && $file === 'admin/slots.php');
    ?>
      <a href="<?= $href ?>" class="<?= $isActive ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</header>
