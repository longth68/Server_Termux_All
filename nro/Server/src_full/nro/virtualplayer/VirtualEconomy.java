package nro.virtualplayer;

import java.util.ArrayList;
import java.util.List;

import nro.consignmentstore.ConsignItem;
import nro.consignmentstore.ConsignShopManager;
import nro.consignmentstore.ConsignShopService;
import nro.inventory.InventoryService;
import nro.server.Manager;
import nro.services.Service;
import nro.template.ItemTemplate;
import models.Item.Item;
import models.Item.ItemOption;
import models.Item.ItemService;
import Utils.Util;

/**
 * Quản lý kinh tế của Virtual Player.
 * PHASE 5 - Economy AI.
 * Quản lý vàng, nhặt đồ, bán rác, mua potion theo personality.
 * Đồ thường đến từ gameplay; đồ hiếm (ký gửi chợ Bông) là drop mô phỏng khi farm quái.
 */
public class VirtualEconomy {

    private final VirtualPlayer vp;
    private long lastTimeSell;
    private long lastTimeConsign;
    private long nextConsignDelay = 60000L + (long) (Math.random() * 180000);
    private long lastTimeLoot;
    private long nextLootDelay = 120000L + (long) (Math.random() * 300000);

    public VirtualEconomy(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Cộng vàng (từ drop, bán đồ, quest).
     */
    public void addGold(long amount) {
        if (vp.inventory == null) return;
        vp.inventory.gold += amount;
        vp.needs.satisfyGold(Math.min(20, amount / 100000f));
    }

    /**
     * Thêm item vào túi (từ drop/quest).
     */
    public boolean addItem(int tempId, int quantity) {
        try {
            Item item = ItemService.gI().createNewItem((short) tempId, quantity);
            if (item == null) return false;
            // Them truc tiep vao bag (khong qua addItemBag de tranh send packet)
            for (int i = 0; i < vp.inventory.itemsBag.size(); i++) {
                Item slot = vp.inventory.itemsBag.get(i);
                if (slot == null || !slot.isNotNullItem()) {
                    vp.inventory.itemsBag.set(i, item);
                    return true;
                }
            }
            return false;
        } catch (Exception e) {
            return false;
        }
    }

    /**
     * Bán vật phẩm rác trong túi để lấy vàng.
     * Chỉ bán item không phải trang bị (type > 2) và không phải item quan trọng.
     */
    public void sellTrash() {
        if (vp.inventory == null || vp.inventory.itemsBag == null) return;
        long now = System.currentTimeMillis();
        if (now - lastTimeSell < 120000) return;
        lastTimeSell = now;

        int scanned = 0;
        for (int i = 0; i < vp.inventory.itemsBag.size() && scanned < 20; i++) {
            Item it = vp.inventory.itemsBag.get(i);
            if (it == null || it.template == null) continue;
            scanned++;
            int tempId = it.template.id;
            if (it.template.type > 2 && it.template.goldSell > 0 && tempId != 457) {
                long gold = Math.min((long) it.template.goldSell * it.quantity, 5_000_000L);
                vp.inventory.gold += gold;
                vp.inventory.itemsBag.set(i, ItemService.gI().createItemNull());
            }
        }
    }

    /**
     * Kiểm tra xem túi có gần đầy không.
     */
    public boolean isBagNearlyFull() {
        if (vp.inventory == null || vp.inventory.itemsBag == null) return false;
        int empty = 0;
        for (Item it : vp.inventory.itemsBag) {
            if (it == null || it.template == null || it.template.id == -1) empty++;
        }
        return empty < 10;
    }

    /**
     * Kiểm tra xem có đủ vàng không (GREEDY cần dự trữ nhiều hơn).
     */
    public boolean hasEnoughGold(long need) {
        if (vp.inventory == null) return false;
        float reserve = 1.0f;
        if (vp.profile.hasPersonality(nro.virtualplayer.core.VirtualPersonality.GREEDY)) reserve = 2.0f;
        return vp.inventory.gold >= need * reserve;
    }

    /**
     * Kiểm tra xem còn đậu thần (457) không.
     */
    public boolean hasPotion() {
        if (vp.inventory == null || vp.inventory.itemsBag == null) return false;
        for (Item it : vp.inventory.itemsBag) {
            if (it != null && it.template != null && it.template.id == 457) {
                return true;
            }
        }
        return false;
    }

    public long getGold() {
        return vp.inventory != null ? vp.inventory.gold : 0;
    }

    // ===== CHỢ KÝ GỬI (CHỢ BÔNG) =====

    /**
     * Bot tự ký gửi vật phẩm hiếm trong túi lên chợ ký gửi.
     * Giá theo độ hiếm, cooldown ngẫu nhiên, tối đa 3 tin đăng mở cùng lúc, mỗi lần tối đa 2 món.
     */
    public void consignRareItems() {
        if (vp.inventory == null || vp.inventory.itemsBag == null) return;
        long now = System.currentTimeMillis();
        if (now - lastTimeConsign < nextConsignDelay) return;
        lastTimeConsign = now;
        nextConsignDelay = 180000L + (long) (Math.random() * 300000);

        try {
            int active = 0;
            for (ConsignItem ci : ConsignShopManager.gI().listItem) {
                if (ci != null && ci.player_sell == (int) vp.id && !ci.isBuy) active++;
            }
            if (active >= 3) return;

            int listed = 0;
            for (int i = 0; i < vp.inventory.itemsBag.size() && listed < 2; i++) {
                Item it = vp.inventory.itemsBag.get(i);
                if (!isConsignable(it)) continue;

                long price = estimatePrice(it.template);
                int goldSell = -1;
                int gemSell = -1;
                if (Util.isTrue(15, 100)) {
                    gemSell = (int) Math.max(1000, price / 2000);
                } else {
                    goldSell = (int) price;
                }
                List<ItemOption> opts = new ArrayList<>(it.itemOptions);
                ConsignShopManager.gI().listItem.add(new ConsignItem(
                        ConsignShopService.gI().getMaxId() + 1,
                        it.template.id,
                        (int) vp.id,
                        ConsignShopService.gI().getTabKiGui(it),
                        goldSell, gemSell, it.quantity,
                        System.currentTimeMillis(), opts, false));
                vp.inventory.itemsBag.set(i, ItemService.gI().createItemNull());
                listed++;
            }
        } catch (Exception ignored) {}
    }

    /**
     * Điều kiện ký gửi giống isDoKyGui(): ngọc rồng (14-20), type 6/14/15, hoặc có option 86/87.
     */
    private boolean isConsignable(Item it) {
        if (it == null || it.template == null || !it.isNotNullItem()) return false;
        int id = it.template.id;
        int type = it.template.type;
        if (id >= 14 && id <= 20) return true;
        if (type == 6 || type == 14 || type == 15) return true;
        try {
            for (ItemOption op : it.itemOptions) {
                if (op != null && op.optionTemplate != null
                        && (op.optionTemplate.id == 86 || op.optionTemplate.id == 87)) {
                    return true;
                }
            }
        } catch (Exception ignored) {}
        return false;
    }

    /**
     * Định giá theo độ hiếm: ngọc rồng đắt nhất, sau đó đến đồ hiếm type 14/15, rồi type 6. Jitter ±20%.
     */
    private long estimatePrice(ItemTemplate t) {
        long base;
        if (t.id >= 14 && t.id <= 20) {
            base = 800_000_000L + Util.nextInt(0, 700_000_000);
        } else if (t.type == 14 || t.type == 15) {
            base = 50_000_000L + Util.nextInt(0, 250_000_000);
        } else {
            base = 10_000_000L + Util.nextInt(0, 60_000_000);
        }
        base = base * (80 + Util.nextInt(0, 40)) / 100;
        return Math.min(base, 2_000_000_000L);
    }

    // ===== FARM MÔ PHỎNG =====

    /**
     * Khi đánh quái có cơ hội nhỏ "farm" được vật phẩm hiếm (đủ điều kiện ký gửi).
     * Cooldown 4-10 phút/lần, túi chứa tối đa 2 món hiếm để tránh dồn hàng.
     */
    public boolean maybeGainRareItem() {
        long now = System.currentTimeMillis();
        if (now - lastTimeLoot < nextLootDelay) return false;
        if (isBagNearlyFull()) return false;

        try {
            lastTimeLoot = now;
            int countRare = 0;
            for (Item it : vp.inventory.itemsBag) {
                if (isConsignable(it)) countRare++;
            }
            if (countRare >= 2) return false;

            List<ItemTemplate> pool = new ArrayList<>();
            for (ItemTemplate t : Manager.ITEM_TEMPLATES) {
                if (t == null || t.id <= 0) continue;
                if (t.type == 6 || t.type == 14 || t.type == 15 || (t.id >= 14 && t.id <= 20)) {
                    pool.add(t);
                }
            }
            if (pool.isEmpty()) return false;

            ItemTemplate pick = pool.get(Util.nextInt(0, pool.size() - 1));
            if (!addItem(pick.id, 1)) return false;
            nextLootDelay = 240000L + (long) (Math.random() * 360000);
            return true;
        } catch (Exception e) {
            return false;
        }
    }
}
