<?php
require_once 'core/Model.php';
require_once 'core/Crypto.php';

class Settings extends Model {
    protected $table = 'settings';
    private const SENSITIVE_KEYS = [
        'turnstile_secret_key',
        'recaptcha_secret_key',
        's3_access_key',
        's3_secret_key',
    ];
    
    public function getSetting($key, $default = null) {
        $setting = $this->findOneWhere('setting_key = ?', [$key]);
        if (!$setting) {
            return $default;
        }

        return $this->isSensitiveKey($key)
            ? Crypto::decrypt($setting['setting_value'])
            : $setting['setting_value'];
    }
    
    public function setSetting($key, $value) {
        if ($this->isSensitiveKey($key)) {
            $value = Crypto::encrypt((string) $value);
        }
        $existing = $this->findOneWhere('setting_key = ?', [$key]);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            return $this->create([
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    public function getAllSettings() {
        $settings = $this->findAll();
        $result = [];
        
        foreach ($settings as $setting) {
            $key = $setting['setting_key'];
            $result[$key] = $this->isSensitiveKey($key)
                ? Crypto::decrypt($setting['setting_value'])
                : $setting['setting_value'];
        }
        
        return $result;
    }
    
    public function getCompanyInfo() {
        return [
            'company_name' => $this->getSetting('company_name', APP_NAME),
            'company_address' => $this->getSetting('company_address', ''),
            'company_phone' => $this->getSetting('company_phone', ''),
            'company_email' => $this->getSetting('company_email', ''),
            'company_website' => $this->getSetting('company_website', ''),
            'company_logo' => $this->getSetting('company_logo', ''),
            'work_order_disclaimer' => $this->getSetting('work_order_disclaimer', 'Set up your Disclaimer on the Settings page'),
            'print_customer_signature' => $this->getSetting('print_customer_signature', '1'),
            'print_technician_signature' => $this->getSetting('print_technician_signature', '1'),
        ];
    }
    
    public function getCaptchaSettings() {
        return [
            'captcha_provider' => $this->getSetting('captcha_provider', 'off'), // off, turnstile, recaptcha
            'turnstile_site_key' => $this->getSetting('turnstile_site_key', ''),
            'turnstile_secret_key' => $this->getSetting('turnstile_secret_key', ''),
            'recaptcha_site_key' => $this->getSetting('recaptcha_site_key', ''),
            'recaptcha_secret_key' => $this->getSetting('recaptcha_secret_key', '')
        ];
    }
    
    public function updateCompanyInfo($data) {
        foreach ($data as $key => $value) {
            $this->setSetting($key, $value);
        }
        return true;
    }
    
    public function updateCaptchaSettings($data) {
        foreach ($data as $key => $value) {
            $this->setSetting($key, $value);
        }
        return true;
    }
    
    public function updateSecuritySettings($data) {
        foreach ($data as $key => $value) {
            $this->setSetting($key, $value);
        }
        return true;
    }
    
    public function updateFormatSettings($data) {
        foreach ($data as $key => $value) {
            $this->setSetting($key, $value);
        }
        return true;
    }

    public function updateAttachmentSettings($data) {
        foreach ($data as $key => $value) {
            $this->setSetting($key, $value);
        }
        return true;
    }

    private function isSensitiveKey(string $key): bool {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }
}
