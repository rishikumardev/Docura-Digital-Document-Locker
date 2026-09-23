<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
start_secure_session();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#142f6f">
    <title>Docura — Secure Digital Document Locker</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="app-shell">

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-icon">D</div>
            <div>
                <div class="brand-title">Docura</div>
                <div class="brand-subtitle">Secure Digital Document Locker</div>
            </div>
        </div>

        <button class="sidebar-upload" id="sidebarUploadBtn">
            <span>＋</span> Upload Document <span class="push-right">↗</span>
        </button>

        <div class="nav-label">Workspace</div>
        <nav class="side-nav">
            <button class="nav-item active" data-page="dashboard"><span>⌂</span><b>Dashboard</b></button>
            <button class="nav-item" data-page="documents"><span>▣</span><b>My Documents</b><em id="navCount">0</em></button>
            <button class="nav-item" data-page="folders"><span>▤</span><b>Folders</b></button>
            <button class="nav-item" data-page="shared"><span>⇄</span><b>Shared With Me</b></button>
            <button class="nav-item" data-page="starred"><span>★</span><b>Starred</b></button>
            <button class="nav-item" data-page="activity"><span>◷</span><b>Activity</b></button>
            <button class="nav-item" data-page="trash"><span>⌫</span><b>Trash</b></button>
        </nav>

        <div id="adminNavWrap" class="hidden">
            <div class="nav-label">Administration</div>
            <nav class="side-nav">
                <button class="nav-item" data-page="admin"><span>◆</span><b>Admin Console</b></button>
            </nav>
        </div>

        <div class="nav-label">Protection</div>
        <nav class="side-nav">
            <button class="nav-item" data-page="security"><span>◉</span><b>Security Center</b></button>
            <button class="nav-item" data-page="settings"><span>⚙</span><b>Settings</b></button>
        </nav>

        <div class="sidebar-bottom">
            <div class="mini-storage">
                <div class="mini-storage-row"><span>Vault storage</span><b id="miniStoragePct">0%</b></div>
                <div class="mini-progress"><i id="miniStorageBar"></i></div>
                <small id="miniStorageText">0 MB of 2 GB used</small>
            </div>
            <button class="side-profile" id="sideProfileBtn">
                <div class="avatar" id="sideAvatar">DU</div>
                <div class="profile-copy"><b id="sideName">Guest User</b><small id="sideStatus">Guest mode</small></div>
                <span>•••</span>
            </button>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="top-icon mobile-menu" id="mobileMenuBtn">☰</button>
            <div class="global-search">
                <span>⌕</span>
                <input id="searchInput" placeholder="Search documents, folders and files...">
                <span class="shortcut">Ctrl K</span>
            </div>
            <div class="top-actions">
                <button class="top-icon" id="themeBtn" title="Toggle dark mode">☾</button>
                <div class="notify-wrap">
                    <button class="top-icon" id="notifyBtn">♢<i class="notify-dot"></i></button>
                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-head"><div><b>Notifications</b><small>Latest vault updates</small></div><button id="closeNotifications">×</button></div>
                        <div class="notification-item"><span class="n-dot blue"></span><div><b>Vault protection is active</b><small>Your local workspace is ready.</small></div></div>
                        <div class="notification-item"><span class="n-dot green"></span><div><b>Storage monitoring enabled</b><small>Usage is shown on your dashboard.</small></div></div>
                    </div>
                </div>
                <div id="authButtons">
                    <button class="btn" id="topSignIn">Sign In</button>
                    <button class="btn primary" id="topCreateAccount">Create Account</button>
                </div>
                <button class="profile-button hidden" id="topProfileBtn"><div class="avatar" id="topAvatar">DU</div></button>
            </div>
        </header>

        <!-- DASHBOARD -->
        <section class="page active" id="page-dashboard">
            <div class="hero-banner">
                <div class="hero-content">
                    <div class="eyebrow light">PERSONAL DIGITAL VAULT</div>
                    <h1>Your documents deserve a <span>beautifully secure</span> home.</h1>
                    <p>Store, organize, preview, download and share important documents from one powerful workspace.</p>
                    <div class="hero-pills"><span>✓ Private vault</span><span>✓ Smart organization</span><span>✓ Fast access</span></div>
                    <div class="hero-actions">
                        <button class="btn hero-secondary" id="heroFolder">＋ New Folder</button>
                        <button class="btn hero-primary" id="heroUpload">↑ Upload Document</button>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-glow glow-a"></div><div class="hero-glow glow-b"></div>
                    <div class="floating-doc fd1"><span>PDF</span><b>Marksheet</b><small>2.4 MB</small></div>
                    <div class="floating-doc fd2"><span>DOC</span><b>Resume</b><small>780 KB</small></div>
                    <div class="floating-doc fd3"><span>✓</span><b>Vault Safe</b><small>Protected</small></div>
                    <div class="hero-shield">◆</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue"><div class="stat-icon">▣</div><span>Total Documents</span><strong id="statDocuments">0</strong><small>Stored in your vault</small></div>
                <div class="stat-card purple"><div class="stat-icon">⇄</div><span>Shared Files</span><strong id="statShared">0</strong><small>Active sharing records</small></div>
                <div class="stat-card cyan"><div class="stat-icon">☁</div><span>Storage Used</span><strong id="statStorage">0 B</strong><small>of 2 GB available</small></div>
                <div class="stat-card green"><div class="stat-icon">✓</div><span>Vault Health</span><strong>98%</strong><small>Protection checks healthy</small></div>
            </div>

            <div class="section-grid two-col">
                <section class="surface">
                    <div class="surface-head"><div><h2>Recent Documents</h2><small>Latest additions to your locker</small></div><button class="text-link" data-go="documents">View all →</button></div>
                    <div id="recentDocuments"></div>
                    <div id="recentEmpty" class="empty-state hidden"><div class="empty-icon">▱</div><b>Your vault is ready</b><small>Upload your first document.</small></div>
                </section>

                <section class="surface">
                    <div class="surface-head"><div><h2>Storage Overview</h2><small>Live database-backed usage</small></div><span class="tiny-badge">LIVE</span></div>
                    <div class="storage-overview">
                        <div class="storage-ring" id="storageRing"><div><b id="ringPercent">0%</b><small>used</small></div></div>
                        <div class="storage-breakdown">
                            <div class="bar-label"><span>Documents</span><b id="documentsStorage">0 B</b></div>
                            <div class="bar"><i id="documentsBar"></i></div>
                            <div class="bar-label"><span>Shared</span><b id="sharedStorage">0 B</b></div>
                            <div class="bar"><i id="sharedBar" class="purple"></i></div>
                            <div class="bar-label"><span>Available</span><b id="freeStorage">2 GB</b></div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="section-grid two-col">
                <section class="surface">
                    <div class="surface-head"><div><h2>Quick Actions</h2><small>Common vault tasks</small></div></div>
                    <div class="quick-grid">
                        <button class="quick-action blue" id="quickUpload"><span>↑</span><b>Upload</b><small>Add documents</small></button>
                        <button class="quick-action purple" id="quickFolder"><span>▤</span><b>New Folder</b><small>Organize files</small></button>
                        <button class="quick-action cyan" id="quickShare"><span>⇄</span><b>Share</b><small>Share securely</small></button>
                        <button class="quick-action green" id="quickSecurity"><span>◉</span><b>Security</b><small>Check protection</small></button>
                    </div>
                </section>
                <section class="surface">
                    <div class="surface-head"><div><h2>Recent Activity</h2><small>Server-side audit trail</small></div><button class="text-link" data-go="activity">See all →</button></div>
                    <div id="dashboardActivity"></div>
                </section>
            </div>
        </section>

        <!-- DOCUMENTS -->
        <section class="page" id="page-documents">
            <div class="page-head"><div><div class="eyebrow">YOUR VAULT</div><h1>My Documents</h1><p>Manage files stored in your SQLite-backed vault.</p></div><button class="btn primary" id="documentsUpload">↑ Upload Document</button></div>
            <div class="toolbar surface">
                <div class="filters">
                    <button class="filter active" data-filter="all">All</button><button class="filter" data-filter="pdf">PDF</button><button class="filter" data-filter="doc">Documents</button><button class="filter" data-filter="image">Images</button><button class="filter" data-filter="sheet">Sheets</button>
                </div>
                <select id="sortSelect"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="name">Name A–Z</option><option value="size">Largest first</option></select>
            </div>
            <div id="documentsGrid" class="card-grid"></div>
            <div id="documentsEmpty" class="empty-state page-empty hidden"><div class="empty-icon">▱</div><b>No documents found</b><small>Upload a file or change the filter.</small></div>
        </section>

        <!-- FOLDERS -->
        <section class="page" id="page-folders">
            <div class="page-head"><div><div class="eyebrow">ORGANIZE</div><h1>Folders</h1><p>Organize your digital documents by category.</p></div><button class="btn primary" id="foldersAdd">＋ New Folder</button></div>
            <div id="foldersGrid" class="folder-grid"></div>
        </section>

        <!-- SHARED -->
        <section class="page" id="page-shared">
            <div class="page-head"><div><div class="eyebrow">COLLABORATION</div><h1>Shared With Me</h1><p>Documents other registered users have shared with you.</p></div></div>
            <div id="sharedList" class="shared-list"></div>
            <div id="sharedEmpty" class="empty-state page-empty hidden"><div class="empty-icon">⇄</div><b>No shared documents</b><small>When another Docura user shares a file with your email, it appears here.</small></div>
        </section>

        <!-- STARRED -->
        <section class="page" id="page-starred">
            <div class="page-head"><div><div class="eyebrow">FAVORITES</div><h1>Starred</h1><p>Your most important documents, pinned here.</p></div></div>
            <div id="starredGrid" class="card-grid"></div>
            <div id="starredEmpty" class="empty-state page-empty hidden"><div class="empty-icon">★</div><b>Nothing starred</b><small>Star a document to pin it here.</small></div>
        </section>

        <!-- ACTIVITY -->
        <section class="page" id="page-activity">
            <div class="page-head"><div><div class="eyebrow">AUDIT TRAIL</div><h1>Activity</h1><p>Server-side record of your document actions.</p></div><button class="btn danger" id="clearActivity">Clear log</button></div>
            <section class="surface"><div id="fullActivity"></div></section>
        </section>

        <!-- TRASH -->
        <section class="page" id="page-trash">
            <div class="page-head"><div><div class="eyebrow">RECOVERY</div><h1>Trash</h1><p>Restore deleted documents or delete them permanently.</p></div><button class="btn danger" id="emptyTrash">Empty Trash</button></div>
            <div id="trashGrid" class="card-grid"></div>
            <div id="trashEmpty" class="empty-state page-empty hidden"><div class="empty-icon">⌫</div><b>Trash is empty</b><small>Deleted documents will appear here.</small></div>
        </section>

        <!-- SECURITY -->
        <section class="page" id="page-security">
            <div class="page-head"><div><div class="eyebrow">PROTECTION</div><h1>Security Center</h1><p>Review account and document protection controls.</p></div><span class="secure-pill">● Protected</span></div>
            <div class="security-grid">
                <div class="security-card"><div class="security-icon blue">♙</div><span class="security-status">Enabled</span><h3>Authentication</h3><p>Session-based login and account creation are handled on the PHP server.</p></div>
                <div class="security-card"><div class="security-icon purple">▣</div><span class="security-status">Active</span><h3>Document Access</h3><p>Only the logged-in owner or an approved recipient can access document actions.</p></div>
                <div class="security-card"><div class="security-icon cyan">◷</div><span class="security-status">Active</span><h3>Activity Logging</h3><p>Uploads, downloads, shares, stars and deletes are written to SQLite.</p></div>
                <div class="security-card"><div class="security-icon green">✓</div><span class="security-status">Active</span><h3>Password Protection</h3><p>User passwords are stored using PHP password hashing instead of plain text.</p></div>
            </div>
        </section>


        <!-- ADMIN -->
        <section class="page" id="page-admin">
            <div class="page-head">
                <div><div class="eyebrow">ROLE-BASED ADMINISTRATION</div><h1>Admin Console</h1><p>Monitor users, documents, storage and sharing records.</p></div>
                <span class="secure-pill">● Admin Access</span>
            </div>

            <div class="stats-grid admin-stats">
                <div class="stat-card blue"><div class="stat-icon">♙</div><span>Total Users</span><strong id="adminUsers">0</strong><small>Registered accounts</small></div>
                <div class="stat-card purple"><div class="stat-icon">▣</div><span>Active Documents</span><strong id="adminDocs">0</strong><small>Non-deleted documents</small></div>
                <div class="stat-card cyan"><div class="stat-icon">☁</div><span>Total Storage</span><strong id="adminStorage">0 B</strong><small>Across all users</small></div>
                <div class="stat-card green"><div class="stat-icon">⇄</div><span>Total Shares</span><strong id="adminShares">0</strong><small>Sharing records</small></div>
            </div>

            <section class="surface admin-table">
                <div class="surface-head"><div><h2>User Management</h2><small>Role-based access overview</small></div><button class="btn" id="refreshAdmin">↻ Refresh</button></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                        <tbody id="adminUsersTable"></tbody>
                    </table>
                </div>
            </section>
        </section>

        <!-- SETTINGS -->
        <section class="page" id="page-settings">
            <div class="page-head"><div><div class="eyebrow">PREFERENCES</div><h1>Settings</h1><p>Update your account profile and interface theme.</p></div></div>
            <div class="settings-grid">
                <section class="surface"><h2>Profile</h2><div class="field"><label>Full name</label><input id="settingsName"></div><div class="field"><label>Email</label><input id="settingsEmail" type="email"></div><button class="btn primary" id="saveSettings">Save Changes</button></section>
                <section class="surface"><h2>Appearance</h2><div class="field"><label>Theme</label><select id="settingsTheme"><option value="light">Light mode</option><option value="dark">Dark mode</option></select></div><p class="muted-text">Dark mode is saved in your browser so your preference remains after reopening the site.</p></section>
            </div>
        </section>
    </main>
