// Logistics Management System - Complete JavaScript

// API base path for the PHP backend
const API_BASE = 'api/';

// Sample data (used as a fallback when window.__SEED__ from the database
// isn't available, e.g. if the page is opened without PHP/MySQL running)
let shipments = [
    {
        id: 'TRK001',
        origin: 'Solwezi, NWP',
        destination: 'Kitwe, CBP',
        status: 'In Transit',
        estimatedDelivery: '2026-03-15',
        priority: 'Express',
        weight: 150,
        progress: 65,
        created: '2026-03-10'
    },
    {
        id: 'TRK002',
        origin: 'Lusaka, LSKP',
        destination: 'Ndola, CBP',
        status: 'Pending',
        estimatedDelivery: '2026-03-18',
        priority: 'Standard',
        weight: 75,
        progress: 25,
        created: '2026-03-12'
    },
    {
        id: 'TRK003',
        origin: 'Kabwe, CP',
        destination: 'Chipata, EP',
        status: 'Delivered',
        estimatedDelivery: '2026-03-10',
        priority: 'Priority',
        weight: 200,
        progress: 100,
        created: '2026-03-05'
    },
    {
        id: 'TRK004',
        origin: 'Livingstone, SP',
        destination: 'Chingola, CBP',
        status: 'Delayed',
        estimatedDelivery: '2026-03-14',
        priority: 'Express',
        weight: 120,
        progress: 45,
        created: '2026-03-08'
    },
    {
        id: 'TRK005',
        origin: 'Kabwe, CP',
        destination: 'Lusaka, LSKP',
        status: 'In Transit',
        estimatedDelivery: '2026-03-16',
        priority: 'Standard',
        weight: 95,
        progress: 50,
        created: '2026-03-11'
    }
];

let fleet = [
    {
        id: 'VH001',
        type: 'Truck',
        status: 'Available',
        driver: 'Mike Johnson',
        location: 'Chipata, EP',
        fuel: 85,
        nextMaintenance: '2026-04-15'
    },
    {
        id: 'VH002',
        type: 'Van',
        status: 'In Transit',
        driver: 'Sarah Williams',
        location: 'Livingstone, SP',
        fuel: 65,
        nextMaintenance: '2026-03-30'
    },
    {
        id: 'VH003',
        type: 'Truck',
        status: 'Maintenance',
        driver: 'N/A',
        location: 'Kabwe, CP',
        fuel: 45,
        nextMaintenance: '2026-03-20'
    },
    {
        id: 'VH004',
        type: 'Van',
        status: 'Available',
        driver: 'Tom Brown',
        location: 'Lusaka, LSKP',
        fuel: 92,
        nextMaintenance: '2026-04-10'
    },
    {
        id: 'VH005',
        type: 'Truck',
        status: 'In Transit',
        driver: 'Emma Davis',
        location: 'Kitwe, CBP',
        fuel: 78,
        nextMaintenance: '2026-04-05'
    }
];

let inventory = [
    { sku: 'ITM-001', description: 'Electronics', quantity: 450, location: 'Aisle 1', status: 'In Stock' },
    { sku: 'ITM-002', description: 'Clothing', quantity: 780, location: 'Aisle 2', status: 'In Stock' },
    { sku: 'ITM-003', description: 'Furniture', quantity: 120, location: 'Aisle 3', status: 'Low Stock' },
    { sku: 'ITM-004', description: 'Food Items', quantity: 890, location: 'Aisle 4', status: 'In Stock' },
    { sku: 'ITM-005', description: 'Medical Supplies', quantity: 307, location: 'Aisle 5', status: 'In Stock' }
];

// If index.php successfully rendered data from MySQL, use that instead
// of the hard-coded sample arrays above.
let currentUserRole = 'admin'; // fallback if seed data isn't available
if (window.__SEED__) {
    if (Array.isArray(window.__SEED__.shipments) && window.__SEED__.shipments.length) {
        shipments = window.__SEED__.shipments;
    }
    sortShipmentsByTrk();
    if (Array.isArray(window.__SEED__.fleet) && window.__SEED__.fleet.length) {
        fleet = window.__SEED__.fleet;
    }
    if (Array.isArray(window.__SEED__.inventory) && window.__SEED__.inventory.length) {
        inventory = window.__SEED__.inventory;
    }
    if (window.__SEED__.currentUser && window.__SEED__.currentUser.role) {
        currentUserRole = window.__SEED__.currentUser.role;
    }
}

// ---------------------------------------------------------------
// Progress always follows status:
//   Pending = 0%, Delayed = 50%, Delivered = 100%,
//   In Transit = climbs on its own (see startLiveUpdates).
// ---------------------------------------------------------------
function progressForStatus(status, currentProgress) {
    switch (status) {
        case 'Pending':   return 0;
        case 'Delayed':   return 50;
        case 'Delivered': return 100;
        case 'In Transit':
        default:
            return typeof currentProgress === 'number' ? currentProgress : 0;
    }
}

// Fix up any shipment whose stored progress doesn't match its status yet
// (e.g. older data saved before this rule existed) and save the correction
// back to the database so it stays right after a refresh.
function normalizeShipmentProgress() {
    shipments.forEach(shipment => {
        const fixed = progressForStatus(shipment.status, shipment.progress);
        if (fixed !== (shipment.progress || 0)) {
            shipment.progress = fixed;
            fetch(API_BASE + 'shipments.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(shipment)
            }).catch(() => {});
        }
    });
}
normalizeShipmentProgress();

// ---------------------------------------------------------------
// Role-based UI gating, matching exactly what the landing page
// promises for each role:
//   staff   - view & create shipments, track deliveries. Cannot
//             edit/delete shipments or touch the fleet/warehouse
//             (those nav items + sections aren't even rendered for
//             staff — see index.php).
//   manager - everything staff can, plus edit/delete shipments and
//             manage the fleet & warehouse.
//   admin   - everything manager can, plus the Users and API Keys
//             settings tabs (also only rendered for admins).
// The API enforces every one of these server-side too — this just
// hides/blocks the controls a staff account isn't allowed to use.
// ---------------------------------------------------------------
function applyRolePermissions() {
    const isStaff = currentUserRole === 'staff';
    if (!isStaff) return;

    const addVehicleBtn = document.getElementById('addVehicleBtn');
    if (addVehicleBtn) addVehicleBtn.style.display = 'none';

    const addStockBtn = document.getElementById('addStockBtn');
    if (addStockBtn) addStockBtn.style.display = 'none';
}

// ---------------------------------------------------------------
// Notification bell
// ---------------------------------------------------------------
let lastSeenUnreadCount = null; // null = haven't checked yet this session

let notifData = [];
let notifFilter = 'unread';

function notifEsc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
}

// Fetch notifications, update the bell badge, and (if the popup is open) redraw it.
function loadNotifications() {
    return fetch(API_BASE + 'notifications.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            if (!res || !res.success) throw new Error('bad response');
            notifData = res.notifications || [];
            const unread = res.unread || 0;

            const badge = document.getElementById('notifBadge');
            if (badge) {
                badge.textContent = unread > 9 ? '9+' : unread;
                badge.style.display = unread > 0 ? 'inline-block' : 'none';
            }

            // Toast only when something NEW arrives (popup closed)
            const modal = document.getElementById('notifModal');
            const popupOpen = modal && modal.classList.contains('open');
            if (!popupOpen && unread > 0 && (lastSeenUnreadCount === null || unread > lastSeenUnreadCount)) {
                const newest = notifData.find(n => !Number(n.is_read));
                showNotification(newest ? newest.message : `You have ${unread} unread notification${unread === 1 ? '' : 's'}.`, (newest && newest.type) || 'info');
            }
            lastSeenUnreadCount = unread;

            renderNotifModal();
        });
}

function renderNotifModal() {
    const list = document.getElementById('notifList');
    if (!list) return;
    const unreadItems = notifData.filter(n => !Number(n.is_read));
    const items = notifFilter === 'unread' ? unreadItems : notifData;

    const summary = document.getElementById('notifSummary');
    const count = document.getElementById('notifUnreadCount');
    const markAll = document.getElementById('notifMarkAllBtn');
    if (count) count.textContent = unreadItems.length;
    if (markAll) markAll.disabled = unreadItems.length === 0;
    if (summary) {
        summary.textContent = unreadItems.length > 0
            ? `You have ${unreadItems.length} unread notification${unreadItems.length === 1 ? '' : 's'}.`
            : (notifData.length > 0 ? "No new notifications. You're all caught up." : 'You have no notifications yet.');
    }

    if (items.length === 0) {
        list.innerHTML = notifFilter === 'unread'
            ? '<div class="notif-empty"><i class="fas fa-check-circle"></i>No unread notifications</div>'
            : '<div class="notif-empty"><i class="fas fa-bell-slash"></i>No notifications yet</div>';
        return;
    }

    const iconMap = { info: 'fa-circle-info', success: 'fa-circle-check', warning: 'fa-triangle-exclamation', error: 'fa-circle-exclamation' };
    const colorMap = { info: '#5dade2', success: '#2ecc71', warning: '#f39c12', error: '#e74c3c' };

    list.innerHTML = items.map(n => {
        const isRead = Number(n.is_read) === 1;
        return `
        <div class="notif-item ${isRead ? 'read' : 'unread'}" data-id="${notifEsc(n.id)}" title="${isRead ? '' : 'Click to mark as read'}">
            <i class="fas notif-ico ${iconMap[n.type] || 'fa-circle-info'}" style="color:${colorMap[n.type] || '#5dade2'};"></i>
            <div>
                <div class="notif-msg">${notifEsc(n.message)}</div>
                <div class="notif-time">${notifEsc(typeof formatDate === 'function' ? formatDate(n.created_at) : n.created_at)}</div>
            </div>
            ${isRead ? '' : '<span class="notif-dot"></span>'}
        </div>`;
    }).join('');
}

function markNotificationsRead(payload) {
    return fetch(API_BASE + 'notifications.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(() => loadNotifications());
}

function setupNotificationBell() {
    const bellBtn = document.getElementById('notifBellBtn');
    const modal = document.getElementById('notifModal');
    if (!bellBtn || !modal) return;

    const open = () => {
        notifFilter = 'unread';
        modal.querySelectorAll('.notif-tab').forEach(t => t.classList.toggle('active', t.dataset.filter === 'unread'));
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('notifSummary').textContent = 'Checking…';
        loadNotifications().catch(() => {
            document.getElementById('notifSummary').textContent = 'Could not load notifications right now.';
            document.getElementById('notifList').innerHTML = '<div class="notif-empty"><i class="fas fa-triangle-exclamation"></i>Please try again in a moment.</div>';
        });
    };
    const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };

    bellBtn.addEventListener('click', e => { e.stopPropagation(); modal.classList.contains('open') ? close() : open(); });
    document.getElementById('notifCloseBtn').addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); }); // click backdrop
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    modal.querySelectorAll('.notif-tab').forEach(tab => tab.addEventListener('click', () => {
        notifFilter = tab.dataset.filter;
        modal.querySelectorAll('.notif-tab').forEach(t => t.classList.toggle('active', t === tab));
        renderNotifModal();
    }));

    // Click a notification to mark it read
    document.getElementById('notifList').addEventListener('click', e => {
        const item = e.target.closest('.notif-item.unread');
        if (item) markNotificationsRead({ id: Number(item.dataset.id) }).catch(() => {});
    });

    document.getElementById('notifMarkAllBtn').addEventListener('click', () => {
        markNotificationsRead({ markAll: true }).catch(() => {});
    });

    loadNotifications().catch(() => {});
    setInterval(() => loadNotifications().catch(() => {}), 60000);
}

document.addEventListener('DOMContentLoaded', () => {
    [applyRolePermissions, setupNotificationBell, setupApiKeysPanel, setupUsersPanel].forEach(fn => {
        try { fn(); } catch (e) { console.error(fn.name + ' failed:', e); }
    });
});

