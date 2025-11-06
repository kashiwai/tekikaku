-- MedentPay Complete Database Schema
-- 全機能対応の完全版スキーマ

-- 既存テーブルの削除（再作成のため）
DROP TABLE IF EXISTS loan_application_documents CASCADE;
DROP TABLE IF EXISTS loan_applications CASCADE;
DROP TABLE IF EXISTS clinic_finance_company_priorities CASCADE;
DROP TABLE IF EXISTS payment_applications CASCADE;
DROP TABLE IF EXISTS clinic_settings CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS clinics CASCADE;
DROP TABLE IF EXISTS finance_companies CASCADE;

-- 1. クリニックテーブル
CREATE TABLE clinics (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  clinic_id VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  name_kana VARCHAR(255),
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  fax VARCHAR(50),
  postal_code VARCHAR(10),
  prefecture VARCHAR(50),
  city VARCHAR(100),
  address VARCHAR(255),
  building VARCHAR(255),
  website VARCHAR(255),
  business_hours JSONB,
  bank_name VARCHAR(100),
  bank_branch VARCHAR(100),
  bank_account_type VARCHAR(20),
  bank_account_number VARCHAR(20),
  bank_account_name VARCHAR(100),
  contract_status VARCHAR(50) DEFAULT 'pending', -- pending, active, suspended, terminated
  contract_date DATE,
  monthly_fee DECIMAL(10,2),
  transaction_fee_rate DECIMAL(5,2),
  notes TEXT,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 2. ユーザーテーブル（クリニックスタッフ）
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  clinic_id UUID REFERENCES clinics(id) ON DELETE CASCADE,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  name_kana VARCHAR(255),
  role VARCHAR(50) NOT NULL, -- super_admin, clinic_admin, staff, viewer
  phone VARCHAR(50),
  department VARCHAR(100),
  position VARCHAR(100),
  permissions JSONB DEFAULT '{}',
  last_login_at TIMESTAMP,
  password_reset_token VARCHAR(255),
  password_reset_expires TIMESTAMP,
  email_verified BOOLEAN DEFAULT false,
  email_verification_token VARCHAR(255),
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 3. 融資会社マスタ
CREATE TABLE finance_companies (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  name_kana VARCHAR(255),
  company_type VARCHAR(50), -- credit_card, loan_company, bank
  api_endpoint VARCHAR(255),
  api_key VARCHAR(255),
  api_secret VARCHAR(255),
  scraping_url VARCHAR(255),
  scraping_config JSONB,
  max_loan_amount DECIMAL(10,2),
  min_loan_amount DECIMAL(10,2),
  interest_rate_min DECIMAL(5,2),
  interest_rate_max DECIMAL(5,2),
  max_installments INTEGER,
  processing_days INTEGER DEFAULT 3,
  business_hours JSONB,
  support_email VARCHAR(255),
  support_phone VARCHAR(50),
  contract_required_documents JSONB,
  auto_approve_threshold DECIMAL(10,2),
  is_api_available BOOLEAN DEFAULT false,
  is_scraping_available BOOLEAN DEFAULT false,
  display_order INTEGER,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 4. クリニック別融資会社優先順位
CREATE TABLE clinic_finance_company_priorities (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  clinic_id UUID REFERENCES clinics(id) ON DELETE CASCADE,
  finance_company_id UUID REFERENCES finance_companies(id) ON DELETE CASCADE,
  priority INTEGER NOT NULL,
  is_enabled BOOLEAN DEFAULT true,
  auto_apply BOOLEAN DEFAULT true,
  max_amount_override DECIMAL(10,2),
  notes TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(clinic_id, finance_company_id),
  UNIQUE(clinic_id, priority)
);

-- 5. クリニック設定
CREATE TABLE clinic_settings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  clinic_id UUID UNIQUE REFERENCES clinics(id) ON DELETE CASCADE,
  auto_apply_enabled BOOLEAN DEFAULT true,
  max_auto_apply_amount DECIMAL(10,2) DEFAULT 1000000,
  require_down_payment BOOLEAN DEFAULT false,
  min_down_payment_rate DECIMAL(5,2) DEFAULT 10,
  payment_link_expiry_days INTEGER DEFAULT 30,
  sms_notification_enabled BOOLEAN DEFAULT true,
  email_notification_enabled BOOLEAN DEFAULT true,
  line_notification_enabled BOOLEAN DEFAULT false,
  reminder_days_before_expiry INTEGER DEFAULT 3,
  custom_message_template TEXT,
  working_hours_start TIME DEFAULT '09:00',
  working_hours_end TIME DEFAULT '18:00',
  working_days JSONB DEFAULT '["mon","tue","wed","thu","fri"]',
  auto_cancel_expired BOOLEAN DEFAULT true,
  require_guarantee BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 6. 決済申請テーブル
CREATE TABLE payment_applications (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  payment_id VARCHAR(100) UNIQUE NOT NULL,
  clinic_id UUID REFERENCES clinics(id) ON DELETE CASCADE,
  patient_name VARCHAR(255) NOT NULL,
  patient_name_kana VARCHAR(255) NOT NULL,
  birth_date DATE NOT NULL,
  gender VARCHAR(10),
  postal_code VARCHAR(10),
  prefecture VARCHAR(50),
  city VARCHAR(100),
  address VARCHAR(255),
  building VARCHAR(255),
  phone VARCHAR(50) NOT NULL,
  mobile VARCHAR(50),
  email VARCHAR(255),
  treatment_type VARCHAR(100),
  treatment_details TEXT,
  total_amount DECIMAL(10,2) NOT NULL,
  down_payment DECIMAL(10,2) DEFAULT 0,
  loan_amount DECIMAL(10,2) NOT NULL,
  preferred_installments INTEGER,
  preferred_finance_company_id UUID REFERENCES finance_companies(id),
  payment_link VARCHAR(500),
  payment_link_token VARCHAR(255) UNIQUE,
  access_count INTEGER DEFAULT 0,
  last_accessed_at TIMESTAMP,
  status VARCHAR(50) DEFAULT 'pending', -- pending, processing, approved, rejected, expired, cancelled
  approved_finance_company_id UUID REFERENCES finance_companies(id),
  approved_amount DECIMAL(10,2),
  approved_installments INTEGER,
  approved_interest_rate DECIMAL(5,2),
  monthly_payment DECIMAL(10,2),
  contract_number VARCHAR(100),
  rejection_reason TEXT,
  notes TEXT,
  metadata JSONB DEFAULT '{}',
  created_by UUID REFERENCES users(id),
  approved_by UUID REFERENCES users(id),
  cancelled_by UUID REFERENCES users(id),
  created_at TIMESTAMP DEFAULT NOW(),
  expires_at TIMESTAMP,
  approved_at TIMESTAMP,
  cancelled_at TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 7. 融資申請テーブル
CREATE TABLE loan_applications (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  payment_application_id UUID REFERENCES payment_applications(id) ON DELETE CASCADE,
  finance_company_id UUID REFERENCES finance_companies(id) ON DELETE CASCADE,
  application_number VARCHAR(100),
  external_application_id VARCHAR(100),
  
  -- 申請者情報
  applicant_data JSONB NOT NULL,
  
  -- 申請情報
  loan_amount DECIMAL(10,2) NOT NULL,
  installments INTEGER NOT NULL,
  purpose VARCHAR(255),
  
  -- 審査状態
  status VARCHAR(50) DEFAULT 'pending', -- pending, submitted, reviewing, approved, rejected, cancelled
  submission_method VARCHAR(50), -- api, scraping, manual
  
  -- 審査結果
  approval_status VARCHAR(50), -- approved, conditional_approved, rejected
  approved_amount DECIMAL(10,2),
  approved_installments INTEGER,
  interest_rate DECIMAL(5,2),
  monthly_payment DECIMAL(10,2),
  total_payment DECIMAL(10,2),
  
  -- 条件
  conditions TEXT,
  required_documents JSONB,
  
  -- API/スクレイピング情報
  api_request_data JSONB,
  api_response_data JSONB,
  scraping_session_id VARCHAR(255),
  scraping_screenshots JSONB,
  
  -- エラー情報
  error_code VARCHAR(100),
  error_message TEXT,
  retry_count INTEGER DEFAULT 0,
  
  -- タイムスタンプ
  submitted_at TIMESTAMP,
  reviewed_at TIMESTAMP,
  approved_at TIMESTAMP,
  rejected_at TIMESTAMP,
  expires_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 8. 申請書類テーブル
CREATE TABLE loan_application_documents (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  loan_application_id UUID REFERENCES loan_applications(id) ON DELETE CASCADE,
  document_type VARCHAR(100) NOT NULL,
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500),
  file_size INTEGER,
  mime_type VARCHAR(100),
  uploaded_by UUID REFERENCES users(id),
  verified BOOLEAN DEFAULT false,
  verified_by UUID REFERENCES users(id),
  verified_at TIMESTAMP,
  notes TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

-- 9. 監査ログテーブル
CREATE TABLE audit_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES users(id),
  clinic_id UUID REFERENCES clinics(id),
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100),
  entity_id UUID,
  old_data JSONB,
  new_data JSONB,
  ip_address INET,
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

-- 10. システム設定テーブル
CREATE TABLE system_settings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  key VARCHAR(100) UNIQUE NOT NULL,
  value JSONB NOT NULL,
  description TEXT,
  updated_by UUID REFERENCES users(id),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- インデックスの作成
CREATE INDEX idx_clinics_clinic_id ON clinics(clinic_id);
CREATE INDEX idx_clinics_email ON clinics(email);
CREATE INDEX idx_clinics_is_active ON clinics(is_active);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_clinic_id ON users(clinic_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);

CREATE INDEX idx_payment_applications_payment_id ON payment_applications(payment_id);
CREATE INDEX idx_payment_applications_clinic_id ON payment_applications(clinic_id);
CREATE INDEX idx_payment_applications_status ON payment_applications(status);
CREATE INDEX idx_payment_applications_created_at ON payment_applications(created_at);
CREATE INDEX idx_payment_applications_payment_link_token ON payment_applications(payment_link_token);

CREATE INDEX idx_loan_applications_payment_application_id ON loan_applications(payment_application_id);
CREATE INDEX idx_loan_applications_finance_company_id ON loan_applications(finance_company_id);
CREATE INDEX idx_loan_applications_status ON loan_applications(status);
CREATE INDEX idx_loan_applications_application_number ON loan_applications(application_number);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_clinic_id ON audit_logs(clinic_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- 初期データの投入
-- スーパー管理者用クリニック
INSERT INTO clinics (clinic_id, name, email, phone, contract_status) VALUES 
  ('ADMIN', 'システム管理', 'admin@payment-gateway-service.com', '03-0000-0000', 'active');

-- デフォルト融資会社
INSERT INTO finance_companies (code, name, company_type, max_loan_amount, min_loan_amount, interest_rate_min, interest_rate_max, max_installments, display_order) VALUES 
  ('CBS', 'シービーエス', 'credit_card', 3000000, 30000, 1.5, 15.0, 84, 1),
  ('APLUS', 'アプラス', 'credit_card', 5000000, 30000, 2.0, 14.0, 60, 2),
  ('SAISON', 'セゾン', 'credit_card', 3000000, 50000, 2.5, 15.0, 48, 3),
  ('ORICO', 'オリコ', 'credit_card', 2000000, 30000, 3.0, 15.0, 60, 4),
  ('JACCS', 'ジャックス', 'credit_card', 3000000, 30000, 2.0, 14.5, 60, 5);

-- テスト用クリニック
INSERT INTO clinics (clinic_id, name, email, phone, postal_code, prefecture, city, address, contract_status) VALUES
  ('CLINIC001', 'スマート医療クリニック', 'info@smartmedical.com', '03-1234-5678', '100-0001', '東京都', '千代田区', '千代田1-1-1', 'active');

-- テスト用管理者（パスワード: smart123）
INSERT INTO users (clinic_id, email, password_hash, name, role) VALUES
  ((SELECT id FROM clinics WHERE clinic_id = 'CLINIC001'),
   'info@smartmedical.com',
   '$2a$10$I3/VRM/I.O.wSwzJHTtNn.8xKofk1yr9iiGC0druZz7x9S0GqnG5O',
   '管理者',
   'clinic_admin');

-- クリニック設定の初期化
INSERT INTO clinic_settings (clinic_id) 
SELECT id FROM clinics WHERE clinic_id != 'ADMIN';