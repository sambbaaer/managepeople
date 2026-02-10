<?php
/**
 * ManagePeople V3 - Entry Point
 */

// 1. Check Setup
if (!file_exists(__DIR__ . '/config.php')) {
    header("Location: setup.php");
    exit;
}

// 2. Init
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

// Controllers
require_once __DIR__ . '/core/controllers/DashboardController.php';
require_once __DIR__ . '/core/controllers/ContactController.php';
require_once __DIR__ . '/core/controllers/TaskController.php';
require_once __DIR__ . '/core/controllers/CalendarController.php';
require_once __DIR__ . '/core/controllers/SearchController.php';
require_once __DIR__ . '/core/controllers/SettingsController.php';

// 3. Routing
$page = $_GET['page'] ?? 'dashboard';

// Public Routes
$publicRoutes = ['login', 'logout', 'forgot_password', 'reset_password', 'calendar_feed'];

if (!Auth::check() && !in_array($page, $publicRoutes)) {
    redirect('index.php?page=login');
}

// Auto Backup Check (Weekly)
if (Auth::check()) {
    try {
        $settingsModel = new Settings();
        if ($settingsModel->get('backup_enabled') == '1') {
            $lastBackup = $settingsModel->get('last_backup');
            if (!$lastBackup || strtotime($lastBackup) < strtotime('-7 days')) {
                SettingsController::performBackup();
            }
        }
    } catch (Exception $e) {
        // Silent fail on backup check to not block app
    }
}