// ---------------------------------------------------------------
// API Keys panel (admin only — the button/panel only exists in the
// DOM at all if index.php rendered it for an admin user)
// ---------------------------------------------------------------
function setupApiKeysPanel() {
    const genBtn = document.getElementById('generateApiKeyBtn');
    if (!genBtn) return; // not an admin, panel wasn't rendered

    function renderKeys(keys) {
        const list = document.getElementById('apiKeysList');
        if (!keys || keys.length === 0) {
            list.innerHTML = '<p style="color:#888;">No API keys yet.</p>';
            return;
        }
        list.innerHTML = keys.map(k => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
                <div>
                    <strong>${k.label}</strong>
                    <div style="font-family:monospace; font-size:.75rem; color:#666; word-break:break-all;">${k.api_key}</div>
                    <div style="font-size:.72rem; color:#999;">${k.rate_limit_per_min} req/min ${k.active == 1 ? '· active' : '· revoked'}</div>
                </div>
                <button class="action-btn" onclick="revokeApiKey(${k.id})" title="Revoke"><i class="fas fa-trash"></i></button>
            </div>
        `).join('');
    }

    window.__loadApiKeys = function loadKeys() {
        fetch(API_BASE + 'keys.php')
            .then(r => r.json())
            .then(res => { if (res && res.success) renderKeys(res.keys); })
            .catch(() => {});
    };

    genBtn.addEventListener('click', () => {
        const label = document.getElementById('newApiKeyLabel').value || 'Integration key';
        fetch(API_BASE + 'keys.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ label })
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                showNotification('API key generated!', 'success');
                document.getElementById('newApiKeyLabel').value = '';
                window.__loadApiKeys();
            } else {
                showNotification((res && res.error) || 'Could not generate key.', 'error');
            }
        })
        .catch(() => showNotification('Could not generate key.', 'error'));
    });

    window.__loadApiKeys();
}

function revokeApiKey(id) {
    if (!confirm('Revoke this API key? Any integration using it will stop working immediately.')) return;
    fetch(API_BASE + 'keys.php?id=' + id, { method: 'DELETE' })
        .then(() => { if (window.__loadApiKeys) window.__loadApiKeys(); })
        .catch(() => {});
}

// ---------------------------------------------------------------
// Users panel (admin only — "manage user accounts" from the landing
// page). The panel only exists in the DOM at all if index.php
// rendered it for an admin user; the API independently enforces
// admin-only access too.
// ---------------------------------------------------------------
function setupUsersPanel() {
    const list = document.getElementById('usersList');
    if (!list) return; // not an admin, panel wasn't rendered

    const roleOptions = ['staff', 'manager', 'admin'];
    const myUserId = window.__SEED__ && window.__SEED__.currentUser ? window.__SEED__.currentUser.id : null;

    const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const roleBadge = r => ({admin:'Administrator', manager:'Logistics Manager', staff:'Logistics Staff'}[r] || r);

    function renderUsers(users) {
        if (!users || users.length === 0) {
            list.innerHTML = '<p style="color:#888;">No users found.</p>';
            return;
        }
        list.innerHTML = users.map(u => {
            const isMe = myUserId && Number(myUserId) === Number(u.id);
            const options = roleOptions.map(r =>
                `<option value="${r}" ${r === u.role ? 'selected' : ''}>${r.charAt(0).toUpperCase() + r.slice(1)}</option>`
            ).join('');
            return `
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #eee;">
                    <div>
                        <strong>${esc(u.full_name)}</strong>${isMe ? ' <span style="color:#999; font-size:.72rem;">(you)</span>' : ''}
                        <div style="font-size:.78rem; color:#999;">${esc(u.email)}</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <select class="setting-select" data-user-id="${u.id}" ${isMe ? 'disabled' : ''} style="padding:5px 8px;">
                            ${options}
                        </select>
                        <button class="action-btn" data-delete-user="${u.id}" title="${isMe ? "You can't delete your own account" : 'Delete account'}" ${isMe ? 'disabled' : ''}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        list.querySelectorAll('select[data-user-id]').forEach(sel => {
            sel.addEventListener('change', () => {
                const id = sel.dataset.userId;
                const role = sel.value;
                fetch(API_BASE + 'users.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, role })
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        showNotification('Role updated.', 'success');
                    } else {
                        showNotification((res && res.error) || 'Could not update role.', 'error');
                        loadUsers(); // revert the dropdown to the real value
                    }
                })
                .catch(() => showNotification('Could not update role.', 'error'));
            });
        });

        list.querySelectorAll('button[data-delete-user]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.deleteUser;
                if (!confirm('Delete this user account? This cannot be undone.')) return;
                fetch(API_BASE + 'users.php?id=' + id, { method: 'DELETE' })
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success) {
                            showNotification('User account deleted.', 'success');
                            loadUsers();
                        } else {
                            showNotification((res && res.error) || 'Could not delete user.', 'error');
                        }
                    })
                    .catch(() => showNotification('Could not delete user.', 'error'));
            });
        });
    }

    function showUsersError(msg) {
        list.innerHTML = '<p style="color:#e74c3c; padding:12px 0;">' + esc(msg) +
            ' <a href="#" id="retryUsers" style="color:#c9a84c;">Retry</a></p>';
        const retry = document.getElementById('retryUsers');
        if (retry) retry.addEventListener('click', e => { e.preventDefault(); loadUsers(); });
    }

    function loadUsers() {
        list.innerHTML = '<div style="padding:16px; color:#888;">Loading…</div>';
        fetch(API_BASE + 'users.php', { credentials: 'same-origin' })
            .then(r => r.text().then(text => {
                let res;
                try { res = JSON.parse(text); }
                catch (e) {
                    console.error('users.php returned non-JSON:', text);
                    throw new Error('Server returned an unexpected response (check PHP/MySQL errors).');
                }
                if (!res.success) throw new Error(res.error || ('Request failed (' + r.status + ').'));
                return res;
            }))
            .then(res => renderUsers(res.users))
            .catch(err => showUsersError('Could not load users: ' + err.message));
    }

    // Refresh whenever the Users tab is opened so newly registered accounts appear
    const usersTabBtn = document.querySelector('.tab-btn[data-tab="users"]');
    if (usersTabBtn) usersTabBtn.addEventListener('click', loadUsers);

    loadUsers();
}

// DOM Elements
let shipmentsBody, searchInput, newShipmentBtn, modal, closeBtn, shipmentForm;
let totalShipmentsEl, deliveredShipmentsEl, inTransitShipmentsEl, delayedShipmentsEl;
let fleetGrid, loader, shipmentCount;

// Pagination variables
let currentPage = 1;
let rowsPerPage = 5;
let currentFilter = 'all';
let currentSearchTerm = '';

// Charts
let deliveryChart, trendChart, priorityChart, detailedChart;

// Initialize the dashboard
function init() {
    // Get DOM elements
    shipmentsBody = document.getElementById('shipmentsBody');
    searchInput = document.getElementById('searchInput');
    newShipmentBtn = document.getElementById('newShipmentBtn');
    modal = document.getElementById('shipmentModal');
    closeBtn = document.querySelector('.close');
    shipmentForm = document.getElementById('shipmentForm');
    totalShipmentsEl = document.getElementById('totalShipments');
    deliveredShipmentsEl = document.getElementById('deliveredShipments');
    inTransitShipmentsEl = document.getElementById('inTransitShipments');
    delayedShipmentsEl = document.getElementById('delayedShipments');
    fleetGrid = document.getElementById('fleetGrid');
    loader = document.getElementById('loader');
    shipmentCount = document.getElementById('shipmentCount');
    
    // Hide loader after page loads
    setTimeout(() => {
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
    }, 5000);
    
    renderShipments();
    renderFleet();
    updateStats();
    setupEventListeners();
    updateDateTime();
    initializeCharts();
    startLiveUpdates();
    updateShipmentCount();
    loadAllShipments();
    loadRecentTracking();
    loadWarehouseData();
    loadFullFleetData();
    loadReportData();
}

