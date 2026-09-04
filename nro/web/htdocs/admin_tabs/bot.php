<?php
/* Tab: Quản lý Bot - include bởi admin.php
   Hệ thống duy nhất: Virtual Player (Bot AI) */
?>
<h3 class="mb-4">Quản Lý Bot</h3>
<div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

<div class="card p-3 mb-3 border-warning">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-brain text-warning"></i> Virtual Player (Bot AI thông minh)</h6>
    <div class="row g-2 align-items-center mb-2">
        <div class="col-md-5">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold">Trạng thái:</span>
                <span id="vpState" class="badge bg-secondary">Đang tải...</span>
                <button class="btn btn-sm btn-success" onclick="vpSetEnabled(1)"><i class="fa-solid fa-power-off"></i> BẬT</button>
                <button class="btn btn-sm btn-danger" onclick="vpSetEnabled(0)"><i class="fa-solid fa-power-off"></i> TẮT</button>
            </div>
        </div>
        <div class="col-md-7 text-end">
            <span class="me-2">Dân số: <b id="vpCount">0/0</b> (Online: <b id="vpOnline">0</b>)</span>
            <input type="number" id="vpPopIn" class="form-control form-control-sm d-inline-block" style="width:80px" value="30" min="0" max="200">
            <button class="btn btn-sm btn-outline-primary" onclick="vpSetPopulation()">Set Dân Số</button>
            <input type="number" id="vpSpAmount" class="form-control form-control-sm d-inline-block" style="width:70px" value="1" min="1" max="50">
            <button class="btn btn-sm btn-success" onclick="vpSpawn()"><i class="fa-solid fa-plus"></i> Tạo Ngay</button>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="small text-muted">EXP <b id="vpExpRate">-</b> | Vàng <b id="vpGoldRate">-</b> | Chat <b id="vpChatRate">-</b> | Đổi map <b id="vpMapRate">-</b> | Tặng đồ <b id="vpGiftRate">-</b> | AFK <b id="vpAfkRate">-</b> | Bảo vệ Player: <b id="vpProtect">-</b> | Ghé thăm/player: <b id="vpPresPer" class="text-warning">-</b> <span class="text-muted">(<b id="vpPresSec">-</b>s)</span></span>
        <span class="flex-grow-1"></span>
        <button class="btn btn-sm btn-outline-info" onclick="vpRefreshRank()"><i class="fa-solid fa-ranking-star"></i> Cập nhật BXH</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="vpSave()"><i class="fa-solid fa-floppy-disk"></i> Lưu Ngay</button>
        <button class="btn btn-sm btn-outline-danger" onclick="vpRemoveAll()"><i class="fa-solid fa-trash"></i> Xóa Toàn Bộ</button>
    </div>

    <div class="table-responsive mt-3" style="max-height:400px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr><th>ID</th><th>Tên</th><th>Power</th><th>Hoạt động</th><th>Map</th><th>Ghé thăm</th><th>Tính cách</th><th>Sửa</th><th>Xóa</th></tr>
            </thead>
            <tbody id="vpBotRows">
                <tr><td colspan="9" class="text-center text-muted">Đang tải...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= HIEN DIEN LUAN PHIEN (PRESENCE ROTATION) ================= -->
<div class="card p-3 mb-3 border-warning">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">
        <i class="fa-solid fa-people-arrows text-warning"></i> Hiện Diện Luân Phiên (Bot ghé thăm người chơi thật)
        <button class="btn btn-sm btn-outline-secondary float-end py-0" onclick="loadVpPresence()"><i class="fa-solid fa-rotate"></i></button>
    </h6>
    <p class="small text-muted m-0 mb-2">Đa số bot sống độc lập khắp thế giới. Hệ thống luôn luân phiên giữ khoảng <b id="prTarget">-</b> bot hoạt động gần <b>mỗi</b> người chơi thật, mỗi lượt ghé kéo dài <b id="prVisit">-</b> giây rồi nhường cho bot khác. Đặt "Ghé thăm/player" = 0 để tắt (bot hoàn toàn độc lập).</p>
    <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
        <span class="badge bg-secondary" id="prEnabled">-</span>
        <span class="small">Người chơi thật online: <b id="prRealPlayers">0</b></span>
        <span class="small">Tổng bot đang ghé thăm: <b id="prVisitingTotal" class="text-warning">0</b></span>
    </div>
    <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
        <table class="table table-bordered table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>ID Player</th><th>Tên người chơi</th><th>Map</th><th class="text-center">Bot cùng khu</th><th class="text-center">Đang ghé thăm</th></tr>
            </thead>
            <tbody id="prRows">
                <tr><td colspan="5" class="text-center text-muted">Đang tải...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= CAU CHAT TUY CHINH ================= -->
