<?php

require_once __DIR__ . '/../models/Settings.php';

class SettingsController
{
    private $model;

    public function __construct()
    {
        $this->model = new Settings();
    }

    public function index()
    {
        $settings = $this->model->getAll();
        $success = $_GET['success'] ?? null;
        $error = $_GET['error'] ?? null;

        // Fetch Mentors
        $db = Database::getInstance();
        Auth::ensureMentorLogsTable();
        $mentors = $db->fetchAll("SELECT id, name, email, created_at FROM users WHERE role = 'mentor' ORDER BY created_at DESC");

        // Fetch Logs
        $mentorLogs = $db->fetchAll("
            SELECT l.*, u.name as mentor_name, u.email as mentor_email 
            FROM mentor_logs l 
            JOIN users u ON l.user_id = u.id 
            ORDER BY l.logged_in_at DESC 
            LIMIT 50
        ");

        // Pass current backup files to view
        $backups = glob(__DIR__ . '/../../data/backups/*.db');
        $backupList = [];
        if ($backups) {
            foreach ($backups as $file) {
                $backupList[] = [
                    'name' => basename($file),
                    'date' => date('d.m.Y H:i', filemtime($file)),
                    'size' => round(filesize($file) / 1024, 2) . ' KB'
                ];
            }
            // Sort new first
            usort($backupList, function ($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });
        }

        require __DIR__ . '/../../templates/settings/index.php';
    }

    public function update()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // General Settings
            if (isset($_POST['goal_month']))
                $this->model->set('goal_month', $_POST['goal_month']);
            if (isset($_POST['goal_year']))
                $this->model->set('goal_year', $_POST['goal_year']);
            if (isset($_POST['goal_metric']))
                $this->model->set('goal_metric', $_POST['goal_metric']);
            if (isset($_POST['neglect_months']))
                $this->model->set('neglect_months', (int) $_POST['neglect_months']);

            // Backup Toggle (checkbox sends 'on' or nothing)
            $backupEnabled = isset($_POST['backup_enabled']) ? '1' : '0';
            $this->model->set('backup_enabled', $backupEnabled);

            redirect('index.php?page=settings&success=saved');
        }
    }

    public function updatePassword()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = $_POST['password_current'] ?? '';
            $new = $_POST['password_new'] ?? '';
            $repeat = $_POST['password_repeat'] ?? '';

            $user = Auth::user();

            // Verify Logic
            if (!password_verify($current, $user['password_hash'])) {
                redirect('index.php?page=settings&error=wrong_pw');
            }
            if ($new !== $repeat) {
                redirect('index.php?page=settings&error=mismatch');
            }
            if (!Auth::validatePassword($new)) {
                redirect('index.php?page=settings&error=weak_pw');
            }

            // Update
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $db = Database::getInstance();
            $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $user['id']]);

            redirect('index.php?page=settings&success=pw_changed');
        }
    }

    public function triggerBackup()
    {
        Auth::denyMentorWriteAccess();
        // Manual trigger
        self::performBackup();
        redirect('index.php?page=settings&success=backup_created');
    }

    public function importProducts()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=settings');
        }

        require_once __DIR__ . '/../models/Product.php';
        $productModel = new Product();

        try {
            if (!isset($_FILES['product_file']) || $_FILES['product_file']['error'] !== UPLOAD_ERR_OK) {
                // Check if specific error
                $msg = "Upload Fehler code: " . ($_FILES['product_file']['error'] ?? 'Unknown');
                if (isset($_FILES['product_file']['error']) && $_FILES['product_file']['error'] === UPLOAD_ERR_NO_FILE) {
                    $msg = "Keine Datei ausgewählt.";
                }
                throw new Exception($msg);
            }

            $tmpPath = $_FILES['product_file']['tmp_name'];
            // Validate mimetype or extension if possible, but JSON content check in model is good too

            $count = $productModel->importFromJson($tmpPath);
            redirect('index.php?page=settings&msg=products_imported&count=' . $count);

        } catch (Exception $e) {
            redirect('index.php?page=settings&error=' . urlencode($e->getMessage()));
        }
    }

    public static function performBackup()
    {
        $dataDir = __DIR__ . '/../../data';
        $source = $dataDir . '/managepeople.db';
        $backupDir = $dataDir . '/backups';

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        if (!file_exists($source))
            return;

        $dest = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.db';
        if (copy($source, $dest)) {
            // Rotate: Keep last 3
            $files = glob($backupDir . '/*.db');
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            // Keep top 3, delete rest
            $toDelete = array_slice($files, 3);
            foreach ($toDelete as $f) {
                unlink($f);
            }

            // Update config
            $settings = new Settings();
            $settings->set('last_backup', date('Y-m-d H:i:s'));
        }
    }

    public function addMentor()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = trim($_POST['email'] ?? '');

            if (empty($name) || empty($email)) {
                redirect('index.php?page=settings&error=mentor_fields_missing');
            }

            try {
                // Generate a temporary random password
                $tempPassword = bin2hex(random_bytes(16)) . '!A1';

                // Register User
                Auth::register($name, $email, $tempPassword, 'mentor');

                // Generate Reset Token
                $token = Auth::generateResetToken($email);

                // Send Invitation Email
                require_once __DIR__ . '/../Mailer.php';
                $mailer = new Mailer();

                $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . dirname($_SERVER['PHP_SELF'])
                    . '/index.php?page=reset_password&token=' . $token;

                $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:500px;margin:0 auto;padding:30px 20px;">';
                $html .= '<h2 style="color:#FF6B6B;margin-bottom:20px;">Einladung als Mentorin</h2>';
                $html .= '<p style="color:#2C3E50;margin-bottom:20px;">Hallo ' . h($name) . ', du wurdest als Mentorin f&uuml;r die ManagePeople Applikation eingeladen.</p>';
                $html .= '<p style="color:#2C3E50;margin-bottom:20px;">Klicke auf den folgenden Link, um dein Passwort festzulegen und dich einzuloggen:</p>';
                $html .= '<a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#FF6B6B;color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:bold;">Passwort festlegen</a>';
                $html .= '<p style="color:#999;font-size:12px;margin-top:25px;">Sollte der Button nicht funktionieren, kopiere diesen Link:</p>';
                $html .= '<p style="color:#999;font-size:11px;">' . h($resetUrl) . '</p>';
                $html .= '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">';
                $html .= '<p style="color:#ccc;font-size:11px;">ManagePeople</p>';
                $html .= '</div>';

                $mailer->send($email, 'Einladung als Mentorin - ManagePeople', $html);

                redirect('index.php?page=settings&success=mentor_created');

            } catch (Exception $e) {
                redirect('index.php?page=settings&error=' . urlencode($e->getMessage()));
            }
        }
    }

    public function deleteMentor()
    {
        Auth::denyMentorWriteAccess();
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $db = Database::getInstance();
            // Verify it's actually a mentor
            $user = $db->fetch("SELECT role FROM users WHERE id = ?", [$id]);
            if ($user && $user['role'] === 'mentor') {
                $db->execute("DELETE FROM users WHERE id = ?", [$id]);
                redirect('index.php?page=settings&success=mentor_deleted');
            }
        }
        redirect('index.php?page=settings&error=delete_failed');
    }
}
