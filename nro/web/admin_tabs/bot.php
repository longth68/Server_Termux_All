<?php
/* Tab: Quan ly BOT AI (Virtual Player) - include boi admin.php
 * Viet dung theo API cua server HASHIRAMA:
 *  vp_status -> {enabled, population, gold_rate, chat_rate, map_change_rate,
 *                gift_rate, afk_rate, player_protection, bots:[{id,name,power,gold,state,presence,map,personalities}]}
 *  vp_detail -> {success, detail:{..., point:{power,hpg,mpg,dameg,defg}, inventory:{gold},
 *                items_body:[{slot,temp_id,name,icon,quantity}]}}
 */
$BOT_ICONS = [];
$q = _query("SELECT id, icon_id FROM item_template");
while($r = mysqli_fetch_assoc($q)) $BOT_ICONS[(int)$r['id']] = (int)$r['icon_id'];
?>
<h3 class="mb-4">Quản Lý BOT AI <small class="text-muted fs-6">(Virtual Player của HASHIRAMA)</small></h3>
<div id="botAlert" class="alert alert-success d-none" role="alert"></div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3 text-center border-primary">
            <div class="fs-4 fw-bold text-primary" id="bpPop">0</div>
            <div class="text-muted small fw-bold"><i class="fa-solid fa-users"></i> Dân số bot hiện tại</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center border-success">
            <div class="fs-4 fw-bold text-success" id="bpEnabled">-</div>
            <div class="text-muted small fw-bold"><i class="fa-solid fa-power-off"></i> Trạng thái hệ thống</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <label class="small fw-bold mb-1">Đặt dân số mục tiêu (0-200)</label>
            <div class="d-flex gap-2">
                <input type="number" id="bpPopTarget" class="form-control" min="0" max="200" value="30">
                <button class="btn btn-primary" onclick="vpNum('vp_set_population','bpPopTarget')">Áp dụng</button>
            </div>
            <label class="small fw-bold mt-2 mb-1">Tạo thêm bot (1-50)</label>
            <div class="d-flex gap-2">
                <input type="number" id="bpSpawn" class="form-control" min="1" max="50" value="5">
                <button class="btn btn-success" onclick="vpNum('vp_spawn','bpSpawn')">Tạo</button>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Bật / Tắt</h6>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-success" onclick="callApi('vp_set_enabled&val=1'); setTimeout(loadBot,500)">BẬT BOT AI</button>
        <button class="btn btn-danger" onclick="callApi('vp_set_enabled&val=0'); setTimeout(loadBot,500)">TẮT BOT AI</button>
        <button class="btn btn-outline-secondary" onclick="callApi('vp_save')">Lưu trạng thái bot</button>
        <button class="btn btn-outline-warning" onclick="if(confirm('Xóa TẤT CẢ bot AI?')) callApi('vp_remove_all'); setTimeout(loadBot,800)">Xóa tất cả</button>
        <button class="btn btn-outline-primary" onclick="loadBot()"><i class="fa-solid fa-rotate"></i> Làm mới</button>
    </div>
</div>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Cấu hình hành vi <small>(lưu ngay vào virtualplayer_config.txt)</small></h6>
    <div class="row g-2" id="cfgGrid"></div>
</div>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Câu chat ngẫu nhiên của bot</h6>
    <div class="d-flex gap-2 mb-2">
        <input type="text" id="chatMsg" class="form-control" placeholder="Nhập câu chat...">
        <button class="btn btn-primary" onclick="chatAdd()">Thêm</button>
    </div>
    <div id="chatList" class="d-flex flex-wrap gap-1"></div>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách bot <span class="badge bg-primary" id="botCount">0</span></h6>
    <table class="table table-bordered table-striped table-hover table-sm">
        <thead class="table-dark">
            <tr><th>ID</th><th>Tên</th><th>Sức mạnh</th><th>Vàng</th><th>Hành động</th><th>Khu map</th><th>Hiện diện</th><th>Tính cách</th><th></th></tr>
        </thead>
        <tbody id="botTableBody"></tbody>
    </table>
</div>

<!-- Modal chi tiet bot -->
<div class="modal fade" id="botDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bdTitle">Chi tiết bot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="bdBody"><p class="text-center text-muted">Đang tải...</p></div>
    </div>
  </div>
</div>

<script>
let botData = null;
const CFG_KEYS = [
    ['gold_rate','Tỉ lệ farm vàng'],
    ['chat_rate','Tần suất chat'],
    ['map_change_rate','Tỉ lệ đổi map'],
    ['gift_rate','Tỉ lệ tặng quà'],
    ['afk_rate','Tỉ lệ AFK'],
    ['player_protection','Bảo vệ người chơi (px)'],
];