<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-comment-dots text-info"></i> Câu Chat Tùy Chỉnh Của Bot</h6>
    <p class="small text-muted m-0 mb-2">Khi có người chơi thật gần, bot sẽ ưu tiên dùng các câu này để chat (60% số lần nói). Để trống = dùng câu tự sinh theo tính cách.</p>
    <div class="d-flex gap-2 mb-2">
        <input type="text" id="chatMsgIn" class="form-control form-control-sm" placeholder="Nhập câu chat... (tối đa 120 ký tự)" maxlength="120">
        <button class="btn btn-sm btn-primary flex-shrink-0" onclick="vpChatAdd()"><i class="fa-solid fa-plus"></i> Thêm</button>
        <button class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="loadVpChats()"><i class="fa-solid fa-rotate"></i></button>
    </div>
    <ul class="list-group list-group-flush" id="chatList" style="max-height:250px;overflow-y:auto;">
        <li class="list-group-item text-muted small">Đang tải...</li>
    </ul>
</div>

<!-- ================= CONFIG EDITOR ================= -->
<div class="card p-3 mb-3 border-info">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-gear text-info"></i> Cấu Hình Bot AI (Virtual Player)</h6>
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small mb-1">EXP Rate (0-1)</label>
            <input type="number" id="cfgExpRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Gold Rate (0-1)</label>
            <input type="number" id="cfgGoldRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Chat Rate (0-1)</label>
            <input type="number" id="cfgChatRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Đổi Map (0-1)</label>
            <input type="number" id="cfgMapRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Tặng Đồ (0-1)</label>
            <input type="number" id="cfgGiftRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">AFK Rate (0-1)</label>
            <input type="number" id="cfgAfkRate" class="form-control form-control-sm" step="0.05" min="0" max="1">
        </div>
    </div>
    <div class="row g-2 align-items-end mt-1">
        <div class="col-md-2">
            <label class="form-label small mb-1">Bảo vệ Player</label>
            <select id="cfgProtect" class="form-select form-select-sm">
                <option value="1">BẬT</option>
                <option value="0">TẮT</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Ghé thăm / người chơi <span class="text-muted">(0-50, 0=tắt)</span></label>
            <input type="number" id="cfgPresPer" class="form-control form-control-sm" step="1" min="0" max="50">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Thời gian ghé thăm <span class="text-muted">(30-3600 giây)</span></label>
            <input type="number" id="cfgPresSec" class="form-control form-control-sm" step="10" min="30" max="3600">
        </div>
        <div class="col-md-4 text-end">
            <label class="form-label small mb-1 d-block">&nbsp;</label>
            <button class="btn btn-sm btn-info fw-bold" onclick="saveVpConfig()"><i class="fa-solid fa-floppy-disk"></i> Lưu Cấu Hình</button>
        </div>
    </div>
</div>