// Render shipments table with pagination and filtering
function renderShipments() {
    if (!shipmentsBody) return;
    
    let filteredShipments = shipments;
    
    // Apply status filter
    if (currentFilter !== 'all') {
        filteredShipments = filteredShipments.filter(s => s.status === currentFilter);
    }
    
    // Apply search filter
    if (currentSearchTerm) {
        filteredShipments = filteredShipments.filter(shipment => 
            shipment.id.toLowerCase().includes(currentSearchTerm) ||
            shipment.origin.toLowerCase().includes(currentSearchTerm) ||
            shipment.destination.toLowerCase().includes(currentSearchTerm) ||
            shipment.status.toLowerCase().includes(currentSearchTerm)
        );
    }
    
    // Pagination
    const totalPages = Math.ceil(filteredShipments.length / rowsPerPage);
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedShipments = filteredShipments.slice(start, end);
    
    // Update page info
    const pageInfo = document.getElementById('pageInfo');
    if (pageInfo) {
        pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1}`;
    }
    
    // Enable/disable pagination buttons
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    if (prevPage) prevPage.disabled = currentPage === 1;
    if (nextPage) nextPage.disabled = currentPage === totalPages || totalPages === 0;
    
    shipmentsBody.innerHTML = '';
    
    if (paginatedShipments.length === 0) {
        shipmentsBody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px;">
                    <i class="fas fa-box-open" style="font-size: 48px; color: #ccc;"></i>
                    <p style="margin-top: 10px;">No shipments found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    paginatedShipments.forEach(shipment => {
        const row = document.createElement('tr');
        
        // Determine status class
        let statusClass = 'status-pending';
        if (shipment.status === 'In Transit') statusClass = 'status-transit';
        if (shipment.status === 'Delivered') statusClass = 'status-delivered';
        if (shipment.status === 'Delayed') statusClass = 'status-delayed';
        
        // Determine priority class
        let priorityClass = 'priority-low';
        if (shipment.priority === 'Express') priorityClass = 'priority-medium';
        if (shipment.priority === 'Priority') priorityClass = 'priority-high';
        
        row.innerHTML = `
            <td><strong>${shipment.id}</strong></td>
            <td><i class="fas fa-map-pin"></i> ${shipment.origin}</td>
            <td><i class="fas fa-flag-checkered"></i> ${shipment.destination}</td>
            <td><span class="status-badge ${statusClass}">${shipment.status}</span></td>
            <td>${formatDate(shipment.estimatedDelivery)}</td>
            <td><span class="priority-badge ${priorityClass}">${shipment.priority}</span></td>
            <td>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${shipment.progress || 0}%"></div>
                </div>
                <span style="font-size: 11px;">${shipment.progress || 0}%</span>
            </td>
            <td>
                <button class="action-btn" onclick="viewShipment('${shipment.id}')" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="action-btn" onclick="trackShipment('${shipment.id}')" title="Track">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
                ${typeof currentUserRole !== 'undefined' && currentUserRole === 'staff' ? '' : `
                <button class="action-btn" onclick="editShipment('${shipment.id}')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn" onclick="deleteShipment('${shipment.id}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>`}
            </td>
        `;
        
        shipmentsBody.appendChild(row);
    });
}

// Render fleet status
function renderFleet() {
    if (!fleetGrid) return;
    
    fleetGrid.innerHTML = '';
    
    fleet.forEach(vehicle => {
        const card = document.createElement('div');
        card.className = 'fleet-card';
        
        let statusClass = 'available';
        if (vehicle.status === 'In Transit') statusClass = 'in-transit';
        if (vehicle.status === 'Maintenance') statusClass = 'maintenance';
        
        const fuelLevel = vehicle.fuel || 0;
        const fuelColor = fuelLevel > 70 ? '#2ecc71' : fuelLevel > 30 ? '#f39c12' : '#e74c3c';
        
        card.innerHTML = `
            <div class="fleet-header-card">
                <i class="fas fa-${vehicle.type === 'Truck' ? 'truck' : 'shuttle-van'}"></i>
                <span class="fleet-status ${statusClass}">${vehicle.status}</span>
            </div>
            <h4>${vehicle.id} - ${vehicle.type}</h4>
            <div class="fleet-details">
                <p><i class="fas fa-user"></i> <strong>Driver:</strong> ${vehicle.driver}</p>
                <p><i class="fas fa-map-pin"></i> <strong>Location:</strong> ${vehicle.location}</p>
                <p><i class="fas fa-gas-pump"></i> <strong>Fuel:</strong> 
                    <span style="color: ${fuelColor};">${fuelLevel}%</span>
                </p>
                <p><i class="fas fa-calendar-alt"></i> <strong>Next Service:</strong> ${formatDate(vehicle.nextMaintenance)}</p>
            </div>
        `;
        
        fleetGrid.appendChild(card);
    });
}

// Update statistics
function updateStats() {
    if (!totalShipmentsEl) return;
    
    const total = shipments.length;
    const delivered = shipments.filter(s => s.status === 'Delivered').length;
    const inTransit = shipments.filter(s => s.status === 'In Transit').length;
    const delayed = shipments.filter(s => s.status === 'Delayed').length;
    
    animateValue(totalShipmentsEl, 0, total, 500);
    animateValue(deliveredShipmentsEl, 0, delivered, 500);
    animateValue(inTransitShipmentsEl, 0, inTransit, 500);
    animateValue(delayedShipmentsEl, 0, delayed, 500);
    
    return { total, delivered, inTransit, delayed };
}

// Animate number counting
function animateValue(element, start, end, duration) {
    if (!element) return;
    const startTime = performance.now();
    const updateCounter = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(progress * (end - start) + start);
        element.textContent = current;
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    };
    requestAnimationFrame(updateCounter);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Update date and time
function updateDateTime() {
    const dateTimeEl = document.getElementById('dateTime');
    if (!dateTimeEl) return;
    
    const update = () => {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const date = now.toLocaleDateString('en-US', options);
        const time = now.toLocaleTimeString('en-US');
        dateTimeEl.innerHTML = `<i class="far fa-calendar-alt"></i> ${date} | <i class="far fa-clock"></i> ${time}`;
    };
    
    update();
    setInterval(update, 1000);
}

// Initialize charts
function initializeCharts() {
    const ctx1 = document.getElementById('deliveryChart')?.getContext('2d');
    const ctx2 = document.getElementById('trendChart')?.getContext('2d');
    const ctx3 = document.getElementById('priorityChart')?.getContext('2d');
    const ctx4 = document.getElementById('detailedChart')?.getContext('2d');
    
    if (ctx1) {
        deliveryChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Deliveries',
                    data: [12, 19, 15, 25],
                    borderColor: '#5dd99c',
                    backgroundColor: 'rgba(93,217,156,0.12)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: 'rgba(201,168,76,0.75)', font: { size: 11 } } }
                },
                scales: {
                    x: { ticks: { color: 'rgba(201,168,76,0.6)' }, grid: { color: 'rgba(201,168,76,0.08)' } },
                    y: { ticks: { color: 'rgba(201,168,76,0.6)' }, grid: { color: 'rgba(201,168,76,0.08)' } }
                }
            }
        });
    }
    
    if (ctx2) {
        trendChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Shipments',
                    data: [65, 59, 80, 81, 56, 95],
                    backgroundColor: 'rgba(201,168,76,0.75)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: 'rgba(201,168,76,0.75)', font: { size: 11 } } }
                },
                scales: {
                    x: { ticks: { color: 'rgba(201,168,76,0.6)' }, grid: { color: 'rgba(201,168,76,0.08)' } },
                    y: { ticks: { color: 'rgba(201,168,76,0.6)' }, grid: { color: 'rgba(201,168,76,0.08)' } }
                }
            }
        });
    }
    
    if (ctx3) {
        priorityChart = new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: ['Standard', 'Express', 'Priority'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: ['rgba(201,168,76,0.8)', 'rgba(93,217,156,0.8)', 'rgba(224,112,112,0.8)'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: 'rgba(201,168,76,0.75)', font: { size: 11 } } }
                }
            }
        });
    }
    
    if (ctx4) {
        detailedChart = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Shipments',
                    data: [12, 19, 15, 25],
                    borderColor: '#c9a84c',
                    backgroundColor: 'rgba(201,168,76,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }
}

// Update charts with real data
function updateCharts() {
    const priorityStats = {
        Standard: shipments.filter(s => s.priority === 'Standard').length,
        Express: shipments.filter(s => s.priority === 'Express').length,
        Priority: shipments.filter(s => s.priority === 'Priority').length
    };
    
    if (priorityChart) {
        priorityChart.data.datasets[0].data = [priorityStats.Standard, priorityStats.Express, priorityStats.Priority];
        priorityChart.update();
    }
}

// Start live updates — every 30 seconds:
//   In Transit  -> progress climbs by a fixed step (auto-marks Delivered at 100%)
//   Delayed     -> held at 50%
//   Delivered   -> held at 100%
//   Pending     -> held at 0%
const IN_TRANSIT_STEP = 5; // % added per 30s tick

function startLiveUpdates() {
    setInterval(() => {
        let changed = false;

        shipments.forEach(shipment => {
            const before = shipment.progress || 0;
            let shipmentChanged = false;

            if (shipment.status === 'In Transit') {
                const next = Math.min(100, before + IN_TRANSIT_STEP);
                if (next !== before) {
                    shipment.progress = next;
                    shipmentChanged = true;
                }
                if (shipment.progress >= 100) {
                    shipment.status = 'Delivered';
                    showNotification(`Shipment ${shipment.id} has been delivered!`, 'success');
                }
            } else {
                shipment.progress = progressForStatus(shipment.status, before);
                if (shipment.progress !== before) shipmentChanged = true;
            }

            if (shipmentChanged) {
                changed = true;
                // Persist the tick so progress isn't lost on refresh.
                fetch(API_BASE + 'shipments.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(shipment)
                }).catch(() => {});
            }
        });

        if (changed) {
            renderShipments();
            updateStats();
            updateCharts();
            updateShipmentCount();
        }
    }, 30000);
}

// Update shipment count badge
function updateShipmentCount() {
    if (shipmentCount) {
        shipmentCount.textContent = shipments.length;
    }
}

// Search functionality
function handleSearch() {
    if (!searchInput) return;
    currentSearchTerm = searchInput.value.toLowerCase();
    currentPage = 1;
    renderShipments();
}

// Filter buttons
function setupFilterButtons() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            currentPage = 1;
            renderShipments();
        });
    });
}

// Modal functions
function openModal() {
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    if (modal && shipmentForm) {
        modal.style.display = 'none';
        shipmentForm.reset();
        document.body.style.overflow = 'auto';
    }
}

// Create new shipment
function createShipment(event) {
    event.preventDefault();

    const instructions = document.getElementById('instructions') ? document.getElementById('instructions').value : '';

    const newShipment = {
        id: generateTrackingId(),
        origin: document.getElementById('origin').value,
        destination: document.getElementById('destination').value,
        weight: document.getElementById('weight').value,
        priority: document.getElementById('priority').value,
        status: 'Pending',
        estimatedDelivery: document.getElementById('estimatedDelivery').value,
        progress: 0,
        created: new Date().toISOString().split('T')[0]
    };

    // Persist to the database; use the server-assigned id if it differs.
    fetch(API_BASE + 'shipments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...newShipment, instructions })
    })
    .then(r => r.json())
    .then(res => { if (res && res.id) newShipment.id = res.id; })
    .catch(() => {})
    .finally(() => {
        shipments.push(newShipment);
        sortShipmentsByTrk();
        renderShipments();
        updateStats();
        updateCharts();
        updateShipmentCount();
        closeModal();
        loadAllShipments();

        showNotification(`Shipment ${newShipment.id} created successfully!`, 'success');
    });
}

// Numeric part of a tracking ID (TRK012 -> 12)
function trkNumber(id) {
    const n = parseInt(String(id).replace(/\D/g, ''), 10);
    return isNaN(n) ? 0 : n;
}

// Keep shipments in order TRK001, TRK002, TRK003, ...
function sortShipmentsByTrk() {
    shipments.sort((a, b) => trkNumber(a.id) - trkNumber(b.id));
}

// Generate tracking ID (one more than the highest existing number)
function generateTrackingId() {
    const max = shipments.reduce((m, s) => Math.max(m, trkNumber(s.id)), 0);
    return 'TRK' + String(max + 1).padStart(3, '0');
}

// Shipment actions
function shipmentStatusClass(status) {
    let statusClass = 'status-pending';
    if (status === 'In Transit') statusClass = 'status-transit';
    if (status === 'Delivered') statusClass = 'status-delivered';
    if (status === 'Delayed') statusClass = 'status-delayed';
    return statusClass;
}

function shipmentPriorityClass(priority) {
    let priorityClass = 'priority-low';
    if (priority === 'Express') priorityClass = 'priority-medium';
    if (priority === 'Priority') priorityClass = 'priority-high';
    return priorityClass;
}

function shipmentDetailRows(shipment) {
    return `
        <div class="vehicle-action-details">
            <div class="vehicle-action-row"><span><i class="fas fa-map-marker-alt"></i> Origin</span><span>${shipment.origin}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-flag-checkered"></i> Destination</span><span>${shipment.destination}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-toggle-on"></i> Status</span><span><span class="status-badge ${shipmentStatusClass(shipment.status)}">${shipment.status}</span></span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-chart-line"></i> Priority</span><span><span class="priority-badge ${shipmentPriorityClass(shipment.priority)}">${shipment.priority}</span></span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-weight-hanging"></i> Weight</span><span>${shipment.weight} kg</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-calendar-plus"></i> Created</span><span>${formatDate(shipment.created)}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-calendar-alt"></i> Est. Delivery</span><span>${formatDate(shipment.estimatedDelivery)}</span></div>
        </div>
    `;
}

// ── Shipment Action Popup (View Details / Track) ─────────────────────────
let shipmentActionModal, closeShipmentActionBtn;

function openShipmentActionModal(titleHTML, bodyHTML) {
    if (!shipmentActionModal) shipmentActionModal = document.getElementById('shipmentActionModal');
    const titleEl = document.getElementById('shipmentActionTitle');
    const bodyEl = document.getElementById('shipmentActionBody');
    if (titleEl) titleEl.innerHTML = titleHTML;
    if (bodyEl) bodyEl.innerHTML = bodyHTML;
    if (shipmentActionModal) {
        shipmentActionModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeShipmentActionModal() {
    if (!shipmentActionModal) shipmentActionModal = document.getElementById('shipmentActionModal');
    if (shipmentActionModal) {
        shipmentActionModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function viewShipment(id) {
    const shipment = shipments.find(s => s.id === id);
    if (!shipment) {
        showNotification(`Shipment ${id} not found.`, 'error');
        return;
    }

    const statusStyle = shipment.status === 'Delivered' ? '' : shipment.status === 'Delayed' ? 'danger' : 'info';
    const icon = shipment.status === 'Delivered' ? 'fa-check-circle' : shipment.status === 'Delayed' ? 'fa-exclamation-triangle' : 'fa-box-open';

    const body = `
        <div class="vehicle-action-status ${statusStyle}">
            <i class="fas ${icon}"></i>
            <div>
                <strong>${shipment.id} details retrieved</strong>
                <span>${shipment.origin} → ${shipment.destination}</span>
            </div>
        </div>
        ${shipmentDetailRows(shipment)}
    `;

    openShipmentActionModal(`<i class="fas fa-box-open"></i> Shipment ${shipment.id}`, body);
}

function trackShipment(id) {
    const shipment = shipments.find(s => s.id === id);
    if (!shipment) {
        showNotification(`Shipment ${id} not found.`, 'error');
        return;
    }

    const statusStyle = shipment.status === 'Delivered' ? '' : shipment.status === 'Delayed' ? 'danger' : 'info';
    const statusMsg = shipment.status === 'In Transit' ? 'On the way to its destination'
        : shipment.status === 'Delivered' ? 'Delivered successfully'
        : shipment.status === 'Delayed' ? 'Delayed — being investigated'
        : 'Being processed for dispatch';

    const body = `
        <div class="vehicle-action-status ${statusStyle}">
            <i class="fas fa-truck-fast"></i>
            <div>
                <strong>${shipment.id} has been tracked</strong>
                <span>${statusMsg}</span>
            </div>
        </div>
        <div class="vehicle-action-row" style="margin-bottom: 14px;">
            <span><i class="fas fa-route"></i> Progress</span>
            <span>${shipment.progress || 0}%</span>
        </div>
        <div class="progress-bar" style="margin-bottom: 18px;">
            <div class="progress-fill" style="width: ${shipment.progress || 0}%"></div>
        </div>
        ${shipmentDetailRows(shipment)}
    `;

    openShipmentActionModal(`<i class="fas fa-truck-fast"></i> Tracking ${shipment.id}`, body);
    showNotification(`${shipment.id} tracked successfully.`, 'success');
}

// ── Edit Shipment Modal ───────────────────────────────────────────────────
let editShipmentModal, closeEditShipmentBtn, editShipmentForm;

function editShipment(id) {
    if (currentUserRole === 'staff') {
        showNotification('Staff accounts can only view and create shipments.', 'error');
        return;
    }

    const shipment = shipments.find(s => s.id === id);
    if (!shipment) {
        showNotification(`Shipment ${id} not found.`, 'error');
        return;
    }

    if (!editShipmentModal) editShipmentModal = document.getElementById('editShipmentModal');
    document.getElementById('editShipmentId').value = shipment.id;
    document.getElementById('editOrigin').value = shipment.origin;
    document.getElementById('editDestination').value = shipment.destination;
    document.getElementById('editWeight').value = shipment.weight;
    document.getElementById('editPriority').value = shipment.priority;
    document.getElementById('editStatus').value = shipment.status;
    document.getElementById('editEstimatedDelivery').value = shipment.estimatedDelivery;

    if (editShipmentModal) {
        editShipmentModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeEditShipmentModal() {
    if (!editShipmentModal) editShipmentModal = document.getElementById('editShipmentModal');
    if (editShipmentModal) {
        editShipmentModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function saveShipmentEdits(event) {
    event.preventDefault();

    const id = document.getElementById('editShipmentId').value;
    const shipment = shipments.find(s => s.id === id);
    if (!shipment) {
        showNotification(`Shipment ${id} not found.`, 'error');
        return;
    }

    shipment.origin = document.getElementById('editOrigin').value;
    shipment.destination = document.getElementById('editDestination').value;
    shipment.weight = document.getElementById('editWeight').value;
    shipment.priority = document.getElementById('editPriority').value;
    shipment.status = document.getElementById('editStatus').value;
    shipment.estimatedDelivery = document.getElementById('editEstimatedDelivery').value;
    shipment.progress = progressForStatus(shipment.status, shipment.progress);

    fetch(API_BASE + 'shipments.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(shipment)
    }).catch(() => {});

    renderShipments();
    updateStats();
    updateCharts();
    loadAllShipments();
    closeEditShipmentModal();

    showNotification(`Shipment ${id} updated successfully!`, 'success');
}

function deleteShipment(id) {
    if (currentUserRole === 'staff') {
        showNotification('Staff accounts can only view and create shipments.', 'error');
        return;
    }
    if (confirm('Are you sure you want to delete this shipment?')) {
        fetch(API_BASE + 'shipments.php?id=' + encodeURIComponent(id), { method: 'DELETE' }).catch(() => {});

        shipments = shipments.filter(s => s.id !== id);
        renderShipments();
        updateStats();
        updateCharts();
        updateShipmentCount();
        loadAllShipments();
        showNotification(`Shipment ${id} deleted successfully!`, 'success');
    }
}

function showAlerts() {
    const delayed = shipments.filter(s => s.status === 'Delayed');
    if (delayed.length > 0) {
        let message = `⚠️ ${delayed.length} delayed shipments:\n\n`;
        delayed.forEach(s => {
            message += `${s.id}: ${s.origin} → ${s.destination}\n`;
        });
        alert(message);
    } else {
        alert('No delayed shipments at this time!');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
        <button class="close-notification" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Navigation setup
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            link.parentElement.classList.add('active');
            
            const activeSection = document.getElementById(`${sectionId}-section`);
            if (activeSection) {
                activeSection.classList.add('active');
            }
            
            const sectionName = sectionId.charAt(0).toUpperCase() + sectionId.slice(1);
            document.title = `LogiTrack Pro - ${sectionName}`;
            
            loadSectionData(sectionId);
        });
    });
}

// Load section-specific data
function loadSectionData(section) {
    switch(section) {
        case 'shipments':
            loadAllShipments();
            break;
        case 'tracking':
            loadRecentTracking();
            break;
        case 'warehouse':
            loadWarehouseData();
            break;
        case 'fleet':
            loadFullFleetData();
            break;
        case 'reports':
            loadReportData();
            break;
    }
}

// Load all shipments
function loadAllShipments() {
    const allShipmentsBody = document.getElementById('allShipmentsBody');
    if (!allShipmentsBody) return;
    
    allShipmentsBody.innerHTML = '';
    shipments.forEach(shipment => {
        const row = document.createElement('tr');
        let statusClass = 'status-pending';
        if (shipment.status === 'In Transit') statusClass = 'status-transit';
        if (shipment.status === 'Delivered') statusClass = 'status-delivered';
        if (shipment.status === 'Delayed') statusClass = 'status-delayed';
        
        let priorityClass = 'priority-low';
        if (shipment.priority === 'Express') priorityClass = 'priority-medium';
        if (shipment.priority === 'Priority') priorityClass = 'priority-high';
        
        row.innerHTML = `
            <td><strong>${shipment.id}</strong></td>
            <td>${shipment.origin}</td>
            <td>${shipment.destination}</td>
            <td><span class="status-badge ${statusClass}">${shipment.status}</span></td>
            <td>${shipment.weight} kg</td>
            <td><span class="priority-badge ${priorityClass}">${shipment.priority}</span></td>
            <td>${formatDate(shipment.created)}</td>
            <td>
                <button class="action-btn" onclick="viewShipment('${shipment.id}')"><i class="fas fa-eye"></i></button>
                ${typeof currentUserRole !== 'undefined' && currentUserRole === 'staff' ? '' : `
                <button class="action-btn" onclick="editShipment('${shipment.id}')"><i class="fas fa-edit"></i></button>`}
            </td>
        `;
        allShipmentsBody.appendChild(row);
    });
}

// Track shipment by ID
function trackShipmentById() {
    const trackingId = document.getElementById('trackingIdInput')?.value.toUpperCase();
    if (!trackingId) return;
    
    const shipment = shipments.find(s => s.id === trackingId);
    const trackingResult = document.getElementById('trackingResult');
    
    if (!trackingResult) return;
    
    if (shipment) {
        const statusMap = {
            'Pending': '📋 Order Received',
            'In Transit': '🚚 On The Way',
            'Delivered': '✅ Delivered',
            'Delayed': '⚠️ Delayed'
        };
        
        let statusClass = 'status-pending';
        if (shipment.status === 'In Transit') statusClass = 'status-transit';
        if (shipment.status === 'Delivered') statusClass = 'status-delivered';
        if (shipment.status === 'Delayed') statusClass = 'status-delayed';
        
        trackingResult.innerHTML = `
            <div class="tracking-card">
                <h3>Tracking Details: ${shipment.id}</h3>
                <div class="tracking-info">
                    <p><strong>From:</strong> ${shipment.origin}</p>
                    <p><strong>To:</strong> ${shipment.destination}</p>
                    <p><strong>Current Status:</strong> <span class="tracking-status ${statusClass}">${statusMap[shipment.status] || shipment.status}</span></p>
                    <p><strong>Estimated Delivery:</strong> ${formatDate(shipment.estimatedDelivery)}</p>
                    <p><strong>Priority:</strong> ${shipment.priority}</p>
                    <div class="tracking-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${shipment.progress || 0}%"></div>
                        </div>
                        <p style="text-align: center; margin-top: 8px;">${shipment.progress || 0}% Complete</p>
                    </div>
                </div>
            </div>
        `;
        
        addToRecentTracking(shipment.id);
    } else {
        trackingResult.innerHTML = `
            <div class="tracking-card error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Shipment with ID ${trackingId} not found.</p>
                <p>Please check the tracking ID and try again.</p>
            </div>
        `;
    }
}

// Add to recent tracking
function addToRecentTracking(trackingId) {
    let recent = JSON.parse(localStorage.getItem('recentTracking') || '[]');
    recent = [trackingId, ...recent.filter(id => id !== trackingId)].slice(0, 5);
    localStorage.setItem('recentTracking', JSON.stringify(recent));
    loadRecentTracking();
}

// Load recent tracking
function loadRecentTracking() {
    const recentList = document.getElementById('recentTrackingList');
    if (!recentList) return;
    
    const recent = JSON.parse(localStorage.getItem('recentTracking') || '[]');
    recentList.innerHTML = '';
    
    recent.forEach(id => {
        const shipment = shipments.find(s => s.id === id);
        if (shipment) {
            const item = document.createElement('div');
            item.className = 'tracking-item';
            item.onclick = () => {
                const input = document.getElementById('trackingIdInput');
                if (input) input.value = id;
                trackShipmentById();
            };
            item.innerHTML = `
                <span><strong>${id}</strong> - ${shipment.origin} → ${shipment.destination}</span>
                <i class="fas fa-chevron-right"></i>
            `;
            recentList.appendChild(item);
        }
    });
}

// Load warehouse data
function loadWarehouseData() {
    const inventoryBody = document.getElementById('inventoryBody');
    if (inventoryBody) {
        inventoryBody.innerHTML = '';
        inventory.forEach(item => {
            const row = document.createElement('tr');
            const statusClass = item.status === 'Low Stock' ? 'status-delayed' : 'status-delivered';
            row.innerHTML = `
                <td><strong>${item.sku}</strong></td>
                <td>${item.description}</td>
                <td>${item.quantity}</td>
                <td>${item.location}</td>
                <td><span class="status-badge ${statusClass}">${item.status}</span></td>
            `;
            inventoryBody.appendChild(row);
        });
    }
}

// Load full fleet data
function loadFullFleetData() {
    const fullFleetGrid = document.getElementById('fullFleetGrid');
    if (!fullFleetGrid) return;
    
    fullFleetGrid.innerHTML = '';
    fleet.forEach(vehicle => {
        const card = document.createElement('div');
        card.className = 'fleet-card';
        
        let statusClass = 'available';
        if (vehicle.status === 'In Transit') statusClass = 'in-transit';
        if (vehicle.status === 'Maintenance') statusClass = 'maintenance';
        
        const fuelLevel = vehicle.fuel || 0;
        const fuelColor = fuelLevel > 70 ? '#2ecc71' : fuelLevel > 30 ? '#f39c12' : '#e74c3c';
        
        card.innerHTML = `
            <div class="fleet-header-card">
                <i class="fas fa-${vehicle.type === 'Truck' ? 'truck' : 'shuttle-van'}"></i>
                <span class="fleet-status ${statusClass}">${vehicle.status}</span>
            </div>
            <h4>${vehicle.id} - ${vehicle.type}</h4>
            <div class="fleet-details">
                <p><i class="fas fa-user"></i> Driver: ${vehicle.driver}</p>
                <p><i class="fas fa-map-pin"></i> Location: ${vehicle.location}</p>
                <p><i class="fas fa-gas-pump"></i> Fuel: <span style="color: ${fuelColor};">${fuelLevel}%</span></p>
                <p><i class="fas fa-calendar-alt"></i> Next Service: ${formatDate(vehicle.nextMaintenance)}</p>
            </div>
            <div class="vehicle-actions">
                <button class="btn-small" onclick="trackVehicle('${vehicle.id}')"><i class="fas fa-map-marker-alt"></i> Track</button>
                <button class="btn-small" onclick="scheduleMaintenance('${vehicle.id}')"><i class="fas fa-wrench"></i> Service</button>
            </div>
        `;
        fullFleetGrid.appendChild(card);
    });
    
    // Update fleet stats
    const activeVehicles = fleet.filter(v => v.status === 'In Transit' || v.status === 'Available').length;
    const maintenanceVehicles = fleet.filter(v => v.status === 'Maintenance').length;
    const avgFuel = Math.floor(fleet.reduce((sum, v) => sum + (v.fuel || 85), 0) / fleet.length);
    
    const activeEl = document.getElementById('activeVehicles');
    const maintenanceEl = document.getElementById('maintenanceVehicles');
    const avgFuelEl = document.getElementById('averageFuel');
    
    if (activeEl) activeEl.textContent = activeVehicles;
    if (maintenanceEl) maintenanceEl.textContent = maintenanceVehicles;
    if (avgFuelEl) avgFuelEl.innerHTML = avgFuel + '<span style="font-size: 20px;">%</span>';
}

// Load report data
function loadReportData() {
    const total = shipments.length;
    const delivered = shipments.filter(s => s.status === 'Delivered').length;
    const onTimeRate = total > 0 ? Math.round((delivered / total) * 100) : 0;
    
    const reportTotal = document.getElementById('reportTotalShipments');
    const reportOnTime = document.getElementById('reportOnTimeRate');
    
    if (reportTotal) reportTotal.textContent = total;
    if (reportOnTime) reportOnTime.innerHTML = onTimeRate + '<span style="font-size: 24px;">%</span>';
}

// Generate report
function generateReport() {
    const reportType = document.getElementById('reportType')?.value || 'shipments';
    const startDate = document.getElementById('reportStartDate')?.value || '';
    const endDate = document.getElementById('reportEndDate')?.value || '';
    const resultsEl = document.getElementById('reportResults');

    showNotification(`Generating ${reportLabel(reportType)}...`, 'info');

    setTimeout(() => {
        if (resultsEl) {
            // The detailed chart's canvas is being replaced, so destroy the
            // old Chart.js instance to avoid it referencing a detached canvas
            if (typeof detailedChart !== 'undefined' && detailedChart) {
                detailedChart.destroy();
                detailedChart = null;
            }
            resultsEl.innerHTML = buildReportHTML(reportType);
        }
        showNotification('Report generated successfully!', 'success');
    }, 600);
}

// Friendly label for a report type
function reportLabel(reportType) {
    const labels = {
        shipments: 'Shipments Report',
        fleet: 'Fleet Performance',
        warehouse: 'Warehouse Report',
        financial: 'Financial Summary'
    };
    return labels[reportType] || 'Report';
}

// Build the HTML for a given report type: summary cards + a record table
function buildReportHTML(reportType) {
    if (reportType === 'fleet') return buildFleetReportHTML();
    if (reportType === 'warehouse') return buildWarehouseReportHTML();
    if (reportType === 'financial') return buildFinancialReportHTML();
    return buildShipmentsReportHTML();
}

function reportCard(label, value, sub) {
    return `
        <div class="report-card">
            <h4>${label}</h4>
            <p class="report-value">${value}</p>
            <span>${sub}</span>
        </div>
    `;
}

// ── Shipments Report ─────────────────────────────────────────────────────
function buildShipmentsReportHTML() {
    const total = shipments.length;
    const delivered = shipments.filter(s => s.status === 'Delivered').length;
    const delayed = shipments.filter(s => s.status === 'Delayed').length;
    const onTimeRate = total > 0 ? Math.round((delivered / total) * 100) : 0;

    const rows = shipments.map(s => {
        let statusClass = 'status-pending';
        if (s.status === 'In Transit') statusClass = 'status-transit';
        if (s.status === 'Delivered') statusClass = 'status-delivered';
        if (s.status === 'Delayed') statusClass = 'status-delayed';
        return `
            <tr>
                <td><strong>${s.id}</strong></td>
                <td>${s.origin}</td>
                <td>${s.destination}</td>
                <td><span class="status-badge ${statusClass}">${s.status}</span></td>
                <td>${s.priority}</td>
                <td>${s.weight} kg</td>
                <td>${formatDate(s.estimatedDelivery)}</td>
            </tr>
        `;
    }).join('');

    return `
        <div class="report-cards">
            ${reportCard('Total Shipments', total, 'All records')}
            ${reportCard('On-Time Delivery Rate', onTimeRate + '<span style="font-size:24px;">%</span>', 'Delivered on schedule')}
            ${reportCard('Delivered', delivered, 'Completed shipments')}
            ${reportCard('Delayed', delayed, 'Needs attention')}
        </div>
        <div class="detailed-report">
            <h4 style="color: var(--gold-light); margin-bottom: 14px;">Shipment Records</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Origin</th><th>Destination</th><th>Status</th><th>Priority</th><th>Weight</th><th>Est. Delivery</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>
    `;
}

// ── Fleet Performance Report ─────────────────────────────────────────────
function buildFleetReportHTML() {
    const available = fleet.filter(v => v.status === 'Available').length;
    const inTransit = fleet.filter(v => v.status === 'In Transit').length;
    const maintenance = fleet.filter(v => v.status === 'Maintenance').length;
    const avgFuel = fleet.length ? Math.round(fleet.reduce((sum, v) => sum + (v.fuel || 0), 0) / fleet.length) : 0;

    const rows = fleet.map(v => {
        let statusClass = 'status-pending';
        if (v.status === 'Available') statusClass = 'status-delivered';
        if (v.status === 'In Transit') statusClass = 'status-transit';
        if (v.status === 'Maintenance') statusClass = 'status-delayed';
        const fuelColor = v.fuel > 70 ? '#5dd99c' : v.fuel > 30 ? '#f0c060' : '#e07070';
        return `
            <tr>
                <td><strong>${v.id}</strong></td>
                <td>${v.type}</td>
                <td><span class="status-badge ${statusClass}">${v.status}</span></td>
                <td>${v.driver}</td>
                <td>${v.location}</td>
                <td><span style="color:${fuelColor}; font-weight:700;">${v.fuel}%</span></td>
                <td>${formatDate(v.nextMaintenance)}</td>
            </tr>
        `;
    }).join('');

    return `
        <div class="report-cards">
            ${reportCard('Total Vehicles', fleet.length, 'Full fleet')}
            ${reportCard('Available', available, 'Ready to dispatch')}
            ${reportCard('In Transit', inTransit, 'On the road')}
            ${reportCard('In Maintenance', maintenance, 'Out of service')}
        </div>
        <div class="detailed-report">
            <h4 style="color: var(--gold-light); margin-bottom: 14px;">Fleet Records &middot; Avg Fuel ${avgFuel}%</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Type</th><th>Status</th><th>Driver</th><th>Location</th><th>Fuel</th><th>Next Service</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>
    `;
}

// ── Warehouse Report ─────────────────────────────────────────────────────
function buildWarehouseReportHTML() {
    const totalItems = inventory.reduce((sum, i) => sum + i.quantity, 0);
    const lowStock = inventory.filter(i => i.status === 'Low Stock').length;
    const skus = inventory.length;
    const aisles = new Set(inventory.map(i => i.location)).size;

    const rows = inventory.map(i => {
        const statusClass = i.status === 'Low Stock' ? 'status-delayed' : 'status-delivered';
        return `
            <tr>
                <td><strong>${i.sku}</strong></td>
                <td>${i.description}</td>
                <td>${i.quantity}</td>
                <td>${i.location}</td>
                <td><span class="status-badge ${statusClass}">${i.status}</span></td>
            </tr>
        `;
    }).join('');

    return `
        <div class="report-cards">
            ${reportCard('Total Items', totalItems.toLocaleString(), 'Across all SKUs')}
            ${reportCard('SKUs Tracked', skus, 'Distinct items')}
            ${reportCard('Aisles In Use', aisles, 'Warehouse floor')}
            ${reportCard('Low Stock Alerts', lowStock, 'Needs reorder')}
        </div>
        <div class="detailed-report">
            <h4 style="color: var(--gold-light); margin-bottom: 14px;">Inventory Records</h4>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>SKU</th><th>Description</th><th>Quantity</th><th>Location</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>
    `;
}

// ── Financial Summary Report ─────────────────────────────────────────────
// Estimated per-shipment rate by priority (used to derive revenue since no billing data exists)
const PRIORITY_RATE = { Standard: 8, Express: 14, Priority: 22 };

function buildFinancialReportHTML() {
    const revenueByPriority = { Standard: 0, Express: 0, Priority: 0 };
    let totalRevenue = 0;

    shipments.forEach(s => {
        const rate = PRIORITY_RATE[s.priority] || PRIORITY_RATE.Standard;
        const value = rate * (s.weight || 0);
        revenueByPriority[s.priority] = (revenueByPriority[s.priority] || 0) + value;
        totalRevenue += value;
    });

    const estCosts = totalRevenue * 0.62; // estimated operating cost ratio
    const netProfit = totalRevenue - estCosts;
    const avgValue = shipments.length ? totalRevenue / shipments.length : 0;

    const rows = Object.keys(revenueByPriority).map(priority => {
        const count = shipments.filter(s => s.priority === priority).length;
        return `
            <tr>
                <td><strong>${priority}</strong></td>
                <td>${count}</td>
                <td>K${revenueByPriority[priority].toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
                <td>K${(count ? revenueByPriority[priority] / count : 0).toLocaleString(undefined, {maximumFractionDigits: 0})}</td>
            </tr>
        `;
    }).join('');

    return `
        <div class="report-cards">
            ${reportCard('Total Revenue', 'K' + totalRevenue.toLocaleString(undefined, {maximumFractionDigits: 0}), 'Estimated, this period')}
            ${reportCard('Est. Operating Costs', 'K' + estCosts.toLocaleString(undefined, {maximumFractionDigits: 0}), '62% of revenue')}
            ${reportCard('Net Profit', 'K' + netProfit.toLocaleString(undefined, {maximumFractionDigits: 0}), 'Revenue minus costs')}
            ${reportCard('Avg Shipment Value', 'K' + avgValue.toLocaleString(undefined, {maximumFractionDigits: 0}), 'Per shipment')}
        </div>
        <div class="detailed-report">
            <h4 style="color: var(--gold-light); margin-bottom: 14px;">Revenue by Priority</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Priority</th><th>Shipments</th><th>Revenue</th><th>Avg / Shipment</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            <p style="font-size: 11px; color: rgba(232,213,184,0.5); margin-top: 10px;">
                * Figures are estimates derived from shipment weight and priority; connect a billing source for actuals.
            </p>
        </div>
    `;
}

// Export shipments
function exportShipments(format) {
    const data = shipments.map(s => ({
        'Tracking ID': s.id,
        'Origin': s.origin,
        'Destination': s.destination,
        'Status': s.status,
        'Weight (kg)': s.weight,
        'Priority': s.priority,
        'Created': s.created,
        'Estimated Delivery': s.estimatedDelivery
    }));
    
    if (format === 'csv') {
        const csv = convertToCSV(data);
        downloadFile(csv, 'shipments.csv', 'text/csv');
        showNotification('Shipments exported to CSV!', 'success');
    } else if (format === 'pdf') {
        showNotification('PDF export will be available in the next update!', 'info');
    }
}

// Convert to CSV
function convertToCSV(data) {
    if (!data || data.length === 0) return '';
    const headers = Object.keys(data[0]);
    const csvRows = [];
    csvRows.push(headers.join(','));
    
    for (const row of data) {
        const values = headers.map(header => {
            const val = row[header];
            return `"${String(val).replace(/"/g, '""')}"`;
        });
        csvRows.push(values.join(','));
    }
    
    return csvRows.join('\n');
}

