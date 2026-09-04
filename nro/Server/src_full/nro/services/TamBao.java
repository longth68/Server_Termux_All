package nro.services;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import jbcd.ConnectDB;
import models.Item.Item;
import models.Item.ItemOption;
import models.Item.ItemService;
import network.io.Message;
import nro.inventory.InventoryService;
import nro.player.Player;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.JSONValue;
import Utils.Logger;
import Utils.Util;

/**
 * Tam Bao - Vong Quay Tam Bao (port hashirama).
 * - Moi "pool" gan voi mot key_item_id (loai khoa).
 * - Dung key nao thi chi quay ra vat pham co key do.
 * - Neu pool thieu slot, se bu bang fallback IDs.
 * - Ghi lich su thang vao history_tambao.
 */
public class TamBao {

    // ====== cau hinh ======
    private static final int REQUIRED_SLOTS = 14;
    private static final int[] FALLBACK_IDS = {220, 221, 222, 223, 224, 15, 15, 17, 18, 19, 20, 381, 382, 383, 384, 385};
    private static final int DEFAULT_VIP_FLAG = 0;
    private static final int DEFAULT_FALLBACK_KEY = 1778;
    public static final List<TamBao_Item> MOC_TAMBAO = new ArrayList<>();
    private final Map<Integer, List<Integer>> POOL_TILE = new HashMap<>();

    private final Map<Integer, List<Item>> POOLS = new HashMap<>();
    private int DEFAULT_KEY_ITEM_ID = -1;

    private final Map<Integer, List<Integer>> POOL_VIP_FLAGS = new HashMap<>();

    private static final byte START = 0;
    private static TamBao instance;

    public static TamBao gI() {
        if (instance == null) instance = new TamBao();
        return instance;
    }

    private TamBao() {
    }

