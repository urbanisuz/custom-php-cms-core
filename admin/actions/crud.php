<?php
// admin/actions/crud.php

require_once dirname(__DIR__, 2) . '/system/Database.php';


if (!isset($db)) {
    $config = require ROOT_PATH . '/config.php';

$dbConfig = [
    'host' => $config['db']['host'],
    'dbname' => $config['db']['dbname'],
    'username' => $config['db']['username'],
    'password' => $config['db']['password'],
];

$db = new \System\Database($dbConfig);
}

// Получаем имя таблицы из GET-параметра и очищаем от опасных символов
$table = trim($_GET['table'] ?? '');
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

if (!$table) {
    echo "<h2>Ошибка</h2><p>Не указана таблица для управления.</p>";
    return;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$mode = $_GET['mode'] ?? 'list'; // list, edit, create, delete
$message = '';
$error = '';

// Получаем список всех доступных таблиц в базе (для удобной навигации)
$tablesQuery = $db->fetchAll("SHOW TABLES");
$dbNameKey = 'Tables_in_' . $config['dbname'];
$allTables = array_map(fn($t) => $t[$dbNameKey] ?? reset($t), $tablesQuery);

// Получаем структуру текущей таблицы (колонки и типы)
try {
    $columnsRaw = $db->fetchAll("DESCRIBE `{$table}`");
} catch (\Throwable $e) {
    echo "<h2>Ошибка</h2><p>Таблица <code>" . htmlspecialchars($table) . "</code> не существует или недоступна.</p>";
    return;
}

$primaryKey = 'id';
$columnNames = [];
foreach ($columnsRaw as $col) {
    $columnNames[] = $col['Field'];
    if ($col['Key'] === 'PRI') {
        $primaryKey = $col['Field'];
    }
}

// Обработка удаления
if ($mode === 'delete' && $id) {
    try {
        $db->query("DELETE FROM `{$table}` WHERE `{$primaryKey}` = ?", [$id]);
        header("Location: index.php?action=crud&table={$table}&msg=deleted");
        exit;
    } catch (\Throwable $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
        $mode = 'list';
    }
}

// Обработка сохранения (Создание / Редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['fields'] ?? [];
    
    // Исключаем первичный ключ из полей вставки, если он автоинкрементный
    unset($data[$primaryKey]);

    if ($id) {
        // UPDATE
        $fieldsSql = [];
        $values = [];
        foreach ($data as $fieldName => $fieldValue) {
            if (in_array($fieldName, $columnNames)) {
                $fieldsSql[] = "`{$fieldName}` = ?";
                $values[] = $fieldValue === '' ? null : $fieldValue;
            }
        }
        $values[] = $id;
        
        if (!empty($fieldsSql)) {
            $sql = "UPDATE `{$table}` SET " . implode(', ', $fieldsSql) . " WHERE `{$primaryKey}` = ?";
            try {
                $db->query($sql, $values);
                header("Location: index.php?action=crud&table={$table}&msg=updated");
                exit;
            } catch (\Throwable $e) {
                $error = "Ошибка обновления: " . $e->getMessage();
            }
        }
    } else {
        // INSERT
        $fieldsKeys = [];
        $placeholders = [];
        $values = [];
        foreach ($data as $fieldName => $fieldValue) {
            if (in_array($fieldName, $columnNames)) {
                $fieldsKeys[] = "`{$fieldName}`";
                $placeholders[] = "?";
                $values[] = $fieldValue === '' ? null : $fieldValue;
            }
        }
        
        if (!empty($fieldsKeys)) {
            $sql = "INSERT INTO `{$table}` (" . implode(', ', $fieldsKeys) . ") VALUES (" . implode(', ', $placeholders) . ")";
            try {
                $db->query($sql, $values);
                header("Location: index.php?action=crud&table={$table}&msg=created");
                exit;
            } catch (\Throwable $e) {
                $error = "Ошибка создания: " . $e->getMessage();
            }
        }
    }
}