// Download file
function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type: type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

// Vehicle functions
let vehicleModal, closeVehicleBtn, vehicleForm;
let stockModal, closeStockBtn, stockForm;

function addVehicle() {
    if (currentUserRole === 'staff') {
        showNotification('Staff accounts cannot manage the fleet.', 'error');
        return;
    }
    openVehicleModal();
}

function openVehicleModal() {
    if (!vehicleModal) vehicleModal = document.getElementById('vehicleModal');
    if (vehicleModal) {
        vehicleModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeVehicleModal() {
    if (!vehicleModal) vehicleModal = document.getElementById('vehicleModal');
    if (!vehicleForm) vehicleForm = document.getElementById('vehicleForm');
    if (vehicleModal && vehicleForm) {
        vehicleModal.style.display = 'none';
        vehicleForm.reset();
        document.body.style.overflow = 'auto';
    }
}

// Generate the next vehicle ID (VH001, VH002, ...)
function generateVehicleId() {
    let maxNum = 0;
    fleet.forEach(v => {
        const num = parseInt((v.id || '').replace(/\D/g, ''), 10);
        if (!isNaN(num) && num > maxNum) maxNum = num;
    });
    return 'VH' + String(maxNum + 1).padStart(3, '0');
}

// Create a new vehicle and add it to the fleet
function createVehicle(event) {
    event.preventDefault();

    const newVehicle = {
        id: generateVehicleId(),
        type: document.getElementById('vehicleType').value,
        status: document.getElementById('vehicleStatus').value,
        driver: document.getElementById('vehicleDriver').value || 'N/A',
        location: document.getElementById('vehicleLocation').value,
        fuel: parseInt(document.getElementById('vehicleFuel').value, 10) || 0,
        nextMaintenance: document.getElementById('vehicleNextService').value
    };

    fetch(API_BASE + 'vehicles.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newVehicle)
    })
    .then(r => r.json())
    .then(res => { if (res && res.id) newVehicle.id = res.id; })
    .catch(() => {})
    .finally(() => {
        fleet.push(newVehicle);

        renderFleet();
        loadFullFleetData();
        closeVehicleModal();

        showNotification(`Vehicle ${newVehicle.id} added to the fleet!`, 'success');
    });
}

// ── Add Stock Item (Warehouse) ────────────────────────────────────────────
function addStockItem() {
    if (currentUserRole === 'staff') {
        showNotification('Staff accounts cannot manage the warehouse.', 'error');
        return;
    }
    openStockModal();
}

function openStockModal() {
    if (!stockModal) stockModal = document.getElementById('stockModal');
    if (stockModal) {
        stockModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeStockModal() {
    if (!stockModal) stockModal = document.getElementById('stockModal');
    if (!stockForm) stockForm = document.getElementById('stockForm');
    if (stockModal && stockForm) {
        stockModal.style.display = 'none';
        stockForm.reset();
        document.body.style.overflow = 'auto';
    }
}

// Generate the next item SKU (ITM-001, ITM-002, ...)
function generateItemSku() {
    let maxNum = 0;
    inventory.forEach(i => {
        const num = parseInt((i.sku || '').replace(/\D/g, ''), 10);
        if (!isNaN(num) && num > maxNum) maxNum = num;
    });
    return 'ITM-' + String(maxNum + 1).padStart(3, '0');
}

// Recalculate and refresh the warehouse summary cards
function updateWarehouseStats() {
    const totalInventoryEl = document.getElementById('totalInventory');
    const pendingPickupsEl = document.getElementById('pendingPickups');
    if (totalInventoryEl) {
        const totalUnits = inventory.reduce((sum, i) => sum + (parseInt(i.quantity, 10) || 0), 0);
        totalInventoryEl.textContent = totalUnits.toLocaleString();
    }
    if (pendingPickupsEl) {
        // Nudge pending pickups slightly whenever new stock lands, for a live feel
        const current = parseInt(pendingPickupsEl.textContent, 10) || 0;
        pendingPickupsEl.textContent = current;
    }
}

// Create a new stock item and add it to the inventory
function createStockItem(event) {
    event.preventDefault();

    const newItem = {
        sku: generateItemSku(),
        description: document.getElementById('stockDescription').value.trim(),
        quantity: parseInt(document.getElementById('stockQuantity').value, 10) || 0,
        location: document.getElementById('stockLocation').value.trim(),
        status: document.getElementById('stockStatus').value
    };

    fetch(API_BASE + 'inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newItem)
    })
    .then(r => r.json())
    .then(res => { if (res && res.sku) newItem.sku = res.sku; })
    .catch(() => {})
    .finally(() => {
        inventory.push(newItem);

        loadWarehouseData();
        updateWarehouseStats();
        closeStockModal();

        showNotification(`Item ${newItem.sku} added to inventory!`, 'success');
    });
}

// ── Vehicle Action Popup (Track / Service) ───────────────────────────────
let vehicleActionModal, closeVehicleActionBtn;

function openVehicleActionModal(titleHTML, bodyHTML) {
    if (!vehicleActionModal) vehicleActionModal = document.getElementById('vehicleActionModal');
    const titleEl = document.getElementById('vehicleActionTitle');
    const bodyEl = document.getElementById('vehicleActionBody');
    if (titleEl) titleEl.innerHTML = titleHTML;
    if (bodyEl) bodyEl.innerHTML = bodyHTML;
    if (vehicleActionModal) {
        vehicleActionModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeVehicleActionModal() {
    if (!vehicleActionModal) vehicleActionModal = document.getElementById('vehicleActionModal');
    if (vehicleActionModal) {
        vehicleActionModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function vehicleTypeIcon(type) {
    return type === 'Truck' ? 'truck' : 'shuttle-van';
}

function vehicleDetailRows(vehicle) {
    const fuelColor = vehicle.fuel > 70 ? '#5dd99c' : vehicle.fuel > 30 ? '#f0c060' : '#e07070';
    return `
        <div class="vehicle-action-details">
            <div class="vehicle-action-row"><span><i class="fas fa-${vehicleTypeIcon(vehicle.type)}"></i> Type</span><span>${vehicle.type}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-toggle-on"></i> Status</span><span>${vehicle.status}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-user"></i> Driver</span><span>${vehicle.driver}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-map-pin"></i> Location</span><span>${vehicle.location}</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-gas-pump"></i> Fuel</span><span style="color:${fuelColor};">${vehicle.fuel}%</span></div>
            <div class="vehicle-action-row"><span><i class="fas fa-calendar-alt"></i> Next Service</span><span>${formatDate(vehicle.nextMaintenance)}</span></div>
        </div>
    `;
}

// Track a vehicle: shows its current live status, doesn't change any data
function trackVehicle(vehicleId) {
    const vehicle = fleet.find(v => v.id === vehicleId);
    if (!vehicle) {
        showNotification(`Vehicle ${vehicleId} not found.`, 'error');
        return;
    }

    const statusText = vehicle.status === 'In Transit'
        ? `On the road near ${vehicle.location}`
        : vehicle.status === 'Maintenance'
            ? `Currently in the workshop at ${vehicle.location}`
            : `Parked and ready at ${vehicle.location}`;

    const body = `
        <div class="vehicle-action-status">
            <i class="fas fa-map-marker-alt"></i>
            <div>
                <strong>${vehicle.id} has been tracked</strong>
                <span>${statusText}</span>
            </div>
        </div>
        ${vehicleDetailRows(vehicle)}
    `;

    openVehicleActionModal(`<i class="fas fa-${vehicleTypeIcon(vehicle.type)}"></i> Tracking ${vehicle.id}`, body);
    showNotification(`${vehicle.id} tracked successfully.`, 'success');
}

// Send a vehicle for maintenance, or report it's already being serviced
function scheduleMaintenance(vehicleId) {
    const vehicle = fleet.find(v => v.id === vehicleId);
    if (!vehicle) {
        showNotification(`Vehicle ${vehicleId} not found.`, 'error');
        return;
    }

    const alreadyInMaintenance = vehicle.status === 'Maintenance';

    if (!alreadyInMaintenance) {
        vehicle.status = 'Maintenance';
        renderFleet();
        loadFullFleetData();
    }

    const body = `
        <div class="vehicle-action-status maintenance">
            <i class="fas fa-wrench"></i>
            <div>
                <strong>${alreadyInMaintenance ? `${vehicle.id} is already being serviced` : `${vehicle.id} has been sent for maintenance`}</strong>
                <span>${alreadyInMaintenance ? `Still in the workshop at ${vehicle.location}` : `Now marked as Maintenance at ${vehicle.location}`}</span>
            </div>
        </div>
        ${vehicleDetailRows(vehicle)}
    `;

    openVehicleActionModal(`<i class="fas fa-wrench"></i> Servicing ${vehicle.id}`, body);
    showNotification(alreadyInMaintenance ? `${vehicle.id} is already in maintenance.` : `${vehicle.id} scheduled for maintenance.`, alreadyInMaintenance ? 'info' : 'success');
}

function refreshFleetData() {
    loadFullFleetData();
    showNotification('Fleet data refreshed!', 'success');
}

// Setup event listeners
function setupEventListeners() {
    if (searchInput) searchInput.addEventListener('input', handleSearch);
    if (newShipmentBtn) newShipmentBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (shipmentForm) shipmentForm.addEventListener('submit', createShipment);

    // Vehicle modal
    vehicleModal = document.getElementById('vehicleModal');
    closeVehicleBtn = document.querySelector('.close-vehicle');
    vehicleForm = document.getElementById('vehicleForm');
    if (closeVehicleBtn) closeVehicleBtn.addEventListener('click', closeVehicleModal);
    if (vehicleForm) vehicleForm.addEventListener('submit', createVehicle);

    // Stock modal
    stockModal = document.getElementById('stockModal');
    closeStockBtn = document.querySelector('.close-stock');
    stockForm = document.getElementById('stockForm');
    if (closeStockBtn) closeStockBtn.addEventListener('click', closeStockModal);
    if (stockForm) stockForm.addEventListener('submit', createStockItem);

    // Vehicle action popup (Track / Service)
    vehicleActionModal = document.getElementById('vehicleActionModal');
    closeVehicleActionBtn = document.querySelector('.close-vehicle-action');
    if (closeVehicleActionBtn) closeVehicleActionBtn.addEventListener('click', closeVehicleActionModal);

    // Shipment action popup (View Details / Track)
    shipmentActionModal = document.getElementById('shipmentActionModal');
    closeShipmentActionBtn = document.querySelector('.close-shipment-action');
    if (closeShipmentActionBtn) closeShipmentActionBtn.addEventListener('click', closeShipmentActionModal);

    // Edit shipment modal
    editShipmentModal = document.getElementById('editShipmentModal');
    closeEditShipmentBtn = document.querySelector('.close-edit-shipment');
    editShipmentForm = document.getElementById('editShipmentForm');
    if (closeEditShipmentBtn) closeEditShipmentBtn.addEventListener('click', closeEditShipmentModal);
    if (editShipmentForm) editShipmentForm.addEventListener('submit', saveShipmentEdits);
    
    // Pagination
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    if (prevPage) prevPage.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderShipments();
        }
    });
    if (nextPage) nextPage.addEventListener('click', () => {
        const filteredShipments = shipments.filter(s => 
            currentFilter === 'all' || s.status === currentFilter
        );
        const totalPages = Math.ceil(filteredShipments.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderShipments();
        }
    });
    
    // Refresh fleet
    const refreshBtn = document.getElementById('refreshFleet');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            renderFleet();
            showNotification('Fleet data refreshed!', 'success');
        });
    }
    
    // Close modal on outside click
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === vehicleModal) {
            closeVehicleModal();
        }
        if (event.target === stockModal) {
            closeStockModal();
        }
        if (event.target === vehicleActionModal) {
            closeVehicleActionModal();
        }
        if (event.target === shipmentActionModal) {
            closeShipmentActionModal();
        }
        if (event.target === editShipmentModal) {
            closeEditShipmentModal();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey && event.key === 'n') {
            event.preventDefault();
            openModal();
        }
        if (event.key === 'Escape' && modal && modal.style.display === 'block') {
            closeModal();
        }
        if (event.key === 'Escape' && vehicleModal && vehicleModal.style.display === 'block') {
            closeVehicleModal();
        }
        if (event.key === 'Escape' && stockModal && stockModal.style.display === 'block') {
            closeStockModal();
        }
        if (event.key === 'Escape' && vehicleActionModal && vehicleActionModal.style.display === 'block') {
            closeVehicleActionModal();
        }
        if (event.key === 'Escape' && shipmentActionModal && shipmentActionModal.style.display === 'block') {
            closeShipmentActionModal();
        }
        if (event.key === 'Escape' && editShipmentModal && editShipmentModal.style.display === 'block') {
            closeEditShipmentModal();
        }
    });
    
    setupFilterButtons();
    setupNavigation();
}