</div>

<!-- PROFILE -->
<div class="profile-popover" id="profilePopover">
    <div class="profile-pop-head"><div class="avatar" id="popupAvatar">DU</div><div><b id="popupName">Guest User</b><small id="popupEmail">Not signed in</small></div></div>
    <div class="profile-info-box"><b>Account status</b><span id="popupStatus">Signed out</span></div>
    <button id="goSettings">⚙ Account Settings</button>
    <button class="logout" id="logoutBtn">⇥ Log Out</button>
</div>

<!-- AUTH -->
<div class="modal" id="authModal">
    <div class="modal-shell auth-shell">
        <button class="modal-close" data-close="authModal">×</button>
        <div class="auth-brand"><div class="brand-icon">D</div><div><b>Docura</b><small>Secure Digital Document Locker</small></div></div>
        <div class="auth-tabs"><button class="active" data-auth="signin">Sign In</button><button data-auth="signup">Create Account</button></div>

        <form id="signinForm">
            <h2>Welcome back 👋</h2><p>Sign in to access your documents.</p>
            <label>Email address<input type="email" id="loginEmail" required placeholder="you@example.com"></label>
            <label>Password<input type="password" id="loginPassword" required minlength="6" placeholder="At least 6 characters"></label>
            <button class="btn primary wide" type="submit">Sign In →</button>
        </form>

        <form id="signupForm" class="hidden">
            <h2>Create your account ✨</h2><p>Start your personal document vault.</p>
            <label>Full name<input id="signupName" required placeholder="Rishi Kumar"></label>
            <label>Email address<input type="email" id="signupEmail" required placeholder="you@example.com"></label>
            <label>Password<input type="password" id="signupPassword" required minlength="6"></label>
            <label>Confirm password<input type="password" id="signupPassword2" required minlength="6"></label>
            <label class="check-row"><input type="checkbox" required> I agree to the account terms.</label>
            <button class="btn primary wide" type="submit">Create Account →</button>
        </form>
        <div class="demo-note">Real accounts are stored securely in SQLite through the PHP backend.</div>
    </div>