// 4. Controller Logic
try {
    switch ($page) {
        // ... existing cases ...
        case 'login':
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                if (Auth::login($email, $password)) {
                    redirect('index.php?page=dashboard');
                } else {
                    $error = "Ungültige Zugangsdaten.";
                }
            }
            require __DIR__ . '/templates/auth/login.php';
            break;

        case 'logout':
            Auth::logout();
            redirect('index.php?page=login');
            break;

        case 'forgot_password':
            $error = '';
            $success = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = trim($_POST['email'] ?? '');
                if ($email) {
                    $token = Auth::generateResetToken($email);
                    if ($token) {
                        try {
                            require_once __DIR__ . '/core/Mailer.php';
                            $mailer = new Mailer();

                            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                                . '://' . $_SERVER['HTTP_HOST']
                                . dirname($_SERVER['PHP_SELF'])
                                . '/index.php?page=reset_password&token=' . $token;

                            $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:500px;margin:0 auto;padding:30px 20px;">';
                            $html .= '<h2 style="color:#FF6B6B;margin-bottom:20px;">Passwort zur&uuml;cksetzen</h2>';
                            $html .= '<p style="color:#2C3E50;margin-bottom:20px;">Du hast eine Passwort-Zur&uuml;cksetzung angefordert. Klicke auf den folgenden Link, um ein neues Passwort zu setzen:</p>';
                            $html .= '<a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#FF6B6B;color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:bold;">Passwort zur&uuml;cksetzen</a>';
                            $html .= '<p style="color:#999;font-size:12px;margin-top:25px;">Dieser Link ist 1 Stunde g&uuml;ltig. Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail.</p>';
                            $html .= '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">';
                            $html .= '<p style="color:#ccc;font-size:11px;">ManagePeople</p>';
                            $html .= '</div>';

                            $mailer->send($email, 'Passwort zuruecksetzen - ManagePeople', $html);
                        } catch (Exception $e) {
                            // Silently fail - don't reveal if email exists
                        }
                    }
                    // Always show success to not reveal if email exists
                    $success = true;
                }
            }
            require __DIR__ . '/templates/auth/forgot_password.php';
            break;

        case 'reset_password':
            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            $error = '';
            $success = false;
            $invalid = false;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['token'] ?? '';
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';

                if ($password !== $passwordConfirm) {
                    $error = 'Passwörter stimmen nicht überein.';
                } elseif (!Auth::validatePassword($password)) {
                    $error = 'Passwort zu schwach (min. 8 Zeichen, Grossbuchstabe, Zahl, Sonderzeichen).';
                } elseif (Auth::resetPassword($token, $password)) {
                    $success = true;
                } else {
                    $invalid = true;
                }
            } else {
                // GET: validate token exists
                if (!Auth::validateResetToken($token)) {
                    $invalid = true;
                }
            }
            require __DIR__ . '/templates/auth/reset_password.php';
            break;

        case 'dashboard':
            $controller = new DashboardController();
            $controller->index();
            break;

        case 'statistics':
            require_once __DIR__ . '/core/controllers/StatisticsController.php';
            $controller = new StatisticsController();
            $controller->index();
            break;

        case 'search':
            $controller = new SearchController();
            $controller->results();
            break;

        // Settings Routes
        case 'settings':
            $controller = new SettingsController();
            $controller->index();
            break;
        case 'settings_update':
            $controller = new SettingsController();
            $controller->update();
            break;
        case 'settings_password':
            $controller = new SettingsController();
            $controller->updatePassword();
            break;
        case 'settings_trigger_backup':
            $controller = new SettingsController();
            $controller->triggerBackup();
            break;
        case 'settings_import_products':
            $controller = new SettingsController();
            $controller->importProducts();
            break;
        case 'settings_mentor_add':
            $controller = new SettingsController();
            $controller->addMentor();
            break;
        case 'settings_mentor_delete':
            $controller = new SettingsController();
            $controller->deleteMentor();
            break;

        // SMART LISTS
        case 'settings_smart_lists':
            require_once 'core/controllers/SmartListController.php';
            $controller = new SmartListController();
            $controller->index();
            break;
        case 'settings_smart_lists_create':
            require_once 'core/controllers/SmartListController.php';
            $controller = new SmartListController();
            $controller->create();
            break;
        case 'settings_smart_lists_delete':
            require_once 'core/controllers/SmartListController.php';
            $controller = new SmartListController();
            $controller->delete();
            break;

        // Contact Routes
        case 'contacts':
            $controller = new ContactController();
            $controller->index();
            break;
        case 'contact_detail':
            $id = $_GET['id'] ?? 0;
            $controller = new ContactController();
            $controller->show($id);
            break;
        case 'contact_edit':
            $id = $_GET['id'] ?? 0;
            $controller = new ContactController();
            $controller->edit($id);
            break;
        case 'contact_create':
            $controller = new ContactController();
            $controller->create();
            break;
        case 'contact_update':
            $id = $_GET['id'] ?? 0;
            $controller = new ContactController();
            $controller->update($id);
            break;
        case 'contact_assign_product':
            $controller = new ContactController();
            $controller->assignProduct();
            break;
        case 'contact_remove_product':
            $controller = new ContactController();
            $controller->removeProduct();
            break;
        case 'contact_workflow_ui':
            $id = $_GET['id'] ?? 0;
            $controller = new ContactController();
            $controller->getWorkflowUI($id);
            break;
        case 'contact_add_note':
            $id = $_GET['id'] ?? 0;
            $controller = new ContactController();
            $controller->addNote($id);
            break;
        case 'contact_quick_create':
            $controller = new ContactController();
            $controller->quickCreate();
            break;
        case 'export_contacts':
            require_once __DIR__ . '/core/controllers/ExportController.php';
            $controller = new ExportController();
            $controller->exportContactsCSV();
            break;

        // Task Routes
        case 'tasks':
            $controller = new TaskController();
            $controller->index();
            break;
        case 'task_create':
            $controller = new TaskController();
            $controller->create();
            break;
        case 'task_toggle':
            $controller = new TaskController();
            $controller->toggle();
            break;
        case 'task_delete':
            $controller = new TaskController();
            $controller->delete();
            break;
        case 'task_reschedule':
            $controller = new TaskController();
            $controller->reschedule();
            break;

        // Calendar Routes
        case 'calendar':
            $controller = new CalendarController();
            $controller->index();
            break;
        case 'calendar_feed':
            $controller = new CalendarController();
            $controller->feed();
            break;

        // Product Routes
        case 'products':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->index();
            break;
        case 'product_create':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->create();
            break;
        case 'product_archive':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->archive();
            break;
        case 'product_unarchive':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->unarchive();
            break;
        case 'product_export':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->export();
            break;
        case 'product_assign_contact':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->assignContact();
            break;
        case 'product_remove_contact':
            require_once __DIR__ . '/core/controllers/ProductController.php';
            $controller = new ProductController();
            $controller->removeContact();
            break;

        // Automation Routes
        case 'automation':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->index();
            break;
        case 'automation_toggle_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->toggleRule();
            break;
        case 'automation_update_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->updateRule();
            break;
        case 'automation_toggle_global':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->toggleGlobal();
            break;
        case 'automation_edit_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->editCustomRule();
            break;
        case 'automation_create_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->createCustomRule();
            break;
        case 'automation_update_custom':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->updateCustomRule();
            break;
        case 'automation_delete_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->deleteCustomRule();
            break;
        case 'automation_export_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->exportRule();
            break;
        case 'automation_import_rule':
            require_once __DIR__ . '/core/controllers/AutomationController.php';
            $controller = new AutomationController();
            $controller->importRule();
            break;

        // Workflow Routes
        case 'workflows':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->index();
            break;
        case 'workflow_edit':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->editTemplate();
            break;
        case 'workflow_save':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->saveTemplate();
            break;
        case 'workflow_delete':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->deleteTemplate();
            break;
        case 'workflow_duplicate':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->duplicateTemplate();
            break;
        case 'workflow_save_step':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->saveStep();
            break;
        case 'workflow_delete_step':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->deleteStep();
            break;
        case 'workflow_start':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->start();
            break;
        case 'workflow_preview':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->preview();
            break;
        case 'workflow_instance':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->viewInstance();
            break;
        case 'workflow_cancel':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->cancelInstance();
            break;
        case 'workflow_export':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->export();
            break;
        case 'workflow_import':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->import();
            break;

        case 'workflow_contacts':
            require_once __DIR__ . '/core/controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->getContacts();
            break;

        default:
            $controller = new DashboardController();
            $controller->index();
            break;
    }
} catch (Exception $e) {
    die("Application Error: " . $e->getMessage());
}