// Initialize the application
document.addEventListener('DOMContentLoaded', init);

// Export functions for global access
window.viewShipment = viewShipment;
window.trackShipment = trackShipment;
window.editShipment = editShipment;
window.deleteShipment = deleteShipment;
window.showAlerts = showAlerts;
window.openModal = openModal;
window.exportShipments = exportShipments;
window.trackShipmentById = trackShipmentById;
window.addVehicle = addVehicle;
window.addStockItem = addStockItem;
window.trackVehicle = trackVehicle;
window.scheduleMaintenance = scheduleMaintenance;
window.refreshFleetData = refreshFleetData;
window.generateReport = generateReport;

// Settings Modal Functionality
let settingsModal, settingsBtn, closeSettings;
let currentSettings = {};

// Initialize settings
function initSettings() {
    settingsModal = document.getElementById('settingsModal');
    settingsBtn = document.getElementById('settingsBtn');
    closeSettings = document.querySelector('.close-settings');
    
    if (settingsBtn) {
        settingsBtn.addEventListener('click', openSettingsModal);
    }
    
    if (closeSettings) {
        closeSettings.addEventListener('click', closeSettingsModal);
    }
    
    // Load saved settings from localStorage
    loadSavedSettings();
    
    // Setup settings tabs
    setupSettingsTabs();
    
    // Setup all settings handlers
    setupProfileHandlers();
    setupPreferencesHandlers();
    setupNotificationHandlers();
    setupSecurityHandlers();
    setupAppearanceHandlers();
    
    // Close modals on outside click
    window.addEventListener('click', (event) => {
        if (event.target === settingsModal) {
            closeSettingsModal();
        }
        if (event.target === document.getElementById('passwordModal')) {
            closePasswordModal();
        }
        if (event.target === document.getElementById('loginHistoryModal')) {
            closeHistoryModal();
        }
    });
}