// Сообщения из редиректов
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $message = 'Запись успешно создана!';
    if ($_GET['msg'] === 'updated') $message = 'Запись успешно обновлена!';
    if ($_GET['msg'] === 'deleted') $message = 'Запись удалена!';
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Управление таблицей: <code><?= htmlspecialchars($table) ?></code></h2>
    <div>
        <a href="index.php?action=crud&table=<?= htmlspecialchars($table) ?>&mode=create" style="background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 14px;">+ Добавить запись</a>
    </div>
</div>

<!-- Селектор таблиц для быстрого переключения -->
<div style="background: #f1f3f5; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
    <span style="font-size: 14px; font-weight: bold; margin-right: 10px;">Выберите таблицу:</span>
    <select onchange="location.href='index.php?action=crud&table=' + this.value" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
        <?php foreach ($allTables as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $t === $table ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($message): ?>
    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($mode === 'create' || $mode === 'edit'): ?>
    <?php
    $row = [];
    if ($mode === 'edit' && $id) {
        $row = $db->fetch("SELECT * FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1", [$id]) ?? [];
    }
    ?>
    <div style="background: #fff; border: 1px solid #dee2e6; padding: 20px; border-radius: 6px;">
        <h3><?= $mode === 'edit' ? 'Редактировать запись #' . $id : 'Новая запись' ?></h3>
        <form method="POST">
            <?php foreach ($columnsRaw as $col): ?>
                <?php 
                $fName = $col['Field'];
                if ($fName === $primaryKey && $mode === 'create' && str_contains($col['Extra'], 'auto_increment')) {
                    continue; // Пропускаем автоинкрементируемый перв. ключ при создании
                }
                $fValue = $row[$fName] ?? '';
                $isPrimary = ($fName === $primaryKey);
                ?>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px;">
                        <?= htmlspecialchars($fName) ?> <span style="font-weight: normal; color: #6c757d; font-size: 12px;">(<?= htmlspecialchars($col['Type']) ?>)</span>
                    </label>
                    <?php if (str_contains(strtolower($col['Type']), 'text')): ?>
                        <textarea name="fields[<?= htmlspecialchars($fName) ?>]" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><?= htmlspecialchars($fValue) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="fields[<?= htmlspecialchars($fName) ?>]" value="<?= htmlspecialchars($fValue) ?>" <?= $isPrimary && $mode === 'edit' ? 'readonly style="background: #e9ecef;"' : '' ?> style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 20px;">
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Сохранить</button>
                <a href="index.php?action=crud&table=<?= htmlspecialchars($table) ?>" style="margin-left: 10px; color: #6c757d; text-decoration: none;">Отмена</a>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- Режим списка -->
    <?php 
    $rows = $db->fetchAll("SELECT * FROM `{$table}` LIMIT 100"); // Ограничим вывод для безопасности до 100 строк
    ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">
            <thead>
                <tr style="background: #f1f3f5; text-align: left;">
                    <?php foreach ($columnNames as $colName): ?>
                        <th style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?= htmlspecialchars($colName) ?></th>
                    <?php endforeach; ?>
                    <th style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= count($columnNames) + 1 ?>" style="padding: 15px; text-align: center; color: #6c757d;">В таблице пока нет записей.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($columnNames as $colName): ?>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($r[$colName] ?? '') ?>
                                </td>
                            <?php endforeach; ?>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right; white-space: nowrap;">
                                <a href="index.php?action=crud&table=<?= htmlspecialchars($table) ?>&mode=edit&id=<?= $r[$primaryKey] ?>" style="color: #007bff; text-decoration: none; margin-right: 10px;">Ред.</a>
                                <a href="index.php?action=crud&table=<?= htmlspecialchars($table) ?>&mode=delete&id=<?= $r[$primaryKey] ?>" onclick="return confirm('Точно удалить запись?');" style="color: #dc3545; text-decoration: none;">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>