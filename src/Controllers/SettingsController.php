<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Utils\Logger;

class SettingsController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $this->getUserId();
        $user = User::find($userId);
        $settings = Setting::where('user_id', $userId);

        $this->view('settings/index', [
            'user' => $user,
            'settings' => $settings,
            'notification_methods' => Setting::getNotificationMethods(),
            'temperature_units' => Setting::getTemperatureUnits(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function updateProfile(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|max:100',
            'timezone' => 'required|timezone',
            'language' => 'required|in:' . implode(',', Setting::getAvailableLanguages())
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $userId = $this->getUserId();
        $user = User::find($userId);

        // Check if email is already taken by another user
        if ($user->email !== $this->request->post('email')) {
            $existingUser = User::findByEmail($this->request->post('email'));
            if ($existingUser) {
                $this->respondWithError('Email is already taken');
                return;
            }
        }

        $user->fill([
            'name' => $this->request->post('name'),
            'email' => $this->request->post('email'),
            'timezone' => $this->request->post('timezone'),
            'language' => $this->request->post('language')
        ]);

        if (!$user->save()) {
            $this->respondWithError('Failed to update profile');
            return;
        }

        $this->respondWithSuccess('Profile updated successfully');
    }

    public function updatePassword(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'current_password' => 'required|min:8',
            'new_password' => 'required|min:8|different:current_password',
            'confirm_password' => 'required|same:new_password'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $userId = $this->getUserId();
        $user = User::find($userId);

        if (!$user->verifyPassword($this->request->post('current_password'))) {
            $this->respondWithError('Current password is incorrect');
            return;
        }

        $user->setPassword($this->request->post('new_password'));

        if (!$user->save()) {
            $this->respondWithError('Failed to update password');
            return;
        }

        Logger::info('Password updated for user ' . $user->email);
        $this->respondWithSuccess('Password updated successfully');
    }

    public function updateNotifications(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'notification_method' => 'required|in:' . implode(',', Setting::getNotificationMethods()),
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'notification_frequency' => 'required|in:immediate,hourly,daily,weekly',
            'quiet_hours_start' => 'date_format:H:i',
            'quiet_hours_end' => 'date_format:H:i'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $userId = $this->getUserId();
        $settings = Setting::firstOrNew(['user_id' => $userId]);

        $settings->fill([
            'notification_method' => $this->request->post('notification_method'),
            'email_notifications' => $this->request->post('email_notifications', false),
            'push_notifications' => $this->request->post('push_notifications', false),
            'sms_notifications' => $this->request->post('sms_notifications', false),
            'notification_frequency' => $this->request->post('notification_frequency'),
            'quiet_hours_start' => $this->request->post('quiet_hours_start'),
            'quiet_hours_end' => $this->request->post('quiet_hours_end')
        ]);

        if (!$settings->save()) {
            $this->respondWithError('Failed to update notification settings');
            return;
        }

        $this->respondWithSuccess('Notification settings updated successfully');
    }

    public function updatePreferences(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'temperature_unit' => 'required|in:' . implode(',', Setting::getTemperatureUnits()),
            'dashboard_refresh_rate' => 'required|integer|min:30|max:3600',
            'default_view' => 'required|in:dashboard,sensors,plants',
            'theme' => 'required|in:' . implode(',', Setting::getAvailableThemes())
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $userId = $this->getUserId();
        $settings = Setting::firstOrNew(['user_id' => $userId]);

        $settings->fill([
            'temperature_unit' => $this->request->post('temperature_unit'),
            'dashboard_refresh_rate' => $this->request->post('dashboard_refresh_rate'),
            'default_view' => $this->request->post('default_view'),
            'theme' => $this->request->post('theme')
        ]);

        if (!$settings->save()) {
            $this->respondWithError('Failed to update preferences');
            return;
        }

        $this->respondWithSuccess('Preferences updated successfully');
    }

    public function exportData(): void {
        $userId = $this->getUserId();
        $user = User::find($userId);

        $data = [
            'user' => $user->toArray(),
            'settings' => Setting::where('user_id', $userId)->toArray(),
            'sensors' => $user->sensors()->toArray(),
            'plants' => $user->plants()->toArray()
        ];

        $filename = 'garden_sensors_export_' . date('Y-m-d_His') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function importData(): void {
        $this->requireCsrfToken();

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->respondWithError('No file uploaded or upload failed');
            return;
        }

        $file = $_FILES['import_file'];
        if ($file['type'] !== 'application/json') {
            $this->respondWithError('Invalid file type. Please upload a JSON file');
            return;
        }

        $data = json_decode(file_get_contents($file['tmp_name']), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->respondWithError('Invalid JSON file');
            return;
        }

        try {
            $userId = $this->getUserId();
            $user = User::find($userId);

            // Import settings
            if (isset($data['settings'])) {
                foreach ($data['settings'] as $settingData) {
                    $setting = Setting::firstOrNew(['user_id' => $userId]);
                    $setting->fill($settingData);
                    $setting->save();
                }
            }

            // Import sensors
            if (isset($data['sensors'])) {
                foreach ($data['sensors'] as $sensorData) {
                    $sensor = new Sensor($sensorData);
                    $sensor->user_id = $userId;
                    $sensor->save();
                }
            }

            // Import plants
            if (isset($data['plants'])) {
                foreach ($data['plants'] as $plantData) {
                    $plant = new Plant($plantData);
                    $plant->user_id = $userId;
                    $plant->save();
                }
            }

            Logger::info('Data imported successfully for user ' . $user->email);
            $this->respondWithSuccess('Data imported successfully');
        } catch (\Exception $e) {
            Logger::error('Data import failed for user ' . $user->email . ': ' . $e->getMessage());
            $this->respondWithError('Failed to import data: ' . $e->getMessage());
        }
    }

    public function deleteAccount(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'password' => 'required',
            'confirm_deletion' => 'required|accepted'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Please confirm account deletion and provide your password');
            return;
        }

        $userId = $this->getUserId();
        $user = User::find($userId);

        if (!$user->verifyPassword($this->request->post('password'))) {
            $this->respondWithError('Incorrect password');
            return;
        }

        try {
            // Delete all associated data
            Setting::where('user_id', $userId)->delete();
            foreach ($user->sensors() as $sensor) {
                $sensor->delete();
            }
            foreach ($user->plants() as $plant) {
                $plant->delete();
            }

            // Delete the user account
            $user->delete();

            Logger::info('Account deleted for user ' . $user->email);
            $this->logout();
            $this->respondWithSuccess('Account deleted successfully', '/login');
        } catch (\Exception $e) {
            Logger::error('Account deletion failed for user ' . $user->email . ': ' . $e->getMessage());
            $this->respondWithError('Failed to delete account: ' . $e->getMessage());
        }
    }
} 