</div>

<!-- UPLOAD -->
<div class="modal" id="uploadModal">
    <div class="modal-shell">
        <button class="modal-close" data-close="uploadModal">×</button>
        <div class="auth-brand"><div class="brand-icon">↑</div><div><b>Upload Documents</b><small>Store files in the server vault</small></div></div>
        <div class="drop-zone" id="dropZone">
            <div class="drop-icon">⇧</div><h3>Drop files here</h3><p>or choose documents from your computer</p>
            <input id="fileInput" type="file" multiple hidden>
            <button type="button" class="btn" id="chooseFiles">Choose Files</button>
        </div>
        <div id="uploadQueue" class="upload-queue"></div>
        <div class="modal-footer"><button class="btn" data-close="uploadModal">Cancel</button><button class="btn primary" id="confirmUpload" disabled>Upload Files</button></div>
    </div>
</div>

<!-- FOLDER -->
<div class="modal" id="folderModal">
    <div class="modal-shell">
        <button class="modal-close" data-close="folderModal">×</button>
        <div class="auth-brand"><div class="brand-icon">▤</div><div><b>Create New Folder</b><small>Organize documents by category</small></div></div>
        <div class="field"><label>Folder name</label><input id="newFolderName" placeholder="e.g. College Certificates"></div>
        <div class="modal-footer"><button class="btn" data-close="folderModal">Cancel</button><button class="btn primary" id="createFolderBtn">Create Folder</button></div>
    </div>