    public void loadItem_TamBao() {
        POOLS.clear();
        POOL_VIP_FLAGS.clear();
        POOL_TILE.clear();
        DEFAULT_KEY_ITEM_ID = -1;

        final String sql = "SELECT id, key_item_id, item_id, quantity, item_options, tile_trung_thuong, des, "
                + "start_at, end_at, enabled "
                + "FROM tambao_items "
                + "WHERE enabled = 1 "
                + "AND (start_at IS NULL OR start_at <= NOW()) "
                + "AND (end_at IS NULL OR end_at >= NOW()) "
                + "ORDER BY id ASC";

        int totalRows = 0;
        try (Connection con = ConnectDB.getConnection(); PreparedStatement ps = con.prepareStatement(sql); ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                totalRows++;
                int keyId = rs.getInt("key_item_id");
                POOLS.computeIfAbsent(keyId, k -> new ArrayList<>());
                POOL_VIP_FLAGS.computeIfAbsent(keyId, k -> new ArrayList<>());
                POOL_TILE.computeIfAbsent(keyId, k -> new ArrayList<>());

                Integer singleId = getNullableInt(rs, "item_id");
                Integer singleQty = getNullableInt(rs, "quantity");
                String optCompact = rs.getString("item_options");
                int tilePercent = rs.getInt("tile_trung_thuong"); // 1 == 1%

                if (singleId != null && singleQty != null) {
                    Item it = ItemService.gI().createNewItem(singleId.shortValue(), singleQty);
                    if (optCompact != null && !optCompact.isEmpty()) {
                        addOptionsFromCompact(optCompact, it);
                    }

                    POOLS.get(keyId).add(it);
                    POOL_VIP_FLAGS.get(keyId).add(DEFAULT_VIP_FLAG);
                    POOL_TILE.get(keyId).add(Math.max(0, Math.min(100, tilePercent))); // clamp 0..100
                }
            }

            // Chi chon key mac dinh, KHONG bu fallback o day
            if (!POOLS.isEmpty() && DEFAULT_KEY_ITEM_ID == -1) {
                DEFAULT_KEY_ITEM_ID = POOLS.keySet().iterator().next();
            }
            Logger.success("Load tambao_items: rows=" + totalRows + ", pools=" + POOLS.size());
        } catch (Exception e) {
            Logger.logException(TamBao.class, e);
        }
    }

    private void padWithFallback(int keyId, List<Item> list) {
        List<Integer> vipFlags = POOL_VIP_FLAGS.get(keyId);
        List<Integer> tiles = POOL_TILE.get(keyId);
        if (vipFlags == null || tiles == null) {
            return;
        }

        int need = REQUIRED_SLOTS - list.size();
        int idx = 0;
        while (need-- > 0) {
            int itemId = FALLBACK_IDS[idx % FALLBACK_IDS.length];
            Item fb = ItemService.gI().createNewItem((short) itemId, 1);
            list.add(fb);
            vipFlags.add(DEFAULT_VIP_FLAG);
            tiles.add(0); // fallback khong co tile rieng
            idx++;
        }
    }

    private static class SpinPool {

        final int keyId;
        final List<Item> items;      // dung 14 item
        final List<Integer> tiles;   // cung thu tu voi items

        SpinPool(int keyId, List<Item> items, List<Integer> tiles) {
            this.keyId = keyId;
            this.items = items;
            this.tiles = tiles;
        }
    }
    private final Map<Long, SpinPool> LAST_SPIN_VIEW = new ConcurrentHashMap<>();

    private SpinPool ensureSpinPool14(int keyId) {
        List<Item> sqlItems = POOLS.getOrDefault(keyId, Collections.emptyList());
        List<Integer> sqlTiles = POOL_TILE.getOrDefault(keyId, Collections.emptyList());

        // 1) Chon tu SQL: neu >14 thi random 14; neu <=14 lay het
        List<Item> chosenItems = new ArrayList<>(REQUIRED_SLOTS);
        List<Integer> chosenTiles = new ArrayList<>(REQUIRED_SLOTS);

        int sqlCount = sqlItems.size();
        if (sqlCount > 0) {
            List<Integer> idx = new ArrayList<>();
            for (int i = 0; i < sqlCount; i++) {
                idx.add(i);
            }
            Collections.shuffle(idx);

            int take = Math.min(REQUIRED_SLOTS, sqlCount);
            for (int k = 0; k < take; k++) {
                int i = idx.get(k);
                chosenItems.add(sqlItems.get(i));
                Integer t = (i < sqlTiles.size() ? sqlTiles.get(i) : 0);
                chosenTiles.add(sanitizePercent(t));
            }
        }

        // 2) Neu thieu slot thi bu fallback (tile = 0)
        int missing = REQUIRED_SLOTS - chosenItems.size();
        if (missing > 0) {
            List<Integer> fb = new ArrayList<>();
            for (int id : FALLBACK_IDS) {
                fb.add(id);
            }
            Collections.shuffle(fb);
            for (int k = 0; k < missing; k++) {
                int itemId = fb.get(k % fb.size());
                Item fbItem = ItemService.gI().createNewItem((short) itemId, 1);
                chosenItems.add(fbItem);
                chosenTiles.add(0);
            }
        }

        // 3) Tron thu tu cuoi cung de moi lan load vong quay la 1 bo cuc ngau nhien
        List<Integer> order = new ArrayList<>();
        for (int i = 0; i < REQUIRED_SLOTS; i++) {
            order.add(i);
        }
        Collections.shuffle(order);

        List<Item> shuffledItems = new ArrayList<>(REQUIRED_SLOTS);
        List<Integer> shuffledTiles = new ArrayList<>(REQUIRED_SLOTS);
        for (int i : order) {
            shuffledItems.add(chosenItems.get(i));
            shuffledTiles.add(chosenTiles.get(i));
        }

        return new SpinPool(keyId, shuffledItems, shuffledTiles);
    }

    private int sanitizePercent(Integer t) {
        return (t == null) ? 0 : Math.max(0, Math.min(100, t));
    }

    private int pickIndexByPercent(List<Integer> tiles) {
        int n = tiles.size();
        if (n == 0) {
            return 0;
        }

        double[] weights = new double[n];
        double sumPos = 0.0;
        int zeros = 0;

        for (int i = 0; i < n; i++) {
            int t = (tiles.get(i) == null) ? 0 : tiles.get(i);
            if (t > 0) {
                weights[i] = t; // % truc tiep
                sumPos += t;
            } else {
                weights[i] = 0.0;
                zeros++;
            }
        }

        if (sumPos > 0) {
            if (sumPos > 100.0) {
                // chuan hoa ve 100%
                for (int i = 0; i < n; i++) {
                    if (weights[i] > 0) {
                        weights[i] = (weights[i] / sumPos) * 100.0;
                    }
                }
            } else if (sumPos < 100.0) {
                // chia phan tram du cho cac item tile=0 (thuong la fallback)
                if (zeros > 0) {
                    double bonus = (100.0 - sumPos) / zeros;
                    for (int i = 0; i < n; i++) {
                        if (weights[i] == 0.0) {
                            weights[i] = bonus;
                        }
                    }
                } else {
                    // khong con item 0% de nhan du -> scale len 100
                    for (int i = 0; i < n; i++) {
                        weights[i] = (weights[i] / sumPos) * 100.0;
                    }
                }
            }
        } else {
            // tat ca = 0 -> chia deu 100%
            double each = 100.0 / n;
            Arrays.fill(weights, each);
        }

        // quay theo phan phoi tich luy
        double total = 0.0;
        for (double w : weights) {
            total += w;
        }

        double r = Math.random() * total;
        double acc = 0.0;
        for (int i = 0; i < n; i++) {
            acc += weights[i];
            if (r < acc) {
                return i;
            }
        }
        return n - 1;
    }

    // =========================================================
    // API QUAY
    // =========================================================
    // Luon dung key mac dinh neu key hanh hanh chua thiet lap
    public void QuayTamBao(Player pl, int solan) {
        int keyId = (DEFAULT_KEY_ITEM_ID != -1) ? DEFAULT_KEY_ITEM_ID : DEFAULT_FALLBACK_KEY;
        QuayTamBaoWithKey(pl, solan, keyId);
    }

    public void QuayTamBaoWithKey(Player pl, int solan, int keyItemId) {
        int effectiveKeyId = keyItemId;
        List<Item> pool = POOLS.get(effectiveKeyId);

        if (pool == null || pool.isEmpty()) {
            effectiveKeyId = DEFAULT_FALLBACK_KEY;
            pool = POOLS.get(effectiveKeyId);
            if (pool == null || pool.isEmpty()) {
                pool = buildFallbackPool(); // khong dung tiles o day
            }
        }

        // kiem tra chia
        Item key = InventoryService.gI().findItemBag(pl, effectiveKeyId);
        if (key == null || key.quantity < solan) {
            if (effectiveKeyId != DEFAULT_FALLBACK_KEY) {
                Item fbKey = InventoryService.gI().findItemBag(pl, DEFAULT_FALLBACK_KEY);
                if (fbKey != null && fbKey.quantity >= solan) {
                    effectiveKeyId = DEFAULT_FALLBACK_KEY;
                    key = fbKey;
                }
            }
        }
        if (key == null || key.quantity < solan) {
            String kname = ItemService.gI().getTemplate(effectiveKeyId).name;
            Service.gI().sendThongBao(pl, "|7|Khong du " + kname);
            return;
        }

        // hanh trang
        if (InventoryService.gI().getCountEmptyBag(pl) < solan) {
            Service.gI().sendThongBao(pl, "Hanh trang can it nhat " + solan + " cho trong");
            return;
        }

        // dung pool dung voi UI da gui (neu co), neu khac key hoac chua co -> tao moi
        SpinPool sp = LAST_SPIN_VIEW.get(pl.id);
        if (sp == null || sp.keyId != effectiveKeyId || sp.items.size() != REQUIRED_SLOTS) {
            sp = ensureSpinPool14(effectiveKeyId);
            LAST_SPIN_VIEW.put(pl.id, sp);
        }

        List<Item> spinItems = sp.items;
        List<Integer> spinTiles = sp.tiles;

        pl.list_id_nhan = new int[REQUIRED_SLOTS];
        String text = "|7|Nhan duoc\n";

        for (int i = 0; i < solan; i++) {
            int k = pickIndexByPercent(spinTiles);
            Item base = spinItems.get(k);

            int qty = base.quantity;
            if (base.template != null && base.template.type == 9) {
                qty = Util.nextInt(1000000, 20000000);
            }

            Item prize = cloneWithQuantity(base, qty);

            pl.list_id_nhan[k] = 1;
            InventoryService.gI().addItemBag(pl, prize);

            if (prize.template.type == 9) {
                text += "|5|x" + Util.format(prize.quantity) + " " + prize.template.name + "\n";
            } else if (prize.template.id == 457) {
                text += "|8|x" + Util.format(prize.quantity) + " " + prize.template.name + "\n";
            } else {
                text += "|6|x" + Util.format(prize.quantity) + " " + prize.template.name + "\n";
            }
            insertHistory(pl, prize);
        }

        pl.diem_quay += solan;
        InventoryService.gI().subQuantityItemsBag(pl, key, solan);
        Service.gI().sendThongBaoFromAdmin(pl, text);
        InventoryService.gI().sendItemBag(pl);
        Send_MocTamBao(pl);
        Send_QuayThuong(pl);
    }

    // helper tao pool fallback 14 item thuong
    private List<Item> buildFallbackPool() {
        List<Item> list = new ArrayList<>(REQUIRED_SLOTS);
        for (int i = 0; i < REQUIRED_SLOTS; i++) {
            int itemId = FALLBACK_IDS[i % FALLBACK_IDS.length];
            list.add(ItemService.gI().createNewItem((short) itemId, 1));
        }
        return list;
    }
    // =========================================================
    // SEND DATA CHO CLIENT (wire-protocol giu nguyen nhu nguon)
    // =========================================================
    public void Send_QuayThuong(Player pl) {
        Message msg = null;
        try {
            msg = new Message(106);
            msg.writer().writeByte(2);
            msg.writer().writeByte(pl.list_id_nhan.length);
            for (int v : pl.list_id_nhan) {
                msg.writer().writeInt(v);
            }
            pl.sendMessage(msg);
        } catch (Exception ignored) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    /**
     * Gui danh sach vat pham trong pool theo key mac dinh.
     * Neu ban co UI cho nhieu key khac nhau, hay them overload `Send_TamBao(pl, keyItemId)`.
     */
    public void Send_TamBao(Player pl) {
        int keyId = (DEFAULT_KEY_ITEM_ID != -1) ? DEFAULT_KEY_ITEM_ID : DEFAULT_FALLBACK_KEY;
        SpinPool sp = ensureSpinPool14(keyId);
        LAST_SPIN_VIEW.put(pl.id, sp); // cache cho lan quay sap toi
        Message msg = null;
        try {
            msg = new Message(106);
            msg.writer().writeByte(0);
            msg.writer().writeByte(START);
            msg.writer().writeShort((short) keyId);
            msg.writer().writeShort(ItemService.gI().getTemplate(keyId).iconID);
            msg.writer().writeByte(REQUIRED_SLOTS);

            for (int i = 0; i < REQUIRED_SLOTS; i++) {
                Item it = sp.items.get(i);
                msg.writer().writeByte(0);                 // active_vip flag
                msg.writer().writeShort(it.template.id);
                msg.writer().writeInt(it.quantity);
                msg.writer().writeUTF(it.getInfo());
                msg.writer().writeUTF(it.getContent());
                List<ItemOption> opts = it.itemOptions;
                msg.writer().writeByte(opts.size());
                for (ItemOption o : opts) {
                    msg.writer().writeByte(o.optionTemplate.id);
                    msg.writer().writeInt(o.param);
                }
            }
            pl.sendMessage(msg);
        } catch (Exception ignored) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public void Send_MocTamBao(Player pl) {
        TamBao.gI().Check_active(pl);
        Message msg = null;
        try {
            msg = new Message(106);
            msg.writer().writeByte(1);
            msg.writer().writeInt(pl.diem_quay);
            msg.writer().writeByte(MOC_TAMBAO.size());
            for (int h = 0; h < MOC_TAMBAO.size(); h++) {
                msg.writer().writeInt(pl.checkNhan_TamBao[h]);
                TamBao_Item item = MOC_TAMBAO.get(h);
                msg.writer().writeShort(item.template.id);
                msg.writer().writeInt(item.quantity);
                msg.writer().writeUTF(item.getInfo());
                msg.writer().writeUTF(item.getContent());
                List<ItemOption> itemOptions = item.itemOptions;
                msg.writer().writeInt(item.id_moc);
                msg.writer().writeInt(item.max_value);
                msg.writer().writeByte(itemOptions.size());
                for (ItemOption o : itemOptions) {
                    msg.writer().writeByte(o.optionTemplate.id);
                    msg.writer().writeInt(o.param);
                }
            }
            pl.sendMessage(msg);
        } catch (Exception ignored) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public void Active_TamBao(Player pl, int id) {
        TamBao_Item item = MOC_TAMBAO.get(id);
        if (pl.checkNhan_TamBao[id] == 0) {
            if (pl.diem_quay >= item.max_value) {
                if (InventoryService.gI().getCountEmptyBag(pl) > 0) {
                    pl.listNhan_TamBao.add(id);
                    InventoryService.gI().addItemBag(pl, item);
                    Service.gI().sendThongBao(pl, "|2|Da nhan x" + item.quantity + " " + item.template.name + "\n");
                    InventoryService.gI().sendItemBag(pl);
                    Send_MocTamBao(pl);
                } else {
                    Service.gI().sendThongBao(pl, "|7|Hanh trang khong du cho trong");
                }
            } else {
                Service.gI().sendThongBao(pl, "|7|Khong du dieu kien Nhan thuong");
            }
        } else {
            Service.gI().sendThongBao(pl, "|7|Ban da nhan roi ma !!!");
        }
    }

    public void Check_active(Player pl) {
        try {
            pl.checkNhan_TamBao = new int[MOC_TAMBAO.size()];
            for (int a = 0; a < MOC_TAMBAO.size(); a++) {
                TamBao_Item moc = MOC_TAMBAO.get(a);
                for (int t = 0; t < pl.listNhan_TamBao.size(); t++) {
                    if (pl.listNhan_TamBao.get(t).equals(moc.id_moc)) {
                        pl.checkNhan_TamBao[a] = 1;
                    }
                }
            }
        } catch (Exception ignored) {
        }
    }
    // =========================================================
    // LOAD MOC TAM BAO (doc giong Phuc Loi) - parse options chuan
    // =========================================================
    public void load_mocTamBao() {
        MOC_TAMBAO.clear();
        final String sql = "SELECT * FROM moc_vong_quay";
        try (Connection con = ConnectDB.getConnection();
             PreparedStatement ps = con.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {

            JSONValue jsonValue = new JSONValue();
            while (rs.next()) {
                TamBao_Item tambao = new TamBao_Item();
                int id_moc = rs.getInt("id");
                int templateId = rs.getInt("item_id");

                tambao.template = ItemService.gI().getTemplate(templateId);
                tambao.quantity = rs.getInt("quantity");
                tambao.createTime = System.currentTimeMillis();
                tambao.max_value = rs.getInt("max_value");
                tambao.id_moc = id_moc;

                tambao.itemOptions.clear();
                String raw = rs.getString("item_options");
                Object parsed = jsonValue.parse(raw);
                if (parsed instanceof JSONArray) {
                    JSONArray arr = (JSONArray) parsed;
                    for (Object entry : arr) {
                        // ho tro [{id,param}] hoac [id,param] hoac chuoi JSON
                        addOptionFromFlexibleEntry(entry, tambao);
                    }
                }
                MOC_TAMBAO.add(tambao);
            }
            Logger.success("Load Moc Tam Bao thanh cong (" + MOC_TAMBAO.size() + ")");
        } catch (Exception e) {
            Logger.logException(TamBao.class, e);
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================
    private static Integer getNullableInt(ResultSet rs, String col) {
        try {
            int v = rs.getInt(col);
            return rs.wasNull() ? null : v;
        } catch (Exception e) {
            return null;
        }
    }

    private void addOptionsFromCompact(String compact, Item target) {
        // "30-1,77-50" -> (30,1) (77,50)
        String[] parts = compact.split(",");
        for (String p : parts) {
            p = p.trim();
            if (p.isEmpty()) {
                continue;
            }
            String[] kv = p.split("-");
            if (kv.length < 2) {
                continue;
            }
            int id = Integer.parseInt(kv[0].trim());
            int param = Integer.parseInt(kv[1].trim());
            target.itemOptions.add(new ItemOption(id, param));
        }
    }

    // chap nhan {id,param} hoac [id,param] hoac chuoi JSON tuong ung
    private void addOptionFromFlexibleEntry(Object entry, Item target) {
        JSONValue jv = new JSONValue();
        if (entry instanceof JSONObject) {
            JSONObject jo = (JSONObject) entry;
            int oid = toInt(jo.get("id"));
            int par = toInt(jo.get("param"));
            target.itemOptions.add(new ItemOption(oid, par));
            return;
        }
        if (entry instanceof JSONArray) {
            JSONArray pair = (JSONArray) entry;
            if (pair.size() >= 2) {
                int oid = toInt(pair.get(0));
                int par = toInt(pair.get(1));
                target.itemOptions.add(new ItemOption(oid, par));
            }
            return;
        }
        if (entry instanceof String) {
            String s = (String) entry;
            Object again = jv.parse(s);
            if (again != null && again != entry) addOptionFromFlexibleEntry(again, target);
        }
    }

    private int toInt(Object o) {
        if (o == null) return 0;
        if (o instanceof Number) return ((Number) o).intValue();
        return Integer.parseInt(String.valueOf(o));
    }

    // clone base item + set quantity; copy option theo template id/param
    private Item cloneWithQuantity(Item base, int quantity) {
        Item it = ItemService.gI().createNewItem(base.template.id, quantity);
        for (ItemOption io : base.itemOptions) {
            it.itemOptions.add(new ItemOption(io.optionTemplate.id, io.param));
        }
        return it;
    }

    // ghi lich su quay (luu JSON cua vat pham)
    public void insertHistory(Player pl, Item prize) {
        final String sql = "INSERT INTO history_tambao (id_player, item) VALUES (?, ?)";
        try (Connection con = ConnectDB.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setLong(1, pl.id);
            ps.setString(2, itemToJson(prize));
            ps.executeUpdate();
        } catch (Exception ignored) {
        }
    }

    // serialize item -> JSON de tien xem log
    private String itemToJson(Item it) {
        JSONObject obj = new JSONObject();
        obj.put("id", (int) it.template.id);
        obj.put("quantity", it.quantity);
        JSONArray opts = new JSONArray();
        for (ItemOption io : it.itemOptions) {
            JSONObject o = new JSONObject();
            o.put("id", io.optionTemplate.id);
            o.put("param", io.param);
            opts.add(o);
        }
        obj.put("options", opts);
        return obj.toJSONString();
    }
}
