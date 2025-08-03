# 📁 CloudStore - Project Progress Tracker (BCA 6th Sem)

This document tracks the implementation status of key features in the CloudStore project, based on supervisor recommendations.

---

## ✅ Completed Features

- [x] **User Authentication**  
  Login and registration system using PHP sessions and secure password hashing.

- [x] **File Upload**  
  Files are uploaded and saved in user-specific storage with metadata saved to the MySQL database.

- [x] **File Download**  
  Authenticated users can download their uploaded files.

- [x] **Soft Delete (Trash Feature)**  
  Deleted files are moved to a trash section (`is_deleted = 1` in DB), not permanently removed.

- [x] **Trash Restore & Permanent Delete**  
  Users can restore or permanently delete trashed files via Trash page.

- [x] **My Drive UI (HTML + Tailwind CSS)**  
  Sidebar, file grid layout, and page navigation implemented with TailwindCSS and Material Icons.

- [x] **PDO with Raw SQL**  
  Used PHP’s PDO extension with prepared statements to prevent SQL injection.

- [x] **Basic Routing & Page Navigation**  
  Sidebar links now correctly route to My Drive, Trash, Shared, etc.

- [x] **Download Protection**  
  File download links are protected and restricted by user ownership.

---

## 🚧 In Progress

- [ ] **AES Encryption (at upload)**  
  Files should be encrypted using AES before storage and decrypted when downloaded. This is a priority.

- [ ] **Client-side Delete (JS-enhanced UI)**  
  JavaScript is now used for delete actions without reloading the page, but needs confirmation & error handling.

- [ ] **Error Handling + Notifications**  
  Need better messages for empty folders, errors, and success alerts.

---

## ❌ Not Started

- [ ] **CSRF Protection**  
  Forms and actions should include tokens to prevent cross-site request forgery.

- [ ] **XSS Protection**  
  All user-generated content (file names) should be sanitized properly.

- [ ] **File Sharing**  
  Ability to generate a shareable link or share with specific users.

- [ ] **File Duplication Check**  
  Prevent users from uploading files with same names or contents.

- [ ] **Information Hiding (Private Files)**  
  Ensure only the owner can view/download their files. Implement if public files are added later.

- [ ] **File Versioning**  
  Store multiple versions of the same file if re-uploaded, instead of overwriting.

- [ ] **Overwrite Handling**  
  Warn before overwriting existing files or handle version control.

- [ ] **ORM Integration (Optional)**  
  Optionally integrate an ORM like Eloquent or Doctrine if time permits.

- [ ] **Cloud/Live Hosting Setup (Optional)**  
  Deploy project to a live server (000webhost, InfinityFree, or paid).

---

## 🔁 Last Updated
**August 1, 2025**