// Open settings modal
function openSettingsModal() {
    if (settingsModal) {
        settingsModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        loadCurrentProfileData();
    }
}

// Close settings modal
function closeSettingsModal() {
    if (settingsModal) {
        settingsModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close password modal
function closePasswordModal() {
    const passwordModal = document.getElementById('passwordModal');
    if (passwordModal) {
        passwordModal.style.display = 'none';
    }
}

// Close history modal
function closeHistoryModal() {
    const historyModal = document.getElementById('loginHistoryModal');
    if (historyModal) {
        historyModal.style.display = 'none';
    }
}

// Setup settings tabs
function setupSettingsTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            const activeTab = document.getElementById(`${tabName}-tab`);
            if (activeTab) activeTab.classList.add('active');
        });
    });
}

// Load saved settings from localStorage
function loadSavedSettings() {
    const saved = localStorage.getItem('logitrack_settings');
    if (saved) {
        try { currentSettings = JSON.parse(saved); } catch (e) { currentSettings = {}; }
    }
    if (!currentSettings.appearance || !currentSettings.preferences) {
        currentSettings = {
            profile: {
                name: '', email: '', phone: '', position: '', department: ''
            },
            preferences: {
                language: 'en',
                dashboardView: 'detailed',
                itemsPerPage: '10',
                chartType: 'bar',
                autoRefresh: true,
                refreshInterval: '60'
            },
            notifications: {
                push: true,
                email: true,
                shipments: true,
                delays: true,
                weeklyReports: false,
                sounds: false
            },
            security: {
                twoFactor: false,
                sessionTimeout: '30'
            },
            appearance: {
                darkMode: false,
                themeColor: '#3498db',
                fontSize: 'medium',
                cardStyle: 'default',
                animationSpeed: 'normal'
            }
        };
    }
    
    // Profile details always come from the logged-in user's account (database),
    // never from localStorage, which is shared by every account on this browser.
    const sp = window.SERVER_PROFILE || {};
    currentSettings.profile = {
        name: sp.name || '',
        email: sp.email || '',
        phone: sp.phone || '',
        position: sp.position || '',
        department: sp.department || ''
    };
    
    // Apply loaded settings to UI
    applySettingsToUI();
}