function botAlert(msg, ok){
    let a = document.getElementById('botAlert');
    a.classList.remove('d-none','alert-danger','alert-success');
    a.classList.add(ok === false ? 'alert-danger' : 'alert-success');
    a.innerText = msg;
    setTimeout(()=>a.classList.add('d-none'), 4000);
}

function fmt(n){ return n===undefined||n===null ? '—' : Number(n).toLocaleString('vi-VN'); }

function loadBot(){
    fetch('?ajax=proxy&action=vp_status').then(r=>r.json()).then(d=>{
        botData = d;
        document.getElementById('bpPop').innerText = d.bots ? d.bots.length : 0;
        document.getElementById('bpEnabled').innerHTML = d.enabled
            ? '<span class="text-success">ĐANG BẬT</span>'
            : '<span class="text-danger">ĐANG TẮT</span>';
        document.getElementById('bpPopTarget').value = d.population ?? 30;
        // cau hinh
        let g = document.getElementById('cfgGrid'); g.innerHTML='';
        CFG_KEYS.forEach(([k,label])=>{
            let col = document.createElement('div');
            col.className = 'col-md-4 col-sm-6';
            col.innerHTML = '<label class="small fw-bold mb-1">'+label+' <code>'+k+'</code></label>'
                + '<div class="d-flex gap-1">'
                + '<input type="text" class="form-control form-control-sm" id="cfg_'+k+'" value="'+(d[k]??'')+'">'
                + '<button class="btn btn-sm btn-outline-primary" onclick="cfgSave(\''+k+'\')">Lưu</button>'
                + '</div>';
            g.appendChild(col);
        });
        // danh sach bot
        let tb = document.getElementById('botTableBody'); tb.innerHTML='';
        let bots = d.bots || [];
        document.getElementById('botCount').innerText = bots.length;
        bots.forEach(b=>{
            let tr = document.createElement('tr');
            tr.innerHTML = '<td class="small text-muted">'+b.id+'</td>'
                + '<td><strong>'+b.name+'</strong></td>'
                + '<td>'+fmt(b.power)+'</td>'
                + '<td>'+fmt(b.gold)+'</td>'
                + '<td><span class="badge bg-info text-dark">'+(b.state||'?')+'</span>'+(b.goal?'<br><small class="text-muted">'+b.goal+'</small>':'')+'</td>'
                + '<td>'+(b.map||'?')+'</td>'
                + '<td><span class="badge '+(b.presence==='online'?'bg-success':'bg-secondary')+'">'+(b.presence||'?')+'</span></td>'
                + '<td class="small text-muted">'+(b.personalities||'')+'</td>'
                + '<td class="text-nowrap">'
                + '<button class="btn btn-sm btn-outline-primary me-1" onclick="botDetail('+b.id+')">Chi tiết</button>'
                + '<button class="btn btn-sm btn-outline-warning me-1" title="Tự trang bị lại đồ cho bot" onclick="fetch(\'?ajax=proxy&action=vp_regear&id='+b.id+'\').then(r=>r.json()).then(d=>{botAlert(d.msg||d.message||\'Xong\',!(d.status===\'error\')); botDetail('+b.id+');})">Regear</button>'
                + '<button class="btn btn-sm btn-outline-danger" onclick="if(confirm(\'Xóa bot '+b.name+'?\')){fetch(\'?ajax=proxy&action=vp_remove&id='+b.id+'\').then(()=>loadBot());}">Xóa</button>'
                + '</td>';
            tb.appendChild(tr);
        });
        if (bots.length === 0) tb.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Chưa có bot nào — bật hệ thống hoặc tạo bot bên trên</td></tr>';
        loadChat();
    }).catch(e=>botAlert('Lỗi tải vp_status: '+(e.message||e), false));
}

function vpNum(action, inputId){
    let v = document.getElementById(inputId).value;
    if (v === '' || isNaN(v)) { alert('Nhập số hợp lệ!'); return; }
    fetch('?ajax=proxy&action='+action+'&val='+encodeURIComponent(v)).then(r=>r.json()).then(d=>{
        botAlert(d.msg || d.message || d.status || 'Xong', !(d.status==='error'));
        setTimeout(loadBot, 600);
    }).catch(e=>botAlert('Lỗi: '+e.message, false));
}

function cfgSave(key){
    let v = document.getElementById('cfg_'+key).value;
    fetch('?ajax=proxy&action=vp_config_set&cfg='+encodeURIComponent(key)+'&val='+encodeURIComponent(v)).then(r=>r.json()).then(d=>{
        botAlert((d.msg || d.message || d.status || '') + '', !(d.status==='error'));
        setTimeout(loadBot, 400);
    }).catch(e=>botAlert('Lỗi: '+e.message, false));
}