</div>

<!-- SHARE -->
<div class="modal" id="shareModal">
    <div class="modal-shell">
        <button class="modal-close" data-close="shareModal">×</button>
        <div class="auth-brand"><div class="brand-icon">⇄</div><div><b>Share Document</b><small id="shareDocumentName"></small></div></div>
        <div class="field"><label>Recipient email</label><input type="email" id="shareEmail" placeholder="registered-user@example.com"></div>
        <div class="field"><label>Permission</label><select id="sharePermission"><option value="view">View only</option><option value="download">View & Download</option></select></div>
        <div class="demo-note">The recipient must already have a Docura account in this version. The share record is stored in SQLite.</div>
        <div class="modal-footer"><button class="btn" data-close="shareModal">Cancel</button><button class="btn primary" id="createShareBtn">Create Share</button></div>
    </div>
</div>

<!-- PREVIEW -->
<div class="modal" id="previewModal">
    <div class="modal-shell preview-shell">
        <button class="modal-close" data-close="previewModal">×</button>
        <div class="auth-brand"><div class="brand-icon">▣</div><div><b id="previewName"></b><small id="previewMeta"></small></div></div>
        <div class="preview-box" id="previewBox"></div>
        <div class="modal-footer"><button class="btn" data-close="previewModal">Close</button><button class="btn primary" id="downloadPreviewBtn">↓ Download</button></div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
window.DOCURA_CSRF = <?= json_encode($csrf) ?>;
</script>
<script src="assets/script.js"></script>
</body>
</html>
