/*
 * NSO Item Picker (mau NRO): modal chon vat pham co ICON + TEN.
 * Dung: ItemPicker.open({ mode:'giftcode'|'single', target:<id|el>, existing:[...], onPick:fn })
 * - mode 'single': click 1 item -> dat id vao target (input) + goi onPick(item)
 * - mode 'giftcode': chon nhieu item + so luong/nang cap -> xuat JSON mang item vao target (textarea)
 */
var ItemPicker = (function () {
    var _items = null;      // {id: {id,n,ic,t,lv,stk}}
    var _list = null;       // sort by id
    var _loading = false;
    var _reqs = [];

    function iconUrl(ic) { return '/images/1/Small' + ic + '.png'; }

    function load(cb) {
        if (_items) { cb(); return; }
        _reqs.push(cb);
        if (_loading) return;
        _loading = true;
        fetch('/apixuli/items-all', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _items = {}; _list = [];
                (d.items || []).forEach(function (it) { _items[it.id] = it; _list.push(it); });
                _loading = false;
                _reqs.forEach(function (f) { try { f(); } catch (e) {} });
                _reqs = [];
            })
            .catch(function () { _loading = false; _reqs = []; });
    }

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    function ensureStyle() {
        if (document.getElementById('ip-style')) return;
        var css = ''
            + '.ip-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:3000;display:none;}'
            + '.ip-overlay.show{display:flex;align-items:center;justify-content:center;}'
            + '.ip-modal{background:#fff;color:#212529;width:92%;max-width:900px;max-height:88vh;border-radius:8px;display:flex;flex-direction:column;box-shadow:0 8px 30px rgba(0,0,0,.3);}'
            + '.ip-head{padding:12px 16px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;}'
            + '.ip-body{padding:12px 16px;overflow:auto;flex:1;}'
            + '.ip-search{width:100%;padding:8px;border:1px solid #ced4da;border-radius:6px;margin-bottom:10px;}'
            + '.ip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:8px;}'
            + '.ip-cell{border:1px solid #ddd;border-radius:6px;padding:6px;text-align:center;cursor:pointer;background:#fafbfc;position:relative;}'
            + '.ip-cell:hover{border-color:#0d6efd;background:#eef5ff;}'
            + '.ip-cell img{width:48px;height:48px;image-rendering:pixelated;}'
            + '.ip-cell .nm{font-size:11px;line-height:1.1;margin-top:3px;word-break:break-word;}'
            + '.ip-cell .id{font-size:10px;color:#888;}'
            + '.ip-cell .qty-badge{position:absolute;top:2px;right:2px;background:#0d6efd;color:#fff;border-radius:8px;font-size:10px;padding:0 5px;}'
            + '.ip-sel{border-top:1px solid #ddd;padding:10px 16px;max-height:180px;overflow:auto;}'
            + '.ip-sel-row{display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px dashed #eee;}'
            + '.ip-sel-row img{width:32px;height:32px;image-rendering:pixelated;}'
            + '.ip-sel-row input{width:70px;}'
            + '.ip-foot{padding:10px 16px;border-top:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;}'
            + '.ip-empty{color:#888;text-align:center;padding:20px;}';
        var st = document.createElement('style'); st.id = 'ip-style'; st.textContent = css;
        document.head.appendChild(st);
    }

    function ensureModal() {
        if (document.getElementById('ip-overlay')) return document.getElementById('ip-overlay');
        var ov = document.createElement('div');
        ov.id = 'ip-overlay'; ov.className = 'ip-overlay';
        ov.innerHTML = '<div class="ip-modal">'
            + '<div class="ip-head"><b id="ip-title">Chọn vật phẩm</b><button class="btn btn-sm btn-secondary" onclick="ItemPicker.close()">Đóng</button></div>'
            + '<div class="ip-body"><input class="ip-search" id="ip-search" placeholder="Tìm theo tên hoặc ID..."><div class="ip-grid" id="ip-grid"></div></div>'
            + '<div class="ip-sel" id="ip-sel" style="display:none"></div>'
            + '<div class="ip-foot"><span class="text-muted small" id="ip-count"></span><div><button class="btn btn-sm btn-success" id="ip-ok" style="display:none">Lưu danh sách</button></div></div>'
            + '</div>';
        document.body.appendChild(ov);
        return ov;
    }

    var state = { mode: 'single', target: null, onPick: null, sel: {} };

    function renderGrid(filter) {
        var grid = document.getElementById('ip-grid');
        var f = (filter || '').trim().toLowerCase();
        var html = '';
        var shown = 0;
        for (var i = 0; i < _list.length && shown < 300; i++) {
            var it = _list[i];
            if (f && !(it.n.toLowerCase().indexOf(f) >= 0 || String(it.id) === f)) continue;
            shown++;
            var badge = (state.mode === 'giftcode' && state.sel[it.id]) ? '<span class="qty-badge">x' + state.sel[it.id].qty + '</span>' : '';
            html += '<div class="ip-cell" onclick="ItemPicker.pick(' + it.id + ')">' + badge
                + '<img src="' + iconUrl(it.ic) + '" onerror="this.style.visibility=\'hidden\'">'
                + '<div class="nm">' + esc(it.n) + '</div><div class="id">#' + it.id + ' lv' + it.lv + '</div></div>';
        }
        grid.innerHTML = html || '<div class="ip-empty">Không tìm thấy.</div>';
    }

    function renderSel() {
        var sel = document.getElementById('ip-sel');
        var ids = Object.keys(state.sel);
        if (!ids.length) { sel.style.display = 'none'; return; }
        sel.style.display = 'block';
        var html = '';
        ids.forEach(function (id) {
            var it = _items[id], s = state.sel[id];
            html += '<div class="ip-sel-row"><img src="' + iconUrl(it.ic) + '" onerror="this.style.visibility=\'hidden\'">'
                + '<span style="flex:1">' + esc(it.n) + ' <small class="text-muted">#' + id + '</small></span>'
                + '<label class="small">SL</label><input type="number" min="1" max="9999" value="' + s.qty + '" onchange="ItemPicker.setQty(' + id + ',this.value)">'
                + '<label class="small">+Up</label><input type="number" min="0" max="16" value="' + s.upgrade + '" onchange="ItemPicker.setUp(' + id + ',this.value)">'
                + '<button class="btn btn-sm btn-outline-danger" onclick="ItemPicker.rm(' + id + ')">X</button></div>';
        });
        sel.innerHTML = html;
        document.getElementById('ip-count').innerText = 'Đã chọn: ' + ids.length + ' vật phẩm';
    }

    function buildGiftJson() {
        var arr = [];
        var now = Date.now();
        Object.keys(state.sel).forEach(function (id) {
            var s = state.sel[id];
            arr.push({
                id: parseInt(id), quantity: s.qty, expire: -1, isLock: false, new: true,
                yen: 0, sys: 0, upgrade: s.upgrade, options: [], created_at: now, updated_at: now
            });
        });
        return JSON.stringify(arr);
    }

    function setTarget(val) {
        var t = state.target;
        if (!t) return;
        var el = (typeof t === 'string') ? document.getElementById(t) : t;
        if (el) el.value = val;
    }

    return {
        open: function (opts) {
            opts = opts || {};
            state.mode = opts.mode || 'single';
            state.target = opts.target || null;
            state.onPick = opts.onPick || null;
            state.sel = {};
            if (state.mode === 'giftcode' && Array.isArray(opts.existing)) {
                opts.existing.forEach(function (e) {
                    var id = e.id || e.tempid;
                    if (id) state.sel[id] = { qty: e.quantity || e.qty || 1, upgrade: e.upgrade || 0 };
                });
            }
            ensureStyle();
            var ov = ensureModal();
            document.getElementById('ip-ok').style.display = state.mode === 'giftcode' ? '' : 'none';
            document.getElementById('ip-ok').onclick = function () {
                setTarget(buildGiftJson());
                if (state.onDone) state.onDone(state.sel);
                ItemPicker.close();
            };
            load(function () {
                document.getElementById('ip-search').value = '';
                renderGrid('');
                renderSel();
                ov.classList.add('show');
            });
            document.getElementById('ip-search').oninput = function () { renderGrid(this.value); };
        },
        close: function () { var ov = document.getElementById('ip-overlay'); if (ov) ov.classList.remove('show'); },
        pick: function (id) {
            if (state.mode === 'giftcode') {
                if (state.sel[id]) state.sel[id].qty++; else state.sel[id] = { qty: 1, upgrade: 0 };
                renderSel();
                renderGrid(document.getElementById('ip-search').value);
            } else {
                setTarget(id);
                if (state.onPick) state.onPick(_items[id]);
                ItemPicker.close();
            }
        },
        rm: function (id) { delete state.sel[id]; renderSel(); renderGrid(document.getElementById('ip-search').value); },
        setQty: function (id, v) { if (state.sel[id]) { state.sel[id].qty = Math.max(1, parseInt(v) || 1); renderGrid(document.getElementById('ip-search').value); } },
        setUp: function (id, v) { if (state.sel[id]) state.sel[id].upgrade = Math.max(0, Math.min(16, parseInt(v) || 0)); },
        // Hien thi ten + icon cho 1 item id vao element (dung cho bang hien thi)
        label: function (id) { var it = _items && _items[id]; return it ? (esc(it.n) + ' <small class="text-muted">#' + id + '</small>') : ('#' + id); },
        img: function (id, size) { var it = _items && _items[id]; if (!it) return ''; size = size || 40; return '<img src="' + iconUrl(it.ic) + '" width="' + size + '" height="' + size + '" style="image-rendering:pixelated" onerror="this.style.display=\'none\'">'; },
        ready: function (cb) { load(cb); }
    };
})();