function loadChat(){
    fetch('?ajax=proxy&action=vp_chat_list').then(r=>r.json()).then(d=>{
        let box = document.getElementById('chatList'); box.innerHTML='';
        (d.lines || []).forEach((line, idx)=>{
            let sp = document.createElement('span');
            sp.className = 'badge bg-light text-dark border';
            sp.style.fontSize = '12px';
            sp.innerHTML = line + ' <a href="#" onclick="chatDel('+idx+');return false;" class="text-danger ms-1">×</a>';
            box.appendChild(sp);
        });
        if ((d.lines || []).length === 0) box.innerHTML = '<span class="text-muted small">Chưa có câu chat nào</span>';
    }).catch(()=>{});
}
function chatAdd(){
    let m = document.getElementById('chatMsg').value.trim();
    if (!m) return;
    fetch('?ajax=proxy&action=vp_chat_add&msg='+encodeURIComponent(m)).then(r=>r.json()).then(d=>{
        document.getElementById('chatMsg').value='';
        loadChat();
    });
}
function chatDel(idx){
    fetch('?ajax=proxy&action=vp_chat_del&val='+idx).then(()=>loadChat());
}

function botDetail(id){
    let modal = new bootstrap.Modal(document.getElementById('botDetailModal'));
    modal.show();
    document.getElementById('bdTitle').innerText = 'Bot #' + id;
    document.getElementById('bdBody').innerHTML = '<p class="text-center text-muted">Đang tải...</p>';
    fetch('?ajax=proxy&action=vp_detail&id='+id).then(r=>r.json()).then(d=>{
        if (!d.success || !d.detail) {
            document.getElementById('bdBody').innerHTML = '<div class="alert alert-warning m-3">'+(d.msg||d.message||'Không tìm thấy bot')+'</div>';
            return;
        }
        let b = d.detail;
        let pt = b.point || {};
        let html = ''
            + '<div class="row g-2 mb-3">'
            +   '<div class="col-md-8 small">'
            +   '<div><b>Tên:</b> '+b.name+' · <b>Giới tính:</b> '+b.gender+'</div>'
            +   '<div><b>Hành động:</b> '+(b.state||'?')+' · <b>Hiện diện:</b> '+(b.presence||'?')+'</div>'
            +   '<div><b>Mục tiêu dài hạn:</b> '+(b.goal||'?')+' · <b>ngắn hạn:</b> '+(b.short_goal||'?')+'</div>'
            +   '<div><b>Tính cách:</b> '+(b.personalities||'—')+'</div>'
            +   '<div><b>Sức mạnh:</b> '+fmt(pt.power)+' · <b>HP:</b> '+fmt(pt.hpg)+' · <b>MP:</b> '+fmt(pt.mpg)+' · <b>Dame:</b> '+fmt(pt.dameg)+' · <b>Def:</b> '+fmt(pt.defg)+'</div>'
            +   '<div><b>Vàng:</b> '+fmt(b.inventory ? b.inventory.gold : 0)+'</div>'
            +   '</div>'
            +   '<div class="col-md-4"><div class="border rounded p-2 bg-light">'
            +     '<label class="small fw-bold">Sửa nhanh</label>'
            +     '<input type="text" class="form-control form-control-sm mb-1" id="edName" placeholder="Tên mới">'
            +     '<input type="number" class="form-control form-control-sm mb-1" id="edPower" placeholder="Sức mạnh">'
            +     '<input type="number" class="form-control form-control-sm mb-1" id="edGold" placeholder="Vàng">'
            +     '<button class="btn btn-sm btn-primary w-100" onclick="botEdit('+id+')">Lưu thông tin</button>'
            +   '</div></div>'
            + '</div>'
            + '<h6 class="fw-bold border-bottom pb-1 mb-2">Trang bị trên người</h6>'
            + '<div class="d-flex flex-wrap gap-2 mb-3" id="bdEquip"></div>'
            + '<div class="border-top pt-2">'
            + '<label class="small fw-bold">Thêm vật phẩm vào bot</label>'
            + '<div class="d-flex gap-1 align-items-center">'
            +   '<select id="addType" class="form-select form-select-sm" style="max-width:110px"><option value="bag">Túi đồ</option><option value="body">Trang bị</option></select>'
            +   '<input type="number" id="addTemp" class="form-control form-control-sm" placeholder="Item ID" style="max-width:120px">'
            +   '<img id="addPrev" width="36" height="36" class="border rounded" style="image-rendering:pixelated;display:none" onerror="this.style.display=\'none\'" onload="this.style.display=\'inline-block\'">'
            +   '<input type="number" id="addQty" class="form-control form-control-sm" placeholder="SL" value="1" style="max-width:80px">'
            +   '<button class="btn btn-sm btn-success" onclick="botAddItem('+id+')">Thêm</button>'
            + '</div>'
            + '<small class="text-muted">Server HASHIRAMA chỉ trả về danh sách TRANG BỊ qua vp_detail — nội dung túi đồ không được expose, dùng nút Thêm/Xóa để thao tác.</small>'
            + '</div>';
        let el = document.getElementById('bdBody');
        el.innerHTML = html;
        let eq = document.getElementById('bdEquip');
        const SLOT_NAMES = ['Áo','Quần','Dây chuyền','Găng tay','Giày','Nhẫn'];
        let items = b.items_body || [];
        let hasEquip = items.some(it => it.temp_id && it.temp_id > 0);
        if (!hasEquip) {
            eq.innerHTML = '<div class="w-100 alert alert-info py-2 mb-0 small">'
                + 'Bot chưa có trang bị (đây là trạng thái thật từ server — bot mới tạo thường trống). '
                + 'Bấm nút <b>Regear</b> ở danh sách để server tự trang bị lại, hoặc thêm thủ công bên dưới với loại "Trang bị".'
                + '</div>';
        }
        items.forEach(it=>{
            if (!(it.temp_id > 0)) return; // bo qua slot trong
            let d = document.createElement('div');
            d.className = 'text-center border rounded p-1';
            d.style.width = '84px';
            let iconHtml = (it.icon >= 0)
                ? '<img src="item_icon.php?id='+it.icon+'&size=3" width="48" height="48" style="image-rendering:pixelated;">'
                : '<div style="height:48px;line-height:48px;" class="text-muted">—</div>';
            d.innerHTML = iconHtml
                + '<div class="small fw-bold text-truncate" title="'+(it.name||'')+'" style="font-size:10px;">'+(it.name||'Trống')+'</div>'
                + '<div style="font-size:9px;" class="text-muted">'+(SLOT_NAMES[it.slot]||('#'+it.slot))+(it.quantity>1?(' x'+it.quantity):'')+'</div>'
                + '<button class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:9px;" onclick="botDelItem('+id+',\'body\','+it.slot+')">Xóa</button>';
            eq.appendChild(d);
        });
        let addTemp = document.getElementById('addTemp');
        const BOT_ICONS = <?= json_encode($BOT_ICONS) ?>;
        addTemp.addEventListener('input', ()=>{
            let ic = BOT_ICONS[parseInt(addTemp.value)];
            let img = document.getElementById('addPrev');
            if (ic !== undefined && parseInt(addTemp.value) > 0) { img.src='item_icon.php?id='+ic+'&size=3'; } else img.style.display='none';
        });
    }).catch(e=>{
        document.getElementById('bdBody').innerHTML = '<div class="alert alert-danger m-3">Lỗi: '+(e.message||e)+'</div>';
    });
}

