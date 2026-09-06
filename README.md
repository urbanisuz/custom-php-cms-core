# Custom Modular PHP CMS (Core Architecture)

A lightweight, high-performance, and modular PHP CMS core built from scratch without heavy frameworks. Designed with a clean architecture inspired by enterprise modular systems (similar to OpenCart 3 structure), focusing on strict OOP, extensibility, security, and developer control.

## 🚀 Key Features

* **Strict OOP & Type Safety:** Written with `declare(strict_types=1)` and modern PHP 8+ features.
* **Modular Architecture (`ModuleManager`):** Dynamic scanning of modules via `manifest.json`, supporting isolated installations, transaction-safe database migrations, and cross-module capability calls (`$ctx->module()`).
* **Robust Security Layer (`Auth`):** 
  * Brute-force protection with IP-based rate limiting and timed lockouts.
  * Session fixation defense via `session_regenerate_id()`.
  * Session hijacking prevention using IP & User-Agent cryptographic fingerprinting (`hash('sha256', ...)`).
* **Theme & Template Engine (`ThemeManager`):** File-based theme discovery via `theme.json`, custom theme settings stored in the database, and isolated template rendering through a unified context.
* **SEO & Routing (`Router`):** URL rewriting and mapping system supporting multi-store and multi-language routing out of the box.
* **Database Abstraction (`Database`):** Secure PDO wrapper with automated error handling, connection health checks, and transaction support.

---

## 📂 Project Structure

```text
system/
├── Auth.php          # Authentication, session security & brute-force protection
├── Context.php       # Global application context passed across modules and templates
├── Database.php      # Secure PDO wrapper & database helper methods
├── Kernel.php        # Core application lifecycle handler & request dispatcher
├── ModuleManager.php # Module scanner, transaction-safe installer & capability runner
├── Router.php        # SEO URL resolution & routing engine
├── ThemeManager.php  # Theme discovery, configuration loader & installer
├── helpers.php       # Global utility functions (e.g., XSS escaping `e()`)
└── init.php          # PSR-4-like custom autoloader for System namespaces
```
💻 Code Highlights
1. Request Dispatching & Lifecycle (Kernel.php)

The kernel initializes configurations, establishes a secure database connection, resolves multi-language URI segments, matches SEO routes, and safely renders theme components through a unified context.
2. Session Hijacking Defense (Auth.php)
PHP

// Cryptographic fingerprint validation on every request
```text
$currentFingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
if (isset($_SESSION['ip_fingerprint']) &&$_SESSION['ip_fingerprint'] !== $currentFingerprint) {$this->logout();
    return false;
}

3. Transaction-Safe Module Installation (ModuleManager.php)
PHP

$pdo = $db->getPdo();$pdo->beginTransaction();

try {
    $installer = new$installerClass($db);$installer->install();

    $db->query(
        "INSERT INTO installed_modules (module_id, version, installed_at) VALUES (?, ?, NOW())",
        [$moduleId,$modInfo['version']]
    );
    
    $pdo->commit();
    return true;
} catch (\Throwable $e) {$pdo->rollBack();
    throw new \Exception("Module installation failed: " . $e->getMessage());
}
```
🛠️ Tech Stack

    Language: PHP 8+ (Strict Typing)

    Database: MySQL / MariaDB (via PDO)

    Design Patterns: Singleton, Dependency Injection (Context-based), Modular Plugin Architecture.

📊 Status & Roadmap

    Current Status: Active Development (v0.1-alpha — Core Architecture)

    Roadmap:

        [x] Core Engine, Autoloader & Database abstraction layer

        [x] Security, Authentication & Cryptographic Session Management

        [x] Module & Theme Manager with Manifest support and Transactions

        [ ] Admin Panel & RBAC (Role-Based Access Control)

        [ ] REST API Layer

👤 Author

Hurshid aka Urbanis — Backend Developer & System Architect
