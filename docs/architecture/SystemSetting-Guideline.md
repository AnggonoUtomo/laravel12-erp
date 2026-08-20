# Guideline Module SystemSetting — SakaSantri

## Tujuan

`SystemSetting` menyediakan global/dynamic application settings yang berkembang seiring kebutuhan module.

## Batas

```text
.env / config        -> konfigurasi deployment/teknis
SystemSetting        -> konfigurasi aplikasi/bisnis dinamis
Master/Domain Data   -> tetap dimiliki module domain terkait
```

## Contoh Cocok

```text
application_name
timezone
date_format
student_number_prefix
invoice_number_prefix
payment_due_warning_days
default_academic_year
```

## Bukan SystemSetting

```text
DB_HOST
APP_KEY
Student
Guardian
Employee
Organization Unit
Class
Subject
Fee Definition
```

## Ownership

Module lain boleh mendaftarkan definition setting, tetapi business rule dan makna setting tetap dimiliki module asal.