function botEdit(id){
    let qs = 'action=vp_edit_info&id='+id;
    let n = document.getElementById('edName').value, p = document.getElementById('edPower').value, g = document.getElementById('edGold').value;
    if (n) qs += '&name2='+encodeURIComponent(n);
    if (p) qs += '&power='+encodeURIComponent(p);
    if (g) qs += '&gold='+encodeURIComponent(g);
    fetch('?ajax=proxy&'+qs).then(r=>r.json()).then(d=>{
        botAlert(d.msg || d.message || 'Xong', !(d.status==='error'));
        botDetail(id); loadBot();
    });
}
function botAddItem(id){
    let t = document.getElementById('addType').value;
    let tid = parseInt(document.getElementById('addTemp').value)||0;
    let q = parseInt(document.getElementById('addQty').value)||1;
    if (tid <= 0) { alert('Nhập Item ID!'); return; }
    fetch('?ajax=proxy&action=vp_item_add&id='+id+'&type='+t+'&tempid='+tid+'&qty='+q).then(r=>r.json()).then(d=>{
        botAlert(d.msg || d.message || 'Xong', !(d.status==='error'));
        botDetail(id);
    });
}
function botDelItem(id, type, slot){
    fetch('?ajax=proxy&action=vp_item_del&id='+id+'&type='+type+'&slot='+slot).then(r=>r.json()).then(d=>{
        botAlert(d.msg || d.message || 'Xong', !(d.status==='error'));
        botDetail(id);
    });
}

loadBot();
setInterval(()=>{ if (!document.hidden) loadBot(); }, 10000);
</script>