<!-- ================= BOT DETAIL MODAL ================= -->
<div class="modal fade" id="botDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title"><i class="fa-solid fa-brain"></i> Chi Tiết Bot: <span id="bdTitle" class="text-primary"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-user"></i> Tính Cách (Profile)</h6>
            <div id="bdProfile" class="small">Đang tải...</div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-bullseye"></i> Mục Tiêu (Goals)</h6>
            <div id="bdGoals" class="small">Đang tải...</div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-heart"></i> Nhu Cầu (Needs)</h6>
            <div id="bdNeeds" class="small">Đang tải...</div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-scroll"></i> Nhiệm Vụ (Quest)</h6>
            <div id="bdQuest" class="small">Đang tải...</div>
          </div>
        </div>
        <hr>
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small mb-1">Map ID</label>
            <input type="number" id="bdTeleportMap" class="form-control form-control-sm" value="0">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">X</label>
            <input type="number" id="bdTeleportX" class="form-control form-control-sm" value="300">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">Y</label>
            <input type="number" id="bdTeleportY" class="form-control form-control-sm" value="300">
          </div>
          <div class="col-md-5 d-flex gap-1">
            <button class="btn btn-sm btn-warning flex-fill" onclick="bdTeleport()"><i class="fa-solid fa-location-dot"></i> Teleport</button>
            <button class="btn btn-sm btn-info flex-fill" onclick="bdRegear()"><i class="fa-solid fa-shirt"></i> Regear</button>
            <button class="btn btn-sm btn-success flex-fill" onclick="bdAddGold()"><i class="fa-solid fa-coins"></i> +Gold</button>
          </div>
        </div>
      </div>
      <div class="modal-footer py-1">
        <button class="btn btn-sm btn-outline-info" onclick="bdReload()"><i class="fa-solid fa-rotate"></i> Làm mới</button>
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL SUA BOT ================= -->
<div class="modal fade" id="botEditModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title"><i class="fa-solid fa-user-pen"></i> Sửa Bot: <span id="beTitle" class="text-primary"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Thong tin co ban -->
        <div class="card p-2 mb-3 bg-light">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small mb-1">Tên</label>
              <input type="text" id="beName" class="form-control form-control-sm" maxlength="24">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-1">Sức mạnh</label>
              <input type="number" id="bePower" class="form-control form-control-sm" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label small mb-1">Vàng</label>
              <input type="number" id="beGold" class="form-control form-control-sm" min="0">
            </div>
            <div class="col-md-3">
              <button class="btn btn-sm btn-primary w-100" onclick="beSaveInfo()"><i class="fa-solid fa-floppy-disk"></i> Lưu Thông Tin</button>
            </div>
          </div>
          <div class="small text-muted mt-2" id="beMeta"></div>
        </div>

        <div class="row g-3">
          <!-- Trang bi -->
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-shirt"></i> Trang Bị <small class="text-muted fw-normal">(click chọn ô)</small></h6>
            <div id="beBodyGrid" class="d-flex flex-wrap gap-2 mb-2"></div>
            <button class="btn btn-sm btn-outline-danger d-none" id="beBodyDelBtn" onclick="beDelItem('body')"><i class="fa-solid fa-trash"></i> Xóa Vật Phẩm Đã Chọn</button>
          </div>
          <!-- Tui do -->
          <div class="col-md-6">
            <h6 class="fw-bold border-bottom pb-1"><i class="fa-solid fa-bag-shopping"></i> Túi Đồ <small class="text-muted fw-normal">(click chọn ô)</small></h6>
            <div id="beBagGrid" class="d-flex flex-wrap gap-2 mb-2" style="max-height:260px;overflow-y:auto;"></div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-danger d-none" id="beBagDelBtn" onclick="beDelItem('bag')"><i class="fa-solid fa-trash"></i> Xóa Ô Đã Chọn</button>
              <button class="btn btn-sm btn-outline-success d-none" id="beEquipBtn" onclick="beEquipSelected()"><i class="fa-solid fa-shirt"></i> Mặc Vào Người</button>
            </div>
          </div>
        </div>

        <!-- Them vat pham -->
        <div class="card p-2 mt-3 bg-light">
          <h6 class="fw-bold border-bottom pb-1 mb-2"><i class="fa-solid fa-plus"></i> Thêm Vật Phẩm</h6>
          <div class="row g-2 align-items-end">
            <div class="col-md-6 position-relative">
              <label class="form-label small mb-1">Tìm theo tên hoặc ID template</label>
              <input type="text" id="beSearch" class="form-control form-control-sm" placeholder="VD: đậu thần hoặc 457..." autocomplete="off">
              <div id="beResults" class="position-absolute bg-white border rounded shadow w-100 mt-1 d-none" style="max-height:220px;overflow-y:auto;z-index:1050;"></div>
            </div>
            <div class="col-md-2">
              <label class="form-label small mb-1">Số lượng</label>
              <input type="number" id="beQty" class="form-control form-control-sm" value="1" min="1" max="9999">
            </div>
            <div class="col-md-4">
              <label class="form-label small mb-1 d-block">&nbsp;</label>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-success flex-fill" onclick="beAddItem('bag')"><i class="fa-solid fa-bag-shopping"></i> Vào Túi</button>
                <button class="btn btn-sm btn-warning flex-fill" onclick="beAddItem('body')"><i class="fa-solid fa-shirt"></i> Mặc Liền</button>
              </div>
            </div>
          </div>
          <div class="mt-2 d-flex align-items-center gap-2" id="bePreviewWrap" style="display:none !important;">
            <img id="bePreviewImg" src="" width="72" height="72" class="border rounded" style="image-rendering:pixelated;">
            <div><div id="bePreviewName" class="fw-bold small"></div><div id="bePreviewInfo" class="text-muted small"></div></div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-1">
        <button class="btn btn-sm btn-outline-info" onclick="beReloadDetail()"><i class="fa-solid fa-rotate"></i> Làm mới</button>
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
// ===== chung =====
function botAlert(data){
    let a = document.getElementById('ajaxAlert');
    a.classList.remove('d-none','alert-danger','alert-success');
    a.classList.add(data.status=='error'?'alert-danger':'alert-success');
    a.innerText = data.msg||data.status;
    setTimeout(()=>a.classList.add('d-none'),3000);
}
function api(action, params){
    let qs = Object.keys(params||{}).map(k=>k+'='+encodeURIComponent(params[k])).join('&');
    return fetch('?ajax=proxy&action='+action+(qs?'&'+qs:'')).then(r=>r.json());
}

