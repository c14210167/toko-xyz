# Fix Employee Management - Missing Columns

## Problem
Error: `Column 'description' not found in table 'roles'`

## Solution

### Option 1: Run SQL Fix Script (Recommended)
Jalankan script berikut di MySQL/phpMyAdmin:

```bash
mysql -u root frontendproject < fix-roles-table.sql
```

atau via phpMyAdmin:
1. Buka phpMyAdmin
2. Pilih database `frontendproject`
3. Klik tab SQL
4. Copy-paste isi file `fix-roles-table.sql`
5. Klik Go

### Option 2: Manual ALTER TABLE
Jika script di atas tidak berfungsi, jalankan query berikut secara manual:

```sql
-- Add description column to roles table
ALTER TABLE roles ADD COLUMN description TEXT AFTER role_name;

-- Add updated_at column to roles table
ALTER TABLE roles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
```

### Option 3: Recreate RBAC Tables
Jika masih ada masalah, recreate semua tabel RBAC:

```bash
mysql -u root frontendproject < rbac-schema.sql
```

## Verification
Setelah menjalankan fix, verifikasi dengan query:

```sql
DESCRIBE roles;
```

Harusnya muncul kolom:
- role_id
- role_name
- description ✓ (harus ada)
- is_system_role
- created_at
- updated_at ✓ (harus ada)

## Notes
Kode sudah dibuat defensive, jadi meskipun kolom description tidak ada, sistem akan tetap berjalan dengan menggunakan string kosong sebagai fallback.

File yang sudah dibuat defensive:
- ✓ staff/api/get-employees.php
- ✓ staff/api/get-employee-detail.php
- ✓ staff/api/get-roles.php
- ✓ staff/employee-detail.php
- ✓ includes/PermissionManager.php
