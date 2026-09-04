package nro.virtualplayer;

import nro.map.ItemMap;
import nro.player.Player;
import nro.services.Service;
import nro.template.ItemTemplate;
import nro.inventory.InventoryService;
import models.Item.Item;
import models.Item.ItemService;
import Utils.Util;
import java.util.ArrayList;
import java.util.List;

/**
 * Trang bị của Virtual Player.
 * PHASE 5 - Equipment.
 *
 * Bot dùng ĐÚNG hệ thống trang bị của Player thật:
 * - Item tạo từ ItemService (template load từ DB item_template)
 * - Equip qua InventoryService.itemBagToBody (tự swap đồ cũ vào bag,
 *   tự tính lại điểm qua Service.point)
 * - Filter template theo: type 0-5 (6 slot chính), gender phù hợp,
 *   strRequire <= power (putItemBody sẽ chặn nếu không đủ)
 *
 * KHÔNG hardcode item id — luôn lọc từ Manager.ITEM_TEMPLATES lúc runtime.
 */
public class VirtualEquipment {

    // 6 slot trang bị chính (type -> body slot 0-5)
    private static final int MIN_EQUIP_TYPE = 0;
    private static final int MAX_EQUIP_TYPE = 5;

    private final VirtualPlayer vp;
    private long lastUpgradeCheck;

