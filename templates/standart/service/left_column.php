<?php
/**
 * Левая колонка (контейнер для модулей)
 * Ожидает массив или коллекцию активных модулей для левой зоны: $leftModules
 */
$leftModules = $leftModules ?? [];
?>
<aside class="sidebar sidebar-left">
    <?php if (!empty($leftModules)): ?>
        <?php foreach ($leftModules as $module): ?>
            <div class="sidebar-widget module-<?= htmlspecialchars($module['name'] ?? 'widget') ?>">
                <?php if (!empty($module['title'])): ?>
                    <h3 class="widget-title"><?= htmlspecialchars($module['title']) ?></h3>
                <?php endif; ?>
                
                <div class="widget-content">
                    <?php 
                    // Подключаем содержимое конкретного модуля (например, фильтр, меню категорий и т.д.)
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