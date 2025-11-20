# Document Portal System Architecture

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Architecture Components](#architecture-components)
3. [Authentication Flow](#authentication-flow)
4. [Database Structure](#database-structure)
5. [File Upload Process](#file-upload-process)
6. [Role-Based Access Control](#role-based-access-control)
7. [Key Workflows](#key-workflows)
8. [Security Features](#security-features)

---

## 🎯 System Overview

The Document Portal is a **plain PHP** application (no frameworks) that provides:
- **Document Management**: Upload, organize, preview, and download documents
- **Folder Hierarchy**: Create nested folders and subfolders
- **User Management**: Admin can create/manage users
- **Activity Logging**: Track all user actions
- **Role-Based Access**: Admin vs Normal User permissions

---

## 🏗️ Architecture Components

### 1. **Core Configuration** (`includes/config.php`)
```php
- Database connection (PDO with prepared statements)
- Session management (session_start())
- Helper functions (is_logged_in(), is_admin(), log_action())
- Security functions (h() for XSS protection)
```

**Key Functions:**
- `is_logged_in()` - Checks if user has active session
- `require_login()` - Redirects to login if not authenticated
- `is_admin()` - Checks if user has admin role
- `log_action()` - Records user actions in logs table
- `h()` - HTML escape function for XSS protection

### 2. **File Structure**
```
DocumentPortal/
├── includes/          # Core configuration & shared code
├── admin/            # Admin-only pages
├── user/             # User pages
├── assets/           # CSS, JS, images
├── uploads/          # Stored documents (server filesystem)
└── Root files        # login.php, index.php, etc.
```

---

## 🔐 Authentication Flow

### Login Process:
```
1. User visits login.php
   ↓
2. If already logged in → Redirect to index.php
   ↓
3. User submits credentials (POST)
   ↓
4. System queries database for username
   ↓
5. password_verify() checks password against hash
   ↓
6. If valid:
   - Store user_id, username, role in $_SESSION
   - Log action in logs table
   - Redirect to index.php
   ↓
7. index.php checks role:
   - Admin → /admin/dashboard.php
   - User → /user/dashboard.php
```

### Session Management:
- **Session Storage**: PHP sessions (stored on server)
- **Session Data**: `user_id`, `username`, `role`
- **Protection**: Every page calls `require_login()` to verify session
- **Logout**: `session_destroy()` clears session data

### Password Security:
- **Hashing**: Uses `password_hash()` with BCRYPT algorithm
- **Verification**: Uses `password_verify()` (timing-safe)
- **Storage**: Only hashed passwords stored in database

---

## 💾 Database Structure

### Tables:

#### 1. **users**
```sql
- id (PK)
- username (UNIQUE)
- password (hashed)
- role (ENUM: 'admin' | 'user')
- created_at
```

#### 2. **folders**
```sql
- id (PK)
- name
- parent_id (FK → folders.id, NULL for root)
- created_by (FK → users.id)
- created_at
```
**Hierarchy**: Self-referencing table (parent_id points to another folder)

#### 3. **documents**
```sql
- id (PK)
- folder_id (FK → folders.id, NULL for root)
- name (original filename)
- file_path (stored filename on server)
- uploaded_by (FK → users.id)
- created_at
```

#### 4. **logs**
```sql
- id (PK)
- user_id (FK → users.id)
- document_id (FK → documents.id, NULL for non-document actions)
- action (VARCHAR: 'upload', 'download', 'delete', 'login', etc.)
- timestamp
```

### Relationships:
```
users (1) ──→ (many) documents
users (1) ──→ (many) folders
users (1) ──→ (many) logs
folders (1) ──→ (many) folders (self-reference)
folders (1) ──→ (many) documents
documents (1) ──→ (many) logs
```

---

## 📤 File Upload Process

### Upload Workflow:
```
1. Admin clicks "Upload Document" button
   ↓
2. Modal opens with file input
   ↓
3. User selects file and submits
   ↓
4. POST to admin/upload.php
   ↓
5. Validation:
   - Check file MIME type (finfo_file())
   - Check file size (max 50MB)
   - Verify user is admin
   ↓
6. Generate unique filename:
   - Format: {uniqid()}_{sanitized_original_name}
   - Example: "507f1f77bcf86cd799439011_document.pdf"
   ↓
7. Move file to /uploads/ directory
   ↓
8. Insert record into documents table:
   - folder_id (current folder or NULL)
   - name (original filename)
   - file_path (stored filename)
   - uploaded_by (current user_id)
   ↓
9. Log action in logs table
   ↓
10. Redirect back with success message
```

### File Storage:
- **Physical Location**: `/uploads/` directory on server
- **Database**: Stores metadata (name, folder, uploader)
- **Security**: Unique filenames prevent conflicts and directory traversal

---

## 👥 Role-Based Access Control

### Admin Permissions:
✅ **Full Access:**
- Upload documents
- Delete documents
- Move documents between folders
- Create/edit/delete folders
- Create/edit/delete users
- View activity logs
- View dashboard statistics

### User Permissions:
✅ **Read-Only Access:**
- View documents
- Preview documents (PDF, images, text)
- Download documents
- Browse folders
- Search documents

❌ **Restricted:**
- Cannot upload
- Cannot delete
- Cannot manage folders
- Cannot manage users
- Cannot view logs

### Access Control Implementation:
```php
// Every admin page checks:
require_login();           // Must be logged in
if (!is_admin()) {         // Must be admin
    header('Location: ...');
    exit;
}
```

---

## 🔄 Key Workflows

### 1. **Document Upload Workflow**
```
User Action → Upload Modal → File Selection → Validation → 
Storage → Database Insert → Log Entry → Redirect
```

### 2. **Folder Navigation Workflow**
```
Click Folder → Query documents WHERE folder_id = X →
Build Breadcrumbs (recursive parent lookup) → Display
```

### 3. **Document Preview Workflow**
```
Click Preview → Query document → Check file type →
- PDF: Embed in iframe
- Image: Display <img>
- Text: Read file and display <pre>
- Other: Show download option
→ Log 'view' action
```

### 4. **Document Download Workflow**
```
Click Download → Query document → Verify file exists →
Set HTTP headers (Content-Disposition: attachment) →
Read file → Output to browser → Log 'download' action
```

### 5. **Folder Creation Workflow**
```
Click "Create Folder" → Modal → Enter name/parent →
Validate (no duplicate names in same parent) →
Insert into folders table → Log action → Refresh
```

### 6. **User Management Workflow**
```
Admin → Users Page → Create/Edit/Delete →
- Create: Hash password, insert user
- Edit: Update username/role, optionally update password
- Delete: Remove user (cascade deletes documents/folders)
→ Log action
```

---

## 🔒 Security Features

### 1. **SQL Injection Protection**
- **Method**: Prepared statements (PDO)
- **Example**: `$stmt->prepare('SELECT * FROM users WHERE id = ?')`
- **Benefit**: Parameters are escaped automatically

### 2. **XSS Protection**
- **Method**: `htmlspecialchars()` function (aliased as `h()`)
- **Usage**: All user output wrapped: `<?= h($variable) ?>`
- **Benefit**: Prevents script injection in HTML

### 3. **Password Security**
- **Hashing**: BCRYPT algorithm (cost factor 10)
- **Verification**: Timing-safe `password_verify()`
- **Storage**: Never store plaintext passwords

### 4. **File Upload Security**
- **Type Validation**: MIME type checking (not just extension)
- **Size Limits**: 50MB maximum
- **Unique Filenames**: Prevents overwrites and conflicts
- **Sanitization**: Filename sanitization before storage

### 5. **Session Security**
- **Server-Side**: Sessions stored on server (not cookies)
- **Validation**: Every page checks `require_login()`
- **Role Checking**: Admin functions verify `is_admin()`

### 6. **Access Control**
- **Route Protection**: Admin pages check role before rendering
- **Redirects**: Unauthorized access redirects to appropriate page
- **Cascade Deletes**: Foreign keys ensure data integrity

---

## 📊 Data Flow Examples

### Example 1: Admin Uploads Document
```
1. Browser: POST /admin/upload.php
2. Server: Validate admin role
3. Server: Check file MIME type
4. Server: Check file size
5. Server: Generate unique filename
6. Server: Move file to /uploads/
7. Database: INSERT INTO documents
8. Database: INSERT INTO logs (action='upload')
9. Server: Set $_SESSION['success']
10. Browser: Redirect to /admin/documents.php
11. Browser: Display success message
```

### Example 2: User Views Document
```
1. Browser: GET /view.php?id=123
2. Server: require_login() check
3. Database: SELECT * FROM documents WHERE id=123
4. Server: Check file exists in /uploads/
5. Database: INSERT INTO logs (action='view')
6. Server: Determine file type
7. Server: Render appropriate viewer (PDF iframe, image, text)
8. Browser: Display preview
```

### Example 3: Folder Navigation
```
1. Browser: GET /admin/documents.php?folder_id=5
2. Server: require_login() + is_admin() checks
3. Database: SELECT * FROM folders WHERE parent_id=5
4. Database: SELECT * FROM documents WHERE folder_id=5
5. Server: Build breadcrumbs (recursive parent lookup)
6. Server: Render folder contents
7. Browser: Display folders and documents
```

---

## 🎨 Frontend Architecture

### CSS Structure:
- **CSS Variables**: Easy theming (colors, spacing)
- **Responsive Design**: Mobile-first approach
- **Component-Based**: Cards, modals, tables, buttons
- **Modern UI**: Gradients, shadows, transitions

### JavaScript:
- **Vanilla JS**: No frameworks
- **Event Handlers**: Form validation, modal management
- **UX Enhancements**: Auto-hide alerts, smooth scrolling
- **Mobile Support**: Sidebar toggle for mobile

### Layout:
- **Sidebar Navigation**: Fixed left sidebar
- **Top Navigation**: User info and logout
- **Main Content**: Dynamic content area
- **Modals**: Overlay dialogs for forms

---

## 🔧 Configuration

### Database Settings (`includes/config.php`):
```php
DB_HOST = '127.0.0.1'
DB_NAME = 'DocumentPortal'
DB_USER = 'root'
DB_PASS = ''
BASE_PATH = '/DocumentPortal'
```

### Upload Settings (`admin/upload.php`):
```php
Max Size: 50MB
Allowed Types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT
Storage: /uploads/ directory
```

---

## 📝 Summary

**System Type**: Traditional server-side PHP application
**Database**: MySQL with PDO
**Authentication**: Session-based with password hashing
**File Storage**: Filesystem + Database metadata
**Security**: Prepared statements, XSS protection, role-based access
**Architecture**: MVC-like structure (Model: DB, View: PHP templates, Controller: PHP logic)

The system is **fully functional**, **secure**, and **ready for production** (after changing default password and adding HTTPS).

