<?php
/**
 * Правая колонка (контейнер для модулей)
 * Ожидает массив активных модулей для правой зоны: $rightModules
 */
$rightModules = $rightModules ?? [];
?>
<aside class="sidebar sidebar-right">
    <?php if (!empty($rightModules)): ?>
        <?php foreach ($rightModules as $module): ?>
            <div class="sidebar-widget module-<?= htmlspecialchars($module['name'] ?? 'widget') ?>">
                <?php if (!empty($module['title'])): ?>
                    <h3 class="widget-title"><?= htmlspecialchars($module['title']) ?></h3>
                <?php endif; ?>
                
                <div class="widget-content">
                    <?php 
                    if (!empty($module['file']) && file_exists($module['file'])) {
                        include $module['file'];
                    } elseif (isset($module['html'])) {
                        echo $module['html'];
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</aside>