// ===== VIRTUAL PLAYER =====
const vpStateNames = {
    SPAWN:'Vừa vào game',IDLE:'Đứng yên',FIND_TARGET:'Tìm quái',MOVE_TO_TARGET:'Di chuyển',
    ATTACK:'Đánh quái',PICK_ITEM:'Nhặt đồ',ESCAPE:'Bỏ chạy',HEAL:'Hồi máu',REST:'Nghỉ ngơi',
    GO_SHOP:'Đi shop',CHANGE_MAP:'Đổi map',EXPLORE:'Khám phá',QUEST:'Làm nhiệm vụ',
    SOCIAL:'Trò chuyện',DEAD:'Chết',OFFLINE:'Offline'
};
const mapNames = {0:'Hành tinh Đỏ',1:'Hành tinh Namếc',2:'Hành tinh Xayda'};
let lastBots = [];
function loadVpStatus(){
    fetch('?ajax=proxy&action=vp_status').then(r=>r.json()).then(d=>{
        let st = document.getElementById('vpState');
        if(d.enabled){ st.textContent='ĐANG CHẠY'; st.className='badge bg-success'; }
        else { st.textContent='ĐANG TẮT'; st.className='badge bg-secondary'; }
        document.getElementById('vpCount').textContent=d.count+'/'+d.target;
        document.getElementById('vpOnline').textContent=d.online_count;
        document.getElementById('vpPopIn').value=d.target;
        document.getElementById('vpExpRate').textContent=(d.exp_rate*100).toFixed(0)+'%';
        document.getElementById('vpGoldRate').textContent=(d.gold_rate*100).toFixed(0)+'%';
        document.getElementById('vpChatRate').textContent=(d.chat_rate*100).toFixed(0)+'%';
        document.getElementById('vpMapRate').textContent=(d.map_change_rate*100).toFixed(0)+'%';
        document.getElementById('vpGiftRate').textContent=(d.gift_rate*100).toFixed(0)+'%';
        document.getElementById('vpAfkRate').textContent=(d.afk_rate*100).toFixed(0)+'%';
        document.getElementById('vpProtect').textContent=d.player_protection?'BẬT':'TẮT';
        document.getElementById('vpPresPer').textContent=(d.presence_per_player>0?d.presence_per_player:'TẮT');
        document.getElementById('vpPresSec').textContent=d.presence_visit_seconds;
        // Fill config editor
        document.getElementById('cfgExpRate').value=d.exp_rate;
        document.getElementById('cfgGoldRate').value=d.gold_rate;
        document.getElementById('cfgChatRate').value=d.chat_rate;
        document.getElementById('cfgMapRate').value=d.map_change_rate;
        document.getElementById('cfgGiftRate').value=d.gift_rate;
        document.getElementById('cfgAfkRate').value=d.afk_rate;
        document.getElementById('cfgProtect').value=d.player_protection?'1':'0';
        document.getElementById('cfgPresPer').value=d.presence_per_player;
        document.getElementById('cfgPresSec').value=d.presence_visit_seconds;
        lastBots = d.bots||[];
        let rows = '';
        lastBots.forEach(b=>{
            let stateTxt = vpStateNames[b.state] || b.state;
            let badge = b.state=='OFFLINE' ? 'bg-secondary' : (b.state=='DEAD' ? 'bg-danger' : (b.online?'bg-primary':'bg-dark'));
            let power = Number(b.power).toLocaleString('vi-VN');
            let mapTxt = b.map_id<0 ? '-' : (mapNames[b.map_id] || ('Map '+b.map_id));
            let safeName = b.name.replace(/'/g,"\\'");
            let visitTxt = b.visiting
                ? `<span class="badge bg-warning text-dark" title="Đang ghé thăm người chơi #${b.host_id}"><i class="fa-solid fa-location-arrow"></i> #${b.host_id}</span>`
                : '<span class="text-muted small">độc lập</span>';
            rows += `<tr>
                <td class="text-muted small">${b.id}</td>
                <td>${b.name}</td>
                <td>${power}</td>
                <td><span class="badge ${badge}">${b.online?'':'⏸ '}${stateTxt}</span></td>
                <td class="small">${mapTxt}</td>
                <td class="text-center">${visitTxt}</td>
                <td class="small text-muted">${b.pers||'-'}</td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary py-0 px-1" title="Sửa" onclick="openBotEdit(${b.id})"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-info py-0 px-1" title="Chi tiết" onclick="openBotDetail(${b.id})"><i class="fa-solid fa-brain"></i></button>
                    </div>
                </td>
                <td><button class="btn btn-sm btn-outline-danger py-0" onclick="vpRemove(${b.id},'${safeName}')"><i class="fa-solid fa-xmark"></i></button></td>
            </tr>`;
        });
        if(!rows) rows = '<tr><td colspan="9" class="text-center text-muted">Chưa có Virtual Player nào</td></tr>';
        document.getElementById('vpBotRows').innerHTML = rows;
    }).catch(()=>{});
}
function vpSetEnabled(v){
    if(v==0 && !confirm('Tắt hệ thống sẽ XÓA toàn bộ Virtual Player. Tiếp tục?')) return;
    api('vp_set_enabled',{val:v}).then(data=>{botAlert(data);loadVpStatus();});
}
function vpSetPopulation(){
    let v = document.getElementById('vpPopIn').value;
    if(v===""||isNaN(v)||v<0||v>200){ alert('Dân số phải từ 0-200!'); return; }
    api('vp_set_population',{val:v}).then(data=>{botAlert(data);loadVpStatus();});
}
function vpSpawn(){
    let amt = document.getElementById('vpSpAmount').value;
    if(amt===""||isNaN(amt)||amt<1||amt>50){ alert('Số lượng phải từ 1-50!'); return; }
    api('vp_spawn',{amount:amt}).then(data=>{botAlert(data);loadVpStatus();});
}
function vpRemove(id, name){
    if(!confirm('Xóa bot "'+name+'"?\n(Tiến trình đã lưu sẽ mất)')) return;
    api('vp_remove',{id:id}).then(data=>{botAlert(data);loadVpStatus();});
}
function vpRemoveAll(){
    if(!confirm('Xóa TOÀN BỘ Virtual Player?\nToàn bộ tiến trình + bộ nhớ của bot sẽ mất!')) return;
    api('vp_remove_all').then(data=>{botAlert(data);loadVpStatus();});
}
function vpSave(){ api('vp_save').then(botAlert); }
function vpRefreshRank(){ api('vp_refresh_rank').then(botAlert); }

// ===== HIEN DIEN LUAN PHIEN (PRESENCE) =====
function loadVpPresence(){
    fetch('?ajax=proxy&action=vp_presence').then(r=>r.json()).then(d=>{
        if(!d || d.status!=='success') return;
        let en = document.getElementById('prEnabled');
        if(d.enabled){ en.textContent='ĐANG BẬT'; en.className='badge bg-success'; }
        else { en.textContent='ĐÃ TẮT (bot độc lập)'; en.className='badge bg-secondary'; }
        document.getElementById('prTarget').textContent = d.enabled ? d.target : 'TẮT';
        document.getElementById('prVisit').textContent = d.visit_seconds;
        document.getElementById('prRealPlayers').textContent = d.real_players;
        document.getElementById('prVisitingTotal').textContent = d.visiting_total;
        let rows = '';
        (d.players||[]).forEach(p=>{
            let mapTxt = p.map_id<0 ? '-' : (mapNames[p.map_id] || ('Map '+p.map_id));
            let name = (p.name||'').replace(/</g,'&lt;');
            let hit = p.visiting>=d.target ? 'bg-success' : (p.visiting>0 ? 'bg-warning text-dark' : 'bg-secondary');
            rows += `<tr>
                <td class="text-muted small">${p.id}</td>
                <td>${name}</td>
                <td class="small">${mapTxt}</td>
                <td class="text-center">${p.bots_in_zone}</td>
                <td class="text-center"><span class="badge ${hit}">${p.visiting}/${d.enabled?d.target:0}</span></td>
            </tr>`;
        });
        if(!rows) rows = '<tr><td colspan="5" class="text-center text-muted">Không có người chơi thật nào đang online</td></tr>';
        document.getElementById('prRows').innerHTML = rows;
    }).catch(()=>{});
}

// ===== CONFIG EDITOR =====
function saveVpConfig(){
    let vals = {
        exp_rate: document.getElementById('cfgExpRate').value,
        gold_rate: document.getElementById('cfgGoldRate').value,
        chat_rate: document.getElementById('cfgChatRate').value,
        map_change_rate: document.getElementById('cfgMapRate').value,
        gift_rate: document.getElementById('cfgGiftRate').value,
        afk_rate: document.getElementById('cfgAfkRate').value,
        player_protection: document.getElementById('cfgProtect').value,
        presence_per_player: document.getElementById('cfgPresPer').value,
        presence_visit_seconds: document.getElementById('cfgPresSec').value
    };
    let promises = Object.entries(vals).map(([k,v])=>api('vp_config_set',{key:k,val:v}));
    Promise.all(promises).then(results=>{
        let last = results[results.length-1];
        botAlert(last);
        loadVpStatus();
        loadVpPresence();
    });
}

// ===== BOT DETAIL MODAL =====
let bdBotId = null;
let bdModal = null;
function openBotDetail(id){
    bdBotId = id;
    if(!bdModal) bdModal = new bootstrap.Modal(document.getElementById('botDetailModal'));
    bdReload();
    bdModal.show();
}
function bdReload(){
    let id = bdBotId;
    document.getElementById('bdTitle').textContent = 'Bot #'+id;
    // Profile
    api('vp_profile',{id:id}).then(d=>{
        if(d.status=='error'){ document.getElementById('bdProfile').textContent=d.msg; return; }
        let html = '<b>'+d.name+'</b><br>Tính cách: '+(d.personalities||[]).join(', ')+'<br>';
        html += '<table class="table table-sm table-bordered mb-0 mt-1"><tbody>';
        html += '<tr><td>Nói nhiều</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-info" style="width:'+(d.talkativeness*100)+'%">'+(d.talkativeness*100).toFixed(0)+'%</div></div></td></tr>';
        html += '<tr><td>Mạo hiểm</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-warning" style="width:'+(d.risk_tolerance*100)+'%">'+(d.risk_tolerance*100).toFixed(0)+'%</div></div></td></tr>';
        html += '<tr><td>Hữu ích</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-success" style="width:'+(d.helpfulness*100)+'%">'+(d.helpfulness*100).toFixed(0)+'%</div></div></td></tr>';
        html += '<tr><td>Cạnh tranh</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-danger" style="width:'+(d.competitiveness*100)+'%">'+(d.competitiveness*100).toFixed(0)+'%</div></div></td></tr>';
        html += '<tr><td>Lười</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-secondary" style="width:'+(d.laziness*100)+'%">'+(d.laziness*100).toFixed(0)+'%</div></div></td></tr>';
        html += '<tr><td>Tham</td><td><div class="progress" style="height:14px"><div class="progress-bar bg-dark" style="width:'+(d.greed*100)+'%">'+(d.greed*100).toFixed(0)+'%</div></div></td></tr>';
        html += '</tbody></table>';
        document.getElementById('bdProfile').innerHTML = html;
    }).catch(()=>{});
    // Goals
    api('vp_goals',{id:id}).then(d=>{
        if(d.status=='error'){ document.getElementById('bdGoals').textContent=d.msg; return; }
        document.getElementById('bdGoals').innerHTML = '<span class="badge bg-primary mb-1">'+d.long_term+'</span><br><span class="badge bg-success">'+d.short_term+'</span>';
    }).catch(()=>{});
    // Needs
    api('vp_needs',{id:id}).then(d=>{
        if(d.status=='error'){ document.getElementById('bdNeeds').textContent=d.msg; return; }
        let html = '<table class="table table-sm table-bordered mb-0"><tbody>';
        let labels = {hp:'HP',mp:'MP',exp:'EXP',gold:'Vàng',item:'Vật phẩm',quest:'Nhiệm vụ',social:'Xã hội',rest:'Nghỉ ngơi',safety:'An toàn',explore:'Khám phá'};
        Object.entries(labels).forEach(([k,l])=>{
            let v = d[k]||0;
            let color = v>70?'bg-danger':(v>40?'bg-warning':'bg-success');
            html += '<tr><td>'+l+'</td><td><div class="progress" style="height:12px"><div class="progress-bar '+color+'" style="width:'+(v)+'%">'+v.toFixed(0)+'</div></div></td></tr>';
        });
        html += '</tbody></table>';
        document.getElementById('bdNeeds').innerHTML = html;
    }).catch(()=>{});
    // Quest
    api('vp_quest',{id:id}).then(d=>{
        if(d.status=='error'){ document.getElementById('bdQuest').textContent=d.msg; return; }
        let html = '<b>'+d.task+'</b><br>Map mục tiêu: '+(d.objective_map>=0?'Map '+d.objective_map:'-');
        html += '<br>Hoàn thành: '+(d.subtask_done?'<span class="text-success">ĐÃ XONG</span>':'<span class="text-warning">CHƯA</span>');
        document.getElementById('bdQuest').innerHTML = html;
    }).catch(()=>{});
}
function bdTeleport(){
    let mapId = document.getElementById('bdTeleportMap').value||0;
    let x = document.getElementById('bdTeleportX').value||300;
    let y = document.getElementById('bdTeleportY').value||300;
    api('vp_teleport',{id:bdBotId,map:mapId,x:x,y:y}).then(d=>{botAlert(d);bdReload();loadVpStatus();});
}
function bdRegear(){
    if(!confirm('Trang bị lại bot này?')) return;
    api('vp_regear',{id:bdBotId}).then(d=>{botAlert(d);bdReload();});
}
function bdAddGold(){
    let amt = prompt('Nhập số vàng muốn thêm:');
    if(!amt||isNaN(amt)||parseInt(amt)<=0) return;
    api('vp_add_gold',{id:bdBotId,amount:amt}).then(d=>{botAlert(d);bdReload();loadVpStatus();});
}

// ===== CAU CHAT =====
function loadVpChats(){
    fetch('?ajax=proxy&action=vp_chat_list').then(r=>r.json()).then(d=>{
        let ul = document.getElementById('chatList');
        if(!d.lines || !d.lines.length){
            ul.innerHTML = '<li class="list-group-item text-muted small">Chưa có câu chat tùy chỉnh nào.</li>';
            return;
        }
        ul.innerHTML = '';
        d.lines.forEach((line,i)=>{
            let li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center py-1 px-2';
            li.innerHTML = `<span class="small">${line.replace(/</g,'&lt;')}</span>
                <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="vpChatDel(${i})"><i class="fa-solid fa-xmark"></i></button>`;
            ul.appendChild(li);
        });
    }).catch(()=>{});
}
function vpChatAdd(){
    let inp = document.getElementById('chatMsgIn');
    if(!inp.value.trim()){ alert('Nhập câu chat!'); return; }
    api('vp_chat_add',{msg:inp.value.trim()}).then(d=>{botAlert(d);inp.value='';loadVpChats();});
}
function vpChatDel(i){
    if(!confirm('Xóa câu chat này?')) return;
    api('vp_chat_del',{idx:i}).then(d=>{botAlert(d);loadVpChats();});
}

// ===== MODAL SUA BOT =====
const typeNames = {0:'Áo',1:'Quần',2:'Găng',3:'Giày',4:'Dây chuyền',5:'Đậu',6:'Lọ',7:'Súng',8:'Bánh',9:'Pet',10:'Thức ăn',11:'Trứng',12:'Radar',13:'Đá nâng cấp',14:'Giấy',15:'Cây',16:'Hộp quà'};
let beBotId = null;
let beItems = null;       // danh sach template tu bot_items.php
let beSelBody = -1, beSelBag = -1, bePickTempId = null;
let beModal = null;

function iconTag(iconId, size, full){ return '<img src="item_icon.php?id='+iconId+(full?'&size=3':'')+'" width="'+size+'" height="'+size+'" style="image-rendering:pixelated;" loading="lazy" title="Template #'+iconId+'">'; }

function ensureItems(cb){
    if(beItems) { cb(); return; }
    fetch('bot_items.php').then(r=>r.json()).then(arr=>{
        beItems = arr;
        cb();
    }).catch(()=>{ alert('Không tải được danh sách vật phẩm!'); });
}
function itemName(tempId){
    let it = (beItems||[]).find(x=>x.id==tempId);
    return it ? it.n : ('Template #'+tempId);
}
function openBotEdit(id){
    beBotId = id; beSelBody = -1; beSelBag = -1; bePickTempId = null;
    ensureItems(()=>{
        if(!beModal) beModal = new bootstrap.Modal(document.getElementById('botEditModal'));
        beReloadDetail();
        beModal.show();
    });
}
function beReloadDetail(){
    fetch('?ajax=proxy&action=vp_detail&id='+beBotId).then(r=>r.json()).then(d=>{
        if(d.status=='error'){ botAlert(d); beModal.hide(); return; }
        renderDetail(d);
    }).catch(()=>{});
}
function renderDetail(d){
    let i = d.info;
    document.getElementById('beTitle').textContent = i.name;
    document.getElementById('beName').value = i.name;
    document.getElementById('bePower').value = i.power;
    document.getElementById('beGold').value = i.gold;
    document.getElementById('beMeta').innerHTML =
        'ID: <b>'+i.id+'</b> | Giới tính: <b>'+(mapNames[i.gender]||i.gender)+'</b> | HP: '+Number(i.hp).toLocaleString('vi-VN')
        +' / '+Number(i.hp_max).toLocaleString('vi-VN')+' | Đánh: '+Number(i.dame).toLocaleString('vi-VN')
        +' | Trạng thái: <b>'+(vpStateNames[i.state]||i.state)+'</b>';
    // Trang bi (5 slot)
    let bg = document.getElementById('beBodyGrid');
    bg.innerHTML = '';
    let bySlot = {};
    (d.items_body||[]).forEach(it=>bySlot[it.slot]=it);
    let slotNames = ['Áo','Quần','Dây chuyền','Găng','Giày'];
    for(let s=0;s<5;s++){
        let it = bySlot[s];
        let div = document.createElement('div');
        div.className = 'be-slot border rounded d-flex flex-column align-items-center justify-content-center';
        div.style.cssText = 'width:72px;height:84px;cursor:pointer;' + (it?'background:#fff':'border-style:dashed;background:#f8f9fa');
        div.innerHTML = it
            ? iconTag(it.icon,64,1) + '<span class="x-small text-truncate w-100 text-center" style="font-size:9px">'+it.name+'</span>'
            : '<span class="text-muted" style="font-size:10px">'+(slotNames[s]||('Ô '+s))+'</span>';
        if(beSelBody===s) div.style.boxShadow = '0 0 0 3px #0d6efd';
        if(it) div.onclick = ()=>{ beSelBody = (beSelBody===s?-1:s); beSelBag = -1; beReloadDetailRender(); };
        else div.onclick = ()=>{};
        bg.appendChild(div);
    }
    toggleBtn('beBodyDelBtn', beSelBody>=0 && !!bySlot[beSelBody]);
    // Tui do
    let bagGrid = document.getElementById('beBagGrid');
    bagGrid.innerHTML = '';
    let usedIdx = {};
    (d.items_bag||[]).forEach(it=>usedIdx[it.index]=it);
    let maxIdx = 0;
    Object.keys(usedIdx).forEach(k=>{ maxIdx = Math.max(maxIdx, parseInt(k)); });
    let cells = '';
    for(let x=0;x<=maxIdx;x++){
        let it = usedIdx[x];
        let div = document.createElement('div');
        div.className = 'be-slot border rounded d-flex flex-column align-items-center justify-content-center position-relative';
        div.style.cssText = 'width:60px;height:70px;cursor:pointer;' + (it?'background:#fff':'border-style:dashed;background:#f8f9fa');
        div.innerHTML = it
            ? iconTag(it.icon,52,1) + '<span class="position-absolute bottom-0 end-0 badge bg-dark py-0" style="font-size:9px">'+it.qty+'</span>'
            : '<span class="text-muted" style="font-size:9px">Ô '+x+'</span>';
        if(beSelBag===x) div.style.boxShadow = '0 0 0 3px #198754';
        div.onclick = ()=>{
            beSelBag = (beSelBag===x?-1:x); beSelBody = -1;
            document.getElementById('beEquipBtn').classList.toggle('d-none', !(beSelBag>=0 && it));
            document.getElementById('beBagDelBtn').classList.toggle('d-none', !(beSelBag>=0 && it));
        };
        bagGrid.appendChild(div);
    }
    if(!Object.keys(usedIdx).length) bagGrid.innerHTML = '<span class="text-muted small">Túi trống</span>';
    document.getElementById('beBagDelBtn').classList.add('d-none');
    document.getElementById('beEquipBtn').classList.add('d-none');
}
function beReloadDetailRender(){ /* chi ve lai highlight -> reload nhe */ beReloadDetail(); }
function toggleBtn(id, show){ document.getElementById(id).classList.toggle('d-none', !show); }
function beSaveInfo(){
    api('vp_edit_info',{id:beBotId,name:document.getElementById('beName').value,power:document.getElementById('bePower').value,gold:document.getElementById('beGold').value})
        .then(d=>{botAlert(d);loadVpStatus();});
}
function beDelItem(type){
    let slot = type==='body' ? beSelBody : beSelBag;
    if(slot<0){ alert('Chọn ô cần xóa!'); return; }
    if(!confirm('Xóa vật phẩm ở ô này?')) return;
    api('vp_item_del',{id:beBotId,type:type,slot:slot}).then(d=>{botAlert(d);beSelBody=-1;beSelBag=-1;beReloadDetail();});
}
function beAddItem(type){
    if(bePickTempId==null){ alert('Chọn vật phẩm từ ô tìm kiếm trước!'); return; }
    let qty = document.getElementById('beQty').value || 1;
    api('vp_item_add',{id:beBotId,type:type,tempid:bePickTempId,qty:qty}).then(d=>{botAlert(d);if(d.status!='error')beReloadDetail();});
}
function beEquipSelected(){
    if(beSelBag<0){ alert('Chọn ô trong túi!'); return; }
    api('vp_item_equip',{id:beBotId,bag_index:beSelBag}).then(d=>{botAlert(d);beSelBag=-1;beReloadDetail();});
}
// Tim kiem item
document.addEventListener('DOMContentLoaded', function(){
    let si = document.getElementById('beSearch');
    si.addEventListener('input', function(){
        ensureItems(()=>{
            let q = si.value.trim().toLowerCase();
            let res = document.getElementById('beResults');
            if(q.length<1){ res.classList.add('d-none'); return; }
            let hits;
            if(/^\d+$/.test(q)){
                hits = beItems.filter(x=>x.id==q);
            } else {
                hits = beItems.filter(x=>x.n.toLowerCase().includes(q)).slice(0,40);
            }
            res.innerHTML = '';
            if(!hits.length){ res.innerHTML = '<div class="p-2 small text-muted">Không tìm thấy</div>'; }
            hits.slice(0,40).forEach(x=>{
                let a = document.createElement('a');
                a.href = 'javascript:void(0)';
                a.className = 'd-flex align-items-center gap-2 p-1 border-bottom text-decoration-none text-dark be-hit';
                a.innerHTML = iconTag(x.c,28) + '<span class="small flex-grow-1">#'+x.id+' — '+x.n.replace(/</g,'&lt;')+'</span><span class="badge bg-secondary">'+(typeNames[x.t]||('T'+x.t))+'</span>';
                a.onclick = ()=>{
                    bePickTempId = x.id;
                    si.value = x.n+' (#'+x.id+')';
                    res.classList.add('d-none');
                    document.getElementById('bePreviewWrap').style.display = 'flex';
                    document.getElementById('bePreviewWrap').style.setProperty('display','flex','important');
                    document.getElementById('bePreviewImg').src = 'item_icon.php?id='+x.c+'&size=3';
                    document.getElementById('bePreviewName').textContent = x.n;
                    document.getElementById('bePreviewInfo').textContent = 'ID template: '+x.id+' | Loại: '+(typeNames[x.t]||x.t);
                };
                res.appendChild(a);
            });
            res.classList.remove('d-none');
        });
    });
    si.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); } });
    document.addEventListener('click', function(e){
        if(!e.target.closest('#beSearch') && !e.target.closest('#beResults')){
            document.getElementById('beResults').classList.add('d-none');
        }
    });
});

setInterval(loadVpStatus, 3000);
setInterval(loadVpPresence, 5000);
loadVpStatus();
loadVpPresence();
loadVpChats();
</script>
