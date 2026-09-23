const THEME_KEY = "docura-theme";
const TAB_SESSION_KEY = "docura-active-session";
let currentUser = null;
let state = {
    filter: "all",
    sort: "newest",
    shareId: null,
    previewId: null,
    previewUrl: null,
    queue: [],
    uploadFolderId: null
};

const API = "api";

function $(id){ return document.getElementById(id); }
function $all(selector){ return [...document.querySelectorAll(selector)]; }

function escapeHtml(v=""){
    return String(v).replace(/[&<>"']/g, m => ({
        "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[m]));
}

function humanSize(bytes){
    if(!bytes) return "0 B";
    const units=["B","KB","MB","GB"]; let i=0,n=Number(bytes);
    while(n>=1024&&i<units.length-1){n/=1024;i++}
    return `${n.toFixed(i?1:0)} ${units[i]}`;
}

function timeAgo(ts){
    const d=(Date.now()-new Date(ts).getTime())/1000;
    if(d<60)return"just now";
    if(d<3600)return`${Math.floor(d/60)} min ago`;
    if(d<86400)return`${Math.floor(d/3600)} hr ago`;
    if(d<604800)return`${Math.floor(d/86400)} day ago`;
    return new Date(ts).toLocaleDateString();
}

function initials(name="Docura User"){
    return name.split(/\s+/).map(x=>x[0]).join("").slice(0,2).toUpperCase();
}

function kind(name="",type=""){
    if(type.startsWith("image/"))return"image";
    if(type.includes("spreadsheet") || /\.(csv|xls|xlsx)$/i.test(name))return"sheet";
    if(type==="application/pdf" || /\.pdf$/i.test(name))return"pdf";
    return"doc";
}

function label(name=""){
    const e=name.split(".").pop().toUpperCase();
    return e.length>4?"FILE":e;
}

function showToast(message,warn=false){
    const t=$("toast");
    t.textContent=message;
    t.className="toast show"+(warn?" warn":"");
    clearTimeout(window.__toast);
    window.__toast=setTimeout(()=>t.className="toast",2400);
}

function setBusy(button,busy,text){
    if(!button)return;
    button.disabled=busy;
    if(text)button.dataset.originalText ||= button.textContent;
    if(busy&&text)button.textContent=text;
    if(!busy&&button.dataset.originalText){
        button.textContent=button.dataset.originalText;
        delete button.dataset.originalText
    }
}

async function apiFetch(url,options={}){
    options.headers=options.headers||{};

    if(options.body instanceof FormData){
        options.headers["X-CSRF-Token"]=window.DOCURA_CSRF;
    }else{
        options.headers["Content-Type"]="application/json";
        options.headers["X-CSRF-Token"]=window.DOCURA_CSRF;
    }

    const response=await fetch(`${API}/${url}`,options);
    let data;

    try{
        data=await response.json()
    }catch{
        data={
            success:false,
            message:"Server returned an invalid response."
        }
    }

    if(response.status===419){
        showToast(
            data.message||
            "Security token expired. Refresh the page.",
            true
        );
        return data
    }

    if(response.status===401){
        showToast(
            data.message||
            "Please sign in.",
            true
        );
        openAuth("signin");
        return data
    }

    if(!response.ok && data.message)
        showToast(data.message,true);

    return data;
}

async function bootstrap(){
    const r=await fetch(`${API}/bootstrap.php`);
    const data=await r.json();

    if(data.csrf)
        window.DOCURA_CSRF=data.csrf;

    currentUser=data.user;
    updateAuthUI();
}

function updateAuthUI(){
    const logged=!!currentUser;

    document.body.classList.toggle(
        "auth-gate-active",
        !logged
    );

    if(!logged){
        $("authModal")?.classList.add(
            "show",
            "auth-gate"
        );
    }else{
        $("authModal")?.classList.remove(
            "auth-gate"
        );
    }

    $("authButtons").classList.toggle(
        "hidden",
        logged
    );

    $("topProfileBtn").classList.toggle(
        "hidden",
        !logged
    );

    const name=
        currentUser?.name||
        "Guest User";

    const email=
        currentUser?.email||
        "Not signed in";

    $("adminNavWrap").classList.toggle(
        "hidden",
        !(currentUser &&
        currentUser.role === "admin")
    );

    $("sideName").textContent=name;

    $("sideStatus").textContent=
        logged?
        "Signed in":
        "Guest mode";

    $("sideAvatar").textContent=
        initials(name);

    $("topAvatar").textContent=
        initials(name);

    $("popupAvatar").textContent=
        initials(name);

    $("popupName").textContent=name;

    $("popupEmail").textContent=email;

    $("popupStatus").textContent=
        logged?
        "Signed in":
        "Signed out";

    $("settingsName").value=name;

    $("settingsEmail").value=
        logged?
        email:
        "";
}

function openUpload(){
    if(!requireAuth()) return;

    state.queue=[];
    state.uploadFolderId=null;

    const input=$("fileInput");

    if(input)
        input.value="";

    renderQueue();
    openModal("uploadModal");
}

function openFolder(){
    if(!requireAuth()) return;

    const input=$("newFolderName");

    if(input)
        input.value="";

    openModal("folderModal");
}

function toggleProfile(){
    $("profilePopover")?.classList.toggle("show");
}

function openAuth(tab="signin",gate=false){
    $("authModal").classList.add("show");

    if(gate){
        $("authModal").classList.add("auth-gate");

        document.body.classList.add(
            "auth-gate-active"
        );
    }

    $all("[data-auth]").forEach(
        b=>b.classList.toggle(
            "active",
            b.dataset.auth===tab
        )
    );

    $("signinForm").classList.toggle(
        "hidden",
        tab!=="signin"
    );

    $("signupForm").classList.toggle(
        "hidden",
        tab!=="signup"
    );
}

function closeModal(id){
    if(
        id==="authModal" &&
        document.body.classList.contains(
            "auth-gate-active"
        )
    )
        return;

    $(id)?.classList.remove("show");
}

function requireAuth(){
    if(!currentUser){
        openAuth("signin");

        showToast(
            "Please sign in to continue.",
            true
        );

        return false
    }

    return true
}

function showPage(page){

    if(
        page!=="dashboard" &&
        !requireAuth()
    )
        return;

    $all(".page").forEach(
        p=>p.classList.remove("active")
    );

    $(`page-${page}`).classList.add("active");

    $all(".nav-item").forEach(
        b=>b.classList.toggle(
            "active",
            b.dataset.page===page
        )
    );

    $("sidebar").classList.remove("open");

    if(page==="dashboard")
        loadDashboard();

    if(page==="documents")
        renderDocuments();

    if(page==="folders")
        renderFolders();

    if(page==="shared")
        renderShared();

    if(page==="starred")
        renderStarred();

    if(page==="activity")
        renderActivity();

    if(page==="trash")
        renderTrash();
}

async function loadDashboard(){

    if(!currentUser)
        return;

    const r=await apiFetch(
        "dashboard.php"
    );

    if(!r.success)
        return;

    const s=r.stats;

    $("statDocuments").textContent=
        s.documents;

    $("statShared").textContent=
        s.shared;

    $("statStorage").textContent=
        humanSize(s.storage_bytes);

    const pct=Math.min(
        100,
        Math.round(
            (s.storage_bytes/2147483648)*100
        )
    );

    $("ringPercent").textContent=
        pct+"%";

    $("storageRing").style.background=
        `conic-gradient(
            #397fff ${pct*3.6}deg,
            #e4ecf6 ${pct*3.6}deg
        )`;

    $("documentsStorage").textContent=
        humanSize(s.storage_bytes*.82);

    $("sharedStorage").textContent=
        humanSize(s.storage_bytes*.18);

    $("freeStorage").textContent=
        humanSize(
            Math.max(
                0,
                2147483648-s.storage_bytes
            )
        );

    $("documentsBar").style.width=
        Math.min(100,pct*.82)+"%";

    $("sharedBar").style.width=
        Math.min(100,pct*.18)+"%";

    $("navCount").textContent=
        s.documents||"";

    $("miniStoragePct").textContent=
        pct+"%";

    $("miniStorageBar").style.width=
        pct+"%";

    $("miniStorageText").textContent=
        `${humanSize(s.storage_bytes)} of 2 GB used`;

    const docs=await apiFetch(
        "documents.php"
    );

    if(docs.success){

        const recent=
            docs.documents.slice(0,5);

        $("recentDocuments").innerHTML=
            recent.map(recentRow).join("");

        $("recentEmpty").classList.toggle(
            "hidden",
            recent.length>0
        );
    }

    const activity=
        await apiFetch(
            "activity.php?limit=4"
        );

    if(activity.success){

        $("dashboardActivity").innerHTML=
            activity.activity.length?
            activity.activity
                .map(activityRow)
                .join("")
            :
            `<div class="muted-text">No activity yet.</div>`;
    }
}

function recentRow(d){
    return `<div class="document-row">
        <div class="file-icon ${kind(d.file_name,d.file_type)}">
            ${label(d.file_name)}
        </div>

        <div class="grow">
            <b>${escapeHtml(d.file_name)}</b>
            <small>
                ${humanSize(d.file_size)}
                ·
                ${timeAgo(d.created_at)}
            </small>
        </div>

        <span class="row-chip">
            ${d.is_starred?"Starred":"Secure"}
        </span>

        <div class="small-actions">
            <button onclick="previewDocument(${d.id})">↗</button>
            <button onclick="downloadDocument(${d.id})">↓</button>
        </div>
    </div>`;
}

function docCard(d,trash=false){

    const buttons=trash
        ?
        `<button onclick="restoreDocument(${d.id})">↶</button>
         <button onclick="permanentDelete(${d.id})">⌫</button>`
        :
        `<button onclick="previewDocument(${d.id})">↗</button>
         <button onclick="downloadDocument(${d.id})">↓</button>
         <button onclick="shareDocument(${d.id},'${escapeHtml(d.file_name)}')">⇄</button>
         <button onclick="trashDocument(${d.id})">⌫</button>`;

    return `<div class="doc-card">

        ${
            !trash
            ?
            `<button
                class="star ${d.is_starred?"on":""}"
                onclick="toggleStar(${d.id})">
                ★
            </button>`
            :
            ""
        }

        <div class="doc-type ${kind(d.file_name,d.file_type)}">
            ${label(d.file_name)}
        </div>

        <div
            class="doc-title"
            title="${escapeHtml(d.file_name)}">
            ${escapeHtml(d.file_name)}
        </div>

        <div class="doc-meta">
            ${humanSize(d.file_size)}
            ·
            ${timeAgo(d.created_at)}
        </div>

        <div class="doc-foot">
            <span class="folder-label">
                ${escapeHtml(d.folder_name||"Vault")}
            </span>

            <div class="small-actions">
                ${buttons}
            </div>
        </div>

    </div>`;
}

async function getDocumentsParams(extra=""){

    let url="documents.php";

    const p=new URLSearchParams();

    if(state.filter!=="all")
        p.set("type",state.filter);

    const q=
        $("searchInput")
        .value
        .trim();

    if(q)
        p.set("q",q);

    const base=
        await apiFetch(
            `${url}?${p.toString()}`
        );

    return base;
}

async function renderDocuments(){

    if(!requireAuth())
        return;

    const r=
        await getDocumentsParams();

    if(!r.success)
        return;

    let docs=r.documents||[];

    if(state.sort==="name")
        docs.sort(
            (a,b)=>
                a.file_name.localeCompare(
                    b.file_name
                )
        );

    if(state.sort==="size")
        docs.sort(
            (a,b)=>
                Number(b.file_size)-
                Number(a.file_size)
        );

    if(state.sort==="oldest")
        docs.sort(
            (a,b)=>
                new Date(a.created_at)-
                new Date(b.created_at)
        );

    if(state.sort==="newest")
        docs.sort(
            (a,b)=>
                new Date(b.created_at)-
                new Date(a.created_at)
        );

    $("documentsGrid").innerHTML=
        docs.map(docCard).join("");

    $("documentsGrid").style.display=
        docs.length?
        "grid":
        "none";

    $("documentsEmpty").classList.toggle(
        "hidden",
        docs.length>0
    );
}

async function renderFolders(){

    const r=
        await apiFetch(
            "folders.php"
        );

    if(!r.success)
        return;

    $("foldersGrid").innerHTML=
        (r.folders||[])
        .map(
            f=>
            `<div class="folder-card">

                <div class="folder-icon">
                    ▤
                </div>

                <h3>
                    ${escapeHtml(
                        f.folder_name
                    )}
                </h3>

                <p>
                    ${f.document_count}
                    document${
                        Number(f.document_count)===1?
                        "":
                        "s"
                    }
                </p>

                <button
                    class="text-link"
                    onclick="showFolderDocs(${f.id})">

                    Open folder →

                </button>

            </div>`
        )
        .join("");
}

async function showFolderDocs(id){

    if(!requireAuth())
        return;

    const r=
        await apiFetch(
            "documents.php"
        );

    if(!r.success)
        return;

    const docs=
        (r.documents||[])
        .filter(
            d=>Number(d.folder_id)===Number(id)
        );

    setPageDirect("documents");

    $("documentsGrid").innerHTML=
        docs.map(docCard).join("");

    $("documentsGrid").style.display=
        docs.length?
        "grid":
        "none";

    $("documentsEmpty").classList.toggle(
        "hidden",
        docs.length>0
    );
}

function setPageDirect(page){

    $all(".page").forEach(
        p=>p.classList.remove("active")
    );

    $(`page-${page}`).classList.add(
        "active"
    );

    $all(".nav-item").forEach(
        b=>b.classList.toggle(
            "active",
            b.dataset.page===page
        )
    );
}

async function renderShared(){

    const r=
        await apiFetch(
            "shares.php"
        );

    if(!r.success)
        return;

    const docs=r.shares||[];

    $("sharedList").innerHTML=
        docs
        .map(
            s=>
            `<div class="shared-row">

                <div class="file-icon ${
                    kind(
                        s.file_name,
                        s.file_type
                    )
                }">
                    ${label(s.file_name)}
                </div>

                <div class="grow">

                    <b>
                        ${escapeHtml(
                            s.file_name
                        )}
                    </b>

                    <small>
                        From
                        ${escapeHtml(
                            s.owner_name
                        )}

                        ·

                        ${
                            s.permission==="download"
                            ?
                            "View & Download"
                            :
                            "View only"
                        }

                        ·

                        ${timeAgo(
                            s.created_at
                        )}
                    </small>

                </div>

                <button
                    class="btn"
                    onclick="previewShared(${s.document_id})">

                    View

                </button>

                ${
                    s.permission==="download"
                    ?
                    `<button
                        class="btn"
                        onclick="downloadDocument(${s.document_id})">

                        Download

                    </button>`
                    :
                    ""
                }

            </div>`
        )
        .join("");

    $("sharedEmpty").classList.toggle(
        "hidden",
        docs.length>0
    );
}

async function renderStarred(){

    const r=
        await apiFetch(
            "documents.php"
        );

    if(!r.success)
        return;

    const docs=
        (r.documents||[])
        .filter(
            d=>Number(d.is_starred)===1
        );

    $("starredGrid").innerHTML=
        docs.map(docCard).join("");

    $("starredGrid").style.display=
        docs.length?
        "grid":
        "none";

    $("starredEmpty").classList.toggle(
        "hidden",
        docs.length>0
    );
}

async function renderTrash(){

    const r=
        await apiFetch(
            "documents.php?trash=1"
        );

    if(!r.success)
        return;

    const docs=r.documents||[];

    $("trashGrid").innerHTML=
        docs.map(
            d=>docCard(d,true)
        ).join("");

    $("trashGrid").style.display=
        docs.length?
        "grid":
        "none";

    $("trashEmpty").classList.toggle(
        "hidden",
        docs.length>0
    );
}

async function renderActivity(){

    const r=
        await apiFetch(
            "activity.php?limit=100"
        );

    if(!r.success)
        return;

    $("fullActivity").innerHTML=
        (r.activity||[])
        .map(activityRow)
        .join("")
        ||
        `<div class="muted-text">
            No activity yet.
        </div>`;
}

function activityRow(e){

    return `<div class="activity-entry">

        <span class="activity-dot"></span>

        <div>

            <b>
                ${escapeHtml(e.action)}
            </b>

            ${escapeHtml(
                e.document_name||""
            )}

            ${
                e.details
                ?
                `<span class="muted-inline">
                    ${escapeHtml(e.details)}
                </span>`
                :
                ""
            }

            <small>
                ${new Date(
                    e.created_at
                ).toLocaleString()}
            </small>

        </div>

    </div>`;
}

async function renderAdmin(){

    if(
        !currentUser ||
        currentUser.role !== "admin"
    ){
        showToast(
            "Admin access required.",
            true
        );

        return;
    }

    const r=
        await apiFetch(
            "admin.php"
        );

    if(!r.success)
        return;

    const s=r.stats;

    $("adminUsers").textContent=
        s.users;

    $("adminDocs").textContent=
        s.documents;

    $("adminStorage").textContent=
        humanSize(
            s.storage_bytes
        );

    $("adminShares").textContent=
        s.shares;

    $("adminUsersTable").innerHTML=
        (r.users_list||[])
        .map(
            u=>
            `<tr>

                <td>${u.id}</td>

                <td>
                    ${escapeHtml(u.name)}
                </td>

                <td>
                    ${escapeHtml(u.email)}
                </td>

                <td>
                    <span class="role-pill ${u.role}">
                        ${escapeHtml(u.role)}
                    </span>
                </td>

                <td>
                    ${new Date(
                        u.created_at
                    ).toLocaleDateString()}
                </td>

            </tr>`
        )
        .join("");
}

async function uploadFiles(){

    if(!requireAuth())
        return;

    const input=$("fileInput");

    const files=[
        ...input.files
    ];

    if(!files.length)
        return;

    const button=
        $("confirmUpload");

    setBusy(
        button,
        true,
        "Uploading..."
    );

    for(const file of files){

        const form=
            new FormData();

        form.append(
            "document",
            file
        );

        const folder=
            state.uploadFolderId||"";

        if(folder)
            form.append(
                "folder_id",
                folder
            );

        const r=
            await apiFetch(
                "documents.php",
                {
                    method:"POST",
                    body:form
                }
            );

        if(!r.success){
            setBusy(
                button,
                false
            );

            return;
        }
    }

    input.value="";

    state.uploadQueue=[];

    renderQueue();

    setBusy(
        button,
        false
    );

    closeModal(
        "uploadModal"
    );

    await refreshAll();

    showToast(
        "Document upload completed"
    );
}

async function toggleStar(id){

    const r=
        await apiFetch(
            "documents.php",
            {
                method:"PUT",
                body:JSON.stringify({
                    id,
                    action:"star"
                })
            }
        );

    if(r.success){

        await refreshAll();

        showToast(
            r.starred?
            "Added to Starred":
            "Removed from Starred"
        );
    }
}

async function trashDocument(id){

    const r=
        await apiFetch(
            "documents.php",
            {
                method:"PUT",
                body:JSON.stringify({
                    id,
                    action:"trash"
                })
            }
        );

    if(r.success){

        await refreshAll();

        showToast(
            "Document moved to Trash"
        );
    }
}

async function restoreDocument(id){

    const r=
        await apiFetch(
            "documents.php",
            {
                method:"PUT",
                body:JSON.stringify({
                    id,
                    action:"restore"
                })
            }
        );

    if(r.success){

        await refreshAll();

        showToast(
            "Document restored"
        );
    }
}

async function permanentDelete(id){

    if(
        !confirm(
            "Permanently delete this document?"
        )
    )
        return;

    const r=
        await apiFetch(
            "documents.php",
            {
                method:"PUT",
                body:JSON.stringify({
                    id,
                    action:"permanent"
                })
            }
        );

    if(r.success){

        await refreshAll();

        showToast(
            "Document permanently deleted",
            true
        );
    }
}

async function emptyTrash(){

    const r=
        await apiFetch(
            "documents.php?trash=1"
        );

    if(
        !r.success ||
        !r.documents.length
    )
        return showToast(
            "Trash is empty"
        );

    if(
        !confirm(
            "Permanently delete all items in Trash?"
        )
    )
        return;

    for(
        const d of r.documents
    )
        await permanentDelete(d.id);
}

async function previewDocument(id){

    if(!requireAuth())
        return;

    const r=
        await apiFetch(
            "documents.php"
        );

    if(!r.success)
        return;

    const doc=
        (r.documents||[])
        .find(
            d=>Number(d.id)===Number(id)
        );

    if(!doc)
        return;

    openPreview(doc);
}

async function previewShared(id){

    openPreview({
        id,
        file_name:"Shared document",
        file_size:0,
        file_type:"application/pdf"
    });
}

function openPreview(doc){

    state.previewId=doc.id;

    $("previewName").textContent=
        doc.file_name;

    $("previewMeta").textContent=
        doc.file_size
        ?
        `${humanSize(doc.file_size)} · ${new Date(doc.created_at).toLocaleString()}`
        :
        "Shared document";

    const k=
        kind(
            doc.file_name,
            doc.file_type
        );

    const box=
        $("previewBox");

    if(state.previewUrl){

        URL.revokeObjectURL(
            state.previewUrl
        );

        state.previewUrl=null;
    }

    const url=
        `api/view.php?id=${encodeURIComponent(doc.id)}&t=${Date.now()}`;

    if(k==="image")
        box.innerHTML=
            `<img
                src="${url}"
                alt="${escapeHtml(doc.file_name)}">`;

    else if(k==="pdf")
        box.innerHTML=
            `<iframe
                src="${url}"
                title="PDF preview">
            </iframe>`;

    else
        box.innerHTML=
            `<div class="preview-file">

                <div class="empty-icon">
                    ▣
                </div>

                <h3>
                    Preview is available for PDF and image files.
                </h3>

                <p>
                    Use Download to open the original file.
                </p>

            </div>`;

    $("previewModal")
        .classList.add("show");
}

function downloadDocument(id){

    if(!requireAuth())
        return;

    window.location.href=
        `api/download.php?id=${encodeURIComponent(id)}`;

    showToast(
        "Download request sent"
    );
}

function shareDocument(id,name){

    if(!requireAuth())
        return;

    state.shareId=id;

    $("shareDocumentName")
        .textContent=name;

    $("shareEmail").value="";

    openModal(
        "shareModal"
    );
}

async function createShare(){

    const email=
        $("shareEmail")
        .value
        .trim();

    const permission=
        $("sharePermission")
        .value;

    if(!email.includes("@"))
        return showToast(
            "Enter a valid email address",
            true
        );

    const r=
        await apiFetch(
            "shares.php",
            {
                method:"POST",
                body:JSON.stringify({
                    document_id:state.shareId,
                    email,
                    permission
                })
            }
        );

    if(r.success){

        closeModal(
            "shareModal"
        );

        await refreshAll();

        showToast(
            "Document shared successfully"
        );
    }
}

async function createFolder(){

    const name=
        $("newFolderName")
        .value
        .trim();

    if(!name)
        return showToast(
            "Enter a folder name",
            true
        );

    const r=
        await apiFetch(
            "folders.php",
            {
                method:"POST",
                body:JSON.stringify({
                    name
                })
            }
        );

    if(r.success){

        closeModal(
            "folderModal"
        );

        $("newFolderName")
            .value="";

        renderFolders();

        showToast(
            "Folder created"
        );
    }
}

async function saveSettings(){

    const name=
        $("settingsName")
        .value
        .trim();

    const email=
        $("settingsEmail")
        .value
        .trim();

    if(!name||!email)
        return showToast(
            "Enter valid profile details",
            true
        );

    const r=
        await apiFetch(
            "profile.php",
            {
                method:"PUT",
                body:JSON.stringify({
                    name,
                    email
                })
            }
        );

    if(r.success){

        currentUser=r.user;

        updateAuthUI();

        showToast(
            "Profile updated"
        );
    }
}

function setTheme(dark){

    document.body.classList.toggle(
        "dark",
        dark
    );

    localStorage.setItem(
        THEME_KEY,
        dark?
        "dark":
        "light"
    );

    $("themeBtn").textContent=
        dark?
        "☀":
        "☾";

    $("settingsTheme").value=
        dark?
        "dark":
        "light";
}

function openModal(id){
    $(id).classList.add("show")
}

function renderQueue(){

    const q=
        $("uploadQueue");

    q.innerHTML=
        state.queue
        .map(
            (f,i)=>
            `<div class="queue-item">

                <div class="file-icon ${
                    kind(f.name,f.type)
                }">
                    ${label(f.name)}
                </div>

                <div class="grow">

                    <b>
                        ${escapeHtml(f.name)}
                    </b>

                    <small>
                        ${humanSize(f.size)}
                    </small>

                </div>

                <button
                    onclick="state.queue.splice(${i},1);renderQueue()">

                    ×

                </button>

            </div>`
        )
        .join("");

    $("confirmUpload").disabled=
        !state.queue.length;
}

async function refreshAll(){

    renderStatsOnly();

    await loadDashboard();

    await renderDocuments();

    await renderFolders();

    await renderShared();

    await renderStarred();

    await renderTrash();

    await renderActivity();
}

function renderStatsOnly(){
    /* dashboard is refreshed by loadDashboard */
}

async function clearActivity(){

    if(
        !confirm(
            "Clear your entire activity log?"
        )
    )
        return;

    const r=
        await apiFetch(
            "activity.php",
            {
                method:"DELETE"
            }
        );

    if(r.success){

        renderActivity();

        loadDashboard();

        showToast(
            "Activity log cleared"
        );
    }
}

function toggleNotifications(){
    $("notificationPanel")
        .classList.toggle("show")
}


/* =========================================================
   PAGE START
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    async()=>{

        loadMetaForClient();

        await bootstrap();

        /*
         * IMPORTANT:
         * Account permanent rahega.
         * Current browser tab ka login temporary marker
         * sessionStorage mein rahega.
         */

        const activeTabSession=
            sessionStorage.getItem(
                TAB_SESSION_KEY
            )==="active";


        /*
         * Agar server session exist karta hai
         * lekin current tab ka session marker nahi hai,
         * to old session logout kar do.
         */

        if(
            currentUser &&
            !activeTabSession
        ){

            await apiFetch(
                "auth.php?action=logout",
                {
                    method:"POST",
                    body:JSON.stringify({})
                }
            );

            currentUser=null;

            sessionStorage.removeItem(
                TAB_SESSION_KEY
            );

            updateAuthUI();

            openAuth(
                "signin",
                true
            );

        }else if(!currentUser){

            sessionStorage.removeItem(
                TAB_SESSION_KEY
            );

            openAuth(
                "signin",
                true
            );
        }


        /* =========================
           NAVIGATION
           ========================= */

        $all(".nav-item").forEach(
            b=>
                b.onclick=()=>
                    showPage(
                        b.dataset.page
                    )
        );

        $all("[data-go]").forEach(
            b=>
                b.onclick=()=>
                    showPage(
                        b.dataset.go
                    )
        );

        $all("[data-page-link]").forEach(
            b=>
                b.onclick=()=>
                    showPage(
                        b.dataset.pageLink
                    )
        );


        /* =========================
           FILTER
           ========================= */

        $all(".filter").forEach(
            b=>
                b.onclick=()=>{

                    $all(".filter")
                        .forEach(
                            x=>
                                x.classList.remove(
                                    "active"
                                )
                        );

                    b.classList.add(
                        "active"
                    );

                    state.filter=
                        b.dataset.filter;

                    renderDocuments();
                }
        );

        $("sortSelect").onchange=e=>{

            state.sort=
                e.target.value;

            renderDocuments();
        };

        $("searchInput").oninput=
            ()=>renderDocuments();


        /* =========================
           UPLOAD BUTTONS
           ========================= */

        $("sidebarUploadBtn").onclick=
            openUpload;

        $("heroUpload").onclick=
            openUpload;

        $("documentsUpload").onclick=
            openUpload;

        $("refreshAdmin").onclick=
            renderAdmin;

        $("quickUpload").onclick=
            openUpload;

        $("heroFolder").onclick=
            ()=>requireAuth()&&openFolder();

        $("quickFolder").onclick=
            ()=>requireAuth()&&openFolder();

        $("foldersAdd").onclick=
            ()=>requireAuth()&&openFolder();

        $("quickShare").onclick=
            ()=>requireAuth("shared");

        $("quickSecurity").onclick=
            ()=>requireAuth("security");


        /* =========================
           AUTH BUTTONS
           ========================= */

        $("topSignIn").onclick=
            ()=>openAuth("signin");

        $("topCreateAccount").onclick=
            ()=>openAuth("signup");

        $all("[data-auth]").forEach(
            b=>
                b.onclick=
                    ()=>openAuth(
                        b.dataset.auth
                    )
        );


        /* =========================
           SIGN IN
           ========================= */

        $("signinForm").onsubmit=
            async e=>{

                e.preventDefault();

                const r=
                    await apiFetch(
                        "auth.php?action=login",
                        {
                            method:"POST",
                            body:JSON.stringify({
                                email:
                                    $("loginEmail")
                                    .value
                                    .trim(),

                                password:
                                    $("loginPassword")
                                    .value
                            })
                        }
                    );

                if(r.success){

                    /*
                     * Current tab ko active mark karo.
                     */
                    sessionStorage.setItem(
                        TAB_SESSION_KEY,
                        "active"
                    );

                    currentUser=r.user;

                    updateAuthUI();

                    closeModal(
                        "authModal"
                    );

                    await refreshAll();

                    showToast(
                        "Signed in successfully"
                    );
                }
            };


        /* =========================
           CREATE ACCOUNT
           ========================= */

        $("signupForm").onsubmit=
            async e=>{

                e.preventDefault();

                if(
                    $("signupPassword").value !==
                    $("signupPassword2").value
                )
                    return showToast(
                        "Passwords do not match",
                        true
                    );

                const r=
                    await apiFetch(
                        "auth.php?action=register",
                        {
                            method:"POST",
                            body:JSON.stringify({
                                name:
                                    $("signupName")
                                    .value
                                    .trim(),

                                email:
                                    $("signupEmail")
                                    .value
                                    .trim(),

                                password:
                                    $("signupPassword")
                                    .value
                            })
                        }
                    );

                if(r.success){

                    /*
                     * Account create hone ke baad
                     * current tab logged-in mark hoga.
                     */
                    sessionStorage.setItem(
                        TAB_SESSION_KEY,
                        "active"
                    );

                    currentUser=r.user;

                    updateAuthUI();

                    closeModal(
                        "authModal"
                    );

                    await refreshAll();

                    showToast(
                        "Account created successfully"
                    );
                }
            };


        /* =========================
           THEME
           ========================= */

        $("themeBtn").onclick=
            ()=>setTheme(
                !document.body
                    .classList
                    .contains("dark")
            );

        $("settingsTheme").onchange=
            e=>
                setTheme(
                    e.target.value==="dark"
                );

        $("saveSettings").onclick=
            saveSettings;


        /* =========================
           PROFILE
           ========================= */

        $("topProfileBtn").onclick=
            toggleProfile;

        $("sideProfileBtn").onclick=
            toggleProfile;

        $("goSettings").onclick=()=>{
            showPage("settings");

            $("profilePopover")
                .classList.remove(
                    "show"
                );
        };


        /* =========================
           LOGOUT
           ========================= */

        $("logoutBtn").onclick=
            async()=>{

                const r=
                    await apiFetch(
                        "auth.php?action=logout",
                        {
                            method:"POST",
                            body:JSON.stringify({})
                        }
                    );

                if(r.success){

                    /*
                     * Current tab session marker remove.
                     */
                    sessionStorage.removeItem(
                        TAB_SESSION_KEY
                    );

                    currentUser=null;

                    updateAuthUI();

                    $("profilePopover")
                        .classList.remove(
                            "show"
                        );

                    showPage(
                        "dashboard"
                    );

                    /*
                     * Logout ke baad
                     * Sign In screen khulegi.
                     */
                    openAuth(
                        "signin",
                        true
                    );

                    showToast(
                        "Logged out successfully"
                    );
                }
            };


        /* =========================
           NOTIFICATIONS
           ========================= */

        $("notifyBtn").onclick=
            toggleNotifications;

        $("closeNotifications").onclick=
            toggleNotifications;


        /* =========================
           MOBILE MENU
           ========================= */

        $("mobileMenuBtn").onclick=
            ()=>
                $("sidebar")
                    .classList
                    .toggle("open");


        /* =========================
           FILE UPLOAD
           ========================= */

        $("chooseFiles").onclick=
            ()=>
                $("fileInput").click();

        $("fileInput").onchange=
            e=>{
                state.queue=[
                    ...e.target.files
                ];

                renderQueue();
            };

        $("confirmUpload").onclick=
            uploadFiles;

        $("dropZone").addEventListener(
            "dragover",
            e=>{
                e.preventDefault();

                $("dropZone")
                    .classList.add(
                        "drag"
                    );
            }
        );

        $("dropZone").addEventListener(
            "dragleave",
            ()=>
                $("dropZone")
                    .classList.remove(
                        "drag"
                    )
        );

        $("dropZone").addEventListener(
            "drop",
            e=>{

                e.preventDefault();

                $("dropZone")
                    .classList.remove(
                        "drag"
                    );

                state.queue=[
                    ...e.dataTransfer.files
                ];

                renderQueue();
            }
        );


        /* =========================
           FOLDER / SHARE
           ========================= */

        $("createFolderBtn").onclick=
            createFolder;

        $("createShareBtn").onclick=
            createShare;

        $("emptyTrash").onclick=
            emptyTrash;

        $("clearActivity").onclick=
            clearActivity;

        $("downloadPreviewBtn").onclick=
            ()=>
                state.previewId &&
                downloadDocument(
                    state.previewId
                );


        /* =========================
           CLOSE MODALS
           ========================= */

        $all("[data-close]").forEach(
            b=>
                b.onclick=
                    ()=>closeModal(
                        b.dataset.close
                    )
        );


        /* =========================
           OUTSIDE CLICK
           ========================= */

        document.addEventListener(
            "click",
            e=>{

                if(
                    !e.target.closest(
                        "#profilePopover"
                    ) &&
                    !e.target.closest(
                        "#topProfileBtn"
                    ) &&
                    !e.target.closest(
                        "#sideProfileBtn"
                    )
                )
                    $("profilePopover")
                        .classList.remove(
                            "show"
                        );

                if(
                    !e.target.closest(
                        ".notify-wrap"
                    )
                )
                    $("notificationPanel")
                        .classList.remove(
                            "show"
                        );
            }
        );


        /* =========================
           KEYBOARD
           ========================= */

        document.addEventListener(
            "keydown",
            e=>{

                if(
                    (e.ctrlKey||e.metaKey) &&
                    e.key.toLowerCase()==="k"
                ){

                    e.preventDefault();

                    $("searchInput")
                        .focus();
                }

                if(e.key==="Escape"){

                    /*
                     * Auth gate ko Escape se
                     * close nahi kar sakte.
                     */
                    if(
                        document.body.classList
                            .contains(
                                "auth-gate-active"
                            )
                    ){

                        openAuth(
                            "signin",
                            true
                        );

                        return;
                    }

                    $all(".modal")
                        .forEach(
                            m=>
                                m.classList.remove(
                                    "show"
                                )
                        );

                    $("profilePopover")
                        .classList.remove(
                            "show"
                        );

                    $("notificationPanel")
                        .classList.remove(
                            "show"
                        );
                }
            }
        );
    }
);


/* =========================
   THEME LOAD
   ========================= */

function loadMetaForClient(){

    setTheme(
        localStorage.getItem(
            THEME_KEY
        )==="dark"
    );
}