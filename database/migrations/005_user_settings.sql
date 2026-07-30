-- Migration 005: Per-user JSON settings (baseline_water_ec, alert flags)

CREATE TABLE IF NOT EXISTS user_settings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    settings JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_settings_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uniq_user_settings_user (user_id)
) ENGINE=InnoDB;

INSERT INTO user_settings (user_id, settings)
SELECT u.id, JSON_OBJECT(
    'email_notifications', true,
    'low_battery_alert', true,
    'moisture_alert', true,
    'temperature_alert', true,
    'ph_alert', true,
    'ec_alert', true,
    'baseline_water_ec', 0.3,
    'update_interval', 300,
    'theme', 'light',
    'language', 'en',
    'timezone', 'UTC'
)
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM user_settings us WHERE us.user_id = u.id
);