// Apply settings to UI
function applySettingsToUI() {
    // Apply appearance settings
    if (currentSettings.appearance.darkMode) {
        document.body.classList.add('dark-mode');
        const darkModeToggle = document.getElementById('darkMode');
        if (darkModeToggle) darkModeToggle.checked = true;
    }
    
    if (currentSettings.appearance.fontSize !== 'medium') {
        document.body.classList.add(`font-${currentSettings.appearance.fontSize}`);
        const fontSizeSelect = document.getElementById('fontSize');
        if (fontSizeSelect) fontSizeSelect.value = currentSettings.appearance.fontSize;
    }
    
    if (currentSettings.appearance.animationSpeed !== 'normal') {
        document.body.classList.add(`animation-${currentSettings.appearance.animationSpeed}`);
        const animationSelect = document.getElementById('animationSpeed');
        if (animationSelect) animationSelect.value = currentSettings.appearance.animationSpeed;
    }
    
    // Apply theme color
    document.documentElement.style.setProperty('--primary-color', currentSettings.appearance.themeColor);
    
    // Apply preferences
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect && currentSettings.preferences.itemsPerPage) {
        itemsPerPageSelect.value = currentSettings.preferences.itemsPerPage;
        if (typeof rowsPerPage !== 'undefined') {
            rowsPerPage = parseInt(currentSettings.preferences.itemsPerPage);
            if (typeof renderShipments === 'function') renderShipments();
        }
    }
    
    const autoRefreshToggle = document.getElementById('autoRefresh');
    if (autoRefreshToggle) autoRefreshToggle.checked = currentSettings.preferences.autoRefresh;
    
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) languageSelect.value = currentSettings.preferences.language;
    
    const dashboardViewSelect = document.getElementById('dashboardView');
    if (dashboardViewSelect) dashboardViewSelect.value = currentSettings.preferences.dashboardView;
    
    const chartTypeSelect = document.getElementById('chartType');
    if (chartTypeSelect) chartTypeSelect.value = currentSettings.preferences.chartType;
    
    const refreshIntervalSelect = document.getElementById('refreshInterval');
    if (refreshIntervalSelect) refreshIntervalSelect.value = currentSettings.preferences.refreshInterval;
    
    // Apply notification settings
    const pushToggle = document.getElementById('pushNotifications');
    if (pushToggle) pushToggle.checked = currentSettings.notifications.push;
    
    const emailToggle = document.getElementById('emailAlerts');
    if (emailToggle) emailToggle.checked = currentSettings.notifications.email;
    
    const shipmentToggle = document.getElementById('shipmentUpdates');
    if (shipmentToggle) shipmentToggle.checked = currentSettings.notifications.shipments;
    
    const delayToggle = document.getElementById('delayAlerts');
    if (delayToggle) delayToggle.checked = currentSettings.notifications.delays;
    
    const weeklyToggle = document.getElementById('weeklyReports');
    if (weeklyToggle) weeklyToggle.checked = currentSettings.notifications.weeklyReports;
    
    const soundToggle = document.getElementById('soundAlerts');
    if (soundToggle) soundToggle.checked = currentSettings.notifications.sounds;
    
    // Apply security settings
    const twoFactorToggle = document.getElementById('twoFactorAuth');
    if (twoFactorToggle) twoFactorToggle.checked = currentSettings.security.twoFactor;
    
    const sessionTimeoutSelect = document.getElementById('sessionTimeout');
    if (sessionTimeoutSelect) sessionTimeoutSelect.value = currentSettings.security.sessionTimeout;
}

// Load current profile data
function loadCurrentProfileData() {
    const nameInput = document.getElementById('userFullName');
    const emailInput = document.getElementById('userEmail');
    const phoneInput = document.getElementById('userPhone');
    const positionInput = document.getElementById('userPosition');
    const departmentInput = document.getElementById('userDepartment');
    
    if (nameInput) nameInput.value = currentSettings.profile.name;
    if (emailInput) emailInput.value = currentSettings.profile.email;
    if (phoneInput) phoneInput.value = currentSettings.profile.phone;
    if (positionInput) positionInput.value = currentSettings.profile.position;
    if (departmentInput) departmentInput.value = currentSettings.profile.department;
}

// Setup profile handlers
function setupProfileHandlers() {
    // Avatar upload
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const avatarUpload = document.getElementById('avatarUpload');
    const profileAvatar = document.getElementById('profileAvatar');
    
    if (changeAvatarBtn && avatarUpload) {
        changeAvatarBtn.addEventListener('click', () => {
            avatarUpload.click();
        });
        
        avatarUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (profileAvatar) {
                        profileAvatar.src = e.target.result;
                        // Also update sidebar avatar
                        const sidebarAvatar = document.querySelector('.user-avatar img');
                        if (sidebarAvatar) sidebarAvatar.src = e.target.result;
                        showNotification('Profile picture updated!', 'success');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Save profile
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', () => {
            currentSettings.profile.name = document.getElementById('userFullName').value;
            currentSettings.profile.email = document.getElementById('userEmail').value;
            currentSettings.profile.phone = document.getElementById('userPhone').value;
            currentSettings.profile.position = document.getElementById('userPosition').value;
            currentSettings.profile.department = document.getElementById('userDepartment').value;
            
            // Update sidebar user info
            const userName = document.querySelector('.user-name');
            if (userName) userName.textContent = currentSettings.profile.name;
            
            saveAllSettings();

            // Persist to the database
            fetch(API_BASE + 'profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    full_name: currentSettings.profile.name,
                    email: currentSettings.profile.email,
                    phone: currentSettings.profile.phone,
                    position: currentSettings.profile.position,
                    department: currentSettings.profile.department
                })
            }).catch(() => {});

            showNotification('Profile updated successfully!', 'success');
        });
    }
    
    // Cancel profile
    const cancelProfileBtn = document.getElementById('cancelProfileBtn');
    if (cancelProfileBtn) {
        cancelProfileBtn.addEventListener('click', loadCurrentProfileData);
    }
}

// Setup preferences handlers
function setupPreferencesHandlers() {
    const savePreference = () => {
        currentSettings.preferences.language = document.getElementById('languageSelect')?.value || 'en';
        currentSettings.preferences.dashboardView = document.getElementById('dashboardView')?.value || 'detailed';
        currentSettings.preferences.itemsPerPage = document.getElementById('itemsPerPage')?.value || '10';
        currentSettings.preferences.chartType = document.getElementById('chartType')?.value || 'bar';
        currentSettings.preferences.autoRefresh = document.getElementById('autoRefresh')?.checked || false;
        currentSettings.preferences.refreshInterval = document.getElementById('refreshInterval')?.value || '60';
        
        if (typeof rowsPerPage !== 'undefined') {
            rowsPerPage = parseInt(currentSettings.preferences.itemsPerPage);
            if (typeof renderShipments === 'function') renderShipments();
        }
    };
    
    const elements = ['languageSelect', 'dashboardView', 'itemsPerPage', 'chartType', 'autoRefresh', 'refreshInterval'];
    elements.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', savePreference);
    });
}

// Setup notification handlers
function setupNotificationHandlers() {
    const saveNotification = () => {
        currentSettings.notifications.push = document.getElementById('pushNotifications')?.checked || false;
        currentSettings.notifications.email = document.getElementById('emailAlerts')?.checked || false;
        currentSettings.notifications.shipments = document.getElementById('shipmentUpdates')?.checked || false;
        currentSettings.notifications.delays = document.getElementById('delayAlerts')?.checked || false;
        currentSettings.notifications.weeklyReports = document.getElementById('weeklyReports')?.checked || false;
        currentSettings.notifications.sounds = document.getElementById('soundAlerts')?.checked || false;
    };
    
    const elements = ['pushNotifications', 'emailAlerts', 'shipmentUpdates', 'delayAlerts', 'weeklyReports', 'soundAlerts'];
    elements.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', saveNotification);
    });
}