    public VirtualEquipment(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Set đồ khởi đầu theo tier power của bot.
     * Gọi sau khi tạo bot (createBot / createBotFromSave).
     */
    public void giveStarterGear() {
        if (vp.nPoint == null || ManagerTemplates.templates().isEmpty()) return;
        long power = vp.nPoint.power;

        for (int type = MIN_EQUIP_TYPE; type <= MAX_EQUIP_TYPE; type++) {
            try {
                ItemTemplate best = pickTemplateForType(type, power);
                if (best != null) {
                    equipTemplate(best);
                }
            } catch (Exception ignored) {}
        }

        // Vài đậu thần dự phòng trong bag
        try {
            economy().addItem(457, 10 + Util.nextInt(0, 20));
        } catch (Exception ignored) {}
    }

    /**
     * Kiểm tra & thay đồ tốt hơn từ bag định kỳ (không tối ưu 100% -
     * có xác suất bỏ qua để giống người thật).
     */
    public void maybeUpgradeGear() {
        if (vp.inventory == null) return;
        long now = System.currentTimeMillis();
        if (now - lastUpgradeCheck < 120_000) return; // 2 phút kiểm tra một lần
        lastUpgradeCheck = now;

        // Người thật hay lười thay đồ / không nhận ra đồ tốt hơn
        if (!Util.isTrue(45, 100)) return;

        long power = vp.nPoint != null ? vp.nPoint.power : 0;

        for (int i = 0; i < vp.inventory.itemsBag.size(); i++) {
            Item bagItem = vp.inventory.itemsBag.get(i);
            if (bagItem == null || bagItem.template == null || !bagItem.isNotNullItem()) continue;
            int type = bagItem.template.type;
            if (type < MIN_EQUIP_TYPE || type > MAX_EQUIP_TYPE) continue;
            if (!canWear(bagItem.template, power)) continue;

            Item current = vp.inventory.itemsBody.get(type);
            if (current == null || !current.isNotNullItem()) {
                equipFromBag(i);
                break; // 1 lần mỗi check
            }
            // Chỉ thay nếu rõ ràng mạnh hơn (so strRequire, fallback level)
            if (isStrictlyBetter(bagItem.template, current.template)) {
                equipFromBag(i);
                break;
            }
        }
    }

    /**
     * Cơ hội tặng đồ cho Player thật (prompt section 17).
     * Điều kiện: relationship >= FRIEND, personality helpfulness, rate thấp.
     * Trả về true nếu đã tặng.
     */
    public boolean checkGiftOpportunity(Player target) {
        if (target == null || vp.zone == null || target.zone == null) return false;
        if (vp.profile == null || vp.memory == null) return false;

        // Quan hệ phải là bạn
        if (!vp.memory.isFriend(target.name)) return false;

        // Config gift_rate chặn tổng thể (0 = không bao giờ tặng)
        float giftRate = VirtualConfig.gI().giftRate;
        if (giftRate <= 0) return false;
        if (!Util.isTrue((int) (giftRate * 50), 100)) return false;

        // Personality quyết định: người ít giúp đỡ thì gần như không bao giờ tặng
        float helpfulness = vp.profile.getHelpfulness();
        if (!Util.isTrue((int) (helpfulness * 30), 100)) return false;

        // Tìm món đáng tặng trong bag: trang bị tốt hơn player đang mặc hoặc material hiếm
        Item gift = findGiftCandidate(target);
        if (gift == null) return false;

        // Drop xuống đất cho player nhặt (playerId = chỉ player đó nhặt được)
        try {
            ItemMap im = new ItemMap(vp.zone, gift.template.id, gift.quantity,
                    target.location.x + Util.nextInt(-20, 20), target.location.y, target.id);
            vp.zone.addItem(im);
        } catch (Exception e) {
            return false;
        }

        // Xoá khỏi bag
        removeOneFromBag(gift);

        // Chat + ghi nhớ quan hệ
        String[] lines = {
            "Ta không dùng món này, huynh nhận đi.",
            "Món này hợp với huynh hơn.",
            "Nhận làm quà nhé, đừng khách sáo."
        };
        String msg = vp.memory.pickChat(lines);
        if (vp.profile.hasPersonality(nro.virtualplayer.core.VirtualPersonality.QUIET)) {
            msg = null; // người ít nói: tặng lặng lẽ
        }
        if (msg != null) {
            try { Service.gI().chat(vp, msg); } catch (Exception ignored) {}
        }
        vp.memory.adjustRelation(target.name, 0); // giữ nguyên, chỉ đánh dấu tương tác
        return true;
    }

    // ===== INTERNAL =====

    /**
     * Chọn template phù hợp cho 1 slot type theo power.
     * Lấy top candidates rồi random → bot khác nhau có đồ khác nhau.
     */
    private ItemTemplate pickTemplateForType(int type, long power) {
        List<ItemTemplate> candidates = new ArrayList<>();
        for (ItemTemplate t : ManagerTemplates.templates()) {
            if (t.type < MIN_EQUIP_TYPE || t.type > MAX_EQUIP_TYPE) continue;
            if (t.type != type) continue;
            if (!canWear(t, power)) continue;
            candidates.add(t);
        }
        if (candidates.isEmpty()) return null;

        // Sắp xếp mạnh dần (strRequire đại diện tier)
        candidates.sort((a, b) -> Integer.compare(b.strRequire, a.strRequire));

        // Chọn trong top 40% mạnh nhất nhưng không vượt quá power quá nhiều
        int topN = Math.max(1, candidates.size() * 4 / 10);
        topN = Math.min(topN, 8);
        return candidates.get(Util.nextInt(0, topN - 1));
    }

    private boolean canWear(ItemTemplate t, long power) {
        // Gender: >=3 là mọi giới tính, ngược lại phải khớp
        if (t.gender < 3 && t.gender != vp.gender) return false;
        // Power requirement (điều kiện putItemBody cũng check)
        if (t.strRequire > power) return false;
        // Bỏ qua item sự kiện để tránh đồ lạ
        if (t.TypeEvent != 0) return false;
        return true;
    }

    private void equipTemplate(ItemTemplate t) {
        try {
            Item it = ItemService.gI().createNewItem(t.id);
            if (it == null || it.template == null) return;
            int bodySlot = it.template.type;
            if (bodySlot < 0 || bodySlot >= vp.inventory.itemsBody.size()) return;
            Item old = vp.inventory.itemsBody.get(bodySlot);
            vp.inventory.itemsBody.set(bodySlot, it);
            if (old != null && old.isNotNullItem()) {
                InventoryService.gI().addItemBag(vp, old);
            }
            try { nro.services.Service.gI().point(vp); } catch (Exception ignored) {}
        } catch (Exception ignored) {}
    }

    private void equipFromBag(int bagIndex) {
        try {
            if (bagIndex < 0 || bagIndex >= vp.inventory.itemsBag.size()) return;
            Item it = vp.inventory.itemsBag.get(bagIndex);
            if (it == null || !it.isNotNullItem()) return;
            int bodySlot = it.template.type;
            if (bodySlot < 0 || bodySlot >= vp.inventory.itemsBody.size()) return;
            Item old = vp.inventory.itemsBody.get(bodySlot);
            vp.inventory.itemsBody.set(bodySlot, it);
            vp.inventory.itemsBag.set(bagIndex, old != null ? old : ItemService.gI().createItemNull());
            try { nro.services.Service.gI().point(vp); } catch (Exception ignored) {}
        } catch (Exception ignored) {}
    }

    private int indexOfInBag(Item item) {
        for (int i = 0; i < vp.inventory.itemsBag.size(); i++) {
            if (vp.inventory.itemsBag.get(i) == item) return i;
        }
        return -1;
    }

    private boolean isStrictlyBetter(ItemTemplate a, ItemTemplate b) {
        if (a.strRequire > 0 || b.strRequire > 0) return a.strRequire > b.strRequire;
        return a.level > b.level;
    }

    /**
     * Tìm món quà hợp lý: trang bị mà player có thể dùng và mạnh hơn
     * thứ player đang mặc ở slot đó.
     */
    private Item findGiftCandidate(Player target) {
        if (vp.inventory == null) return null;
        Item best = null;
        int bestGain = 0;

        for (Item it : vp.inventory.itemsBag) {
            if (it == null || it.template == null || !it.isNotNullItem()) continue;
            int type = it.template.type;
            if (type < MIN_EQUIP_TYPE || type > MAX_EQUIP_TYPE) continue;
            if (it.template.TypeEvent != 0) continue;
            // Player phải mặc được (gender)
            if (it.template.gender < 3 && it.template.gender != target.gender) continue;
            // Player phải đủ power mặc
            if (target.nPoint == null || it.template.strRequire > target.nPoint.power) continue;

            Item current = target.inventory != null ? target.inventory.itemsBody.get(type) : null;
            int gain;
            if (current == null || !current.isNotNullItem()) {
                gain = it.template.strRequire + 1;
            } else {
                gain = it.template.strRequire - current.template.strRequire;
            }
            if (gain > bestGain) {
                bestGain = gain;
                best = it;
            }
        }
        return bestGain > 0 ? best : null;
    }

    private void removeOneFromBag(Item item) {
        try {
            for (int i = 0; i < vp.inventory.itemsBag.size(); i++) {
                if (vp.inventory.itemsBag.get(i) == item) {
                    vp.inventory.itemsBag.set(i, ItemService.gI().createItemNull());
                    return;
                }
            }
        } catch (Exception ignored) {}
    }

    private VirtualEconomy economy() {
        return new VirtualEconomy(vp);
    }

    /** Access trung gian đến danh sách template (tránh import trực tiếp nhiều nơi). */
    private static class ManagerTemplates {
        static List<ItemTemplate> templates() {
            try {
                return nro.server.Manager.ITEM_TEMPLATES;
            } catch (Exception e) {
                return new ArrayList<>();
            }
        }
    }
}