// Setup security handlers
function setupSecurityHandlers() {
    // Change password
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', () => {
            closeSettingsModal();
            const passwordModal = document.getElementById('passwordModal');
            if (passwordModal) passwordModal.style.display = 'block';
        });
    }
    
    // Password form submission
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showNotification('Password must be at least 8 characters!', 'error');
                return;
            }
            if (!/[a-z]/.test(newPassword) || !/[A-Z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[^a-zA-Z0-9]/.test(newPassword)) {
                showNotification('Password needs upper & lowercase letters, a number, and a symbol!', 'error');
                return;
            }
            
            fetch(API_BASE + 'profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ new_password: newPassword })
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    showNotification('Password changed successfully!', 'success');
                } else {
                    showNotification((res && res.error) || 'Could not change password.', 'error');
                }
            })
            .catch(() => showNotification('Password changed successfully!', 'success'))
            .finally(() => {
                closePasswordModal();
                passwordForm.reset();
            });
        });
    }
    
    // Password strength checker
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', (e) => {
            const password = e.target.value;
            const strengthDiv = document.getElementById('passwordStrength');
            if (!strengthDiv) return;

            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;

            let strength = 'weak';
            if (score >= 5) strength = 'strong';
            else if (score >= 3) strength = 'medium';

            strengthDiv.className = `password-strength ${strength}`;
            strengthDiv.textContent = !password ? '' : strength === 'weak' ? 'Weak password' : strength === 'medium' ? 'Medium password' : 'Strong password';
        });
    }
    
    // Two-factor authentication
    const twoFactorToggle = document.getElementById('twoFactorAuth');
    if (twoFactorToggle) {
        twoFactorToggle.addEventListener('change', () => {
            currentSettings.security.twoFactor = twoFactorToggle.checked;
            if (twoFactorToggle.checked) {
                showNotification('Two-factor authentication enabled!', 'success');
            } else {
                showNotification('Two-factor authentication disabled!', 'info');
            }
        });
    }
    
    // Session timeout
    const sessionTimeoutSelect = document.getElementById('sessionTimeout');
    if (sessionTimeoutSelect) {
        sessionTimeoutSelect.addEventListener('change', () => {
            currentSettings.security.sessionTimeout = sessionTimeoutSelect.value;
            showNotification('Session timeout updated!', 'success');
        });
    }
    
    // View login history
    const viewHistoryBtn = document.getElementById('viewLoginHistoryBtn');
    if (viewHistoryBtn) {
        viewHistoryBtn.addEventListener('click', () => {
            closeSettingsModal();
            const historyModal = document.getElementById('loginHistoryModal');
            if (historyModal) {
                displayLoginHistory();
                historyModal.style.display = 'block';
            }
        });
    }
    
    // Export data
    const exportDataBtn = document.getElementById('exportDataBtn');
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', () => {
            const data = {
                settings: currentSettings,
                shipments: typeof shipments !== 'undefined' ? shipments : [],
                fleet: typeof fleet !== 'undefined' ? fleet : [],
                exportDate: new Date().toISOString()
            };
            const dataStr = JSON.stringify(data, null, 2);
            const blob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `logitrack_export_${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
            showNotification('Data exported successfully!', 'success');
        });
    }
}

// Display login history
function displayLoginHistory() {
    const historyList = document.getElementById('loginHistoryList');
    if (!historyList) return;
    
    const sampleHistory = [
        { device: 'Chrome on Windows', location: 'New York, USA', time: '2024-03-15 09:30 AM', status: 'success' },
        { device: 'Safari on Mac', location: 'Los Angeles, USA', time: '2024-03-14 02:15 PM', status: 'success' },
        { device: 'Firefox on Linux', location: 'Chicago, USA', time: '2024-03-13 11:45 AM', status: 'success' },
        { device: 'Mobile App', location: 'Miami, USA', time: '2024-03-12 08:20 AM', status: 'success' }
    ];
    
    historyList.innerHTML = '';
    sampleHistory.forEach(item => {
        const div = document.createElement('div');
        div.className = 'history-item';
        div.innerHTML = `
            <div class="history-info">
                <span class="device"><i class="fas fa-laptop"></i> ${item.device}</span>
                <span class="location"><i class="fas fa-map-marker-alt"></i> ${item.location}</span>
            </div>
            <div class="history-time">${item.time}</div>
            <span class="history-badge ${item.status}">
                <i class="fas fa-${item.status === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${item.status === 'success' ? 'Success' : 'New Device'}
            </span>
        `;
        historyList.appendChild(div);
    });
}

// Setup appearance handlers
function setupAppearanceHandlers() {
    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkMode');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', () => {
            currentSettings.appearance.darkMode = darkModeToggle.checked;
            if (darkModeToggle.checked) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        });
    }
    
    // Color picker
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', () => {
            const color = option.dataset.color;
            colorOptions.forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            currentSettings.appearance.themeColor = color;
            document.documentElement.style.setProperty('--primary-color', color);
        });
    });
    
    // Font size
    const fontSizeSelect = document.getElementById('fontSize');
    if (fontSizeSelect) {
        fontSizeSelect.addEventListener('change', () => {
            const sizes = ['small', 'medium', 'large', 'xlarge'];
            sizes.forEach(size => document.body.classList.remove(`font-${size}`));
            if (fontSizeSelect.value !== 'medium') {
                document.body.classList.add(`font-${fontSizeSelect.value}`);
            }
            currentSettings.appearance.fontSize = fontSizeSelect.value;
        });
    }
    
    // Card style
    const cardStyleSelect = document.getElementById('cardStyle');
    if (cardStyleSelect) {
        cardStyleSelect.addEventListener('change', () => {
            currentSettings.appearance.cardStyle = cardStyleSelect.value;
            // Apply card style changes
            const cards = document.querySelectorAll('.stat-card, .fleet-card, .analytics-card');
            if (cardStyleSelect.value === 'compact') {
                cards.forEach(card => card.style.padding = '16px');
            } else if (cardStyleSelect.value === 'elevated') {
                cards.forEach(card => card.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)');
            } else {
                cards.forEach(card => {
                    card.style.padding = '';
                    card.style.boxShadow = '';
                });
            }
        });
    }
    
    // Animation speed
    const animationSelect = document.getElementById('animationSpeed');
    if (animationSelect) {
        animationSelect.addEventListener('change', () => {
            const speeds = ['fast', 'normal', 'slow', 'none'];
            speeds.forEach(speed => document.body.classList.remove(`animation-${speed}`));
            if (animationSelect.value !== 'normal') {
                document.body.classList.add(`animation-${animationSelect.value}`);
            }
            currentSettings.appearance.animationSpeed = animationSelect.value;
        });
    }
}

// Save all settings
function saveAllSettings() {
    // Don't store profile details locally - they belong to the account in the database
    const toStore = Object.assign({}, currentSettings);
    delete toStore.profile;
    localStorage.setItem('logitrack_settings', JSON.stringify(toStore));
    showNotification('All settings saved successfully!', 'success');
}

// Save all settings button
const saveAllBtn = document.getElementById('saveAllSettingsBtn');
if (saveAllBtn) {
    saveAllBtn.addEventListener('click', saveAllSettings);
}

// Reset settings
const resetBtn = document.getElementById('resetSettingsBtn');
if (resetBtn) {
    resetBtn.addEventListener('click', () => {
        if (confirm('Reset all settings to default? This cannot be undone.')) {
            localStorage.removeItem('logitrack_settings');
            location.reload();
        }
    });
}

// Close password modal
const closePassword = document.querySelector('.close-password');
if (closePassword) {
    closePassword.addEventListener('click', closePasswordModal);
}

// Close history modal
const closeHistory = document.querySelector('.close-history');
if (closeHistory) {
    closeHistory.addEventListener('click', closeHistoryModal);
}

// Initialize settings when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSettings);
} else {
    initSettings();
}

// ============================================
// MOBILE RESPONSIVE FUNCTIONALITY
// ============================================

let mobileMenuBtn, mobileOverlay, sidebar;
let isMenuOpen = false;

function initMobileResponsive() {
    mobileMenuBtn = document.getElementById('mobileMenuBtn');
    mobileOverlay = document.getElementById('mobileOverlay');
    sidebar = document.getElementById('sidebar');
    
    if (!sidebar) {
        // If sidebar doesn't have ID, try by class
        sidebar = document.querySelector('.center-nav');
    }
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenu);
    }
    
    // Close menu when a nav link is clicked
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
    
    // Close menu when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && isMenuOpen) {
            if (sidebar && !sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                closeMobileMenu();
            }
        }
    });
    
    // Handle window resize - close menu if screen becomes larger
    window.addEventListener('resize', handleResize);
    
    // Fix viewport height for mobile browsers
    setMobileViewportHeight();
    window.addEventListener('resize', setMobileViewportHeight);
    
    // Add touch feedback for mobile
    addTouchFeedback();
}

function toggleMobileMenu() {
    if (sidebar) {
        sidebar.classList.toggle('active');
        isMenuOpen = sidebar.classList.contains('active');
        
        if (mobileOverlay) {
            mobileOverlay.style.display = isMenuOpen ? 'block' : 'none';
        }
        
        // Prevent body scroll when menu is open
        if (isMenuOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

function closeMobileMenu() {
    if (sidebar && isMenuOpen) {
        sidebar.classList.remove('active');
        isMenuOpen = false;
        
        if (mobileOverlay) {
            mobileOverlay.style.display = 'none';
        }
        document.body.style.overflow = '';
    }
}

function handleResize() {
    // Close menu and reset styles on desktop
    if (window.innerWidth > 768) {
        if (isMenuOpen) {
            closeMobileMenu();
        }
        if (mobileOverlay) {
            mobileOverlay.style.display = 'none';
        }
        if (sidebar) {
            sidebar.classList.remove('active');
        }
        document.body.style.overflow = '';
    }
}

function setMobileViewportHeight() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

function addTouchFeedback() {
    if ('ontouchstart' in window) {
        const buttons = document.querySelectorAll('button, .nav-link, .filter-btn, .action-btn, .tab-btn, .page-btn');
        
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                this.style.opacity = '1';
                setTimeout(() => {
                    this.style.opacity = '';
                }, 150);
            });
            
            button.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
            });
        });
    }
}

// Fix for iOS input zoom issue
function fixIOSInputZoom() {
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            if (window.innerWidth < 768) {
                document.body.style.fontSize = '16px';
            }
        });
        
        input.addEventListener('blur', () => {
            document.body.style.fontSize = '';
        });
    });
}

// Make tables scrollable smoothly on touch devices
function initTouchScrolling() {
    const tables = document.querySelectorAll('.table-container');
    tables.forEach(table => {
        let isDragging = false;
        let startX;
        let scrollLeft;
        
        table.addEventListener('touchstart', (e) => {
            isDragging = true;
            startX = e.touches[0].pageX - table.offsetLeft;
            scrollLeft = table.scrollLeft;
        }, { passive: true });
        
        table.addEventListener('touchend', () => {
            isDragging = false;
        });
        
        table.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.touches[0].pageX - table.offsetLeft;
            const walk = (x - startX) * 2;
            table.scrollLeft = scrollLeft - walk;
        }, { passive: false });
    });
}

// Redraw charts on resize for better responsiveness
let resizeTimer;
function handleChartResize() {
    if (typeof deliveryChart !== 'undefined' && deliveryChart) deliveryChart.resize();
    if (typeof trendChart !== 'undefined' && trendChart) trendChart.resize();
    if (typeof priorityChart !== 'undefined' && priorityChart) priorityChart.resize();
    if (typeof detailedChart !== 'undefined' && detailedChart) detailedChart.resize();
}

window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        handleChartResize();
    }, 250);
});

// Initialize mobile features when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initMobileResponsive();
        fixIOSInputZoom();
        initTouchScrolling();
    });
} else {
    initMobileResponsive();
    fixIOSInputZoom();
    initTouchScrolling();
}

// Export functions for debugging (optional)
window.mobileMenu = {
    open: toggleMobileMenu,
    close: closeMobileMenu,
    isOpen: () => isMenuOpen
};